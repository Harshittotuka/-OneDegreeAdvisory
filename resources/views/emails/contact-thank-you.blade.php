@extends('emails.layout', ['eyebrow' => 'Thanks for getting in touch'])

@section('content')
  @php($firstName = trim(explode(' ', $data['name'] ?? '')[0] ?? ''))
  <h1 style="margin:0 0 14px;font-size:22px;color:#1a0088;">Thank you{{ $firstName ? ', '.$firstName : '' }}!</h1>

  <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#3b3168;">
    We've received your enquiry and a member of the One Degree Advisory team will be in touch shortly to plan your next steps.
  </p>

  <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#3b3168;">
    In the meantime, feel free to reply to this email with anything else you'd like us to know about your goals, timeline, or the programs and universities you're considering.
  </p>

  @if(!empty($data['message']))
    <p style="margin:24px 0 6px;font-size:13px;text-transform:uppercase;letter-spacing:.4px;color:#9a93b8;">Your message to us</p>
    <div style="padding:14px 16px;background:#faf9fd;border-radius:10px;font-size:14px;line-height:1.7;color:#241c4a;white-space:pre-wrap;">{{ $data['message'] }}</div>
  @endif

  <p style="margin:28px 0 0;font-size:15px;line-height:1.7;color:#3b3168;">
    Warm regards,<br>
    <strong style="color:#1a0088;">The One Degree Advisory team</strong>
  </p>
@endsection
