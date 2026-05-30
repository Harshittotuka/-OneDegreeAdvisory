<?php

namespace App\Http\Controllers;

use App\Support\AboutContent;
use App\Support\BlogContent;
use App\Support\MbbsCountryContent;
use App\Support\StudyLocationContent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home');
    }

    public function about(AboutContent $about): View
    {
        return view('pages.about', [
            'sections' => $about->visible(),
        ]);
    }

    public function careers(): View
    {
        return view('pages.careers');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function blogIndex(Request $request, BlogContent $blog): View
    {
        $perPage = 9;
        $page    = max(1, (int) $request->query('page', 1));

        // Only show posts whose visibility is on (default true for legacy posts).
        $allPosts = array_values(array_filter(
            $blog->all(),
            fn (array $p) => ($p['visible'] ?? true) === true
        ));

        // Pull out the pinned/featured post so it stays on top of every page
        // and never gets pushed down by pagination.
        $featured = null;
        foreach ($allPosts as $i => $candidate) {
            if (! empty($candidate['featured'])) {
                $featured = $candidate;
                unset($allPosts[$i]);
                break;
            }
        }
        $allPosts = array_values($allPosts);

        $totalPages = max(1, (int) ceil(count($allPosts) / $perPage));
        $page       = min($page, $totalPages);
        $posts      = array_slice($allPosts, ($page - 1) * $perPage, $perPage);

        // The featured post renders as the big hero card at the top (template
        // treats posts[0] as featured). Pinning it here keeps it first on page 1+.
        if ($featured) {
            array_unshift($posts, $featured);
        }

        return view('pages.blog-index', [
            'posts'      => $posts,
            'page'       => $page,
            'totalPages' => $totalPages,
        ]);
    }

    public function blogPost(string $slug, BlogContent $blog): View
    {
        $post = $blog->forSlug($slug);
        abort_unless($post, 404);

        return view('pages.blog-post', [
            'post'    => $post,
            'related' => $blog->related($slug, 4),
        ]);
    }

    public function testPreparation(): View
    {
        return view('pages.test-preparation');
    }

    public function admissionsCounselling(): View
    {
        return view('pages.admissions-counselling');
    }

    public function studentServices(): View
    {
        return view('pages.student-services');
    }

    public function undergraduate(): View
    {
        return view('pages.undergraduate');
    }

    public function postgraduate(): View
    {
        return view('pages.postgraduate');
    }

    public function llb(): View
    {
        return view('pages.llb');
    }

    public function mba(): View
    {
        return view('pages.mba');
    }

    public function doctoral(): View
    {
        return view('pages.doctoral');
    }

    public function mbbsStudent(): View
    {
        return view('pages.mbbs-student');
    }

    public function country(string $country, StudyLocationContent $content): View
    {
        $studyContent = $content->forSlug($country);

        abort_unless($studyContent['page'] ?? null, 404);

        return view('countries.destination', [
            'destination' => $studyContent['destination'] ?? [],
            'studyContent' => $studyContent,
        ]);
    }

    public function mbbsCountry(string $country, MbbsCountryContent $content): View
    {
        $countries = [
            'georgia' => ['name' => 'Georgia', 'flag' => 'ge'],
            'russia' => ['name' => 'Russia', 'flag' => 'ru'],
            'kazakhstan' => ['name' => 'Kazakhstan', 'flag' => 'kz'],
            'kyrgyzstan' => ['name' => 'Kyrgyzstan', 'flag' => 'kg'],
            'uzbekistan' => ['name' => 'Uzbekistan', 'flag' => 'uz'],
        ];

        abort_unless(isset($countries[$country]), 404);

        $mbbsContent = $content->forSlug($country);
        abort_unless($mbbsContent['page'] ?? null, 404);

        return view('mbbs.country', [
            'countrySlug' => $country,
            'countryMeta' => $countries[$country],
            'mbbsContent' => $mbbsContent,
        ]);
    }
}
