<?php

namespace App\Support;

class StudyLocationContent
{
    private const DEFAULT_PATH = 'app/leverageedu_study_locations_content.json';

    public function forSlug(string $slug): array
    {
        $sheets = $this->loadSheets();
        $page = $this->firstForSlug($sheets['Pages'] ?? [], $slug);
        $sections = $this->rowsForSlug($sheets['Sections'] ?? [], $slug);
        $cards = $this->rowsForSlug($sheets['Cards'] ?? [], $slug);
        $courses = $this->cleanCourses($this->rowsForSlug($sheets['Courses'] ?? [], $slug));
        $images = $this->validImages($this->rowsForSlug($sheets['Images'] ?? [], $slug));
        $uiText = $this->uiText($sheets['UiText'] ?? [], $slug);
        $whyCards = $this->cleanCards($this->cardsLike($cards, 'Why'));
        $costCards = $this->cleanCards($this->cardsLike($cards, 'Cost of Studying'));
        $indianStudents = $this->indianStudents($sheets['IndianStudents'] ?? [], $slug);

        return [
            'page' => $page,
            'destination' => $this->destinationFromPage($page),
            'uiText' => $uiText,
            'sections' => $sections,
            'sectionCopy' => [
                'why' => $this->sectionLike($sections, 'Why'),
                'courses' => $this->sectionLike($sections, 'Top Courses'),
                'intakes' => $this->sectionLike($sections, 'Intakes'),
                'costs' => $this->sectionLike($sections, 'Cost of Studying'),
            ],
            'whyCards' => $whyCards,
            'topCourses' => $courses,
            'costCards' => $costCards,
            'costTables' => $this->costTables($costCards),
            'intakeCards' => $this->cleanCards($this->cardsLike($cards, 'Intakes')),
            'featureImages' => $this->featureImages($images),
            'indianStudents' => $indianStudents,
            'generatedAt' => $this->loadGeneratedAt(),
        ];
    }

    public function destinations(): array
    {
        $sheets = $this->loadSheets();
        $destinations = array_values(array_filter(
            array_map(fn (array $page) => $this->destinationFromPage($page), $sheets['Pages'] ?? []),
            fn (array $destination) => ($destination['slug'] ?? '') !== '' && ($destination['name'] ?? '') !== ''
        ));

        usort($destinations, fn (array $a, array $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));

        return $destinations;
    }

    private function destinationFromPage(array $page): array
    {
        $slug = (string) ($page['page_slug'] ?? '');
        $country = (string) ($page['country'] ?? '');
        $name = (string) ($page['nav_label'] ?? $country);

        return [
            'slug' => $slug,
            'name' => $name,
            'country' => $country,
            'flag' => (string) ($page['flag_code'] ?? ''),
            'eu' => ($page['uses_eu_flag'] ?? '') === 'yes',
            'flag_alt' => (string) ($page['flag_alt'] ?? ''),
            'hero_image' => (string) ($page['hero_image'] ?? ''),
            'hero_key' => (string) ($page['hero_key'] ?? ''),
        ];
    }

    private function uiText(array $rows, string $slug): array
    {
        $text = [];

        foreach ($rows as $row) {
            if (($row['page_slug'] ?? '') !== $slug) {
                continue;
            }

            $key = (string) ($row['text_key'] ?? '');
            if ($key === '') {
                continue;
            }

            $text[$key] = (string) ($row['text_value'] ?? '');
        }

        return $text;
    }

    private function indianStudents(array $rows, string $slug): array
    {
        $filtered = array_values(array_filter(
            $rows,
            fn (array $row) => ($row['page_slug'] ?? '') === $slug
        ));

        if ($filtered === []) {
            return [];
        }

        usort($filtered, fn (array $a, array $b) => ((int) ($a['card_order'] ?? 0)) <=> ((int) ($b['card_order'] ?? 0)));

        $first = $filtered[0];
        $cards = array_map(fn (array $row) => [
            'value' => $this->plainText($row['card_value'] ?? ''),
            'description' => $this->plainText($row['card_description'] ?? ''),
            'highlighted' => ($row['card_highlighted'] ?? '') === 'yes',
        ], $filtered);

        return [
            'subtitle' => $this->plainText($first['subtitle'] ?? ''),
            'heading_before' => $this->plainText($first['heading_before'] ?? ''),
            'heading_highlight' => $this->plainText($first['heading_highlight'] ?? ''),
            'heading_after' => $this->plainText($first['heading_after'] ?? ''),
            'cta_text' => $this->plainText($first['cta_text'] ?? ''),
            'cards' => $cards,
        ];
    }

    private function plainText(mixed $value): string
    {
        $text = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/', ' ', strip_tags($text)));
        $text = (string) preg_replace('/\(\s+/', '(', $text);
        $text = (string) preg_replace('/\s+\)/', ')', $text);

        return (string) preg_replace('/\s+([?.!,;:])/', '$1', $text);
    }

    private function loadSheets(): array
    {
        $path = storage_path(self::DEFAULT_PATH);

        if (! is_file($path)) {
            return [];
        }

        $payload = json_decode((string) file_get_contents($path), true);

        return is_array($payload['sheets'] ?? null) ? $payload['sheets'] : [];
    }

    private function loadGeneratedAt(): string
    {
        $path = storage_path(self::DEFAULT_PATH);

        if (! is_file($path)) {
            return '';
        }

        $payload = json_decode((string) file_get_contents($path), true);

        return (string) ($payload['generated_at_utc'] ?? '');
    }

