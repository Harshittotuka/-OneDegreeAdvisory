<?php

namespace Tests\Feature;

use App\Support\StudyLocationContent;
use Tests\TestCase;

class SitePagesTest extends TestCase
{
    public function test_primary_pages_render(): void
    {
        foreach (['/', '/index.html', '/about', '/about.html', '/contact', '/contact.html', '/blog', '/blog/one-degree-test-requirements', '/services/admissions-counselling', '/mbbs/student'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_admissions_counselling_page_is_linked_from_services_menu(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('/services/admissions-counselling', false)
            ->assertSee('Admissions Counselling');

        $this->get('/services/admissions-counselling')
            ->assertOk()
            ->assertSee('Admissions counselling for every study-abroad dream.')
            ->assertSee('Australian Admissions')
            ->assertSee('Medicine Admissions');
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
            ->assertDontSee('assets/heroes/uk.jpg', false);
    }

    public function test_dynamic_country_guide_pages_render_from_generated_content(): void
    {
        $destinations = app(StudyLocationContent::class)->destinations();

        $this->assertCount(19, $destinations);
        $this->assertContains('study-in-georgia', array_column($destinations, 'slug'));
        $this->assertContains('study-in-kazakhstan', array_column($destinations, 'slug'));

        foreach ($destinations as $destination) {
            $response = $this->get("/countries/{$destination['slug']}")
                ->assertOk()
                ->assertSee($destination['name']);

            if (($destination['hero_image'] ?? '') !== '') {
                $response->assertDontSee($destination['hero_image'], false);
            }
        }
    }

    public function test_unknown_country_returns_not_found(): void
    {
        $this->get('/countries/study-in-nowhere')->assertNotFound();
    }
}
