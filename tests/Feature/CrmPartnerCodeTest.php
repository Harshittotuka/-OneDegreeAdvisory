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
 *
 * A code is no longer issued on its own screen. Creating a partner on the Team
 * screen makes the account and the code together, and the tests below that name
 * crm.team.store are testing exactly that join.
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

    public function test_creating_a_partner_creates_the_account_and_the_code_together(): void
    {
        $admin = $this->admin();

        $this->withSession(['crm_user_id' => $admin->id])->post(route('crm.team.store'), [
            'name' => 'Rhea Nair',
            'phone' => '+91 98765 43230',
            'email' => 'referrals@acme-education.test',
            'role' => 'partner',
            'partner_access' => 'read',
            'company_name' => 'Acme Education',
            'code' => 'acme10',
            'company_link' => 'https://acme-education.test',
        ])->assertSessionHasNoErrors()
            // Straight to the tab holding the link, because the link is the point.
            ->assertRedirect(route('crm.dashboard', ['view' => 'partner-codes']));

        $account = CrmUser::query()->where('email', 'referrals@acme-education.test')->sole();
        $code = CrmPartnerCode::query()->sole();

        // One company, one record each side, joined.
        $this->assertSame($account->id, $code->crm_user_id);
        $this->assertTrue($account->isPartner());

        // Stored in capitals, which is what makes the link case-insensitive.
        $this->assertSame('ACME10', $code->code);
        $this->assertSame('Acme Education', $code->company_name);
        $this->assertSame('https://acme-education.test', $code->company_link);
        $this->assertTrue($code->is_active);
        $this->assertSame($admin->id, $code->created_by);

        // The contact details are not asked for twice: referral notices go to the
        // address the partner signs in with.
        $this->assertSame('referrals@acme-education.test', $code->email);
        $this->assertSame('Rhea Nair', $code->contact_name);
        $this->assertSame('9876543230', $code->phone);

        // Both halves are recorded in the audit log, like any other admin action.
        $this->assertDatabaseHas('crm_audit_logs', ['event' => 'team_member_created', 'subject_id' => $account->id]);
        $this->assertDatabaseHas('crm_audit_logs', [
            'event' => 'partner_code_created',
            'subject_type' => 'partner_code',
            'subject_id' => $code->id,
        ]);
    }

    /**
     * The merge, from the side the person sees: a partner is created once, and
     * both screens then show both halves of them.
     */
    public function test_the_tab_lists_partners_and_sends_adding_one_to_the_team_form(): void
    {
        $admin = $this->admin();
        $session = ['crm_user_id' => $admin->id];

        $this->withSession($session)->post(route('crm.team.store'), [
            'name' => 'Rhea Nair', 'phone' => '9876543220', 'email' => 'rhea@acme-education.test',
            'role' => 'partner', 'partner_access' => 'edit',
            'company_name' => 'Acme Education', 'code' => 'ACME10',
        ])->assertSessionHasNoErrors();

        // The tab carries no create form of its own — only the way to the one form.
        $this->withSession($session)->get(route('crm.dashboard', ['view' => 'partner-codes']))
            ->assertOk()
            ->assertSee('Add new partner')
            ->assertSee(e(route('crm.dashboard', ['view' => 'team', 'add' => 1, 'role' => 'partner'])), false)
            ->assertDontSee('Add partner code')
            // And each row says who signs in for the company.
            ->assertSee('Signs in as Rhea Nair')
            ->assertSee('Read and edit');

        // The account's own pane holds the company, the code and the link.
        $partner = CrmUser::query()->where('role', 'partner')->sole();
        $this->withSession($session)->get(route('crm.dashboard', ['view' => 'team', 'member' => $partner->id]))
            ->assertOk()
            ->assertSee('Referral company')
            ->assertSee('name="company_name"', false)
            ->assertSee('value="ACME10"', false)
            ->assertSee(route('profiler', ['partner' => 'ACME10']), false);

        // And the Team form arrives on the partner role when the tab sent it there.
        $this->withSession($session)->get(route('crm.dashboard', ['view' => 'team', 'add' => 1, 'role' => 'partner']))
            ->assertOk()
            ->assertSee('Add a partner')
            ->assertSee('data-partner-fields', false)
            ->assertSee('value="partner" selected', false);
    }

    /**
     * The merge, from the side that bit first: a partner has one set of fields,
     * and both screens that edit them save the same two records. A form that
     * showed a partner's details and wrote back only half of them was the split
     * this feature set out to close.
     */
    public function test_both_partner_forms_carry_the_same_fields(): void
    {
        $admin = $this->admin();
        $session = ['crm_user_id' => $admin->id];
        [$partner, $code] = $this->partnerWithCode($session);

        $forms = [
            // Creating one, on the Team screen.
            $this->withSession($session)
                ->get(route('crm.dashboard', ['view' => 'team', 'add' => 1, 'role' => 'partner']))->assertOk(),
            // That partner's own pane, which is where the Partner codes tab sends
            // you to edit one.
            $this->withSession($session)
                ->get(route('crm.dashboard', ['view' => 'team', 'member' => $partner->id]))->assertOk(),
        ];

        // Same inputs, same labels, on all three — they come from one partial.
        foreach ($forms as $form) {
            foreach (['name', 'phone', 'email', 'partner_access', 'company_name', 'code', 'company_link'] as $field) {
                $form->assertSee('name="'.$field.'"', false);
            }
            foreach (['Full name', 'Mobile number', 'Email address', 'Partner access', 'Referral company', 'Company name', 'Custom code', 'Company link'] as $label) {
                $form->assertSee($label);
            }
        }
    }

    public function test_saving_from_the_team_screen_carries_the_company_with_it(): void
    {
        $admin = $this->admin();
        $session = ['crm_user_id' => $admin->id];
        [$partner] = $this->partnerWithCode($session);

        $this->withSession($session)->patch(route('crm.team.update', $partner), [
            'name' => 'Rhea N Nair', 'phone' => '9876543221', 'email' => 'hello@acme-education.test',
            'role' => 'partner', 'partner_access' => 'read',
            'company_name' => 'Acme Education Pvt Ltd', 'code' => 'ACME10',
        ])->assertSessionHasNoErrors();

        // The address a partner signs in with is the address their referrals go
        // to, so moving one moves the other.
        $code = CrmPartnerCode::query()->sole();
        $this->assertSame('hello@acme-education.test', $code->email);
        $this->assertSame('Rhea N Nair', $code->contact_name);
        $this->assertSame('9876543221', $code->phone);
        $this->assertSame('Acme Education Pvt Ltd', $code->company_name);
    }

    public function test_the_tab_sends_editing_to_the_partner_and_offers_no_form_of_its_own(): void
    {
        $admin = $this->admin();
        $session = ['crm_user_id' => $admin->id];
        [$partner, $code] = $this->partnerWithCode($session);

        // Edit is a link to the partner, not a second form over the same fields.
        $this->withSession($session)->get(route('crm.dashboard', ['view' => 'partner-codes']))
            ->assertOk()
            ->assertSee(e(route('crm.dashboard', ['view' => 'team', 'role' => 'partner', 'member' => $partner->id])), false)
            ->assertDontSee('name="company_name"', false)
            ->assertDontSee('Editing');

        // ?edit= was that form's state and means nothing now: the row is a row.
        $this->withSession($session)->get(route('crm.dashboard', ['view' => 'partner-codes', 'edit' => $code->id]))
            ->assertOk()
            ->assertDontSee('name="company_name"', false);

        // And the tab can no longer write a partner at all.
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('crm.partner-codes.update'));
    }



    /**
     * The Team screen carries the link's state and its controls, not just a copy
     * box: a paused link is otherwise invisible from the screen a partner is
     * managed on, and pausing meant leaving for another tab.
     */
    public function test_the_team_screen_shows_the_link_state_and_can_pause_it(): void
    {
        $admin = $this->admin();
        $session = ['crm_user_id' => $admin->id];
        [$partner, $code] = $this->partnerWithCode($session);

        $memberUrl = route('crm.dashboard', ['view' => 'team', 'member' => $partner->id]);

        $this->withSession($session)->get($memberUrl)->assertOk()
            ->assertSee('Live')
            ->assertSee('Pause their link')
            ->assertSee('Remove their link')
            ->assertSee(route('crm.partner-codes.toggle', $code), false)
            ->assertSee(route('crm.partner-codes.destroy', $code), false);

        // The list says so too, so it is visible without opening anyone.
        $this->withSession($session)->get(route('crm.dashboard', ['view' => 'team']))->assertOk()
            ->assertSee('ACME10')
            ->assertSee('Live')
            ->assertDontSee('Link off');

        // Pausing from here is the same endpoint the Partner codes tab posts to.
        $this->withSession($session)->patch(route('crm.partner-codes.toggle', $code))->assertSessionHasNoErrors();
        $this->assertFalse($code->fresh()->is_active);

        $this->withSession($session)->get($memberUrl)->assertOk()
            ->assertSee('Resume their link')
            ->assertSee('recorded without a partner');

        // And the two off-states are told apart in the list: the login still works.
        $this->withSession($session)->get(route('crm.dashboard', ['view' => 'team']))->assertOk()
            ->assertSee('Link off')
            ->assertSee('Active');

        // Removing the link leaves the account standing.
        $this->withSession($session)->delete(route('crm.partner-codes.destroy', $code))->assertSessionHasNoErrors();
        $this->assertSame(0, CrmPartnerCode::query()->count());
        $this->assertTrue($partner->fresh()->is_active);
        $this->withSession($session)->get($memberUrl)->assertOk()->assertSee('no tracking link yet');
    }

    public function test_deleting_a_partner_account_pauses_the_link_rather_than_losing_it(): void
    {
        $admin = $this->admin();
        $session = ['crm_user_id' => $admin->id];

        $this->withSession($session)->post(route('crm.team.store'), [
            'name' => 'Rhea Nair', 'phone' => '9876543220', 'email' => 'rhea@acme-education.test',
            'role' => 'partner', 'partner_access' => 'read',
            'company_name' => 'Acme Education', 'code' => 'ACME10',
        ])->assertSessionHasNoErrors();

        $partner = CrmUser::query()->where('role', 'partner')->sole();
        $code = CrmPartnerCode::query()->sole();
        $lead = CrmLead::query()->create([
            'lead_number' => 'OD-10001', 'name' => 'Referred Student', 'phone' => '9998887771',
            'priority' => 'medium', 'status' => 'new', 'partner_code_id' => $code->id,
        ]);

        $this->withSession($session)->delete(route('crm.team.destroy', $partner))->assertSessionHasNoErrors();

        // The company record outlives the login, so the lead keeps its attribution,
        // but the link stops crediting and stops emailing a partner we have dropped.
        $code->refresh();
        $this->assertFalse($code->is_active);
        $this->assertNull($code->crm_user_id);
        $this->assertSame($code->id, $lead->fresh()->partner_code_id);
        $this->assertNull(CrmPartnerCode::resolve('ACME10'));
    }

    /**
     * A code whose account is gone keeps the leads it referred. It is listed and
     * can be paused or removed, but there is nothing left to edit about it: the
     * fields belonged to a partner who no longer exists.
     */
    public function test_an_orphaned_code_keeps_its_leads_and_can_still_be_removed(): void
    {
        $admin = $this->admin();
        $session = ['crm_user_id' => $admin->id];
        $code = $this->partnerCode('Legacy Referrals', 'LEGACY');
        $lead = CrmLead::query()->create([
            'lead_number' => 'OD-10001', 'name' => 'Referred Student', 'phone' => '9998887771',
            'priority' => 'medium', 'status' => 'new', 'partner_code_id' => $code->id,
        ]);

        $this->withSession($session)->get(route('crm.dashboard', ['view' => 'partner-codes']))
            ->assertOk()
            ->assertSee('LEGACY')
            ->assertSee('No workspace account')
            ->assertSee('1 lead')
            ->assertSee(route('crm.partner-codes.toggle', $code), false);

        $this->withSession($session)->patch(route('crm.partner-codes.toggle', $code))->assertSessionHasNoErrors();
        $this->assertFalse($code->fresh()->is_active);
        $this->assertSame($code->id, $lead->fresh()->partner_code_id);

        $this->withSession($session)->delete(route('crm.partner-codes.destroy', $code))->assertSessionHasNoErrors();
        $this->assertNull($lead->fresh()->partner_code_id);
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

        // And cannot reach the one place a partner is created from either.
        $this->withSession(['crm_user_id' => $counsellor->id])->post(route('crm.team.store'), [
            'name' => 'Sneaky Contact', 'phone' => '9876543212', 'email' => 'hi@sneaky.test',
            'role' => 'partner', 'partner_access' => 'read',
            'company_name' => 'Sneaky Referrals', 'code' => 'SNEAK',
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

        // Adding one goes to the Team screen, which holds the whole partner.
        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'partner-codes']))
            ->assertOk()
            ->assertSee(e(route('crm.dashboard', ['view' => 'team', 'add' => 1, 'role' => 'partner'])), false);
    }

    public function test_a_code_is_unique_however_it_is_typed(): void
    {
        $admin = $this->admin();
        $this->partnerCode('Acme Education', 'ACME10');

        $base = [
            'name' => 'Other Contact', 'phone' => '9876543220', 'email' => 'hi@other.test',
            'role' => 'partner', 'partner_access' => 'read', 'company_name' => 'Other Company',
        ];

        $this->withSession(['crm_user_id' => $admin->id])
            ->post(route('crm.team.store'), [...$base, 'code' => 'acme10'])
            ->assertSessionHasErrors('code');

        // And a code has to survive being put in a URL.
        $this->withSession(['crm_user_id' => $admin->id])
            ->post(route('crm.team.store'), [...$base, 'code' => 'not a code!'])
            ->assertSessionHasErrors('code');

        // A partner without one is refused outright — a partner with no link is
        // a partner nothing can be credited to.
        $this->withSession(['crm_user_id' => $admin->id])
            ->post(route('crm.team.store'), $base)
            ->assertSessionHasErrors('code');

        $this->assertSame(1, CrmPartnerCode::query()->count());
        // None of the three refusals left an account behind either.
        $this->assertDatabaseMissing('crm_users', ['email' => 'hi@other.test']);
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

    /**
     * The bug this covers: a profiler arriving through a partner's link was
     * credited to their code and stayed invisible to them. A partner's workspace
     * is filtered by partner_id alone, and capture only ever set partner_code_id,
     * so the partner logged in to an empty list holding their own referral.
     */
    public function test_a_referral_through_a_partners_link_is_visible_to_that_partner(): void
    {
        $admin = $this->admin();
        $session = ['crm_user_id' => $admin->id];
        [$partner, $code] = $this->partnerWithCode($session);

        $this->post('/profiler', [
            'action' => 'submit', 'degree' => 'masters', 'section' => 6,
            'answers' => ['q_ec_level' => 'Just Participated'],
            'contact' => ['name' => 'Referred Student', 'email' => 'referred@student.test', 'phone' => '9998887771'],
            'partner' => 'acme10',
        ])->assertOk()->assertJson(['ok' => true]);

        $lead = CrmLead::query()->where('email', 'referred@student.test')->sole();
        $this->assertSame($code->id, $lead->partner_code_id);
        // Both halves: credited to the code, and named to the account behind it.
        $this->assertSame($partner->id, $lead->partner_id);

        // Which is what the partner's own workspace is filtered by.
        $this->withSession(['crm_user_id' => $partner->id])
            ->get(route('crm.dashboard', ['view' => 'leads']))
            ->assertOk()
            ->assertSee('Referred Student');
    }

    /** A code nobody signs in for still credits, and names no one. */
    public function test_a_code_with_no_account_credits_without_naming_a_partner(): void
    {
        $this->partnerCode('Legacy Referrals', 'LEGACY');

        $this->post('/profiler', [
            'action' => 'submit', 'degree' => 'masters', 'section' => 6,
            'answers' => ['q_ec_level' => 'Just Participated'],
            'contact' => ['name' => 'Legacy Student', 'email' => 'legacy@student.test', 'phone' => '9998887772'],
            'partner' => 'LEGACY',
        ])->assertOk()->assertJson(['ok' => true]);

        $lead = CrmLead::query()->where('email', 'legacy@student.test')->sole();
        $this->assertNotNull($lead->partner_code_id);
        $this->assertNull($lead->partner_id);
    }

    /* ─────────────────── the lead's own field ─────────────────── */

    /**
     * A partner and their code are one company, so the lead form stopped asking
     * for both. The code is shown, carries the partner's, and is never a second
     * dropdown that could name a different company from the first.
     */
    public function test_the_lead_form_shows_the_code_and_does_not_offer_it_as_a_choice(): void
    {
        $admin = $this->admin();
        $session = ['crm_user_id' => $admin->id];
        [$partner, $code] = $this->partnerWithCode($session);

        $lead = CrmLead::query()->create([
            'lead_number' => 'OD-10001', 'name' => 'Referred Student', 'phone' => '9998887771',
            'priority' => 'medium', 'status' => 'new', 'partner_code_id' => $code->id,
        ]);

        $this->withSession($session)->get(route('crm.dashboard', ['view' => 'leads', 'lead' => $lead->id]))
            ->assertOk()
            // Shown, not chosen: a readonly display plus the value it posts.
            ->assertSee('data-partner-code-display', false)
            ->assertSee('data-partner-code-input', false)
            ->assertSee('Acme Education (ACME10)')
            // And the partner options carry the code the choice sets.
            ->assertSee('data-code-label="Acme Education (ACME10)"', false)
            ->assertDontSee('<select name="partner_code_id"', false);

        // Naming the partner and posting their code together is accepted.
        $payload = [
            'name' => 'Referred Student', 'phone' => '9998887771', 'priority' => 'medium', 'status' => 'new',
            'partner_id' => $partner->id, 'partner_code_id' => $code->id,
        ];
        $this->withSession($session)->put(route('crm.leads.update', $lead), $payload)->assertSessionHasNoErrors();
        $this->assertSame($code->id, $lead->fresh()->partner_code_id);
        $this->assertSame($partner->id, $lead->fresh()->partner_id);

        // Even once their link is paused: naming a partner brings their code with
        // them rather than failing the save.
        $this->withSession($session)->patch(route('crm.partner-codes.toggle', $code))->assertSessionHasNoErrors();
        $this->withSession($session)->put(route('crm.leads.update', $lead), $payload)->assertSessionHasNoErrors();
        $this->assertSame($code->id, $lead->fresh()->partner_code_id);
    }


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

    /**
     * A partner made the way the CRM makes one: account and code in a single post.
     *
     * @return array{0: CrmUser, 1: CrmPartnerCode}
     */
    private function partnerWithCode(array $session): array
    {
        $this->withSession($session)->post(route('crm.team.store'), [
            'name' => 'Rhea Nair', 'phone' => '9876543220', 'email' => 'rhea@acme-education.test',
            'role' => 'partner', 'partner_access' => 'read',
            'company_name' => 'Acme Education', 'code' => 'ACME10',
        ])->assertSessionHasNoErrors();

        return [CrmUser::query()->where('role', 'partner')->sole(), CrmPartnerCode::query()->sole()];
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
