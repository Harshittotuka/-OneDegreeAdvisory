<?php

namespace App\Http\Controllers;

use App\Support\BlogContent;
use App\Support\MbbsCountryContent;
use App\Support\StudyLocationContent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home');
    }

    public function about(): View
    {
        return view('pages.about');
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

        $allPosts   = $blog->all();
        $totalPages = max(1, (int) ceil(count($allPosts) / $perPage));
        $page       = min($page, $totalPages);
        $posts      = array_slice($allPosts, ($page - 1) * $perPage, $perPage);

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

    public function mbbsStudent(): View
    {
        return view('pages.mbbs-student');
    }

    public function mbbsStudentV2(): View
    {
        return view('pages.mbbs-student-v2');
    }

    public function country(string $country): View
    {
        $destination = Arr::first(
            config('site.destinations'),
            fn (array $destination) => $destination['slug'] === $country
        );

        abort_unless($destination, 404);

        return view("countries.{$country}", [
            'destination' => $destination,
        ]);
    }

    public function countryV2(string $country, StudyLocationContent $content): View
    {
        $studyContent = $content->forSlug($country);

        abort_unless($studyContent['page'] ?? null, 404);

        return view('countries.study-location-dynamic', [
            'destination' => $studyContent['destination'] ?? [],
            'studyContent' => $studyContent,
        ]);
    }

    public function studyInUkDynamic(StudyLocationContent $content): View
    {
        return $this->countryV2('study-in-uk', $content);
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
