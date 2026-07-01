<?php

/*
|--------------------------------------------------------------------------
| Student Profiler — questionnaire definition (+ presentation icons)
|--------------------------------------------------------------------------
| The questions themselves are the EXACT set served by the partner gatewayhub
| profiler (edumilestones engine) for each degree level, captured verbatim —
| same wording, same options, same input types — into questions.json:
|
|   Bachelors = 21 questions   (level 1)
|   Masters   = 25 questions   (level 2)
|   Doctorate = 38 questions   (level 3)
|
| To refresh them, re-pull /student-profiler/get-profiling-questions.php from
| the source and regenerate questions.json; nothing else needs to change.
|
| On top of the raw questions this file adds:
|   • the degree-card presentation meta (labels, accents);
|   • a presentation ICON on every radio/chips option. The wizard shows a
|     colourful emoji on top of each choice card. Each "Foo" becomes
|     ['label' => 'Foo', 'icon' => '🎯']. The stored answer value is ALWAYS the
|     label string, so icons are purely visual: submissions and the admin
|     snapshot are unchanged.
|   • a special "engscore" widget for the English-test field (test-type picker
|     + per-skill score inputs).
|
| Icons are emoji (colourful, render on every OS, no external/hotlinked assets,
| all chosen ≤ Emoji 3.0 so they render even on older Windows). Regional-flag
| emoji are deliberately avoided for the country choices (Windows renders them
| as plain letters) — distinctive landmark/symbol emoji are used instead. Icons
| are also chosen to be DISTINCT within each question (no two options on the
| same screen share a glyph).
|
| Field schema (per section → fields[]):
|   type     : radio | chips | select | text | tel | engscore
|   key      : stable answer key (derived from the source question id)
|   label    : exact question text
|   options  : exact choices (radio / chips / select)
|   placeholder / required : input hints
*/

$sections = json_decode((string) file_get_contents(__DIR__ . '/questions.json'), true) ?: [];

$config = [
    'degreeOrder' => ['highschool', 'bachelors', 'masters', 'doctorate'],
    'degrees' => [
        'highschool' => ['label' => 'High School', 'initial' => 'H', 'tag' => 'Class 9–12 student', 'examples' => 'Grade 9 · 10 · 11 · 12', 'accent' => 'green', 'featured' => false],
        'bachelors' => ['label' => 'Bachelor’s', 'initial' => 'B', 'tag' => 'Undergraduate degree', 'examples' => 'BS · BA · BBA · BEng', 'accent' => 'blue', 'featured' => false],
        'masters'   => ['label' => 'Master’s', 'initial' => 'M', 'tag' => 'Postgraduate degree', 'examples' => 'MS · MBA · MA · MEng', 'accent' => 'orange', 'featured' => true],
        'doctorate' => ['label' => 'Doctorate', 'initial' => 'D', 'tag' => 'PhD & research degree', 'examples' => 'PhD · DBA · EdD', 'accent' => 'gold', 'featured' => false],
    ],
    'sections' => $sections,
];

/**
 * Resolve a presentation icon for one option, given its question's label (which
 * tells us the question's theme) and the option text. Pure presentation — never
 * affects the stored value. A closure (not a named function) so re-requiring
 * this file can't trigger a redeclare error.
 */
