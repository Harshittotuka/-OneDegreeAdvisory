{{--
  Stripe-style header — the site's only nav. All behaviour lives in
  public/stripe-nav.js; the three dropdown panels share one overlay that morphs
  (size + position + cross-fade) between triggers, Stripe-style.

  Two content variants are rendered and toggled by the floating "Nav" switcher
  (html.nav-updated, wired in public/ui-switchers.js):
    • Current  — the existing layout (Services menu, full Courses list).
    • Updated  — Services removed; Courses led by MBBS (LLB dropped, other
                 tracks → profiler); Destinations drop Tajikistan and gain a
                 "Show more" tile. See the .nav-variant / .dest-more rules and
                 the html.nav-updated overrides in styles.css.
--}}
@php
    $mbbsCountries = [
        ['name' => 'Russia', 'flag' => 'ru'],
        ['name' => 'Georgia', 'flag' => 'ge'],
        ['name' => 'Kazakhstan', 'flag' => 'kz'],
        ['name' => 'Kyrgyzstan', 'flag' => 'kg'],
        ['name' => 'Tajikistan', 'flag' => 'tj'],
        ['name' => 'Uzbekistan', 'flag' => 'uz'],
    ];
    usort($mbbsCountries, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

    $destinations = app(\App\Support\StudyLocationContent::class)->destinations();
    $mbbsCountryRoutes = ['georgia','russia','kazakhstan','kyrgyzstan','uzbekistan'];

    // External student profiler — the "Updated" nav links the non-MBBS course
    // tracks and the destination "Show more" cards here.
    $profilingUrl = 'https://gatewayhub.onedegreeadvisory.com/student-profiler/?channel_id=NDg4OQ==';
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

    <div class="stripe-nav-panel" data-stripe-panel>
      <div class="stripe-nav-menu" data-stripe-menu>
        <a @class(['stripe-nav-link', 'is-active' => ($activeNav ?? null) === 'home']) href="{{ route('home') }}">Home</a>
        <a @class(['stripe-nav-link', 'is-active' => ($activeNav ?? null) === 'about']) href="{{ route('about') }}">About</a>

        <button @class(['stripe-nav-link', 'stripe-nav-trigger', 'has-active' => ($activeNav ?? null) === 'destinations'])
                type="button" data-stripe-trigger="destinations" aria-haspopup="true" aria-expanded="false" aria-controls="stripe-sec-destinations">
          <span>Destinations</span>
          <i class="nav-trigger-chevron" data-lucide="chevron-down"></i>
        </button>

        <button @class(['stripe-nav-link', 'stripe-nav-trigger', 'has-active' => in_array($activeNav ?? null, ['courses', 'mbbs'], true)])
                type="button" data-stripe-trigger="courses" aria-haspopup="true" aria-expanded="false" aria-controls="stripe-sec-courses">
          <span>Courses</span>
          <i class="nav-trigger-chevron" data-lucide="chevron-down"></i>
        </button>

        <button @class(['stripe-nav-link', 'stripe-nav-trigger', 'has-active' => ($activeNav ?? null) === 'services'])
                type="button" data-stripe-trigger="services" aria-haspopup="true" aria-expanded="false" aria-controls="stripe-sec-services">
          <span>Services</span>
          <i class="nav-trigger-chevron" data-lucide="chevron-down"></i>
        </button>
      </div>

      {{-- One shared overlay that morphs between the three panels --}}
      <div class="stripe-flyout" data-stripe-flyout aria-hidden="true">
        <span class="stripe-flyout-arrow" data-stripe-arrow aria-hidden="true"></span>
        <div class="stripe-flyout-bg" data-stripe-bg>
          <div class="stripe-flyout-viewport" data-stripe-viewport>

            {{-- ============ Destinations ============ --}}
            <div class="stripe-flyout-section" id="stripe-sec-destinations" data-stripe-section="destinations" role="region" aria-label="Study destinations">
              <div class="nav-dropdown-shell">
                <div class="nav-dropdown-main">
                  <div class="nav-dropdown-topline">
                    <span class="nav-dropdown-badge">Country guides</span>
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
                    {{-- "Updated" nav only: a non-clickable "and more" cell that sits like another country. --}}
                    <span class="dest-card dest-more" aria-hidden="true">
                      <span class="dest-flag dest-more-icon"><i data-lucide="ellipsis"></i></span>
                      <span class="dest-meta"><strong>and more</strong></span>
                    </span>
                  </div>

                  <div class="nav-dropdown-topline nav-dropdown-topline--mbbs">
                    <span class="nav-dropdown-badge">MBBS</span>
                  </div>

                  <div class="nav-dropdown-grid">
                    @foreach ($mbbsCountries as $country)
                      @php($mbbsSlug = strtolower($country['name']))
                      <a @class(['dest-card', 'dest-card--hide-updated' => $mbbsSlug === 'tajikistan']) href="{{ in_array($mbbsSlug, $mbbsCountryRoutes, true) ? route('mbbs.country', $mbbsSlug) : route('mbbs.student').'#corridor' }}">
                        <span class="dest-flag" aria-hidden="true">
                          <img src="https://flagcdn.com/w40/{{ $country['flag'] }}.png" alt="">
                        </span>
                        <span class="dest-meta"><strong>{{ $country['name'] }}</strong></span>
                      </a>
                    @endforeach
                    {{-- "Updated" nav only: a non-clickable "and more" cell that sits like another country. --}}
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

            {{-- ============ Courses ============ --}}
            <div class="stripe-flyout-section nav-dropdown--courses" id="stripe-sec-courses" data-stripe-section="courses" role="region" aria-label="Courses">
              <div class="nav-dropdown-shell">
                <div class="nav-dropdown-main course-menu">
                  {{-- CURRENT nav --}}
                  <div class="nav-variant nav-variant--current">
                    <div class="nav-dropdown-topline course-menu-topline">
                      <span class="nav-dropdown-badge">Course pathways</span>
                      <span class="course-menu-count">6 tracks</span>
                    </div>

                    <div class="course-menu-grid">
                      <span class="course-menu-card course-menu-card--disabled">
                        <span class="course-icon course-icon--pg" aria-hidden="true"><i data-lucide="award"></i></span>
                        <span class="course-menu-copy"><strong>Postgraduate</strong><small>Master's and PG</small></span>
                      </span>
                      <span class="course-menu-card course-menu-card--disabled">
                        <span class="course-icon course-icon--ug" aria-hidden="true"><i data-lucide="graduation-cap"></i></span>
                        <span class="course-menu-copy"><strong>Undergraduate</strong><small>Bachelor's degrees</small></span>
                      </span>
                      <a class="course-menu-card course-menu-card--mbbs" href="{{ route('mbbs.student') }}">
                        <span class="course-icon course-icon--mbbs" aria-hidden="true"><i data-lucide="stethoscope"></i></span>
                        <span class="course-menu-copy"><strong>MBBS</strong><small>Medicine abroad</small></span>
                        <span class="course-menu-arrow" aria-hidden="true"><i data-lucide="chevron-right"></i></span>
                      </a>
                      <span class="course-menu-card course-menu-card--disabled">
                        <span class="course-icon course-icon--mba" aria-hidden="true"><i data-lucide="trending-up"></i></span>
                        <span class="course-menu-copy"><strong>MBA</strong><small>Business abroad</small></span>
                      </span>
                      <span class="course-menu-card course-menu-card--disabled">
                        <span class="course-icon course-icon--llb" aria-hidden="true"><i data-lucide="scale"></i></span>
                        <span class="course-menu-copy"><strong>LLB</strong><small>Law abroad</small></span>
                      </span>
                      <span class="course-menu-card course-menu-card--disabled">
                        <span class="course-icon course-icon--doctoral" aria-hidden="true"><i data-lucide="microscope"></i></span>
                        <span class="course-menu-copy"><strong>Doctoral</strong><small>PhD and research</small></span>
                      </span>
                    </div>
                  </div>

                  {{-- UPDATED nav: MBBS first + simplified, LLB removed, other tracks link to the profiler. --}}
                  <div class="nav-variant nav-variant--updated">
                    <div class="nav-dropdown-topline course-menu-topline">
                      <span class="nav-dropdown-badge">Course pathways</span>
                      <span class="course-menu-count">5 tracks</span>
                    </div>

                    <div class="course-menu-grid">
                      <a class="course-menu-card" href="{{ route('mbbs.student') }}">
                        <span class="course-icon course-icon--mbbs" aria-hidden="true"><i data-lucide="stethoscope"></i></span>
                        <span class="course-menu-copy"><strong>MBBS</strong><small>Medicine abroad</small></span>
                      </a>
                      <a class="course-menu-card" href="{{ $profilingUrl }}" target="_blank" rel="noopener">
                        <span class="course-icon course-icon--pg" aria-hidden="true"><i data-lucide="award"></i></span>
                        <span class="course-menu-copy"><strong>Postgraduate</strong><small>Master's and PG</small></span>
                      </a>
                      <a class="course-menu-card" href="{{ $profilingUrl }}" target="_blank" rel="noopener">
                        <span class="course-icon course-icon--ug" aria-hidden="true"><i data-lucide="graduation-cap"></i></span>
                        <span class="course-menu-copy"><strong>Undergraduate</strong><small>Bachelor's degrees</small></span>
                      </a>
                      <a class="course-menu-card" href="{{ $profilingUrl }}" target="_blank" rel="noopener">
                        <span class="course-icon course-icon--mba" aria-hidden="true"><i data-lucide="trending-up"></i></span>
                        <span class="course-menu-copy"><strong>MBA</strong><small>Business abroad</small></span>
                      </a>
                      <a class="course-menu-card" href="{{ $profilingUrl }}" target="_blank" rel="noopener">
                        <span class="course-icon course-icon--doctoral" aria-hidden="true"><i data-lucide="microscope"></i></span>
                        <span class="course-menu-copy"><strong>Doctoral</strong><small>PhD and research</small></span>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {{-- ============ Services ============ --}}
            <div class="stripe-flyout-section nav-dropdown--courses nav-dropdown--services" id="stripe-sec-services" data-stripe-section="services" role="region" aria-label="Services">
              <div class="nav-dropdown-shell">
                <div class="nav-dropdown-main course-menu">
                  <div class="nav-dropdown-topline course-menu-topline">
                    <span class="nav-dropdown-badge">Our services</span>
                  </div>

                  <div class="course-menu-grid">
                    <button class="course-menu-card" type="button" data-students-hub-trigger data-feature="students-hub" aria-haspopup="dialog" aria-controls="students-hub-coming-soon">
                      <span class="course-icon course-icon--pg" aria-hidden="true"><i data-lucide="sparkles"></i></span>
                      <span class="course-menu-copy"><strong>Students Hub</strong><small>AI-powered student tools</small></span>
                    </button>
                    <button class="course-menu-card" type="button" data-students-hub-trigger data-feature="career-mentoring" aria-haspopup="dialog" aria-controls="students-hub-coming-soon">
                      <span class="course-icon course-icon--ug" aria-hidden="true"><i data-lucide="users"></i></span>
                      <span class="course-menu-copy"><strong>Career Mentoring</strong><small>1:1 expert guidance</small></span>
                    </button>
                    <button class="course-menu-card" type="button" data-students-hub-trigger data-feature="student-development" aria-haspopup="dialog" aria-controls="students-hub-coming-soon">
                      <span class="course-icon course-icon--doctoral" aria-hidden="true"><i data-lucide="trending-up"></i></span>
                      <span class="course-menu-copy"><strong>Student Development Programme</strong><small>Build your profile</small></span>
                    </button>

                    <div class="submenu-wrap" data-submenu>
                      <button class="course-menu-card has-submenu" type="button" data-submenu-trigger aria-haspopup="true" aria-expanded="false">
                        <span class="course-icon course-icon--llb" aria-hidden="true"><i data-lucide="globe"></i></span>
                        <span class="course-menu-copy"><strong>Study Abroad</strong><small>Test prep, services &amp; more</small></span>
                        <span class="course-menu-arrow" aria-hidden="true"><i data-lucide="chevron-right"></i></span>
                      </button>

                      <div class="course-submenu" data-submenu-panel role="menu" aria-label="Study Abroad">
                        <a class="course-menu-card" href="{{ route('services.test-prep') }}" role="menuitem">
                          <span class="course-icon course-icon--pg" aria-hidden="true"><i data-lucide="clipboard-check"></i></span>
                          <span class="course-menu-copy"><strong>Test Preparation</strong><small>ACT, SAT, IELTS &amp; more</small></span>
                          <span class="course-menu-arrow" aria-hidden="true"><i data-lucide="chevron-right"></i></span>
                        </a>
                        <a class="course-menu-card" href="{{ route('services.student-services') }}" role="menuitem">
                          <span class="course-icon course-icon--ug" aria-hidden="true"><i data-lucide="hand-helping"></i></span>
                          <span class="course-menu-copy"><strong>Student Services</strong><small>End-to-end support</small></span>
                          <span class="course-menu-arrow" aria-hidden="true"><i data-lucide="chevron-right"></i></span>
                        </a>
                        <a class="course-menu-card" href="{{ route('services.admissions-counselling') }}" role="menuitem">
                          <span class="course-icon course-icon--mba" aria-hidden="true"><i data-lucide="compass"></i></span>
                          <span class="course-menu-copy"><strong>Admission Counselling</strong><small>Plan your application</small></span>
                          <span class="course-menu-arrow" aria-hidden="true"><i data-lucide="chevron-right"></i></span>
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

      <a class="nav-cta stripe-nav-cta" href="{{ route('contact') }}">
        <i data-lucide="message-circle"></i>
        <span>Contact</span>
      </a>
    </div>
  </nav>
</header>
