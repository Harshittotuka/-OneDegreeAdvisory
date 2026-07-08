@extends('admin.layout')

@section('title', 'Career Library')

@push('head')
<style>
  .cl-intro { color: var(--muted); font-size: .9rem; margin: -4px 0 22px; max-width: 720px; }
  .cl-section-title { margin: 0 0 4px; font-size: 1.02rem; font-weight: 800; }
  .cl-section-sub { margin: 0 0 16px; color: var(--muted); font-size: .82rem; }
  .cl-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
  .cl-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; }
  .cl-error { background: #fdecea; border: 1px solid #f5c6c0; color: var(--danger);
    padding: 13px 16px; border-radius: 11px; margin-bottom: 20px; font-weight: 700; }

  .cl-toolbar { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; flex-wrap: wrap; }
  .cl-toolbar input[type=search] { flex: 1; min-width: 220px; padding: 10px 14px; border: 1px solid var(--line);
    border-radius: 10px; font: inherit; }
  .cl-count { color: var(--muted); font-size: .82rem; font-weight: 700; white-space: nowrap; }

  .cl-row { display: grid; grid-template-columns: 26px 44px 1fr auto auto auto; gap: 12px; align-items: center;
    padding: 10px 12px; border: 1px solid var(--line); border-radius: 12px; background: #fafbfc; margin-bottom: 8px; }
  .cl-row.dragging { opacity: .45; }
  .cl-drag { cursor: grab; color: var(--muted); display: inline-flex; }
  .cl-tile { width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; }
  .cl-name { min-width: 0; }
  .cl-name strong { display: block; font-size: .92rem; }
  .cl-name span { color: var(--muted); font-size: .76rem; }
  .cl-flags { display: flex; gap: 12px; align-items: center; }
  .cl-flag { display: inline-flex; align-items: center; gap: 6px; font-size: .78rem; font-weight: 700; color: var(--muted); }
  .cl-flag input { width: 15px; height: 15px; accent-color: var(--teal); cursor: pointer; }
  .cl-variants { font-size: .74rem; color: var(--muted); white-space: nowrap; }
  .cl-row-actions { display: flex; gap: 6px; }

  /* Tailwind tile colour approximations for the admin preview */
  .cl-tile svg { width: 22px; height: 22px; }

  @media (max-width: 860px) {
    .cl-grid, .cl-grid-2 { grid-template-columns: 1fr; }
    .cl-row { grid-template-columns: 26px 1fr auto; }
    .cl-tile, .cl-variants { display: none; }
  }
</style>
@endpush

@section('content')
<p class="cl-intro">
  Manage the <a href="{{ route('career-library.index') }}" target="_blank" rel="noopener">Trending Career</a> library —
  the landing page copy, the career tiles (order, icon, colours, visibility) and every career's full report.
  Drag rows to reorder the public grid. The first 40 visible tiles show first on the landing page.
</p>

@if (session('error'))
  <div class="cl-error">{{ session('error') }}</div>
@endif

{{-- ── Page settings ── --}}
<div class="panel" style="padding: 22px; margin-bottom: 20px;">
  <h2 class="cl-section-title">Page settings</h2>
  <p class="cl-section-sub">Hero copy, contact strip and behaviour of the public page.</p>

  <form method="POST" action="{{ route('admin.career-library.settings') }}">
    @csrf
    <div class="cl-grid">
      <div class="field" style="margin:0;">
        <label>Hero title (first line)</label>
        <input type="text" name="hero_title_prefix" value="{{ $settings['hero_title_prefix'] }}" maxlength="120">
      </div>
      <div class="field" style="margin:0;">
        <label>Hero title (highlighted)</label>
        <input type="text" name="hero_title_highlight" value="{{ $settings['hero_title_highlight'] }}" maxlength="120">
        <p class="hint">Rendered in the indigo→pink gradient.</p>
      </div>
      <div class="field" style="margin:0;">
        <label>Hero subtitle</label>
        <input type="text" name="hero_subtitle" value="{{ $settings['hero_subtitle'] }}" maxlength="300">
      </div>
      <div class="field" style="margin:0;">
        <label>Search placeholder</label>
        <input type="text" name="search_placeholder" value="{{ $settings['search_placeholder'] }}" maxlength="120">
      </div>
      <div class="field" style="margin:0;">
        <label>Trending heading</label>
        <input type="text" name="trending_heading" value="{{ $settings['trending_heading'] }}" maxlength="120">
      </div>
      <div class="field" style="margin:0;">
        <label>Bottom button label</label>
        <input type="text" name="explore_button" value="{{ $settings['explore_button'] }}" maxlength="120">
      </div>
      <div class="field" style="margin:0;">
        <label>Contact email (navbar)</label>
        <input type="email" name="contact_email" value="{{ $settings['contact_email'] }}" maxlength="120">
      </div>
      <div class="field" style="margin:0;">
        <label>Contact phone (navbar)</label>
        <input type="text" name="contact_phone" value="{{ $settings['contact_phone'] }}" maxlength="40">
      </div>
      <div class="field" style="margin:0;">
        <label>Report year</label>
        <input type="text" name="report_year" value="{{ $settings['report_year'] }}" maxlength="8">
        <p class="hint">Shown in the Market Snapshot and “Next Step” cards.</p>
      </div>
      <div class="field" style="margin:0;">
        <label>“Take the Next Step” link</label>
        <input type="url" name="next_steps_url" value="{{ $settings['next_steps_url'] }}" maxlength="300">
        <p class="hint">All four cards on a career page link here.</p>
      </div>
    </div>
    <div style="margin-top: 18px;">
      <button type="submit" class="btn btn-primary">Save settings</button>
    </div>
  </form>
</div>

{{-- ── Add a career ── --}}
<div class="panel" style="padding: 22px; margin-bottom: 20px;">
  <h2 class="cl-section-title">Add a career</h2>
  <p class="cl-section-sub">Creates an empty report — fill in its details on the next screen.</p>

  <form method="POST" action="{{ route('admin.career-library.store') }}" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
    @csrf
    <input type="text" name="title" placeholder="Career name — e.g. Marine Biology" required maxlength="120"
           style="flex:1; min-width:260px; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; font: inherit;">
    <button type="submit" class="btn btn-primary">Create career</button>
  </form>
</div>

{{-- ── Career list ── --}}
<div class="panel" style="padding: 22px; margin-bottom: 20px;">
  <h2 class="cl-section-title">Careers</h2>
  <p class="cl-section-sub">Order here is the exact order of the public “Trending Now” grid (it loads 40 tiles at a time as visitors scroll).</p>

  <div class="cl-toolbar">
    <input type="search" id="cl-search" placeholder="Filter careers…">
    <span class="cl-count"><span id="cl-visible-count">{{ count($careers) }}</span> / {{ count($careers) }} careers</span>
  </div>

  <div id="cl-list">
    @foreach ($careers as $career)
      <div class="cl-row" draggable="true" data-slug="{{ $career['slug'] }}" data-title="{{ mb_strtolower($career['title']) }}">
        <span class="cl-drag" title="Drag to reorder">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="8" cy="5" r="1.6"/><circle cx="16" cy="5" r="1.6"/><circle cx="8" cy="12" r="1.6"/><circle cx="16" cy="12" r="1.6"/><circle cx="8" cy="19" r="1.6"/><circle cx="16" cy="19" r="1.6"/></svg>
        </span>
        <span class="cl-tile" style="background:#eef0ff; color:#4f46e5;">{!! \App\Support\CareerLibraryIcons::svg($career['iconType']) !!}</span>
        <div class="cl-name">
          <strong>{{ $career['title'] }}</strong>
          <span>/{{ $career['slug'] }} · {{ implode(', ', array_keys($career['data'])) ?: 'no data' }}</span>
        </div>
        <form method="POST" action="{{ route('admin.career-library.flags', $career['slug']) }}" class="cl-flags">
          @csrf
          <label class="cl-flag" title="Show in the first badge cycle">
            <input type="hidden" name="trending" value="0">
            <input type="checkbox" name="trending" value="1" @checked($career['trending']) onchange="this.form.submit()"> Trending
          </label>
          <label class="cl-flag" title="Show on the public landing grid">
            <input type="hidden" name="visible" value="0">
            <input type="checkbox" name="visible" value="1" @checked($career['visible']) onchange="this.form.submit()"> Visible
          </label>
        </form>
        <span class="cl-variants">{{ count($career['data']) }} variant{{ count($career['data']) === 1 ? '' : 's' }}</span>
        <div class="cl-row-actions">
          <a class="btn btn-ghost btn-sm" href="{{ route('admin.career-library.live', $career['slug']) }}">Edit</a>
          <a class="btn btn-ghost btn-sm" target="_blank" rel="noopener"
             href="{{ url('/global-career-library/in/'.str_replace(' ', '-', $career['title']).'/en-IN') }}">View</a>
          <form method="POST" action="{{ route('admin.career-library.destroy', $career['slug']) }}"
                onsubmit="return confirm('Delete “{{ $career['title'] }}” and all its report data?');">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
          </form>
        </div>
      </div>
    @endforeach
  </div>
</div>

<script>
(() => {
  // Filter
  const search = document.getElementById('cl-search');
  const rows = () => Array.from(document.querySelectorAll('#cl-list .cl-row'));
  search?.addEventListener('input', () => {
    const q = search.value.toLowerCase().trim();
    let shown = 0;
    rows().forEach(r => {
      const hit = !q || r.dataset.title.includes(q);
      r.style.display = hit ? '' : 'none';
      if (hit) shown++;
    });
    document.getElementById('cl-visible-count').textContent = shown;
  });

  // Drag reorder → POST the new slug order
  const list = document.getElementById('cl-list');
  let dragged = null;

  list?.addEventListener('dragstart', e => {
    dragged = e.target.closest('.cl-row');
    dragged?.classList.add('dragging');
  });
  list?.addEventListener('dragend', () => {
    if (!dragged) return;
    dragged.classList.remove('dragging');
    dragged = null;
    fetch(@json(route('admin.career-library.reorder')), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
      body: JSON.stringify({ order: rows().map(r => r.dataset.slug) })
    }).then(r => { if (r.ok && window.cmsToast) window.cmsToast('Order saved.'); });
  });
  list?.addEventListener('dragover', e => {
    e.preventDefault();
    const after = rows().filter(r => r !== dragged).find(r => {
      const rect = r.getBoundingClientRect();
      return e.clientY < rect.top + rect.height / 2;
    });
    if (!dragged) return;
    if (after) list.insertBefore(dragged, after);
    else list.appendChild(dragged);
  });
})();
</script>
@endsection
