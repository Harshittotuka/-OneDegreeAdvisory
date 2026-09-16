<?php

namespace Tests\Feature;

use App\Models\CrmLead;
use App\Models\CrmWebsiteSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the isolated Student Profiler module: the wizard renders with its
 * config + assets, the endpoint behaves, and a completed profile is recorded on
 * submit. Progress is NOT cached — nothing is persisted to the session while
 * filling in the wizard — so the save/reset actions are no-ops.
 */
class StudentProfilerTest extends TestCase
{
    use RefreshDatabase;

    private function profilerField(array $config, string $degree, string $key): array
    {
        foreach ($config['sections'][$degree] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                if (($field['key'] ?? null) === $key) {
                    return $field;
                }
            }
        }

        $this->fail("Missing profiler field {$degree}:{$key}");
    }

    public function test_wizard_page_renders_with_config_and_assets(): void
    {
        $this->get('/profiler')
            ->assertOk()
            ->assertSee('data-sp-root', false)
            ->assertSee('window.__PROFILER__', false)
            ->assertSee('assets/student-profiler/student-profiler.js', false)
            ->assertSee('"degreeOrder"', false);
    }

    public function test_save_action_does_not_persist_or_record(): void
    {
        // Progress is not cached: a 'save' writes nothing to the session and
        // records no submission — only 'submit' does.
        $this->post('/profiler', [
            'action'  => 'save',
            'degree'  => 'masters',
            'section' => 2,
            'answers' => ['q_ec_level' => 'Just Participated'],
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertNull(session('profiler.degree'));
        $this->assertNull(session('profiler.answers'));
        $this->assertDatabaseCount('crm_website_submissions', 0);
    }

    public function test_wizard_always_starts_fresh(): void
    {
        // Any pre-existing session state is ignored — the view is always fresh.
        $this->withSession(['profiler.degree' => 'doctorate', 'profiler.section' => 1])
            ->get('/profiler')
            ->assertOk()
            ->assertSee('"degree":null', false)
            ->assertDontSee('"degree":"doctorate"', false);
    }

    public function test_number_only_profiler_fields_have_input_rules(): void
    {
        $config = require base_path('app/Modules/StudentProfiler/questionnaire.php');

        $this->assertSame('decimal', $this->profilerField($config, 'highschool', 'q_hs_result_c9_actual')['input']['kind']);
        $this->assertSame(100, $this->profilerField($config, 'highschool', 'q_hs_result_c9_actual')['input']['max']);
        $this->assertSame(100, $this->profilerField($config, 'bachelors', 'q_a87ff679')['input']['max']);
        $this->assertSame('integer', $this->profilerField($config, 'bachelors', 'q_45c48cce')['input']['kind']);
        $this->assertSame(4, $this->profilerField($config, 'bachelors', 'q_45c48cce')['input']['maxLength']);
        $this->assertSame(100, $this->profilerField($config, 'masters', 'q_37693cfc')['input']['max']);
        $this->assertSame('integer', $this->profilerField($config, 'masters', 'q_1ff1de77')['input']['kind']);
        $this->assertSame(100, $this->profilerField($config, 'doctorate', 'q_6c8349cc')['input']['max']);
        $this->assertSame('engscore', $this->profilerField($config, 'highschool', 'q_9bf31c7f')['type']);
        $this->assertSame('9', $this->profilerField($config, 'highschool', 'q_9bf31c7f')['tests'][0]['max']);
        $this->assertSame('engscore', $this->profilerField($config, 'masters', 'q_3dd48ab3')['type']);
        $this->assertSame('9', $this->profilerField($config, 'masters', 'q_3dd48ab3')['tests'][0]['max']);
        $this->assertFalse($this->profilerField($config, 'masters', 'q_3dd48ab3')['overallRequired']);
        $this->assertArrayNotHasKey('input', $this->profilerField($config, 'masters', 'q_98f13708'));
    }

    public function test_submit_records_without_any_rating(): void
    {
        $res = $this->post('/profiler', [
            'action'  => 'submit',
            'degree'  => 'masters',
            'section' => 6,
            'answers' => ['q_ec_level' => 'Just Participated'],
        ])->assertOk()->assertJson(['ok' => true]);

        // Scoring is removed entirely — no score/band/shortlist must be returned.
        $this->assertNull($res->json('report'));
        $this->assertArrayNotHasKey('score', (array) $res->json());

        // The completed profile is recorded once.
        $submission = CrmWebsiteSubmission::query()->sole();
        $this->assertSame('profiler', $submission->source);
        $this->assertSame('masters', $submission->degree);
        $this->assertNotNull($submission->lead);
    }

    /**
     * Still records nothing, but no longer answers "ok" while doing it.
     *
     * The bland 200 was there so a prober could not learn which degrees are
     * valid. It hid nothing — the wizard renders the whole list on the page —
     * and it meant a real visitor whose degree went missing was thanked for a
     * submission that stored nothing and emailed no one.
     */
    public function test_submit_with_invalid_degree_records_nothing(): void
    {
        $this->postJson('/profiler', [
            'action'  => 'submit',
            'degree'  => 'hacker',
            'section' => 1,
            'answers' => ['x' => 'y'],
        ])->assertStatus(422)->assertJson(['ok' => false, 'field' => 'degree']);

        $this->assertDatabaseCount('crm_website_submissions', 0);
    }

    public function test_reset_action_returns_ok(): void
    {
        $this->post('/profiler', ['action' => 'reset'])
            ->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseCount('crm_website_submissions', 0);
    }
    /**
     * The bug this covers: a submit carrying no valid degree fell past the
     * capture and still answered with the success message. The visitor was
     * thanked, no lead was stored, no mail was sent and nothing was logged —
     * eight real submissions were lost that way on UAT before it was noticed.
     */
    public function test_a_submit_without_a_degree_is_refused_rather_than_thanked(): void
    {
        foreach ([null, '', 'not-a-degree'] as $degree) {
            $this->postJson('/profiler', [
                'action' => 'submit', 'degree' => $degree, 'section' => 6,
                'answers' => ['q_ec_level' => 'Just Participated'],
                'contact' => ['name' => 'Lost Student', 'email' => 'lost@mailbox.test', 'phone' => '9998887771'],
            ])->assertStatus(422)->assertJson(['ok' => false, 'field' => 'degree']);
        }

        // Nothing captured, and no success message anywhere near it.
        $this->assertSame(0, CrmLead::query()->count());
        $this->assertSame(0, CrmWebsiteSubmission::query()->count());
    }

    /** A placeholder address is refused, and says why rather than "invalid". */
    public function test_a_placeholder_email_is_refused_with_a_reason(): void
    {
        $response = $this->postJson('/profiler', [
            'action' => 'submit', 'degree' => 'masters', 'section' => 6,
            'answers' => ['q_ec_level' => 'Just Participated'],
            'contact' => ['name' => 'Tester', 'email' => 'test@example.com', 'phone' => '9998887772'],
        ])->assertStatus(422)->assertJson(['ok' => false, 'field' => 'email']);

        $this->assertStringContainsString('example.com', $response->json('message'));
        $this->assertSame(0, CrmLead::query()->count());
    }

}
