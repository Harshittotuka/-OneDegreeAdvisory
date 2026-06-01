<!doctype html>
<html lang="en" data-color-theme="signature">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1a0088">
    <meta name="description" content="{{ $pageDescription ?? config('site.description') }}">
    <title>{{ $pageTitle ?? config('site.name') }}</title>

    @php($canonicalUrl = $canonical ?? url()->current())
    @php($ogImageUrl = isset($ogImage) ? (\Illuminate\Support\Str::startsWith($ogImage, ['http://', 'https://']) ? $ogImage : asset(ltrim($ogImage, '/'))) : asset('assets/Logo/og-image.png'))
    <link rel="canonical" href="{{ $canonicalUrl }}">

    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/Logo/mark.svg') }}">
    <link rel="icon" type="image/png" href="{{ asset('assets/Logo/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/Logo/favicon.png') }}">

    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:site_name" content="{{ config('site.name') }}">
    <meta property="og:title" content="{{ $pageTitle ?? config('site.name') }}">
    <meta property="og:description" content="{{ $pageDescription ?? config('site.description') }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $ogImageUrl }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle ?? config('site.name') }}">
    <meta name="twitter:description" content="{{ $pageDescription ?? config('site.description') }}">
    <meta name="twitter:image" content="{{ $ogImageUrl }}">

    @stack('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://images.unsplash.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
      (function () {
        document.documentElement.classList.add("js");
        try {
          var t = sessionStorage.getItem("oda:color-theme");
          if (t === "signature" || t === "signature-white" || t === "fedex" || t === "cream") {
            document.documentElement.dataset.colorTheme = t;
          }
        } catch (e) {}
        try {
          if (localStorage.getItem("oda:nav-style") === "stripe") {
            document.documentElement.classList.add("nav-stripe");
          }
        } catch (e) {}
      })();
    </script>
    <link rel="stylesheet" href="{{ asset('styles.css') }}">
    <link rel="stylesheet" href="{{ asset('stripe-nav.css') }}">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
    <script src="{{ asset('script.js') }}" defer></script>
    <script src="{{ asset('stripe-nav.js') }}" defer></script>
  </head>
  <body class="{{ $bodyClass ?? '' }}">
    <a class="skip-link" href="#{{ $mainId ?? 'main' }}">Skip to content</a>

    @include('partials.header', ['activeNav' => $activeNav ?? null])
    @include('partials.header-stripe', ['activeNav' => $activeNav ?? null])

    <div class="students-hub-overlay" id="students-hub-coming-soon" data-students-hub-overlay role="dialog" aria-modal="true" aria-labelledby="students-hub-title" aria-describedby="students-hub-desc" aria-hidden="true" hidden>
      <div class="students-hub-backdrop" data-students-hub-close></div>
      <div class="students-hub-dialog" role="document">
        <button class="students-hub-close" type="button" data-students-hub-close aria-label="Close Students Hub preview">
          <i data-lucide="x" aria-hidden="true"></i>
        </button>

        <div class="students-hub-ai-mark" aria-hidden="true">
          <span class="students-hub-chip"><i data-lucide="bot"></i></span>
        </div>

        <div class="students-hub-copy">
          <span class="students-hub-kicker">
            <i data-lucide="sparkles" aria-hidden="true"></i>
            AI-powered student tools
          </span>
          <h2 id="students-hub-title">Students Hub is coming soon</h2>
          <p id="students-hub-desc">A smarter space for profile insights, best-fit university shortlists, application planning, and progress tracking.</p>

          <div class="students-hub-features" aria-label="Students Hub preview features">
            <span><i data-lucide="brain" aria-hidden="true"></i> Profile intelligence</span>
            <span><i data-lucide="target" aria-hidden="true"></i> Best-fit shortlists</span>
            <span><i data-lucide="list-checks" aria-hidden="true"></i> Application copilot</span>
          </div>
        </div>
      </div>
    </div>

    @yield('content')

    @include('partials.footer')

    @unless(($activeNav ?? null) === 'contact')
      <a class="contact-fab" href="{{ route('contact') }}" aria-label="Contact us" data-contact-fab>
        <span class="contact-fab__icon" aria-hidden="true">
          <i data-lucide="message-circle"></i>
        </span>
        <span class="contact-fab__label" data-contact-fab-label>Talk to an advisor</span>
      </a>
    @endunless

    <button class="nav-style-toggle" type="button" data-nav-style-toggle aria-pressed="false" aria-label="Switch navigation style">
      <span class="nav-style-toggle__icon" aria-hidden="true"><i data-lucide="layout-dashboard"></i></span>
      <span class="nav-style-toggle__text">
        <small>Nav style</small>
        <strong data-nav-style-label>Classic</strong>
      </span>
      <span class="nav-style-toggle__switch" aria-hidden="true"></span>
    </button>

    <div class="theme-switcher" aria-label="Color theme switcher" data-theme-switcher>
      <button class="theme-swatch theme-swatch-signature" type="button" data-theme-option="signature" aria-label="Use Original color theme" aria-pressed="true">
        <span class="visually-hidden">Original</span>
      </button>
      <button class="theme-swatch theme-swatch-signature-white" type="button" data-theme-option="signature-white" aria-label="Use Original theme with white background" aria-pressed="false">
        <span class="visually-hidden">Original + white background</span>
      </button>
      <button class="theme-swatch theme-swatch-fedex" type="button" data-theme-option="fedex" aria-label="Use FedEx color theme" aria-pressed="false">
        <span class="visually-hidden">FedEx</span>
      </button>
      <button class="theme-swatch theme-swatch-cream" type="button" data-theme-option="cream" aria-label="Use Logo Colours theme" aria-pressed="false">
        <span class="visually-hidden">Logo Colours</span>
      </button>
    </div>

    <script type="application/ld+json">
      {!! json_encode([
          '@context' => 'https://schema.org',
          '@type' => 'EducationalOrganization',
          'name' => config('site.name'),
          'url' => url('/'),
          'logo' => asset('assets/Logo/og-image.png'),
          'description' => config('site.description'),
          'email' => config('site.contact.email'),
          'telephone' => config('site.contact.phone'),
          'address' => config('site.contact.address'),
          'areaServed' => 'Worldwide',
      ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
  </body>
</html>
