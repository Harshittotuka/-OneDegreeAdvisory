<?php

namespace Tests\Feature;

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

    public function test_login_waits_for_the_selected_theme_stylesheet_before_revealing(): void
    {
        $this->get(route('crm.login'))
            ->assertOk()
            ->assertSee('class="crm-css-pending"', false)
            ->assertSee("stylesheet.dataset.crmThemeLoading = 'true'", false)
            ->assertSee("link.dataset.crmThemeLoading !== 'true'", false)
            ->assertSee('assets/crm/crm.css?v=', false)
            ->assertSee('assets/crm/crm-theme-switcher.css?v=', false);
    }

    public function test_super_admin_can_request_and_verify_phone_otp(): void
    {
        config()->set('crm.super_admin.name', 'CRM Owner');
        config()->set('crm.super_admin.phone', '9876543210');

        $request = $this->post(route('crm.otp.request'), ['login' => '+91 98765 43210']);
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
        $this->post(route('crm.otp.request'), ['login' => '9876543210'])
            ->assertSessionHasErrors('login');
    }

    public function test_team_member_can_request_an_otp_with_their_email(): void
    {
        $user = CrmUser::query()->create(['name' => 'Email Admin', 'phone' => '9876543215', 'email' => 'email-admin@example.com', 'role' => 'super_admin', 'is_active' => true]);

        $this->post(route('crm.otp.request'), ['login' => 'EMAIL-ADMIN@example.com'])
            ->assertRedirect()->assertSessionHas('otp_sent')->assertSessionHas('crm_otp_user_id', $user->id);
        Mail::assertSent(CrmOtpMail::class, fn (CrmOtpMail $mail): bool => $mail->hasTo($user->email));

        $this->post(route('crm.otp.verify'), ['otp' => session('debug_otp')])
            ->assertRedirect(route('crm.dashboard'))
            ->assertSessionHas('crm_user_id', $user->id);
    }

    public function test_unknown_email_cannot_request_an_otp(): void
    {
        $this->post(route('crm.otp.request'), ['login' => 'stranger@example.com'])
            ->assertSessionHasErrors('login');
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

    public function test_manual_leads_cannot_duplicate_an_existing_email_address(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'email' => 'admin@example.com', 'role' => 'super_admin', 'is_active' => true]);
        CrmLead::query()->create([
            'lead_number' => 'OD-10001', 'name' => 'Existing Lead', 'phone' => '9876500001',
            'email' => 'student@example.com', 'priority' => 'medium', 'status' => 'new',
        ]);

        $this->withSession(['crm_user_id' => $admin->id])->post(route('crm.leads.store'), [
            'name' => 'Duplicate Email', 'phone' => '9876500002', 'email' => 'STUDENT@example.com',
            'priority' => 'medium', 'status' => 'new',
        ])->assertSessionHasErrors(['email'], errorBag: 'leadCreate');

        $this->assertDatabaseCount('crm_leads', 1);
    }

    public function test_an_existing_duplicate_email_does_not_block_unrelated_lead_edits(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);
        $lead = $this->leadFor($admin, 'Legacy One', '9876500011');
        $lead->update(['email' => 'legacy@example.com']);
        $this->leadFor($admin, 'Legacy Two', '9876500012')->update(['email' => 'legacy@example.com']);

        $this->withSession(['crm_user_id' => $admin->id])->put(route('crm.leads.update', $lead), [
            'name' => 'Legacy One Updated', 'phone' => $lead->phone, 'email' => 'legacy@example.com',
            'priority' => 'high', 'status' => 'interested',
            // An open follow-up status has to carry a date; see the follow-up pairing test.
            'follow_up_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('crm_leads', ['id' => $lead->id, 'name' => 'Legacy One Updated', 'priority' => 'high']);
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
            ->assertSee('data-theme-option="classic"', false)
            ->assertSee('data-theme-option="evergreen"', false)
            ->assertSee('data-theme-option="orbit"', false)
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

    public function test_super_admin_can_promote_and_demote_a_team_member(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'email' => 'admin@example.com', 'role' => 'super_admin', 'is_active' => true]);
        $member = CrmUser::query()->create(['name' => 'Asha', 'phone' => '9876543211', 'email' => 'asha@example.com', 'role' => 'counsellor', 'is_active' => true]);

        $this->withSession(['crm_user_id' => $admin->id])
            ->patch(route('crm.team.role', $member))
            ->assertSessionHasNoErrors();
        $this->assertTrue($member->fresh()->isSuperAdmin());
        $this->assertDatabaseHas('crm_audit_logs', ['event' => 'team_member_role_changed', 'subject_id' => $member->id]);

        $this->withSession(['crm_user_id' => $admin->id])
            ->patch(route('crm.team.role', $member))
            ->assertSessionHasNoErrors();
        $this->assertFalse($member->fresh()->isSuperAdmin());
    }

    public function test_role_changes_are_guarded(): void
    {
        config()->set('crm.super_admin.phone', '9876543210');
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'email' => 'admin@example.com', 'role' => 'super_admin', 'is_active' => true]);
        $counsellor = CrmUser::query()->create(['name' => 'Asha', 'phone' => '9876543211', 'email' => 'asha@example.com', 'role' => 'counsellor', 'is_active' => true]);
        $peer = CrmUser::query()->create(['name' => 'Second', 'phone' => '9876543212', 'email' => 'second@example.com', 'role' => 'super_admin', 'is_active' => true]);

        // Nobody can change their own role, and counsellors cannot change roles at all.
        $this->withSession(['crm_user_id' => $admin->id])->patch(route('crm.team.role', $admin))->assertForbidden();
        $this->withSession(['crm_user_id' => $counsellor->id])->patch(route('crm.team.role', $peer))->assertForbidden();

        // Config-defined super admins cannot be demoted (sync would re-promote them anyway).
        $this->withSession(['crm_user_id' => $peer->id])
            ->patch(route('crm.team.role', $admin))
            ->assertSessionHasErrors('team');
        $this->assertTrue($admin->fresh()->isSuperAdmin());
    }

    public function test_super_admin_can_change_a_member_phone_number(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'email' => 'admin@example.com', 'role' => 'super_admin', 'is_active' => true]);
        $member = CrmUser::query()->create(['name' => 'Asha', 'phone' => '9876543211', 'email' => 'asha@example.com', 'role' => 'counsellor', 'is_active' => true]);
        $other = CrmUser::query()->create(['name' => 'Ravi', 'phone' => '9876543212', 'email' => 'ravi@example.com', 'role' => 'counsellor', 'is_active' => true]);

        $this->withSession(['crm_user_id' => $admin->id])->patch(route('crm.team.update', $member), [
            'name' => 'Asha', 'phone' => '+91 91234 56789', 'email' => 'asha@example.com',
        ])->assertSessionHasNoErrors();
        $this->assertSame('9123456789', $member->fresh()->phone);

        // Another member's number is rejected, as is a number that is too short.
        $this->withSession(['crm_user_id' => $admin->id])->patch(route('crm.team.update', $member), [
            'name' => 'Asha', 'phone' => $other->phone, 'email' => 'asha@example.com',
        ])->assertSessionHasErrors('team');
        $this->withSession(['crm_user_id' => $admin->id])->patch(route('crm.team.update', $member), [
            'name' => 'Asha', 'phone' => '12345', 'email' => 'asha@example.com',
        ])->assertSessionHasErrors('team');
        $this->assertSame('9123456789', $member->fresh()->phone);
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

    public function test_converted_status_cannot_bypass_student_conversion(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);
        $lead = $this->leadFor($admin, 'Conversion Path Lead', '9876500088');

        $this->withSession(['crm_user_id' => $admin->id])->put(route('crm.leads.update', $lead), [
            'name' => $lead->name,
            'phone' => $lead->phone,
            'priority' => 'medium',
            'lead_type' => 'general',
            'status' => 'converted',
        ])->assertSessionHasErrors(['status']);

        $lead->refresh();
        $this->assertSame('new', $lead->status);
        $this->assertFalse($lead->is_student);

        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'leads', 'lead' => $lead->id]))
            ->assertOk()
            ->assertSee('Pipeline status')
            ->assertSee('Convert to student')
            ->assertSee('Enrollment is completed later from the Student tab.');
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

    public function test_super_admin_can_delete_a_team_member_and_their_leads_are_unassigned(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'email' => 'admin@example.com', 'role' => 'super_admin', 'is_active' => true]);
        $counsellor = CrmUser::query()->create(['name' => 'Asha', 'phone' => '9876543211', 'role' => 'counsellor', 'is_active' => true]);
        $lead = $this->leadFor($counsellor, 'Owned Lead', '9876500021');

        $this->withSession(['crm_user_id' => $admin->id])
            ->delete(route('crm.team.destroy', $counsellor))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('crm_users', ['id' => $counsellor->id]);
        $this->assertNull($lead->fresh()->assigned_to);
        $this->assertDatabaseHas('crm_audit_logs', ['event' => 'team_member_deleted', 'subject_id' => $counsellor->id]);
    }

    public function test_team_member_cannot_delete_themselves(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);

        $this->withSession(['crm_user_id' => $admin->id])
            ->delete(route('crm.team.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('crm_users', ['id' => $admin->id]);
    }

    public function test_super_admin_can_delete_a_peer_super_admin(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);
        $peer = CrmUser::query()->create(['name' => 'Second', 'phone' => '9876543212', 'role' => 'super_admin', 'is_active' => true]);

        $this->withSession(['crm_user_id' => $admin->id])
            ->delete(route('crm.team.destroy', $peer))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('crm_users', ['id' => $peer->id]);
    }

    public function test_counsellor_cannot_delete_team_members(): void
    {
        $counsellor = CrmUser::query()->create(['name' => 'Asha', 'phone' => '9876543211', 'role' => 'counsellor', 'is_active' => true]);
        $victim = CrmUser::query()->create(['name' => 'Ravi', 'phone' => '9876543212', 'role' => 'counsellor', 'is_active' => true]);

        $this->withSession(['crm_user_id' => $counsellor->id])
            ->delete(route('crm.team.destroy', $victim))
            ->assertForbidden();

        $this->assertDatabaseHas('crm_users', ['id' => $victim->id]);
    }

    public function test_enrolled_students_can_be_filtered_by_journey_stage(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);
        $docPending = $this->leadFor($admin, 'Doc Pending Student', '9876500051');
        $docPending->update(['is_student' => true, 'status' => 'converted', 'student_stage' => 'doc_pending']);
        $visaGranted = $this->leadFor($admin, 'Visa Granted Student', '9876500052');
        $visaGranted->update(['is_student' => true, 'status' => 'converted', 'student_stage' => 'visa_granted']);

        // The students view exposes a journey-stage filter and shows the stage column.
        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'students']))
            ->assertOk()
            ->assertSee('name="student_stage"', false)
            ->assertSee('All journey stages')
            ->assertSee('<th>Journey stage</th>', false)
            ->assertSee('<th>Category</th>', false)
            ->assertSee('Doc Pending Student')
            ->assertSee('Visa Granted Student')
            ->assertSee('Visa granted');

        // Filtering by a stage narrows the list to matching students only.
        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'students', 'student_stage' => 'visa_granted']))
            ->assertOk()
            ->assertSee('Visa Granted Student')
            ->assertDontSee('Doc Pending Student');
    }

    public function test_lead_workspace_no_longer_offers_a_delete_option(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);
        $lead = $this->leadFor($admin, 'Undeletable Lead', '9876500031');

        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'leads', 'lead' => $lead->id]))
            ->assertOk()
            ->assertDontSee('Delete lead')
            ->assertDontSee('Lead administration')
            ->assertDontSee('Move lead to trash');
    }

    public function test_leads_view_filters_by_the_next_follow_up_date(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);

        $this->leadFor($admin, 'Morning Lead', '9876500001')->update(['follow_up_at' => now()->addDays(3)->setTime(9, 15)]);
        $this->leadFor($admin, 'Evening Lead', '9876500002')->update(['follow_up_at' => now()->addDays(3)->setTime(18, 45)]);
        $this->leadFor($admin, 'Other Day Lead', '9876500003')->update(['follow_up_at' => now()->addDays(4)->setTime(11, 0)]);
        $this->leadFor($admin, 'Unscheduled Lead', '9876500004');

        // Assert on the paginator rather than the HTML: the follow-up reminder
        // popover renders upcoming leads regardless of the active filters.
        $listed = function (array $query) use ($admin): array {
            $response = $this->withSession(['crm_user_id' => $admin->id])
                ->get(route('crm.dashboard', ['view' => 'leads'] + $query))->assertOk();

            return $response->viewData('leads')->pluck('name')->sort()->values()->all();
        };

        // The whole chosen day matches, whatever time the follow-up was set for.
        $this->assertSame(
            ['Evening Lead', 'Morning Lead'],
            $listed(['follow_up_date' => now()->addDays(3)->format('Y-m-d')]),
        );
        $this->assertSame(['Other Day Lead'], $listed(['follow_up_date' => now()->addDays(4)->format('Y-m-d')]));
        $this->assertSame([], $listed(['follow_up_date' => now()->addDays(9)->format('Y-m-d')]));

        // No date and an unparseable date both leave the list untouched.
        $this->assertContains('Unscheduled Lead', $listed(['follow_up_date' => '']));
        $this->assertContains('Unscheduled Lead', $listed(['follow_up_date' => 'not-a-date']));

        $this->withSession(['crm_user_id' => $admin->id])->get(route('crm.dashboard', ['view' => 'leads']))
            ->assertOk()
            ->assertSee('name="follow_up_date"', false)
            ->assertSee('Next follow-up');
    }

    public function test_lead_details_expose_academic_background_fields_for_manual_entry(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);
        $lead = $this->leadFor($admin, 'Academic Lead', '9876500041');

        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'leads', 'lead' => $lead->id]))
            ->assertOk()
            ->assertSee('Academic background')
            ->assertSee('Graduation CGPA / %')
            ->assertSee('English Proficiency Test')
            ->assertSee('Aptitude test')
            ->assertSee('name="tenth_passing_year"', false)
            ->assertSee('data-test-repeater="english_tests"', false)
            ->assertSee('data-test-repeater="aptitude_tests"', false);

        $this->withSession(['crm_user_id' => $admin->id])->put(route('crm.leads.update', $lead), [
            'name' => $lead->name, 'phone' => $lead->phone, 'priority' => 'medium', 'status' => 'new',
            'tenth_score' => '88.4', 'tenth_passing_year' => '2019',
            'twelfth_score' => '91', 'twelfth_passing_year' => '2021',
            'graduation_score' => '8.2 CGPA', 'graduation_passing_year' => '2025', 'backlogs' => '0',
            'english_tests' => [
                ['test' => 'ielts', 'score' => '7.5', 'date' => '2026-03-14'],
                ['test' => 'toefl', 'score' => '105', 'date' => ''],
                ['test' => '', 'score' => '', 'date' => ''],
            ],
            'aptitude_tests' => [
                ['test' => 'gre', 'score' => '320', 'date' => ''],
                ['test' => 'other', 'name' => 'CLAT', 'score' => '92 percentile', 'date' => ''],
            ],
        ])->assertSessionHasNoErrors();

        $lead->refresh();
        $this->assertSame('88.4', $lead->tenth_score);
        $this->assertSame(2019, $lead->tenth_passing_year);
        $this->assertSame('8.2 CGPA', $lead->graduation_score);
        // The blank row is dropped and the rest re-indexed.
        $this->assertSame([
            ['test' => 'ielts', 'name' => null, 'score' => '7.5', 'date' => '2026-03-14'],
            ['test' => 'toefl', 'name' => null, 'score' => '105', 'date' => null],
        ], $lead->english_tests);
        $this->assertSame([
            ['test' => 'gre', 'name' => null, 'score' => '320', 'date' => null],
            ['test' => 'other', 'name' => 'CLAT', 'score' => '92 percentile', 'date' => null],
        ], $lead->aptitude_tests);
        $this->assertDatabaseHas('crm_lead_activities', ['crm_lead_id' => $lead->id, 'type' => 'updated']);

        $base = ['name' => $lead->name, 'phone' => $lead->phone, 'priority' => 'medium', 'status' => 'new'];
        $this->withSession(['crm_user_id' => $admin->id])
            ->put(route('crm.leads.update', $lead), $base + ['aptitude_tests' => [['test' => 'not-a-test']]])
            ->assertSessionHasErrors('aptitude_tests.0.test');

        // "Other" must carry the free-text test name.
        $this->withSession(['crm_user_id' => $admin->id])
            ->put(route('crm.leads.update', $lead), $base + ['english_tests' => [['test' => 'other', 'score' => '60']]])
            ->assertSessionHasErrors('english_tests.0.name');

        // Removing every row submits no rows at all — the hidden marker still clears the column.
        $this->withSession(['crm_user_id' => $admin->id])
            ->put(route('crm.leads.update', $lead), $base + ['english_tests_present' => '1'])
            ->assertSessionHasNoErrors();
        $lead->refresh();
        $this->assertNull($lead->english_tests);
        $this->assertNotNull($lead->aptitude_tests);
    }

    public function test_every_view_offers_a_soft_refresh_button_in_all_three_themes(): void
    {
        $admin = CrmUser::query()->create([
            'name' => 'Refresh Admin', 'phone' => '9811111111', 'email' => 'refresh@example.com',
            'role' => 'super_admin', 'is_active' => true,
        ]);

        // The topbar is shared chrome, so one button reaches Classic, Evergreen
        // and Orbit; only the spin animation is theme-independent CSS.
        foreach (['dashboard', 'leads', 'followups', 'students', 'enrollments', 'mock-invites'] as $view) {
            $this->withSession(['crm_user_id' => $admin->id])
                ->get(route('crm.dashboard', ['view' => $view]))
                ->assertOk()
                ->assertSee('data-crm-refresh', false)
                ->assertSee('icon-btn-refresh', false)
                ->assertSee('aria-label="Refresh this view"', false);
        }
    }

    public function test_the_refresh_button_swaps_in_place_rather_than_reloading(): void
    {
        $script = file_get_contents(public_path('assets/crm/crm.js'));

        // It must go through loadCrmPage (the fetch + swap path) with a replaced
        // history entry — never location.reload(), which would flash the chrome
        // and lose scroll, the open drawer and any typed filters.
        $this->assertStringContainsString("target.closest('[data-crm-refresh]')", $script);
        $this->assertStringContainsString("loadCrmPage(window.location.href, {", $script);
        $this->assertStringContainsString("historyMode: 'replace'", $script);
        $this->assertStringNotContainsString('location.reload', $script);

        $css = file_get_contents(public_path('assets/crm/crm-dashboard.css'));
        $this->assertStringContainsString('crmRefreshSpin', $css);
    }

    public function test_the_follow_up_planner_holds_every_open_conversation(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);

        // Every open status belongs in the planner, with or without a date.
        foreach (['not_answered', 'call_back', 'follow_up', 'interested', 'future_lead'] as $index => $status) {
            $this->leadFor($admin, 'Open '.$status, '98765100'.$index)->update(['status' => $status]);
        }
        // A dated, still-incomplete follow-up stays in the planner whatever the status.
        $this->leadFor($admin, 'Dated New Lead', '9876510090')->update(['follow_up_at' => now()->addDay()]);
        // Closed and completed leads stay out.
        $this->leadFor($admin, 'Junk Lead', '9876510091')->update(['status' => 'junk']);
        $this->leadFor($admin, 'Done Lead', '9876510092')
            ->update(['follow_up_at' => now()->addDay(), 'follow_up_completed_at' => now()]);

        $planner = $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'followups']))->assertOk();

        foreach (['Open not_answered', 'Open call_back', 'Open follow_up', 'Open interested', 'Dated New Lead'] as $name) {
            $planner->assertSee($name);
        }
        $planner->assertDontSee('Junk Lead')->assertDontSee('Done Lead');
    }

    public function test_every_sidebar_badge_counts_the_view_it_opens(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'email' => 'a@example.com', 'role' => 'super_admin', 'is_active' => true]);

        // Two open conversations, only one of them overdue — the case where the
        // Follow-ups badge used to read 1 beside a list of 2, because it counted
        // overdue rather than what the planner holds.
        $this->leadFor($admin, 'Overdue One', '9876520001')
            ->update(['status' => 'follow_up', 'follow_up_at' => now()->subDays(2)]);
        $this->leadFor($admin, 'Upcoming One', '9876520002')
            ->update(['status' => 'interested', 'follow_up_at' => now()->addDays(4)]);
        $this->leadFor($admin, 'Closed One', '9876520003')->update(['status' => 'junk']);

        $dashboard = $this->withSession(['crm_user_id' => $admin->id])->get(route('crm.dashboard'))->assertOk()->getContent();

        foreach (['leads' => 'nav-leads', 'followups' => 'nav-followups'] as $view => $navClass) {
            preg_match('~'.$navClass.'\s.*?</a>~s', $dashboard, $nav);
            $this->assertNotEmpty($nav, "The {$navClass} link was not found.");
            preg_match('~nav-badge[^>]*>(\d+)</span>~', $nav[0], $badge);
            $this->assertNotEmpty($badge, "The {$navClass} badge was not found.");

            $page = $this->withSession(['crm_user_id' => $admin->id])
                ->get(route('crm.dashboard', ['view' => $view]))->assertOk()->getContent();
            $this->assertSame(
                (int) $badge[1],
                substr_count($page, '<tr data-crm-href'),
                "The {$navClass} badge does not match the number of rows in the view it opens."
            );
        }

        // Overdue keeps its own, smaller number — it is a different question, and
        // the badge now carries the urgency as a colour instead of as the count.
        $this->assertStringContainsString('nav-badge is-alert', $dashboard);
        $this->assertStringContainsString('title="2 open · 1 overdue"', $dashboard);
    }

    public function test_the_follow_up_card_the_badge_and_the_planner_all_report_the_same_number(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'email' => 'a@example.com', 'role' => 'super_admin', 'is_active' => true]);

        $n = 0;
        foreach (\App\Support\CrmOptions::FOLLOW_UP_STATUSES as $status) {
            $n++;
            $this->leadFor($admin, 'Open '.$status, '987651'.str_pad((string) $n, 4, '0', STR_PAD_LEFT))
                ->update(['status' => $status]);
        }
        // Parked on a status that is not "open" but with a conversation booked.
        // The planner holds it, so every figure about the planner must count it —
        // missing it is what made the card read 1 beside a list of 2.
        $this->leadFor($admin, 'Booked But New', '9876519003')
            ->update(['status' => 'new', 'follow_up_at' => now()->addDays(2)]);
        // Neither of these belongs anywhere near the planner.
        $this->leadFor($admin, 'Junk One', '9876519001')->update(['status' => 'junk']);
        $this->leadFor($admin, 'Done One', '9876519002')
            ->update(['follow_up_at' => now()->subDay(), 'follow_up_completed_at' => now()]);

        $expected = count(\App\Support\CrmOptions::FOLLOW_UP_STATUSES) + 1;

        $dashboard = $this->withSession(['crm_user_id' => $admin->id])->get(route('crm.dashboard'))->assertOk();
        $dashboard->assertSee('<strong>'.$expected.'</strong><span>In follow-up</span>', false);
        $dashboard->assertSee('title="'.$expected.' open · 0 overdue"', false);

        $planner = $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'followups']))->assertOk();
        $this->assertSame($expected, substr_count($planner->getContent(), '<tr data-crm-href'));
        $planner->assertSee('Booked But New')->assertDontSee('Junk One')->assertDontSee('Done One');

        // The grouped status filter still narrows the leads list to the open
        // statuses; it is just no longer what the card opens.
        $grouped = $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'leads', 'status' => \App\Support\CrmOptions::FOLLOW_UP_GROUP]))
            ->assertOk();
        $grouped->assertDontSee('Junk One')->assertDontSee('Booked But New');
        foreach (\App\Support\CrmOptions::FOLLOW_UP_STATUSES as $status) {
            $grouped->assertSee('Open '.$status);
        }

        // The grouped value is a filter only — it must never land on a lead.
        $this->assertArrayNotHasKey(\App\Support\CrmOptions::FOLLOW_UP_GROUP, \App\Support\CrmOptions::STATUSES);
    }

    public function test_every_dashboard_number_lands_on_a_list_of_exactly_that_many_leads(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'email' => 'a@example.com', 'role' => 'super_admin', 'is_active' => true]);

        $n = 0;
        $make = function (array $attrs) use (&$n, $admin): void {
            $n++;
            CrmLead::query()->create([
                'lead_number' => 'OD-'.str_pad((string) $n, 5, '0', STR_PAD_LEFT), 'name' => 'Lead '.$n,
                'phone' => '98765'.str_pad((string) $n, 5, '0', STR_PAD_LEFT),
                'priority' => 'medium', 'status' => 'new', 'assigned_to' => $admin->id, 'created_by' => $admin->id,
            ])->update($attrs);
        };

        $make(['status' => 'new']);
        $make(['status' => 'interested', 'follow_up_at' => now()->addDays(3)]);
        $make(['status' => 'interested', 'follow_up_at' => now()->subDays(2)]);
        $make(['status' => 'follow_up', 'follow_up_at' => now()->subDays(5)]);
        $make(['status' => 'call_back', 'follow_up_at' => now()->subDay()]);
        $make(['status' => 'future_lead', 'follow_up_at' => now()->addMonths(8)]);
        $make(['status' => 'not_answered']);
        $make(['status' => 'junk']);
        $make(['is_student' => true, 'status' => 'converted', 'student_stage' => 'doc_pending']);
        $make(['follow_up_at' => now()->subDays(9), 'follow_up_completed_at' => now()]);

        $dashboard = $this->withSession(['crm_user_id' => $admin->id])->get(route('crm.dashboard'))->assertOk()->getContent();

        // Read each figure and its destination out of the rendered page, follow the
        // link, and count the rows. A number that opens a differently sized list is
        // the bug this catches: the Overdue card once said 3 and opened all 7 open
        // conversations, because it linked at the planner with no filter at all.
        $checked = 0;
        // [pattern, index of the number, index of the label] — the three blocks
        // order those two differently in the markup.
        $patterns = [
            'stat card' => ['~<a class="stat[^"]*" href="([^"]+)">.*?<strong>([\d,]+)</strong><span>([^<]+)</span>~s', 2, 3],
            'metric' => ['~<a href="([^"]+)"><span>([^<]+)</span><strong>(\d+)</strong>~', 3, 2],
            'pipeline row' => ['~<a class="pipeline-breakdown-row" href="([^"]+)">.*?</i>([^<]+)</span>.*?<b>(\d+)</b>~s', 3, 2],
        ];
        foreach ($patterns as $kind => [$pattern, $figureAt, $labelAt]) {
            preg_match_all($pattern, $dashboard, $matches, PREG_SET_ORDER);
            $this->assertNotEmpty($matches, "No {$kind} was found on the dashboard.");
            foreach ($matches as $match) {
                [, $href] = $match;
                $figure = (int) str_replace(',', '', $match[$figureAt]);
                $label = trim($match[$labelAt]);
                $page = $this->withSession(['crm_user_id' => $admin->id])
                    ->get(html_entity_decode($href))->assertOk()->getContent();
                $this->assertSame(
                    $figure,
                    substr_count($page, '<tr data-crm-href'),
                    "The {$kind} \"{$label}\" reads {$figure} but its link opens a different number of leads."
                );
                $checked++;
            }
        }
        // Six cards, two linked metrics, and one row per status present.
        $this->assertGreaterThanOrEqual(14, $checked);
    }

    public function test_the_owner_filter_reaches_unassigned_leads_and_deactivated_owners(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'email' => 'a@example.com', 'role' => 'super_admin', 'is_active' => true]);
        $active = CrmUser::query()->create(['name' => 'Active Counsellor', 'phone' => '9876543211', 'email' => 'ac@example.com', 'role' => 'counsellor', 'is_active' => true]);
        $former = CrmUser::query()->create(['name' => 'Former Counsellor', 'phone' => '9876543212', 'email' => 'fc@example.com', 'role' => 'counsellor', 'is_active' => false]);

        $this->leadFor($active, 'Owned Lead', '9876510161');
        $legacy = $this->leadFor($former, 'Inherited Lead', '9876510162');
        $orphan = $this->leadFor($admin, 'Nobody Owns Me', '9876510163');
        $orphan->update(['assigned_to' => null]);

        $page = $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'leads']))->assertOk();

        // A deactivated counsellor still owns leads, so the filter must reach them
        // — their name shows in the Owner column either way.
        // One flat list — names and counts, no grouping.
        $page->assertDontSee('No longer taking leads')
            ->assertDontSee('<optgroup', false)
            ->assertSee('Former Counsellor · 1')
            ->assertSee('Active Counsellor · 1')
            ->assertSee('Unassigned · 1');

        // Selecting each option narrows to exactly those leads.
        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'leads', 'assigned_to' => 'unassigned']))
            ->assertOk()->assertSee('Nobody Owns Me')->assertDontSee('Owned Lead');

        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'leads', 'assigned_to' => $former->id]))
            ->assertOk()->assertSee('Inherited Lead')->assertDontSee('Owned Lead');

        unset($legacy);
    }

    public function test_the_specific_source_filter_dropdown_is_gone(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);
        $this->leadFor($admin, 'Sourced Lead', '9876510171')->update(['source' => 'Instagram']);

        $html = $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'leads']))
            ->assertOk()
            ->assertDontSee('All specific sources')
            ->getContent();
        // Only the dropdown goes. The "Lead source" text field on the lead form
        // is a different control and stays.
        $this->assertDoesNotMatchRegularExpression('/<select[^>]*name="source"/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*name="source"/', $html);

        // The clause behind it stays, so an explicit link or saved export URL
        // still narrows the way it always did.
        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'leads', 'source' => 'Instagram']))
            ->assertOk()->assertSee('Sourced Lead');
    }

    public function test_intake_is_free_text_on_the_academic_card_and_reaches_the_export(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);
        $lead = $this->leadFor($admin, 'Intake Lead', '9876510181');

        $this->withSession(['crm_user_id' => $admin->id])->put(route('crm.leads.update', $lead), [
            'name' => $lead->name, 'phone' => $lead->phone, 'priority' => 'medium', 'status' => 'new',
            // Anything the counsellor types is accepted — intakes are named
            // differently per destination, so there is no fixed list to match.
            'intake' => "Spring '27 (rolling)",
        ])->assertSessionHasNoErrors();
        $this->assertSame("Spring '27 (rolling)", $lead->fresh()->intake);

        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'leads', 'lead' => $lead->id]))
            ->assertOk()
            ->assertSee('name="intake"', false)
            ->assertSee('list="leadIntakeOptions"', false)
            // The datalist only suggests; it never constrains.
            ->assertSee('<datalist id="leadIntakeOptions">', false)
            ->assertSee('September '.now()->addYear()->year);

        $csv = $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.leads.export'))->streamedContent();
        $this->assertStringContainsString('Intake', $csv);
        $this->assertStringContainsString("Spring '27 (rolling)", $csv);
    }

    public function test_future_lead_is_a_selectable_status_that_behaves_like_the_other_open_ones(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);
        $lead = $this->leadFor($admin, 'Next Intake Lead', '9876510151');
        $payload = ['name' => $lead->name, 'phone' => $lead->phone, 'priority' => 'medium'];

        $this->assertSame('Future lead', \App\Support\CrmOptions::STATUSES['future_lead']);
        $this->assertContains('future_lead', \App\Support\CrmOptions::FOLLOW_UP_STATUSES);

        // It is an open status, so it carries the same dated-follow-up rule.
        $this->withSession(['crm_user_id' => $admin->id])
            ->put(route('crm.leads.update', $lead), $payload + ['status' => 'future_lead'])
            ->assertSessionHasErrors('follow_up_at');

        $this->withSession(['crm_user_id' => $admin->id])
            ->put(route('crm.leads.update', $lead), $payload + [
                'status' => 'future_lead', 'follow_up_at' => now()->addMonths(6)->format('Y-m-d H:i:s'),
            ])->assertSessionHasNoErrors();
        $this->assertSame('future_lead', $lead->fresh()->status);

        // ...and it is held in the planner rather than dropping out of sight.
        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'followups']))
            ->assertOk()
            ->assertSee('Next Intake Lead')
            ->assertSee('status-future_lead', false);

        // Every theme needs the badge colour, or it renders as an unstyled pill.
        foreach (['crm.css', 'crm-classic.css', 'crm-orbit.css'] as $theme) {
            $this->assertStringContainsString(
                '.status-future_lead{',
                file_get_contents(public_path('assets/crm/'.$theme)),
                "{$theme} is missing the Future lead badge colour."
            );
        }
    }

    public function test_open_follow_up_statuses_are_tinted_brown_in_the_status_dropdowns(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);
        $lead = $this->leadFor($admin, 'Tinted Lead', '9876510141');
        $lead->update(['status' => 'call_back', 'follow_up_at' => now()->addDay()]);

        $page = $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'leads', 'lead' => $lead->id]))->assertOk();

        // Each open-status option carries the tint class...
        foreach (['not_answered', 'call_back', 'follow_up', 'interested', 'future_lead'] as $status) {
            $page->assertSee('<option value="'.$status.'" class="is-followup-status"', false);
        }
        // ...the closed select is tinted while one of them is selected...
        $page->assertSee('data-followup-tinted class="is-followup-status"', false);
        // ...and closed statuses stay untinted.
        $page->assertSee('<option value="junk" class=""', false);
        $page->assertSee('Brown-tinted statuses keep this lead in the Follow-up planner.');
        // The date is mandatory while the lead sits on one of those statuses.
        $page->assertSee('is-followup-required', false)->assertSee('Required for this status');
    }

    public function test_an_open_follow_up_status_requires_a_follow_up_date(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);
        $lead = $this->leadFor($admin, 'Needs A Date', '9876510101');
        $payload = ['name' => $lead->name, 'phone' => $lead->phone, 'priority' => 'medium'];

        $this->withSession(['crm_user_id' => $admin->id])
            ->put(route('crm.leads.update', $lead), $payload + ['status' => 'call_back'])
            ->assertSessionHasErrors('follow_up_at');
        $this->assertSame('new', $lead->fresh()->status);

        $this->withSession(['crm_user_id' => $admin->id])
            ->put(route('crm.leads.update', $lead), $payload + [
                'status' => 'call_back', 'follow_up_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ])->assertSessionHasNoErrors();
        $this->assertSame('call_back', $lead->fresh()->status);
    }

    public function test_scheduling_a_follow_up_defaults_the_status_to_follow_up(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);
        $lead = $this->leadFor($admin, 'Fresh Enquiry', '9876510111');
        $payload = ['name' => $lead->name, 'phone' => $lead->phone, 'priority' => 'medium'];

        // Picking a date on a "New lead" moves it into the planner.
        $this->withSession(['crm_user_id' => $admin->id])
            ->put(route('crm.leads.update', $lead), $payload + [
                'status' => 'new', 'follow_up_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ])->assertSessionHasNoErrors();
        $this->assertSame('follow_up', $lead->fresh()->status);

        // A status the counsellor already chose from the open set is left alone.
        $this->withSession(['crm_user_id' => $admin->id])
            ->put(route('crm.leads.update', $lead), $payload + [
                'status' => 'interested', 'follow_up_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
            ])->assertSessionHasNoErrors();
        $this->assertSame('interested', $lead->fresh()->status);

        // Re-saving without touching the date keeps the counsellor's own status.
        $unchanged = $lead->fresh()->follow_up_at->format('Y-m-d H:i:s');
        $this->withSession(['crm_user_id' => $admin->id])
            ->put(route('crm.leads.update', $lead), $payload + ['status' => 'not_interested', 'follow_up_at' => $unchanged])
            ->assertSessionHasNoErrors();
        $this->assertSame('not_interested', $lead->fresh()->status);
    }

    public function test_a_test_row_can_record_that_the_student_never_sat_the_test(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);
        $lead = $this->leadFor($admin, 'No Tests Yet', '9876510121');

        $this->withSession(['crm_user_id' => $admin->id])->put(route('crm.leads.update', $lead), [
            'name' => $lead->name, 'phone' => $lead->phone, 'priority' => 'medium', 'status' => 'new',
            // A stray score submitted alongside "Not taken" is dropped, not stored.
            'english_tests' => [['test' => 'not_taken', 'score' => '7.5', 'date' => '2026-03-14']],
            'aptitude_tests' => [['test' => 'not_taken']],
        ])->assertSessionHasNoErrors();

        $lead->refresh();
        $this->assertSame([['test' => 'not_taken', 'name' => null, 'score' => null, 'date' => null]], $lead->english_tests);
        $this->assertSame([['test' => 'not_taken', 'name' => null, 'score' => null, 'date' => null]], $lead->aptitude_tests);

        // Both catalogs offer the option on the academic card.
        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'leads', 'lead' => $lead->id]))
            ->assertOk()
            ->assertSee('value="not_taken"', false)
            ->assertSee('>Not taken</option>', false);
    }

    public function test_every_crm_list_numbers_its_rows(): void
    {
        $admin = CrmUser::query()->create([
            'name' => 'Admin', 'phone' => '9876543210', 'email' => 'admin@example.com',
            'role' => 'super_admin', 'is_active' => true,
        ]);
        $this->leadFor($admin, 'Numbered Lead', '9876510131')
            ->update(['status' => 'interested', 'follow_up_at' => now()->addDay()]);
        $this->leadFor($admin, 'Numbered Student', '9876510132')
            ->update(['is_student' => true, 'status' => 'converted', 'student_stage' => 'doc_pending']);
        \App\Models\PaymentAttempt::query()->create([
            'request_token' => str_repeat('c', 64), 'session_hash' => str_repeat('d', 64),
            'page_slug' => 'test-preparation', 'block_id' => 'compare-plans', 'option_index' => 0,
            'item_name' => 'IELTS Coaching', 'amount' => 1800000, 'currency' => 'INR',
            'customer_name' => 'Rhea Kapoor', 'customer_email' => 'rhea@example.com',
            'customer_phone' => '9876543219', 'status' => 'order_created',
        ]);
        \App\Models\CrmSubscriber::query()->create([
            'email' => 'reader@example.com', 'source' => 'Blog newsletter', 'status' => 'active', 'subscribed_at' => now(),
        ]);
        \App\Models\CrmMockInterviewInvite::query()->create([
            'token' => \App\Models\CrmMockInterviewInvite::freshToken(), 'recipient_name' => 'Rahul Student',
            'question_count' => 15, 'max_uses' => 3, 'created_by' => $admin->id, 'expires_at' => now()->addDays(30),
        ]);

        foreach (['leads', 'followups', 'students', 'enrollments', 'subscriptions', 'mock-invites'] as $view) {
            $this->withSession(['crm_user_id' => $admin->id])
                ->get(route('crm.dashboard', ['view' => $view]))
                ->assertOk()
                ->assertSee('class="col-serial">Serial No', false)
                ->assertSee('class="col-serial">1</td>', false);
        }

        // The audit log is a card list rather than a table, so it numbers entries too.
        \App\Models\CrmAuditLog::query()->create([
            'crm_user_id' => $admin->id, 'event' => 'crm_login', 'description' => 'Signed in.',
        ]);
        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'audit']))
            ->assertOk()
            ->assertSee('class="audit-serial">1</span>', false);
    }

    public function test_lead_list_says_how_many_records_it_holds_and_lets_the_page_size_change(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);
        for ($i = 1; $i <= 30; $i++) {
            $this->leadFor($admin, 'Paged Lead '.$i, '98765'.str_pad((string) $i, 5, '0', STR_PAD_LEFT));
        }

        // The old page size stopped at 20 with nothing on screen saying so, which
        // read as records that had never been saved.
        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'leads']))
            ->assertOk()
            ->assertSee('of <strong>30</strong> records', false)
            ->assertSee('name="per_page"', false);

        // A smaller page still paginates, and the count line names the page.
        $response = $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'leads', 'per_page' => 25]))
            ->assertOk()
            ->assertSee('page 1 of 2');
        $this->assertSame(25, $response->viewData('leads')->count());

        // Page size survives into the pagination links, or page 2 would silently
        // fall back to the default.
        $this->assertStringContainsString('per_page=25', $response->viewData('leads')->url(2));

        // Anything not on the offered list falls back to the default rather than
        // letting a URL ask for an unbounded page.
        $fallback = $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'leads', 'per_page' => 5000]))->assertOk();
        $this->assertSame(50, $fallback->viewData('leads')->perPage());
    }

    public function test_counselling_and_shortlisting_is_recorded_on_the_pipeline_card(): void
    {
        $admin = CrmUser::query()->create(['name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true]);
        $lead = $this->leadFor($admin, 'Shortlist Lead', '9876510191');

        // Blank until someone records it — an untouched lead is not a "no".
        $this->assertNull($lead->counselling_shortlisting);
        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'leads', 'lead' => $lead->id]))
            ->assertOk()
            ->assertSee('name="counselling_shortlisting"', false)
            ->assertSee('Counselling and Shortlisting');

        $payload = ['name' => $lead->name, 'phone' => $lead->phone, 'priority' => 'medium', 'status' => 'new'];
        $this->withSession(['crm_user_id' => $admin->id])
            ->put(route('crm.leads.update', $lead), $payload + ['counselling_shortlisting' => 'yes'])
            ->assertSessionHasNoErrors();
        $this->assertSame('yes', $lead->fresh()->counselling_shortlisting);

        // The change is told in words on the timeline, not as a raw column name.
        $this->assertStringContainsString(
            'Counselling and shortlisting set to “Yes”.',
            (string) $lead->activities()->where('type', 'updated')->latest()->first()?->body,
        );

        $this->withSession(['crm_user_id' => $admin->id])
            ->put(route('crm.leads.update', $lead), $payload + ['counselling_shortlisting' => 'maybe'])
            ->assertSessionHasErrors('counselling_shortlisting');

        // Clearing it puts the lead back to "not recorded".
        $this->withSession(['crm_user_id' => $admin->id])
            ->put(route('crm.leads.update', $lead), $payload + ['counselling_shortlisting' => ''])
            ->assertSessionHasNoErrors();
        $this->assertNull($lead->fresh()->counselling_shortlisting);

        $lead->update(['counselling_shortlisting' => 'no']);
        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'leads']))
            ->assertOk()
            ->assertSee('✕ No');

        $csv = $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.leads.export'))->streamedContent();
        $this->assertStringContainsString('Counselling and shortlisting', $csv);
    }

    private function leadFor(CrmUser $owner, string $name, string $phone): CrmLead
    {
        return CrmLead::query()->create([
            'lead_number' => 'OD-'.substr($phone, -5), 'name' => $name, 'phone' => $phone,
            'priority' => 'medium', 'status' => 'new', 'assigned_to' => $owner->id, 'created_by' => $owner->id,
        ]);
    }
}
