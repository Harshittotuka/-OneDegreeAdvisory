<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
  /* Room at top/bottom for the running header + indigo footer bar, both drawn
     on the dompdf canvas by ProfileReportPdf (skipped on the cover page). */
  @page { margin: 112px 40px 84px; }
  * { box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; color: #3a3a55; font-size: 12px; line-height: 1.5; margin: 0; }

  /* ── Palette (One Degree brand) ──
     indigo #1a0088 · ink #221f5b · orange #ff5e32 · cream #f5f0e8 · lavender #eceafb */

  /* ────────────────────────  COVER (page 1)  ────────────────────────
     Full-bleed cream page: the bg/strip/ring divs are absolutely positioned
     with negative offsets so they paint across the @page margins too. */
  .cover-bg    { position: absolute; top: -112px; left: -40px; width: 794px; height: 1123px; background: #f5f0e8; }
  .cover-strip { position: absolute; top: -112px; left: -40px; width: 794px; height: 10px; background: #ff5e32; }
  .cover-ring1 { position: absolute; top: 430px; left: 432px; width: 460px; height: 460px; border: 64px solid #ebe3d3; border-radius: 50%; }
  .cover-ring2 { position: absolute; top: 615px; left: 556px; width: 300px; height: 300px; border: 42px solid #f6c4ae; border-radius: 50%; }

  .cover { position: relative; padding-top: 26px; }
  .cv-mark  { font-size: 46px; font-weight: 700; color: #1a0088; letter-spacing: -2px; line-height: 1; }
  .cv-mark .deg { color: #ff5e32; }
  .cv-lock1 { font-size: 12.5px; letter-spacing: 6px;   font-weight: 700; color: #221f5b; margin: 4px 0 0 2px; }
  .cv-lock2 { font-size: 8.5px;  letter-spacing: 7.5px; font-weight: 700; color: #ff5e32; margin: 3px 0 0 2px; }

  .cover-eyebrow   { margin-top: 66px; font-size: 12.5px; letter-spacing: 4.5px; font-weight: 700; color: #ff5e32; text-transform: uppercase; }
  .cover-title     { font-size: 47px; line-height: 1.05; font-weight: 700; color: #221f5b; margin: 10px 0 8px; }
  .cover-source    { font-size: 15px; font-style: italic; color: #8b90a4; }
  .cover-preplabel { margin-top: 46px; font-size: 11px; letter-spacing: 2.5px; font-weight: 700; color: #6a7089; text-transform: uppercase; }
  .cover-name      { font-size: 30px; font-weight: 700; color: #ff5e32; margin-top: 4px; }

  table.cover-card { margin-top: 26px; width: 62%; background: #ffffff; border: 1px solid #e9e2d4; border-radius: 10px; border-collapse: separate; border-spacing: 0; }
  .cover-card td { padding: 13px 16px; border-bottom: 1px solid #f2ede3; font-size: 13px; }
  .cover-card tr:last-child td { border-bottom: 0; }
  .cover-card td.k { width: 32%; color: #1a0088; font-weight: 700; }
  .cover-card td.v { color: #3a3a55; }

  .cover-date  { margin-top: 32px; font-size: 12px; color: #6a7089; }
  .cover-rule  { border: 0; border-top: 1px solid #e4dccc; margin: 16px 0 12px; }
  .cover-legal { font-size: 9.5px; font-style: italic; color: #a09a8c; line-height: 1.65; }

  /* ── Section headings ── */
  h2 { font-size: 26px; font-weight: 700; color: #221f5b; margin: 0 0 6px; }
  .rule { width: 64px; height: 5px; background: #ff5e32; border-radius: 3px; margin: 0 0 10px; }
  .lead { color: #8b90a4; font-size: 12px; margin: 0 0 8px; }

  /* ── Two-column question-card grid ── */
  table.cards { width: 100%; border-collapse: separate; border-spacing: 12px; margin: 2px -12px 0; }
  td.card { width: 50%; vertical-align: top; background: #fbf8f2; border-left: 4px solid #ffa285; border-radius: 9px; padding: 12px 15px 11px; }
  td.spacer { width: 50%; }
  .q-label { font-size: 9px; font-weight: 700; letter-spacing: .4px; color: #2a1d8f; text-transform: uppercase; line-height: 1.5; }
  .q-val { font-size: 13px; font-weight: 700; color: #221f5b; margin-top: 5px; }
  .q-val .chk { color: #ff5e32; font-weight: 700; }

  /* ── Quick Snapshot callout ── */
  .snap { background: #eceafb; border-radius: 10px; padding: 13px 16px 11px; margin-top: 14px; page-break-inside: avoid; }
  .snap-head { font-size: 13px; font-weight: 700; color: #221f5b; margin: 0 0 7px; }
  .snap-head .tick, .kp-head .tick { color: #ff5e32; }
  table.snap-grid { width: 100%; border-collapse: collapse; }
  /* Hanging indent so wrapped item lines align after the arrow. */
  table.snap-grid td { width: 50%; vertical-align: top; padding: 3px 12px 3px 14px; text-indent: -14px; font-size: 11px; color: #3a3a55; }
  table.snap-grid td .ar { color: #ff5e32; font-weight: 700; }

  /* ── Summary page: strengths / areas — simple bordered lists ──
     green #1f9d6b (strengths) · amber #d98a12 (areas to strengthen).
     Plain coloured TEXT marks only — dompdf draws small fully-rounded
     chips (border-radius: 999px) as malformed shapes. */
  table.sum-head { width: 100%; border-collapse: collapse; margin: 18px 0 7px; }
  table.sum-head td { padding: 0; vertical-align: bottom; }
  td.sum-ttl { font-size: 12.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
  td.sum-ttl.s { color: #157a52; }
  td.sum-ttl.a { color: #a9690b; }
  td.sum-n { text-align: right; font-size: 9px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: #9aa0b3; }

  table.sum { width: 100%; border-collapse: separate; border-spacing: 0; background: #fff; border: 1px solid #e5ddcc; border-radius: 8px; }
  table.sum td { vertical-align: top; padding: 10px 14px 10px 0; border-bottom: 1px solid #f0ebe0; font-size: 11.5px; line-height: 1.55; color: #3a3a55; }
  table.sum tr:last-child td { border-bottom: 0; }
  table.sum td.m { width: 32px; padding-left: 14px; padding-right: 0; font-weight: 700; }
  table.sum.s td.m { color: #1f9d6b; }
  table.sum.a td.m { color: #d98a12; }

  /* Closing "What Happens Next" panel. */
  .keypts { background: #221f5b; color: #e9e6f8; border-radius: 10px; padding: 15px 18px; font-size: 11.5px; line-height: 1.7; margin-top: 20px; page-break-inside: avoid; }
  .keypts .kp-head { font-size: 13px; font-weight: 700; color: #fff; margin: 0 0 6px; }

  .break { page-break-before: always; }
</style>
</head>
<body>

  {{-- ─────────────────────────  COVER  ───────────────────────── --}}
  <div class="cover-bg"></div>
  <div class="cover-strip"></div>
  <div class="cover-ring1"></div>
  <div class="cover-ring2"></div>

  <div class="cover">
    <div class="cv-mark">one<span class="deg">&deg;</span></div>
    <div class="cv-lock1">ONE DEGREE</div>
    <div class="cv-lock2">ADVISORY</div>

    <div class="cover-eyebrow">Career Report</div>
    <div class="cover-title">{!! implode('<br>', array_map('e', preg_split('/\s+/', trim($data['sourceLabel'] ?: 'Profile Report')))) !!}</div>
    <div class="cover-source">One Degree Advisory</div>

    <div class="cover-preplabel">Report prepared for</div>
    <div class="cover-name">{{ $data['name'] ?: 'The Applicant' }}</div>

    <table class="cover-card">
      <tr><td class="k">Phone</td><td class="v">{{ $data['phone'] ?: '—' }}</td></tr>
      <tr><td class="k">Email</td><td class="v">{{ $data['email'] ?: '—' }}</td></tr>
      @if(!empty($data['degreeLabel']))
        <tr><td class="k">Programme</td><td class="v">{{ $data['degreeLabel'] }}</td></tr>
      @endif
    </table>

    <div class="cover-date">Date of Issue: {{ now()->format('j F Y') }}</div>
    <hr class="cover-rule">
    <div class="cover-legal">
      This report is intended only for the use of the individual or entity to which it is addressed and may contain
      information that is non-public, proprietary, privileged, confidential, and exempt from disclosure under
      applicable law. It is an initial, automated summary generated from the responses shared on the One Degree
      Advisory website. No part of this report may be reproduced in any form or manner without prior written
      permission from the company.
    </div>
  </div>

  {{-- ────────────  Q&A PAGES (My Profile / My Profiling)  ──────────── --}}
  @php $qn = 0; @endphp
  @foreach(($data['pages'] ?? []) as $page)
    <div class="break">
      <h2>{{ $page['title'] }}</h2>
      <div class="rule"></div>
      <div class="lead">{{ $page['lead'] }}</div>

      <table class="cards">
        @foreach(array_chunk($page['answers'], 2) as $pair)
          <tr>
            @foreach($pair as $a)
              @php $qn++; @endphp
              <td class="card">
                <div class="q-label">Q{{ $qn }} &middot; {{ $a['label'] }}</div>
                <div class="q-val"><span class="chk">&#10003;</span> {{ $a['value'] !== '' ? $a['value'] : '—' }}</div>
              </td>
            @endforeach
            @if(count($pair) === 1)<td class="spacer"></td>@endif
          </tr>
        @endforeach
      </table>

      @if(!empty($page['callout']['items']))
        <div class="snap">
          <div class="snap-head"><span class="tick">&#10003;</span>&nbsp; {{ $page['callout']['title'] }}</div>
          <table class="snap-grid">
            @foreach(array_chunk($page['callout']['items'], 2) as $pair)
              <tr>
                @foreach($pair as $item)
                  <td><span class="ar">&rarr;</span>&nbsp; {{ $item }}</td>
                @endforeach
                @if(count($pair) === 1)<td></td>@endif
              </tr>
            @endforeach
          </table>
        </div>
      @endif
    </div>
  @endforeach

  {{-- ──────────────────  SUMMARY & WAY FORWARD  ────────────────── --}}
  <div class="break">
    <h2>Summary &amp; Way Forward</h2>
    <div class="rule"></div>
    <div class="lead">An honest first read of your profile — every profile is personally reviewed by our advisors</div>

    @if(!empty($data['strengths']))
      <table class="sum-head">
        <tr>
          <td class="sum-ttl s">Strengths</td>
          <td class="sum-n">{{ count($data['strengths']) }} noted</td>
        </tr>
      </table>
      <table class="sum s">
        @foreach($data['strengths'] as $s)
          <tr><td class="m">&#10003;</td><td>{{ $s }}</td></tr>
        @endforeach
      </table>
    @endif

    @if(!empty($data['improvements']))
      <table class="sum-head">
        <tr>
          <td class="sum-ttl a">Areas to strengthen</td>
          <td class="sum-n">{{ count($data['improvements']) }} to work on</td>
        </tr>
      </table>
      <table class="sum a">
        @foreach($data['improvements'] as $s)
          <tr><td class="m">!</td><td>{{ $s }}</td></tr>
        @endforeach
      </table>
    @endif

    @if(!empty($data['closing']))
      <div class="keypts">
        <div class="kp-head"><span class="tick">&#10003;</span>&nbsp; What Happens Next</div>
        {{ $data['closing'] }}
      </div>
    @endif
  </div>

</body>
</html>
