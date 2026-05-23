<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f3b45">
    <meta name="description" content="{{ $pageDescription ?? config('site.description') }}">
    <title>{{ $pageTitle ?? config('site.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://images.unsplash.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
      (function () {
        document.documentElement.classList.add("js");
        try {
          var theme = sessionStorage.getItem("oda:color-theme");
          if (theme === "fedex" || theme === "custom") {
            document.documentElement.dataset.colorTheme = theme;
          }
        } catch (error) {}
      })();
    </script>
    <link rel="stylesheet" href="{{ asset('styles.css') }}">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
    <script src="{{ asset('script.js') }}" defer></script>
  </head>
  <body>
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

    <script type="application/ld+json">
      {!! json_encode([
          '@context' => 'https://schema.org',
          '@type' => 'EducationalOrganization',
          'name' => config('site.name'),
          'url' => url('/'),
          'description' => config('site.description'),
          'email' => config('site.contact.email'),
          'telephone' => config('site.contact.phone'),
          'address' => config('site.contact.address'),
          'areaServed' => 'Worldwide',
      ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
  </body>
</html>
