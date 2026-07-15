<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmUser;
use App\Services\CrmNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CrmUserController extends Controller
{
    public function store(Request $request, CrmNotifier $notifier): RedirectResponse
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

        $roleLabel = $member->isSuperAdmin() ? 'super admin' : 'counsellor';
        $recipients = CrmUser::query()->where('role', 'super_admin')->where('is_active', true)->get()->push($member)->unique('id');
        $notifier->sendToUsers(
            $recipients,
            'CRM '.$roleLabel.' account created',
            'New CRM team account',
            $member->name.' now has '.$roleLabel.' access to the One Degree CRM.',
            ['Name' => $member->name, 'Role' => ucfirst($roleLabel), 'Mobile' => '+91 '.$member->phone, 'Email' => $member->email, 'Created by' => $admin->name],
            route('crm.login'),
            'Open CRM login',
        );

        return back()->with('status', ucfirst($roleLabel).' created. They can now sign in with their phone and receive the OTP on their registered email.');
    }

    public function update(Request $request, CrmUser $member, CrmNotifier $notifier): RedirectResponse
    {
        /** @var CrmUser $admin */
        $admin = $request->attributes->get('crm_user');
        abort_unless($admin->isSuperAdmin(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('crm_users', 'email')->ignore($member->id)],
        ]);
        $member->update(['name' => trim($data['name']), 'email' => strtolower(trim($data['email']))]);

        $recipients = CrmUser::query()->where('role', 'super_admin')->where('is_active', true)->get()->push($member)->unique('id');
        $notifier->sendToUsers(
            $recipients,
            'CRM team account updated: '.$member->name,
            'CRM account updated',
            $admin->name.' updated your One Degree CRM account details.',
            ['Name' => $member->name, 'Email' => $member->email, 'Role' => $member->isSuperAdmin() ? 'Super admin' : 'Counsellor'],
            route('crm.dashboard'),
        );

        return back()->with('status', $member->name.'\'s account details were updated.');
    }

    public function toggle(Request $request, CrmUser $member, CrmNotifier $notifier): RedirectResponse
    {
        /** @var CrmUser $admin */
        $admin = $request->attributes->get('crm_user');
        abort_unless($admin->isSuperAdmin() && $admin->id !== $member->id, 403);
        if ($member->isSuperAdmin() && $member->is_active && CrmUser::query()->where('role', 'super_admin')->where('is_active', true)->count() <= 1) {
            return back()->withErrors(['team' => 'At least one super admin must remain active.']);
        }
        $member->update(['is_active' => ! $member->is_active]);

        $roleLabel = $member->isSuperAdmin() ? 'Super admin' : 'Counsellor';
        $recipients = CrmUser::query()->where('role', 'super_admin')->where('is_active', true)->get()->push($member)->unique('id');
        $notifier->sendToUsers(
            $recipients,
            'Your One Degree CRM access was '.($member->is_active ? 'restored' : 'disabled'),
            'CRM access '.($member->is_active ? 'restored' : 'disabled'),
            $admin->name.' '.($member->is_active ? 'restored' : 'disabled').' your One Degree CRM access.',
            ['Account' => $member->name, 'Role' => $roleLabel, 'Status' => $member->is_active ? 'Active' : 'Disabled'],
            $member->is_active ? route('crm.login') : null,
            'Open CRM login',
            true,
        );

        return back()->with('status', $roleLabel.' access '.($member->is_active ? 'restored.' : 'disabled.'));
    }

    private function normalisePhone(string $phone): string
    {
        return substr((string) preg_replace('/\D+/', '', $phone), -10);
    }
}
