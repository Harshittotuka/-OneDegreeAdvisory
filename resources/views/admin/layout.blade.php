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
      /* Materio-style light theme, original Materio purple accent (#666cff) */
      --bg: #f4f5fa; --panel: #ffffff; --ink: #2b2c40; --muted: #6e6b7b;
      --line: #e9e9f0; --teal: #666cff; --teal-dark: #5256e0; --teal-soft: #ebecff;
      --danger: #ea5455; --amber: #9a6b00;
      --sidebar: #ffffff; --sidebar-line: #ececf3;
      --side-ink: #5d586c; --side-label: #a8a5b5;
      --radius: 12px; --shadow: 0 4px 18px rgba(43,44,64,.07);
      --shadow-lg: 0 10px 34px rgba(43,44,64,.12);
      --sidebar-w: 260px;
    }
    * { box-sizing: border-box; }
    html, body { margin: 0; }
    body { font-family: "Manrope", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
      background: var(--bg); color: var(--ink); line-height: 1.5; -webkit-font-smoothing: antialiased; }
    a { color: var(--teal); text-decoration: none; }

    /* ── Shell ── */
    .cms-shell { display: grid; grid-template-columns: var(--sidebar-w) 1fr; min-height: 100vh; }
    .cms-scrim { display: none; }

    /* ── Sidebar (light, Materio) ── */
    .cms-side { position: sticky; top: 0; align-self: start; height: 100vh; display: flex; flex-direction: column;
      background: var(--sidebar); color: var(--side-ink); border-right: 1px solid var(--sidebar-line); overflow-y: auto; }
    .cms-side-brand { display: flex; align-items: center; gap: 11px; padding: 22px 22px 16px; }
    .cms-side-brand .mark { display: grid; place-items: center; width: 38px; height: 38px; border-radius: 10px;
      background: linear-gradient(135deg, var(--teal), #9094ff); box-shadow: 0 6px 16px rgba(102,108,255,.35); }
    .cms-side-brand .mark img { width: 22px; height: 22px; filter: brightness(0) invert(1); }
    .cms-side-brand b { color: var(--ink); font-size: 1.06rem; font-weight: 800; letter-spacing: .04em; line-height: 1.1; text-transform: uppercase; }
    .cms-side-brand span { display: block; font-size: .7rem; color: var(--muted); font-weight: 600; letter-spacing: .02em; text-transform: none; }
    .cms-nav { padding: 4px 14px 14px; flex: 1; }
    .cms-nav-label { padding: 18px 10px 7px; font-size: .67rem; font-weight: 800; letter-spacing: .1em;
      text-transform: uppercase; color: var(--side-label); }
    .cms-nav-item { position: relative; display: flex; align-items: center; gap: 12px; padding: 10px 12px; margin: 3px 0;
      border-radius: 9px; color: var(--side-ink); font-weight: 600; font-size: .92rem;
      transition: background .15s, color .15s, transform .12s; }
    .cms-nav-item:hover { background: #f6f5fb; color: var(--ink); transform: translateX(2px); }
    .cms-nav-item.is-active { background: linear-gradient(72deg, var(--teal) 0%, #9094ff 100%); color: #fff;
      box-shadow: 0 5px 14px rgba(102,108,255,.45); }
    .cms-nav-item.is-active:hover { transform: none; color: #fff; }
    .cms-nav-item i { width: 19px; height: 19px; flex-shrink: 0; }
    .cms-nav-item.is-soon { opacity: .55; cursor: default; }
    .cms-nav-item.is-soon:hover { background: none; color: var(--side-ink); transform: none; }
    .cms-nav-soon { margin-left: auto; font-size: .58rem; font-weight: 800; letter-spacing: .06em;
      background: #efeef5; color: #9b97a8; padding: 3px 8px; border-radius: 999px; }
    .cms-side-foot { padding: 14px 18px; border-top: 1px solid var(--sidebar-line); }
    .cms-side-foot form { margin: 0; }
    .cms-side-foot button { width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;
      background: #f6f5fb; color: var(--side-ink); border: 1px solid var(--sidebar-line); border-radius: 9px;
      padding: 11px; font-family: inherit; font-weight: 700; font-size: .86rem; cursor: pointer; transition: background .15s, color .15s; }
    .cms-side-foot button:hover { background: #ebecff; color: var(--teal-dark); border-color: #cdd0ff; }

    /* ── Main ── */
    .cms-main { min-width: 0; display: flex; flex-direction: column; }
    .cms-topbar { position: sticky; top: 14px; z-index: 20; display: flex; align-items: center; gap: 14px;
      margin: 14px 24px 0; padding: 0 18px; height: 62px; background: rgba(255,255,255,.9);
      backdrop-filter: blur(10px); border: 1px solid var(--line); border-radius: 12px; box-shadow: var(--shadow); }
    .cms-topbar h1 { margin: 0; font-size: 1.08rem; font-weight: 800; letter-spacing: -.01em; white-space: nowrap; }
    .cms-topbar-spacer { flex: 1; }
    .cms-topbar a.cms-viewsite { display: inline-flex; align-items: center; gap: 7px; color: var(--muted);
      font-weight: 700; font-size: .86rem; padding: 8px 12px; border-radius: 9px; transition: background .15s, color .15s; }
    .cms-topbar a.cms-viewsite:hover { color: var(--teal); background: var(--teal-soft); }
    .cms-burger { display: none; background: none; border: 0; cursor: pointer; color: var(--ink); }

    .cms-wrap { max-width: 1180px; width: 100%; margin: 0 auto; padding: 24px 24px 90px; }

    .cms-flash { display: flex; align-items: center; gap: 10px; background: var(--teal-soft); border: 1px solid #cdd0ff;
      color: var(--teal-dark); padding: 13px 16px; border-radius: 11px; margin-bottom: 20px; font-weight: 700; }
    .cms-errors { background: #fdecea; border: 1px solid #f5c6c0; color: var(--danger);
      padding: 13px 16px; border-radius: 11px; margin-bottom: 20px; }
    .cms-errors ul { margin: 0; padding-left: 18px; }

    /* ── Utility classes (consumed by index/form/dashboard pages) ── */
    .btn { display: inline-flex; align-items: center; gap: 8px; cursor: pointer; border: 1px solid transparent;
      border-radius: 10px; padding: 10px 16px; font-size: .9rem; font-weight: 700; font-family: inherit;
      transition: background .15s, border-color .15s, color .15s, transform .12s, box-shadow .15s; }
    .btn:active { transform: translateY(1px); }
    .btn-primary { background: var(--teal); color: #fff; box-shadow: 0 6px 16px rgba(102,108,255,.28); }
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
    input:focus, textarea:focus, select:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(102,108,255,.13); }
    .field { margin-bottom: 16px; }
    .hint { font-weight: 500; color: var(--muted); font-size: .78rem; margin-top: 5px; }

    /* ── Toast notifications (top-right) ── */
    .cms-toasts { position: fixed; top: 16px; right: 16px; z-index: 1000; display: flex; flex-direction: column; gap: 10px; }
    .cms-toast { display: flex; align-items: center; gap: 10px; background: #fff; border: 1px solid var(--line);
      border-left: 4px solid var(--teal); box-shadow: 0 14px 34px rgba(43,44,64,.2); border-radius: 11px;
      padding: 13px 16px; font-weight: 700; font-size: .9rem; color: var(--ink); min-width: 230px; max-width: 360px;
      transform: translateX(120%); opacity: 0; transition: transform .3s ease, opacity .3s ease; }
    .cms-toast.show { transform: translateX(0); opacity: 1; }
    .cms-toast.error { border-left-color: var(--danger); color: var(--danger); }
    .cms-toast i { width: 18px; height: 18px; color: var(--teal); flex-shrink: 0; }
    .cms-toast.error i { color: var(--danger); }

    @media (max-width: 880px) {
      .cms-shell { grid-template-columns: 1fr; }
      .cms-side { position: fixed; z-index: 60; width: var(--sidebar-w); transform: translateX(-100%);
        transition: transform .25s ease; box-shadow: 0 0 40px rgba(0,0,0,.25); }
      .cms-shell.is-open .cms-side { transform: translateX(0); }
      .cms-burger { display: inline-flex; }
      .cms-topbar { margin: 12px 16px 0; }
      .cms-wrap { padding: 20px 16px 80px; }
      .cms-scrim { display: none; position: fixed; inset: 0; background: rgba(8,20,26,.45); z-index: 50; }
      .cms-shell.is-open .cms-scrim { display: block; }
    }
  </style>
  @stack('head')
</head>
<body>
  @php
    $current = \Illuminate\Support\Facades\Route::currentRouteName() ?? '';
    $navGroups = [
      ['label' => '', 'items' => [
        ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard'],
      ]],
      ['label' => 'Content', 'items' => [
        ['label' => 'Home Page', 'icon' => 'panel-top', 'route' => 'admin.home-hero.live', 'match' => 'admin.home-hero'],
        ['label' => 'Blog Posts', 'icon' => 'newspaper', 'route' => 'admin.blog.index', 'match' => 'admin.blog'],
        ['label' => 'About Page', 'icon' => 'layout-template', 'route' => 'admin.about.index', 'match' => 'admin.about'],
        ['label' => 'Notification Bar', 'icon' => 'megaphone', 'route' => 'admin.notice-bar.index', 'match' => 'admin.notice-bar'],
        ['label' => 'Sync non-MBBS countries', 'icon' => 'globe', 'route' => 'admin.country-sync.index', 'match' => 'admin.country-sync'],
      ]],
      ['label' => 'Coming soon', 'items' => [
        ['label' => 'Settings', 'icon' => 'settings', 'soon' => true],
      ]],
    ];
  @endphp

  <div class="cms-shell" id="cms-shell">
    <div class="cms-scrim" data-close></div>

    <aside class="cms-side">
      <div class="cms-side-brand">
        <span class="mark"><img src="{{ asset('assets/Logo/mark.svg') }}" alt=""></span>
        <b>{{ config('site.name') }}<span>Content Studio</span></b>
      </div>

      <nav class="cms-nav">
        @foreach($navGroups as $group)
          @if(! empty($group['label']))<div class="cms-nav-label">{{ $group['label'] }}</div>@endif
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