$iconFor = function (string $fieldLabel, string $opt): string {
    $fl = mb_strtolower($fieldLabel);
    $ol = mb_strtolower($opt);

    // 1) Options with a fixed meaning. The extracurriculars / differentiators /
    //    participation sets are shared verbatim with the evaluator, so they reuse
    //    the evaluator's exact icons; countries use landmark/symbol emoji.
    static $exact = [
        // Extracurriculars engaged in
        'NGO / Social Volunteering' => '🤝',
        'Associations / Clubs'      => '👥',
        'Competitive Sports'        => '⚽',
        'Performance Arts'          => '🎭',
        'Competitions'              => '🏅',
        'Just Hobbies'              => '🎨',
        // Highest level of participation
        'Held Leadership positions'           => '👑',
        'Organized Events with major Success' => '🎉',
        'Just Participated'                   => '🙋',
        'None of the same'                    => '🚫',
        // Still involved?
        'Yes, Heavily'              => '🔥',
        'Somewhat'                  => '👍',
        'No, was earlier involved'  => '⏳',
        // Differentiators
        'Significant International Experience'                                   => '🌍',
        'Family business / Start Ups Exposure'                                   => '🏢',
        'Successful Entrepreneurial Venture'                                     => '🚀',
        'Multiple Industry Certifications (CFA, Six Sigma)'                      => '📜',
        'Publications, Awards, Patents to name'                                  => '🏅',
        'Major Projects Undertaken - 7 Figure impact, double-digit improvements' => '📈',
        // Countries (landmark/symbol — never regional flags, which break on Windows)
        'USA'             => '🗽',
        'UK'              => '🎡',
        'Canada'          => '🍁',
        'Australia'       => '🦘',
        'New Zealand'     => '🥝',
        'Ireland'         => '🍀',
        'Germany'         => '🏰',
        'Rest Europe'     => '🏛️',
        'Malaysia'        => '🏙️',
        'Singapore'       => '🦁',
        'Other Countries' => '🌍',
        // Boards
        'CBSE Board'  => '🏫',
        'ICSE Board'  => '🏛️',
        'IB Board'    => '🌐',
        'State Board' => '🗺️',
        'Other Board' => '📋',
    ];
    if (isset($exact[$opt])) {
        return $exact[$opt];
    }

    // 2) Resolve by the question's theme (detected from its label). These are all
    //    written so each option in the question gets a DISTINCT icon.
    if (str_contains($fl, 'intake')) {
        // By month/season so SEP / JULY / JAN / MAR each differ.
        $m = substr($ol, 0, 3);
        static $months = [
            'jan' => '❄️', 'feb' => '⛄', 'mar' => '🌸', 'apr' => '🌷',
            'may' => '🌼', 'jun' => '🌤️', 'jul' => '🌞', 'aug' => '☀️',
            'sep' => '🍁', 'oct' => '🍂', 'nov' => '🌫️', 'dec' => '🎄',
        ];
        return $months[$m] ?? '🗓️'; // "Later" / anything else
    }
    if (str_contains($fl, 'visa')) {
        return $ol === 'no' ? '✅' : '⚠️';
    }
    if (str_contains($fl, 'gap')) {
        // No gap, then 1 / 2 / 3 / 3+ each distinct.
        if (str_contains($ol, 'no gap') || str_contains($ol, 'no gaps')) return '✅';
        if (str_contains($ol, '+')) return '🛑';
        if (str_starts_with($ol, '1')) return '⏳';
        if (str_starts_with($ol, '2')) return '⌛';
        if (str_starts_with($ol, '3')) return '🗓️';
        return '📆';
    }
    if (str_contains($fl, 'backlog')) {
        // No backlogs, then 1 / 2 / 3-4 / 5+ each distinct.
        if (str_contains($ol, 'no ')) return '✅';
        if (str_starts_with($ol, '1')) return '⚠️';
        if (str_starts_with($ol, '2')) return '🔁';
        if (str_starts_with($ol, '3')) return '🔂';
        return '🚩'; // "More than 5 …"
    }
    if (str_contains($fl, 'status')) {
        return str_contains($ol, 'complet') ? '✅' : '📖';
    }
    if (str_contains($fl, 'ibdp point')) {
        if ($ol === 'n/a') {
            return '➖';
        }
        if (str_starts_with($opt, '24')) return '🥉';
        if (str_starts_with($opt, '32')) return '🥈';
        if (str_starts_with($opt, '39')) return '🥇';
        return '🎯';
    }
    // NOTE: standardised-test score bands, budget tiers and work-experience are
    // NOT resolved here. They are clean ordered tiers, so the loop below assigns
    // them a descending/ascending ladder of icons by rank — see $tieredIcons().
    if (str_contains($fl, 'duration')) {
        // Years pursued → clock faces, so 5Y/4Y/3Y/2Y/1Y each differ.
        if (str_starts_with($ol, '5')) return '🕔';
        if (str_starts_with($ol, '4')) return '🕓';
        if (str_starts_with($ol, '3')) return '🕒';
        if (str_starts_with($ol, '2')) return '🕑';
        if (str_starts_with($ol, '1')) return '🕐';
        return '⏳'; // "Less / Lesser than …"
    }
    if (str_contains($fl, 'country')) {
        return '🌍';
    }

    // 3) Option-level fallbacks (course types, then a generic yes/no).
    if (str_contains($ol, 'degree'))      return '🎓';
    if (str_contains($ol, 'diploma'))     return '📜';
    if (str_contains($ol, 'certificate')) return '📃';
    if ($ol === 'yes') return '✅';
    if ($ol === 'no')  return '🚫';

    return '🔹'; // neutral last resort — should not be reached for the current set
                 // (a real emoji so any future unmatched option still renders on-brand)
};

