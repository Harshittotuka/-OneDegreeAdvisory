@switch($name)
    @case('dashboard')
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
        @break
    @case('leads')
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 20v-1.8a4.2 4.2 0 0 0-4.2-4.2H6.2A4.2 4.2 0 0 0 2 18.2V20"/><circle cx="9" cy="7" r="4"/><path d="M17 11a4 4 0 0 0 0-8M22 20v-1.8a4.2 4.2 0 0 0-3-4"/></svg>
        @break
    @case('enrollments')
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 10h19M7 15h3"/></svg>
        @break
    @case('subscriptions')
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2.5" y="4.5" width="19" height="15" rx="2.5"/><path d="m3.5 7 8.5 6 8.5-6"/><path d="m15.8 16.2 1.4 1.4 3-3"/></svg>
        @break
    @case('followups')
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16.5" rx="2.5"/><path d="M8 2.5v4M16 2.5v4M3 9.5h18"/><circle cx="15.5" cy="15.5" r="3"/><path d="M15.5 14v1.7l1.2.8"/></svg>
        @break
    @case('students')
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m2 9 10-5 10 5-10 5L2 9Z"/><path d="M6 11.2V16c2.8 2.5 9.2 2.5 12 0v-4.8M22 9v6"/></svg>
        @break
    @case('audit')
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="3" width="16" height="18" rx="2.5"/><path d="M8 8h8M8 12h5M8 16h4"/><path d="m15 15.5 1.5 1.5 3-3"/></svg>
        @break
    @case('team')
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20v-1.5A4.5 4.5 0 0 1 7 14h4"/><circle cx="17.5" cy="16.5" r="3"/><path d="M17.5 12v1.5M17.5 19.5V21M13 16.5h1.5M20.5 16.5H22M14.3 13.3l1 1M19.7 18.7l1 1M20.7 13.3l-1 1M15.3 18.7l-1 1"/></svg>
        @break
    @case('logout')
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5M14 8l4 4-4 4M8 12h10"/></svg>
        @break
@endswitch
