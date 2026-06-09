@extends('emails.layout', ['eyebrow' => 'Application received'])

@section('content')
  @php
    $firstName = trim(explode(' ', $data['name'] ?? '')[0] ?? '');
    $rolePhrase = !empty($data['role']) ? ' for the '.$data['role'].' role' : '';
  @endphp
  <h1 style="margin:0 0 14px;font-size:22px;color:#1a0088;">Thank you{{ $firstName ? ', '.$firstName : '' }}!</h1>

  <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#3b3168;">
    Thank you for your interest in joining One Degree Advisory. We've received your application{{ $rolePhrase }} and it will be reviewed by a partner — not a bot.
  </p>

  <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#3b3168;">
    If there's a fit, we'll reach out to you within <strong>ten working days</strong>. We appreciate the time you took to share your story with us.
  </p>

  <p style="margin:28px 0 0;font-size:15px;line-height:1.7;color:#3b3168;">
    Warm regards,<br>
    <strong style="color:#1a0088;">The One Degree Advisory partners</strong>
  </p>
@endsection
