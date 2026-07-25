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

        return is_file($path)
            ? asset($file).'?v='.filemtime($path)
            : asset($file);
    }
}
