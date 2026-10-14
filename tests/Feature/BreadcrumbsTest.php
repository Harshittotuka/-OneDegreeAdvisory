<?php

namespace Tests\Feature;

use App\Support\CareerLibraryStore;
use Tests\TestCase;

class BreadcrumbsTest extends TestCase
{
    /**
     * The drawn trail (when switched on) and the BreadcrumbList are built from
     * one list; read both back from the page.
     *
     * @return array{visible: list<string>, schema: list<array{name: string, item: string}>}
     */
    private function trailOn(string $path): array
    {
        $html = $this->get($path)->assertOk()->getContent();

        preg_match('#<nav class="site-crumbs".*?</nav>#s', $html, $nav);
        preg_match_all('#<li class="site-crumbs__item[^"]*"[^>]*>\s*<(?:a|span)[^>]*>([^<]*)<#', $nav[0] ?? '', $names);

        $schema = [];
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $blocks);
        foreach ($blocks[1] as $json) {
            $data = json_decode($json, true);
            if (($data['@type'] ?? null) === 'BreadcrumbList') {
                $schema = array_map(
                    fn (array $item) => ['name' => $item['name'], 'item' => $item['item']],
                    $data['itemListElement']
                );
            }
        }

        return [
            'visible' => array_map(fn ($n) => html_entity_decode($n, ENT_QUOTES), $names[1]),
            'schema' => $schema,
        ];
    }

    public function test_the_home_page_has_no_trail(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('class="site-crumbs"', false)
            ->assertDontSee('BreadcrumbList', false);
    }

    public function test_the_trail_is_not_drawn_but_search_engines_still_get_it(): void
    {
        $trail = $this->trailOn('/about');

        $this->assertSame([], $trail['visible']);
        $this->assertSame([
            ['name' => 'Home', 'item' => url('/')],
            ['name' => 'About', 'item' => url('/about')],
        ], $trail['schema']);
    }

    public function test_a_service_page_hangs_off_study_abroad(): void
    {
        $this->assertSame([
            ['name' => 'Home', 'item' => url('/')],
            ['name' => 'Study Abroad', 'item' => url('/study-abroad')],
            ['name' => 'Admissions Counselling', 'item' => url('/services/admissions-counselling')],
        ], $this->trailOn('/services/admissions-counselling')['schema']);
    }

    public function test_a_blog_post_ends_in_its_own_title(): void
    {
        $schema = $this->trailOn('/blog/one-degree-test-requirements')['schema'];

        $this->assertSame(['Home', 'Blog'], array_slice(array_column($schema, 'name'), 0, 2));
        $this->assertCount(3, $schema);
        $this->assertSame(url('/blog/one-degree-test-requirements'), $schema[2]['item']);
    }

    public function test_a_career_report_hangs_off_the_library(): void
    {
        // Report pages are switched off in the CMS by default, and the
        // sitemap leaves them out while they are; turn them on for this test.
        $settings = ['detail_pages_enabled' => true] + app(CareerLibraryStore::class)->settings();
        $store = \Mockery::mock(CareerLibraryStore::class, [])->makePartial();
        $store->shouldReceive('settings')->andReturn($settings);
        $this->instance(CareerLibraryStore::class, $store);

        $names = array_column($this->trailOn('/global-career-library/in/Data-Science/en-IN')['schema'], 'name');

        // The last crumb is the report's own title (CMS copy), so only its shape is fixed.
        $this->assertSame(['Home', 'Global Career Library'], array_slice($names, 0, 2));
        $this->assertCount(3, $names);
        $this->assertStringEndsWith(' career', $names[2]);
    }

    public function test_switched_on_the_drawn_trail_matches_the_schema(): void
    {
        config(['site.show_breadcrumbs' => true]);

        $trail = $this->trailOn('/services/admissions-counselling');
        $this->assertSame(['Home', 'Study Abroad', 'Admissions Counselling'], $trail['visible']);
        $this->assertSame(array_column($trail['schema'], 'name'), $trail['visible']);

        // Only the page itself is left unlinked.
        $html = $this->get('/mbbs/student')->assertOk()->getContent();
        $this->assertStringContainsString('<li class="site-crumbs__item"><a href="'.url('/').'">Home</a></li>', $html);
        $this->assertStringContainsString('<li class="site-crumbs__item is-current" aria-current="page"><span>MBBS Abroad</span></li>', $html);
    }

    /**
     * Every page the sitemap offers Google carries exactly one BreadcrumbList:
     * none missing, and none doubled up by a page that still emits its own.
     */
    public function test_every_page_in_the_sitemap_has_exactly_one_trail(): void
    {
        $sitemap = $this->get('/sitemap.xml')->assertOk()->getContent();
        preg_match_all('#<loc>([^<]+)</loc>#', $sitemap, $locs);
        $this->assertNotEmpty($locs[1]);

        foreach ($locs[1] as $loc) {
            $path = parse_url($loc, PHP_URL_PATH) ?: '/';
            if ($path === '/') {
                continue;
            }

            $html = $this->get($path)->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, '"BreadcrumbList"'), "BreadcrumbList count on {$path}");
            $this->assertStringNotContainsString('<nav class="site-crumbs"', $html, "Trail drawn on {$path}");
        }
    }
}
