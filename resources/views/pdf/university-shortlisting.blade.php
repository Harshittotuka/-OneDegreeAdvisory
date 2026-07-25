<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
  @page { margin: 58px 34px 42px; }
  * { box-sizing: border-box; }
  body {
    margin: 0;
    color: #2f3045;
    font-family: DejaVu Sans, sans-serif;
    font-size: 8.2px;
    line-height: 1.28;
  }

  .running-head {
    position: fixed;
    top: -47px;
    left: 0;
    right: 0;
    height: 38px;
    border-bottom: 2px solid #ff5e32;
  }
  .mark { color: #1a0088; font-size: 18px; font-weight: 700; letter-spacing: -1px; }
  .mark span { color: #ff5e32; }
  .head-right { position: absolute; top: 2px; right: 0; text-align: right; }
  .head-right b { color: #221f5b; font-size: 8px; }
  .head-right small { display: block; color: #9aa0b3; font-size: 5px; letter-spacing: 1.6px; margin-top: 2px; }

  .footer {
    position: fixed;
    left: -34px;
    right: -34px;
    bottom: -42px;
    height: 30px;
    padding: 9px 34px 0;
    background: #221f5b;
    color: #d0cde8;
    font-size: 7px;
  }
  .footer b { color: #fff; }
  .footer .page { float: right; color: #fff; font-weight: 700; }

  /* Second footer line, drawn as its own fixed overlay rather than a child of
     .footer — the contact line uses a float, and dompdf does not clear floats
     reliably, which would overlap the two lines. */
  .footer-note {
    position: fixed;
    left: -34px;
    right: -34px;
    bottom: -42px;
    height: 30px;
    padding: 20px 34px 0;
    color: #d0cde8;
    font-size: 5.5px;
    text-align: center;
  }

  h1 {
    margin: 0;
    color: #221f5b;
    font-size: 23px;
    line-height: 1.08;
    letter-spacing: -.5px;
  }
  .accent { width: 56px; height: 4px; margin: 7px 0 7px; background: #ff5e32; }
  .subtitle { margin: 0 0 14px; color: #717386; font-size: 10px; }

  table { width: 100%; table-layout: fixed; border-collapse: collapse; }
  th, td {
    border: 1px solid #d7d5df;
    padding: 5px 6px;
    vertical-align: top;
    overflow-wrap: anywhere;
    word-wrap: break-word;
  }
  thead th {
    border-color: #3b2a95;
    background: #1a0088;
    color: #fff;
    font-size: 8.5px;
    font-weight: 700;
    text-align: left;
  }
  thead th:first-child { background: #221f5b; border-color: #221f5b; }
  tbody tr:nth-child(even) td { background: #f7f6fa; }
  tbody td.label {
    background: #eceafb !important;
    color: #221f5b;
    font-weight: 700;
  }
  tbody tr.url-row td:not(.label) {
    color: #45445c;
    font-size: 7px;
    overflow-wrap: anywhere;
    word-break: break-all;
  }
  .empty { color: #b1afbb; }
  .cell-line { display: block; }

  body.dense { font-size: 6.8px; line-height: 1.2; }
  body.dense h1 { font-size: 20px; }
  body.dense .subtitle { margin-bottom: 10px; font-size: 8.5px; }
  body.dense th, body.dense td { padding: 3px 4px; }
  body.dense thead th { font-size: 7px; }
  body.dense tbody tr.url-row td:not(.label) { font-size: 5.8px; }

  body.ultra { font-size: 5.35px; line-height: 1.12; }
  body.ultra h1 { font-size: 17px; }
  body.ultra .accent { height: 3px; margin: 4px 0; }
  body.ultra .subtitle { margin-bottom: 7px; font-size: 7px; }
  body.ultra th, body.ultra td { padding: 2px 3px; }
  body.ultra thead th { font-size: 5.6px; }
  body.ultra tbody tr.url-row td:not(.label) { font-size: 4.7px; }
</style>
</head>
<body class="{{ $density }}">
  <div class="running-head">
    <div class="mark">one<span>&deg;</span></div>
    <div class="head-right">
      <b>{{ $studentName }}</b>
      <small>CAREER REPORT</small>
    </div>
  </div>

  <div class="footer">
    <b>One Degree Advisory</b> &nbsp;|&nbsp; Contact: +91 8233365888 &nbsp;|&nbsp; counselling@onedegreeadvisory.com
    <span class="page">Page {{ $pageCount }} of {{ $pageCount }}</span>
  </div>

  {{-- Matches the second footer line ProfileReportPdf draws on the pages this
       one is appended to, so the disclaimer runs through the whole report. --}}
  <div class="footer-note">{{ \App\Support\AiDisclaimer::TEXT }}</div>

  <h1>University Shortlisting{{ $shortlist['country'] !== '' ? ' - '.$shortlist['country'] : '' }}</h1>
  <div class="accent"></div>
  <p class="subtitle">Program comparison prepared for {{ $studentName }}</p>

  @php
    $optionCount = max(1, count($shortlist['options']));
    $labelWidth = $optionCount >= 7 ? 10 : ($optionCount >= 5 ? 11 : 15);
    $optionWidth = (100 - $labelWidth) / $optionCount;
  @endphp

  <table>
    <colgroup>
      <col style="width: {{ $labelWidth }}%">
      @foreach($shortlist['options'] as $unused)
        <col style="width: {{ $optionWidth }}%">
      @endforeach
    </colgroup>
    <thead>
      <tr>
        <th>Attribute</th>
        @foreach($shortlist['options'] as $option)
          <th>{{ $option }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($shortlist['rows'] as $row)
        <tr @class(['url-row' => $row['is_url']])>
          <td class="label">{{ $row['label'] }}</td>
          @foreach($row['values'] as $value)
            <td>
              @if($value === '')
                <span class="empty">-</span>
              @else
                @foreach(explode("\n", $value) as $line)
                  <span class="cell-line">{{ $line }}</span>
                @endforeach
              @endif
            </td>
          @endforeach
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
