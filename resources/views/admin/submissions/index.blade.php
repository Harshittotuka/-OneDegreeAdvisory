@extends('admin.layout')
@section('title', 'Profiler Submissions')

@push('head')
<style>
  .subs-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:18px; }
  .subs-tab { display:inline-flex; align-items:center; gap:7px; padding:8px 14px; border-radius:999px;
    border:1px solid var(--line); background:#fff; color:var(--side-ink); font-weight:700; font-size:.84rem; }
  .subs-tab:hover { border-color:var(--teal); color:var(--teal); }
  .subs-tab.is-active { background:var(--teal); border-color:var(--teal); color:#fff; box-shadow:0 5px 14px rgba(102,108,255,.35); }
  .subs-tab .pill { background:rgba(0,0,0,.08); border-radius:999px; padding:1px 8px; font-size:.74rem; }
  .subs-tab.is-active .pill { background:rgba(255,255,255,.25); }

  .subs-table { width:100%; border-collapse:collapse; font-size:.9rem; }
  .subs-table th { text-align:left; background:#f8f5f1; padding:12px 16px; font-weight:800;
    font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); }
  .subs-table td { padding:12px 16px; border-top:1px solid var(--line); vertical-align:top; }
  .subs-table tr:hover td { background:#fafbfc; }

  .src-badge { display:inline-block; font-size:.72rem; font-weight:800; padding:3px 10px; border-radius:999px; }
  .src-profiler { background:#ebecff; color:#4044c4; }
  .src-evaluator { background:#e6f6ec; color:#1f7a45; }
  .deg-badge { display:inline-block; background:#fff4e6; color:#9a6b00; font-size:.72rem; font-weight:800;
    padding:3px 10px; border-radius:999px; text-transform:capitalize; }
  .subs-snip { color:var(--muted); font-size:.82rem; max-width:360px; }
  .subs-snip b { color:var(--ink); font-weight:700; }
  .subs-actions { display:flex; gap:6px; justify-content:flex-end; }
</style>
@endpush

@section('content')
  <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px;">
    <div>
      <h1 style="margin:0;font-size:1.45rem;letter-spacing:-.01em;">Profiler submissions</h1>
      <p style="margin:3px 0 0;color:var(--muted);font-size:.85rem;">
        Completed questionnaires from the Student Profiler and Profile Evaluator pages.
      </p>
    </div>
    @if($counts['all'])
      <a class="btn btn-primary" href="{{ route('admin.submissions.export') }}">
        <i data-lucide="download" style="width:16px;height:16px;"></i> Export CSV
      </a>
    @endif
  </div>

  @if(session('status'))
    <div class="panel panel-pad" style="margin-bottom:16px;padding:13px 16px;color:var(--teal-dark);font-weight:600;">{{ session('status') }}</div>
  @endif

  <div class="subs-tabs">
    <a class="subs-tab @if($source === '') is-active @endif" href="{{ route('admin.submissions.index') }}">
      All <span class="pill">{{ $counts['all'] }}</span>
    </a>
    <a class="subs-tab @if($source === 'profiler') is-active @endif" href="{{ route('admin.submissions.index', ['source' => 'profiler']) }}">
      Student Profiler <span class="pill">{{ $counts['profiler'] }}</span>
    </a>
    <a class="subs-tab @if($source === 'evaluator') is-active @endif" href="{{ route('admin.submissions.index', ['source' => 'evaluator']) }}">
      Profile Evaluator <span class="pill">{{ $counts['evaluator'] }}</span>
    </a>
  </div>

  @if(count($submissions))
    <div class="panel" style="overflow-x:auto;">
      <table class="subs-table">
        <thead>
          <tr>
            <th>Submitted</th>
            <th>Source</th>
            <th>Degree</th>
            <th>Summary</th>
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
              $isProfiler = ($s['source'] ?? '') === 'profiler';
            @endphp
            <tr>
              <td style="white-space:nowrap;color:var(--muted);">
                {{ ! empty($s['submitted_at']) ? \Illuminate\Support\Carbon::parse($s['submitted_at'])->format('M j, Y') : '—' }}
                <div style="font-size:.78rem;">{{ ! empty($s['submitted_at']) ? \Illuminate\Support\Carbon::parse($s['submitted_at'])->format('g:i A') : '' }}</div>
              </td>
              <td>
                <span class="src-badge {{ $isProfiler ? 'src-profiler' : 'src-evaluator' }}">{{ $s['source_label'] ?? ($s['source'] ?? '—') }}</span>
              </td>
              <td>
                @if(! empty($s['degree']))<span class="deg-badge">{{ $s['degree'] }}</span>@else <span style="color:var(--muted);">—</span>@endif
              </td>
              <td>
                <div class="subs-snip">
                  <b>{{ $answerCount }}</b> {{ \Illuminate\Support\Str::plural('answer', $answerCount) }}@if($firstVal) · {{ \Illuminate\Support\Str::limit($firstVal, 70) }}@endif
                </div>
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
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <div class="panel panel-pad" style="text-align:center;color:var(--muted);padding:50px 20px;">
      No submissions yet. They’ll appear here when visitors complete the
      <a href="{{ route('profiler') }}" target="_blank">Student Profiler</a> or
      <a href="{{ route('profile.evaluate') }}" target="_blank">Profile Evaluator</a>.
    </div>
  @endif
@endsection
