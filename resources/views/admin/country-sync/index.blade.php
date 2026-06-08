@extends('admin.layout')

@section('title', $tool['title'] ?? 'Sync non-MBBS countries')

@push('head')
<style>
  .sync-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
  .sync-title { margin: 0; font-size: 1.08rem; font-weight: 800; }
  .sync-sub { margin: 4px 0 0; color: var(--muted); font-size: .86rem; }
  .sync-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 10px; }
  .sync-panel { padding: 22px; margin-bottom: 20px; }
  .sync-meta { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-top: 14px; }
  .sync-metric { border: 1px solid var(--line); border-radius: 10px; padding: 14px; background: #fafbfc; min-width: 0; }
  .sync-metric b { display: block; font-size: 1.35rem; line-height: 1.1; }
  .sync-metric span { display: block; color: var(--muted); font-size: .76rem; font-weight: 700; margin-top: 5px; }
  .sync-status { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 16px; }
  .sync-status-row { border: 1px solid var(--line); border-radius: 10px; padding: 12px; background: #fff; }
  .sync-status-row strong { display: block; font-size: .82rem; }
  .sync-status-row span { display: block; color: var(--muted); font-size: .78rem; margin-top: 3px; word-break: break-word; }
  .sync-log { margin-top: 16px; border: 1px solid var(--line); border-radius: 10px; background: #0f172a; color: #dbeafe;
    padding: 12px; max-height: 260px; overflow: auto; white-space: pre-wrap; word-break: break-word; font-size: .76rem; }
  .sync-table-wrap { overflow-x: auto; border: 1px solid var(--line); border-radius: 12px; background: #fff; }
  .sync-table { width: 100%; border-collapse: collapse; min-width: 980px; }
  .sync-table th, .sync-table td { padding: 12px; border-bottom: 1px solid var(--line); vertical-align: top; text-align: left; }
  .sync-table th { font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); background: #fafbfc; }
  .sync-table tr:last-child td { border-bottom: 0; }
  .sync-badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 3px 8px; font-size: .72rem; font-weight: 800; background: var(--teal-soft); color: var(--teal-dark); }
  .sync-badge.removed { background: #fdecea; color: var(--danger); }
  .sync-badge.modified { background: #eef4ff; color: #244a8f; }
  .sync-row-title { font-weight: 800; font-size: .83rem; max-width: 230px; }
  .sync-row-key { margin-top: 4px; color: var(--muted); font-size: .72rem; max-width: 240px; word-break: break-word; }
  .sync-old { margin: 0; white-space: pre-wrap; word-break: break-word; max-height: 160px; overflow: auto; color: #334155; font-size: .78rem; }
  .sync-new { min-height: 92px; font-size: .8rem; line-height: 1.45; }
  .sync-check { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; }
  .sync-check input { width: 18px; height: 18px; accent-color: var(--teal); cursor: pointer; }
  .sync-empty { color: var(--muted); font-size: .88rem; margin: 0; }
  .sync-note { margin: 10px 0 0; color: var(--muted); font-size: .78rem; }

  /* ── Live progress panel ── */
  .sync-progress { margin-top: 18px; border: 1px solid var(--line); border-radius: 12px; background: #fbfcfe; padding: 16px; }
  .sync-progress[hidden] { display: none; }
  .sync-progress-head { display: flex; align-items: center; gap: 10px; }
  .sync-spinner { width: 18px; height: 18px; border: 2.5px solid #cfd8e6; border-top-color: var(--teal);
    border-radius: 50%; flex-shrink: 0; }
  .sync-spinner.spin { animation: sync-spin .8s linear infinite; }
  @keyframes sync-spin { to { transform: rotate(360deg); } }
  .sync-progress-title { font-weight: 800; font-size: .9rem; }
  .sync-progress-elapsed { margin-left: auto; color: var(--muted); font-size: .78rem; font-variant-numeric: tabular-nums; font-weight: 700; }
  .sync-bar-track { margin-top: 12px; height: 10px; border-radius: 999px; background: #e4e9f1; overflow: hidden; }
  .sync-bar-fill { height: 100%; width: 3%; border-radius: 999px; background: linear-gradient(90deg, var(--teal), #f59e4d);
    transition: width .4s ease; }
  .sync-bar-fill.is-done { background: linear-gradient(90deg, #1f9d6b, #34c98a); }
  .sync-bar-fill.is-failed { background: linear-gradient(90deg, var(--danger), #e06a5d); }
  .sync-progress-row { display: flex; align-items: baseline; gap: 10px; margin-top: 8px; }
  .sync-progress-step { font-size: .82rem; font-weight: 700; color: var(--ink); }
  .sync-progress-pct { margin-left: auto; font-size: .82rem; font-weight: 800; color: var(--teal-dark); font-variant-numeric: tabular-nums; }
  .sync-progress-log { margin-top: 12px; border-radius: 10px; background: #0f172a; color: #dbeafe; padding: 12px;
    max-height: 220px; overflow: auto; white-space: pre-wrap; word-break: break-word; font-size: .74rem; line-height: 1.5;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
  @media (max-width: 760px) {
    .sync-head { display: block; }
    .sync-actions { justify-content: flex-start; margin-top: 14px; }
    .sync-meta, .sync-status { grid-template-columns: 1fr; }
  }
</style>
@endpush

@section('content')
@php
  $tool = $tool ?? [
    'title' => 'Sync non-MBBS countries',
    'source_label' => 'Leverage Edu',
    'source_url' => 'https://leverageedu.com/',
    'description' => 'non-MBBS country pages, workbook, and website JSON review.',
    'route_prefix' => 'admin.country-sync',
    'apply_confirm' => 'Update live country data with the reviewed scrape?',
    'note' => 'Selected changes update the website JSON. The full Update all data action also replaces the workbook and scraper snapshot.',
  ];
  $syncRoute = fn (string $name) => route($tool['route_prefix'].'.'.$name);
  $comparison = $state['comparison'] ?? null;
  $summary = $comparison['summary'] ?? null;
  $details = $comparison ? array_slice($comparison['details'], 0, 250) : [];
  $detailCount = $comparison ? count($comparison['details']) : 0;
@endphp

<div class="panel sync-panel">
  <div class="sync-head">
    <div>
      <h2 class="sync-title">{{ $tool['title'] }}</h2>
      <p class="sync-sub">Source: <a href="{{ $tool['source_url'] }}" target="_blank" rel="noopener">{{ $tool['source_label'] }}</a> &mdash; {{ $tool['description'] }}</p>
    </div>
    <div class="sync-actions">
      {{-- JS intercepts and runs the check in the background; the plain POST is a no-JS fallback. --}}
      <form method="POST" action="{{ $syncRoute('check') }}" id="sync-check-form">
        @csrf
        <button type="submit" class="btn btn-primary" id="sync-start-btn">
          <i data-lucide="refresh-cw" style="width:16px;height:16px;"></i> Check source changes
        </button>
      </form>

      @if($state['review']['exists'])
        <form method="POST" action="{{ $syncRoute('apply') }}" onsubmit="return confirm(@json($tool['apply_confirm']));">
          @csrf
          <button type="submit" class="btn btn-ghost">
            <i data-lucide="upload-cloud" style="width:16px;height:16px;"></i> Update all data
          </button>
        </form>
      @endif
    </div>
  </div>

  <div class="sync-progress" id="sync-progress" {{ $state['running'] ? '' : 'hidden' }}>
    <div class="sync-progress-head">
      <span class="sync-spinner spin" id="sync-spinner"></span>
      <span class="sync-progress-title" id="sync-progress-title">Checking source…</span>
      <span class="sync-progress-elapsed" id="sync-progress-elapsed">0:00</span>
    </div>
    <div class="sync-bar-track"><div class="sync-bar-fill" id="sync-bar"></div></div>
    <div class="sync-progress-row">
      <span class="sync-progress-step" id="sync-progress-step">Starting…</span>
      <span class="sync-progress-pct" id="sync-progress-pct">0%</span>
    </div>
    <pre class="sync-progress-log" id="sync-progress-log"></pre>
  </div>

  @if($summary)
    <div class="sync-meta">
      <div class="sync-metric"><b>{{ $summary['changed_percent'] }}%</b><span>Data changed</span></div>
      <div class="sync-metric"><b>{{ $summary['changed_records'] }}</b><span>Changed records</span></div>
      <div class="sync-metric"><b>{{ $summary['added_records'] }}</b><span>Added records</span></div>
      <div class="sync-metric"><b>{{ $summary['field_changes'] }}</b><span>Changed fields</span></div>
    </div>
  @else
    <p class="sync-empty">No review run is available yet.</p>
  @endif

  <div class="sync-status">
    <div class="sync-status-row">
      <strong>Live JSON</strong>
      <span>{{ $state['live']['exists'] ? 'Generated '.$state['live']['generated_at'].'; file updated '.$state['live']['updated_at'] : 'Missing' }}</span>
    </div>
    <div class="sync-status-row">
      <strong>Review JSON</strong>
      <span>{{ $state['review']['exists'] ? 'Generated '.$state['review']['generated_at'].'; file updated '.$state['review']['updated_at'] : 'Not checked yet' }}</span>
    </div>
  </div>

  @if(! empty($state['last_run']['output']))
    <details style="margin-top:16px;">
      <summary style="cursor:pointer; font-size:.82rem; font-weight:800;">Last scraper output · {{ $state['last_run']['updated_at'] }}</summary>
      <pre class="sync-log">{{ $state['last_run']['output'] }}</pre>
    </details>
  @endif

  @if($state['review']['report_exists'] || $state['review']['workbook_exists'])
    <div class="sync-actions" style="justify-content:flex-start; margin-top:16px;">
      @if($state['review']['report_exists'])
        <a class="btn btn-ghost btn-sm" href="{{ $syncRoute('report') }}">
          <i data-lucide="file-spreadsheet" style="width:15px;height:15px;"></i> Pending report
        </a>
      @endif
      @if($state['review']['workbook_exists'])
        <a class="btn btn-ghost btn-sm" href="{{ $syncRoute('workbook') }}">
          <i data-lucide="download" style="width:15px;height:15px;"></i> Review workbook
        </a>
      @endif
    </div>
  @endif
</div>

@if($comparison)
  <form method="POST" action="{{ $syncRoute('selected') }}">
    @csrf
    <div class="panel sync-panel">
      <div class="sync-head" style="margin-bottom:14px;">
        <div>
          <h2 class="sync-title">Changed data</h2>
          <p class="sync-sub">{{ $detailCount }} field-level change(s) found. Showing {{ count($details) }}.</p>
        </div>
        <button type="submit" class="btn btn-primary">
          <i data-lucide="save" style="width:16px;height:16px;"></i> Apply selected changes
        </button>
      </div>

      @if($details === [])
        <p class="sync-empty">No changed fields in the reviewed scrape.</p>
      @else
        <div class="sync-table-wrap">
          <table class="sync-table">
            <thead>
              <tr>
                <th>Apply</th>
                <th>Type</th>
                <th>Area</th>
                <th>Record</th>
                <th>Field</th>
                <th>Old content</th>
                <th>New / modified content</th>
              </tr>
            </thead>
            <tbody>
              @foreach($details as $detail)
                <tr>
                  <td>
                    <label class="sync-check">
                      <input type="checkbox" name="changes[{{ $detail['token'] }}][apply]" value="1">
                    </label>
                  </td>
                  <td><span class="sync-badge {{ $detail['change_type'] }}">{{ $detail['change_type'] }}</span></td>
                  <td>{{ $detail['sheet_name'] }}</td>
                  <td>
                    <div class="sync-row-title">{{ $detail['row_label'] }}</div>
                    <div class="sync-row-key">{{ $detail['row_key'] }}</div>
                  </td>
                  <td>{{ $detail['field_name'] }}</td>
                  <td><pre class="sync-old">{{ $detail['old_value'] }}</pre></td>
                  <td>
                    @if($detail['change_type'] === 'removed')
                      <p class="sync-old">{{ $detail['old_value'] }}</p>
                    @else
                      <textarea class="sync-new" name="changes[{{ $detail['token'] }}][value]">{{ $detail['new_value'] }}</textarea>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @if($detailCount > count($details))
          <p class="sync-note">{{ $detailCount - count($details) }} more change(s) are in the pending report workbook.</p>
        @endif
        <p class="sync-note">{{ $tool['note'] }}</p>
      @endif
    </div>
  </form>
@endif
@endsection

@push('scripts')
<script>
(function () {
  const startUrl = @json($syncRoute('start'));
  const progressUrl = @json($syncRoute('progress'));
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  const form = document.getElementById('sync-check-form');
  const btn = document.getElementById('sync-start-btn');
  const panel = document.getElementById('sync-progress');
  const spinner = document.getElementById('sync-spinner');
  const titleEl = document.getElementById('sync-progress-title');
  const elapsedEl = document.getElementById('sync-progress-elapsed');
  const bar = document.getElementById('sync-bar');
  const stepEl = document.getElementById('sync-progress-step');
  const pctEl = document.getElementById('sync-progress-pct');
  const logEl = document.getElementById('sync-progress-log');

  let polling = false;

  function fmt(s) {
    s = Math.max(0, Math.round(s));
    return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
  }

  function setBar(p, cls) {
    bar.style.width = Math.max(2, Math.min(100, p)) + '%';
    pctEl.textContent = Math.round(p) + '%';
    bar.classList.remove('is-done', 'is-failed');
    if (cls) bar.classList.add(cls);
  }

  function finish(ok, msg) {
    polling = false;
    setBar(100, ok ? 'is-done' : 'is-failed');
    spinner.classList.remove('spin');
    titleEl.textContent = ok ? 'Source check complete' : 'Source check failed';
    if (window.cmsToast) window.cmsToast(msg || (ok ? 'Done.' : 'Failed.'), ok ? '' : 'error');
    if (ok) {
      stepEl.textContent = 'Reloading to show the changes…';
      setTimeout(() => window.location.reload(), 1400);
    } else {
      stepEl.textContent = msg || 'See the log above.';
      btn.disabled = false;
    }
  }

  async function poll() {
    try {
      const res = await fetch(progressUrl, { headers: { 'Accept': 'application/json' } });
      const d = await res.json();
      if (typeof d.percent === 'number') setBar(d.percent);
      if (d.step || d.phase) stepEl.textContent = d.step || d.phase;
      if (typeof d.elapsed === 'number') elapsedEl.textContent = fmt(d.elapsed);
      if (d.log_tail) { logEl.textContent = d.log_tail; logEl.scrollTop = logEl.scrollHeight; }
      if (d.done) return finish(true, d.message);
      if (d.failed) return finish(false, d.message);
    } catch (e) { /* transient — keep polling */ }
    if (polling) setTimeout(poll, 1500);
  }

  async function start() {
    btn.disabled = true;
    panel.hidden = false;
    titleEl.textContent = 'Checking source…';
    spinner.classList.add('spin');
    stepEl.textContent = 'Starting…';
    logEl.textContent = '';
    setBar(3);
    try {
      const res = await fetch(startUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
      });
      const d = await res.json();
      if (!d.ok) return finish(false, d.error || 'Could not start the check.');
    } catch (e) {
      return finish(false, 'Could not start the check.');
    }
    if (!polling) { polling = true; poll(); }
  }

  if (form) form.addEventListener('submit', (e) => { e.preventDefault(); start(); });

  // A run was already in progress when the page loaded — resume tracking it.
  @if($state['running'])
    btn.disabled = true;
    spinner.classList.add('spin');
    if (!polling) { polling = true; poll(); }
  @endif
})();
</script>
@endpush
