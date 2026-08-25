<?php

namespace Tests\Feature;

use App\Models\PageBuilderToken;
use App\Support\BriefPageStore;
use App\Support\PageBuilderTokens;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The MCP server a claude.ai Project connects to.
 *
 * Two behaviours matter more than the rest and are easy to regress:
 *   - The server must NEVER answer 401. Claude reads a 401 as "start OAuth
 *     discovery", and with no authorization server here the connector then fails
 *     with an unhelpful "couldn't reach the MCP server" instead of surfacing a
 *     bad token. Auth failures have to arrive as tool errors.
 *   - initialize and tools/list must work with no token at all, or the connector
 *     cannot be added in the first place.
 */
class PageBuilderMcpTest extends TestCase
{
    use RefreshDatabase;

    private string $storePath;

    private ?string $storeBackup = null;

    protected function setUp(): void
    {
        parent::setUp();

        // These tests write real pages, so snapshot the store and put it back.
        $this->storePath = storage_path('app/brief-pages.json');
        $this->storeBackup = is_file($this->storePath)
            ? (string) file_get_contents($this->storePath)
            : null;

        config(['page_api.mcp.enabled' => true, 'page_api.drafts_only' => true]);
    }

    protected function tearDown(): void
    {
        if ($this->storeBackup !== null) {
            file_put_contents($this->storePath, $this->storeBackup);
        } elseif (is_file($this->storePath)) {
            unlink($this->storePath);
        }

        parent::tearDown();
    }

