@extends('admin.layout')
@section('title', 'Dashboard')

@push('head')
<style>
  .cms-wrap { max-width: 1320px; }

  .dsh-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 22px; }
  .dsh-grid > * { min-width: 0; }
  .span-3 { grid-column: span 3; } .span-4 { grid-column: span 4; }
  .span-5 { grid-column: span 5; } .span-7 { grid-column: span 7; }
  .span-8 { grid-column: span 8; } .span-12 { grid-column: span 12; }

  /* ── Welcome banner ── */
  .dsh-hero { position: relative; overflow: hidden; padding: 26px 28px;
    background: linear-gradient(110deg, #fff 60%, #f3f3ff 100%); }
  .dsh-hero h2 { margin: 0 0 6px; font-size: 1.5rem; letter-spacing: -.02em; }
  .dsh-hero h2 b { color: var(--teal); }
  .dsh-hero p { margin: 0 0 18px; color: var(--muted); max-width: 56ch; }
  .dsh-hero .btn-row { display: flex; flex-wrap: wrap; gap: 10px; }
  .dsh-hero .blob { position: absolute; right: -40px; top: -40px; width: 200px; height: 200px; border-radius: 50%;
    background: radial-gradient(circle at 30% 30%, rgba(102,108,255,.18), rgba(102,108,255,0) 70%); pointer-events: none; }
  .dsh-hero .ring { position: absolute; right: 34px; bottom: -54px; width: 150px; height: 150px; border-radius: 50%;
    border: 22px solid rgba(102,108,255,.08); pointer-events: none; }

  /* ── KPI cards ── */
  .dsh-kpi { padding: 20px 20px 18px; display: flex; flex-direction: column; gap: 2px;
    transition: transform .18s ease, box-shadow .18s ease; }
  .dsh-kpi:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }
  .dsh-ico { display: grid; place-items: center; width: 44px; height: 44px; border-radius: 11px;
    background: var(--cb, #ebecff); color: var(--c, var(--teal)); margin-bottom: 12px; }
  .dsh-ico i { width: 22px; height: 22px; }
  .dsh-num { font-size: 1.9rem; font-weight: 800; letter-spacing: -.02em; line-height: 1.1; }
  .dsh-kpi-label { font-weight: 700; font-size: .95rem; }
  .dsh-kpi-sub { color: var(--muted); font-size: .82rem; margin-top: 4px; }

  /* ── Card heading ── */
  .dsh-card { padding: 22px; height: 100%; }
  .dsh-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
  .dsh-card-head h3 { margin: 0; font-size: 1.05rem; letter-spacing: -.01em; }
  .dsh-card-head p { margin: 2px 0 0; color: var(--muted); font-size: .82rem; }

  /* ── Chips ── */
  .chip { display: inline-flex; align-items: center; gap: 5px; font-size: .74rem; font-weight: 800;
    padding: 3px 9px; border-radius: 999px; }
  .chip i { width: 12px; height: 12px; }
  .chip.green { background: #e6f8ee; color: #1f8a4c; }
  .chip.red { background: #fdeaea; color: var(--danger); }
  .chip.amber { background: #fdf3dd; color: #9a6b00; }
  .chip.blue { background: #eaf1fe; color: #3f6fd6; }
  .chip.grey { background: #f0f0f5; color: #6e6b7b; }

  /* ── Donut (pure CSS, no chart library) ── */
  .dsh-donut-row { display: flex; align-items: center; gap: 22px; flex-wrap: wrap; }
  .dsh-donut { position: relative; width: 150px; height: 150px; border-radius: 50%; flex-shrink: 0; }
  .dsh-donut::after { content: ""; position: absolute; inset: 26px; background: #fff; border-radius: 50%;
    box-shadow: inset 0 1px 3px rgba(43,44,64,.06); }
  .dsh-donut-center { position: absolute; inset: 0; display: grid; place-items: center; text-align: center; z-index: 1; }
  .dsh-donut-center b { font-size: 1.5rem; font-weight: 800; letter-spacing: -.02em; line-height: 1; }
  .dsh-donut-center span { display: block; font-size: .68rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin-top: 3px; }
  .dsh-legend { display: flex; flex-direction: column; gap: 11px; flex: 1; min-width: 150px; }
  .dsh-legend-item { display: flex; align-items: center; gap: 10px; font-size: .88rem; }
  .dsh-legend-item .dot { width: 11px; height: 11px; border-radius: 3px; flex-shrink: 0; }
  .dsh-legend-item .lbl { font-weight: 600; }
  .dsh-legend-item .val { margin-left: auto; font-weight: 800; }

  /* ── Quick actions ── */
  .dsh-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .dsh-act { display: flex; align-items: center; gap: 12px; padding: 14px; border: 1px solid var(--line);
    border-radius: 11px; color: var(--ink); font-weight: 700; font-size: .9rem; transition: border-color .15s, transform .12s, box-shadow .15s; }
  .dsh-act:hover { border-color: var(--teal); transform: translateY(-2px); box-shadow: var(--shadow); color: var(--teal-dark); }
  .dsh-act .dsh-ico { margin: 0; width: 38px; height: 38px; }
  .dsh-act .dsh-ico i { width: 18px; height: 18px; }

  /* ── Country sync ── */
  .dsh-stat-line { display: flex; align-items: baseline; gap: 10px; margin-bottom: 6px; }
  .dsh-stat-line b { font-size: 2.1rem; font-weight: 800; letter-spacing: -.02em; }
  .dsh-meta { color: var(--muted); font-size: .85rem; margin: 0 0 16px; }

  /* ── Timeline ── */
  .dsh-tl { list-style: none; margin: 0; padding: 0; position: relative; }
  .dsh-tl::before { content: ""; position: absolute; left: 6px; top: 6px; bottom: 6px; width: 2px; background: var(--line); }
  .dsh-tl li { position: relative; padding: 0 0 18px 26px; }
  .dsh-tl li:last-child { padding-bottom: 0; }
  .dsh-tl .tl-dot { position: absolute; left: 0; top: 3px; width: 14px; height: 14px; border-radius: 50%;
    border: 3px solid #fff; box-shadow: 0 0 0 2px currentColor; }
  .dsh-tl .tl-title { font-weight: 700; font-size: .92rem; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  .dsh-tl .tl-title a { color: var(--ink); }
  .dsh-tl .tl-title a:hover { color: var(--teal); }
  .dsh-tl .tl-meta { color: var(--muted); font-size: .8rem; margin-top: 2px; }
  .dsh-empty { color: var(--muted); font-size: .88rem; padding: 8px 0; }

  @media (max-width: 1100px) {
    .span-3 { grid-column: span 6; }
    .span-4, .span-5, .span-7, .span-8 { grid-column: span 12; }
  }
  @media (max-width: 620px) {
    .span-3 { grid-column: span 12; }
    .dsh-actions { grid-template-columns: 1fr; }
  }
</style>
@endpush

@section('content')
@php
  $hour = (int) now()->format('G');
  $greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

  $totalContent = $stats['posts_total'] + $stats['countries'] + $stats['mbbs_countries'] + $stats['notice_total'];

  // Content mix → CSS conic-gradient (no JS, no chart library).
  $mix = [
    ['label' => 'Blog posts',     'value' => $stats['posts_total'],    'color' => '#666cff'],
    ['label' => 'Destinations',   'value' => $stats['countries'],      'color' => '#3f6fd6'],
    ['label' => 'MBBS countries', 'value' => $stats['mbbs_countries'], 'color' => '#0f7a78'],
    ['label' => 'Notification items', 'value' => $stats['notice_total'],   'color' => '#28c76f'],
  ];
  $mixTotal = max(1, array_sum(array_column($mix, 'value')));
  $acc = 0; $stops = [];
  foreach ($mix as $m) {
    $start = round($acc / $mixTotal * 360, 2);
    $acc += $m['value'];
    $end = round($acc / $mixTotal * 360, 2);
    if ($m['value'] > 0) { $stops[] = $m['color'].' '.$start.'deg '.$end.'deg'; }
  }
  $conic = $stops ? 'conic-gradient('.implode(', ', $stops).')' : 'conic-gradient(#e9e9f0 0deg 360deg)';

  $variantLabel = ['original' => 'Original', 'minimal' => 'Minimal', 'compact' => 'Compact'][$stats['notice_variant']] ?? ucfirst($stats['notice_variant']);
@endphp

<div class="dsh-grid">

  {{-- Welcome banner --}}
  <section class="panel dsh-hero span-8">
    <div class="blob"></div><div class="ring"></div>
    @if(session('cms_super_admin'))
      <span class="chip blue" style="margin-bottom:10px;"><i data-lucide="shield-check"></i> Super Admin — all pages unlocked</span>
    @endif
    <h2>{{ $greet }} 👋 Welcome to <b>{{ config('site.name') }}</b> Content Studio</h2>
    <p>You're managing <b>{{ $totalContent }}</b> pieces of content across the site — blog posts, study destinations and notification-bar announcements. Everything here is file-backed and updates the live site instantly.</p>
    <div class="btn-row">
      <a class="btn btn-primary" href="{{ route('admin.blog.create') }}"><i data-lucide="plus" style="width:16px;height:16px;"></i> New blog post</a>
      <a class="btn btn-ghost" href="{{ route('home') }}" target="_blank"><i data-lucide="external-link" style="width:16px;height:16px;"></i> View live site</a>
    </div>
  </section>

  {{-- Featured / quick highlight --}}
  <section class="panel dsh-kpi span-3">
    <span class="dsh-ico" style="--c:#9a6b00;--cb:#fdf3dd"><i data-lucide="star"></i></span>
    <div class="dsh-kpi-label">Featured blog post</div>
    @if($stats['featured'])
      <div class="dsh-num" style="font-size:1.15rem;line-height:1.35;margin-top:6px;">{{ \Illuminate\Support\Str::limit($stats['featured']['title'], 60) }}</div>
      <a class="dsh-kpi-sub" style="color:var(--teal);font-weight:700;margin-top:8px;display:inline-flex;gap:5px;align-items:center;" href="{{ route('admin.blog.edit', $stats['featured']['slug']) }}">
        <i data-lucide="pencil" style="width:13px;height:13px;"></i> Edit featured post
      </a>
    @else
      <div class="dsh-kpi-sub" style="margin-top:8px;">No post is currently featured. Pick one from <a href="{{ route('admin.blog.index') }}">Blog Posts</a>.</div>
    @endif
  </section>

  {{-- KPI cards --}}
  <section class="panel dsh-kpi span-3">
    <span class="dsh-ico" style="--c:#666cff;--cb:#ebecff"><i data-lucide="newspaper"></i></span>
    <div class="dsh-num">{{ $stats['posts_total'] }}</div>
    <div class="dsh-kpi-label">Blog posts</div>
    <div class="dsh-kpi-sub">
      <span class="chip green"><i data-lucide="eye"></i> {{ $stats['posts_visible'] }} live</span>
      @if($stats['posts_hidden'] > 0)<span class="chip red"><i data-lucide="eye-off"></i> {{ $stats['posts_hidden'] }} hidden</span>@endif
    </div>
  </section>

  <section class="panel dsh-kpi span-3">
    <span class="dsh-ico" style="--c:#0f7a78;--cb:#e7f7f5"><i data-lucide="stethoscope"></i></span>
    <div class="dsh-num">{{ $stats['mbbs_countries'] }}</div>
    <div class="dsh-kpi-label">MBBS countries</div>
    <div class="dsh-kpi-sub">Country pages kept in sync from AV Global Overseas.</div>
  </section>

  <section class="panel dsh-kpi span-3">
    <span class="dsh-ico" style="--c:#3f6fd6;--cb:#eaf1fe"><i data-lucide="globe"></i></span>
    <div class="dsh-num">{{ $stats['countries'] }}</div>
    <div class="dsh-kpi-label">Study destinations</div>
    <div class="dsh-kpi-sub">Non-MBBS country pages, kept in sync from Leverage&nbsp;Edu.</div>
  </section>

  <section class="panel dsh-kpi span-4">
    <span class="dsh-ico" style="--c:#28c76f;--cb:#e6f8ee"><i data-lucide="megaphone"></i></span>
    <div class="dsh-num">{{ $stats['notice_total'] }}</div>
    <div class="dsh-kpi-label">Notification-bar items</div>
    <div class="dsh-kpi-sub">{{ $stats['notice_visible'] }} showing · {{ $variantLabel }} style</div>
  </section>

  {{-- Content mix donut --}}
  <section class="panel dsh-card span-4">
    <div class="dsh-card-head">
      <div><h3>Content mix</h3><p>Where your content lives</p></div>
    </div>
    <div class="dsh-donut-row">
      <div class="dsh-donut" style="background: {{ $conic }};">
        <div class="dsh-donut-center"><b>{{ $totalContent }}</b><span>Total</span></div>
      </div>
      <div class="dsh-legend">
        @foreach($mix as $m)
          <div class="dsh-legend-item">
            <span class="dot" style="background: {{ $m['color'] }};"></span>
            <span class="lbl">{{ $m['label'] }}</span>
            <span class="val">{{ $m['value'] }}</span>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- Quick actions --}}
  <section class="panel dsh-card span-4">
    <div class="dsh-card-head"><div><h3>Quick actions</h3><p>Jump straight in</p></div></div>
    <div class="dsh-actions">
      <a class="dsh-act" href="{{ route('admin.blog.create') }}">
        <span class="dsh-ico" style="--c:#666cff;--cb:#ebecff"><i data-lucide="plus"></i></span> New post
      </a>
      <a class="dsh-act" href="{{ route('admin.home-hero.live') }}">
        <span class="dsh-ico" style="--c:#3f6fd6;--cb:#eaf1fe"><i data-lucide="panel-top"></i></span> Home page
      </a>
      @if(session('cms_super_admin'))
      <a class="dsh-act" href="{{ route('admin.about.live') }}">
        <span class="dsh-ico" style="--c:#9a6b00;--cb:#fdf3dd"><i data-lucide="layout-template"></i></span> About page
      </a>
      @endif
      <a class="dsh-act" href="{{ route('admin.notice-bar.index') }}">
        <span class="dsh-ico" style="--c:#28c76f;--cb:#e6f8ee"><i data-lucide="megaphone"></i></span> Notification bar
      </a>
      <a class="dsh-act" href="{{ route('admin.country-visibility.index') }}">
        <span class="dsh-ico" style="--c:#3f6fd6;--cb:#eaf1fe"><i data-lucide="eye"></i></span> Country visibility
      </a>
      <a class="dsh-act" href="{{ route('admin.mbbs-country-sync.index') }}">
        <span class="dsh-ico" style="--c:#0f7a78;--cb:#e7f7f5"><i data-lucide="stethoscope"></i></span> MBBS sync
      </a>
    </div>
  </section>

  {{-- Country sync status --}}
  <section class="panel dsh-card span-4">
    <div class="dsh-card-head">
      <div><h3>Country data</h3><p>non-MBBS · Leverage&nbsp;Edu</p></div>
      @if($countrySync['running'])
        <span class="chip amber"><i data-lucide="loader"></i> Running</span>
      @elseif($countrySync['exists'])
        <span class="chip green"><i data-lucide="check"></i> Synced</span>
      @else
        <span class="chip grey"><i data-lucide="minus"></i> No data</span>
      @endif
    </div>
    <div class="dsh-stat-line"><b>{{ $stats['countries'] }}</b> <span style="color:var(--muted);">destinations</span></div>
    <p class="dsh-meta">
      @if($countrySync['updated_at'])
        Last updated {{ $countrySync['updated_at']->diffForHumans() }}
      @else
        No source data has been pulled yet.
      @endif
    </p>
    <a class="btn btn-ghost btn-sm" href="{{ route('admin.country-sync.index') }}">
      <i data-lucide="refresh-cw" style="width:14px;height:14px;"></i> Open sync tool
    </a>
  </section>

  {{-- MBBS country sync status --}}
  <section class="panel dsh-card span-4">
    <div class="dsh-card-head">
      <div><h3>MBBS country data</h3><p>AV Global Overseas</p></div>
      @if($mbbsCountrySync['running'])
        <span class="chip amber"><i data-lucide="loader"></i> Running</span>
      @elseif($mbbsCountrySync['exists'])
        <span class="chip green"><i data-lucide="check"></i> Synced</span>
      @else
        <span class="chip grey"><i data-lucide="minus"></i> No data</span>
      @endif
    </div>
    <div class="dsh-stat-line"><b>{{ $stats['mbbs_countries'] }}</b> <span style="color:var(--muted);">MBBS countries</span></div>
    <p class="dsh-meta">
      @if($mbbsCountrySync['updated_at'])
        Last updated {{ $mbbsCountrySync['updated_at']->diffForHumans() }}
      @else
        No MBBS source data has been pulled yet.
      @endif
    </p>
    <a class="btn btn-ghost btn-sm" href="{{ route('admin.mbbs-country-sync.index') }}">
      <i data-lucide="refresh-cw" style="width:14px;height:14px;"></i> Open MBBS sync tool
    </a>
  </section>

  {{-- Recent posts timeline --}}
  <section class="panel dsh-card span-12">
    <div class="dsh-card-head">
      <div><h3>Recent blog posts</h3><p>Your latest articles</p></div>
      <a class="chip blue" href="{{ route('admin.blog.index') }}" style="text-decoration:none;">View all <i data-lucide="arrow-right"></i></a>
    </div>
    @if(count($recentPosts))
      <ul class="dsh-tl">
        @foreach($recentPosts as $post)
          @php
            $visible = ($post['visible'] ?? true) === true;
            $isFeat = ! empty($post['featured']);
            $color = $isFeat ? '#9a6b00' : ($visible ? '#28c76f' : '#ea5455');
          @endphp
          <li>
            <span class="tl-dot" style="color: {{ $color }};"></span>
            <div class="tl-title">
              <a href="{{ route('admin.blog.edit', $post['slug']) }}">{{ \Illuminate\Support\Str::limit($post['title'], 64) }}</a>
              @if($isFeat)<span class="chip amber"><i data-lucide="star"></i> Featured</span>
              @elseif(! $visible)<span class="chip red"><i data-lucide="eye-off"></i> Hidden</span>@endif
            </div>
            <div class="tl-meta">
              {{ ! empty($post['date']) ? \Illuminate\Support\Carbon::parse($post['date'])->format('M j, Y') : '—' }}
              @if(! empty($post['category'])) · {{ $post['category'] }} @endif
              @if(! empty($post['read_time'])) · {{ $post['read_time'] }} min read @endif
            </div>
          </li>
        @endforeach
      </ul>
    @else
      <p class="dsh-empty">No posts yet. <a href="{{ route('admin.blog.create') }}">Create your first one →</a></p>
    @endif
  </section>

</div>
@endsection
