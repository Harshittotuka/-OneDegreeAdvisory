@extends('admin.layout')
@section('title', 'About Page')

@push('head')
<style>
  .list-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 8px; }
  .list-head h2 { margin: 0; font-size: 1.5rem; letter-spacing: -.01em; }
  .list-head .count { margin: 3px 0 0; color: var(--muted); font-size: .9rem; }
  .head-actions { display: flex; gap: 10px; align-items: center; }

  /* Add-section dropdown */
  .add-wrap { position: relative; }
  .add-menu { position: absolute; right: 0; top: 46px; min-width: 280px; background: #fff; border: 1px solid var(--line);
    border-radius: 12px; box-shadow: 0 18px 40px rgba(13,33,42,.18); padding: 7px; display: none; z-index: 30; }
  .add-wrap.open .add-menu { display: block; }
  .add-item { display: flex; align-items: flex-start; gap: 11px; padding: 10px 11px; border-radius: 9px; color: var(--ink); }
  .add-item:hover { background: #f3f6f8; }
  .add-item i { width: 18px; height: 18px; color: var(--teal); margin-top: 2px; flex-shrink: 0; }
  .add-item b { display: block; font-size: .9rem; font-weight: 700; }
  .add-item span { display: block; font-size: .76rem; color: var(--muted); line-height: 1.35; }

  .reorder-note { display: flex; align-items: center; gap: 8px; color: var(--muted); font-size: .82rem; margin: 16px 0 14px; }

  /* Section rows */
  .sec-list { display: flex; flex-direction: column; gap: 12px; }
  .sec-card { display: flex; align-items: center; gap: 14px; background: var(--panel); border: 1px solid var(--line);
    border-radius: var(--radius); box-shadow: var(--shadow); padding: 13px 16px;
    transition: box-shadow .18s ease, transform .12s ease, opacity .18s; }
  .sec-card.s-hidden { opacity: .62; background: #fbfbfc; }
  .sec-card.dragging { opacity: .4; }
  .sec-card.drop-target { outline: 2px dashed var(--teal); outline-offset: 3px; }
  .sec-grip { cursor: grab; color: #9aa6b4; display: inline-flex; padding: 4px; }
  .sec-grip:active { cursor: grabbing; }
  .sec-thumb { width: 64px; height: 48px; border-radius: 9px; object-fit: cover; background: #eef1f4; flex-shrink: 0; }
  .sec-thumb-ph { width: 64px; height: 48px; border-radius: 9px; background: #eef1f4; display: flex; align-items: center;
    justify-content: center; color: #aab4bf; flex-shrink: 0; }
  .sec-main { min-width: 0; flex: 1; }
  .sec-type { display: inline-flex; align-items: center; gap: 6px; font-size: .68rem; font-weight: 800; letter-spacing: .07em;
    text-transform: uppercase; color: var(--teal-dark); }
  .sec-type i { width: 13px; height: 13px; }
  .sec-title { margin: 4px 0 0; font-size: 1rem; font-weight: 800; letter-spacing: -.01em; white-space: nowrap;
    overflow: hidden; text-overflow: ellipsis; }
  .sec-sub { margin: 2px 0 0; color: var(--muted); font-size: .8rem; }
  .sec-pill { display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--line); background: #fff;
    border-radius: 999px; padding: 6px 12px; font-size: .78rem; font-weight: 700; cursor: pointer; font-family: inherit;
    color: var(--muted); }
  .sec-pill i { width: 14px; height: 14px; }
  .sec-pill.on { color: #1f8a4c; border-color: #b7e2c8; background: #f1faf4; }
  .sec-pill.off { color: var(--danger); border-color: #f0c4be; background: #fdf2f1; }
  .sec-acts { display: flex; gap: 7px; align-items: center; }
  .sec-acts form { margin: 0; }
  @media (max-width: 720px) {
    .sec-card { flex-wrap: wrap; }
    .sec-thumb, .sec-thumb-ph { display: none; }
    .sec-acts { width: 100%; justify-content: flex-end; }
  }
  .empty { text-align: center; color: var(--muted); padding: 50px 20px; border: 1px dashed var(--line); border-radius: var(--radius); }
</style>
@endpush

@section('content')
  @php
    $imgUrl = fn ($p) => $p ? (\Illuminate\Support\Str::startsWith($p, ['http://', 'https://', '/']) ? $p : asset($p)) : null;

    $summary = function (array $s) {
        $d = $s['data'] ?? [];
        foreach (['heading', 'eyebrow', 'heading_highlight', 'heading_pre', 'stat_num'] as $k) {
            if (! empty($d[$k])) return $d[$k];
        }
        foreach ($d as $v) {
            if (is_array($v) && isset($v[0]) && is_array($v[0])) {
                foreach (['heading', 'name', 'label', 'value', 'tag'] as $k) {
                    if (! empty($v[0][$k])) return $v[0][$k];
                }
            }
        }
        return '';
    };

    $thumb = function (array $s) {
        $d = $s['data'] ?? [];
        foreach (['photo_lg', 'image', 'photo'] as $k) {
            if (! empty($d[$k])) return $d[$k];
        }
        foreach ($d as $v) {
            if (is_array($v) && isset($v[0]) && is_array($v[0])) {
                foreach (['image', 'photo'] as $k) {
                    if (! empty($v[0][$k])) return $v[0][$k];
                }
            }
        }
        return null;
    };
  @endphp

  <div class="list-head">
    <div>
      <h2>About page</h2>
      <p class="count">{{ count($sections) }} {{ \Illuminate\Support\Str::plural('section', count($sections)) }} · drag to reorder, toggle to show/hide</p>
    </div>
    <div class="head-actions">
      <a class="btn btn-ghost" href="{{ route('about') }}" target="_blank"><i data-lucide="external-link" style="width:15px;height:15px;"></i> View page</a>
      <a class="btn btn-ghost" href="{{ route('admin.about.live') }}"><i data-lucide="mouse-pointer-click" style="width:15px;height:15px;"></i> Live edit</a>
      <div class="add-wrap" id="add-wrap">
        <button class="btn btn-primary" type="button" id="add-btn"><i data-lucide="plus" style="width:16px;height:16px;"></i> Add section</button>
        <div class="add-menu">
          @foreach($types as $key => $t)
            <a class="add-item" href="{{ route('admin.about.create', ['type' => $key]) }}">
              <i data-lucide="{{ $t['icon'] }}"></i>
              <span><b>{{ $t['label'] }}</b><span>{{ $t['desc'] }}</span></span>
            </a>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  @if(count($sections))
    <div class="reorder-note">
      <i data-lucide="grip-vertical" style="width:15px;height:15px;"></i> Drag the handle on the left of each card to change the order sections appear on the page.
    </div>

    <div class="sec-list" id="sec-list">
      @foreach($sections as $section)
        @php
          $type = $section['type'] ?? '';
          $meta = $types[$type] ?? ['label' => ucfirst($type), 'icon' => 'square'];
          $visible = ($section['visible'] ?? true) === true;
          $t = $imgUrl($thumb($section));
        @endphp
        <article class="sec-card {{ $visible ? '' : 's-hidden' }}" data-id="{{ $section['id'] }}" data-visible="{{ $visible ? 1 : 0 }}">
          <span class="sec-grip" draggable="true" title="Drag to reorder"><i data-lucide="grip-vertical"></i></span>

          @if($t)
            <img class="sec-thumb" src="{{ $t }}" alt="" loading="lazy">
          @else
            <span class="sec-thumb-ph"><i data-lucide="{{ $meta['icon'] }}" style="width:18px;height:18px;"></i></span>
          @endif

          <div class="sec-main">
            <span class="sec-type"><i data-lucide="{{ $meta['icon'] }}"></i> {{ $meta['label'] }}</span>
            <h3 class="sec-title">{{ $summary($section) ?: $meta['label'] }}</h3>
            <p class="sec-sub">#{{ $section['id'] }}</p>
          </div>

          <div class="sec-acts">
            <form method="POST" action="{{ route('admin.about.visibility', $section['id']) }}" data-ajax>
              @csrf
              <button type="submit" class="sec-pill {{ $visible ? 'on' : 'off' }}" data-vis-btn>
                <i data-lucide="{{ $visible ? 'eye' : 'eye-off' }}"></i>
                <span>{{ $visible ? 'Visible' : 'Hidden' }}</span>
              </button>
            </form>
            <a class="btn btn-ghost btn-sm" href="{{ route('admin.about.edit', $section['id']) }}"><i data-lucide="pencil" style="width:14px;height:14px;"></i> Edit</a>
            <form method="POST" action="{{ route('admin.about.destroy', $section['id']) }}"
                  onsubmit="return confirm('Delete the “{{ addslashes($meta['label']) }}” section? This cannot be undone.');">
              @csrf @method('DELETE')
              <button class="btn btn-danger btn-sm" type="submit"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
            </form>
          </div>
        </article>
      @endforeach
    </div>
  @else
    <div class="empty">No sections yet. Use <b>Add section</b> to build the page.</div>
  @endif
@endsection

@push('scripts')
<script>
(function () {
  const CSRF = document.querySelector('meta[name=csrf-token]').content;

  /* ── Add-section dropdown ── */
  const addWrap = document.getElementById('add-wrap');
  if (addWrap) {
    document.getElementById('add-btn').addEventListener('click', (e) => { e.stopPropagation(); addWrap.classList.toggle('open'); });
    document.addEventListener('click', () => addWrap.classList.remove('open'));
    addWrap.querySelector('.add-menu').addEventListener('click', (e) => e.stopPropagation());
  }

  const list = document.getElementById('sec-list');
  if (!list) return;
  const flash = (msg) => { if (window.cmsToast) window.cmsToast(msg); };

  /* ── AJAX visibility toggle ── */
  list.addEventListener('submit', (e) => {
    const form = e.target.closest('form[data-ajax]');
    if (!form) return;
    e.preventDefault();
    fetch(form.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: new FormData(form) })
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(d => {
        const card = list.querySelector('.sec-card[data-id="' + d.id + '"]');
        if (card) {
          card.dataset.visible = d.visible ? '1' : '0';
          card.classList.toggle('s-hidden', !d.visible);
          const btn = card.querySelector('[data-vis-btn]');
          btn.className = 'sec-pill ' + (d.visible ? 'on' : 'off');
          btn.innerHTML = '<i data-lucide="' + (d.visible ? 'eye' : 'eye-off') + '"></i><span>' + (d.visible ? 'Visible' : 'Hidden') + '</span>';
          if (window.lucide) lucide.createIcons();
        }
        flash('✓ ' + (d.message || 'Updated'));
      })
      .catch(() => alert('Could not update. Please try again.'));
  });

  /* ── Drag to reorder (vertical) ── */
  const REORDER_URL = @json(route('admin.about.reorder'));
  let dragCard = null;
  list.querySelectorAll('.sec-grip').forEach(grip => {
    grip.addEventListener('dragstart', (e) => {
      dragCard = grip.closest('.sec-card');
      dragCard.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', dragCard.dataset.id);
      if (e.dataTransfer.setDragImage) e.dataTransfer.setDragImage(dragCard, 20, 20);
    });
    grip.addEventListener('dragend', () => {
      if (dragCard) dragCard.classList.remove('dragging');
      list.querySelectorAll('.drop-target').forEach(c => c.classList.remove('drop-target'));
      dragCard = null;
    });
  });
  list.addEventListener('dragover', (e) => {
    e.preventDefault();
    if (!dragCard) return;
    const over = e.target.closest('.sec-card');
    list.querySelectorAll('.drop-target').forEach(c => { if (c !== over) c.classList.remove('drop-target'); });
    if (!over || over === dragCard) return;
    over.classList.add('drop-target');
    const rect = over.getBoundingClientRect();
    const after = (e.clientY - rect.top) > rect.height / 2;
    list.insertBefore(dragCard, after ? over.nextSibling : over);
  });
  list.addEventListener('drop', (e) => {
    e.preventDefault();
    list.querySelectorAll('.drop-target').forEach(c => c.classList.remove('drop-target'));
    const ids = [...list.querySelectorAll('.sec-card')].map(c => c.dataset.id);
    fetch(REORDER_URL, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ ids })
    }).then(r => { if (!r.ok) throw new Error(); flash('✓ Order saved'); })
      .catch(() => alert('Could not save the new order. Please refresh and try again.'));
  });
})();
</script>
@endpush
