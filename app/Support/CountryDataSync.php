<?php

namespace App\Support;

use RuntimeException;
use Symfony\Component\Process\Process;

class CountryDataSync
{
    private const LIVE_BASE = 'leverageedu_study_locations_content';
    private const REVIEW_BASE = 'leverageedu_study_locations_content.review';
    private const SHEETS = [
        'Pages',
        'Sections',
        'Cards',
        'Courses',
        'Images',
        'IndianStudents',
        'UiText',
    ];

    private const KEY_FIELDS = [
        'Pages' => ['page_slug'],
        'Sections' => ['page_slug', 'section_order'],
        'Cards' => ['page_slug', 'section_order', 'card_order'],
        'Courses' => ['page_slug', 'section_order', 'course_order'],
        'Images' => ['page_slug', 'section_order', 'image_order'],
        'IndianStudents' => ['page_slug', 'card_order'],
        'UiText' => ['page_slug', 'text_key'],
    ];

    public function state(): array
    {
        $live = $this->readJsonIfExists($this->liveJsonPath());
        $review = $this->readJsonIfExists($this->reviewJsonPath());
        $comparison = null;

        if ($live !== [] && $review !== []) {
            $comparison = $this->compare($live, $review);
        }

        return [
            'paths' => $this->paths(),
            'running' => $this->isRunning(),
            'live' => [
                'exists' => is_file($this->liveJsonPath()),
                'generated_at' => (string) ($live['generated_at_utc'] ?? ''),
                'updated_at' => $this->fileTime($this->liveJsonPath()),
            ],
            'review' => [
                'exists' => is_file($this->reviewJsonPath()),
                'generated_at' => (string) ($review['generated_at_utc'] ?? ''),
                'updated_at' => $this->fileTime($this->reviewJsonPath()),
                'report_exists' => is_file($this->reviewReportPath()),
                'workbook_exists' => is_file($this->reviewWorkbookPath()),
            ],
            'last_run' => [
                'exists' => is_file($this->lastRunPath()),
                'updated_at' => $this->fileTime($this->lastRunPath()),
                'output' => $this->readLastRun(),
            ],
            'comparison' => $comparison,
        ];
    }

    public function runReview(): array
    {
        $this->prepareReviewSnapshot();
        $this->deleteIfExists($this->reviewReportPath());
        $this->deleteIfExists($this->reviewWorkbookPath());
        $this->deleteIfExists($this->reviewJsonPath());

        $command = $this->reviewCommand();

        $environment = $this->processEnvironment();
        $process = new Process($command, base_path(), $environment, null, 900);
        $process->run();

        $output = trim($this->launcherSummary($command, $environment).PHP_EOL.PHP_EOL.$process->getOutput().PHP_EOL.$process->getErrorOutput());
        $this->writeLastRun($output);

        if (! $process->isSuccessful()) {
            throw new RuntimeException($output !== '' ? $output : 'Country scraper failed.');
        }

        if (! is_file($this->reviewJsonPath())) {
            throw new RuntimeException('Country scraper finished, but no review JSON was created.');
        }

        return [
            'output' => $output,
            'comparison' => $this->state()['comparison'],
        ];
    }

    /**
     * Launch the scraper as a detached background process and return immediately.
     * Progress is streamed to a live log that the front-end polls via progress().
     */
    public function startReview(): array
    {
        if ($this->isRunning()) {
            return ['started' => false, 'already_running' => true];
        }

        $this->prepareReviewSnapshot();
        $this->deleteIfExists($this->reviewReportPath());
        $this->deleteIfExists($this->reviewWorkbookPath());
        $this->deleteIfExists($this->reviewJsonPath());
        $this->deleteIfExists($this->exitPath());

        $command = $this->reviewCommand();
        $environment = $this->processEnvironment();

        $bat = $this->buildBatch($command, $environment);
        $this->writeText($this->batPath(), $bat);

        $this->writeStatus([
            'state' => 'running',
            'started_at' => gmdate('Y-m-d H:i:s').' UTC',
            'started_unix' => time(),
        ]);

        // start /B launches the batch in its own (window-less) process and returns
        // instantly, so the detached scraper outlives this HTTP request. The
        // >nul redirect keeps the detached tree off the launcher's stdout pipe.
        $launch = 'start "" /B "'.$this->batPath().'" >nul 2>&1';
        $handle = @popen($launch, 'r');
        if ($handle === false) {
            $this->writeStatus(['state' => 'failed', 'finished_at' => gmdate('Y-m-d H:i:s').' UTC']);
            throw new RuntimeException('Could not launch the country sync process.');
        }
        pclose($handle);

        return ['started' => true];
    }

