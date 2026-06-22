<?php

namespace Tests\Concerns;

/**
 * Snapshots storage/app/profile-submissions.json before each test and restores
 * it afterwards (deleting it if it did not exist), so tests that hit the
 * profiler / evaluator submit endpoints — which now record submissions — never
 * pollute the real, gitignored CMS data file. Mirrors the snapshot/restore
 * pattern used by the other file-backed-store tests in this suite.
 */
trait PreservesProfileSubmissions
{
    private ?string $submissionsBackup = null;

    protected function backupSubmissions(): void
    {
        $path = storage_path('app/profile-submissions.json');
        $this->submissionsBackup = is_file($path) ? (string) file_get_contents($path) : null;
    }

    protected function restoreSubmissions(): void
    {
        $path = storage_path('app/profile-submissions.json');
        if ($this->submissionsBackup !== null) {
            file_put_contents($path, $this->submissionsBackup);
        } elseif (is_file($path)) {
            @unlink($path);
        }
    }
}
