<?php

namespace Tests\Feature;

use App\Support\BlogContent;
use App\Support\BriefPageStore;
use App\Support\MbbsCountryContent;
use App\Support\NotFoundSuggestions;
use App\Support\StudyLocationContent;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NotFoundSuggestionsTest extends TestCase
{
    /** Fixed content, so the guesses do not move with whatever storage holds. */
    private function suggestions(): NotFoundSuggestions
    {
        $blog = \Mockery::mock(BlogContent::class);
        $blog->shouldReceive('all')->andReturn([
            ['slug' => 'how-to-get-into-bates-college', 'title' => 'How To Get Into Bates College'],
            ['slug' => 'the-white-male-in-admissions', 'title' => 'The Reality of White Male College Admission Rates'],
            ['slug' => 'secret-draft', 'title' => 'Secret Draft', 'visible' => false],
            ['slug' => 'moved-away', 'title' => 'Moved Away', 'link_url' => '/visa'],
        ]);

        $briefs = \Mockery::mock(BriefPageStore::class);
        $briefs->shouldReceive('visible')->andReturn([['title' => 'Europe Packages', 'path' => '/europe']]);

        $destinations = \Mockery::mock(StudyLocationContent::class);
        $destinations->shouldReceive('destinations')->andReturn([
            ['name' => 'Canada', 'slug' => 'study-in-canada'],
            ['name' => 'UK', 'slug' => 'study-in-uk'],
        ]);

        $mbbs = \Mockery::mock(MbbsCountryContent::class);
        $mbbs->shouldReceive('countries')->andReturn([['name' => 'Armenia', 'slug' => 'armenia']]);

        return new NotFoundSuggestions($blog, $briefs, $destinations, $mbbs);
    }

    public static function nearMisses(): array
    {
        return [
            'section word left out' => ['/countries/canada', '/countries/study-in-canada'],
            'bare country' => ['/canada', '/countries/study-in-canada'],
            'typo' => ['/vsa', '/visa'],
            'common suffix' => ['/contact-us', '/contact'],
            'cut short' => ['/services/test-prep', '/services/test-preparation'],
            'short form' => ['/sop', '/statement-of-purpose'],
            'test name' => ['/ielts', '/services/test-preparation'],
            'mbbs country' => ['/mbbs/armenia', '/mbbs/country/armenia'],
            'home page section' => ['/destinations', '/#destinations'],
            'page-builder page' => ['/europe-packages', '/europe'],
            'old file name' => ['/about.php', '/about'],
        ];
    }

    #[DataProvider('nearMisses')]
    public function test_a_near_miss_is_matched_to_the_page_it_meant(string $typed, string $meant): void
    {
        $this->assertSame($meant, $this->suggestions()->for($typed)[0]['path'] ?? null);
    }

    public function test_an_exact_word_beats_a_typo_of_another(): void
    {
        // "bates" is one letter off "rates"; only Bates College should be offered.
        $this->assertSame(
            ['/blog/how-to-get-into-bates-college'],
            array_column($this->suggestions()->for('/blog/bates'), 'path')
        );
    }

    public function test_an_address_that_means_nothing_gets_no_guess(): void
    {
        foreach (['/wp-login.php', '/xyz123', '/', '/index.html'] as $path) {
            $this->assertSame([], $this->suggestions()->for($path), $path);
        }
    }

    public function test_hidden_and_redirect_only_posts_are_never_offered(): void
    {
        $paths = array_column($this->suggestions()->pages(), 'path');

        $this->assertContains('/blog/how-to-get-into-bates-college', $paths);
        $this->assertNotContains('/blog/secret-draft', $paths);
        $this->assertNotContains('/blog/moved-away', $paths);
    }

    public function test_the_finder_knows_test_names_and_short_forms(): void
    {
        $index = collect($this->suggestions()->searchIndex())->keyBy('t');

        $this->assertStringContainsString('ielts', $index['Test Preparation']['k']);
        $this->assertStringContainsString('sop', $index['Statement of Purpose']['k']);
        $this->assertSame(url('/visa'), $index['Student Visa']['u']);
    }
}
