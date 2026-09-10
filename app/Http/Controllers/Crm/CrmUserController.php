<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmUser;
use App\Services\CrmAuditLogger;
use App\Support\CrmOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CrmUserController extends Controller
{
    public function store(Request $request, CrmAuditLogger $auditLogger): RedirectResponse
    {
        /** @var CrmUser $admin */
        $admin = $request->attributes->get('crm_user');
        abort_unless($admin->isSuperAdmin(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:190', 'real_email', 'unique:crm_users,email'],
            'role' => ['required', Rule::in(array_keys(CrmOptions::ROLES))],
            // Only a partner account carries this, and creating one has to state
            // it: the whole point of the role is that the super admin decides
            // whether the partner can just watch their students or also update them.
            'partner_access' => ['exclude_unless:role,partner', 'required', Rule::in(array_keys(CrmOptions::PARTNER_ACCESS))],
        ], [
            'partner_access.required' => 'Choose whether this partner gets read-only or read and edit access.',
        ]);
        $phone = $this->normalisePhone($data['phone']);
        if (CrmUser::query()->where('phone', $phone)->exists()) {
            return back()->withErrors(['phone' => 'This mobile number already has a CRM account.'])->withInput();
        }

        $member = CrmUser::query()->create([
            'name' => trim($data['name']), 'phone' => $phone, 'email' => strtolower(trim($data['email'])), 'role' => $data['role'],
            'partner_access' => $data['partner_access'] ?? null,
            'is_active' => true, 'created_by' => $admin->id,
        ]);

        $auditLogger->record($request, $admin, 'team_member_created', 'Created CRM team member '.$member->name.'.', [
            'subject_type' => 'team_member',
            'subject_id' => $member->id,
            'subject_label' => $member->name,
            'changes' => ['after' => $member->only(['name', 'phone', 'email', 'role', 'partner_access', 'is_active'])],
        ]);

        $access = $member->isPartner() ? ' with '.strtolower($member->partnerAccessLabel()).' access to the students naming them' : '';

        return back()->with('status', $member->roleLabel().' created'.$access.'. They can now sign in with their phone or email and receive the OTP on their registered email.');
    }

    /**
     * One save for the whole account.
     *
     * The page used to spread an account across four endpoints — details, role,
     * partner access, and the disable toggle — each with its own button. Name,
     * role and access now travel together in a single form, so this method
     * carries every guard the separate role and access endpoints used to hold:
     * you cannot change your own role, a config-defined super admin cannot be
     * demoted or renumbered, the last active super admin has to stay one, and an
     * account never crosses between the in-house roles and Partner.
     *
     * Disabling and deleting stay separate on purpose. They are destructive and
     * should never ride along with a rename.
     */
    public function update(Request $request, CrmUser $member, CrmAuditLogger $auditLogger): RedirectResponse
    {
        /** @var CrmUser $admin */
        $admin = $request->attributes->get('crm_user');
        abort_unless($admin->isSuperAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:190', 'real_email', Rule::unique('crm_users', 'email')->ignore($member->id)],
            'role' => ['required', Rule::in(array_keys(CrmOptions::ROLES))],
            'partner_access' => ['exclude_unless:role,partner', 'required', Rule::in(array_keys(CrmOptions::PARTNER_ACCESS))],
        ], [
            'partner_access.required' => 'Choose whether this partner gets read-only or read and edit access.',
        ]);

        $phone = $this->normalisePhone($data['phone']);
        if (strlen($phone) !== 10) {
            return back()->withErrors(['team' => 'Enter a valid 10-digit mobile number for '.$member->name.'.']);
        }
        if ($phone !== $member->phone) {
            if (in_array($member->phone, $this->configSuperAdminPhones(), true)) {
                return back()->withErrors(['team' => $member->name.'\'s mobile number is fixed in the server configuration and must be changed there.']);
            }
            if (CrmUser::query()->where('phone', $phone)->whereKeyNot($member->id)->exists()) {
                return back()->withErrors(['team' => 'This mobile number already has a CRM account.']);
            }
        }

        if ($error = $this->roleChangeError($admin, $member, $data['role'])) {
            return back()->withErrors(['team' => $error]);
        }

        $before = $member->only(['name', 'phone', 'email', 'role', 'partner_access']);
        $member->update([
            'name' => trim($data['name']),
            'phone' => $phone,
            'email' => strtolower(trim($data['email'])),
            'role' => $data['role'],
            'partner_access' => $data['role'] === 'partner' ? $data['partner_access'] : null,
        ]);
        $after = $member->only(array_keys($before));

        if ($after === $before) {
            return back()->with('status', 'No changes to save for '.$member->name.'.');
        }

        // One row per fact, so the audit log keeps its own vocabulary: a rename
        // and a promotion are two different things to go looking for later.
        $detailsChanged = array_intersect_key($before, array_flip(['name', 'phone', 'email'])) !== array_intersect_key($after, array_flip(['name', 'phone', 'email']));
        if ($detailsChanged) {
            $auditLogger->record($request, $admin, 'team_member_updated', 'Updated CRM team member '.$member->name.'.', [
                'subject_type' => 'team_member',
                'subject_id' => $member->id,
                'subject_label' => $member->name,
                'changes' => ['before' => $before, 'after' => $after],
            ]);
        }
        if ($before['role'] !== $after['role']) {
            $auditLogger->record($request, $admin, 'team_member_role_changed', 'Changed '.$member->name.' from '.strtolower(CrmOptions::ROLES[$before['role']] ?? $before['role']).' to '.strtolower($member->roleLabel()).'.', [
                'subject_type' => 'team_member',
                'subject_id' => $member->id,
                'subject_label' => $member->name,
                'changes' => ['before' => ['role' => $before['role']], 'after' => ['role' => $after['role']]],
            ]);
        }
        if ($before['partner_access'] !== $after['partner_access'] && $member->isPartner()) {
            $auditLogger->record($request, $admin, 'partner_access_changed', 'Set partner access for '.$member->name.' to '.strtolower($member->partnerAccessLabel()).'.', [
                'subject_type' => 'team_member',
                'subject_id' => $member->id,
                'subject_label' => $member->name,
                'changes' => ['before' => ['partner_access' => $before['partner_access']], 'after' => ['partner_access' => $after['partner_access']]],
            ]);
        }

        return back()->with('status', $member->name.'\'s account was updated.');
    }

    /**
     * Why a requested role is refused, or null when it is allowed.
     *
     * These are the rules the old promote/demote button held. They matter more
     * now, not less: a dropdown makes every role look one click away, so the
     * ones that are not have to say so rather than silently apply.
     */
    private function roleChangeError(CrmUser $admin, CrmUser $member, string $role): ?string
    {
        if ($role === $member->role) {
            return null;
        }

        if ($admin->id === $member->id) {
            return 'You cannot change your own access level. Ask another super admin to do it.';
        }

        // Partner is not a rung on the in-house ladder. Crossing either way would
        // leave the leads that name this partner pointing at an account the
        // Partner field can no longer offer, so the account is re-created instead.
        if ($member->isPartner() || $role === 'partner') {
            return $member->name.' cannot move between Partner and the in-house roles, because the students naming them as partner would be left pointing at an account that is no longer one. Delete the account and create it again in the role you want.';
        }

        if ($member->isSuperAdmin()) {
            if (in_array($member->phone, $this->configSuperAdminPhones(), true)) {
                return $member->name.' is a super admin defined in the server configuration and cannot be demoted from here.';
            }
            if ($member->is_active && CrmUser::query()->where('role', 'super_admin')->where('is_active', true)->count() <= 1) {
                return 'At least one super admin must remain active.';
            }
        }

        return null;
    }

    public function toggle(Request $request, CrmUser $member, CrmAuditLogger $auditLogger): RedirectResponse
    {
        /** @var CrmUser $admin */
        $admin = $request->attributes->get('crm_user');
        abort_unless($admin->isSuperAdmin() && $admin->id !== $member->id, 403);
        if ($member->isSuperAdmin() && $member->is_active && CrmUser::query()->where('role', 'super_admin')->where('is_active', true)->count() <= 1) {
            return back()->withErrors(['team' => 'At least one super admin must remain active.']);
        }
        $wasActive = $member->is_active;
        $member->update(['is_active' => ! $member->is_active]);

        $auditLogger->record($request, $admin, 'team_member_access_changed', ($member->is_active ? 'Restored' : 'Disabled').' CRM access for '.$member->name.'.', [
            'subject_type' => 'team_member',
            'subject_id' => $member->id,
            'subject_label' => $member->name,
            'changes' => ['before' => ['is_active' => $wasActive], 'after' => ['is_active' => $member->is_active]],
        ]);

        return back()->with('status', $member->roleLabel().' access '.($member->is_active ? 'restored.' : 'disabled.'));
    }

    public function destroy(Request $request, CrmUser $member, CrmAuditLogger $auditLogger): RedirectResponse
    {
        /** @var CrmUser $admin */
        $admin = $request->attributes->get('crm_user');
        abort_unless($admin->isSuperAdmin() && $admin->id !== $member->id, 403);

        if ($member->isSuperAdmin() && CrmUser::query()->where('role', 'super_admin')->count() <= 1) {
            return back()->withErrors(['team' => 'At least one super admin must remain in the CRM.']);
        }

        $roleLabel = $member->roleLabel();
        $name = $member->name;
        $wasPartner = $member->isPartner();
        // Leads owned or created by this member are automatically unassigned
        // (nullOnDelete), and so is the Partner field on any lead naming them.
        $reassigned = $wasPartner ? $member->partnerLeads()->count() : $member->leads()->count();

        $auditLogger->record($request, $admin, 'team_member_deleted', 'Deleted CRM team member '.$name.'.', [
            'subject_type' => 'team_member',
            'subject_id' => $member->id,
            'subject_label' => $name,
            'changes' => ['before' => $member->only(['name', 'phone', 'email', 'role', 'partner_access', 'is_active']), 'leads_unassigned' => $reassigned],
        ]);

        $member->delete();

        $note = match (true) {
            $reassigned === 0 => '',
            $wasPartner => ' '.$reassigned.' lead(s) no longer name a partner.',
            default => ' '.$reassigned.' lead(s) they owned are now unassigned.',
        };

        return back()->with('status', $roleLabel.' '.$name.' was removed from the CRM.'.$note);
    }

    private function normalisePhone(string $phone): string
    {
        return substr((string) preg_replace('/\D+/', '', $phone), -10);
    }

    /**
     * Phones from config('crm.super_admin' / 'crm.additional_super_admins').
     * CrmSuperAdminSync re-promotes (and would re-create) these on every
     * login request, so demoting them or editing their phone here is futile.
     *
     * @return list<string>
     */
    private function configSuperAdminPhones(): array
    {
        return collect([(array) config('crm.super_admin')])
            ->merge((array) config('crm.additional_super_admins', []))
            ->map(fn (array $admin): string => $this->normalisePhone((string) ($admin['phone'] ?? '')))
            ->filter(fn (string $phone): bool => strlen($phone) === 10)
            ->values()
            ->all();
    }
}
