@php
    // Top blue notice bar — its display variant (left-socials / no-socials /
    // static-notice) is managed in the CMS (/admin/notice-bar) and applied
    // server-side here so there is no flash and no dependence on a JS switcher.
    $topbarVariant = app(\App\Support\NoticeBarStore::class)->get()['variant'] ?? 'left-socials';

    // When a CMS live-editor renders a real page (e.g. the home-hero editor),
    // $cmsEdit injects the editor chrome and locks every non-edited section.
    $cmsEdit = $cmsEdit ?? false;
@endphp
<!doctype html>
<html lang="en" data-color-theme="cream" class="topbar-{{ $topbarVariant }}">
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
      // Any host that is not the canonical public domain (the nip.io UAT box,
      // the raw IP, a *.litespeed preview) must never be indexed, or it would
      // compete with the live site as duplicate content. The canonical host
      // keeps its normal index directive (or a per-page $robots override).
      $robotsValue = \App\Support\Seo::isCanonicalHost()
        ? ($robots ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1')
        : 'noindex, nofollow';
      $googleSiteVerification = trim((string) config('services.google.site_verification'));
      $googleTagId = app()->environment('production') && ! $cmsEdit ? trim((string) config('services.google.tag_id')) : '';
      $googleTagManagerId = app()->environment('production') && ! $cmsEdit ? trim((string) config('services.google.tag_manager_id')) : '';
    @endphp
    <meta name="theme-color" content="#1a0088">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Read by script.js so the email guidance matches the server rule. --}}
    <meta name="email-help" content="{{ config('site.forms.email_help') }}">
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
    @if($googleTagManagerId !== '' || $googleTagId !== '')
      <link rel="preconnect" href="https://www.googletagmanager.com">
    @endif
    {{-- Inter is deliberately absent: no rule under this layout ever asks for it.
         It used to be requested here at five weights on every page, which only
         inflated this render-blocking stylesheet. The one place that does use
         Inter (the career library) has its own layout and its own font link. --}}
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
      (function () {
        var root = document.documentElement;
        root.classList.add("js");

        // The content entry animation is for a load that arrives on its own. A
        // same-site navigation is already being animated by the cross-document
        // view transition, so mark those and let the stylesheet skip it —
        // otherwise the content cross-fades and then lifts again.
        //
        // This has to be inline in the head: pagereveal fires before the new
        // document's first paint, which a deferred script is not guaranteed to
        // beat. Browsers without cross-document view transitions never fire the
        // event, so the animation simply always runs for them.
        window.addEventListener("pagereveal", function (event) {
          if (event.viewTransition) root.classList.add("is-vt-nav");
        });
      })();
    </script>
    {{-- Speculation rules: the browser prefetches a page once the pointer rests
         on its link, so by the time the click lands the HTML is already in
         cache and the view transition has nothing to wait for. "moderate"
         means hover/pointerdown only — never a blanket crawl of every link.
         Prefetch (not prerender) is deliberate: it fetches the document
         without running its scripts, so nothing fires analytics or side
         effects for a page the visitor never opens.

         Excluded: the CRM, the admin CMS, the mock-interview invite links
         (one-time tokens) and /profiler (a proxy to a partner's server — a
         hover here would hit somebody else's origin). Add data-no-prefetch to
         any individual link that should never be fetched early. --}}
    <script type="speculationrules">
      {
        "prefetch": [{
          "where": {"and": [
            {"href_matches": "/*"},
            {"not": {"href_matches": ["/crm*", "/admin*", "/cms*", "/mock-interview/*", "/profiler*", "/sitemap.xml", "/robots.txt"]}},
            {"not": {"selector_matches": ["[data-no-prefetch]", "[download]", "[rel~=nofollow]"]}}
          ]},
          "eagerness": "moderate"
        }]
      }
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
    @php
        // Cache-bust local CSS/JS by file mtime. These are served statically by
        // LiteSpeed with long cache headers and the URLs carry no version, so an
        // old copy would otherwise stick in browsers/proxies after a deploy
        // (which is how a stale script.js can leave forms wired to nothing).
        // Shared with the views via App\Support\Asset::v().
        $assetVer = fn (string $file) => \App\Support\Asset::v($file);
    @endphp
    <link rel="stylesheet" href="{{ $assetVer('styles.css') }}">
    <link rel="stylesheet" href="{{ $assetVer('stripe-nav.css') }}">
    {{-- Lucide is self-hosted and pinned. It was loaded from unpkg as
         `lucide@latest`, whose redirect carries max-age=60 — so after a minute
         idle every page paid a third-party DNS + TLS + redirect round trip
         (~240ms) before any of the ~700 <i data-lucide> placeholders on the page
         could become icons. Same-origin and immutable, they now arrive with the
         rest of the page instead of popping in after it. --}}
    <script src="{{ $assetVer('assets/vendor/lucide.min.js') }}" defer></script>
    <script src="{{ $assetVer('script.js') }}" defer></script>
    <script src="{{ $assetVer('stripe-nav.js') }}" defer></script>
  </head>
  <body class="{{ trim(($bodyClass ?? '').($cmsEdit ? ' cms-editing' : '')) }}">
    @if($googleTagManagerId !== '')
      <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ rawurlencode($googleTagManagerId) }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif
    <a class="skip-link" href="#{{ $mainId ?? 'main' }}">Skip to content</a>

    @include('partials.header-stripe', ['activeNav' => $activeNav ?? null])

    @include('partials.students-hub-overlay')

    {{-- Server-rendered fallback for the consult/careers/newsletter forms: when
         JS is stale or disabled the form does a normal POST and lands back here
         with this flash, so submitting is never a silent no-op. With JS working,
         the AJAX popup handles feedback and this never renders. --}}
    @if(session()->has('form_status'))
      <div role="status" aria-live="polite"
           style="position:fixed;left:50%;bottom:24px;transform:translateX(-50%);z-index:9999;max-width:min(92vw,520px);display:flex;align-items:center;gap:12px;padding:14px 18px;border-radius:12px;box-shadow:0 12px 32px rgba(0,0,0,.18);font:500 15px/1.4 system-ui,sans-serif;color:#fff;background:{{ session('form_ok') ? '#1f9d57' : '#c0392b' }};">
        <span>{{ session('form_status') }}</span>
        <button type="button" onclick="this.parentElement.remove()" aria-label="Dismiss"
                style="margin-left:auto;border:0;background:transparent;color:inherit;font-size:20px;line-height:1;cursor:pointer;">&times;</button>
      </div>
    @endif

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

      // Geo + map link for the Jaipur office. Present only when configured so
      // we never advertise placeholder coordinates.
      $geo = config('site.contact.geo', []);
      $geoNode = (isset($geo['lat'], $geo['lng']) && $geo['lat'] !== '' && $geo['lng'] !== '')
        ? ['@type' => 'GeoCoordinates', 'latitude' => (string) $geo['lat'], 'longitude' => (string) $geo['lng']]
        : null;

      $orgNode = array_filter([
        // Dual-typed: an EducationalOrganization (what the business does) that is
        // also a LocalBusiness (a physical office in Jaipur). The LocalBusiness
        // facet — address + geo + map — is what lets Google treat this as a
        // local entity in India, clearly distinct from the similarly-named US
        // financial-advisory firm that otherwise dominates the brand query.
        '@type' => ['EducationalOrganization', 'LocalBusiness'],
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
        'geo' => $geoNode,
        'hasMap' => trim((string) config('site.contact.maps_url')) ?: null,
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
        // Profile URLs only — the socials list also carries the tel: dialer
        // entry, and sameAs must not contain it (the number is already on
        // telephone / contactPoint above).
        'sameAs' => array_values(array_filter(
          array_column(config('site.socials', []), 'href'),
          fn ($href) => is_string($href) && str_starts_with($href, 'http')
        )),
      ]);

      $orgJsonLd = \App\Support\Seo::jsonLd(['@context' => 'https://schema.org', '@graph' => [
        $orgNode,
        array_filter([
          '@type' => 'WebSite',
          '@id' => url('/#website'),
          'url' => url('/'),
          'name' => config('site.name'),
          // Reinforces the site as a distinct named entity ("One Degree
          // Advisory" / "ODA") for the brand query, separate from the US firm.
          'alternateName' => 'ODA',
          'description' => config('site.description'),
          'publisher' => ['@id' => url('/#organization')],
          'inLanguage' => 'en',
        ]),
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
