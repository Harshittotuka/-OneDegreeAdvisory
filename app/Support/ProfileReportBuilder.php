<?php

namespace App\Support;

/**
 * Turns a completed Student Profiler submission (the human-readable snapshot
 * produced by ProfileSubmissionStore::snapshot()) into a single $data array
 * that both the team-notification and applicant thank-you emails render.
 *
 * The Student Profiler is intentionally NOT scored — so the "analysis" here is
 * honest and rule-based: it surfaces the key facts the student gave us
 * (destinations, intake, budget, tests, experience) and turns gaps into plain
 * next-step guidance. No score, band, or shortlist is invented.
 */
class ProfileReportBuilder
{
    /**
     * @param  string       $source       "profiler" | "evaluator"
     * @param  string       $sourceLabel  Friendly source name (e.g. "Student Profiler")
     * @param  string|null  $degreeLabel  Target degree label for the profiler (null for the evaluator)
     * @param  array        $sections     Snapshot from ProfileSubmissionStore::snapshot()
     * @param  array        $meta         { name, email, phone }
     * @return array<string,mixed>
     */
    public static function build(string $source, string $sourceLabel, ?string $degreeLabel, array $sections, array $meta): array
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

        // Destination keywords match the profiler's "Which country do you prefer
        // to study in?" and the evaluator's "Do you have any target Countries in
        // mind?" — WITHOUT matching the evaluator's university-rank question
        // ("…rank of your Under Grad university in your country?").
        $destKeys = ['prefer to study', 'target countr', 'countries in mind', 'which country', 'destination', 'study in'];

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
            if (str_contains(mb_strtolower($backlog['value']), 'no ')) {
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
            'closing'       => $closing,
        ];
    }
}
