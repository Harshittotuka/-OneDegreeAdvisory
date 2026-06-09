<?php

namespace App\Support;

class MbbsCountryContent
{
    private const DEFAULT_PATH = 'app/mbbs_avglobal_content.json';

    public function __construct(private CountryVisibilityStore $visibility)
    {
    }

    public function countries(bool $visibleOnly = true): array
    {
        $sheets = $this->loadSheets();
        $pages = $sheets['Pages'] ?? [];
        $factsBySlug = [];
        foreach ($sheets['Facts'] ?? [] as $fact) {
            if (! is_array($fact)) {
                continue;
            }
            $slug = (string) ($fact['page_slug'] ?? '');
            $label = (string) ($fact['fact_label'] ?? '');
            if ($slug === '' || $label === '') {
                continue;
            }
            $factsBySlug[$slug][$label] = (string) ($fact['fact_value'] ?? '');
        }

        $countries = [];
        foreach ($pages as $page) {
            if (! is_array($page)) {
                continue;
            }

            $slug = (string) ($page['page_slug'] ?? '');
            $name = (string) ($page['country'] ?? '');
            if ($slug === '' || $name === '') {
                continue;
            }

            $countries[] = [
                'slug' => $slug,
                'name' => $name,
                'flag' => strtolower((string) ($page['flag_code'] ?? '')),
                'flag_url' => (string) ($page['flag_url'] ?? ''),
                'hero_image' => (string) ($page['hero_image'] ?? ''),
                'hero_heading' => (string) ($page['hero_heading'] ?? ''),
                'hero_text' => (string) ($page['hero_text'] ?? ''),
                'hero_badge' => (string) ($page['hero_badge'] ?? ''),
                'source_updated' => (string) ($page['source_updated'] ?? ''),
                'facts' => $factsBySlug[$slug] ?? [],
            ];
        }

        if ($visibleOnly) {
            $countries = array_values(array_filter(
                $countries,
                fn (array $country): bool => $this->visibility->isVisible(
                    CountryVisibilityStore::GROUP_MBBS,
                    (string) ($country['slug'] ?? '')
                )
            ));
        }

        usort($countries, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $countries;
    }

    public function allCountries(): array
    {
        return $this->countries(false);
    }

    public function isVisible(string $slug): bool
    {
        return $this->visibility->isVisible(CountryVisibilityStore::GROUP_MBBS, $slug);
    }

    public function forSlug(string $slug): array
    {
        $sheets = $this->loadSheets();

        $page = $this->firstForSlug($sheets['Pages'] ?? [], $slug);

        $sections = $this->rowsForSlug($sheets['Sections'] ?? [], $slug);
        usort($sections, fn ($a, $b) => ((int) ($a['section_order'] ?? 0)) <=> ((int) ($b['section_order'] ?? 0)));

        $subpoints = $this->groupBy(
            $this->rowsForSlug($sheets['Subpoints'] ?? [], $slug),
            'section_key',
            'subpoint_order'
        );

        $facts = $this->rowsForSlug($sheets['Facts'] ?? [], $slug);
        usort($facts, fn ($a, $b) => ((int) ($a['fact_order'] ?? 0)) <=> ((int) ($b['fact_order'] ?? 0)));

        $neet = $this->rowsForSlug($sheets['Neet'] ?? [], $slug);
        usort($neet, fn ($a, $b) => ((int) ($a['neet_order'] ?? 0)) <=> ((int) ($b['neet_order'] ?? 0)));

        $neetTrend = $this->rowsForSlug($sheets['NeetTrend'] ?? [], $slug);
        usort($neetTrend, fn ($a, $b) => ((int) ($a['trend_order'] ?? 0)) <=> ((int) ($b['trend_order'] ?? 0)));

        return [
            'page' => $page,
            'country' => $this->countryMetaFromPage($page),
            'sections' => $this->indexBy($sections, 'section_key'),
            'subpoints' => $subpoints,
            'facts' => $facts,
            'neet' => $neet,
            'neetTrend' => $neetTrend,
        ];
    }

    private function loadSheets(): array
    {
        $path = storage_path(self::DEFAULT_PATH);

        if (! is_file($path)) {
            return [];
        }

        $payload = json_decode((string) file_get_contents($path), true);

        return is_array($payload['sheets'] ?? null) ? $payload['sheets'] : [];
    }

    private function rowsForSlug(array $rows, string $slug): array
    {
        return array_values(array_filter(
            $rows,
            fn (array $row) => ($row['page_slug'] ?? '') === $slug
        ));
    }

    private function countryMetaFromPage(array $page): array
    {
        return [
            'slug' => (string) ($page['page_slug'] ?? ''),
            'name' => (string) ($page['country'] ?? ''),
            'flag' => strtolower((string) ($page['flag_code'] ?? '')),
            'flag_url' => (string) ($page['flag_url'] ?? ''),
            'hero_image' => (string) ($page['hero_image'] ?? ''),
            'hero_badge' => (string) ($page['hero_badge'] ?? ''),
            'source_updated' => (string) ($page['source_updated'] ?? ''),
        ];
    }

    private function firstForSlug(array $rows, string $slug): array
    {
        foreach ($rows as $row) {
            if (($row['page_slug'] ?? '') === $slug) {
                return $row;
            }
        }

        return [];
    }

    private function indexBy(array $rows, string $key): array
    {
        $output = [];
        foreach ($rows as $row) {
            $value = (string) ($row[$key] ?? '');
            if ($value !== '') {
                $output[$value] = $row;
            }
        }

        return $output;
    }

    private function groupBy(array $rows, string $key, string $orderField): array
    {
        $output = [];
        foreach ($rows as $row) {
            $value = (string) ($row[$key] ?? '');
            if ($value === '') {
                continue;
            }
            $output[$value][] = $row;
        }
        foreach ($output as &$group) {
            usort($group, fn ($a, $b) => ((int) ($a[$orderField] ?? 0)) <=> ((int) ($b[$orderField] ?? 0)));
        }

        return $output;
    }
}
