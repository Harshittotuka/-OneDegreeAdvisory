<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmUser;
use App\Services\CrmAuditLogger;
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
            'email' => ['required', 'email', 'max:190', 'unique:crm_users,email'],
            'role' => ['required', Rule::in(['counsellor', 'super_admin'])],
        ]);
        $phone = $this->normalisePhone($data['phone']);
        if (CrmUser::query()->where('phone', $phone)->exists()) {
            return back()->withErrors(['phone' => 'This mobile number already has a CRM account.'])->withInput();
        }

        $member = CrmUser::query()->create([
            'name' => trim($data['name']), 'phone' => $phone, 'email' => strtolower(trim($data['email'])), 'role' => $data['role'],
            'is_active' => true, 'created_by' => $admin->id,
        ]);

        $auditLogger->record($request, $admin, 'team_member_created', 'Created CRM team member '.$member->name.'.', [
            'subject_type' => 'team_member',
            'subject_id' => $member->id,
            'subject_label' => $member->name,
            'changes' => ['after' => $member->only(['name', 'phone', 'email', 'role', 'is_active'])],
        ]);

        $roleLabel = $member->isSuperAdmin() ? 'super admin' : 'counsellor';

        return back()->with('status', ucfirst($roleLabel).' created. They can now sign in with their phone and receive the OTP on their registered email.');
    }

    public function update(Request $request, CrmUser $member, CrmAuditLogger $auditLogger): RedirectResponse
    {
        /** @var CrmUser $admin */
        $admin = $request->attributes->get('crm_user');
        abort_unless($admin->isSuperAdmin(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('crm_users', 'email')->ignore($member->id)],
        ]);
        $before = $member->only(['name', 'email']);
        $member->update(['name' => trim($data['name']), 'email' => strtolower(trim($data['email']))]);

        $auditLogger->record($request, $admin, 'team_member_updated', 'Updated CRM team member '.$member->name.'.', [
            'subject_type' => 'team_member',
            'subject_id' => $member->id,
            'subject_label' => $member->name,
            'changes' => ['before' => $before, 'after' => $member->only(['name', 'email'])],
        ]);

        return back()->with('status', $member->name.'\'s account details were updated.');
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

        $roleLabel = $member->isSuperAdmin() ? 'Super admin' : 'Counsellor';

        return back()->with('status', $roleLabel.' access '.($member->is_active ? 'restored.' : 'disabled.'));
    }

    public function destroy(Request $request, CrmUser $member, CrmAuditLogger $auditLogger): RedirectResponse
    {
        /** @var CrmUser $admin */
        $admin = $request->attributes->get('crm_user');
        abort_unless($admin->isSuperAdmin() && $admin->id !== $member->id, 403);

        if ($member->isSuperAdmin() && CrmUser::query()->where('role', 'super_admin')->count() <= 1) {
            return back()->withErrors(['team' => 'At least one super admin must remain in the CRM.']);
        }

        $roleLabel = $member->isSuperAdmin() ? 'Super admin' : 'Counsellor';
        $name = $member->name;
        // Leads owned or created by this member are automatically unassigned (nullOnDelete).
        $reassigned = $member->leads()->count();

        $auditLogger->record($request, $admin, 'team_member_deleted', 'Deleted CRM team member '.$name.'.', [
            'subject_type' => 'team_member',
            'subject_id' => $member->id,
            'subject_label' => $name,
            'changes' => ['before' => $member->only(['name', 'phone', 'email', 'role', 'is_active']), 'leads_unassigned' => $reassigned],
        ]);

        $member->delete();

        $note = $reassigned > 0
            ? ' '.$reassigned.' lead(s) they owned are now unassigned.'
            : '';

        return back()->with('status', $roleLabel.' '.$name.' was removed from the CRM.'.$note);
    }

    private function normalisePhone(string $phone): string
    {
        return substr((string) preg_replace('/\D+/', '', $phone), -10);
    }
}
