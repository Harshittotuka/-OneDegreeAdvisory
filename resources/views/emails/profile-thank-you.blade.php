@extends('emails.layout', [
  'eyebrow' => 'Profile received',
  'preheader' => 'Thanks for completing your profile. Your report is attached as a PDF.',
])

@section('content')
  @php($firstName = trim(explode(' ', $data['name'] ?? '')[0] ?? ''))

  <h1 style="margin:0 0 12px;font-size:25px;line-height:1.25;color:#102a43;">Thank you{{ $firstName ? ', '.$firstName : '' }}.</h1>

  <p style="margin:0 0 18px;font-size:16px;line-height:1.75;color:#243b53;">
    We have received your {{ $data['sourceLabel'] }}@if(!empty($data['degreeLabel'])) for a <strong>{{ $data['degreeLabel'] }}</strong>@endif.
    @if($pdf !== null)
      We have attached a copy of your <strong>profile report</strong> (PDF) — it summarises your key details, an initial read of your strengths and the areas to work on, and all of your responses.
    @else
      Our team will review the details you shared and reach out to you shortly.
    @endif
  </p>

  @if($pdf !== null)
    <p style="margin:0 0 18px;font-size:14px;line-height:1.7;color:#5f6f7a;">
      📎 Attached: <strong style="color:#102a43;">{{ $pdfName }}</strong>
    </p>
  @endif

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:6px 0 22px;background:#eef6f5;border:1px solid #cfe6e3;border-radius:8px;border-collapse:separate;overflow:hidden;">
    <tr>
      <td style="padding:15px 16px;font-size:15px;line-height:1.7;color:#0f5f5d;">
        An advisor from One Degree Advisory will personally review your profile and contact you with a detailed analysis and the right next steps.
      </td>
    </tr>
  </table>

  <p style="margin:18px 0 0;font-size:15px;line-height:1.7;color:#243b53;">
    You can reply to this email with anything else you would like us to know.
  </p>

  <p style="margin:26px 0 0;font-size:15px;line-height:1.7;color:#243b53;">
    Warm regards,<br>
    <strong style="color:#102a43;">Team One Degree Advisory</strong>
  </p>
@endsection
