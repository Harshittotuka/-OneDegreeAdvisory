@extends('emails.layout', [
  'eyebrow' => 'Secure CRM access',
  'preheader' => 'Your One Degree CRM verification code is '.$otp.'.',
])

@section('content')
  <h1 style="margin:0 0 12px;font-size:25px;line-height:1.25;color:#102a43;">Your CRM login code</h1>
  <p style="margin:0 0 18px;font-size:16px;line-height:1.75;color:#243b53;">
    Hello {{ $crmUser->name }}, use this one-time code to securely sign in to the One Degree CRM.
  </p>
  <div style="margin:24px 0;padding:22px;border:1px solid #d9e3e8;border-radius:10px;background:#f7fafb;text-align:center;">
    <div style="font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#0f7a78;">Verification code</div>
    <div style="margin-top:10px;font-size:34px;font-weight:800;letter-spacing:.24em;color:#102a43;">{{ $otp }}</div>
  </div>
  <p style="margin:0;font-size:15px;line-height:1.7;color:#243b53;">
    This code expires in {{ config('crm.otp.ttl_minutes') }} minutes. If you did not request it, you can safely ignore this email.
  </p>
@endsection
