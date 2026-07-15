<?php

namespace App\Services;

use App\Models\CrmUser;
use Illuminate\Support\Collection;

class CrmSuperAdminSync
{
    /** @return Collection<int, CrmUser> */
    public function sync(): Collection
    {
        $admins = collect([(array) config('crm.super_admin')])
            ->merge((array) config('crm.additional_super_admins', []));

        return $admins->map(function (array $admin): ?CrmUser {
            $phone = $this->normalisePhone((string) ($admin['phone'] ?? ''));
            $email = trim((string) ($admin['email'] ?? ''));

            if (strlen($phone) !== 10 || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return null;
            }

            $user = CrmUser::query()->firstOrNew(['phone' => $phone]);
            $user->fill([
                'name' => trim((string) ($admin['name'] ?? 'Super Admin')) ?: 'Super Admin',
                'email' => $email,
                'role' => 'super_admin',
            ]);
            if (! $user->exists) {
                $user->is_active = true;
            }
            $user->save();

            return $user;
        })->filter()->values();
    }

    private function normalisePhone(string $phone): string
    {
        return substr((string) preg_replace('/\D+/', '', $phone), -10);
    }
}
