<!doctype html>
<html lang="en" class="crm-css-pending">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>CRM sign in · One Degree Advisory</title>
    <style>html.crm-css-pending body{visibility:hidden}</style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500..700&family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link id="crmThemeStylesheet" rel="stylesheet" href="{{ asset('assets/crm/crm.css') }}" data-classic-href="{{ asset('assets/crm/crm-classic.css') }}" data-evergreen-href="{{ asset('assets/crm/crm.css') }}" data-orbit-href="{{ asset('assets/crm/crm-orbit.css') }}">
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
            const reveal = () => document.documentElement.classList.remove('crm-css-pending');

            if (stylesheet.href === selectedHref) {
                reveal();
                return;
            }

            stylesheet.addEventListener('load', reveal, { once: true });
            stylesheet.addEventListener('error', reveal, { once: true });
            stylesheet.href = selectedHref;
        })();
    </script>
    <noscript><style>html.crm-css-pending body{visibility:visible}</style></noscript>
    <link rel="stylesheet" href="{{ asset('assets/crm/crm-theme-switcher.css') }}">
</head>
<body>
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
                {{ session('otp_sent') || $errors->has('otp') ? 'Enter the six-digit code sent to your registered mobile number.' : 'Sign in using the mobile number registered by your super admin.' }}
            </p>

            @if($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif

            @if(session('debug_otp'))
                <div class="alert alert-info"><strong>Local test OTP:</strong> {{ session('debug_otp') }}</div>
            @endif

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
                            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" inputmode="numeric" autocomplete="tel" maxlength="10" placeholder="98765 43210" autofocus required>
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
<script src="{{ asset('assets/crm/crm.js') }}" defer></script>
</body>
</html>
