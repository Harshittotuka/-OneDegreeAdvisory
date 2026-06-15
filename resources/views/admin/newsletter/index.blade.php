@extends('admin.layout')
@section('title', 'Subscribers')

@push('head')
<style>
  .subs-table { width: 100%; border-collapse: collapse; font-size: .9rem; }
  .subs-table th { text-align: left; background: #f8f5f1; padding: 12px 16px; font-weight: 800;
    font-size: .72rem; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); }
  .subs-table td { padding: 12px 16px; border-top: 1px solid var(--line); }
  .subs-table tr:hover td { background: #fafbfc; }
  .subs-email { font-weight: 700; }
  .subs-src { display: inline-block; background: #eef1f4; color: #4b5a66; font-size: .74rem; font-weight: 700;
    padding: 2px 9px; border-radius: 999px; }
</style>
@endpush

@section('content')
  <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px;">
    <div>
      <h1 style="margin:0;font-size:1.45rem;letter-spacing:-.01em;">Newsletter subscribers</h1>
      <p style="margin:3px 0 0;color:var(--muted);font-size:.85rem;">
        {{ count($subscribers) }} {{ \Illuminate\Support\Str::plural('subscriber', count($subscribers)) }} — collected from the blog signup forms.
      </p>
    </div>
    @if(count($subscribers))
      <a class="btn btn-primary" href="{{ route('admin.newsletter.export') }}">
        <i data-lucide="download" style="width:16px;height:16px;"></i> Export CSV
      </a>
    @endif
  </div>

  @if(session('status'))
    <div class="panel panel-pad" style="margin-bottom:16px;color:var(--teal-dark);font-weight:600;">{{ session('status') }}</div>
  @endif

  @if(count($subscribers))
    <div class="panel" style="overflow:hidden;">
      <table class="subs-table">
        <thead>
          <tr>
            <th>Email</th>
            <th>Source</th>
            <th>Subscribed</th>
            <th style="width:1%;"></th>
          </tr>
        </thead>
        <tbody>
          @foreach($subscribers as $s)
            <tr>
              <td class="subs-email"><a href="mailto:{{ $s['email'] ?? '' }}">{{ $s['email'] ?? '' }}</a></td>
              <td>@if(! empty($s['source']))<span class="subs-src">{{ $s['source'] }}</span>@else <span style="color:var(--muted);">—</span>@endif</td>
              <td style="color:var(--muted);">{{ ! empty($s['date']) ? \Illuminate\Support\Carbon::parse($s['date'])->format('M j, Y · g:i A') : '—' }}</td>
              <td>
                <form method="POST" action="{{ route('admin.newsletter.destroy') }}"
                      onsubmit="return confirm('Remove {{ addslashes($s['email'] ?? '') }} from the list?');">
                  @csrf
                  <input type="hidden" name="email" value="{{ $s['email'] ?? '' }}">
                  <button class="btn btn-ghost btn-sm" type="submit" title="Remove subscriber">
                    <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                  </button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <div class="panel panel-pad" style="text-align:center;color:var(--muted);padding:50px 20px;">
      No subscribers yet. They’ll appear here when visitors sign up from the blog.
    </div>
  @endif
@endsection
