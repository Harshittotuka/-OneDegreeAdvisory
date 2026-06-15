@extends('admin.layout')
@section('title', 'Blog Posts')

@push('head')
<style>
  .cms-wrap { max-width: none; } /* full-width list */
  .list-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
  .list-head h2 { margin: 0; font-size: 1.5rem; letter-spacing: -.01em; }
  .list-head .count { margin: 3px 0 0; color: var(--muted); font-size: .9rem; }

  /* Filter pills */
  .filterbar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 20px; }
  .filter-pill { border: 1px solid var(--line); background: #fff; border-radius: 999px; padding: 7px 15px; font-size: .85rem;
    font-weight: 700; cursor: pointer; color: var(--muted); font-family: inherit; display: inline-flex; gap: 7px; align-items: center; }
  .filter-pill:hover { border-color: var(--teal); color: var(--teal); }
  .filter-pill.active { background: var(--ink); color: #fff; border-color: var(--ink); }
  .filter-pill .c { background: rgba(0,0,0,.08); border-radius: 999px; padding: 0 7px; font-size: .74rem; }
  .filter-pill.active .c { background: rgba(255,255,255,.22); }
  .filterbar .reorder-saved { margin-left: auto; }

  /* 4-column grid */
  .post-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 20px; }
  @media (max-width: 1240px) { .post-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
  @media (max-width: 920px)  { .post-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  @media (max-width: 560px)  { .post-grid { grid-template-columns: 1fr; } }
  .post-grid .post-card.is-filtered { display: none; }

  .post-card { position: relative; display: flex; flex-direction: column; background: var(--panel);
    border: 1px solid var(--line); border-radius: var(--radius); overflow: visible;
    box-shadow: var(--shadow); transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
  .post-card:hover { transform: translateY(-4px); }
  /* Status conveyed purely by a soft colored box-shadow — no border, no accent bar.
     Featured overrides visible/hidden. */
  .post-card.s-visible  { box-shadow: 0 6px 22px rgba(47,174,116,.30); }
  .post-card.s-visible:hover  { box-shadow: 0 14px 34px rgba(47,174,116,.42); }
  .post-card.s-hidden   { box-shadow: 0 6px 22px rgba(224,90,75,.28); }
  .post-card.s-hidden:hover   { box-shadow: 0 14px 34px rgba(224,90,75,.40); }
  .post-card.s-featured { box-shadow: 0 6px 24px rgba(234,164,0,.42); }
  .post-card.s-featured:hover { box-shadow: 0 14px 36px rgba(234,164,0,.55); }

  .post-thumb { position: relative; aspect-ratio: 16 / 9; background: #eef1f4; overflow: hidden; border-radius: var(--radius) var(--radius) 0 0; }
  .post-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
  .post-thumb .ph { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #aab4bf; }
  .post-card.s-hidden .post-thumb img, .post-card.s-hidden .post-thumb .ph { filter: grayscale(.75); opacity: .55; }
  .post-chip { position: absolute; left: 12px; top: 12px; background: rgba(20,37,62,.82); color: #fff;
    font-size: .7rem; font-weight: 700; padding: 4px 10px; border-radius: 999px; backdrop-filter: blur(4px); }
  .post-grip { position: absolute; left: 12px; bottom: 12px; display: inline-flex; align-items: center; gap: 5px;
    background: rgba(20,37,62,.82); color: #fff; font-size: .68rem; font-weight: 700; padding: 4px 9px;
    border-radius: 999px; cursor: grab; backdrop-filter: blur(4px); user-select: none; }
  .post-grip:active { cursor: grabbing; }
  .badge-br { position: absolute; right: 12px; bottom: 12px; display: inline-flex; align-items: center; gap: 5px;
    font-size: .68rem; font-weight: 800; padding: 4px 9px; border-radius: 999px; backdrop-filter: blur(4px); }
  .badge-feat { background: #f2b705; color: #4a3500; box-shadow: 0 4px 12px rgba(212,160,23,.5); }
  .badge-hidden { background: rgba(192,57,43,.92); color: #fff; }

  .post-card.dragging { opacity: .45; }
  .post-card.drop-target { outline: 2px dashed var(--teal); outline-offset: 3px; }

  /* Kebab menu */
  .kebab-wrap { position: absolute; top: 10px; right: 10px; z-index: 6; }
  .kebab { width: 32px; height: 32px; border-radius: 8px; border: 0; cursor: pointer; color: #fff;
    background: rgba(20,37,62,.82); display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
  .kebab:hover { background: rgba(20,37,62,.96); }
  .kebab-menu { position: absolute; right: 0; top: 38px; min-width: 184px; background: #fff; border: 1px solid var(--line);
    border-radius: 11px; box-shadow: 0 14px 34px rgba(13,33,42,.2); padding: 6px; display: none; }
  .kebab-wrap.open .kebab-menu { display: block; }
  .kebab-menu form { margin: 0; }
  .mi { width: 100%; display: flex; align-items: center; gap: 9px; background: none; border: 0; cursor: pointer;
    padding: 9px 10px; border-radius: 8px; font-family: inherit; font-size: .86rem; font-weight: 600; color: var(--ink); text-align: left; }
  .mi:hover { background: #f3f6f8; }
  .mi.green { color: #1f8a4c; } .mi.red { color: var(--danger); } .mi.gold { color: #9a6b00; }
  .mi i { width: 16px; height: 16px; }
  .mi-sep { height: 1px; background: var(--line); margin: 5px 4px; }

  .post-body { padding: 15px 16px 14px; display: flex; flex-direction: column; flex: 1; }
  .post-title { font-size: 1rem; font-weight: 800; line-height: 1.3; letter-spacing: -.01em; margin: 0 0 6px;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
  .post-meta { color: var(--muted); font-size: .8rem; margin: 0 0 12px; }
  .post-cats { display: flex; flex-wrap: wrap; gap: 5px; margin: 0 0 14px; }
  .post-cat { background: #eef1f4; color: #4b5a66; font-size: .72rem; font-weight: 700; padding: 2px 9px; border-radius: 999px; }
  .post-actions { margin-top: auto; display: flex; gap: 7px; padding-top: 12px; border-top: 1px solid var(--line); }
  .post-actions .btn { flex: 1; justify-content: center; }
  .post-actions form { flex: 1; display: flex; }

  .reorder-note { display: flex; align-items: center; gap: 8px; color: var(--muted); font-size: .82rem; margin-bottom: 14px; }
  .reorder-saved { color: var(--teal-dark); font-weight: 700; opacity: 0; transition: opacity .2s; }
  .reorder-saved.show { opacity: 1; }
  .empty, .no-match { text-align: center; color: var(--muted); padding: 50px 20px; border: 1px dashed var(--line); border-radius: var(--radius); }
</style>
@endpush

@section('content')
  @php
    $imgUrl = fn ($p) => $p ? (\Illuminate\Support\Str::startsWith($p, ['http://', 'https://', '/']) ? $p : asset($p)) : null;
    $visibleCount = collect($posts)->filter(fn ($p) => ($p['visible'] ?? true) === true)->count();
    $hiddenCount = count($posts) - $visibleCount;
    $featuredCount = collect($posts)->filter(fn ($p) => ! empty($p['featured']))->count();
  @endphp

  <div class="list-head">
    <div>
      <h2>Blog posts</h2>
      <p class="count">{{ count($posts) }} {{ \Illuminate\Support\Str::plural('post', count($posts)) }}</p>
    </div>
    <a class="btn btn-primary" href="{{ route('admin.blog.create') }}"><i data-lucide="plus" style="width:16px;height:16px;"></i> New post</a>
  </div>

  @if(count($posts))
    <div class="filterbar" id="filterbar">
      <button class="filter-pill active" data-filter="all">All <span class="c">{{ count($posts) }}</span></button>
      <button class="filter-pill" data-filter="visible"><i data-lucide="eye" style="width:14px;height:14px;"></i> Visible <span class="c">{{ $visibleCount }}</span></button>
      <button class="filter-pill" data-filter="hidden"><i data-lucide="eye-off" style="width:14px;height:14px;"></i> Hidden <span class="c">{{ $hiddenCount }}</span></button>
      <button class="filter-pill" data-filter="featured"><i data-lucide="star" style="width:14px;height:14px;"></i> Featured <span class="c">{{ $featuredCount }}</span></button>
    </div>

    <div class="reorder-note">
      <i data-lucide="grip-vertical" style="width:15px;height:15px;"></i> Drag the handle on each card to reorder posts.
    </div>

    <div class="post-grid" id="post-grid">
      @foreach($posts as $post)
        @php
          $visible = ($post['visible'] ?? true) === true;
          $featured = ! empty($post['featured']);
          $cats = $post['categories'] ?? array_filter([$post['category'] ?? null]);
          $stateClass = $featured ? 's-featured' : ($visible ? 's-visible' : 's-hidden');
        @endphp
        <article class="post-card {{ $stateClass }}" data-slug="{{ $post['slug'] }}"
                 data-visible="{{ $visible ? 1 : 0 }}" data-featured="{{ $featured ? 1 : 0 }}">
          <div class="post-thumb">
            @if($imgUrl($post['image'] ?? null))
              <img src="{{ $imgUrl($post['image']) }}" alt="{{ $post['alt'] ?? '' }}" loading="lazy">
            @else
              <div class="ph"><i data-lucide="image"></i></div>
            @endif
            @if(! empty($cats))<span class="post-chip">{{ $cats[array_key_first($cats)] }}</span>@endif
            <span class="post-grip" draggable="true" title="Drag to reorder"><i data-lucide="grip-vertical" style="width:13px;height:13px;"></i> Drag</span>
            @if($featured)
              <span class="badge-br badge-feat"><i data-lucide="star" style="width:12px;height:12px;"></i> Featured</span>
            @elseif(! $visible)
              <span class="badge-br badge-hidden"><i data-lucide="eye-off" style="width:12px;height:12px;"></i> Hidden</span>
            @endif
          </div>

          {{-- Kebab menu (outside thumb so its dropdown isn't clipped) --}}
          <div class="kebab-wrap" data-kebab>
            <button type="button" class="kebab" data-kebab-btn aria-label="More actions"><i data-lucide="more-vertical" style="width:18px;height:18px;"></i></button>
            <div class="kebab-menu">
              <form method="POST" action="{{ route('admin.blog.featured', $post['slug']) }}" data-ajax data-kind="feature" data-slug="{{ $post['slug'] }}">
                @csrf
                <button type="submit" class="mi gold" data-mi="feature">
                  <i data-lucide="star"></i> <span>{{ $featured ? 'Remove featured' : 'Set as featured' }}</span>
                </button>
              </form>
              <form method="POST" action="{{ route('admin.blog.visibility', $post['slug']) }}" data-ajax data-kind="visibility" data-slug="{{ $post['slug'] }}">
                @csrf
                <button type="submit" class="mi {{ $visible ? 'red' : 'green' }}" data-mi="visibility">
                  <i data-lucide="{{ $visible ? 'eye-off' : 'eye' }}"></i> <span>{{ $visible ? 'Hide from blog' : 'Make visible' }}</span>
                </button>
              </form>
              <div class="mi-sep"></div>
              <a class="mi" href="{{ route('admin.blog.edit', $post['slug']) }}"><i data-lucide="pencil"></i> Edit</a>
              <a class="mi" href="{{ route('blog.post', $post['slug']) }}" target="_blank"><i data-lucide="external-link"></i> View on site</a>
            </div>
          </div>

          <div class="post-body">
            <h3 class="post-title">{{ $post['title'] }}</h3>
            <p class="post-meta">
              {{ ! empty($post['date']) ? \Illuminate\Support\Carbon::parse($post['date'])->format('M j, Y') : '—' }}
              @if(! empty($post['read_time'])) · {{ $post['read_time'] }} min @endif
            </p>
            @if(! empty($post['link_url']))
              <p class="post-meta" style="color:#6c4fd6;font-weight:700;margin-top:-6px;">
                <i data-lucide="corner-up-right" style="width:13px;height:13px;vertical-align:-2px;"></i> Redirects to {{ $post['link_url'] }}
              </p>
            @endif
            @if(! empty($cats))
              <div class="post-cats">@foreach($cats as $c)<span class="post-cat">{{ $c }}</span>@endforeach</div>
            @endif
            <div class="post-actions">
              <a class="btn btn-ghost btn-sm" href="{{ route('admin.blog.edit', $post['slug']) }}"><i data-lucide="pencil" style="width:14px;height:14px;"></i> Edit</a>
              <form method="POST" action="{{ route('admin.blog.destroy', $post['slug']) }}"
                    onsubmit="return confirm('Delete “{{ addslashes($post['title']) }}”? This cannot be undone.');">
                @csrf @method('DELETE')
                <button class="btn btn-danger btn-sm" type="submit" style="flex:1;justify-content:center;"><i data-lucide="trash-2" style="width:14px;height:14px;"></i> Delete</button>
              </form>
            </div>
          </div>
        </article>
      @endforeach
    </div>
    <div class="no-match" id="no-match" style="display:none;">No posts match this filter.</div>
  @else
    <div class="empty">No posts yet. Create your first one.</div>
  @endif
@endsection

@push('scripts')
<script>
(function () {
  const grid = document.getElementById('post-grid');
  if (!grid) return;
  const CSRF = document.querySelector('meta[name=csrf-token]').content;
  const noMatch = document.getElementById('no-match');
  let activeFilter = 'all';

  const closeKebabs = () => document.querySelectorAll('[data-kebab].open').forEach(w => w.classList.remove('open'));
  const flash = (msg) => { if (window.cmsToast) window.cmsToast(msg); };

  /* ── Re-render a card from its data-visible / data-featured state ── */
  function renderCard(card) {
    const v = card.dataset.visible === '1';
    const f = card.dataset.featured === '1';
    card.classList.remove('s-visible', 's-hidden', 's-featured');
    card.classList.add(f ? 's-featured' : (v ? 's-visible' : 's-hidden'));

    const thumb = card.querySelector('.post-thumb');
    const old = thumb.querySelector('.badge-br');
    if (old) old.remove();
    if (f || !v) {
      const b = document.createElement('span');
      b.className = 'badge-br ' + (f ? 'badge-feat' : 'badge-hidden');
      b.innerHTML = f ? '<i data-lucide="star" style="width:12px;height:12px;"></i> Featured'
                      : '<i data-lucide="eye-off" style="width:12px;height:12px;"></i> Hidden';
      thumb.appendChild(b);
    }

    const fb = card.querySelector('[data-mi="feature"]');
    if (fb) fb.innerHTML = '<i data-lucide="star"></i> <span>' + (f ? 'Remove featured' : 'Set as featured') + '</span>';
    const vb = card.querySelector('[data-mi="visibility"]');
    if (vb) {
      vb.className = 'mi ' + (v ? 'red' : 'green');
      vb.innerHTML = '<i data-lucide="' + (v ? 'eye-off' : 'eye') + '"></i> <span>' + (v ? 'Hide from blog' : 'Make visible') + '</span>';
    }
    if (window.lucide) lucide.createIcons();
  }

  function refreshCounts() {
    const cards = [...grid.querySelectorAll('.post-card')];
    const vis = cards.filter(c => c.dataset.visible === '1').length;
    const feat = cards.filter(c => c.dataset.featured === '1').length;
    const set = (f, n) => { const el = document.querySelector('.filter-pill[data-filter="' + f + '"] .c'); if (el) el.textContent = n; };
    set('all', cards.length); set('visible', vis); set('hidden', cards.length - vis); set('featured', feat);
  }

  function applyFilter(f) {
    activeFilter = f;
    let shown = 0;
    grid.querySelectorAll('.post-card').forEach(card => {
      const v = card.dataset.visible === '1', ff = card.dataset.featured === '1';
      const m = f === 'all' || (f === 'visible' && v) || (f === 'hidden' && !v) || (f === 'featured' && ff);
      card.classList.toggle('is-filtered', !m);
      if (m) shown++;
    });
    noMatch.style.display = shown ? 'none' : 'block';
  }

  /* ── Kebab dropdowns ── */
  document.querySelectorAll('[data-kebab-btn]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const wrap = btn.closest('[data-kebab]');
      const open = wrap.classList.contains('open');
      closeKebabs();
      wrap.classList.toggle('open', !open);
    });
  });
  document.addEventListener('click', closeKebabs);

  /* ── Filter pills ── */
  document.querySelectorAll('.filter-pill').forEach(pill => {
    pill.addEventListener('click', () => {
      document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      applyFilter(pill.dataset.filter);
    });
  });

  /* ── AJAX toggles (feature / visibility) — no page reload ── */
  grid.addEventListener('submit', (e) => {
    const form = e.target.closest('form[data-ajax]');
    if (!form) return;
    e.preventDefault();
    fetch(form.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: new FormData(form) })
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(d => {
        grid.querySelectorAll('.post-card').forEach(c => {
          c.dataset.featured = (d.featuredSlug && c.dataset.slug === d.featuredSlug) ? '1' : '0';
        });
        const card = grid.querySelector('.post-card[data-slug="' + d.slug + '"]');
        if (card) card.dataset.visible = d.visible ? '1' : '0';
        if (d.featuredSlug) {
          const fc = grid.querySelector('.post-card[data-slug="' + d.featuredSlug + '"]');
          if (fc) fc.dataset.visible = '1';
        }
        grid.querySelectorAll('.post-card').forEach(renderCard);
        refreshCounts();
        applyFilter(activeFilter);
        closeKebabs();
        flash('✓ ' + (d.message || 'Updated'));
      })
      .catch(() => alert('Action failed. Please try again.'));
  });

  /* ── Drag to reorder ── */
  const REORDER_URL = @json(route('admin.blog.reorder'));
  let dragCard = null;
  grid.querySelectorAll('.post-grip').forEach(grip => {
    grip.addEventListener('dragstart', (e) => {
      dragCard = grip.closest('.post-card');
      dragCard.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', dragCard.dataset.slug);
      if (e.dataTransfer.setDragImage) e.dataTransfer.setDragImage(dragCard, 20, 20);
    });
    grip.addEventListener('dragend', () => {
      if (dragCard) dragCard.classList.remove('dragging');
      grid.querySelectorAll('.drop-target').forEach(c => c.classList.remove('drop-target'));
      dragCard = null;
    });
  });
  grid.addEventListener('dragover', (e) => {
    e.preventDefault();
    if (!dragCard) return;
    const over = e.target.closest('.post-card');
    grid.querySelectorAll('.drop-target').forEach(c => { if (c !== over) c.classList.remove('drop-target'); });
    if (!over || over === dragCard) return;
    over.classList.add('drop-target');
    const rect = over.getBoundingClientRect();
    const after = (e.clientY - rect.top) > rect.height / 2;
    grid.insertBefore(dragCard, after ? over.nextSibling : over);
  });
  grid.addEventListener('drop', (e) => {
    e.preventDefault();
    grid.querySelectorAll('.drop-target').forEach(c => c.classList.remove('drop-target'));
    const slugs = [...grid.querySelectorAll('.post-card')].map(c => c.dataset.slug);
    fetch(REORDER_URL, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ slugs })
    }).then(r => { if (!r.ok) throw new Error(); flash('✓ Order saved'); })
      .catch(() => alert('Could not save the new order. Please refresh and try again.'));
  });
})();
</script>
@endpush
