<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign in · {{ config('site.name') }} CMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root { --teal: #ef6c1a; --teal-dark: #cf550c; --ink: #14253e; --muted: #6a7686; --line: #e5e8ee; --danger: #c0392b; }
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; display: grid; grid-template-columns: 1fr 1fr;
      font-family: "Manrope", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; color: var(--ink); }

    /* Left brand panel */
    .aside { position: relative; overflow: hidden; padding: 56px 52px; color: #eaf0f7;
      background: linear-gradient(160deg, #0e1f3d 0%, #1d3a6b 70%, #ef6c1a 170%); display: flex; flex-direction: column; }
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
    input:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(239,108,26,.13); }
    button { margin-top: 18px; width: 100%; padding: 12px; border: 0; border-radius: 11px; cursor: pointer;
      background: var(--teal); color: #fff; font-weight: 800; font-size: .96rem; font-family: inherit;
      box-shadow: 0 8px 20px rgba(239,108,26,.3); transition: background .15s; }
    button:hover { background: var(--teal-dark); }
    .err { display: flex; gap: 9px; background: #fdecea; border: 1px solid #f5c6c0; color: var(--danger);
      padding: 11px 14px; border-radius: 11px; margin-bottom: 18px; font-size: .88rem; font-weight: 600; }
    @media (max-width: 760px) { body { grid-template-columns: 1fr; } .aside { display: none; } }
  </style>
</head>
<body>
  <aside class="aside">
    <div class="aside-brand">
      <img src="{{ asset('assets/Logo/mark.svg') }}" alt="">
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
      <input id="password" type="password" name="password" autofocus required placeholder="Enter your password">
      <button type="submit">Sign in →</button>
    </form>
  </main>
</body>
</html>
