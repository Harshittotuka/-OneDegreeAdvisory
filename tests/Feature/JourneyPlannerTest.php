<?php

namespace Tests\Feature;

use App\Http\Middleware\StudentAuth;
use App\Mail\JourneyMeetingMail;
use App\Models\CrmJourneyApplication;
use App\Models\CrmJourneyDocument;
use App\Models\CrmJourneyPlan;
use App\Models\CrmLead;
use App\Models\CrmLeadActivity;
use App\Models\CrmStudentAccount;
use App\Models\CrmUser;
use App\Support\JourneyPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JourneyPlannerTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'counsellor', array $extra = []): CrmUser
    {
        static $n = 0;
        $n++;

        return CrmUser::query()->create(array_merge([
            'name' => ucfirst($role).' '.$n, 'phone' => '98000000'.str_pad((string) $n, 2, '0', STR_PAD_LEFT),
            'email' => "{$role}{$n}@mailbox.test", 'role' => $role, 'is_active' => true,
        ], $extra));
    }

    private function student(CrmUser $counsellor, array $extra = []): CrmLead
    {
        static $n = 0;
        $n++;

        return CrmLead::query()->create(array_merge([
            'lead_number' => 'OD-2'.str_pad((string) $n, 4, '0', STR_PAD_LEFT), 'name' => 'Ananya Rao', 'phone' => '97000000'.str_pad((string) $n, 2, '0', STR_PAD_LEFT),
            'email' => "student{$n}@mailbox.test", 'priority' => 'medium', 'status' => 'converted',
            'is_student' => true, 'student_stage' => 'doc_pending', 'assigned_to' => $counsellor->id, 'intake' => 'Fall 2027',
        ], $extra));
    }

    private function as(CrmUser $user): static
    {
        return $this->withSession(['crm_user_id' => $user->id]);
    }

    /** Start the planner as the counsellor and return the temporary password it issued. */
    private function start(CrmUser $counsellor, CrmLead $lead): string
    {
        $response = $this->as($counsellor)->post(route('crm.journey.start', $lead))->assertRedirect(route('crm.journey.show', $lead));

        return $response->getSession()->get('journey_credentials')['password'];
    }

    /** A student who has signed in and chosen their own password. */
    private function signedInStudent(CrmLead $lead): static
    {
        $account = $lead->studentAccount()->firstOrFail();
        $account->forceFill(['must_change_password' => false])->save();
        $this->flushSession();

        return $this->withSession([StudentAuth::SESSION_KEY => $account->id]);
    }

    /* ------------------------------------------------------------ template */

    public function test_the_template_carries_the_workbook_content(): void
    {
        $this->assertCount(7, JourneyPlanner::phases());
        $this->assertCount(32, JourneyPlanner::coreDefinitions());
        $this->assertCount(22, JourneyPlanner::applicationDefinitions());

        // Keys are stored in every plan: they must stay unique across the template.
        $keys = collect(JourneyPlanner::phases())->flatMap(fn ($p) => array_column($p['activities'], 'key'));
        $this->assertSame($keys->count(), $keys->unique()->count());
    }

    public function test_percent_complete_leaves_excluded_and_not_applicable_out(): void
    {
        $r = JourneyPlanner::rollup([
            ['inc' => true, 'status' => 'Completed'],
            ['inc' => true, 'status' => 'In Progress'],
            ['inc' => true, 'status' => 'Not Applicable'],
            ['inc' => true, 'status' => 'Not Started'],
            ['inc' => false, 'status' => 'Completed'],
        ]);

        // Completed ÷ (Included − Not Applicable) = 1 ÷ (4 − 1).
        $this->assertSame(4, $r['included']);
        $this->assertSame(1, $r['completed']);
        $this->assertSame(33, $r['percent']);
    }

    public function test_activities_added_to_the_template_later_appear_in_old_plans(): void
    {
        $state = JourneyPlanner::normalise(['initial-consultation' => ['inc' => true, 'status' => 'Completed'], 'retired-key' => ['status' => 'Completed']], JourneyPlanner::coreDefinitions());

        $this->assertSame('Completed', $state['initial-consultation']['status']);
        $this->assertSame('Not Started', $state['gap-analysis']['status']);
        $this->assertArrayNotHasKey('retired-key', $state);
    }

    /* ------------------------------------------------------------ starting the planner */

    public function test_starting_the_planner_builds_the_plan_and_the_student_login(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor, ['email' => 'Ananya.Rao@Mailbox.test']);

        $password = $this->start($counsellor, $lead);

        $plan = CrmJourneyPlan::query()->where('crm_lead_id', $lead->id)->sole();
        $this->assertSame('Fall 2027', $plan->intake);
        $this->assertFalse($plan->coreState()['research-project']['inc'], 'Profile-building extras start switched off, as in the workbook.');

        $account = CrmStudentAccount::query()->where('crm_lead_id', $lead->id)->sole();
        $this->assertSame('ananya.rao@mailbox.test', $account->email);
        $this->assertTrue($account->must_change_password);
        $this->assertTrue(Hash::check($password, $account->password));
        $this->assertNotSame($password, $account->password, 'Only the hash is stored.');

        // The temporary password is shown once, on the planner page the start redirects to.
        $this->get(route('crm.journey.show', $lead))->assertOk()->assertSee($password)->assertSee('noindex, nofollow', false);
        $this->get(route('crm.journey.show', $lead))->assertOk()->assertDontSee($password);
    }

    public function test_starting_twice_keeps_the_first_login(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);
        $hash = $lead->studentAccount()->value('password');

        $this->as($counsellor)->post(route('crm.journey.start', $lead))->assertRedirect(route('crm.journey.show', $lead))->assertSessionMissing('journey_credentials');

        $this->assertDatabaseCount('crm_student_accounts', 1);
        $this->assertSame($hash, $lead->studentAccount()->value('password'));
    }

    public function test_the_planner_needs_the_students_email_to_start(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor, ['email' => null]);

        $this->as($counsellor)->post(route('crm.journey.start', $lead))->assertSessionHasErrors('planner');
        $this->assertDatabaseCount('crm_journey_plans', 0);
        $this->assertDatabaseCount('crm_student_accounts', 0);
    }

    public function test_the_planner_page_waits_for_the_start(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);

        $this->as($counsellor)->get(route('crm.journey.show', $lead))->assertRedirect()->assertSessionHasErrors('planner');
        $this->assertDatabaseCount('crm_journey_plans', 0);
    }

    public function test_only_people_who_can_see_the_lead_start_or_open_its_planner(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);

        $this->as($this->user())->post(route('crm.journey.start', $lead))->assertForbidden();
        $this->as($counsellor)->post(route('crm.journey.start', $this->student($counsellor, ['is_student' => false, 'status' => 'interested'])))->assertNotFound();
        $this->flushSession();
        $this->get(route('crm.journey.show', $lead))->assertRedirect(route('crm.login'));
    }

    public function test_the_crm_drawer_offers_to_start_the_planner(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);

        $this->as($counsellor)->get(route('crm.dashboard', ['view' => 'students', 'lead' => $lead->id]))
            ->assertOk()->assertSee('Start journey planner')->assertSee(route('crm.journey.start', $lead), false);

        $this->start($counsellor, $lead);
        $this->as($counsellor)->get(route('crm.dashboard', ['view' => 'students', 'lead' => $lead->id]))
            ->assertOk()->assertSee('Open journey planner')->assertSee('not signed in yet');
    }

    /* ------------------------------------------------------------ counsellor edits */

    public function test_completing_an_activity_records_today_as_its_completion_date(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $this->as($counsellor)->patchJson(route('crm.journey.activity', $lead), [
            'scope' => 'core', 'key' => 'english-test', 'status' => 'Completed', 'notes' => 'IELTS 7.5', 'target' => '2026-10-01',
        ])->assertOk()->assertJsonPath('activity.status', 'Completed')->assertJsonPath('activity.done', now()->toDateString());

        $row = $lead->journeyPlan->coreState()['english-test'];
        $this->assertSame('IELTS 7.5', $row['notes']);
        $this->assertSame('2026-10-01', $row['target']);
        $this->assertSame('team:'.$counsellor->id, $row['by']);
    }

    public function test_activity_edits_are_validated(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $this->as($counsellor)->patchJson(route('crm.journey.activity', $lead), ['scope' => 'core', 'key' => 'english-test', 'status' => 'Done'])->assertUnprocessable();
        $this->as($counsellor)->patchJson(route('crm.journey.activity', $lead), ['scope' => 'core', 'key' => 'english-test', 'owner' => 'Neighbour'])->assertUnprocessable();
        $this->as($counsellor)->patchJson(route('crm.journey.activity', $lead), ['scope' => 'core', 'key' => 'english-test', 'target' => '01/10/2026'])->assertUnprocessable();
        $this->as($counsellor)->patchJson(route('crm.journey.activity', $lead), ['scope' => 'core', 'key' => 'no-such-activity', 'status' => 'Completed'])->assertNotFound();
    }

    public function test_universities_get_the_standard_checklist_and_obvious_ticks(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $id = $this->as($counsellor)->postJson(route('crm.journey.applications.store', $lead), [
            'university' => 'HEC Paris', 'country' => 'France', 'program' => 'MiM', 'fit' => 'reach',
        ])->assertOk()->assertJsonPath('application.acts.fit.status', 'Completed')->json('application.id');

        $application = CrmJourneyApplication::query()->findOrFail($id);
        $this->assertCount(22, $application->activityState());
        $this->assertFalse($application->activityState()['visa-submitted']['inc'], 'Visa steps wait until a seat is confirmed.');

        $this->as($counsellor)->patchJson(route('crm.journey.applications.update', [$lead, $id]), ['offer_type' => 'Conditional'])
            ->assertOk()->assertJsonPath('application.offerType', 'Conditional')->assertJsonPath('application.acts.offer.status', 'Completed');

        $this->as($counsellor)->patchJson(route('crm.journey.activity', $lead), [
            'scope' => 'app', 'application_id' => $id, 'key' => 'visa-submitted', 'inc' => true,
        ])->assertOk()->assertJsonPath('activity.inc', true);

        $this->as($counsellor)->deleteJson(route('crm.journey.applications.destroy', [$lead, $id]))->assertOk();
        $this->assertDatabaseMissing('crm_journey_applications', ['id' => $id]);
    }

    public function test_a_university_from_another_students_plan_cannot_be_touched(): void
    {
        $counsellor = $this->user();
        $mine = $this->student($counsellor);
        $theirs = $this->student($counsellor);
        $this->start($counsellor, $mine);
        $this->start($counsellor, $theirs);
        $id = $this->as($counsellor)->postJson(route('crm.journey.applications.store', $theirs), ['university' => 'LSE'])->json('application.id');

        $this->as($counsellor)->patchJson(route('crm.journey.applications.update', [$mine, $id]), ['university' => 'Hijacked'])->assertNotFound();
        $this->as($counsellor)->patchJson(route('crm.journey.activity', $mine), ['scope' => 'app', 'application_id' => $id, 'key' => 'sop', 'status' => 'Completed'])->assertNotFound();
        $this->assertSame('LSE', CrmJourneyApplication::query()->find($id)->university);
    }

    public function test_a_partner_reads_the_planner_but_cannot_change_it(): void
    {
        $counsellor = $this->user();
        $partner = $this->user('partner', ['partner_access' => 'edit']);
        $lead = $this->student($counsellor, ['partner_id' => $partner->id]);

        $this->as($partner)->get(route('crm.journey.show', $lead))->assertOk()->assertSee("hasn't been started", false);
        $this->as($partner)->post(route('crm.journey.start', $lead))->assertForbidden();

        $this->start($counsellor, $lead);
        $this->as($partner)->get(route('crm.journey.show', $lead))->assertOk()->assertSee('"mode":"partner"', false)->assertDontSee('"login":{', false);
        $this->as($partner)->patchJson(route('crm.journey.activity', $lead), ['scope' => 'core', 'key' => 'english-test', 'status' => 'Completed'])->assertForbidden();
        $this->as($partner)->postJson(route('crm.journey.login.reset', $lead))->assertForbidden();
    }

    /* ------------------------------------------------------------ student login */

    public function test_a_student_signs_in_and_must_choose_their_own_password(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $password = $this->start($counsellor, $lead);
        $this->flushSession();

        $this->get(route('student.login'))->assertOk()->assertSee('Student portal');
        $this->post(route('student.login.attempt'), ['email' => strtoupper($lead->email), 'password' => $password])
            ->assertRedirect(route('student.password'));

        // Nothing else opens until the temporary password is replaced.
        $this->get(route('student.dashboard'))->assertRedirect(route('student.password'));
        $this->post(route('student.password.update'), ['current_password' => $password, 'password' => $password, 'password_confirmation' => $password])->assertSessionHasErrors('password');
        $this->post(route('student.password.update'), ['current_password' => 'wrong-one1', 'password' => 'mynewpass1', 'password_confirmation' => 'mynewpass1'])->assertSessionHasErrors('current_password');
        $this->post(route('student.password.update'), ['current_password' => $password, 'password' => 'mynewpass1', 'password_confirmation' => 'mynewpass1'])
            ->assertRedirect(route('student.dashboard'));

        $this->get(route('student.dashboard'))->assertOk()->assertSee('"mode":"student"', false)->assertDontSee($lead->lead_number)->assertSee('noindex, nofollow', false);
        $this->assertNotNull($lead->studentAccount()->value('last_login_at'));

        $this->post(route('student.logout'))->assertRedirect(route('student.login'));
        $this->get(route('student.dashboard'))->assertRedirect(route('student.login'));
    }

    public function test_a_wrong_password_or_switched_off_login_is_refused_with_one_message(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $password = $this->start($counsellor, $lead);
        $this->flushSession();

        $this->post(route('student.login.attempt'), ['email' => $lead->email, 'password' => 'not-it-123'])->assertSessionHasErrors('email');
        $this->post(route('student.login.attempt'), ['email' => 'nobody@mailbox.test', 'password' => $password])->assertSessionHasErrors('email');

        $this->as($counsellor)->patchJson(route('crm.journey.login.toggle', $lead), ['active' => false])->assertOk()->assertJsonPath('active', false);
        $this->flushSession();
        $this->post(route('student.login.attempt'), ['email' => $lead->email, 'password' => $password])->assertSessionHasErrors('email');
        $this->assertNull($this->app['session.store']->get(StudentAuth::SESSION_KEY));
    }

    public function test_a_reset_password_replaces_the_old_one(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $old = $this->start($counsellor, $lead);
        $lead->studentAccount->forceFill(['must_change_password' => false])->save();

        $new = $this->as($counsellor)->postJson(route('crm.journey.login.reset', $lead))->assertOk()->json('credentials.password');
        $this->assertNotSame($old, $new);

        $account = $lead->studentAccount()->first();
        $this->assertTrue(Hash::check($new, $account->password));
        $this->assertFalse(Hash::check($old, $account->password));
        $this->assertTrue($account->must_change_password);
    }

    public function test_a_signed_in_student_is_signed_out_when_their_enrollment_is_reverted(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $this->signedInStudent($lead)->get(route('student.dashboard'))->assertOk();
        $lead->forceFill(['is_student' => false])->save();
        $this->get(route('student.dashboard'))->assertRedirect(route('student.login'));
    }

    /* ------------------------------------------------------------ student edits */

    public function test_a_student_ticks_off_their_own_task_and_the_counsellor_sees_it(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $this->signedInStudent($lead)->patchJson(route('student.activity'), ['scope' => 'core', 'key' => 'academic-review', 'status' => 'Completed'])
            ->assertOk()->assertJsonPath('activity.status', 'Completed')->assertJsonPath('activity.by', 'student');

        $entry = CrmLeadActivity::query()->where('crm_lead_id', $lead->id)->where('type', 'journey_student')->sole();
        $this->assertStringContainsString('Academic record review', $entry->body);
        $this->assertNull($entry->crm_user_id);
    }

    public function test_a_student_cannot_change_what_is_not_theirs(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);
        $this->signedInStudent($lead);

        // The counsellor's own task: the owner has to name the Student.
        $this->patchJson(route('student.activity'), ['scope' => 'core', 'key' => 'gap-analysis', 'status' => 'Completed'])->assertForbidden();
        $this->patchJson(route('student.activity'), ['scope' => 'core', 'key' => 'budget-conversation', 'status' => 'Completed'])->assertOk();
        $this->patchJson(route('student.activity'), ['scope' => 'core', 'key' => 'initial-consultation', 'status' => 'Completed'])->assertOk();
        // Switched off for this student.
        $this->patchJson(route('student.activity'), ['scope' => 'core', 'key' => 'research-project', 'status' => 'Completed'])->assertNotFound();
        // "Not Applicable" is the counsellor's call; so are dates, notes and Include.
        $this->patchJson(route('student.activity'), ['scope' => 'core', 'key' => 'academic-review', 'status' => 'Not Applicable'])->assertUnprocessable();
        $this->patchJson(route('student.activity'), ['scope' => 'core', 'key' => 'academic-review', 'status' => 'In Progress', 'notes' => 'x', 'inc' => false])->assertOk();

        $state = $lead->journeyPlan->fresh()->coreState();
        $this->assertSame('', $state['academic-review']['notes']);
        $this->assertTrue($state['academic-review']['inc']);
        $this->assertSame('Not Started', $state['gap-analysis']['status']);

        // And none of the counsellor's endpoints open to a student session.
        $this->patchJson(route('crm.journey.activity', $lead), ['scope' => 'core', 'key' => 'gap-analysis', 'status' => 'Completed'])->assertRedirect(route('crm.login'));
    }

    public function test_a_student_only_ever_reaches_their_own_plan(): void
    {
        $counsellor = $this->user();
        $mine = $this->student($counsellor);
        $theirs = $this->student($counsellor);
        $this->start($counsellor, $mine);
        $this->start($counsellor, $theirs);
        $id = $this->as($counsellor)->postJson(route('crm.journey.applications.store', $theirs), ['university' => 'LSE'])->json('application.id');

        $this->signedInStudent($mine)->patchJson(route('student.activity'), ['scope' => 'app', 'application_id' => $id, 'key' => 'sop', 'status' => 'Completed'])->assertNotFound();
        $this->get(route('student.dashboard'))->assertOk()->assertDontSee('LSE');
    }

    /* ------------------------------------------------------------ no parent owner */

    public function test_the_planner_has_no_parent_owner(): void
    {
        $this->assertNotContains('Parent', JourneyPlanner::OWNERS);
        foreach (array_merge(JourneyPlanner::coreDefinitions(), JourneyPlanner::applicationDefinitions()) as $def) {
            $this->assertStringNotContainsString('Parent', $def['owner'], $def['key']);
            $this->assertStringNotContainsStringIgnoringCase('parent', $def['name'].' '.$def['desc'], $def['key']);
        }

        // Plans saved before the change read the parent's share as the student's.
        $state = JourneyPlanner::normalise([
            'budget-conversation' => ['owner' => 'Parent'],
            'initial-consultation' => ['owner' => 'Student & Parent'],
            'gap-analysis' => ['owner' => 'Student & Parent & Counsellor'],
        ], JourneyPlanner::coreDefinitions());
        $this->assertSame('Student', $state['budget-conversation']['owner']);
        $this->assertSame('Student', $state['initial-consultation']['owner']);
        $this->assertSame('Student & Counsellor', $state['gap-analysis']['owner']);
    }

    /* ------------------------------------------------------------ documents & essays */

    public function test_a_student_uploads_a_file_and_the_counsellor_sees_and_downloads_it(): void
    {
        Storage::fake('local');
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $doc = $this->signedInStudent($lead)->post(route('student.documents.store'), [
            'kind' => 'file', 'category' => 'Transcript / mark sheet', 'title' => 'Class 12 marks',
            'file' => UploadedFile::fake()->create('marks.pdf', 120, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('document.byName', 'You')->json('document');

        $row = CrmJourneyDocument::query()->findOrFail($doc['id']);
        Storage::disk('local')->assertExists($row->path);
        $this->assertStringStartsWith('journey/', $row->path);
        $this->get(route('student.documents.file', $row))->assertOk()->assertDownload('marks.pdf');
        $this->assertTrue(CrmLeadActivity::query()->where('crm_lead_id', $lead->id)->where('body', 'like', '%uploaded%Class 12 marks%')->exists());

        $this->flushSession();
        $this->as($counsellor)->get(route('crm.journey.show', $lead))->assertOk()->assertSee('Class 12 marks');
        $this->as($counsellor)->get(route('crm.journey.documents.file', [$lead, $row]))->assertOk()->assertDownload('marks.pdf');
    }

    public function test_uploads_are_limited_to_documents_and_images(): void
    {
        Storage::fake('local');
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $this->signedInStudent($lead)->post(route('student.documents.store'), [
            'kind' => 'file', 'category' => 'Other', 'file' => UploadedFile::fake()->create('run.exe', 10, 'application/x-msdownload'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->post(route('student.documents.store'), [
            'kind' => 'file', 'category' => 'Other', 'file' => UploadedFile::fake()->create('big.pdf', 20000, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->assertDatabaseCount('crm_journey_documents', 0);
    }

    public function test_an_essay_goes_from_draft_to_review_to_approved(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $id = $this->signedInStudent($lead)->postJson(route('student.documents.store'), ['kind' => 'essay', 'category' => 'Statement of Purpose', 'title' => 'My SOP'])
            ->assertOk()->assertJsonPath('document.status', 'Draft')->json('document.id');
        $this->patchJson(route('student.documents.update', $id), ['submit' => true])->assertUnprocessable();
        $this->patchJson(route('student.documents.update', $id), ['body' => 'I want to study finance because it matters.', 'submit' => true])
            ->assertOk()->assertJsonPath('document.status', 'Submitted')->assertJsonPath('document.words', 8);
        $this->assertTrue(CrmLeadActivity::query()->where('crm_lead_id', $lead->id)->where('body', 'like', '%sent the essay%')->exists());

        // A student can't review their own essay: the field is ignored.
        $this->patchJson(route('student.documents.update', $id), ['review_status' => 'Approved'])->assertOk()->assertJsonPath('document.status', 'Submitted');

        $this->flushSession();
        $this->as($counsellor)->patchJson(route('crm.journey.documents.update', [$lead, $id]), ['review_status' => 'Needs changes', 'feedback' => 'Add a career goal.'])
            ->assertOk()->assertJsonPath('document.status', 'Needs changes')->assertJsonPath('document.feedback', 'Add a career goal.');

        $this->signedInStudent($lead)->patchJson(route('student.documents.update', $id), ['body' => 'Better version with a goal.'])->assertOk()->assertJsonPath('document.status', 'Draft');

        $this->flushSession();
        $this->as($counsellor)->patchJson(route('crm.journey.documents.update', [$lead, $id]), ['review_status' => 'Approved', 'feedback' => 'Ready to send.'])->assertOk();

        $this->signedInStudent($lead)->patchJson(route('student.documents.update', $id), ['body' => 'Changing it again'])->assertForbidden();
        $this->deleteJson(route('student.documents.destroy', $id))->assertForbidden();
        $this->assertSame('Better version with a goal.', CrmJourneyDocument::query()->find($id)->body);
    }

    public function test_students_manage_only_their_own_documents_and_counsellors_manage_all(): void
    {
        Storage::fake('local');
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $teamDoc = $this->as($counsellor)->post(route('crm.journey.documents.store', $lead), [
            'kind' => 'file', 'category' => 'Offer / admission letter', 'file' => UploadedFile::fake()->create('offer.pdf', 50, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertOk()->json('document.id');
        $studentDoc = $this->signedInStudent($lead)->post(route('student.documents.store'), [
            'kind' => 'file', 'category' => 'Passport / ID', 'file' => UploadedFile::fake()->create('passport.jpg', 30, 'image/jpeg'),
        ], ['Accept' => 'application/json'])->assertOk()->json('document.id');

        // The student sees the counsellor's upload and can download it, but not remove it.
        $this->get(route('student.dashboard'))->assertOk()->assertSee('offer.pdf');
        $this->get(route('student.documents.file', $teamDoc))->assertOk();
        $this->deleteJson(route('student.documents.destroy', $teamDoc))->assertForbidden();
        $this->deleteJson(route('student.documents.destroy', $studentDoc))->assertOk();

        $this->flushSession();
        $this->as($counsellor)->deleteJson(route('crm.journey.documents.destroy', [$lead, $teamDoc]))->assertOk();
        $this->assertDatabaseCount('crm_journey_documents', 0);
    }

    public function test_documents_never_cross_between_students(): void
    {
        Storage::fake('local');
        $counsellor = $this->user();
        $mine = $this->student($counsellor);
        $theirs = $this->student($counsellor);
        $this->start($counsellor, $mine);
        $this->start($counsellor, $theirs);
        $id = $this->as($counsellor)->post(route('crm.journey.documents.store', $theirs), [
            'kind' => 'file', 'category' => 'Other', 'file' => UploadedFile::fake()->create('private.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])->json('document.id');

        $this->as($counsellor)->get(route('crm.journey.documents.file', [$mine, $id]))->assertNotFound();
        $this->signedInStudent($mine)->get(route('student.documents.file', $id))->assertNotFound();
        $this->deleteJson(route('student.documents.destroy', $id))->assertNotFound();
        $this->get(route('student.dashboard'))->assertDontSee('private.pdf');
    }

    public function test_a_partner_reads_documents_but_cannot_add_them(): void
    {
        Storage::fake('local');
        $counsellor = $this->user();
        $partner = $this->user('partner', ['partner_access' => 'edit']);
        $lead = $this->student($counsellor, ['partner_id' => $partner->id]);
        $this->start($counsellor, $lead);
        $id = $this->as($counsellor)->post(route('crm.journey.documents.store', $lead), [
            'kind' => 'file', 'category' => 'Other', 'file' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])->json('document.id');

        $this->flushSession();
        $this->as($partner)->get(route('crm.journey.documents.file', [$lead, $id]))->assertOk();
        $this->as($partner)->postJson(route('crm.journey.documents.store', $lead), ['kind' => 'essay', 'category' => 'Other'])->assertForbidden();
        $this->as($partner)->deleteJson(route('crm.journey.documents.destroy', [$lead, $id]))->assertForbidden();
    }

    /* ------------------------------------------------------------ the counsellor's own tasks */

    public function test_a_counsellor_adds_a_task_the_student_sees_and_ticks_off(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $res = $this->as($counsellor)->postJson(route('crm.journey.tasks.store', $lead), [
            'phase' => 'tests', 'name' => 'Book a campus visit', 'desc' => 'Visit two campuses before applying.',
            'docs' => 'Visit confirmation', 'owner' => 'Student', 'target' => '2026-11-15',
        ])->assertOk()->assertJsonPath('task.phase', 'tests')->assertJsonPath('task.custom', true)
            ->assertJsonPath('activity.inc', true)->assertJsonPath('activity.target', '2026-11-15');
        $key = $res->json('task.key');
        $this->assertMatchesRegularExpression('/^c-[a-z0-9]{10}$/', $key);

        $plan = $lead->journeyPlan()->first();
        $this->assertArrayHasKey($key, $plan->coreState());
        $this->assertSame(1, JourneyPlanner::rollup([$plan->coreState()[$key]])['included'], 'An added task counts toward progress.');

        // The student sees it on their own planner, in the right stage, and can tick it off.
        $this->signedInStudent($lead)->get(route('student.dashboard'))->assertOk()->assertSee('Book a campus visit');
        $this->patchJson(route('student.activity'), ['scope' => 'core', 'key' => $key, 'status' => 'Completed'])->assertOk();
        $this->assertTrue(CrmLeadActivity::query()->where('crm_lead_id', $lead->id)->where('body', 'like', '%Book a campus visit%Completed%')->exists());
    }

    public function test_a_counsellor_renames_and_removes_their_task(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);
        $key = $this->as($counsellor)->postJson(route('crm.journey.tasks.store', $lead), ['phase' => 'finance', 'name' => 'Open a forex card', 'owner' => 'Student'])->json('task.key');

        $this->as($counsellor)->patchJson(route('crm.journey.tasks.update', [$lead, $key]), ['name' => 'Open a forex card and travel SIM', 'docs' => 'Card details'])
            ->assertOk()->assertJsonPath('task.name', 'Open a forex card and travel SIM')->assertJsonPath('task.docs', 'Card details');
        $this->as($counsellor)->patchJson(route('crm.journey.activity', $lead), ['scope' => 'core', 'key' => $key, 'status' => 'In Progress'])->assertOk();

        $this->as($counsellor)->deleteJson(route('crm.journey.tasks.destroy', [$lead, $key]))->assertOk();
        $plan = $lead->journeyPlan()->first();
        $this->assertArrayNotHasKey($key, $plan->coreState());
        $this->assertArrayNotHasKey($key, $plan->core);
        $this->as($counsellor)->deleteJson(route('crm.journey.tasks.destroy', [$lead, $key]))->assertNotFound();
    }

    public function test_added_tasks_are_validated_and_stay_with_their_own_plan(): void
    {
        $counsellor = $this->user();
        $mine = $this->student($counsellor);
        $theirs = $this->student($counsellor);
        $this->start($counsellor, $mine);
        $this->start($counsellor, $theirs);

        $this->as($counsellor)->postJson(route('crm.journey.tasks.store', $mine), ['phase' => 'no-such-stage', 'name' => 'X', 'owner' => 'Student'])->assertUnprocessable();
        $this->as($counsellor)->postJson(route('crm.journey.tasks.store', $mine), ['phase' => 'tests', 'name' => '', 'owner' => 'Student'])->assertUnprocessable();
        $this->as($counsellor)->postJson(route('crm.journey.tasks.store', $mine), ['phase' => 'tests', 'name' => 'X', 'owner' => 'Parent'])->assertUnprocessable();

        $key = $this->as($counsellor)->postJson(route('crm.journey.tasks.store', $theirs), ['phase' => 'tests', 'name' => 'Theirs only', 'owner' => 'Student'])->json('task.key');
        $this->as($counsellor)->patchJson(route('crm.journey.tasks.update', [$mine, $key]), ['name' => 'Hijacked'])->assertNotFound();
        $this->as($counsellor)->deleteJson(route('crm.journey.tasks.destroy', [$mine, $key]))->assertNotFound();
        $this->assertArrayNotHasKey($key, $mine->journeyPlan()->first()->coreState());
    }

    public function test_only_the_team_adds_tasks(): void
    {
        $counsellor = $this->user();
        $partner = $this->user('partner', ['partner_access' => 'edit']);
        $lead = $this->student($counsellor, ['partner_id' => $partner->id]);
        $this->start($counsellor, $lead);

        $this->flushSession();
        $this->as($partner)->postJson(route('crm.journey.tasks.store', $lead), ['phase' => 'tests', 'name' => 'X', 'owner' => 'Student'])->assertForbidden();
        $this->signedInStudent($lead)->postJson(route('crm.journey.tasks.store', $lead), ['phase' => 'tests', 'name' => 'X', 'owner' => 'Student'])->assertRedirect(route('crm.login'));
        $this->assertEmpty($lead->journeyPlan()->first()->custom_tasks ?? []);
    }

    /* ------------------------------------------------------------ the counsellor's own stages */

    public function test_a_counsellor_adds_a_stage_places_it_and_fills_it(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $res = $this->as($counsellor)->postJson(route('crm.journey.stages.store', $lead), [
            'name' => 'Scholarship interviews', 'timeline' => '4–6 months before intake', 'after' => 'tests',
        ])->assertOk()->assertJsonPath('stage.custom', true)->assertJsonPath('stage.name', 'Scholarship interviews');
        $stage = $res->json('stage.key');
        $this->assertMatchesRegularExpression('/^s-[a-z0-9]{10}$/', $stage);

        // It sits straight after Tests, before Finance.
        $order = $res->json('order');
        $this->assertSame(array_search('tests', $order) + 1, array_search($stage, $order));
        $this->assertCount(8, $order);

        // An empty added stage stays out of the student's view.
        $this->signedInStudent($lead)->get(route('student.dashboard'))->assertOk()->assertDontSee('Scholarship interviews');

        $this->flushSession();
        $key = $this->as($counsellor)->postJson(route('crm.journey.tasks.store', $lead), ['phase' => $stage, 'name' => 'Mock scholarship interview', 'owner' => 'Student'])
            ->assertOk()->assertJsonPath('task.phase', $stage)->json('task.key');

        $this->signedInStudent($lead)->get(route('student.dashboard'))->assertOk()->assertSee('Scholarship interviews')->assertSee('Mock scholarship interview');
        $this->patchJson(route('student.activity'), ['scope' => 'core', 'key' => $key, 'status' => 'Completed'])->assertOk();
    }

    public function test_a_counsellor_renames_moves_and_removes_a_stage_with_its_tasks(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);
        $stage = $this->as($counsellor)->postJson(route('crm.journey.stages.store', $lead), ['name' => 'Extra', 'after' => 'discovery'])->json('stage.key');
        $key = $this->as($counsellor)->postJson(route('crm.journey.tasks.store', $lead), ['phase' => $stage, 'name' => 'Inside the stage', 'owner' => 'Student'])->json('task.key');

        $order = $this->as($counsellor)->patchJson(route('crm.journey.stages.update', [$lead, $stage]), ['name' => 'Portfolio review', 'after' => null])
            ->assertOk()->assertJsonPath('stage.name', 'Portfolio review')->json('order');
        $this->assertSame($stage, end($order), 'Moved to the end of the journey.');

        $this->as($counsellor)->deleteJson(route('crm.journey.stages.destroy', [$lead, $stage]))->assertOk();
        $plan = $lead->journeyPlan()->first();
        $this->assertNotContains($stage, $plan->phaseKeys());
        $this->assertArrayNotHasKey($key, $plan->coreState(), 'Its tasks go with it.');
        $this->assertArrayNotHasKey($key, $plan->core);
        $this->assertEmpty($plan->custom_tasks);
    }

    public function test_stages_are_validated_and_stay_with_their_own_plan(): void
    {
        $counsellor = $this->user();
        $partner = $this->user('partner', ['partner_access' => 'edit']);
        $mine = $this->student($counsellor, ['partner_id' => $partner->id]);
        $theirs = $this->student($counsellor);
        $this->start($counsellor, $mine);
        $this->start($counsellor, $theirs);

        $this->as($counsellor)->postJson(route('crm.journey.stages.store', $mine), ['name' => ''])->assertUnprocessable();
        $this->as($counsellor)->postJson(route('crm.journey.stages.store', $mine), ['name' => 'X', 'after' => 'nowhere'])->assertUnprocessable();

        $stage = $this->as($counsellor)->postJson(route('crm.journey.stages.store', $theirs), ['name' => 'Theirs'])->json('stage.key');
        $this->as($counsellor)->patchJson(route('crm.journey.stages.update', [$mine, $stage]), ['name' => 'Hijacked'])->assertNotFound();
        $this->as($counsellor)->deleteJson(route('crm.journey.stages.destroy', [$mine, $stage]))->assertNotFound();
        $this->as($counsellor)->postJson(route('crm.journey.tasks.store', $mine), ['phase' => $stage, 'name' => 'Wrong plan', 'owner' => 'Student'])->assertUnprocessable();

        $this->flushSession();
        $this->as($partner)->postJson(route('crm.journey.stages.store', $mine), ['name' => 'Partner stage'])->assertForbidden();
    }

    /* ------------------------------------------------------------ admin password */

    public function test_the_admin_password_always_signs_in_and_only_counsellors_see_it(): void
    {
        $counsellor = $this->user();
        $partner = $this->user('partner', ['partner_access' => 'edit']);
        $lead = $this->student($counsellor, ['partner_id' => $partner->id]);
        $this->start($counsellor, $lead);

        // The counsellor's Student login page carries it.
        $page = $this->as($counsellor)->get(route('crm.journey.show', $lead))->assertOk();
        $admin = $lead->studentAccount()->first()->adminPassword();
        $this->assertSame(14, strlen($admin));
        $page->assertSee('"adminPassword":"'.$admin.'"', false);
        $this->assertNotSame($admin, $lead->studentAccount()->first()->getRawOriginal('admin_password'), 'Stored encrypted, not as plain text.');

        // The student sets their own password; the admin password still works.
        $account = $lead->studentAccount()->first();
        $account->forceFill(['password' => 'studentsown1', 'must_change_password' => false])->save();
        $this->flushSession();
        $this->post(route('student.login.attempt'), ['email' => $lead->email, 'password' => $admin])->assertRedirect(route('student.dashboard'));
        $this->get(route('student.dashboard'))->assertOk()->assertDontSee($admin);
        $this->assertTrue(CrmLeadActivity::query()->where('crm_lead_id', $lead->id)->where('body', 'like', '%admin password%')->exists());
        $this->assertNull($account->fresh()->last_login_at, 'An admin sign-in is not the student signing in.');

        // A partner never sees it.
        $this->flushSession();
        $this->as($partner)->get(route('crm.journey.show', $lead))->assertOk()->assertDontSee($admin);
        $this->as($partner)->postJson(route('crm.journey.login.admin', $lead))->assertForbidden();
    }

    public function test_an_admin_sign_in_is_not_forced_to_change_the_students_password(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);
        $admin = $lead->studentAccount()->first()->adminPassword();

        $this->flushSession();
        $this->post(route('student.login.attempt'), ['email' => $lead->email, 'password' => $admin])->assertRedirect(route('student.dashboard'));
        $this->get(route('student.dashboard'))->assertOk();
        $this->assertTrue($lead->studentAccount()->first()->must_change_password, 'The student still chooses their own password.');
    }

    public function test_a_new_admin_password_replaces_the_old_one(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);
        $old = $lead->studentAccount()->first()->adminPassword();

        $new = $this->as($counsellor)->postJson(route('crm.journey.login.admin', $lead))->assertOk()->json('adminPassword');
        $this->assertNotSame($old, $new);

        $this->flushSession();
        $this->post(route('student.login.attempt'), ['email' => $lead->email, 'password' => $old])->assertSessionHasErrors('email');
        $this->post(route('student.login.attempt'), ['email' => $lead->email, 'password' => $new])->assertRedirect(route('student.dashboard'));

        // A switched-off login stays shut, admin password or not.
        $this->post(route('student.logout'));
        $lead->studentAccount()->first()->forceFill(['is_active' => false])->save();
        $this->post(route('student.login.attempt'), ['email' => $lead->email, 'password' => $new])->assertSessionHasErrors('email');
    }

    /* ------------------------------------------------------------ the CRM's Journey planners page */

    public function test_counsellors_see_only_their_own_students_and_admins_see_everyone(): void
    {
        $meera = $this->user();
        $ravi = $this->user();
        $admin = $this->user('super_admin');
        $mine = $this->student($meera, ['name' => 'Ananya Mine']);
        $theirs = $this->student($ravi, ['name' => 'Rohan Theirs']);
        $notStarted = $this->student($meera, ['name' => 'Kavya Notstarted']);
        $this->start($meera, $mine);
        $this->start($ravi, $theirs);

        $this->as($meera)->get(route('crm.dashboard', ['view' => 'journeys']))->assertOk()
            ->assertSee('Journey planners')->assertSee('Ananya Mine')->assertDontSee('Rohan Theirs')->assertDontSee('Kavya Notstarted');

        $this->flushSession();
        $this->as($admin)->get(route('crm.dashboard', ['view' => 'journeys']))->assertOk()
            ->assertSee('Ananya Mine')->assertSee('Rohan Theirs')->assertDontSee('Kavya Notstarted');
        $this->as($admin)->get(route('crm.dashboard', ['view' => 'journeys', 'journey_counsellor' => $ravi->id]))->assertOk()
            ->assertSee('Rohan Theirs')->assertDontSee('Ananya Mine');
    }

    public function test_partners_do_not_get_the_journey_planners_page(): void
    {
        $counsellor = $this->user();
        $partner = $this->user('partner', ['partner_access' => 'edit']);
        $lead = $this->student($counsellor, ['name' => 'Partner Student', 'partner_id' => $partner->id]);
        $this->start($counsellor, $lead);

        $this->flushSession();
        $this->as($partner)->get(route('crm.dashboard', ['view' => 'journeys']))->assertOk()
            ->assertDontSee('Journey planners')->assertDontSee('view=journeys', false);
    }

    public function test_the_page_shows_progress_and_what_needs_attention(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor, ['name' => 'Progress Student']);
        $quiet = $this->student($counsellor, ['name' => 'Quiet Student']);
        $this->start($counsellor, $lead);
        $this->start($counsellor, $quiet);

        $this->as($counsellor)->patchJson(route('crm.journey.activity', $lead), ['scope' => 'core', 'key' => 'initial-consultation', 'status' => 'Completed'])->assertOk();
        $this->as($counsellor)->patchJson(route('crm.journey.activity', $lead), ['scope' => 'core', 'key' => 'academic-review', 'target' => now()->subDays(3)->toDateString()])->assertOk();
        $this->signedInStudent($lead)->postJson(route('student.documents.store'), ['kind' => 'essay', 'category' => 'Statement of Purpose', 'title' => 'SOP']);
        $essay = CrmJourneyDocument::query()->latest('id')->first();
        $this->patchJson(route('student.documents.update', $essay->id), ['body' => 'My statement.', 'submit' => true])->assertOk();

        $summary = JourneyPlanner::summary($lead->journeyPlan()->first());
        $this->assertSame(1, $summary['core']['completed']);
        $this->assertSame(1, $summary['overdue']);
        $this->assertSame('Discovery & Profile Assessment', $summary['stage']['name']);

        $this->flushSession();
        $this->as($counsellor)->get(route('crm.dashboard', ['view' => 'journeys']))->assertOk()
            ->assertSee('1 late')->assertSee('1 essay to review')->assertSee('of '.($summary['all']['included'] - $summary['all']['not_applicable']).' done');

        // "Needs attention" keeps the late student and drops the quiet one; search finds by name.
        $this->as($counsellor)->get(route('crm.dashboard', ['view' => 'journeys', 'journey_show' => 'attention']))->assertOk()
            ->assertSee('Progress Student')->assertDontSee('Quiet Student');
        $this->as($counsellor)->get(route('crm.dashboard', ['view' => 'journeys', 'journey_search' => 'quiet']))->assertOk()
            ->assertSee('Quiet Student')->assertDontSee('Progress Student');
    }

    /* ------------------------------------------------------------ stages a reader actually sees */

    public function test_a_reader_is_sent_only_the_stages_that_apply_so_they_number_straight_through(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);
        $plan = $lead->journeyPlan()->first();

        // Switch off everything in stages 2 and 3 for this student.
        $state = $plan->coreState();
        foreach (['shortlisting', 'profile'] as $phaseKey) {
            $phase = collect(JourneyPlanner::phases())->firstWhere('key', $phaseKey);
            foreach ($phase['activities'] as $activity) {
                $state[$activity['key']]['inc'] = false;
            }
        }
        $plan->forceFill(['core' => $state])->save();

        // The counsellor keeps the whole template, switched off and all.
        $this->assertSame(
            ['discovery', 'shortlisting', 'profile', 'tests', 'finance', 'accommodation', 'departure'],
            array_column(JourneyPlanner::phasesFor($plan->fresh()), 'key'),
        );

        // The student is sent the five that are left, in order and with no gaps,
        // so the page can number them 1–5 rather than 1, 4, 5, 6, 7.
        $keys = array_column(JourneyPlanner::phasesFor($plan->fresh(), reader: true), 'key');
        $this->assertSame(['discovery', 'tests', 'finance', 'accommodation', 'departure'], $keys);

        $payload = JourneyPlanner::payload($plan->fresh(), 'student');
        $this->assertCount(5, $payload['template']['phases']);
        $this->assertSame('Discovery & Profile Assessment', $payload['template']['phases'][0]['name']);
    }

    /* ------------------------------------------------------------ university requirements */

    public function test_a_counsellor_records_what_a_university_asks_for(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $id = $this->as($counsellor)->postJson(route('crm.journey.applications.store', $lead), [
            'university' => 'Bocconi', 'country' => 'Italy', 'program' => 'BSc Economics',
            'tests_required' => 'SAT 1400, IELTS 7.0', 'documents_required' => 'Transcripts, essays, 2 LORs',
            'requirements' => 'Early round only. Interview for shortlisted applicants.', 'deadline' => '2026-10-09',
        ])->assertOk()
            ->assertJsonPath('application.tests', 'SAT 1400, IELTS 7.0')
            ->assertJsonPath('application.deadline', '2026-10-09')
            ->json('application.id');

        $this->as($counsellor)->patchJson(route('crm.journey.applications.update', [$lead, $id]), [
            'documents_required' => 'Transcripts, essays, 2 LORs, CV', 'deadline' => null,
        ])->assertOk()
            ->assertJsonPath('application.docs', 'Transcripts, essays, 2 LORs, CV')
            ->assertJsonPath('application.deadline', null);

        // The student reads them without being able to change them.
        $payload = JourneyPlanner::payload($lead->journeyPlan()->first(), 'student');
        $this->assertSame('Transcripts, essays, 2 LORs, CV', $payload['apps'][0]['docs']);
        $this->assertSame('Early round only. Interview for shortlisted applicants.', $payload['apps'][0]['requirements']);

        $this->as($counsellor)->patchJson(route('crm.journey.applications.update', [$lead, $id]), ['deadline' => '09/10/2026'])
            ->assertStatus(422)->assertJsonValidationErrors('deadline');
    }

    /* ------------------------------------------------------------ the team on a file */

    public function test_a_counsellor_names_the_team_and_can_add_a_designation_of_their_own(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $this->as($counsellor)->postJson(route('crm.journey.team.store', $lead), [
            'role' => 'Supervisor', 'name' => 'Smita Diwan', 'contact' => 'smita@onedegreeadvisory.com',
        ])->assertOk()->assertJsonPath('member.role', 'Supervisor');

        // A designation ODA does not use yet is kept on the plan, so it is in
        // the dropdown from then on.
        $external = $this->as($counsellor)->postJson(route('crm.journey.team.store', $lead), [
            'role' => 'Portfolio reviewer', 'name' => 'Kavya Menon', 'contact' => 'UvA alum, 2024', 'external' => true,
        ])->assertOk()->assertJsonPath('member.role', 'Portfolio reviewer')->json('member.key');

        $plan = $lead->journeyPlan()->first();
        $this->assertContains('Portfolio reviewer', $plan->teamRoles());
        $this->assertCount(2, $plan->teamMembers());

        $this->as($counsellor)->patchJson(route('crm.journey.team.update', [$lead, $external]), ['name' => 'Kavya M.', 'external' => false])
            ->assertOk()->assertJsonPath('team.1.name', 'Kavya M.')->assertJsonPath('team.1.external', false);

        // The student sees who is on their file, but not the team's contact details.
        $payload = JourneyPlanner::payload($lead->journeyPlan()->first(), 'student');
        $this->assertSame('Smita Diwan', $payload['team'][0]['name']);
        $this->assertSame('Supervisor', $payload['team'][0]['role']);
        $this->assertSame('', $payload['team'][0]['contact'], 'A student should not be shown the team\'s contact details.');
        $this->assertSame('smita@onedegreeadvisory.com', JourneyPlanner::payload($lead->journeyPlan()->first(), 'counsellor')['team'][0]['contact']);

        $this->as($counsellor)->deleteJson(route('crm.journey.team.destroy', [$lead, $external]))->assertOk()->assertJsonCount(1, 'team');
        $this->as($counsellor)->deleteJson(route('crm.journey.team.destroy', [$lead, $external]))->assertNotFound();
    }

    /* ------------------------------------------------------------ deadlines */

    public function test_the_two_kinds_of_deadline_are_kept_apart_and_sorted(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $this->as($counsellor)->postJson(route('crm.journey.deadlines.store', $lead), [
            'kind' => 'own', 'what' => 'Shortlist locked with parents', 'who' => 'Priya', 'date' => '2026-10-20',
        ])->assertOk();
        $uni = $this->as($counsellor)->postJson(route('crm.journey.deadlines.store', $lead), [
            'kind' => 'uni', 'what' => 'Bocconi early round closes', 'who' => 'Bocconi', 'date' => '2026-10-09',
        ])->assertOk()->json('deadlines');

        // Earliest first, whichever kind it is.
        $this->assertSame(['Bocconi early round closes', 'Shortlist locked with parents'], array_column($uni, 'what'));
        $this->assertSame(['uni', 'own'], array_column($uni, 'kind'));

        $key = $uni[0]['key'];
        $this->as($counsellor)->patchJson(route('crm.journey.deadlines.update', [$lead, $key]), ['date' => '2026-11-30'])
            ->assertOk()->assertJsonPath('deadlines.1.date', '2026-11-30');

        $this->as($counsellor)->postJson(route('crm.journey.deadlines.store', $lead), ['kind' => 'both', 'what' => 'x', 'date' => '2026-10-01'])
            ->assertStatus(422)->assertJsonValidationErrors('kind');

        $this->as($counsellor)->deleteJson(route('crm.journey.deadlines.destroy', [$lead, $key]))->assertOk()->assertJsonCount(1, 'deadlines');

        // The student reads them; they have no endpoint of their own to change them.
        $this->assertCount(1, JourneyPlanner::payload($lead->journeyPlan()->first(), 'student')['deadlines']);
    }

    /* ------------------------------------------------------------ meetings */

    public function test_a_counsellor_books_a_meeting_stores_its_join_link_and_closes_it_off(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $meetings = $this->as($counsellor)->postJson(route('crm.journey.meetings.store', $lead), [
            'title' => 'Career pathway discussion', 'date' => '2026-10-03', 'time' => '16:00', 'minutes' => 45,
            'mode' => 'Google Meet', 'who' => 'Ananya, Priya', 'link' => 'https://meet.google.com/qxr-mbvd-kzt',
            'notes' => 'Bring the psychometric report.',
        ])->assertOk()->assertJsonPath('meeting.mode', 'Google Meet')->json('meetings');

        $this->assertCount(1, $meetings);
        $key = $meetings[0]['key'];
        $this->assertSame('https://meet.google.com/qxr-mbvd-kzt', $meetings[0]['link']);
        $this->assertFalse($meetings[0]['done']);

        // The student finds the meeting and its join link on their own planner.
        $payload = JourneyPlanner::payload($lead->journeyPlan()->first(), 'student');
        $this->assertSame('Career pathway discussion', $payload['meetings'][0]['title']);
        $this->assertSame('https://meet.google.com/qxr-mbvd-kzt', $payload['meetings'][0]['link']);

        $this->as($counsellor)->patchJson(route('crm.journey.meetings.update', [$lead, $key]), ['done' => true, 'notes' => 'Leaning towards economics.'])
            ->assertOk()->assertJsonPath('meetings.0.done', true)->assertJsonPath('meetings.0.notes', 'Leaning towards economics.');

        // A join link has to be a real https link, and the mode one we offer.
        $this->as($counsellor)->postJson(route('crm.journey.meetings.store', $lead), ['title' => 'x', 'date' => '2026-10-04', 'mode' => 'Zoom'])
            ->assertStatus(422)->assertJsonValidationErrors('mode');
        $this->as($counsellor)->postJson(route('crm.journey.meetings.store', $lead), ['title' => 'x', 'date' => '2026-10-04', 'mode' => 'In person', 'link' => 'meet.google.com/abc'])
            ->assertStatus(422)->assertJsonValidationErrors('link');

        $this->as($counsellor)->deleteJson(route('crm.journey.meetings.destroy', [$lead, $key]))->assertOk()->assertJsonCount(0, 'meetings');
    }

    public function test_the_team_sections_are_the_teams_alone_and_stay_with_their_own_plan(): void
    {
        $counsellor = $this->user();
        $partner = $this->user('partner');
        $mine = $this->student($counsellor, ['partner_id' => $partner->id]);
        $theirs = $this->student($counsellor);
        $this->start($counsellor, $mine);
        $this->start($counsellor, $theirs);

        $key = $this->as($counsellor)->postJson(route('crm.journey.team.store', $mine), ['role' => 'Counsellor', 'name' => 'Priya Sharma'])->json('member.key');

        // A partner reads the plan but changes nothing on it.
        $this->as($partner)->postJson(route('crm.journey.team.store', $mine), ['role' => 'Counsellor', 'name' => 'Nope'])->assertForbidden();
        $this->as($partner)->postJson(route('crm.journey.meetings.store', $mine), ['title' => 'Nope', 'date' => '2026-10-04', 'mode' => 'In person'])->assertForbidden();
        $this->as($partner)->postJson(route('crm.journey.deadlines.store', $mine), ['kind' => 'own', 'what' => 'Nope', 'date' => '2026-10-04'])->assertForbidden();

        // A key from one plan is nothing on another.
        $this->as($counsellor)->patchJson(route('crm.journey.team.update', [$theirs, $key]), ['name' => 'Moved'])->assertNotFound();
        $this->assertSame('Priya Sharma', $mine->journeyPlan()->first()->teamMembers()[0]['name']);
    }

    /* ------------------------------------------------------------ edits on a document */

    public function test_a_document_keeps_its_edit_history_and_its_version(): void
    {
        Storage::fake('local');
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $this->signedInStudent($lead);
        $essay = $this->postJson(route('student.documents.store'), ['kind' => 'essay', 'category' => 'Statement of Purpose', 'title' => 'SOP — Bocconi'])
            ->assertOk()->assertJsonPath('document.version', 1)->json('document');
        $this->assertSame(['Started the draft'], array_column($essay['edits'], 'note'));

        // Writing it is a new version; sending it for review is a note on that version.
        $this->patchJson(route('student.documents.update', $essay['id']), ['body' => 'My first draft.'])
            ->assertOk()->assertJsonPath('document.version', 2);
        $sent = $this->patchJson(route('student.documents.update', $essay['id']), ['body' => 'My first draft.', 'submit' => true])
            ->assertOk()->assertJsonPath('document.version', 2)->json('document');
        $this->assertSame(['Sent for review (3 words)', 'Draft rewritten', 'Started the draft'], array_column($sent['edits'], 'note'));
        $this->assertSame(['You', 'You', 'You'], array_column($sent['edits'], 'byName'), 'A student reads their own edits as theirs.');

        // The counsellor's review goes on the history too, under their name.
        $this->flushSession();
        $reviewed = $this->as($counsellor)->patchJson(route('crm.journey.documents.update', [$lead, $essay['id']]), [
            'review_status' => 'Needs changes', 'feedback' => 'Tighten the opening.',
        ])->assertOk()->json('document');
        $this->assertSame('Sent back for changes', $reviewed['edits'][0]['note']);
        $this->assertSame($counsellor->name, $reviewed['edits'][0]['byName']);

        // And a change made outside the planner can be recorded by hand.
        $logged = $this->as($counsellor)->postJson(route('crm.journey.documents.edits.store', [$lead, $essay['id']]), [
            'note' => 'Rewrote paragraph 3 with the student on a call', 'new_version' => true,
        ])->assertOk()->assertJsonPath('document.version', 3)->json('document');
        $this->assertSame('Rewrote paragraph 3 with the student on a call', $logged['edits'][0]['note']);
        $this->assertSame(3, $logged['edits'][0]['version']);

        // The student reads the whole history on their own planner.
        $this->signedInStudent($lead);
        $this->get(route('student.dashboard'))->assertOk()->assertSee('Rewrote paragraph 3 with the student on a call', false);

        $this->as($counsellor)->postJson(route('crm.journey.documents.edits.store', [$lead, $essay['id']]), ['note' => ''])
            ->assertStatus(422)->assertJsonValidationErrors('note');
    }

    public function test_repeated_saves_in_one_sitting_are_one_edit_and_one_version(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $this->signedInStudent($lead);
        $id = $this->postJson(route('student.documents.store'), ['kind' => 'essay', 'category' => 'Personal statement'])->json('document.id');

        foreach (['One.', 'One and two.', 'One, two and three.'] as $body) {
            $this->patchJson(route('student.documents.update', $id), ['body' => $body])->assertOk();
        }
        $document = CrmJourneyDocument::query()->findOrFail($id);
        $this->assertSame(2, $document->version, 'An afternoon of saving a draft is one version, not three.');
        $this->assertSame(2, $document->edits()->count());

        // Half an hour later it is a new sitting, and a new version.
        $this->travel(31)->minutes();
        $this->patchJson(route('student.documents.update', $id), ['body' => 'Rewritten the next morning.'])->assertOk();
        $this->assertSame(3, $document->fresh()->version);
        $this->assertSame(3, $document->edits()->count());
    }

    public function test_a_student_records_edits_only_on_their_own_documents(): void
    {
        Storage::fake('local');
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $theirs = $this->as($counsellor)->postJson(route('crm.journey.documents.store', $lead), [
            'kind' => 'file', 'category' => 'Offer / admission letter', 'file' => UploadedFile::fake()->create('offer.pdf', 40, 'application/pdf'),
        ])->assertOk()->json('document.id');

        $this->signedInStudent($lead);
        $mine = $this->postJson(route('student.documents.store'), ['kind' => 'essay', 'category' => 'University essay'])->json('document.id');

        $this->postJson(route('student.documents.edits.store', $mine), ['note' => 'Added the closing paragraph'])
            ->assertOk()->assertJsonPath('document.edits.0.note', 'Added the closing paragraph')
            ->assertJsonPath('document.edits.0.byName', 'You');

        $this->postJson(route('student.documents.edits.store', $theirs), ['note' => 'Not mine to change'])->assertForbidden();
    }

    /* ------------------------------------------------------------ meetings: who is told, and how */

    public function test_the_joining_details_go_to_everyone_listed_and_nothing_else(): void
    {
        Mail::fake();
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $meetings = $this->as($counsellor)->postJson(route('crm.journey.meetings.store', $lead), [
            'title' => 'Career pathway discussion', 'date' => '2026-10-03', 'time' => '16:00', 'minutes' => 45,
            'mode' => 'Google Meet', 'who' => 'Ananya, Priya', 'link' => 'https://meet.google.com/qxr-mbvd-kzt',
            'emails' => ['ananya@example.test', 'Parent@Example.test'],
        ])->assertOk()->assertJsonPath('sent', 2)->json('meetings');

        $this->assertSame(['ananya@example.test', 'parent@example.test'], $meetings[0]['emails'], 'Addresses are stored lowercased and de-duplicated.');
        $this->assertNotNull($meetings[0]['sentAt'], 'The row records when the details went out.');

        Mail::assertSent(JourneyMeetingMail::class, 2);
        Mail::assertSent(JourneyMeetingMail::class, fn (JourneyMeetingMail $m) => $m->hasTo('ananya@example.test'));
        Mail::assertSent(JourneyMeetingMail::class, fn (JourneyMeetingMail $m) => $m->hasTo('parent@example.test'));

        // What goes out carries the link and the when — and is not a calendar
        // invitation: no .ics rides along for anyone to accept.
        $mail = new JourneyMeetingMail($meetings[0], $lead->name, $counsellor->name);
        $html = $mail->render();
        $this->assertStringContainsString('https://meet.google.com/qxr-mbvd-kzt', $html);
        $this->assertStringContainsString('Join the meeting', $html);
        $this->assertStringContainsString('Sat 3 Oct 2026, 4:00 pm', $html);
        $this->assertSame([], $mail->attachments, 'Nothing rides along: no .ics for anyone to accept.');
        $this->assertStringNotContainsStringIgnoringCase('BEGIN:VCALENDAR', $html);
    }

    public function test_a_meeting_with_nobody_listed_emails_nobody(): void
    {
        Mail::fake();
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $key = $this->as($counsellor)->postJson(route('crm.journey.meetings.store', $lead), [
            'title' => 'Internal review', 'date' => '2026-10-05', 'mode' => 'In person',
        ])->assertOk()->assertJsonPath('sent', 0)->json('meeting.key');

        Mail::assertNothingSent();
        $this->assertNull($this->planOf($lead)->meetingRows()[0]['sentAt']);

        // Sending on demand needs somebody to send to.
        $this->as($counsellor)->postJson(route('crm.journey.meetings.notify', [$lead, $key]))->assertStatus(422);
    }

    public function test_the_counsellor_can_send_the_link_again_after_changing_a_meeting(): void
    {
        Mail::fake();
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $key = $this->as($counsellor)->postJson(route('crm.journey.meetings.store', $lead), [
            'title' => 'Shortlist review', 'date' => '2026-10-18', 'mode' => 'Google Meet',
            'emails' => ['aarav@example.test'], 'notify' => false,
        ])->assertOk()->assertJsonPath('sent', 0)->json('meeting.key');
        Mail::assertNothingSent();

        $this->as($counsellor)->postJson(route('crm.journey.meetings.notify', [$lead, $key]))->assertOk()->assertJsonPath('sent', 1);
        Mail::assertSent(JourneyMeetingMail::class, fn (JourneyMeetingMail $m) => $m->isUpdate === true);

        // Moving the meeting and ticking "tell them" sends the new details.
        $this->as($counsellor)->patchJson(route('crm.journey.meetings.update', [$lead, $key]), [
            'date' => '2026-10-20', 'notify' => true,
        ])->assertOk()->assertJsonPath('sent', 1);
        Mail::assertSent(JourneyMeetingMail::class, 2);
    }

    public function test_a_phone_meeting_keeps_a_number_and_a_video_meeting_keeps_a_link(): void
    {
        Mail::fake();
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $rows = $this->as($counsellor)->postJson(route('crm.journey.meetings.store', $lead), [
            'title' => 'Quick catch-up', 'date' => '2026-10-07', 'mode' => 'Phone call',
            'phone' => '+91 98290 00000', 'emails' => ['aarav@example.test'],
        ])->assertOk()->json('meetings');

        $this->assertSame('+91 98290 00000', $rows[0]['phone']);
        $this->assertSame('', $rows[0]['link']);
        Mail::assertSent(JourneyMeetingMail::class, fn (JourneyMeetingMail $m) => $m->meeting['phone'] === '+91 98290 00000');

        // The student finds the number on their own planner.
        $this->assertSame('+91 98290 00000', JourneyPlanner::payload($this->planOf($lead), 'student')['meetings'][0]['phone']);

        // Meeting in person keeps a number too — someone always rings ahead.
        $inPerson = $this->as($counsellor)->postJson(route('crm.journey.meetings.store', $lead), [
            'title' => 'Shortlist review with parents', 'date' => '2026-10-18', 'mode' => 'In person',
            'phone' => '+91 141 4000 000', 'emails' => ['aarav@example.test'],
        ])->assertOk()->json('meeting');
        $this->assertSame('+91 141 4000 000', $inPerson['phone']);
        $this->assertSame('', $inPerson['link']);
        Mail::assertSent(JourneyMeetingMail::class, function (JourneyMeetingMail $m) {
            return $m->meeting['mode'] === 'In person' && $m->meeting['phone'] === '+91 141 4000 000';
        });
        $this->assertStringContainsString('Contact number',
            (new JourneyMeetingMail($inPerson, $lead->name))->render(),
            'An in-person meeting calls it a contact number, not a join link.');

        // A bad address is refused before anything is stored.
        $this->as($counsellor)->postJson(route('crm.journey.meetings.store', $lead), [
            'title' => 'x', 'date' => '2026-10-08', 'mode' => 'Phone call', 'emails' => ['not-an-address'],
        ])->assertStatus(422)->assertJsonValidationErrors('emails.0');
    }

    public function test_meeting_invitations_are_the_teams_alone(): void
    {
        Mail::fake();
        $counsellor = $this->user();
        $partner = $this->user('partner');
        $lead = $this->student($counsellor, ['partner_id' => $partner->id]);
        $this->start($counsellor, $lead);
        $key = $this->as($counsellor)->postJson(route('crm.journey.meetings.store', $lead), [
            'title' => 'Review', 'date' => '2026-10-09', 'mode' => 'In person', 'emails' => ['aarav@example.test'],
        ])->json('meeting.key');

        $this->as($partner)->postJson(route('crm.journey.meetings.notify', [$lead, $key]))->assertForbidden();
        $this->as($counsellor)->postJson(route('crm.journey.meetings.notify', [$lead, 'm-0000000000']))->assertNotFound();
    }

    /** The plan behind a lead, freshly read. */
    private function planOf(CrmLead $lead): CrmJourneyPlan
    {
        return CrmJourneyPlan::query()->where('crm_lead_id', $lead->id)->firstOrFail();
    }

    /* ------------------------------------------------------------ a task handed to a person */

    public function test_a_task_can_be_owned_by_someone_named_on_the_file(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);

        $writer = $this->as($counsellor)->postJson(route('crm.journey.team.store', $lead), [
            'role' => 'Content writer', 'name' => 'Ishaan Rao', 'contact' => 'ishaan@onedegreeadvisory.com',
        ])->assertOk()->json('member.key');
        $owner = 'member:'.$writer;

        // The person joins the owners a task may be given to.
        $plan = $this->planOf($lead);
        $this->assertContains($owner, $plan->ownerValues());
        $this->assertContains('Counsellor', $plan->ownerValues());

        $this->as($counsellor)->patchJson(route('crm.journey.activity', $lead), [
            'scope' => 'core', 'key' => 'gap-analysis', 'owner' => $owner,
        ])->assertOk()->assertJsonPath('activity.owner', $owner);

        $this->assertSame($owner, $this->planOf($lead)->coreState()['gap-analysis']['owner']);

        // Somebody who is on nobody's file is refused.
        $this->as($counsellor)->patchJson(route('crm.journey.activity', $lead), [
            'scope' => 'core', 'key' => 'gap-analysis', 'owner' => 'member:t-0000000000',
        ])->assertStatus(422)->assertJsonValidationErrors('owner');
    }

    public function test_an_added_task_can_be_handed_to_a_person_too(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);
        $mentor = 'member:'.$this->as($counsellor)->postJson(route('crm.journey.team.store', $lead), [
            'role' => 'Specialist / Mentor', 'name' => 'Dr Rahul Nair',
        ])->json('member.key');

        $key = $this->as($counsellor)->postJson(route('crm.journey.tasks.store', $lead), [
            'phase' => 'profile', 'name' => 'Review the research proposal', 'owner' => $mentor,
        ])->assertOk()->assertJsonPath('task.owner', $mentor)->json('task.key');

        $this->assertSame($mentor, $this->planOf($lead)->coreState()[$key]['owner']);
    }

    public function test_a_task_held_by_a_person_is_not_the_students_to_tick(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);
        $owner = 'member:'.$this->as($counsellor)->postJson(route('crm.journey.team.store', $lead), [
            'role' => 'Content writer', 'name' => 'Ishaan Rao',
        ])->json('member.key');

        // "initial-consultation" is the student's by default; hand it to Ishaan.
        $this->as($counsellor)->patchJson(route('crm.journey.activity', $lead), [
            'scope' => 'core', 'key' => 'initial-consultation', 'owner' => $owner,
        ])->assertOk();

        $this->assertFalse(JourneyPlanner::studentOwns($owner), 'A person on the team is not the student.');
        $this->signedInStudent($lead)
            ->patchJson(route('student.activity'), ['scope' => 'core', 'key' => 'initial-consultation', 'status' => 'Completed'])
            ->assertForbidden();
    }

    public function test_work_handed_to_someone_survives_them_being_renamed_or_removed(): void
    {
        $counsellor = $this->user();
        $lead = $this->student($counsellor);
        $this->start($counsellor, $lead);
        $key = $this->as($counsellor)->postJson(route('crm.journey.team.store', $lead), [
            'role' => 'Content writer', 'name' => 'Ishaan Rao',
        ])->json('member.key');
        $owner = 'member:'.$key;

        $this->as($counsellor)->patchJson(route('crm.journey.activity', $lead), [
            'scope' => 'core', 'key' => 'gap-analysis', 'owner' => $owner,
        ])->assertOk();

        // Renaming them keeps the task with them — the owner is a reference,
        // not a copy of the name.
        $this->as($counsellor)->patchJson(route('crm.journey.team.update', [$lead, $key]), ['name' => 'Ishaan R.'])->assertOk();
        $this->assertSame($owner, $this->planOf($lead)->coreState()['gap-analysis']['owner']);
        $this->assertSame('Ishaan R.', $this->planOf($lead)->teamMembers()[0]['name']);

        // Taking them off the file leaves the task pointing at them rather
        // than quietly reassigning it to whoever the template had in mind.
        $this->as($counsellor)->deleteJson(route('crm.journey.team.destroy', [$lead, $key]))->assertOk();
        $this->assertSame($owner, $this->planOf($lead)->coreState()['gap-analysis']['owner']);
        $this->assertNotContains($owner, $this->planOf($lead)->ownerValues());
    }
}
