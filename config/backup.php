<?php

return [
    /*
    | One rolling restore point is created after each successful CMS/CRM
    | request that changed durable data. Keeping this separate from Laravel's
    | cache/session data prevents logins and page views from creating backups.
    */
    // Opt-in only: production explicitly enables this. Local and UAT remain
    // disabled even if the variable is missing from their environment files.
    'enabled' => env('CMS_CRM_BACKUP_ENABLED', false),
    'keep' => max(1, (int) env('CMS_CRM_BACKUP_KEEP', 5)),
    'directory' => env('CMS_CRM_BACKUP_PATH', storage_path('app/backups/cms-crm')),
    'include_uploads' => env('CMS_CRM_BACKUP_INCLUDE_UPLOADS', true),
    'cms_files' => [
        'about-page.json',
        'blog-posts.json',
        'brief-pages.json',
        'career-counselling.json',
        'career-library.json',
        'country-visibility.json',
        'destinations-layout.json',
        'home-hero.json',
        'notice-bar.json',
        'test-prep-compare.json',
        'leverageedu_study_locations_content.json',
        'leverageedu_study_locations_content.xlsx',
        'leverageedu_study_locations_content.snapshot.json',
        'mbbs_avglobal_content.json',
        'mbbs_avglobal_content.xlsx',
        'mbbs_avglobal_content.snapshot.json',
        // Legacy lead stores are retained because they can still be imported
        // into the CRM and are not tracked by Git.
        'newsletter-subscribers.json',
        'profile-submissions.json',
    ],
    'database_connection' => env('CMS_CRM_BACKUP_DB_CONNECTION'),
    'mysqldump_path' => env('CMS_CRM_MYSQLDUMP_PATH', 'mysqldump'),
    'timeout_seconds' => max(30, (int) env('CMS_CRM_BACKUP_TIMEOUT', 300)),
    'lock_seconds' => max(60, (int) env('CMS_CRM_BACKUP_LOCK_SECONDS', 600)),
    'lock_wait_seconds' => max(1, (int) env('CMS_CRM_BACKUP_LOCK_WAIT_SECONDS', 60)),
];
