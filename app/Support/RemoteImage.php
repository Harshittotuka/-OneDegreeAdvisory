<?php

namespace App\Support;

/**
 * Width variants for remotely hosted images.
 *
 * The hero background is stored in the CMS as a single Unsplash URL, and it was
 * stored at the width the desktop hero needs (w=2200, ~600KB). Every phone
 * downloaded that same file to paint a 412px-wide box, which is most of the
 * homepage's Largest Contentful Paint on a throttled connection.
 *
 * Unsplash resizes on demand from a query parameter, so we can hand the browser
 * a srcset and let it pick. Anything we do not know how to resize is returned
 * untouched — a CMS upload or a one-off host still works, it just gets no
 * srcset, which is exactly the behaviour everything had before.
 */
class RemoteImage
{
    /**
     * Ladder for a full-bleed (100vw) image. Covers a 412px phone at DPR 2, a
     * 1350px laptop at DPR 1 and a 1440px screen at DPR 2 without a big jump.
     */
    public const FULL_BLEED_WIDTHS = [640, 828, 1080, 1440, 1920, 2560];

    /** Hosts whose URLs take a `w` query parameter. */
    private const RESIZABLE_HOSTS = ['images.unsplash.com', 'plus.unsplash.com'];

    public static function isResizable(string $url): bool
    {
        $host = strtolower((string) parse_url(trim($url), PHP_URL_HOST));

        return $host !== '' && in_array($host, self::RESIZABLE_HOSTS, true);
    }

    /**
     * The same image asked for at a specific width. Every other parameter the
     * CMS stored (auto=format, fit=crop, q=…) is preserved, so the crop and the
     * quality stay exactly as they were authored.
     */
    public static function at(string $url, int $width): string
    {
        $url = trim($url);

        if (! self::isResizable($url)) {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            return $url;
        }

        $query = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        $query['w'] = $width;
        unset($query['h']); // a fixed height alongside a new width would re-crop

        return (($parts['scheme'] ?? 'https').'://').$parts['host']
            .($parts['path'] ?? '')
            .'?'.http_build_query($query);
    }

    /**
     * A `srcset` value, or '' when the host cannot be resized (in which case the
     * caller should emit a plain src and nothing else).
     */
    public static function srcset(string $url, array $widths = self::FULL_BLEED_WIDTHS): string
    {
        if (! self::isResizable($url)) {
            return '';
        }

        $out = [];
        foreach ($widths as $w) {
            $w = (int) $w;
            if ($w > 0) {
                $out[$w] = self::at($url, $w).' '.$w.'w';
            }
        }
        ksort($out);

        return implode(', ', $out);
    }

    /**
     * Sensible `src` fallback for browsers that ignore srcset: mid-ladder rather
     * than the largest, so an old browser is not punished with the 2560px file.
     */
    public static function fallback(string $url, array $widths = self::FULL_BLEED_WIDTHS): string
    {
        if (! self::isResizable($url) || $widths === []) {
            return trim($url);
        }

        sort($widths);

        return self::at($url, (int) $widths[intdiv(count($widths), 2)]);
    }
}
