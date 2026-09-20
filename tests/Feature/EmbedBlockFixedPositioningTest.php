<?php

namespace Tests\Feature;

use App\Support\BriefPageStore;
use Tests\TestCase;

/**
 * An "AI / Embed" block is pasted markup rendered verbatim, so it may position
 * its own content fixed -- an overlay, a sticky bar, a :target lightbox.
 *
 * Nothing in the save/render pipeline touches that markup, but the page it
 * lands on does: <main> plays an entry animation on transform, and an element
 * animating transform is the containing block for its position:fixed
 * descendants. With a forwards fill that role never ends, so a fixed overlay
 * was laid out against <main> instead of the viewport and then clipped out of
 * sight by .odp-file-page's overflow-x: clip.
 */
class EmbedBlockFixedPositioningTest extends TestCase
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

    /** Save a single block of $type onto $slug through the studio endpoint. */
    private function savePageWithBlock(string $slug, string $type, array $data): void
    {
        $page = app(BriefPageStore::class)->find($slug);
        $this->assertNotNull($page, "fixture page [$slug] is missing");

        $this->withSession(['cms_authenticated' => true, 'cms_super_admin' => true])
            ->postJson(route('admin.pages.save', $slug), [
                'title' => $page['title'] ?? 'Untitled',
                'visible' => true,
                'path' => $page['path'] ?? "/briefs/{$slug}",
                'layout' => [[
                    'id' => 'r1', 'cols' => [[
                        'id' => 'c1', 'span' => 12, 'blocks' => [[
                            'id' => 'b1', 'type' => $type, 'visible' => true, 'data' => $data,
                        ]],
                    ]],
                ]],
            ])->assertOk();
    }

    public function test_the_entry_animation_releases_main_once_it_has_played(): void
    {
        $css = file_get_contents(public_path('styles.css'));

        $this->assertMatchesRegularExpression(
            '/animation: page-content-enter \d+ms [^;]*backwards;/',
            $css,
            'The entry animation must not hold <main> with a forwards fill.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/animation: page-content-enter \d+ms [^;]*\b(both|forwards);/',
            $css,
            'A forwards fill never ends, so <main> would stay the containing block for position:fixed content.'
        );

        // Belt and braces for the 420ms the animation is actually running:
        // pasted markup is fixed from the first frame, unlike the site's own
        // fixed UI, which lives outside <main> or re-parents to <body> on open.
        $this->assertStringContainsString('@keyframes page-content-fade', $css);
        $this->assertStringContainsString(
            'html:not(.is-vt-nav) body.odp-has-embed:not(.cms-editing) main {',
            $css
        );
    }

    public function test_a_page_carrying_an_embed_block_opts_out_of_the_lift(): void
    {
        $this->savePageWithBlock('europe', 'embed', [
            'html' => '<div id="ov" style="position:fixed;inset:0">overlay</div>',
        ]);

        $html = $this->get('/europe')->assertOk()->getContent();

        $this->assertStringContainsString('odp-has-embed', $html);
        // The pasted markup itself still reaches the browser untouched.
        $this->assertStringContainsString('style="position:fixed;inset:0"', $html);
    }

    public function test_a_page_without_an_embed_block_keeps_the_lift(): void
    {
        $this->savePageWithBlock('europe', 'heading', ['title' => 'Just a heading']);

        $html = $this->get('/europe')->assertOk()->getContent();

        $this->assertStringNotContainsString('odp-has-embed', $html);
    }

    public function test_pasting_a_whole_document_keeps_what_lives_outside_its_body(): void
    {
        $studio = $this->withSession(['cms_authenticated' => true, 'cms_super_admin' => true])
            ->get(route('admin.pages.studio', 'europe'))
            ->assertOk()
            ->getContent();

        // Slicing the paste down to <body> dropped the section's own <style>,
        // font <link> and <script src> along with any trailing <script>.
        $this->assertStringNotContainsString(
            "s.match(/<body[^>]*>([\\s\\S]*?)<\\/body>/i)",
            $studio,
            'cleanPastedCode must not reduce a pasted document to its <body>.'
        );
        // Head-only tags still go, or they would render as stray text.
        $this->assertStringContainsString('<title[^>]*>[\s\S]*?<\/title>', $studio);
        $this->assertStringContainsString('<(meta|base)\b[^>]*>', $studio);
    }
}
