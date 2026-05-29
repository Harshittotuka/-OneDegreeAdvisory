<?php

namespace App\Support;

class BlogContent
{
    public function all(): array
    {
        return $this->posts;
    }

    public function forSlug(string $slug): ?array
    {
        foreach ($this->posts as $post) {
            if ($post['slug'] === $slug) {
                return $post;
            }
        }

        return null;
    }

    public function related(string $slug, int $limit = 6): array
    {
        $current = $this->forSlug($slug);
        if (! $current) {
            return [];
        }

        $matches = array_values(array_filter(
            $this->posts,
            fn (array $post) => $post['slug'] !== $slug && $post['category'] === $current['category']
        ));

        foreach ($this->posts as $post) {
            if (count($matches) >= $limit) {
                break;
            }

            if ($post['slug'] === $slug || in_array($post, $matches, true)) {
                continue;
            }

            $matches[] = $post;
        }

        return array_slice($matches, 0, $limit);
    }

    private array $posts = [
        [
            'slug' => 'fallout-of-federal-cuts',
            'title' => 'The Impact of Federal Funding Cuts on Harvard, Columbia, & Elite Universities',
            'category' => 'One Degree',
            'date' => '2025-04-17',
            'read_time' => 7,
            'author' => 'One Degree',
            'excerpt' => 'A working placeholder on how policy shifts can ripple through admissions priorities at highly selective universities.',
            'image' => '/assets/heroes/usa.jpg',
            'alt' => 'A university setting in the United States.',
            'body' => [
                ['kind' => 'p', 'text' => 'This placeholder article outlines the kinds of strategic questions families should ask when institutional priorities shift.'],
            ],
        ],
        [
            'slug' => 'one-degree-test-requirements',
            'title' => 'One Degree Test Requirements for Class of 2030',
            'category' => 'College Admissions',
            'date' => '2026-05-27',
            'read_time' => 6,
            'author' => 'One Degree',
            'excerpt' => 'A dummy version of a One Degree-style post about testing policies across One Degree for the Class of 2030.',
            'image' => '/assets/heroes/uk.jpg',
            'alt' => 'A historic university setting.',
            'body' => [
                ['kind' => 'p', 'html' => 'In a highly competitive era of elite college admissions, testing requirements remain one of the clearest ways application processes vary among One Degree schools. This dummy copy is here only to preserve the page rhythm while the final editorial content is prepared.'],
                ['kind' => 'p', 'text' => 'Families should verify each university policy before submitting applications, then build a testing plan early enough that scores support the rest of the file rather than becoming a last-minute scramble.'],
                ['kind' => 'h2', 'text' => 'Which One Degree Schools Require Standardized Tests?'],
                ['kind' => 'table', 'rows' => [
                    ['One Degree School', 'Testing Required?'],
                    ['<a href="#">Brown University</a>', 'Yes'],
                    ['<a href="#">Columbia University</a>', 'Test-Optional'],
                    ['<a href="#">Cornell University</a>', 'Yes'],
                    ['<a href="#">Dartmouth College</a>', 'Yes'],
                    ['<a href="#">Harvard University</a>', 'Yes'],
                    ['<a href="#">University of Pennsylvania</a>', 'Yes'],
                    ['<a href="#">Princeton University</a>', 'Testing required for a future cycle, optional for the current cycle'],
                    ['<a href="#">Yale University</a>', 'Test-Flexible'],
                ]],
                ['kind' => 'p', 'text' => 'The larger strategic point is simple: testing policies are not the same as testing value. When a score strengthens the academic case, applicants should usually treat it as useful evidence.'],
                ['kind' => 'h2', 'text' => 'Test Scores Still Matter at Test-Optional Schools'],
                ['kind' => 'p', 'text' => 'A test-optional policy does not make strong testing irrelevant. It changes how students decide whether a score helps their candidacy relative to grades, course rigor, recommendations, essays, and institutional context.'],
                ['kind' => 'p', 'text' => 'Applicants with strong scores should think carefully before withholding them, especially when applying to the most selective colleges in the country.'],
                ['kind' => 'h2', 'text' => 'Only if a School Forbids the Submission of Test Scores Do They Really Not Matter'],
                ['kind' => 'p', 'text' => 'When a college explicitly refuses to consider testing, the strategic question changes. Everywhere else, scores can still serve as one more academic signal in a crowded applicant pool.'],
            ],
        ],
        [
            'slug' => 'ucla-acceptance-rate',
            'title' => 'UCLA Acceptance Rate and Statistics',
            'category' => 'College Admissions',
            'date' => '2026-05-28',
            'read_time' => 5,
            'author' => 'One Degree',
            'excerpt' => 'Dummy admissions statistics copy for a UCLA-focused article card.',
            'image' => '/assets/heroes/canada.jpg',
            'alt' => 'A university campus scene.',
            'body' => [
                ['kind' => 'p', 'text' => 'This is placeholder article content for a future UCLA statistics post.'],
            ],
        ],
        [
            'slug' => 'lowest-acceptance-rate-colleges',
            'title' => 'Lowest Acceptance Rate Colleges in 2026',
            'category' => 'College Admissions',
            'date' => '2026-05-28',
            'read_time' => 6,
            'author' => 'One Degree',
            'excerpt' => 'A placeholder overview of the institutions with the most selective admissions outcomes.',
            'image' => '/assets/heroes/australia.jpg',
            'alt' => 'A global university destination.',
            'body' => [
                ['kind' => 'p', 'text' => 'This dummy post will be replaced with a full analysis of selectivity trends.'],
            ],
        ],
        [
            'slug' => 'one-degree-regular-decision-deadlines',
            'title' => 'One Degree Regular Decision Application Deadlines',
            'category' => 'One Degree',
            'date' => '2026-05-28',
            'read_time' => 4,
            'author' => 'One Degree',
            'excerpt' => 'A dummy deadline guide for Regular Decision planning.',
            'image' => '/assets/heroes/europe.jpg',
            'alt' => 'A European study destination.',
            'body' => [
                ['kind' => 'p', 'text' => 'Placeholder content for One Degree Regular Decision deadlines.'],
            ],
        ],
        [
            'slug' => 'the-early-advantage-at-tulane-university',
            'title' => 'The Early Advantage at Tulane University',
            'category' => 'Early Decision / Early Action',
            'date' => '2026-05-28',
            'read_time' => 5,
            'author' => 'One Degree',
            'excerpt' => 'A dummy post about early application strategy at Tulane.',
            'image' => '/assets/heroes/dubai.jpg',
            'alt' => 'A modern international study destination.',
            'body' => [
                ['kind' => 'p', 'text' => 'Placeholder content for a future Tulane early admissions article.'],
            ],
        ],
        [
            'slug' => 'the-white-male-in-admissions',
            'title' => 'The Reality of White Male College Admission Rates',
            'category' => 'College Admissions',
            'date' => '2026-05-28',
            'read_time' => 7,
            'author' => 'One Degree',
            'excerpt' => 'A placeholder admissions trends article with One Degree-style card length.',
            'image' => '/assets/heroes/ireland.jpg',
            'alt' => 'Students near a university building.',
            'body' => [
                ['kind' => 'p', 'text' => 'Placeholder content for a future admissions rates article.'],
            ],
        ],
        [
            'slug' => 'top-non-one-degree-colleges-apply',
            'title' => 'Top 10 Non-One Degree Colleges To Apply To',
            'category' => 'College Admissions',
            'date' => '2026-05-27',
            'read_time' => 6,
            'author' => 'One Degree',
            'excerpt' => 'A dummy list-style article for the blog listing page.',
            'image' => '/assets/heroes/germany.jpg',
            'alt' => 'A university destination abroad.',
            'body' => [
                ['kind' => 'p', 'text' => 'Placeholder content for a future non-One Degree college list.'],
            ],
        ],
        [
            'slug' => 'how-to-get-into-syracuse-university',
            'title' => 'How To Get Into Syracuse University',
            'category' => 'College Admissions',
            'date' => '2026-04-21',
            'read_time' => 8,
            'author' => 'One Degree',
            'excerpt' => 'How to get into Syracuse University including acceptance rate trends, GPA expectations, and key admissions factors.',
            'image' => '/assets/heroes/france.jpg',
            'alt' => 'A European campus city.',
            'body' => [
                ['kind' => 'p', 'text' => 'Placeholder content for a Syracuse admissions guide.'],
            ],
        ],
        [
            'slug' => 'how-to-get-into-indiana-university',
            'title' => 'How To Get Into Indiana University',
            'category' => 'College Admissions',
            'date' => '2026-04-21',
            'read_time' => 8,
            'author' => 'One Degree',
            'excerpt' => 'How to get into Indiana University including acceptance rate trends, class rank data, and key factors.',
            'image' => '/assets/heroes/italy.jpg',
            'alt' => 'A historic international university city.',
            'body' => [
                ['kind' => 'p', 'text' => 'Placeholder content for an Indiana University admissions guide.'],
            ],
        ],
        [
            'slug' => 'how-to-get-into-uc-santa-cruz',
            'title' => 'How To Get Into UC Santa Cruz',
            'category' => 'College Admissions',
            'date' => '2026-04-18',
            'read_time' => 8,
            'author' => 'One Degree',
            'excerpt' => 'How to get into UC Santa Cruz including acceptance rate trends, GPA expectations, and admissions factors.',
            'image' => '/assets/heroes/netherlands.jpg',
            'alt' => 'An international study destination.',
            'body' => [
                ['kind' => 'p', 'text' => 'Placeholder content for a UC Santa Cruz guide.'],
            ],
        ],
        [
            'slug' => 'how-to-get-into-uc-irvine',
            'title' => 'How To Get Into UC Irvine',
            'category' => 'College Admissions',
            'date' => '2026-04-18',
            'read_time' => 8,
            'author' => 'One Degree',
            'excerpt' => 'How to get into UC Irvine with acceptance rate insights, GPA ranges, and admissions requirements.',
            'image' => '/assets/heroes/spain.jpg',
            'alt' => 'A warm international campus setting.',
            'body' => [
                ['kind' => 'p', 'text' => 'Placeholder content for a UC Irvine admissions guide.'],
            ],
        ],
        [
            'slug' => 'how-to-get-into-bucknell-university',
            'title' => 'How To Get Into Bucknell University',
            'category' => 'College Admissions',
            'date' => '2026-04-18',
            'read_time' => 7,
            'author' => 'One Degree',
            'excerpt' => 'How to get into Bucknell University with acceptance rate insights and admissions requirements.',
            'image' => '/assets/heroes/new-zealand.jpg',
            'alt' => 'A scenic study destination.',
            'body' => [
                ['kind' => 'p', 'text' => 'Placeholder content for a Bucknell admissions guide.'],
            ],
        ],
        [
            'slug' => 'how-to-get-into-college-of-the-holy-cross',
            'title' => 'How To Get Into College of the Holy Cross',
            'category' => 'College Admissions',
            'date' => '2026-04-18',
            'read_time' => 7,
            'author' => 'One Degree',
            'excerpt' => 'How to get into College of the Holy Cross with acceptance rate trends and class rank data.',
            'image' => '/assets/heroes/finland.jpg',
            'alt' => 'A northern European study destination.',
            'body' => [
                ['kind' => 'p', 'text' => 'Placeholder content for a Holy Cross admissions guide.'],
            ],
        ],
        [
            'slug' => 'how-to-get-into-brandeis-university',
            'title' => 'How To Get Into Brandeis University',
            'category' => 'College Admissions',
            'date' => '2026-04-18',
            'read_time' => 7,
            'author' => 'One Degree',
            'excerpt' => 'How to get into Brandeis University with a breakdown of acceptance rate and holistic review.',
            'image' => '/assets/heroes/belgium.jpg',
            'alt' => 'A European university destination.',
            'body' => [
                ['kind' => 'p', 'text' => 'Placeholder content for a Brandeis admissions guide.'],
            ],
        ],
        [
            'slug' => 'how-to-get-into-case-western-reserve-university',
            'title' => 'How To Get Into Case Western Reserve University',
            'category' => 'College Admissions',
            'date' => '2026-04-18',
            'read_time' => 7,
            'author' => 'One Degree',
            'excerpt' => 'How to get into Case Western Reserve University with acceptance rate and admissions data.',
            'image' => '/assets/heroes/poland.jpg',
            'alt' => 'A study abroad destination.',
            'body' => [
                ['kind' => 'p', 'text' => 'Placeholder content for a Case Western admissions guide.'],
            ],
        ],
        [
            'slug' => 'how-to-get-into-bates-college',
            'title' => 'How To Get Into Bates College',
            'category' => 'College Admissions',
            'date' => '2026-04-18',
            'read_time' => 7,
            'author' => 'One Degree',
            'excerpt' => 'How to get into Bates College with class rank data, academic expectations, and application insights.',
            'image' => '/assets/heroes/malta.jpg',
            'alt' => 'A coastal study destination.',
            'body' => [
                ['kind' => 'p', 'text' => 'Placeholder content for a Bates admissions guide.'],
            ],
        ],
        [
            'slug' => 'how-to-get-into-virginia-tech',
            'title' => 'How To Get Into Virginia Tech',
            'category' => 'College Admissions',
            'date' => '2026-04-18',
            'read_time' => 7,
            'author' => 'One Degree',
            'excerpt' => 'How to get into Virginia Tech including acceptance rate trends, GPA expectations, and key factors.',
            'image' => '/assets/heroes/georgia.jpg',
            'alt' => 'A global education destination.',
            'body' => [
                ['kind' => 'p', 'text' => 'Placeholder content for a Virginia Tech admissions guide.'],
            ],
        ],
    ];
}
