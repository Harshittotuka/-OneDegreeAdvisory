<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * File-backed store for the top blue notice bar. The whole bar — its display
 * variant, marquee speed, teaser length and the list of announcement items —
 * lives in a single editable JSON file so the CMS can manage it with no
 * database. Seeded once from config('site.notices').
 */
class NoticeBarStore
{
    private string $path;

    /** Memo so repeated get()/visibleItems() calls on one instance read disk once. */
    private ?array $cache = null;

    public function __construct()
    {
        $this->path = storage_path('app/notice-bar.json');
    }

    /** The full notice-bar config, with defaults filled in for any missing keys. */
    public function get(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if (! is_file($this->path)) {
            $seed = $this->defaults();
            $this->write($seed);

            return $this->cache = $seed;
        }

        $data = json_decode((string) file_get_contents($this->path), true);

        return $this->cache = $this->normalize(is_array($data) ? $data : []);
    }

    /** Only the items that should appear on the public site. */
    public function visibleItems(): array
    {
        return array_values(array_filter(
            $this->get()['items'],
            fn (array $item) => $item['visible'] ?? true
        ));
    }

    public function save(array $data): array
    {
        $clean = $this->normalize($data);
        $this->write($clean);
        $this->cache = $clean;

        return $clean;
    }

    /** Coerce a raw array into the canonical shape with sane bounds. */
    private function normalize(array $data): array
    {
        $defaults = $this->defaults();

        $variant = (string) ($data['variant'] ?? $defaults['variant']);
        if (! in_array($variant, ['original', 'minimal', 'compact'], true)) {
            $variant = 'original';
        }

        $wordCount = (int) ($data['word_count'] ?? $defaults['word_count']);
        $wordCount = max(0, min(50, $wordCount));

        $speed = (int) ($data['speed'] ?? $defaults['speed']);
        $speed = max(5, min(120, $speed));

        $items = [];
        foreach ($data['items'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $text = trim((string) ($item['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $items[] = [
                'text' => $text,
                'href' => trim((string) ($item['href'] ?? '')),
                'visible' => (bool) ($item['visible'] ?? true),
            ];
        }

        return [
            'variant' => $variant,
            'word_count' => $wordCount,
            'speed' => $speed,
            'items' => $items,
        ];
    }

    /** Seed data, derived from the legacy config('site.notices') list. */
    public function defaults(): array
    {
        $items = [];
        foreach (config('site.notices', []) as $notice) {
            $text = trim((string) (is_array($notice) ? ($notice['text'] ?? '') : $notice));
            if ($text === '') {
                continue;
            }

            $href = '';
            if (is_array($notice)) {
                if (! empty($notice['route']) && Route::has($notice['route'])) {
                    $href = route($notice['route'], [], false);
                } elseif (! empty($notice['href'])) {
                    $href = (string) $notice['href'];
                }
            }

            $items[] = ['text' => $text, 'href' => $href, 'visible' => true];
        }

        return [
            'variant' => 'original',
            'word_count' => 5,
            'speed' => 14,
            'items' => $items,
        ];
    }

    private function write(array $data): void
    {
        if (! is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0775, true);
        }

        file_put_contents(
            $this->path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }
}
