<?php

namespace App\Support;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class CmsCrmBackupManager
{
    private bool $dirty = false;

    private bool $scheduled = false;

    private bool $running = false;

    /** @var array<string, true> */
    private array $reasons = [];

    public function markDirty(string $reason): void
    {
        if (! config('backup.enabled')) {
            return;
        }

        $this->dirty = true;
        $this->reasons[$reason] = true;
    }

    /**
     * Run after the HTTP response has been sent. Multiple writes in one request
     * deliberately become one consistent restore point.
     */
    public function schedule(): void
    {
        if (! $this->dirty || $this->scheduled || ! config('backup.enabled')) {
            return;
        }

        $this->scheduled = true;
        app()->terminating(fn () => $this->flush());
    }

    public function discard(): void
    {
        $this->dirty = false;
        $this->scheduled = false;
        $this->reasons = [];
    }

    public function flush(): ?string
    {
        if (! $this->dirty || $this->running || ! config('backup.enabled')) {
            return null;
        }

        $reasons = implode(',', array_keys($this->reasons)) ?: 'application-update';
        $this->dirty = false;
        $this->scheduled = false;
        $this->reasons = [];

        try {
            return $this->createSnapshot($reasons);
        } catch (Throwable $e) {
            // A post-response backup failure must never turn a completed save
            // into an apparent application failure. It is still made visible in
            // the server log for monitoring/alerting.
            Log::error('CMS/CRM backup failed.', [
                'reason' => $reasons,
                'exception' => $e,
            ]);

            return null;
        }
    }

    public function createSnapshot(string $reason = 'manual'): string
    {
        if (! config('backup.enabled')) {
            throw new RuntimeException('CMS/CRM backups are disabled in this environment.');
        }

        if ($this->running) {
            throw new RuntimeException('A CMS/CRM backup is already running in this process.');
        }

        $this->running = true;

        try {
            $lock = Cache::lock('cms-crm-backup', (int) config('backup.lock_seconds'));

            return $lock->block(
                (int) config('backup.lock_wait_seconds'),
                fn () => $this->createSnapshotWhileLocked($reason),
            );
        } finally {
            $this->running = false;
        }
    }

    /** @return list<array{id: string, path: string, created_at: string|null, reason: string|null, database_driver: string|null}> */
    public function snapshots(): array
    {
        $items = [];

        foreach ($this->snapshotDirectories() as $path) {
            $manifest = $this->readManifest($path);
            $items[] = [
                'id' => basename($path),
                'path' => $path,
                'created_at' => is_string($manifest['created_at'] ?? null) ? $manifest['created_at'] : null,
                'reason' => is_string($manifest['reason'] ?? null) ? $manifest['reason'] : null,
                'database_driver' => is_string($manifest['database']['driver'] ?? null) ? $manifest['database']['driver'] : null,
            ];
        }

        return $items;
    }

    private function createSnapshotWhileLocked(string $reason): string
    {
        $root = $this->backupRoot();
        $this->ensureDirectory($root);

        $id = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Ymd-His-u')
            .'-'.bin2hex(random_bytes(2));
        $temporary = $root.DIRECTORY_SEPARATOR.'.'.$id.'.partial';
        $final = $root.DIRECTORY_SEPARATOR.$id;
        $previous = $this->snapshotDirectories()[0] ?? null;

        $this->ensureDirectory($temporary);

        try {
            $database = $this->backupDatabase($temporary);
            $cmsFiles = $this->backupCmsFiles($temporary, $previous);
            $uploads = $this->backupUploads($temporary, $previous);

            $manifest = [
                'version' => 1,
                'id' => $id,
                'created_at' => gmdate(DATE_ATOM),
                'reason' => $reason,
                'database' => $database,
                'cms_files' => $cmsFiles,
                'uploads' => $uploads,
            ];

            $manifestPath = $temporary.DIRECTORY_SEPARATOR.'manifest.json';
            $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($json === false || file_put_contents($manifestPath, $json.PHP_EOL, LOCK_EX) === false) {
                throw new RuntimeException('Could not write the backup manifest.');
            }

            if (! @rename($temporary, $final)) {
                throw new RuntimeException('Could not finalize the CMS/CRM backup directory.');
            }

            $this->pruneOldSnapshots();

            Log::info('CMS/CRM backup created.', ['path' => $final, 'reason' => $reason]);

            return $final;
        } catch (Throwable $e) {
            if (is_dir($temporary)) {
                $this->deleteDirectory($temporary, true);
            }
            throw $e;
        }
    }

