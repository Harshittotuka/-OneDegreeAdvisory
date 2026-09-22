<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class MbbsCountryContent
{
    private const DEFAULT_PATH = 'app/mbbs_avglobal_content.json';

    public function __construct(private CountryVisibilityStore $visibility)
    {
    }

    /**
     * The nav's MBBS dropdown, on every page. Same reasoning and the same
     * invalidation as StudyLocationContent::destinations(): the key carries the
     * content file and the visibility file, so a sync or a toggle is picked up.
     */
    public function countries(bool $visibleOnly = true): array
    {
        return Cache::remember(
            'nav:mbbs-countries:'.md5(implode('|', [
                self::fingerprint(storage_path(self::DEFAULT_PATH)),
                self::fingerprint(storage_path('app/country-visibility.json')),
                $visibleOnly ? 'visible' : 'all',
            ])),
            now()->addDay(),
            fn () => $this->computeCountries($visibleOnly)
        );
    }

    /** mtime+size of a file, or 'none' — enough to notice any rewrite. */
    private static function fingerprint(string $path): string
    {
        clearstatcache(true, $path);

        return is_file($path) ? filemtime($path).'-'.filesize($path) : 'none';
    }

    /**
     * The partner's brand, stripped from the page title only.
     *
     * The MBBS pages are scraped from avglobaloverseas.com, and the title came
     * through as "MBBS in Georgia … | NMC Approved Guide | AV Global". That put
     * another company's name in our search result, and pushed ours past the
     * 90-character title cap so it rendered as "One Degree Adv".
     *
     * Deliberately the title and nothing else. The scraped body copy also names
     * that company — "AV Global owns and manages student hostels near partner
     * universities in Tbilisi", "an AV Global coordinator living on site" — and
     * those are statements about who runs a hostel, not branding. Swapping the
     * name in them would turn someone else's claim into a false claim of ours,
     * which is worse than leaving it visible. They need an editorial decision
     * about the real arrangement; see Part 5 of SEO-MARKETING-PLAN.md.
     */
    private function withoutPartnerBrandInTitle(string $title): string
    {
        $title = preg_replace('/\s*[|\x{2013}\x{2014}-]\s*AV\s*Global(?:\s+Overseas)?\s*/iu', '', $title) ?? $title;
        $title = trim(preg_replace('/\s{2,}/', ' ', $title) ?? $title, " \t\n\r\0\x0B|-");

        // These titles arrive as several "|"-separated claims and run past 80
        // characters before our own name is appended, so the name was being cut
        // off the end ("… | One Degree Adv"). Keep whole leading segments only
        // while they still leave room for it. At least one segment always
        // survives, even a long one — a slightly long title is recoverable, a
        // title that does not say whose site it is is not.
        $room = 60 - mb_strlen(' | '.config('site.name'));
        // "|" or a spaced dash, since the source uses both ("… 2026 - Fees,
        // Admission, Top Universities"). The spaces matter: they keep a date
        // range like "2026-27" in one piece.
        $segments = array_values(array_filter(
            array_map('trim', preg_split('/\s*\|\s*|\s+[-\x{2013}\x{2014}]\s+/u', $title) ?: []),
            fn ($s) => $s !== ''
        ));

        if ($segments === []) {
            return $title;
        }

        $kept = array_shift($segments);
        foreach ($segments as $segment) {
            if (mb_strlen($kept.' | '.$segment) > $room) {
                break;
            }
            $kept .= ' | '.$segment;
        }

        return $kept;
    }

    private function computeCountries(bool $visibleOnly): array
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

        if (trim((string) ($page['page_title'] ?? '')) !== '') {
            $page['page_title'] = $this->withoutPartnerBrandInTitle((string) $page['page_title']);
        }

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

    /**
     * Decoded sheets, kept for the rest of the request — the MBBS file is
     * ~260 KB and the nav decodes it on every page. Keyed on mtime and size so
     * a fresh MBBS sync is picked up rather than served stale.
     *
     * @var array<string, array>
     */
    private static array $sheetCache = [];

    private function loadSheets(): array
    {
        $path = storage_path(self::DEFAULT_PATH);

        if (! is_file($path)) {
            return [];
        }

        $key = $path.':'.filemtime($path).':'.filesize($path);

        if (! isset(self::$sheetCache[$key])) {
            $payload = json_decode((string) file_get_contents($path), true);
            self::$sheetCache = [$key => is_array($payload['sheets'] ?? null) ? $payload['sheets'] : []];
        }

        return self::$sheetCache[$key];
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
