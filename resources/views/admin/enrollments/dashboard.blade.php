@extends('admin.layout')

@section('title', 'Admin Dashboard')

@push('head')
<style>
  .ad-grid{display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; margin-bottom:16px;}
  .ad-card{background:var(--panel); border:1px solid var(--line); border-radius:var(--radius); box-shadow:var(--shadow);
    padding:18px; animation:cmsUp .5s ease both;}
  .ad-card:nth-child(2){animation-delay:.05s} .ad-card:nth-child(3){animation-delay:.1s} .ad-card:nth-child(4){animation-delay:.15s}
  .ad-card .k{display:flex; align-items:center; gap:8px; color:var(--muted); font-weight:700; font-size:.76rem; text-transform:uppercase; letter-spacing:.04em;}
  .ad-card .k i{width:16px; height:16px; color:var(--teal-dark);}
  .ad-card .v{margin-top:9px; font-size:1.7rem; font-weight:800; letter-spacing:-.02em;}
  .ad-card .s{margin-top:3px; color:var(--muted); font-size:.78rem; font-weight:600;}
  .ad-card.hero{background:linear-gradient(135deg,#fb8b3d 0%,var(--teal) 45%,#ea580c 100%); border:0; color:#fff; box-shadow:0 14px 32px rgba(234,88,12,.34);}
  .ad-card.hero .k, .ad-card.hero .k i, .ad-card.hero .s{color:rgba(255,255,255,.9);}

  .ad-mini{display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; margin-bottom:22px;}

  .ad-cols{display:grid; grid-template-columns:1fr 1fr; gap:18px;}
  .ad-panel{background:var(--panel); border:1px solid var(--line); border-radius:var(--radius); box-shadow:var(--shadow); padding:20px; animation:cmsUp .55s .1s ease both;}
  .ad-panel h3{margin:0 0 16px; font-size:1rem; font-weight:800; display:flex; align-items:center; gap:8px;}
  .ad-panel h3 i{width:18px; height:18px; color:var(--teal-dark);}

  .ad-bar{margin-bottom:14px;}
  .ad-bar:last-child{margin-bottom:0;}
  .ad-bar-top{display:flex; justify-content:space-between; gap:10px; font-size:.86rem; margin-bottom:6px;}
  .ad-bar-top b{font-weight:800;}
  .ad-bar-top span{color:var(--muted); font-weight:700; white-space:nowrap;}
  .ad-bar-track{height:9px; border-radius:999px; background:#f0eef6; overflow:hidden;}
  .ad-bar-fill{height:100%; border-radius:999px; background:linear-gradient(90deg,#ea580c,var(--teal) 60%,#fdba74); transform-origin:left; animation:adGrow .7s cubic-bezier(.2,.8,.2,1) both;}
  @keyframes adGrow{from{transform:scaleX(0);} to{transform:scaleX(1);}}

  .ad-list{display:flex; flex-direction:column;}
  .ad-row{display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid var(--line);}
  .ad-row:last-child{border-bottom:0;}
  .ad-av{display:grid; place-items:center; width:36px; height:36px; border-radius:10px; background:var(--teal-soft); color:var(--teal-dark); font-weight:800; font-size:.82rem; flex-shrink:0;}
  .ad-row .nm{font-weight:700; font-size:.88rem;}
  .ad-row .mt{color:var(--muted); font-size:.76rem;}
  .ad-row .amt{margin-left:auto; text-align:right; white-space:nowrap;}
  .ad-row .amt b{font-weight:800; font-size:.9rem;}
  .badge{display:inline-block; padding:3px 9px; border-radius:999px; font-size:.68rem; font-weight:800; text-transform:capitalize;}
  .badge.paid{background:#e3f6ed; color:#0a7d4d;} .badge.fail{background:#fdecea; color:#c0392b;} .badge.wait{background:#fff5e6; color:#9a6b00;}
  .ad-empty{color:var(--muted); font-size:.86rem; padding:18px 0;}
  .ad-foot{margin-top:14px;}
  .ad-head{display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; flex-wrap:wrap;}
  .ad-head .meta{color:var(--muted); font-size:.82rem; font-weight:600; display:flex; align-items:center; gap:7px;}
  .ad-head .meta i{width:15px; height:15px;}
  @media(max-width:980px){ .ad-grid{grid-template-columns:repeat(2,1fr);} .ad-cols{grid-template-columns:1fr;} .ad-mini{grid-template-columns:1fr;} }
</style>
@endpush

@section('content')
  @php
    $fmt = fn ($paise) => '₹'.number_format(((int) $paise) / 100, 2);
    $maxPlan = max(1, (int) ($byPlan->max('total') ?? 1));
    $badge = fn (string $s) => match ($s) {
      'paid' => ['paid', 'Paid'],
      'payment_failed', 'order_failed' => ['fail', 'Failed'],
      default => ['wait', 'Pending'],
    };
  @endphp

  <div class="ad-head">
    <span class="meta"><i data-lucide="clock-3"></i> Updated {{ now()->format('d M Y, H:i') }}</span>
  </div>

  <div class="ad-grid">
    <div class="ad-card hero">
      <div class="k"><i data-lucide="indian-rupee"></i> Total revenue</div>
      <div class="v">{{ $fmt($stats['revenue']) }}</div>
      <div class="s">{{ number_format($stats['paid']) }} paid enrollments</div>
    </div>
    <div class="ad-card">
      <div class="k"><i data-lucide="calendar"></i> This month</div>
      <div class="v">{{ $fmt($stats['month_revenue']) }}</div>
      <div class="s">{{ now()->format('F Y') }}</div>
    </div>
    <div class="ad-card">
      <div class="k"><i data-lucide="badge-check"></i> Paid</div>
      <div class="v">{{ number_format($stats['paid']) }}</div>
      <div class="s">of {{ number_format($stats['total']) }} attempts</div>
    </div>
    <div class="ad-card">
      <div class="k"><i data-lucide="trending-up"></i> Conversion</div>
      <div class="v">{{ $stats['conversion'] }}%</div>
      <div class="s">paid / total attempts</div>
    </div>
  </div>

  <div class="ad-mini">
    <div class="ad-card">
      <div class="k"><i data-lucide="users"></i> Total enrollments</div>
      <div class="v">{{ number_format($stats['total']) }}</div>
    </div>
    <div class="ad-card">
      <div class="k"><i data-lucide="clock"></i> Pending / started</div>
      <div class="v">{{ number_format($stats['pending']) }}</div>
    </div>
    <div class="ad-card">
      <div class="k"><i data-lucide="x-circle"></i> Failed</div>
      <div class="v">{{ number_format($stats['failed']) }}</div>
    </div>
  </div>

  <div class="ad-cols">
    <div class="ad-panel">
      <h3><i data-lucide="bar-chart-3"></i> Revenue by plan</h3>
      @forelse($byPlan as $p)
        <div class="ad-bar">
          <div class="ad-bar-top"><b>{{ $p->item_name }}</b><span>{{ $fmt($p->total) }} · {{ $p->cnt }}×</span></div>
          <div class="ad-bar-track"><div class="ad-bar-fill" style="width:{{ max(4, round($p->total / $maxPlan * 100)) }}%"></div></div>
        </div>
      @empty
        <div class="ad-empty">No paid enrollments yet — revenue by plan will appear here.</div>
      @endforelse
    </div>

    <div class="ad-panel">
      <h3><i data-lucide="activity"></i> Recent enrollments</h3>
      @forelse($recent as $a)
        @php [$cls, $lbl] = $badge((string) $a->status); @endphp
        <div class="ad-list"><div class="ad-row">
          <span class="ad-av">{{ strtoupper(mb_substr(trim($a->customer_name ?: '?'), 0, 1)) }}</span>
          <div>
            <div class="nm">{{ $a->customer_name }}</div>
            <div class="mt">{{ $a->item_name }} · {{ optional($a->created_at)->format('d M, H:i') }}</div>
          </div>
          <div class="amt"><b>{{ $fmt($a->amount) }}</b><br><span class="badge {{ $cls }}">{{ $lbl }}</span></div>
        </div></div>
      @empty
        <div class="ad-empty">No enrollments yet.</div>
      @endforelse
      <div class="ad-foot">
        <a class="btn btn-primary btn-sm" href="{{ route('admin.enrollments.index') }}"><i data-lucide="list" style="width:15px;height:15px;"></i> View all enrollments</a>
      </div>
    </div>
  </div>
@endsection
