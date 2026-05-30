<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Dashboard') · {{ config('site.name') }} CMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #eef1f4; --panel: #ffffff; --ink: #14253e; --muted: #6a7686;
      --line: #e5e8ee; --teal: #ef6c1a; --teal-dark: #cf550c; --teal-soft: #fdeee2;
      --danger: #c0392b; --amber: #9a6b00;
      --sidebar: #0e1f3d; --sidebar-2: #13284c; --sidebar-line: rgba(255,255,255,.08);
      --radius: 14px; --shadow: 0 10px 30px rgba(13,33,42,.06);
      --sidebar-w: 256px;
    }
    * { box-sizing: border-box; }
    html, body { margin: 0; }
    body { font-family: "Manrope", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
      background: var(--bg); color: var(--ink); line-height: 1.5; -webkit-font-smoothing: antialiased; }
    a { color: var(--teal); text-decoration: none; }

    /* ── Shell ── */
    .cms-shell { display: grid; grid-template-columns: var(--sidebar-w) 1fr; min-height: 100vh; }
    .cms-scrim { display: none; } /* fixed overlay, only shown on mobile when open — never a grid cell */

    /* ── Sidebar ── */
    .cms-side { position: sticky; top: 0; align-self: start; height: 100vh; display: flex; flex-direction: column;
      background: linear-gradient(185deg, var(--sidebar) 0%, var(--sidebar-2) 100%); color: #c3cfe2; overflow-y: auto; }
    .cms-side-brand { display: flex; align-items: center; gap: 11px; padding: 20px 20px 18px; }
    .cms-side-brand img { width: 34px; height: 34px; }
    .cms-side-brand b { color: #fff; font-size: 1rem; font-weight: 800; letter-spacing: -.01em; line-height: 1.1; }
    .cms-side-brand span { display: block; font-size: .72rem; color: #8aa0c4; font-weight: 600; }
    .cms-nav { padding: 6px 12px; flex: 1; }
    .cms-nav-label { padding: 16px 12px 7px; font-size: .68rem; font-weight: 800; letter-spacing: .12em;
      text-transform: uppercase; color: #7186a8; }
    .cms-nav-item { display: flex; align-items: center; gap: 11px; padding: 10px 12px; margin: 2px 0;
      border-radius: 10px; color: #c3cfe2; font-weight: 600; font-size: .92rem; transition: background .15s, color .15s; }
    .cms-nav-item:hover { background: rgba(255,255,255,.05); color: #fff; }
    .cms-nav-item.is-active { background: var(--teal); color: #fff; box-shadow: 0 6px 16px rgba(239,108,26,.4); }
    .cms-nav-item i { width: 18px; height: 18px; }
    .cms-nav-item.is-soon { opacity: .5; cursor: default; }
    .cms-nav-item.is-soon:hover { background: none; color: #c3cfe2; }
    .cms-nav-soon { margin-left: auto; font-size: .62rem; font-weight: 800; letter-spacing: .08em;
      background: rgba(255,255,255,.1); color: #93a4c2; padding: 2px 7px; border-radius: 999px; }
    .cms-side-foot { padding: 14px 16px; border-top: 1px solid var(--sidebar-line); }
    .cms-side-foot form { margin: 0; }
    .cms-side-foot button { width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;
      background: rgba(255,255,255,.06); color: #c3cfe2; border: 1px solid var(--sidebar-line); border-radius: 10px;
      padding: 10px; font-family: inherit; font-weight: 700; font-size: .86rem; cursor: pointer; }
    .cms-side-foot button:hover { background: rgba(255,255,255,.12); color: #fff; }

    /* ── Main ── */
    .cms-main { min-width: 0; display: flex; flex-direction: column; }
    .cms-topbar { position: sticky; top: 0; z-index: 20; display: flex; align-items: center; gap: 16px;
      padding: 0 28px; height: 64px; background: rgba(255,255,255,.86); backdrop-filter: blur(10px);
      border-bottom: 1px solid var(--line); }
    .cms-topbar h1 { margin: 0; font-size: 1.12rem; font-weight: 800; letter-spacing: -.01em; }
    .cms-topbar-spacer { flex: 1; }
    .cms-topbar a.cms-viewsite { display: inline-flex; align-items: center; gap: 7px; color: var(--muted);
      font-weight: 700; font-size: .86rem; }
    .cms-topbar a.cms-viewsite:hover { color: var(--teal); }
    .cms-burger { display: none; background: none; border: 0; cursor: pointer; color: var(--ink); }
    .cms-wrap { max-width: 1080px; width: 100%; margin: 0 auto; padding: 28px 28px 90px; }

    .cms-flash { display: flex; align-items: center; gap: 10px; background: var(--teal-soft); border: 1px solid #f6cdaa;
      color: var(--teal-dark); padding: 13px 16px; border-radius: 11px; margin-bottom: 20px; font-weight: 700; }
    .cms-errors { background: #fdecea; border: 1px solid #f5c6c0; color: var(--danger);
      padding: 13px 16px; border-radius: 11px; margin-bottom: 20px; }
    .cms-errors ul { margin: 0; padding-left: 18px; }

    /* ── Utility classes (consumed by index/form pages) ── */
    .btn { display: inline-flex; align-items: center; gap: 8px; cursor: pointer; border: 1px solid transparent;
      border-radius: 10px; padding: 10px 16px; font-size: .9rem; font-weight: 700; font-family: inherit;
      transition: background .15s, border-color .15s, color .15s, transform .12s; }
    .btn:active { transform: translateY(1px); }
    .btn-primary { background: var(--teal); color: #fff; box-shadow: 0 6px 16px rgba(239,108,26,.28); }
    .btn-primary:hover { background: var(--teal-dark); }
    .btn-ghost { background: #fff; border-color: var(--line); color: var(--ink); }
    .btn-ghost:hover { border-color: var(--teal); color: var(--teal); }
    .btn-danger { background: #fff; border-color: #f0c4be; color: var(--danger); }
    .btn-danger:hover { background: var(--danger); color: #fff; border-color: var(--danger); }
    .btn-sm { padding: 7px 12px; font-size: .82rem; border-radius: 8px; }
    .panel { background: var(--panel); border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow); }
    label { display: block; font-weight: 700; font-size: .82rem; margin-bottom: 6px; color: var(--ink); }
    input[type=text], input[type=date], input[type=number], input[type=password], textarea, select {
      width: 100%; padding: 11px 13px; border: 1px solid var(--line); border-radius: 10px;
      font-family: inherit; font-size: .94rem; color: var(--ink); background: #fff; transition: border-color .15s, box-shadow .15s; }
    textarea { resize: vertical; min-height: 80px; }
    input:focus, textarea:focus, select:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(239,108,26,.13); }
    .field { margin-bottom: 16px; }
    .hint { font-weight: 500; color: var(--muted); font-size: .78rem; margin-top: 5px; }

    /* ── Toast notifications (top-right) ── */
    .cms-toasts { position: fixed; top: 16px; right: 16px; z-index: 1000; display: flex; flex-direction: column; gap: 10px; }
    .cms-toast { display: flex; align-items: center; gap: 10px; background: #fff; border: 1px solid var(--line);
      border-left: 4px solid var(--teal); box-shadow: 0 14px 34px rgba(13,33,42,.2); border-radius: 11px;
      padding: 13px 16px; font-weight: 700; font-size: .9rem; color: var(--ink); min-width: 230px; max-width: 360px;
      transform: translateX(120%); opacity: 0; transition: transform .3s ease, opacity .3s ease; }
    .cms-toast.show { transform: translateX(0); opacity: 1; }
    .cms-toast.error { border-left-color: var(--danger); color: var(--danger); }
    .cms-toast i { width: 18px; height: 18px; color: var(--teal); flex-shrink: 0; }
    .cms-toast.error i { color: var(--danger); }

    @media (max-width: 880px) {
      .cms-shell { grid-template-columns: 1fr; }
      .cms-side { position: fixed; z-index: 60; width: var(--sidebar-w); transform: translateX(-100%);
        transition: transform .25s ease; box-shadow: 0 0 40px rgba(0,0,0,.4); }
      .cms-shell.is-open .cms-side { transform: translateX(0); }
      .cms-burger { display: inline-flex; }
      .cms-scrim { display: none; position: fixed; inset: 0; background: rgba(8,20,26,.5); z-index: 50; }
      .cms-shell.is-open .cms-scrim { display: block; }
    }
  </style>
  @stack('head')
</head>
<body>
  @php
    $current = \Illuminate\Support\Facades\Route::currentRouteName() ?? '';
    $navGroups = [
      ['label' => 'Content', 'items' => [
        ['label' => 'Blog Posts', 'icon' => 'newspaper', 'route' => 'admin.blog.index', 'match' => 'admin.blog'],
        ['label' => 'About Page', 'icon' => 'layout-template', 'route' => 'admin.about.index', 'match' => 'admin.about'],
      ]],
      ['label' => 'Coming soon', 'items' => [
        ['label' => 'Media Library', 'icon' => 'image', 'soon' => true],
        ['label' => 'Settings', 'icon' => 'settings', 'soon' => true],
      ]],
    ];
  @endphp

  <div class="cms-shell" id="cms-shell">
    <div class="cms-scrim" data-close></div>

    <aside class="cms-side">
      <div class="cms-side-brand">
        <img src="{{ asset('assets/Logo/mark.svg') }}" alt="">
        <b>{{ config('site.name') }}<span>Content Studio</span></b>
      </div>

      <nav class="cms-nav">
        @foreach($navGroups as $group)
          <div class="cms-nav-label">{{ $group['label'] }}</div>
          @foreach($group['items'] as $item)
            @if(! empty($item['soon']))
              <span class="cms-nav-item is-soon">
                <i data-lucide="{{ $item['icon'] }}"></i>{{ $item['label'] }}
                <span class="cms-nav-soon">SOON</span>
              </span>
            @else
              <a class="cms-nav-item @if($current && str_starts_with($current, $item['match'])) is-active @endif"
                 href="{{ route($item['route']) }}">
                <i data-lucide="{{ $item['icon'] }}"></i>{{ $item['label'] }}
              </a>
            @endif
          @endforeach
        @endforeach
      </nav>

      <div class="cms-side-foot">
        <form method="POST" action="{{ route('admin.logout') }}">
          @csrf
          <button type="submit"><i data-lucide="log-out" style="width:16px;height:16px;"></i> Log out</button>
        </form>
      </div>
    </aside>

    <div class="cms-main">
      <header class="cms-topbar">
        <button class="cms-burger" data-toggle aria-label="Menu"><i data-lucide="menu"></i></button>
        <h1>@yield('title', 'Dashboard')</h1>
        <div class="cms-topbar-spacer"></div>
        <a class="cms-viewsite" href="{{ route('home') }}" target="_blank"><i data-lucide="external-link" style="width:16px;height:16px;"></i> View site</a>
      </header>

      <div class="cms-wrap">
        @if($errors->any())
          <div class="cms-errors">
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
          </div>
        @endif

        @yield('content')
      </div>
    </div>
  </div>

  <div class="cms-toasts" id="cms-toasts"></div>

  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <script>
    (function () {
      if (window.lucide) lucide.createIcons();
      const shell = document.getElementById('cms-shell');
      document.querySelectorAll('[data-toggle]').forEach(b => b.addEventListener('click', () => shell.classList.toggle('is-open')));
      document.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => shell.classList.remove('is-open')));

      // ── Toast popups (top-right) ──
      const toastWrap = document.getElementById('cms-toasts');
      window.cmsToast = function (msg, type) {
        if (!toastWrap || !msg) return;
        const t = document.createElement('div');
        t.className = 'cms-toast' + (type === 'error' ? ' error' : '');
        t.innerHTML = '<i data-lucide="' + (type === 'error' ? 'alert-circle' : 'check-circle-2') + '"></i><span></span>';
        t.querySelector('span').textContent = msg;
        toastWrap.appendChild(t);
        if (window.lucide) lucide.createIcons();
        requestAnimationFrame(() => t.classList.add('show'));
        setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 320); }, 3200);
      };
      @if(session('status'))
        window.cmsToast(@json(session('status')));
      @endif
    })();
  </script>
  @stack('scripts')
</body>
</html>
