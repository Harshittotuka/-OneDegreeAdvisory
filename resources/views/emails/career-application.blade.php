@extends('emails.layout', [
  'eyebrow' => 'New career application',
  'preheader' => 'A new career application has arrived from '.$data['name'].'.',
])

@section('content')
  @php
    $resumeFile = ! empty($data['resume']['name']) ? $data['resume']['name'].' - attached to this email' : null;
    $rows = [
      'Applicant' => $data['name'] ?? null,
      'Email' => $data['email'] ?? null,
      'Mobile' => $data['phone'] ?? null,
      'LinkedIn' => $data['linkedin'] ?? null,
      'Role of interest' => $data['role'] ?? null,
      'Experience' => $data['experience'] ?? null,
      'Resume file' => $resumeFile,
      'Resume link' => $data['resume_link'] ?? null,
    ];
  @endphp

  <div style="display:inline-block;margin-bottom:14px;padding:6px 10px;background:#fff4db;color:#8a5a00;border-radius:999px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Hiring lead</div>

  <h1 style="margin:0 0 10px;font-size:24px;line-height:1.25;color:#102a43;">{{ $data['name'] }} applied for {{ $data['role'] }}</h1>

  <p style="margin:0 0 22px;font-size:15px;line-height:1.7;color:#435563;">
    This application came from the Careers page. Reply to this email to respond directly to the applicant.
  </p>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d9e3e8;border-radius:8px;border-collapse:separate;overflow:hidden;font-size:14px;line-height:1.55;">
    @foreach($rows as $label => $value)
      @if(!empty($value))
        <tr>
          <td style="width:42%;padding:12px 14px;background:#f7fafb;border-bottom:1px solid #e5edf1;color:#60717d;font-weight:700;">{{ $label }}</td>
          <td style="padding:12px 14px;border-bottom:1px solid #e5edf1;color:#102a43;font-weight:700;">
            @if(in_array($label, ['LinkedIn', 'Resume link'], true))
              <a href="{{ $value }}" style="color:#0f7a78;text-decoration:underline;">{{ $value }}</a>
            @else
              {{ $value }}
            @endif
          </td>
        </tr>
      @endif
    @endforeach
  </table>

  @if(!empty($data['message']))
    <h2 style="margin:24px 0 8px;font-size:14px;color:#102a43;text-transform:uppercase;letter-spacing:.06em;">Cover note</h2>
    <div style="padding:16px 18px;background:#f7fafb;border:1px solid #d9e3e8;border-radius:8px;font-size:15px;line-height:1.7;color:#243b53;white-space:pre-wrap;">{{ $data['message'] }}</div>
  @endif

  <table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:24px;">
    <tr>
      <td style="background:#0f7a78;border-radius:6px;">
        <a href="mailto:{{ $data['email'] }}" style="display:inline-block;padding:12px 18px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">Reply to applicant</a>
      </td>
    </tr>
  </table>

  <p style="margin:22px 0 0;font-size:12px;line-height:1.6;color:#7c8d96;">
    Storage consent: {{ !empty($data['consent']) ? 'Yes, agreed to store application details' : 'No' }}
  </p>
@endsection
