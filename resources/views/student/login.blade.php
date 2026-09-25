<!doctype html>
<html lang="en">
<head>
    @include('student.partials.auth-head', ['title' => 'Student sign in'])
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

            <span class="eyebrow">Your study abroad journey</span>
            <h1>Welcome back</h1>
            <p class="login-intro">Sign in with the email address and password your counsellor gave you when they set up your journey planner.</p>

            <form method="post" action="{{ route('student.login.attempt') }}" data-transition-form data-transition-label="Opening your journey…">
                @csrf
                <div class="field">
                    <label for="email">Email address</label>
                    <div class="input-wrap"><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" placeholder="name@domain.com" autofocus required></div>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="input-wrap"><input id="password" name="password" type="password" autocomplete="current-password" placeholder="••••••••" required></div>
                </div>
                <button class="btn btn-navy btn-block" type="submit">Sign in <span aria-hidden="true">→</span></button>
            </form>
            <div class="login-meta"><span>Forgot your password? Ask your counsellor for a new one.</span></div>
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
            <span class="eyebrow crm-login-visual-eyebrow">Everything in one place</span>
            <h2>Your whole journey abroad, one step at a time.</h2>
            <p>See what's done, what's next and what's due, from your first consultation to the day you fly out.</p>
            <div class="visual-grid">
                <div class="visual-card"><b>Your next steps</b><span>The tasks that are yours, earliest first.</span></div>
                <div class="visual-card"><b>Every university</b><span>A checklist for each application, through to the visa.</span></div>
                <div class="visual-card"><b>Deadlines</b><span>Every date in one list, with anything late flagged.</span></div>
                <div class="visual-card"><b>Tick things off</b><span>Mark a task done and your counsellor sees it straight away.</span></div>
            </div>
        </div>
    </aside>
</main>
<div class="transition-screen" id="transitionScreen" aria-hidden="true">
    <div class="transition-card">
        <span class="transition-logo"><img src="{{ asset('assets/Logo/mark-light.svg') }}" alt=""></span>
        <span class="transition-rings" aria-hidden="true"><i></i><i></i><i></i></span>
        <strong data-transition-copy>Opening your journey…</strong>
        <small>One Degree student portal</small>
    </div>
</div>
<script src="{{ asset('assets/crm/crm.js') }}?v={{ filemtime(public_path('assets/crm/crm.js')) }}" defer></script>
</body>
</html>
