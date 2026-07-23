@extends('career-library.layout')

@section('title', 'Trending Career')

@section('app')
{{-- Content injected via JS (search form + trending grid), exactly like the source page. --}}
@endsection

@section('overlays')
{{-- Lead-capture popup shown when a career is clicked. Self-contained CSS: the
     page's compiled Tailwind (no build step) omits several utilities this modal
     needs, so it carries its own styles rather than depending on them. --}}
<style>
  #cl-idx-gate { position: fixed; inset: 0; z-index: 100000; display: none; align-items: center; justify-content: center;
    padding: 16px; background: rgba(15,23,42,.62); -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px); }
  #cl-idx-gate.is-open { display: flex; }
  .cl-idx-card { position: relative; width: 100%; max-width: 440px; background: #fff; border-radius: 20px;
    overflow: hidden; box-shadow: 0 30px 70px rgba(2,6,23,.45); animation: fadeInUp .35s ease-out both; }
  .cl-idx-head { background: linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; padding: 22px 24px; }
  .cl-idx-head h2 { margin:0; font-size:1.35rem; font-weight:800; line-height:1.2; color:#fff; }
  .cl-idx-head p { margin:6px 0 0; font-size:.9rem; color:#e0e7ff; line-height:1.5; }
  .cl-idx-body { padding: 24px; }
  .cl-idx-field { margin-bottom: 16px; }
  .cl-idx-field label { display:block; font-size:.85rem; font-weight:600; color:#334155; margin-bottom:6px; }
  .cl-idx-field input { width:100%; padding:12px 16px; border:1px solid #e2e8f0; border-radius:12px; outline:none;
    color:#1e293b; font-size:1rem; transition:border-color .15s,box-shadow .15s; background:#fff; }
  .cl-idx-field input:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(199,210,254,.7); }
  #cl-idx-error { color:#dc2626; font-size:.85rem; margin:0 0 12px; display:none; }
  #cl-idx-error.is-shown { display:block; }
  #cl-idx-submit { width:100%; background:#4f46e5; color:#fff; border:0; font-weight:700; font-size:1rem;
    padding:13px; border-radius:12px; cursor:pointer; transition:background .15s,opacity .15s; }
  #cl-idx-submit:hover { background:#4338ca; }
  #cl-idx-submit:disabled { opacity:.6; cursor:not-allowed; }
  .cl-idx-fineprint { font-size:.72rem; color:#94a3b8; text-align:center; line-height:1.5; margin:14px 0 0; }
  .cl-idx-close { position:absolute; top:14px; right:14px; width:32px; height:32px; border:0; border-radius:50%;
    background:rgba(255,255,255,.18); color:#fff; font-size:1rem; line-height:1; cursor:pointer; z-index:2;
    display:flex; align-items:center; justify-content:center; }
  .cl-idx-close:hover { background:rgba(255,255,255,.32); }
  .cl-idx-success { padding: 40px 28px 34px; text-align:center; position:relative; overflow:hidden; }
  .cl-idx-confetti { position:absolute; inset:0; pointer-events:none; overflow:hidden; }
  .cl-idx-confetti span { position:absolute; top:-10%; width:8px; height:14px; border-radius:2px; opacity:0;
    animation: cl-confetti-fall 1.6s ease-in forwards; }
  @keyframes cl-confetti-fall {
    0%   { opacity:0; transform: translate3d(0,-20px,0) rotate(0deg); }
    8%   { opacity:1; }
    100% { opacity:0; transform: translate3d(var(--cl-drift,0px),260px,0) rotate(var(--cl-spin,360deg)); }
  }
  .cl-idx-badge-wrap { position:relative; width:88px; height:88px; margin:0 auto 20px; }
  .cl-idx-badge-ring { position:absolute; inset:0; border-radius:50%; border:2px solid rgba(22,163,74,.35); opacity:0;
    animation: cl-ring-pulse 1.4s ease-out .15s forwards; }
  .cl-idx-badge-ring.cl-ring-2 { animation-delay:.32s; }
  @keyframes cl-ring-pulse {
    0%   { transform: scale(.6); opacity:.55; }
    100% { transform: scale(1.9); opacity:0; }
  }
  .cl-idx-check { position:absolute; inset:12px; border-radius:50%; background:radial-gradient(circle at 32% 28%,#4ade80,#16a34a);
    color:#fff; display:flex; align-items:center; justify-content:center; box-shadow:0 10px 24px -6px rgba(22,163,74,.55), inset 0 2px 4px rgba(255,255,255,.35);
    transform: scale(0); animation: cl-check-pop .5s cubic-bezier(.34,1.56,.64,1) .1s forwards; }
  .cl-idx-check svg { width:36px; height:36px; }
  .cl-idx-check svg path { stroke-dasharray:28; stroke-dashoffset:28; animation: cl-check-draw .4s ease-out .45s forwards; }
  @keyframes cl-check-pop { to { transform: scale(1); } }
  @keyframes cl-check-draw { to { stroke-dashoffset:0; } }
  .cl-idx-success h2 { margin:0 0 8px; font-size:1.5rem; font-weight:800; color:#0f172a;
    opacity:0; transform: translateY(6px); animation: cl-fade-up .4s ease-out .35s forwards; }
  .cl-idx-success p { margin:0; font-size:.95rem; color:#475569; line-height:1.6;
    opacity:0; transform: translateY(6px); animation: cl-fade-up .4s ease-out .45s forwards; }
  @keyframes cl-fade-up { to { opacity:1; transform:translateY(0); } }
  .cl-idx-redirect-note { margin-top:10px !important; font-size:.82rem !important; color:#7c3aed !important; font-weight:600; display:none;
    align-items:center; justify-content:center; gap:6px; }
  .cl-idx-redirect-note .cl-idx-spinner { width:13px; height:13px; border-radius:50%; border:2px solid rgba(124,58,237,.25);
    border-top-color:#7c3aed; animation: cl-spin .7s linear infinite; display:inline-block; }
  @keyframes cl-spin { to { transform: rotate(360deg); } }
  #cl-idx-success-action { margin-top:22px; background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; border:0; font-weight:700; font-size:.95rem;
    padding:13px 26px; border-radius:12px; cursor:pointer; box-shadow:0 10px 20px -8px rgba(79,70,229,.55);
    transition:transform .15s, box-shadow .15s; opacity:0; animation: cl-fade-up .4s ease-out .55s forwards; }
  #cl-idx-success-action:hover { transform: translateY(-2px); box-shadow:0 14px 24px -8px rgba(79,70,229,.6); }
  #cl-idx-success-action:active { transform: translateY(0); }
</style>
<div id="cl-idx-gate" aria-modal="true" role="dialog" aria-labelledby="cl-idx-title">
  <div class="cl-idx-card">
    <button type="button" class="cl-idx-close" data-idx-close aria-label="Close">&#10005;</button>

    <div data-idx-form-state>
      <div class="cl-idx-head">
        <h2 id="cl-idx-title">Get the full career report</h2>
        <p>Share your details and our team will help you explore <strong data-idx-career>this career</strong>.</p>
      </div>
      <form id="cl-idx-form" class="cl-idx-body" novalidate>
        <div class="cl-idx-field">
          <label for="cl-idx-name">Full name</label>
          <input type="text" id="cl-idx-name" name="name" required autocomplete="name" placeholder="Your name">
        </div>
        <div class="cl-idx-field">
          <label for="cl-idx-email">Email</label>
          <input type="email" id="cl-idx-email" name="email" required autocomplete="email" placeholder="you@example.com">
        </div>
        <div class="cl-idx-field">
          <label for="cl-idx-phone">Phone</label>
          <input type="tel" id="cl-idx-phone" name="phone" required autocomplete="tel" placeholder="+91 90000 00000">
        </div>
        <p id="cl-idx-error"></p>
        <button type="submit" id="cl-idx-submit"><span class="cl-idx-submit-label">Submit details</span></button>
        <p class="cl-idx-fineprint">By continuing you agree to be contacted about your career interests.</p>
      </form>
    </div>

    <div data-idx-success-state style="display:none;">
      <div class="cl-idx-success">
        <div class="cl-idx-confetti" data-idx-confetti aria-hidden="true"></div>
        <div class="cl-idx-badge-wrap">
          <div class="cl-idx-badge-ring"></div>
          <div class="cl-idx-badge-ring cl-ring-2"></div>
          <div class="cl-idx-check">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
          </div>
        </div>
        <h2>Thank you!</h2>
        <p>Our team will reach out to you shortly with more details.</p>
        <p class="cl-idx-redirect-note" data-idx-redirect-note><span class="cl-idx-spinner"></span> Taking you to the career report…</p>
        <button type="button" id="cl-idx-success-action" data-idx-success-action>Explore more careers</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
@php
    // Blade's @json splits its argument on commas (they become the json_encode
    // flag/depth params), so anything non-trivial is precomputed here and
    // passed as a bare variable.
    //
    // Only surface the countries we actually hold variants for, so the search
    // form can never offer a combo that silently falls back. Defaults keep the
    // form working if the controller didn't pass them (e.g. the show() error
    // fallback renders this same view).
    $availCountries = $countries ?? array_values(\App\Support\CareerLibraryStore::COUNTRIES);
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

    const COUNTRIES = @json($jsCountries);

    const TRENDING_CAREERS = @json($jsCareers);

    const SETTINGS = @json($jsSettings);

    const LEAD_URL = @json(route('career-library.lead'));
    const ENSURE_URL = @json(route('career-library.ensure'));
    const DETAIL_ENABLED = @json((bool) ($settings['detail_pages_enabled'] ?? false));
    const CSRF = @json(csrf_token());
    const INITIAL_ERROR = @json($jsError);

    // --- STATE & UTILS ---
    const appContainer = document.getElementById('app-container');

    // ?error= and the {career} URL segment are untrusted input. Escape them
    // before they reach innerHTML / an HTML attribute so they cannot inject
    // markup (reflected XSS).
    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    let currentState = {
        data: null,
        loading: false,
        error: null,
        params: @json($jsParams)
    };

    // --- ACTION HANDLERS ---
    // With detail pages ON, clicking a career (or submitting the search) opens
    // its full report straight away; the lead-capture popup appears there after
    // the CMS reading window. With detail pages OFF, there is no report to show,
    // so we fall back to the immediate lead-capture popup on this page.
    window.handleSearchSubmit = (e) => {
        e.preventDefault();
        const form = e.target;
        const ctx = {
            career: (form.careerName.value || '').trim(),
            country: (form.country.value || 'India').trim() || 'India',
            language: 'English',
        };
        DETAIL_ENABLED ? goToReport(ctx) : openLeadGate(ctx);
    };

    window.exploreTrendingCareer = (careerName) => {
        const ctx = { career: careerName, country: 'India', language: 'English' };
        DETAIL_ENABLED ? goToReport(ctx) : openLeadGate(ctx);
    };

    // Resolve a career to its report URL (server validates it's curated and
    // handles the country/language fallback) and navigate there. If it isn't in
    // the library yet, surface the same error the search flow shows.
    async function goToReport(ctx) {
        try {
            const res = await fetch(ENSURE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({
                    careerName: ctx.career || '',
                    country: ctx.country || 'India',
                    language: ctx.language || 'English',
                }),
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.ok && data.redirect) {
                window.location.href = data.redirect;
            } else {
                const msg = (data && data.error) || 'That career is not available yet.';
                updateState({ error: msg, data: null, loading: false });
                showToast(msg);
            }
        } catch (err) {
            showToast('Something went wrong. Please try again.');
        }
    }

    // --- LEAD CAPTURE POPUP ---
    // A single blurred-backdrop popup captures name/email/phone, records the
    // lead server-side, then flips to a "thank you" state. When the server
    // returns a report URL (detail pages enabled) it goes on to open the report.
    const gate = document.getElementById('cl-idx-gate');
    const leadForm = document.getElementById('cl-idx-form');
    const leadFormState = gate && gate.querySelector('[data-idx-form-state]');
    const leadSuccessState = gate && gate.querySelector('[data-idx-success-state]');
    const leadError = document.getElementById('cl-idx-error');
    const leadSubmit = document.getElementById('cl-idx-submit');
    const careerLabel = gate && gate.querySelector('[data-idx-career]');
    const redirectNote = gate && gate.querySelector('[data-idx-redirect-note]');
    const successAction = gate && gate.querySelector('[data-idx-success-action]');
    let leadContext = {};

    function setSubmitLabel(text) {
        const l = leadSubmit && leadSubmit.querySelector('.cl-idx-submit-label');
        if (l) l.textContent = text;
    }

    function openLeadGate(ctx) {
        if (!gate) return;
        leadContext = ctx || {};
        if (careerLabel) careerLabel.textContent = leadContext.career || 'this career';
        if (leadFormState) leadFormState.style.display = '';
        if (leadSuccessState) leadSuccessState.style.display = 'none';
        if (leadError) leadError.classList.remove('is-shown');
        if (leadForm) leadForm.reset();
        if (leadSubmit) leadSubmit.disabled = false;
        setSubmitLabel('Submit details');
        gate.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        const first = document.getElementById('cl-idx-name');
        if (first) setTimeout(() => first.focus(), 50);
    }
    window.openLeadGate = openLeadGate;

    function closeLeadGate() {
        if (!gate) return;
        gate.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    function showLeadError(msg) {
        if (leadError) { leadError.textContent = msg; leadError.classList.add('is-shown'); }
    }

    const confettiHost = gate && gate.querySelector('[data-idx-confetti]');
    const CONFETTI_COLORS = ['#4f46e5', '#7c3aed', '#16a34a', '#f59e0b', '#ec4899', '#0ea5e9'];

    function spawnConfetti() {
        if (!confettiHost) return;
        confettiHost.innerHTML = '';
        const pieces = 26;
        for (let i = 0; i < pieces; i++) {
            const el = document.createElement('span');
            const left = Math.random() * 100;
            const drift = (Math.random() * 120 - 60).toFixed(0) + 'px';
            const spin = (Math.random() * 520 + 200).toFixed(0) + 'deg';
            const delay = (Math.random() * 0.25).toFixed(2) + 's';
            const duration = (1.1 + Math.random() * 0.7).toFixed(2) + 's';
            el.style.left = left + '%';
            el.style.background = CONFETTI_COLORS[i % CONFETTI_COLORS.length];
            el.style.setProperty('--cl-drift', drift);
            el.style.setProperty('--cl-spin', spin);
            el.style.animationDelay = delay;
            el.style.animationDuration = duration;
            if (i % 3 === 0) el.style.borderRadius = '50%';
            confettiHost.appendChild(el);
        }
    }

    function replayEntranceAnimations() {
        if (!leadSuccessState) return;
        const animated = leadSuccessState.querySelectorAll('.cl-idx-badge-ring, .cl-idx-check, .cl-idx-check svg path, h2, p, #cl-idx-success-action');
        animated.forEach((node) => {
            node.style.animation = 'none';
            void node.offsetWidth;
            node.style.animation = '';
        });
    }

    function showLeadSuccess(redirect) {
        if (leadFormState) leadFormState.style.display = 'none';
        if (leadSuccessState) leadSuccessState.style.display = 'block';
        replayEntranceAnimations();
        spawnConfetti();

        if (redirect && DETAIL_ENABLED) {
            if (redirectNote) redirectNote.style.display = 'flex';
            if (successAction) {
                successAction.textContent = 'View career report →';
                successAction.onclick = () => { window.location.href = redirect; };
            }
            setTimeout(() => { window.location.href = redirect; }, 1800);
        } else {
            if (redirectNote) redirectNote.style.display = 'none';
            if (successAction) {
                successAction.textContent = 'Explore more careers';
                successAction.onclick = () => closeLeadGate();
            }
        }
    }

    if (gate && leadForm) {
        gate.addEventListener('click', (e) => {
            if (e.target === gate || (e.target.closest && e.target.closest('[data-idx-close]'))) closeLeadGate();
        });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeLeadGate(); });

        leadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (leadError) leadError.classList.remove('is-shown');

            const name = (document.getElementById('cl-idx-name').value || '').trim();
            const email = (document.getElementById('cl-idx-email').value || '').trim();
            const phone = (document.getElementById('cl-idx-phone').value || '').trim();

            if (!name || !email || !phone) { showLeadError('Please fill in your name, email and phone.'); return; }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showLeadError('Please enter a valid email address.'); return; }

            leadSubmit.disabled = true;
            setSubmitLabel('Submitting…');

            try {
                const res = await fetch(LEAD_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        name, email, phone,
                        career: leadContext.career || '',
                        country: leadContext.country || '',
                        language: leadContext.language || '',
                    }),
                });
                if (!res.ok) throw new Error('Request failed');
                let data = {};
                try { data = await res.json(); } catch (_) {}
                try { sessionStorage.setItem('cl_lead_captured', '1'); } catch (_) {}
                showLeadSuccess(data.redirect || null);
            } catch (err) {
                leadSubmit.disabled = false;
                setSubmitLabel('Submit details');
                showLeadError('Something went wrong. Please try again.');
            }
        });
    }

    // Arriving back here after submitting on a report page: show the thank-you
    // confirmation popup (the report itself is never unlocked).
    try {
        if (sessionStorage.getItem('cl_show_thanks') === '1') {
            sessionStorage.removeItem('cl_show_thanks');
            openLeadGate({});
            showLeadSuccess(null);
        }
    } catch (e) {}

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
            <p>${escapeHtml(error)}</p>
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
                value="${escapeHtml(params.careerName)}"
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

            <!-- Submit Button -->
            <button
                type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold h-16 px-8 rounded-xl transition-all transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2 shadow-lg shadow-indigo-200 w-full md:w-auto text-lg"
            >
                <span>Explore</span>
                <span style="width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">${ICONS.sparkles.replace('width="24" height="24"', 'width="20" height="20"')}</span>
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
        document.querySelectorAll('nav, .stripe-site-header').forEach(function(nav) {
            nav.style.display = 'none';
        });
    }
</script>
@endsection
