<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * File-backed store for the About page. Sections live in a single editable JSON
 * file so the CMS can create, edit, reorder and delete them with no database.
 * The file is seeded once from AboutContent::defaults().
 */
class AboutStore
{
    private string $path;

    public function __construct()
    {
        $this->path = storage_path('app/about-page.json');
    }

    /** All sections, in display order. */
    public function all(): array
    {
        if (! is_file($this->path)) {
            $seed = (new AboutContent)->defaults();
            $this->writeAll($seed);

            return $seed;
        }

        $data = json_decode((string) file_get_contents($this->path), true);

        return is_array($data) ? $data : [];
    }

    public function find(string $id): ?array
    {
        foreach ($this->all() as $section) {
            if (($section['id'] ?? null) === $id) {
                return $section;
            }
        }

        return null;
    }

    /**
     * Create or update a section. When $originalId matches an existing section the
     * record is updated in place (keeping its position); otherwise it is appended.
     */
    public function save(array $section, ?string $originalId = null): array
    {
        $sections = $this->all();
        $needle = $originalId ?? ($section['id'] ?? null);

        $index = null;
        foreach ($sections as $i => $existing) {
            if (($existing['id'] ?? null) === $needle) {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            $sections[] = $section;
        } else {
            $sections[$index] = $section;
        }

        $this->writeAll($sections);

        return $section;
    }

    public function setVisibility(string $id, bool $visible): void
    {
        $sections = $this->all();
        foreach ($sections as &$s) {
            if (($s['id'] ?? null) === $id) {
                $s['visible'] = $visible;
            }
        }
        unset($s);

        $this->writeAll($sections);
    }

    /** Reorder sections to match the given id order; any omitted keep their order at the end. */
    public function reorder(array $ids): void
    {
        $byId = [];
        foreach ($this->all() as $section) {
            $byId[$section['id'] ?? ''] = $section;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
                unset($byId[$id]);
            }
        }

        foreach ($byId as $section) {
            $ordered[] = $section;
        }

        $this->writeAll($ordered);
    }

    /** Overwrite the entire store with the given ordered list of sections. */
    public function replaceAll(array $sections): void
    {
        $this->writeAll($sections);
    }

    public function delete(string $id): void
    {
        $sections = array_values(array_filter(
            $this->all(),
            fn (array $s) => ($s['id'] ?? null) !== $id
        ));

        $this->writeAll($sections);
    }

    /** A URL-safe id unique across all sections except $ignore (the one being edited). */
    public function uniqueId(string $desired, ?string $ignore = null): string
    {
        $base = Str::slug($desired) ?: 'section';
        $taken = array_filter(
            array_column($this->all(), 'id'),
            fn ($s) => $s !== $ignore
        );

        $id = $base;
        $n = 2;
        while (in_array($id, $taken, true)) {
            $id = $base.'-'.$n;
            $n++;
        }

        return $id;
    }

    private function writeAll(array $sections): void
    {
        if (! is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0775, true);
        }

        $written = file_put_contents(
            $this->path,
            json_encode(array_values($sections), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        if ($written === false) {
            throw new \RuntimeException('Could not save the About CMS data.');
        }

        app(CmsCrmBackupManager::class)->markDirty('cms-json');
    }
}
