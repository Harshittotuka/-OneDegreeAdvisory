@php
    use App\Support\Seo;

    $rawSeoTitle     = trim((string) ($post['seo_title'] ?? ''));
    $pageTitle       = $rawSeoTitle !== '' ? $rawSeoTitle : $post['title'].' | '.config('site.name').' Blog';
    $pageDescription = Seo::description($post['meta_description'] ?? null, ($post['excerpt'] ?? '') ?: Seo::blogBodyText($post['body'] ?? []));
    $activeNav       = 'blog';
    $mainId          = 'blog-main';
    $bodyClass       = 'page-blog page-blog-post';
    $canonical       = route('blog.post', $post['slug']);
    $ogImage         = $post['image'] ?? null;
    $ogImageAlt      = $post['alt'] ?? $post['title'];
    $ogType          = 'article';
    $robots          = ($post['visible'] ?? true) === true ? null : 'noindex, nofollow';

    $fmtDate = fn (string $iso): string => date('F j, Y', strtotime($iso));
    $publishedTime = date(DATE_ATOM, strtotime($post['date']));
    $modifiedTime = date(DATE_ATOM, strtotime($post['updated_at'] ?? $post['date']));
    $categories = $post['categories'] ?? array_filter([$post['category'] ?? null]);
    $articleJsonLd = Seo::jsonLd([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
        'headline' => $post['title'],
        'description' => $pageDescription,
        'image' => Seo::imageUrl($post['image'] ?? null),
        'datePublished' => $publishedTime,
        'dateModified' => $modifiedTime,
        'author' => ['@type' => 'Person', 'name' => $post['author'] ?? config('site.name')],
        'publisher' => ['@type' => 'Organization', 'name' => config('site.name'), 'logo' => ['@type' => 'ImageObject', 'url' => asset('assets/Logo/og-image.png')]],
        'articleSection' => array_values($categories),
        'wordCount' => str_word_count(Seo::blogBodyText($post['body'] ?? [])),
        'inLanguage' => 'en',
    ]);
    $breadcrumbJsonLd = Seo::jsonLd([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => route('blog.index')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $post['title'], 'item' => $canonical],
        ],
    ]);
@endphp

@extends('layouts.app')

@push('head')
  <meta property="article:published_time" content="{{ $publishedTime }}">
  <meta property="article:modified_time" content="{{ $modifiedTime }}">
  <meta property="article:author" content="{{ $post['author'] ?? config('site.name') }}">
  @foreach($categories as $category)
    <meta property="article:section" content="{{ $category }}">
  @endforeach
  <script type="application/ld+json">
  {!! $articleJsonLd !!}
  </script>
  <script type="application/ld+json">
  {!! $breadcrumbJsonLd !!}
  </script>
@endpush

