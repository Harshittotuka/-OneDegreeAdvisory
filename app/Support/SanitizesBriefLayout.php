<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * The single sanitizer for a Page Builder grid layout, shared by the visual
 * studio (BriefPageCmsController) and the machine callers that go through
 * PageBuilderWriter.
 *
 * Every value that reaches brief-pages.json passes through here: unknown block
 * types are dropped, each block's data is rebuilt field-by-field from
 * BriefSchema, and scalars are capped and type-coerced. Keeping it in one trait
 * means an API-authored page can never carry anything the studio would refuse.
 */
trait SanitizesBriefLayout
{
    /** Rebuild rows → cols → blocks, keeping only schema-known blocks and fields. */
    protected function sanitizeLayout(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $cols = [];
            foreach (($row['cols'] ?? []) as $col) {
                if (! is_array($col)) {
                    continue;
                }
                $blocks = [];
                foreach (($col['blocks'] ?? []) as $b) {
                    if (! is_array($b)) {
                        continue;
                    }
                    $type = (string) ($b['type'] ?? '');
                    if (! BriefSchema::isType($type)) {
                        continue;
                    }
                    $blocks[] = [
                        'id' => (Str::slug((string) ($b['id'] ?? '')) ?: ('b'.Str::random(6))),
                        'type' => $type,
                        'visible' => (bool) ($b['visible'] ?? true),
                        'data' => $this->sanitizeData($type, is_array($b['data'] ?? null) ? $b['data'] : []),
                    ];
                }
                $span = max(1, min(12, (int) ($col['span'] ?? 12)));
                $cols[] = ['id' => (Str::slug((string) ($col['id'] ?? '')) ?: ('c'.Str::random(6))), 'span' => $span, 'blocks' => $blocks];
            }
            if ($cols) {
                $out[] = [
                    'id' => (Str::slug((string) ($row['id'] ?? '')) ?: ('r'.Str::random(6))),
                    'width' => (($row['width'] ?? '') === 'full') ? 'full' : '',
                    'cols' => $cols,
                ];
            }
        }

        return $out;
    }

    protected function sanitizeData(string $type, array $raw): array
    {
        $out = [];
        foreach (BriefSchema::type($type)['fields'] ?? [] as $field) {
            $key = $field['key'];
            if ($field['type'] === 'repeater') {
                $rows = is_array($raw[$key] ?? null) ? $raw[$key] : [];
                $out[$key] = $this->sanitizeRepeater($field['fields'], $rows);

                continue;
            }
            $out[$key] = $this->cleanScalar($field, $raw[$key] ?? null);
        }

        return $out;
    }

    protected function sanitizeRepeater(array $fields, array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $clean = [];
            $hasContent = false;
            foreach ($fields as $field) {
                if ($field['type'] === 'repeater') {
                    $nested = is_array($row[$field['key']] ?? null) ? $row[$field['key']] : [];
                    $clean[$field['key']] = $this->sanitizeRepeater($field['fields'], $nested);
                    if ($clean[$field['key']] !== []) {
                        $hasContent = true;
                    }

                    continue;
                }
                $value = $this->cleanScalar($field, $row[$field['key']] ?? null);
                $clean[$field['key']] = $value;
                if ($value !== '' && $value !== false) {
                    $hasContent = true;
                }
            }
            if ($hasContent) {
                $items[] = $clean;
            }
        }

        return $items;
    }

    protected function cleanScalar(array $field, mixed $value): mixed
    {
        return match ($field['type']) {
            'checkbox' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'icon' => preg_replace('/[^a-z0-9-]/', '', strtolower(trim((string) $value))),
            'color' => preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', trim((string) $value)) ? strtolower(trim((string) $value)) : '',
            'select' => array_key_exists((string) $value, $field['options'] ?? [])
                ? (string) $value
                : (string) array_key_first($field['options'] ?? ['' => '']),
            'richtext' => $this->cleanRichText((string) $value),
            // Raw embed code (HTML/CSS/JS), stored as-is (capped).
            'code' => mb_substr((string) $value, 0, 120000),
            'image' => mb_substr(trim((string) $value), 0, 2000),
            'textarea' => mb_substr(trim((string) $value), 0, 6000),
            default => mb_substr(trim((string) $value), 0, 1000),
        };
    }

    /** Strip <script>/<style> and on* handlers; keep basic formatting HTML. */
    protected function cleanRichText(string $html): string
    {
        $html = preg_replace('#<\s*(script|style)[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html);
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);

        return mb_substr(trim($html), 0, 12000);
    }

    /** Whether a layout contains a live payment gateway block. */
    protected function layoutHasPayment(array $rows): bool
    {
        foreach ($rows as $row) {
            foreach ((is_array($row) ? ($row['cols'] ?? []) : []) as $col) {
                foreach ((is_array($col) ? ($col['blocks'] ?? []) : []) as $block) {
                    if (is_array($block) && ($block['type'] ?? '') === 'payment') {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Normalize a caller-chosen URL path. Invalid, reserved or already-taken
     * paths fall back to the page's current path (so a bad edit can never
     * orphan a live page).
     */
    protected function cleanPath(string $raw, array $page, BriefPageStore $store): string
    {
        $current = $page['path'] ?? ('/briefs/'.($page['slug'] ?? 'page'));

        $p = '/'.trim(strtolower(trim($raw)), '/');
        $p = preg_replace('#/+#', '/', $p);

        if ($p === '/' || ! preg_match('#^/[a-z0-9/-]+$#', $p)) {
            return $current;
        }

        foreach (['/admin', '/storage', '/api', '/mcp', '/login', '/logout'] as $reserved) {
            if ($p === $reserved || str_starts_with($p, $reserved.'/')) {
                return $current;
            }
        }

        $other = $store->findByPath($p);
        if ($other !== null && ($other['slug'] ?? null) !== ($page['slug'] ?? null)) {
            return $current;
        }

        return $p;
    }
}
