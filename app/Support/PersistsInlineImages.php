<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Shared by the live CMS editors. The editors keep a freshly-cropped/uploaded
 * image client-side as a base64 data URL and only send it to the server when
 * the page is saved. This trait walks a save payload, writes any such inline
 * images to the public disk, and swaps each for its asset() URL — so an image
 * reaches storage only when the page is actually saved, never on a discarded edit.
 */
trait PersistsInlineImages
{
    protected function persistInlineImages(array $data, string $folder): array
    {
        array_walk_recursive($data, function (&$value) use ($folder) {
            if (is_string($value) && str_starts_with($value, 'data:image/')) {
                $url = $this->storeDataUrlImage($value, $folder);
                if ($url !== null) {
                    $value = $url;
                }
            }
        });

        return $data;
    }

    private function storeDataUrlImage(string $dataUrl, string $folder): ?string
    {
        if (! preg_match('#^data:image/([a-z0-9.+-]+);base64,(.+)$#is', $dataUrl, $m)) {
            return null;
        }

        $binary = base64_decode($m[2], true);
        if ($binary === false || $binary === '' || strlen($binary) > 8 * 1024 * 1024) {
            return null; // unreadable, or larger than the 8 MB upload cap
        }

        $ext = match (strtolower($m[1])) {
            'png' => 'png',
            'webp' => 'webp',
            'gif' => 'gif',
            'svg+xml' => 'svg',
            default => 'jpg',
        };

        $name = trim($folder, '/').'/'.bin2hex(random_bytes(8)).'.'.$ext;
        Storage::disk('public')->put($name, $binary);

        return asset('storage/'.$name);
    }
}
