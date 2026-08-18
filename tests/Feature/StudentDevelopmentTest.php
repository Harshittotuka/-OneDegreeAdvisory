<?php

namespace Tests\Feature;

use App\Support\HeroContent;
use Tests\TestCase;

/**
 * The Student Development Programme page (/student-development-programme): the
 * public render, the track mosaic's outbound links, the FAQ + its structured
 * data, and the home-hero button that reaches it — including the read-time
 * back-fill that keeps that button alive on a box whose (gitignored)
 * home-hero.json still holds the shipped "coming soon" placeholder.
 */
class StudentDevelopmentTest extends TestCase
{
    private const CS_URL = 'https://infolith.in/internship/cs';

    private const ECE_URL = 'https://infolith.in/internship/ece';

    private string $heroPath;

    /** A copy of the real hero JSON (if any), restored after each test. */
    private ?string $heroBackup = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->heroPath = storage_path('app/home-hero.json');
        $this->heroBackup = is_file($this->heroPath) ? file_get_contents($this->heroPath) : null;
    }

    protected function tearDown(): void
    {
        if ($this->heroBackup !== null) {
            file_put_contents($this->heroPath, $this->heroBackup);
        } else {
            @unlink($this->heroPath);
        }

        parent::tearDown();
    }

    /* ─────────────────────── The page itself ─────────────────────── */

    public function test_the_page_renders(): void
    {
        $this->get(route('student-development'))
            ->assertOk()
            ->assertSee('Student Development Programme')
            ->assertSee('sdp-page', false)
            ->assertSee('Student Development Programme — Engineering Skill Tracks', false);
    }

    public function test_the_route_is_the_expected_url(): void
    {
        $this->assertSame(url('/student-development-programme'), route('student-development'));
    }

    /* ─────────────────────── The track mosaic ─────────────────────── */

    public function test_every_track_card_links_out_to_the_partner_programme(): void
    {
        $html = $this->get(route('student-development'))->assertOk()->getContent();

        // 14 tiles: 9 CS-side (the CS branch card, 5 CS tracks, and the 3 plan
        // buttons are counted separately below) — assert on the mosaic instead by
        // counting anchors that carry a partner URL at all.
        $partnerLinks = preg_match_all('/href="'.preg_quote(self::CS_URL, '/').'"/', $html)
            + preg_match_all('/href="'.preg_quote(self::ECE_URL, '/').'"/', $html);

        $this->assertGreaterThanOrEqual(14, $partnerLinks, 'The mosaic, the plans and the closing CTAs should all link to the partner programme.');
    }

    public function test_the_computer_science_branch_card_links_to_the_cs_programme(): void
    {
        $this->get(route('student-development'))
            ->assertOk()
            ->assertSee('Software &amp; data internship', false)
            ->assertSee('href="'.self::CS_URL.'"', false);
    }

    public function test_the_electronics_branch_card_links_to_the_ece_programme(): void
    {
        // The ECE tracks are the same programme for a different discipline, so
        // they must not be pointed at the CS enrolment page.
        $this->get(route('student-development'))
            ->assertOk()
            ->assertSee('Hardware &amp; firmware internship', false)
            ->assertSee('href="'.self::ECE_URL.'"', false);
    }

    public function test_every_outbound_link_is_opened_safely(): void
    {
        $html = $this->get(route('student-development'))->assertOk()->getContent();

        // Every anchor that leaves the site must carry rel="noopener" alongside
        // its target, or the partner page gets a handle on window.opener.
        preg_match_all('/<a\b[^>]*href="https:\/\/infolith\.in[^"]*"[^>]*>/', $html, $matches);

        $this->assertNotEmpty($matches[0]);
        foreach ($matches[0] as $anchor) {
            $this->assertStringContainsString('target="_blank"', $anchor);
            $this->assertStringContainsString('rel="noopener"', $anchor);
        }
    }

    public function test_the_mosaic_spans_tile_the_six_column_grid_exactly(): void
    {
        $html = $this->get(route('student-development'))->assertOk()->getContent();

        preg_match_all('/style="--c:(\d+);--r:(\d+)"/', $html, $matches, PREG_SET_ORDER);

        $this->assertCount(13, $matches, 'The mosaic should render 13 track tiles.');

        // Column x row area of every tile, plus the full-width closing strip,
        // has to be a whole number of six-column rows — otherwise the last row
        // of the "random" grid ends in a visible hole.
        $area = array_sum(array_map(fn (array $m) => (int) $m[1] * (int) $m[2], $matches)) + 6;

        $this->assertSame(0, $area % 6, 'The mosaic no longer tiles its six columns — it will render with a gap.');
        $this->assertSame(36, $area);
    }

    /* ─────────────────────── FAQ ─────────────────────── */

    public function test_the_faq_is_rendered_with_matching_structured_data(): void
    {
        $html = $this->get(route('student-development'))->assertOk()->getContent();

        $this->assertStringContainsString('What are the benefits of learning extra skills alongside my degree?', $html);
        $this->assertStringContainsString('"@type": "FAQPage"', $html);

        // Structured data that does not match the visible page is a rich-result
        // violation, so the two counts have to move together.
        $visible = preg_match_all('/class="sdp-faq__q"/', $html);
        $structured = preg_match_all('/"@type": "Question"/', $html);

        $this->assertSame(8, $visible);
        $this->assertSame($visible, $structured);
    }

    /* ─────────────────────── Home hero link ─────────────────────── */

    public function test_the_home_hero_button_links_to_the_page(): void
    {
        $action = collect((new HeroContent())->forDisplay()['actions'])
            ->firstWhere('label', 'Student Development Programme');

        $this->assertNotNull($action, 'The hero no longer has a "Student Development Programme" button.');
        $this->assertSame('/student-development-programme', $action['href']);
        $this->assertNotSame('disabled', $action['style'], 'The button must be clickable, not a "coming soon" placeholder.');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="/student-development-programme"', false);
    }

    public function test_a_stored_coming_soon_placeholder_is_back_filled_on_read(): void
    {
        // home-hero.json is gitignored and restored from a server backup on
        // deploy, so every existing box still holds the disabled placeholder.
        // Without the back-fill the button would stay dead there for good.
        file_put_contents($this->heroPath, json_encode([
            'eyebrow' => 'Global Admissions',
            'actions' => [
                ['label' => 'Student Development Programme', 'icon' => 'graduation-cap', 'href' => '', 'style' => 'disabled', 'row' => 0],
            ],
        ]));

        $action = (new HeroContent())->current()['actions'][0];

        $this->assertSame('/student-development-programme', $action['href']);
        $this->assertSame('ghost', $action['style']);
    }

    public function test_an_edited_button_is_left_alone(): void
    {
        // Once an admin saves the hero, the stored value is the authority — the
        // back-fill must never overwrite a deliberate choice.
        file_put_contents($this->heroPath, json_encode([
            'actions' => [
                ['label' => 'Student Development Programme', 'icon' => 'graduation-cap', 'href' => '/somewhere-else', 'style' => 'orange', 'row' => 0],
            ],
        ]));

        $action = (new HeroContent())->current()['actions'][0];

        $this->assertSame('/somewhere-else', $action['href']);
        $this->assertSame('orange', $action['style']);
    }
}
