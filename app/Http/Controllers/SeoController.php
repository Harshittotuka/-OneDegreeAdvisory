<?php

namespace App\Http\Controllers;

use App\Support\BlogContent;
use App\Support\BriefPageStore;
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

        foreach ($studyLocations->destinations() as $destination) {
            if (empty($destination['slug'])) {
                continue;
            }

            $this->addUrl($urls, route('country.show', $destination['slug']), '0.68', 'monthly');
        }

        foreach ($mbbsCountries->countries() as $country) {
            if (empty($country['slug'])) {
                continue;
            }

            $this->addUrl($urls, route('mbbs.country', $country['slug']), '0.68', 'monthly');
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
            'courses.llb' => ['priority' => '0.68', 'changefreq' => 'monthly'],
            'courses.mba' => ['priority' => '0.72', 'changefreq' => 'monthly'],
            'courses.doctoral' => ['priority' => '0.68', 'changefreq' => 'monthly'],
            'mbbs.student' => ['priority' => '0.82', 'changefreq' => 'monthly'],
            'careers' => ['priority' => '0.50', 'changefreq' => 'monthly'],
            'contact' => ['priority' => '0.82', 'changefreq' => 'monthly'],
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

    private function date(mixed $value): ?string
    {
        $time = strtotime((string) $value);

        return $time ? date('Y-m-d', $time) : null;
    }
}
