@php
    // Top blue notice bar — its display variant (original / minimal / compact) is
    // managed in the CMS (/admin/notice-bar) and applied server-side here so
    // there is no flash and no dependence on the old floating switcher.
    $topbarVariant = app(\App\Support\NoticeBarStore::class)->get()['variant'] ?? 'original';

    // When a CMS live-editor renders a real page (e.g. the home-hero editor),
    // $cmsEdit injects the editor chrome and locks every non-edited section.
    $cmsEdit = $cmsEdit ?? false;
@endphp
<!doctype html>
<html lang="en" data-color-theme="cream"@if($topbarVariant !== 'original') class="topbar-{{ $topbarVariant }}"@endif>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
      $metaTitle = \App\Support\Seo::title($pageTitle ?? null, config('site.name'), 90);
      $metaDescription = \App\Support\Seo::description($pageDescription ?? null, config('site.description'), 170);
      $canonicalUrl = \App\Support\Seo::pageUrl($canonical ?? url()->current());
      $ogImageUrl = \App\Support\Seo::imageUrl($ogImage ?? null);
      // Declare dimensions only for the default share image (assets/Logo/og-image.png,
      // a known 1200x630). Per-page overrides (blog/country photos) vary in size, so
      // we omit width/height for them rather than advertise wrong dimensions.
      $ogImageIsDefault = trim((string) ($ogImage ?? '')) === '';
      $robotsValue = $robots ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
      $googleSiteVerification = trim((string) config('services.google.site_verification'));
      $googleTagId = app()->environment('production') && ! $cmsEdit ? trim((string) config('services.google.tag_id')) : '';
      $googleTagManagerId = app()->environment('production') && ! $cmsEdit ? trim((string) config('services.google.tag_manager_id')) : '';
    @endphp
    <meta name="theme-color" content="#1a0088">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="{{ $robotsValue }}">
    <meta name="description" content="{{ $metaDescription }}">
    @if($googleSiteVerification !== '')
      <meta name="google-site-verification" content="{{ $googleSiteVerification }}">
    @endif
    <title>{{ $metaTitle }}</title>
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="alternate" hreflang="en" href="{{ $canonicalUrl }}">
    <link rel="alternate" hreflang="x-default" href="{{ $canonicalUrl }}">

    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/Logo/mark.svg') }}">
    <link rel="icon" type="image/png" href="{{ asset('assets/Logo/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/Logo/favicon.png') }}">

    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:locale" content="en_IN">
    <meta property="og:site_name" content="{{ config('site.name') }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $ogImageUrl }}">
    <meta property="og:image:alt" content="{{ $ogImageAlt ?? config('site.name') }}">
    @if($ogImageIsDefault)
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $ogImageUrl }}">
    <meta name="twitter:image:alt" content="{{ $ogImageAlt ?? config('site.name') }}">

    @stack('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://images.unsplash.com">
    <link rel="preconnect" href="https://unpkg.com">
    @if($googleTagManagerId !== '' || $googleTagId !== '')
      <link rel="preconnect" href="https://www.googletagmanager.com">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;450;500;600;700&family=Jost:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
      (function () {
        var root = document.documentElement;
        root.classList.add("js");
      })();
    </script>
    @if($googleTagManagerId !== '')
      <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer',@json($googleTagManagerId));
      </script>
    @elseif($googleTagId !== '')
      <script async src="https://www.googletagmanager.com/gtag/js?id={{ rawurlencode($googleTagId) }}"></script>
      <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($googleTagId), {'send_page_view': true});
      </script>
    @endif
    <link rel="stylesheet" href="{{ asset('styles.css') }}">
    <link rel="stylesheet" href="{{ asset('stripe-nav.css') }}">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
    <script src="{{ asset('script.js') }}" defer></script>
    <script src="{{ asset('stripe-nav.js') }}" defer></script>
  </head>
  <body class="{{ trim(($bodyClass ?? '').($cmsEdit ? ' cms-editing' : '')) }}">
    @if($googleTagManagerId !== '')
      <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ rawurlencode($googleTagManagerId) }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif
    <a class="skip-link" href="#{{ $mainId ?? 'main' }}">Skip to content</a>

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

    {{-- JSON is built in PHP so the literal context key is not read as a Blade directive. --}}
    @php
      $addr = config('site.contact.address_parts', []);
      $postalAddress = array_filter([
        '@type' => 'PostalAddress',
        'streetAddress' => $addr['street'] ?? null,
        'addressLocality' => $addr['locality'] ?? null,
        'addressRegion' => $addr['region'] ?? null,
        'postalCode' => $addr['postal_code'] ?? null,
        'addressCountry' => $addr['country'] ?? null,
      ]);

      // Mirror config('site.services') into an OfferCatalog of Service offers.
      $serviceOffers = array_values(array_map(fn (array $s) => [
        '@type' => 'Offer',
        'itemOffered' => array_filter([
          '@type' => 'Service',
          'name' => $s['name'] ?? '',
          'description' => $s['description'] ?? null,
          'provider' => ['@id' => url('/#organization')],
        ]),
      ], config('site.services', [])));

      $orgNode = array_filter([
        '@type' => 'EducationalOrganization',
        '@id' => url('/#organization'),
        'name' => config('site.name'),
        'alternateName' => 'ODA',
        'url' => url('/'),
        'logo' => ['@type' => 'ImageObject', 'url' => asset('assets/Logo/og-image.png')],
        'image' => asset('assets/Logo/og-image.png'),
        'description' => config('site.description'),
        'email' => config('site.contact.email'),
        'telephone' => config('site.contact.phone'),
        'address' => $postalAddress ?: config('site.contact.address'),
        'areaServed' => 'Worldwide',
        'knowsAbout' => array_values(config('site.expertise', [])),
        'contactPoint' => [array_filter([
          '@type' => 'ContactPoint',
          'telephone' => config('site.contact.phone'),
          'email' => config('site.contact.email'),
          'contactType' => 'customer support',
          'areaServed' => 'Worldwide',
          'availableLanguage' => ['English', 'Hindi'],
        ])],
        'hasOfferCatalog' => $serviceOffers ? [
          '@type' => 'OfferCatalog',
          'name' => 'Education advisory services',
          'itemListElement' => $serviceOffers,
        ] : null,
        'sameAs' => array_values(array_filter(array_column(config('site.socials', []), 'href'))),
      ]);

      $orgJsonLd = \App\Support\Seo::jsonLd(['@context' => 'https://schema.org', '@graph' => [
        $orgNode,
        ['@type' => 'WebSite', '@id' => url('/#website'), 'url' => url('/'), 'name' => config('site.name'), 'description' => config('site.description'), 'publisher' => ['@id' => url('/#organization')], 'inLanguage' => 'en'],
      ]]);
    @endphp
    <script type="application/ld+json">
      {!! $orgJsonLd !!}
    </script>

    @if($cmsEdit)
      @include('admin.home-hero._editor_chrome')
    @endif
  </body>
</html>
