@extends('layouts.app')

{{--
    Student Profiler — animated, degree-adaptive questionnaire.

    Self-contained to the Student Profiler module. It extends the shared site
    layout for the header/footer/SEO chrome, but every style and script it uses
    is its own module asset (prefixed `sp-`), injected via @push('head'). The
    wizard UI is rendered client-side by student-profiler.js from the JSON
    config below; a no-JS fallback explains the experience and links to contact.
--}}

@php
    // Self-contained mtime cache-bust for the module's own assets.
    $spAsset = function (string $file) {
        $path = public_path($file);
        return is_file($path) ? asset($file) . '?v=' . filemtime($path) : asset($file);
    };
@endphp

@push('head')
    <link rel="stylesheet" href="{{ $spAsset('assets/student-profiler/student-profiler.css') }}">
    <script>
        window.__PROFILER__ = {
            config: @json($config),
            state: @json($state),
            endpoint: @json(url('/profiler')),
            csrf: @json(csrf_token())
        };
    </script>
    <script src="{{ $spAsset('assets/student-profiler/student-profiler.js') }}" defer></script>
@endpush

@section('content')
<main id="main" class="sp-root" data-sp-root>
    <noscript>
        <div class="sp-noscript">
            <h1>Student Profiler</h1>
            <p>This interactive profiler needs JavaScript enabled. It asks about your degree level,
               academics, test scores, study preferences and budget, then gives you a tailored profile
               report and a best-fit university shortlist.</p>
            <p><a href="{{ route('contact') }}">Talk to an advisor instead →</a></p>
        </div>
    </noscript>

    {{-- The wizard mounts here. Markup is built by student-profiler.js. --}}
    <div class="sp-stage" data-sp-stage aria-live="polite"></div>
</main>
@endsection
