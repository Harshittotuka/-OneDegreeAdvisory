<?php

namespace Tests\Feature;

use App\Support\MbbsCountryContent;
use Tests\TestCase;

/**
 * The MBBS country pages are scraped from avglobaloverseas.com, and the scrape
 * brought that company's name through in the page title:
 *
 *   MBBS in Georgia for Indian Students 2026 | NMC Approved Guide | AV Global
 *
 * Live, that rendered as "… | AV Global | One Degree Adv" — another company
 * named in our search result, and ours cut in half by the 90-character title
 * cap. This pins both halves of the fix for every country, because a re-sync
 * brings the raw titles back.
 */
class MbbsCountryTitleTest extends TestCase
{

    public function test_no_mbbs_title_names_the_partner_or_loses_our_own_name(): void
    {
        $slugs = array_column(app(MbbsCountryContent::class)->countries(false), 'slug');

        $this->assertNotEmpty($slugs, 'no MBBS countries loaded');

        foreach ($slugs as $slug) {
            $html = $this->get("/mbbs/country/{$slug}")->assertOk()->getContent();
            preg_match('/<title>(.*?)<\/title>/s', $html, $m);
            $title = html_entity_decode(trim($m[1] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $this->assertDoesNotMatchRegularExpression(
                '/AV\s*Global/i',
                $title,
                "{$slug} still names the partner in its title: {$title}"
            );
            $this->assertStringContainsString(
                config('site.name'),
                $title,
                "{$slug} title lost our own name to the length cap: {$title}"
            );
        }
    }

    /** A date range must not be mistaken for a separator and chopped. */
    public function test_a_date_range_in_a_title_survives(): void
    {
        $title = (string) (app(MbbsCountryContent::class)->forSlug('kazakhstan')['page']['page_title'] ?? '');

        $this->assertStringContainsString('2026-27', $title, "Kazakhstan's date range was split: {$title}");
    }
}
