{{-- Global Career Library — standalone page shell (own compiled Tailwind, own
     slim navbar; the main site chrome is intentionally absent). --}}
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Global Career Library')</title>
    <meta name="description" content="@yield('meta_description', 'An intelligent career library tool that generates comprehensive career paths, work nature insights, and curated resources for any profession.')">
    <meta name="keywords" content="@yield('meta_keywords', 'career, guidance, ai, roadmap, jobs, profession, education')">
    <link rel="icon" type="image/png" href="{{ asset('assets/Logo/favicon.png') }}" />
    <link href="{{ asset('career-library/output.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Enforce professional sans-serif for all headings */
        h1, h2, h3, h4, h5, h6 { font-family: 'Inter', sans-serif; }

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

    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', 'Global Career Library')">
    <meta property="og:type" content="website">
    <meta property="og:image" content="{{ asset('assets/Logo/og-image.png') }}">

    @if (\App\Support\Seo::isCanonicalHost())
        <link rel="canonical" href="{{ url()->current() }}">
    @else
        <meta name="robots" content="noindex, nofollow" />
    @endif
</head>
<body class="bg-stone-50 text-slate-900 antialiased selection:bg-rose-100 selection:text-rose-700 min-h-screen flex flex-col{{ ! empty($live) ? ' cms-editing' : '' }}">

    <!-- Navbar -->
    <nav class="relative z-10 border-b border-white/50 bg-white/50 backdrop-blur-md">
        <div class="max-w-7xl mx-auto pt-2 pb-2 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-2 cursor-pointer">
                    <a href="{{ url('/') }}"><img class="img-responsive" style="display: inline-block;max-width: 200px !important;max-height: 80px;" loading="lazy" src="{{ asset('assets/Logo/mark.svg') }}" alt="One Degree Advisory"></a>
                </div>
                <div class="flex items-center gap-2 cursor-pointer hidden md:block">
                    <ul class="flex items-center gap-6 text-gray-700">

                        <!-- Email -->
                        <li class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="4" width="20" height="16" rx="2" ry="2"></rect>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                            <a href="mailto:{{ $settings['contact_email'] }}"><span>{{ $settings['contact_email'] }}</span></a>
                        </li>

                        <!-- Phone -->
                        <li class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67 A2 2 0 0 1 4.11 2h3 a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 a2 2 0 0 1-.45 2.11L8.09 9.91 a16 16 0 0 0 6 6 l1.27-1.27 a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7 A2 2 0 0 1 22 16.92z"/>
                            </svg>
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $settings['contact_phone']) }}"><span>{{ $settings['contact_phone'] }}</span></a>
                        </li>

                    </ul>
                </div>
            </div>
        </div>
    </nav>

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

    </main>

    @yield('overlays')

    <!-- Logic -->
    @yield('scripts')

    @if (! empty($live))
        {{-- Admin live editor chrome (bottom bar, popovers, serializer). Only
             rendered by CareerLibraryCmsController::live() behind cms.auth. --}}
        @include('admin.career-library._editor_chrome')
    @endif
</body>
</html>
