<!doctype html>
<html lang="en">
<head>
    @include('student.partials.auth-head', ['title' => $account->must_change_password ? 'Choose your password' : 'Change password'])
</head>
<body class="sp-body">
@include('crm.partials.toasts', [
    'successMessage' => session('status'),
    'errorMessage' => $errors->first(),
])
<main class="sp-auth">
    <section class="sp-panel">
        <div class="sp-card">
            <a class="sp-brand" href="{{ route('student.dashboard') }}">
                <img src="{{ asset('assets/Logo/mark.svg') }}" alt="One Degree Advisory">
                <span><b>One Degree</b><small>Student portal</small></span>
            </a>

            @if($account->must_change_password)
                <span class="sp-eyebrow">One quick step</span>
                <h1>Choose your own <em>password</em></h1>
                <p class="sp-intro">You signed in with the temporary password from your counsellor. Pick a new one that only you know. It needs at least 8 characters, with letters and numbers.</p>
            @else
                <span class="sp-eyebrow">Your account</span>
                <h1>Change your <em>password</em></h1>
                <p class="sp-intro">Signed in as {{ $account->email }}. Your new password needs at least 8 characters, with letters and numbers.</p>
            @endif

            <form method="post" action="{{ route('student.password.update') }}" class="sp-form" data-transition-form data-transition-label="Saving your password…">
                @csrf
                @foreach([
                    ['current_password', $account->must_change_password ? 'Temporary password' : 'Current password', 'current-password', true],
                    ['password', 'New password', 'new-password', false],
                    ['password_confirmation', 'Type the new password again', 'new-password', false],
                ] as [$name, $label, $complete, $focus])
                    <div class="sp-field">
                        <label for="{{ $name }}">{{ $label }}</label>
                        <div class="sp-input">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/></svg>
                            <input id="{{ $name }}" name="{{ $name }}" type="password" autocomplete="{{ $complete }}" @if($name !== 'current_password') minlength="8" @endif required @if($focus) autofocus @endif>
                            <button type="button" class="sp-reveal" data-sp-reveal="{{ $name }}" aria-label="Show password" aria-pressed="false">
                                <svg class="eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-off" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18M10.6 5.6A9.7 9.7 0 0 1 12 5.5C18 5.5 21.5 12 21.5 12a17 17 0 0 1-3 3.8M6.2 6.9C3.9 8.6 2.5 12 2.5 12S6 18.5 12 18.5c1.6 0 3-.4 4.2-1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                            </button>
                        </div>
                    </div>
                @endforeach
                <button class="sp-btn" type="submit"><span>Save password</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
            </form>

            <div class="sp-meta sp-meta-links">
                @unless($account->must_change_password)<a href="{{ route('student.dashboard') }}">Back to my journey</a>@endunless
                <form method="post" action="{{ route('student.logout') }}">@csrf<button type="submit">Sign out</button></form>
            </div>
        </div>
    </section>

    @include('student.partials.auth-visual', [
        'eyebrow' => 'Keep your account safe',
        'lead' => 'Your plan is',
        'gold' => 'private',
        'tail' => 'to you.',
        'text' => "Don't share your password. If you think someone else knows it, change it here or ask your counsellor to reset it.",
    ])
</main>
@include('student.partials.auth-transition', ['label' => 'Saving…'])
</body>
</html>
