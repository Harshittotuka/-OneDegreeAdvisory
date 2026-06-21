<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Choose portal · {{ config('site.name') }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg:#f4f5fa; --panel:#fff; --ink:#2b2c40; --muted:#6e6b7b; --line:#eceef5;
      --indigo:#666cff; --indigo-dk:#5256e0; --indigo-soft:#ebecff;
      --orange:#f97316; --orange-dk:#c2410c; --orange-soft:#fff1e6;
      --radius:18px;
    }
    *{box-sizing:border-box;} html,body{margin:0;height:100%;}
    a{color:inherit; text-decoration:none;}

    body{
      font-family:"Manrope",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;
      color:var(--ink); min-height:100vh; padding:32px;
      display:flex; flex-direction:column; align-items:center; justify-content:center;
      background:var(--bg); position:relative; overflow:hidden;
    }
    /* soft animated colour wash in the background */
    body::before, body::after{
      content:""; position:fixed; border-radius:50%; filter:blur(70px); opacity:.5; z-index:0; pointer-events:none;
    }
    body::before{ width:520px; height:520px; top:-180px; left:-120px;
      background:radial-gradient(circle, #c9ccff 0%, transparent 70%); animation:float1 14s ease-in-out infinite; }
    body::after{ width:460px; height:460px; bottom:-160px; right:-120px;
      background:radial-gradient(circle, #ffd5c6 0%, transparent 70%); animation:float2 16s ease-in-out infinite; }
    @keyframes float1{ 0%,100%{transform:translate(0,0);} 50%{transform:translate(40px,30px);} }
    @keyframes float2{ 0%,100%{transform:translate(0,0);} 50%{transform:translate(-40px,-30px);} }

    .pp-brand{ position:relative; z-index:1; display:flex; align-items:center; gap:12px; margin-bottom:6px; animation:ppUp .5s ease both; }
    .pp-brand .mark{ display:grid; place-items:center; width:46px; height:46px; border-radius:13px;
      background:linear-gradient(135deg,var(--indigo),#9094ff); box-shadow:0 10px 24px rgba(102,108,255,.42); animation:bob 5s ease-in-out infinite; }
    .pp-brand .mark img{ width:25px; height:25px; filter:brightness(0) invert(1); }
    .pp-brand b{ font-size:1.25rem; font-weight:800; letter-spacing:.04em; text-transform:uppercase; }
    @keyframes bob{ 0%,100%{transform:translateY(0);} 50%{transform:translateY(-5px);} }

    .pp-sub{ position:relative; z-index:1; color:var(--muted); font-weight:600; margin:4px 0 30px; font-size:.95rem; animation:ppUp .5s .06s ease both; }

    .pp-grid{ position:relative; z-index:1; display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:24px; width:100%; max-width:740px; }

    .pp-card{
      position:relative; overflow:hidden; display:flex; flex-direction:column; gap:14px;
      background:var(--panel); border:1px solid var(--line); border-radius:var(--radius);
      padding:30px 28px; box-shadow:0 12px 36px rgba(43,44,64,.09);
      transition:transform .22s cubic-bezier(.2,.8,.2,1), box-shadow .22s, border-color .22s;
      animation:ppUp .6s ease both;
    }
    .pp-grid .pp-card:nth-child(1){ animation-delay:.12s; }
    .pp-grid .pp-card:nth-child(2){ animation-delay:.2s; }
    /* top accent bar that grows in on hover */
    .pp-card::before{ content:""; position:absolute; inset:0 0 auto 0; height:4px; transform:scaleX(0); transform-origin:left;
      transition:transform .3s cubic-bezier(.2,.8,.2,1); }
    .pp-card:hover{ transform:translateY(-6px); box-shadow:0 24px 56px rgba(43,44,64,.18); }
    .pp-card:hover::before{ transform:scaleX(1); }

    .pp-card .pp-ic{ display:grid; place-items:center; width:56px; height:56px; border-radius:15px;
      transition:transform .25s cubic-bezier(.2,.8,.2,1), box-shadow .25s; }
    .pp-card .pp-ic svg{ width:28px; height:28px; transition:transform .25s; }
    .pp-card:hover .pp-ic{ transform:scale(1.08) rotate(-4deg); }

    .pp-card h2{ margin:0; font-size:1.2rem; font-weight:800; color:var(--ink); letter-spacing:-.01em; }
    .pp-card p{ margin:0; color:var(--muted); font-size:.9rem; line-height:1.55; }
    .pp-go{ margin-top:6px; display:inline-flex; align-items:center; gap:8px; font-weight:800; font-size:.9rem; }
    .pp-go svg{ width:16px; height:16px; transition:transform .25s cubic-bezier(.2,.8,.2,1); }
    .pp-card:hover .pp-go svg{ transform:translateX(5px); }

    /* per-card accent colours */
    .pp-card.cms::before{ background:linear-gradient(90deg,var(--indigo),#9094ff); }
    .pp-card.cms .pp-ic{ background:var(--indigo-soft); color:var(--indigo-dk); }
    .pp-card.cms:hover{ border-color:#cdd0ff; }
    .pp-card.cms:hover .pp-ic{ box-shadow:0 10px 22px rgba(102,108,255,.4); }
    .pp-card.cms .pp-go{ color:var(--indigo-dk); }

    .pp-card.admin::before{ background:linear-gradient(90deg,var(--orange),#fb923c); }
    .pp-card.admin .pp-ic{ background:var(--orange-soft); color:var(--orange-dk); }
    .pp-card.admin:hover{ border-color:#fed0a8; }
    .pp-card.admin:hover .pp-ic{ box-shadow:0 10px 22px rgba(249,115,22,.38); }
    .pp-card.admin .pp-go{ color:var(--orange-dk); }

    .pp-foot{ position:relative; z-index:1; margin-top:32px; animation:ppUp .5s .3s ease both; }
    .pp-foot form{ margin:0; }
    .pp-foot button{ background:none; border:0; color:var(--muted); font-family:inherit; font-weight:700; font-size:.86rem;
      cursor:pointer; display:inline-flex; align-items:center; gap:7px; padding:8px 14px; border-radius:9px; transition:color .15s, background .15s; }
    .pp-foot button:hover{ color:var(--orange-dk); background:#fff; }

    @keyframes ppUp{ from{opacity:0; transform:translateY(18px);} to{opacity:1; transform:none;} }

    /* leave transition when a portal is chosen */
    body{ transition:opacity .3s ease, transform .3s ease; }
    body.is-leaving{ opacity:0; transform:scale(.985); }
    body.is-leaving.to-admin{ background:#fff4ec; }
    body.is-leaving.to-cms{ background:#f6f6ff; }

    @media(max-width:620px){ .pp-grid{grid-template-columns:1fr;} }
    @media(prefers-reduced-motion:reduce){
      *{animation:none !important; transition:none !important;}
    }
  </style>
</head>
<body>
  <div class="pp-brand">
    <span class="mark"><img src="{{ asset('assets/Logo/mark.svg') }}" alt=""></span>
    <b>{{ config('site.name') }}</b>
  </div>
  <p class="pp-sub">Choose where you want to go{{ session('cms_super_admin') ? ' · Super Admin' : '' }}</p>

  <div class="pp-grid">
    <a class="pp-card cms" href="{{ route('admin.dashboard') }}">
      <span class="pp-ic"><i data-lucide="layout-dashboard"></i></span>
      <h2>CMS · Content Studio</h2>
      <p>Manage website content — pages, blog, home hero, notice bar, countries and the page builder.</p>
      <span class="pp-go">Open CMS <i data-lucide="arrow-right"></i></span>
    </a>

    <a class="pp-card admin" href="{{ route('admin.overview') }}">
      <span class="pp-ic"><i data-lucide="users"></i></span>
      <h2>Admin · Enrollments</h2>
      <p>See everyone enrolling in plans — customer details, payments, status and revenue.</p>
      <span class="pp-go">Open Admin <i data-lucide="arrow-right"></i></span>
    </a>
  </div>

  <div class="pp-foot">
    <form method="POST" action="{{ route('admin.logout') }}">
      @csrf
      <button type="submit"><i data-lucide="log-out" style="width:15px;height:15px;"></i> Log out</button>
    </form>
  </div>

  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <script>
    if (window.lucide) lucide.createIcons();
    // Smooth fade/tint into the chosen portal before navigating.
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    document.querySelectorAll('.pp-card').forEach(function (card) {
      card.addEventListener('click', function (e) {
        if (reduce) return; // let the browser navigate normally
        e.preventDefault();
        var href = card.getAttribute('href');
        document.body.classList.add('is-leaving', card.classList.contains('admin') ? 'to-admin' : 'to-cms');
        setTimeout(function () { window.location.href = href; }, 240);
      });
    });
  </script>
</body>
</html>