    /**
     * Snapshot of the running (or last) check: percent, current step, live log tail.
     */
    public function progress(): array
    {
        $status = $this->readStatus();
        $state = (string) ($status['state'] ?? 'idle');
        $log = $this->readLiveLog();
        $parsed = $this->parseProgress($log);

        $exitExists = is_file($this->exitPath());
        $exitCode = $exitExists ? (int) trim((string) @file_get_contents($this->exitPath())) : null;

        $done = false;
        $failed = false;

        if ($exitExists) {
            if ($exitCode === 0 && is_file($this->reviewJsonPath())) {
                $done = true;
            } else {
                $failed = true;
            }

            // Archive the live log into the persistent last-run log and flip status once.
            if ($state === 'running') {
                $this->writeLastRun($log);
                $status['state'] = $state = $done ? 'done' : 'failed';
                $status['finished_at'] = gmdate('Y-m-d H:i:s').' UTC';
                $this->writeStatus($status);
            }
        } elseif ($state === 'done') {
            $done = true;
        } elseif ($state === 'failed') {
            $failed = true;
        }

        $running = $state === 'running' && ! $exitExists;
        $percent = ($done || $failed) ? 100 : $parsed['percent'];

        $result = [
            'ok' => true,
            'running' => $running,
            'done' => $done,
            'failed' => $failed,
            'state' => $state,
            'percent' => $percent,
            'phase' => $parsed['phase'],
            'step' => $parsed['step'],
            'current' => $parsed['current'],
            'total' => $parsed['total'],
            'log_tail' => $parsed['tail'],
            'elapsed' => isset($status['started_unix']) ? max(0, time() - (int) $status['started_unix']) : 0,
        ];

        if ($done) {
            $summary = $this->state()['comparison']['summary'] ?? null;
            $result['summary'] = $summary;
            $result['message'] = $summary
                ? "Done — {$summary['changed_percent']}% changed across {$summary['changed_records']} record(s), {$summary['field_changes']} field change(s)."
                : 'Source check complete.';
        } elseif ($failed) {
            $result['message'] = 'Source check failed. See the log below.';
        }

        return $result;
    }

    public function isRunning(): bool
    {
        $status = $this->readStatus();

        if (($status['state'] ?? '') !== 'running') {
            return false;
        }

        // The scraper has written its exit marker — it is no longer running.
        if (is_file($this->exitPath())) {
            return false;
        }

        // Safety valve: never report "running" forever if the process vanished.
        $started = (int) ($status['started_unix'] ?? 0);
        if ($started > 0 && (time() - $started) > 1800) {
            return false;
        }

        return true;
    }

    public function applyAll(): string
    {
        if (! is_file($this->reviewJsonPath())) {
            throw new RuntimeException('Run a source check before updating live country data.');
        }

        $backupDir = $this->backupLiveFiles();
        $this->copyRequired($this->reviewJsonPath(), $this->liveJsonPath());

        if (is_file($this->reviewWorkbookPath())) {
            $this->copyRequired($this->reviewWorkbookPath(), $this->liveWorkbookPath());
        }

        if (is_file($this->reviewSnapshotPath())) {
            $this->copyRequired($this->reviewSnapshotPath(), $this->liveSnapshotPath());
        }

        return $backupDir;
    }

