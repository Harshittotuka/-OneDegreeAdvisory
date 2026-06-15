@php
    use App\Support\Seo;
    use App\Support\BlogContent;

    $pageTitle       = $page > 1 ? 'Blog Page '.$page.' | '.config('site.name') : 'Blog | '.config('site.name');
    $pageDescription = $page > 1
      ? 'Page '.$page.' of the One Degree Advisory journal: practical writing on admissions, scholarships, applications, testing, and university strategy.'
      : 'Practical, evidence-led writing on global admissions, scholarships, applications, testing, and university strategy.';
    $activeNav       = 'blog';
    $mainId          = 'blog-main';
    $bodyClass       = 'page-blog';
    $canonical       = $page > 1 ? route('blog.index', ['page' => $page]) : route('blog.index');

    $fmtDate = fn (string $iso): string => date('F j, Y', strtotime($iso));
    $pageUrl = fn (int $p): string => $p <= 1 ? route('blog.index') : route('blog.index', ['page' => $p]);

    $featured = $posts[0] ?? null;
    $gridPosts = array_values(array_slice($posts, 1));
    $topPosts = array_slice($gridPosts, 0, 5);
    $masonryPosts = array_slice($gridPosts, 5);
    $itemListJsonLd = Seo::jsonLd([
        '@context' => 'https://schema.org',
        '@type' => 'Blog',
        'name' => 'The One Degree Journal',
        'description' => $pageDescription,
        'url' => $canonical,
        'publisher' => ['@type' => 'Organization', 'name' => config('site.name'), 'url' => route('home')],
        'blogPost' => array_values(array_map(fn (array $post): array => [
            '@type' => 'BlogPosting',
            'headline' => $post['title'] ?? '',
            'url' => BlogContent::url($post),
            'datePublished' => $post['date'] ?? null,
            'image' => Seo::imageUrl($post['image'] ?? null),
            'description' => Seo::description($post['meta_description'] ?? null, $post['excerpt'] ?? null),
        ], $posts)),
    ]);
@endphp

@extends('layouts.app')

@push('head')
  @if($page > 1)
    <link rel="prev" href="{{ $pageUrl($page - 1) }}">
  @endif
  @if($page < $totalPages)
    <link rel="next" href="{{ $pageUrl($page + 1) }}">
  @endif
  <script type="application/ld+json">
  {!! $itemListJsonLd !!}
  </script>
@endpush

