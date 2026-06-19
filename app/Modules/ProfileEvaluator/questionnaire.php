<?php

/*
|--------------------------------------------------------------------------
| Profile Evaluator — questionnaire definition
|--------------------------------------------------------------------------
| A native ODA rebuild of the mim-essay "Evaluate My Profile" tool
| (https://www.mim-essay.com/evaluate-my-profile). The SIX sections, every
| question and every option are captured VERBATIM from that tool — same
| wording, same order, same input types. Only the surrounding presentation
| (hero copy, section framing) is ours.
|
| Unlike the degree-adaptive [student-profiler], this tool is NOT scored and
| NOT degree-branched: one fixed set of sections is shown to everyone. On
| completion the profile is handed to the team for a manual review — exactly
| the same ending as the Student Profiler (review → submit → "we'll get back
| to you" popup). No score, band or shortlist is ever computed or shown.
|
| Field schema (per section → fields[]):
|   type     : radio (single choice) | chips (multi-select) | text
|   key      : stable answer key
|   label    : exact question text (verbatim from mim-essay)
|   options  : exact choices — each is ['label' => '<verbatim>', 'icon' => '<emoji>'].
|              The icon is shown on top of the option card (mim-essay shows a
|              colourful illustration per choice). The stored answer value is
|              ALWAYS the label string, so icons are presentation-only.
|   placeholder / required : input hints
|
| Emoji are used for icons (colourful, render on every OS, no external/hotlinked
| assets). Regional-flag emoji are deliberately avoided for the country choices
| because Windows browsers render them as plain letters — distinctive landmark/
| symbol emoji are used instead.
|
| NOTE on Test Scores: the test-type options on mim-essay load dynamically and
| only "Not planning to take" is present in static markup. The GMAT / GMAT
| Focus / GRE / Executive Assessment set below is the standard mim-essay set.
*/

/** Helper: build an option from a [label, emoji] pair. */
$opt = fn (string $label, string $icon): array => ['label' => $label, 'icon' => $icon];