    public function applySelected(array $submittedChanges): int
    {
        if (! is_file($this->reviewJsonPath())) {
            throw new RuntimeException('Run a source check before applying selected changes.');
        }

        $live = $this->readJson($this->liveJsonPath());
        $review = $this->readJson($this->reviewJsonPath());
        $comparison = $this->compare($live, $review);
        $detailsByToken = [];

        foreach ($comparison['details'] as $detail) {
            $detailsByToken[$detail['token']] = $detail;
        }

        $applied = 0;
        $this->backupLiveFiles();

        foreach ($submittedChanges as $token => $payload) {
            if (! is_array($payload) || empty($payload['apply']) || ! isset($detailsByToken[$token])) {
                continue;
            }

            $detail = $detailsByToken[$token];
            $sheet = $detail['sheet_name'];
            $rows = $live['sheets'][$sheet] ?? [];
            if (! is_array($rows)) {
                $rows = [];
            }

            $index = $this->findRowIndex($rows, $sheet, $detail['row_key']);

            if ($detail['change_type'] === 'added') {
                $row = json_decode((string) ($payload['value'] ?? ''), true);
                if (! is_array($row)) {
                    $row = $detail['new_row'];
                }
                $rows[] = $row;
                $live['sheets'][$sheet] = $rows;
                $applied++;
                continue;
            }

            if ($detail['change_type'] === 'removed') {
                if ($index >= 0) {
                    unset($rows[$index]);
                    $live['sheets'][$sheet] = array_values($rows);
                    $applied++;
                }
                continue;
            }

            if ($detail['change_type'] === 'modified' && $index >= 0) {
                $rows[$index][$detail['field_name']] = (string) ($payload['value'] ?? '');
                $live['sheets'][$sheet] = $rows;
                $applied++;
            }
        }

        if ($applied > 0) {
            $this->writeJson($this->liveJsonPath(), $live);
        }

        return $applied;
    }

    public function paths(): array
    {
        return [
            'live_json' => $this->liveJsonPath(),
            'live_workbook' => $this->liveWorkbookPath(),
            'live_snapshot' => $this->liveSnapshotPath(),
            'review_json' => $this->reviewJsonPath(),
            'review_workbook' => $this->reviewWorkbookPath(),
            'review_snapshot' => $this->reviewSnapshotPath(),
            'review_report' => $this->reviewReportPath(),
        ];
    }

    public function reviewReportPath(): string
    {
        return storage_path('app/'.self::LIVE_BASE.'_review_pending_changes.xlsx');
    }

    public function reviewWorkbookPath(): string
    {
        return storage_path('app/'.self::REVIEW_BASE.'.xlsx');
    }

    private function compare(array $oldDataset, array $newDataset): array
    {
        $oldMap = $this->comparableMap($oldDataset);
        $newMap = $this->comparableMap($newDataset);
        $oldKeys = array_keys($oldMap);
        $newKeys = array_keys($newMap);
        $addedKeys = array_values(array_diff($newKeys, $oldKeys));
        $removedKeys = array_values(array_diff($oldKeys, $newKeys));
        $modifiedKeys = [];

        foreach (array_intersect($oldKeys, $newKeys) as $key) {
            if ($this->normalizedRow($oldMap[$key]['row']) !== $this->normalizedRow($newMap[$key]['row'])) {
                $modifiedKeys[] = $key;
            }
        }

        sort($addedKeys);
        sort($removedKeys);
        sort($modifiedKeys);

        $details = [];

        foreach ($addedKeys as $key) {
            $new = $newMap[$key];
            $details[] = $this->detailRow('added', $new['sheet'], $new['row_key'], '__row__', '', $new['row'], [], $new['row']);
        }

        foreach ($removedKeys as $key) {
            $old = $oldMap[$key];
            $details[] = $this->detailRow('removed', $old['sheet'], $old['row_key'], '__row__', $old['row'], '', $old['row'], []);
        }

        foreach ($modifiedKeys as $key) {
            $old = $oldMap[$key];
            $new = $newMap[$key];
            $fields = array_values(array_unique(array_merge(array_keys($old['row']), array_keys($new['row']))));
            sort($fields);

            foreach ($fields as $field) {
                if ($this->normalizeValue($old['row'][$field] ?? '') === $this->normalizeValue($new['row'][$field] ?? '')) {
                    continue;
                }

                $details[] = $this->detailRow(
                    'modified',
                    $new['sheet'],
                    $new['row_key'],
                    $field,
                    $old['row'][$field] ?? '',
                    $new['row'][$field] ?? '',
                    $old['row'],
                    $new['row']
                );
            }
        }

        $changedRecords = count($addedKeys) + count($removedKeys) + count($modifiedKeys);
        $denominator = max(count($oldMap), count($newMap), 1);

        return [
            'has_changes' => $changedRecords > 0,
            'summary' => [
                'records_before' => count($oldMap),
                'records_after' => count($newMap),
                'added_records' => count($addedKeys),
                'removed_records' => count($removedKeys),
                'modified_records' => count($modifiedKeys),
                'changed_records' => $changedRecords,
                'changed_percent' => round(($changedRecords / $denominator) * 100, 2),
                'field_changes' => count($details),
            ],
            'details' => $details,
        ];
    }

