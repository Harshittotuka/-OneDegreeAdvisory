<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * The breadcrumb trail shown under the header, and the BreadcrumbList that
 * describes the same trail to search engines. Both are built from one list,
 * so the markup and the schema can never disagree.
 *
 * A page gets its trail one of two ways:
 *   - from ROUTES below, looked up by the current route's name, or
 *   - from a $breadcrumbs variable the view sets itself, for pages whose last
 *     crumb is data (a blog post's title, a country's name). The view sets it
 *     to [] to show no trail at all.
 *
 * A route that is in neither place (the home page, the student and CRM areas,
 * the private mock-interview links) shows no trail. That is the safe default
 * for a page nobody has thought about yet.
 */
class Breadcrumbs
{
    /**
     * Crumbs after "Home", as [label, parent route name]. The last entry is
     * the page itself and carries no route: it links to the canonical URL.
     *
     * A parent appears only where a real page sits above this one and links
     * down to it. The nav's own groups (Destinations, Student Hub, Fit
     * Assessment) are menus with no page behind them, so the pages inside
     * them hang directly off Home.
     */
    private const ROUTES = [
        'about' => [['About', null]],
        'careers' => [['Careers', null]],
        'contact' => [['Contact', null]],
        'privacy' => [['Privacy Policy', null]],
        'terms' => [['Terms & Conditions', null]],
        'blog.index' => [['Blog', null]],

        'study-abroad' => [['Study Abroad', null]],
        // /study-abroad lists these three as its own services.
        'services.admissions-counselling' => [['Study Abroad', 'study-abroad'], ['Admissions Counselling', null]],
        'services.test-prep' => [['Study Abroad', 'study-abroad'], ['Test Preparation', null]],
        'services.student-services' => [['Study Abroad', 'study-abroad'], ['Student Services', null]],

        'courses.ug' => [['Undergraduate Programs', null]],
        'courses.pg' => [['Postgraduate Programs', null]],
        'courses.mba' => [['MBA Programs', null]],
        'courses.doctoral' => [['Doctoral & PhD Programs', null]],
        'mbbs.student' => [['MBBS Abroad', null]],

        'career-counselling' => [['Career Counselling', null]],
        'career-library.index' => [['Global Career Library', null]],
        'student-development' => [['Student Development Programme', null]],
        'profiler' => [['Student Profiler', null]],
        'loan-acco.index' => [['Loan & Accommodation', null]],
        'visa' => [['Student Visa', null]],
        'visa-mock' => [['Visa Mock Interview', null]],
        'sop.index' => [['Statement of Purpose', null]],
        'referral' => [['Referral Program', null]],
    ];

    /**
     * The full trail for the current page, Home first, each crumb as
     * ['name' => ..., 'url' => ...]. Empty when the page shows no trail.
     *
     * @param  array<int, array{name: string, url?: string|null}>|null  $crumbs  a view's own crumbs after Home; null to look the route up
     * @param  string  $currentUrl  the page's canonical URL, used for the last crumb
     * @return array<int, array{name: string, url: string}>
     */
    public static function trail(?array $crumbs, string $currentUrl): array
    {
        $crumbs ??= self::forRoute(Route::currentRouteName());

        if ($crumbs === []) {
            return [];
        }

        $last = array_key_last($crumbs);
        $trail = [['name' => 'Home', 'url' => route('home')]];

        foreach ($crumbs as $i => $crumb) {
            $name = trim((string) ($crumb['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $trail[] = [
                'name' => $name,
                'url' => $i === $last ? $currentUrl : (string) ($crumb['url'] ?? $currentUrl),
            ];
        }

        return count($trail) > 1 ? $trail : [];
    }

    /**
     * Every page with a fixed trail, as route name => the name its trail ends
     * in. The 404 page offers these when it guesses what a broken link meant.
     *
     * @return array<string, string>
     */
    public static function pageNames(): array
    {
        return array_map(fn (array $crumbs) => $crumbs[array_key_last($crumbs)][0], self::ROUTES);
    }

    /**
     * @return array<int, array{name: string, url: string|null}>
     */
    public static function forRoute(?string $routeName): array
    {
        return array_map(fn (array $crumb) => [
            'name' => $crumb[0],
            'url' => $crumb[1] !== null ? route($crumb[1]) : null,
        ], self::ROUTES[$routeName] ?? []);
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $trail
     */
    public static function jsonLd(array $trail): string
    {
        return Seo::jsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $crumb, int $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            ], $trail, array_keys($trail)),
        ]);
    }
}