return [
    'hero' => [
        'eyebrow'  => 'ODA · PROFILE EVALUATION',
        'title'    => 'Want to Study in the Best Schools of the World?',
        'subtitle' => 'The most comprehensive profile evaluation tool on the planet. Answer a few quick questions across six areas — academics, extracurriculars, differentiators, work, tests and target degree — and our advisors will personally review your profile.',
        'cta'      => 'Evaluate Me',
        'points'   => ['6 quick sections', 'Takes about 3 minutes', 'Reviewed by an ODA advisor'],
    ],

    'sections' => [
        /* ── 1 · ACADEMICS ─────────────────────────────────────────── */
        [
            'key'      => 'academics',
            'eyebrow'  => 'Academics',
            'icon'     => '📚',
            'title'    => 'Your academics',
            'subtitle' => 'Tell us about your undergraduate record and any standout achievements.',
            'fields'   => [
                [
                    'type'     => 'radio',
                    'key'      => 'q_cgpa',
                    'label'    => 'What is your College CGPA or Percentage?',
                    'required' => true,
                    'options'  => [
                        $opt('Above 90% or 9 CGPA', '🌟'),
                        $opt('Above 80% or 8 CGPA', '🏅'),
                        $opt('Above 70% or 7 CGPA', '🎖️'),
                        $opt('Below 70% or 7 CGPA', '📜'),
                    ],
                ],
                [
                    'type'     => 'radio',
                    'key'      => 'q_uni_rank',
                    'label'    => 'What would you say was the rank of your Under Grad university in your country?',
                    'required' => true,
                    'options'  => [
                        $opt('Top 10', '🏆'),
                        $opt('Between 10-25', '🥇'),
                        $opt('Between 25-50', '🛡️'),
                        $opt('Below 50', '🥉'),
                    ],
                ],
                [
                    'type'    => 'chips',
                    'key'     => 'q_achievements',
                    'label'   => 'Do you have any other notable achievements?',
                    'help'    => 'Select all that apply.',
                    'options' => [
                        $opt('I have received an award / scholarship', '🏆'),
                        $opt('I have engaged in Academic Projects', '📊'),
                        $opt('Research Papers', '📄'),
                        $opt('Top 10 rank holder', '🥇'),
                        $opt('Unconventional background (medicine, law etc)', '🧬'),
                    ],
                ],
            ],
        ],

        /* ── 2 · EXTRA CURRICULAR ──────────────────────────────────── */
        [
            'key'      => 'extracurricular',
            'eyebrow'  => 'Extra Curricular',
            'icon'     => '🏃',
            'title'    => 'Your extracurriculars',
            'subtitle' => 'What you have been part of outside the classroom.',
            'fields'   => [
                [
                    'type'    => 'chips',
                    'key'     => 'q_ec_engaged',
                    'label'   => 'What all of the following have you been engaged in? (last 2 years only, before that has a limited effect)',
                    'help'    => 'Select all that apply.',
                    'options' => [
                        $opt('NGO / Social Volunteering', '🤝'),
                        $opt('Associations / Clubs', '👥'),
                        $opt('Competitive Sports', '⚽'),
                        $opt('Performance Arts', '🎭'),
                        $opt('Competitions', '🏅'),
                        $opt('Just Hobbies', '🎨'),
                    ],
                ],
                [
                    'type'    => 'radio',
                    'key'     => 'q_ec_level',
                    'label'   => 'What has been your highest level of participation?',
                    'options' => [
                        $opt('Held Leadership positions', '👑'),
                        $opt('Organized Events with major Success', '🎉'),
                        $opt('Just Participated', '🙋'),
                        $opt('None of the same', '🚫'),
                    ],
                ],
                [
                    'type'    => 'radio',
                    'key'     => 'q_ec_current',
                    'label'   => 'Are you still involved in the same?',
                    'options' => [
                        $opt('Yes, Heavily', '🔥'),
                        $opt('Somewhat', '👍'),
                        $opt('No, was earlier involved', '⏳'),
                    ],
                ],
            ],
        ],

        /* ── 3 · DIFFERENTIATORS ───────────────────────────────────── */
        [
            'key'      => 'differentiators',
            'eyebrow'  => 'Differentiators',
            'icon'     => '✨',
            'title'    => 'Your differentiators',
            'subtitle' => 'The things that make your profile stand apart from the rest.',
            'fields'   => [
                [
                    'type'    => 'chips',
                    'key'     => 'q_differentiators',
                    'label'   => 'Choose all extracurriculars you have been engaged in',
                    'help'    => 'Select all that apply.',
                    'options' => [
                        $opt('Significant International Experience', '🌍'),
                        $opt('Family business / Start Ups Exposure', '🏢'),
                        $opt('Successful Entrepreneurial Venture', '🚀'),
                        $opt('Multiple Industry Certifications (CFA, Six Sigma)', '📜'),
                        $opt('Publications, Awards, Patents to name', '🏅'),
                        $opt('Major Projects Undertaken - 7 Figure impact, double-digit improvements', '📈'),
                    ],
                ],
            ],
        ],

        /* ── 4 · WORK EXPERIENCE ───────────────────────────────────── */
        [
            'key'      => 'work',
            'eyebrow'  => 'Work Experience',
            'icon'     => '💼',
            'title'    => 'Your work experience',
            'subtitle' => 'Your professional background and the impact you have made.',
            'fields'   => [
                [
                    'type'     => 'radio',
                    'key'      => 'q_work_years',
                    'label'    => 'How much work experience do you have?',
                    'required' => true,
                    'options'  => [
                        $opt('Just Internships', '🎓'),
                        $opt('0-2 years', '🕐'),
                        $opt('2-4 years', '📈'),
                        $opt('4+ years', '💼'),
                    ],
                ],
                [
                    'type'    => 'radio',
                    'key'     => 'q_company_size',
                    'label'   => 'What is the size of the company you have worked at?',
                    'options' => [
                        $opt('International MNC', '🌐'),
                        $opt('National MNC', '🏢'),
                        $opt('Local Company', '🏬'),
                        $opt('Startup', '🚀'),
                    ],
                ],
                [
                    'type'    => 'chips',
                    'key'     => 'q_work_achievements',
                    'label'   => 'Highlight workplace achievements.',
                    'help'    => 'Select all that apply.',
                    'options' => [
                        $opt('Fortune 100 Brand Exposure', '🏆'),
                        $opt('Tangible Achievement via Projects', '✅'),
                        $opt('Led teams of 10+', '👥'),
                        $opt('Created new Processes and Systems', '⚙️'),
                        $opt('Multiple Promotions / Awards', '📈'),
                        $opt('Unique Work profile', '⭐'),
                    ],
                ],
            ],
        ],

        /* ── 5 · TEST SCORES ───────────────────────────────────────── */
        [
            'key'      => 'tests',
            'eyebrow'  => 'Test Scores',
            'icon'     => '📝',
            'title'    => 'Your test scores',
            'subtitle' => 'Standardised admission tests — add whatever applies to you.',
            'fields'   => [
                [
                    'type'     => 'radio',
                    'key'      => 'q_test_type',
                    'label'    => 'Which admission test have you taken or are planning to take?',
                    'required' => true,
                    'options'  => [
                        $opt('GMAT', '📝'),
                        $opt('GMAT Focus', '🎯'),
                        $opt('GRE', '📘'),
                        $opt('Executive Assessment (EA)', '💼'),
                        $opt('Not planning to take', '🚫'),
                    ],
                ],
                [
                    'type'        => 'text',
                    'key'         => 'q_test_score',
                    'label'       => 'What score have you achieved or are hoping to achieve?',
                    'placeholder' => 'e.g. 720 / 320 / N/A',
                ],
            ],
        ],

        /* ── 6 · DEGREE OF INTEREST ────────────────────────────────── */
        [
            'key'      => 'degree',
            'eyebrow'  => 'Degree of Interest',
            'icon'     => '🎓',
            'title'    => 'Your degree of interest',
            'subtitle' => 'Where you would like to take your profile next.',
            'fields'   => [
                [
                    'type'     => 'chips',
                    'key'      => 'q_target_degree',
                    'label'    => 'Which degree are you planning to apply to?',
                    'help'     => 'Select all that apply.',
                    'required' => true,
                    'options'  => [
                        $opt('Masters in Management', '📊'),
                        $opt('MBA', '💼'),
                        $opt('Masters in Finance', '💰'),
                        $opt('Masters in Business Analytics', '📈'),
                        $opt('Masters in Engineering Management', '⚙️'),
                        $opt('Deferred MBA', '⏳'),
                    ],
                ],
                [
                    'type'    => 'chips',
                    'key'     => 'q_target_countries',
                    'label'   => 'Do you have any target Countries in mind?',
                    'help'    => 'Select all that apply.',
                    'options' => [
                        $opt('USA', '🗽'),
                        $opt('Ireland + UK', '🍀'),
                        $opt('Canada', '🍁'),
                        $opt('Europe', '🏛️'),
                        $opt('Germany', '🏰'),
                        $opt('France', '🗼'),
                        $opt('Australia', '🦘'),
                        $opt('SE Asia', '🌴'),
                    ],
                ],
            ],
        ],
    ],
];
