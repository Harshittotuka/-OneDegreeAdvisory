<?php

/**
 * Kazakhstan — hand-written guide copy (see App\Support\CountryGuideCopy).
 *
 * Rewritten 22 Sep 2026. This page draws the second-most impressions of any
 * country guide — 558 in three months at position 13.66 — and the queries
 * behind them are specific and commercial: `cheapest university in kazakhstan
 * for international students` (position 5.9), `study in kazakhstan` (68),
 * `kazakhstan universities` (62), `universities in kazakhstan` (63.5). The copy
 * leads with cost, because that is demonstrably what people are asking.
 *
 * Figures are the ones this page's own cards and cost tables already state.
 */

return [
    'seo_title' => 'Study in Kazakhstan | One Degree Advisory',

    'seo_description' => 'Kazakh public universities charge from about $1,500 a year, with NMC-recognised medical and Bologna-compatible STEM degrees. See if it fits.',

    'sections' => [
        'why' => [
            'heading' => 'Why Kazakhstan is on more shortlists than it used to be',
            'body' => 'Kazakhstan offers something narrow but valuable: recognised degrees at close to the lowest cost of any internationally accredited system, with public university tuition starting around $1,500 a year. Al-Farabi Kazakh National University is globally ranked, the medical faculties are WHO and NMC-recognised, and the country is a Bologna Process participant, so engineering and STEM credits transfer the way European ones do.',
        ],

        'courses' => [
            'heading' => 'Where Kazakhstan is genuinely strong',
            'body' => 'Two fields carry most of the demand. Medicine is the larger one, on the strength of NMC recognition and cost. Engineering and the applied sciences are the quieter case and often the better one — Bologna-compatible, taught in English at the larger universities, and priced well below any Western European equivalent. Almaty alone lists more than 900 courses across 38 institutions; Astana adds another 46.',
        ],

        'intakes' => [
            'heading' => 'Intakes and the documents that slow them down',
            'body' => 'The primary intake is September to October, with a February round behind it. What delays Kazakh admissions is rarely the university: it is document legalisation and attestation, which has to be sequenced correctly and cannot be rushed at the end. Applications should be moving by April for an autumn start, and medical applicants need a qualifying NEET result in hand before an admission letter carries any weight with the NMC.',
        ],

        'costs' => [
            'heading' => 'What Kazakhstan costs, honestly',
            'body' => 'Public university tuition runs about $1,500 to $4,000 a year at undergraduate level, with living costs in Astana around $350 to $600 a month. That combination is why the page you are reading exists — few systems with recognised medical faculties sit at this price. The trade-off is distance from the graduate job markets most families are ultimately aiming at, and that belongs in the decision alongside the fees.',
        ],
    ],

    /*
     * From Search Console: `cheapest university in kazakhstan for international
     * students` already sits at position 5.9 — the strongest non-brand position
     * the site has — while `study in kazakhstan` (68), `kazakhstan universities`
     * (62) and `universities in kazakhstan for international students` (76) sit
     * nowhere. The first question below is written for the query that is
     * already working.
     */
    'faq' => [
        [
            'q' => 'Which are the cheapest universities in Kazakhstan for international students?',
            'a' => 'Public universities are the affordable route: tuition runs from about $1,500 to $4,000 a year at undergraduate level, and living costs in Astana sit around $350 to $600 a month. The cheapest option on paper is rarely the right one — recognition, language of instruction and the state of the hostels vary far more between institutions than the fees do, and that is the comparison worth making.',
        ],
        [
            'q' => 'Are Kazakhstan medical degrees recognised in India?',
            'a' => 'Kazakhstan has medical faculties that meet World Health Organisation listing and the National Medical Commission\'s requirements, which is why it appears on so many shortlists. Recognition attaches to the institution, not to the country, so it has to be checked for the specific university before you pay anything — and Indian students still need a qualifying NEET result before admission and must pass the Indian screening examination to practise on return.',
        ],
        [
            'q' => 'Is Kazakhstan good for engineering and STEM, not just medicine?',
            'a' => 'Yes, and it is the quieter case that often suits students better. Kazakhstan is a Bologna Process participant, so engineering and applied science credits are structured the way European ones are, and the larger universities teach in English. Almaty alone lists more than 900 courses across 38 institutions, with another 46 institutions in Astana.',
        ],
        [
            'q' => 'When should I apply for a Kazakh university?',
            'a' => 'The primary intake is September to October, with a February round behind it, and applications should be moving by April for an autumn start. What delays these admissions is almost never the university — it is document legalisation and attestation, which has a fixed sequence and cannot be compressed at the end.',
        ],
    ],

    'indian_students' => [
        'subtitle' => 'Indian students in Kazakhstan',
        // The view omits the space before a fragment that opens with
        // punctuation, so this one does not start with a dash.
        'heading_before' => 'Recognised degrees at',
        'heading_highlight' => 'close to the lowest cost anywhere',
        'heading_after' => 'if the sequence is right.',
    ],
];
