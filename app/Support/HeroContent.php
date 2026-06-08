<?php

namespace App\Support;

/**
 * File-backed store + read interface for the Home-page hero. The hero is a single
 * editable record living in storage/app/home-hero.json so the live CMS can edit
 * every field with no database. The file is seeded once from defaults() so the
 * published page keeps its original content until an editor changes it.
 *
 * Field shape:
 *   eyebrow            text   — small kicker above the headline
 *   heading_pre        text   — headline, before the highlighted words
 *   heading_highlight  text   — the gold/italic words inside the headline
 *   heading_post       text   — headline, after the line break
 *   background         image  — full-bleed background photo (URL)
 *   actions            list   — buttons: { label, icon, href, style }
 *                               style ∈ orange | ghost | disabled
 */
class HeroContent
{
    /** Allowed button styles → public CSS classes. */
    public const STYLES = ['orange', 'ghost', 'disabled'];
    public const TEXT_STYLE_KEYS = ['eyebrow', 'heading', 'highlight'];
    public const TEXT_STYLE_MODES = ['default', 'solid', 'gradient'];
    public const TEXT_ANIMATIONS = ['theme', 'none', 'shimmer', 'pulse', 'lift'];

    public const TEXT_STYLE_DEFAULTS = [
        'eyebrow' => [
            'mode' => 'default',
            'color' => '',
            'gradient_start' => '#e7c655',
            'gradient_end' => '#ff6a1a',
            'animation' => 'theme',
        ],
        'heading' => [
            'mode' => 'default',
            'color' => '',
            'gradient_start' => '#ffffff',
            'gradient_end' => '#a9eee4',
            'animation' => 'theme',
        ],
        'highlight' => [
            'mode' => 'default',
            'color' => '',
            'gradient_start' => '#e7c655',
            'gradient_end' => '#ff8a3d',
            'animation' => 'theme',
        ],
    ];

    private string $path;

    public function __construct()
    {
        $this->path = storage_path('app/home-hero.json');
    }

    /** The current hero data — seeded from defaults() on first load. */
    public function current(): array
    {
        if (! is_file($this->path)) {
            $seed = $this->defaults();
            $this->save($seed);

            return $seed;
        }

        $data = json_decode((string) file_get_contents($this->path), true);

        return is_array($data) ? array_merge($this->defaults(), $data) : $this->defaults();
    }

    /** What the public page renders. (Kept separate so preview logic can swap it.) */
    public function forDisplay(): array
    {
        return $this->current();
    }

    public function save(array $data): void
    {
        if (! is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0775, true);
        }

        file_put_contents(
            $this->path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    /** Coerce raw editor input into the exact, safe hero shape. */
    public function sanitize(array $raw): array
    {
        $text = fn ($v, $max = 600) => mb_substr(trim((string) ($v ?? '')), 0, $max);
        $color = fn ($v) => preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', trim((string) ($v ?? ''))) ? trim((string) $v) : '';

        $rawColors = is_array($raw['colors'] ?? null) ? $raw['colors'] : [];
        $rawStyles = is_array($raw['styles'] ?? null) ? $raw['styles'] : [];
        $styles = [];
        foreach (self::TEXT_STYLE_KEYS as $key) {
            $rawStyle = is_array($rawStyles[$key] ?? null) ? $rawStyles[$key] : [];
            $legacyColor = $color($rawColors[$key] ?? '');
            $mode = in_array($rawStyle['mode'] ?? '', self::TEXT_STYLE_MODES, true)
                ? $rawStyle['mode']
                : ($legacyColor !== '' ? 'solid' : self::TEXT_STYLE_DEFAULTS[$key]['mode']);

            $solidColor = $color($rawStyle['color'] ?? '') ?: $legacyColor;
            if ($mode === 'solid' && $solidColor === '') {
                $mode = 'default';
            }

            $styles[$key] = [
                'mode' => $mode,
                'color' => $solidColor,
                'gradient_start' => $color($rawStyle['gradient_start'] ?? '') ?: self::TEXT_STYLE_DEFAULTS[$key]['gradient_start'],
                'gradient_end' => $color($rawStyle['gradient_end'] ?? '') ?: self::TEXT_STYLE_DEFAULTS[$key]['gradient_end'],
                'animation' => in_array($rawStyle['animation'] ?? '', self::TEXT_ANIMATIONS, true)
                    ? $rawStyle['animation']
                    : self::TEXT_STYLE_DEFAULTS[$key]['animation'],
            ];
        }

        $colors = [
            'eyebrow' => $styles['eyebrow']['mode'] === 'solid' ? $styles['eyebrow']['color'] : '',
            'heading' => $styles['heading']['mode'] === 'solid' ? $styles['heading']['color'] : '',
            'highlight' => $styles['highlight']['mode'] === 'solid' ? $styles['highlight']['color'] : '',
        ];

        // Row index (0-based) — buttons stack into responsive rows.
        $rowIndex = fn ($v) => is_numeric($v) ? max(0, min(20, (int) $v)) : 0;

        $actions = [];
        foreach (is_array($raw['actions'] ?? null) ? $raw['actions'] : [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = $text($row['label'] ?? '', 120);
            $icon = preg_replace('/[^a-z0-9-]/', '', strtolower(trim((string) ($row['icon'] ?? ''))));
            $href = $text($row['href'] ?? '', 300);
            $style = in_array($row['style'] ?? '', self::STYLES, true) ? $row['style'] : 'orange';

            if ($label === '' && $icon === '') {
                continue; // drop empty buttons
            }

            $actions[] = [
                'label' => $label,
                'icon' => $icon,
                'href' => $href,
                'style' => $style,
                'row' => $rowIndex($row['row'] ?? 0),
            ];
        }

        // A freshly-cropped, not-yet-saved background arrives as a base64 data URL
        // (used only for the live phone preview); keep it whole. A real save first
        // converts it to a short file URL, so the 1000-char cap still applies there.
        $rawBackground = trim((string) ($raw['background'] ?? ''));
        $background = str_starts_with($rawBackground, 'data:image/')
            ? $rawBackground
            : $text($rawBackground, 1000);

        return [
            'eyebrow' => $text($raw['eyebrow'] ?? '', 120),
            'heading_pre' => $text($raw['heading_pre'] ?? '', 200),
            'heading_highlight' => $text($raw['heading_highlight'] ?? '', 200),
            'heading_post' => $text($raw['heading_post'] ?? '', 200),
            'background' => $background,
            'colors' => $colors,
            'styles' => $styles,
            'actions' => $actions,
        ];
    }

    /** The built-in seed — the current home hero expressed as editable data. */
    public function defaults(): array
    {
        return [
            'eyebrow' => 'Global Admissions',
            'heading_pre' => 'You are',
            'heading_highlight' => 'one degree',
            'heading_post' => 'away from the world.',
            'background' => 'https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=2200&q=88',
            'colors' => ['eyebrow' => '', 'heading' => '', 'highlight' => ''],
            'styles' => self::TEXT_STYLE_DEFAULTS,
            'actions' => [
                ['label' => 'Career Mentoring', 'icon' => 'compass', 'href' => '', 'style' => 'disabled', 'row' => 0],
                ['label' => 'Student Development Programme', 'icon' => 'graduation-cap', 'href' => '', 'style' => 'disabled', 'row' => 0],
                ['label' => 'Study Abroad', 'icon' => 'globe', 'href' => '/study-abroad', 'style' => 'orange', 'row' => 0],
            ],
        ];
    }
}
