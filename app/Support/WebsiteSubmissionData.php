<?php

namespace App\Support;

class WebsiteSubmissionData
{
    public static function snapshot(array $sections, array $answers): array
    {
        $out = [];
        foreach ($sections as $section) {
            $items = [];
            foreach ((array) ($section['fields'] ?? []) as $field) {
                $key = $field['key'] ?? null;
                if ($key === null || ! array_key_exists($key, $answers)) {
                    continue;
                }
                $value = $answers[$key];
                if ($value === null || $value === '' || (is_array($value) && $value === [])) {
                    continue;
                }
                $items[] = [
                    'label' => (string) ($field['label'] ?? $key),
                    'value' => is_array($value) ? array_map('strval', array_values($value)) : [(string) $value],
                ];
            }
            if ($items !== []) {
                $out[] = [
                    'eyebrow' => (string) ($section['eyebrow'] ?? ''),
                    'title' => (string) ($section['title'] ?? ''),
                    'answers' => $items,
                ];
            }
        }

        return $out;
    }

    public static function tabulate(iterable $submissions): array
    {
        $questions = [];
        $seen = [];
        $rows = [];
        foreach ($submissions as $submission) {
            $sections = is_array($submission) ? ($submission['sections'] ?? []) : ($submission->sections ?? []);
            $meta = is_array($submission) ? ($submission['meta'] ?? []) : ($submission->meta ?? []);
            $answers = [];
            foreach ((array) $sections as $section) {
                foreach ((array) ($section['answers'] ?? []) as $answer) {
                    $label = (string) ($answer['label'] ?? '');
                    if ($label === '') continue;
                    if (! isset($seen[$label])) {
                        $seen[$label] = true;
                        $questions[] = $label;
                    }
                    $answers[$label] = implode(', ', (array) ($answer['value'] ?? []));
                }
            }
            $rows[] = [
                'submitted_at' => is_array($submission) ? ($submission['submitted_at'] ?? '') : $submission->submitted_at?->toDateTimeString(),
                'source_label' => is_array($submission) ? ($submission['source_label'] ?? '') : $submission->source_label,
                'name' => (string) ($meta['name'] ?? ''),
                'email' => (string) ($meta['email'] ?? ''),
                'phone' => (string) ($meta['phone'] ?? ''),
                'degree' => (string) (is_array($submission) ? ($submission['degree'] ?? '') : ($submission->degree ?? '')),
                'answers' => $answers,
            ];
        }

        return ['questions' => $questions, 'rows' => $rows];
    }
}
