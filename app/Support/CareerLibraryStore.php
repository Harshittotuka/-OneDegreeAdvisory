<?php

namespace App\Support;

/**
 * File-backed store for the Global Career Library (/global-career-library) —
 * a fully self-contained careers explorer with no external dependency.
 *
 * One JSON file holds everything the CMS manages: page settings (hero copy,
 * contact strip, next-steps link) and the career list. Each career carries
 * its tile look (icon + tailwind colour classes), flags (trending / visible)
 * and one or more data variants keyed "Country|Language" (e.g.
 * "India|English") holding the full report payload (seo, introduction,
 * whoShouldPursue, workNature, eligibility, stats, pathways, option groups,
 * videoRecommendations).
 *
 * The file is seeded from resources/data/career-library-seed.json (tracked in
 * git) because storage/app JSON is gitignored per environment.
 */
class CareerLibraryStore
{
    /** Search-form country list (code => name), verbatim from the source page. */
    public const COUNTRIES = [
        'IN' => 'India', 'US' => 'United States', 'CA' => 'Canada', 'GB' => 'United Kingdom',
        'AU' => 'Australia', 'DE' => 'Germany', 'FR' => 'France', 'AE' => 'United Arab Emirates',
        'JP' => 'Japan', 'SG' => 'Singapore', 'CN' => 'China', 'BR' => 'Brazil',
        'ZA' => 'South Africa', 'RU' => 'Russia', 'IT' => 'Italy', 'ES' => 'Spain',
        'NL' => 'Netherlands', 'SE' => 'Sweden', 'CH' => 'Switzerland', 'NZ' => 'New Zealand',
        'MX' => 'Mexico', 'ID' => 'Indonesia', 'SA' => 'Saudi Arabia', 'TR' => 'Turkey',
        'KR' => 'South Korea', 'TH' => 'Thailand', 'MY' => 'Malaysia', 'VN' => 'Vietnam',
        'PH' => 'Philippines', 'EG' => 'Egypt', 'NG' => 'Nigeria', 'KE' => 'Kenya',
        'AR' => 'Argentina', 'PL' => 'Poland', 'IE' => 'Ireland', 'MK' => 'North Macedonia',
        'LK' => 'Sri Lanka', 'ZW' => 'Zimbabwe', 'QA' => 'Qatar', 'UG' => 'Uganda',
    ];

    /** Language name => BCP-47 code used in detail URLs, verbatim from the source page. */
    public const LANGUAGE_CODES = [
        'English' => 'en-IN', 'Hindi' => 'hi-IN', 'Marathi' => 'mr-IN', 'Punjabi' => 'pa-IN',
        'Spanish' => 'es-ES', 'French' => 'fr-FR', 'German' => 'de-DE', 'Bengali' => 'bn-IN',
        'Tamil' => 'ta-IN', 'Telugu' => 'te-IN', 'Macedonian' => 'mk-MK', 'Arabic' => 'ar-SA',
        'Sinhala' => 'si-LK', 'Manipuri' => 'mni-IN',
    ];

    /** Tile icon keys that exist in CareerLibraryIcons::MAP. */
    public const ICON_TYPES = [
        'search', 'mapPin', 'globe', 'sparkles', 'compass', 'clock', 'target', 'barChart',
        'trendingUp', 'users', 'briefcase', 'checkCircle', 'chevronRight', 'zap', 'cpu',
        'chevronDown', 'arrowRight', 'building', 'youtube', 'playCircle', 'gradHat',
        'generic', 'volumeHigh', 'calendar', 'square',
    ];

    /** The default data variant every career should have. */
    public const DEFAULT_VARIANT = 'India|English';

    private string $path;

    /** Memo so repeated get() calls on one instance read disk once. */
    private ?array $cache = null;

    public function __construct()
    {
        $this->path = storage_path('app/career-library.json');
    }