    private function rpc(string $method, array $params = [], array $headers = [], int $id = 1)
    {
        return $this->withHeaders($headers)->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => $method,
            'params' => $params,
        ]);
    }

    private function freshToken(int $days = 15): string
    {
        return app(PageBuilderTokens::class)->issue('Test project', $days)['token'];
    }

    /** @return array<string, mixed> the structuredContent of a tools/call result */
    private function tool(string $tool, array $args, ?string $token = null): array
    {
        $token ??= $this->freshToken();
        $response = $this->rpc('tools/call', ['name' => $tool, 'arguments' => $args + ['token' => $token]]);
        $response->assertOk();

        return $response->json('result');
    }

    /* ───────────────────────── Transport ───────────────────────── */

    public function test_initialize_works_with_no_token_and_advertises_tools(): void
    {
        $response = $this->rpc('initialize', ['protocolVersion' => '2025-11-25'])->assertOk();

        $this->assertSame('2.0', $response->json('jsonrpc'));
        $this->assertSame('2025-11-25', $response->json('result.protocolVersion'));
        $this->assertArrayHasKey('tools', $response->json('result.capabilities'));
        $this->assertSame('onedegree-page-builder', $response->json('result.serverInfo.name'));
        $this->assertStringContainsString('hidden', (string) $response->json('result.instructions'));
    }

    public function test_an_unknown_protocol_version_is_answered_with_a_version_we_support(): void
    {
        $response = $this->rpc('initialize', ['protocolVersion' => '1999-01-01'])->assertOk();

        $this->assertSame('2025-11-25', $response->json('result.protocolVersion'));
    }

    public function test_an_unsupported_protocol_version_header_is_a_400(): void
    {
        $this->rpc('ping', [], ['MCP-Protocol-Version' => 'not-a-version'])->assertStatus(400);
        $this->rpc('ping', [], ['MCP-Protocol-Version' => '2025-11-25'])->assertOk();
    }

    public function test_tools_list_works_with_no_token_so_the_connector_can_be_added(): void
    {
        $tools = $this->rpc('tools/list')->assertOk()->json('result.tools');

        $names = array_column($tools, 'name');
        foreach (['list_pages', 'get_page', 'list_block_types', 'get_block_schema',
            'create_page', 'update_page', 'append_rows', 'duplicate_page', 'delete_draft'] as $expected) {
            $this->assertContains($expected, $names);
        }

        // Every tool must describe its input, or the model cannot call it.
        foreach ($tools as $tool) {
            $this->assertSame('object', $tool['inputSchema']['type']);
            $this->assertArrayHasKey('token', $tool['inputSchema']['properties']);
        }
    }

    public function test_a_notification_is_acknowledged_with_202_and_no_body(): void
    {
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'])
            ->assertStatus(202)
            ->assertNoContent(202);
    }

    public function test_the_get_stream_is_declined_with_405_not_an_error_page(): void
    {
        $this->getJson('/mcp')->assertStatus(405)->assertJsonPath('error.code', -32601);
    }

    public function test_an_unknown_method_is_a_jsonrpc_error_not_an_http_error(): void
    {
        $this->rpc('does/not/exist')->assertOk()->assertJsonPath('error.code', -32601);
    }

    public function test_a_foreign_origin_is_refused(): void
    {
        $this->rpc('ping', [], ['Origin' => 'https://evil.example.com'])->assertStatus(403);
        $this->rpc('ping', [], ['Origin' => 'https://claude.ai'])->assertOk();
    }

    public function test_the_endpoint_can_be_switched_off(): void
    {
        config(['page_api.mcp.enabled' => false]);

        $this->rpc('initialize')->assertNotFound();
    }

    /* ───────────────────────── Auth ───────────────────────── */

    public function test_a_missing_token_is_a_tool_error_never_a_401(): void
    {
        $response = $this->rpc('tools/call', ['name' => 'list_pages', 'arguments' => []]);

        // The status is the point of this test: a 401 would send Claude into an
        // OAuth discovery flow that this site cannot answer.
        $response->assertOk();
        $this->assertTrue($response->json('result.isError'));
        $this->assertStringContainsString('/admin/pages', $response->json('result.content.0.text'));
    }

    public function test_an_expired_token_is_refused(): void
    {
        $token = $this->freshToken();
        PageBuilderToken::query()->update(['expires_at' => now()->subDay()]);

        $result = $this->rpc('tools/call', ['name' => 'list_pages', 'arguments' => ['token' => $token]])
            ->assertOk()->json('result');

        $this->assertTrue($result['isError']);
    }

    public function test_a_revoked_token_is_refused(): void
    {
        $token = $this->freshToken();
        app(PageBuilderTokens::class)->revoke(PageBuilderToken::first()->id);

        $result = $this->rpc('tools/call', ['name' => 'list_pages', 'arguments' => ['token' => $token]])
            ->assertOk()->json('result');

        $this->assertTrue($result['isError']);
    }

    public function test_a_token_supplied_as_an_authorization_header_is_accepted(): void
    {
        $token = $this->freshToken();

        $result = $this->rpc(
            'tools/call',
            ['name' => 'list_pages', 'arguments' => []],
            ['Authorization' => 'Bearer '.$token],
        )->assertOk()->json('result');

        $this->assertFalse($result['isError']);
        $this->assertNotEmpty($result['structuredContent']['pages']);
    }

    public function test_using_a_token_records_that_it_was_used(): void
    {
        $token = $this->freshToken();
        $this->tool('list_pages', [], $token);

        $model = PageBuilderToken::first();
        $this->assertNotNull($model->last_used_at);
        $this->assertSame(1, $model->use_count);
    }

    /* ───────────────────────── Tools ───────────────────────── */

    public function test_the_block_vocabulary_is_discoverable_and_excludes_payment(): void
    {
        $types = $this->tool('list_block_types', [])['structuredContent']['block_types'];
        $names = array_column($types, 'type');

        $this->assertContains('hero', $names);
        $this->assertNotContains('payment', $names, 'The payment block must never be offered to a connector.');

        $schema = $this->tool('get_block_schema', ['types' => ['hero', 'nope']])['structuredContent'];
        $this->assertArrayHasKey('hero', $schema['block_types']);
        $this->assertNotEmpty($schema['block_types']['hero']['fields']);
        $this->assertSame(['nope'], $schema['unknown_types']);
    }

    public function test_a_created_page_is_a_hidden_draft_and_404s_for_the_public(): void
    {
        $result = $this->tool('create_page', [
            'title' => 'Mcp Draft Page',
            'meta_description' => 'Written over MCP.',
            'layout' => [[
                'cols' => [[
                    'span' => 12,
                    'blocks' => [['type' => 'heading', 'data' => ['heading' => 'Hello from MCP']]],
                ]],
            ]],
        ]);

        $page = $result['structuredContent']['page'];

        $this->assertFalse($result['isError']);
        $this->assertFalse($page['visible']);
        $this->assertSame('mcp-draft-page', $page['slug']);
        $this->assertSame('Written over MCP.', $page['meta_description']);
        $this->assertNotEmpty($page['page_title']);
        $this->assertSame('Hello from MCP', $page['layout'][0]['cols'][0]['blocks'][0]['data']['heading']);

        $this->get($page['path'])->assertNotFound();
    }

    /**
     * The draft has to actually render, not merely be stored: a block written
     * over MCP goes through the same blade partials as one built in the studio,
     * and a bad field would only surface here.
     */
    public function test_an_mcp_authored_page_renders_for_a_super_admin(): void
    {
        $page = $this->tool('create_page', [
            'title' => 'Mcp Rendered Page',
            'layout' => [
                ['width' => 'full', 'cols' => [['span' => 12, 'blocks' => [
                    ['type' => 'hero', 'data' => ['eyebrow' => 'Destination brief', 'title' => 'Rendered Heading', 'copy' => 'Intro paragraph here.']],
                ]]]],
                ['cols' => [
                    ['span' => 6, 'blocks' => [['type' => 'callout', 'data' => ['label' => 'Left note', 'body' => 'Half width.']]]],
                    ['span' => 6, 'blocks' => [['type' => 'callout', 'data' => ['label' => 'Right note', 'body' => 'Other half.']]]],
                ]],
                ['cols' => [['span' => 12, 'blocks' => [
                    ['type' => 'brief_cards', 'data' => ['label' => 'What to weigh', 'cards' => [
                        ['title' => 'Total cost', 'body' => 'Tuition plus living.', 'level' => 'high', 'tags' => [['text' => 'budget']]],
                    ]]],
                ]]]],
            ],
        ])['structuredContent']['page'];

        $this->assertSame(4, $page['blocks']);

        $rendered = $this->withSession(['cms_authenticated' => true, 'cms_super_admin' => true])
            ->get($page['path'])
            ->assertOk();

        $rendered->assertSee('Rendered Heading', false);
        $rendered->assertSee('Left note', false);
        $rendered->assertSee('Right note', false);
        $rendered->assertSee('Total cost', false);
        $rendered->assertSee('budget', false);
    }

    public function test_a_payment_block_is_refused_over_mcp(): void
    {
        $result = $this->tool('create_page', [
            'title' => 'Mcp Payment Attempt',
            'layout' => [['cols' => [['span' => 12, 'blocks' => [['type' => 'payment', 'data' => []]]]]]],
        ]);

        $this->assertTrue($result['isError']);
        $this->assertNull(app(BriefPageStore::class)->find('mcp-payment-attempt'));
    }

    public function test_seo_and_path_can_be_set_and_reserved_paths_fall_back(): void
    {
        $ok = $this->tool('create_page', [
            'title' => 'Mcp Seo Page',
            'path' => '/study-in-ireland',
            'page_title' => 'Study in Ireland — costs and intakes',
            'meta_description' => 'Fees, intakes and post-study work rights.',
        ])['structuredContent']['page'];

        $this->assertSame('/study-in-ireland', $ok['path']);
        $this->assertSame('Study in Ireland — costs and intakes', $ok['page_title']);

        $reserved = $this->tool('create_page', [
            'title' => 'Mcp Reserved Page',
            'path' => '/admin/pages',
        ])['structuredContent']['page'];

        $this->assertSame('/briefs/mcp-reserved-page', $reserved['path']);
    }

    /**
     * /mcp is a live route, so a page claiming that path would be unreachable —
     * and could be mistaken for the connector being broken. Same for the other
     * reserved prefixes.
     */
    public function test_a_page_cannot_claim_a_reserved_path(): void
    {
        foreach (['/mcp', '/admin', '/admin/pages', '/storage/x', '/login'] as $index => $reserved) {
            $page = $this->tool('create_page', [
                'title' => 'Mcp Reserved Attempt '.$index,
                'path' => $reserved,
            ])['structuredContent']['page'];

            $this->assertSame(
                '/briefs/'.$page['slug'],
                $page['path'],
                "A page must not be able to take over {$reserved}.",
            );
        }
    }

    public function test_a_draft_can_be_updated_and_rows_appended(): void
    {
        $slug = $this->tool('create_page', [
            'title' => 'Mcp Growing Page',
            'layout' => [['cols' => [['span' => 12, 'blocks' => [['type' => 'heading', 'data' => ['heading' => 'One']]]]]]],
        ])['structuredContent']['page']['slug'];

        $updated = $this->tool('update_page', [
            'slug' => $slug,
            'meta_description' => 'Now with a summary.',
        ])['structuredContent']['page'];
        $this->assertSame('Now with a summary.', $updated['meta_description']);
        $this->assertCount(1, $updated['layout'], 'Omitting layout must leave it alone.');

        $appended = $this->tool('append_rows', [
            'slug' => $slug,
            'rows' => [['cols' => [['span' => 12, 'blocks' => [['type' => 'heading', 'data' => ['heading' => 'Two']]]]]]],
        ])['structuredContent']['page'];

        $this->assertCount(2, $appended['layout']);
        $this->assertSame('Two', $appended['layout'][1]['cols'][0]['blocks'][0]['data']['heading']);
        $this->assertFalse($appended['visible']);
    }

    public function test_a_live_page_cannot_be_edited_but_can_be_duplicated(): void
    {
        $store = app(BriefPageStore::class);
        $slug = $this->tool('create_page', ['title' => 'Mcp Live Page'])['structuredContent']['page']['slug'];
        $store->setVisibility($slug, true);

        $refused = $this->tool('update_page', ['slug' => $slug, 'title' => 'Hijacked']);
        $this->assertTrue($refused['isError']);
        $this->assertSame('Mcp Live Page', $store->find($slug)['title']);

        $copy = $this->tool('duplicate_page', ['slug' => $slug]);
        $this->assertFalse($copy['isError']);
        $this->assertFalse($copy['structuredContent']['page']['visible']);
    }

    public function test_a_draft_can_be_deleted_but_a_live_page_cannot(): void
    {
        $store = app(BriefPageStore::class);

        $draft = $this->tool('create_page', ['title' => 'Mcp Throwaway'])['structuredContent']['page']['slug'];
        $this->assertFalse($this->tool('delete_draft', ['slug' => $draft])['isError']);
        $this->assertNull($store->find($draft));

        $live = $this->tool('create_page', ['title' => 'Mcp Keep Me'])['structuredContent']['page']['slug'];
        $store->setVisibility($live, true);
        $this->assertTrue($this->tool('delete_draft', ['slug' => $live])['isError']);
        $this->assertNotNull($store->find($live));
    }

    public function test_a_missing_page_and_an_unknown_tool_are_readable_tool_errors(): void
    {
        $missing = $this->tool('get_page', ['slug' => 'no-such-page']);
        $this->assertTrue($missing['isError']);
        $this->assertStringContainsString('No page with that slug', $missing['content'][0]['text']);

        $unknown = $this->tool('nonexistent_tool', []);
        $this->assertTrue($unknown['isError']);
    }

    public function test_a_missing_required_field_is_a_readable_tool_error(): void
    {
        $result = $this->tool('create_page', []);

        $this->assertTrue($result['isError']);
        $this->assertStringContainsString('title', $result['content'][0]['text']);
    }
}
