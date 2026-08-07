@extends('emails.layout', [
  'eyebrow' => 'Referral received',
  'preheader' => 'We have logged your referral of '.$data['student_name'].' and a counsellor will reach out to them shortly.',
])

@section('content')
  @php
    $firstName = \Illuminate\Support\Str::of($data['referrer_name'] ?? '')->trim()->explode(' ')->first() ?: 'there';
    $steps = [
      'The student receives their visa' => "Once {$data['student_name']}'s visa is approved and confirmed.",
      'They report to their university' => 'Enrolment is confirmed when they join their program.',
      'We verify the enrolment' => 'Our team confirms the details with the university.',
      'Your reward is released' => 'Paid by bank transfer or UPI, typically within 4-8 weeks of verification.',
    ];
  @endphp

  <div style="display:inline-block;margin-bottom:14px;padding:6px 10px;background:#fdece7;color:#b8431f;border-radius:999px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Referral program</div>

  <h1 style="margin:0 0 10px;font-size:24px;line-height:1.25;color:#102a43;">Thank you, {{ $firstName }}</h1>

  <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#435563;">
    We have recorded your referral of <strong>{{ $data['student_name'] }}</strong>. One of our counsellors will reach out
    to them shortly, and we have sent them a short introduction letting them know you passed their details on.
  </p>

  <h2 style="margin:0 0 8px;font-size:14px;color:#102a43;text-transform:uppercase;letter-spacing:.06em;">What we recorded</h2>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #d9e3e8;border-radius:8px;border-collapse:separate;overflow:hidden;font-size:14px;line-height:1.55;">
    <tr>
      <td style="width:42%;padding:12px 14px;background:#f7fafb;border-bottom:1px solid #e5edf1;color:#60717d;font-weight:700;">Student</td>
      <td style="padding:12px 14px;border-bottom:1px solid #e5edf1;color:#102a43;font-weight:700;">{{ $data['student_name'] }}</td>
    </tr>
    @if(!empty($data['level']))
      <tr>
        <td style="width:42%;padding:12px 14px;background:#f7fafb;border-bottom:1px solid #e5edf1;color:#60717d;font-weight:700;">Study level</td>
        <td style="padding:12px 14px;border-bottom:1px solid #e5edf1;color:#102a43;font-weight:700;">{{ $data['level'] }}</td>
      </tr>
    @endif
    @if(!empty($data['country']))
      <tr>
        <td style="width:42%;padding:12px 14px;background:#f7fafb;border-bottom:1px solid #e5edf1;color:#60717d;font-weight:700;">Preferred country</td>
        <td style="padding:12px 14px;border-bottom:1px solid #e5edf1;color:#102a43;font-weight:700;">{{ $data['country'] }}</td>
      </tr>
    @endif
  </table>

  <h2 style="margin:24px 0 8px;font-size:14px;color:#102a43;text-transform:uppercase;letter-spacing:.06em;">When your reward arrives</h2>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.6;">
    @foreach($steps as $title => $detail)
      <tr>
        <td width="26" valign="top" style="padding:8px 0;color:#b8431f;font-weight:700;">{{ $loop->iteration }}.</td>
        <td style="padding:8px 0;color:#243b53;">
          <strong style="color:#102a43;">{{ $title }}</strong><br>
          <span style="color:#5f6f7a;">{{ $detail }}</span>
        </td>
      </tr>
    @endforeach
  </table>

  <p style="margin:22px 0 0;font-size:15px;line-height:1.7;color:#435563;">
    You can refer as many students as you like — each successful enrolment is rewarded separately.
  </p>

  <table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:22px;">
    <tr>
      <td style="background:#b8431f;border-radius:6px;">
        <a href="{{ route('referral') }}" style="display:inline-block;padding:12px 18px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">Refer another student</a>
      </td>
    </tr>
  </table>

  <p style="margin:22px 0 0;font-size:12px;line-height:1.6;color:#7c8d96;">
    Reward structures can vary by destination, program level and promotional period, and are confirmed with you at the
    time of referral. Questions? Just reply to this email.
  </p>
@endsection
