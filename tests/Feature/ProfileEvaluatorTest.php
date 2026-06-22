<?php

namespace Tests\Feature;

use Tests\Concerns\PreservesProfileSubmissions;
use Tests\TestCase;

/**
 * Covers the isolated Profile Evaluator module (the mim-essay "Evaluate My
 * Profile" rebuild): the wizard renders with its config + assets, the
 * session-only save/submit/reset endpoint behaves, and — like the Student
 * Profiler — submit returns NO score/rating. Nothing here touches the DB.
 */
class ProfileEvaluatorTest extends TestCase
{
    use PreservesProfileSubmissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupSubmissions();
    }

    protected function tearDown(): void
    {
        $this->restoreSubmissions();
        parent::tearDown();
    }

    public function test_wizard_page_renders_with_config_and_assets(): void
    {
        $this->get('/evaluate-my-profile')
            ->assertOk()
            ->assertSee('data-pe-root', false)
            ->assertSee('window.__EVALUATOR__', false)
            ->assertSee('assets/profile-evaluator/profile-evaluator.js', false)
            ->assertSee('Evaluate Me', false)
            ->assertSee('What is your College CGPA or Percentage?', false);
    }

    public function test_save_persists_answers_and_section_to_session(): void
    {
        $this->post('/evaluate-my-profile', [
            'action'  => 'save',
            'section' => 2,
            'answers' => ['q_cgpa' => 'Above 90% or 9 CGPA', 'q_target_countries' => ['USA', 'Canada']],
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertSame(2, session('evaluator.section'));
        $this->assertSame('Above 90% or 9 CGPA', session('evaluator.answers')['q_cgpa']);
        $this->assertSame(['USA', 'Canada'], session('evaluator.answers')['q_target_countries']);
    }

    public function test_section_is_clamped_to_valid_range(): void
    {
        // 6 sections → review step == index 6; anything beyond is clamped to 6.
        $this->post('/evaluate-my-profile', ['action' => 'save', 'section' => 99])
            ->assertOk()->assertJson(['ok' => true]);

        $this->assertSame(6, session('evaluator.section'));
    }

    public function test_submit_marks_submitted_without_any_rating(): void
    {
        $res = $this->post('/evaluate-my-profile', [
            'action'  => 'submit',
            'section' => 6,
            'answers' => ['q_cgpa' => 'Above 80% or 8 CGPA'],
        ])->assertOk()->assertJson(['ok' => true]);

        // Scoring is intentionally absent — no score/band/shortlist returned.
        $this->assertNull($res->json('report'));
        $this->assertArrayNotHasKey('score', (array) $res->json());
        $this->assertTrue(session('evaluator.submitted'));
    }

    public function test_reset_clears_session(): void
    {
        $this->withSession(['evaluator.section' => 3, 'evaluator.answers' => ['q_cgpa' => 'x']])
            ->post('/evaluate-my-profile', ['action' => 'reset'])
            ->assertOk()->assertJson(['ok' => true]);

        $this->assertNull(session('evaluator.section'));
        $this->assertNull(session('evaluator.answers'));
    }

    public function test_restored_session_is_passed_to_view_state(): void
    {
        $this->withSession(['evaluator.section' => 2])
            ->get('/evaluate-my-profile')
            ->assertOk()
            ->assertSee('"section":2', false);
    }
}
