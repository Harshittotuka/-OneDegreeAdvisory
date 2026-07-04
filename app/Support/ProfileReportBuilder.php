<?php

namespace App\Support;

/**
 * Turns a completed Student Profiler submission (the human-readable snapshot
 * produced by ProfileSubmissionStore::snapshot()) into a single $data array
 * that the team-notification / applicant thank-you emails and the attached
 * PDF report render.
 *
 * Besides the raw facts (highlights, strengths, areas to work on) this also
 * prepares everything the branded report design needs:
 *   • pages      — the responses regrouped into the report's two Q&A pages
 *                  ("My Profile" = academics, "My Profiling" = tests & prefs);
 *                  the academics page carries a "Quick Snapshot" callout
 *                  composed from the answers.
 *
 * The report is deliberately unscored — there is no "Profile Weightage" figure;
 * the honest strengths / areas-to-work-on read plus the closing note (a personal
 * review by the team) is the whole of the assessment.
 */
class ProfileReportBuilder
{
    /**
     * Short display labels for the report's question cards, keyed by the
     * normalised (lowercase, single-spaced) questionnaire label. Presentation
     * only — submissions/emails keep the original wording. Unknown labels fall
     * back to a generic cleanup (the "(* Mandatory)" marker is stripped).
     */
    private const DISPLAY_LABELS = [
        'preferred intake' => 'Preferred Intake',
        'your overall percentage in 12th class. if pursuing then expected percentage(* mandatory)' => '12th Class Percentage (Overall / Expected)',
        'subject combinations in 12th class or ibdp(* mandatory)' => 'Subject Combination (12th / IBDP)',
        'your board in 12th class' => 'Board in 12th Class',
        'your ibdp point score ( if from ib board or else keep it blank)' => 'IBDP Point Score (24–45)',
        'year of passing 12th class(* mandatory)' => 'Year of Passing 12th Class',
        'no. of backlogs/repeats (if any) please mention the exact no of repeats.(* mandatory)' => 'No. of Backlogs / Repeats',
        'no. of backlogs/repeats (if any) - please specify the exact no of repeats. (* mandatory)' => 'No. of Backlogs / Repeats',
        'no. of backlogs/repeats (if any)' => 'No. of Backlogs / Repeats',
        'education gap (in between studies or after studies)' => 'Education Gap',
        'work experience or internship project' => 'Work Experience / Internship',
        'overall ielts score' => 'Overall IELTS Score',
        'overall ielts / toefl / pte score or expected(* mandatory)' => 'Overall IELTS / TOEFL / PTE Score',
        'individual score in ielts / toefl / pte score (listening, reading, writing, speaking)' => 'English Band Scores (L / R / W / S)',
        'ielts / toefl / pte score' => 'IELTS / TOEFL / PTE Score',
        'sat score' => 'SAT Score',
        'gmat score' => 'GMAT Score',
        'gre score' => 'GRE Score',
        'any budget constraint (Total Cost per Annum)' => 'Budget Constraint (Total Cost per Annum)',
        'country preference (maximum 2 country)' => 'Country Preference (Max. 2)',
        'country preference' => 'Country Preference',
        'previous visa rejections (if any)' => 'Previous Visa Rejections',
        'travel history (countries visited/travelled previously)' => 'Travel History',
        'course preference(* mandatory)' => 'Course Preference',
        'course preference for post graduation (you would like to study)(* mandatory)' => 'Course Preference (Postgraduate)',
        'course preference you want to pursue(* mandatory)' => 'Course Preference',
        'any specilaization preference' => 'Specialisation Preference',
        'do you have any university/college preference?' => 'University / College Preference',
        'are you looking for undergraduate degree/ diploma or certificate program' => 'Program Type Sought',
        'are you looking for postgraduate degree/ diploma or certificate program' => 'Program Type Sought',
        'your expectations' => 'Your Expectations',
        'what all of the following have you been engaged in? (last 2 years only, before that has a limited effect)' => 'Extracurricular Engagement (Last 2 Years)',
        'what has been your highest level of participation?' => 'Highest Level of Participation',
        'are you still involved in the same?' => 'Still Involved in the Same?',
        'which of these differentiators apply to you?' => 'Profile Differentiators',
        'what would you say was the rank of your under grad university in your country?' => 'UG University Rank (In Country)',
        'do you have any other notable achievements? (you can choose multiple)' => 'Other Notable Achievements',
        'graduation (ug) course you completed/ pursuing (full course name)' => 'Graduation (UG) Course',
        'graduation (ug) course type you pursued' => 'Graduation (UG) Course Type',
        'graduation (ug) duration you pursued' => 'Graduation (UG) Duration',
        'your overall % or cgpa in bachelors. if pursuing then expected % or cgpa' => 'Bachelors % / CGPA (Overall / Expected)',
        'your score in bachelors (% or cgpa). if pursuing then expected score' => 'Bachelors Score (% / CGPA)',
        'year of passing graduation(* mandatory)' => 'Year of Passing Graduation',
        'your overall percentage in 10th class(* mandatory)' => '10th Class Percentage',
        'your current status of 12th class' => '12th Class Status',
        'your current status of graduation' => 'Graduation Status',
        'post graduation (pg) course type you pursued' => 'Post Graduation (PG) Course Type',
        'post graduation (pg) duration you pursued' => 'Post Graduation (PG) Duration',
        'your current status of post graduation' => 'Post Graduation Status',
        'your score in post graduation (% or cgpa). if pursuing then expected score' => 'Post Graduation Score (% / CGPA)',
        'project details of masters' => 'Masters Project Details',
        'year of passed 10th class(* mandatory)' => 'Year of Passing 10th Class',
        'year of pass 12th class(* mandatory)' => 'Year of Passing 12th Class',
        'year of pass graduation(* mandatory)' => 'Year of Passing Graduation',
        'year of pass post graduation(* mandatory)' => 'Year of Passing Post Graduation',
    ];

