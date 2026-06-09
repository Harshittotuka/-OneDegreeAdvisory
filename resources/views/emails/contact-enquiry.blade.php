@extends('emails.layout', ['eyebrow' => 'New website enquiry'])

@section('content')
  <h1 style="margin:0 0 6px;font-size:20px;color:#1a0088;">New enquiry from the website</h1>
  <p style="margin:0 0 22px;font-size:14px;line-height:1.6;color:#6f6792;">
    Submitted via the Contact / Home enquiry form. Reply directly to this email to reach {{ $data['name'] }}.
  </p>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.6;border-collapse:collapse;">
    @php
      $rows = [
        'Name'                 => $data['name'] ?? null,
        'Email'                => $data['email'] ?? null,
        'Mobile'               => $data['phone'] ?? null,
        'City'                 => $data['city'] ?? null,
        'Preferred destination'=> $data['destination'] ?? null,
        'Academic level'       => $data['level'] ?? null,
      ];
    @endphp
    @foreach($rows as $label => $value)
      @if(!empty($value))
        <tr>
          <td style="padding:8px 12px;width:180px;vertical-align:top;color:#9a93b8;border-bottom:1px solid #f0eef8;">{{ $label }}</td>
          <td style="padding:8px 12px;vertical-align:top;color:#241c4a;border-bottom:1px solid #f0eef8;font-weight:600;">{{ $value }}</td>
        </tr>
      @endif
    @endforeach
  </table>

  @if(!empty($data['message']))
    <p style="margin:22px 0 6px;font-size:13px;text-transform:uppercase;letter-spacing:.4px;color:#9a93b8;">Message</p>
    <div style="padding:14px 16px;background:#faf9fd;border-radius:10px;font-size:14px;line-height:1.7;color:#241c4a;white-space:pre-wrap;">{{ $data['message'] }}</div>
  @endif

  <p style="margin:24px 0 0;font-size:12px;color:#9a93b8;">
    Marketing consent: {{ !empty($data['consent']) ? 'Yes — agreed to communications' : 'No' }}
  </p>
@endsection
