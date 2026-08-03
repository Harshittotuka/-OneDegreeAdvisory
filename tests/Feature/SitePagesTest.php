<?php

namespace Tests\Feature;

use App\Support\BlogContent;
use App\Support\StudyLocationContent;
use App\Support\MbbsCountryContent;
use Tests\TestCase;

class SitePagesTest extends TestCase
{
    private ?string $visibilityBackup = null;
    private bool $visibilityExisted = false;

    private function visibilityPath(): string
    {
        return storage_path('app/country-visibility.json');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->visibilityExisted = is_file($this->visibilityPath());
        $this->visibilityBackup = $this->visibilityExisted ? file_get_contents($this->visibilityPath()) : null;

        if ($this->visibilityExisted) {
            unlink($this->visibilityPath());
        }
    }

    protected function tearDown(): void
    {
        if ($this->visibilityExisted) {
            file_put_contents($this->visibilityPath(), (string) $this->visibilityBackup);
        } elseif (is_file($this->visibilityPath())) {
            unlink($this->visibilityPath());
        }

        parent::tearDown();
    }

    public function test_primary_pages_render(): void
    {
        foreach (['/', '/about', '/contact', '/blog', '/blog/one-degree-test-requirements', '/services/admissions-counselling', '/mbbs/student'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_evaluate_your_personality_opens_decode_in_the_same_tab(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<a class="course-menu-card" href="https://decode.onedegreeadvisory.com/">', false)
            ->assertDontSee('data-feature="personality-assessment"', false);
    }

    public function test_legacy_pages_redirect_to_canonical_urls(): void
    {
        foreach ([
            '/index.html' => '/',
            '/about.html' => '/about',
            '/contact.html' => '/contact',
            '/packages' => '/europe',
        ] as $from => $to) {
            $this->get($from)
                ->assertStatus(301)
                ->assertRedirect($to);
        }
    }

    public function test_seo_files_render(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<urlset', false)
            ->assertSee('<loc>'.url('/').'</loc>', false);

        $this->get('https://onedegreeadvisory.com/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /admin/')
            ->assertSee('Sitemap: https://onedegreeadvisory.com/sitemap.xml');
    }

    public function test_sitemap_excludes_redirect_only_blog_urls(): void
    {
        $this->mock(BlogContent::class, function ($mock): void {
            $mock->shouldReceive('all')->once()->andReturn([
                [
                    'slug' => 'redirect-only-post',
                    'visible' => true,
                    'link_url' => '/europe',
                ],
                [
                    'slug' => 'regular-article',
                    'visible' => true,
                    'date' => '2026-06-19',
                ],
            ]);
        });

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertDontSee('<loc>'.route('blog.post', 'redirect-only-post').'</loc>', false)
            ->assertSee('<loc>'.route('blog.post', 'regular-article').'</loc>', false)
            ->assertSee('<loc>'.route('europe').'</loc>', false);
    }

    public function test_link_only_blog_entries_redirect_permanently(): void
    {
        $this->mock(BlogContent::class, function ($mock): void {
            $mock->shouldReceive('forSlug')->with('redirect-only-post')->once()->andReturn([
                'slug' => 'redirect-only-post',
                'visible' => true,
                'link_url' => '/europe',
            ]);
        });

        $this->get('/blog/redirect-only-post')
            ->assertStatus(301)
            ->assertRedirect('/europe');
    }

    public function test_public_pages_link_to_course_and_service_landing_pages(): void
    {
        // The Destinations dropdown carries Undergrad / Postgrad / MBA only.
        // LLB has been removed outright; Doctoral is still live but deliberately
        // unlinked (listed in the admin "unlinked pages" report).
        $this->get('/')
            ->assertOk()
            ->assertSee(route('courses.ug'), false)
            ->assertSee(route('courses.pg'), false)
            ->assertSee(route('courses.mba'), false);

        $this->get('/study-abroad')
            ->assertOk()
            ->assertSee(route('services.admissions-counselling'), false)
            ->assertSee(route('services.student-services'), false)
            ->assertSee(route('services.test-prep'), false);
    }

    public function test_admissions_counselling_page_is_live_and_listed_as_unlinked(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('data-stripe-trigger="services"', false)
            ->assertDontSee('/services/admissions-counselling', false);

        $this->get('/services/admissions-counselling')
            ->assertOk()
            ->assertSee('Admissions counselling for every')
            ->assertSee('study&#8209;abroad', false)
            ->assertSee('Australian Admissions')
            ->assertSee('Medicine Admissions');

        $this->withSession(['cms_authenticated' => true]);

        $this->get(route('admin.unlinked-pages.index'))
            ->assertOk()
            ->assertSee('Unlinked Pages')
            ->assertSee('/services/admissions-counselling', false)
            ->assertSee('Admissions Counselling');
    }

    public function test_insights_page_is_removed(): void
    {
        foreach (['/insights', '/insights.html'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    public function test_dynamic_uk_page_uses_content_data(): void
    {
        /* Every visible string on the country page is sourced from the
           scraped leverageedu JSON — no hardcoded country-specific copy.
           These assertions check strings that come from the JSON for UK. */
        $this->get('/countries/study-in-uk')
            ->assertOk()
            ->assertSee('Why the United Kingdom?')   // JSON: Sections.section_heading (Why)
            ->assertSeeInOrder(['Indian Students in UK', 'Top Courses to Study in UK'])
            ->assertSee('HESA data, Jan 2026')       // JSON: IndianStudents.card_description, stripped from source HTML
            ->assertDontSee('data-and-analysis/students/table-28', false)
            ->assertSee('Top Courses to Study in UK') // JSON: Sections.section_heading (Courses)
            ->assertSee('MSc Artificial Intelligence')// JSON: Courses.course_name
            ->assertSee('University of Essex')        // JSON: Courses.university_name
            ->assertDontSee('Popular cities in UK')  // Popular cities section removed from study-in pages
            ->assertSee('Cost of Studying in the UK')// JSON: Sections.section_heading (Costs)
            ->assertSee('Foundation Program')        // JSON: Cards.card_title
            ->assertSee('UK_London.jpg', false)      // JSON: Images.image_url
            ->assertSee('UK_Edinburgh.webp', false)  // JSON: Images.image_url
            ->assertDontSee('src="http://localhost/assets/heroes/uk.jpg"', false);
    }

    public function test_dynamic_country_guide_pages_render_from_generated_content(): void
    {
        $destinations = app(StudyLocationContent::class)->destinations();

        $this->assertCount(19, $destinations);
        $this->assertContains('study-in-georgia', array_column($destinations, 'slug'));
        $this->assertContains('study-in-kazakhstan', array_column($destinations, 'slug'));

        foreach ($destinations as $destination) {
            $this->get("/countries/{$destination['slug']}")
                ->assertOk()
                ->assertSee($destination['name']);
        }
    }

    public function test_unknown_country_returns_not_found(): void
    {
        $this->get('/countries/study-in-nowhere')->assertNotFound();
    }

    public function test_dynamic_mbbs_country_pages_render_from_av_global_content(): void
    {
        $countries = app(MbbsCountryContent::class)->countries();

        $this->assertCount(23, $countries);
        $this->assertContains('georgia', array_column($countries, 'slug'));
        $this->assertContains('united-kingdom', array_column($countries, 'slug'));
        $this->assertContains('czech-republic', array_column($countries, 'slug'));

        foreach ($countries as $country) {
            $this->get("/mbbs/country/{$country['slug']}")
                ->assertOk()
                ->assertSee($country['name'])
                ->assertDontSee('Source-backed country profile')
                ->assertDontSee('Source-backed guide')
                ->assertDontSee('Source sections')
                ->assertDontSee('<dt>Facts</dt>', false)
                ->assertDontSee('<dt>Sections</dt>', false)
                ->assertDontSee('<dt>Steps</dt>', false);
        }
    }

    /**
     * Guards a real dead band: stripe-nav.css hid the desktop links at 1080px
     * while styles.css only revealed the hamburger at 920px, so 921-1080px had
     * no navigation at all — the width a 1280 laptop reports at 125% zoom.
     */
    public function test_the_hamburger_appears_wherever_the_desktop_nav_links_are_hidden(): void
    {
        $css = file_get_contents(public_path('stripe-nav.css'));
        $js = file_get_contents(public_path('stripe-nav.js'));

        $mobileBlock = strpos($css, '@media (max-width: 1080px)');
        $this->assertNotFalse($mobileBlock, 'The nav still switches to mobile at 1080px.');

        // The toggle must be given a display inside that same block.
        preg_match('/\.stripe-nav-toggle\s*\{[^}]*display:\s*grid/', $css, $m, PREG_OFFSET_CAPTURE);
        $this->assertNotEmpty($m, 'The hamburger must be shown, not left at the inherited display:none.');
        $this->assertGreaterThan(
            $mobileBlock,
            $m[0][1],
            'The hamburger must be revealed inside the max-width:1080px block, not the desktop one.'
        );

        // ...and the script must agree on where "desktop" ends.
        $this->assertStringContainsString('(min-width: 1081px)', $js);
    }

    public function test_the_laptop_band_gives_containers_room_without_touching_wide_screens(): void
    {
        $css = file_get_contents(public_path('styles.css'));

        // The band is bounded at both ends on purpose: below 921px the tuned
        // mobile ladder owns the layout, above 1440px gutters are already wide.
        $band = strpos($css, '@media (min-width: 921px) and (max-width: 1440px)');
        $this->assertNotFalse($band, 'The laptop band must exist.');

        // Base gutters stay as they were — the band only raises the floor.
        $this->assertStringContainsString('width: min(calc(100% - 40px), var(--container));', $css);
        $this->assertStringContainsString('width: min(calc(100% - 64px), var(--container));', $css);

        // The hero headline must step down for laptops, or its four wrapped
        // lines push the hero CTAs below the fold on a short screen.
        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 921px\) and \(max-width: 1180px\) \{\s*\.hero-headline \{\s*font-size: 3\.8rem/',
            $css
        );
        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 1181px\) and \(max-width: 1440px\) \{\s*\.hero-headline \{\s*font-size: 4\.6rem/',
            $css
        );
    }

    public function test_pages_cross_fade_into_each_other_instead_of_flashing_white(): void
    {
        $css = file_get_contents(public_path('styles.css'));

        $this->assertStringContainsString('@view-transition { navigation: auto; }', $css);
        // The chrome is identical on every page, so it holds still while the
        // content between it changes. Without these names the header and footer
        // cross-fade too, which is what made a navigation read as a full reload.
        $this->assertStringContainsString('.stripe-site-header { view-transition-name: site-header; }', $css);
        $this->assertStringContainsString('.site-footer { view-transition-name: site-footer; }', $css);
        // Motion is opt-out-able; without this the transition ignores the setting.
        $this->assertMatchesRegularExpression(
            '/@media \(prefers-reduced-motion: reduce\) \{\s*@view-transition \{ navigation: none; \}/',
            $css
        );

        // Both names must resolve to exactly one element per page, or the
        // browser aborts the whole transition.
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'class="stripe-site-header"'));
        $this->assertSame(1, substr_count($html, 'class="site-footer"'));
    }

    public function test_links_are_prefetched_on_hover_but_never_the_private_areas(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        preg_match('~<script type="speculationrules">(.*?)</script>~s', $html, $match);
        $this->assertNotEmpty($match, 'The speculation rules block must be rendered.');
        $rules = json_decode(trim($match[1]), true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'Speculation rules must be valid JSON.');

        $rule = $rules['prefetch'][0];
        // "moderate" is hover/pointerdown only. "eager" would crawl every link
        // on the page the moment it loads, multiplying requests to the server.
        $this->assertSame('moderate', $rule['eagerness']);

        // Prefetching an authenticated area, a one-time invite token, or the
        // partner-proxied profiler would be wasteful at best and harmful at worst.
        $excluded = $rule['where']['and'][1]['not']['href_matches'];
        foreach (['/crm*', '/admin*', '/cms*', '/mock-interview/*', '/profiler*'] as $path) {
            $this->assertContains($path, $excluded);
        }
    }

    public function test_page_content_animates_in_on_a_fresh_load_only(): void
    {
        $css = file_get_contents(public_path('styles.css'));

        $this->assertStringContainsString('@keyframes page-content-enter', $css);
        // Gated twice on purpose: a view-transition navigation is already
        // animated (running both reads as a double-take), and a CMS live editor
        // re-renders as you type, which would replay it on every keystroke.
        $this->assertStringContainsString(
            'html:not(.is-vt-nav) body:not(.cms-editing) main {',
            $css
        );
        $this->assertMatchesRegularExpression('/animation: page-content-enter \d+ms/', $css);

        // The listener that sets the gate must be inline in the head: pagereveal
        // fires before the incoming document paints, which a deferred script is
        // not guaranteed to beat. Both public layouts share styles.css, so both
        // need it or career-library pages would double-animate.
        foreach (['/', '/global-career-library'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            $head = substr($html, 0, strpos($html, '</head>') ?: strlen($html));
            $this->assertStringContainsString('addEventListener("pagereveal"', $head, "{$path} head is missing the gate.");
            $this->assertStringContainsString('classList.add("is-vt-nav")', $head, "{$path} never sets the gate class.");
        }
    }

    public function test_icons_are_served_from_this_origin_rather_than_a_cdn(): void
    {
        // lucide@latest on unpkg answers its redirect with max-age=60, so after a
        // minute idle every page paid a third-party round trip before any of its
        // ~700 icon placeholders could render.
        $this->assertFileExists(public_path('assets/vendor/lucide.min.js'));

        foreach (['/', '/about', '/global-career-library'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            $this->assertStringNotContainsString('unpkg.com/lucide', $html, "{$path} still loads Lucide from a CDN.");
            $this->assertMatchesRegularExpression(
                '~src="[^"]*assets/vendor/lucide\.min\.js\?v=\d+"~',
                $html,
                "{$path} must load the self-hosted, cache-busted Lucide."
            );
        }
    }
}
