<?php

namespace App\Support;

/**
 * Hand-written copy for the country guides, layered over the scraped content.
 *
 * The country pages are built from a partner site's pages via the country-sync
 * scraper. What that produces is not prose: a section body arrives as the DOM's
 * text nodes joined with "|", heading fragments and all, so a page's visible
 * copy is whatever single sentence destination.blade's $plainBody() manages to
 * salvage from it. Google reads that for what it is, which is why these pages
 * draw impressions and rank in the 30s and 60s.
 *
 * Copy written here wins over the scraped values. It lives in the repo (not in
 * storage/, which is gitignored and per-box) for two reasons: it deploys like
 * any other change, and re-running country-sync cannot overwrite it — the same
 * reasoning as StudyLocationContent::withOurBrand(), which rewrites the
 * partner's brand out on read rather than in the stored file.
 *
 * To rewrite a guide, add resources/data/country-guides/<page_slug>.php. Any
 * key you leave out keeps the scraped value, so a partial rewrite is fine.
 */
class CountryGuideCopy
{
    /** @var array<string, array>|null */
    private static ?array $cache = null;

    /** Editorial copy for a page slug, or [] when that guide has not been rewritten. */
    public static function forSlug(string $slug): array
    {
        $slug = trim($slug);

        if ($slug === '') {
            return [];
        }

        return self::all()[$slug] ?? [];
    }

    /** Page slugs that have hand-written copy. */
    public static function rewrittenSlugs(): array
    {
        return array_keys(self::all());
    }

    /** @return array<string, array> */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $copy = [];

        foreach (glob(self::directory().'/*.php') ?: [] as $file) {
            $slug = basename($file, '.php');
            $data = require $file;

            if (is_array($data) && $data !== []) {
                $copy[$slug] = $data;
            }
        }

        return self::$cache = $copy;
    }

    /** Test seam: forget what was loaded from disk. */
    public static function flush(): void
    {
        self::$cache = null;
    }

    /**
     * Resolved from this file rather than via resource_path(), so the copy can
     * also be read before the container is up — a PHPUnit data provider runs
     * before the application boots.
     */
    private static function directory(): string
    {
        return dirname(__DIR__, 2).'/resources/data/country-guides';
    }

    /**
     * Lay the editorial copy over one page's scraped payload.
     *
     * Only the keys a guide actually defines are replaced, so a guide can fix
     * its meta description and leave every section alone. Section bodies are
     * written to `section_body_clean`, which the view renders whole — unlike
     * the scraped `section_body`, which it has to pick a sentence out of.
     */
    public static function apply(array $payload, string $slug): array
    {
        $copy = self::forSlug($slug);

        if ($copy === []) {
            return $payload;
        }

        foreach (['seo_title', 'seo_description', 'page_title', 'hero_heading', 'hero_text'] as $key) {
            if (isset($copy[$key]) && trim((string) $copy[$key]) !== '') {
                $payload['page'][$key] = (string) $copy[$key];
            }
        }

        foreach ($copy['sections'] ?? [] as $key => $section) {
            if (! isset($payload['sectionCopy'][$key])) {
                continue;
            }

            if (trim((string) ($section['heading'] ?? '')) !== '') {
                $payload['sectionCopy'][$key]['section_heading'] = (string) $section['heading'];
            }

            if (trim((string) ($section['body'] ?? '')) !== '') {
                $payload['sectionCopy'][$key]['section_body_clean'] = (string) $section['body'];
            }
        }

        // The "Indian students in <country>" band repeats, word for word, the
        // one sentence the why-section salvages — the same text twice on the
        // page, once as a paragraph and once as an <h2>. A guide can give the
        // band its own headline instead.
        foreach (['heading_before', 'heading_highlight', 'heading_after'] as $key) {
            if (isset($copy['indian_students'][$key])) {
                $payload['indianStudents'][$key] = (string) $copy['indian_students'][$key];
            }
        }

        if (isset($copy['indian_students']['subtitle'])) {
            $payload['indianStudents']['subtitle'] = (string) $copy['indian_students']['subtitle'];
        }

        // Questions and answers, written to match what people actually search.
        // These render on the page and are also emitted as FAQPage structured
        // data; Google requires the two to match, so there is one source.
        //
        // Only ever from copy written here. The scraped content cannot be used
        // for this: its "student life" passages describe another company's
        // hostels and staff, and marking those up would restate someone else's
        // claims as ours in machine-readable form.
        $payload['faq'] = array_values(array_filter(
            array_map(
                fn ($item) => [
                    'question' => trim((string) ($item['q'] ?? '')),
                    'answer' => trim((string) ($item['a'] ?? '')),
                ],
                $copy['faq'] ?? []
            ),
            fn ($item) => $item['question'] !== '' && $item['answer'] !== ''
        ));

        return $payload;
    }
}
