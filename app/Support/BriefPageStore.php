<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * File-backed store for CMS-built "brief" pages (the .odp-* design system).
 * Unlike the single-page About store, this holds MANY pages in one JSON file:
 *
 *   [ { slug, path, title, page_title, meta_description, visible,
 *       sections: [ { id, type, visible, data:{...} } ] }, ... ]
 *
 * Seeded once from BriefPageContent::defaults() (the four hand-built pages).
 */
class BriefPageStore
{
    private string $path;

    public function __construct()
    {
        $this->path = storage_path('app/brief-pages.json');
    }

    /** All pages, in display order. */
    public function all(): array
    {
        if (! is_file($this->path)) {
            $seed = (new BriefPageContent)->defaults();
            $this->writeAll($seed);

            return $seed;
        }

        $data = json_decode((string) file_get_contents($this->path), true);
        if (! is_array($data)) {
            return [];
        }

        $pages = array_map([$this, 'normalize'], $data);
        [$pages, $changed] = $this->upgradeStoredPages($pages);
        if ($changed) {
            $this->writeAll($pages);
        }

        return $pages;
    }

    /** Ensure a page has a grid `layout`; legacy `sections` are wrapped one-per-row. */
    private function normalize(array $page): array
    {
        if (! empty($page['layout']) && is_array($page['layout'])) {
            return $page;
        }

        $rows = [];
        foreach (($page['sections'] ?? []) as $block) {
            $rows[] = [
                'id' => 'row-'.Str::random(6),
                'cols' => [['id' => 'col-'.Str::random(6), 'span' => 12, 'blocks' => [$block]]],
            ];
        }
        $page['layout'] = $rows;

        return $page;
    }

    /**
     * Add newly shipped CMS blocks once without overwriting later editor choices.
     * The version marker remains even if an editor intentionally deletes the block.
     */
    private function upgradeStoredPages(array $pages): array
    {
        $changed = false;
        foreach ($pages as &$page) {
            if (($page['slug'] ?? '') !== 'europe') {
                continue;
            }

            $paymentVersion = (int) ($page['content_versions']['razorpay_payment_block'] ?? 0);
            if ($paymentVersion < 1) {
                $hasPayment = false;
                foreach (($page['layout'] ?? []) as $row) {
                    foreach (($row['cols'] ?? []) as $column) {
                        foreach (($column['blocks'] ?? []) as $block) {
                            if (($block['type'] ?? '') === 'payment') {
                                $hasPayment = true;
                                break 3;
                            }
                        }
                    }
                }

                if (! $hasPayment) {
                    $paymentRow = [
                        'id' => 'row-europe-payment',
                        'width' => '',
                        'cols' => [[
                            'id' => 'col-europe-payment',
                            'span' => 12,
                            'blocks' => [BriefPageContent::europePaymentBlock()],
                        ]],
                    ];

                    $insertAt = count($page['layout'] ?? []);
                    foreach (($page['layout'] ?? []) as $index => $row) {
                        foreach (($row['cols'] ?? []) as $column) {
                            foreach (($column['blocks'] ?? []) as $block) {
                                if (($block['type'] ?? '') === 'disclaimer') {
                                    $insertAt = $index;
                                    break 3;
                                }
                            }
                        }
                    }
                    array_splice($page['layout'], $insertAt, 0, [$paymentRow]);
                }

                $page['content_versions']['razorpay_payment_block'] = 1;
                $changed = true;
            }

            $pricingVersion = (int) ($page['content_versions']['razorpay_pricing_links'] ?? 0);
            if ($pricingVersion < 2) {
                foreach ($page['layout'] as &$row) {
                    foreach ($row['cols'] as &$column) {
                        foreach ($column['blocks'] as &$block) {
                            if (($block['type'] ?? '') !== 'pricing') {
                                continue;
                            }

                            $sharedHref = trim((string) ($block['data']['enrol_href'] ?? ''));
                            if ($sharedHref === '' || str_contains(strtolower($sharedHref), 'wa.me/')) {
                                $block['data']['enrol_href'] = '#europe-payment-option-0';
                            }
                            if (isset($block['data']['plans']) && is_array($block['data']['plans'])) {
                                foreach ($block['data']['plans'] as $index => &$plan) {
                                    $planHref = trim((string) ($plan['btn_href'] ?? ''));
                                    if ($planHref === '' || str_contains(strtolower($planHref), 'wa.me/')) {
                                        $plan['btn_href'] = '#europe-payment-option-'.$index;
                                    }
                                }
                                unset($plan);
                            }
                            break 3;
                        }
                    }
                }
                unset($block, $column, $row);

                $page['content_versions']['razorpay_pricing_links'] = 2;
                $changed = true;
            }
        }
        unset($page);

        return [$pages, $changed];
    }