@section('content')
<main id="blog-main" class="blog-page">

  <header class="blog-page-head">
    <div class="container">
      <span class="eyebrow">The One Degree Journal</span>
      <h1 class="blog-page-title">Notes from the advisory desk.</h1>
      <p class="blog-page-kicker">
        Field notes from counsellors who run the actual application files:
        what changed this cycle, what to ignore, and where the time should go.
      </p>
    </div>
  </header>

  <section class="blog-grid-section">
    <div class="container blog-grid-container">
      @if($featured)
        <article class="blog-featured-post reveal">
          <a class="blog-featured-media" href="{{ BlogContent::url($featured) }}" aria-label="Read: {{ $featured['title'] }}">
            <img src="{{ Seo::imageUrl($featured['image'] ?? null) }}" alt="{{ $featured['alt'] ?? '' }}" loading="eager" fetchpriority="high" decoding="async">
          </a>
          <div class="blog-featured-copy">
            <div class="blog-card-chips">
              @foreach($featured['categories'] ?? array_filter([$featured['category'] ?? null]) as $cat)
                <span class="blog-chip">{{ $cat }}</span>
              @endforeach
            </div>
            <h2>
              <a href="{{ BlogContent::url($featured) }}">{{ $featured['title'] }}</a>
            </h2>
            <p class="blog-list-meta">
              <time datetime="{{ $featured['date'] }}">{{ $fmtDate($featured['date']) }}</time>
              @if(! empty($featured['read_time']))
                <span aria-hidden="true">&middot;</span>
                <span>{{ $featured['read_time'] }} min read</span>
              @endif
            </p>
            @if(! empty($featured['excerpt']))
              <p>{{ $featured['excerpt'] }}</p>
            @endif
          </div>
        </article>
      @endif

      <div class="blog-editorial-grid">
        <div class="blog-editorial-grid-top">
          <div class="blog-grid-extras">
            <aside class="blog-grid-signup reveal">
              <h2>Stay Current On<br> College Admissions</h2>
              <p>Sign up for our newsletter to stay updated with our blogs and helpful resources for college admissions.</p>
              <form action="{{ route('newsletter.subscribe') }}" method="POST" data-newsletter-form aria-label="Blog newsletter signup">
                @csrf
                <input type="hidden" name="source" value="Blog sidebar">
                <label class="visually-hidden" for="blog-grid-email">Email address</label>
                <input id="blog-grid-email" type="email" name="email" placeholder="Your Email" required>
                <button type="submit" aria-label="Sign up">
                  <i data-lucide="arrow-right"></i>
                </button>
              </form>
            </aside>

            <aside class="blog-grid-social reveal">
              <img class="blog-grid-social-mark" src="{{ asset('assets/Logo/mark.svg') }}" alt="" aria-hidden="true" width="104" height="36">
              <p>Application strategy changes quickly. The families who do best keep their timeline, testing, and college list moving together.</p>
              <strong>One Degree</strong>
            </aside>
          </div>

          @foreach($topPosts as $post)
            <article class="blog-grid-card reveal">
              <a class="blog-grid-card-media" href="{{ BlogContent::url($post) }}" aria-label="Read: {{ $post['title'] }}">
                <img src="{{ Seo::imageUrl($post['image'] ?? null) }}" alt="{{ $post['alt'] ?? '' }}" loading="lazy" decoding="async">
              </a>
              <div class="blog-grid-card-body">
                <div class="blog-card-chips">
                  @foreach($post['categories'] ?? array_filter([$post['category'] ?? null]) as $cat)
                    <span class="blog-chip">{{ $cat }}</span>
                  @endforeach
                </div>
                <h2>
                  <a href="{{ BlogContent::url($post) }}">{{ $post['title'] }}</a>
                </h2>
                @if(! empty($post['excerpt']))
                  <p>{{ $post['excerpt'] }}</p>
                @endif
                <p class="blog-list-meta">
                  <time datetime="{{ $post['date'] }}">{{ $fmtDate($post['date']) }}</time>
                </p>
              </div>
            </article>
          @endforeach
        </div>

        @if(! empty($masonryPosts))
          <div class="blog-editorial-grid-page">
            @foreach($masonryPosts as $post)
              <article class="blog-grid-card reveal">
                <a class="blog-grid-card-media" href="{{ BlogContent::url($post) }}" aria-label="Read: {{ $post['title'] }}">
                  <img src="{{ Seo::imageUrl($post['image'] ?? null) }}" alt="{{ $post['alt'] ?? '' }}" loading="lazy" decoding="async">
                </a>
                <div class="blog-grid-card-body">
                  <div class="blog-card-chips">
                  @foreach($post['categories'] ?? array_filter([$post['category'] ?? null]) as $cat)
                    <span class="blog-chip">{{ $cat }}</span>
                  @endforeach
                </div>
                  <h2>
                    <a href="{{ BlogContent::url($post) }}">{{ $post['title'] }}</a>
                  </h2>
                  @if(! empty($post['excerpt']))
                    <p>{{ $post['excerpt'] }}</p>
                  @endif
                  <p class="blog-list-meta">
                    <time datetime="{{ $post['date'] }}">{{ $fmtDate($post['date']) }}</time>
                  </p>
                </div>
              </article>
            @endforeach
          </div>
        @endif
      </div>

      @if($totalPages > 1)
        <nav class="blog-pagination" aria-label="Blog pagination">
          @if($page > 1)
            <a class="blog-pagination-arrow" href="{{ $pageUrl($page - 1) }}" rel="prev" aria-label="Previous page">
              <i data-lucide="chevron-left"></i>
            </a>
          @endif

          @for($p = 1; $p <= $totalPages; $p++)
            @if($p === $page)
              <span class="blog-pagination-page is-current" aria-current="page">{{ $p }}</span>
            @else
              <a class="blog-pagination-page" href="{{ $pageUrl($p) }}">{{ $p }}</a>
            @endif
          @endfor

          @if($page < $totalPages)
            <a class="blog-pagination-arrow" href="{{ $pageUrl($page + 1) }}" rel="next" aria-label="Next page">
              <i data-lucide="chevron-right"></i>
            </a>
          @endif
        </nav>
      @endif
    </div>
  </section>

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
        <input id="blog-newsletter-email" type="email" name="email" required placeholder="you@example.com">
        <button class="btn btn-primary" type="submit">
          <span>Subscribe</span>
          <i data-lucide="arrow-up-right"></i>
        </button>
      </form>
    </div>
  </section>

</main>
@endsection
