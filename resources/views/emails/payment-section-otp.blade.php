@extends('emails.layout', [
    'title' => 'Payment section authorization',
    'eyebrow' => 'Page Builder security',
    'preheader' => 'Someone is trying to add a payment section in the website builder.',
])

@section('content')
  <h1 style="margin:0 0 12px;font-size:24px;line-height:1.25;color:#102a43;">Authorize a payment section</h1>
  <p style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#526573;">
    An admin is trying to save a page that contains a <strong>payment section</strong> in the website builder.
    Share this one-time code only with the authorized person making this change. The code expires in {{ $ttlMinutes }} minutes.
  </p>

  <div style="margin:0 0 24px;padding:20px;border:1px solid #d9e3e8;border-radius:8px;background:#f7fafb;text-align:center;">
    <div style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#71818c;">Authorization code</div>
    <div style="margin-top:8px;font-size:36px;font-weight:800;letter-spacing:.24em;color:#2b1fa8;">{{ $otp }}</div>
  </div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px;line-height:1.6;">
    <tr><td style="padding:7px 0;color:#71818c;width:150px;">Page</td><td style="padding:7px 0;font-weight:700;color:#102a43;">{{ $pageTitle }}</td></tr>
    <tr><td style="padding:7px 0;color:#71818c;">Path</td><td style="padding:7px 0;color:#102a43;">{{ $pagePath }}</td></tr>
  </table>

  <p style="margin:22px 0 0;padding:14px;border-left:4px solid #f05a28;background:#fff5f0;font-size:13px;line-height:1.6;color:#6c4a3c;">
    If you did not expect this, do not share the code. Without it, no payment section can be published on the website.
  </p>
@endsection
