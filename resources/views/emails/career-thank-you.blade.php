@extends('emails.layout', [
  'eyebrow' => 'Application received',
  'preheader' => 'Your application has reached One Degree Advisory.',
])

@section('content')
  @php
    $firstName = trim(explode(' ', $data['name'] ?? '')[0] ?? '');
    $rolePhrase = !empty($data['role']) ? ' for the '.$data['role'].' role' : '';
  @endphp

  <h1 style="margin:0 0 12px;font-size:25px;line-height:1.25;color:#102a43;">Thank you{{ $firstName ? ', '.$firstName : '' }}.</h1>

  <p style="margin:0 0 18px;font-size:16px;line-height:1.75;color:#243b53;">
    We have received your application{{ $rolePhrase }}. It will be reviewed by the One Degree Advisory team.
  </p>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0;border:1px solid #d9e3e8;border-radius:8px;border-collapse:separate;overflow:hidden;">
    <tr>
      <td style="padding:16px 18px;background:#f7fafb;border-bottom:1px solid #d9e3e8;">
        <div style="font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#0f7a78;">Application status</div>
      </td>
    </tr>
    <tr>
      <td style="padding:16px 18px;font-size:15px;line-height:1.7;color:#243b53;">
        <strong style="color:#102a43;">Received</strong><br>
        Your profile, note, and resume details have reached our careers inbox.
      </td>
    </tr>
    <tr>
      <td style="padding:0 18px 16px;font-size:15px;line-height:1.7;color:#243b53;">
        <strong style="color:#102a43;">Review</strong><br>
        If there is a fit, we will contact you within ten working days.
      </td>
    </tr>
    <tr>
      <td style="padding:0 18px 18px;font-size:15px;line-height:1.7;color:#243b53;">
        <strong style="color:#102a43;">Next conversation</strong><br>
        Shortlisted candidates will hear from us by email or phone for the next step.
      </td>
    </tr>
  </table>

  <p style="margin:24px 0 0;font-size:15px;line-height:1.7;color:#243b53;">
    You can reply to this email if you need to correct anything in your application.
  </p>

  <p style="margin:26px 0 0;font-size:15px;line-height:1.7;color:#243b53;">
    Warm regards,<br>
    <strong style="color:#102a43;">One Degree Advisory Careers</strong>
  </p>
@endsection
