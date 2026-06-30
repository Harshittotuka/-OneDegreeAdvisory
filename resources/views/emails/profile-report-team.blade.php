@extends('emails.layout', [
  'eyebrow' => 'New profile submission',
  'preheader' => 'A new '.$data['sourceLabel'].' submission has arrived'.($data['name'] ? ' from '.$data['name'] : '').'. Report attached.',
])

@section('content')
  <div style="display:inline-block;margin-bottom:14px;padding:6px 10px;background:#e6f4f1;color:#0f6f6d;border-radius:999px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">{{ $data['sourceLabel'] }}</div>

  <h1 style="margin:0 0 10px;font-size:24px;line-height:1.25;color:#102a43;">
    New profile{{ $data['name'] ? ' from '.$data['name'] : '' }}
  </h1>

  <p style="margin:0 0 22px;font-size:15px;line-height:1.7;color:#435563;">
    A visitor completed the <strong>{{ $data['sourceLabel'] }}</strong>@if(!empty($data['degreeLabel'])) for a <strong>{{ $data['degreeLabel'] }}</strong>@endif.
    The full profile report — key facts, an initial strengths/areas read, and all responses — is attached as a PDF.
  </p>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 22px;border:1px solid #d9e3e8;border-radius:8px;border-collapse:separate;overflow:hidden;font-size:14px;line-height:1.55;">
    <tr>
      <td style="width:42%;padding:12px 14px;background:#f7fafb;border-bottom:1px solid #e5edf1;color:#60717d;font-weight:700;">Name</td>
      <td style="padding:12px 14px;border-bottom:1px solid #e5edf1;color:#102a43;font-weight:700;">{{ $data['name'] ?: '—' }}</td>
    </tr>
    <tr>
      <td style="padding:12px 14px;background:#f7fafb;border-bottom:1px solid #e5edf1;color:#60717d;font-weight:700;">Email</td>
      <td style="padding:12px 14px;border-bottom:1px solid #e5edf1;color:#102a43;font-weight:700;">{{ $data['email'] ?: '—' }}</td>
    </tr>
    <tr>
      <td style="padding:12px 14px;background:#f7fafb;color:#60717d;font-weight:700;">Phone</td>
      <td style="padding:12px 14px;color:#102a43;font-weight:700;">{{ $data['phone'] ?: '—' }}</td>
    </tr>
  </table>

  @if($pdf === null)
    <p style="margin:0 0 18px;padding:12px 14px;background:#fdf1f1;border:1px solid #f3cccc;border-radius:8px;font-size:13px;line-height:1.6;color:#9b2c2c;">
      The report PDF could not be generated for this submission — please open the admin submissions panel to view the full responses.
    </p>
  @else
    <p style="margin:0 0 18px;font-size:14px;line-height:1.7;color:#5f6f7a;">
      📎 Attachment: <strong style="color:#102a43;">{{ $pdfName }}</strong>
    </p>
  @endif

  {{-- Quick-reply button for the team's inbox. Suppressed only in the PDF
       review export ($pdfMode), where a mailto link is not useful. --}}
  @if(empty($pdfMode) && filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL))
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:4px;">
      <tr>
        <td style="background:#0f7a78;border-radius:6px;">
          <a href="mailto:{{ $data['email'] }}" style="display:inline-block;padding:12px 18px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">Reply to {{ $data['name'] ?: $data['email'] }}</a>
        </td>
      </tr>
    </table>
  @endif
@endsection
