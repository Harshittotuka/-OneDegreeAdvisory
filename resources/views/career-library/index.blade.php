@extends('career-library.layout')

@section('title', 'Global Career Library')

@section('app')
{{-- Content injected via JS (search form + trending grid), exactly like the source page. --}}
@endsection

@section('scripts')
@php
    // Blade's @json splits its argument on commas (they become the json_encode
    // flag/depth params), so anything non-trivial is precomputed here and
    // passed as a bare variable.
    //
    // Only surface the countries/languages we actually hold variants for, so the
    // search form can never offer a combo that silently falls back. Defaults keep
    // the form working if the controller didn't pass them (e.g. the show() error
    // fallback renders this same view).
    $availCountries = $countries ?? array_values(\App\Support\CareerLibraryStore::COUNTRIES);
    $availLanguages = $languages ?? array_keys(\App\Support\CareerLibraryStore::LANGUAGE_CODES);
    $jsCountries = collect(\App\Support\CareerLibraryStore::COUNTRIES)
        ->filter(fn ($name) => in_array($name, $availCountries, true))
        ->map(fn ($name, $code) => ['code' => $code, 'name' => $name])->values();
    $jsCareers = collect($careers)
        ->map(fn ($c) => ['title' => $c['title'], 'iconType' => $c['iconType'], 'bg' => $c['bg'], 'text' => $c['text']])->values();
    $jsSettings = [
        'heroTitlePrefix' => $settings['hero_title_prefix'],
        'heroTitleHighlight' => $settings['hero_title_highlight'],
        'heroSubtitle' => $settings['hero_subtitle'],
        'searchPlaceholder' => $settings['search_placeholder'],
        'trendingHeading' => $settings['trending_heading'],
        'exploreButton' => $settings['explore_button'],
    ];
    $jsParams = [
        'careerName' => $prefill['careerName'] ?? '',
        'country' => $prefill['country'] ?? 'India',
        'language' => $prefill['language'] ?? 'English',
    ];
    $jsError = $error ?? null;
