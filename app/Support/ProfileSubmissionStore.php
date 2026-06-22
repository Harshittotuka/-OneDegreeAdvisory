<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * File-backed store for completed questionnaire submissions from BOTH the
 * Student Profiler (/profiler) and the Profile Evaluator (/evaluate-my-profile).
 *
 * Like the rest of the CMS data, submissions live in a single editable JSON file
 * (storage/app/profile-submissions.json) so they can be collected and reviewed
 * with no database. Each record stores a human-readable snapshot (section →
 * question → answer) captured at submit time, so the admin panel can show the
 * full Q&A even if the questionnaire definition later changes.
 */
class ProfileSubmissionStore
{
    private string $path;

    public function __construct()
    {
        $this->path = storage_path('app/profile-submissions.json');
    }

    /** All submissions, newest first. */
    public function all(): array
    {
        if (! is_file($this->path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($this->path), true);

        return is_array($data) ? $data : [];
    }

    /** Only submissions from a given source ("profiler" | "evaluator"). */
    public function bySource(string $source): array
    {
        return array_values(array_filter(
            $this->all(),
            fn (array $r) => ($r['source'] ?? '') === $source
        ));
    }

    public function find(string $id): ?array
    {
        foreach ($this->all() as $row) {
            if (($row['id'] ?? '') === $id) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Record a completed submission. Returns the generated id.
     *
     * @param  string       $source      Machine source key: "profiler" | "evaluator".
     * @param  string       $sourceLabel Friendly source name for display.
     * @param  string|null  $degree      Degree key for the profiler (null for the evaluator).
     * @param  array        $sections    Human-readable snapshot from self::snapshot().
     * @param  array        $meta        Optional extra fields (e.g. contact, name).
     */
    public function add(string $source, string $sourceLabel, ?string $degree, array $sections, array $meta = []): string
    {
        $id = (string) Str::uuid();

        $rows = $this->all();
        array_unshift($rows, [
            'id'           => $id,
            'source'       => $source,
            'source_label' => $sourceLabel,
            'degree'       => $degree,
            'meta'         => $meta,
            'sections'     => $sections,
            'submitted_at' => now()->toDateTimeString(),
        ]);

        $this->writeAll($rows);

        return $id;
    }

    public function delete(string $id): void
    {
        $this->writeAll(array_values(array_filter(
            $this->all(),
            fn (array $r) => ($r['id'] ?? '') !== $id
        )));
    }

    /**
     * Build a human-readable snapshot of the answers, grouped by section.
     * Only answered fields are included; values are always normalised to a list
     * of strings (single-choice → one item, multi-select → many).
     *
     * @param  array<int, array<string, mixed>>  $sections  Questionnaire sections (each with fields[]).
     * @param  array<string, mixed>              $answers   Flat key => value map of answers.
     * @return array<int, array{eyebrow:string,title:string,answers:array<int,array{label:string,value:array<int,string>}>}>
     */
    public static function snapshot(array $sections, array $answers): array
    {
        $out = [];

        foreach ($sections as $sec) {
            $items = [];

            foreach (($sec['fields'] ?? []) as $field) {
                $key = $field['key'] ?? null;
                if ($key === null || ! array_key_exists($key, $answers)) {
                    continue;
                }

                $value = $answers[$key];
                if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
                    continue;
                }

                $items[] = [
                    'label' => (string) ($field['label'] ?? $key),
                    'value' => is_array($value)
                        ? array_values(array_map(fn ($v) => (string) $v, $value))
                        : [(string) $value],
                ];
            }

            if ($items) {
                $out[] = [
                    'eyebrow' => (string) ($sec['eyebrow'] ?? ''),
                    'title'   => (string) ($sec['title'] ?? ''),
                    'answers' => $items,
                ];
            }
        }

        return $out;
    }

    private function writeAll(array $rows): void
    {
        if (! is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0775, true);
        }

        file_put_contents(
            $this->path,
            json_encode(array_values($rows), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }
}