    /** Only pages flagged visible (for public routing). */
    public function visible(): array
    {
        return array_values(array_filter($this->all(), fn ($p) => (bool) ($p['visible'] ?? true)));
    }

    public function find(string $slug): ?array
    {
        foreach ($this->all() as $page) {
            if (($page['slug'] ?? null) === $slug) {
                return $page;
            }
        }

        return null;
    }

    /** Look a page up by its public path (e.g. "/europe"). */
    public function findByPath(string $path): ?array
    {
        $path = '/'.ltrim($path, '/');
        foreach ($this->all() as $page) {
            if (($page['path'] ?? null) === $path) {
                return $page;
            }
        }

        return null;
    }

    /**
     * Create or update a page. When $originalSlug matches an existing page the
     * record is updated in place (keeping its position); otherwise it is appended.
     */
    public function save(array $page, ?string $originalSlug = null): array
    {
        $pages = $this->all();
        $needle = $originalSlug ?? ($page['slug'] ?? null);

        $index = null;
        foreach ($pages as $i => $existing) {
            if (($existing['slug'] ?? null) === $needle) {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            $pages[] = $page;
        } else {
            $pages[$index] = $page;
        }

        $this->writeAll($pages);

        return $page;
    }

    public function setVisibility(string $slug, bool $visible): void
    {
        $pages = $this->all();
        foreach ($pages as &$p) {
            if (($p['slug'] ?? null) === $slug) {
                $p['visible'] = $visible;
            }
        }
        unset($p);

        $this->writeAll($pages);
    }

    /** Duplicate a page, returning the new copy (or null if the source is missing). */
    public function duplicate(string $slug): ?array
    {
        $source = $this->find($slug);
        if ($source === null) {
            return null;
        }

        $copy = $source;
        $copy['slug'] = $this->uniqueSlug($slug.'-copy');
        $copy['path'] = '/briefs/'.$copy['slug'];
        $copy['title'] = ($source['title'] ?? 'Untitled').' (copy)';
        $copy['page_title'] = $copy['title'].' | '.config('site.name', 'One Degree Advisory');
        $copy['visible'] = false;

        $pages = $this->all();
        $pages[] = $copy;
        $this->writeAll($pages);

        return $copy;
    }

    public function delete(string $slug): void
    {
        $pages = array_values(array_filter(
            $this->all(),
            fn (array $p) => ($p['slug'] ?? null) !== $slug
        ));

        $this->writeAll($pages);
    }

    /** A URL-safe slug unique across all pages except $ignore (the one being edited). */
    public function uniqueSlug(string $desired, ?string $ignore = null): string
    {
        $base = Str::slug($desired) ?: 'page';
        $taken = array_filter(
            array_column($this->all(), 'slug'),
            fn ($s) => $s !== $ignore
        );

        $slug = $base;
        $n = 2;
        while (in_array($slug, $taken, true)) {
            $slug = $base.'-'.$n;
            $n++;
        }

        return $slug;
    }

    private function writeAll(array $pages): void
    {
        if (! is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0775, true);
        }

        $written = file_put_contents(
            $this->path,
            json_encode(array_values($pages), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        if ($written === false) {
            throw new \RuntimeException('Could not save the Page Builder CMS data.');
        }

        app(CmsCrmBackupManager::class)->markDirty('cms-json');
    }
}