@section('content')
<main id="blog-main" class="blog-page blog-post">

  <header class="blog-post-hero">
    <div class="blog-post-hero-media">
      <img src="{{ Seo::imageUrl($post['image'] ?? null) }}" alt="{{ $post['alt'] ?? '' }}" loading="eager" fetchpriority="high" decoding="async">
    </div>
    <div class="container blog-post-hero-copy">
      <a class="blog-post-back" href="{{ route('blog.index') }}">
        <i data-lucide="arrow-left"></i>
        <span>All articles</span>
      </a>
      <div class="blog-post-chips">
        @foreach($post['categories'] ?? array_filter([$post['category'] ?? null]) as $cat)
          <span class="blog-chip blog-chip--on-dark">{{ $cat }}</span>
        @endforeach
      </div>
      <h1 class="blog-post-title">{{ $post['title'] }}</h1>
      <p class="blog-post-meta">
        <time datetime="{{ $post['date'] }}">{{ $fmtDate($post['date']) }}</time>
        @if(! empty($post['read_time']))
          <span aria-hidden="true">&middot;</span>
          <span>{{ $post['read_time'] }} min read</span>
        @endif
        <span aria-hidden="true">&middot;</span>
        <span>By {{ $post['author'] }}</span>
      </p>
    </div>
  </header>

  <article class="blog-post-article">
    <div class="container blog-post-shell">
      <div class="blog-post-body">
        @if(! empty($post['excerpt']))
          <p class="blog-post-lede">{{ $post['excerpt'] }}</p>
        @endif

        @foreach($post['body'] as $block)
          @switch($block['kind'])
            @case('h2')
              <h2>{{ $block['text'] }}</h2>
              @break

            @case('p')
              <p>{!! $block['html'] ?? e($block['text']) !!}</p>
              @break

            @case('table')
              <div class="blog-post-table-wrap">
                <table class="blog-post-table">
                  <tbody>
                    @foreach($block['rows'] as $row)
                      <tr>
                        @foreach($row as $cell)
                          <td>{!! $cell !!}</td>
                        @endforeach
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
              @break

            @case('list')
              <ul class="blog-post-list">
                @foreach($block['items'] as $item)
                  <li>{{ $item }}</li>
                @endforeach
              </ul>
              @break

            @case('quote')
              <blockquote class="blog-post-quote">
                <p>&ldquo;{{ $block['text'] }}&rdquo;</p>
                @if(! empty($block['attribution']))
                  <cite>&mdash; {{ $block['attribution'] }}</cite>
                @endif
              </blockquote>
              @break

            @case('image')
              <figure class="blog-post-figure">
                <img src="{{ Seo::imageUrl($block['url'] ?? null) }}" alt="{{ $block['alt'] ?? '' }}" loading="lazy" decoding="async">
                @if(! empty($block['caption']))
                  <figcaption>{{ $block['caption'] }}</figcaption>
                @endif
              </figure>
              @break
          @endswitch
        @endforeach

        @if($post['show_cta'] ?? true)
        <aside class="blog-post-inline-cta">
          <div>
            <span class="eyebrow">Toward the right shortlist</span>
            <h3>Want this read against your own profile?</h3>
            <p>Thirty minutes with a senior counsellor, no obligation and no sales pitch.</p>
          </div>
          <a class="btn btn-primary" href="{{ route('contact') }}">
            <span>Book a free strategy call</span>
            <i data-lucide="arrow-up-right"></i>
          </a>
        </aside>
        @endif
      </div>
    </div>
  </article>

  @if(! empty($related))
    <section class="blog-related">
      <div class="container">
        <div class="blog-related-head">
          <span class="eyebrow">Keep reading</span>
          <h2>Related from the journal</h2>
        </div>
        <div class="blog-related-grid">
          @foreach($related as $rel)
            <a class="blog-related-card reveal" href="{{ \App\Support\BlogContent::url($rel) }}">
              <div class="blog-related-media">
                <img src="{{ Seo::imageUrl($rel['image'] ?? null) }}" alt="{{ $rel['alt'] ?? '' }}" loading="lazy" decoding="async">
              </div>
              <div class="blog-related-body">
                <span class="blog-chip">{{ $rel['category'] }}</span>
                <h3>{{ $rel['title'] }}</h3>
                <p class="blog-list-meta">
                  <time datetime="{{ $rel['date'] }}">{{ $fmtDate($rel['date']) }}</time>
                  @if(! empty($rel['read_time']))
                    <span aria-hidden="true">&middot;</span>
                    <span>{{ $rel['read_time'] }} min</span>
                  @endif
                </p>
              </div>
            </a>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  <section class="blog-newsletter">
    <div class="container blog-newsletter-shell">
      <div class="blog-newsletter-copy">
        <span class="eyebrow">Stay in the loop</span>
        <h2>One thoughtful brief, every Friday.</h2>
        <p>Five-minute reads on what changed this cycle for the corridors we cover. No noise, no resends, easy to unsubscribe.</p>
      </div>
      <form class="blog-newsletter-form" action="{{ route('newsletter.subscribe') }}" method="POST" data-newsletter-form aria-label="Newsletter signup">
        @csrf
        <input type="hidden" name="source" value="Blog newsletter">
        <label class="visually-hidden" for="blog-newsletter-email">Email address</label>
        <input id="blog-newsletter-email" type="email" name="email" required placeholder="you@domain.com">
        <button class="btn btn-primary" type="submit">
          <span>Subscribe</span>
          <i data-lucide="arrow-up-right"></i>
        </button>
      </form>
    </div>
  </section>

</main>
@endsection
