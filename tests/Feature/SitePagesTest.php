<?php

namespace Tests\Feature;

use Tests\TestCase;

class SitePagesTest extends TestCase
{
    public function test_primary_pages_render(): void
    {
        foreach (['/', '/index.html', '/about', '/about.html', '/contact', '/contact.html', '/mbbs/student'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_insights_page_is_removed(): void
    {
        foreach (['/insights', '/insights.html'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    public function test_country_pages_render_with_clean_and_legacy_urls(): void
    {
        foreach (config('site.destinations') as $destination) {
            $this->get("/countries/{$destination['slug']}")->assertOk();
            $this->get("/countries/{$destination['slug']}.html")->assertOk();
        }
    }

    public function test_unknown_country_returns_not_found(): void
    {
        $this->get('/countries/study-in-nowhere')->assertNotFound();
    }
}
