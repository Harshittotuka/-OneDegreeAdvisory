<?php

namespace App\Console\Commands;

use App\Services\CrmSuperAdminSync;
use Illuminate\Console\Command;

class SyncCrmSuperAdmins extends Command
{
    protected $signature = 'crm:sync-super-admins';

    protected $description = 'Create or refresh the configured CRM super administrator accounts';

    public function handle(CrmSuperAdminSync $sync): int
    {
        $admins = $sync->sync();
        $this->info('CRM super admins synced: '.$admins->count());
        $admins->each(fn ($admin) => $this->line($admin->name.' <'.$admin->email.'> +91 '.$admin->phone));

        return self::SUCCESS;
    }
}
