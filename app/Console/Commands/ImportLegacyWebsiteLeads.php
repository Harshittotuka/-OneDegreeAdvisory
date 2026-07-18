<?php

namespace App\Console\Commands;

use App\Services\LegacyWebsiteLeadImporter;
use Illuminate\Console\Command;

class ImportLegacyWebsiteLeads extends Command
{
    protected $signature = 'crm:import-website-leads';
    protected $description = 'Idempotently import legacy website submissions, newsletter subscribers and enrollments into the CRM';

    public function handle(LegacyWebsiteLeadImporter $importer): int
    {
        $counts = $importer->import();
        $this->info("CRM import complete: {$counts['profiles']} submissions, {$counts['newsletters']} subscribers, {$counts['payments']} payments processed.");

        return self::SUCCESS;
    }
}
