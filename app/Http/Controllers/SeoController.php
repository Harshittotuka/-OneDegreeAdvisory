<?php

namespace App\Http\Controllers;

use App\Support\BlogContent;
use App\Support\BriefPageStore;
use App\Support\CountryGuideCopy;
use App\Support\MbbsCountryContent;
use App\Support\Seo;
use App\Support\StudyLocationContent;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        // Only the canonical production host (onedegreeadvisory.com) is allowed
        // to be crawled. Any other host — the nip.io UAT/test box, the raw IP,
        // or a *.litespeed preview — gets a blanket disallow so Google never
        // indexes a duplicate of the site that would compete with the real one.
        if (! Seo::isCanonicalHost()) {
            return response("User-agent: *\nDisallow: /\n", 200)
                ->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        $content = file_get_contents(public_path('robots.txt')) ?: '';

        return response($content, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function sitemap(
        BlogContent $blog,
        BriefPageStore $briefs,
        StudyLocationContent $studyLocations,
        MbbsCountryContent $mbbsCountries
    ): Response {
        $urls = [];

        foreach ($this->staticRoutes() as $route => $meta) {
            $this->addUrl($urls, route($route), $meta['priority'], $meta['changefreq']);
        }

        $this->addUrl($urls, route('blog.index'), '0.80', 'weekly');

        foreach ($blog->all() as $post) {
            if (($post['visible'] ?? true) !== true || empty($post['slug'])) {
                continue;
            }

            // Link-only entries have no article of their own: /blog/{slug}
            // permanently redirects to another page. Redirecting URLs do not
            // belong in a sitemap; the destination is emitted by its own page
            // source (static route, brief page, country page, and so on).
            if (BlogContent::isLink($post)) {
                continue;
            }

            $this->addUrl(
                $urls,
                route('blog.post', $post['slug']),
                '0.74',
                'monthly',
                $this->date($post['updated_at'] ?? $post['date'] ?? null)
            );
        }

        foreach ($briefs->visible() as $page) {
            if (empty($page['path'])) {
                continue;
            }

            $this->addUrl($urls, url($page['path']), '0.70', 'monthly');
        }

        // Country pages last changed when the partner sync last finished --
        // except the ones rewritten by hand, which carry their own date and are
        // newer than any sync.
        $countrySynced = $this->syncFinishedAt('country-sync-status.json');

        foreach ($studyLocations->destinations() as $destination) {
            if (empty($destination['slug'])) {
                continue;
            }

            $written = CountryGuideCopy::forSlug($destination['slug'])['updated'] ?? null;

            $this->addUrl(
                $urls,
                route('country.show', $destination['slug']),
                '0.68',
                'monthly',
                $this->date($written ?? $countrySynced)
            );
        }

        $mbbsSynced = $this->syncFinishedAt('mbbs-country-sync-status.json');

        foreach ($mbbsCountries->countries() as $country) {
            if (empty($country['slug'])) {
                continue;
            }

            $this->addUrl(
                $urls,
                route('mbbs.country', $country['slug']),
                '0.68',
                'monthly',
                $this->date($mbbsSynced)
            );
        }

        ksort($urls);

        $xml = view('seo.sitemap', ['urls' => array_values($urls)])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function staticRoutes(): array
    {
        return [
            'home' => ['priority' => '1.00', 'changefreq' => 'weekly'],
            'about' => ['priority' => '0.78', 'changefreq' => 'monthly'],
            'study-abroad' => ['priority' => '0.86', 'changefreq' => 'monthly'],
            'services.admissions-counselling' => ['priority' => '0.82', 'changefreq' => 'monthly'],
            'services.student-services' => ['priority' => '0.78', 'changefreq' => 'monthly'],
            'services.test-prep' => ['priority' => '0.78', 'changefreq' => 'monthly'],
            'courses.ug' => ['priority' => '0.74', 'changefreq' => 'monthly'],
            'courses.pg' => ['priority' => '0.74', 'changefreq' => 'monthly'],
            'courses.mba' => ['priority' => '0.72', 'changefreq' => 'monthly'],
            'courses.doctoral' => ['priority' => '0.68', 'changefreq' => 'monthly'],
            'mbbs.student' => ['priority' => '0.82', 'changefreq' => 'monthly'],
            // Student Hub tools and landing pages. These shipped after the
            // original list and were never added, so none of them was ever
            // advertised to search engines.
            'profiler' => ['priority' => '0.80', 'changefreq' => 'monthly'],
            'career-counselling' => ['priority' => '0.80', 'changefreq' => 'monthly'],
            'career-library.index' => ['priority' => '0.78', 'changefreq' => 'weekly'],
            'sop.index' => ['priority' => '0.76', 'changefreq' => 'monthly'],
            'visa' => ['priority' => '0.76', 'changefreq' => 'monthly'],
            'visa-mock' => ['priority' => '0.74', 'changefreq' => 'monthly'],
            'loan-acco.index' => ['priority' => '0.72', 'changefreq' => 'monthly'],
            'student-development' => ['priority' => '0.72', 'changefreq' => 'monthly'],
            'referral' => ['priority' => '0.60', 'changefreq' => 'monthly'],
            'careers' => ['priority' => '0.50', 'changefreq' => 'monthly'],
            'contact' => ['priority' => '0.82', 'changefreq' => 'monthly'],
            'privacy' => ['priority' => '0.30', 'changefreq' => 'yearly'],
            'terms' => ['priority' => '0.30', 'changefreq' => 'yearly'],
        ];
    }

    private function addUrl(array &$urls, string $loc, string $priority, string $changefreq, ?string $lastmod = null): void
    {
        $urls[$loc] = array_filter([
            'loc' => $loc,
            'lastmod' => $lastmod,
            'changefreq' => $changefreq,
            'priority' => $priority,
        ]);
    }

    /**
     * When a partner sync last finished writing the content behind a set of
     * pages, or null if it has never run.
     *
     * This is a real answer to "when did this page last change", which is the
     * only kind Google pays attention to. Pages with no honest date -- the
     * static routes, and the brief pages, whose store keeps block versions but
     * no timestamps -- get no lastmod at all rather than a guess. A sitemap
     * that claims everything changed today is one Google learns to ignore.
     */
    private function syncFinishedAt(string $file): ?string
    {
        $path = storage_path('app/'.$file);

        if (! is_file($path)) {
            return null;
        }

        $status = json_decode((string) file_get_contents($path), true);

        return is_array($status) ? ($status['finished_at'] ?? null) : null;
    }

    private function date(mixed $value): ?string
    {
        $time = strtotime((string) $value);

        return $time ? date('Y-m-d', $time) : null;
    }
}
