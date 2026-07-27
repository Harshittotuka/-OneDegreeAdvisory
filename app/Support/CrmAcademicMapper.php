<?php

namespace App\Support;

/**
 * Maps the free-form website questionnaire answers onto the CRM lead's structured
 * academic-background columns.
 *
 * The profiler asks the same thing in several wordings depending on the degree
 * (high school / UG / PG), so matching is done on lowercased label fragments
 * rather than exact strings. Whatever the website never asks — the 10th passing
 * year and any test date — stays blank for a counsellor to fill in.
 */
class CrmAcademicMapper
{
    /** The repeatable ['test','name','score','date'] columns. */
    public const TEST_FIELDS = ['english_tests', 'aptitude_tests'];

    /** Every column this mapper can populate, in card order. */
    public const FIELDS = [
        'tenth_score', 'tenth_passing_year', 'twelfth_score', 'twelfth_passing_year',
        'graduation_score', 'graduation_passing_year', 'backlogs',
        ...self::TEST_FIELDS,
    ];

    /** Aptitude tests the questionnaire asks about, keyed by the label fragment to look for. */
    private const APTITUDE_LABELS = ['sat' => 'sat', 'act' => 'act', 'gre' => 'gre', 'gmat' => 'gmat'];

    /**
     * Merge the academic answers of several submissions, newest first — the first
     * submission that answers a field wins.
     *
     * @param  iterable<\App\Models\CrmWebsiteSubmission|array>  $submissions
     * @return array<string, string|int|array>
     */
    public static function fromSubmissions(iterable $submissions): array
    {
        $mapped = [];
        foreach ($submissions as $submission) {
            $sections = is_array($submission) ? $submission : ($submission->sections ?: []);
            foreach (self::fromSections((array) $sections) as $field => $value) {
                $mapped[$field] ??= $value;
            }
        }

        return $mapped;
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<string, string|int|array>
     */
    public static function fromSections(array $sections): array
    {
        $mapped = [];
        $english = ['test' => null, 'score' => null];
        $aptitude = [];

        foreach ($sections as $section) {
            foreach ((array) ($section['answers'] ?? []) as $answer) {
                $label = mb_strtolower(trim((string) ($answer['label'] ?? '')));
                $values = array_values(array_filter(
                    array_map(fn ($value): string => trim((string) $value), (array) ($answer['value'] ?? [])),
                    fn (string $value): bool => $value !== '',
                ));
                if ($label === '' || $values === []) {
                    continue;
                }

                self::readAnswer($mapped, $english, $aptitude, $label, $values, implode(', ', $values));
            }
        }

        if ($english['test'] !== null || $english['score'] !== null) {
            $mapped['english_tests'] = [self::row($english['test'] ?? 'other', $english['score'])];
        }
        if ($aptitude !== []) {
            $mapped['aptitude_tests'] = array_values(array_map(
                fn (string $score, string $test): array => self::row($test, $score),
                $aptitude,
                array_keys($aptitude),
            ));
        }

        return array_filter($mapped, fn ($value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /** @return array{test: string, name: null, score: string, date: null} */
    private static function row(string $test, ?string $score): array
    {
        return ['test' => $test, 'name' => null, 'score' => self::short((string) $score), 'date' => null];
    }

    /**
     * @param  array<string, string|int|null>  $mapped
     * @param  array{test: ?string, score: ?string}  $english
     * @param  array<string, string>  $aptitude
     * @param  array<int, string>  $values
     */
    private static function readAnswer(array &$mapped, array &$english, array &$aptitude, string $label, array $values, string $text): void
    {
        // The English widget stores ["IELTS", "Overall: 7.5", "Listening: 7", …].
        if (str_contains($label, 'individual score')) {
            foreach ($values as $value) {
                if (preg_match('/^(ielts|toefl|pte|duolingo)$/i', $value)) {
                    $english['test'] ??= mb_strtolower($value);
                }
                if (preg_match('/^overall\s*[:\-]\s*(.+)$/i', $value, $matches)) {
                    $english['score'] ??= trim($matches[1]);
                }
            }

            return;
        }

        // Standalone "Overall IELTS Score" / "IELTS / ToEFL / PTE Score" bands.
        if (str_contains($label, 'score') && preg_match('/\b(ielts|toefl|pte)\b/', $label)) {
            $english['score'] ??= $text;
            if (str_contains($label, 'ielts') && ! str_contains($label, 'toefl') && ! str_contains($label, 'pte')) {
                $english['test'] ??= 'ielts';
            }

            return;
        }

        // "SAT score", "GMAT Score", "GRE Score" — one aptitude row each.
        if (str_contains($label, 'score')) {
            foreach (self::APTITUDE_LABELS as $test => $fragment) {
                if (preg_match('/\b'.$fragment.'\b/', $label)) {
                    $aptitude[$test] ??= $text;

                    return;
                }
            }
        }

        match (true) {
            str_contains($label, 'year of passing 12th') => $mapped['twelfth_passing_year'] ??= self::year($text),
            str_contains($label, 'year of passing graduation') => $mapped['graduation_passing_year'] ??= self::year($text),
            str_contains($label, 'backlog') => $mapped['backlogs'] ??= self::short($text),
            str_contains($label, 'cgpa') && str_contains($label, 'bachelor') => $mapped['graduation_score'] ??= self::short($text),
            str_contains($label, 'class 10 result') => $mapped['tenth_score'] ??= self::short($text),
            str_contains($label, 'class 12 result')
                || (str_contains($label, '12th class') && str_contains($label, 'percentage')) => $mapped['twelfth_score'] ??= self::short($text),
            default => null,
        };
    }

    private static function short(string $value): string
    {
        return mb_substr($value, 0, 40);
    }

    private static function year(string $value): ?int
    {
        return preg_match('/(?:19|20)\d{2}/', $value, $matches) ? (int) $matches[0] : null;
    }
}
