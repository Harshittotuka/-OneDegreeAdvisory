{{-- Trending Career — page shell. Uses its own compiled Tailwind (output.css)
     for the page body, but shares the real site chrome (the Stripe navbar from
     partials.header-stripe + its styles.css / stripe-nav.css / stripe-nav.js),
     so the navbar here is identical to the rest of the site. --}}
@php
    // Notice-bar variant, mirrored from layouts/app.blade.php so the shared
    // header renders exactly as it does site-wide.
    $topbarVariant = app(\App\Support\NoticeBarStore::class)->get()['variant'] ?? 'left-socials';

    // Cache-bust local CSS/JS — the site's own helper, not a local copy of half
    // of it. The copy that used to live here appended the mtime and stopped
    // there, so it never did the .min swap that Asset::preferMinified does, and
    // this page alone was served the 620 KB styles.css while every other page
    // got the 448 KB build. Same call as layouts/app now.
    $assetVer = fn (string $file) => \App\Support\Asset::v($file);

    // Encoded here, not inline below: Blade reads a bare '@context' or '@type'
    // in markup as a directive. Same approach as layouts/app.blade.php.
    $clOrgJsonLd = json_encode([
        [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('site.name'),
            'url' => url('/'),
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'EducationalOrganization',
            'name' => config('site.name'),
            'url' => url('/'),
            'logo' => asset('assets/Logo/og-image.png'),
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp
<!DOCTYPE html>
<html lang="en" data-color-theme="cream" class="topbar-{{ $topbarVariant }}">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Mirrors layouts/app: these pages share styles.css, so they get the same
         content entry animation and must skip it on a view-transition
         navigation for the same reason. Inline because pagereveal fires before
         the incoming document's first paint. --}}
    <script>
      window.addEventListener("pagereveal", function (event) {
        if (event.viewTransition) document.documentElement.classList.add("is-vt-nav");
      });
    </script>
    <title>@yield('title', 'Trending Career')</title>
    <meta name="description" content="@yield('meta_description', 'An intelligent career library tool that generates comprehensive career paths, work nature insights, and curated resources for any profession.')">
    <meta name="keywords" content="@yield('meta_keywords', 'career, guidance, ai, roadmap, jobs, profession, education')">
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/Logo/mark.svg') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/Logo/favicon-32.png') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/Logo/apple-touch-icon.png') }}" />
    {{-- Page body is Tailwind; the shared navbar needs the site stylesheets.
         Load Tailwind first so the site nav CSS (loaded after) wins for the
         header, while Tailwind utilities (class selectors) still beat the
         element-level body/link rules from styles.css. --}}
    <link href="{{ asset('career-library/output.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ $assetVer('styles.css') }}">
    <link rel="stylesheet" href="{{ $assetVer('stripe-nav.css') }}">
    {{-- The other 40%% of what used to be one stylesheet. Every rule in here
         matched nothing at first render on any of the site's pages, so it can
         arrive after the first paint without changing it -- which is the whole
         point: it is 238 KB the browser no longer has to fetch and parse before
         it may draw anything. media="print" is the trick that makes it
         non-blocking; the onload hands it back to the real media list once it
         has landed. --}}
    <link rel="stylesheet" href="{{ $assetVer('styles-deferred.css') }}" media="print" onload="this.media='all';this.onload=null">
    <noscript><link rel="stylesheet" href="{{ $assetVer('styles-deferred.css') }}"></noscript>
    {{-- One combined request: these were two separate render-blocking
         stylesheets for the same five families. The preconnects let the DNS,
         TCP and TLS for both font hosts happen while the HTML is still being
         parsed instead of after the stylesheet is discovered. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@300;400;500;600;700;800&family=Jost:wght@600;700&family=Manrope:wght@400;500;600;700;800&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    {{-- jQuery used to load here: 88 KB, render-blocking, from a third-party
         CDN, on every page of this section. The detail page never called it at
         all, and the landing page called it three times -- one click handler,
         one focus handler and a .val(''). Those are now plain DOM calls in
         index.blade, so nothing is left to load. --}}
    {{-- Self-hosted and pinned, same as layouts/app: `lucide@latest` on unpkg
         redirects with max-age=60, so every page paid a third-party round trip
         before its icons could render. Also as in layouts/app, what ships is
         the generated subset with the full library as its named fallback. --}}
    <script src="{{ $assetVer('assets/vendor/lucide-subset.min.js') }}"
            data-lucide-fallback="{{ $assetVer('assets/vendor/lucide.min.js') }}" defer></script>
    <script src="{{ $assetVer('stripe-nav.js') }}" defer></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Enforce professional sans-serif for all headings */
        h1, h2, h3, h4, h5, h6 { font-family: 'Inter', sans-serif; }

        /* We now load the site's styles.css (for the shared navbar). It carries
           bare element rules (h1/h2/h3 font-family/size/line-height/margin, the
           dark body background gradient, etc.) meant for the main site's layout.
           Neutralise them inside this Tailwind page so the page keeps rendering
           exactly as it did before — the navbar (which sits OUTSIDE #app-container
           and uses no headings) is unaffected. Tailwind utility classes still win
           by specificity; these element-level resets only catch nodes with no
           matching utility. */
        body.cl-body { background: #fafaf9; } /* Tailwind bg-stone-50, kills the site's dark gradient */
        #app-container h1, #app-container h2, #app-container h3,
        #app-container h4, #app-container h5, #app-container h6 {
            font-family: 'Inter', sans-serif;
            line-height: normal;
            max-width: none;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .gradient-text {
            background: linear-gradient(to right, #4338ca, #be185d);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .animate-blob {
            animation: blob 7s infinite;
        }
        .animation-delay-2000 {
            animation-delay: 2s;
        }
        .animation-delay-4000 {
            animation-delay: 4s;
        }
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease-out forwards;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* Accordion Transitions */
        .accordion-content {
            transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
            max-height: 0;
            opacity: 0;
            overflow: hidden;
        }
        .accordion-content.open {
            max-height: 500px; /* Arbitrary large height */
            opacity: 1;
        }
        .rotate-180 {
            transform: rotate(180deg);
        }
        /* Custom Scrollbar for Dropdown */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Shimmer Animation for Badges */
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        .shimmer-effect {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, transparent 0%, rgba(255,255,255,0.6) 50%, transparent 100%);
            transform: skewX(-20deg);
            animation: shimmer 2s infinite;
        }

        /* Shake Animation for Market Snapshot */
        @keyframes shake-gentle {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            10% { transform: translate(-1px, -1px) rotate(-0.5deg); }
            20% { transform: translate(1px, 1px) rotate(0.5deg); }
            30% { transform: translate(-1px, 1px) rotate(-0.5deg); }
            40% { transform: translate(1px, -1px) rotate(0.5deg); }
            50% { transform: translate(0, 0) rotate(0deg); }
        }
        .animate-shake-card {
            animation: shake-gentle 5s infinite;
        }
    </style>

    {{-- og:description and the Twitter card were both absent, so a shared link
         to any page in this section rendered as a bare title over an image with
         no supporting text. They read the same @section the meta description
         does, so a page sets its copy once. --}}
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ config('site.name') }}">
    <meta property="og:locale" content="en_IN">
    <meta property="og:title" content="@yield('title', 'Trending Career')">
    <meta property="og:description" content="@yield('meta_description', 'An intelligent career library tool that generates comprehensive career paths, work nature insights, and curated resources for any profession.')">
    <meta property="og:type" content="website">
    <meta property="og:image" content="{{ asset('assets/Logo/og-image.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'Trending Career')">
    <meta name="twitter:description" content="@yield('meta_description', 'An intelligent career library tool that generates comprehensive career paths, work nature insights, and curated resources for any profession.')">
    <meta name="twitter:image" content="{{ asset('assets/Logo/og-image.png') }}">

    {{-- Structured data. Every other page on the site carries at least the
         organisation graph; this section carried none, which is why it was the
         only page in the sitemap with no structured data at all. A page can add
         its own on top through the 'schema' section. --}}
    <script type="application/ld+json">{!! $clOrgJsonLd !!}</script>
    @yield('schema')

    @if (\App\Support\Seo::isCanonicalHost())
        <link rel="canonical" href="{{ url()->current() }}">
    @else
        <meta name="robots" content="noindex, nofollow" />
    @endif
</head>
<body class="cl-body bg-stone-50 text-slate-900 antialiased selection:bg-rose-100 selection:text-rose-700 min-h-screen flex flex-col{{ ! empty($live) ? ' cms-editing' : '' }}">

    {{-- Shared site navbar — identical to the rest of the site. --}}
    @include('partials.header-stripe', ['activeNav' => 'career-library'])

    {{-- Coming-soon overlay for the navbar's "Evaluate your personality"
         trigger. The main site drives this via script.js, which we don't load
         here, so a small self-contained handler (below) powers it instead. --}}
    @include('partials.students-hub-overlay')

    <div id="toast-container" class="fixed bottom-10 left-1/2 -translate-x-1/2 z-50 space-y-3 flex flex-col items-center">
    </div>

    <!-- Main Content -->
    <main class="relative z-10 flex-grow flex flex-col items-center w-full">

        <!-- Background Shapes -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none z-0">
            <div class="absolute -top-20 -left-20 w-96 h-96 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
            <div class="absolute top-40 -right-20 w-96 h-96 bg-purple-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>
            <div class="absolute -bottom-32 left-1/2 w-96 h-96 bg-pink-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-4000"></div>
        </div>

        <!-- App Container -->
        <div id="app-container" class="w-full relative z-10">
            @yield('app')
        </div>

        {{-- Anything a crawler must be able to read goes here, not above.
             index.blade's own script does appContainer.innerHTML = html on
             load, so every byte inside #app-container is replaced before the
             page settles -- which is why this section was reaching Google as a
             couple of hundred words with no links in it. This block sits
             outside that assignment and survives. --}}
        @yield('after-app')

    </main>

    @yield('overlays')

    {{-- The shared navbar uses Lucide <i data-lucide> icons. On the main site
         script.js does the initial createIcons(); we don't load script.js here,
         so render them once Lucide is ready (it's deferred, so poll briefly). --}}
    <script>
        (function initNavIcons() {
            if (window.lucide) { window.lucide.createIcons(); return; }
            var tries = 0;
            var t = setInterval(function () {
                if (window.lucide || tries++ > 40) {
                    clearInterval(t);
                    if (window.lucide) window.lucide.createIcons();
                }
            }, 50);
        })();
    </script>

    {{-- Self-contained "coming soon" handler for the navbar's Students-Hub
         triggers (e.g. "Evaluate your personality"). The main site handles this
         in public/script.js, which this page intentionally doesn't load; this
         mirrors that behaviour (open/populate/close + Esc) using the same CSS. --}}
    <script>
        (function initStudentsHub() {
            var overlay = document.querySelector('[data-students-hub-overlay]');
            var triggers = document.querySelectorAll('[data-students-hub-trigger]');
            if (!overlay || !triggers.length) return;

            var CONTENT = {
                'personality-assessment': {
                    kicker: 'Know how you work',
                    title: 'Personality Assessment',
                    desc: 'A guided assessment that surfaces your strengths, working style, and best-fit paths — so your applications play to what makes you, you.',
                    features: [
                        { icon: 'brain', label: 'Strengths profile' },
                        { icon: 'compass', label: 'Work-style insights' },
                        { icon: 'target', label: 'Best-fit matches' }
                    ]
                }
            };
            var DEFAULT_CONTENT = {
                kicker: 'AI-powered student tools',
                title: 'Students Hub',
                desc: 'A smarter space for profile insights, best-fit university shortlists, application planning, and progress tracking.',
                features: [
                    { icon: 'brain', label: 'Profile intelligence' },
                    { icon: 'target', label: 'Best-fit shortlists' },
                    { icon: 'list-checks', label: 'Application copilot' }
                ]
            };

            var closeEls = overlay.querySelectorAll('[data-students-hub-close]');
            var closeTimer = null;
            var lastFocused = null;
            function renderIcons() { if (window.lucide) window.lucide.createIcons(); }

            function populate(trigger) {
                var c = (trigger && CONTENT[trigger.dataset.feature]) || DEFAULT_CONTENT;
                var kicker = overlay.querySelector('.students-hub-kicker');
                var title = overlay.querySelector('#students-hub-title');
                var desc = overlay.querySelector('#students-hub-desc');
                var feats = overlay.querySelector('.students-hub-features');
                if (kicker) kicker.innerHTML = '<i data-lucide="sparkles" aria-hidden="true"></i>' + c.kicker;
                if (title) title.textContent = c.title + ' is coming soon';
                if (desc) desc.textContent = c.desc;
                if (feats) {
                    feats.setAttribute('aria-label', c.title + ' preview features');
                    feats.innerHTML = c.features.map(function (f) {
                        return '<span><i data-lucide="' + f.icon + '" aria-hidden="true"></i> ' + f.label + '</span>';
                    }).join('');
                }
            }

            function open(trigger) {
                window.clearTimeout(closeTimer);
                lastFocused = trigger || document.activeElement;
                populate(trigger);
                overlay.hidden = false;
                overlay.classList.remove('is-closing');
                document.body.classList.add('student-hub-open');
                window.requestAnimationFrame(function () {
                    overlay.classList.add('is-open');
                    overlay.setAttribute('aria-hidden', 'false');
                    renderIcons();
                });
            }

            function close() {
                if (!overlay.classList.contains('is-open')) return;
                window.clearTimeout(closeTimer);
                overlay.classList.remove('is-open');
                overlay.classList.add('is-closing');
                overlay.setAttribute('aria-hidden', 'true');
                closeTimer = window.setTimeout(function () {
                    overlay.hidden = true;
                    overlay.classList.remove('is-closing');
                    document.body.classList.remove('student-hub-open');
                    if (lastFocused && document.contains(lastFocused)) lastFocused.focus();
                }, 260);
            }

            triggers.forEach(function (t) { t.addEventListener('click', function () { open(t); }); });
            closeEls.forEach(function (el) { el.addEventListener('click', close); });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && overlay.classList.contains('is-open')) { e.preventDefault(); close(); }
            });
        })();
    </script>

    <!-- Logic -->
    @yield('scripts')

    @if (! empty($live))
        {{-- Admin live editor chrome (bottom bar, popovers, serializer). Only
             rendered by CareerLibraryCmsController::live() behind cms.auth. --}}
        @include('admin.career-library._editor_chrome')
    @endif
</body>
</html>
