<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * "Did you mean…?" for the 404 page: the real pages a broken URL most likely
 * meant, e.g. /countries/canada → Study in Canada, /vsa → Student Visa,
 * /services/test-prep → Test Preparation.
 *
 * The candidates are the pages the sitemap lists — fixed pages, blog posts,
 * country guides, MBBS countries and page-builder pages — so a suggestion is
 * never a hidden draft or a private area. Nothing here redirects: a guess is
 * only offered as a link.
 */
class NotFoundSuggestions
{
    /** Words that say nothing about which page was meant. */
    private const STOP_WORDS = [
        'a', 'an', 'and', 'at', 'for', 'in', 'of', 'on', 'the', 'to', 'us',
        'amp', 'htm', 'html', 'index', 'page', 'pages', 'php', 'www',
    ];

    /**
     * Short forms students type for pages whose names spell them out. Without
     * these "/sop" reads as a typo of "top" and lands on the wrong article.
     */
    private const ALIASES = [
        'sop' => ['statement', 'purpose'],
        'ielts' => ['test', 'preparation'],
        'toefl' => ['test', 'preparation'],
        'pte' => ['test', 'preparation'],
        'gre' => ['test', 'preparation'],
        'gmat' => ['test', 'preparation'],
        'sat' => ['test', 'preparation'],
        'phd' => ['doctoral'],
        'ug' => ['undergraduate'],
        'pg' => ['postgraduate'],
    ];

    /** A guess must score this well, on a 0–1 scale, to be offered at all. */
    private const MIN_SCORE = 0.55;

    /**
     * …and come within this share of the best guess, so one clear match is not
     * padded out with weak ones that merely share a section ("/blogs" → Blog,
     * not every post under /blog).
     */
    private const NEAR_BEST = 0.9;

    /** @var array<int, array{title: string, url: string, path: string}>|null */
    private ?array $pages = null;

    public function __construct(
        private BlogContent $blog,
        private BriefPageStore $briefs,
        private StudyLocationContent $destinations,
        private MbbsCountryContent $mbbs,
    ) {}

    /**
     * @return array<int, array{title: string, url: string, path: string}>
     */
    public function for(string $path, int $limit = 3): array
    {
        $query = [];
        foreach (self::words($path) as $word) {
            array_push($query, ...(self::ALIASES[$word] ?? [$word]));
        }
        $query = array_values(array_unique($query));
        if ($query === []) {
            return [];
        }
        $querySlug = implode('-', $query);

        $scored = [];
        foreach ($this->pages() as $page) {
            $words = self::words($page['path'].' '.$page['title']);
            $hits = array_sum(array_map(fn (string $q) => self::bestMatch($q, $words), $query));
            if ($hits == 0) {
                continue;
            }

            // Mostly: how many of the words typed does this page answer to.
            // Then how close the address itself is, which breaks ties between
            // pages that share the same words (/visa over /visa-mock-interview
            // for "/vsa").
            similar_text($querySlug, implode('-', self::words($page['path'])), $percent);
            $score = 0.75 * ($hits / count($query)) + 0.25 * ($percent / 100);

            if ($score >= self::MIN_SCORE) {
                $scored[] = $page + ['score' => $score];
            }
        }

        if ($scored === []) {
            return [];
        }

        usort($scored, fn (array $a, array $b) => $b['score'] <=> $a['score']);
        $floor = $scored[0]['score'] * self::NEAR_BEST;

        return array_map(
            fn (array $page) => ['title' => $page['title'], 'url' => $page['url'], 'path' => $page['path']],
            array_slice(array_values(array_filter($scored, fn (array $page) => $page['score'] >= $floor)), 0, $limit)
        );
    }

    /**
     * What the 404 page's in-page finder searches: each page's title and URL,
     * plus the words a student might type for it instead — its address, and
     * any short form above that it answers to ("ielts" → Test Preparation).
     *
     * @return array<int, array{t: string, u: string, k: string}>
     */
    public function searchIndex(): array
    {
        return array_map(function (array $page): array {
            $words = self::words($page['path'].' '.$page['title']);
            $aliases = array_keys(array_filter(
                self::ALIASES,
                fn (array $expansion) => array_diff($expansion, $words) === []
            ));

            return [
                't' => $page['title'],
                'u' => $page['url'],
                'k' => implode(' ', array_merge(self::words($page['path']), $aliases)),
            ];
        }, $this->pages());
    }

    /**
     * Every public page, as title + URL.
     *
     * @return array<int, array{title: string, url: string, path: string}>
     */
    public function pages(): array
    {
        if ($this->pages !== null) {
            return $this->pages;
        }

        $pages = [];
        $add = function (string $title, string $url) use (&$pages): void {
            $title = trim($title);
            if ($title === '') {
                return;
            }
            $fragment = (string) parse_url($url, PHP_URL_FRAGMENT);
            $path = '/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/').($fragment !== '' ? '#'.$fragment : '');
            $pages[$path] ??= ['title' => $title, 'url' => $url, 'path' => $path];
        };

        foreach (Breadcrumbs::pageNames() as $route => $name) {
            $add($name, route($route));
        }

        // The country list has no page of its own: it is a section of the home
        // page, the same place the country pages' trail points to.
        $add('Study destinations', route('home').'#destinations');

        foreach ($this->destinations->destinations() as $destination) {
            if (! empty($destination['slug'])) {
                $add('Study in '.$destination['name'], route('country.show', $destination['slug']));
            }
        }

        foreach ($this->mbbs->countries() as $country) {
            if (! empty($country['slug'])) {
                $add('MBBS in '.$country['name'], route('mbbs.country', $country['slug']));
            }
        }

        foreach ($this->briefs->visible() as $brief) {
            if (! empty($brief['path'])) {
                $add((string) ($brief['title'] ?? ''), url($brief['path']));
            }
        }

        // Same rule as the sitemap: visible posts with an article of their own.
        foreach ($this->blog->all() as $post) {
            if (($post['visible'] ?? true) === true && ! empty($post['slug']) && ! BlogContent::isLink($post)) {
                $add((string) ($post['title'] ?? ''), route('blog.post', $post['slug']));
            }
        }

        return $this->pages = array_values($pages);
    }

    /**
     * @return list<string>
     */
    private static function words(string $text): array
    {
        $text = Str::lower(Str::ascii(rawurldecode($text)));
        $words = preg_split('/[^a-z0-9]+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_diff($words, self::STOP_WORDS)));
    }

    /**
     * How well the closest of $words answers to $query: 1 for the same word,
     * less for one cut short ("prep" for "preparation") and less again for a
     * typo, so "bates" finds Bates College before it finds "rates".
     *
     * @param  list<string>  $words
     */
    private static function bestMatch(string $query, array $words): float
    {
        $best = 0.0;
        foreach ($words as $word) {
            $best = max($best, self::match($query, $word));
            if ($best === 1.0) {
                break;
            }
        }

        return $best;
    }

    private static function match(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        $la = strlen($a);
        $lb = strlen($b);
        if ($la >= 4 && $lb >= 4 && (str_starts_with($a, $b) || str_starts_with($b, $a))) {
            return 0.9;
        }

        $typos = $la >= 7 ? 2 : ($la >= 3 ? 1 : 0);

        return $typos > 0 && abs($la - $lb) <= $typos && levenshtein($a, $b) <= $typos ? 0.7 : 0.0;
    }
}
