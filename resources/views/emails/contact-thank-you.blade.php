@extends('emails.layout', [
  'eyebrow' => 'Enquiry received',
  'preheader' => 'Thanks for reaching out to One Degree Advisory. Our team will contact you shortly.',
])

@section('content')
  @php($firstName = trim(explode(' ', $data['name'] ?? '')[0] ?? ''))

  <h1 style="margin:0 0 12px;font-size:25px;line-height:1.25;color:#102a43;">Thank you{{ $firstName ? ', '.$firstName : '' }}.</h1>

  <p style="margin:0 0 18px;font-size:16px;line-height:1.75;color:#243b53;">
    We have received your enquiry. An advisor from One Degree Advisory will review your details and reach out to help you plan the next step.
  </p>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0;border:1px solid #d9e3e8;border-radius:8px;border-collapse:separate;overflow:hidden;">
    <tr>
      <td style="padding:16px 18px;background:#f7fafb;border-bottom:1px solid #d9e3e8;">
        <div style="font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#0f7a78;">What happens next</div>
      </td>
    </tr>
    <tr>
      <td style="padding:16px 18px;font-size:15px;line-height:1.7;color:#243b53;">
        <strong style="color:#102a43;">1. Profile review</strong><br>
        We look at your preferred destination, academic level, timeline, and goals.
      </td>
    </tr>
    <tr>
      <td style="padding:0 18px 16px;font-size:15px;line-height:1.7;color:#243b53;">
        <strong style="color:#102a43;">2. Advisor callback</strong><br>
        Our team will contact you on the phone number or email address you shared.
      </td>
    </tr>
    <tr>
      <td style="padding:0 18px 18px;font-size:15px;line-height:1.7;color:#243b53;">
        <strong style="color:#102a43;">3. Clear next steps</strong><br>
        You will receive practical guidance on shortlist, applications, tests, scholarships, or visa readiness.
      </td>
    </tr>
  </table>

  @if(!empty($data['message']))
    <h2 style="margin:24px 0 8px;font-size:14px;color:#102a43;text-transform:uppercase;letter-spacing:.06em;">Your note to us</h2>
    <div style="padding:16px 18px;background:#f7fafb;border:1px solid #d9e3e8;border-radius:8px;font-size:15px;line-height:1.7;color:#243b53;white-space:pre-wrap;">{{ $data['message'] }}</div>
  @endif

  <p style="margin:24px 0 0;font-size:15px;line-height:1.7;color:#243b53;">
    You can reply to this email with anything else you want us to know.
  </p>

  <p style="margin:26px 0 0;font-size:15px;line-height:1.7;color:#243b53;">
    Warm regards,<br>
    <strong style="color:#102a43;">Team One Degree Advisory</strong>
  </p>
@endsection
