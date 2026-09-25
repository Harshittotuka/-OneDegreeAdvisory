<!doctype html>
<html lang="en">
<head>
    @include('student.partials.auth-head', ['title' => $account->must_change_password ? 'Choose your password' : 'Change password'])
</head>
<body>
@include('crm.partials.toasts', [
    'successMessage' => session('status'),
    'errorMessage' => $errors->first(),
])
<main class="crm-login">
    <section class="login-panel">
        <div class="login-card auth-step">
            <div class="login-brand">
                <span class="brand-mark"><img src="{{ asset('assets/Logo/mark-light.svg') }}" alt=""></span>
                <span class="brand-copy"><strong>One Degree Advisory</strong><span>Student portal</span></span>
            </div>

            @if($account->must_change_password)
                <span class="eyebrow">One quick step</span>
                <h1>Choose your own password</h1>
                <p class="login-intro">You signed in with the temporary password from your counsellor. Pick a new one that only you know. It needs at least 8 characters, with letters and numbers.</p>
            @else
                <span class="eyebrow">Your account</span>
                <h1>Change your password</h1>
                <p class="login-intro">Signed in as {{ $account->email }}. Your new password needs at least 8 characters, with letters and numbers.</p>
            @endif

            <form method="post" action="{{ route('student.password.update') }}" data-transition-form data-transition-label="Saving your password…">
                @csrf
                <div class="field">
                    <label for="current_password">{{ $account->must_change_password ? 'Temporary password' : 'Current password' }}</label>
                    <div class="input-wrap"><input id="current_password" name="current_password" type="password" autocomplete="current-password" required autofocus></div>
                </div>
                <div class="field">
                    <label for="password">New password</label>
                    <div class="input-wrap"><input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required></div>
                </div>
                <div class="field">
                    <label for="password_confirmation">Type the new password again</label>
                    <div class="input-wrap"><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required></div>
                </div>
                <button class="btn btn-navy btn-block" type="submit">Save password <span aria-hidden="true">→</span></button>
            </form>
            <div class="login-meta">
                @unless($account->must_change_password)<a href="{{ route('student.dashboard') }}">Back to my journey</a>@endunless
                <form method="post" action="{{ route('student.logout') }}" style="display:inline">@csrf<button type="submit" style="background:none;border:none;color:inherit;font:inherit;text-decoration:underline;cursor:pointer;padding:0">Sign out</button></form>
            </div>
        </div>
    </section>
    <aside class="login-visual">
        <div class="login-graphic" aria-hidden="true">
            <span class="orbit orbit-one"><i></i></span>
            <span class="orbit orbit-two"><i></i></span>
            <span class="orbit orbit-three"><i></i></span>
            <span class="graphic-node node-lead">Shortlist</span>
            <span class="graphic-node node-follow">Apply</span>
            <span class="graphic-node node-enrolled">Fly out</span>
            <span class="graphic-line line-one"></span>
            <span class="graphic-line line-two"></span>
        </div>
        <div class="visual-content">
            <span class="eyebrow crm-login-visual-eyebrow">Keep your account safe</span>
            <h2>Your plan is private to you.</h2>
            <p>Don't share your password. If you think someone else knows it, change it here or ask your counsellor to reset it.</p>
        </div>
    </aside>
</main>
<div class="transition-screen" id="transitionScreen" aria-hidden="true">
    <div class="transition-card">
        <span class="transition-logo"><img src="{{ asset('assets/Logo/mark-light.svg') }}" alt=""></span>
        <span class="transition-rings" aria-hidden="true"><i></i><i></i><i></i></span>
        <strong data-transition-copy>Saving…</strong>
        <small>One Degree student portal</small>
    </div>
</div>
<script src="{{ asset('assets/crm/crm.js') }}?v={{ filemtime(public_path('assets/crm/crm.js')) }}" defer></script>
</body>
</html>
