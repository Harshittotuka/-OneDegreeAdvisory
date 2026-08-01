@php
    $newInviteId = session('new_invite');
    $newInvite = $newInviteId ? $mockInvites->firstWhere('id', $newInviteId) : null;
@endphp
<section class="workspace crm-invite-workspace">
    <div class="workspace-head">
        <div class="workspace-title">
            <h2>Mock interview links</h2>
            <p>{{ number_format($mockInvites->total()) }} link{{ $mockInvites->total() === 1 ? '' : 's' }} issued · each one unlocks an extended round the public page cannot</p>
        </div>
        <a class="btn btn-outline" href="{{ route('visa-mock') }}" target="_blank" rel="noopener" data-native-navigation>Open the free page</a>
    </div>

    @if($newInvite)
        <div class="invite-fresh" data-invite-fresh>
            <div class="invite-fresh-copy">
                <strong>Link ready for {{ $newInvite->recipient_name }}</strong>
                <span>{{ $newInvite->question_count }} questions · usable {{ $newInvite->max_uses }} times · expires {{ $newInvite->expires_at?->format('d M Y') ?? 'never' }}</span>
            </div>
            <input class="control invite-fresh-url" type="text" readonly value="{{ $newInvite->shareUrl() }}" aria-label="Interview link" onfocus="this.select()">
            <button class="btn btn-primary" type="button" data-copy-link="{{ $newInvite->shareUrl() }}">Copy link</button>
        </div>
    @endif

    <form class="invite-create" method="post" action="{{ route('crm.mock-invites.store') }}">@csrf
        <div class="invite-create-head">
            <h3>Generate a new link</h3>
            <p>Pick the question count you want to give this student. The free page stays capped at 10 — only this link opens the rest of the {{ $mockQuestionTotal }}-question bank.</p>
        </div>
        <div class="form-grid">
            <div @class(['field', 'has-error' => $errors->has('recipient_name')])>
                <label for="invite_name">Student name <span class="required">*</span></label>
                <input id="invite_name" name="recipient_name" value="{{ old('recipient_name') }}" required>
                @error('recipient_name')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div @class(['field', 'has-error' => $errors->has('question_count')])>
                <label for="invite_count">Question count <span class="required">*</span></label>
                <select id="invite_count" name="question_count" required>
                    @foreach($mockInviteCounts as $count)
                        <option value="{{ $count }}" @selected((int) old('question_count', 15) === $count)>{{ $count }} questions{{ $count === $mockQuestionTotal ? ' — all categories' : '' }}</option>
                    @endforeach
                </select>
                @error('question_count')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div @class(['field', 'has-error' => $errors->has('recipient_email')])>
                <label for="invite_email">Email <span class="field-optional">optional</span></label>
                <input id="invite_email" name="recipient_email" type="email" value="{{ old('recipient_email') }}">
                <span class="field-help">Recorded for your reference only — nothing is emailed automatically.</span>
                @error('recipient_email')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div @class(['field', 'has-error' => $errors->has('recipient_phone')])>
                <label for="invite_phone">Mobile <span class="field-optional">optional</span></label>
                <input id="invite_phone" name="recipient_phone" inputmode="tel" value="{{ old('recipient_phone') }}">
                @error('recipient_phone')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div @class(['field', 'has-error' => $errors->has('destination')])>
                <label for="invite_destination">Destination country <span class="field-optional">optional</span></label>
                <input id="invite_destination" name="destination" value="{{ old('destination') }}" placeholder="e.g. United Kingdom">
                <span class="field-help">Pre-fills the student's setup screen.</span>
                @error('destination')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div @class(['field', 'has-error' => $errors->has('expires_in_days')])>
                <label for="invite_expiry">Expires in</label>
                <select id="invite_expiry" name="expires_in_days">
                    @foreach([7 => '7 days', 14 => '14 days', 30 => '30 days', 90 => '90 days', 365 => '1 year'] as $days => $label)
                        <option value="{{ $days }}" @selected((int) old('expires_in_days', 30) === $days)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('expires_in_days')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div @class(['field', 'invite-field-wide', 'has-error' => $errors->has('notes')])>
                <label for="invite_notes">Internal note <span class="field-optional">optional</span></label>
                <input id="invite_notes" name="notes" value="{{ old('notes') }}" placeholder="Why this student needs the extended round">
                @error('notes')<span class="field-error">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="invite-create-foot">
            <span class="field-help">Every link is usable 3 times. Attempts are counted when the student presses Start, so a refresh costs nothing.</span>
            <button class="btn btn-primary" type="submit">Generate link</button>
        </div>
    </form>

    <form class="filters crm-invite-filters" method="get" action="{{ route('crm.dashboard') }}" data-crm-filter-form>
        <input type="hidden" name="view" value="mock-invites">
        <div class="search-wrap"><input class="control" type="search" name="invite_search" value="{{ request('invite_search') }}" placeholder="Search student name, email or mobile"></div>
        <button class="btn btn-outline" type="submit">Filter</button>
    </form>

    @if($mockInvites->count())
        <div class="table-wrap"><table class="invite-table">
            <thead><tr><th>Student</th><th>Round</th><th>Attempts</th><th>Status</th><th>Issued</th><th>Share</th></tr></thead>
            <tbody>@foreach($mockInvites as $invite)
                @php
                    $completed = $invite->attempts->whereNotNull('completed_at');
                    $contact = collect([$invite->recipient_email, $invite->recipient_phone])->filter()->implode(' · ');
                @endphp
                <tr @class(['is-dead' => ! $invite->isUsable()])>
                    <td>
                        <strong>{{ $invite->recipient_name }}</strong>
                        <span class="subtext">{{ $contact !== '' ? $contact : 'No contact recorded' }}</span>
                        @if($invite->notes)<span class="subtext">{{ $invite->notes }}</span>@endif
                    </td>
                    <td>{{ $invite->question_count }} questions<span class="subtext">{{ $invite->destination ?: 'No destination set' }}</span></td>
                    <td>
                        <strong>{{ $invite->uses_count }} of {{ $invite->max_uses }}</strong>
                        <span class="subtext">{{ $invite->remainingUses() }} remaining</span>
                        @if($invite->attempts->count())
                            <details class="invite-attempts">
                                <summary>{{ $completed->count() }} completed</summary>
                                <ul class="plain">
                                    @foreach($invite->attempts->sortBy('started_at') as $attempt)
                                        <li>
                                            {{ $attempt->started_at->format('d M, g:i A') }} —
                                            @if($attempt->isComplete())
                                                {{ $attempt->questions_answered }} answered{{ $attempt->overall_score !== null ? ', scored '.number_format($attempt->overall_score, 1).'/10' : '' }}
                                            @else
                                                started, no report yet
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </td>
                    <td><span class="invite-status is-{{ $invite->state() }}">{{ $invite->statusLabel() }}</span>@if($invite->expires_at)<span class="subtext">{{ $invite->isExpired() ? 'Expired' : 'Expires' }} {{ $invite->expires_at->format('d M Y') }}</span>@endif</td>
                    <td>{{ $invite->created_at->format('d M Y') }}<span class="subtext">{{ $invite->creator?->name ?? 'Deleted user' }}</span></td>
                    <td><div class="invite-actions">
                        <button class="btn btn-outline btn-compact" type="button" data-copy-link="{{ $invite->shareUrl() }}">Copy link</button>
                        @if(! $invite->isRevoked() && ($crmUser->isSuperAdmin() || $invite->created_by === $crmUser->id))
                            <form method="post" action="{{ route('crm.mock-invites.revoke', $invite) }}" onsubmit="return confirm('Revoke this link? The student will not be able to use it again.')">@csrf @method('PATCH')<button class="btn btn-danger btn-compact" type="submit">Revoke</button></form>
                        @endif
                    </div></td>
                </tr>
            @endforeach</tbody>
        </table></div>
        @if($mockInvites->hasPages())<div class="pagination-wrap">{{ $mockInvites->onEachSide(1)->links() }}</div>@endif
    @else
        <div class="empty">
            <span class="empty-icon">◎</span>
            <h3>No interview links yet</h3>
            <p>Generate one above and share it with a student over WhatsApp or email.</p>
        </div>
    @endif
</section>

{{-- Copy-link clicks are handled by the delegated [data-copy-link] listener in
     crm.js. An inline <script> here would never run: the CRM swaps views in via
     DOMParser, whose scripts are inert. --}}
