<?php

namespace Tests\Feature;

use App\Support\ProfileSubmissionStore;
use Tests\Concerns\PreservesProfileSubmissions;
use Tests\TestCase;

/**
 * Covers the questionnaire-submission pipeline that now backs both the Student
 * Profiler and the Profile Evaluator: the new Extracurriculars & differentiators
 * section is present for every degree, a completed questionnaire is recorded as
 * a readable snapshot, and the admin panel can list / view / delete / export them.
 */
class ProfileSubmissionsTest extends TestCase
{
    use PreservesProfileSubmissions;

    protected function setUp(): void
    {
        parent::setUp();
        // Start every test from a clean slate, then restore the real file after.
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

    public function test_every_degree_has_the_extracurriculars_section_after_academics(): void
    {
        $config = require base_path('app/Modules/StudentProfiler/questionnaire.php');

        foreach (['bachelors', 'masters', 'doctorate'] as $degree) {
            $sections = $config['sections'][$degree];
            $keys = array_column($sections, 'key');

            $this->assertContains('extracurricular', $keys, "$degree is missing the extracurricular section");

            // It must sit immediately AFTER the last academic-background section.
            $afterKey = $degree === 'doctorate' ? 'experience' : 'academic';
            $this->assertSame(
                array_search($afterKey, $keys, true) + 1,
                array_search('extracurricular', $keys, true),
                "extracurricular is not placed right after '$afterKey' for $degree"
            );

            // The four MIM-derived fields are present.
            $ec = $sections[array_search('extracurricular', $keys, true)];
            $this->assertSame(
                ['q_ec_engaged', 'q_ec_level', 'q_ec_current', 'q_differentiators'],
                array_column($ec['fields'], 'key')
            );
        }
    }

    public function test_profiler_wizard_page_shows_the_new_questions(): void
    {
        // The config is embedded via Blade @json (which escapes & to &),
        // so assert on substrings without the ampersand.
        $this->get('/profiler')
            ->assertOk()
            ->assertSee('Your extracurriculars', false)
            ->assertSee('What has been your highest level of participation?', false)
            ->assertSee('Which of these differentiators apply to you?', false);
    }

    public function test_profiler_submit_records_a_readable_snapshot(): void
    {
        $this->post('/profiler', [
            'action'  => 'submit',
            'degree'  => 'masters',
            'section' => 6,
            'answers' => [
                'q_ec_engaged'      => ['Competitive Sports', 'Competitions'],
                'q_ec_level'        => 'Held Leadership positions',
                'q_differentiators' => ['Successful Entrepreneurial Venture'],
            ],
        ])->assertOk()->assertJson(['ok' => true]);

        $rows = $this->store()->all();
        $this->assertCount(1, $rows);

        $row = $rows[0];
        $this->assertSame('profiler', $row['source']);
        $this->assertSame('masters', $row['degree']);

        // Snapshot is grouped by section and keyed by the verbatim question label.
        $flat = [];
        foreach ($row['sections'] as $sec) {
            foreach ($sec['answers'] as $a) {
                $flat[$a['label']] = $a['value'];
            }
        }
        $this->assertSame(['Competitive Sports', 'Competitions'], $flat['What all of the following have you been engaged in? (last 2 years only, before that has a limited effect)']);
        $this->assertSame(['Held Leadership positions'], $flat['What has been your highest level of participation?']);
        $this->assertSame(['Successful Entrepreneurial Venture'], $flat['Which of these differentiators apply to you?']);
    }

    public function test_evaluator_submit_records_a_submission(): void
    {
        $this->post('/evaluate-my-profile', [
            'action'  => 'submit',
            'section' => 6,
            'answers' => [
                'q_cgpa'       => 'Above 90% or 9 CGPA',
                'q_ec_engaged' => ['Associations / Clubs'],
            ],
        ])->assertOk()->assertJson(['ok' => true]);

        $rows = $this->store()->bySource('evaluator');
        $this->assertCount(1, $rows);
        $this->assertNull($rows[0]['degree']);

        $labels = [];
        foreach ($rows[0]['sections'] as $sec) {
            $labels = array_merge($labels, array_column($sec['answers'], 'label'));
        }
        $this->assertContains('What is your College CGPA or Percentage?', $labels);
    }

    public function test_submitting_twice_in_one_session_records_only_once(): void
    {
        $payload = ['action' => 'submit', 'degree' => 'bachelors', 'section' => 6, 'answers' => ['q_ec_level' => 'Just Participated']];

        $session = $this->withSession([]);
        $session->post('/profiler', $payload)->assertOk();
        // A re-POST (e.g. double click / refresh) in the same session must not duplicate.
        $session->post('/profiler', $payload)->assertOk();

        $this->assertCount(1, $this->store()->all());
    }

    public function test_admin_can_list_view_and_delete_submissions(): void
    {
        $id = $this->store()->add('profiler', 'Student Profiler', 'doctorate', [
            ['eyebrow' => 'Extracurriculars', 'title' => 'Your extracurriculars & differentiators', 'answers' => [
                ['label' => 'Are you still involved in the same?', 'value' => ['Yes, Heavily']],
            ]],
        ]);

        $admin = $this->withSession(['cms_authenticated' => true]);

        $admin->get(route('admin.submissions.index'))
            ->assertOk()
            ->assertSee('Profiler submissions')
            ->assertSee('Student Profiler')
            ->assertSee('doctorate');

        $admin->get(route('admin.submissions.show', $id))
            ->assertOk()
            ->assertSee('Are you still involved in the same?')
            ->assertSee('Yes, Heavily');

        $admin->post(route('admin.submissions.destroy'), ['id' => $id])
            ->assertRedirect(route('admin.submissions.index'));

        $this->assertCount(0, $this->store()->all());
    }

    public function test_admin_submissions_require_authentication(): void
    {
        $this->get(route('admin.submissions.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_export_submissions_as_csv(): void
    {
        $this->store()->add('evaluator', 'Profile Evaluator', null, [
            ['eyebrow' => 'Academics', 'title' => 'Your academics', 'answers' => [
                ['label' => 'What is your College CGPA or Percentage?', 'value' => ['Above 80% or 8 CGPA']],
            ]],
        ]);

        $response = $this->withSession(['cms_authenticated' => true])
            ->get(route('admin.submissions.export'))
            ->assertOk();

        $csv = $response->streamedContent();
        // fputcsv quotes the "Submitted at" cell (it contains a space), so assert
        // the un-quoted tail of the header plus the data values.
        $this->assertStringContainsString('Source,Degree,Section,Question,Answer', $csv);
        $this->assertStringContainsString('Submitted at', $csv);
        $this->assertStringContainsString('Profile Evaluator', $csv);
        $this->assertStringContainsString('Above 80% or 8 CGPA', $csv);
    }
}
