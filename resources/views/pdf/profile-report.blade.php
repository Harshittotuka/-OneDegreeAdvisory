<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
  @page { margin: 28px 34px; }
  * { box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; color: #243b53; font-size: 12px; line-height: 1.5; margin: 0; }

  .header { background: #102a43; color: #fff; padding: 18px 20px; border-radius: 6px; }
  .header .brand { font-size: 18px; font-weight: 700; }
  .header .eyebrow { font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: #c9d7df; margin-top: 3px; }
  .header .meta { font-size: 10px; color: #c9d7df; margin-top: 8px; }

  h2 { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #102a43; margin: 22px 0 8px; padding-bottom: 5px; border-bottom: 2px solid #0f7a78; }
  .sub { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; margin: 0 0 6px; }
  .sub.s { color: #0f7a78; }
  .sub.a { color: #b9770e; }

  table { width: 100%; border-collapse: collapse; }
  td { vertical-align: top; }

  .kv td { padding: 7px 10px; border: 1px solid #e5edf1; font-size: 12px; }
  .kv td.k { width: 38%; background: #f7fafb; color: #60717d; font-weight: 700; }
  .kv td.v { color: #102a43; font-weight: 700; }

  .list { border: 1px solid #e5edf1; border-radius: 5px; margin: 0 0 10px; }
  .list td { padding: 8px 11px; border-bottom: 1px solid #eef2f5; font-size: 12px; }
  .list tr:last-child td { border-bottom: 0; }
  .list.s td { background: #f3faf9; }
  .list.a td { background: #fdf8ee; }
  .mark { font-weight: 700; }
  .mark.s { color: #0f7a78; }
  .mark.a { color: #b9770e; }

  .qa { margin: 0 0 12px; border: 1px solid #d9e3e8; border-radius: 5px; }
  .qa .sec { background: #102a43; color: #fff; font-weight: 700; font-size: 11px; letter-spacing: .4px; padding: 8px 11px; }
  .qa td { padding: 7px 11px; border-bottom: 1px solid #eef2f5; font-size: 12px; }
  .qa tr:last-child td { border-bottom: 0; }
  .qa td.q { width: 46%; background: #f7fafb; color: #60717d; font-weight: 700; }
  .qa td.an { color: #102a43; }

  .callout { background: #eef6f5; border: 1px solid #cfe6e3; border-radius: 5px; padding: 11px 13px; color: #0f5f5d; font-size: 11px; margin: 4px 0 0; }
  .foot { margin-top: 18px; padding-top: 10px; border-top: 1px solid #d9e3e8; color: #7c8d96; font-size: 9.5px; }
</style>
</head>
<body>

  <div class="header">
    <div class="brand">One Degree Advisory</div>
    <div class="eyebrow">{{ $data['sourceLabel'] }}{{ !empty($data['degreeLabel']) ? ' · '.$data['degreeLabel'] : '' }} — Profile Report</div>
    <div class="meta">Prepared for {{ $data['name'] ?: 'the applicant' }} &nbsp;·&nbsp; {{ now()->format('d M Y') }}</div>
  </div>

  <h2>Applicant details</h2>
  <table class="kv">
    <tr><td class="k">Name</td><td class="v">{{ $data['name'] ?: '—' }}</td></tr>
    <tr><td class="k">Email</td><td class="v">{{ $data['email'] ?: '—' }}</td></tr>
    <tr><td class="k">Phone</td><td class="v">{{ $data['phone'] ?: '—' }}</td></tr>
  </table>

  @if(!empty($data['highlights']))
    <h2>Profile at a glance</h2>
    <table class="kv">
      @foreach($data['highlights'] as $label => $value)
        <tr><td class="k">{{ $label }}</td><td class="v">{{ $value }}</td></tr>
      @endforeach
    </table>
  @endif

  <h2>About this profile</h2>
  @if(!empty($data['strengths']))
    <div class="sub s">Strengths</div>
    <table class="list s">
      @foreach($data['strengths'] as $s)
        <tr><td><span class="mark s">&#10003;</span>&nbsp; {{ $s }}</td></tr>
      @endforeach
    </table>
  @endif
  @if(!empty($data['improvements']))
    <div class="sub a">Areas to strengthen</div>
    <table class="list a">
      @foreach($data['improvements'] as $s)
        <tr><td><span class="mark a">!</span>&nbsp; {{ $s }}</td></tr>
      @endforeach
    </table>
  @endif
  @if(!empty($data['closing']))
    <div class="callout">{{ $data['closing'] }}</div>
  @endif

  <h2>Full responses</h2>
  @forelse($data['sections'] as $section)
    @if(!empty($section['answers']))
      <table class="qa">
        <tr><td class="sec" colspan="2">{{ $section['title'] ?: 'Section' }}</td></tr>
        @foreach($section['answers'] as $a)
          <tr>
            <td class="q">{{ $a['label'] }}</td>
            <td class="an">{{ implode(', ', (array) ($a['value'] ?? [])) }}</td>
          </tr>
        @endforeach
      </table>
    @endif
  @empty
    <p>No answers were captured for this submission.</p>
  @endforelse

  <div class="foot">
    This report was generated automatically from the applicant's responses on the One Degree Advisory website. It is an initial summary, not a final assessment — our team reviews every profile personally.
  </div>

</body>
</html>
