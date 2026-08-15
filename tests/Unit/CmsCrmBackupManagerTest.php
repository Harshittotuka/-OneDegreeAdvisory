<?php

namespace Tests\Unit;

use App\Support\CmsCrmBackupManager;
use Illuminate\Support\Facades\DB;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class CmsCrmBackupManagerTest extends TestCase
{
    private string $testRoot;

    private string $databasePath;

    private string $cmsFileName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testRoot = storage_path('framework/testing/cms-crm-backup-'.bin2hex(random_bytes(4)));
        $this->databasePath = $this->testRoot.DIRECTORY_SEPARATOR.'source.sqlite';
        $this->cmsFileName = 'backup-test-'.bin2hex(random_bytes(4)).'.json';
        mkdir($this->testRoot, 0750, true);
        touch($this->databasePath);
        file_put_contents(storage_path('app/'.$this->cmsFileName), '{"cms":"kept"}');

        config([
            'backup.enabled' => true,
            'backup.keep' => 5,
            'backup.directory' => $this->testRoot.DIRECTORY_SEPARATOR.'snapshots',
            'backup.include_uploads' => false,
            'backup.cms_files' => [$this->cmsFileName],
            'backup.database_connection' => 'backup_test',
            'database.connections.backup_test' => [
                'driver' => 'sqlite',
                'database' => $this->databasePath,
                'prefix' => '',
                'foreign_key_constraints' => true,
                'busy_timeout' => null,
                'journal_mode' => null,
                'synchronous' => null,
                'transaction_mode' => 'DEFERRED',
            ],
        ]);

        DB::purge('backup_test');
        DB::connection('backup_test')->statement('CREATE TABLE backup_example (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
        DB::connection('backup_test')->table('backup_example')->insert(['name' => 'kept']);
    }

    protected function tearDown(): void
    {
        DB::disconnect('backup_test');
        DB::purge('backup_test');
        @unlink(storage_path('app/'.$this->cmsFileName));
        $this->removeDirectory($this->testRoot);

        parent::tearDown();
    }

    public function test_it_creates_a_verified_sqlite_snapshot(): void
    {
        $path = app(CmsCrmBackupManager::class)->createSnapshot('unit-test');

        $this->assertFileExists($path.'/database.sqlite');
        $this->assertFileExists($path.'/manifest.json');
        $this->assertFileExists($path.'/cms/'.$this->cmsFileName);

        $manifest = json_decode((string) file_get_contents($path.'/manifest.json'), true);
        $this->assertSame('sqlite', $manifest['database']['driver']);
        $this->assertSame('unit-test', $manifest['reason']);
        $this->assertSame(
            hash_file('sha256', $path.'/database.sqlite'),
            $manifest['database']['sha256'],
        );

        $snapshot = new \PDO('sqlite:'.$path.'/database.sqlite');
        $this->assertSame('kept', $snapshot->query('SELECT name FROM backup_example')->fetchColumn());
    }

    public function test_it_keeps_only_the_five_newest_snapshots(): void
    {
        $manager = app(CmsCrmBackupManager::class);
        $first = $manager->createSnapshot('first');

        for ($i = 2; $i <= 6; $i++) {
            usleep(2000);
            $manager->createSnapshot('snapshot-'.$i);
        }

        $this->assertCount(5, $manager->snapshots());
        $this->assertDirectoryDoesNotExist($first);
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($path);
    }
}