/**
 * Score bands and budget tiers are ordered best→worst / largest→smallest, so a
 * single repeated icon looks flat. Instead, assign a descending ladder by rank.
 * The "opt-out" rows ("Not Appeared", "No … Constraint") keep their own marker
 * and do NOT consume a ladder rung. All glyphs are Emoji 1.0–3.0 (render as a
 * single colour glyph on every current OS, incl. older Windows 10).
 *
 * @param array<int, mixed> $options ordered option strings for one field
 * @return array<int, array{label:string,icon:string}>
 */
$tieredIcons = function (array $options, array $ladder, string $optOutIcon): array {
    $rank = 0;

    return array_map(function ($opt) use (&$rank, $ladder, $optOutIcon) {
        $label = is_array($opt) ? ($opt['label'] ?? '') : (string) $opt;
        $ol = mb_strtolower($label);
        // Rows that sit outside the scale (no score taken / no budget limit).
        if (str_contains($ol, 'not appeared') || str_contains($ol, 'no ')) {
            return ['label' => $label, 'icon' => $optOutIcon];
        }
        $icon = $ladder[min($rank, count($ladder) - 1)];
        $rank++;

        return ['label' => $label, 'icon' => $icon];
    }, $options);
};

/**
 * Work-experience tiers run least→most, so an ASCENDING ladder reads naturally.
 * The "no experience" row opts out with its own marker. Detected by a leading
 * "no" (covers "No", "No work experience", …) so every other row gets a
 * distinct rung.
 *
 * @param array<int, mixed> $options
 * @return array<int, array{label:string,icon:string}>
 */
$workIcons = function (array $options): array {
    $rank = 0;
    $ladder = ['🌱', '⌛', '💼', '📈', '🏆', '⭐'];

    return array_map(function ($opt) use (&$rank, $ladder) {
        $label = is_array($opt) ? ($opt['label'] ?? '') : (string) $opt;
        if (str_starts_with(mb_strtolower($label), 'no')) {
            return ['label' => $label, 'icon' => '🚫'];
        }
        $icon = $ladder[min($rank, count($ladder) - 1)];
        $rank++;

        return ['label' => $label, 'icon' => $icon];
    }, $options);
};

