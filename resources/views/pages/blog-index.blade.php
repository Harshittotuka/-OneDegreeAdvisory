@php
    $pageTitle       = 'Blog | '.config('site.name');
    $pageDescription = 'Practical, evidence-led writing on global admissions, scholarships, applications, testing, and university strategy.';
    $activeNav       = 'blog';
    $mainId          = 'blog-main';
    $bodyClass       = 'page-blog';

    $fmtDate = fn (string $iso): string => date('F j, Y', strtotime($iso));

    $featured = $posts[0] ?? null;
    $gridPosts = array_values(array_slice($posts, 1));
    $topPosts = array_slice($gridPosts, 0, 5);
    $masonryPosts = array_slice($gridPosts, 5);
@endphp

@extends('layouts.app')

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
          <a class="blog-featured-media" href="{{ route('blog.post', $featured['slug']) }}" aria-label="Read: {{ $featured['title'] }}">
            <img src="{{ $featured['image'] }}" alt="{{ $featured['alt'] ?? '' }}" loading="lazy">
          </a>
          <div class="blog-featured-copy">
            <span class="blog-chip">{{ $featured['category'] }}</span>
            <h2>
              <a href="{{ route('blog.post', $featured['slug']) }}">{{ $featured['title'] }}</a>
            </h2>
            <p class="blog-list-meta">
              <time datetime="{{ $featured['date'] }}">{{ $fmtDate($featured['date']) }}</time>
              <span aria-hidden="true">&middot;</span>
              <span>{{ $featured['read_time'] }} min read</span>
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
              <form onsubmit="event.preventDefault();" aria-label="Blog newsletter signup">
                <label class="visually-hidden" for="blog-grid-email">Email address</label>
                <input id="blog-grid-email" type="email" placeholder="Your Email" required>
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
              <a class="blog-grid-card-media" href="{{ route('blog.post', $post['slug']) }}" aria-label="Read: {{ $post['title'] }}">
                <img src="{{ $post['image'] }}" alt="{{ $post['alt'] ?? '' }}" loading="lazy">
              </a>
              <div class="blog-grid-card-body">
                <span class="blog-chip">{{ $post['category'] }}</span>
                <h2>
                  <a href="{{ route('blog.post', $post['slug']) }}">{{ $post['title'] }}</a>
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
                <a class="blog-grid-card-media" href="{{ route('blog.post', $post['slug']) }}" aria-label="Read: {{ $post['title'] }}">
                  <img src="{{ $post['image'] }}" alt="{{ $post['alt'] ?? '' }}" loading="lazy">
                </a>
                <div class="blog-grid-card-body">
                  <span class="blog-chip">{{ $post['category'] }}</span>
                  <h2>
                    <a href="{{ route('blog.post', $post['slug']) }}">{{ $post['title'] }}</a>
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
            <a class="blog-pagination-arrow" href="{{ route('blog.index') }}?page={{ $page - 1 }}" rel="prev" aria-label="Previous page">
              <i data-lucide="chevron-left"></i>
            </a>
          @endif

          @for($p = 1; $p <= $totalPages; $p++)
            @if($p === $page)
              <span class="blog-pagination-page is-current" aria-current="page">{{ $p }}</span>
            @else
              <a class="blog-pagination-page" href="{{ route('blog.index') }}?page={{ $p }}">{{ $p }}</a>
            @endif
          @endfor

          @if($page < $totalPages)
            <a class="blog-pagination-arrow" href="{{ route('blog.index') }}?page={{ $page + 1 }}" rel="next" aria-label="Next page">
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
      <form class="blog-newsletter-form" onsubmit="event.preventDefault();" aria-label="Newsletter signup">
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
