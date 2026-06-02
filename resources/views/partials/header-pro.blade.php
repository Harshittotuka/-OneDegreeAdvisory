{{--
  "Pro" header — the 3rd nav style (opt-in).

  It REUSES the Stripe header's markup + CSS classes (.stripe-*) verbatim, so it
  inherits the exact same morphing-overlay styling and animation. The only
  differences are:
    • JS hooks are namespaced data-pro-* (driven by public/pro-nav.js) so it
      never collides with the Stripe nav's data-stripe-* wiring.
    • A `nav-pro-skin` marker class on the root scopes the professional
      re-skin (Inter typography + a fresh, cohesive icon set) in pro-nav.css.
  Visibility is controlled by `html.nav-pro` (see pro-nav.css). Classic and
  Stripe headers are untouched.
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
@endphp

<header class="stripe-site-header nav-pro-skin" data-pro-header>
  @include('partials.notice-bar')

  <nav class="stripe-nav" aria-label="Primary navigation" data-pro-nav>
    <a class="brand" href="{{ route('home') }}#top" aria-label="{{ config('site.name') }} home">
      <img class="brand-mark" src="{{ asset('assets/Logo/mark.svg') }}" alt="" aria-hidden="true" width="104" height="36">
      <span class="brand-wordmark">
        <strong>One Degree</strong>
        <small>Advisory</small>
      </span>
    </a>

    <button class="nav-toggle stripe-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" data-pro-mobile-toggle>
      <i data-lucide="menu"></i>
    </button>

    <div class="stripe-nav-panel" data-pro-panel>
      <div class="stripe-nav-menu" data-pro-menu>
        <a @class(['stripe-nav-link', 'is-active' => ($activeNav ?? null) === 'home']) href="{{ route('home') }}">Home</a>
        <a @class(['stripe-nav-link', 'is-active' => ($activeNav ?? null) === 'about']) href="{{ route('about') }}">About</a>

        <button @class(['stripe-nav-link', 'stripe-nav-trigger', 'has-active' => ($activeNav ?? null) === 'destinations'])
                type="button" data-pro-trigger="destinations" aria-haspopup="true" aria-expanded="false" aria-controls="pro-sec-destinations">
          <span>Destinations</span>
          <i class="nav-trigger-chevron" data-lucide="chevron-down"></i>
        </button>

        <button @class(['stripe-nav-link', 'stripe-nav-trigger', 'has-active' => in_array($activeNav ?? null, ['courses', 'mbbs'], true)])
                type="button" data-pro-trigger="courses" aria-haspopup="true" aria-expanded="false" aria-controls="pro-sec-courses">
          <span>Courses</span>
          <i class="nav-trigger-chevron" data-lucide="chevron-down"></i>
        </button>

        <button @class(['stripe-nav-link', 'stripe-nav-trigger', 'has-active' => ($activeNav ?? null) === 'services'])
                type="button" data-pro-trigger="services" aria-haspopup="true" aria-expanded="false" aria-controls="pro-sec-services">
          <span>Services</span>
          <i class="nav-trigger-chevron" data-lucide="chevron-down"></i>
        </button>
      </div>

      {{-- One shared overlay that morphs between the three panels --}}
      <div class="stripe-flyout" data-pro-flyout aria-hidden="true">
        <span class="stripe-flyout-arrow" data-pro-arrow aria-hidden="true"></span>
        <div class="stripe-flyout-bg" data-pro-bg>
          <div class="stripe-flyout-viewport" data-pro-viewport>

            {{-- ============ Destinations ============ --}}
            <div class="stripe-flyout-section" id="pro-sec-destinations" data-pro-section="destinations" role="region" aria-label="Study destinations">
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
                  </div>

                  <div class="nav-dropdown-topline nav-dropdown-topline--mbbs">
                    <span class="nav-dropdown-badge">MBBS</span>
                  </div>

                  <div class="nav-dropdown-grid">
                    @foreach ($mbbsCountries as $country)
                      @php($mbbsSlug = strtolower($country['name']))
                      <a class="dest-card" href="{{ in_array($mbbsSlug, $mbbsCountryRoutes, true) ? route('mbbs.country', $mbbsSlug) : route('mbbs.student').'#corridor' }}">
                        <span class="dest-flag" aria-hidden="true">
                          <img src="https://flagcdn.com/w40/{{ $country['flag'] }}.png" alt="">
                        </span>
                        <span class="dest-meta"><strong>{{ $country['name'] }}</strong></span>
                      </a>
                    @endforeach
                  </div>
                </div>

                <aside class="nav-dropdown-feature">
                  <span class="feature-icon" aria-hidden="true"><i data-lucide="compass"></i></span>
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
            <div class="stripe-flyout-section nav-dropdown--courses" id="pro-sec-courses" data-pro-section="courses" role="region" aria-label="Courses">
              <div class="nav-dropdown-shell">
                <div class="nav-dropdown-main course-menu">
                  <div class="nav-dropdown-topline course-menu-topline">
                    <span class="nav-dropdown-badge">Course pathways</span>
                    <span class="course-menu-count">6 tracks</span>
                  </div>

                  <div class="course-menu-grid">
                    <span class="course-menu-card course-menu-card--disabled">
                      <span class="course-icon course-icon--pg" aria-hidden="true"><i data-lucide="book-marked"></i></span>
                      <span class="course-menu-copy"><strong>Postgraduate</strong><small>Master's and PG</small></span>
                    </span>
                    <span class="course-menu-card course-menu-card--disabled">
                      <span class="course-icon course-icon--ug" aria-hidden="true"><i data-lucide="book-open"></i></span>
                      <span class="course-menu-copy"><strong>Undergraduate</strong><small>Bachelor's degrees</small></span>
                    </span>
                    <a class="course-menu-card course-menu-card--mbbs" href="{{ route('mbbs.student') }}">
                      <span class="course-icon course-icon--mbbs" aria-hidden="true"><i data-lucide="heart-pulse"></i></span>
                      <span class="course-menu-copy"><strong>MBBS</strong><small>Medicine abroad</small></span>
                      <span class="course-menu-arrow" aria-hidden="true"><i data-lucide="chevron-right"></i></span>
                    </a>
                    <span class="course-menu-card course-menu-card--disabled">
                      <span class="course-icon course-icon--mba" aria-hidden="true"><i data-lucide="briefcase"></i></span>
                      <span class="course-menu-copy"><strong>MBA</strong><small>Business abroad</small></span>
                    </span>
                    <span class="course-menu-card course-menu-card--disabled">
                      <span class="course-icon course-icon--llb" aria-hidden="true"><i data-lucide="gavel"></i></span>
                      <span class="course-menu-copy"><strong>LLB</strong><small>Law abroad</small></span>
                    </span>
                    <span class="course-menu-card course-menu-card--disabled">
                      <span class="course-icon course-icon--doctoral" aria-hidden="true"><i data-lucide="flask-conical"></i></span>
                      <span class="course-menu-copy"><strong>Doctoral</strong><small>PhD and research</small></span>
                    </span>
                  </div>
                </div>
              </div>
            </div>

            {{-- ============ Services ============ --}}
            <div class="stripe-flyout-section nav-dropdown--courses nav-dropdown--services" id="pro-sec-services" data-pro-section="services" role="region" aria-label="Services">
              <div class="nav-dropdown-shell">
                <div class="nav-dropdown-main course-menu">
                  <div class="nav-dropdown-topline course-menu-topline">
                    <span class="nav-dropdown-badge">Our services</span>
                  </div>

                  <div class="course-menu-grid">
                    <button class="course-menu-card" type="button" data-students-hub-trigger data-feature="students-hub" aria-haspopup="dialog" aria-controls="students-hub-coming-soon">
                      <span class="course-icon course-icon--pg" aria-hidden="true"><i data-lucide="layout-grid"></i></span>
                      <span class="course-menu-copy"><strong>Students Hub</strong><small>AI-powered student tools</small></span>
                    </button>
                    <button class="course-menu-card" type="button" data-students-hub-trigger data-feature="career-mentoring" aria-haspopup="dialog" aria-controls="students-hub-coming-soon">
                      <span class="course-icon course-icon--ug" aria-hidden="true"><i data-lucide="handshake"></i></span>
                      <span class="course-menu-copy"><strong>Career Mentoring</strong><small>1:1 expert guidance</small></span>
                    </button>
                    <button class="course-menu-card" type="button" data-students-hub-trigger data-feature="student-development" aria-haspopup="dialog" aria-controls="students-hub-coming-soon">
                      <span class="course-icon course-icon--doctoral" aria-hidden="true"><i data-lucide="rocket"></i></span>
                      <span class="course-menu-copy"><strong>Student Development Programme</strong><small>Build your profile</small></span>
                    </button>

                    <div class="submenu-wrap" data-submenu>
                      <button class="course-menu-card has-submenu" type="button" data-submenu-trigger aria-haspopup="true" aria-expanded="false">
                        <span class="course-icon course-icon--llb" aria-hidden="true"><i data-lucide="plane"></i></span>
                        <span class="course-menu-copy"><strong>Study Abroad</strong><small>Test prep, services &amp; more</small></span>
                        <span class="course-menu-arrow" aria-hidden="true"><i data-lucide="chevron-right"></i></span>
                      </button>

                      <div class="course-submenu" data-submenu-panel role="menu" aria-label="Study Abroad">
                        <a class="course-menu-card" href="{{ route('services.test-prep') }}" role="menuitem">
                          <span class="course-icon course-icon--pg" aria-hidden="true"><i data-lucide="pencil-ruler"></i></span>
                          <span class="course-menu-copy"><strong>Test Preparation</strong><small>ACT, SAT, IELTS &amp; more</small></span>
                          <span class="course-menu-arrow" aria-hidden="true"><i data-lucide="chevron-right"></i></span>
                        </a>
                        <a class="course-menu-card" href="{{ route('services.student-services') }}" role="menuitem">
                          <span class="course-icon course-icon--ug" aria-hidden="true"><i data-lucide="life-buoy"></i></span>
                          <span class="course-menu-copy"><strong>Student Services</strong><small>End-to-end support</small></span>
                          <span class="course-menu-arrow" aria-hidden="true"><i data-lucide="chevron-right"></i></span>
                        </a>
                        <a class="course-menu-card" href="{{ route('services.admissions-counselling') }}" role="menuitem">
                          <span class="course-icon course-icon--mba" aria-hidden="true"><i data-lucide="route"></i></span>
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
