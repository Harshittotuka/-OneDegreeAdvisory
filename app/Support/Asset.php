<?php

namespace App\Support;

class Asset
{
    /**
     * Cache-busted URL for a file in public/.
     *
     * LiteSpeed serves public/ statically with long cache headers, so an asset
     * whose URL never changes stays pinned in browsers and proxies even after
     * the bytes on disk are replaced. That is how a swapped-in image can keep
     * rendering as the old one long after the deploy (the test-prep hero image
     * did exactly this). Appending the file mtime makes every replacement a
     * new URL, while unchanged files keep their cached copy.
     */
    public static function v(string $file): string
    {
        $file = ltrim($file, '/');
        $path = public_path($file);

        [$file, $path] = self::preferMinified($file, $path);

        return is_file($path)
            ? asset($file).'?v='.filemtime($path)
            : asset($file);
    }

    /**
     * Swap styles.css for styles.min.css when a build exists.
     *
     * The readable file stays the one everyone edits and the one git tracks as
     * the source; `npm run minify` writes the .min sibling next to it. The mtime
     * comparison is the safety catch: edit the source and forget to rebuild, and
     * the stale build is ignored rather than silently shipped, so the worst case
     * is an unminified page rather than a wrong one.
     *
     * @return array{0: string, 1: string}
     */
    private static function preferMinified(string $file, string $path): array
    {
        if (! preg_match('/^(.+)\.(css|js)$/', $file, $m) || str_ends_with($m[1], '.min')) {
            return [$file, $path];
        }

        $minFile = $m[1].'.min.'.$m[2];
        $minPath = public_path($minFile);

        // The tolerance is for deploys, not for developers. git does not restore
        // mtimes, so a checkout stamps the source and its build with whatever
        // time each happened to be written — a sub-second skew in the wrong
        // direction would otherwise silently drop the whole site back to the
        // unminified files. A real "edited the source and forgot to rebuild"
        // is minutes old, so it still loses to this check.
        if (is_file($minPath) && is_file($path) && filemtime($minPath) >= filemtime($path) - 10) {
            return [$minFile, $minPath];
        }

        return [$file, $path];
    }
}
