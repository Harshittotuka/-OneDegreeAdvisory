{{-- Shared profile-report body: key facts, a basic strengths/areas read, and
     the full Q&A. Rendered inside both the team-notification and the applicant
     thank-you emails. Expects $data from App\Support\ProfileReportBuilder. --}}

@if(!empty($data['highlights']))
  <h2 style="margin:6px 0 10px;font-size:14px;color:#102a43;text-transform:uppercase;letter-spacing:.06em;">Profile at a glance</h2>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border:1px solid #d9e3e8;border-radius:8px;border-collapse:separate;overflow:hidden;font-size:14px;line-height:1.55;">
    @foreach($data['highlights'] as $label => $value)
      <tr>
        <td style="width:42%;padding:12px 14px;background:#f7fafb;border-bottom:1px solid #e5edf1;color:#60717d;font-weight:700;">{{ $label }}</td>
        <td style="padding:12px 14px;border-bottom:1px solid #e5edf1;color:#102a43;font-weight:700;">{{ $value }}</td>
      </tr>
    @endforeach
  </table>
@endif

<h2 style="margin:6px 0 10px;font-size:14px;color:#102a43;text-transform:uppercase;letter-spacing:.06em;">About your profile</h2>

@if(!empty($data['strengths']))
  <div style="font-size:13px;font-weight:700;color:#0f7a78;margin:0 0 8px;text-transform:uppercase;letter-spacing:.04em;">Strengths</div>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px;border:1px solid #cfe6e3;border-radius:8px;border-collapse:separate;overflow:hidden;">
    @foreach($data['strengths'] as $i => $s)
      <tr>
        <td style="padding:12px 15px;font-size:15px;line-height:1.65;color:#243b53;background:#f3faf9;{{ $i + 1 < count($data['strengths']) ? 'border-bottom:1px solid #dcefe9;' : '' }}">
          <span style="color:#0f7a78;font-weight:700;">&#10003;</span>&nbsp; {{ $s }}
        </td>
      </tr>
    @endforeach
  </table>
@endif

@if(!empty($data['improvements']))
  <div style="font-size:13px;font-weight:700;color:#b9770e;margin:0 0 8px;text-transform:uppercase;letter-spacing:.04em;">Areas to strengthen</div>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px;border:1px solid #f0dcb0;border-radius:8px;border-collapse:separate;overflow:hidden;">
    @foreach($data['improvements'] as $i => $s)
      <tr>
        <td style="padding:12px 15px;font-size:15px;line-height:1.65;color:#243b53;background:#fdf8ee;{{ $i + 1 < count($data['improvements']) ? 'border-bottom:1px solid #f3e6c6;' : '' }}">
          <span style="color:#b9770e;font-weight:700;">!</span>&nbsp; {{ $s }}
        </td>
      </tr>
    @endforeach
  </table>
@endif

@if(!empty($data['closing']))
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;background:#eef6f5;border:1px solid #cfe6e3;border-radius:8px;border-collapse:separate;overflow:hidden;">
    <tr>
      <td style="padding:15px 16px;font-size:14px;line-height:1.7;color:#0f5f5d;">{{ $data['closing'] }}</td>
    </tr>
  </table>
@endif

<h2 style="margin:6px 0 10px;font-size:14px;color:#102a43;text-transform:uppercase;letter-spacing:.06em;">Full responses</h2>
@forelse($data['sections'] as $section)
  @if(!empty($section['answers']))
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;border:1px solid #d9e3e8;border-radius:8px;border-collapse:separate;overflow:hidden;font-size:14px;line-height:1.55;">
      <tr>
        <td colspan="2" style="padding:12px 14px;background:#102a43;color:#ffffff;font-weight:700;font-size:13px;letter-spacing:.04em;">
          {{ $section['title'] ?: 'Section' }}
        </td>
      </tr>
      @foreach($section['answers'] as $a)
        <tr>
          <td style="width:46%;padding:11px 14px;background:#f7fafb;border-bottom:1px solid #e5edf1;color:#60717d;font-weight:700;vertical-align:top;">{{ $a['label'] }}</td>
          <td style="padding:11px 14px;border-bottom:1px solid #e5edf1;color:#102a43;vertical-align:top;">{{ implode(', ', (array) ($a['value'] ?? [])) }}</td>
        </tr>
      @endforeach
    </table>
  @endif
@empty
  <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#5f6f7a;">No answers were captured for this submission.</p>
@endforelse
