<?php

namespace Tests\Feature;

use App\Support\ProfileSubmissionStore;
use Tests\Concerns\PreservesProfileSubmissions;
use Tests\TestCase;

/**
 * Covers the Student Profiler submission pipeline: the Extracurriculars &
 * differentiators section is present for every degree, a completed
 * questionnaire is recorded as a readable snapshot, and the admin panel can
 * list / view / delete / export them.
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

        foreach (['highschool', 'bachelors', 'masters', 'doctorate'] as $degree) {
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

            // The four MIM-derived fields are present; masters & doctorate
            // additionally carry the "notable achievements" multi-select.
            $ec = $sections[array_search('extracurricular', $keys, true)];
            $expected = ['q_ec_engaged', 'q_ec_level', 'q_ec_current', 'q_differentiators'];
            if (in_array($degree, ['masters', 'doctorate'], true)) {
                $expected[] = 'q_achievements';
            }
            $this->assertSame($expected, array_column($ec['fields'], 'key'));
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

    public function test_highschool_degree_mirrors_bachelors_questions(): void
    {
        $config = require base_path('app/Modules/StudentProfiler/questionnaire.php');

        // High School is offered as its own level, using the Bachelor's question set.
        $this->assertContains('highschool', $config['degreeOrder']);
        $this->assertSame('High School', $config['degrees']['highschool']['label']);
        $this->assertSame(
            $config['sections']['bachelors'],
            $config['sections']['highschool'],
            'High School questions should mirror the Bachelor\'s set'
        );
    }

    public function test_highschool_submit_records_a_readable_snapshot(): void
    {
        $this->post('/profiler', [
            'action'  => 'submit',
            'degree'  => 'highschool',
            'section' => 6,
            'answers' => ['q_ec_level' => 'Held Leadership positions'],
        ])->assertOk()->assertJson(['ok' => true]);

        $rows = $this->store()->all();
        $this->assertCount(1, $rows);
        $this->assertSame('profiler', $rows[0]['source']);
        $this->assertSame('highschool', $rows[0]['degree']);
    }

    public function test_progress_is_not_cached_each_submit_records(): void
    {
        // Progress is no longer cached or de-duplicated server-side (the submit
        // button is disabled client-side to prevent accidental double clicks),
        // so each submit POST records its own row.
        $payload = ['action' => 'submit', 'degree' => 'bachelors', 'section' => 6, 'answers' => ['q_ec_level' => 'Just Participated']];

        $session = $this->withSession([]);
        $session->post('/profiler', $payload)->assertOk();
        $session->post('/profiler', $payload)->assertOk();

        $this->assertCount(2, $this->store()->all());
    }

    public function test_admin_can_list_view_and_delete_submissions(): void
    {
        $id = $this->store()->add('profiler', 'Student Profiler', 'doctorate', [
            ['eyebrow' => 'Extracurriculars', 'title' => 'Your extracurriculars & differentiators', 'answers' => [
                ['label' => 'Are you still involved in the same?', 'value' => ['Yes, Heavily']],
            ]],
        ]);

        $admin = $this->withSession(['cms_authenticated' => true]);

        $admin->get(route('admin.submissions.profiler'))
            ->assertOk()
            ->assertSee('Student Profiler submissions')
            ->assertSee('doctorate')
            // It now lives in the Admin portal (orange), not the CMS Content Studio.
            ->assertSee('portal-admin', false)
            ->assertSee('Admin Portal')
            ->assertDontSee('Content Studio');

        $admin->get(route('admin.submissions.show', $id))
            ->assertOk()
            ->assertSee('Are you still involved in the same?')
            ->assertSee('Yes, Heavily');

        // Deleting a profiler submission returns to the Student Profiler tab.
        $admin->post(route('admin.submissions.destroy'), ['id' => $id])
            ->assertRedirect(route('admin.submissions.profiler'));

        $this->assertCount(0, $this->store()->all());
    }

    public function test_submissions_list_shows_only_profiler_source(): void
    {
        $this->store()->add('profiler', 'Student Profiler', 'masters', [
            ['eyebrow' => 'Academics', 'title' => 'Academics', 'answers' => [
                ['label' => 'Q', 'value' => ['PROFILER_ONLY_MARKER']],
            ]],
        ]);
        // A stray record from the (now removed) evaluator must never surface.
        $this->store()->add('evaluator', 'Profile Evaluator', null, [
            ['eyebrow' => 'Academics', 'title' => 'Academics', 'answers' => [
                ['label' => 'Q', 'value' => ['EVALUATOR_ONLY_MARKER']],
            ]],
        ]);

        $admin = $this->withSession(['cms_authenticated' => true]);

        // Bare /admin/submissions defaults to the Student Profiler list.
        $admin->get(route('admin.submissions.index'))->assertRedirect(route('admin.submissions.profiler'));

        $admin->get(route('admin.submissions.profiler'))
            ->assertOk()
            ->assertSee('PROFILER_ONLY_MARKER')
            ->assertDontSee('EVALUATOR_ONLY_MARKER');
    }

    public function test_admin_submissions_require_authentication(): void
    {
        $this->get(route('admin.submissions.profiler'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_export_submissions_as_csv(): void
    {
        $this->store()->add('profiler', 'Student Profiler', 'masters', [
            ['eyebrow' => 'Academics', 'title' => 'Your academics', 'answers' => [
                ['label' => 'Your Board in 12th Class', 'value' => ['CBSE Board']],
            ]],
        ], ['name' => 'Riya Sharma', 'email' => 'riya@example.com', 'phone' => '+91 99999 11111']);

        $response = $this->withSession(['cms_authenticated' => true])
            ->get(route('admin.submissions.export'))
            ->assertOk();

        $csv = $response->streamedContent();
        // fputcsv quotes the "Submitted at" cell (it contains a space), so assert
        // the un-quoted tail of the header plus the data values.
        $this->assertStringContainsString('Source,Name,Email,Phone,Degree,Section,Question,Answer', $csv);
        $this->assertStringContainsString('Submitted at', $csv);
        $this->assertStringContainsString('Student Profiler', $csv);
        $this->assertStringContainsString('CBSE Board', $csv);
        // Lead contact is exported alongside every answer row.
        $this->assertStringContainsString('Riya Sharma', $csv);
        $this->assertStringContainsString('riya@example.com', $csv);
    }

    public function test_profiler_submit_captures_lead_contact_into_meta(): void
    {
        $this->post('/profiler', [
            'action'  => 'submit',
            'degree'  => 'masters',
            'section' => 6,
            'answers' => ['q_ec_level' => 'Just Participated'],
            'contact' => ['name' => 'Neha Gupta', 'email' => 'neha@example.com', 'phone' => '9876543210'],
        ])->assertOk()->assertJson(['ok' => true]);

        $row = $this->store()->all()[0];
        $this->assertSame('Neha Gupta', $row['meta']['name']);
        $this->assertSame('neha@example.com', $row['meta']['email']);
    }

    public function test_admin_list_and_detail_show_lead_contact(): void
    {
        $this->store()->add('profiler', 'Student Profiler', 'masters', [
            ['eyebrow' => 'Academics', 'title' => 'Academics', 'answers' => [
                ['label' => 'Q', 'value' => ['A']],
            ]],
        ], ['name' => 'Karan Mehta', 'email' => 'karan@example.com', 'phone' => '+91 80000 00000']);

        $admin = $this->withSession(['cms_authenticated' => true]);

        $admin->get(route('admin.submissions.profiler'))
            ->assertOk()
            ->assertSee('Karan Mehta')
            ->assertSee('karan@example.com');

        $id = $this->store()->all()[0]['id'];
        $admin->get(route('admin.submissions.show', $id))
            ->assertOk()
            ->assertSee('Karan Mehta')
            ->assertSee('+91 80000 00000');
    }

    public function test_admin_table_view_renders_answers_as_columns(): void
    {
        $this->store()->add('profiler', 'Student Profiler', 'masters', [
            ['eyebrow' => 'Academics', 'title' => 'Your academics', 'answers' => [
                ['label' => 'Your Board in 12th Class', 'value' => ['CBSE Board']],
            ]],
        ], ['name' => 'Tara Singh', 'email' => 'tara@example.com', 'phone' => '12345']);

        $this->withSession(['cms_authenticated' => true])
            ->get(route('admin.submissions.profiler', ['view' => 'table']))
            ->assertOk()
            ->assertSee('subs-grid', false)                     // flat grid rendered
            ->assertSee('Your Board in 12th Class', false)       // question is a column header
            ->assertSee('CBSE Board')                            // answer in a cell
            ->assertSee('Tara Singh');
    }

    public function test_admin_can_export_submissions_as_excel(): void
    {
        $this->store()->add('profiler', 'Student Profiler', 'masters', [
            ['eyebrow' => 'Academics', 'title' => 'Your academics', 'answers' => [
                ['label' => 'Your Board in 12th Class', 'value' => ['CBSE Board']],
            ]],
        ], ['name' => 'Tara Singh', 'email' => 'tara@example.com', 'phone' => '12345']);

        $res = $this->withSession(['cms_authenticated' => true])
            ->get(route('admin.submissions.export-excel', ['source' => 'profiler']))
            ->assertOk();

        $this->assertStringContainsString('spreadsheetml.sheet', (string) $res->headers->get('Content-Type'));
        $this->assertStringContainsString('.xlsx', (string) $res->headers->get('Content-Disposition'));

        $content = $res->getContent();
        $this->assertStringStartsWith('PK', $content); // ZIP magic bytes (a real .xlsx)
        // The package is a "stored" (uncompressed) zip, so the inline strings
        // appear verbatim in the bytes — handy to assert the data made it in.
        $this->assertStringContainsString('Tara Singh', $content);
        $this->assertStringContainsString('Your Board in 12th Class', $content);
    }

    public function test_admin_can_download_a_submission_as_doc(): void
    {
        $id = $this->store()->add('profiler', 'Student Profiler', 'masters', [
            ['eyebrow' => 'Academics', 'title' => 'Your academics', 'answers' => [
                ['label' => 'Your Board in 12th Class', 'value' => ['CBSE Board']],
            ]],
        ], ['name' => 'Asha Rao', 'email' => 'asha@example.com', 'phone' => '999']);

        $res = $this->withSession(['cms_authenticated' => true])
            ->get(route('admin.submissions.download', $id))
            ->assertOk();

        $this->assertStringContainsString('application/msword', (string) $res->headers->get('Content-Type'));
        $this->assertStringContainsString('.doc', (string) $res->headers->get('Content-Disposition'));

        $content = $res->getContent();
        // The doc mirrors the on-screen cards: section, question label, answer chip, contact.
        $this->assertStringContainsString('Your academics', $content);
        $this->assertStringContainsString('Your Board in 12th Class', $content);
        $this->assertStringContainsString('CBSE Board', $content);
        $this->assertStringContainsString('Asha Rao', $content);
    }

    public function test_submission_download_requires_authentication(): void
    {
        $id = $this->store()->add('profiler', 'Student Profiler', 'masters', []);
        $this->get(route('admin.submissions.download', $id))->assertRedirect(route('admin.login'));
    }
}
