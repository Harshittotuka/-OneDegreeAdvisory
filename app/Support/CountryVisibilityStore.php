<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class CountryVisibilityStore
{
    public const GROUP_NON_MBBS = 'non_mbbs';

    public const GROUP_MBBS = 'mbbs';

    private string $path;

    private ?array $cache = null;

    public function __construct()
    {
        $this->path = storage_path('app/country-visibility.json');
    }

    public function get(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if (! is_file($this->path)) {
            return $this->cache = $this->defaults();
        }

        $data = json_decode((string) file_get_contents($this->path), true);

        return $this->cache = $this->normalize(is_array($data) ? $data : []);
    }

    public function hidden(string $group): array
    {
        $group = $this->normalizeGroupName($group);

        return $this->get()[$group]['hidden'] ?? [];
    }

    public function isVisible(string $group, string $slug): bool
    {
        $slug = $this->normalizeSlug($slug);

        return $slug === '' || ! in_array($slug, $this->hidden($group), true);
    }

    public function saveFromVisible(array $visibleByGroup, array $allSlugsByGroup): array
    {
        $hiddenByGroup = [];

        foreach ($this->groups() as $group) {
            $all = $this->uniqueSlugs($allSlugsByGroup[$group] ?? []);
            $visible = array_flip($this->uniqueSlugs($visibleByGroup[$group] ?? []));

            $hiddenByGroup[$group] = array_values(array_filter(
                $all,
                fn (string $slug): bool => ! isset($visible[$slug])
            ));
        }

        return $this->saveHidden($hiddenByGroup);
    }

    public function saveHidden(array $hiddenByGroup): array
    {
        $data = $this->defaults();

        foreach ($this->groups() as $group) {
            $data[$group]['hidden'] = $this->uniqueSlugs($hiddenByGroup[$group] ?? []);
        }

        $data['updated_at_utc'] = Carbon::now('UTC')->toIso8601String();
        $this->write($data);
        $this->cache = $data;

        return $data;
    }

    public function defaults(): array
    {
        return [
            self::GROUP_NON_MBBS => ['hidden' => []],
            self::GROUP_MBBS => ['hidden' => []],
            'updated_at_utc' => '',
        ];
    }

    private function normalize(array $data): array
    {
        $normalized = $this->defaults();

        foreach ($this->groups() as $group) {
            $normalized[$group]['hidden'] = $this->uniqueSlugs($data[$group]['hidden'] ?? []);
        }

        $normalized['updated_at_utc'] = (string) ($data['updated_at_utc'] ?? '');

        return $normalized;
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
            throw new \RuntimeException('Could not save the Country Visibility CMS data.');
        }

        app(CmsCrmBackupManager::class)->markDirty('cms-json');
    }

    private function groups(): array
    {
        return [self::GROUP_NON_MBBS, self::GROUP_MBBS];
    }

    private function normalizeGroupName(string $group): string
    {
        return in_array($group, $this->groups(), true) ? $group : self::GROUP_NON_MBBS;
    }

    private function uniqueSlugs(array $slugs): array
    {
        $seen = [];
        $clean = [];

        foreach ($slugs as $slug) {
            $slug = $this->normalizeSlug($slug);
            if ($slug === '' || isset($seen[$slug])) {
                continue;
            }

            $seen[$slug] = true;
            $clean[] = $slug;
        }

        sort($clean, SORT_NATURAL);

        return $clean;
    }

    private function normalizeSlug(mixed $slug): string
    {
        return strtolower(trim((string) $slug));
    }
}
