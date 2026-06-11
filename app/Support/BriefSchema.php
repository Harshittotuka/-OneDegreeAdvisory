<?php

namespace App\Support;

/**
 * Declarative schema for the Brief Page Builder. Every block type lists the
 * exact fields the builder renders and the controller sanitizes — one source of
 * truth shared by the editor, the live preview, and the public renderer.
 *
 * Field types understood by the editor and sanitizer:
 *   text | textarea | richtext | image | icon | select | checkbox | color | repeater
 * A `repeater` carries its own nested `fields` (an editable, reorderable list).
 *
 * Every block also gets three "appearance" fields appended (see withAppearance):
 *   surface (none/card/tint/gradient), accent (overrides --file-orange),
 *   accent2 (overrides --file-blue) — applied as CSS vars on the block wrapper,
 *   which recolours the .odp-* accents inside it.
 */
class BriefSchema
{
    /** All block types, keyed by type slug. Order = the "Add block" palette order. */
    public static function types(): array
    {
        $surfaceOpts = ['' => 'Plain', 'card' => 'White card', 'tint' => 'Soft tint', 'gradient' => 'Brand gradient'];

        $types = [
            'hero' => [
                'label' => 'Hero',
                'icon' => 'sparkles',
                'desc' => 'Big gradient headline, intro copy, action buttons and a highlights side panel.',
                'fields' => [
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow'],
                    ['key' => 'title', 'type' => 'textarea', 'label' => 'Title (big gradient heading)'],
                    ['key' => 'copy', 'type' => 'textarea', 'label' => 'Intro paragraph'],
                    ['key' => 'actions', 'type' => 'repeater', 'label' => 'Buttons', 'item' => 'Button', 'fields' => [
                        ['key' => 'label', 'type' => 'text', 'label' => 'Label'],
                        ['key' => 'icon', 'type' => 'icon', 'label' => 'Icon'],
                        ['key' => 'href', 'type' => 'text', 'label' => 'Link'],
                        ['key' => 'style', 'type' => 'select', 'label' => 'Style', 'options' => ['primary' => 'Primary (gradient)', 'secondary' => 'Secondary (outline)']],
                    ]],
                    ['key' => 'panel_heading', 'type' => 'text', 'label' => 'Side panel — heading'],
                    ['key' => 'panel_items', 'type' => 'repeater', 'label' => 'Side panel — list', 'item' => 'Item', 'fields' => [
                        ['key' => 'icon', 'type' => 'icon', 'label' => 'Icon'],
                        ['key' => 'text', 'type' => 'text', 'label' => 'Text'],
                    ]],
                ],
            ],

            'country_banner' => [
                'label' => 'Country banner',
                'icon' => 'flag',
                'desc' => 'A flag image beside a kicker + headline, on an orange band.',
                'fields' => [
                    ['key' => 'flag', 'type' => 'image', 'label' => 'Flag / image (URL or upload)'],
                    ['key' => 'kicker', 'type' => 'text', 'label' => 'Kicker'],
                    ['key' => 'heading', 'type' => 'textarea', 'label' => 'Headline'],
                ],
            ],

            'callout' => [
                'label' => 'Callout',
                'icon' => 'megaphone',
                'desc' => 'A coloured action box — icon, bold label, paragraph.',
                'fields' => [
                    ['key' => 'icon', 'type' => 'icon', 'label' => 'Icon'],
                    ['key' => 'label', 'type' => 'text', 'label' => 'Label'],
                    ['key' => 'body', 'type' => 'textarea', 'label' => 'Body'],
                ],
            ],

            'brief_cards' => [
                'label' => 'Highlight cards',
                'icon' => 'layout-grid',
                'desc' => 'Stacked highlight cards with a HIGH/MEDIUM badge and tags.',
                'fields' => [
                    ['key' => 'label', 'type' => 'text', 'label' => 'Section label'],
                    ['key' => 'cards', 'type' => 'repeater', 'label' => 'Cards', 'item' => 'Card', 'fields' => [
                        ['key' => 'title', 'type' => 'text', 'label' => 'Title'],
                        ['key' => 'body', 'type' => 'textarea', 'label' => 'Body'],
                        ['key' => 'level', 'type' => 'select', 'label' => 'Badge', 'options' => ['high' => 'HIGH', 'medium' => 'MEDIUM', '' => 'None']],
                        ['key' => 'tags', 'type' => 'repeater', 'label' => 'Tags', 'item' => 'Tag', 'fields' => [
                            ['key' => 'text', 'type' => 'text', 'label' => 'Tag'],
                        ]],
                    ]],
                ],
            ],

            'pitch' => [
                'label' => 'Pitch panel',
                'icon' => 'panels-top-left',
                'desc' => 'A gradient panel: heading, intro, and two columns of bullet points.',
                'fields' => [
                    ['key' => 'label', 'type' => 'text', 'label' => 'Section label'],
                    ['key' => 'heading', 'type' => 'textarea', 'label' => 'Heading'],
                    ['key' => 'intro', 'type' => 'textarea', 'label' => 'Intro paragraph'],
                    ['key' => 'columns', 'type' => 'repeater', 'label' => 'Columns', 'item' => 'Column', 'fields' => [
                        ['key' => 'heading', 'type' => 'text', 'label' => 'Column heading'],
                        ['key' => 'items', 'type' => 'repeater', 'label' => 'Points', 'item' => 'Point', 'fields' => [
                            ['key' => 'text', 'type' => 'text', 'label' => 'Point'],
                        ]],
                    ]],
                ],
            ],

            'split' => [
                'label' => 'Split info cards',
                'icon' => 'columns-2',
                'desc' => 'Two (or more) info cards side by side — heading, body and a bullet list.',
                'fields' => [
                    ['key' => 'label', 'type' => 'text', 'label' => 'Section label'],
                    ['key' => 'cards', 'type' => 'repeater', 'label' => 'Cards', 'item' => 'Card', 'fields' => [
                        ['key' => 'heading', 'type' => 'text', 'label' => 'Heading'],
                        ['key' => 'body', 'type' => 'textarea', 'label' => 'Body'],
                        ['key' => 'items', 'type' => 'repeater', 'label' => 'Bullet list', 'item' => 'Item', 'fields' => [
                            ['key' => 'text', 'type' => 'text', 'label' => 'Item (HTML allowed)'],
                        ]],
                        ['key' => 'tone', 'type' => 'select', 'label' => 'Tone', 'options' => ['' => 'Default', 'warn' => 'Warning (orange)', 'good' => 'Positive (green)']],
                    ]],
                ],
            ],

            'card_grid' => [
                'label' => 'Card grid (tiles)',
                'icon' => 'grid-3x3',
                'desc' => 'A grid of dark gradient tiles — emoji, title, meta and a line of copy.',
                'fields' => [
                    ['key' => 'label', 'type' => 'text', 'label' => 'Section label'],
                    ['key' => 'cards', 'type' => 'repeater', 'label' => 'Tiles', 'item' => 'Tile', 'fields' => [
                        ['key' => 'emoji', 'type' => 'text', 'label' => 'Emoji / flag'],
                        ['key' => 'title', 'type' => 'text', 'label' => 'Title'],
                        ['key' => 'meta', 'type' => 'text', 'label' => 'Meta (small)'],
                        ['key' => 'body', 'type' => 'textarea', 'label' => 'Body'],
                    ]],
                ],
            ],

            'table' => [
                'label' => 'Table',
                'icon' => 'table',
                'desc' => 'A styled table — header row plus rows of cells with optional tone colours.',
                'fields' => [
                    ['key' => 'label', 'type' => 'text', 'label' => 'Section label'],
                    ['key' => 'headings', 'type' => 'repeater', 'label' => 'Column headings', 'item' => 'Heading', 'fields' => [
                        ['key' => 'text', 'type' => 'text', 'label' => 'Heading'],
                    ]],
                    ['key' => 'rows', 'type' => 'repeater', 'label' => 'Rows', 'item' => 'Row', 'fields' => [
                        ['key' => 'cells', 'type' => 'repeater', 'label' => 'Cells', 'item' => 'Cell', 'fields' => [
                            ['key' => 'text', 'type' => 'text', 'label' => 'Cell'],
                            ['key' => 'tone', 'type' => 'select', 'label' => 'Tone', 'options' => ['' => 'Default', 'key' => 'Key (blue, bold)', 'good' => 'Positive (green)', 'warn' => 'Warning (orange)']],
                        ]],
                    ]],
                    ['key' => 'note', 'type' => 'textarea', 'label' => 'Footnote (optional)'],
                ],
            ],

            'talk' => [
                'label' => 'Talking points',
                'icon' => 'message-square-quote',
                'desc' => 'Numbered talking points.',
                'fields' => [
                    ['key' => 'label', 'type' => 'text', 'label' => 'Section label'],
                    ['key' => 'quoted', 'type' => 'checkbox', 'label' => 'Wrap each point in “quotes”'],
                    ['key' => 'items', 'type' => 'repeater', 'label' => 'Points', 'item' => 'Point', 'fields' => [
                        ['key' => 'text', 'type' => 'textarea', 'label' => 'Point'],
                    ]],
                ],
            ],

            'timeline' => [
                'label' => 'Timeline / key dates',
                'icon' => 'calendar-clock',
                'desc' => 'Date + detail rows.',
                'fields' => [
                    ['key' => 'label', 'type' => 'text', 'label' => 'Section label'],
                    ['key' => 'rows', 'type' => 'repeater', 'label' => 'Rows', 'item' => 'Row', 'fields' => [
                        ['key' => 'date', 'type' => 'text', 'label' => 'Date'],
                        ['key' => 'detail', 'type' => 'textarea', 'label' => 'Detail'],
                    ]],
                ],
            ],

            'tip' => [
                'label' => 'Tip / quote',
                'icon' => 'lightbulb',
                'desc' => 'A kicker plus an italic tip or quote, on a left accent rule.',
                'fields' => [
                    ['key' => 'kicker', 'type' => 'text', 'label' => 'Kicker'],
                    ['key' => 'body', 'type' => 'textarea', 'label' => 'Body'],
                ],
            ],

            'sources' => [
                'label' => 'Sources / links',
                'icon' => 'link',
                'desc' => 'A list of labelled external links.',
                'fields' => [
                    ['key' => 'label', 'type' => 'text', 'label' => 'Section label'],
                    ['key' => 'links', 'type' => 'repeater', 'label' => 'Links', 'item' => 'Link', 'fields' => [
                        ['key' => 'text', 'type' => 'text', 'label' => 'Label'],
                        ['key' => 'href', 'type' => 'text', 'label' => 'URL'],
                    ]],
                ],
            ],

            'cta_band' => [
                'label' => 'CTA band',
                'icon' => 'megaphone',
                'desc' => 'A full-width gradient call-to-action with a button.',
                'fields' => [
                    ['key' => 'heading', 'type' => 'text', 'label' => 'Heading'],
                    ['key' => 'body', 'type' => 'textarea', 'label' => 'Body'],
                    ['key' => 'btn_label', 'type' => 'text', 'label' => 'Button label'],
                    ['key' => 'btn_icon', 'type' => 'icon', 'label' => 'Button icon'],
                    ['key' => 'btn_href', 'type' => 'text', 'label' => 'Button link'],
                ],
            ],

            'dest_strip' => [
                'label' => 'Destinations strip',
                'icon' => 'globe',
                'desc' => 'A centred row of flag tiles (country code → flag).',
                'fields' => [
                    ['key' => 'label', 'type' => 'text', 'label' => 'Heading'],
                    ['key' => 'items', 'type' => 'repeater', 'label' => 'Destinations', 'item' => 'Destination', 'fields' => [
                        ['key' => 'code', 'type' => 'text', 'label' => 'Country code (e.g. de) — blank = 🌐'],
                        ['key' => 'name', 'type' => 'text', 'label' => 'Name'],
                    ]],
                ],
            ],

            'journey' => [
                'label' => 'Journey steps',
                'icon' => 'route',
                'desc' => 'Numbered step cards, each with a list of sub-items, ending in a flourish.',
                'fields' => [
                    ['key' => 'title', 'type' => 'textarea', 'label' => 'Section title'],
                    ['key' => 'steps', 'type' => 'repeater', 'label' => 'Steps', 'item' => 'Step', 'fields' => [
                        ['key' => 'label', 'type' => 'text', 'label' => 'Step label'],
                        ['key' => 'heading', 'type' => 'text', 'label' => 'Step heading'],
                        ['key' => 'items', 'type' => 'repeater', 'label' => 'Sub-items', 'item' => 'Item', 'fields' => [
                            ['key' => 'name', 'type' => 'text', 'label' => 'Name'],
                            ['key' => 'desc', 'type' => 'textarea', 'label' => 'Description'],
                        ]],
                    ]],
                    ['key' => 'final_title', 'type' => 'text', 'label' => 'Final card — title'],
                    ['key' => 'final_body', 'type' => 'textarea', 'label' => 'Final card — body'],
                ],
            ],

            'vouchers' => [
                'label' => 'Voucher cards',
                'icon' => 'gift',
                'desc' => 'A row of voucher/referral cards.',
                'fields' => [
                    ['key' => 'title', 'type' => 'text', 'label' => 'Heading'],
                    ['key' => 'subtitle', 'type' => 'textarea', 'label' => 'Subtitle'],
                    ['key' => 'cards', 'type' => 'repeater', 'label' => 'Vouchers', 'item' => 'Voucher', 'fields' => [
                        ['key' => 'tier', 'type' => 'text', 'label' => 'Tier'],
                        ['key' => 'icon', 'type' => 'text', 'label' => 'Emoji'],
                        ['key' => 'amount', 'type' => 'text', 'label' => 'Amount'],
                        ['key' => 'variant', 'type' => 'select', 'label' => 'Style', 'options' => ['explorer' => 'Light', 'achiever-r' => 'Orange', 'infinity' => 'Blue']],
                        ['key' => 'badge', 'type' => 'text', 'label' => 'Badge (optional)'],
                    ]],
                ],
            ],

            'pricing' => [
                'label' => 'Pricing plans',
                'icon' => 'badge-dollar-sign',
                'desc' => 'Pricing plan cards with feature lists and an enrol button.',
                'fields' => [
                    ['key' => 'heading', 'type' => 'text', 'label' => 'Heading'],
                    ['key' => 'sub', 'type' => 'text', 'label' => 'Subheading'],
                    ['key' => 'enrol_href', 'type' => 'text', 'label' => 'Enrol link (all plans)'],
                    ['key' => 'plans', 'type' => 'repeater', 'label' => 'Plans', 'item' => 'Plan', 'fields' => [
                        ['key' => 'variant', 'type' => 'select', 'label' => 'Style', 'options' => ['starter' => 'Starter', 'achiever' => 'Featured (orange)', 'elite' => 'Elite (blue)']],
                        ['key' => 'name', 'type' => 'text', 'label' => 'Name'],
                        ['key' => 'badge', 'type' => 'text', 'label' => 'Badge'],
                        ['key' => 'price', 'type' => 'text', 'label' => 'Price'],
                        ['key' => 'desc', 'type' => 'textarea', 'label' => 'Description'],
                        ['key' => 'features', 'type' => 'repeater', 'label' => 'Features', 'item' => 'Feature', 'fields' => [
                            ['key' => 'text', 'type' => 'text', 'label' => 'Feature (HTML allowed)'],
                        ]],
                        ['key' => 'btn_label', 'type' => 'text', 'label' => 'Button label (blank = “Enrol Now”)'],
                        ['key' => 'btn_href', 'type' => 'text', 'label' => 'Button link (blank = shared enrol link)'],
                    ]],
                ],
            ],

            'disclaimer' => [
                'label' => 'Disclaimer list',
                'icon' => 'triangle-alert',
                'desc' => 'A two-column bullet list inside a left-accent card.',
                'fields' => [
                    ['key' => 'heading', 'type' => 'text', 'label' => 'Heading'],
                    ['key' => 'items', 'type' => 'repeater', 'label' => 'Items', 'item' => 'Item', 'fields' => [
                        ['key' => 'text', 'type' => 'textarea', 'label' => 'Item (HTML allowed)'],
                    ]],
                ],
            ],

            'heading' => [
                'label' => 'Section heading',
                'icon' => 'heading',
                'desc' => 'A label + heading + sub-paragraph, to introduce a section.',
                'fields' => [
                    ['key' => 'label', 'type' => 'text', 'label' => 'Eyebrow label'],
                    ['key' => 'heading', 'type' => 'text', 'label' => 'Heading'],
                    ['key' => 'sub', 'type' => 'textarea', 'label' => 'Sub-paragraph'],
                ],
            ],

            'rich_text' => [
                'label' => 'Rich text',
                'icon' => 'pilcrow',
                'desc' => 'A free block of formatted text (basic HTML allowed).',
                'fields' => [
                    ['key' => 'html', 'type' => 'richtext', 'label' => 'Content'],
                ],
            ],

            'image' => [
                'label' => 'Image',
                'icon' => 'image',
                'desc' => 'A single image with optional caption.',
                'fields' => [
                    ['key' => 'src', 'type' => 'image', 'label' => 'Image'],
                    ['key' => 'alt', 'type' => 'text', 'label' => 'Alt text'],
                    ['key' => 'caption', 'type' => 'text', 'label' => 'Caption'],
                    ['key' => 'rounded', 'type' => 'select', 'label' => 'Corners', 'options' => ['16' => 'Rounded', '0' => 'Square', '999' => 'Circle']],
                ],
            ],

            'card' => [
                'label' => 'Card',
                'icon' => 'square-square',
                'desc' => 'A single flexible card — image or icon, title, text and a button. Drop several across a row to build a card section.',
                'fields' => [
                    ['key' => 'style', 'type' => 'select', 'label' => 'Card style', 'options' => ['plain' => 'Light card', 'tile' => 'Dark tile', 'outline' => 'Outline']],
                    ['key' => 'image', 'type' => 'image', 'label' => 'Top image (optional)'],
                    ['key' => 'emoji', 'type' => 'text', 'label' => 'Emoji (optional)'],
                    ['key' => 'icon', 'type' => 'icon', 'label' => 'Icon (optional)'],
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow'],
                    ['key' => 'title', 'type' => 'text', 'label' => 'Title'],
                    ['key' => 'body', 'type' => 'textarea', 'label' => 'Body'],
                    ['key' => 'btn_label', 'type' => 'text', 'label' => 'Button label (optional)'],
                    ['key' => 'btn_href', 'type' => 'text', 'label' => 'Button link'],
                    ['key' => 'btn_icon', 'type' => 'icon', 'label' => 'Button icon'],
                ],
            ],

            'text' => [
                'label' => 'Text',
                'icon' => 'type',
                'desc' => 'A paragraph / rich-text block (basic HTML allowed).',
                'fields' => [
                    ['key' => 'body', 'type' => 'richtext', 'label' => 'Text'],
                    ['key' => 'align', 'type' => 'select', 'label' => 'Align', 'options' => ['left' => 'Left', 'center' => 'Center', 'right' => 'Right']],
                    ['key' => 'size', 'type' => 'select', 'label' => 'Size', 'options' => ['' => 'Normal', 'lg' => 'Large', 'sm' => 'Small']],
                ],
            ],

            'button' => [
                'label' => 'Button',
                'icon' => 'mouse-pointer-click',
                'desc' => 'A standalone call-to-action button — restyle, reshape, resize, recolour and link it anywhere.',
                'fields' => [
                    ['key' => 'label', 'type' => 'text', 'label' => 'Label'],
                    ['key' => 'href', 'type' => 'text', 'label' => 'Link (URL, /page or #section)'],
                    ['key' => 'icon', 'type' => 'icon', 'label' => 'Icon (optional)'],
                    ['key' => 'style', 'type' => 'select', 'label' => 'Style', 'options' => ['gradient' => 'Gradient', 'solid' => 'Solid (accent colour)', 'outline' => 'Outline', 'ghost' => 'Text link']],
                    ['key' => 'size', 'type' => 'select', 'label' => 'Size', 'options' => ['' => 'Medium', 'sm' => 'Small', 'lg' => 'Large']],
                    ['key' => 'shape', 'type' => 'select', 'label' => 'Shape', 'options' => ['pill' => 'Pill', 'rounded' => 'Rounded', 'square' => 'Square']],
                    ['key' => 'align', 'type' => 'select', 'label' => 'Align', 'options' => ['left' => 'Left', 'center' => 'Center', 'right' => 'Right']],
                    ['key' => 'block', 'type' => 'checkbox', 'label' => 'Full width'],
                ],
            ],

            'divider' => [
                'label' => 'Divider',
                'icon' => 'minus',
                'desc' => 'A horizontal rule between blocks.',
                'fields' => [
                    ['key' => 'style', 'type' => 'select', 'label' => 'Style', 'options' => ['line' => 'Solid line', 'dashed' => 'Dashed', 'dots' => 'Dotted']],
                ],
            ],

            'spacer' => [
                'label' => 'Spacer',
                'icon' => 'move-vertical',
                'desc' => 'Adjustable vertical space.',
                'fields' => [
                    ['key' => 'size', 'type' => 'select', 'label' => 'Height', 'options' => ['sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large', 'xl' => 'Extra large']],
                ],
            ],

            'embed' => [
                'label' => 'AI / Embed',
                'icon' => 'sparkles',
                'desc' => 'Paste a self-contained HTML/CSS/JS section (e.g. generated by “Build with AI”). Rendered as-is on the page.',
                'fields' => [
                    ['key' => 'html', 'type' => 'code', 'label' => 'HTML / CSS / JS'],
                ],
            ],
        ];

        // Palette "Basics" — everything else is offered as a ready-made component
        // preset (BriefPresets) or generated via Build-with-AI.
        $contentCats = ['button', 'divider', 'spacer', 'embed'];

        // Append the shared appearance controls to every block + tag category.
        foreach ($types as $slug => &$def) {
            $def['cat'] = in_array($slug, $contentCats, true) ? 'content' : 'section';
            $def['fields'][] = ['key' => 'surface', 'type' => 'select', 'label' => 'Background', 'options' => $surfaceOpts, 'group' => 'appearance'];
            $def['fields'][] = ['key' => 'accent', 'type' => 'color', 'label' => 'Accent colour', 'group' => 'appearance'];
            $def['fields'][] = ['key' => 'accent2', 'type' => 'color', 'label' => 'Secondary colour', 'group' => 'appearance'];
        }
        unset($def);

        return $types;
    }

    public static function type(string $type): ?array
    {
        return self::types()[$type] ?? null;
    }

    public static function isType(string $type): bool
    {
        return array_key_exists($type, self::types());
    }

    /** Fresh, empty data for a new block — repeaters get one starter row. */
    public static function blank(string $type): array
    {
        $def = self::type($type);
        if ($def === null) {
            return [];
        }

        return self::blankFields($def['fields']);
    }

    /** Fresh empty values for an arbitrary field list (used for repeater row templates). */
    public static function blankRow(array $fields): array
    {
        return self::blankFields($fields);
    }

    private static function blankFields(array $fields): array
    {
        $out = [];
        foreach ($fields as $field) {
            if ($field['type'] === 'repeater') {
                $out[$field['key']] = [self::blankFields($field['fields'])];

                continue;
            }
            $out[$field['key']] = $field['type'] === 'checkbox' ? false : '';
        }

        return $out;
    }
}