    /** The full library (settings + careers), seeding from the repo file on first use. */
    public function get(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if (! is_file($this->path)) {
            $seed = $this->readSeed();
            $this->write($this->path, $seed);

            return $this->cache = $seed;
        }

        $data = json_decode((string) file_get_contents($this->path), true);

        return $this->cache = $this->normalize(is_array($data) ? $data : []);
    }

    public function save(array $data): array
    {
        $clean = $this->normalize($data);
        $this->write($this->path, $clean);

        return $this->cache = $clean;
    }

    public function settings(): array
    {
        return $this->get()['settings'];
    }

    public function updateSettings(array $settings): array
    {
        $data = $this->get();
        $data['settings'] = array_merge($data['settings'], $settings);

        return $this->save($data);
    }

    /** All careers, in stored (display) order. */
    public function careers(): array
    {
        return $this->get()['careers'];
    }

    /** Careers shown on the public landing grid, in stored order. */
    public function visibleCareers(): array
    {
        return array_values(array_filter($this->careers(), fn (array $c) => $c['visible'] ?? true));
    }

    public function findBySlug(string $slug): ?array
    {
        foreach ($this->careers() as $career) {
            if ($career['slug'] === $slug) {
                return $career;
            }
        }

        return null;
    }

    /**
     * Find a career by its display name, case-insensitively. Detail URLs carry
     * the title with spaces turned into hyphens ("Data-Science"), so both the
     * raw name and the hyphenated form are matched.
     */
    public function findByName(string $name): ?array
    {
        $needle = mb_strtolower(trim(str_replace('-', ' ', $name)));

        foreach ($this->careers() as $career) {
            if (mb_strtolower($career['title']) === $needle) {
                return $career;
            }
        }

        return null;
    }

    /**
     * The report payload for a career in a given country/language, falling back
     * to the default variant and then to whichever variant exists. Returns null
     * only when the career has no data at all.
     */
    public function variantFor(array $career, string $country, string $language): ?array
    {
        $data = $career['data'] ?? [];

        return $data["{$country}|{$language}"]
            ?? $data[self::DEFAULT_VARIANT]
            ?? (reset($data) ?: null);
    }

    public function upsertCareer(array $career): array
    {
        $data = $this->get();
        $replaced = false;

        foreach ($data['careers'] as $i => $existing) {
            if ($existing['slug'] === $career['slug']) {
                $data['careers'][$i] = $career;
                $replaced = true;
                break;
            }
        }

        if (! $replaced) {
            $data['careers'][] = $career;
        }

        return $this->save($data);
    }

    public function deleteCareer(string $slug): array
    {
        $data = $this->get();
        $data['careers'] = array_values(array_filter(
            $data['careers'],
            fn (array $c) => $c['slug'] !== $slug
        ));

        return $this->save($data);
    }

    /** Reorder careers to the given slug order; unknown slugs keep their spot at the end. */
    public function reorder(array $slugs): array
    {
        $data = $this->get();
        $bySlug = [];
        foreach ($data['careers'] as $career) {
            $bySlug[$career['slug']] = $career;
        }

        $ordered = [];
        foreach ($slugs as $slug) {
            if (isset($bySlug[$slug])) {
                $ordered[] = $bySlug[$slug];
                unset($bySlug[$slug]);
            }
        }

        $data['careers'] = array_merge($ordered, array_values($bySlug));

        return $this->save($data);
    }

