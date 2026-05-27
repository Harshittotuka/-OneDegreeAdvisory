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

    $mbbsNames = array_map(fn ($c) => strtolower($c['name']), $mbbsCountries);

    $destinations = config('site.destinations');
    $destinations = is_array($destinations) ? $destinations : [];
    $destinations = array_values(array_filter(
        $destinations,
        fn ($d) => ! in_array(strtolower($d['name'] ?? ''), $mbbsNames, true),
    ));
    usort($destinations, fn ($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));
@endphp

<header class="site-header" data-header>
  <div class="notice">
    <p>{{ config('site.notice') }}</p>
    <a href="{{ route('contact') }}">Book a profile review</a>
  </div>
  <nav class="nav-shell" aria-label="Primary navigation">
    <a class="brand" href="{{ route('home') }}#top" aria-label="{{ config('site.name') }} home">
      <span class="brand-mark" aria-hidden="true">1&deg;</span>
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

      <div class="nav-item has-dropdown" data-dropdown>
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
                      @else
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
                  <a class="dest-card" href="{{ route('mbbs.student') }}#corridor" role="menuitem">
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

      <div @class(['nav-item', 'has-dropdown', 'has-active' => in_array($activeNav ?? null, ['mbbs', 'mbbs-v2'], true)]) data-dropdown>
        <button class="nav-trigger" type="button" aria-haspopup="true" aria-expanded="false" data-dropdown-trigger>
          <span>Temp Nav</span>
          <i class="nav-trigger-chevron" data-lucide="chevron-down"></i>
        </button>

        <div class="nav-dropdown nav-dropdown--compact" data-dropdown-panel role="menu" aria-label="Advisory tracks">
          <div class="nav-dropdown-shell">
            <div class="nav-dropdown-main">
              <div class="nav-dropdown-topline">
                <span class="nav-dropdown-eyebrow">Advisory tracks</span>
                <span class="nav-dropdown-badge">Specialist desks</span>
              </div>

              <div class="nav-dropdown-grid nav-dropdown-grid--tracks">
                <a @class(['dest-card', 'track-card', 'is-active' => $activeNav === 'mbbs']) href="{{ route('mbbs.student') }}" role="menuitem">
                  <span class="track-icon" aria-hidden="true"><i data-lucide="stethoscope"></i></span>
                  <span class="dest-meta">
                    <strong>MBBS</strong>
                    <small>Medical study-abroad track</small>
                  </span>
                </a>
                <a @class(['dest-card', 'track-card', 'is-active' => $activeNav === 'mbbs-v2']) href="{{ route('mbbs.student.v2') }}" role="menuitem">
                  <span class="track-icon" aria-hidden="true"><i data-lucide="layout-dashboard"></i></span>
                  <span class="dest-meta">
                    <strong>MBBS v2</strong>
                    <small>Comparison layout</small>
                  </span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    @if (! in_array($activeNav ?? null, ['home', 'contact', 'about']))
    <div class="currency-switch" data-currency-switch>
      <button class="currency-trigger" type="button" data-currency-trigger aria-haspopup="true" aria-expanded="false" aria-label="Change currency">
        <i data-lucide="circle-dollar-sign"></i>
        <span data-currency-label>USD</span>
        <i class="currency-chevron" data-lucide="chevron-down"></i>
      </button>
      <div class="currency-menu" data-currency-menu role="menu">
        <button type="button" role="menuitem" data-currency-option="USD"><span class="cur-sym">$</span><span class="cur-name">US Dollar</span><span class="cur-code">USD</span></button>
        <button type="button" role="menuitem" data-currency-option="INR"><span class="cur-sym">&#8377;</span><span class="cur-name">Indian Rupee</span><span class="cur-code">INR</span></button>
        <button type="button" role="menuitem" data-currency-option="GBP"><span class="cur-sym">&pound;</span><span class="cur-name">British Pound</span><span class="cur-code">GBP</span></button>
        <button type="button" role="menuitem" data-currency-option="EUR"><span class="cur-sym">&euro;</span><span class="cur-name">Euro</span><span class="cur-code">EUR</span></button>
        <button type="button" role="menuitem" data-currency-option="CAD"><span class="cur-sym">CA$</span><span class="cur-name">Canadian Dollar</span><span class="cur-code">CAD</span></button>
        <button type="button" role="menuitem" data-currency-option="AUD"><span class="cur-sym">A$</span><span class="cur-name">Australian Dollar</span><span class="cur-code">AUD</span></button>
        <button type="button" role="menuitem" data-currency-option="AED"><span class="cur-sym">AED</span><span class="cur-name">UAE Dirham</span><span class="cur-code">AED</span></button>
        <button type="button" role="menuitem" data-currency-option="NZD"><span class="cur-sym">NZ$</span><span class="cur-name">New Zealand Dollar</span><span class="cur-code">NZD</span></button>
      </div>
    </div>
    @endif

    <a class="nav-cta" href="{{ route('contact') }}">
      <i data-lucide="message-circle"></i>
      <span>Contact</span>
    </a>
  </nav>
</header>
