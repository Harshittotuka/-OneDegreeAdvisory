@extends('admin.layout')
@section('title', 'Submission')

@push('head')
<style>
  .sub-meta { display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:22px; }
  .src-badge { display:inline-block; font-size:.74rem; font-weight:800; padding:4px 12px; border-radius:999px; }
  .src-profiler { background:#ebecff; color:#4044c4; }
  .src-evaluator { background:#e6f6ec; color:#1f7a45; }
  .deg-badge { display:inline-block; background:#fff4e6; color:#9a6b00; font-size:.74rem; font-weight:800;
    padding:4px 12px; border-radius:999px; text-transform:capitalize; }
  .sub-when { color:var(--muted); font-size:.86rem; font-weight:600; }

  .sub-sec { margin-bottom:18px; }
  .sub-sec h2 { margin:0 0 2px; font-size:.7rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:var(--teal-dark); }
  .sub-sec h3 { margin:0 0 12px; font-size:1.02rem; font-weight:800; }
  .qa { padding:13px 0; border-top:1px solid var(--line); }
  .qa:first-of-type { border-top:0; }
  .qa .q { font-weight:700; font-size:.9rem; margin-bottom:7px; color:var(--ink); }
  .qa .a { display:flex; flex-wrap:wrap; gap:6px; }
  .qa .chip { display:inline-block; background:#f1f0f7; color:#454360; font-size:.84rem; font-weight:600;
    padding:5px 11px; border-radius:8px; }
</style>
@endpush

@php
  $isProfiler = ($submission['source'] ?? '') === 'profiler';
  $backRoute  = $isProfiler ? 'admin.submissions.profiler' : 'admin.submissions.evaluator';
@endphp

@section('content')
  <div style="margin-bottom:18px;">
    <a class="btn btn-ghost btn-sm" href="{{ route($backRoute) }}">
      <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Back to {{ $isProfiler ? 'Student Profiler' : 'Profile Evaluator' }}
    </a>
  </div>

  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:8px;">
    <h1 style="margin:0;font-size:1.4rem;letter-spacing:-.01em;">{{ $submission['source_label'] ?? 'Submission' }}</h1>
    <form method="POST" action="{{ route('admin.submissions.destroy') }}"
          onsubmit="return confirm('Delete this submission permanently?');">
      @csrf
      <input type="hidden" name="id" value="{{ $submission['id'] ?? '' }}">
      <button class="btn btn-danger btn-sm" type="submit">
        <i data-lucide="trash-2" style="width:14px;height:14px;"></i> Delete
      </button>
    </form>
  </div>

  <div class="sub-meta">
    <span class="src-badge {{ $isProfiler ? 'src-profiler' : 'src-evaluator' }}">{{ $submission['source_label'] ?? ($submission['source'] ?? '—') }}</span>
    @if(! empty($submission['degree']))<span class="deg-badge">{{ $submission['degree'] }}</span>@endif
    <span class="sub-when">
      <i data-lucide="clock" style="width:14px;height:14px;vertical-align:-2px;"></i>
      {{ ! empty($submission['submitted_at']) ? \Illuminate\Support\Carbon::parse($submission['submitted_at'])->format('M j, Y · g:i A') : '—' }}
    </span>
  </div>

  @php $sections = $submission['sections'] ?? []; @endphp
  @if(count($sections))
    @foreach($sections as $sec)
      <div class="panel panel-pad sub-sec" style="padding:18px 20px;">
        @if(! empty($sec['eyebrow']))<h2>{{ $sec['eyebrow'] }}</h2>@endif
        @if(! empty($sec['title']))<h3>{{ $sec['title'] }}</h3>@endif
        @foreach($sec['answers'] ?? [] as $a)
          <div class="qa">
            <div class="q">{{ $a['label'] ?? '' }}</div>
            <div class="a">
              @forelse((array) ($a['value'] ?? []) as $v)
                <span class="chip">{{ $v }}</span>
              @empty
                <span style="color:var(--muted);">—</span>
              @endforelse
            </div>
          </div>
        @endforeach
      </div>
    @endforeach
  @else
    <div class="panel panel-pad" style="text-align:center;color:var(--muted);padding:40px 20px;">
      This submission has no recorded answers.
    </div>
  @endif
@endsection
