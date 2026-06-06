<?php

namespace App\Support;

/**
 * Declarative schema for the About-page CMS. Every section type lists the exact
 * fields the editor renders and the controller sanitizes — one source of truth
 * shared by the admin form, the admin index, and the validation layer.
 *
 * Field types understood by the editor and sanitizer:
 *   text | textarea | image | icon | select | checkbox | repeater
 * A `repeater` carries its own nested `fields` (an editable, reorderable list).
 */
class AboutSchema
{
    /** All section types, keyed by type slug. */
    public static function types(): array
    {
        return [
            'hero' => [
                'label' => 'Hero',
                'icon' => 'sparkles',
                'desc' => 'Top banner — eyebrow, headline, lede, buttons, trust metrics and the photo collage.',
                'fields' => [
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow'],
                    ['key' => 'heading_pre', 'type' => 'text', 'label' => 'Headline — start'],
                    ['key' => 'heading_highlight', 'type' => 'text', 'label' => 'Headline — highlighted words'],
                    ['key' => 'heading_mid', 'type' => 'text', 'label' => 'Headline — middle'],
                    ['key' => 'heading_em', 'type' => 'text', 'label' => 'Headline — emphasised ending'],
                    ['key' => 'lede', 'type' => 'textarea', 'label' => 'Lede paragraph'],
                    ['key' => 'actions', 'type' => 'repeater', 'label' => 'Buttons', 'item' => 'Button', 'fields' => [
                        ['key' => 'label', 'type' => 'text', 'label' => 'Label'],
                        ['key' => 'href', 'type' => 'text', 'label' => 'Link (URL or #anchor)'],
                        ['key' => 'icon', 'type' => 'icon', 'label' => 'Icon'],
                        ['key' => 'style', 'type' => 'select', 'label' => 'Style', 'options' => ['primary' => 'Primary', 'ghost' => 'Ghost']],
                    ]],
                    ['key' => 'metrics', 'type' => 'repeater', 'label' => 'Trust metrics', 'item' => 'Metric', 'fields' => [
                        ['key' => 'value', 'type' => 'text', 'label' => 'Value'],
                        ['key' => 'label', 'type' => 'text', 'label' => 'Label'],
                    ]],
                    ['key' => 'photo_lg', 'type' => 'image', 'label' => 'Collage photo — large'],
                    ['key' => 'photo_sm', 'type' => 'image', 'label' => 'Collage photo — small'],
                    ['key' => 'badge_icon', 'type' => 'icon', 'label' => 'Badge icon'],
                    ['key' => 'badge_title', 'type' => 'text', 'label' => 'Badge title'],
                    ['key' => 'badge_subtitle', 'type' => 'text', 'label' => 'Badge subtitle'],
                ],
            ],

            'cards' => [
                'label' => 'Vision & Mission cards',
                'icon' => 'columns-2',
                'desc' => 'A heading plus a row of accent cards (icon, tag, heading, body).',
                'fields' => [
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow'],
                    ['key' => 'heading', 'type' => 'text', 'label' => 'Section heading'],
                    ['key' => 'cards', 'type' => 'repeater', 'label' => 'Cards', 'item' => 'Card', 'fields' => [
                        ['key' => 'accent', 'type' => 'select', 'label' => 'Accent colour', 'options' => ['' => 'Default', 'vision' => 'Vision (teal)', 'mission' => 'Mission (gold)']],
                        ['key' => 'icon', 'type' => 'icon', 'label' => 'Icon'],
                        ['key' => 'tag', 'type' => 'text', 'label' => 'Tag'],
                        ['key' => 'heading', 'type' => 'textarea', 'label' => 'Heading'],
                        ['key' => 'body', 'type' => 'textarea', 'label' => 'Body'],
                    ]],
                ],
            ],

            'pillars' => [
                'label' => 'Pillars (text + image rows)',
                'icon' => 'rows-3',
                'desc' => 'Alternating text/image rows — each with an eyebrow, heading, body, chips and a photo.',
                'fields' => [
                    ['key' => 'items', 'type' => 'repeater', 'label' => 'Pillars', 'item' => 'Pillar', 'fields' => [
                        ['key' => 'anchor', 'type' => 'text', 'label' => 'Anchor id (for #links)'],
                        ['key' => 'reverse', 'type' => 'checkbox', 'label' => 'Reverse layout (image on left)'],
                        ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow'],
                        ['key' => 'heading', 'type' => 'textarea', 'label' => 'Heading'],
                        ['key' => 'body', 'type' => 'textarea', 'label' => 'Body'],
                        ['key' => 'image', 'type' => 'image', 'label' => 'Photo'],
                        ['key' => 'image_alt', 'type' => 'text', 'label' => 'Photo alt text'],
                        ['key' => 'tag_icon', 'type' => 'icon', 'label' => 'Photo tag — icon'],
                        ['key' => 'tag_label', 'type' => 'text', 'label' => 'Photo tag — label'],
                        ['key' => 'chips', 'type' => 'repeater', 'label' => 'Chips', 'item' => 'Chip', 'fields' => [
                            ['key' => 'icon', 'type' => 'icon', 'label' => 'Icon'],
                            ['key' => 'label', 'type' => 'text', 'label' => 'Label'],
                        ]],
                    ]],
                ],
            ],

            'impact' => [
                'label' => 'Impact stats',
                'icon' => 'bar-chart-3',
                'desc' => 'A heading, intro and a grid of stat cards (icon, value, suffix, label).',
                'fields' => [
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow'],
                    ['key' => 'heading', 'type' => 'text', 'label' => 'Section heading'],
                    ['key' => 'intro', 'type' => 'textarea', 'label' => 'Intro paragraph'],
                    ['key' => 'stats', 'type' => 'repeater', 'label' => 'Stats', 'item' => 'Stat', 'fields' => [
                        ['key' => 'icon', 'type' => 'icon', 'label' => 'Icon'],
                        ['key' => 'value', 'type' => 'text', 'label' => 'Value'],
                        ['key' => 'suffix', 'type' => 'text', 'label' => 'Suffix (e.g. + or %)'],
                        ['key' => 'label', 'type' => 'text', 'label' => 'Label'],
                    ]],
                ],
            ],

            'team' => [
                'label' => 'Team / Founders',
                'icon' => 'users',
                'desc' => 'A heading, intro and a grid of people cards (photo, name, role, bio, desk, LinkedIn).',
                'fields' => [
                    ['key' => 'anchor', 'type' => 'text', 'label' => 'Anchor id (for #links)'],
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow'],
                    ['key' => 'heading', 'type' => 'text', 'label' => 'Section heading'],
                    ['key' => 'intro', 'type' => 'textarea', 'label' => 'Intro paragraph'],
                    ['key' => 'members', 'type' => 'repeater', 'label' => 'People', 'item' => 'Person', 'fields' => [
                        ['key' => 'photo', 'type' => 'image', 'label' => 'Photo'],
                        ['key' => 'name', 'type' => 'text', 'label' => 'Name'],
                        ['key' => 'role', 'type' => 'text', 'label' => 'Role'],
                        ['key' => 'bio', 'type' => 'textarea', 'label' => 'Bio'],
                        ['key' => 'desk', 'type' => 'text', 'label' => 'Desk coverage'],
                        ['key' => 'hand_text', 'type' => 'text', 'label' => 'Hand icon — hover help text'],
                        ['key' => 'linkedin', 'type' => 'text', 'label' => 'Hand icon — click link (URL)'],
                    ]],
                ],
            ],

            'cta' => [
                'label' => 'Call to action',
                'icon' => 'megaphone',
                'desc' => 'Closing banner — copy, buttons, tags and a photo with a stat box.',
                'fields' => [
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow'],
                    ['key' => 'heading', 'type' => 'text', 'label' => 'Heading'],
                    ['key' => 'body', 'type' => 'textarea', 'label' => 'Body'],
                    ['key' => 'actions', 'type' => 'repeater', 'label' => 'Buttons', 'item' => 'Button', 'fields' => [
                        ['key' => 'label', 'type' => 'text', 'label' => 'Label'],
                        ['key' => 'href', 'type' => 'text', 'label' => 'Link (URL or #anchor)'],
                        ['key' => 'icon', 'type' => 'icon', 'label' => 'Icon'],
                        ['key' => 'style', 'type' => 'select', 'label' => 'Style', 'options' => ['primary' => 'Primary', 'ghost' => 'Ghost']],
                    ]],
                    ['key' => 'tags', 'type' => 'repeater', 'label' => 'Tags', 'item' => 'Tag', 'fields' => [
                        ['key' => 'icon', 'type' => 'icon', 'label' => 'Icon'],
                        ['key' => 'label', 'type' => 'text', 'label' => 'Label'],
                    ]],
                    ['key' => 'image', 'type' => 'image', 'label' => 'Photo'],
                    ['key' => 'image_alt', 'type' => 'text', 'label' => 'Photo alt text'],
                    ['key' => 'stat_num', 'type' => 'text', 'label' => 'Stat number'],
                    ['key' => 'stat_label', 'type' => 'text', 'label' => 'Stat label'],
                ],
            ],
        ];
    }

    /** Metadata for one type (or null when unknown). */
    public static function type(string $type): ?array
    {
        return self::types()[$type] ?? null;
    }

    public static function isType(string $type): bool
    {
        return isset(self::types()[$type]);
    }

    /** A blank data payload for a new section of the given type. */
    public static function blank(string $type): array
    {
        return self::blankItem(self::type($type)['fields'] ?? []);
    }

    /** Blank values for a set of fields. Repeaters get one starter item so the
     *  live editor always has something to edit/duplicate (empty items are
     *  dropped again on save). */
    private static function blankItem(array $fields): array
    {
        $data = [];
        foreach ($fields as $field) {
            $data[$field['key']] = self::blankValue($field);
        }

        return $data;
    }

    private static function blankValue(array $field): mixed
    {
        return match ($field['type']) {
            'repeater' => [self::blankItem($field['fields'] ?? [])],
            'checkbox' => false,
            'select' => array_key_first($field['options'] ?? ['' => '']),
            default => '',
        };
    }
}
