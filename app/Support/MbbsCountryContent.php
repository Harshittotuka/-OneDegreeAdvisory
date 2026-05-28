<?php

namespace App\Support;

class MbbsCountryContent
{
    private const DEFAULT_PATH = 'app/mbbs_bookmyuniversity_content.json';

    public function forSlug(string $slug): array
    {
        $sheets = $this->loadSheets();

        $page = $this->firstForSlug($sheets['Pages'] ?? [], $slug);
        $sections = $this->indexBy(
            $this->rowsForSlug($sheets['Sections'] ?? [], $slug),
            'section_key'
        );
        $bullets = $this->groupBy(
            $this->rowsForSlug($sheets['Bullets'] ?? [], $slug),
            'section_key',
            'bullet_order'
        );
        $subpoints = $this->rowsForSlug($sheets['Subpoints'] ?? [], $slug);
        usort($subpoints, fn ($a, $b) => ((int) ($a['subpoint_order'] ?? 0)) <=> ((int) ($b['subpoint_order'] ?? 0)));
        $facts = $this->rowsForSlug($sheets['Facts'] ?? [], $slug);
        usort($facts, fn ($a, $b) => ((int) ($a['fact_order'] ?? 0)) <=> ((int) ($b['fact_order'] ?? 0)));

        $admissionSteps = $this->rowsForSlug($sheets['AdmissionSteps'] ?? [], $slug);
        usort($admissionSteps, fn ($a, $b) => ((int) ($a['step_order'] ?? 0)) <=> ((int) ($b['step_order'] ?? 0)));

        return [
            'page' => $page,
            'sections' => $sections,
            'bullets' => $bullets,
            'subpoints' => $subpoints,
            'facts' => $facts,
            'admissionSteps' => $admissionSteps,
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
