@extends('admin.layout')
@section('title', 'Unlinked Pages')

@push('head')
<style>
  .upl-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; margin-bottom: 18px; }
  .upl-head h2 { margin: 0; font-size: 1.3rem; }
  .upl-head p { max-width: 720px; }
  .upl-panel { overflow: hidden; margin-bottom: 18px; }
  .upl-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 18px; border-bottom: 1px solid var(--line); background: #faf9ff; }
  .upl-panel-head h3 { margin: 0; font-size: 1rem; }
  .upl-panel-head span { color: var(--muted); font-size: .82rem; font-weight: 700; }
  .upl-table { width: 100%; border-collapse: collapse; }
  .upl-table th { text-align: left; padding: 12px 16px; font-size: .72rem; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; border-bottom: 1px solid var(--line); }
  .upl-table td { padding: 13px 16px; border-bottom: 1px solid var(--line); vertical-align: top; }
  .upl-table tr:last-child td { border-bottom: 0; }
  .upl-page-title { display: flex; flex-direction: column; gap: 3px; }
  .upl-page-title strong { font-size: .94rem; }
  .upl-badge { display: inline-flex; align-items: center; gap: 5px; width: max-content; padding: 3px 8px; border-radius: 999px; font-size: .7rem; font-weight: 800; background: #f0f0f5; color: var(--muted); }
  .upl-badge.live { background: #e6f8ee; color: #1f8a4c; }
  .upl-badge.hidden { background: #fdeaea; color: var(--danger); }
  .upl-path { font-size: .82rem; color: var(--muted); word-break: break-word; }
  .upl-actions { display: inline-flex; justify-content: flex-end; gap: 8px; white-space: nowrap; }
  @media (max-width: 860px) {
    .upl-head, .upl-panel-head { align-items: flex-start; flex-direction: column; }
    .upl-table { min-width: 780px; }
    .upl-scroll { overflow-x: auto; }
  }
</style>
@endpush

@section('content')
  <div class="upl-head">
    <div>
      <h2>Unlinked Pages</h2>
      <p class="hint" style="margin:4px 0 0;">Public pages that stay live but are not linked from the updated primary navigation. Keep them here for quick access without putting them back in the nav.</p>
    </div>
    <a class="btn btn-ghost" href="{{ route('home') }}" target="_blank"><i data-lucide="external-link"></i> View site</a>
  </div>

  @foreach(collect($staticPages)->groupBy('group') as $group => $pages)
    <section class="panel upl-panel">
      <div class="upl-panel-head">
        <h3>{{ $group }}</h3>
        <span>{{ count($pages) }} unlinked page{{ count($pages) === 1 ? '' : 's' }}</span>
      </div>
      <div class="upl-scroll">
        <table class="upl-table">
          <thead>
            <tr>
              <th>Page</th>
              <th>URL</th>
              <th>Status</th>
              <th>Why listed</th>
              <th style="text-align:right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($pages as $page)
              <tr>
                <td>
                  <span class="upl-page-title">
                    <strong>{{ $page['title'] }}</strong>
                    <span class="upl-badge">{{ $page['route'] }}</span>
                  </span>
                </td>
                <td><code class="upl-path">{{ $page['path'] }}</code></td>
                <td><span class="upl-badge live"><i data-lucide="eye" style="width:13px;height:13px;"></i> {{ $page['status'] }}</span></td>
                <td class="hint" style="margin:0;">{{ $page['note'] }}</td>
                <td style="text-align:right;">
                  <span class="upl-actions">
                    <a class="btn btn-ghost btn-sm" href="{{ $page['url'] }}" target="_blank"><i data-lucide="external-link"></i> View</a>
                  </span>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </section>
  @endforeach

  <section class="panel upl-panel">
    <div class="upl-panel-head">
      <h3>CMS-built brief pages</h3>
      <span>{{ count($briefPages) }} page{{ count($briefPages) === 1 ? '' : 's' }}</span>
    </div>
    <div class="upl-scroll">
      <table class="upl-table">
        <thead>
          <tr>
            <th>Page</th>
            <th>URL</th>
            <th>Status</th>
            <th>Why listed</th>
            <th style="text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($briefPages as $page)
            <tr>
              <td>
                <span class="upl-page-title">
                  <strong>{{ $page['title'] }}</strong>
                  <span class="upl-badge">{{ $page['route'] }}</span>
                </span>
              </td>
              <td><code class="upl-path">{{ $page['path'] }}</code></td>
              <td>
                <span @class(['upl-badge', 'live' => $page['status'] === 'Live', 'hidden' => $page['status'] !== 'Live'])>
                  <i data-lucide="{{ $page['status'] === 'Live' ? 'eye' : 'eye-off' }}" style="width:13px;height:13px;"></i> {{ $page['status'] }}
                </span>
              </td>
              <td class="hint" style="margin:0;">{{ $page['note'] }}</td>
              <td style="text-align:right;">
                <span class="upl-actions">
                  @if($page['editable'])
                    <a class="btn btn-ghost btn-sm" href="{{ $page['edit_url'] }}"><i data-lucide="pencil"></i> Edit</a>
                  @endif
                  <a class="btn btn-ghost btn-sm" href="{{ $page['url'] }}" target="_blank"><i data-lucide="external-link"></i> View</a>
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="hint" style="padding:18px;">No CMS-built pages yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
@endsection
