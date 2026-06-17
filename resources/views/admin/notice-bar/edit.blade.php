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

  /* Static-notification rich-text editor */
  .nb-static-wrap { margin-top: 18px; padding-top: 18px; border-top: 1px dashed var(--line); }
  .nb-static-wrap[hidden] { display: none; }
  .nb-static-label { display: block; font-weight: 800; font-size: .85rem; margin-bottom: 8px; }
  .nb-rt-toolbar { display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
  .nb-rt-btn { display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--line); background: #fff; border-radius: 8px;
    padding: 7px 10px; font: 700 .82rem inherit; color: var(--ink); cursor: pointer; }
  .nb-rt-btn:hover { border-color: var(--teal); color: var(--teal); }
  .nb-rt-btn i { width: 15px; height: 15px; }
  .nb-rt-sep { width: 1px; height: 22px; background: var(--line); margin: 0 2px; }
  /* Preview the real bar context: dark blue background, gold links. */
  .nb-rt { min-height: 46px; border: 1px solid var(--line); border-radius: 10px; padding: 11px 14px; background: #0e2a44; color: #fff;
    font-weight: 700; line-height: 1.5; outline: none; }
  .nb-rt:focus { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(102,108,255,.18); }
  .nb-rt:empty::before { content: attr(data-ph); color: rgba(255,255,255,.5); font-weight: 600; }
  .nb-rt a { color: #ffc21f; text-decoration: underline; text-underline-offset: 3px; }
  .nb-link-pop { display: flex; gap: 8px; align-items: center; margin-top: 10px; padding: 10px; border: 1px solid var(--line); border-radius: 10px; background: #fafbfc; }
  .nb-link-pop[hidden] { display: none; }
  .nb-link-pop input { flex: 1; padding: 8px 10px; border: 1px solid var(--line); border-radius: 8px; font: inherit; }
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
  scrolls, how much of each item shows, the text colour / style / weight, and the
  announcement list itself.
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
          <option value="left-socials"       @selected(($bar['variant'] ?? 'left-socials') === 'left-socials')>Left socials — all icons left + scrolling notices</option>
          <option value="left-socials-cycle" @selected(($bar['variant'] ?? '') === 'left-socials-cycle')>Left socials, fade on phone — icons fade one-by-one on phones</option>
          <option value="no-socials"         @selected(($bar['variant'] ?? '') === 'no-socials')>No socials — scrolling notices only</option>
          <option value="static-notice"      @selected(($bar['variant'] ?? '') === 'static-notice')>Left socials + static notification</option>
        </select>
        <p class="hint">All icon styles now keep WhatsApp on the left too. “Fade on phone” shows one icon at a time on small screens. “Static notification” shows one centered message with clickable words.</p>
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

      <div class="field" style="margin:0;">
        <label for="nb-gap">Gap between items (px)</label>
        <input id="nb-gap" type="number" name="item_gap" min="8" max="240" value="{{ $bar['item_gap'] ?? 64 }}">
        <p class="hint">Distance between scrolling announcements. Higher = more spaced out. Default 64.</p>
      </div>

      <div class="field" style="margin:0;">
        <label for="nb-color">Text colour</label>
        <input id="nb-color" type="color" name="text_color" value="{{ $bar['text_color'] ?? '#ff5e32' }}"
               style="width:100%; height:42px; padding:4px; border:1px solid var(--line); border-radius:8px; background:#fff; cursor:pointer;">
        <p class="hint">Colour of the scrolling announcement text. Default brand orange.</p>
      </div>

      <div class="field" style="margin:0;">
        <label for="nb-font-style">Font style</label>
        <select id="nb-font-style" name="font_style">
          <option value="normal" @selected(($bar['font_style'] ?? 'normal') === 'normal')>Normal</option>
          <option value="italic" @selected(($bar['font_style'] ?? '') === 'italic')>Italic</option>
        </select>
        <p class="hint">Italic slants the announcement text.</p>
      </div>

      <div class="field" style="margin:0;">
        <label for="nb-bold">Weight</label>
        <label class="nb-vis" style="margin-top:8px;">
          <input id="nb-bold" type="checkbox" name="bold" value="1" @checked(! empty($bar['bold']))> Bold text
        </label>
        <p class="hint">Off = normal weight.</p>
      </div>
    </div>

    {{-- Static notification editor — only used by the "Left socials + static notification" style. --}}
    <div class="nb-static-wrap" id="nb-static-wrap" @if(($bar['variant'] ?? 'left-socials') !== 'static-notice') hidden @endif>
      <label class="nb-static-label">Static notification text</label>
      <div class="nb-rt-toolbar" role="toolbar" aria-label="Formatting">
        <button type="button" class="nb-rt-btn" data-rt="bold" title="Bold"><i data-lucide="bold"></i></button>
        <button type="button" class="nb-rt-btn" data-rt="italic" title="Italic"><i data-lucide="italic"></i></button>
        <span class="nb-rt-sep"></span>
        <button type="button" class="nb-rt-btn" data-rt="link" title="Link the selected words"><i data-lucide="link"></i> Add link</button>
        <button type="button" class="nb-rt-btn" data-rt="unlink" title="Remove link"><i data-lucide="unlink"></i></button>
      </div>
      <div class="nb-rt" id="nb-static-editor" contenteditable="true" data-ph="Type your message… select words, then “Add link”.">{!! $bar['static_text'] ?? '' !!}</div>
      <input type="hidden" name="static_text" id="nb-static-input" value="{{ $bar['static_text'] ?? '' }}">
      <p class="hint">Shown centered in the bar. Select any word(s) and click <strong>Add link</strong> to make them clickable. Links, <strong>bold</strong> and <em>italic</em> are allowed.</p>

      <div class="nb-link-pop" id="nb-link-pop" hidden>
        <input type="text" id="nb-link-url" list="nb-link-options" placeholder="/contact, #insights or https://…" autocomplete="off">
        <button type="button" class="btn btn-primary btn-sm" id="nb-link-apply">Apply link</button>
        <button type="button" class="btn btn-ghost btn-sm" id="nb-link-cancel">Cancel</button>
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

<script>
  // ── Static-notification editor: show for the static style, rich-text + links ──
  (function () {
    const variant = document.getElementById('nb-variant');
    const wrap = document.getElementById('nb-static-wrap');
    if (!variant || !wrap) return;
    const editor = document.getElementById('nb-static-editor');
    const hidden = document.getElementById('nb-static-input');
    const form = document.getElementById('nb-form');
    const pop = document.getElementById('nb-link-pop');
    const urlInput = document.getElementById('nb-link-url');

    const toggle = () => { wrap.hidden = variant.value !== 'static-notice'; };
    variant.addEventListener('change', toggle);
    toggle();

    const sync = () => { hidden.value = editor.innerHTML.trim(); };
    editor.addEventListener('input', sync);
    sync();

    // Remember the last selection inside the editor so toolbar clicks can use it.
    let savedRange = null;
    function saveRange() {
      const sel = window.getSelection();
      if (sel && sel.rangeCount && editor.contains(sel.anchorNode)) savedRange = sel.getRangeAt(0).cloneRange();
    }
    editor.addEventListener('keyup', saveRange);
    editor.addEventListener('mouseup', saveRange);
    function restore() {
      editor.focus();
      if (!savedRange) return;
      const sel = window.getSelection();
      sel.removeAllRanges(); sel.addRange(savedRange);
    }
    function cmd(name) { restore(); document.execCommand(name, false, null); sync(); saveRange(); }

    // Keep the editor's selection when a toolbar button is pressed.
    wrap.querySelectorAll('[data-rt]').forEach(btn => {
      btn.addEventListener('mousedown', (e) => e.preventDefault());
      btn.addEventListener('click', () => {
        const action = btn.dataset.rt;
        if (action === 'bold' || action === 'italic' || action === 'unlink') { cmd(action); return; }
        if (action === 'link') {
          if (!savedRange || savedRange.collapsed) { alert('Select the word(s) you want to link first.'); return; }
          pop.hidden = false; urlInput.value = ''; urlInput.focus();
        }
      });
    });

    document.getElementById('nb-link-apply').addEventListener('click', () => {
      const url = urlInput.value.trim();
      if (!url) { pop.hidden = true; return; }
      restore();
      document.execCommand('createLink', false, url);
      editor.querySelectorAll('a[href]').forEach(a => {
        if (/^https?:\/\//i.test(a.getAttribute('href'))) { a.target = '_blank'; a.rel = 'noopener'; }
      });
      pop.hidden = true; sync(); saveRange();
    });
    document.getElementById('nb-link-cancel').addEventListener('click', () => { pop.hidden = true; });
    urlInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); document.getElementById('nb-link-apply').click(); } });

    if (form) form.addEventListener('submit', sync);
  })();
</script>
@endpush