    /**
     * @param  string       $source         "profiler" | "evaluator"
     * @param  string       $sourceLabel    Friendly source name (e.g. "Student Profiler")
     * @param  string|null  $degreeLabel    Target degree label for the profiler (null for the evaluator)
     * @param  array        $sections       Snapshot from ProfileSubmissionStore::snapshot()
     * @param  array        $meta           { name, email, phone }
     * @param  int|null     $questionTotal  Reserved (formerly fed the weightage heuristic,
     *                                      which the report no longer shows); kept so existing
     *                                      callers need no change.
     * @return array<string,mixed>
     */
    public static function build(string $source, string $sourceLabel, ?string $degreeLabel, array $sections, array $meta, ?int $questionTotal = null): array
    {
        // Flatten every answered question to {label, value, section} so the
        // analysis can scan labels by keyword without caring about structure.
        $flat = [];
        foreach ($sections as $sec) {
            foreach (($sec['answers'] ?? []) as $a) {
                $flat[] = [
                    'label'   => (string) ($a['label'] ?? ''),
                    'value'   => implode(', ', array_map('strval', (array) ($a['value'] ?? []))),
                    'section' => (string) ($sec['title'] ?? ''),
                ];
            }
        }

        // First answered question whose label contains ANY of the needles.
        $find = function (array $needles) use ($flat): ?array {
            foreach ($flat as $a) {
                $l = mb_strtolower($a['label']);
                foreach ($needles as $n) {
                    if (str_contains($l, $n)) {
                        return $a;
                    }
                }
            }

            return null;
        };

        // A value counts as "missing / negative" when blank or an explicit
        // opt-out — covers both forms (no…, none, not appeared, not planning, n/a).
        $isNegative = function (string $v): bool {
            $v = mb_strtolower(trim($v));

            return $v === ''
                || $v === 'no' || str_starts_with($v, 'no ')
                || str_contains($v, 'not appeared')
                || str_contains($v, 'not planning')
                || str_contains($v, 'none')
                || $v === 'n/a' || $v === 'na';
        };
        $has = fn (?array $a): bool => $a !== null && ! $isNegative($a['value']);

        // "Clean" no-issues answers: 0 / none / nil / no… — used for backlogs.
        $isClean = function (string $v): bool {
            $v = mb_strtolower(trim($v));

            return $v === '' || $v === '0' || $v === 'none' || $v === 'nil'
                || $v === 'no' || str_starts_with($v, 'no ')
                || $v === 'n/a' || $v === 'na';
        };

        // Destination keywords match the profiler's "Which country do you prefer
        // to study in?" and the evaluator's "Do you have any target Countries in
        // mind?" — WITHOUT matching the university-rank question
        // ("…rank of your Under Grad university in your country?").
        $destKeys = ['prefer to study', 'target countr', 'countries in mind', 'which country', 'destination', 'country preference'];

        // Standardised admission test. ' gre' is space-prefixed so it does not
        // match "deGREe"; the evaluator stores the test in the value, so the
        // "admission test" label is matched too.
        $testKeys = ['gmat', ' gre', 'gre score', 'sat score', ' sat ', 'admission test', 'what score'];

        // ── Key facts: pulled out for an at-a-glance summary block ──────────
        $highlights = [];
        $add = function (string $label, ?array $a) use (&$highlights) {
            if ($a !== null && $a['value'] !== '') {
                $highlights[$label] = $a['value'];
            }
        };
        $add('Preferred destination', $find($destKeys));
        $add('Target degree', $find(['which degree', 'degree are you', 'planning to apply']));
        $add('Academic record', $find(['cgpa', 'percentage', 'gpa']));
        $add('Course / specialisation', $find(['course', 'programme', 'program', 'specialis', 'field of study']));
        $add('Target intake', $find(['intake']));
        $add('Budget', $find(['budget']));
        $add('Work experience', $find(['work experience']));
        $add('Admission test', $find(['admission test']));

        // ── About your profile: a basic, honest read of strengths and the
        //    areas to work on, derived purely from the answers (never a score).
        //    The closing line promises a detailed analysis from the team.
        $strengths    = [];
        $improvements = [];

        // English proficiency — only relevant to the study-abroad profiler. The
        // evaluator (MBA/GMAT-style) never asks for it, so it is not raised there.
        $english = $find(['ielts', 'toefl', 'pte', 'english proficiency', 'individual score']);
        if ($english !== null) {
            if ($has($english)) {
                $strengths[] = 'English proficiency already demonstrated through a test score (IELTS / TOEFL / PTE).';
            } else {
                $improvements[] = 'An English proficiency test (IELTS, TOEFL or PTE) is still pending — most universities and student visas require one.';
            }
        } elseif ($source === 'profiler') {
            $improvements[] = 'An English proficiency test (IELTS, TOEFL or PTE) is still pending — most universities and student visas require one.';
        }

        // Standardised admission test (GMAT / GRE / SAT). Detected by label on
        // the profiler ("Your GMAT score") and by the test-type question on the
        // evaluator ("Which admission test…", value "GMAT" / "Not planning…").
        $test = $find($testKeys);
        if ($test !== null) {
            if ($has($test)) {
                $strengths[] = 'A standardised admission test (GMAT / GRE / SAT) is part of your profile, which competitive programmes look for.';
            } else {
                $improvements[] = 'A standardised admission test score (GMAT / GRE / SAT) will strengthen competitive applications — we can advise on which to take.';
            }
        }

        $backlog = $find(['backlog']);
        if ($backlog) {
            if ($isClean($backlog['value'])) {
                $strengths[] = 'A clean academic record with no backlogs.';
            } else {
                $improvements[] = 'Academic backlogs ('.$backlog['value'].') are best addressed up front in your applications.';
            }
        }

        $gap = $find(['gap']);
        if ($gap) {
            if (str_contains(mb_strtolower($gap['value']), 'no gap')) {
                $strengths[] = 'No gaps in your education timeline.';
            } else {
                $improvements[] = 'An education gap ('.$gap['value'].') is best supported with a clear, positive explanation.';
            }
        }

        // Work experience — "0-…", "Just Internships" and opt-outs read as early
        // career; everything else (e.g. "2-4 years", "4+ years") as a strength.
        $work = $find(['work experience']);
        if ($work) {
            $wl = mb_strtolower($work['value']);
            if ($isNegative($wl) || str_contains($wl, 'internship') || str_starts_with($wl, '0')) {
                $improvements[] = 'Limited work experience — relevant internships or projects can add weight to your profile.';
            } else {
                $strengths[] = 'Professional work experience ('.$work['value'].') adds strength to your application.';
            }
        }

        $diff = $find(['differentiat', 'set you apart', 'stand apart', 'notable achievement', 'achievement', 'publication', 'award']);
        if ($has($diff)) {
            $strengths[] = 'Strong differentiators / achievements noted: '.$diff['value'].'.';
        }

        $visa = $find(['visa']);
        if ($visa && str_contains(mb_strtolower($visa['value']), 'yes')) {
            $improvements[] = 'A previous visa refusal needs careful handling in your next application — our team will guide you on this.';
        }

        $destination = $find($destKeys);
        if ($has($destination)) {
            $strengths[] = 'You have a clear study destination in mind ('.$destination['value'].'), which lets us advise you precisely.';
        }

        // Always leave the student with at least one positive note.
        if (! $strengths) {
            $strengths[] = 'You have shared a clear picture of your goals — a strong starting point for personalised guidance.';
        }

        $strengths    = array_slice($strengths, 0, 5);
        $improvements = array_slice($improvements, 0, 5);

        // ── Report pages: the design's two Q&A pages + their callouts ───────
        $pages = self::pages($sections, $find, $has, $isClean, $destKeys);

        $closing = 'This is a quick, automated read of your profile. Our team will personally review everything you have shared and reach out to you with a detailed analysis and the right next steps.';

        return [
            'source'        => $source,
            'sourceLabel'   => $sourceLabel,
            'degreeLabel'   => $degreeLabel,
            'name'          => trim((string) ($meta['name'] ?? '')),
            'email'         => trim((string) ($meta['email'] ?? '')),
            'phone'         => trim((string) ($meta['phone'] ?? '')),
            'sections'      => $sections,
            'answeredCount' => count($flat),
            'sectionCount'  => count($sections),
            'highlights'    => $highlights,
            'strengths'     => $strengths,
            'improvements'  => $improvements,
            'pages'         => $pages,
            'closing'       => $closing,
        ];
    }

