@php
    use App\Support\Seo;

    $pageTitle = ($page['page_title'] ?? '') ?: (($page['title'] ?? config('site.name')).' | '.config('site.name'));
    $pageDescription = Seo::description($page['meta_description'] ?? null, Seo::layoutText($page['layout'] ?? []) ?: config('site.description'), 170);
    $activeNav = null;
    $mainId = 'main';
    $canonical = url($page['path'] ?? request()->path());
    $robots = ($page['visible'] ?? true) ? null : 'noindex, nofollow';
    $webPageJsonLd = Seo::jsonLd([
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => $pageTitle,
        'description' => $pageDescription,
        'url' => $canonical,
        'isPartOf' => ['@type' => 'WebSite', 'name' => config('site.name'), 'url' => route('home')],
        'publisher' => ['@type' => 'Organization', 'name' => config('site.name'), 'url' => route('home')],
        'inLanguage' => 'en',
    ]);
    $breadcrumbJsonLd = Seo::jsonLd([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $page['title'] ?? 'Page', 'item' => $canonical],
        ],
    ]);
@endphp

@extends('layouts.app')

@include('partials.brief._styles')

@push('head')
  <script type="application/ld+json">
  {!! $webPageJsonLd !!}
  </script>
  <script type="application/ld+json">
  {!! $breadcrumbJsonLd !!}
  </script>
@endpush

@section('content')
<main id="main" class="odp-file-page">
  <div class="odp-file-container">
    @include('partials.brief._render', [
      'layout' => $page['layout'] ?? null,
      'sections' => $page['sections'] ?? [],
      'pageSlug' => $page['slug'] ?? '',
    ])
  </div>
</main>
@endsection