@endphp
<script type="module">

    const ITEMS_PER_LOAD = 40;
    let currentIndex = 0;

    // SVG Icons Map
    const ICONS = @json(\App\Support\CareerLibraryIcons::MAP);

    // --- DATA ---
    const LANGUAGES = @json($availLanguages);

    const COUNTRIES = @json($jsCountries);

    const TRENDING_CAREERS = @json($jsCareers);

    const SETTINGS = @json($jsSettings);

    const ENSURE_URL = @json(route('career-library.ensure'));
    const CSRF = @json(csrf_token());
    const INITIAL_ERROR = @json($jsError);

    // --- STATE & UTILS ---
    const appContainer = document.getElementById('app-container');

    let currentState = {
        data: null,
        loading: false,
        error: null,
        params: @json($jsParams)
    };

    // --- SERVICE ---
    // Ensures a report exists for the requested combination (fetching it live
    // into the server cache when needed) and returns the detail-page URL.
    async function ensureCareerData(params) {
        try {
            const text = await $.ajax({
                url: ENSURE_URL,
                type: "POST",
                data: { ...params, _token: CSRF }
            });

            const result = typeof text === 'string' ? JSON.parse(text) : text;
            if (!result.ok) {
                throw new Error(result.error || "Invalid career search.");
            }
            return result.redirect;

        } catch (error) {
            console.error(error);
            const message = error.responseJSON?.error || error.message;
            throw new Error(message || "Failed to generate career report.");
        }
    }

    // --- ACTION HANDLERS ---
    window.handleSearchSubmit = async (e) => {
        e.preventDefault();
        const form = e.target;
        const params = {
            careerName: form.careerName.value,
            country: form.country.value,
            language: form.language.value
        };
        updateState({ loading: true, error: null, params });

        try {
            const redirect = await ensureCareerData(params);
            window.location.href = redirect;
        } catch (err) {
            updateState({ loading: false, error: err.message });
            showToast(err.message);
        }
    };

    window.exploreTrendingCareer = async (careerName) => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        const params = { careerName, country: 'India', language: 'English' };
        updateState({ loading: true, error: null, params, data: null });

        try {
            const redirect = await ensureCareerData(params);
            window.location.href = redirect;
        } catch (err) {
            updateState({ loading: false, error: err.message });
            showToast(err.message);
        }
    }

    window.resetSearch = () => {
        updateState({ data: null, error: null, loading: false });
    };

    // --- COUNTRY DROPDOWN LOGIC ---
    window.filterCountries = (search) => {
        const dropdown = document.getElementById('country-dropdown');
        if (!dropdown) return;

        const filtered = COUNTRIES.filter(c =>
        c.name.toLowerCase().includes(search.toLowerCase()) ||
        c.code.toLowerCase().includes(search.toLowerCase())
        );

        if (filtered.length === 0) {
        dropdown.innerHTML = '<div class="px-4 py-2 text-sm text-slate-500">No country found</div>';
        } else {
        dropdown.innerHTML = filtered.map(c => `
            <div
            onclick="selectCountry('${c.name}')"
            class="px-4 py-2 text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 cursor-pointer flex justify-between items-center transition-colors"
            >
            <span>${c.name}</span>
            <span class="text-xs font-mono bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded">${c.code}</span>
            </div>
        `).join('');
        }
    };

    window.selectCountry = (name) => {
        const input = document.getElementById('country-input');
        if (input) input.value = name;
        window.hideCountryDropdown();
    };

    window.showCountryDropdown = () => {
        const dropdown = document.getElementById('country-dropdown');
        const input = document.getElementById('country-input');
        if (dropdown && input) {
            dropdown.classList.remove('hidden');
            window.filterCountries(input.value); // Initial filter based on current val
        }
    };

    window.hideCountryDropdown = () => {
        // Small delay to allow click event on option to fire before hiding
        setTimeout(() => {
        const dropdown = document.getElementById('country-dropdown');
        if (dropdown) dropdown.classList.add('hidden');
        }, 200);
    };

    // --- RENDER FUNCTIONS ---
    function updateState(newState) {
        currentState = { ...currentState, ...newState };
        renderApp();
    }

    function renderApp() {
        const { data, loading, error } = currentState;
        let html = '';

        // 1. Search Form (Show if no data OR if loading initial)
        if (!data && !loading && !error) {
        html = renderSearchForm();
        }
        // 2. Loading State
        else if (loading) {
            // Initial loading
            html = `
            <div class="flex flex-col items-center justify-center py-20 min-h-[60vh]">
                <div class="animate-spin rounded-full h-12 w-12 border-4 border-indigo-600 border-t-transparent mb-4"></div>
                <p class="text-slate-600 font-medium animate-pulse">Designing your career roadmap...</p>
            </div>
            `;
        }
        // 3. Error State
        else if (error) {
        html = `
            ${renderSearchForm()}
            <div class="mt-8 p-4 bg-red-50 border border-red-200 rounded-xl text-red-600 max-w-lg mx-auto text-center animate-fade-in-up">
            <p>${error}</p>
            </div>
        `;
        }

        appContainer.innerHTML = html;
        bindSearchWidgets();
    }

    function renderSearchForm() {
        const { params } = currentState;

        return `
        <div class="w-full max-w-7xl mx-auto px-4 py-8 md:py-12 text-center animate-fade-in-up">
            <h1 class="text-3xl md:text-5xl font-bold mb-6 tracking-tight leading-tight">
            ${SETTINGS.heroTitlePrefix} <br class="hidden md:block" /> <span class="gradient-text">${SETTINGS.heroTitleHighlight}</span>
            </h1>
            <p class="text-[16px] md:text-[20px] text-slate-600 mb-10 max-w-3xl mx-auto">
            ${SETTINGS.heroSubtitle}
            </p>

            <form onsubmit="handleSearchSubmit(event)" class="glass-panel p-2 md:p-3 rounded-2xl shadow-2xl flex flex-col md:flex-row gap-3 relative z-10 w-full max-w-5xl mx-auto">

            <!-- Career Input -->
            <div class="relative group text-left w-full md:flex-1 h-16">
                <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-500">
                ${ICONS.search}
                </div>
                <input
                type="text"
                name="careerName"
                id="careerInput"
                autocomplete="off"
                value="${params.careerName}"
                placeholder="${SETTINGS.searchPlaceholder}"
                class="w-full pl-14 pr-4 h-full rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition-all bg-white/50 focus:bg-white text-slate-800 placeholder:text-slate-400 font-medium text-lg"
                required
                />

                <!-- DROPDOWN -->
                <div id="careerDropdown"
                    class="absolute top-full left-0 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-y-auto hidden z-[9999]">
                </div>

            </div>

            <!-- Country Input (Searchable Dropdown) -->
            <div class="w-full md:w-64 h-16 relative group text-left">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-500 z-10 h-16">
                ${ICONS.mapPin}
                </div>
                <input
                type="text"
                id="country-input"
                name="country"
                value="${params.country}"
                placeholder="Select Country"
                autocomplete="off"
                onfocus="showCountryDropdown()"
                oninput="filterCountries(this.value)"
                onblur="hideCountryDropdown()"
                class="w-full pl-11 pr-8 h-16 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition-all bg-white/50 focus:bg-white text-slate-800 font-medium cursor-text text-lg"
                />
                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-400 h-16">
                ${ICONS.chevronDown}
                </div>

                <!-- Dropdown List -->
                <div id="country-dropdown" class="absolute top-full left-0 w-full mt-2 bg-white rounded-xl border border-slate-200 shadow-xl max-h-60 overflow-y-auto hidden z-50 custom-scrollbar">
                <!-- Populated via JS -->
                </div>
            </div>

            <!-- Language Select -->
            <div class="w-full md:w-40 relative group text-left h-16">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-500">
                ${ICONS.globe}
                </div>
                <select
                name="language"
                class="w-full pl-11 pr-8 h-full rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition-all bg-white/50 focus:bg-white text-slate-800 font-medium appearance-none cursor-pointer text-lg"
                >
                ${LANGUAGES.map(lang => `<option value="${lang}" ${params.language === lang ? 'selected' : ''}>${lang}</option>`).join('')}
                </select>
                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-400">
                ${ICONS.chevronDown}
                </div>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold h-16 px-8 rounded-xl transition-all transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2 shadow-lg shadow-indigo-200 w-full md:w-auto text-lg"
            >
                <span>Explore</span>
                ${ICONS.sparkles}
            </button>
            </form>

            <!-- TRENDING NOW SECTION -->
            <div class="mt-20 max-w-7xl mx-auto">
                <h3 class="text-3xl font-bold text-slate-900 mb-10 tracking-tight">${SETTINGS.trendingHeading}</h3>
                <div id="trending-container" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 md:gap-6">
                </div>
            </div>

            <!-- Button -->
            <div class="flex items-center justify-center mt-10">
                <button
                    class="
                        relative px-8 py-4 text-xl font-bold
                        bg-gradient-to-r from-indigo-700 to-pink-700
                        bg-clip-text text-transparent
                        border border-white/20 rounded-full
                        hover:scale-105 transition-all duration-300
                        shadow-lg hover:shadow-pink-600/40
                        scrollToTop
                    "
                    style="background: linear-gradient(to right, #4338ca, #be185d);-webkit-background-clip: text;-webkit-text-fill-color: transparent;">
                    ${SETTINGS.exploreButton}
                </button>
            </div>

        </div>
        `;
    }

    function showToast(message, type = "error") {
        const toast = document.createElement("div");

        const colors = {
            error: "bg-red-600 text-white",
            success: "bg-green-600 text-white",
            warning: "bg-yellow-500 text-black",
            info: "bg-blue-600 text-white"
        };

        toast.className =
            `px-4 py-3 rounded-lg shadow-lg text-sm font-medium ${colors[type]} ` +
            `transform transition-all duration-300 opacity-0 translate-y-5 cursor-pointer`;

        toast.textContent = message;

        const container = document.getElementById("toast-container");
        container.appendChild(toast);

        // animate upwards + fade-in
        setTimeout(() => {
            toast.classList.remove("opacity-0", "translate-y-5");
        }, 10);

        let hideTimeout;
        let remaining = 2000; // 2 sec
        let startTime = Date.now();

        function startTimer() {
            startTime = Date.now();
            hideTimeout = setTimeout(hideToast, remaining);
        }

        function pauseTimer() {
            clearTimeout(hideTimeout);
            remaining -= Date.now() - startTime;
        }

        function hideToast() {
            toast.classList.add("opacity-0", "translate-y-5");
            setTimeout(() => toast.remove(), 300);
        }

        // Start auto close timer
        startTimer();

        // Pause on hover
        toast.addEventListener("mouseenter", pauseTimer);

        // Resume on leave
        toast.addEventListener("mouseleave", startTimer);
    }

    // --- Dropdown Search ---
    const careers = TRENDING_CAREERS.map(c => c.title);

    // The search form is re-rendered on every state change, so its widgets
    // (career autocomplete, trending grid, scroll button) re-bind each time.
    function bindSearchWidgets() {
        const input = document.getElementById("careerInput");
        const dropdown = document.getElementById("careerDropdown");

        if (input && dropdown) {
            input.addEventListener("input", function () {
                const value = this.value.toLowerCase().trim();

                if (!value) {
                    dropdown.innerHTML = "";
                    dropdown.classList.add("hidden");
                    return;
                }

                // Filter matching careers
                const matches = careers.filter(c =>
                    c.toLowerCase().includes(value)
                );

                if (matches.length === 0) {
                    dropdown.classList.add("hidden");
                    return;
                }

                // Build dropdown items
                dropdown.innerHTML = matches
                    .map(item => `
                        <div class="px-4 py-2 cursor-pointer hover:bg-indigo-100 text-slate-700">
                            ${item}
                        </div>
                    `)
                    .join("");

                dropdown.classList.remove("hidden");
            });

            // Click item to select
            dropdown.addEventListener("click", function (e) {
                if (e.target && e.target.matches("div")) {
                    input.value = e.target.textContent.trim();
                    dropdown.classList.add("hidden");
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener("click", function (e) {
                if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add("hidden");
                }
            });
        }

        $(".scrollToTop").off('click').on('click', function(){
            document.getElementById("app-container").scrollIntoView({
                behavior: "smooth"
            });
        });

        currentIndex = 0;
        const container = document.getElementById('trending-container');
        if (container) {
            container.innerHTML = '';
            renderTrendingChunk();
        }

        $("#country-input").off('focus').on('focus', function(){
            $(this).val('');
            showCountryDropdown();
        });
    }

    function renderTrendingChunk() {

        // Helper to get random badges
        const badges = [
            { label: 'In Demand 🔥', color: 'bg-rose-100 text-rose-700 border-rose-200' },
            { label: 'New Age ✨', color: 'bg-violet-100 text-violet-700 border-violet-200' },
            { label: 'High Pay 💰', color: 'bg-emerald-100 text-emerald-700 border-emerald-200' }
        ];

        const container = document.getElementById('trending-container');
        if (!container) return;

        const slice = TRENDING_CAREERS.slice(
            currentIndex,
            currentIndex + ITEMS_PER_LOAD
        );

        slice.forEach((career, idx) => {
            const badge = badges[(currentIndex + idx) % badges.length];

            container.insertAdjacentHTML(
            'beforeend',
            `
            <div
                onclick="exploreTrendingCareer('${career.title.replace(/'/g, "\\'")}')"
                class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all cursor-pointer group flex flex-col items-center justify-center gap-4 text-center h-48 relative overflow-hidden"
            >
                <div class="absolute top-2 right-2">
                <span class="relative inline-flex overflow-hidden rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ${badge.color}">
                    <span class="shimmer-effect"></span>
                    <span class="relative">${badge.label}</span>
                </span>
                </div>

                <div class="w-14 h-14 rounded-2xl ${career.bg} flex items-center justify-center ${career.text} group-hover:scale-110 transition-transform duration-300">
                ${ICONS[career.iconType] || ICONS.generic}
                </div>

                <span class="text-[13px] font-bold text-slate-700 group-hover:text-indigo-600 transition-colors">
                ${career.title}
                </span>
            </div>
            `
            );
        });

        currentIndex += ITEMS_PER_LOAD;
    }

    window.addEventListener('scroll', () => {
        if (
            window.innerHeight + window.scrollY >=
            document.body.offsetHeight - 300
        ) {
            if (currentIndex < TRENDING_CAREERS.length) {
            renderTrendingChunk();
            }
        }
    });

    // Initial Render
    if (INITIAL_ERROR) {
        currentState.error = INITIAL_ERROR;
    }
    renderApp();
    if (INITIAL_ERROR) {
        showToast(INITIAL_ERROR);
    }

    if(window.self !== window.top){
        document.querySelectorAll('nav').forEach(function(nav) {
            nav.style.display = 'none';
        });
    }
</script>
@endsection