    /**
     * Regroup the snapshot into the report design's two Q&A pages and compose
     * the academics page's "Quick Snapshot" callout from the answers.
     *
     * @return array<int, array<string,mixed>>
     */
    private static function pages(array $sections, \Closure $find, \Closure $has, \Closure $isClean, array $destKeys): array
    {
        // Page 1 ("My Profile") = schooling/academics; page 2 ("My Profiling")
        // = everything else (extracurriculars, tests, preferences, details).
        $isProfile = function (string $title): bool {
            $t = mb_strtolower($title);
            foreach (['goal', 'academic', 'schooling', 'graduation & post', 'experience & gaps'] as $n) {
                if (str_contains($t, $n)) {
                    return true;
                }
            }

            return false;
        };

        $profile = [];
        $profiling = [];
        foreach ($sections as $sec) {
            $bucket = $isProfile((string) ($sec['title'] ?? '')) ? 'profile' : 'profiling';
            foreach (($sec['answers'] ?? []) as $a) {
                $label = (string) ($a['label'] ?? '');
                $value = implode(', ', array_map('strval', (array) ($a['value'] ?? [])));
                // The English-widget answer stores a raw token list — compact
                // it to the design's tidy band-score shape for display.
                if (str_contains(mb_strtolower($label), 'individual score')) {
                    $value = self::compactEnglish($value);
                }
                $row = ['label' => self::displayLabel($label), 'value' => $value];
                if ($bucket === 'profile') {
                    $profile[] = $row;
                } else {
                    $profiling[] = $row;
                }
            }
        }

        // ── "Quick Snapshot" items (academics page) ──────────────────────
        $snap = [];

        $bach = $find(['% or cgpa in bachelors', 'score in bachelors']);
        $twelfth = $find(['percentage in 12th']);
        // High-school card now stores per-class results (e.g. "Class 12 result
        // (%) — expected"). Use the HIGHEST class present as the headline
        // academic figure when there's no 12th-percentage answer. $find returns
        // by form order, so probe each class label most-senior-first instead.
        $classResult = null;
        foreach (['class 12 result', 'class 11 result', 'class 10 result', 'class 9 result'] as $needle) {
            $hit = $find([$needle]);
            if ($has($hit)) { $classResult = $hit; break; }
        }
        $acad = $has($bach) ? $bach : ($has($twelfth) ? $twelfth : ($has($classResult) ? $classResult : null));
        if ($acad !== null) {
            $v = trim($acad['value']);
            $inBachelors = str_contains(mb_strtolower($acad['label']), 'bachelor');
            // "85" reads better as "85%" — but leave CGPA-scale numbers alone.
            if (is_numeric($v) && (float) $v >= 35 && ! str_contains($v, '%')) {
                $v .= '%';
            }
            // Name the class the figure belongs to: Bachelors, a specific
            // "Class N" (high-school per-class result), or 12th by default.
            $where = '12th';
            if ($inBachelors) {
                $where = 'Bachelors';
            } elseif (preg_match('/class\s+(\d+)/i', $acad['label'], $m)) {
                $where = 'Class '.$m[1]
                    .(str_contains(mb_strtolower($acad['label']), 'expected') ? ' (expected)' : '');
            }
            $line = $v.' in '.$where;
            if (! $inBachelors) {
                $extras = array_filter([
                    $find(['subject combination'])['value'] ?? '',
                    $find(['board in 12th'])['value'] ?? '',
                ], fn ($x) => trim((string) $x) !== '');
                if ($extras) {
                    $line .= ' ('.implode(', ', $extras).')';
                }
            }
            $snap[] = $line;
        }

        $backlog = $find(['backlog']);
        $gap     = $find(['gap']);
        if ($backlog !== null || $gap !== null) {
            $backlogOk = $backlog === null || $isClean($backlog['value']);
            $gapOk     = $gap === null || str_contains(mb_strtolower($gap['value']), 'no gap');
            if ($backlogOk && $gapOk) {
                $snap[] = 'No backlogs, repeats or education gaps';
            } else {
                $parts = [];
                if (! $backlogOk) {
                    $parts[] = 'Backlogs: '.$backlog['value'];
                }
                if (! $gapOk) {
                    $parts[] = 'Education gap: '.$gap['value'];
                }
                $snap[] = implode(' · ', $parts);
            }
        }

        $eng = $find(['overall ielts', 'individual score', 'ielts / toefl']);
        if ($has($eng)) {
            $v = $eng['value'];
            if (preg_match('/overall:\s*([0-9.]+)/i', $v, $m)) {
                $code = preg_match('/\b(ielts|toefl|pte)\b/i', $v, $t) ? strtoupper($t[1]) : 'English';
                $snap[] = 'Overall '.$code.' Score: '.$m[1];
            } elseif (mb_strlen($v) <= 34) {
                $snap[] = (str_contains(mb_strtolower($eng['label']), 'overall') ? 'Overall English Score: ' : 'English test: ').$v;
            } else {
                $snap[] = 'English test score on record';
            }
        }
        $snap = array_slice($snap, 0, 4);

        // Assemble the pages. Only the academics page carries a callout (the
        // "Quick Snapshot"); the profiling page is just its Q&A cards.
        $pages = [];
        if ($profile) {
            $pages[] = [
                'key'     => 'profile',
                'title'   => 'My Profile',
                'lead'    => 'Academic background, as shared by the student',
                'answers' => $profile,
                'callout' => ['title' => 'Quick Snapshot', 'items' => $snap],
            ];
        }
        if ($profiling) {
            $pages[] = [
                'key'     => 'profiling',
                'title'   => 'My Profiling',
                'lead'    => 'Test scores, preferences & readiness for study abroad',
                'answers' => $profiling,
                'callout' => null,
            ];
        }

        return $pages;
    }

