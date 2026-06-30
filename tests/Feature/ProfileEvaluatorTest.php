<?php

namespace Tests\Feature;

use App\Support\ProfileSubmissionStore;
use Tests\Concerns\PreservesProfileSubmissions;
use Tests\TestCase;

/**
 * Covers the isolated Profile Evaluator module (the mim-essay "Evaluate My
 * Profile" rebuild): the wizard renders with its config + assets, the endpoint
 * behaves, and a completed profile is recorded on submit. Progress is NOT
 * cached — nothing is persisted to the session — so save/reset are no-ops, and
 * like the Student Profiler, submit returns NO score/rating.
 */
class ProfileEvaluatorTest extends TestCase
{
    use PreservesProfileSubmissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupSubmissions();
        $path = storage_path('app/profile-submissions.json');
        if (is_file($path)) {
            @unlink($path);
        }
    }

    protected function tearDown(): void
    {
        $this->restoreSubmissions();
        parent::tearDown();
    }

    private function store(): ProfileSubmissionStore
    {
        return new ProfileSubmissionStore();
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

    public function test_save_action_does_not_persist_or_record(): void
    {
        // Progress is not cached: a 'save' writes nothing to the session and
        // records no submission.
        $this->post('/evaluate-my-profile', [
            'action'  => 'save',
            'section' => 2,
            'answers' => ['q_cgpa' => 'Above 90% or 9 CGPA'],
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertNull(session('evaluator.section'));
        $this->assertNull(session('evaluator.answers'));
        $this->assertCount(0, $this->store()->all());
    }

    public function test_wizard_always_starts_fresh(): void
    {
        // Any pre-existing session state is ignored — the view always starts at 0.
        $this->withSession(['evaluator.section' => 2])
            ->get('/evaluate-my-profile')
            ->assertOk()
            ->assertSee('"section":0', false)
            ->assertDontSee('"section":2', false);
    }

    public function test_submit_records_without_any_rating(): void
    {
        $res = $this->post('/evaluate-my-profile', [
            'action'  => 'submit',
            'section' => 6,
            'answers' => ['q_cgpa' => 'Above 80% or 8 CGPA'],
        ])->assertOk()->assertJson(['ok' => true]);

        // Scoring is intentionally absent — no score/band/shortlist returned.
        $this->assertNull($res->json('report'));
        $this->assertArrayNotHasKey('score', (array) $res->json());

        // The completed evaluation is recorded once.
        $rows = $this->store()->bySource('evaluator');
        $this->assertCount(1, $rows);
        $this->assertNull($rows[0]['degree']);
    }

    public function test_reset_action_returns_ok(): void
    {
        $this->post('/evaluate-my-profile', ['action' => 'reset'])
            ->assertOk()->assertJson(['ok' => true]);

        $this->assertCount(0, $this->store()->all());
    }
}
