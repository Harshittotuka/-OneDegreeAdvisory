@extends('emails.layout', [
    'title' => 'Payment received',
    'eyebrow' => 'Thank you',
    'preheader' => 'We have received your payment — our admissions team will be in touch shortly.',
])

@section('content')
  <h1 style="margin:0 0 12px;font-size:24px;line-height:1.25;color:#102a43;">Thank you, {{ $attempt->customer_name }}!</h1>
  <p style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#526573;">
    We have received your payment. Our admissions team will reach out to you shortly with the next steps.
  </p>

  <div style="margin:0 0 24px;padding:18px;border:1px solid #d9e3e8;border-radius:8px;background:#f7fafb;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px;line-height:1.6;">
      <tr><td style="padding:6px 0;color:#71818c;width:130px;">Plan</td><td style="padding:6px 0;font-weight:700;color:#102a43;">{{ $attempt->item_name }}</td></tr>
      <tr><td style="padding:6px 0;color:#71818c;">Amount paid</td><td style="padding:6px 0;font-size:18px;font-weight:800;color:#0a7d4d;">₹{{ number_format($attempt->amount / 100, 2) }}</td></tr>
      <tr><td style="padding:6px 0;color:#71818c;">Payment ID</td><td style="padding:6px 0;color:#102a43;">{{ $attempt->razorpay_payment_id }}</td></tr>
      <tr><td style="padding:6px 0;color:#71818c;">Date</td><td style="padding:6px 0;color:#102a43;">{{ optional($attempt->paid_at)->format('d M Y, H:i') }}</td></tr>
    </table>
  </div>

  <p style="margin:0 0 6px;font-size:14px;line-height:1.7;color:#526573;">
    If you have any questions, simply reply to this email or contact us at
    <a href="mailto:{{ config('site.contact.email') }}" style="color:#2b1fa8;">{{ config('site.contact.email') }}</a>.
  </p>
  <p style="margin:18px 0 0;font-size:14px;line-height:1.7;color:#526573;">Warm regards,<br><strong style="color:#102a43;">{{ config('site.name') }}</strong></p>
@endsection
