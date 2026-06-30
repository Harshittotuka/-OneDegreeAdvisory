<?php

namespace App\Support;

/**
 * Turns a completed profiler/evaluator submission (the human-readable snapshot
 * produced by ProfileSubmissionStore::snapshot()) into a single $data array
 * that both the team-notification and applicant thank-you emails render.
 *
 * The Student Profiler and Profile Evaluator are intentionally NOT scored — so
 * the "analysis" here is honest and rule-based: it surfaces the key facts the
 * student gave us (destinations, intake, budget, tests, experience) and turns
 * gaps into plain next-step guidance. No score, band, or shortlist is invented.
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

        $has = fn (?array $a): bool => $a !== null
            && $a['value'] !== ''
            && ! str_contains(mb_strtolower($a['value']), 'not appeared')
            && ! str_starts_with(mb_strtolower($a['value']), 'no ');

        // ── Key facts: pulled out for an at-a-glance summary block ──────────
        $highlights = [];
        $add = function (string $label, ?array $a) use (&$highlights) {
            if ($a !== null && $a['value'] !== '') {
                $highlights[$label] = $a['value'];
            }
        };
        $add('Preferred destination', $find(['country', 'destination', 'where']));
        $add('Target intake', $find(['intake']));
        $add('Course / programme', $find(['course', 'programme', 'program', 'specialis', 'field']));
        $add('Academic level', $find(['highest qualification', 'current qualification', 'academic', 'qualification']));
        $add('Budget', $find(['budget']));
        $add('Work experience', $find(['work experience']));

        // ── About your profile: a basic, honest read of strengths and the
        //    areas to work on, derived purely from the answers (never a score).
        //    The closing line promises a detailed analysis from the team.
        $strengths    = [];
        $improvements = [];

        $english = $find(['ielts', 'toefl', 'pte', 'english', 'individual score']);
        if ($has($english)) {
            $strengths[] = 'English proficiency already demonstrated through a test score (IELTS / TOEFL / PTE).';
        } else {
            $improvements[] = 'An English proficiency test (IELTS, TOEFL or PTE) is still pending — most universities and student visas require one.';
        }

        $standardised = $find([' gre', 'gre ', 'gmat', ' sat', 'sat ']);
        if ($has($standardised)) {
            $strengths[] = 'A standardised test score (GRE / GMAT / SAT) is already in hand, which competitive programmes look for.';
        } elseif ($degreeLabel !== null && $degreeLabel !== '') {
            $improvements[] = 'A GRE / GMAT / SAT score is not on your profile yet; depending on your target programmes it can make applications more competitive.';
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

        $work = $find(['work experience']);
        if ($work) {
            if (str_starts_with(mb_strtolower($work['value']), 'no')) {
                $improvements[] = 'Limited work experience — relevant internships or projects can add weight to your profile.';
            } else {
                $strengths[] = 'Professional work experience ('.$work['value'].') adds strength to your application.';
            }
        }

        $diff = $find(['differentiat', 'set you apart', 'achievement', 'publication', 'award']);
        if ($has($diff)) {
            $strengths[] = 'Notable differentiators on your profile: '.$diff['value'].'.';
        }

        $visa = $find(['visa']);
        if ($visa && str_contains(mb_strtolower($visa['value']), 'yes')) {
            $improvements[] = 'A previous visa refusal needs careful handling in your next application — our team will guide you on this.';
        }

        $destination = $find(['country', 'destination', 'where']);
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
