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
        // Map the legacy variants onto the redesigned set.
        $variant = match ($variant) {
            'original', 'compact' => 'left-socials',
            'minimal' => 'no-socials',
            default => $variant,
        };
        if (! in_array($variant, ['left-socials', 'no-socials', 'static-notice', 'left-socials-cycle'], true)) {
            $variant = 'left-socials';
        }

        // The centred static announcement (HTML, links allowed) for the
        // "static-notice" style. Sanitised to a safe subset of tags.
        $staticText = $this->sanitizeStaticHtml((string) ($data['static_text'] ?? $defaults['static_text']));

        $wordCount = (int) ($data['word_count'] ?? $defaults['word_count']);
        $wordCount = max(0, min(50, $wordCount));

        $speed = (int) ($data['speed'] ?? $defaults['speed']);
        $speed = max(5, min(120, $speed));

        // Distance (px) between scrolling items; the trailing padding mirrors it
        // so the seamless-loop join stays invisible (see the partial/CSS).
        $itemGap = (int) ($data['item_gap'] ?? $defaults['item_gap']);
        $itemGap = max(8, min(240, $itemGap));

        $textColor = strtolower((string) ($data['text_color'] ?? $defaults['text_color']));
        if (! preg_match('/^#[0-9a-f]{6}$/', $textColor)) {
            $textColor = $defaults['text_color'];
        }

        $fontStyle = (string) ($data['font_style'] ?? $defaults['font_style']);
        if (! in_array($fontStyle, ['normal', 'italic'], true)) {
            $fontStyle = 'normal';
        }

        $bold = (bool) ($data['bold'] ?? $defaults['bold']);

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
            'item_gap' => $itemGap,
            'text_color' => $textColor,
            'font_style' => $fontStyle,
            'bold' => $bold,
            'static_text' => $staticText,
            'items' => $items,
        ];
    }

    /**
     * Keep only basic formatting + links from the static-notice HTML, and force
     * every link to a safe href (relative path, http(s), #anchor, mailto/tel).
     */
    private function sanitizeStaticHtml(string $raw): string
    {
        $html = trim($raw);
        if ($html === '') {
            return '';
        }

        // Drop <script>/<style> with their contents (strip_tags would leak the inner text).
        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = strip_tags($html, '<a><strong><em><b><i><br>');

        $html = preg_replace_callback('/<a\b[^>]*>/i', function (array $m): string {
            if (preg_match('/href\s*=\s*("|\')(.*?)\1/i', $m[0], $h)) {
                $href = trim(html_entity_decode($h[2]));
                if (preg_match('~^(https?://|/|#|mailto:|tel:)~i', $href)) {
                    $rel = preg_match('~^https?://~i', $href) ? ' target="_blank" rel="noopener"' : '';

                    return '<a href="'.htmlspecialchars($href, ENT_QUOTES).'"'.$rel.'>';
                }
            }

            // Unsafe or missing href → keep the words, drop the link.
            return '<a>';
        }, $html) ?? '';

        return mb_substr($html, 0, 2000);
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
            'variant' => 'left-socials',
            'word_count' => 5,
            'speed' => 14,
            'item_gap' => 64,
            'text_color' => '#ff5e32',
            'font_style' => 'normal',
            'bold' => false,
            'static_text' => 'Admissions for the 2026 intake are now open. <a href="/contact">Book a free consultation</a>.',
            'items' => $items,
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
            throw new \RuntimeException('Could not save the Notice Bar CMS data.');
        }

        app(CmsCrmBackupManager::class)->markDirty('cms-json');
    }
}
