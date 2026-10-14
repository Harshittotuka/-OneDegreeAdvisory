@php
    // Laravel renders this view for every 404: an unknown URL (the fallback
    // route finds no page-builder page), a retired blog slug, a hidden country.
    // It keeps the site's header, footer and contact button, so a visitor who
    // followed a dead link still has every way back in.
    $pageTitle = 'Page Not Found | '.config('site.name');
    $pageDescription = 'The page you were looking for is not on this site. Find study-abroad guidance, country guides, visa help and test preparation from One Degree Advisory.';
    $activeNav = null;
    $mainId = 'main';
    $bodyClass = 'page-not-found';
    // A 404 status is already enough to keep it out of the index; say so
    // plainly too, but let crawlers follow the links on to the real pages.
    $robots = 'noindex, follow';
    // The route that 404'd (a blog post, a country) would otherwise lend this
    // page its own trail.
    $breadcrumbs = [];

    $requestedPath = '/'.ltrim(request()->path(), '/');

    // Best guesses at what the broken address meant, and everything the
    // in-page finder can search (App\Support\NotFoundSuggestions).
    $finder = app(\App\Support\NotFoundSuggestions::class);
    $suggestions = $finder->for($requestedPath);
    $searchIndex = $finder->searchIndex();

    // A lead who lands here from an ad or a shared link is one tap from an
    // advisor; the WhatsApp message says where they were trying to go.
    $phone = config('site.contact.phone');
    $email = config('site.contact.email');
    $whatsappUrl = 'https://wa.me/'.config('site.contact.phone_e164').'?text='
        .rawurlencode('Hi One Degree, I was looking for '.url($requestedPath).' on your website.');

    $routes = [
        ['route' => 'study-abroad', 'icon' => 'globe', 'title' => 'Study Abroad', 'text' => 'Country fit, applications, scholarships and visas in one plan.'],
        ['route' => 'mbbs.student', 'icon' => 'stethoscope', 'title' => 'MBBS Abroad', 'text' => 'NMC-approved medical universities, compared side by side.'],
        ['route' => 'visa', 'icon' => 'stamp', 'title' => 'Student Visa', 'text' => 'A free eligibility pre-check and expert filing support.'],
        ['route' => 'services.test-prep', 'icon' => 'book-open-check', 'title' => 'Test Preparation', 'text' => 'IELTS, TOEFL, SAT, GRE and GMAT prep on your timeline.'],
        ['route' => 'career-counselling', 'icon' => 'compass', 'title' => 'Career Counselling', 'text' => 'Assessments and guidance for Class 8 to 12 and beyond.'],
        ['route' => 'blog.index', 'icon' => 'newspaper', 'title' => 'Blog', 'text' => 'Field notes from counsellors on what changed this cycle.'],
    ];

    $destinations = app(\App\Support\StudyLocationContent::class)->destinations();
@endphp

@extends('layouts.app')

