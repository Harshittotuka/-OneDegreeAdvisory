{{--
  Stripe-style header — the site's only nav. All behaviour lives in
  public/stripe-nav.js; the dropdown panels share one overlay that morphs
  (size + position + cross-fade) between triggers, Stripe-style.
--}}
@php
    $destinations = app(\App\Support\StudyLocationContent::class)->destinations();
    $mbbsCountries = app(\App\Support\MbbsCountryContent::class)->countries();
    $destLayout = app(\App\Support\DestinationsLayoutStore::class)->get();
@endphp

<header class="stripe-site-header" data-stripe-header>
  @include('partials.notice-bar')

  <nav class="stripe-nav" aria-label="Primary navigation" data-stripe-nav>
    <a class="brand" href="{{ route('home') }}#top" aria-label="{{ config('site.name') }} home">
      <img class="brand-mark" src="{{ asset('assets/Logo/mark.svg') }}" alt="" aria-hidden="true" width="104" height="36">
      <span class="brand-wordmark">
        <strong>One Degree</strong>
        <small>Advisory</small>
      </span>
    </a>

    <button class="nav-toggle stripe-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" data-stripe-mobile-toggle>
      <i data-lucide="menu"></i>
    </button>

    {{-- Dim backdrop behind the mobile off-canvas drawer (desktop: hidden). --}}
    <div class="stripe-nav-scrim" data-stripe-scrim aria-hidden="true"></div>

    <div class="stripe-nav-panel" data-stripe-panel>
      {{-- Drawer header — only renders inside the mobile off-canvas drawer. --}}
      <div class="stripe-drawer-head">
        <a class="brand stripe-drawer-brand" href="{{ route('home') }}#top" aria-label="{{ config('site.name') }} home">
          <img class="brand-mark" src="{{ asset('assets/Logo/mark.svg') }}" alt="" aria-hidden="true" width="104" height="36">
          <span class="brand-wordmark">
            <strong>One Degree</strong>
            <small>Advisory</small>
          </span>
        </a>
        <button class="stripe-drawer-close" type="button" aria-label="Close navigation" data-stripe-mobile-close>
          <i data-lucide="x"></i>
        </button>
      </div>

      {{-- Scrollable body of the drawer (desktop: display:contents — flows into the nav grid). --}}
      <div class="stripe-nav-scroll" data-stripe-scroll>
      <div class="stripe-nav-menu" data-stripe-menu>
        <a @class(['stripe-nav-link', 'is-active' => ($activeNav ?? null) === 'home']) href="{{ route('home') }}">Home</a>
        <a @class(['stripe-nav-link', 'is-active' => ($activeNav ?? null) === 'about']) href="{{ route('about') }}">About</a>

        <button @class(['stripe-nav-link', 'stripe-nav-trigger', 'has-active' => in_array($activeNav ?? null, ['destinations', 'mbbs', 'undergraduate', 'postgraduate', 'mba'], true)])
                type="button" data-stripe-trigger="destinations" aria-haspopup="true" aria-expanded="false" aria-controls="stripe-sec-destinations">
          <span>Destinations</span>
          <i class="nav-trigger-chevron" data-lucide="chevron-down"></i>
        </button>

        <button @class(['stripe-nav-link', 'stripe-nav-trigger', 'has-active' => ($activeNav ?? null) === 'new-tabs'])
                type="button" data-stripe-trigger="new-tabs" aria-haspopup="true" aria-expanded="false" aria-controls="stripe-sec-new-tabs">
          <span>Student Hub</span>
          <i class="nav-trigger-chevron" data-lucide="chevron-down"></i>
        </button>

        <a @class(['stripe-nav-link', 'is-active' => ($activeNav ?? null) === 'career-library']) href="{{ route('career-library.index') }}">Trending Careers</a>

      </div>

      {{-- One shared overlay that morphs between the panels. --}}
      <div class="stripe-flyout" data-stripe-flyout aria-hidden="true">
        <span class="stripe-flyout-arrow" data-stripe-arrow aria-hidden="true"></span>
        <div class="stripe-flyout-bg" data-stripe-bg>
          <div class="stripe-flyout-viewport" data-stripe-viewport>

            {{-- ============ Destinations ============ --}}
            {{-- Grid layout (columns / gap / panel width) is CMS-editable — see
                 /admin/destinations-layout. Values feed the CSS custom properties
                 consumed by .nav-dropdown-grid and the section width. --}}
            <div class="stripe-flyout-section" id="stripe-sec-destinations" data-stripe-section="destinations" role="region" aria-label="Study destinations"
                 style="--dest-cols: {{ $destLayout['columns'] }}; --dest-gap: {{ $destLayout['gap'] }}px; --dest-width: {{ $destLayout['width'] }}px;">
              <div class="nav-dropdown-shell">
                <div class="nav-dropdown-main">
                  <div class="nav-dropdown-topline nav-dropdown-topline--guide-links">
                    <span class="nav-dropdown-badge">Country guides</span>
                    <a @class(['nav-dropdown-badge', 'nav-dropdown-badge-link', 'is-active' => ($activeNav ?? null) === 'undergraduate']) href="{{ route('courses.ug') }}">
                      <span>Undergrad</span>
                      <i data-lucide="arrow-up-right" aria-hidden="true"></i>
                    </a>
                    <a @class(['nav-dropdown-badge', 'nav-dropdown-badge-link', 'is-active' => ($activeNav ?? null) === 'postgraduate']) href="{{ route('courses.pg') }}">
                      <span>Postgrad</span>
                      <i data-lucide="arrow-up-right" aria-hidden="true"></i>
                    </a>
                    <a @class(['nav-dropdown-badge', 'nav-dropdown-badge-link', 'is-active' => ($activeNav ?? null) === 'mba']) href="{{ route('courses.mba') }}">
                      <span>MBA</span>
                      <i data-lucide="arrow-up-right" aria-hidden="true"></i>
                    </a>
                  </div>

                  <div class="nav-dropdown-grid">
                    @foreach ($destinations as $destination)
                      <a class="dest-card" href="{{ route('country.show', $destination['slug']) }}">
                        <span @class(['dest-flag', 'dest-flag-eu' => $destination['eu'] ?? false]) aria-hidden="true">
                          @if ($destination['eu'] ?? false)
                            @include('partials.eu-flag')
                          @elseif (! empty($destination['flag']))
                            <img src="https://flagcdn.com/w40/{{ $destination['flag'] }}.png" alt="">
                          @endif
                        </span>
                        <span class="dest-meta"><strong>{{ $destination['name'] }}</strong></span>
                      </a>
                    @endforeach
                    {{-- A non-clickable "and more" cell that sits like another country. --}}
                    <span class="dest-card dest-more" aria-hidden="true">
                      <span class="dest-flag dest-more-icon"><i data-lucide="ellipsis"></i></span>
                      <span class="dest-meta"><strong>and more</strong></span>
                    </span>
                  </div>

                  <div class="nav-dropdown-topline nav-dropdown-topline--mbbs">
                    <a class="nav-dropdown-badge nav-dropdown-badge-link nav-dropdown-badge--gold" href="{{ route('mbbs.student') }}">
                      <span>MBBS</span>
                      <i data-lucide="arrow-up-right" aria-hidden="true"></i>
                    </a>
                  </div>

                  <div class="nav-dropdown-grid">
                    @foreach ($mbbsCountries as $country)
                      <a class="dest-card" href="{{ route('mbbs.country', $country['slug']) }}">
                        <span class="dest-flag" aria-hidden="true">
                          @if(! empty($country['flag']))
                            <img src="https://flagcdn.com/w40/{{ $country['flag'] }}.png" alt="">
                          @elseif(! empty($country['flag_url']))
                            <img src="{{ $country['flag_url'] }}" alt="">
                          @else
                            <i data-lucide="map-pin"></i>
                          @endif
                        </span>
                        <span class="dest-meta"><strong>{{ $country['name'] }}</strong></span>
                      </a>
                    @endforeach
                    {{-- A non-clickable "and more" cell that sits like another country. --}}
                    <span class="dest-card dest-more" aria-hidden="true">
                      <span class="dest-flag dest-more-icon"><i data-lucide="ellipsis"></i></span>
                      <span class="dest-meta"><strong>and more</strong></span>
                    </span>
                  </div>
                </div>

                <aside class="nav-dropdown-feature">
                  <span class="feature-icon" aria-hidden="true"><i data-lucide="map"></i></span>
                  <span class="nav-dropdown-eyebrow">Start with a shortlist</span>
                  <h3>Find your best-fit country.</h3>
                  <p>Match budget, intake, program goals, and outcomes before you apply.</p>
                  <a class="feature-cta" href="{{ route('contact') }}">
                    <span>Book a country call</span>
                    <span class="feature-cta-icon" aria-hidden="true"><i data-lucide="arrow-up-right"></i></span>
                  </a>
                </aside>
              </div>
            </div>

            {{-- ============ Student Hub ============ --}}
            <div class="stripe-flyout-section nav-dropdown--courses" id="stripe-sec-new-tabs" data-stripe-section="new-tabs" role="region" aria-label="Student Hub">
              <div class="nav-dropdown-shell">
                <div class="nav-dropdown-main course-menu">
                  <div class="nav-dropdown-topline course-menu-topline">
                    <span class="nav-dropdown-badge">New</span>
                    <span class="course-menu-count">4 tools</span>
                  </div>

                  <div class="course-menu-grid course-menu-grid--hub">
                    <a class="course-menu-card" href="{{ route('profiler') }}">
                      <span class="course-icon course-icon--ug" aria-hidden="true"><i data-lucide="user-plus"></i></span>
                      <span class="course-menu-copy"><strong>Build My Profile</strong><small>Map academics, scores &amp; goals</small></span>
                      <span class="course-menu-arrow" aria-hidden="true"><i data-lucide="arrow-right"></i></span>
                    </a>
                    <a class="course-menu-card" href="{{ route('services.test-prep') }}">
                      <span class="course-icon course-icon--mba" aria-hidden="true"><i data-lucide="book-open-check"></i></span>
                      <span class="course-menu-copy"><strong>Test Preparation</strong><small>IELTS, TOEFL, SAT, GRE &amp; GMAT prep</small></span>
                      <span class="course-menu-arrow" aria-hidden="true"><i data-lucide="arrow-right"></i></span>
                    </a>
                    <a class="course-menu-card" href="{{ route('loan-acco.index') }}">
                      <span class="course-icon course-icon--doctoral" aria-hidden="true"><i data-lucide="wallet"></i></span>
                      <span class="course-menu-copy"><strong>Loan &amp; Acco</strong><small>Education loans &amp; verified student housing</small></span>
                      <span class="course-menu-arrow" aria-hidden="true"><i data-lucide="arrow-right"></i></span>
                    </a>
                    <a @class(['course-menu-card', 'is-active' => ($activeNav ?? null) === 'visa']) href="{{ route('visa') }}">
                      <span class="course-icon course-icon--visa" aria-hidden="true"><i data-lucide="stamp"></i></span>
                      <span class="course-menu-copy"><strong>Visa</strong><small>Free eligibility check &amp; expert visa guidance</small></span>
                      <span class="course-menu-arrow" aria-hidden="true"><i data-lucide="arrow-right"></i></span>
                    </a>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
      </div>{{-- /.stripe-nav-scroll --}}

      {{-- Pinned footer band inside the mobile drawer (desktop: display:contents). --}}
      <div class="stripe-drawer-foot">
        <a class="nav-cta stripe-nav-cta" href="{{ route('contact') }}">
          <i data-lucide="message-circle"></i>
          <span>Contact</span>
        </a>
      </div>
    </div>
  </nav>
</header>