    private function rowsForSlug(array $rows, string $slug): array
    {
        $filtered = array_values(array_filter(
            $rows,
            fn (array $row) => ($row['page_slug'] ?? '') === $slug
        ));

        usort($filtered, fn (array $a, array $b) => $this->sortKey($a) <=> $this->sortKey($b));

        return $filtered;
    }

    private function firstForSlug(array $rows, string $slug): array
    {
        foreach ($rows as $row) {
            if (($row['page_slug'] ?? '') === $slug) {
                return $row;
            }
        }

        return [];
    }

    private function sectionLike(array $sections, string $needle): array
    {
        foreach ($sections as $section) {
            if (stripos((string) ($section['section_heading'] ?? ''), $needle) !== false) {
                return $section;
            }
        }

        return [];
    }

    private function cardsLike(array $cards, string $needle): array
    {
        return array_values(array_filter(
            $cards,
            fn (array $card) => stripos((string) ($card['section_heading'] ?? ''), $needle) !== false
        ));
    }

    private function cleanCards(array $cards): array
    {
        return array_map(function (array $card): array {
            $title = (string) ($card['card_title'] ?? '');
            $body = $this->cleanCardBody($title, (string) ($card['card_body'] ?? ''));

            return array_merge($card, [
                'card_body_clean' => $body,
            ]);
        }, $cards);
    }

    private function cleanCourses(array $courses): array
    {
        return array_values(array_filter(array_map(function (array $course): array {
            return [
                'country' => (string) ($course['country'] ?? ''),
                'page_slug' => (string) ($course['page_slug'] ?? ''),
                'section_heading' => (string) ($course['section_heading'] ?? ''),
                'course_order' => (string) ($course['course_order'] ?? ''),
                'university_name' => (string) ($course['university_name'] ?? ''),
                'country_flag' => (string) ($course['country_flag'] ?? ''),
                'course_name' => (string) ($course['course_name'] ?? ''),
                'duration' => (string) ($course['duration'] ?? ''),
                'credential' => (string) ($course['credential'] ?? ''),
                'cta_text' => (string) ($course['cta_text'] ?? ''),
                'cta_url' => (string) ($course['cta_url'] ?? ''),
            ];
        }, $courses), fn (array $course) => $course['course_name'] !== ''));
    }

    private function costTables(array $cards): array
    {
        $currency = '(?:[£$€]|(?:AUD|CAD|USD|EUR|NZD|AED|INR|GBP|JPY|CHF|SGD)\s+)';
        $amount = '[0-9][0-9,]*(?:\.[0-9]+)?';
        $rangeAmount = '(?:\s*[-–—]\s*(?:' . $currency . ')?' . $amount . ')?';
        $valueSuffix = '(?:\s*\/\s*(?:month|mo|yr|year|semester))?';
        $pattern = '/([\p{L}][\p{L}\p{N}\s()\'\/.&\-]+?)\s+(' . $currency . $amount . $rangeAmount . $valueSuffix . ')/u';

        return array_map(function (array $card) use ($pattern): array {
            $body = (string) ($card['card_body_clean'] ?? '');
            $rows = [];

            preg_match_all($pattern, $body, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $label = trim(preg_replace('/\s+/', ' ', $match[1]) ?? '');
                $value = trim(preg_replace('/\s+/', ' ', $match[2]) ?? '');

                if ($label === '' || $value === '') {
                    continue;
                }

                $rows[] = [
                    'label' => $label,
                    'value' => $value,
                ];
            }

            /* The intro line is derived from the scraped card body — we keep
               whatever sentence the source provides (after the parsed table
               rows are stripped). No hardcoded fallback. */
            $intro = $body;
            foreach ($rows as $row) {
                $intro = str_replace($row['label'].' '.$row['value'], '', $intro);
            }
            $intro = trim(preg_replace('/\s+/', ' ', $intro) ?? '');

            return [
                'title' => (string) ($card['card_title'] ?? ''),
                'intro' => $intro,
                'rows' => $rows,
            ];
        }, $cards);
    }

    private function featureImages(array $images): array
    {
        $seen = [];
        $featureImages = [];
        $preferred = array_values(array_filter(
            $images,
            fn (array $image) => stripos((string) ($image['section_heading'] ?? ''), 'Popular cities') !== false
        ));

        if ($preferred === []) {
            $preferred = $images;
        }

        foreach ($preferred as $image) {
            $url = (string) ($image['image_url'] ?? '');

            if ($url === '' || isset($seen[$url])) {
                continue;
            }

            $seen[$url] = true;
            $featureImages[] = [
                'image_url' => $url,
                'image_alt' => (string) ($image['image_alt'] ?? ''),
            ];
        }

        return $featureImages;
    }

    private function validImages(array $images): array
    {
        return array_values(array_filter($images, function (array $image): bool {
            $url = (string) ($image['image_url'] ?? '');

            return $url !== ''
                && ! str_starts_with($url, 'data:')
                && ! str_ends_with(strtolower($url), '.svg')
                && ! str_ends_with($url, '/');
        }));
    }

    private function cleanCardBody(string $title, string $body): string
    {
        $body = trim(preg_replace('/\s+/', ' ', $body) ?? '');
        $title = trim($title);

        if ($title !== '' && str_starts_with(strtolower($body), strtolower($title))) {
            $body = trim(substr($body, strlen($title)));
        }

        return $body;
    }

    private function sortKey(array $row): array
    {
        return [
            (int) ($row['section_order'] ?? 0),
            (int) ($row['content_order'] ?? $row['card_order'] ?? $row['course_order'] ?? $row['faq_order'] ?? $row['link_order'] ?? $row['image_order'] ?? 0),
        ];
    }
}
