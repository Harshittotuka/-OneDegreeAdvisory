<?php

namespace App\Support;

/**
 * File-backed store for the Destinations mega-menu grid layout. Lets the CMS
 * tune how the country-guide / MBBS cards are arranged in the nav dropdown —
 * column count, the gap between cards, and the overall panel width — with no
 * database. The values are applied as CSS custom properties on the dropdown in
 * resources/views/partials/header-stripe.blade.php and consumed by the
 * .nav-dropdown-grid rule (public/styles.css) + the destinations section width
 * (public/stripe-nav.css). Defaults mirror those CSS baselines.
 */
class DestinationsLayoutStore
{
    private string $path;

    /** Memo so repeated get() calls on one instance read disk once. */
    private ?array $cache = null;

    public function __construct()
    {
        $this->path = storage_path('app/destinations-layout.json');
    }

    /** The full layout config, with defaults filled in for any missing keys. */
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

        $columns = (int) ($data['columns'] ?? $defaults['columns']);
        $columns = max(2, min(6, $columns));

        $gap = (int) ($data['gap'] ?? $defaults['gap']);
        $gap = max(2, min(24, $gap));

        $width = (int) ($data['width'] ?? $defaults['width']);
        $width = max(680, min(1280, $width));

        return [
            'columns' => $columns,
            'gap' => $gap,
            'width' => $width,
        ];
    }

    public function defaults(): array
    {
        // Mirrors the CSS baseline: .nav-dropdown-grid is repeat(3, …) at gap 5px
        // (public/styles.css) inside a 940px destinations panel (public/stripe-nav.css).
        return [
            'columns' => 3,
            'gap' => 5,
            'width' => 940,
        ];
    }

    private function write(array $data): void
    {
        if (! is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0775, true);
        }

        $written = file_put_contents(
            $this->path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        if ($written === false) {
            throw new \RuntimeException('Could not save the Destinations Layout CMS data.');
        }

        app(CmsCrmBackupManager::class)->markDirty('cms-json');
    }
}
