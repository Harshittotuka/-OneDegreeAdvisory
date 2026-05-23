<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home');
    }

    public function insights(): View
    {
        return view('pages.insights');
    }

    public function about(): View
    {
        return view('pages.about');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function mbbsStudent(): View
    {
        return view('pages.mbbs-student');
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
}
