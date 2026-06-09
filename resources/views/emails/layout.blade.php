<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="x-apple-disable-message-reformatting">
  <title>{{ $title ?? config('site.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#eef3f6;font-family:Arial,'Segoe UI',sans-serif;color:#14213d;">
  @if(!empty($preheader))
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">{{ $preheader }}</div>
  @endif

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef3f6;padding:28px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;background:#ffffff;border:1px solid #d9e3e8;border-radius:8px;overflow:hidden;">
          <tr>
            <td style="background:#102a43;padding:26px 30px;border-bottom:4px solid #0f7a78;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td>
                    <div style="font-size:20px;font-weight:700;line-height:1.2;color:#ffffff;">One Degree Advisory</div>
                    <div style="margin-top:5px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#c9d7df;">{{ $eyebrow ?? 'Global education strategy' }}</div>
                  </td>
                  <td align="right" style="font-size:12px;color:#c9d7df;white-space:nowrap;">{{ now()->format('d M Y') }}</td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding:30px;">
              @yield('content')
            </td>
          </tr>

          <tr>
            <td style="background:#f7fafb;padding:22px 30px;border-top:1px solid #d9e3e8;font-size:12px;line-height:1.7;color:#5f6f7a;">
              <strong style="display:block;margin-bottom:3px;color:#102a43;">One Degree Advisory</strong>
              {{ config('site.contact.address') }}<br>
              {{ config('site.contact.phone') }} | {{ config('site.contact.email') }}<br>
              <span style="color:#7c8d96;">This message was sent by the One Degree Advisory website.</span>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
