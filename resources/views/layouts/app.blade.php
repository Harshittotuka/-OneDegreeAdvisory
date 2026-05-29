<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1a0088">
    <meta name="description" content="{{ $pageDescription ?? config('site.description') }}">
    <title>{{ $pageTitle ?? config('site.name') }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/Logo/mark.svg') }}">
    <link rel="icon" type="image/png" href="{{ asset('assets/Logo/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/Logo/favicon.png') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('site.name') }}">
    <meta property="og:title" content="{{ $pageTitle ?? config('site.name') }}">
    <meta property="og:description" content="{{ $pageDescription ?? config('site.description') }}">
    <meta property="og:image" content="{{ asset('assets/Logo/og-image.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ asset('assets/Logo/og-image.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://images.unsplash.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
      (function () {
        document.documentElement.classList.add("js");
        try {
          var theme = sessionStorage.getItem("oda:color-theme");
          var allowed = ["sapphire", "signature"];
          if (allowed.indexOf(theme) !== -1) {
            document.documentElement.dataset.colorTheme = theme;
          }
        } catch (error) {}
      })();
    </script>
    <link rel="stylesheet" href="{{ asset('styles.css') }}">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
    <script src="{{ asset('script.js') }}" defer></script>
  </head>
  <body class="{{ $bodyClass ?? '' }}">
    <a class="skip-link" href="#{{ $mainId ?? 'main' }}">Skip to content</a>

    @include('partials.header', ['activeNav' => $activeNav ?? null])

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

    <div class="theme-switcher" aria-label="Color theme switcher" data-theme-switcher>
      <button class="theme-swatch theme-swatch-current" type="button" data-theme-option="current" aria-label="Use original color theme" aria-pressed="true">
        <span aria-hidden="true">1</span>
        <span class="visually-hidden">Original</span>
      </button>
      <button class="theme-swatch theme-swatch-sapphire" type="button" data-theme-option="sapphire" aria-label="Use Sapphire color theme" aria-pressed="false">
        <span aria-hidden="true">2</span>
        <span class="visually-hidden">Sapphire</span>
      </button>
      <button class="theme-swatch theme-swatch-signature" type="button" data-theme-option="signature" aria-label="Use Signature color theme" aria-pressed="false">
        <span aria-hidden="true">3</span>
        <span class="visually-hidden">Signature</span>
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