@push('head')
<style>
  .nf-hero {
    position: relative;
    overflow: hidden;
    padding: clamp(56px, 8vw, 104px) 0 clamp(48px, 6vw, 80px);
    background:
      radial-gradient(ellipse 60% 70% at 92% 8%, rgba(255, 94, 50, 0.16), transparent 70%),
      radial-gradient(ellipse 50% 60% at 4% 100%, rgba(26, 0, 136, 0.06), transparent 70%);
  }
  .nf-hero__inner {
    display: grid;
    grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr);
    align-items: center;
    gap: clamp(32px, 6vw, 88px);
  }
  .nf-copy h1 {
    margin: 18px 0 20px;
    font-family: "Cormorant Garamond", Georgia, serif;
    font-size: clamp(2.5rem, 5vw, 4.2rem);
    font-weight: 700;
    line-height: 1.04;
    letter-spacing: -0.01em;
    color: var(--navy);
    text-wrap: balance;
  }
  .nf-copy h1 em {
    font-style: italic;
    color: var(--coral);
    white-space: nowrap;
  }
  .nf-lead {
    max-width: 34rem;
    margin: 0;
    color: var(--muted);
    font-size: 1.05rem;
    line-height: 1.7;
  }
  .nf-path {
    display: flex;
    align-items: center;
    gap: 10px;
    max-width: 34rem;
    margin: 22px 0 0;
    color: var(--muted);
    font-size: 0.88rem;
  }
  .nf-path code {
    overflow: hidden;
    min-width: 0;
    padding: 5px 10px;
    border: 1px solid var(--theme-border, rgba(26, 0, 136, 0.14));
    border-radius: 8px;
    background: var(--theme-surface-solid, #fff);
    color: var(--ink);
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.82rem;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  /* "Did you mean": the most useful thing on the page when it has an answer,
     so it sits right under the address that missed. */
  .nf-guess {
    max-width: 34rem;
    margin: 24px 0 0;
    padding: 16px 18px;
    border: 1px solid rgba(255, 94, 50, 0.32);
    border-radius: 16px;
    background: linear-gradient(135deg, rgba(255, 94, 50, 0.08), rgba(255, 255, 255, 0.7));
  }
  .nf-guess__label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 10px;
    color: var(--coral);
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
  }
  .nf-guess__label svg {
    width: 15px;
    height: 15px;
  }
  .nf-guess ul {
    display: grid;
    gap: 6px;
    margin: 0;
    padding: 0;
    list-style: none;
  }
  .nf-guess li {
    text-align: left;
  }
  .nf-guess a {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 2px 12px;
    padding: 9px 12px;
    border-radius: 10px;
    background: var(--theme-surface-solid, #fff);
    color: inherit;
    text-decoration: none;
    transition: box-shadow 160ms ease, transform 160ms ease;
  }
  .nf-guess a:hover,
  .nf-guess a:focus-visible {
    box-shadow: 0 8px 22px rgba(255, 94, 50, 0.18);
    transform: translateY(-1px);
  }
  .nf-guess strong {
    overflow: hidden;
    color: var(--navy);
    font-size: 0.98rem;
    font-weight: 800;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .nf-guess small {
    overflow: hidden;
    grid-column: 1;
    color: var(--muted);
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.76rem;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .nf-guess a > svg,
  .nf-guess a > i {
    grid-column: 2;
    grid-row: 1 / span 2;
    width: 18px;
    height: 18px;
    color: var(--coral);
  }

  .nf-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 28px;
  }
  /* Two classes deep: this block loads before styles.css, whose `.btn`
     resets the border and would win a tie. */
  .nf-actions .nf-btn-line {
    border: 1px solid rgba(26, 0, 136, 0.32);
    background: transparent;
    color: var(--navy);
  }
  .nf-actions .nf-btn-line:hover {
    border-color: var(--navy);
    background: rgba(26, 0, 136, 0.05);
  }
  .nf-reach {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px 16px;
    margin: 16px 0 0;
    color: var(--muted);
    font-size: 0.88rem;
  }
  .nf-reach a {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--navy);
    font-weight: 700;
    text-decoration: none;
  }
  .nf-reach a:hover,
  .nf-reach a:focus-visible {
    color: var(--coral);
    text-decoration: underline;
    text-underline-offset: 3px;
  }
  .nf-reach svg {
    width: 15px;
    height: 15px;
  }

  /* The numeral: "404" with the small orange ring the logo puts after "one",
     over a dotted globe crossed by one flight path — "not on the map". */
  .nf-mark {
    position: relative;
    isolation: isolate;
    justify-self: center;
    font-family: "Cormorant Garamond", Georgia, serif;
    font-size: clamp(8rem, 17vw, 14rem);
    font-weight: 700;
    /* Cormorant's default figures are old-style, and its 4s drop below the
       line into the caption. Lining figures sit on it. */
    font-variant-numeric: lining-nums;
    line-height: 0.9;
    letter-spacing: -0.04em;
    color: var(--navy);
    user-select: none;
  }
  .nf-mark::after {
    content: "";
    position: absolute;
    top: 0.06em;
    right: -0.16em;
    width: 0.12em;
    height: 0.12em;
    border: 0.03em solid var(--coral);
    border-radius: 50%;
  }
  .nf-globe {
    position: absolute;
    z-index: -1;
    top: 42%;
    left: 50%;
    width: 1.75em;
    height: 1.75em;
    border-radius: 50%;
    background-image: radial-gradient(circle, rgba(26, 0, 136, 0.13) 0.013em, transparent 0.017em);
    background-size: 0.065em 0.065em;
    -webkit-mask-image: radial-gradient(circle at 42% 38%, #000 40%, transparent 70%);
    mask-image: radial-gradient(circle at 42% 38%, #000 40%, transparent 70%);
    transform: translate(-50%, -50%);
  }
  .nf-orbit {
    position: absolute;
    z-index: -1;
    top: 42%;
    left: 50%;
    width: 2.2em;
    height: 0.8em;
    border: 0.008em dashed rgba(255, 94, 50, 0.55);
    border-radius: 50%;
    transform: translate(-50%, -50%) rotate(-14deg);
  }
  /* The one point on the path: where the page should have been. */
  .nf-orbit::after {
    content: "";
    position: absolute;
    top: 12%;
    right: 6%;
    width: 0.05em;
    height: 0.05em;
    border-radius: 50%;
    background: var(--coral);
    box-shadow: 0 0 0 0.022em rgba(255, 94, 50, 0.22);
    animation: nf-ping 2.4s ease-out infinite;
  }
  @keyframes nf-ping {
    0% { box-shadow: 0 0 0 0 rgba(255, 94, 50, 0.45); }
    70%, 100% { box-shadow: 0 0 0 0.06em rgba(255, 94, 50, 0); }
  }
  .nf-mark__caption {
    display: block;
    margin-top: 0.12em;
    font-family: inherit;
    font-size: max(0.11em, 15px);
    font-style: italic;
    font-weight: 600;
    letter-spacing: 0.01em;
    text-align: center;
    color: var(--muted);
  }

  .nf-routes {
    padding: clamp(48px, 6vw, 80px) 0 clamp(64px, 8vw, 104px);
    border-top: 1px solid var(--theme-border-soft, rgba(40, 33, 22, 0.08));
  }
  .nf-routes__head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px 40px;
    margin-bottom: 30px;
  }
  .nf-routes h2 {
    margin: 0 0 8px;
    font-family: "Cormorant Garamond", Georgia, serif;
    font-size: clamp(1.9rem, 3vw, 2.5rem);
    font-weight: 700;
    line-height: 1.1;
    color: var(--navy);
    text-wrap: balance;
  }
  .nf-routes__lead {
    margin: 0;
    color: var(--muted);
  }

  /* In-page finder over every public page. Hidden until the script below
     wires it up: with no script there is nothing for it to search with. */
  .nf-search {
    position: relative;
    flex: 0 1 380px;
    min-width: min(100%, 300px);
  }
  .nf-search label,
  .nf-search__status {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip-path: inset(50%);
    white-space: nowrap;
  }
  .nf-search__field {
    position: relative;
  }
  .nf-search__field > svg,
  .nf-search__field > i {
    position: absolute;
    top: 50%;
    left: 16px;
    width: 18px;
    height: 18px;
    color: var(--muted);
    transform: translateY(-50%);
    pointer-events: none;
  }
  .nf-search input {
    width: 100%;
    height: 50px;
    padding: 0 18px 0 46px;
    border: 1px solid var(--theme-field-border, rgba(26, 0, 136, 0.18));
    border-radius: 999px;
    background: var(--theme-surface-solid, #fff);
    color: var(--ink);
    font: inherit;
    font-size: 0.95rem;
    transition: border-color 160ms ease, box-shadow 160ms ease;
  }
  .nf-search input:focus {
    border-color: var(--coral);
    outline: none;
    box-shadow: 0 0 0 4px var(--theme-focus-ring, rgba(255, 94, 50, 0.32));
  }
  .nf-search__results {
    position: absolute;
    z-index: 20;
    top: calc(100% + 8px);
    right: 0;
    left: 0;
    overflow: hidden;
    margin: 0;
    padding: 6px;
    border: 1px solid var(--theme-border, rgba(26, 0, 136, 0.14));
    border-radius: 16px;
    background: var(--theme-surface-solid, #fff);
    box-shadow: 0 22px 48px rgba(40, 33, 22, 0.16);
    list-style: none;
  }
  .nf-search__results li {
    text-align: left;
  }
  .nf-search__results a {
    display: block;
    padding: 10px 12px;
    border-radius: 10px;
    color: var(--navy);
    font-size: 0.93rem;
    font-weight: 700;
    text-decoration: none;
  }
  .nf-search__results a small {
    display: block;
    overflow: hidden;
    margin-top: 2px;
    color: var(--muted);
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.74rem;
    font-weight: 400;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .nf-search__results a:hover,
  .nf-search__results a:focus-visible,
  .nf-search__results a.is-active {
    outline: none;
    background: rgba(255, 94, 50, 0.08);
  }
  .nf-search__empty {
    padding: 10px 12px;
    color: var(--muted);
    font-size: 0.9rem;
  }

  .nf-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin: 0;
    padding: 0;
    list-style: none;
  }
  /* The site justifies `main li` for prose lists; a one-line card blurb
     justified across a narrow column just opens gaps between words. */
  .nf-grid li,
  .nf-countries__list li {
    text-align: left;
  }
  .nf-card {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: start;
    gap: 14px;
    height: 100%;
    padding: 20px;
    border: 1px solid var(--theme-border, rgba(26, 0, 136, 0.14));
    border-radius: 16px;
    background: var(--theme-surface-solid, #fff);
    color: inherit;
    text-decoration: none;
    transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
  }
  .nf-card:hover,
  .nf-card:focus-visible {
    border-color: rgba(255, 94, 50, 0.55);
    box-shadow: var(--theme-card-shadow, 0 16px 38px rgba(40, 33, 22, 0.12));
    transform: translateY(-2px);
  }
  .nf-card__icon {
    display: grid;
    place-items: center;
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: rgba(255, 94, 50, 0.12);
    color: var(--coral);
  }
  .nf-card__icon svg {
    width: 20px;
    height: 20px;
  }
  .nf-card strong {
    display: block;
    margin: 1px 0 4px;
    color: var(--navy);
    font-size: 1rem;
    font-weight: 800;
  }
  .nf-card small {
    display: block;
    color: var(--muted);
    font-size: 0.88rem;
    line-height: 1.5;
  }
  .nf-card__arrow {
    margin-top: 2px;
    color: var(--muted);
    transition: color 180ms ease, transform 180ms ease;
  }
  .nf-card__arrow svg {
    width: 18px;
    height: 18px;
  }
  .nf-card:hover .nf-card__arrow {
    color: var(--coral);
    transform: translateX(3px);
  }

  .nf-countries {
    display: flex;
    align-items: baseline;
    gap: 8px 14px;
    margin-top: 36px;
  }
  .nf-countries__list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 0;
    padding: 0;
    list-style: none;
  }
  .nf-countries__label {
    flex: 0 0 auto;
    color: var(--ink);
    font-size: 0.82rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
  }
  .nf-countries a {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 13px 7px 9px;
    border: 1px solid var(--theme-border, rgba(26, 0, 136, 0.14));
    border-radius: 999px;
    background: var(--theme-surface-solid, #fff);
    color: var(--navy);
    font-size: 0.86rem;
    font-weight: 600;
    text-decoration: none;
    transition: border-color 160ms ease, color 160ms ease;
  }
  .nf-countries a:hover,
  .nf-countries a:focus-visible {
    border-color: var(--coral);
    color: var(--coral);
  }
  .nf-countries img {
    width: 20px;
    height: 15px;
    border-radius: 3px;
    object-fit: cover;
  }

  @media (max-width: 920px) {
    .nf-hero__inner {
      grid-template-columns: minmax(0, 1fr);
    }
    .nf-mark {
      grid-row: 1;
      justify-self: start;
      font-size: clamp(6rem, 26vw, 9rem);
    }
    .nf-mark__caption {
      text-align: left;
    }
    /* The numeral sits at the left edge here; centred on it, the globe and
       its path would run off the screen. */
    .nf-globe,
    .nf-orbit {
      left: 60%;
    }
    .nf-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .nf-search {
      flex-basis: 100%;
    }
  }
  @media (max-width: 600px) {
    .nf-grid {
      grid-template-columns: minmax(0, 1fr);
    }
    .nf-countries {
      flex-direction: column;
    }
    .nf-actions .btn {
      flex: 1 1 auto;
    }
  }
  @media (prefers-reduced-motion: reduce) {
    .nf-orbit::after {
      animation: none;
    }
  }
</style>
@endpush

@section('content')
<main id="main" class="nf-page">
  <section class="nf-hero" aria-labelledby="nf-title">
    <div class="container nf-hero__inner">
      <div class="nf-copy">
        <span class="eyebrow">Error 404 &middot; Page not found</span>
        <h1 id="nf-title">This page took a <em>gap year</em>.</h1>
        <p class="nf-lead">
          The link may be out of date, or the address may have a typo.
          @if($suggestions !== [])
            We think you were heading for one of these.
          @else
            The page you wanted isn't here, but everything below is, and an advisor is a message away.
          @endif
        </p>
        <p class="nf-path">
          <span>You asked for</span>
          <code title="{{ $requestedPath }}">{{ $requestedPath }}</code>
        </p>

        @if($suggestions !== [])
          <div class="nf-guess">
            <p class="nf-guess__label" id="nf-guess-label"><i data-lucide="sparkles" aria-hidden="true"></i>Did you mean</p>
            <ul aria-labelledby="nf-guess-label">
              @foreach($suggestions as $suggestion)
                <li>
                  <a href="{{ $suggestion['url'] }}">
                    <strong>{{ $suggestion['title'] }}</strong>
                    <small>{{ $suggestion['path'] }}</small>
                    <i data-lucide="arrow-right" aria-hidden="true"></i>
                  </a>
                </li>
              @endforeach
            </ul>
          </div>
        @endif

        <div class="nf-actions">
          <a class="btn btn-orange" href="{{ route('home') }}">
            <i data-lucide="arrow-left" aria-hidden="true"></i>
            <span>Back to home</span>
          </a>
          <a class="btn nf-btn-line" href="{{ $whatsappUrl }}" target="_blank" rel="noopener">
            <i data-lucide="message-circle" aria-hidden="true"></i>
            <span>Ask us on WhatsApp</span>
          </a>
        </div>
        <p class="nf-reach">
          <span>Prefer to talk?</span>
          <a href="tel:+{{ config('site.contact.phone_e164') }}"><i data-lucide="phone-call" aria-hidden="true"></i>{{ $phone }}</a>
          <a href="mailto:{{ $email }}"><i data-lucide="mail" aria-hidden="true"></i>{{ $email }}</a>
        </p>
      </div>

      <div class="nf-mark" aria-hidden="true">
        <span class="nf-globe"></span>
        <span class="nf-orbit"></span>
        404<span class="nf-mark__caption">not on the map</span>
      </div>
    </div>
  </section>

  <section class="nf-routes" aria-labelledby="nf-routes-title">
    <div class="container">
      <div class="nf-routes__head">
        <div>
          <h2 id="nf-routes-title">Pick up where you meant to go</h2>
          <p class="nf-routes__lead">The pages students open most, or search for the one you wanted.</p>
        </div>

        <form class="nf-search" role="search" data-nf-search hidden>
          <label for="nf-search-input">Search the site's pages</label>
          <div class="nf-search__field">
            <i data-lucide="search" aria-hidden="true"></i>
            <input id="nf-search-input" type="search" autocomplete="off" spellcheck="false"
                   placeholder="Search pages, countries, articles…"
                   aria-describedby="nf-search-status" aria-controls="nf-search-results">
          </div>
          <ul class="nf-search__results" id="nf-search-results" hidden></ul>
          <p class="nf-search__status" id="nf-search-status" aria-live="polite"></p>
        </form>
      </div>

      <ul class="nf-grid">
        @foreach($routes as $item)
          <li>
            <a class="nf-card" href="{{ route($item['route']) }}">
              <span class="nf-card__icon" aria-hidden="true"><i data-lucide="{{ $item['icon'] }}"></i></span>
              <span>
                <strong>{{ $item['title'] }}</strong>
                <small>{{ $item['text'] }}</small>
              </span>
              <span class="nf-card__arrow" aria-hidden="true"><i data-lucide="arrow-right"></i></span>
            </a>
          </li>
        @endforeach
      </ul>

      @if($destinations !== [])
        <div class="nf-countries">
        <span class="nf-countries__label" id="nf-countries-label">Country guides</span>
        <ul class="nf-countries__list" aria-labelledby="nf-countries-label">
          @foreach($destinations as $destination)
            <li>
              <a href="{{ route('country.show', $destination['slug']) }}">
                @if(! ($destination['eu'] ?? false) && ! empty($destination['flag']))
                  {{-- Decorative: the country's name is the link text. --}}
                  <img src="https://flagcdn.com/w40/{{ $destination['flag'] }}.png" alt="" width="20" height="15" loading="lazy" decoding="async">
                @endif
                <span>{{ $destination['name'] }}</span>
              </a>
            </li>
          @endforeach
        </ul>
        </div>
      @endif
    </div>
  </section>
</main>

<script>
  (function () {
    // Tell Analytics which address broke and where the visitor came from, so
    // dead links can be found and fixed. The tag only loads in production.
    var notFound = { page_path: @json($requestedPath), page_referrer: document.referrer || "(direct)" };
    if (typeof window.gtag === "function") {
      window.gtag("event", "page_not_found", notFound);
    } else if (window.dataLayer && window.dataLayer.push) {
      window.dataLayer.push(Object.assign({ event: "page_not_found" }, notFound));
    }

    // In-page finder. Every word typed must appear in a page's title or its
    // address. First the page whose address or title is exactly what was
    // typed ("visa" → /visa), then titles that start with it, then the rest.
    var form = document.querySelector("[data-nf-search]");
    if (!form) return;
    var pages = @json($searchIndex);
    var input = form.querySelector("input");
    var list = form.querySelector(".nf-search__results");
    var status = form.querySelector(".nf-search__status");
    var LIMIT = 6;

    function norm(text) {
      return String(text).toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "").replace(/[^a-z0-9]+/g, " ").trim();
    }
    pages.forEach(function (p) {
      p.title = norm(p.t);
      p.slug = norm(p.u.replace(/^https?:\/\/[^/]+/, ""));
      p.hay = p.title + " " + norm(p.k);
    });

    function search(query) {
      var words = norm(query).split(" ").filter(Boolean);
      if (!words.length) return [];
      var joined = words.join(" ");
      return pages
        .filter(function (p) { return words.every(function (w) { return p.hay.indexOf(w) !== -1; }); })
        .map(function (p) {
          var rank = p.slug === joined || p.title === joined ? 0
            : p.title.indexOf(joined) === 0 ? 1
            : p.title.indexOf(joined) !== -1 ? 2 : 3;
          return { page: p, rank: rank };
        })
        .sort(function (a, b) { return a.rank - b.rank || a.page.t.length - b.page.t.length; })
        .slice(0, LIMIT)
        .map(function (r) { return r.page; });
    }

    function render() {
      var query = input.value;
      var found = search(query);
      list.innerHTML = "";
      if (!norm(query)) {
        list.hidden = true;
        status.textContent = "";
        return;
      }
      if (!found.length) {
        var empty = document.createElement("li");
        empty.className = "nf-search__empty";
        empty.textContent = "Nothing by that name. Try a country, a course or a test.";
        list.appendChild(empty);
      }
      found.forEach(function (p, i) {
        var li = document.createElement("li");
        var a = document.createElement("a");
        a.href = p.u;
        a.textContent = p.t;
        var path = document.createElement("small");
        path.textContent = p.u.replace(/^https?:\/\/[^/]+/, "") || "/";
        a.appendChild(path);
        if (i === 0) a.className = "is-active";
        li.appendChild(a);
        list.appendChild(li);
      });
      list.hidden = false;
      status.textContent = found.length ? found.length + " matching page" + (found.length === 1 ? "" : "s") : "No matching pages";
    }

    input.addEventListener("input", render);
    input.addEventListener("keydown", function (e) {
      var links = list.querySelectorAll("a");
      if (e.key === "ArrowDown" && links.length) {
        e.preventDefault();
        links[0].focus();
      } else if (e.key === "Escape") {
        input.value = "";
        render();
      }
    });
    list.addEventListener("keydown", function (e) {
      var links = Array.prototype.slice.call(list.querySelectorAll("a"));
      var at = links.indexOf(document.activeElement);
      if (e.key === "ArrowDown" && at < links.length - 1) {
        e.preventDefault();
        links[at + 1].focus();
      } else if (e.key === "ArrowUp") {
        e.preventDefault();
        (at > 0 ? links[at - 1] : input).focus();
      } else if (e.key === "Escape") {
        input.focus();
      }
    });
    // Enter goes to the best match rather than reloading the 404.
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var first = list.querySelector("a");
      if (first) window.location.href = first.href;
    });
    // Close the list when focus leaves the finder.
    form.addEventListener("focusout", function () {
      setTimeout(function () {
        if (!form.contains(document.activeElement)) list.hidden = true;
      }, 0);
    });
    input.addEventListener("focus", function () {
      if (norm(input.value)) render();
    });

    form.hidden = false;
  })();
</script>
@endsection
