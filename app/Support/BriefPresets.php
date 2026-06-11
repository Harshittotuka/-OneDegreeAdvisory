<?php

namespace App\Support;

/**
 * Ready-made studio components: real sections (with their data) harvested from
 * the four hand-built brief pages. The studio palette shows each as a live
 * preview thumbnail; dragging one in drops a fully-populated block the user
 * then edits. Source of truth is BriefPageContent::defaults() — the seed
 * templates, independent of any edits made to the live pages.
 */
class BriefPresets
{
    /** slug:sectionId → palette label (order = palette order). */
    private const PICKS = [
        'europe:hero' => 'Hero — gradient headline',
        'destination-new-zealand:banner' => 'Country banner',
        'destination-new-zealand:action' => 'Action callout',
        'destination-new-zealand:highlights' => 'Highlight cards',
        'medicine-and-beyond:pathmap' => 'Tile grid (dark cards)',
        'medicine-and-beyond:p1-split' => 'Split info cards',
        'destination-new-zealand:why' => 'Pitch panel (gradient)',
        'wednesday-briefings:ibc' => 'Comparison table',
        'destination-new-zealand:talk' => 'Numbered talking points',
        'medicine-and-beyond:dates' => 'Timeline / key dates',
        'medicine-and-beyond:p1-head' => 'Section heading',
        'destination-new-zealand:tip' => 'Tip / quote',
        'destination-new-zealand:sources' => 'Sources / links',
        'europe:destinations' => 'Flags strip',
        'europe:journey' => 'Journey steps',
        'europe:vouchers' => 'Voucher cards',
        'europe:packages' => 'Pricing plans',
        'europe:disclaimer' => 'Disclaimer list',
        'medicine-and-beyond:cta' => 'CTA band',
    ];

    /** All presets: key → ['label', 'type', 'data']. */
    public static function all(): array
    {
        $bySection = [];
        foreach ((new BriefPageContent)->defaults() as $page) {
            foreach ($page['sections'] ?? [] as $s) {
                $bySection[($page['slug'] ?? '').':'.($s['id'] ?? '')] = $s;
            }
        }

        // Hand-made presets (not harvested from a page) come first.
        $out = [
            'cta-button' => [
                'label' => 'Button — call to action',
                'type' => 'button',
                'data' => [
                    'label' => 'Book a free consultation',
                    'href' => '/contact',
                    'icon' => 'calendar-check',
                    'style' => 'gradient',
                    'size' => 'lg',
                    'shape' => 'pill',
                    'align' => 'center',
                    'block' => false,
                ],
            ],
        ];

        foreach (self::PICKS as $key => $label) {
            $s = $bySection[$key] ?? null;
            if ($s === null || ! BriefSchema::isType($s['type'] ?? '')) {
                continue;
            }
            $out[str_replace(':', '--', $key)] = [
                'label' => $label,
                'type' => $s['type'],
                'data' => $s['data'] ?? [],
            ];
        }

        return $out;
    }

    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}
