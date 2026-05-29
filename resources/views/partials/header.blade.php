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
@endphp

<header class="site-header" data-header>
  <div class="notice">
    @include('partials.socials', ['variant' => 'notice'])
    <p>{{ config('site.notice') }}</p>
    <a href="{{ route('contact') }}">Book a profile review</a>
  </div>
  <nav class="nav-shell" aria-label="Primary navigation">
    <a class="brand" href="{{ route('home') }}#top" aria-label="{{ config('site.name') }} home">
      <img class="brand-mark" src="{{ asset('assets/Logo/mark.svg') }}" alt="" aria-hidden="true" width="104" height="36">
      <span>
        <strong>{{ config('site.name') }}</strong>
        <small>{{ config('site.tagline') }}</small>
      </span>
    </a>

    <button class="nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" data-nav-toggle>
      <i data-lucide="menu"></i>
    </button>

    <div class="nav-menu" data-nav-menu>
      <a @class(['is-active' => $activeNav === 'home']) href="{{ route('home') }}">Home</a>
      <a @class(['is-active' => $activeNav === 'about']) href="{{ route('about') }}">About</a>
      <a @class(['is-active' => $activeNav === 'blog']) href="{{ route('blog.index') }}">Blog</a>
      <button class="students-hub-trigger" type="button" data-students-hub-trigger aria-haspopup="dialog" aria-controls="students-hub-coming-soon">
        <i data-lucide="sparkles" aria-hidden="true"></i>
        <span>Students Hub</span>
      </button>

      <div @class(['nav-item', 'has-dropdown', 'has-active' => ($activeNav ?? null) === 'destinations']) data-dropdown>
        <button class="nav-trigger" type="button" aria-haspopup="true" aria-expanded="false" data-dropdown-trigger>
          <span>Destinations</span>
          <i class="nav-trigger-chevron" data-lucide="chevron-down"></i>
        </button>

        <div class="nav-dropdown" data-dropdown-panel role="menu" aria-label="Study destinations">
          <div class="nav-dropdown-shell">
            <div class="nav-dropdown-main">
              <div class="nav-dropdown-topline">
                <span class="nav-dropdown-badge">Country guides</span>
              </div>

              <div class="nav-dropdown-grid">
                @foreach ($destinations as $destination)
                  <a class="dest-card" href="{{ route('country.show', $destination['slug']) }}" role="menuitem">
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
                @php($mbbsCountryRoutes = ['georgia','russia','kazakhstan','kyrgyzstan','uzbekistan'])
                @foreach ($mbbsCountries as $country)
                  @php($mbbsSlug = strtolower($country['name']))
                  <a class="dest-card" href="{{ in_array($mbbsSlug, $mbbsCountryRoutes, true) ? route('mbbs.country', $mbbsSlug) : route('mbbs.student').'#corridor' }}" role="menuitem">
                    <span class="dest-flag" aria-hidden="true">
                      <img src="https://flagcdn.com/w40/{{ $country['flag'] }}.png" alt="">
                    </span>
                    <span class="dest-meta"><strong>{{ $country['name'] }}</strong></span>
                  </a>
                @endforeach
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
      </div>

      <div @class(['nav-item', 'has-dropdown', 'has-active' => in_array($activeNav ?? null, ['courses', 'mbbs'], true)]) data-dropdown>
        <button class="nav-trigger" type="button" aria-haspopup="true" aria-expanded="false" data-dropdown-trigger>
          <span>Courses</span>
          <i class="nav-trigger-chevron" data-lucide="chevron-down"></i>
        </button>

        <div class="nav-dropdown nav-dropdown--courses" data-dropdown-panel role="menu" aria-label="Courses">
          <div class="nav-dropdown-shell">
            <div class="nav-dropdown-main course-menu">
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
                <a class="course-menu-card course-menu-card--mbbs" href="{{ route('mbbs.student') }}" role="menuitem">
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
          </div>
        </div>
      </div>

      <div @class(['nav-item', 'has-dropdown', 'has-active' => ($activeNav ?? null) === 'services']) data-dropdown>
        <button class="nav-trigger" type="button" aria-haspopup="true" aria-expanded="false" data-dropdown-trigger>
          <span>Services</span>
          <i class="nav-trigger-chevron" data-lucide="chevron-down"></i>
        </button>

        <div class="nav-dropdown nav-dropdown--courses nav-dropdown--services" data-dropdown-panel role="menu" aria-label="Services">
          <div class="nav-dropdown-shell">
            <div class="nav-dropdown-main course-menu">
              <div class="nav-dropdown-topline course-menu-topline">
                <span class="nav-dropdown-badge">Our services</span>
                <span class="course-menu-count">3 services</span>
              </div>

              <div class="course-menu-grid">
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
                  <span class="course-menu-copy"><strong>Admissions Counselling</strong><small>Plan your application</small></span>
                  <span class="course-menu-arrow" aria-hidden="true"><i data-lucide="chevron-right"></i></span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <a class="nav-cta" href="{{ route('contact') }}">
      <i data-lucide="message-circle"></i>
      <span>Contact</span>
    </a>
  </nav>
</header>
