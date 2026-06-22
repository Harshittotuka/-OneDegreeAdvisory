@extends('admin.layout')

@section('title', 'Enrollments')

@push('head')
<style>
  /* Transactions is a dense data table — let it use the full section width. */
  .cms-wrap{max-width:none;}
  .en-stats{display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; margin-bottom:22px;}
  .en-stat{background:var(--panel); border:1px solid var(--line); border-radius:var(--radius); box-shadow:var(--shadow); padding:18px 18px;}
  .en-stat .k{display:flex; align-items:center; gap:8px; color:var(--muted); font-weight:700; font-size:.78rem; text-transform:uppercase; letter-spacing:.04em;}
  .en-stat .k i{width:16px;height:16px;}
  .en-stat .v{margin-top:8px; font-size:1.6rem; font-weight:800; letter-spacing:-.02em;}
  .en-tools{display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:16px;}
  .en-tools form{display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin:0;}
  .en-tools input[type=text]{width:240px;}
  .en-tools select{width:auto;}
  .en-count{color:var(--muted); font-weight:700; font-size:.85rem; margin-left:auto;}
  .en-table-wrap{background:var(--panel); border:1px solid var(--line); border-radius:var(--radius); box-shadow:var(--shadow); overflow:auto;}
  table.en{width:100%; border-collapse:collapse; font-size:.88rem; min-width:880px;}
  table.en th{text-align:left; padding:13px 14px; font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); border-bottom:1px solid var(--line); white-space:nowrap;}
  table.en td{padding:12px 14px; border-bottom:1px solid var(--line); vertical-align:top;}
  table.en tr:last-child td{border-bottom:0;}
  table.en tr:hover td{background:#faf9ff;}
  .en-name{font-weight:800;}
  .en-mail{color:var(--muted); font-size:.82rem;}
  .en-amt{font-weight:800; white-space:nowrap;}
  .en-id{font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.74rem; color:var(--muted);}
  .badge{display:inline-block; padding:4px 10px; border-radius:999px; font-size:.72rem; font-weight:800; text-transform:capitalize; white-space:nowrap;}
  .badge.paid{background:#e3f6ed; color:#0a7d4d;}
  .badge.fail{background:#fdecea; color:#c0392b;}
  .badge.wait{background:#fff5e6; color:#9a6b00;}
  .en-empty{padding:46px; text-align:center; color:var(--muted);}
  .en-empty i{width:34px; height:34px; color:var(--line);}
  table.en td.en-act-cell{vertical-align:middle;}
  /* Keep the actions in view while the wide table scrolls horizontally. */
  table.en th:last-child{position:sticky; right:0; background:var(--panel); z-index:1;}
  table.en td.en-act-cell{position:sticky; right:0; background:var(--panel); box-shadow:-9px 0 12px -10px rgba(43,44,64,.22);}
  table.en th:last-child{box-shadow:-9px 0 12px -10px rgba(43,44,64,.22);}
  table.en tr:hover td.en-act-cell{background:#faf9ff;}
  .en-actions{display:inline-flex; gap:8px; align-items:center;}
  .en-actions form{margin:0; display:inline-flex;}
  .en-status{width:150px; height:36px; padding:0 30px 0 11px; font-size:.8rem; font-weight:700; color:var(--ink);
    border:1px solid var(--line); border-radius:8px; background:#fff; cursor:pointer; appearance:none; -webkit-appearance:none;
    background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%236e6b7b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>");
    background-repeat:no-repeat; background-position:right 9px center;}
  .en-del{width:36px; height:36px; padding:0; display:inline-grid; place-items:center; flex-shrink:0; border-radius:8px;}
  .en-del i{width:15px; height:15px;}
  @media(max-width:880px){ .en-stats{grid-template-columns:repeat(2,1fr);} }
</style>
@endpush

@section('content')
  @php
    $fmt = fn ($paise) => '₹'.number_format(((int) $paise) / 100, 2);
    $badge = function (string $s): array {
      return match ($s) {
        'paid' => ['paid', 'Paid'],
        'payment_failed', 'order_failed' => ['fail', 'Failed'],
        default => ['wait', str_replace('_', ' ', $s) ?: 'Pending'],
      };
    };
  @endphp

  <div data-refresh-zone>
  <div class="en-stats">
    <div class="en-stat"><div class="k"><i data-lucide="users"></i> Total enrollments</div><div class="v">{{ number_format($stats['total']) }}</div></div>
    <div class="en-stat"><div class="k"><i data-lucide="badge-check"></i> Paid</div><div class="v">{{ number_format($stats['paid']) }}</div></div>
    <div class="en-stat"><div class="k"><i data-lucide="indian-rupee"></i> Revenue (paid)</div><div class="v">{{ $fmt($stats['revenue']) }}</div></div>
    <div class="en-stat"><div class="k"><i data-lucide="x-circle"></i> Failed</div><div class="v">{{ number_format($stats['failed']) }}</div></div>
  </div>

  <div class="en-tools">
    <form method="GET" action="{{ route('admin.enrollments.index') }}">
      <input type="text" name="q" value="{{ $q }}" placeholder="Search name, email, phone, plan, payment id…">
      <select name="status" onchange="this.form.submit()">
        <option value="">All statuses</option>
        <option value="paid" @selected($status==='paid')>Paid</option>
        <option value="order_created" @selected($status==='order_created')>Awaiting payment</option>
        <option value="payment_failed" @selected($status==='payment_failed')>Payment failed</option>
        <option value="order_failed" @selected($status==='order_failed')>Order failed</option>
      </select>
      <button class="btn btn-primary btn-sm" type="submit"><i data-lucide="search" style="width:15px;height:15px;"></i> Search</button>
      @if($q !== '' || $status !== '')
        <a class="btn btn-ghost btn-sm" href="{{ route('admin.enrollments.index') }}">Clear</a>
      @endif
    </form>
    <span class="en-count">{{ number_format(count($attempts)) }} shown</span>
  </div>

  <div class="en-table-wrap">
    @if(count($attempts))
      <table class="en">
        <thead>
          <tr>
            <th>Date</th><th>Customer</th><th>Phone</th><th>Plan</th><th>Amount</th>
            <th>Status</th><th>Page</th><th>Razorpay</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($attempts as $a)
            @php [$cls, $lbl] = $badge((string) $a->status); @endphp
            <tr>
              <td style="white-space:nowrap;">{{ optional($a->created_at)->format('d M Y') }}<br><span class="en-mail">{{ optional($a->created_at)->format('H:i') }}</span></td>
              <td><div class="en-name">{{ $a->customer_name }}</div><div class="en-mail">{{ $a->customer_email }}</div></td>
              <td>{{ $a->customer_phone ?: '—' }}</td>
              <td>{{ $a->item_name }}</td>
              <td class="en-amt">{{ $fmt($a->amount) }}</td>
              <td><span class="badge {{ $cls }}">{{ $lbl }}</span></td>
              <td>{{ $a->page_slug }}</td>
              <td>
                @if($a->razorpay_payment_id)<div class="en-id">{{ $a->razorpay_payment_id }}</div>@endif
                @if($a->razorpay_order_id)<div class="en-id">{{ $a->razorpay_order_id }}</div>@endif
                @if(! $a->razorpay_payment_id && ! $a->razorpay_order_id)—@endif
              </td>
              <td class="en-act-cell">
                @php
                  // Always show the current status as an option, even if it isn't normally editable.
                  $opts = $editable;
                  if (! isset($opts[$a->status])) { $opts = [$a->status => ucfirst(str_replace('_', ' ', (string) $a->status))] + $opts; }
                @endphp
                <div class="en-actions">
                  <form method="POST" action="{{ route('admin.enrollments.status', $a) }}">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="en-status" onchange="this.form.submit()" title="Change status">
                      @foreach($opts as $val => $lbl2)
                        <option value="{{ $val }}" @selected($a->status === $val)>{{ $lbl2 }}</option>
                      @endforeach
                    </select>
                  </form>
                  <form method="POST" action="{{ route('admin.enrollments.destroy', $a) }}"
                        onsubmit="return confirm('Delete this transaction permanently? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger en-del" title="Delete record"><i data-lucide="trash-2"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <div class="en-empty">
        <i data-lucide="inbox"></i>
        <p>No enrollments{{ ($q !== '' || $status !== '') ? ' match your filter' : ' yet' }}.</p>
      </div>
    @endif
  </div>
  </div>
@endsection
