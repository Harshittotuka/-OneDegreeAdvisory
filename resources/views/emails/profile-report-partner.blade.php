@extends('emails.layout', [
  'eyebrow' => 'Referral received',
  'preheader' => 'A student who used your link'.($data['name'] ? ' ('.$data['name'].')' : '').' has completed the Student Profiler.',
])

{{--
    Referral notice to a partner company (ProfileReportPartnerMail).

    Sent alongside our own team notification whenever a profiler submission
    arrives through /profiler?partner=CODE. It carries who came in, how to reach
    them and the headline facts only — the full report PDF stays internal.
--}}

@section('content')
  @php
    $student = $data['name'] ?: 'A student';
    $contact = $partner->contact_name ? \Illuminate\Support\Str::of($partner->contact_name)->trim()->explode(' ')->first() : null;
    // The at-a-glance facts the builder already pulled out. Trimmed to the few a
    // referring partner actually asks about; the rest is ours to advise on.
    $shown = collect($data['highlights'] ?? [])
        ->only(['Preferred destination', 'Target degree', 'Course / specialisation', 'Target intake'])
        ->filter(fn ($value) => trim((string) $value) !== '');
  @endphp

  <div style="display:inline-block;margin-bottom:14px;padding:6px 10px;background:#e6f4f1;color:#0f6f6d;border-radius:999px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Code {{ $partner->code }}</div>

  <h1 style="margin:0 0 10px;font-size:24px;line-height:1.25;color:#102a43;">
    {{ $student }} completed the Student Profiler
  </h1>

  <p style="margin:0 0 22px;font-size:15px;line-height:1.7;color:#435563;">
    Hello {{ $contact ?: $partner->company_name }} — a student who came to us through your referral link has just
    filled in the {{ $data['sourceLabel'] }}@if(!empty($data['degreeLabel'])) for a <strong>{{ $data['degreeLabel'] }}</strong>@endif.
    Our advisors will review the profile and reach out to them directly.
  </p>

  <h2 style="margin:0 0 8px;font-size:14px;color:#102a43;text-transform:uppercase;letter-spacing:.06em;">The student</h2>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 22px;border:1px solid #d9e3e8;border-radius:8px;border-collapse:separate;overflow:hidden;font-size:14px;line-height:1.55;">
    <tr>
      <td style="width:42%;padding:12px 14px;background:#f7fafb;border-bottom:1px solid #e5edf1;color:#60717d;font-weight:700;">Name</td>
      <td style="padding:12px 14px;border-bottom:1px solid #e5edf1;color:#102a43;font-weight:700;">{{ $data['name'] ?: '—' }}</td>
    </tr>
    <tr>
      <td style="padding:12px 14px;background:#f7fafb;border-bottom:1px solid #e5edf1;color:#60717d;font-weight:700;">Email</td>
      <td style="padding:12px 14px;border-bottom:1px solid #e5edf1;color:#102a43;font-weight:700;">{{ $data['email'] ?: '—' }}</td>
    </tr>
    <tr>
      <td style="padding:12px 14px;background:#f7fafb;@if($shown->isNotEmpty())border-bottom:1px solid #e5edf1;@endif color:#60717d;font-weight:700;">Phone</td>
      <td style="padding:12px 14px;@if($shown->isNotEmpty())border-bottom:1px solid #e5edf1;@endif color:#102a43;font-weight:700;">{{ $data['phone'] ?: '—' }}</td>
    </tr>
    @foreach($shown as $label => $value)
      <tr>
        <td style="padding:12px 14px;background:#f7fafb;@unless($loop->last)border-bottom:1px solid #e5edf1;@endunless color:#60717d;font-weight:700;">{{ $label }}</td>
        <td style="padding:12px 14px;@unless($loop->last)border-bottom:1px solid #e5edf1;@endunless color:#102a43;font-weight:700;">{{ $value }}</td>
      </tr>
    @endforeach
  </table>

  <p style="margin:0 0 6px;font-size:14px;line-height:1.7;color:#5f6f7a;">
    Recorded against <strong style="color:#102a43;">{{ $partner->company_name }}</strong>, code
    <strong style="color:#102a43;">{{ $partner->code }}</strong>. Keep sharing that link and every profile submitted
    through it reaches you here.
  </p>

  <table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:22px;">
    <tr>
      <td style="background:#0f7a78;border-radius:6px;">
        <a href="{{ $partner->profilerUrl() }}" style="display:inline-block;padding:12px 18px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">Open your referral link</a>
      </td>
    </tr>
  </table>

  <p style="margin:22px 0 0;font-size:12px;line-height:1.6;color:#7c8d96;">
    These are the details the student shared with us so you can recognise your referral. Their full profile assessment
    stays with our advisory team. Questions about this referral? Just reply to this email.
  </p>
@endsection
