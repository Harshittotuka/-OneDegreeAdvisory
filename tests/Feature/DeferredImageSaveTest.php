<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DeferredImageSaveTest extends TestCase
{
    /** 1x1 transparent PNG. */
    private const PNG_B64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    private array $backups = [];
    private array $imagesBefore = [];

    private function dir(string $folder): string
    {
        return storage_path('app/public/'.$folder);
    }

    private function snapshotFile(string $path): void
    {
        $this->backups[$path] = is_file($path) ? file_get_contents($path) : null;
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Protect the real file-backed content stores.
        $this->snapshotFile(storage_path('app/home-hero.json'));
        $this->snapshotFile(storage_path('app/about-page.json'));
        // Record existing upload files so we can delete only what a test creates.
        foreach (['home-hero', 'about'] as $folder) {
            $this->imagesBefore[$folder] = is_dir($this->dir($folder)) ? glob($this->dir($folder).'/*') : [];
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->backups as $path => $contents) {
            if ($contents !== null) {
                file_put_contents($path, $contents);
            }
        }
        // Remove any image files the test wrote to the public disk.
        foreach (['home-hero', 'about'] as $folder) {
            $now = is_dir($this->dir($folder)) ? glob($this->dir($folder).'/*') : [];
            foreach (array_diff($now, $this->imagesBefore[$folder]) as $created) {
                @unlink($created);
            }
        }
        parent::tearDown();
    }

    public function test_home_hero_data_url_is_written_to_storage_only_on_save(): void
    {
        $before = $this->imagesBefore['home-hero'];
        $dataUrl = 'data:image/png;base64,'.self::PNG_B64;

        $this->withSession(['cms_authenticated' => true]);
        $this->post(route('admin.home-hero.live.save'), ['data' => ['background' => $dataUrl]])
            ->assertOk()
            ->assertJson(['ok' => true]);

        // The saved JSON must reference a real file URL, never the data URL.
        $saved = json_decode((string) file_get_contents(storage_path('app/home-hero.json')), true);
        $this->assertStringStartsWith('http', $saved['background']);
        $this->assertStringContainsString('/storage/home-hero/', $saved['background']);
        $this->assertStringNotContainsString('data:image', $saved['background']);

        // Exactly one new file landed on disk, and it decodes to the PNG.
        $after = glob($this->dir('home-hero').'/*');
        $created = array_values(array_diff($after, $before));
        $this->assertCount(1, $created);
        $this->assertSame(base64_decode(self::PNG_B64), file_get_contents($created[0]));
    }

    public function test_about_data_url_is_written_to_storage_only_on_save(): void
    {
        $before = $this->imagesBefore['about'];
        $dataUrl = 'data:image/png;base64,'.self::PNG_B64;

        $this->withSession(['cms_authenticated' => true, 'cms_super_admin' => true]);

        // A single "pillars" section whose first pillar carries a freshly-cropped image.
        $sections = [[
            'id' => 'pillars',
            'type' => 'pillars',
            'visible' => true,
            'data' => ['items' => [['heading' => 'X', 'image' => $dataUrl]]],
        ]];

        $this->post(route('admin.about.live.save'), ['sections' => $sections])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $saved = json_decode((string) file_get_contents(storage_path('app/about-page.json')), true);
        $img = $saved[0]['data']['items'][0]['image'] ?? '';
        $this->assertStringContainsString('/storage/about/', $img);
        $this->assertStringNotContainsString('data:image', $img);

        $after = glob($this->dir('about').'/*');
        $this->assertCount(1, array_values(array_diff($after, $before)));
    }

    public function test_import_url_returns_a_data_url_and_writes_nothing(): void
    {
        $before = $this->imagesBefore['home-hero'];
        Http::fake([
            '*' => Http::response(base64_decode(self::PNG_B64), 200, ['Content-Type' => 'image/png']),
        ]);

        $this->withSession(['cms_authenticated' => true]);
        $resp = $this->post(route('admin.home-hero.import'), ['url' => 'https://example.com/photo.png'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertStringStartsWith('data:image/png;base64,', $resp->json('url'));

        // Importing for a crop must not write anything to disk.
        $after = glob($this->dir('home-hero').'/*');
        $this->assertSame($before, $after);
    }

    public function test_save_without_image_change_adds_no_files(): void
    {
        $before = $this->imagesBefore['home-hero'];

        $this->withSession(['cms_authenticated' => true]);
        // Re-saving with an existing http(s) URL (not a data URL) writes no file.
        $this->post(route('admin.home-hero.live.save'), ['data' => ['background' => 'https://images.example/x.jpg']])
            ->assertOk();

        $this->assertSame($before, glob($this->dir('home-hero').'/*'));
    }
}
