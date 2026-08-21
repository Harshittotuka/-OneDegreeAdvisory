<section class="workspace crm-subscriber-workspace">
    <div class="workspace-head">
        <div class="workspace-title">
            <h2>Blocked submissions</h2>
            <p>{{ number_format($spamAttempts->total()) }} caught by the honeypot field{{ $spamAttempts->total() === 1 ? '' : '' }} · {{ number_format($spamCount) }} total</p>
        </div>
        <span class="audit-private-label">Super admin only</span>
    </div>

    <p class="subtext" style="margin:-8px 0 16px;max-width:60ch;">
        Every public lead form carries a hidden field real visitors never see. A submission that fills it never
        reaches the CRM or sends mail — it's logged here instead, so repeat bot/script traffic from the same
        IP is visible without cluttering your leads.
    </p>

    <form id="crmSpamFilters" class="filters crm-subscriber-filters" method="get" action="{{ route('crm.dashboard') }}" data-crm-filter-form>
        <input type="hidden" name="view" value="spam">
        <div class="search-wrap"><input class="control" type="search" name="spam_search" value="{{ request('spam_search') }}" placeholder="Search IP address or form"></div>
        <button class="btn btn-outline" type="submit">Filter</button>
    </form>

    @if($spamAttempts->count())
        @include('crm.partials.list-count', ['paginator' => $spamAttempts, 'filterForm' => 'crmSpamFilters', 'noun' => 'attempt'])
        <div class="audit-list">
            @foreach($spamAttempts as $attempt)
                <article class="audit-entry">
                    <div class="audit-entry-main">
                        <span class="audit-serial">{{ $spamAttempts->firstItem() + $loop->index }}</span>
                        <span class="audit-action"><span class="audit-event">{{ str($attempt->source)->replace('-', ' ')->title() }}</span><strong>Honeypot field filled on submit</strong></span>
                        <span class="audit-meta"><strong>{{ $attempt->created_at->format('d M Y') }}</strong><small>{{ $attempt->created_at->format('g:i:s A') }}</small><small>{{ $attempt->ip_address ?: 'IP unavailable' }}</small></span>
                    </div>
                    @if($attempt->payload)
                        <details class="audit-details">
                            <summary>View submitted data</summary>
                            <div class="audit-change-groups">
                                <div class="audit-change-group">
                                    <strong>Payload</strong>
                                    @foreach($attempt->payload as $field => $value)
                                        <span><b>{{ str((string) $field)->replace('_', ' ')->title() }}</b><em>{{ is_array($value) ? implode(', ', $value) : $value }}</em></span>
                                    @endforeach
                                    @if($attempt->user_agent)
                                        <span><b>User agent</b><em>{{ $attempt->user_agent }}</em></span>
                                    @endif
                                </div>
                            </div>
                        </details>
                    @endif
                </article>
            @endforeach
        </div>
        @if($spamAttempts->hasPages())<div class="pagination-wrap">{{ $spamAttempts->onEachSide(1)->links('pagination::crm') }}</div>@endif
    @else
        <div class="empty"><span class="empty-icon">✓</span><h3>No blocked submissions</h3><p>Attempts that fill the honeypot field on a public form will show up here.</p></div>
    @endif
</section>
