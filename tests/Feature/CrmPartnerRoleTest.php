<?php

namespace Tests\Feature;

use App\Models\CrmLead;
use App\Models\CrmUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The Partner role: an outside referral partner who signs in to watch the
 * students whose "Partner name" field carries their name.
 *
 * Three rules are what the role is for, and each is covered here: only a super
 * admin creates one, only the team sets the Partner field on a lead, and a
 * partner reaches nothing but the leads naming them — at whichever of the two
 * access levels the super admin chose.
 */
class CrmPartnerRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_only_a_super_admin_can_create_a_partner_account(): void
    {
        $admin = $this->admin();
        $counsellor = $this->counsellor('Asha', '9876543211');

        // A partner is created in full: the login and the referral company behind
        // it are one act on one form (see CrmPartnerCodeTest for the code half).
        $this->withSession(['crm_user_id' => $admin->id])->post(route('crm.team.store'), [
            'name' => 'Bright Futures', 'phone' => '9876543220', 'email' => 'partner@mailbox.test',
            'role' => 'partner', 'partner_access' => 'read',
            'company_name' => 'Bright Futures', 'code' => 'BRIGHT',
        ])->assertSessionHasNoErrors();

        $partner = CrmUser::query()->where('email', 'partner@mailbox.test')->firstOrFail();
        $this->assertTrue($partner->isPartner());
        $this->assertSame('read', $partner->partner_access);
        $this->assertFalse($partner->canEditLeads());

        // A counsellor cannot create any account, partner included.
        $this->withSession(['crm_user_id' => $counsellor->id])->post(route('crm.team.store'), [
            'name' => 'Second Partner', 'phone' => '9876543221', 'email' => 'partner2@mailbox.test',
            'role' => 'partner', 'partner_access' => 'edit',
        ])->assertForbidden();

        // And neither can a partner.
        $this->withSession(['crm_user_id' => $partner->id])->post(route('crm.team.store'), [
            'name' => 'Third Partner', 'phone' => '9876543222', 'email' => 'partner3@mailbox.test',
            'role' => 'partner', 'partner_access' => 'edit',
        ])->assertForbidden();

        $this->assertSame(1, CrmUser::query()->where('role', 'partner')->count());
    }

    public function test_creating_a_partner_requires_an_access_level(): void
    {
        $admin = $this->admin();

        $this->withSession(['crm_user_id' => $admin->id])->post(route('crm.team.store'), [
            'name' => 'No Access Chosen', 'phone' => '9876543220', 'email' => 'partner@mailbox.test', 'role' => 'partner',
        ])->assertSessionHasErrors('partner_access');

        $this->assertDatabaseMissing('crm_users', ['email' => 'partner@mailbox.test']);

        // The switch is meaningless on the in-house roles, so it is never stored.
        $this->withSession(['crm_user_id' => $admin->id])->post(route('crm.team.store'), [
            'name' => 'A Counsellor', 'phone' => '9876543221', 'email' => 'counsellor@mailbox.test',
            'role' => 'counsellor', 'partner_access' => 'edit',
        ])->assertSessionHasNoErrors();

        $this->assertNull(CrmUser::query()->where('email', 'counsellor@mailbox.test')->firstOrFail()->partner_access);
    }

    public function test_a_partner_sees_only_the_leads_naming_them(): void
    {
        $counsellor = $this->counsellor('Asha', '9876543211');
        $partner = $this->partner('Bright Futures', '9876543220', 'read');
        $other = $this->partner('Rival Agency', '9876543221', 'edit');

        $mine = $this->leadFor($counsellor, 'Partner Student', '9876500001', $partner);
        $theirs = $this->leadFor($counsellor, 'Rival Student', '9876500002', $other);
        $none = $this->leadFor($counsellor, 'Direct Student', '9876500003');

        $this->withSession(['crm_user_id' => $partner->id])->get(route('crm.dashboard', ['view' => 'leads']))
            ->assertOk()
            ->assertSee('Partner Student')
            ->assertDontSee('Rival Student')
            ->assertDontSee('Direct Student');

        // Their own lead opens; the other two are not theirs to touch at all.
        $this->withSession(['crm_user_id' => $partner->id])
            ->get(route('crm.dashboard', ['view' => 'leads', 'lead' => $mine->id]))
            ->assertOk()->assertSee('Partner Student');

        foreach ([$theirs, $none] as $hidden) {
            $this->withSession(['crm_user_id' => $partner->id])
                ->put(route('crm.leads.update', $hidden), [
                    'name' => 'Blocked', 'phone' => $hidden->phone, 'priority' => 'medium', 'status' => 'new',
                ])->assertForbidden();
        }

        // The counsellor who owns all three still sees all three.
        $this->withSession(['crm_user_id' => $counsellor->id])->get(route('crm.dashboard', ['view' => 'leads']))
            ->assertOk()->assertSee('Partner Student')->assertSee('Rival Student')->assertSee('Direct Student');
    }

    public function test_a_read_only_partner_cannot_change_anything(): void
    {
        $counsellor = $this->counsellor('Asha', '9876543211');
        $partner = $this->partner('Bright Futures', '9876543220', 'read');
        $lead = $this->leadFor($counsellor, 'Partner Student', '9876500001', $partner);

        $this->withSession(['crm_user_id' => $partner->id])->put(route('crm.leads.update', $lead), [
            'name' => 'Renamed', 'phone' => $lead->phone, 'priority' => 'high', 'status' => 'interested',
            'follow_up_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertForbidden();

        $this->withSession(['crm_user_id' => $partner->id])
            ->post(route('crm.leads.comments.store', $lead), ['comment' => 'Blocked note'])
            ->assertForbidden();

        $this->assertSame('Partner Student', $lead->fresh()->name);
        $this->assertDatabaseMissing('crm_lead_activities', ['crm_lead_id' => $lead->id, 'type' => 'comment']);

        // The drawer still renders, with the form locked and no save button.
        $this->withSession(['crm_user_id' => $partner->id])
            ->get(route('crm.dashboard', ['view' => 'leads', 'lead' => $lead->id]))
            ->assertOk()
            ->assertSee('Partner access · read only')
            ->assertSee('<fieldset class="drawer-fieldset" disabled', false)
            ->assertDontSee('Save lead details');
    }

    public function test_an_edit_partner_can_update_their_students_but_not_the_partner_field(): void
    {
        $counsellor = $this->counsellor('Asha', '9876543211');
        $partner = $this->partner('Bright Futures', '9876543220', 'edit');
        $other = $this->partner('Rival Agency', '9876543221', 'edit');
        $lead = $this->leadFor($counsellor, 'Partner Student', '9876500001', $partner);

        $this->withSession(['crm_user_id' => $partner->id])->put(route('crm.leads.update', $lead), [
            'name' => 'Partner Student', 'phone' => $lead->phone, 'priority' => 'high', 'status' => 'interested',
            'city' => 'Jaipur', 'follow_up_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertSessionHasNoErrors();

        $this->assertSame('Jaipur', $lead->fresh()->city);
        $this->assertSame('high', $lead->fresh()->priority);

        $this->withSession(['crm_user_id' => $partner->id])
            ->post(route('crm.leads.comments.store', $lead), ['comment' => 'Spoke to the family.'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('crm_lead_activities', ['crm_lead_id' => $lead->id, 'type' => 'comment']);

        // Moving the lead to another partner — or to themselves — is refused
        // outright rather than quietly ignored.
        $this->withSession(['crm_user_id' => $partner->id])->put(route('crm.leads.update', $lead), [
            'name' => 'Partner Student', 'phone' => $lead->phone, 'priority' => 'high', 'status' => 'interested',
            'partner_id' => $other->id, 'follow_up_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors('partner_id');

        $this->assertSame($partner->id, $lead->fresh()->partner_id);

        // The counsellor owner is likewise not theirs to set.
        $this->withSession(['crm_user_id' => $partner->id])->put(route('crm.leads.update', $lead), [
            'name' => 'Partner Student', 'phone' => $lead->phone, 'priority' => 'high', 'status' => 'interested',
            'assigned_to' => $partner->id, 'follow_up_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors('assigned_to');
    }

    public function test_a_counsellor_and_a_super_admin_both_set_the_partner_field(): void
    {
        $admin = $this->admin();
        $counsellor = $this->counsellor('Asha', '9876543211');
        $partner = $this->partner('Bright Futures', '9876543220', 'read');
        $lead = $this->leadFor($counsellor, 'Direct Student', '9876500001');

        $this->withSession(['crm_user_id' => $counsellor->id])->put(route('crm.leads.update', $lead), [
            'name' => $lead->name, 'phone' => $lead->phone, 'priority' => 'medium', 'status' => 'new',
            'partner_id' => $partner->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame($partner->id, $lead->fresh()->partner_id);
        // The change is told as a sentence on the timeline, by partner name.
        $this->assertStringContainsString(
            'Partner set to',
            (string) $lead->activities()->where('type', 'updated')->first()->body,
        );

        $this->withSession(['crm_user_id' => $admin->id])->put(route('crm.leads.update', $lead), [
            'name' => $lead->name, 'phone' => $lead->phone, 'priority' => 'medium', 'status' => 'new',
            'partner_id' => '',
        ])->assertSessionHasNoErrors();

        $this->assertNull($lead->fresh()->partner_id);

        // Only a partner account can be named there.
        $this->withSession(['crm_user_id' => $admin->id])->put(route('crm.leads.update', $lead), [
            'name' => $lead->name, 'phone' => $lead->phone, 'priority' => 'medium', 'status' => 'new',
            'partner_id' => $counsellor->id,
        ])->assertSessionHasErrors('partner_id');
    }

    public function test_an_edit_partners_drawer_is_unlocked(): void
    {
        $counsellor = $this->counsellor('Asha', '9876543211');
        $partner = $this->partner('Bright Futures', '9876543220', 'edit');
        $lead = $this->leadFor($counsellor, 'Partner Student', '9876500001', $partner);

        $this->withSession(['crm_user_id' => $partner->id])
            ->get(route('crm.dashboard', ['view' => 'leads', 'lead' => $lead->id]))
            ->assertOk()
            ->assertSee('Partner access · read and edit')
            ->assertSee('<fieldset class="drawer-fieldset" >', false)
            ->assertSee('Save lead details')
            ->assertSee('Add to timeline')
            // The Partner field is not even rendered for them, and neither is the
            // counsellor owner or the "Mark completed" planner button.
            ->assertDontSee('name="partner_id"', false)
            ->assertDontSee('name="assigned_to"', false)
            ->assertDontSee('Mark completed');
    }

    public function test_a_deactivated_partner_stays_on_the_leads_already_naming_them(): void
    {
        $admin = $this->admin();
        $counsellor = $this->counsellor('Asha', '9876543211');
        $partner = $this->partner('Bright Futures', '9876543220', 'read');
        $lead = $this->leadFor($counsellor, 'Partner Student', '9876500001', $partner);

        $this->withSession(['crm_user_id' => $admin->id])
            ->patch(route('crm.team.toggle', $partner))
            ->assertSessionHasNoErrors();
        $this->assertFalse($partner->fresh()->is_active);

        // Saving the lead keeps the name rather than silently blanking the field,
        // because the dropdown still offers a partner who holds leads.
        $this->withSession(['crm_user_id' => $counsellor->id])->put(route('crm.leads.update', $lead), [
            'name' => $lead->name, 'phone' => $lead->phone, 'priority' => 'medium', 'status' => 'new',
            'partner_id' => $partner->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame($partner->id, $lead->fresh()->partner_id);

        // A switched-off partner cannot be put on a lead that does not name them.
        $fresh = $this->leadFor($counsellor, 'Direct Student', '9876500002');
        $this->withSession(['crm_user_id' => $counsellor->id])->put(route('crm.leads.update', $fresh), [
            'name' => $fresh->name, 'phone' => $fresh->phone, 'priority' => 'medium', 'status' => 'new',
            'partner_id' => $partner->id,
        ])->assertSessionHasErrors('partner_id');

        // And a disabled account cannot sign in at all.
        $this->withSession(['crm_user_id' => $partner->id])
            ->get(route('crm.dashboard'))
            ->assertRedirect(route('crm.login'));
    }

    public function test_only_a_super_admin_moves_the_read_edit_switch(): void
    {
        $admin = $this->admin();
        $counsellor = $this->counsellor('Asha', '9876543211');
        $partner = $this->partner('Bright Futures', '9876543220', 'read');

        // The switch rides along with the rest of the account in one save now.
        $save = fn (CrmUser $m, array $over = []): array => array_merge([
            'name' => $m->name, 'phone' => $m->phone, 'email' => $m->email, 'role' => $m->role,
        ], $over);

        $this->withSession(['crm_user_id' => $counsellor->id])
            ->patch(route('crm.team.update', $partner), $save($partner, ['partner_access' => 'edit']))
            ->assertForbidden();

        $this->withSession(['crm_user_id' => $partner->id])
            ->patch(route('crm.team.update', $partner), $save($partner, ['partner_access' => 'edit']))
            ->assertForbidden();

        $this->assertSame('read', $partner->fresh()->partner_access);

        $this->withSession(['crm_user_id' => $admin->id])
            ->patch(route('crm.team.update', $partner), $save($partner, ['partner_access' => 'edit']))
            ->assertSessionHasNoErrors();

        $this->assertSame('edit', $partner->fresh()->partner_access);
        $this->assertTrue($partner->fresh()->canEditLeads());
        $this->assertDatabaseHas('crm_audit_logs', ['event' => 'partner_access_changed', 'subject_id' => $partner->id]);

        // Junk is refused.
        $this->withSession(['crm_user_id' => $admin->id])
            ->patch(route('crm.team.update', $partner), $save($partner, ['partner_access' => 'everything']))
            ->assertSessionHasErrors('partner_access');

        // And the switch is simply not stored on an account that is not a partner.
        $this->withSession(['crm_user_id' => $admin->id])
            ->patch(route('crm.team.update', $counsellor), $save($counsellor, ['partner_access' => 'edit']))
            ->assertSessionHasNoErrors();
        $this->assertNull($counsellor->fresh()->partner_access);
    }

    public function test_a_partner_cannot_be_moved_onto_the_in_house_ladder(): void
    {
        $admin = $this->admin();
        $partner = $this->partner('Bright Futures', '9876543220', 'edit');

        foreach (['counsellor', 'super_admin'] as $role) {
            $this->withSession(['crm_user_id' => $admin->id])
                ->patch(route('crm.team.update', $partner), [
                    'name' => $partner->name, 'phone' => $partner->phone,
                    'email' => $partner->email, 'role' => $role,
                ])->assertSessionHasErrors('team');
        }

        $this->assertSame('partner', $partner->fresh()->role);
        $this->assertSame('edit', $partner->fresh()->partner_access);
    }


    public function test_a_partner_cannot_reach_the_teams_own_tools(): void
    {
        $counsellor = $this->counsellor('Asha', '9876543211');
        $partner = $this->partner('Bright Futures', '9876543220', 'edit');
        $lead = $this->leadFor($counsellor, 'Partner Student', '9876500001', $partner);
        $lead->update(['follow_up_at' => now()->addDay()]);

        $session = ['crm_user_id' => $partner->id];

        // Creating and importing leads stays with the team.
        $this->withSession($session)->post(route('crm.leads.store'), [
            'name' => 'Sneaked In', 'phone' => '9876500009', 'priority' => 'medium', 'status' => 'new',
        ])->assertForbidden();
        $this->withSession($session)->post(route('crm.leads.import'), [
            'file' => UploadedFile::fake()->createWithContent('leads.csv', "name,phone\nSneaked In,9876500009\n"),
        ])->assertForbidden();

        // So does enrolling a student and completing a scheduled follow-up.
        $this->withSession($session)->post(route('crm.leads.convert', $lead), [
            'student_category' => 'paid', 'student_stage' => 'doc_pending',
            'enrollment_amount' => 50000, 'enrollment_date' => now()->format('Y-m-d'), 'payment_reference' => 'RCPT-1',
        ])->assertForbidden();
        $this->withSession($session)->post(route('crm.leads.follow-up.complete', $lead))->assertForbidden();

        // And so do the in-house tools behind the tabs a partner never sees.
        $this->withSession($session)->post(route('crm.mock-invites.store'), [
            'recipient_name' => 'Someone', 'question_count' => 10,
        ])->assertForbidden();
        $this->withSession($session)->get(route('crm.dashboard', ['view' => 'audit']))->assertForbidden();

        $this->assertDatabaseMissing('crm_leads', ['name' => 'Sneaked In']);
        $this->assertFalse((bool) $lead->fresh()->is_student);
        $this->assertNull($lead->fresh()->follow_up_completed_at);
    }

    public function test_a_partners_sidebar_holds_only_their_students(): void
    {
        $counsellor = $this->counsellor('Asha', '9876543211');
        $partner = $this->partner('Bright Futures', '9876543220', 'read');
        $this->leadFor($counsellor, 'Partner Student', '9876500001', $partner);

        $this->withSession(['crm_user_id' => $partner->id])->get(route('crm.dashboard'))
            ->assertOk()
            ->assertSee('Partner · Read only')
            ->assertDontSee('nav-followups')
            ->assertDontSee('nav-enrollments')
            ->assertDontSee('nav-shortlisting')
            ->assertDontSee('nav-mock-invites')
            ->assertDontSee('Add lead')
            ->assertDontSee('Import Excel / CSV')
            ->assertDontSee('Team management');

        // The in-house views fall back to the dashboard rather than erroring.
        foreach (['followups', 'enrollments', 'shortlisting', 'mock-invites', 'subscriptions'] as $view) {
            $this->withSession(['crm_user_id' => $partner->id])
                ->get(route('crm.dashboard', ['view' => $view]))
                ->assertOk()
                ->assertDontSee('nav-enrollments');
        }
    }

    /**
     * What the page renders is CrmTeamManagementTest's job; the rule that
     * belongs with the roles is who may open it at all.
     */
    public function test_only_a_super_admin_can_open_team_management(): void
    {
        $admin = $this->admin();
        $counsellor = $this->counsellor('Asha', '9876543211');
        $partner = $this->partner('Bright Futures', '9876543220', 'read');

        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'team']))
            ->assertOk()->assertSee('Team management');

        // Refused outright rather than silently redirected, the way the audit
        // log is: asking for it is deliberate, so landing elsewhere reads as a
        // broken link.
        $this->withSession(['crm_user_id' => $counsellor->id])
            ->get(route('crm.dashboard', ['view' => 'team']))->assertForbidden();
        $this->withSession(['crm_user_id' => $partner->id])
            ->get(route('crm.dashboard', ['view' => 'team']))->assertForbidden();
    }

    public function test_the_dashboard_map_can_be_expanded(): void
    {
        $admin = $this->admin();

        $this->withSession(['crm_user_id' => $admin->id])->get(route('crm.dashboard'))
            ->assertOk()
            ->assertSee('data-map-expand', false)
            ->assertSee('aria-label="Expand the map to full screen"', false)
            ->assertSee('data-lead-world-map', false);
    }

    public function test_deleting_a_partner_leaves_their_students_in_the_crm(): void
    {
        $admin = $this->admin();
        $counsellor = $this->counsellor('Asha', '9876543211');
        $partner = $this->partner('Bright Futures', '9876543220', 'edit');
        $lead = $this->leadFor($counsellor, 'Partner Student', '9876500001', $partner);

        $this->withSession(['crm_user_id' => $admin->id])
            ->delete(route('crm.team.destroy', $partner))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('crm_users', ['id' => $partner->id]);
        $this->assertDatabaseHas('crm_leads', ['id' => $lead->id, 'partner_id' => null]);
        $this->assertSame($counsellor->id, $lead->fresh()->assigned_to);
    }

    public function test_the_partner_filter_and_export_column_are_there_for_the_team(): void
    {
        $admin = $this->admin();
        $counsellor = $this->counsellor('Asha', '9876543211');
        $partner = $this->partner('Bright Futures', '9876543220', 'read');
        $mine = $this->leadFor($counsellor, 'Partner Student', '9876500001', $partner);
        $this->leadFor($counsellor, 'Direct Student', '9876500002');

        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'leads', 'partner_id' => [$partner->id]]))
            ->assertOk()->assertSee('Partner Student')->assertDontSee('Direct Student');

        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'leads', 'partner_id' => 'none']))
            ->assertOk()->assertSee('Direct Student')->assertDontSee('Partner Student');

        $csv = $this->withSession(['crm_user_id' => $admin->id])->get(route('crm.leads.export'))->streamedContent();
        $this->assertStringContainsString('Partner', $csv);
        $this->assertStringContainsString('Bright Futures', $csv);

        // A partner's own export carries their students and nobody else's.
        $partnerCsv = $this->withSession(['crm_user_id' => $partner->id])->get(route('crm.leads.export'))->streamedContent();
        $this->assertStringContainsString($mine->lead_number, $partnerCsv);
        $this->assertStringNotContainsString('Direct Student', $partnerCsv);
    }

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

    private function partner(string $name, string $phone, string $access): CrmUser
    {
        return CrmUser::query()->create([
            'name' => $name, 'phone' => $phone, 'email' => str_replace(' ', '-', strtolower($name)).'@mailbox.test',
            'role' => 'partner', 'partner_access' => $access, 'is_active' => true,
        ]);
    }

    private function leadFor(CrmUser $owner, string $name, string $phone, ?CrmUser $partner = null): CrmLead
    {
        return CrmLead::query()->create([
            'lead_number' => 'OD-'.substr($phone, -5), 'name' => $name, 'phone' => $phone,
            'priority' => 'medium', 'status' => 'new', 'assigned_to' => $owner->id, 'created_by' => $owner->id,
            'partner_id' => $partner?->id,
        ]);
    }
}