    public static function slugify(string $title): string
    {
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));

        return $slug !== '' ? $slug : 'career';
    }

    /* ── Normalisation ── */

    private function readSeed(): array
    {
        $seedPath = resource_path('data/career-library-seed.json');
        $seed = is_file($seedPath)
            ? json_decode((string) file_get_contents($seedPath), true)
            : [];

        return $this->normalize(is_array($seed) ? $seed : []);
    }

    private function normalize(array $data): array
    {
        $settingsIn = is_array($data['settings'] ?? null) ? $data['settings'] : [];
        $defaults = $this->defaultSettings();

        $text = static function (array $src, string $key, string $default, int $max = 300): string {
            $value = array_key_exists($key, $src) ? (string) ($src[$key] ?? '') : $default;

            return mb_substr(trim($value), 0, $max);
        };

        $settings = [
            'hero_title_prefix' => $text($settingsIn, 'hero_title_prefix', $defaults['hero_title_prefix'], 120),
            'hero_title_highlight' => $text($settingsIn, 'hero_title_highlight', $defaults['hero_title_highlight'], 120),
            'hero_subtitle' => $text($settingsIn, 'hero_subtitle', $defaults['hero_subtitle'], 300),
            'search_placeholder' => $text($settingsIn, 'search_placeholder', $defaults['search_placeholder'], 120),
            'trending_heading' => $text($settingsIn, 'trending_heading', $defaults['trending_heading'], 120),
            'explore_button' => $text($settingsIn, 'explore_button', $defaults['explore_button'], 120),
            'contact_email' => $text($settingsIn, 'contact_email', $defaults['contact_email'], 120),
            'contact_phone' => $text($settingsIn, 'contact_phone', $defaults['contact_phone'], 40),
            'next_steps_url' => $text($settingsIn, 'next_steps_url', $defaults['next_steps_url'], 300),
            'report_year' => $text($settingsIn, 'report_year', $defaults['report_year'], 8),
        ];

        $careers = [];
        $seen = [];
        foreach ($data['careers'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $title = mb_substr(trim((string) ($row['title'] ?? '')), 0, 120);
            if ($title === '') {
                continue;
            }

            $slug = self::slugify((string) ($row['slug'] ?? $title));
            if (isset($seen[$slug])) {
                continue;
            }
            $seen[$slug] = true;

            $iconType = (string) ($row['iconType'] ?? 'generic');
            if (! in_array($iconType, self::ICON_TYPES, true)) {
                $iconType = 'generic';
            }

            $variants = [];
            foreach ((array) ($row['data'] ?? []) as $key => $variant) {
                if (is_array($variant) && preg_match('/^[^|]+\|[^|]+$/', (string) $key)) {
                    $variants[(string) $key] = $this->normalizeCareerData($variant);
                }
            }

            $careers[] = [
                'slug' => $slug,
                'title' => $title,
                'iconType' => $iconType,
                // Tailwind utility pairs for the tile (e.g. bg-indigo-100 / text-indigo-600).
                'bg' => mb_substr(trim((string) ($row['bg'] ?? 'bg-indigo-100')), 0, 40),
                'text' => mb_substr(trim((string) ($row['text'] ?? 'text-indigo-600')), 0, 40),
                'trending' => (bool) ($row['trending'] ?? false),
                'visible' => (bool) ($row['visible'] ?? true),
                'data' => $variants,
            ];
        }

        return ['settings' => $settings, 'careers' => $careers];
    }

    /**
     * Coerce one report payload into the canonical shape the templates expect.
     * Every field is optional in the CMS; the blade side tolerates empties.
     */
    public function normalizeCareerData(array $d): array
    {
        $str = fn ($v, int $max = 2000) => mb_substr(trim((string) ($v ?? '')), 0, $max);
        $strList = function ($list, int $max = 600) use ($str): array {
            $out = [];
            foreach ((array) $list as $item) {
                $s = $str($item, $max);
                if ($s !== '') {
                    $out[] = $s;
                }
            }

            return $out;
        };
        $titleDescList = function ($list) use ($str): array {
            $out = [];
            foreach ((array) $list as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $title = $str($item['title'] ?? '', 200);
                if ($title === '') {
                    continue;
                }
                $out[] = ['title' => $title, 'description' => $str($item['description'] ?? '', 1200)];
            }

            return $out;
        };

        $seoIn = is_array($d['seo'] ?? null) ? $d['seo'] : [];
        $faqs = [];
        foreach ((array) ($seoIn['faqs'] ?? []) as $faq) {
            if (! is_array($faq)) {
                continue;
            }
            $q = $str($faq['question'] ?? '', 300);
            if ($q === '') {
                continue;
            }
            $faqs[] = ['question' => $q, 'answer' => $str($faq['answer'] ?? '', 1200)];
        }

        $statsIn = is_array($d['stats'] ?? null) ? $d['stats'] : [];
        $salaryIn = is_array($statsIn['salary'] ?? null) ? $statsIn['salary'] : [];
        $demand = $str($statsIn['demandLevel'] ?? 'High', 20);
        if (! in_array($demand, ['High', 'Medium', 'Low'], true)) {
            $demand = 'High';
        }

        $pathways = [];
        foreach ((array) ($d['pathways'] ?? []) as $path) {
            if (! is_array($path)) {
                continue;
            }
            $name = $str($path['name'] ?? '', 200);
            if ($name === '') {
                continue;
            }
            $pathways[] = ['name' => $name, 'steps' => $titleDescList($path['steps'] ?? [])];
        }

        $videos = [];
        foreach ((array) ($d['videoRecommendations'] ?? []) as $video) {
            if (! is_array($video)) {
                continue;
            }
            $title = $str($video['title'] ?? '', 300);
            if ($title === '') {
                continue;
            }
            $videos[] = [
                'title' => $title,
                'channelName' => $str($video['channelName'] ?? '', 200),
                'description' => $str($video['description'] ?? '', 600),
                // Optional: a real link (external URL or an uploaded file's
                // stored path) and a thumbnail image. When absent, the public
                // page falls back to a YouTube-search link + icon placeholder.
                'url' => $str($video['url'] ?? '', 500),
                'thumbnail' => $str($video['thumbnail'] ?? '', 500),
            ];
        }

        $workIn = is_array($d['workNature'] ?? null) ? $d['workNature'] : [];

        return [
            'seo' => [
                'title' => $str($seoIn['title'] ?? '', 200),
                'description' => $str($seoIn['description'] ?? '', 400),
                'keywords' => $strList($seoIn['keywords'] ?? [], 120),
                'faqs' => $faqs,
            ],
            'title' => $str($d['title'] ?? '', 200),
            'introduction' => $str($d['introduction'] ?? ''),
            'whoShouldPursue' => $strList($d['whoShouldPursue'] ?? []),
            'workNature' => [
                'description' => $str($workIn['description'] ?? ''),
                'examples' => $strList($workIn['examples'] ?? []),
            ],
            'eligibility' => $strList($d['eligibility'] ?? []),
            'stats' => [
                'salary' => [
                    'entry' => $str($salaryIn['entry'] ?? '', 60),
                    'median' => $str($salaryIn['median'] ?? '', 60),
                    'senior' => $str($salaryIn['senior'] ?? '', 60),
                    'currency' => $str($salaryIn['currency'] ?? 'INR', 20),
                ],
                'jobGrowth' => $str($statsIn['jobGrowth'] ?? '', 40),
                'demandLevel' => $demand,
                'topIndustries' => $strList($statsIn['topIndustries'] ?? [], 120),
                'futureOutlook' => $str($statsIn['futureOutlook'] ?? ''),
            ],
            'pathways' => $pathways,
            'conventionalOptions' => $titleDescList($d['conventionalOptions'] ?? []),
            'newAgeOptions' => $titleDescList($d['newAgeOptions'] ?? []),
            'aiRelatedOptions' => $titleDescList($d['aiRelatedOptions'] ?? []),
            'videoRecommendations' => $videos,
        ];
    }

    private function defaultSettings(): array
    {
        return [
            'hero_title_prefix' => 'Explore 3000+ New Age',
            'hero_title_highlight' => 'Career Options',
            'hero_subtitle' => 'Explore 3000+ careers with role insights, opportunities, growth scope, and steps to become one.',
            'search_placeholder' => 'E.g., Data Scientist, Pilot, Chef...',
            'trending_heading' => 'Trending Now',
            'explore_button' => 'Explore 3000+ Careers 🚀',
            'contact_email' => 'onedegreeadvisory@gmail.com',
            'contact_phone' => '8451825015',
            'next_steps_url' => url('/contact'),
            'report_year' => '2026',
        ];
    }

    private function write(string $path, array $data): void
    {
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        file_put_contents(
            $path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }
}
