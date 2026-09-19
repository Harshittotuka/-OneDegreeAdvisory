<?php

namespace Tests\Feature;

use App\Support\BriefPageStore;
use Tests\TestCase;

/**
 * Changing a page's URL in the Page Builder. A CMS page is served by the
 * fallback route, so a path an application route already answers can never
 * reach the page — those saves used to be accepted and silently do nothing.
 */
class PageBuilderUrlPathTest extends TestCase
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

    /** Save $slug through the studio endpoint with a new URL path. */
    private function saveWithPath(string $slug, string $path)
    {
        $page = app(BriefPageStore::class)->find($slug);
        $this->assertNotNull($page, "fixture page [$slug] is missing");

        return $this->withSession(['cms_authenticated' => true, 'cms_super_admin' => true])
            ->postJson(route('admin.pages.save', $slug), [
                'title' => $page['title'] ?? 'Untitled',
                'visible' => true,
                'path' => $path,
                'layout' => $page['layout'] ?? [],
                'page_title' => $page['page_title'] ?? '',
                'meta_description' => $page['meta_description'] ?? '',
            ]);
    }

    public function test_a_free_url_is_saved_and_serves_the_page(): void
    {
        $this->saveWithPath('test', '/destination-canada')
            ->assertOk()
            ->assertJsonPath('path', '/destination-canada')
            ->assertJsonMissingPath('path_message');

        $this->assertSame('/destination-canada', app(BriefPageStore::class)->find('test')['path']);
        $this->get('/destination-canada')->assertOk();
    }

    public function test_a_url_an_application_route_answers_is_refused_with_a_reason(): void
    {
        $this->saveWithPath('test', '/destination-canada')->assertOk();

        // A live page, a route with a parameter, and a redirect route: none of
        // these ever reach the fallback, so none may be handed to a CMS page.
        foreach (['/statement-of-purpose', '/courses/mba', '/countries/canada', '/packages'] as $taken) {
            $this->saveWithPath('test', $taken)
                ->assertOk()
                ->assertJsonPath('path', '/destination-canada')
                ->assertJsonPath('path_message', fn ($m) => is_string($m) && str_contains($m, '/destination-canada'));
        }

        $this->assertSame('/destination-canada', app(BriefPageStore::class)->find('test')['path']);
    }

    public function test_a_page_may_be_moved_back_to_its_own_briefs_url(): void
    {
        $this->saveWithPath('test', '/destination-canada')->assertOk();

        // /briefs/{slug} is a real route, but it is this page's own, so keeping
        // or returning to it is not a collision.
        $this->saveWithPath('test', '/briefs/test')
            ->assertOk()
            ->assertJsonPath('path', '/briefs/test');
    }

    public function test_moving_a_seeded_page_redirects_its_original_url(): void
    {
        $this->saveWithPath('wednesday-briefings', '/wednesday-intel')
            ->assertOk()
            ->assertJsonPath('path', '/wednesday-intel');

        $this->get('/wednesday-intel')->assertOk();

        // The original URL is a hardcoded route that keeps answering, so it has
        // to forward rather than go on serving the page at its old address.
        $this->get('/wednesday-briefings')->assertRedirect('/wednesday-intel');
    }

    public function test_a_briefs_url_that_is_not_the_pages_own_slug_still_serves_it(): void
    {
        // The live case: an editor whose page ended up with slug "scholarship-4"
        // pointed it at /briefs/scholarship. /briefs/{slug} used to look the slug
        // up directly, so the URL they had just been told was saved 404'd.
        $store = app(BriefPageStore::class);
        $page = $store->find('test');
        $page['slug'] = 'scholarship-4';
        $page['visible'] = true;
        $store->save($page, 'test');

        $this->saveWithPath('scholarship-4', '/briefs/scholarship')
            ->assertOk()
            ->assertJsonPath('path', '/briefs/scholarship')
            ->assertJsonMissingPath('path_message');

        $this->get('/briefs/scholarship')->assertOk();
    }

    public function test_a_briefs_url_belonging_to_another_page_is_still_refused(): void
    {
        // /briefs/europe is the URL show() falls back to for the europe page, so
        // another page may not take it.
        $this->saveWithPath('test', '/briefs/europe')
            ->assertOk()
            ->assertJsonPath('path', '/briefs/test')
            ->assertJsonPath('path_message', fn ($m) => is_string($m));
    }

    public function test_an_unknown_briefs_url_is_still_a_404(): void
    {
        $this->get('/briefs/nothing-here')->assertNotFound();
    }

    public function test_renaming_a_pages_title_never_moves_its_url(): void
    {
        // The slug is assigned once at creation and save() never recomputes it,
        // so a retitle cannot strand the URL the page is already published at.
        $this->saveWithPath('test', '/briefs/test')->assertOk();

        $page = app(BriefPageStore::class)->find('test');
        $this->withSession(['cms_authenticated' => true, 'cms_super_admin' => true])
            ->postJson(route('admin.pages.save', 'test'), [
                'title' => 'A Completely Different Name',
                'visible' => true,
                'path' => '/briefs/test',
                'layout' => $page['layout'] ?? [],
                'page_title' => '', 'meta_description' => '',
            ])->assertOk()->assertJsonPath('path', '/briefs/test');

        $after = app(BriefPageStore::class)->find('test');
        $this->assertSame('A Completely Different Name', $after['title']);
        $this->assertSame('test', $after['slug'], 'a retitle must not change the slug');
        $this->assertSame('/briefs/test', $after['path']);
        $this->get('/briefs/test')->assertOk();
    }

    public function test_an_unmoved_seeded_page_still_serves_its_own_url(): void
    {
        $this->get('/wednesday-briefings')->assertOk();
        $this->get('/europe')->assertOk();
    }
}
