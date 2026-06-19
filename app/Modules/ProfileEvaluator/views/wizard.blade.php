@extends('layouts.app')

{{--
    Profile Evaluator — animated profile-evaluation questionnaire.

    A native rebuild of the mim-essay "Evaluate My Profile" tool. Self-contained
    to this module: it extends the shared site layout for the header/footer/SEO
    chrome, but every style and script it uses is its own module asset (prefixed
    `pe-`), injected via @push('head'). The wizard UI is rendered client-side by
    profile-evaluator.js from the JSON config below; a no-JS fallback explains
    the experience and links to contact.
--}}

@php
    // Self-contained mtime cache-bust for the module's own assets.
    $peAsset = function (string $file) {
        $path = public_path($file);
        return is_file($path) ? asset($file) . '?v=' . filemtime($path) : asset($file);
    };
@endphp

@push('head')
    {{-- Roboto + Poppins — the exact type lockup mim-essay's evaluate-my-profile uses. --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Roboto:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ $peAsset('assets/profile-evaluator/profile-evaluator.css') }}">
    <script>
        window.__EVALUATOR__ = {
            config: @json($config),
            state: @json($state),
            endpoint: @json(url('/evaluate-my-profile')),
            csrf: @json(csrf_token())
        };
    </script>
    <script src="{{ $peAsset('assets/profile-evaluator/profile-evaluator.js') }}" defer></script>
@endpush

@section('content')
<main id="main" class="pe-root" data-pe-root>
    <noscript>
        <div class="pe-noscript">
            <h1>Evaluate My Profile</h1>
            <p>This interactive profile evaluator needs JavaScript enabled. It asks about your
               academics, extracurriculars, differentiators, work experience, test scores and
               target degree, then hands your profile to our advisors for a personal review.</p>
            <p><a href="{{ route('contact') }}">Talk to an advisor instead →</a></p>
        </div>
    </noscript>

    {{-- The wizard mounts here. Markup is built by profile-evaluator.js. --}}
    <div class="pe-stage" data-pe-stage aria-live="polite"></div>
</main>
@endsection
