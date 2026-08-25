<?php

namespace Tests\Feature;

use App\Models\PageBuilderToken;
use App\Support\PageBuilderTokens;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Claude access" in the Page Builder — issuing and revoking the tokens a
 * claude.ai Project connects with.
 */
class PageBuilderTokenAdminTest extends TestCase
{
    use RefreshDatabase;

    private function asSuperAdmin(): self
    {
        return $this->withSession(['cms_authenticated' => true, 'cms_super_admin' => true]);
    }

    public function test_the_screen_shows_the_connector_url_and_the_generate_form(): void
    {
        $response = $this->asSuperAdmin()->get(route('admin.pages.tokens.index'))->assertOk();

        $response->assertSee('Claude access');
        $response->assertSee(rtrim((string) config('app.url'), '/').'/mcp');
        $response->assertSee('Generate token');
        $response->assertSee('No tokens yet', false);
    }

    public function test_a_standard_admin_cannot_manage_tokens(): void
    {
        $this->withSession(['cms_authenticated' => true])
            ->get(route('admin.pages.tokens.index'))
            ->assertRedirect(route('admin.pages.index'));

        $this->withSession(['cms_authenticated' => true])
            ->post(route('admin.pages.tokens.store'), ['label' => 'Sneaky', 'days' => 15])
            ->assertRedirect(route('admin.pages.index'));

        $this->assertSame(0, PageBuilderToken::count());
    }

    public function test_a_signed_out_visitor_is_sent_to_the_login(): void
    {
        $this->get(route('admin.pages.tokens.index'))->assertRedirect(route('admin.login'));
    }

    public function test_generating_a_token_shows_the_plaintext_exactly_once(): void
    {
        $response = $this->asSuperAdmin()->post(route('admin.pages.tokens.store'), [
            'label' => 'Claude project — website pages',
            'days' => 15,
        ])->assertRedirect(route('admin.pages.tokens.index'));

        $fresh = $response->getSession()->get('page_builder_fresh_token');
        $this->assertNotNull($fresh);
        $this->assertStringStartsWith(PageBuilderTokens::PREFIX, $fresh['token']);
        $this->assertSame(15, $fresh['days']);

        $model = PageBuilderToken::sole();
        $this->assertSame('Claude project — website pages', $model->label);
        $this->assertTrue($model->isUsable());
        $this->assertTrue($model->expires_at->isSameDay(now()->addDays(15)));

        // Only the hash is kept, so the plaintext is unrecoverable afterwards.
        $this->assertNotSame($fresh['token'], $model->token_hash);
        $this->assertSame(hash('sha256', $fresh['token']), $model->token_hash);

        // Exactly once: the redirect target still holds the flash and shows it…
        $this->get(route('admin.pages.tokens.index'))
            ->assertOk()
            ->assertSee($fresh['token'], false);

        // …and the very next visit does not, because the flash is spent.
        $this->get(route('admin.pages.tokens.index'))
            ->assertOk()
            ->assertDontSee($fresh['token'], false);
    }

    public function test_an_unsupported_lifetime_is_rejected(): void
    {
        $this->asSuperAdmin()
            ->post(route('admin.pages.tokens.store'), ['label' => 'Forever', 'days' => 3650])
            ->assertSessionHasErrors('days');

        $this->assertSame(0, PageBuilderToken::count());
    }

    public function test_a_token_can_be_revoked_from_the_list(): void
    {
        $token = app(PageBuilderTokens::class)->issue('To be revoked', 15);
        $id = $token['model']->id;

        $this->asSuperAdmin()
            ->delete(route('admin.pages.tokens.destroy', $id))
            ->assertRedirect(route('admin.pages.tokens.index'));

        $this->assertNotNull(PageBuilderToken::find($id)->revoked_at);
        $this->assertSame('revoked', PageBuilderToken::find($id)->status());
        $this->assertNull(app(PageBuilderTokens::class)->verify($token['token']));
    }

    public function test_the_list_shows_a_hint_but_never_the_whole_token(): void
    {
        $issued = app(PageBuilderTokens::class)->issue('Visible in list', 30);

        $this->asSuperAdmin()
            ->get(route('admin.pages.tokens.index'))
            ->assertOk()
            ->assertSee('Visible in list')
            ->assertSee('Active')
            ->assertSee($issued['model']->hint)
            ->assertDontSee($issued['token']);
    }

    public function test_the_page_builder_index_links_to_claude_access_for_a_super_admin(): void
    {
        $this->asSuperAdmin()
            ->get(route('admin.pages.index'))
            ->assertOk()
            ->assertSee(route('admin.pages.tokens.index'), false);
    }

    /**
     * Separate test rather than a second request in the one above: withSession
     * merges into the session already built up, so a super-admin request
     * earlier in the same test would leave cms_super_admin set and this would
     * pass for the wrong reason.
     */
    public function test_the_page_builder_index_hides_claude_access_from_a_standard_admin(): void
    {
        $this->withSession(['cms_authenticated' => true])
            ->get(route('admin.pages.index'))
            ->assertOk()
            ->assertDontSee(route('admin.pages.tokens.index'), false);
    }
}
