@extends('emails.layout', [
    'title' => 'Your CRM login code',
    'eyebrow' => 'Secure CRM access',
    'preheader' => 'Your secure One Degree CRM login code is ready.',
])

@section('content')
    <div style="font-size:13px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#0f7a78;">One-time password</div>
    <h1 style="margin:10px 0 12px;font-size:27px;line-height:1.25;color:#102a43;">Sign in securely, {{ $crmUser->name }}</h1>
    <p style="margin:0;font-size:15px;line-height:1.75;color:#526674;">Use the verification code below to finish signing in to your One Degree CRM account.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;">
        <tr>
            <td align="center" style="padding:22px;border:1px solid #cfe1df;border-radius:12px;background:#f1f8f7;">
                <div style="font-size:34px;line-height:1;letter-spacing:.28em;text-indent:.28em;font-weight:800;color:#0f5f5d;">{{ $otp }}</div>
                <div style="margin-top:12px;font-size:12px;color:#61757f;">Valid for {{ config('crm.otp.ttl_minutes') }} minutes</div>
            </td>
        </tr>
    </table>

    <p style="margin:0;font-size:13px;line-height:1.7;color:#6b7d87;">For your security, never share this code. If you did not request it, you can safely ignore this email.</p>
@endsection
