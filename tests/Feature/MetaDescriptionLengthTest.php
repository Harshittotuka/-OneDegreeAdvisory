<?php

namespace Tests\Feature;

use App\Support\Seo;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The AIOSEO audit of 21 Sep 2026 flagged the homepage description at 165
 * characters. Twelve pages in all were over the 160 Google renders, and the
 * layout's own cap was 170 — so the longest were cut mid-word before they ever
 * reached the SERP. These pin both halves: the cap, and a clean cut.
 */
class MetaDescriptionLengthTest extends TestCase
{
    /**
     * Pages whose description is written in the repo (a blade or a controller),
     * so the test owns it end to end: it must fit without being truncated.
     */
    public static function codeOwnedPagePaths(): array
    {
        return array_map(fn ($p) => [$p], [
            '/',
            '/about',
            '/contact',
            '/careers',
            '/study-abroad',
            '/services/student-services',
            '/services/test-preparation',
            '/services/admissions-counselling',
            '/courses/undergraduate',
            '/courses/postgraduate',
            '/courses/mba',
            '/courses/doctoral',
            '/mbbs/student',
            '/career-counselling',
            '/visa',
            '/visa-mock-interview',
            '/profiler',
            '/referral-program',
            '/statement-of-purpose',
            '/loan-accommodation',
            '/student-development-programme',
            '/privacy-policy',
            '/terms-and-conditions',
            '/blog',
        ]);
    }

    /**
     * Those, plus the pages whose description comes from the CMS. Their text is
     * edited outside the repo (and storage/app is gitignored), so all this can
     * fairly assert of them is that the cap holds.
     */
    public static function allPagePaths(): array
    {
        return array_merge(self::codeOwnedPagePaths(), array_map(fn ($p) => [$p], [
            '/europe',
            '/blog/one-degree-test-requirements',
        ]));
    }

    #[DataProvider('allPagePaths')]
    public function test_page_meta_description_fits_the_serp_budget(string $path): void
    {
        $description = $this->metaDescription($path);

        $this->assertNotSame('', $description, "$path has no meta description");
        $this->assertLessThanOrEqual(
            160,
            mb_strlen($description),
            "$path meta description is ".mb_strlen($description)." characters: $description"
        );
    }

    /**
     * A description the layout had to shorten stops mid-sentence, with no full
     * stop — which is how the four longest pages were reaching Google before
     * this. Anything written in the repo should fit on its own.
     */
    #[DataProvider('codeOwnedPagePaths')]
    public function test_page_meta_description_is_not_silently_truncated(string $path): void
    {
        $description = $this->metaDescription($path);

        $this->assertMatchesRegularExpression(
            '/[.!?]$/u',
            $description,
            "$path meta description looks cut off — shorten the source to 160 characters or fewer: $description"
        );
    }

    public function test_an_over_long_description_is_cut_on_a_word_boundary(): void
    {
        $long = 'One Degree Advisory is a premium global education advisory helping '
            .'students choose the right universities, strengthen their profiles, apply '
            .'with confidence, and prepare for arrival in a new country.';

        $cut = Seo::description($long);

        $this->assertLessThanOrEqual(160, mb_strlen($cut));
        $this->assertStringEndsNotWith(',', $cut, 'a dangling comma was left behind');
        $this->assertStringStartsWith($cut, $long, 'the cut text is no longer a prefix of the original');
        $this->assertSame(
            ' ',
            mb_substr($long, mb_strlen($cut), 1),
            'the description was cut in the middle of a word'
        );
    }

    public function test_a_description_that_already_fits_is_left_alone(): void
    {
        $short = 'Study abroad with a senior advisor — start with a free profile review.';

        $this->assertSame($short, Seo::description($short));
    }

    private function metaDescription(string $path): string
    {
        $html = $this->get($path)->assertOk()->getContent();

        preg_match('/<meta name="description" content="([^"]*)"/', $html, $m);

        return html_entity_decode($m[1] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
