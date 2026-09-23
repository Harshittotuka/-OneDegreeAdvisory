<?php

/**
 * Georgia — hand-written guide copy (see App\Support\CountryGuideCopy).
 *
 * Rewritten 22 Sep 2026. 192 impressions in three months at position 13.45.
 * Search Console shows the demand around Georgia is overwhelmingly medical and
 * regulatory — `nmc approved universities in georgia`, `is mbbs from georgia
 * valid in india`, `how much neet score is required for mbbs in georgia` — all
 * ranking between 45 and 88. The copy below answers that intent head-on rather
 * than describing Georgia in general terms.
 *
 * Figures are the ones this page's own cards and cost tables already state.
 */

return [
    // The day this copy was written, as a literal. Not a file mtime:
    // git does not restore mtimes, so a checkout would re-date every
    // guide to the deploy and the sitemap would start claiming they
    // all changed today. Bump it by hand when the copy changes.
    'updated' => '2026-09-21',

    'seo_title' => 'Study in Georgia for Indian Students | One Degree Advisory',

    'seo_description' => 'Georgian public universities charge from about $2,500 a year, and the medical route is NMC-recognised. An honest read on whether it suits you.',

    'sections' => [
        'why' => [
            'heading' => 'Why Georgia, and who it actually suits',
            'body' => 'Georgia has become a serious destination for Indian students for two reasons that rarely appear together: tuition at public universities starts around $2,500 a year, and its medical faculties are recognised by the WHO and the NMC. Higher education here follows the Bologna Process, so credits and degrees read the way European ones do. Tbilisi State University, founded in 1918, is the oldest in the Caucasus and anchors a system that is small but genuinely international.',
        ],

        'courses' => [
            'heading' => 'Medicine first, but not only medicine',
            'body' => 'Most students arriving from India are here for the six-year MD or MBBS route, and that is the path with the deepest infrastructure and the clearest regulatory position. Business, IT and engineering programmes in English exist alongside it, mainly in Tbilisi and Batumi. If medicine is what you are weighing, read our MBBS in Georgia desk rather than this page — it covers NMC Gazette compliance and NEET requirements properly.',
        ],

        'intakes' => [
            'heading' => 'Intakes, and the NEET timing that governs them',
            'body' => 'September to October is the primary intake, with a smaller February to March round. For medical applicants the sequence matters more than the deadline: a qualifying NEET result has to exist before an admission letter has any standing with the NMC, so the realistic planning window runs from the NEET result through to visa filing over the summer. Non-medical applicants have a more forgiving timeline, but should still be applying by spring.',
        ],

        'costs' => [
            'heading' => 'What Georgia costs over a full course',
            'body' => 'Undergraduate tuition at a public university runs roughly $2,500 to $5,000 a year, and living costs in Tbilisi sit around $350 to $600 a month — among the lowest of any destination with recognised medical faculties. Students may work up to 40 hours a week, which is unusually permissive. The honest caution is that a six-year medical course is a six-year commitment: judge it on the total, not the first year\'s invoice.',
        ],
    ],

    /*
     * Straight from Search Console: `nmc approved universities in georgia` (37
     * impressions, position 75), `is mbbs from georgia valid in india`,
     * `is neet required for mbbs in georgia`, `cheapest universities in
     * georgia`. Every one of them is a question, and the site was ranking in
     * the 50s to 90s for all of them without answering any of them plainly.
     *
     * Deliberately general on regulation: these answers point at the rule and
     * at our MBBS desk rather than quoting a NEET cut-off or a university list,
     * because those change and a wrong number here would be worse than none.
     */
    'faq' => [
        [
            'q' => 'Is an MBBS degree from Georgia valid in India?',
            'a' => 'A medical degree from a recognised Georgian university can be the basis for practising in India, but the degree alone is not what allows it. You must have qualified NEET before admission, study at an institution that meets the National Medical Commission\'s requirements, and pass the Indian screening examination on return. Treat any advisor who describes a Georgian degree as automatically valid in India with caution.',
        ],
        [
            'q' => 'Is NEET required to study MBBS in Georgia?',
            'a' => 'Yes, for any Indian student who intends to practise medicine in India. A qualifying NEET result must exist before you take admission — obtaining it afterwards does not repair the position, and an admission letter issued without one carries no weight with the National Medical Commission. Students who do not plan to return to India are in a different position, and should get that confirmed in writing before committing.',
        ],
        [
            'q' => 'How much does it cost to study in Georgia?',
            'a' => 'Undergraduate tuition at a public university runs roughly $2,500 to $5,000 a year, with living costs in Tbilisi around $350 to $600 a month — among the lowest of any destination with internationally recognised medical faculties. For a six-year medical course, judge the total across all six years rather than the first year\'s invoice.',
        ],
        [
            'q' => 'Can I work while studying in Georgia?',
            'a' => 'Georgia permits international students to work up to 40 hours a week, which is unusually permissive compared with most European destinations. In practice, a full medical timetable leaves little room for it, so it is better treated as an option than as part of the funding plan.',
        ],
        [
            'q' => 'Which is better for medicine — Georgia or another destination?',
            'a' => 'It depends on where you intend to practise, not on which country markets itself best. Georgia is strong on cost and on recognised faculties; other destinations are stronger on post-study work rights or clinical exposure. Our MBBS in Georgia desk goes through the regulatory position and the honest trade-offs in more detail than a country page can.',
        ],
    ],

    'indian_students' => [
        'subtitle' => 'Indian students in Georgia',
        // The view omits the space before a fragment that opens with
        // punctuation, so this one does not start with a dash.
        'heading_before' => 'Recognised faculties,',
        'heading_highlight' => 'the lowest tuition in the region',
        'heading_after' => 'and the paperwork done properly.',
    ],
];
