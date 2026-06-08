@extends('admin.layout')

@section('title', 'Notification Bar')

@push('head')
<style>
  .nb-intro { color: var(--muted); font-size: .9rem; margin: -4px 0 22px; max-width: 640px; }
  .nb-section-title { margin: 0 0 4px; font-size: 1.02rem; font-weight: 800; }
  .nb-section-sub { margin: 0 0 16px; color: var(--muted); font-size: .82rem; }
  .nb-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
  .nb-row { display: grid; grid-template-columns: 1fr auto auto; gap: 12px; align-items: center;
    padding: 12px; border: 1px solid var(--line); border-radius: 12px; background: #fafbfc; margin-bottom: 10px; }
  .nb-row-fields { display: grid; gap: 8px; min-width: 0; }
  .nb-vis { display: flex; align-items: center; gap: 6px; margin: 0; font-size: .8rem; font-weight: 700;
    color: var(--muted); white-space: nowrap; }
  .nb-vis input[type=checkbox] { width: 16px; height: 16px; accent-color: var(--teal); cursor: pointer; }
  .nb-row-actions { display: flex; gap: 4px; }
  .nb-icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px;
    border: 1px solid var(--line); background: #fff; border-radius: 8px; cursor: pointer; color: var(--muted);
    transition: border-color .15s, color .15s; }
  .nb-icon-btn:hover { border-color: var(--teal); color: var(--teal); }
  .nb-icon-btn.nb-del:hover { border-color: var(--danger); color: var(--danger); }
  .nb-icon-btn i { width: 16px; height: 16px; }
  .nb-actions { margin-top: 22px; display: flex; gap: 10px; }
  @media (max-width: 720px) {
    .nb-grid { grid-template-columns: 1fr; }
    .nb-row { grid-template-columns: 1fr; }
    .nb-row-actions { justify-content: flex-end; }
  }
</style>
@endpush

@section('content')
<p class="nb-intro">
  Manage the scrolling blue bar at the very top of the site — its display style, how fast it
  scrolls, how much of each item shows, and the announcement list itself.
</p>

<form method="POST" action="{{ route('admin.notice-bar.update') }}" id="nb-form">
  @csrf

  {{-- ── Display settings ── --}}
  <div class="panel" style="padding: 22px; margin-bottom: 20px;">
    <h2 class="nb-section-title">Display</h2>
    <p class="nb-section-sub">Controls the whole bar. (This replaces the old floating “Top bar” switcher.)</p>

    <div class="nb-grid">
      <div class="field" style="margin:0;">
        <label for="nb-variant">Top bar style</label>
        <select id="nb-variant" name="variant">
          <option value="original" @selected(($bar['variant'] ?? 'original') === 'original')>Original — socials + WhatsApp number</option>
          <option value="minimal"  @selected(($bar['variant'] ?? '') === 'minimal')>No socials — marquee only</option>
          <option value="compact"  @selected(($bar['variant'] ?? '') === 'compact')>WhatsApp icon — compact</option>
        </select>
      </div>

      <div class="field" style="margin:0;">
        <label for="nb-words">Words shown per item</label>
        <input id="nb-words" type="number" name="word_count" min="0" max="50" value="{{ $bar['word_count'] ?? 5 }}">
        <p class="hint">Teaser length. Use <strong>0</strong> to show each item’s full text.</p>
      </div>

      <div class="field" style="margin:0;">
        <label for="nb-speed">Scroll speed (seconds per loop)</label>
        <input id="nb-speed" type="number" name="speed" min="5" max="120" value="{{ $bar['speed'] ?? 14 }}">
        <p class="hint">Lower = faster. Default 14.</p>
      </div>
    </div>
  </div>

  {{-- ── Items ── --}}
  <div class="panel" style="padding: 22px;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom: 14px;">
      <div>
        <h2 class="nb-section-title">Announcement items</h2>
        <p class="nb-section-sub" style="margin:0;">Add, remove and reorder. An item with no link shows as plain (non-clickable) text. Click the <strong>link field</strong> to pick from available pages &amp; sections.</p>
      </div>
      <button type="button" class="btn btn-ghost btn-sm" data-nb-add><i data-lucide="plus"></i> Add item</button>
    </div>

    <div data-nb-list>
      @foreach($bar['items'] ?? [] as $i => $item)
        @include('admin.notice-bar._row', ['i' => $i, 'item' => $item])
      @endforeach
    </div>

    <p class="hint" data-nb-empty @if(! empty($bar['items'])) style="display:none" @endif>
      No items yet — click “Add item” to create your first announcement.
    </p>
  </div>

  <div class="nb-actions">
    <button type="submit" class="btn btn-primary"><i data-lucide="save" style="width:16px;height:16px;"></i> Save changes</button>
    <a class="btn btn-ghost" href="{{ route('home') }}" target="_blank"><i data-lucide="external-link" style="width:16px;height:16px;"></i> Preview site</a>
  </div>
</form>

{{-- Shared link suggestions — referenced by every row's link field via list="nb-link-options". --}}
<datalist id="nb-link-options">
  @foreach($linkSuggestions as $group => $options)
    @foreach($options as $value => $label)
      <option value="{{ $value }}">{{ $group }} · {{ $label }} ({{ $value }})</option>
    @endforeach
  @endforeach
</datalist>

{{-- Hidden template cloned by “Add item”. __INDEX__ is swapped for a unique token. --}}
<template id="nb-row-tpl">
  @include('admin.notice-bar._row', ['i' => '__INDEX__', 'item' => ['text' => '', 'href' => '', 'visible' => true]])
</template>
@endsection

@push('scripts')
<script>
  (function () {
    const list  = document.querySelector('[data-nb-list]');
    const tpl   = document.getElementById('nb-row-tpl');
    const empty = document.querySelector('[data-nb-empty]');
    const addBtn = document.querySelector('[data-nb-add]');
    if (!list || !tpl) return;

    let uid = 1000000; // newly-added rows get unique keys, away from server indexes

    function refresh() {
      if (empty) empty.style.display = list.children.length ? 'none' : '';
      if (window.lucide) lucide.createIcons();
    }

    addBtn && addBtn.addEventListener('click', function () {
      const html = tpl.innerHTML.replace(/__INDEX__/g, String(uid++));
      const tmp = document.createElement('div');
      tmp.innerHTML = html.trim();
      const row = tmp.firstElementChild;
      list.appendChild(row);
      refresh();
      const firstInput = row.querySelector('input[type=text]');
      if (firstInput) firstInput.focus();
    });

    list.addEventListener('click', function (e) {
      const row = e.target.closest('[data-nb-row]');
      if (!row) return;
      if (e.target.closest('[data-nb-del]')) {
        row.remove();
        refresh();
      } else if (e.target.closest('[data-nb-up]') && row.previousElementSibling) {
        row.parentNode.insertBefore(row, row.previousElementSibling);
      } else if (e.target.closest('[data-nb-down]') && row.nextElementSibling) {
        row.parentNode.insertBefore(row.nextElementSibling, row);
      }
    });

    refresh();
  })();
</script>
@endpush
