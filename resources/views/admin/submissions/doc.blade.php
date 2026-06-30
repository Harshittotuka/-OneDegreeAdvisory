{{-- Standalone, self-contained HTML document for the per-submission download.
     Served as a Word .doc (application/msword) — Word/Google Docs open it and it
     can be saved as PDF from there. Styling mirrors the on-screen Q&A cards. --}}
@php
  $isProfiler = ($submission['source'] ?? '') === 'profiler';
  $srcLabel   = (string) ($submission['source_label'] ?? ($submission['source'] ?? '—'));
  $degree     = trim((string) ($submission['degree'] ?? ''));
  $when       = ! empty($submission['submitted_at'])
      ? \Illuminate\Support\Carbon::parse($submission['submitted_at'])->format('M j, Y · g:i A')
      : '—';
  $meta   = is_array($submission['meta'] ?? null) ? $submission['meta'] : [];
  $cName  = trim((string) ($meta['name'] ?? ''));
  $cEmail = trim((string) ($meta['email'] ?? ''));
  $cPhone = trim((string) ($meta['phone'] ?? ''));
  $sections = $submission['sections'] ?? [];
  $srcBg = $isProfiler ? '#ebecff' : '#e6f6ec';
  $srcFg = $isProfiler ? '#4044c4' : '#1f7a45';
@endphp
<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta charset="utf-8">
<title>{{ $srcLabel }}{{ $cName ? ' — '.$cName : '' }}</title>
<style>
  body { font-family: 'Segoe UI', Arial, sans-serif; color:#181134; font-size:11.5pt; line-height:1.45; }
  h1 { font-size:20pt; margin:0 0 6pt; color:#181134; }
  .meta { margin:0 0 16pt; color:#5f5a85; font-size:10pt; }
  .badge { display:inline-block; font-size:9pt; font-weight:bold; padding:3pt 10pt; border-radius:10pt; margin-right:6pt; }
  .panel { border:1px solid #e2e3ee; border-radius:8pt; padding:12pt 16pt; margin:0 0 12pt; }
  .eyebrow { font-size:8.5pt; font-weight:bold; letter-spacing:1pt; text-transform:uppercase; color:#d8431d; margin:0 0 2pt; }
  .title { font-size:13pt; font-weight:bold; margin:0 0 8pt; color:#181134; }
  .q { font-weight:bold; font-size:10.5pt; margin:10pt 0 4pt; color:#181134; }
  .a { margin:0 0 4pt; }
  .chip { display:inline-block; background:#f1f0f7; color:#454360; font-size:10pt; padding:3pt 9pt; border-radius:6pt; margin:0 5pt 5pt 0; }
  .muted { color:#8a8597; }
  table.ct { border-collapse:collapse; }
  table.ct td { padding:4pt 22pt 4pt 0; vertical-align:top; }
  table.ct .lbl { font-size:8.5pt; font-weight:bold; text-transform:uppercase; letter-spacing:.6pt; color:#8a8597; }
</style>
</head>
<body>
  <h1>{{ $srcLabel }}</h1>
  <div class="meta">
    <span class="badge" style="background:{{ $srcBg }};color:{{ $srcFg }};">{{ $srcLabel }}</span>
    @if($degree)<span class="badge" style="background:#fff4e6;color:#9a6b00;text-transform:capitalize;">{{ $degree }}</span>@endif
    <span>Submitted {{ $when }}</span>
  </div>

  @if($cName || $cEmail || $cPhone)
    <div class="panel">
      <div class="eyebrow">Contact</div>
      <table class="ct"><tr>
        <td><div class="lbl">Name</div>{{ $cName ?: '—' }}</td>
        <td><div class="lbl">Email</div>{{ $cEmail ?: '—' }}</td>
        <td><div class="lbl">Phone</div>{{ $cPhone ?: '—' }}</td>
      </tr></table>
    </div>
  @endif

  @forelse($sections as $sec)
    <div class="panel">
      @if(! empty($sec['eyebrow']))<div class="eyebrow">{{ $sec['eyebrow'] }}</div>@endif
      @if(! empty($sec['title']))<div class="title">{{ $sec['title'] }}</div>@endif
      @foreach($sec['answers'] ?? [] as $a)
        <div class="q">{{ $a['label'] ?? '' }}</div>
        <div class="a">
          @forelse((array) ($a['value'] ?? []) as $v)
            <span class="chip">{{ $v }}</span>
          @empty
            <span class="muted">—</span>
          @endforelse
        </div>
      @endforeach
    </div>
  @empty
    <div class="panel muted" style="text-align:center;">This submission has no recorded answers.</div>
  @endforelse
</body>
</html>
