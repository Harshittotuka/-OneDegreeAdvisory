<section class="workspace crm-subscriber-workspace">
    <div class="workspace-head">
        <div class="workspace-title">
            <h2>Newsletter subscriptions</h2>
            <p>{{ number_format($subscribers->total()) }} subscription{{ $subscribers->total() === 1 ? '' : 's' }}</p>
        </div>
        <a class="btn btn-outline" href="{{ route('crm.subscribers.export', request()->only(['subscriber_search', 'subscriber_status', 'subscriber_source'])) }}" data-native-navigation>Export CSV</a>
    </div>

    <div class="subscriber-summary" aria-label="Subscription summary">
        <div><strong>{{ number_format($subscriberCount) }}</strong><span>Total subscriptions</span></div>
        <div><strong>{{ number_format($subscriberActiveCount) }}</strong><span>Active</span></div>
        <div><strong>{{ number_format($subscriberSources->count()) }}</strong><span>Signup sources</span></div>
    </div>

    <form id="crmSubscriberFilters" class="filters crm-subscriber-filters" method="get" action="{{ route('crm.dashboard') }}" data-crm-filter-form>
        <input type="hidden" name="view" value="subscriptions">
        <div class="search-wrap"><input class="control" type="search" name="subscriber_search" value="{{ request('subscriber_search') }}" placeholder="Search email address"></div>
        @include('crm.partials.multi-filter', [
            'name' => 'subscriber_status', 'options' => ['active' => 'Active', 'unsubscribed' => 'Unsubscribed'],
            'selected' => \App\Support\CrmFilter::raw(request(), 'subscriber_status'),
            'placeholder' => 'All statuses', 'label' => 'Filter by status', 'noun' => 'statuses',
        ])
        @include('crm.partials.multi-filter', [
            'name' => 'subscriber_source',
            'options' => collect($subscriberSources)->mapWithKeys(fn ($source) => [$source => $source])->all(),
            'selected' => \App\Support\CrmFilter::raw(request(), 'subscriber_source'),
            'placeholder' => 'All signup sources', 'label' => 'Filter by signup source', 'noun' => 'sources',
        ])
        <button class="btn btn-outline" type="submit">Filter</button>
    </form>

    @if($subscribers->count())
        @include('crm.partials.list-count', ['paginator' => $subscribers, 'filterForm' => 'crmSubscriberFilters', 'noun' => 'subscription'])
        <div class="table-wrap"><table class="subscriber-table">
            <thead><tr><th class="col-serial">Serial No</th><th>Email</th><th>Signup source</th><th>Status</th><th>Subscribed</th>@if($crmUser->isSuperAdmin())<th>Manage</th>@endif</tr></thead>
            <tbody>@foreach($subscribers as $subscriber)<tr>
                <td class="col-serial">{{ $subscribers->firstItem() + $loop->index }}</td>
                <td><a class="subscriber-email" href="mailto:{{ $subscriber->email }}">{{ $subscriber->email }}</a></td>
                <td>{{ $subscriber->source ?: 'Website newsletter' }}</td>
                <td><span class="subscriber-status is-{{ $subscriber->status }}">{{ $subscriber->status === 'active' ? 'Active' : 'Unsubscribed' }}</span></td>
                <td>{{ $subscriber->subscribed_at->format('d M Y') }}<span class="subtext">{{ $subscriber->subscribed_at->format('g:i A') }}</span></td>
                @if($crmUser->isSuperAdmin())<td><div class="subscriber-actions">
                    <form method="post" action="{{ route('crm.subscribers.update', $subscriber) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $subscriber->status === 'active' ? 'unsubscribed' : 'active' }}"><button class="btn btn-outline btn-compact" type="submit">{{ $subscriber->status === 'active' ? 'Unsubscribe' : 'Reactivate' }}</button></form>
                    <form method="post" action="{{ route('crm.subscribers.destroy', $subscriber) }}" onsubmit="return confirm('Remove this subscriber permanently?')">@csrf @method('DELETE')<button class="btn btn-danger btn-compact" type="submit">Delete</button></form>
                </div></td>@endif
            </tr>@endforeach</tbody>
        </table></div>
        @if($subscribers->hasPages())<div class="pagination-wrap">{{ $subscribers->onEachSide(1)->links('pagination::crm') }}</div>@endif
    @else
        <div class="empty"><span class="empty-icon">@</span><h3>No subscriptions found</h3><p>Newsletter signups will appear here automatically.</p></div>
    @endif
</section>
