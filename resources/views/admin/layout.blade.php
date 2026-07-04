<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="robots" content="noindex, nofollow">
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
    /* Super admin: tint the subtitle so the role reads clearly, no extra chrome. */
    .cms-side-brand.is-super span { color: var(--teal-dark); font-weight: 700; }
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
    .cms-side-foot .cms-switch { display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 10px;
      background: var(--teal-soft); color: var(--teal-dark); border: 1px solid #cdd0ff; border-radius: 9px;
      padding: 11px; font-weight: 700; font-size: .86rem; transition: background .15s; }
    .cms-side-foot .cms-switch:hover { background: #dfe1ff; }
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
    .cms-topbar .cms-refresh { display: inline-flex; align-items: center; gap: 7px; background: none; border: 0;
      cursor: pointer; color: var(--muted); font-family: inherit; font-weight: 700; font-size: .86rem;
      padding: 8px 12px; border-radius: 9px; transition: background .15s, color .15s; }
    .cms-topbar .cms-refresh:hover { color: var(--teal); background: var(--teal-soft); }
    .cms-topbar .cms-refresh i { transition: transform .5s ease; }
    .cms-topbar .cms-refresh.spin i { animation: cmsSpin .6s linear infinite; }
    @keyframes cmsSpin { to { transform: rotate(360deg); } }
    [data-refresh-zone] { transition: opacity .2s ease; }
    [data-refresh-zone].is-refreshing { opacity: .4; pointer-events: none; }
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

    /* ── Admin portal: rich royal-orange theme ── */
    body.portal-admin {
      --teal: #f97316; --teal-dark: #c2410c; --teal-soft: #fff1e6;
    }
    body.portal-admin .cms-wrap a:not(.btn) { color: var(--teal-dark); }
    body.portal-admin .btn-primary { color: #fff; box-shadow: 0 8px 18px rgba(249, 115, 22, .30); }
    body.portal-admin .btn-primary:hover { background: var(--teal-dark); }
    body.portal-admin .cms-side-brand .mark {
      background: linear-gradient(135deg, #f97316, #fdba74);
      box-shadow: 0 8px 20px rgba(249, 115, 22, .40);
    }
    body.portal-admin .cms-side-brand.is-super span { color: #c2410c; }
    body.portal-admin .cms-nav-item.is-active {
      background: linear-gradient(72deg, #ea580c 0%, #f97316 55%, #fb923c 100%);
      box-shadow: 0 6px 16px rgba(234, 88, 12, .44);
    }
    body.portal-admin .cms-nav-item:not(.is-active):hover { background: #fff4ea; }
    body.portal-admin .cms-side-foot .cms-switch { background: #fff1e6; color: #c2410c; border-color: #fed0a8; }
    body.portal-admin .cms-side-foot .cms-switch:hover { background: #ffe3cb; }
    body.portal-admin .cms-side-foot button:hover { background: #fff1e6; color: #c2410c; border-color: #fed0a8; }
    body.portal-admin .cms-topbar a.cms-viewsite:hover { color: #c2410c; background: #fff1e6; }
    body.portal-admin .cms-flash { border-color: #fed0a8; }
    body.portal-admin input:focus, body.portal-admin textarea:focus, body.portal-admin select:focus {
      border-color: #f97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, .16);
    }
    body.portal-admin .cms-toast { border-left-color: #f97316; }
    body.portal-admin .cms-toast i { color: #f97316; }

    /* Accent-bearing chrome eases between themes on switch / load. */
    .cms-side-brand .mark, .cms-nav-item, .cms-side-foot .cms-switch, .btn-primary { transition:
      background .35s ease, box-shadow .35s ease, color .2s ease, border-color .2s ease, transform .12s ease; }

    /* ── Portal entrance animation (plays on open / switch) ── */
    @keyframes cmsUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
    @keyframes cmsDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }
    @keyframes cmsFade { from { opacity: 0; } to { opacity: 1; } }
    .cms-side-brand { animation: cmsUp .45s ease both; }
    .cms-nav { animation: cmsFade .5s .08s ease both; }
    .cms-nav-item, .cms-nav-label { animation: cmsUp .42s ease both; }
    .cms-topbar { animation: cmsDown .45s ease both; }
    .cms-wrap { animation: cmsUp .5s .06s ease both; }

    @media (prefers-reduced-motion: reduce) {
      .cms-side-brand, .cms-nav, .cms-nav-item, .cms-nav-label, .cms-topbar, .cms-wrap { animation: none !important; }
    }

    @media (max-width: 880px) {
      .cms-shell { grid-template-columns: 1fr; }
      .cms-side { position: fixed; z-index: 60; width: var(--sidebar-w); transform: translateX(-100%);
        transition: transform .25s ease; box-shadow: 0 0 40px rgba(0,0,0,.25); }
      .cms-shell.is-open .cms-side { transform: translateX(0); }
      .cms-burger { display: inline-flex; }
      .cms-topbar { margin: 12px 16px 0; gap: 8px; padding: 0 12px; }
      /* Keep the topbar on one line: title truncates, actions go icon-only. */
      .cms-topbar h1 { min-width: 0; overflow: hidden; text-overflow: ellipsis; font-size: 1rem; }
      .cms-topbar .cms-tb-label { display: none; }
      .cms-topbar a.cms-viewsite, .cms-topbar .cms-refresh { padding: 8px; gap: 0; }
      .cms-wrap { padding: 20px 16px 80px; }
      .cms-scrim { display: none; position: fixed; inset: 0; background: rgba(8,20,26,.45); z-index: 50; }
      .cms-shell.is-open .cms-scrim { display: block; }
    }
  </style>
  @stack('head')
</head>
<body class="{{ ($portal ?? 'cms') === 'admin' ? 'portal-admin' : '' }}">
  @php
    $current = \Illuminate\Support\Facades\Route::currentRouteName() ?? '';
    $isSuper = (bool) session('cms_super_admin');
    $portal = $portal ?? 'cms';

    if ($portal === 'admin') :
      $brandSub = $isSuper ? 'Super Admin · Admin Portal' : 'Admin Portal';
      $navGroups = [
        ['label' => '', 'items' => [
          ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => 'admin.overview', 'match' => 'admin.overview'],
        ]],
        ['label' => 'Payments', 'items' => [
          ['label' => 'Enrollments', 'icon' => 'users', 'route' => 'admin.enrollments.index', 'match' => 'admin.enrollments.index'],
          ['label' => 'Test Prep', 'icon' => 'graduation-cap', 'route' => 'admin.enrollments.test-prep', 'match' => 'admin.enrollments.test-prep'],
        ]],
        ['label' => 'Leads', 'items' => [
          ['label' => 'Student Profiler', 'icon' => 'clipboard-list', 'route' => 'admin.submissions.profiler', 'match' => 'admin.submissions.profiler'],
        ]],
        ['label' => 'Coming soon', 'items' => [
          ['label' => 'Settings', 'icon' => 'settings', 'soon' => true],
        ]],
      ];
    else :
      $brandSub = $isSuper ? 'Super Admin · Content Studio' : 'Content Studio';
    $contentItems = [
      ['label' => 'Home Page', 'icon' => 'panel-top', 'route' => 'admin.home-hero.live', 'match' => 'admin.home-hero'],
      // Page Builder sits directly below Home Page — available to all CMS admins.
      ['label' => 'Page Builder', 'icon' => 'layout-panel-top', 'route' => 'admin.pages.index', 'match' => 'admin.pages'],
      ['label' => 'Blog Posts', 'icon' => 'newspaper', 'route' => 'admin.blog.index', 'match' => 'admin.blog'],
      ['label' => 'Subscribers', 'icon' => 'mail', 'route' => 'admin.newsletter.index', 'match' => 'admin.newsletter'],
    ];
    // Super admin unlocks the (otherwise hidden) About-page editor.
    if ($isSuper) {
      $contentItems[] = ['label' => 'About Page', 'icon' => 'layout-template', 'route' => 'admin.about.live', 'match' => 'admin.about'];
    }
    $contentItems[] = ['label' => 'Notification Bar', 'icon' => 'megaphone', 'route' => 'admin.notice-bar.index', 'match' => 'admin.notice-bar'];
    $contentItems[] = ['label' => 'Test Prep · Compare', 'icon' => 'bar-chart-3', 'route' => 'admin.test-prep-compare.index', 'match' => 'admin.test-prep-compare'];
    $contentItems[] = ['label' => 'Destinations Menu', 'icon' => 'layout-grid', 'route' => 'admin.destinations-layout.index', 'match' => 'admin.destinations-layout'];
    $contentItems[] = ['label' => 'Unlinked Pages', 'icon' => 'unlink', 'route' => 'admin.unlinked-pages.index', 'match' => 'admin.unlinked-pages'];
    // Country visibility is a super-admin-only tool.
    if ($isSuper) {
      $contentItems[] = ['label' => 'Country visibility', 'icon' => 'eye', 'route' => 'admin.country-visibility.index', 'match' => 'admin.country-visibility'];
    }
    $contentItems[] = ['label' => 'Sync non-MBBS countries', 'icon' => 'globe', 'route' => 'admin.country-sync.index', 'match' => 'admin.country-sync'];
    $contentItems[] = ['label' => 'Sync MBBS countries', 'icon' => 'stethoscope', 'route' => 'admin.mbbs-country-sync.index', 'match' => 'admin.mbbs-country-sync'];

    $comingSoon = [['label' => 'Settings', 'icon' => 'settings', 'soon' => true]];
    if (! $isSuper) {
      array_unshift($comingSoon, ['label' => 'About Page', 'icon' => 'layout-template', 'soon' => true]);
    }

    $navGroups = [
      ['label' => '', 'items' => [
        ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard'],
      ]],
      ['label' => 'Content', 'items' => $contentItems],
      ['label' => 'Coming soon', 'items' => $comingSoon],
    ];
    endif;
  @endphp

  <div class="cms-shell" id="cms-shell">
    <div class="cms-scrim" data-close></div>

    <aside class="cms-side">
      <div class="cms-side-brand @if($isSuper) is-super @endif">
        <span class="mark"><img src="{{ asset('assets/Logo/mark.svg') }}" alt=""></span>
        <b>{{ config('site.name') }}<span>{{ $brandSub }}</span></b>
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
        <a class="cms-switch" href="{{ route('admin.portal') }}"><i data-lucide="grid-2x2" style="width:16px;height:16px;"></i> Switch portal</a>
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
        <button type="button" class="cms-refresh" data-refresh title="Refresh this page"><i data-lucide="refresh-cw" style="width:16px;height:16px;"></i> <span class="cms-tb-label">Refresh</span></button>
        <a class="cms-viewsite" href="{{ route('home') }}" target="_blank" title="View site"><i data-lucide="external-link" style="width:16px;height:16px;"></i> <span class="cms-tb-label">View site</span></a>
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
      // Refresh: swap just the marked content zone (smooth); fall back to full reload.
      document.querySelectorAll('[data-refresh]').forEach(b => b.addEventListener('click', () => {
        const zone = document.querySelector('[data-refresh-zone]');
        b.classList.add('spin');
        if (!zone) { location.reload(); return; }
        b.disabled = true;
        zone.classList.add('is-refreshing');
        fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
          .then(r => r.text())
          .then(html => {
            const fresh = new DOMParser().parseFromString(html, 'text/html').querySelector('[data-refresh-zone]');
            if (!fresh) { location.reload(); return; }
            zone.innerHTML = fresh.innerHTML;
            if (window.lucide) lucide.createIcons();
            zone.classList.remove('is-refreshing');
          })
          .catch(() => location.reload())
          .finally(() => { b.classList.remove('spin'); b.disabled = false; });
      }));

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