    private function comparableMap(array $dataset): array
    {
        $map = [];
        $sheets = $dataset['sheets'] ?? [];

        foreach (self::SHEETS as $sheet) {
            $rows = $sheets[$sheet] ?? [];
            if (! is_array($rows)) {
                continue;
            }

            foreach ($rows as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $rowKey = $this->rowKey($sheet, $row, (int) $index);
                $map[$sheet.'::'.$rowKey] = [
                    'sheet' => $sheet,
                    'row_key' => $rowKey,
                    'row' => $row,
                ];
            }
        }

        return $map;
    }

    private function detailRow(
        string $type,
        string $sheet,
        string $rowKey,
        string $field,
        mixed $oldValue,
        mixed $newValue,
        array $oldRow,
        array $newRow,
    ): array {
        $row = $newRow !== [] ? $newRow : $oldRow;

        $detail = [
            'change_type' => $type,
            'sheet_name' => $sheet,
            'row_key' => $rowKey,
            'row_label' => $this->rowLabel($sheet, $row, $rowKey),
            'country' => (string) ($row['country'] ?? ''),
            'field_name' => $field,
            'old_value' => $this->displayValue($oldValue),
            'new_value' => $this->displayValue($newValue),
            'old_row' => $oldRow,
            'new_row' => $newRow,
        ];
        $detail['token'] = hash('sha256', implode("\0", [$type, $sheet, $rowKey, $field]));

        return $detail;
    }

    private function rowKey(string $sheet, array $row, int $index): string
    {
        $fields = self::KEY_FIELDS[$sheet] ?? [];
        $parts = [];

        foreach ($fields as $field) {
            $value = $this->normalizeValue($row[$field] ?? '');
            if ($value === '') {
                return 'index='.$index;
            }
            $parts[] = $field.'='.$value;
        }

        return $parts !== [] ? implode('|', $parts) : 'index='.$index;
    }

    private function rowLabel(string $sheet, array $row, string $rowKey): string
    {
        $fields = match ($sheet) {
            'Pages' => ['country', 'page_slug', 'hero_heading'],
            'Sections' => ['country', 'section_heading'],
            'Cards' => ['country', 'section_heading', 'card_title'],
            'Courses' => ['country', 'course_name', 'university_name'],
            'Images' => ['country', 'section_heading', 'image_alt'],
            'IndianStudents' => ['country', 'card_value', 'card_description'],
            'UiText' => ['country', 'text_key'],
            default => ['country', 'page_slug'],
        };

        $values = [];
        foreach ($fields as $field) {
            $value = trim((string) ($row[$field] ?? ''));
            if ($value !== '' && ! in_array($value, $values, true)) {
                $values[] = $value;
            }
        }

        return $values !== [] ? implode(' / ', $values) : $rowKey;
    }

    private function normalizedRow(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[$key] = $this->normalizeValue($value);
        }
        ksort($normalized);