    /**
     * Compact the English-score widget's raw token list — e.g.
     * "IELTS, Overall: 7.0, Listening: 7, Reading: 7, Writing: 7, Speaking: 7"
     * → "IELTS — Overall 7.0 (L 7 · R 7 · W 7 · S 7)". Multiple tests are
     * joined with "; ". Anything unparseable is returned untouched.
     */
    public static function compactEnglish(string $value): string
    {
        $skills = ['listening' => 'L', 'reading' => 'R', 'writing' => 'W', 'speaking' => 'S'];
        $tests  = [];
        $cur    = null;

        foreach (array_map('trim', explode(',', $value)) as $tok) {
            $up = strtoupper($tok);
            if (in_array($up, ['IELTS', 'TOEFL', 'PTE'], true)) {
                $cur = $up;
                $tests[$cur] = ['overall' => null, 'skills' => []];
                continue;
            }
            if ($cur !== null && preg_match('/^(overall|listening|reading|writing|speaking)\s*:\s*(.+)$/i', $tok, $m)) {
                $k = mb_strtolower($m[1]);
                if ($k === 'overall') {
                    $tests[$cur]['overall'] = trim($m[2]);
                } else {
                    $tests[$cur]['skills'][$skills[$k]] = trim($m[2]);
                }
                continue;
            }

            return $value; // unexpected token — keep the raw answer
        }

        $out = [];
        foreach ($tests as $code => $t) {
            $part = $code;
            if ($t['overall'] !== null) {
                $part .= ' — Overall '.$t['overall'];
            }
            if ($t['skills']) {
                $bits = [];
                foreach (['L', 'R', 'W', 'S'] as $s) {
                    if (isset($t['skills'][$s])) {
                        $bits[] = $s.' '.$t['skills'][$s];
                    }
                }
                $part .= ' ('.implode(' · ', $bits).')';
            }
            $out[] = $part;
        }

        return $out ? implode('; ', $out) : $value;
    }

    /** Short, design-friendly display label for a questionnaire question. */
    public static function displayLabel(string $label): string
    {
        $key = trim((string) preg_replace('/\s+/', ' ', mb_strtolower($label)));
        if (isset(self::DISPLAY_LABELS[$key])) {
            return self::DISPLAY_LABELS[$key];
        }

        // Fallback: strip the "(* Mandatory)" marker and tidy the wording.
        $clean = (string) preg_replace('/\s*\(\*\s*mandatory\)\s*/i', ' ', $label);
        $clean = trim((string) preg_replace('/\s+/', ' ', $clean), " \t.-:");

        return $clean !== '' ? $clean : $label;
    }
}
