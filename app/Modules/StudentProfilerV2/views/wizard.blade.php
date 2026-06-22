@extends('layouts.app')

{{--
    Student Profiler V2 — the Student Profiler's degree-adaptive questions
    rendered in the Profile Evaluator's design.

    Self-contained to this module: it extends the shared site layout for the
    header/footer/SEO chrome, but every style and script it uses is its own
    module asset (prefixed `p2-`), injected via @push('head'). The wizard UI is
    rendered client-side by student-profiler-v2.js from the JSON config below; a
    no-JS fallback explains the experience and links to contact.

    v1 (/profiler) is left completely untouched — this is an additive sibling.
--}}

@php
    // Self-contained mtime cache-bust for the module's own assets.
    $p2Asset = function (string $file) {
        $path = public_path($file);
        return is_file($path) ? asset($file) . '?v=' . filemtime($path) : asset($file);
    };
@endphp

@push('head')
    {{-- Roboto + Poppins — the exact type lockup the Profile Evaluator design uses. --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Roboto:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ $p2Asset('assets/student-profiler-v2/student-profiler-v2.css') }}">
    <script>
        window.__PROFILER_V2__ = {
            config: @json($config),
            state: @json($state),
            endpoint: @json(url('/profiler-v2')),
            csrf: @json(csrf_token())
        };
    </script>
    <script src="{{ $p2Asset('assets/student-profiler-v2/student-profiler-v2.js') }}" defer></script>
@endpush

@section('content')
<main id="main" class="p2-root" data-p2-root>
    <noscript>
        <div class="p2-noscript">
            <h1>Student Profiler</h1>
            <p>This interactive profiler needs JavaScript enabled. It asks about your degree level,
               academics, test scores, study preferences and budget, then hands your profile to our
               advisors for a personal review.</p>
            <p><a href="{{ route('contact') }}">Talk to an advisor instead →</a></p>
        </div>
    </noscript>

    {{-- The wizard mounts here. Markup is built by student-profiler-v2.js. --}}
    <div class="p2-stage" data-p2-stage aria-live="polite"></div>
</main>
@endsection
