@extends('emails.layout', [
    'title' => 'New enrolment payment',
    'eyebrow' => 'Payment received',
    'preheader' => $attempt->customer_name.' paid for '.$attempt->item_name,
])

@section('content')
  <h1 style="margin:0 0 12px;font-size:24px;line-height:1.25;color:#102a43;">Payment received</h1>
  <p style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#526573;">
    A website visitor has completed a payment. Details below — reply to this email to reach the customer directly.
  </p>

  <div style="margin:0 0 22px;padding:16px 18px;border:1px solid #d9e3e8;border-radius:8px;background:#f7fafb;">
    <div style="font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#71818c;">Amount paid</div>
    <div style="margin-top:4px;font-size:28px;font-weight:800;color:#0a7d4d;">₹{{ number_format($attempt->amount / 100, 2) }}</div>
    <div style="margin-top:2px;font-size:13px;color:#526573;">{{ $attempt->item_name }}</div>
  </div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px;line-height:1.6;">
    <tr><td style="padding:7px 0;color:#71818c;width:150px;">Name</td><td style="padding:7px 0;font-weight:700;color:#102a43;">{{ $attempt->customer_name }}</td></tr>
    <tr><td style="padding:7px 0;color:#71818c;">Email</td><td style="padding:7px 0;color:#102a43;">{{ $attempt->customer_email }}</td></tr>
    <tr><td style="padding:7px 0;color:#71818c;">Phone</td><td style="padding:7px 0;color:#102a43;">{{ $attempt->customer_phone ?: 'Not provided' }}</td></tr>
    <tr><td style="padding:7px 0;color:#71818c;">Plan</td><td style="padding:7px 0;font-weight:700;color:#102a43;">{{ $attempt->item_name }}</td></tr>
    <tr><td style="padding:7px 0;color:#71818c;">Page</td><td style="padding:7px 0;color:#102a43;">{{ $attempt->page_slug }}</td></tr>
    <tr><td style="padding:7px 0;color:#71818c;">Payment ID</td><td style="padding:7px 0;color:#102a43;">{{ $attempt->razorpay_payment_id }}</td></tr>
    <tr><td style="padding:7px 0;color:#71818c;">Order ID</td><td style="padding:7px 0;color:#102a43;">{{ $attempt->razorpay_order_id }}</td></tr>
    <tr><td style="padding:7px 0;color:#71818c;">Paid at</td><td style="padding:7px 0;color:#102a43;">{{ optional($attempt->paid_at)->format('d M Y, H:i') }}</td></tr>
  </table>
@endsection
