@extends('emails.layout', [
  'eyebrow' => 'CRM notification',
  'preheader' => $messageText,
])

@section('content')
  <h1 style="margin:0 0 12px;font-size:24px;line-height:1.3;color:#102a43;">{{ $headline }}</h1>
  <p style="margin:0 0 20px;font-size:15px;line-height:1.75;color:#243b53;">{{ $messageText }}</p>

  @if($details !== [])
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;border:1px solid #d9e3e8;border-radius:8px;border-collapse:separate;overflow:hidden;">
      @foreach($details as $label => $value)
        <tr>
          <td style="width:36%;padding:11px 14px;background:#f7fafb;border-bottom:1px solid #e5ecef;font-size:12px;font-weight:700;color:#0f7a78;">{{ $label }}</td>
          <td style="padding:11px 14px;border-bottom:1px solid #e5ecef;font-size:14px;line-height:1.5;color:#243b53;">{{ is_array($value) ? implode(', ', $value) : $value }}</td>
        </tr>
      @endforeach
    </table>
  @endif

  @if($actionUrl)
    <p style="margin:24px 0 0;">
      <a href="{{ $actionUrl }}" style="display:inline-block;padding:12px 20px;border-radius:8px;background:#0f7a78;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">{{ $actionLabel }}</a>
    </p>
  @endif
@endsection
