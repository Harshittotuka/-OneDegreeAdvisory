<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="x-apple-disable-message-reformatting">
  <title>{{ $title ?? config('site.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f3f8;font-family:'Segoe UI',Helvetica,Arial,sans-serif;color:#241c4a;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f3f8;padding:24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 6px 24px rgba(26,0,136,.08);">

          <!-- Header -->
          <tr>
            <td style="background:#1a0088;padding:26px 32px;">
              <span style="display:inline-block;font-size:18px;font-weight:700;color:#ffffff;letter-spacing:.3px;">One Degree Advisory</span>
              <span style="display:block;margin-top:4px;font-size:12px;color:#c7c0f0;letter-spacing:.4px;text-transform:uppercase;">{{ $eyebrow ?? 'Global education strategy' }}</span>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:32px;">
              @yield('content')
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#faf9fd;padding:22px 32px;border-top:1px solid #ece9f6;font-size:12px;line-height:1.6;color:#6f6792;">
              <strong style="color:#241c4a;">One Degree Advisory</strong><br>
              {{ config('site.contact.address') }}<br>
              {{ config('site.contact.phone') }} · {{ config('site.contact.email') }}<br>
              <span style="color:#9a93b8;">This is an automated message from the One Degree Advisory website.</span>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
