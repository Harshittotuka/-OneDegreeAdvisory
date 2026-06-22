<?php

/*
|--------------------------------------------------------------------------
| Student Profiler V2 — questionnaire definition
|--------------------------------------------------------------------------
| Profiler V2 must use the EXACT SAME questions as the original Student
| Profiler (/profiler). Rather than duplicate them (and risk the two drifting
| apart), this re-uses the v1 questionnaire as the single source of truth:
| same degreeOrder, same degree cards, same degree-adaptive sections.
|
| The ONE thing v2 adds is presentation icons. v2 wears the Profile Evaluator
| design, whose option cards show an emoji icon on top of each choice. The
| shared profiler questions store options as plain strings — and v1 must keep
| them that way (its chip UI renders the string directly) — so here, in v2's
| layer ONLY, we attach an icon to every radio/chips option, turning each
| "Foo" into ['label' => 'Foo', 'icon' => '🎯']. The stored answer value is
| ALWAYS the label string, so icons are purely visual: submissions, scoring
| (none) and the admin snapshot are all unchanged.
|
| Icons are emoji (colourful, render on every OS, no external/hotlinked assets)
| and regional-flag emoji are deliberately avoided for the country choices
| (Windows renders them as plain letters) — distinctive landmark/symbol emoji
| are used instead. Same rule the evaluator follows.
*/

$config = require dirname(__DIR__) . '/StudentProfiler/questionnaire.php';

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

    // 2) Resolve by the question's theme (detected from its label).
    if (str_contains($fl, 'intake')) {
        return $ol === 'later' ? '🗓️' : '📅';
    }
    if (str_contains($fl, 'visa')) {
        return $ol === 'no' ? '✅' : '⚠️';
    }
    if (str_contains($fl, 'gap')) {
        return str_contains($ol, 'no gap') ? '✅' : '⏳';
    }
    if (str_contains($fl, 'backlog')) {
        return str_contains($ol, 'no ') ? '✅' : '🔁';
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
    // NOTE: standardised-test score bands and budget tiers are NOT resolved here.
    // They are clean ordered tiers, so the loop below assigns them a descending
    // ladder of icons (medals / magnitude) by rank — see $tieredIcons().
    if (str_contains($fl, 'work experience')) {
        if (str_contains($ol, 'no'))     return '🚫';
        if (str_contains($ol, 'intern')) return '🎓';
        return '💼';
    }
    if (str_contains($fl, 'duration')) {
        return '⏱️';
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

// Walk every degree's sections and attach an icon to each radio/chips option.
// Text / tel fields have no options and are left exactly as they are.
foreach ($config['sections'] as $degree => &$sections) {
    foreach ($sections as &$section) {
        // Iterate $section['fields'] directly by reference — NOT ($section['fields'] ?? []),
        // because `??` returns a copy and writes through &$field would be lost.
        if (empty($section['fields']) || ! is_array($section['fields'])) {
            continue;
        }
        foreach ($section['fields'] as &$field) {
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
