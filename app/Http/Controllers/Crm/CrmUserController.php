<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmPartnerCode;
use App\Models\CrmUser;
use App\Services\CrmAuditLogger;
use App\Support\CrmOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRM accounts — and, for a partner, the referral company behind the account.
 *
 * A partner used to be entered twice: once here as a login, once on the Partner
 * codes tab as a company with a tracking code, with nothing joining the two. One
 * form now does both. The company fields ride along with the partner role and are
 * excluded for every other role, so creating a counsellor is unchanged.
 */
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
            // Company, code and website. Required rather than optional: a partner
            // without a link has nothing to hand out and would not appear on the
            // Partner codes tab, which is the screen this form is reached from.
            ...$this->companyRules($request),
        ], [
            'partner_access.required' => 'Choose whether this partner gets read-only or read and edit access.',
            ...CrmPartnerCode::companyMessages(),
        ]);
        $phone = CrmUser::normalisePhone($data['phone']);
        if (CrmUser::query()->where('phone', $phone)->exists()) {
            return back()->withErrors(['phone' => 'This mobile number already has a CRM account.'])->withInput();
        }

        $member = CrmUser::query()->create([
            'name' => trim($data['name']), 'phone' => $phone, 'email' => strtolower(trim($data['email'])), 'role' => $data['role'],
            'partner_access' => $data['partner_access'] ?? null,
            'is_active' => true, 'created_by' => $admin->id,
        ]);

        $auditLogger->record($request, $admin, 'team_member_created', 'Created CRM team member '.$member->name.'.',
            $member->auditSubject(['after' => $member->only([...CrmUser::TRACKED_FIELDS, 'is_active'])]));

        if (! $member->isPartner()) {
            return back()->with('status', $member->roleLabel().' created. They can now sign in with their phone or email and receive the OTP on their registered email.');
        }

        $code = $this->createPartnerCode($request, $admin, $member, $data, $auditLogger);

        // Back to the Partner codes tab rather than the form: the link is the
        // point of a partner, and that is the screen holding it.
        return redirect()->to(route('crm.dashboard', ['view' => 'partner-codes']))
            ->with('status', $member->name.' was created with '.strtolower($member->partnerAccessLabel()).' access, and can share the link carrying code '.$code->code.'. They sign in with their phone or email and receive the OTP on their registered email.')
            ->with('new_partner_code', $code->id);
    }

    /**
     * The company rules, tied to the partner role.
     *
     * exclude_unless keeps them off a counsellor or super admin entirely — the
     * fields are hidden for those roles, and a hidden field must not be able to
     * fail a save. $required is relaxed on update for a partner created before
     * accounts and codes were joined: they have no company yet, and a save that
     * only moves their read/edit switch should not be blocked by that.
     *
     * @return array<string, array<int, mixed>>
     */
    private function companyRules(Request $request, ?CrmPartnerCode $existing = null, bool $required = true): array
    {
        // Normalised before the rules see it — CrmPartnerCode::normaliseCode says why.
        $request->merge(['code' => CrmPartnerCode::normaliseCode($request->input('code')) ?: null]);

        $rules = [];
        foreach (CrmPartnerCode::companyRules($existing, $required) as $field => $fieldRules) {
            $rules[$field] = ['exclude_unless:role,partner', ...$fieldRules];
        }

        return $rules;
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

        // The company this partner represents is edited here too, so the account
        // and its link are one record to the person maintaining them. Its own
        // code is ignored by the uniqueness rule, or re-saving a partner
        // unchanged would collide with itself.
        $existingCode = $member->isPartner() ? $member->partnerCode()->first() : null;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:190', 'real_email', Rule::unique('crm_users', 'email')->ignore($member->id)],
            'role' => ['required', Rule::in(array_keys(CrmOptions::ROLES))],
            'partner_access' => ['exclude_unless:role,partner', 'required', Rule::in(array_keys(CrmOptions::PARTNER_ACCESS))],
            ...$this->companyRules($request, $existingCode, (bool) $existingCode),
        ], [
            'partner_access.required' => 'Choose whether this partner gets read-only or read and edit access.',
            ...CrmPartnerCode::companyMessages(),
        ]);

        $phone = CrmUser::normalisePhone($data['phone']);
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

        $before = $member->only(CrmUser::TRACKED_FIELDS);
        $member->update([
            'name' => trim($data['name']),
            'phone' => $phone,
            'email' => strtolower(trim($data['email'])),
            'role' => $data['role'],
            'partner_access' => $data['role'] === 'partner' ? $data['partner_access'] : null,
        ]);
        $after = $member->only(array_keys($before));

        // Runs before the "nothing changed" exit below, because a partner can be
        // saved with their name untouched and only their company or code edited.
        $codeNote = $member->isPartner()
            ? $this->syncPartnerCode($request, $admin, $member, $data, $existingCode, $auditLogger)
            : null;

        if ($after === $before && $codeNote === null) {
            return back()->with('status', 'No changes to save for '.$member->name.'.');
        }

        // One row per fact, so the audit log keeps its own vocabulary: a rename
        // and a promotion are two different things to go looking for later.
        $detailsChanged = array_intersect_key($before, array_flip(['name', 'phone', 'email'])) !== array_intersect_key($after, array_flip(['name', 'phone', 'email']));
        if ($detailsChanged) {
            $auditLogger->record($request, $admin, 'team_member_updated', 'Updated CRM team member '.$member->name.'.',
                $member->auditSubject(['before' => $before, 'after' => $after]));
        }
        if ($before['role'] !== $after['role']) {
            $auditLogger->record($request, $admin, 'team_member_role_changed', 'Changed '.$member->name.' from '.strtolower(CrmOptions::ROLES[$before['role']] ?? $before['role']).' to '.strtolower($member->roleLabel()).'.',
                $member->auditSubject(['before' => ['role' => $before['role']], 'after' => ['role' => $after['role']]]));
        }
        if ($before['partner_access'] !== $after['partner_access'] && $member->isPartner()) {
            $auditLogger->record($request, $admin, 'partner_access_changed', 'Set partner access for '.$member->name.' to '.strtolower($member->partnerAccessLabel()).'.',
                $member->auditSubject(['before' => ['partner_access' => $before['partner_access']], 'after' => ['partner_access' => $after['partner_access']]]));
        }

        return back()->with('status', $member->name.'\'s account was updated.'.(string) $codeNote);
    }

    /**
     * Create the company half of a partner, linked to the account half.
     *
     * Reached both when a partner is created (store) and when one issued before
     * accounts and codes were joined is given a company for the first time
     * (syncPartnerCode), so the record and its audit entry are built once.
     *
     * @param  array<string, mixed>  $data
     */
    private function createPartnerCode(Request $request, CrmUser $admin, CrmUser $member, array $data, CrmAuditLogger $auditLogger): CrmPartnerCode
    {
        $code = CrmPartnerCode::query()->create([
            'crm_user_id' => $member->id,
            ...CrmPartnerCode::companyValues($data),
            // Not asked for twice: the referral notices go to the same address
            // the partner signs in with.
            ...CrmPartnerCode::contactFrom($member),
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $auditLogger->record($request, $admin, 'partner_code_created', 'Created partner code '.$code->label().'.',
            $code->auditSubject(['after' => $code->only(CrmPartnerCode::TRACKED_FIELDS)]));

        return $code;
    }

    /**
     * Save the company half of a partner alongside the account half.
     *
     * Returns null when nothing about the company changed, so the caller can
     * still say "no changes to save"; otherwise the sentence to append to the
     * status, which is how a retired code gets said out loud.
     *
     * A partner created before accounts and codes were joined has no company
     * record yet. One is created the first time a company and code are entered
     * on their form, and until then the account simply saves without one.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncPartnerCode(Request $request, CrmUser $admin, CrmUser $member, array $data, ?CrmPartnerCode $existing, CrmAuditLogger $auditLogger): ?string
    {
        $company = CrmPartnerCode::companyValues($data);

        if (! $existing) {
            if ($company['company_name'] === '' || $company['code'] === '') {
                return null;
            }

            $code = $this->createPartnerCode($request, $admin, $member, $data, $auditLogger);

            return ' They can now share the link carrying code '.$code->code.'.';
        }

        $before = $existing->only(CrmPartnerCode::TRACKED_FIELDS);
        $existing->update([...$company, ...CrmPartnerCode::contactFrom($member)]);
        $after = $existing->only(CrmPartnerCode::TRACKED_FIELDS);

        if ($after === $before) {
            return null;
        }

        $auditLogger->record($request, $admin, 'partner_code_updated', 'Updated partner code '.$existing->label().'.',
            $existing->auditSubject(['before' => $before, 'after' => $after]));

        // Changing the code retires the old link, which is worth saying out loud:
        // anything already printed or posted with it stops being attributed.
        return $before['code'] !== $after['code']
            ? ' The old link ('.$before['code'].') no longer names a partner — reshare the new one.'
            : '';
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

        $auditLogger->record($request, $admin, 'team_member_access_changed', ($member->is_active ? 'Restored' : 'Disabled').' CRM access for '.$member->name.'.',
            $member->auditSubject(['before' => ['is_active' => $wasActive], 'after' => ['is_active' => $member->is_active]]));

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
        $code = $wasPartner ? $member->partnerCode()->first() : null;
        // Leads owned or created by this member are automatically unassigned
        // (nullOnDelete), and so is the Partner field on any lead naming them.
        $reassigned = $wasPartner ? $member->partnerLeads()->count() : $member->leads()->count();

        $auditLogger->record($request, $admin, 'team_member_deleted', 'Deleted CRM team member '.$name.'.',
            $member->auditSubject([
                'before' => $member->only([...CrmUser::TRACKED_FIELDS, 'is_active']),
                'leads_unassigned' => $reassigned,
            ]));

        $member->delete();

        $note = match (true) {
            $reassigned === 0 => '',
            $wasPartner => ' '.$reassigned.' lead(s) no longer name a partner.',
            default => ' '.$reassigned.' lead(s) they owned are now unassigned.',
        };

        if ($code) {
            // crm_user_id is nullOnDelete, so the company record outlives the
            // login and the leads it referred keep their attribution. It is
            // paused rather than left live: we have stopped working with this
            // partner, so the link should stop crediting and stop emailing them.
            $code->refresh()->update(['is_active' => false, 'crm_user_id' => null]);

            $auditLogger->record($request, $admin, 'partner_code_access_changed', 'Paused partner code '.$code->label().' with the account it belonged to.',
                $code->auditSubject(['before' => ['is_active' => true], 'after' => ['is_active' => false]]));

            $note .= ' Their link ('.$code->code.') is paused, and can be resumed or removed on the Partner codes tab.';
        }

        return back()->with('status', $roleLabel.' '.$name.' was removed from the CRM.'.$note);
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
            ->map(fn (array $admin): string => CrmUser::normalisePhone((string) ($admin['phone'] ?? '')))
            ->filter(fn (string $phone): bool => strlen($phone) === 10)
            ->values()
            ->all();
    }
}
