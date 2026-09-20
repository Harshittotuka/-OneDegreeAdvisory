@php
    use App\Support\Seo;

    $pageTitle = ($page['page_title'] ?? '') ?: (($page['title'] ?? config('site.name')).' | '.config('site.name'));
    $pageDescription = Seo::description($page['meta_description'] ?? null, Seo::layoutText($page['layout'] ?? []) ?: config('site.description'), 170);
    $activeNav = null;
    $mainId = 'main';

    // An "AI / Embed" block holds pasted markup that may position itself fixed
    // (overlays, sticky bars, a :target lightbox). While the <main> entry
    // animation runs, <main> is the containing block for those — they lay out
    // against it rather than the viewport, and .odp-file-page's overflow-x:
    // clip then hides them outright. A page carrying one asks for the
    // fade-only variant instead. See .odp-has-embed in public/styles.css.
    $hasEmbed = false;
    foreach (($page['layout'] ?? []) as $row) {
        foreach (($row['cols'] ?? []) as $col) {
            foreach (($col['blocks'] ?? []) as $block) {
                if (is_array($block) && ($block['type'] ?? '') === 'embed') {
                    $hasEmbed = true;
                    break 3;
                }
            }
        }
    }
    // Legacy pages stored a flat section list instead of the grid.
    foreach ($hasEmbed ? [] : ($page['sections'] ?? []) as $block) {
        if (is_array($block) && ($block['type'] ?? '') === 'embed') {
            $hasEmbed = true;
            break;
        }
    }
    $bodyClass = $hasEmbed ? 'odp-has-embed' : null;

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
