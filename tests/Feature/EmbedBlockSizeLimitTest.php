<?php

namespace Tests\Feature;

use App\Support\BriefPageStore;
use App\Support\BriefSchema;
use App\Support\PageBuilderWriter;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * An embed block used to be capped with a bare mb_substr, so anything past the
 * limit was cut at that exact character and the save reported success. The cut
 * lands mid-tag, which does not shorten the section so much as break it: a live
 * page kept its <style> and its row of popovertarget buttons while every dialog
 * those buttons pointed at, and the closing <script>, had been trimmed away.
 */
class EmbedBlockSizeLimitTest extends TestCase
{
    private string $storePath;

    private ?string $original = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storePath = storage_path('app/brief-pages.json');
        $this->original = is_file($this->storePath) ? file_get_contents($this->storePath) : null;
    }

    protected function tearDown(): void
    {
        if ($this->original !== null) {
            file_put_contents($this->storePath, $this->original);
        }

        parent::tearDown();
    }

    /** An embed block of exactly $length characters, ending in a closing tag. */
    private function blockOf(int $length): string
    {
        $tail = '<button popovertarget="d1">open</button><div id="d1" popover>dialog</div>';
        $filler = str_repeat('x', max(0, $length - mb_strlen($tail)));

        return $filler.$tail;
    }

    private function saveEmbed(string $html)
    {
        $page = app(BriefPageStore::class)->find('europe');

        return $this->withSession(['cms_authenticated' => true, 'cms_super_admin' => true])
            ->postJson(route('admin.pages.save', 'europe'), [
                'title' => $page['title'] ?? 'Europe',
                'visible' => true,
                'path' => $page['path'] ?? '/europe',
                'layout' => [[
                    'id' => 'r1', 'cols' => [[
                        'id' => 'c1', 'span' => 12, 'blocks' => [[
                            'id' => 'b1', 'type' => 'embed', 'visible' => true, 'data' => ['html' => $html],
                        ]],
                    ]],
                ]],
            ]);
    }

    public function test_a_large_block_keeps_the_markup_at_its_very_end(): void
    {
        $html = $this->blockOf(BriefSchema::CODE_MAX - 100);

        $this->saveEmbed($html)->assertOk();

        // The end of the block is what a truncation eats first, and it is where
        // the dialogs and the closing <script> of a generated section live.
        $rendered = $this->get('/europe')->assertOk()->getContent();
        $this->assertStringContainsString('<div id="d1" popover>dialog</div>', $rendered);
        $this->assertStringContainsString('popovertarget="d1"', $rendered);
    }

    public function test_an_oversized_block_is_refused_rather_than_cut(): void
    {
        $before = file_get_contents($this->storePath);

        $response = $this->saveEmbed($this->blockOf(BriefSchema::CODE_MAX + 1))
            ->assertStatus(422)
            ->assertJsonPath('ok', false);

        $this->assertStringContainsString('too large to save', (string) $response->json('message'));
        $this->assertStringContainsString('Split the section', (string) $response->json('message'));

        // Refused means refused: nothing half-written to the store.
        $this->assertSame($before, file_get_contents($this->storePath));
    }

    public function test_the_mcp_writer_refuses_an_oversized_block_too(): void
    {
        $writer = app(PageBuilderWriter::class);

        try {
            $writer->create([
                'title' => 'Oversized draft',
                'layout' => [[
                    'id' => 'r1', 'cols' => [[
                        'id' => 'c1', 'span' => 12, 'blocks' => [[
                            'id' => 'b1', 'type' => 'embed', 'visible' => true,
                            'data' => ['html' => $this->blockOf(BriefSchema::CODE_MAX + 1)],
                        ]],
                    ]],
                ]],
            ], 'token#test');
            $this->fail('An oversized embed block should not be writable over MCP.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString(
                'too large to save',
                implode(' ', $e->validator->errors()->all())
            );
        }
    }

    public function test_the_studio_never_lets_an_empty_preview_wipe_a_pasted_block(): void
    {
        $studio = $this->withSession(['cms_authenticated' => true, 'cms_super_admin' => true])
            ->get(route('admin.pages.studio', 'europe'))
            ->assertOk()
            ->getContent();

        // syncEmbed writes the canvas back into the textarea that actually gets
        // saved. renderBlock is debounced, so the canvas is empty for a moment
        // after a paste, and writing that back blanked the block.
        $this->assertStringContainsString(
            "if(next.trim()==='' && ta.value.trim()!==''){ return; }",
            $studio,
            'syncEmbed must refuse to overwrite real markup with an empty preview.'
        );

        // And a blank embed block never goes to the server unannounced.
        $this->assertStringContainsString('saving now would store it blank', $studio);
    }
}
