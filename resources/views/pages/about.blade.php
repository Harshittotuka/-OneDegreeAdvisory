@php
    $pageTitle = 'About | One Degree Advisory';
    $pageDescription = 'Meet One Degree Advisory — a senior, partner-led education advisory architecting study-abroad futures with strategy, evidence, and care.';
    $activeNav = 'about';
    $mainId = 'main';
@endphp

@extends('layouts.app')

@section('content')
<main id="main" class="va-about-page">

  {{-- Sections are managed in the CMS at /admin/about — order, visibility and
       every field are editable there. Each type renders via its own partial. --}}
  @foreach($sections as $section)
    @includeIf('partials.about.'.($section['type'] ?? ''), ['data' => $section['data'] ?? [], 'section' => $section])
  @endforeach

</main>
@endsection
