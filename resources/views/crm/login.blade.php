<!doctype html>
<html lang="en" class="crm-css-pending">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>CRM sign in · One Degree Advisory</title>
    <style>html.crm-css-pending{background:#17182a}html.crm-css-pending body{visibility:hidden!important;opacity:0!important}html.crm-css-pending:before{content:"Loading CRM…";position:fixed;inset:0;display:grid;place-items:center;color:rgba(255,255,255,.72);font:600 13px/1.4 system-ui,sans-serif;letter-spacing:.08em;text-transform:uppercase}</style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500..700&family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link id="crmThemeStylesheet" rel="stylesheet" href="{{ asset('assets/crm/crm.css') }}?v={{ filemtime(public_path('assets/crm/crm.css')) }}" data-classic-href="{{ asset('assets/crm/crm-classic.css') }}?v={{ filemtime(public_path('assets/crm/crm-classic.css')) }}" data-evergreen-href="{{ asset('assets/crm/crm.css') }}?v={{ filemtime(public_path('assets/crm/crm.css')) }}" data-orbit-href="{{ asset('assets/crm/crm-orbit.css') }}?v={{ filemtime(public_path('assets/crm/crm-orbit.css')) }}">
    <script>
        (() => {
            let theme = 'evergreen';
            try {
                const savedTheme = localStorage.getItem('crmTheme');
                theme = ['classic', 'evergreen', 'orbit'].includes(savedTheme) ? savedTheme : 'evergreen';
            } catch (error) {}
            document.documentElement.dataset.crmTheme = theme;
            const stylesheet = document.getElementById('crmThemeStylesheet');
            const selectedHref = theme === 'classic' ? stylesheet.dataset.classicHref : (theme === 'orbit' ? stylesheet.dataset.orbitHref : stylesheet.dataset.evergreenHref);
            if (stylesheet.href !== selectedHref) {
                stylesheet.dataset.crmThemeLoading = 'true';
                const finishThemeLoad = () => delete stylesheet.dataset.crmThemeLoading;
                stylesheet.addEventListener('load', finishThemeLoad, { once: true });
                stylesheet.addEventListener('error', finishThemeLoad, { once: true });
                stylesheet.href = selectedHref;
            }
        })();
    </script>
    <noscript><style>html.crm-css-pending body{visibility:visible!important;opacity:1!important}html.crm-css-pending:before{display:none}</style></noscript>
    <link id="crmThemeSwitcherStylesheet" rel="stylesheet" href="{{ asset('assets/crm/crm-theme-switcher.css') }}?v={{ filemtime(public_path('assets/crm/crm-theme-switcher.css')) }}">
    <link id="crmToastStylesheet" rel="stylesheet" href="{{ asset('assets/crm/crm-toast.css') }}?v={{ filemtime(public_path('assets/crm/crm-toast.css')) }}">
    <script>
        (() => {
            const root = document.documentElement;
            const stylesheets = ['crmThemeStylesheet', 'crmThemeSwitcherStylesheet', 'crmToastStylesheet']
                .map(id => document.getElementById(id))
                .filter(Boolean);
            let revealed = false;
            const reveal = () => {
                if (revealed) return;
                revealed = true;
                root.classList.remove('crm-css-pending');
                root.classList.add('crm-css-ready');
            };
            const isReady = link => link.dataset.crmThemeLoading !== 'true' && Boolean(link.sheet);
            const ready = link => isReady(link)
                ? Promise.resolve()
                : new Promise(resolve => {
                    let settled = false;
                    const done = () => {
                        if (settled) return;
                        settled = true;
                        resolve();
                    };
                    link.addEventListener('load', done, { once: true });
                    link.addEventListener('error', done, { once: true });
                    queueMicrotask(() => {
                        if (isReady(link)) done();
                    });
                });

            Promise.all(stylesheets.map(ready)).then(() => requestAnimationFrame(() => requestAnimationFrame(reveal)));
            window.setTimeout(reveal, 5000);
        })();
    </script>
</head>
<body>
@include('crm.partials.toasts', [
    'errorMessage' => $errors->first(),
    'infoMessage' => session('debug_otp') ? 'Local test OTP: '.session('debug_otp') : null,
])
<main class="crm-login">
    <section class="login-panel">
        <div class="login-card auth-step">
            <div class="login-brand">
                <span class="brand-mark"><img src="{{ asset('assets/Logo/mark-light.svg') }}" alt=""></span>
                <span class="brand-copy"><strong>One Degree Advisory</strong><span>Lead management workspace</span></span>
            </div>

            <span class="eyebrow">Secure team access</span>
            <h1>{{ session('otp_sent') || $errors->has('otp') ? 'Verify your number' : 'Welcome to your CRM' }}</h1>
            <p class="login-intro">
                @if(session('otp_sent') || $errors->has('otp'))
                    @php($otpDelivery = (array) session('crm_otp_delivery', []))
                    Enter the six-digit code sent securely to your registered {{ in_array('sms', $otpDelivery, true) && in_array('email', $otpDelivery, true) ? 'email and mobile number' : (in_array('sms', $otpDelivery, true) ? 'mobile number' : 'email address') }}.
                @else
                    Sign in using the mobile number registered by your super admin.
                @endif
            </p>

            @if(session('otp_sent') || $errors->has('otp'))
                <form method="post" action="{{ route('crm.otp.verify') }}" data-transition-form data-transition-label="Opening your workspace…">
                    @csrf
                    <div class="field">
                        <label for="otp">One-time password</label>
                        <div class="input-wrap"><input class="otp-input" id="otp" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" placeholder="••••••" autofocus required></div>
                    </div>
                    <button class="btn btn-navy btn-block" type="submit">Verify and sign in <span aria-hidden="true">→</span></button>
                </form>
                <div class="login-meta">
                    <span>Code expires in {{ config('crm.otp.ttl_minutes') }} minutes</span>
                    <a href="{{ route('crm.login') }}" data-transition-link data-transition-label="Changing mobile number…">Use another number</a>
                </div>
            @else
                <form method="post" action="{{ route('crm.otp.request') }}" data-transition-form data-transition-label="Sending your secure OTP…">
                    @csrf
                    <div class="field">
                        <label for="phone">Registered mobile number</label>
                        <div class="input-wrap">
                            <span class="phone-code">+91</span>
                            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" inputmode="tel" autocomplete="tel" placeholder="98765 43210" autofocus required>
                        </div>
                    </div>
                    <button class="btn btn-navy btn-block" type="submit">Send secure OTP <span aria-hidden="true">→</span></button>
                </form>
                <div class="login-meta"><span>Only authorised team members can sign in</span><span>OTP protected</span></div>
            @endif
        </div>
    </section>
    <aside class="login-visual">
        <div class="login-graphic" aria-hidden="true">
            <span class="orbit orbit-one"><i></i></span>
            <span class="orbit orbit-two"><i></i></span>
            <span class="orbit orbit-three"><i></i></span>
            <span class="graphic-node node-lead">Lead</span>
            <span class="graphic-node node-follow">Follow-up</span>
            <span class="graphic-node node-enrolled">Enrolled</span>
            <span class="graphic-line line-one"></span>
            <span class="graphic-line line-two"></span>
        </div>
        <div class="visual-content">
            <span class="eyebrow crm-login-visual-eyebrow">One workspace, complete visibility</span>
            <h2>Turn every enquiry into a well-managed journey.</h2>
            <p>Keep your admissions team aligned with clear ownership, timely follow-ups, detailed student profiles and a complete activity trail.</p>
            <div class="visual-grid">
                <div class="visual-card"><b>Never miss a follow-up</b><span>Advance and due-day reminders keep every conversation moving.</span></div>
                <div class="visual-card"><b>Clear accountability</b><span>Assign, transfer and audit every lead from first touch to enrolment.</span></div>
                <div class="visual-card"><b>One student timeline</b><span>Calls, comments, status updates and milestones stay together.</span></div>
                <div class="visual-card"><b>Role-based privacy</b><span>Counsellors see their own portfolio; super admins see the whole team.</span></div>
            </div>
        </div>
    </aside>
</main>
<div class="transition-screen" id="transitionScreen" aria-hidden="true">
    <div class="transition-card">
        <span class="transition-logo"><img src="{{ asset('assets/Logo/mark-light.svg') }}" alt=""></span>
        <span class="transition-rings" aria-hidden="true"><i></i><i></i><i></i></span>
        <strong data-transition-copy>Securing your workspace…</strong>
        <small>One Degree Lead CRM</small>
    </div>
</div>
<script src="{{ asset('assets/crm/crm.js') }}?v={{ filemtime(public_path('assets/crm/crm.js')) }}" defer></script>
</body>
</html>