        return $normalized;
    }

    private function normalizeValue(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return trim((string) preg_replace('/\s+/', ' ', (string) $value));
    }

    private function displayValue(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }

        return (string) $value;
    }

    private function findRowIndex(array $rows, string $sheet, string $rowKey): int
    {
        foreach ($rows as $index => $row) {
            if (is_array($row) && $this->rowKey($sheet, $row, (int) $index) === $rowKey) {
                return (int) $index;
            }
        }

        return -1;
    }

    private function prepareReviewSnapshot(): void
    {
        $this->ensureDirectory(dirname($this->reviewSnapshotPath()));

        if (is_file($this->liveSnapshotPath())) {
            $this->copyRequired($this->liveSnapshotPath(), $this->reviewSnapshotPath());

            return;
        }

        $this->deleteIfExists($this->reviewSnapshotPath());
    }

    private function backupLiveFiles(): string
    {
        $backupDir = storage_path('app/country-sync-backups/'.gmdate('Ymd-His'));
        $this->ensureDirectory($backupDir);

        foreach ([
            $this->liveJsonPath(),
            $this->liveWorkbookPath(),
            $this->liveSnapshotPath(),
        ] as $path) {
            if (is_file($path)) {
                $this->copyRequired($path, $backupDir.DIRECTORY_SEPARATOR.basename($path));
            }
        }

        return $backupDir;
    }

    private function readJsonIfExists(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        return $this->readJson($path);
    }

    private function readJson(string $path): array
    {
        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            throw new RuntimeException('Could not read JSON: '.$path);
        }

        return $data;
    }

    private function writeJson(string $path, array $data): void
    {
        $this->ensureDirectory(dirname($path));
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false || file_put_contents($path, $json.PHP_EOL) === false) {
            throw new RuntimeException('Could not write JSON: '.$path);
        }
    }

    private function reviewCommand(): array
    {
        return array_merge($this->pythonCommand(), [
            (string) base_path('scripts/leverageedu_study_locations.py'),
            '--approve',
            '--output',
            $this->reviewWorkbookPath(),
            '--snapshot',
            $this->reviewSnapshotPath(),
            '--content-json',
            $this->reviewJsonPath(),
            '--report',
            $this->reviewReportPath(),
        ]);
    }

    /**
     * Build a self-contained .bat that runs the scraper unbuffered, streams output
     * to the live log, and records the exit code so progress() can detect completion.
     */
    private function buildBatch(array $command, array $environment): string
    {
        $python = (string) array_shift($command);
        $args = array_map(fn ($arg) => '"'.$arg.'"', $command);

        $live = $this->liveLogPath();
        $exit = $this->exitPath();

        $lines = [
            '@echo off',
            'chcp 65001 >nul',
            'set "PYTHONIOENCODING=utf-8"',
            'set "PYTHONUTF8=1"',
            'set "PATH='.($environment['PATH'] ?? '%PATH%').'"',
            'echo Country source check started.>"'.$live.'"',
            'echo Launcher: '.$python.'>>"'.$live.'"',
            'echo.>>"'.$live.'"',
            '"'.$python.'" -u '.implode(' ', $args).' >>"'.$live.'" 2>&1',
            'set "RC=%ERRORLEVEL%"',
            'echo.>>"'.$live.'"',
            'echo [[COUNTRY-SYNC-EXIT %RC%]]>>"'.$live.'"',
            // Redirect-first form: `echo 0>"file"` would make cmd parse `0>` as a
            // stdin redirect, so the exit code must be echoed after the redirection.
            '>"'.$exit.'" echo %RC%',
        ];

        return implode("\r\n", $lines)."\r\n";
    }

    private function pythonCommand(): array
    {
        $configured = trim((string) env('COUNTRY_SYNC_PYTHON', ''));

        if ($configured !== '') {
            return [$configured];
        }

        foreach ($this->pythonCandidates() as $candidate) {
            if (is_file($candidate)) {
                return [$candidate];
            }
        }

        return ['python'];
    }

    private function pythonCandidates(): array
    {
        $userProfile = rtrim((string) (getenv('USERPROFILE') ?: 'C:\Users\harsh'), '\\/');

        return [
            $userProfile.'\AppData\Local\Programs\Python\Python314\python.exe',
            $userProfile.'\AppData\Local\Programs\Python\Python313\python.exe',
            $userProfile.'\AppData\Local\Programs\Python\Python312\python.exe',
            $userProfile.'\AppData\Local\Programs\Python\Python311\python.exe',
            'C:\Users\harsh\AppData\Local\Programs\Python\Python314\python.exe',
            'C:\Users\harsh\AppData\Local\Programs\Python\Python311\python.exe',
        ];
    }

    private function processEnvironment(): array
    {
        $windows = getenv('SystemRoot') ?: getenv('WINDIR') ?: 'C:\Windows';
        $systemPath = implode(PATH_SEPARATOR, [
            $windows.'\System32',
            $windows,
            $windows.'\System32\Wbem',
            $windows.'\System32\WindowsPowerShell\v1.0',
            $windows.'\System32\OpenSSH',
        ]);
        $existingPath = getenv('PATH') ?: getenv('Path') ?: '';
        $path = $existingPath !== '' ? $systemPath.PATH_SEPARATOR.$existingPath : $systemPath;
        $temp = sys_get_temp_dir() ?: $windows.'\Temp';

        return [
            'SystemRoot' => $windows,
            'WINDIR' => $windows,
            'windir' => $windows,
            'ComSpec' => $windows.'\System32\cmd.exe',
            'PATH' => $path,
            'Path' => $path,
            'TEMP' => $temp,
            'TMP' => $temp,
            'PYTHONIOENCODING' => 'utf-8',
            'PYTHONUTF8' => '1',
        ];
    }

    private function launcherSummary(array $command, array $environment): string
    {
        $python = (string) ($command[0] ?? 'python');
        $systemRoot = (string) ($environment['SystemRoot'] ?? '');
        $path = strtolower((string) ($environment['PATH'] ?? ''));
        $system32 = strtolower($systemRoot.'\System32');

        return implode(PHP_EOL, [
            'Python launcher: '.$python,
            'SystemRoot: '.$systemRoot,
            'PATH has System32: '.(str_contains($path, $system32) ? 'yes' : 'no'),
        ]);
    }

    private function fileTime(string $path): string
    {
        return is_file($path) ? date('Y-m-d H:i:s', (int) filemtime($path)) : '';
    }

    private function copyRequired(string $from, string $to): void
    {
        $this->ensureDirectory(dirname($to));
        if (! copy($from, $to)) {
            throw new RuntimeException("Could not copy {$from} to {$to}.");
        }
    }

    private function ensureDirectory(string $path): void
    {
        if (! is_dir($path) && ! mkdir($path, 0775, true) && ! is_dir($path)) {
            throw new RuntimeException('Could not create directory: '.$path);
        }
    }

    private function deleteIfExists(string $path): void
    {
        if (is_file($path) && ! unlink($path)) {
            throw new RuntimeException('Could not remove old review file: '.$path);
        }
    }

    private function readLastRun(): string
    {
        if (! is_file($this->lastRunPath())) {
            return '';
        }

        $text = trim((string) file_get_contents($this->lastRunPath()));

        return strlen($text) > 12000 ? '...'.substr($text, -12000) : $text;
    }

    private function writeLastRun(string $output): void
    {
        $text = trim($output);
        $header = 'Country sync run at '.gmdate('Y-m-d H:i:s').' UTC';
        $this->writeText($this->lastRunPath(), $header.PHP_EOL.PHP_EOL.($text !== '' ? $text : 'No output.'));
    }

    private function writeText(string $path, string $text): void
    {
        $this->ensureDirectory(dirname($path));
        if (file_put_contents($path, $text.PHP_EOL) === false) {
            throw new RuntimeException('Could not write file: '.$path);
        }
    }

    private function readLiveLog(): string
    {
        if (! is_file($this->liveLogPath())) {
            return '';
        }

        // The detached process may hold the file open for append; tolerate a
        // transient read failure rather than blanking the panel.
        $text = @file_get_contents($this->liveLogPath());

        return $text === false ? '' : (string) $text;
    }

    /**
     * Turn the raw scraper log into a percent + human step label for the UI.
     */
    private function parseProgress(string $log): array
    {
        $lines = $log === '' ? [] : (preg_split('/\r\n|\r|\n/', rtrim($log)) ?: []);

        $total = 19;
        $current = 0;
        $phase = 'Starting…';
        $step = 'Preparing the source check…';
        $sawDiscovering = false;
        $sawSummary = false;
        $sawWrite = false;

        foreach ($lines as $line) {
            if (stripos($line, 'Discovering country URLs') !== false) {
                $sawDiscovering = true;
                $phase = 'Discovering pages';
                $step = 'Finding country pages on leverageedu.com…';
            }
            if (preg_match('/Discovered\s+(\d+)\s+country/i', $line, $m)) {
                $total = max(1, (int) $m[1]);
            }
            if (preg_match('#^\[(\d+)/(\d+)\]\s+Fetching\s+(\S+)#i', $line, $m)) {
                $current = (int) $m[1];
                $total = max(1, (int) $m[2]);
                $phase = 'Fetching country pages';
                $step = 'Fetching '.$this->slugFromUrl($m[3])." ({$current}/{$total})";
            }
            if (stripos($line, 'Change summary') !== false) {
                $sawSummary = true;
                $phase = 'Comparing data';
                $step = 'Comparing fresh source data with the current website data…';
            }
            if (stripos($line, 'content JSON updated') !== false
                || stripos($line, 'content JSON refreshed') !== false
                || stripos($line, 'content JSON created') !== false
                || stripos($line, 'Snapshot updated') !== false
                || stripos($line, 'No data changes detected') !== false) {
                $sawWrite = true;
                $phase = 'Finishing';
                $step = 'Writing the review files…';
            }
        }

        if ($sawWrite) {
            $percent = 98;
        } elseif ($sawSummary) {
            $percent = 92;
        } elseif ($current > 0) {
            $percent = (int) round(8 + ($current / $total) * 82);
        } elseif ($sawDiscovering) {
            $percent = 6;
        } else {
            $percent = 3;
        }

        return [
            'percent' => max(2, min(99, $percent)),
            'phase' => $phase,
            'step' => $step,
            'current' => $current,
            'total' => $total,
            'tail' => implode("\n", array_slice($lines, -45)),
        ];
    }

    private function slugFromUrl(string $url): string
    {
        $path = trim((string) (parse_url($url, PHP_URL_PATH) ?: $url), '/');
        $segment = $path !== '' ? basename($path) : $url;
        $segment = (string) preg_replace('/^study-in-/i', '', $segment);
        $segment = trim(str_replace('-', ' ', $segment));

        return $segment !== '' ? ucwords($segment) : $url;
    }

    private function readStatus(): array
    {
        if (! is_file($this->statusPath())) {
            return [];
        }

        $data = json_decode((string) @file_get_contents($this->statusPath()), true);

        return is_array($data) ? $data : [];
    }

    private function writeStatus(array $data): void
    {
        $this->writeJson($this->statusPath(), $data);
    }

    private function liveLogPath(): string
    {
        return storage_path('app/country-sync-live.log');
    }

    private function statusPath(): string
    {
        return storage_path('app/country-sync-status.json');
    }

    private function exitPath(): string
    {
        return storage_path('app/country-sync-exit.txt');
    }

    private function batPath(): string
    {
        return storage_path('app/country-sync-run.bat');
    }

    private function liveJsonPath(): string
    {
        return storage_path('app/'.self::LIVE_BASE.'.json');
    }

    private function liveWorkbookPath(): string
    {
        return storage_path('app/'.self::LIVE_BASE.'.xlsx');
    }

    private function liveSnapshotPath(): string
    {
        return storage_path('app/'.self::LIVE_BASE.'.snapshot.json');
    }

    private function reviewJsonPath(): string
    {
        return storage_path('app/'.self::REVIEW_BASE.'.json');
    }

    private function reviewSnapshotPath(): string
    {
        return storage_path('app/'.self::REVIEW_BASE.'.snapshot.json');
    }

    private function lastRunPath(): string
    {
        return storage_path('app/country-sync-last-run.log');
    }
}
