<?php

namespace App\Support;

use Illuminate\Support\Str;

class Seo
{
    public static function title(?string $value, ?string $fallback = null, int $max = 70): string
    {
        $title = self::plainText($value);
        if ($title === '') {
            $title = self::plainText($fallback);
        }

        return Str::limit($title, $max, '');
    }

    public static function description(?string $value, ?string $fallback = null, int $max = 160): string
    {
        $description = self::plainText($value);
        if ($description === '') {
            $description = self::plainText($fallback);
        }

        return Str::limit($description, $max, '');
    }

    public static function plainText(mixed $value): string
    {
        if (is_array($value)) {
            $value = self::flattenText($value);
        }

        $text = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim($text);
    }

    public static function blogBodyText(array $blocks): string
    {
        $parts = [];

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            $kind = (string) ($block['kind'] ?? '');
            if (in_array($kind, ['p', 'h2', 'quote'], true)) {
                $parts[] = (string) ($block['html'] ?? $block['text'] ?? '');
                $parts[] = (string) ($block['attribution'] ?? '');
            } elseif ($kind === 'list') {
                $parts[] = self::plainText($block['items'] ?? []);
            } elseif ($kind === 'table') {
                $parts[] = self::plainText($block['rows'] ?? []);
            } elseif ($kind === 'image') {
                $parts[] = (string) ($block['caption'] ?? $block['alt'] ?? '');
            }
        }

        return self::plainText(implode(' ', $parts));
    }

    public static function layoutText(array $layout): string
    {
        return self::plainText(self::flattenText($layout, [
            'accent',
            'accent2',
            'btn_href',
            'code',
            'color',
            'flag',
            'href',
            'id',
            'icon',
            'image',
            'src',
            'span',
            'style',
            'type',
            'url',
            'variant',
            'visible',
            'width',
        ]));
    }

    public static function imageUrl(?string $url = null): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return asset('assets/Logo/og-image.png');
        }

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        if (Str::startsWith($url, '//')) {
            return 'https:'.$url;
        }

        return asset(self::optimizedAssetPath(ltrim($url, '/')));
    }

    public static function pageUrl(?string $path = null): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return url('/');
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return url('/'.ltrim($path, '/'));
    }

    public static function jsonLd(array $data): string
    {
        return (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * True only when the current request is served from the one canonical
     * public host (config('site.canonical_host'), e.g. onedegreeadvisory.com).
     * Everything else — the nip.io UAT box, the raw server IP, a hosting
     * preview domain, localhost — is a non-canonical mirror that must be kept
     * out of the index so it never competes with the live site as duplicate
     * content. Note this is keyed off a FIXED canonical host, not APP_URL,
     * because each environment's APP_URL is its own host and would otherwise
     * report itself as canonical. Outside an HTTP request (console, queue,
     * sitemap render) we treat it as canonical so generated artifacts keep
     * their normal directives.
     */
    public static function isCanonicalHost(): bool
    {
        $canonical = trim((string) config('site.canonical_host'));
        if ($canonical === '') {
            return true;
        }

        $request = request();
        if ($request === null) {
            return true;
        }

        // Accept the bare host and its www. variant as canonical; the www→non
        // www redirect / forced root URL handles normalising which one shows.
        $host = strtolower($request->getHost());
        $canonical = strtolower($canonical);

        return $host === $canonical
            || $host === 'www.'.$canonical
            || 'www.'.$host === $canonical;
    }

    private static function flattenText(array $value, array $skipKeys = []): string
    {
        $parts = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && in_array($key, $skipKeys, true)) {
                continue;
            }

            if (is_array($item)) {
                $parts[] = self::flattenText($item, $skipKeys);
            } elseif (is_scalar($item)) {
                $text = trim((string) $item);
                if ($text !== '' && ! Str::startsWith($text, ['http://', 'https://', '/', '#', 'data:'])) {
                    $parts[] = $text;
                }
            }
        }

        return implode(' ', array_filter($parts));
    }

    private static function optimizedAssetPath(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return $path;
        }

        $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);

        return $webp && is_file(public_path($webp)) ? $webp : $path;
    }
}
