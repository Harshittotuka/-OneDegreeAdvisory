<?php

/**
 * Belgium — hand-written guide copy (see App\Support\CountryGuideCopy).
 *
 * Rewritten 22 Sep 2026. Search Console showed this page drawing 373
 * impressions in three months at an average position of 39, and `study in
 * belgium` drawing 195 impressions at position 61.5 — real demand, no ranking.
 * The scraped copy it replaced was the partner page's DOM text joined with
 * "|", from which the view could salvage a single sentence.
 *
 * Every figure below is one the page already states in its own cards and cost
 * tables (tuition, intakes, living costs, English-taught programme count), so
 * the prose and the tables cannot drift apart. The judgement around them is
 * ours.
 */

return [
    // 58 characters, and it names the audience the page is actually for.
    'seo_title' => 'Study in Belgium for Indian Students | One Degree Advisory',

    // 154 characters: leads with the number that makes Belgium worth reading
    // about, then what we do, rather than "Study in Belgium with ...".
    'seo_description' => 'Belgian public universities charge from about €1,000 a year, with 1,100+ English-taught degrees and Schengen access. See if it fits your plan.',

    'sections' => [
        'why' => [
            'heading' => 'Why Belgium is worth a serious look',
            'body' => 'Belgium is one of the few places in Western Europe where a public university degree still costs around €1,000 to €4,500 a year rather than the €15,000 or more that comparable English-taught programmes command elsewhere. It is a small country with two academic traditions — Flemish in the north, French in the south — and more than 1,100 programmes taught in English across them. Brussels being the seat of both the EU and NATO also means the research and internship networks are unusually deep for a country this size.',
        ],

        'courses' => [
            'heading' => 'What Indian students usually come to Belgium for',
            'body' => 'Most of the demand we see is postgraduate: data science and statistics at KU Leuven, food technology and biotechnology at Ghent, and business and international relations in Brussels, where the institutions sit next to the organisations their graduates want to work for. Engineering and life sciences are the strongest fields, and one-year master\'s options exist alongside the standard two-year research routes.',
        ],

        'intakes' => [
            'heading' => 'Intakes, and when you actually need to start',
            'body' => 'The main intake is September to October, with a smaller one in February. The date that matters is not the deadline but the one before it: for a September start, applications should be in by March or April, because Belgian universities assess non-EU credentials slowly and the student visa process needs months rather than weeks behind it. Students who begin in January for a September intake are working with a comfortable timeline. Those who begin in June are not.',
        ],

        'costs' => [
            'heading' => 'What a year in Belgium actually costs',
            'body' => 'Tuition at a public university runs from about €1,000 to €4,500 a year at undergraduate level, which is the reason most students look at Belgium in the first place. Living costs are the larger number: budget €900 to €1,300 a month in Brussels, somewhat less in Leuven, Ghent or Liège. Students may work up to 20 hours a week during term, which helps at the margins but should never be the plan that makes the budget work.',
        ],
    ],

    /*
     * Questions taken from what this page is already shown for in Search
     * Console — `study in belgium` (195 impressions, position 61), `belgium
     * intake` (position 9.2), `study in belgium for international students`,
     * `how to study in belgium` — answered directly rather than alluded to.
     * These render on the page and are emitted as FAQPage structured data.
     */
    'faq' => [
        [
            'q' => 'Can Indian students study in Belgium in English?',
            'a' => 'Yes. Belgian universities run more than 1,100 programmes taught entirely in English, concentrated at master\'s level. Undergraduate teaching is more often in Dutch or French, so for a bachelor\'s degree the English-taught options are real but narrower, and worth checking programme by programme before you commit to the country.',
        ],
        [
            'q' => 'How much does it cost to study in Belgium?',
            'a' => 'Tuition at a public university runs from about €1,000 to €4,500 a year at undergraduate level — low by Western European standards. Living costs are the bigger figure at roughly €900 to €1,300 a month in Brussels, and somewhat less in Leuven, Ghent or Liège. Budget for the living costs first; the tuition is rarely what decides affordability.',
        ],
        [
            'q' => 'When are the intakes in Belgium, and when should I apply?',
            'a' => 'The main intake is September to October, with a smaller one in February. Applications should be submitted by March or April for a September start. Belgian universities assess non-EU qualifications slowly and the student visa process needs months behind it, so the deadline is not the date that matters — the assessment queue in front of it is.',
        ],
        [
            'q' => 'Can I work while studying in Belgium?',
            'a' => 'Students on a residence permit may work up to 20 hours a week during term time. That helps at the margins and covers some living costs, but it should never be the assumption that makes your budget balance — visa applications require you to show you can fund the year without it.',
        ],
        [
            'q' => 'Is a Belgian degree recognised elsewhere in Europe?',
            'a' => 'Belgium is in the European Higher Education Area, so its degrees and credits are structured for recognition across it. A Belgian student residence permit also gives you travel access to the Schengen area, which is part of why students weighing a single EU destination often end up comparing Belgium against far more expensive options.',
        ],
    ],

    // Replaces a band headline that repeated the why-section sentence verbatim,
    // so the same words no longer appear twice on the page.
    'indian_students' => [
        'subtitle' => 'Indian students in Belgium',
        'heading_before' => 'A degree recognised across',
        'heading_highlight' => 'all 26 Schengen countries',
        'heading_after' => 'for the price of one.',
    ],
];
