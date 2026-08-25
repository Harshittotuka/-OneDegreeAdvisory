@extends('admin.layout')
@section('title', 'Page Builder')

@section('content')
  <div style="display:flex;flex-wrap:wrap;gap:14px;justify-content:space-between;align-items:flex-end;margin-bottom:18px;">
    <div>
      <h2 style="margin:0;font-size:1.3rem;">Page Builder</h2>
      <p class="hint" style="margin:4px 0 0;">Build and manage premium “brief” pages from composable blocks — cards, tables, callouts, images and more.</p>
    </div>
    <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
    @if(session('cms_super_admin'))
      <a class="btn btn-ghost" href="{{ route('admin.pages.tokens.index') }}" title="Let a Claude project build pages here">
        <i data-lucide="key-round"></i> Claude access
      </a>
    @endif
    <form method="POST" action="{{ route('admin.pages.store') }}" style="display:flex;gap:8px;align-items:flex-end;">
      @csrf
      <div>
        <label for="np-title" style="font-size:.74rem;">New page title</label>
        <input id="np-title" type="text" name="title" placeholder="e.g. Destination Update: Canada" required style="width:260px;">
      </div>
      <button class="btn btn-primary" type="submit"><i data-lucide="plus"></i> Create</button>
    </form>
    </div>
  </div>

  <div class="panel" style="overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr style="background:#faf9ff;border-bottom:1px solid var(--line);">
          <th style="text-align:left;padding:12px 16px;font-size:.74rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;">Page</th>
          <th style="text-align:left;padding:12px 16px;font-size:.74rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;">URL</th>
          <th style="text-align:left;padding:12px 16px;font-size:.74rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;">Status</th>
          <th style="text-align:right;padding:12px 16px;font-size:.74rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;">Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($pages as $p)
          <tr style="border-bottom:1px solid var(--line);">
            <td style="padding:13px 16px;">
              <strong>{{ $p['title'] ?? 'Untitled' }}</strong>
              <div class="hint" style="margin:2px 0 0;">{{ count($p['sections'] ?? []) }} block{{ count($p['sections'] ?? []) === 1 ? '' : 's' }}</div>
            </td>
            <td style="padding:13px 16px;"><code style="font-size:.82rem;color:var(--muted);">{{ $p['path'] ?? '/briefs/'.$p['slug'] }}</code></td>
            <td style="padding:13px 16px;">
              @if($p['visible'] ?? true)
                <span style="display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:700;color:#0f8a4f;"><i data-lucide="eye" style="width:15px;height:15px;"></i> Live</span>
              @else
                <span style="display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:700;color:var(--muted);"><i data-lucide="eye-off" style="width:15px;height:15px;"></i> Hidden</span>
              @endif
            </td>
            <td style="padding:13px 16px;text-align:right;white-space:nowrap;">
              <a class="btn btn-ghost btn-sm" href="{{ route('admin.pages.studio', $p['slug']) }}"><i data-lucide="pencil"></i> Edit</a>
              <a class="btn btn-ghost btn-sm" href="{{ $p['path'] ?? ('/briefs/'.$p['slug']) }}" target="_blank"><i data-lucide="external-link"></i></a>
              <form method="POST" action="{{ route('admin.pages.duplicate', $p['slug']) }}" style="display:inline;">@csrf
                <button class="btn btn-ghost btn-sm" type="submit" title="Duplicate"><i data-lucide="copy"></i></button>
              </form>
              <form method="POST" action="{{ route('admin.pages.visibility', $p['slug']) }}" style="display:inline;">@csrf
                <button class="btn btn-ghost btn-sm" type="submit" title="{{ ($p['visible'] ?? true) ? 'Hide' : 'Show' }}"><i data-lucide="{{ ($p['visible'] ?? true) ? 'eye-off' : 'eye' }}"></i></button>
              </form>
              <form method="POST" action="{{ route('admin.pages.destroy', $p['slug']) }}" style="display:inline;" onsubmit="return confirm('Delete “{{ $p['title'] ?? 'this page' }}” permanently?');">
                @csrf @method('DELETE')
                <button class="btn btn-danger btn-sm" type="submit" title="Delete"><i data-lucide="trash-2"></i></button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endsection
