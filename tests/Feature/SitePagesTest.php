<?php

namespace Tests\Feature;

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
        foreach (['/', '/index.html', '/about', '/about.html', '/contact', '/contact.html', '/blog', '/blog/one-degree-test-requirements', '/services/admissions-counselling', '/mbbs/student'] as $path) {
            $this->get($path)->assertOk();
        }
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
}
