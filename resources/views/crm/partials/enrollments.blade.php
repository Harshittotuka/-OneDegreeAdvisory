<section class="workspace crm-source-workspace">
    <div class="workspace-head">
        <div class="workspace-title"><h2>Website payments</h2><p>{{ number_format($enrollments->total()) }} payment record{{ $enrollments->total() === 1 ? '' : 's' }} in this view</p></div>
        <a class="btn btn-outline" href="{{ route('crm.enrollments.export', request()->only(['search', 'payment_status', 'enrollment_source', 'enrollment_plan'])) }}" data-native-navigation>Export CSV</a>
    </div>

    <form id="crmEnrollmentFilters" class="filters crm-enrollment-filters" method="get" action="{{ route('crm.dashboard') }}" data-crm-filter-form>
        <input type="hidden" name="view" value="enrollments">
        <div class="search-wrap"><input class="control" type="search" name="search" value="{{ request('search') }}" placeholder="Search student, program, payment or order ID"></div>
        <select class="control" name="payment_status"><option value="">All payment statuses</option>@foreach($paymentStatuses as $key=>$label)<option value="{{ $key }}" @selected(request('payment_status')===$key)>{{ $label }}</option>@endforeach</select>
        <select class="control" name="enrollment_source"><option value="">All source pages</option>@foreach($enrollmentSources as $source)<option value="{{ $source }}" @selected(request('enrollment_source')===$source)>{{ str($source)->replace('-',' ')->title() }}</option>@endforeach</select>
        <select class="control" name="enrollment_plan"><option value="">All programs</option>@foreach($enrollmentPlans as $plan)<option value="{{ $plan }}" @selected(request('enrollment_plan')===$plan)>{{ $plan }}</option>@endforeach</select>
        <button class="btn btn-outline" type="submit">Filter</button>
    </form>

    @if($enrollments->count())
        @include('crm.partials.list-count', ['paginator' => $enrollments, 'filterForm' => 'crmEnrollmentFilters', 'noun' => 'payment'])
        <div class="table-wrap"><table class="enrollment-table">
            <thead><tr><th class="col-serial">Serial No</th><th>Student</th><th>Program</th><th>Source</th><th>Amount</th><th>Payment details</th><th>Created</th>@if($crmUser->isSuperAdmin())<th>Manage</th>@endif</tr></thead>
            <tbody>@foreach($enrollments as $attempt)<tr>
                <td class="col-serial">{{ $enrollments->firstItem() + $loop->index }}</td>
                <td><div class="lead-primary"><span class="lead-avatar">{{ $initials($attempt->customer_name) }}</span><span class="lead-name">@if($attempt->crm_lead_id)<a href="{{ route('crm.dashboard',['view'=>'enrollments','lead'=>$attempt->crm_lead_id]) }}"><strong>{{ $attempt->customer_name }}</strong></a>@else<strong>{{ $attempt->customer_name }}</strong>@endif<span>{{ $attempt->customer_email }}@if($attempt->customer_phone) · {{ $attempt->customer_phone }}@endif</span></span></div></td>
                <td><strong>{{ $attempt->item_name }}</strong><span class="subtext">Option {{ $attempt->option_index + 1 }}</span></td>
                <td><span class="source-page-pill">{{ str($attempt->page_slug)->replace('-',' ')->title() }}</span><span class="subtext">{{ $attempt->block_id }}</span></td>
                <td><strong>{{ $attempt->currency }} {{ number_format($attempt->amount / 100, 2) }}</strong>@if($attempt->paid_at)<span class="subtext">Paid {{ $attempt->paid_at->format('d M Y') }}</span>@endif</td>
                <td><span class="badge status-{{ $attempt->status === 'paid' ? 'converted' : (in_array($attempt->status, ['order_created','order_creating']) ? 'follow_up' : 'dropped') }}">{{ $paymentStatuses[$attempt->status] ?? str($attempt->status)->replace('_',' ')->title() }}</span>@if($attempt->razorpay_payment_id)<span class="payment-reference">Payment: {{ $attempt->razorpay_payment_id }}</span>@endif @if($attempt->razorpay_order_id)<span class="payment-reference">Order: {{ $attempt->razorpay_order_id }}</span>@endif @if($attempt->failure_reason)<span class="payment-failure">{{ $attempt->failure_reason }}</span>@endif</td>
                <td>{{ $attempt->created_at->format('d M Y') }}<span class="subtext">{{ $attempt->created_at->format('g:i A') }}</span></td>
                @if($crmUser->isSuperAdmin())<td><div class="crm-payment-actions"><form method="post" action="{{ route('crm.enrollments.update',$attempt) }}">@csrf @method('PATCH')<select name="status" class="control">@foreach($paymentStatuses as $key=>$label)<option value="{{ $key }}" @selected($attempt->status===$key)>{{ $label }}</option>@endforeach</select><button class="btn btn-outline btn-compact" type="submit">Save</button></form><form method="post" action="{{ route('crm.enrollments.destroy',$attempt) }}" onsubmit="return confirm('Delete this transaction? The linked student record will remain.')">@csrf @method('DELETE')<button class="btn btn-danger btn-compact" type="submit">Delete</button></form></div></td>@endif
            </tr>@endforeach</tbody>
        </table></div>
        @if($enrollments->hasPages())<div class="pagination-wrap">{{ $enrollments->onEachSide(1)->links('pagination::crm') }}</div>@endif
    @else
        <div class="empty"><h3>No website payments found</h3><p>Try changing the filters. New website checkout records will appear here automatically.</p></div>
    @endif
</section>