    /** @return array{driver: string, connection: string, file: string, bytes: int, sha256: string} */
    private function backupDatabase(string $destination): array
    {
        $connectionName = (string) (config('backup.database_connection') ?: config('database.default'));
        $connection = DB::connection($connectionName);
        $driver = $connection->getDriverName();
        $file = $driver === 'sqlite' ? 'database.sqlite' : 'database.sql';
        $target = $destination.DIRECTORY_SEPARATOR.$file;

        if ($driver === 'sqlite') {
            $configuredPath = (string) config("database.connections.{$connectionName}.database");
            if ($configuredPath === ':memory:') {
                throw new RuntimeException('Cannot persist a backup of an in-memory SQLite database.');
            }

            $pdo = $connection->getPdo();
            $pdo->exec('VACUUM INTO '.$pdo->quote($target));
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->dumpMysql($connectionName, $target);
        } else {
            throw new RuntimeException("Unsupported backup database driver [{$driver}].");
        }

        if (! is_file($target) || filesize($target) === 0) {
            throw new RuntimeException('The database backup file was not created or is empty.');
        }

        return [
            'driver' => $driver,
            'connection' => $connectionName,
            'file' => $file,
            'bytes' => (int) filesize($target),
            'sha256' => (string) hash_file('sha256', $target),
        ];
    }

