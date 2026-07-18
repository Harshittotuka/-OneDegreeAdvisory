<?php

namespace App\Support;

use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

final class PdfStudentName
{
    public function extract(string $path): string
    {
        try {
            $document = (new Parser)->parseFile($path);
            $pages = $document->getPages();
            $text = isset($pages[0]) ? $pages[0]->getText() : '';

            foreach ([
                '/REPORT\s+PREPARED\s+FOR\s*[\r\n]+\s*([^\r\n]+)/iu',
                '/PREPARED\s+FOR\s*[:\-]?\s*[\r\n]+\s*([^\r\n]+)/iu',
            ] as $pattern) {
                if (preg_match($pattern, $text, $match)) {
                    $name = $this->clean($match[1]);
                    if ($this->looksLikeName($name)) {
                        return $name;
                    }
                }
            }
        } catch (\Throwable) {
            // Name extraction is a convenience; PDF replacement can continue
            // when a report uses image-only text or an unsupported font map.
        }

        return 'Student';
    }

    private function clean(string $value): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $value) ?? '';

        return Str::limit(Str::squish($value), 80, '');
    }

    private function looksLikeName(string $value): bool
    {
        return $value !== ''
            && mb_strlen($value) >= 2
            && ! preg_match('/\b(phone|email|programme|program|date)\b/i', $value);
    }
}