// Walk every degree's sections and attach an icon to each radio/chips option.
// Text / tel fields have no options and are left exactly as they are.
foreach ($config['sections'] as $degree => &$sections) {
    foreach ($sections as &$section) {
        // Iterate $section['fields'] directly by reference — NOT ($section['fields'] ?? []),
        // because `??` returns a copy and writes through &$field would be lost.
        if (empty($section['fields']) || ! is_array($section['fields'])) {
            continue;
        }
        // Does this section carry a standalone "Overall … Score" TEXT field? If
        // so, the engscore widget absorbs it so all English scores live in ONE
        // unified, per-test block — and the standalone field is hidden. Capture
        // whether it was mandatory so the widget can enforce the overall score.
        $overallRequired = false;
        foreach (($section['fields'] ?? []) as $f) {
            if (($f['type'] ?? '') === 'text' && str_contains(mb_strtolower($f['label'] ?? ''), 'overall')) {
                if (! empty($f['required'])) {
                    $overallRequired = true;
                }
            }
        }

        foreach ($section['fields'] as &$field) {
            $fl0 = mb_strtolower($field['label'] ?? '');

            // Hide the standalone "Overall … Score" text field — the engscore
            // widget below renders the overall input itself (its requiredness is
            // carried over to the widget). Kept in $config but flagged hidden so
            // the JS skips it; nothing is rendered or validated twice.
            if (($field['type'] ?? '') === 'text' && str_contains($fl0, 'overall')) {
                $field['hidden']   = true;
                $field['required'] = false;
                continue;
            }

            // The single free-text "Individual score in IELTS / ToEFL / PTE
            // (Listening, Reading, Writing, Speaking)" box becomes a compact,
            // animated widget — a test-type picker (IELTS/TOEFL/PTE, IELTS
            // pre-selected) plus an overall input and four per-skill inputs, each
            // scale adapting to the chosen test. It keeps the SAME key + label;
            // only the input type changes. The stored answer is an array of
            // strings like ["IELTS","Overall: 7.5","Listening: 7",…], which the
            // generic snapshot/review code already renders as chips.
            if (($field['type'] ?? '') === 'text' && str_contains($fl0, 'individual score')) {
                $field['type'] = 'engscore';
                $field['help'] = 'Add scores for any test you have taken — IELTS, TOEFL and/or PTE. Switch tabs to enter more than one; all are saved.';
                $field['overall'] = true;
                $field['overallRequired'] = $overallRequired;
                // Each test carries TWO scales: per-skill (scale/max/step) and the
                // overall total (oScale/oMax/oStep) — TOEFL's overall is /120.
                $field['tests'] = [
                    ['code' => 'IELTS', 'icon' => '📘', 'scale' => '/ 9',  'max' => '9',  'step' => '0.5', 'oScale' => '/ 9',   'oMax' => '9',   'oStep' => '0.5'],
                    ['code' => 'TOEFL', 'icon' => '📗', 'scale' => '/ 30', 'max' => '30', 'step' => '1',   'oScale' => '/ 120', 'oMax' => '120', 'oStep' => '1'],
                    ['code' => 'PTE',   'icon' => '📙', 'scale' => '/ 90', 'max' => '90', 'step' => '1',   'oScale' => '/ 90',  'oMax' => '90',  'oStep' => '1'],
                ];
                $field['components'] = [
                    ['code' => 'L', 'label' => 'Listening', 'icon' => '👂'],
                    ['code' => 'R', 'label' => 'Reading',   'icon' => '📖'],
                    ['code' => 'W', 'label' => 'Writing',   'icon' => '✍️'],
                    ['code' => 'S', 'label' => 'Speaking',  'icon' => '🗣️'],
                ];
                continue;
            }

            if (! in_array($field['type'] ?? '', ['radio', 'chips'], true)) {
                continue;
            }
            $label   = $field['label'] ?? '';
            $fl      = mb_strtolower($label);
            $options = $field['options'] ?? [];

            if (preg_match('/\b(sat|gmat|gre|ielts|toefl|pte)\b/', $fl)) {
                // Standardised-test score bands (descending): medals, then neutral.
                $field['options'] = $tieredIcons($options, ['🥇', '🥈', '🥉', '🎯', '📊', '📋'], '🚫');
            } elseif (str_contains($fl, 'budget')) {
                // Budget tiers (largest → smallest spend): a magnitude descent.
                $field['options'] = $tieredIcons($options, ['💎', '💰', '💵', '💴'], '♾️');
            } elseif (str_contains($fl, 'work experience')) {
                // Experience tiers (least → most): an ascending ladder.
                $field['options'] = $workIcons($options);
            } else {
                $field['options'] = array_map(
                    fn ($opt) => is_array($opt) ? $opt : ['label' => $opt, 'icon' => $iconFor($label, (string) $opt)],
                    $options
                );
            }
        }
        unset($field);
    }
    unset($section);
}
unset($sections);

return $config;
