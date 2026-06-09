<?php

namespace App\Support;

class MbbsCountryDataSync extends CountryDataSync
{
    protected function liveBase(): string
    {
        return 'mbbs_avglobal_content';
    }

    protected function reviewBase(): string
    {
        return 'mbbs_avglobal_content.review';
    }

    protected function sheets(): array
    {
        return ['Pages', 'Facts', 'Sections', 'Subpoints', 'Neet', 'NeetTrend'];
    }

    protected function keyFields(): array
    {
        return [
            'Pages' => ['page_slug'],
            'Facts' => ['page_slug', 'fact_order'],
            'Sections' => ['page_slug', 'section_key'],
            'Subpoints' => ['page_slug', 'section_key', 'subpoint_order'],
            'Neet' => ['page_slug', 'neet_order'],
            'NeetTrend' => ['page_slug', 'trend_order'],
        ];
    }

    protected function scraperScript(): string
    {
        return 'scripts/mbbs_avglobal.py';
    }

    protected function runFilePrefix(): string
    {
        return 'mbbs-country-sync';
    }

    protected function backupDirectoryName(): string
    {
        return 'mbbs-country-sync-backups';
    }

    protected function syncDisplayName(): string
    {
        return 'MBBS country sync';
    }

    protected function sourceHost(): string
    {
        return 'avglobaloverseas.com';
    }

    protected function pythonEnvKeys(): array
    {
        return ['MBBS_COUNTRY_SYNC_PYTHON', 'COUNTRY_SYNC_PYTHON'];
    }

    protected function rowLabelFields(string $sheet): array
    {
        return match ($sheet) {
            'Pages' => ['country', 'page_slug', 'hero_heading'],
            'Facts' => ['country', 'fact_label', 'fact_value'],
            'Sections' => ['country', 'section_heading'],
            'Subpoints' => ['country', 'section_key', 'subpoint_heading'],
            'Neet' => ['country', 'category', 'marks'],
            'NeetTrend' => ['country', 'year', 'general_marks'],
            default => ['country', 'page_slug'],
        };
    }
}
