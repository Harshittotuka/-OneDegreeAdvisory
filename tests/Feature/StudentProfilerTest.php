<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Covers the isolated Student Profiler module: the wizard renders with its
 * config + assets, the session-only save/submit/reset endpoint behaves, and a
 * bad degree is rejected. Nothing here touches the DB or any existing feature.
 */
class StudentProfilerTest extends TestCase
{
    public function test_wizard_page_renders_with_config_and_assets(): void
    {
        $this->get('/profiler')
            ->assertOk()
            ->assertSee('data-sp-root', false)
            ->assertSee('window.__PROFILER__', false)
            ->assertSee('assets/student-profiler/student-profiler.js', false)
            ->assertSee('"degreeOrder"', false);
    }

    public function test_save_persists_degree_and_answers_to_session(): void
    {
        $this->post('/profiler', [
            'action'  => 'save',
            'degree'  => 'masters',
            'section' => 2,
            'answers' => ['score' => '8.4', 'destinations' => ['USA', 'Canada']],
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertSame('masters', session('profiler.degree'));
        $this->assertSame(2, session('profiler.section'));
        $this->assertSame('8.4', session('profiler.answers')['score']);
    }

    public function test_invalid_degree_is_rejected(): void
    {
        $this->post('/profiler', ['action' => 'save', 'degree' => 'hacker', 'section' => 1])
            ->assertOk()->assertJson(['ok' => true]);

        $this->assertNull(session('profiler.degree'));
    }

    public function test_submit_marks_submitted_without_any_rating(): void
    {
        $res = $this->post('/profiler', [
            'action'  => 'submit',
            'degree'  => 'masters',
            'section' => 5,
            'answers' => ['q_abc' => 'something'],
        ])->assertOk()->assertJson(['ok' => true]);

        // Scoring is removed entirely — no score/band/shortlist must be returned.
        $this->assertNull($res->json('report'));
        $this->assertNull($res->json('report.score'));
        $this->assertArrayNotHasKey('score', (array) $res->json());
        $this->assertTrue(session('profiler.submitted'));
    }

    public function test_reset_clears_session(): void
    {
        $this->withSession(['profiler.degree' => 'masters', 'profiler.section' => 3])
            ->post('/profiler', ['action' => 'reset'])
            ->assertOk()->assertJson(['ok' => true]);
    }

    public function test_restored_session_is_passed_to_view_state(): void
    {
        $this->withSession(['profiler.degree' => 'doctorate', 'profiler.section' => 1])
            ->get('/profiler')
            ->assertOk()
            ->assertSee('"degree":"doctorate"', false);
    }
}
