<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CrmUserController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        /** @var CrmUser $admin */
        $admin = $request->attributes->get('crm_user');
        abort_unless($admin->isSuperAdmin(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^[6-9][0-9]{9}$/', 'unique:crm_users,phone'],
        ]);
        CrmUser::query()->create([
            'name' => trim($data['name']), 'phone' => $data['phone'], 'role' => 'counsellor',
            'is_active' => true, 'created_by' => $admin->id,
        ]);

        return back()->with('status', 'Counsellor created. They can now sign in with their phone and OTP.');
    }

    public function toggle(Request $request, CrmUser $member): RedirectResponse
    {
        /** @var CrmUser $admin */
        $admin = $request->attributes->get('crm_user');
        abort_unless($admin->isSuperAdmin() && ! $member->isSuperAdmin(), 403);
        $member->update(['is_active' => ! $member->is_active]);

        return back()->with('status', $member->is_active ? 'Counsellor access restored.' : 'Counsellor access disabled.');
    }
}
