<?php

namespace Tests\Feature;

use App\Support\Seo;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Titles, the sibling of MetaDescriptionLengthTest.
 *
 * Part 1 of SEO-MARKETING-PLAN.md claims every page ends in
 * "| One Degree Advisory". Eight pages had stopped doing that, and two ran past
 * the ~60 characters Google renders. Both are pinned here.
 */
class PageTitleTest extends TestCase
{
    /** Pages whose title is written in the repo, so the test owns it. */
    public static function codeOwnedPagePaths(): array
    {
        return MetaDescriptionLengthTest::codeOwnedPagePaths();
    }

    #[DataProvider('codeOwnedPagePaths')]
    public function test_page_title_fits_what_google_renders(string $path): void
    {
        $title = $this->title($path);

        $this->assertNotSame('', $title, "$path has no title");
        $this->assertLessThanOrEqual(
            60,
            mb_strlen($title),
            "$path title is ".mb_strlen($title)." characters and will be cut in results: $title"
        );
    }

    #[DataProvider('codeOwnedPagePaths')]
    public function test_page_title_carries_the_brand(string $path): void
    {
        $this->assertStringContainsString(
            'One Degree Advisory',
            $this->title($path),
            "$path title does not name the brand"
        );
    }

    public function test_an_over_long_title_is_cut_on_a_word_boundary(): void
    {
        $long = 'Postgraduate and Master\'s Study Abroad Programmes for Indian Students '
            .'Across Nineteen Destinations | One Degree Advisory';

        $cut = Seo::title($long);

        $this->assertLessThanOrEqual(70, mb_strlen($cut));
        // A cut must not leave half a word, or a dangling "|" separator.
        $this->assertStringEndsNotWith('|', $cut);
        $this->assertStringStartsWith($cut, $long, 'the cut title is no longer a prefix of the original');
        $this->assertSame(
            ' ',
            mb_substr($long, mb_strlen($cut), 1),
            'the title was cut in the middle of a word'
        );
    }

    public function test_a_title_that_already_fits_is_left_alone(): void
    {
        $short = 'Referral Program | One Degree Advisory';

        $this->assertSame($short, Seo::title($short));
    }

    private function title(string $path): string
    {
        $html = $this->get($path)->assertOk()->getContent();

        preg_match('/<title>(.*?)<\/title>/s', $html, $m);

        return html_entity_decode(trim($m[1] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
