<?php

namespace Tests\Feature;

use App\Mail\CrmNotificationMail;
use App\Mail\CrmOtpMail;
use App\Models\CrmLead;
use App\Models\CrmLeadActivity;
use App\Models\CrmUser;
use App\Services\CrmOtpSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CrmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_super_admin_can_request_and_verify_phone_otp(): void
    {
        config()->set('crm.super_admin.name', 'CRM Owner');
        config()->set('crm.super_admin.phone', '9876543210');

        $request = $this->post(route('crm.otp.request'), ['phone' => '+91 98765 43210']);
        $request->assertRedirect()->assertSessionHas('otp_sent')->assertSessionHas('debug_otp');
        $otp = session('debug_otp');

        $admin = CrmUser::query()->where('phone', '9876543210')->firstOrFail();
        $this->assertTrue($admin->isSuperAdmin());
        $this->assertSame('Admissions@onedegreeadvisory.com', $admin->email);
        $this->assertDatabaseHas('crm_users', [
            'phone' => '9829027413',
            'email' => 'harshittotuka1@gmail.com',
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        Mail::assertSent(CrmOtpMail::class, fn (CrmOtpMail $mail): bool => $mail->hasTo($admin->email));

        $this->post(route('crm.otp.verify'), ['otp' => $otp])
            ->assertRedirect(route('crm.dashboard'))
            ->assertSessionHas('crm_user_id', $admin->id);
    }

    public function test_unknown_phone_cannot_request_an_otp(): void
    {
        $this->post(route('crm.otp.request'), ['phone' => '9876543210'])
            ->assertSessionHasErrors('phone');
    }

    public function test_msg91_driver_sends_the_generated_otp_through_the_configured_flow(): void
    {
        config()->set('crm.otp.channels', ['sms']);
        config()->set('crm.sms.driver', 'msg91');
        config()->set('crm.sms.msg91.endpoint', 'https://control.msg91.com/api/v5/flow');
        config()->set('crm.sms.msg91.auth_key', 'test-auth-key');
        config()->set('crm.sms.msg91.flow_id', 'test-flow-id');
        config()->set('crm.sms.msg91.otp_variable', 'OTP');
        Http::fake(['control.msg91.com/*' => Http::response(['type' => 'success'], 200)]);
        $user = CrmUser::query()->create(['name' => 'SMS Admin', 'phone' => '9876543210', 'email' => 'sms-admin@example.com', 'role' => 'super_admin', 'is_active' => true]);

        $this->assertSame(['sms'], app(CrmOtpSender::class)->send($user, '654321'));
        Http::assertSent(fn ($request): bool => $request->hasHeader('authkey', 'test-auth-key')
            && $request['template_id'] === 'test-flow-id'
            && $request['recipients'][0]['mobiles'] === '919876543210'
            && $request['recipients'][0]['OTP'] === '654321');
    }

    public function test_invalid_new_lead_reopens_modal_with_inline_errors_and_old_input(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);

        $response = $this->followingRedirects()
            ->withSession(['crm_user_id' => $admin->id])
            ->from(route('crm.dashboard'))
            ->post(route('crm.leads.store'), [
                'name' => 'Invalid Status Lead', 'phone' => '123', 'priority' => 'medium', 'status' => 'not-a-status',
            ]);

        $response
            ->assertOk()
            ->assertSee('class="overlay open" id="leadModal"', false)
            ->assertSee('aria-hidden="false"', false)
            ->assertSee('data-open-on-load', false)
            ->assertSee('class="field has-error"', false)
            ->assertSee('The selected status is invalid.')
            ->assertSee('value="Invalid Status Lead"', false)
            ->assertDontSee('Please check the submitted details.');
    }

    public function test_lead_mobile_number_has_no_strict_format_validation(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'email' => 'admin@example.com', 'role' => 'super_admin', 'is_active' => true]);

        $this->withSession(['crm_user_id' => $admin->id])->post(route('crm.leads.store'), [
            'name' => 'Short Mobile Lead', 'phone' => '123', 'priority' => 'medium', 'status' => 'new',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('crm_leads', ['name' => 'Short Mobile Lead', 'phone' => '123']);
    }

    public function test_dashboard_renders_partial_navigation_hooks(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);
        $lead = $this->leadFor($admin, 'Smooth Navigation Lead', '9876500099');
        $lead->update([
            'country_interest' => 'Canada',
            'course_interest' => 'Data Science',
            'source' => 'Website',
            'follow_up_at' => now()->addDay(),
        ]);

        $dashboard = $this->withSession(['crm_user_id' => $admin->id])->get(route('crm.dashboard'));
        $dashboard
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('aria-label="Lead summary"', false)
            ->assertSee('data-classic-href=', false)
            ->assertSee('data-evergreen-href=', false)
            ->assertSee('data-orbit-href=', false)
            ->assertSee('data-crm-theme-switcher', false)
            ->assertSee('>Classic</option>', false)
            ->assertSee('>Evergreen</option>', false)
            ->assertSee('>Orbit</option>', false)
            ->assertSee('assets/crm/crm-dashboard.css', false)
            ->assertSee('data-dashboard-insights', false)
            ->assertSee('data-lead-world-map', false)
            ->assertSee('data-leaflet-canvas', false)
            ->assertSee('data-map-points=', false)
            ->assertSee('leaflet@1.9.4', false)
            ->assertSee('team-management-modal', false)
            ->assertSee('team-create-form', false)
            ->assertSee('Save changes')
            ->assertSee('Where students want to study')
            ->assertSee('Pipeline health')
            ->assertSee('Lead sources')
            ->assertSee('Canada')
            ->assertSee('Website')
            ->assertDontSee('data-crm-filter-form', false);

        $this->withSession(['crm_user_id' => $admin->id])->get(route('crm.dashboard', ['view' => 'leads']))
            ->assertOk()
            ->assertSee('data-crm-app', false)
            ->assertSee('data-crm-filter-form', false)
            ->assertSee('data-crm-href=', false)
            ->assertDontSee('aria-label="Lead summary"', false)
            ->assertDontSee('data-dashboard-insights', false)
            ->assertDontSee('onclick="location.href=', false);
    }

    public function test_counsellor_only_sees_owned_leads_and_super_admin_sees_all(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);
        $one = CrmUser::query()->create(['name' => 'Asha', 'phone' => '9876543211', 'role' => 'counsellor', 'is_active' => true]);
        $two = CrmUser::query()->create(['name' => 'Ravi', 'phone' => '9876543212', 'role' => 'counsellor', 'is_active' => true]);
        $leadOne = $this->leadFor($one, 'Asha Lead', '9876500001');
        $leadTwo = $this->leadFor($two, 'Ravi Lead', '9876500002');

        $this->withSession(['crm_user_id' => $one->id])->get(route('crm.dashboard', ['view' => 'leads']))
            ->assertOk()->assertSee('Asha Lead')->assertDontSee('Ravi Lead');

        $this->withSession(['crm_user_id' => $admin->id])->get(route('crm.dashboard', ['view' => 'leads']))
            ->assertOk()->assertSee('Asha Lead')->assertSee('Ravi Lead');

        $this->withSession(['crm_user_id' => $one->id])->put(route('crm.leads.update', $leadTwo), [
            'name' => 'Blocked', 'phone' => $leadTwo->phone, 'priority' => 'medium', 'status' => 'new',
        ])->assertForbidden();
    }

    public function test_super_admin_can_create_counsellors_and_transfer_lead_ownership(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);
        $this->withSession(['crm_user_id' => $admin->id])->post(route('crm.team.store'), [
            'name' => 'New Counsellor', 'phone' => '+91 98765 43211', 'email' => 'counsellor@example.com', 'role' => 'counsellor',
        ])->assertSessionHasNoErrors();
        $counsellor = CrmUser::query()->where('phone', '9876543211')->firstOrFail();
        $this->assertSame('counsellor@example.com', $counsellor->email);
        Mail::assertSent(CrmNotificationMail::class, fn (CrmNotificationMail $mail): bool => $mail->hasTo('counsellor@example.com'));
        $lead = $this->leadFor($admin, 'Transfer Me', '9876500003');

        $this->withSession(['crm_user_id' => $admin->id])->put(route('crm.leads.update', $lead), [
            'name' => $lead->name, 'phone' => $lead->phone, 'priority' => 'high', 'status' => 'interested',
            'assigned_to' => $counsellor->id, 'follow_up_at' => now()->addDays(10)->format('Y-m-d H:i:s'),
        ])->assertSessionHasNoErrors();

        $this->assertSame($counsellor->id, $lead->fresh()->assigned_to);
        $this->assertDatabaseHas('crm_lead_activities', ['crm_lead_id' => $lead->id, 'type' => 'updated']);
    }

    public function test_super_admin_can_create_and_see_another_super_admin(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'email' => 'admin@example.com', 'role' => 'super_admin', 'is_active' => true]);

        $this->withSession(['crm_user_id' => $admin->id])->post(route('crm.team.store'), [
            'name' => 'Second Admin', 'phone' => '9876543219', 'email' => 'second-admin@example.com', 'role' => 'super_admin',
        ])->assertSessionHasNoErrors();

        $member = CrmUser::query()->where('email', 'second-admin@example.com')->firstOrFail();
        $this->assertTrue($member->isSuperAdmin());
        $this->withSession(['crm_user_id' => $admin->id])->get(route('crm.dashboard'))
            ->assertOk()
            ->assertSee('Second Admin')
            ->assertSee('second-admin@example.com')
            ->assertSee('Super admin');
    }

    public function test_follow_up_email_reminders_send_once_for_each_due_window(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'email' => 'admin@example.com', 'role' => 'super_admin', 'is_active' => true]);
        $owner = CrmUser::query()->create(['name' => 'Asha', 'phone' => '9876543211', 'email' => 'asha@example.com', 'role' => 'counsellor', 'is_active' => true]);
        $lead = $this->leadFor($owner, 'Reminder Email Lead', '9876500088');
        $lead->update(['follow_up_at' => now()->addDay()->setTime(11, 0)]);

        $this->artisan('crm:send-follow-up-reminders')->assertSuccessful();
        $this->artisan('crm:send-follow-up-reminders')->assertSuccessful();

        Mail::assertSent(CrmNotificationMail::class, 2);
        $this->assertDatabaseCount('crm_lead_activities', 1);
        $this->assertDatabaseHas('crm_lead_activities', ['crm_lead_id' => $lead->id, 'type' => 'reminder_email']);
    }

    public function test_follow_up_reminders_appear_one_day_before_and_on_due_day(): void
    {
        $user = CrmUser::query()->create(['name' => 'Asha', 'phone' => '9876543211', 'role' => 'counsellor', 'is_active' => true]);
        $lead = $this->leadFor($user, 'Reminder Lead', '9876500004');
        $lead->update(['follow_up_at' => now()->addDay()->setTime(11, 0)]);

        $this->withSession(['crm_user_id' => $user->id])->get(route('crm.dashboard'))
            ->assertOk()->assertSee('Advance reminder')->assertSee('Reminder Lead');

        $this->travel(1)->days();
        $this->withSession(['crm_user_id' => $user->id])->get(route('crm.dashboard'))
            ->assertOk()->assertSee('Due today')->assertSee('Reminder Lead');
    }

    public function test_comments_and_conversion_are_recorded_on_the_timeline(): void
    {
        $user = CrmUser::query()->create(['name' => 'Asha', 'phone' => '9876543211', 'role' => 'counsellor', 'is_active' => true]);
        $lead = $this->leadFor($user, 'Student Lead', '9876500005');

        $this->withSession(['crm_user_id' => $user->id])->postJson(route('crm.leads.comments.store', $lead), [
            'comment' => 'Student will share documents on Friday.',
        ])->assertOk()
            ->assertJsonPath('message', 'Comment added to the timeline.')
            ->assertJsonPath('activity.actor', 'Asha')
            ->assertJsonPath('activity.body', 'Student will share documents on Friday.')
            ->assertJsonPath('total', 1);

        $this->withSession(['crm_user_id' => $user->id])->post(route('crm.leads.convert', $lead), [
            'student_category' => 'paid', 'student_stage' => 'doc_pending', 'enrollment_amount' => 25000,
        ])->assertSessionHasNoErrors();

        $this->withSession(['crm_user_id' => $user->id])->patch(route('crm.leads.student-journey.update', $lead), [
            'student_category' => 'paid', 'student_stage' => 'doc_complete', 'enrollment_amount' => 30000,
            'enrollment_date' => now()->toDateString(), 'payment_reference' => 'OD-PAY-1001',
            'conversion_remarks' => 'Documents verified and ready for application.',
        ])->assertSessionHasNoErrors()->assertSessionHas('status', 'Student journey updated.');

        $this->assertTrue($lead->fresh()->is_student);
        $this->assertSame('converted', $lead->fresh()->status);
        $this->assertSame('doc_complete', $lead->fresh()->student_stage);
        $this->assertSame(30000, $lead->fresh()->enrollment_amount);
        $this->assertSame(3, CrmLeadActivity::query()->where('crm_lead_id', $lead->id)->count());
    }

    public function test_csv_import_skips_duplicate_phone_numbers_and_export_downloads_visible_leads(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);
        $owner = CrmUser::query()->create(['name' => 'Asha', 'phone' => '9876543211', 'role' => 'counsellor', 'is_active' => true]);
        $csv = "name,phone,email,course,priority,source\nImported Student,9876500010,student@example.com,MBA,high,Education Fair\nDuplicate Student,9876500010,duplicate@example.com,MBA,low,Referral\n";

        $this->withSession(['crm_user_id' => $admin->id])->post(route('crm.leads.import'), [
            'file' => UploadedFile::fake()->createWithContent('leads.csv', $csv),
            'assigned_to' => $owner->id,
        ])->assertSessionHasNoErrors()->assertSessionHas('status', 'Imported 1 lead(s); skipped 1 invalid or duplicate row(s).');

        $this->assertDatabaseHas('crm_leads', ['name' => 'Imported Student', 'assigned_to' => $owner->id, 'priority' => 'high']);
        $export = $this->withSession(['crm_user_id' => $owner->id])->get(route('crm.leads.export'));
        $export->assertOk()->assertDownload();
        $this->assertStringContainsString('Imported Student', $export->streamedContent());
    }

    private function leadFor(CrmUser $owner, string $name, string $phone): CrmLead
    {
        return CrmLead::query()->create([
            'lead_number' => 'OD-'.substr($phone, -5), 'name' => $name, 'phone' => $phone,
            'priority' => 'medium', 'status' => 'new', 'assigned_to' => $owner->id, 'created_by' => $owner->id,
        ]);
    }
}
