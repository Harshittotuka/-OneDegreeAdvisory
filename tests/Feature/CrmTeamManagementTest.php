<?php

namespace Tests\Feature;

use App\Models\CrmUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Team management: a two-pane master–detail view.
 *
 * A flat list of accounts on the left, everything about the selected one on the
 * right, and one Save covering name, access level and partner access together.
 * Selection lives in the URL (?view=team&member=<id>), so these tests drive the
 * page the way a browser does — by following links and posting forms.
 */
class CrmTeamManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_the_page_opens_on_the_card_grid_with_every_account_listed(): void
    {
        $admin = $this->admin();
        $counsellor = $this->member('Asha Menon', '9876543211', 'counsellor');
        $partner = $this->member('Bright Futures', '9876543220', 'partner', 'edit');

        $response = $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'team']))
            ->assertOk();

        // Two panes, one row per account, no role groups to scan past.
        $response
            ->assertSee('class="team-panes"', false)
            ->assertSee('class="team-list-pane"', false)
            ->assertSee('class="team-detail-pane"', false)
            ->assertSee('Main Admin')
            ->assertSee('Asha Menon')
            ->assertSee('Bright Futures')
            ->assertDontSee('data-team-group', false);

        // Every row links to its own detail pane.
        foreach ([$admin, $counsellor, $partner] as $member) {
            $response->assertSee('member='.$member->id, false);
        }

        // Nothing selected, so the main frame browses: one card per account,
        // carrying the same filter hooks as the sidebar rows so a chip narrows
        // both at once, plus an Add tile inside the grid.
        $response
            ->assertSee('class="team-cards"', false)
            ->assertSee('class="team-cards"', false)
            ->assertSee('data-team-card', false)
            ->assertSee('data-team-visible-count', false)
            ->assertSee('team-card is-add', false)
            ->assertSee('Add a team member');

        // One card per account, each carrying the role its chip filters on.
        $this->assertSame(3, substr_count($response->getContent(), 'data-team-card'));
        $response
            ->assertSee('team-card role-super-admin', false)
            ->assertSee('team-card role-counsellor', false)
            ->assertSee('team-card role-partner', false);

        // Search and the role chips.
        $response
            ->assertSee('data-team-toolbar', false)
            ->assertSee('data-team-search-input', false)
            ->assertSee('data-team-filter="all"', false)
            ->assertSee('data-team-filter="super-admin"', false)
            ->assertSee('data-team-filter="counsellor"', false)
            ->assertSee('data-team-filter="partner"', false)
            ->assertSee('data-team-no-results', false)
            ->assertSee('data-team-search="asha menon asha-menon@mailbox.test 9876543211"', false);
    }

    public function test_the_add_form_is_its_own_state_reached_from_the_grid(): void
    {
        $admin = $this->admin();

        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'team', 'add' => 1]))
            ->assertOk()
            ->assertSee('Add a team member')
            ->assertSee('Create account')
            ->assertSee('data-team-role-select', false)
            ->assertSee('data-partner-access-field', false)
            ->assertSee('All accounts')
            // The grid steps aside while the form is open.
            ->assertDontSee('class="team-cards"', false);
    }

    /**
     * The bug this covers: the chips used to be client-side toggles, so while
     * the main frame was on the Add form or an open account they could only
     * narrow the sidebar — the frame stayed put. They are links now, and each
     * one clears ?member and ?add so the grid always comes back.
     */
    public function test_a_role_chip_returns_the_main_frame_to_the_grid(): void
    {
        $admin = $this->admin();
        $this->member('Asha Menon', '9876543211', 'counsellor');
        $partner = $this->member('Bright Futures', '9876543220', 'partner', 'read');
        $session = ['crm_user_id' => $admin->id];

        // From the Add form, every chip link drops ?add.
        $addPage = $this->withSession($session)
            ->get(route('crm.dashboard', ['view' => 'team', 'add' => 1]))
            ->assertOk();
        foreach (['all', 'super-admin', 'counsellor', 'partner'] as $slug) {
            $this->assertStringContainsString('data-team-filter="'.$slug.'"', $addPage->getContent());
        }
        $this->assertStringNotContainsString('add=1&amp;role=', $addPage->getContent());

        // From an open account, they drop ?member.
        $memberPage = $this->withSession($session)
            ->get(route('crm.dashboard', ['view' => 'team', 'member' => $partner->id]))
            ->assertOk();
        $this->assertStringNotContainsString('member='.$partner->id.'&amp;role=', $memberPage->getContent());

        // And following one — the chip href carries no ?add — lands on the grid.
        $this->withSession($session)
            ->get(route('crm.dashboard', ['view' => 'team', 'role' => 'partner']))
            ->assertOk()
            ->assertSee('class="team-cards"', false)
            ->assertDontSee('Create account')
            ->assertDontSee('Save changes');
    }

    public function test_a_role_chip_narrows_the_sidebar_and_the_cards_together(): void
    {
        $admin = $this->admin();
        $counsellor = $this->member('Asha Menon', '9876543211', 'counsellor');
        $partner = $this->member('Bright Futures', '9876543220', 'partner', 'read');
        $session = ['crm_user_id' => $admin->id];

        $partners = $this->withSession($session)
            ->get(route('crm.dashboard', ['view' => 'team', 'role' => 'partner']))
            ->assertOk();

        $html = $partners->getContent();
        // One card and one row survive; the others are gone from both surfaces.
        $this->assertSame(1, substr_count($html, 'data-team-card'));
        $this->assertSame(1, substr_count($html, 'data-team-member'));
        $this->assertStringContainsString('team-card role-partner', $html);
        $this->assertStringNotContainsString('team-card role-counsellor', $html);
        $this->assertStringNotContainsString('team-card role-super-admin', $html);
        // The heading names the group, and the chip reads as chosen.
        $partners->assertSee('<h3>Partners</h3>', false)->assertSee('aria-current="true"', false);
        // Counting stays on the full team, so the chips keep their totals.
        $partners->assertSee('Counsellors <span class="chip-count">1</span>', false);

        $adminHtml = $this->withSession($session)
            ->get(route('crm.dashboard', ['view' => 'team', 'role' => 'super-admin']))
            ->assertOk()->getContent();
        $this->assertStringContainsString('team-card role-super-admin', $adminHtml);
        $this->assertStringNotContainsString('team-card role-partner', $adminHtml);

        // A junk value is ignored rather than emptying the page.
        $allHtml = $this->withSession($session)
            ->get(route('crm.dashboard', ['view' => 'team', 'role' => 'nonsense']))
            ->assertOk()->getContent();
        $this->assertSame(3, substr_count($allHtml, 'data-team-card'));

        // Opening someone keeps the filter, so the sidebar does not jump back.
        $keptHtml = $this->withSession($session)
            ->get(route('crm.dashboard', ['view' => 'team', 'role' => 'partner', 'member' => $partner->id]))
            ->assertOk()->getContent();
        $this->assertSame(1, substr_count($keptHtml, 'data-team-member'));
        $this->assertStringNotContainsString($counsellor->email, $keptHtml);
    }

    public function test_selecting_an_account_opens_one_form_holding_everything(): void
    {
        $admin = $this->admin();
        $counsellor = $this->member('Asha Menon', '9876543211', 'counsellor');

        $response = $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'team', 'member' => $counsellor->id]))
            ->assertOk();

        $response
            ->assertSee('class="team-detail"', false)
            ->assertSee('is-selected', false)
            // The detail replaces the grid, and says how to get back to it.
            ->assertDontSee('class="team-cards"', false)
            ->assertSee('All accounts')
            // Name, mobile, email and access level in a single form posting once.
            ->assertSee('action="'.route('crm.team.update', $counsellor).'"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="role"', false)
            ->assertSee('Save changes')
            // Destructive actions are separate, and explicit rather than icons.
            ->assertSee(route('crm.team.toggle', $counsellor), false)
            ->assertSee('Disable sign-in')
            ->assertSee('Delete account')
            // An in-house account is not offered the Partner role.
            ->assertDontSee('data-partner-access-field', false)
            ->assertDontSee('>Partner<', false);
    }

    public function test_a_partner_detail_pane_carries_the_read_edit_switch(): void
    {
        $admin = $this->admin();
        $partner = $this->member('Bright Futures', '9876543220', 'partner', 'read');

        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'team', 'member' => $partner->id]))
            ->assertOk()
            ->assertSee('data-partner-access-field', false)
            ->assertSee('name="partner_access"', false)
            ->assertSee('0 students')
            // And is told it cannot cross to an in-house role.
            ->assertSee('cannot be moved to an in-house role');
    }

    public function test_your_own_pane_hides_the_dangerous_controls(): void
    {
        $admin = $this->admin();
        $peer = $this->member('Second Admin', '9876543219', 'super_admin');

        $own = $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'team', 'member' => $admin->id]))
            ->assertOk();

        $own->assertSee('You cannot change your own access level');
        $this->assertStringNotContainsString(route('crm.team.toggle', $admin), $own->getContent());
        $this->assertStringNotContainsString('value="DELETE"', $own->getContent());

        // A colleague's pane has both.
        $other = $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'team', 'member' => $peer->id]))
            ->assertOk();
        $this->assertStringContainsString(route('crm.team.toggle', $peer), $other->getContent());
        $this->assertStringContainsString('value="DELETE"', $other->getContent());
    }

    public function test_one_save_updates_details_role_and_access_together(): void
    {
        $admin = $this->admin();
        $counsellor = $this->member('Asha Menon', '9876543211', 'counsellor');
        $session = ['crm_user_id' => $admin->id];

        // Rename and promote in the same post.
        $this->withSession($session)->patch(route('crm.team.update', $counsellor), [
            'name' => 'Asha M Menon', 'phone' => '9876543212',
            'email' => 'asha.menon@mailbox.test', 'role' => 'super_admin',
        ])->assertSessionHasNoErrors();

        $counsellor->refresh();
        $this->assertSame('Asha M Menon', $counsellor->name);
        $this->assertSame('9876543212', $counsellor->phone);
        $this->assertSame('asha.menon@mailbox.test', $counsellor->email);
        $this->assertTrue($counsellor->isSuperAdmin());

        // Two facts, two audit rows — a rename and a promotion stay findable apart.
        $this->assertDatabaseHas('crm_audit_logs', ['event' => 'team_member_updated', 'subject_id' => $counsellor->id]);
        $this->assertDatabaseHas('crm_audit_logs', ['event' => 'team_member_role_changed', 'subject_id' => $counsellor->id]);

        // Demote back.
        $this->withSession($session)->patch(route('crm.team.update', $counsellor), [
            'name' => $counsellor->name, 'phone' => $counsellor->phone,
            'email' => $counsellor->email, 'role' => 'counsellor',
        ])->assertSessionHasNoErrors();
        $this->assertSame('counsellor', $counsellor->fresh()->role);

        // Saving an unchanged form is a no-op, not a spurious audit row.
        $before = \App\Models\CrmAuditLog::query()->count();
        $this->withSession($session)->patch(route('crm.team.update', $counsellor), [
            'name' => $counsellor->fresh()->name, 'phone' => $counsellor->fresh()->phone,
            'email' => $counsellor->fresh()->email, 'role' => 'counsellor',
        ])->assertSessionHasNoErrors();
        $this->assertSame($before, \App\Models\CrmAuditLog::query()->count());
    }

    public function test_the_same_save_moves_a_partners_read_edit_switch(): void
    {
        $admin = $this->admin();
        $partner = $this->member('Bright Futures', '9876543220', 'partner', 'read');

        $this->withSession(['crm_user_id' => $admin->id])->patch(route('crm.team.update', $partner), [
            'name' => 'Bright Futures Overseas', 'phone' => $partner->phone,
            'email' => $partner->email, 'role' => 'partner', 'partner_access' => 'edit',
        ])->assertSessionHasNoErrors();

        $partner->refresh();
        $this->assertSame('Bright Futures Overseas', $partner->name);
        $this->assertSame('edit', $partner->partner_access);
        $this->assertTrue($partner->canEditLeads());
        $this->assertDatabaseHas('crm_audit_logs', ['event' => 'partner_access_changed', 'subject_id' => $partner->id]);
    }

    public function test_the_save_keeps_every_guard_the_old_buttons_held(): void
    {
        $admin = $this->admin();
        $counsellor = $this->member('Asha Menon', '9876543211', 'counsellor');
        $partner = $this->member('Bright Futures', '9876543220', 'partner', 'read');
        $session = ['crm_user_id' => $admin->id];

        $base = fn (CrmUser $m, array $over = []): array => array_merge([
            'name' => $m->name, 'phone' => $m->phone, 'email' => $m->email, 'role' => $m->role,
        ], $over);

        // You cannot change your own access level.
        $this->withSession($session)->patch(route('crm.team.update', $admin), $base($admin, ['role' => 'counsellor']))
            ->assertSessionHasErrors('team');
        $this->assertTrue($admin->fresh()->isSuperAdmin());

        // The last active super admin has to stay one.
        $this->withSession(['crm_user_id' => $counsellor->id]);
        $second = $this->member('Second Admin', '9876543219', 'super_admin');
        $this->withSession(['crm_user_id' => $second->id])
            ->patch(route('crm.team.update', $admin), $base($admin, ['role' => 'counsellor']))
            ->assertSessionHasNoErrors();
        $this->assertSame('counsellor', $admin->fresh()->role);
        $this->withSession(['crm_user_id' => $admin->fresh()->id])->get(route('crm.dashboard', ['view' => 'team']))
            ->assertForbidden();
        $this->withSession(['crm_user_id' => $second->id])
            ->patch(route('crm.team.update', $second), $base($second, ['role' => 'counsellor']))
            ->assertSessionHasErrors('team');

        // Partner never crosses to an in-house role, or the other way.
        $this->withSession(['crm_user_id' => $second->id])
            ->patch(route('crm.team.update', $partner), $base($partner, ['role' => 'counsellor', 'partner_access' => 'read']))
            ->assertSessionHasErrors('team');
        $this->assertSame('partner', $partner->fresh()->role);
        $this->withSession(['crm_user_id' => $second->id])
            ->patch(route('crm.team.update', $counsellor), $base($counsellor, ['role' => 'partner', 'partner_access' => 'read']))
            ->assertSessionHasErrors('team');
        $this->assertSame('counsellor', $counsellor->fresh()->role);

        // A partner save still has to say which access level.
        $this->withSession(['crm_user_id' => $second->id])
            ->patch(route('crm.team.update', $partner), $base($partner))
            ->assertSessionHasErrors('partner_access');
    }

    public function test_disable_restore_and_delete_still_work(): void
    {
        $admin = $this->admin();
        $counsellor = $this->member('Asha Menon', '9876543211', 'counsellor');
        $session = ['crm_user_id' => $admin->id];

        $this->withSession($session)->patch(route('crm.team.toggle', $counsellor))->assertSessionHasNoErrors();
        $this->assertFalse($counsellor->fresh()->is_active);

        // A disabled account stays on the list, marked rather than dropped.
        $this->withSession($session)->get(route('crm.dashboard', ['view' => 'team']))
            ->assertOk()
            ->assertSee('data-team-status="disabled"', false)
            ->assertSee('Asha Menon');

        $this->withSession($session)->patch(route('crm.team.toggle', $counsellor))->assertSessionHasNoErrors();
        $this->assertTrue($counsellor->fresh()->is_active);

        $this->withSession($session)->delete(route('crm.team.destroy', $counsellor))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('crm_users', ['id' => $counsellor->id]);
    }

    public function test_creating_an_account_still_works_from_the_add_state(): void
    {
        $admin = $this->admin();
        $session = ['crm_user_id' => $admin->id];

        foreach ([
            ['Asha Menon', '9876543211', 'counsellor', null],
            ['Second Admin', '9876543219', 'super_admin', null],
            ['Bright Futures', '9876543220', 'partner', 'read'],
        ] as [$name, $phone, $role, $access]) {
            $this->withSession($session)->post(route('crm.team.store'), array_filter([
                'name' => $name, 'phone' => $phone,
                'email' => str_replace(' ', '-', strtolower($name)).'@mailbox.test',
                'role' => $role, 'partner_access' => $access,
            ]))->assertSessionHasNoErrors();
        }

        $this->assertSame(4, CrmUser::query()->count());
        $this->assertSame('read', CrmUser::query()->where('phone', '9876543220')->firstOrFail()->partner_access);
    }

    public function test_an_unknown_member_id_simply_shows_the_grid(): void
    {
        $admin = $this->admin();

        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'team', 'member' => 999999]))
            ->assertOk()
            ->assertSee('class="team-cards"', false);
    }

    public function test_blocked_submissions_keeps_its_note_inside_the_panel(): void
    {
        $admin = $this->admin();

        $this->withSession(['crm_user_id' => $admin->id])->get(route('crm.dashboard', ['view' => 'spam']))
            ->assertOk()
            ->assertSee('class="workspace-note"', false)
            ->assertDontSee('style="margin:-8px 0 16px', false)
            ->assertSee('carries a hidden field real visitors never see')
            ->assertSee('No blocked submissions');
    }

    private function admin(): CrmUser
    {
        return CrmUser::query()->create([
            'name' => 'Main Admin', 'phone' => '9876543210', 'email' => 'admin@mailbox.test',
            'role' => 'super_admin', 'is_active' => true,
        ]);
    }

    private function member(string $name, string $phone, string $role, ?string $access = null): CrmUser
    {
        return CrmUser::query()->create([
            'name' => $name, 'phone' => $phone,
            'email' => str_replace(' ', '-', strtolower($name)).'@mailbox.test',
            'role' => $role, 'partner_access' => $access, 'is_active' => true,
        ]);
    }
}
