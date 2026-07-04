<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign in · {{ config('site.name') }} CMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root { --teal: #666cff; --teal-dark: #5256e0; --ink: #14253e; --muted: #6a7686; --line: #e5e8ee; --danger: #c0392b; }
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; display: grid; grid-template-columns: 1fr 1fr;
      font-family: "Manrope", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; color: var(--ink); }

    /* Left brand panel */
    .aside { position: relative; overflow: hidden; padding: 56px 52px; color: #eaf0f7;
      background: linear-gradient(160deg, #0e1f3d 0%, #1d3a6b 70%, #666cff 170%); display: flex; flex-direction: column; }
    .aside::after { content: ""; position: absolute; width: 460px; height: 460px; right: -150px; bottom: -160px;
      background: radial-gradient(circle, rgba(255,255,255,.12), transparent 70%); border-radius: 50%; }
    .aside-brand { display: flex; align-items: center; gap: 12px; }
    .aside-brand img { width: 40px; height: 40px; }
    .aside-brand b { font-size: 1.05rem; font-weight: 800; }
    .aside-body { margin-top: auto; position: relative; z-index: 1; }
    .aside-body h2 { font-size: 2rem; line-height: 1.18; font-weight: 800; letter-spacing: -.02em; margin: 0 0 14px; }
    .aside-body p { color: #aebfd8; font-size: 1rem; max-width: 34ch; margin: 0; }

    /* Right form panel */
    .main { display: flex; align-items: center; justify-content: center; padding: 40px; background: #f6f8f9; }
    .card { width: min(380px, 100%); }
    .card h1 { font-size: 1.5rem; margin: 0 0 6px; letter-spacing: -.01em; }
    .card .sub { color: var(--muted); margin: 0 0 26px; font-size: .92rem; }
    label { display: block; font-weight: 700; font-size: .82rem; margin-bottom: 7px; }
    input { width: 100%; padding: 12px 14px; border: 1px solid var(--line); border-radius: 11px; font-size: .96rem; font-family: inherit; }
    input:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(102,108,255,.13); }
    button { margin-top: 18px; width: 100%; padding: 12px; border: 0; border-radius: 11px; cursor: pointer;
      background: var(--teal); color: #fff; font-weight: 800; font-size: .96rem; font-family: inherit;
      box-shadow: 0 8px 20px rgba(102,108,255,.3); transition: background .15s; }
    button:hover { background: var(--teal-dark); }
    .err { display: flex; gap: 9px; background: #fdecea; border: 1px solid #f5c6c0; color: var(--danger);
      padding: 11px 14px; border-radius: 11px; margin-bottom: 18px; font-size: .88rem; font-weight: 600; }

    .remember { display: flex; align-items: center; gap: 9px; margin-top: 14px; font-size: .85rem; font-weight: 600;
      color: var(--muted); cursor: pointer; user-select: none; }
    .remember input[type=checkbox] { width: 16px; height: 16px; padding: 0; margin: 0; border-radius: 4px;
      accent-color: var(--teal); cursor: pointer; flex-shrink: 0; }

    /* Powered-by credit (bottom-right of the screen) */
    .powered { position: fixed; right: 24px; bottom: 18px; z-index: 3; color: #1d3550; font-size: .82rem;
      font-weight: 800; letter-spacing: .02em; opacity: .78; text-decoration: none;
      transition: transform .18s ease, opacity .18s ease, color .18s ease; }
    .powered:hover { transform: translateY(-2px); opacity: 1; color: var(--teal-dark); }
    .powered span { position: relative; line-height: 1; }
    .powered small { font: inherit; font-weight: 600; color: var(--muted); opacity: .72; }
    .powered span::after { content: ""; position: absolute; left: 0; right: 0; bottom: -5px; height: 1px;
      background: linear-gradient(90deg, transparent, currentColor, transparent);
      opacity: .35; transform: scaleX(.72); transition: transform .18s ease, opacity .18s ease; }
    .powered:hover span::after { opacity: .7; transform: scaleX(1); }

    @media (max-width: 760px) {
      body { grid-template-columns: 1fr; }
      .aside { display: none; }
      .powered { right: 16px; bottom: 14px; font-size: .78rem; }
    }
  </style>
</head>
<body>
  <aside class="aside">
    <div class="aside-brand">
      <img src="{{ asset('assets/Logo/mark-light.svg') }}" alt="{{ config('site.name') }}">
      <b>{{ config('site.name') }}</b>
    </div>
    <div class="aside-body">
      <h2>Content Studio</h2>
      <p>Publish and manage your blog — stories, images, and more, all in one place.</p>
    </div>
  </aside>

  <main class="main">
    <form class="card" method="POST" action="{{ route('admin.login.attempt') }}">
      @csrf
      <h1>Welcome back</h1>
      <p class="sub">Sign in to manage your content.</p>
      @if($errors->any())
        <div class="err">{{ $errors->first() }}</div>
      @endif
      <label for="password">Password</label>
      <input id="password" type="password" name="password" autofocus required placeholder="Enter your password" autocomplete="current-password">
      <label class="remember"><input type="checkbox" name="remember" value="1" checked> Keep me signed in for 30 days</label>
      <button type="submit">Sign in →</button>
    </form>
  </main>

  <a class="powered" href="https://infolith.in/" target="_blank" rel="noopener">
    <span><small>Powered by</small> Infolith</span>
  </a>
</body>
</html>
