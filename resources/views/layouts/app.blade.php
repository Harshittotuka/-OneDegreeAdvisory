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
    <meta name="theme-color" content="#1a0088">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;450;500;600;700&family=Jost:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
      (function () {
        var root = document.documentElement;
        root.classList.add("js");
        // Apply persisted preview-switcher choices early to avoid a flash.
        // (The top-bar variant is set server-side from the CMS; only the Nav
        // content switcher remains a client-side preview toggle.)
        try {
          if (localStorage.getItem("oda:nav-content") === "updated") {
            root.classList.add("nav-updated");
          }
        } catch (e) {}
      })();
    </script>
    <link rel="stylesheet" href="{{ asset('styles.css') }}">
    <link rel="stylesheet" href="{{ asset('stripe-nav.css') }}">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
    <script src="{{ asset('script.js') }}" defer></script>
    <script src="{{ asset('stripe-nav.js') }}" defer></script>
    <script src="{{ asset('ui-switchers.js') }}" defer></script>
  </head>
  <body class="{{ trim(($bodyClass ?? '').($cmsEdit ? ' cms-editing' : '')) }}">
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

    <div class="ui-switch ui-switch--nav" role="group" aria-label="Navigation content" data-nav-content-switch>
      <span class="ui-switch__label">Nav</span>
      <div class="ui-switch__options">
        <button class="ui-switch__btn" type="button" data-nav-content-option="current" aria-pressed="true">Current</button>
        <button class="ui-switch__btn" type="button" data-nav-content-option="updated" aria-pressed="false">Updated</button>
      </div>
    </div>

    {{-- JSON is built in PHP so the literal context key is not read as a Blade directive. --}}
    @php($orgJsonLd = json_encode(['@context' => 'https://schema.org', '@type' => 'EducationalOrganization', 'name' => config('site.name'), 'url' => url('/'), 'logo' => asset('assets/Logo/og-image.png'), 'description' => config('site.description'), 'email' => config('site.contact.email'), 'telephone' => config('site.contact.phone'), 'address' => config('site.contact.address'), 'areaServed' => 'Worldwide'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT))
    <script type="application/ld+json">
      {!! $orgJsonLd !!}
    </script>

    @if($cmsEdit)
      @include('admin.home-hero._editor_chrome')
    @endif
  </body>
</html>
