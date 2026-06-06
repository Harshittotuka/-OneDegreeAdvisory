@php
    $pageTitle       = $post['title'].' | '.config('site.name').' Blog';
    $pageDescription = $post['excerpt'] ?? config('site.name').' blog article.';
    $activeNav       = 'blog';
    $mainId          = 'blog-main';
    $bodyClass       = 'page-blog page-blog-post';

    $fmtDate = fn (string $iso): string => date('F j, Y', strtotime($iso));
@endphp

@extends('layouts.app')

@section('content')
<main id="blog-main" class="blog-page blog-post">

  <header class="blog-post-hero">
    <div class="blog-post-hero-media">
      <img src="{{ $post['image'] }}" alt="{{ $post['alt'] ?? '' }}" loading="eager">
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
        <span aria-hidden="true">&middot;</span>
        <span>{{ $post['read_time'] }} min read</span>
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
                <img src="{{ $block['url'] }}" alt="{{ $block['alt'] ?? '' }}" loading="lazy">
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
            <a class="blog-related-card reveal" href="{{ route('blog.post', $rel['slug']) }}">
              <div class="blog-related-media">
                <img src="{{ $rel['image'] }}" alt="{{ $rel['alt'] ?? '' }}" loading="lazy">
              </div>
              <div class="blog-related-body">
                <span class="blog-chip">{{ $rel['category'] }}</span>
                <h3>{{ $rel['title'] }}</h3>
                <p class="blog-list-meta">
                  <time datetime="{{ $rel['date'] }}">{{ $fmtDate($rel['date']) }}</time>
                  <span aria-hidden="true">&middot;</span>
                  <span>{{ $rel['read_time'] }} min</span>
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
