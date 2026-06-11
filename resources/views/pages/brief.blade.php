@php
    $pageTitle = ($page['page_title'] ?? '') ?: (($page['title'] ?? 'One Degree Advisory').' | One Degree Advisory');
    $pageDescription = $page['meta_description'] ?? config('site.description');
    $activeNav = null;
    $mainId = 'main';
@endphp

@extends('layouts.app')

@include('partials.brief._styles')

@section('content')
<main id="main" class="odp-file-page">
  <div class="odp-file-container">
    @include('partials.brief._render', ['layout' => $page['layout'] ?? null, 'sections' => $page['sections'] ?? []])
  </div>
</main>
@endsection
