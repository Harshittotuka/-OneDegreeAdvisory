@extends('emails.layout', [
  'eyebrow' => 'An introduction',
  'preheader' => $data['referrer_name'].' thought One Degree Advisory could help with your plans to study abroad.',
])

@section('content')
  @php
    $firstName = \Illuminate\Support\Str::of($data['student_name'] ?? '')->trim()->explode(' ')->first() ?: 'there';
    $helps = [
      'Choosing the right course and university' => 'Shortlists built around your profile, budget and goals — not a fixed list.',
      'Applications and documents' => 'SOPs, LORs and everything the university actually asks for.',
      'Visa preparation' => 'Documentation, financials and interview practice.',
      'Loans and accommodation' => 'Education loan options and verified student housing.',
    ];
  @endphp

  <div style="display:inline-block;margin-bottom:14px;padding:6px 10px;background:#e6f4f1;color:#0f6f6d;border-radius:999px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Introduction</div>

  <h1 style="margin:0 0 10px;font-size:24px;line-height:1.25;color:#102a43;">Hello {{ $firstName }}</h1>

  <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#435563;">
    <strong>{{ $data['referrer_name'] }}</strong> passed your details on to us, thinking we might be able to help with your
    plans to study abroad. We are One Degree Advisory, an education advisory based in Jaipur, and we guide students from
    the first shortlist through to the visa.
  </p>

  @php
    // "a Master's in Germany" / "a Master's" / "study in Germany" — built here so
    // the sentence reads naturally whichever of the two the referrer filled in.
    $level = trim((string) ($data['level'] ?? ''));
    $country = trim((string) ($data['country'] ?? ''));
    $interest = match (true) {
        $level !== '' && $country !== '' => $level.' study in '.$country,
        $level !== '' => $level.' study',
        $country !== '' => 'studying in '.$country,
        default => '',
    };
  @endphp

  @if($interest !== '')
    <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#435563;">
      They mentioned you are looking at <strong>{{ $interest }}</strong>. If that is right we can get straight into
      specifics; if it is not, no problem at all — the first conversation is about working out where you actually are.
    </p>
  @endif

  <h2 style="margin:0 0 8px;font-size:14px;color:#102a43;text-transform:uppercase;letter-spacing:.06em;">What we help with</h2>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.6;">
    @foreach($helps as $title => $detail)
      <tr>
        <td width="18" valign="top" style="padding:8px 0;color:#0f7a78;font-weight:700;">&bull;</td>
        <td style="padding:8px 0;color:#243b53;">
          <strong style="color:#102a43;">{{ $title }}</strong><br>
          <span style="color:#5f6f7a;">{{ $detail }}</span>
        </td>
      </tr>
    @endforeach
  </table>

  <p style="margin:20px 0 0;font-size:15px;line-height:1.7;color:#435563;">
    A counsellor will reach out over the next day or two. There is no cost to that first conversation and no obligation
    to go any further.
  </p>

  <table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:22px;">
    <tr>
      <td style="background:#0f7a78;border-radius:6px;">
        <a href="{{ route('home') }}" style="display:inline-block;padding:12px 18px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">See what we do</a>
      </td>
      <td style="width:10px;"></td>
      <td style="background:#102a43;border-radius:6px;">
        <a href="{{ route('contact') }}" style="display:inline-block;padding:12px 18px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;">Talk to us</a>
      </td>
    </tr>
  </table>

  <p style="margin:24px 0 0;font-size:12px;line-height:1.6;color:#7c8d96;">
    You are receiving this because {{ $data['referrer_name'] }} shared your contact details with us and confirmed they had
    your permission to do so. If you would rather not hear from us, just reply with "no thanks" and we will remove your
    details right away.
  </p>
@endsection
