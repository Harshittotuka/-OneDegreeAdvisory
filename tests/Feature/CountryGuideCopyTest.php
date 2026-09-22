<?php

namespace Tests\Feature;

use App\Support\CountryGuideCopy;
use App\Support\StudyLocationContent;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The country guides are built from a scraped partner site, and what the
 * scraper produces is the page's DOM text joined with "|" — not prose. Guides
 * rewritten by hand live in resources/data/country-guides and are laid over the
 * scraped values on read, so that running country-sync again cannot overwrite
 * them. These pin that layering, because losing it would silently restore the
 * partner's copy.
 */
class CountryGuideCopyTest extends TestCase
{
    public static function rewrittenSlugs(): array
    {
        return array_map(fn ($slug) => [$slug], CountryGuideCopy::rewrittenSlugs());
    }

    public function test_some_guides_have_been_rewritten(): void
    {
        $this->assertNotEmpty(
            CountryGuideCopy::rewrittenSlugs(),
            'No country guide copy was loaded from resources/data/country-guides.'
        );
    }

    #[DataProvider('rewrittenSlugs')]
    public function test_written_copy_reaches_the_page(string $slug): void
    {
        $copy = CountryGuideCopy::forSlug($slug);
        $html = $this->get("/countries/{$slug}")->assertOk()->getContent();

        $this->assertStringContainsString(
            e($copy['seo_title']),
            $html,
            "{$slug} is not using its written title"
        );

        foreach ($copy['sections'] ?? [] as $key => $section) {
            $this->assertStringContainsString(
                e($section['heading']),
                $html,
                "{$slug} is not using its written {$key} heading"
            );
            // The scraped body is pipe-joined fragments the view has to salvage
            // a sentence from; written prose must print whole, so a distinctive
            // tail of it has to survive to the page.
            $this->assertStringContainsString(
                e(mb_substr($section['body'], -60)),
                $html,
                "{$slug}'s written {$key} copy is being truncated instead of printed whole"
            );
        }
    }

    #[DataProvider('rewrittenSlugs')]
    public function test_written_meta_fits_what_google_renders(string $slug): void
    {
        $html = $this->get("/countries/{$slug}")->assertOk()->getContent();

        preg_match('/<title>(.*?)<\/title>/s', $html, $t);
        preg_match('/<meta name="description" content="([^"]*)"/', $html, $d);

        $title = html_entity_decode(trim($t[1] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $description = html_entity_decode($d[1] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $this->assertLessThanOrEqual(60, mb_strlen($title), "{$slug} title is too long: {$title}");
        $this->assertStringContainsString('One Degree Advisory', $title, "{$slug} title does not name the brand");

        $this->assertLessThanOrEqual(160, mb_strlen($description), "{$slug} description is too long");
        // Written descriptions fit on their own, so they must not arrive cut.
        $this->assertMatchesRegularExpression('/[.!?]$/u', $description, "{$slug} description looks cut off: {$description}");
    }

    /**
     * The partner's name must not appear in anything a reader or a crawler
     * reads as copy. Its asset URLs are a separate matter and stay: the city
     * photographs are served from its CDN, and rewriting those would break the
     * images (the same carve-out StudyLocationContent::withOurBrand() makes).
     */
    #[DataProvider('rewrittenSlugs')]
    public function test_the_partner_brand_never_reaches_a_rewritten_page_as_copy(string $slug): void
    {
        $html = $this->get("/countries/{$slug}")->assertOk()->getContent();

        $visible = html_entity_decode(
            strip_tags(preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is', ' ', $html)),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $this->assertStringNotContainsStringIgnoringCase('leverage edu', $visible);
        $this->assertStringNotContainsStringIgnoringCase('leverageedu', $visible);

        preg_match('/<title>(.*?)<\/title>/s', $html, $t);
        preg_match('/<meta name="description" content="([^"]*)"/', $html, $d);
        $this->assertStringNotContainsStringIgnoringCase('leverage', $t[1] ?? '');
        $this->assertStringNotContainsStringIgnoringCase('leverage', $d[1] ?? '');
    }

    /**
     * The "Indian students in <country>" band used to repeat, word for word,
     * the sentence the why-section prints just above it — the same text twice
     * on one page, once as a paragraph and once as an <h2>.
     */
    #[DataProvider('rewrittenSlugs')]
    public function test_the_band_headline_is_not_a_repeat_of_the_why_paragraph(string $slug): void
    {
        $content = app(StudyLocationContent::class)->forSlug($slug);

        $band = trim(implode(' ', [
            $content['indianStudents']['heading_before'] ?? '',
            $content['indianStudents']['heading_highlight'] ?? '',
            $content['indianStudents']['heading_after'] ?? '',
        ]));
        $why = (string) ($content['sectionCopy']['why']['section_body_clean'] ?? '');

        $this->assertNotSame('', $band, "{$slug} has no band headline");
        $this->assertStringNotContainsString($band, $why, "{$slug} repeats its band headline in the why copy");
    }

    /**
     * FAQPage is only honoured when the same text is on the page, and markup
     * describing text a reader cannot see is a structured-data violation. The
     * two come from one source in the guide file; this proves they stay level.
     */
    #[DataProvider('rewrittenSlugs')]
    public function test_the_faq_markup_matches_what_is_on_the_page(string $slug): void
    {
        $expected = CountryGuideCopy::forSlug($slug)['faq'] ?? [];
        $html = $this->get("/countries/{$slug}")->assertOk()->getContent();

        if ($expected === []) {
            $this->assertStringNotContainsString('FAQPage', $html, "{$slug} emits FAQPage with no questions");

            return;
        }

        preg_match_all('~<script type="application/ld\+json">(.*?)</script>~s', $html, $blocks);
        $faq = null;
        foreach ($blocks[1] as $block) {
            $decoded = json_decode(trim($block), true);
            if (($decoded['@type'] ?? '') === 'FAQPage') {
                $faq = $decoded;
            }
        }

        $this->assertNotNull($faq, "{$slug} has written questions but emits no FAQPage");
        $this->assertCount(count($expected), $faq['mainEntity'], "{$slug} marks up a different number of questions");

        foreach ($faq['mainEntity'] as $question) {
            $this->assertSame('Question', $question['@type']);
            $this->assertStringContainsString(
                e($question['name']),
                $html,
                "{$slug} marks up a question that is not on the page: {$question['name']}"
            );
            $this->assertStringContainsString(
                e($question['acceptedAnswer']['text']),
                $html,
                "{$slug} marks up an answer that is not on the page"
            );
        }
    }

    /** A guide with no written copy must be completely unaffected. */
    public function test_a_guide_without_written_copy_is_untouched(): void
    {
        $slug = 'study-in-spain';
        $this->assertSame([], CountryGuideCopy::forSlug($slug), 'this test needs a slug with no written copy');

        $content = app(StudyLocationContent::class)->forSlug($slug);

        $this->assertArrayNotHasKey('section_body_clean', $content['sectionCopy']['why'] ?? []);
        $this->get("/countries/{$slug}")->assertOk();
    }
}