    private function dumpMysql(string $connectionName, string $target): void
    {
        $config = (array) config("database.connections.{$connectionName}");
        $database = (string) ($config['database'] ?? '');
        $username = (string) ($config['username'] ?? '');

        if ($database === '' || $username === '') {
            throw new RuntimeException('The MySQL backup connection is missing its database or username.');
        }

        $command = [
            (string) config('backup.mysqldump_path'),
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--no-tablespaces',
            '--host='.(string) ($config['host'] ?? '127.0.0.1'),
            '--port='.(string) ($config['port'] ?? 3306),
            '--user='.$username,
            '--result-file='.$target,
        ];

        if (! empty($config['unix_socket'])) {
            $command[] = '--socket='.(string) $config['unix_socket'];
        }

        $command[] = $database;

        $process = new Process($command, base_path(), [
            'MYSQL_PWD' => (string) ($config['password'] ?? ''),
        ]);
        $process->setTimeout((float) config('backup.timeout_seconds'));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('mysqldump failed: '.trim($process->getErrorOutput() ?: $process->getOutput()));
        }
    }

    /** @return list<array{file: string, bytes: int, sha256: string, linked_from_previous: bool}> */
    private function backupCmsFiles(string $destination, ?string $previous): array
    {
        $targetDirectory = $destination.DIRECTORY_SEPARATOR.'cms';
        $this->ensureDirectory($targetDirectory);
        $backedUp = [];

        foreach ((array) config('backup.cms_files') as $name) {
            $name = basename((string) $name);
            $source = storage_path('app/'.$name);
            if (! is_file($source)) {
                continue;
            }

            $target = $targetDirectory.DIRECTORY_SEPARATOR.$name;
            $previousFile = $previous ? $previous.DIRECTORY_SEPARATOR.'cms'.DIRECTORY_SEPARATOR.$name : null;
            $sha256 = (string) hash_file('sha256', $source);
            $linked = $this->copyOrLinkUnchanged($source, $target, $previousFile, $sha256);

            $backedUp[] = [
                'file' => $name,
                'bytes' => (int) filesize($target),
                'sha256' => $sha256,
                'linked_from_previous' => $linked,
            ];
        }

        usort($backedUp, fn (array $a, array $b) => $a['file'] <=> $b['file']);

        return $backedUp;
    }

    /** @return array{included: bool, files: int, bytes: int} */
    private function backupUploads(string $destination, ?string $previous): array
    {
        if (! config('backup.include_uploads')) {
            return ['included' => false, 'files' => 0, 'bytes' => 0];
        }

        $source = storage_path('app/public');
        if (! is_dir($source)) {
            return ['included' => true, 'files' => 0, 'bytes' => 0];
        }

        $target = $destination.DIRECTORY_SEPARATOR.'uploads';
        $this->ensureDirectory($target);
        $files = 0;
        $bytes = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            $relative = substr($item->getPathname(), strlen($source) + 1);
            $copyTo = $target.DIRECTORY_SEPARATOR.$relative;

            if ($item->isDir()) {
                $this->ensureDirectory($copyTo);

                continue;
            }

            $this->ensureDirectory(dirname($copyTo));
            $previousFile = $previous ? $previous.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$relative : null;
            $this->copyOrLinkUnchanged($item->getPathname(), $copyTo, $previousFile);

            $files++;
            $bytes += (int) $item->getSize();
        }

        return ['included' => true, 'files' => $files, 'bytes' => $bytes];
    }

    private function copyOrLinkUnchanged(
        string $source,
        string $target,
        ?string $previous,
        ?string $sourceHash = null,
    ): bool {
        if ($previous && is_file($previous) && filesize($source) === filesize($previous)) {
            $sourceHash ??= (string) hash_file('sha256', $source);
            $previousHash = (string) hash_file('sha256', $previous);

            if (hash_equals($sourceHash, $previousHash) && @link($previous, $target)) {
                return true;
            }
        }

        if (! copy($source, $target)) {
            throw new RuntimeException("Could not back up file [{$source}].");
        }

        return false;
    }

    private function pruneOldSnapshots(): void
    {
        $keep = (int) config('backup.keep');
        $snapshots = $this->snapshotDirectories();

        foreach (array_slice($snapshots, $keep) as $oldSnapshot) {
            $this->deleteDirectory($oldSnapshot);
        }
    }

    /** @return list<string> */
    private function snapshotDirectories(): array
    {
        $root = $this->backupRoot();
        if (! is_dir($root)) {
            return [];
        }

        $directories = array_values(array_filter(
            glob($root.DIRECTORY_SEPARATOR.'*') ?: [],
            fn (string $path) => is_dir($path) && is_file($path.DIRECTORY_SEPARATOR.'manifest.json'),
        ));
        rsort($directories, SORT_STRING);

        return $directories;
    }

    /** @return array<string, mixed> */
    private function readManifest(string $snapshot): array
    {
        $decoded = json_decode((string) file_get_contents($snapshot.DIRECTORY_SEPARATOR.'manifest.json'), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function backupRoot(): string
    {
        $path = trim((string) config('backup.directory'));
        if ($path === '') {
            throw new RuntimeException('CMS/CRM backup directory is not configured.');
        }

        return rtrim($path, '\\/');
    }

    private function ensureDirectory(string $path): void
    {
        if (! is_dir($path) && ! mkdir($path, 0750, true) && ! is_dir($path)) {
            throw new RuntimeException("Could not create backup directory [{$path}].");
        }
    }

    private function deleteDirectory(string $path, bool $allowPartial = false): void
    {
        $root = $this->backupRoot();
        $rootReal = realpath($root);
        $pathReal = realpath($path);

        if ($rootReal === false || $pathReal === false
            || ! str_starts_with($pathReal.DIRECTORY_SEPARATOR, $rootReal.DIRECTORY_SEPARATOR)
            || (! $allowPartial && ! is_file($pathReal.DIRECTORY_SEPARATOR.'manifest.json'))
        ) {
            throw new RuntimeException("Refusing to delete an invalid backup path [{$path}].");
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($pathReal, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            $removed = $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            if (! $removed) {
                throw new RuntimeException("Could not remove old backup item [{$item->getPathname()}].");
            }
        }

        if (! rmdir($pathReal)) {
            throw new RuntimeException("Could not remove old backup [{$pathReal}].");
        }
    }
}
