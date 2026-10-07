<!doctype html>
<html lang="en">
<head>
    @include('student.partials.auth-head', ['title' => 'Student sign in'])
</head>
<body class="sp-body">
@include('partials.glass-lens')
@include('crm.partials.toasts', [
    'successMessage' => session('status'),
    'errorMessage' => $errors->first(),
])
<main class="sp-auth">
    <section class="sp-panel">
        <div class="sp-card">
            <a class="sp-brand" href="{{ url('/') }}">
                <img class="logo-light" src="{{ asset('assets/Logo/mark.svg') }}" alt="One Degree Advisory">
                <img class="logo-dark" src="{{ asset('assets/Logo/mark-light.svg') }}" alt="">
                <span><b>One Degree</b><small>Student portal</small></span>
            </a>

            <span class="sp-eyebrow">Your study abroad journey</span>
            <h1>Welcome <em>back</em></h1>
            <p class="sp-intro">Sign in with the email address and password your counsellor gave you when they set up your journey planner.</p>

            <form method="post" action="{{ route('student.login.attempt') }}" class="sp-form" data-transition-form data-transition-label="Opening your journey…">
                @csrf
                <div class="sp-field">
                    <label for="email">Email address</label>
                    <div class="sp-input">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/></svg>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" placeholder="name@domain.com" autofocus required>
                    </div>
                </div>
                <div class="sp-field">
                    <label for="password">Password</label>
                    <div class="sp-input">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/></svg>
                        <input id="password" name="password" type="password" autocomplete="current-password" placeholder="••••••••" required>
                        <button type="button" class="sp-reveal" data-sp-reveal="password" aria-label="Show password" aria-pressed="false">
                            <svg class="eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-off" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18M10.6 5.6A9.7 9.7 0 0 1 12 5.5C18 5.5 21.5 12 21.5 12a17 17 0 0 1-3 3.8M6.2 6.9C3.9 8.6 2.5 12 2.5 12S6 18.5 12 18.5c1.6 0 3-.4 4.2-1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                        </button>
                    </div>
                </div>
                <button class="sp-btn" type="submit"><span>Sign in</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
            </form>

            <div class="sp-meta">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5h.01"/></svg>
                <span>Forgot your password? Ask your counsellor for a new one.</span>
            </div>
            <div class="sp-trust" aria-hidden="true">
                <span><svg viewBox="0 0 24 24"><path d="M12 3 5 6v5c0 4.4 3 8.3 7 9.5 4-1.2 7-5.1 7-9.5V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg>Private to you</span>
                <span><svg viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5"/></svg>Updates live</span>
                <span><svg viewBox="0 0 24 24"><path d="M17 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20"/><circle cx="10" cy="7.5" r="3.5"/><path d="M21 20v-1.5a4 4 0 0 0-3-3.9M16 4.1a3.5 3.5 0 0 1 0 6.8"/></svg>Your counsellor sees it too</span>
            </div>
        </div>
    </section>

    @include('student.partials.auth-visual', [
        'eyebrow' => 'Everything in one place',
        'lead' => 'Your journey abroad,',
        'gold' => 'one degree',
        'tail' => 'at a time.',
        'text' => "See what's done, what's next and what's due, from your first consultation to the day you fly out.",
    ])
</main>
@include('student.partials.auth-transition', ['label' => 'Opening your journey…'])
</body>
</html>
