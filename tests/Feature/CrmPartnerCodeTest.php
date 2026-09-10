<?php

namespace Tests\Feature;

use App\Mail\ProfileReportPartnerMail;
use App\Mail\ProfileReportTeamMail;
use App\Mail\ProfileReportThankYouMail;
use App\Models\CrmLead;
use App\Models\CrmPartnerCode;
use App\Models\CrmUser;
use App\Models\CrmWebsiteSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Partner codes: a referral company, the code we give them, and what a profiler
 * submission carrying that code does.
 *
 * The feature is only worth having if three things hold, and each is covered
 * here: only a super admin issues a code, a submission through /profiler?partner=
 * is credited to that company and emails them alongside us, and a code that was
 * never issued (or has been paused) changes nothing at all about the capture.
 */
class CrmPartnerCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    /* ───────────────────────── the CRM tab ───────────────────────── */

    public function test_a_super_admin_creates_a_partner_code(): void
    {
        $admin = $this->admin();

        $this->withSession(['crm_user_id' => $admin->id])->post(route('crm.partner-codes.store'), [
            'company_name' => 'Acme Education',
            'code' => 'acme10',
            'email' => 'referrals@acme-education.test',
            'phone' => '+91 98765 43210',
            'contact_name' => 'Rhea Nair',
            'company_link' => 'https://acme-education.test',
        ])->assertSessionHasNoErrors();

        $code = CrmPartnerCode::query()->sole();
        // Stored in capitals, which is what makes the link case-insensitive.
        $this->assertSame('ACME10', $code->code);
        $this->assertSame('Acme Education', $code->company_name);
        $this->assertSame('referrals@acme-education.test', $code->email);
        $this->assertSame('Rhea Nair', $code->contact_name);
        $this->assertSame('https://acme-education.test', $code->company_link);
        $this->assertTrue($code->is_active);
        $this->assertSame($admin->id, $code->created_by);

        // The issuing is recorded in the audit log like any other admin action.
        $this->assertDatabaseHas('crm_audit_logs', [
            'event' => 'partner_code_created',
            'subject_type' => 'partner_code',
            'subject_id' => $code->id,
        ]);
    }

    public function test_only_a_super_admin_reaches_the_partner_codes_tab(): void
    {
        $admin = $this->admin();
        $counsellor = $this->counsellor('Asha', '9876543211');

        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'partner-codes']))
            ->assertOk()->assertSee('Partner codes');

        // Asking for the screen without the rights says so rather than silently
        // landing somewhere else.
        $this->withSession(['crm_user_id' => $counsellor->id])
            ->get(route('crm.dashboard', ['view' => 'partner-codes']))
            ->assertForbidden();

        $this->withSession(['crm_user_id' => $counsellor->id])->post(route('crm.partner-codes.store'), [
            'company_name' => 'Sneaky Referrals', 'code' => 'SNEAK', 'email' => 'hi@sneaky.test',
        ])->assertForbidden();

        $this->assertDatabaseCount('crm_partner_codes', 0);
    }

    public function test_the_tab_shows_each_code_its_link_and_its_lead_count(): void
    {
        $admin = $this->admin();
        $code = $this->partnerCode('Acme Education', 'ACME10');
        CrmLead::query()->create([
            'lead_number' => 'OD-10001', 'name' => 'Referred Student', 'phone' => '9998887771',
            'priority' => 'medium', 'status' => 'new', 'partner_code_id' => $code->id,
        ]);

        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'partner-codes']))
            ->assertOk()
            ->assertSee('Acme Education')
            ->assertSee('ACME10')
            // The link to hand out is on the row, not something to assemble by hand.
            ->assertSee(route('profiler', ['partner' => 'ACME10']), false)
            ->assertSee('1 lead');

        // ?edit= opens that row's fields in place, filled in.
        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'partner-codes', 'edit' => $code->id]))
            ->assertOk()
            ->assertSee('Editing Acme Education')
            ->assertSee('name="company_name"', false);
    }

    public function test_a_code_is_unique_however_it_is_typed(): void
    {
        $admin = $this->admin();
        $this->partnerCode('Acme Education', 'ACME10');

        $this->withSession(['crm_user_id' => $admin->id])->post(route('crm.partner-codes.store'), [
            'company_name' => 'Other Company', 'code' => 'acme10', 'email' => 'hi@other.test',
        ])->assertSessionHasErrors('code');

        // And a code has to survive being put in a URL.
        $this->withSession(['crm_user_id' => $admin->id])->post(route('crm.partner-codes.store'), [
            'company_name' => 'Other Company', 'code' => 'not a code!', 'email' => 'hi@other.test',
        ])->assertSessionHasErrors('code');

        $this->assertSame(1, CrmPartnerCode::query()->count());
    }

    public function test_pausing_a_code_keeps_the_leads_it_already_brought_in(): void
    {
        $admin = $this->admin();
        $code = $this->partnerCode('Acme Education', 'ACME10');
        $lead = CrmLead::query()->create([
            'lead_number' => 'OD-10001', 'name' => 'Referred Student', 'phone' => '9998887771',
            'priority' => 'medium', 'status' => 'new', 'partner_code_id' => $code->id,
        ]);

        $this->withSession(['crm_user_id' => $admin->id])
            ->patch(route('crm.partner-codes.toggle', $code))->assertSessionHasNoErrors();

        $this->assertFalse($code->fresh()->is_active);
        $this->assertSame($code->id, $lead->fresh()->partner_code_id);

        // Deleting is the other route: the lead survives, minus its attribution.
        $this->withSession(['crm_user_id' => $admin->id])
            ->delete(route('crm.partner-codes.destroy', $code))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('crm_partner_codes', 0);
        $this->assertNotNull($lead->fresh());
        $this->assertNull($lead->fresh()->partner_code_id);
    }

    /* ─────────────────── the profiler link ─────────────────── */

    public function test_the_profiler_page_carries_an_issued_code_and_drops_an_unknown_one(): void
    {
        $this->partnerCode('Acme Education', 'ACME10');

        // Typed in lower case, matched anyway.
        $this->get('/profiler?partner=acme10')->assertOk()->assertSee('partner: "ACME10"', false);

        $this->get('/profiler?partner=NOPE')->assertOk()->assertSee('partner: null', false);
        $this->get('/profiler')->assertOk()->assertSee('partner: null', false);
    }

    public function test_a_submission_through_a_partner_link_credits_and_notifies_the_partner(): void
    {
        $code = $this->partnerCode('Acme Education', 'ACME10');

        $this->post('/profiler', [
            'action' => 'submit',
            'degree' => 'masters',
            'section' => 6,
            'answers' => ['q_ec_level' => 'Just Participated'],
            'contact' => ['name' => 'Referred Student', 'email' => 'student@mailbox.test', 'phone' => '9998887771'],
            'partner' => 'acme10',
        ])->assertOk()->assertJson(['ok' => true]);

        // 1. The lead names the partner code.
        $lead = CrmLead::query()->sole();
        $this->assertSame($code->id, $lead->partner_code_id);
        $this->assertSame('Acme Education', $lead->partnerCode->company_name);

        // 2. The submission keeps the code too, so the record of which link this
        //    form came through survives the lead being re-attributed later.
        $submission = CrmWebsiteSubmission::query()->sole();
        $this->assertSame('ACME10', $submission->meta['partner_code']);
        $this->assertSame('Acme Education', $submission->meta['partner_company']);

        // 3. The timeline says where it came from.
        $this->assertStringContainsString('Referred by Acme Education (ACME10).', $lead->activities()->first()->body);

        // 4. Three emails: our mailbox, the student, and the partner.
        Mail::assertSent(ProfileReportTeamMail::class);
        Mail::assertSent(ProfileReportThankYouMail::class);
        Mail::assertSent(ProfileReportPartnerMail::class, fn (ProfileReportPartnerMail $mail): bool => $mail->hasTo('referrals@acme-education.test')
            && $mail->partner->code === 'ACME10');
    }

    public function test_a_partner_notice_carries_the_student_but_not_the_report_pdf(): void
    {
        $this->partnerCode('Acme Education', 'ACME10');

        $this->post('/profiler', [
            'action' => 'submit', 'degree' => 'masters', 'section' => 6,
            'answers' => ['q_ec_level' => 'Just Participated'],
            'contact' => ['name' => 'Referred Student', 'email' => 'student@mailbox.test', 'phone' => '9998887771'],
            'partner' => 'ACME10',
        ])->assertOk();

        Mail::assertSent(ProfileReportPartnerMail::class, function (ProfileReportPartnerMail $mail): bool {
            $rendered = $mail->render();
            // The full profile report stays internal — the partner gets the
            // student's details and the headline facts, no PDF.
            $mail->assertHasNoAttachments();

            return str_contains($rendered, 'Referred Student')
                && str_contains($rendered, 'student@mailbox.test')
                && str_contains($rendered, 'ACME10');
        });

        // Our own notification names the referrer, so the team knows to credit it.
        Mail::assertSent(ProfileReportTeamMail::class, fn (ProfileReportTeamMail $mail): bool => str_contains($mail->render(), 'Acme Education (ACME10)'));
    }

    public function test_an_unknown_or_paused_code_records_the_lead_with_no_partner(): void
    {
        $paused = $this->partnerCode('Dormant Partners', 'DORMANT');
        $paused->update(['is_active' => false]);

        foreach (['NEVER-ISSUED', 'DORMANT', ''] as $index => $submitted) {
            $this->post('/profiler', [
                'action' => 'submit', 'degree' => 'masters', 'section' => 6,
                'answers' => ['q_ec_level' => 'Just Participated'],
                'contact' => ['name' => 'Student '.$index, 'email' => 'student'.$index.'@mailbox.test', 'phone' => '99988877'.(10 + $index)],
                'partner' => $submitted,
            ])->assertOk()->assertJson(['ok' => true]);
        }

        // Every profile is still captured — the attribution is the only casualty.
        $this->assertSame(3, CrmLead::query()->count());
        $this->assertSame(0, CrmLead::query()->whereNotNull('partner_code_id')->count());
        Mail::assertNotSent(ProfileReportPartnerMail::class);
        Mail::assertSent(ProfileReportTeamMail::class, 3);
    }

    public function test_the_first_referral_keeps_the_credit(): void
    {
        $first = $this->partnerCode('First Partner', 'FIRST');
        $second = $this->partnerCode('Second Partner', 'SECOND');

        $submit = fn (string $partner) => $this->post('/profiler', [
            'action' => 'submit', 'degree' => 'masters', 'section' => 6,
            'answers' => ['q_ec_level' => 'Just Participated'],
            'contact' => ['name' => 'Returning Student', 'email' => 'returning@mailbox.test', 'phone' => '9998887771'],
            'partner' => $partner,
        ])->assertOk();

        $submit('FIRST');
        $submit('SECOND');

        // One lead, still credited to whoever originally sent them.
        $lead = CrmLead::query()->sole();
        $this->assertSame($first->id, $lead->partner_code_id);
        $this->assertNotSame($second->id, $lead->partner_code_id);

        // The second partner is still told about the submission they generated —
        // it came through their link, whoever the lead is credited to.
        Mail::assertSent(ProfileReportPartnerMail::class, 2);
    }

    /* ─────────────────── the lead's own field ─────────────────── */

    public function test_the_team_can_correct_an_attribution_and_a_partner_cannot(): void
    {
        $admin = $this->admin();
        $code = $this->partnerCode('Acme Education', 'ACME10');
        $partnerAccount = CrmUser::query()->create([
            'name' => 'Watching Partner', 'phone' => '9876543230', 'email' => 'watch@mailbox.test',
            'role' => 'partner', 'partner_access' => 'edit', 'is_active' => true,
        ]);
        $lead = CrmLead::query()->create([
            'lead_number' => 'OD-10001', 'name' => 'Referred Student', 'phone' => '9998887771',
            'priority' => 'medium', 'status' => 'new', 'partner_id' => $partnerAccount->id,
        ]);

        $payload = [
            'name' => 'Referred Student', 'phone' => '9998887771', 'priority' => 'medium', 'status' => 'new',
            'partner_code_id' => $code->id,
        ];

        $this->withSession(['crm_user_id' => $admin->id])
            ->put(route('crm.leads.update', $lead), $payload)->assertSessionHasNoErrors();
        $this->assertSame($code->id, $lead->fresh()->partner_code_id);

        // Where a lead came from is our record, not the partner's to rewrite —
        // even one given edit access.
        $this->withSession(['crm_user_id' => $partnerAccount->id])
            ->put(route('crm.leads.update', $lead), [...$payload, 'partner_code_id' => ''])
            ->assertSessionHasErrors('partner_code_id');
        $this->assertSame($code->id, $lead->fresh()->partner_code_id);
    }

    /* ───────────────────────── helpers ───────────────────────── */

    private function admin(): CrmUser
    {
        return CrmUser::query()->create([
            'name' => 'Admin', 'phone' => '9876543210', 'email' => 'admin@mailbox.test',
            'role' => 'super_admin', 'is_active' => true,
        ]);
    }

    private function counsellor(string $name, string $phone): CrmUser
    {
        return CrmUser::query()->create([
            'name' => $name, 'phone' => $phone, 'email' => strtolower($name).'@mailbox.test',
            'role' => 'counsellor', 'is_active' => true,
        ]);
    }

    private function partnerCode(string $company, string $code): CrmPartnerCode
    {
        return CrmPartnerCode::query()->create([
            'company_name' => $company,
            'code' => CrmPartnerCode::normaliseCode($code),
            'email' => 'referrals@'.strtolower(str_replace(' ', '-', $company)).'.test',
            'contact_name' => 'Rhea Nair',
            'is_active' => true,
        ]);
    }
}
