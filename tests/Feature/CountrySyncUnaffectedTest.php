<?php

namespace Tests\Feature;

use App\Support\CountryGuideCopy;
use App\Support\MbbsCountryContent;
use App\Support\StudyLocationContent;
use Tests\TestCase;

/**
 * The country guides are fetched from partner sites by the country-sync
 * scraper, which writes storage/app/leverageedu_study_locations_content.json
 * directly. Two things were added on the read side that could in principle get
 * between that file and the page:
 *
 *   1. the nav country lists are now cached (StudyLocationContent::destinations),
 *   2. hand-written copy is laid over the scraped values (CountryGuideCopy).
 *
 * Both are read-time only — nothing here writes to the content file — but
 * "read-time only" is worth proving rather than asserting, because the failure
 * mode is silent: a sync would appear to succeed while the site kept serving
 * yesterday's data. These rewrite the file the way a sync does and check the
 * site notices.
 */
class CountrySyncUnaffectedTest extends TestCase
{
    private string $path;

    private string $mbbsPath;

    private ?string $backup = null;

    private ?string $mbbsBackup = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = storage_path('app/leverageedu_study_locations_content.json');
        $this->mbbsPath = storage_path('app/mbbs_avglobal_content.json');

        $this->backup = is_file($this->path) ? file_get_contents($this->path) : null;
        $this->mbbsBackup = is_file($this->mbbsPath) ? file_get_contents($this->mbbsPath) : null;

        if ($this->backup === null) {
            $this->markTestSkipped('No scraped country content on this box.');
        }
    }

    protected function tearDown(): void
    {
        // Restore both before anything else: leaving a mutated copy of a
        // partner's content file behind would be worse than a failing test.
        if ($this->backup !== null) {
            file_put_contents($this->path, $this->backup);
            clearstatcache(true, $this->path);
        }

        if ($this->mbbsBackup !== null) {
            file_put_contents($this->mbbsPath, $this->mbbsBackup);
            clearstatcache(true, $this->mbbsPath);
        }

        CountryGuideCopy::flush();

        parent::tearDown();
    }

    /** Rewrite the content file the way a completed sync does. */
    private function writeAsSyncWould(callable $mutate): void
    {
        $data = json_decode((string) file_get_contents($this->path), true);
        $mutate($data);
        file_put_contents(
            $this->path,
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );
        clearstatcache(true, $this->path);
    }

    /**
     * A guide with no hand-written copy must show exactly what the partner sent,
     * including after a re-sync — nothing may cache in front of it.
     */
    public function test_freshly_synced_partner_data_reaches_the_page(): void
    {
        $slug = 'study-in-spain';
        $this->assertSame([], CountryGuideCopy::forSlug($slug), 'this test needs a slug with no written copy');

        $marker = 'Synced marker '.uniqid('', true);

        $this->writeAsSyncWould(function (array &$data) use ($slug, $marker) {
            foreach ($data['sheets']['Pages'] as &$row) {
                if (($row['page_slug'] ?? '') === $slug) {
                    $row['seo_description'] = $marker.' — partner supplied description.';
                }
            }
        });

        $this->get("/countries/{$slug}")
            ->assertOk()
            ->assertSee($marker, false);
    }

    /** The nav dropdown is cached; a sync that renames a country must still show through. */
    public function test_a_synced_rename_shows_in_the_navigation(): void
    {
        $slug = 'study-in-spain';
        $renamed = 'Spain '.substr(md5((string) mt_rand()), 0, 6);

        // Warm the cache with the current value first, so a stale read would fail.
        app(StudyLocationContent::class)->destinations();

        $this->writeAsSyncWould(function (array &$data) use ($slug, $renamed) {
            foreach ($data['sheets']['Pages'] as &$row) {
                if (($row['page_slug'] ?? '') === $slug) {
                    $row['nav_label'] = $renamed;
                }
            }
        });

        $names = array_column(app(StudyLocationContent::class)->destinations(false), 'name');

        $this->assertContains($renamed, $names, 'the cached nav did not notice a re-sync');
    }

    /**
     * The other direction: a sync must not be able to overwrite copy written by
     * hand. That is the whole reason the copy lives in the repo rather than in
     * the content file.
     */
    public function test_a_sync_cannot_overwrite_hand_written_copy(): void
    {
        $slug = 'study-in-belgium';
        $written = CountryGuideCopy::forSlug($slug);
        $this->assertNotSame([], $written, 'this test needs a slug with written copy');

        $this->writeAsSyncWould(function (array &$data) use ($slug) {
            foreach ($data['sheets']['Pages'] as &$row) {
                if (($row['page_slug'] ?? '') === $slug) {
                    $row['seo_title'] = 'Study in Belgium | Leverage Edu';
                    $row['seo_description'] = 'Study in Belgium with Leverage Edu.';
                }
            }
            foreach ($data['sheets']['Sections'] as &$section) {
                if (($section['page_slug'] ?? '') === $slug) {
                    $section['section_body'] = 'Re-scraped fragment | Talk to an Expert | Leverage Edu';
                }
            }
        });

        $html = $this->get("/countries/{$slug}")->assertOk()->getContent();

        $this->assertStringContainsString(e($written['seo_title']), $html);
        $this->assertStringContainsString(e($written['sections']['why']['heading']), $html);
        $this->assertStringContainsString(e(mb_substr($written['sections']['why']['body'], -60)), $html);
        $this->assertStringNotContainsString('Leverage Edu', strip_tags($html));
    }

    /**
     * The MBBS pages come from a second partner through their own sync, and the
     * same two things sit on their read path: the nav list is cached, and the
     * page title has the partner's brand stripped out of it. Same proof.
     */
    public function test_freshly_synced_mbbs_data_reaches_the_page(): void
    {
        if ($this->mbbsBackup === null) {
            $this->markTestSkipped('No scraped MBBS content on this box.');
        }

        $renamed = 'Georgia '.substr(md5((string) mt_rand()), 0, 6);

        // Warm the cached list first, so a stale read would fail the assertion.
        app(MbbsCountryContent::class)->countries(false);

        $data = json_decode((string) file_get_contents($this->mbbsPath), true);
        foreach ($data['sheets']['Pages'] as &$row) {
            if (($row['page_slug'] ?? '') === 'georgia') {
                $row['country'] = $renamed;
            }
        }
        unset($row);
        file_put_contents($this->mbbsPath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        clearstatcache(true, $this->mbbsPath);

        $names = array_column(app(MbbsCountryContent::class)->countries(false), 'name');

        $this->assertContains($renamed, $names, 'the cached MBBS nav did not notice a re-sync');
    }

    /** Nothing on the read path may write to either partner's content file. */
    public function test_rendering_never_writes_to_the_content_files(): void
    {
        $before = md5_file($this->path);
        $mbbsBefore = $this->mbbsBackup !== null ? md5_file($this->mbbsPath) : null;

        $this->get('/countries/study-in-belgium')->assertOk();
        $this->get('/countries/study-in-spain')->assertOk();
        $this->get('/mbbs/country/georgia')->assertOk();
        $this->get('/')->assertOk();
        app(StudyLocationContent::class)->forSlug('study-in-belgium');
        app(StudyLocationContent::class)->destinations();
        app(MbbsCountryContent::class)->forSlug('georgia');

        clearstatcache(true, $this->path);
        clearstatcache(true, $this->mbbsPath);

        $this->assertSame($before, md5_file($this->path), 'rendering modified the scraped country file');

        if ($mbbsBefore !== null) {
            $this->assertSame($mbbsBefore, md5_file($this->mbbsPath), 'rendering modified the scraped MBBS file');
        }
    }
}
