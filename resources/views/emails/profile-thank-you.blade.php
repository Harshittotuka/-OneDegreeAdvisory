@extends('emails.layout', [
  'eyebrow' => 'Profile received',
  'preheader' => 'Thanks for completing your profile. Here is a copy with our initial analysis.',
])

@section('content')
  @php($firstName = trim(explode(' ', $data['name'] ?? '')[0] ?? ''))

  <h1 style="margin:0 0 12px;font-size:25px;line-height:1.25;color:#102a43;">Thank you{{ $firstName ? ', '.$firstName : '' }}.</h1>

  <p style="margin:0 0 18px;font-size:16px;line-height:1.75;color:#243b53;">
    We have received your {{ $data['sourceLabel'] }}@if(!empty($data['degreeLabel'])) for a <strong>{{ $data['degreeLabel'] }}</strong>@endif.
    An advisor from One Degree Advisory will personally review it and reach out to help you plan the next step. A copy of your responses and our initial analysis is below for your records.
  </p>

  @include('emails.partials.profile-report', ['data' => $data])

  <p style="margin:24px 0 0;font-size:15px;line-height:1.7;color:#243b53;">
    You can reply to this email with anything else you would like us to know.
  </p>

  <p style="margin:26px 0 0;font-size:15px;line-height:1.7;color:#243b53;">
    Warm regards,<br>
    <strong style="color:#102a43;">Team One Degree Advisory</strong>
  </p>
@endsection
