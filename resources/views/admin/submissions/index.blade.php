@extends('admin.layout')
@section('title', 'Profiler Submissions')

@push('head')
<style>
  /* This page reads better edge-to-edge: the inline answer tables are wide. */
  .cms-wrap { max-width: none; }

  .subs-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:18px; }
  .subs-tab { display:inline-flex; align-items:center; gap:7px; padding:8px 14px; border-radius:999px;
    border:1px solid var(--line); background:#fff; color:var(--side-ink); font-weight:700; font-size:.84rem; }
  .subs-tab:hover { border-color:var(--teal); color:var(--teal); }
  .subs-tab.is-active { background:var(--teal); border-color:var(--teal); color:#fff; box-shadow:0 5px 14px rgba(0,0,0,.14); }
  .subs-tab .pill { background:rgba(0,0,0,.08); border-radius:999px; padding:1px 8px; font-size:.74rem; }
  .subs-tab.is-active .pill { background:rgba(255,255,255,.25); }

  .subs-toolbar { display:flex; justify-content:flex-end; margin-bottom:10px; }
  .subs-expand-all { display:inline-flex; align-items:center; gap:7px; background:none; border:0; cursor:pointer;
    font-family:inherit; font-weight:700; font-size:.82rem; color:var(--muted); padding:6px 8px; border-radius:8px; }
  .subs-expand-all:hover { color:var(--teal); background:var(--teal-soft); }

  .subs-table { width:100%; border-collapse:collapse; font-size:.9rem; }
  .subs-table th { text-align:left; background:#f8f5f1; padding:12px 16px; font-weight:800;
    font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); }
  .subs-table > tbody > tr.subs-row > td { padding:12px 16px; border-top:1px solid var(--line); vertical-align:top; }
  .subs-table > tbody > tr.subs-row:hover > td { background:#fafbfc; }

  .deg-badge { display:inline-block; background:#fff4e6; color:#9a6b00; font-size:.72rem; font-weight:800;
    padding:3px 10px; border-radius:999px; text-transform:capitalize; }
  .subs-actions { display:flex; gap:6px; justify-content:flex-end; }

  /* Summary cell doubles as the expand toggle. */
  .sub-toggle { display:inline-flex; align-items:flex-start; gap:9px; background:none; border:0; cursor:pointer;
    font-family:inherit; font-size:.9rem; color:var(--ink); padding:0; text-align:left; }
  .sub-toggle .subs-snip { color:var(--muted); font-size:.84rem; max-width:520px; }
  .sub-toggle .subs-snip b { color:var(--ink); font-weight:700; }
  .sub-toggle .chev { width:16px; height:16px; color:var(--muted); transition:transform .15s ease; flex-shrink:0; margin-top:1px; }
  .sub-toggle:hover .subs-snip, .sub-toggle:hover .chev { color:var(--teal); }
  .sub-toggle.is-open .chev { transform:rotate(90deg); }

  /* Inline answer detail (revealed on expand). */
  .sub-detail > td { padding:0 16px 16px !important; background:#fbfaf9; border-top:0 !important; }
  .qa-sec { margin-top:14px; }
  .qa-sec__h { font-size:.7rem; font-weight:800; letter-spacing:.07em; text-transform:uppercase;
    color:var(--teal-dark); margin-bottom:4px; }
  .qa-table { width:100%; border-collapse:collapse; background:#fff; border:1px solid var(--line); border-radius:10px; overflow:hidden; }
  .qa-table th, .qa-table td { padding:9px 13px; border-top:1px solid var(--line); vertical-align:top;
    text-align:left; font-size:.86rem; background:transparent; text-transform:none; letter-spacing:normal; }
  .qa-table tr:first-child th, .qa-table tr:first-child td { border-top:0; }
  .qa-table th { width:34%; font-weight:700; color:var(--ink); }
  .qa-table td { color:var(--ink); }
  .qa-chips { display:flex; flex-wrap:wrap; gap:6px; }
  .qa-chip { display:inline-block; background:#f1f0f7; color:#454360; font-size:.82rem; font-weight:600;
    padding:4px 10px; border-radius:7px; }
  .qa-empty { color:var(--muted); font-size:.84rem; padding:10px 2px; }
</style>
@endpush

@php
  $isProfilerTab = $source === 'profiler';
  $tabName  = $isProfilerTab ? 'Student Profiler' : 'Profile Evaluator';
  $tabBlurb = $isProfilerTab
    ? 'Completed Student Profiler questionnaires (/profiler).'
    : 'Completed Profile Evaluator questionnaires (/evaluate-my-profile).';
@endphp

@section('content')
  <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px;">
    <div>
      <h1 style="margin:0;font-size:1.45rem;letter-spacing:-.01em;">{{ $tabName }} submissions</h1>
      <p style="margin:3px 0 0;color:var(--muted);font-size:.85rem;">{{ $tabBlurb }}</p>
    </div>
    @if(count($submissions))
      <a class="btn btn-primary" href="{{ route('admin.submissions.export', ['source' => $source]) }}">
        <i data-lucide="download" style="width:16px;height:16px;"></i> Export CSV
      </a>
    @endif
  </div>

  @if(session('status'))
    <div class="panel panel-pad" style="margin-bottom:16px;padding:13px 16px;color:var(--teal-dark);font-weight:600;">{{ session('status') }}</div>
  @endif

  <div class="subs-tabs">
    <a class="subs-tab @if($source === 'profiler') is-active @endif" href="{{ route('admin.submissions.profiler') }}">
      Student Profiler <span class="pill">{{ $counts['profiler'] }}</span>
    </a>
    <a class="subs-tab @if($source === 'evaluator') is-active @endif" href="{{ route('admin.submissions.evaluator') }}">
      Profile Evaluator <span class="pill">{{ $counts['evaluator'] }}</span>
    </a>
  </div>

  @if(count($submissions))
    <div class="subs-toolbar">
      <button type="button" class="subs-expand-all" data-expand-all aria-expanded="false">
        <i data-lucide="chevrons-up-down" style="width:15px;height:15px;"></i>
        <span data-expand-all-label>Expand all</span>
      </button>
    </div>

    <div class="panel" style="overflow-x:auto;">
      <table class="subs-table">
        <thead>
          <tr>
            <th>Submitted</th>
            <th>Degree</th>
            <th>Summary &amp; answers</th>
            <th style="width:1%;"></th>
          </tr>
        </thead>
        <tbody>
          @foreach($submissions as $s)
            @php
              $secs = $s['sections'] ?? [];
              $answerCount = 0;
              $firstVal = null;
              foreach ($secs as $sec) {
                foreach (($sec['answers'] ?? []) as $a) {
                  $answerCount++;
                  if ($firstVal === null && ! empty($a['value'])) {
                    $firstVal = implode(', ', (array) $a['value']);
                  }
                }
              }
              $rid = $s['id'] ?? $loop->index;
            @endphp
            <tr class="subs-row">
              <td style="white-space:nowrap;color:var(--muted);">
                {{ ! empty($s['submitted_at']) ? \Illuminate\Support\Carbon::parse($s['submitted_at'])->format('M j, Y') : '—' }}
                <div style="font-size:.78rem;">{{ ! empty($s['submitted_at']) ? \Illuminate\Support\Carbon::parse($s['submitted_at'])->format('g:i A') : '' }}</div>
              </td>
              <td>
                @if(! empty($s['degree']))<span class="deg-badge">{{ $s['degree'] }}</span>@else <span style="color:var(--muted);">—</span>@endif
              </td>
              <td>
                <button type="button" class="sub-toggle" data-sub-toggle="{{ $rid }}" aria-expanded="false"
                        aria-controls="sub-detail-{{ $rid }}" title="Show all answers">
                  <i class="chev" data-lucide="chevron-right"></i>
                  <span class="subs-snip">
                    <b>{{ $answerCount }}</b> {{ \Illuminate\Support\Str::plural('answer', $answerCount) }}@if($firstVal) · {{ \Illuminate\Support\Str::limit($firstVal, 80) }}@endif
                  </span>
                </button>
              </td>
              <td>
                <div class="subs-actions">
                  <a class="btn btn-ghost btn-sm" href="{{ route('admin.submissions.show', $s['id'] ?? '') }}" title="View submission">
                    <i data-lucide="eye" style="width:14px;height:14px;"></i> View
                  </a>
                  <form method="POST" action="{{ route('admin.submissions.destroy') }}"
                        onsubmit="return confirm('Delete this submission permanently?');">
                    @csrf
                    <input type="hidden" name="id" value="{{ $s['id'] ?? '' }}">
                    <button class="btn btn-danger btn-sm" type="submit" title="Delete submission">
                      <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            <tr class="sub-detail" id="sub-detail-{{ $rid }}" hidden>
              <td colspan="4">
                @if(count($secs))
                  @foreach($secs as $sec)
                    <div class="qa-sec">
                      <div class="qa-sec__h">
                        {{ $sec['eyebrow'] ?? '' }}@if(! empty($sec['title'])) · {{ $sec['title'] }}@endif
                      </div>
                      <table class="qa-table">
                        <tbody>
                          @foreach($sec['answers'] ?? [] as $a)
                            <tr>
                              <th>{{ $a['label'] ?? '' }}</th>
                              <td>
                                <div class="qa-chips">
                                  @forelse((array) ($a['value'] ?? []) as $v)
                                    <span class="qa-chip">{{ $v }}</span>
                                  @empty
                                    <span style="color:var(--muted);">—</span>
                                  @endforelse
                                </div>
                              </td>
                            </tr>
                          @endforeach
                        </tbody>
                      </table>
                    </div>
                  @endforeach
                @else
                  <div class="qa-empty">This submission has no recorded answers.</div>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <div class="panel panel-pad" style="text-align:center;color:var(--muted);padding:50px 20px;">
      No {{ $tabName }} submissions yet. They’ll appear here when visitors complete the
      <a href="{{ $isProfilerTab ? route('profiler') : route('profile.evaluate') }}" target="_blank">{{ $tabName }}</a>.
    </div>
  @endif
@endsection

@push('scripts')
<script>
  (function () {
    function setRow(btn, open) {
      var id = btn.getAttribute('data-sub-toggle');
      var row = document.getElementById('sub-detail-' + id);
      if (!row) return;
      if (open) row.removeAttribute('hidden'); else row.setAttribute('hidden', '');
      btn.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    var toggles = Array.prototype.slice.call(document.querySelectorAll('[data-sub-toggle]'));
    toggles.forEach(function (btn) {
      btn.addEventListener('click', function () {
        setRow(btn, btn.getAttribute('aria-expanded') !== 'true');
      });
    });

    // Expand all / collapse all.
    var allBtn = document.querySelector('[data-expand-all]');
    if (allBtn) {
      allBtn.addEventListener('click', function () {
        var open = allBtn.getAttribute('aria-expanded') !== 'true';
        toggles.forEach(function (btn) { setRow(btn, open); });
        allBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        var lbl = allBtn.querySelector('[data-expand-all-label]');
        if (lbl) lbl.textContent = open ? 'Collapse all' : 'Expand all';
      });
    }
  })();
</script>
@endpush
