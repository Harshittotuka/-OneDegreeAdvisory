@extends('admin.layout')
@section('title', $mode === 'create' ? 'New post' : 'Edit post')

@push('head')
<style>
  .cms-wrap { max-width: none; } /* editor spans the full remaining width */
  .form-grid { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
  @media (max-width: 880px) { .form-grid { grid-template-columns: 1fr; } }
  .form-side { position: sticky; top: 84px; display: flex; flex-direction: column; gap: 18px; }
  @media (max-width: 880px) { .form-side { position: static; } }
  .panel-pad { padding: 22px; }
  .panel-head { display: flex; align-items: center; gap: 9px; margin: 0 0 18px; padding-bottom: 13px;
    border-bottom: 1px solid var(--line); font-size: 1.02rem; font-weight: 800; letter-spacing: -.01em; }
  .panel-head i { width: 18px; height: 18px; color: var(--teal); }
  .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  .blocks { display: flex; flex-direction: column; gap: 14px; }
  .block { border: 1px solid var(--line); border-radius: 11px; background: #fff; box-shadow: 0 2px 8px rgba(13,33,42,.04); }
  .block-head { display: flex; align-items: center; justify-content: space-between; gap: 10px;
    padding: 9px 12px; border-bottom: 1px solid var(--line); background: #f8f5f1; border-radius: 11px 11px 0 0; }
  .block-kind { display: inline-flex; align-items: center; gap: 6px; font-weight: 800; font-size: .72rem;
    text-transform: uppercase; letter-spacing: .07em; color: var(--teal-dark); }
  .block-body { padding: 13px; }
  .block-ctl { display: flex; gap: 4px; }
  .icon-btn { cursor: pointer; border: 1px solid var(--line); background: #fff; border-radius: 7px;
    width: 30px; height: 28px; font-size: .82rem; line-height: 1; color: var(--muted); }
  .icon-btn:hover { border-color: var(--teal); color: var(--teal); }
  .icon-btn.del:hover { border-color: var(--danger); color: var(--danger); }
  /* Block buttons as a floating bottom-center sticky bar */
  .toolbar { position: fixed; left: 50%; bottom: 18px; transform: translateX(-50%); z-index: 40;
    display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 8px; margin: 0;
    background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 9px 12px;
    box-shadow: 0 16px 40px rgba(13,33,42,.22); max-width: calc(100vw - 32px); }
  @media (min-width: 881px) { .toolbar { left: calc(50% + var(--sidebar-w) / 2); } }
  .toolbar-label { font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .07em;
    color: var(--muted); padding: 0 4px 0 2px; }
  .toolbar button { display: inline-flex; align-items: center; gap: 6px; cursor: pointer; border: 1px solid var(--line);
    background: #fff; border-radius: 10px; padding: 9px 13px; font-size: .85rem; font-weight: 700; color: var(--ink); font-family: inherit;
    transition: background .15s, border-color .15s, color .15s; }
  .toolbar button:hover { border-color: var(--teal); color: var(--teal); background: var(--teal-soft); }
  /* Mobile: full-width sticky bottom bar that scrolls horizontally */
  @media (max-width: 880px) {
    .toolbar { left: 0; right: 0; bottom: 0; transform: none; width: 100%; max-width: none;
      flex-wrap: nowrap; overflow-x: auto; justify-content: flex-start; gap: 7px; padding: 10px 12px;
      border-radius: 14px 14px 0 0; border-left: 0; border-right: 0; -webkit-overflow-scrolling: touch; }
    .toolbar-label { display: none; }
    .toolbar button { flex: 0 0 auto; white-space: nowrap; }
  }

  /* Tinted sidebar panels so each section is visually distinct */
  .panel-details { background: #f5f8fc; border-color: #dbe6f2; }
  .panel-details .panel-head { color: #1d3a6b; border-color: #dbe6f2; }
  .panel-details .panel-head i { color: #2a4f8a; }
  .panel-hero { background: #fdf5ee; border-color: #f2dcc6; }
  .panel-hero .panel-head { color: var(--teal-dark); border-color: #f2dcc6; }
  .panel-hero .panel-head i { color: var(--teal); }
  .img-preview { margin-top: 10px; max-width: 100%; border-radius: 9px; border: 1px solid var(--line); display: none; }
  .img-preview[src] { display: block; }
  .uploading { font-size: .8rem; color: var(--teal); margin-top: 6px; }
  .empty-blocks { text-align: center; color: var(--muted); padding: 30px; border: 1px dashed var(--line); border-radius: 11px; background: #fafbfc; }
  /* Drop-style file inputs */
  .filedrop { display: block; border: 1px dashed #cfd8e0; border-radius: 9px; padding: 10px 12px; background: #fafbfc;
    font-size: .84rem; color: var(--muted); cursor: pointer; }
  .filedrop:hover { border-color: var(--teal); color: var(--teal); }
  .save-row { display: flex; gap: 10px; }
  /* Hero image: Upload / Link toggle + preview */
  .hero-tabs { display: flex; gap: 5px; background: #fff; border: 1px solid var(--line); border-radius: 10px; padding: 4px; margin-bottom: 12px; }
  .hero-tab { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 0;
    background: none; cursor: pointer; padding: 8px; border-radius: 7px; font-family: inherit; font-weight: 700; font-size: .85rem; color: var(--muted); }
  .hero-tab i { width: 15px; height: 15px; }
  .hero-tab.active { background: var(--teal); color: #fff; }
  [data-hero-pane][hidden] { display: none; }
  .hero-preview-wrap { position: relative; margin-top: 12px; }
  .hero-preview-wrap img { width: 100%; border-radius: 9px; border: 1px solid var(--line); display: none; }
  .hero-preview-wrap img[src] { display: block; }
  .hero-preview-empty { border: 1px dashed var(--line); border-radius: 9px; padding: 22px; text-align: center;
    color: var(--muted); font-size: .82rem; background: #fff; }
  .hero-remove { position: absolute; top: 8px; right: 8px; background: rgba(20,37,62,.85); color: #fff; border: 0;
    border-radius: 7px; padding: 5px 10px; font-size: .76rem; font-weight: 700; cursor: pointer; display: none; align-items: center; gap: 5px; }
  .hero-remove:hover { background: var(--danger); }
  /* Category tag input */
  .tagbox { display: flex; flex-wrap: wrap; align-items: center; gap: 7px; border: 1px solid var(--line);
    border-radius: 10px; padding: 8px 10px; background: #fff; }
  .tagbox:focus-within { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(102,108,255,.13); }
  .tagbox input { border: 0; outline: 0; box-shadow: none; padding: 4px; flex: 1; min-width: 120px; font-size: .9rem; }
  .tag { display: inline-flex; align-items: center; gap: 6px; background: var(--teal-soft); color: var(--teal-dark);
    border: 1px solid #cdd0ff; border-radius: 999px; padding: 4px 6px 4px 11px; font-size: .82rem; font-weight: 700; }
  .tag button { border: 0; background: none; cursor: pointer; color: var(--teal-dark); line-height: 1; font-size: 1rem; padding: 0 2px; }
  .tag button:hover { color: var(--danger); }
  /* Status toggles */
  .status-toggle { display: flex; align-items: flex-start; gap: 10px; cursor: pointer; background: #fafbfc;
    border: 1px solid var(--line); border-radius: 10px; padding: 11px 12px; margin-bottom: 10px; font-weight: 700; font-size: .9rem; }
  .status-toggle input { width: auto; margin-top: 2px; }
  .status-toggle .hint { margin: 2px 0 0; font-weight: 500; }
</style>
@endpush

@section('content')
  <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px;">
    <div>
      <h1 style="margin:0;font-size:1.45rem;letter-spacing:-.01em;">{{ $mode === 'create' ? 'New post' : 'Edit post' }}</h1>
      @if($mode === 'edit')<p style="margin:3px 0 0;color:var(--muted);font-size:.85rem;">/blog/{{ $post['slug'] }}</p>@endif
    </div>
    <a class="btn btn-ghost" href="{{ route('admin.blog.index') }}"><i data-lucide="arrow-left" style="width:15px;height:15px;"></i> All posts</a>
  </div>

  <form method="POST" id="post-form"
        action="{{ $mode === 'create' ? route('admin.blog.store') : route('admin.blog.update', $post['slug']) }}">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif
    <input type="hidden" name="body" id="body-input">

    <div class="form-grid">
      {{-- Main column --}}
      <div style="display:flex;flex-direction:column;gap:24px;">
        <div class="panel panel-pad">
          <div class="field">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="{{ old('title', $post['title']) }}" placeholder="Your post headline…" required>
          </div>
          <div class="field" style="margin-bottom:0;">
            <label for="excerpt">Excerpt / summary</label>
            <textarea id="excerpt" name="excerpt" maxlength="400">{{ old('excerpt', $post['excerpt']) }}</textarea>
            <div class="hint">Shown on the blog listing and as the lead paragraph.</div>
          </div>
        </div>

        <div class="panel panel-pad">
          <h2 class="panel-head"><i data-lucide="layout-list"></i> Content blocks</h2>
          <div class="toolbar">
            <span class="toolbar-label">Add block</span>
            <button type="button" data-add="p"><i data-lucide="pilcrow" style="width:15px;height:15px;"></i> Paragraph</button>
            <button type="button" data-add="h2"><i data-lucide="heading" style="width:15px;height:15px;"></i> Heading</button>
            <button type="button" data-add="list"><i data-lucide="list" style="width:15px;height:15px;"></i> List</button>
            <button type="button" data-add="table"><i data-lucide="table" style="width:15px;height:15px;"></i> Table</button>
            <button type="button" data-add="quote"><i data-lucide="quote" style="width:15px;height:15px;"></i> Quote</button>
            <button type="button" data-add="image"><i data-lucide="image" style="width:15px;height:15px;"></i> Image</button>
          </div>
          <div class="blocks" id="blocks"></div>
        </div>
      </div>

      {{-- Sidebar --}}
      <div class="form-side">
        <div class="panel panel-pad">
          <div class="save-row">
            <button class="btn btn-primary" type="submit" style="flex:1;justify-content:center;">
              <i data-lucide="check" style="width:16px;height:16px;"></i> {{ $mode === 'create' ? 'Create post' : 'Save changes' }}
            </button>
            @if($mode === 'edit')
              <a class="btn btn-ghost" href="{{ route('blog.post', $post['slug']) }}" target="_blank" title="Preview"><i data-lucide="external-link" style="width:16px;height:16px;"></i></a>
            @endif
          </div>
        </div>

        <div class="panel panel-pad panel-details">
          <h2 class="panel-head"><i data-lucide="settings-2"></i> Details</h2>
          <div class="field">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" value="{{ old('slug', $post['slug']) }}" placeholder="auto-from-title">
            <div class="hint">URL: /blog/<span id="slug-preview">{{ $post['slug'] ?: '…' }}</span></div>
          </div>
          <div class="field">
            <label>Categories</label>
            <div class="tagbox" id="tagbox">
              <span id="tags"></span>
              <input type="text" id="cat-input" list="cat-list" placeholder="Add a category…" autocomplete="off">
            </div>
            <datalist id="cat-list">
              @foreach($categories as $c)<option value="{{ $c }}">@endforeach
            </datalist>
            <div id="cat-hidden"></div>
            <div class="hint">Press Enter or comma to add. Choose an existing one or type a new category.</div>
          </div>
          <div class="row-2">
            <div class="field">
              <label for="date">Date</label>
              <input type="date" id="date" name="date" value="{{ old('date', $post['date']) }}" required>
            </div>
            <div class="field">
              <label for="read_time">Read (min)</label>
              <input type="number" id="read_time" name="read_time" min="1" max="120"
                     value="{{ old('read_time', $post['read_time']) }}" placeholder="auto">
            </div>
          </div>
          <div class="field">
            <label for="author">Author</label>
            <input type="text" id="author" name="author" value="{{ old('author', $post['author']) }}">
          </div>
          <label class="status-toggle">
            <input type="checkbox" name="visible" value="1" @checked(old('visible', $post['visible'] ?? true))>
            <span>Visible on the blog<br><span class="hint">Turn off to hide this post from the blog listing.</span></span>
          </label>
          <label class="status-toggle">
            <input type="checkbox" name="featured" value="1" @checked(old('featured', $post['featured'] ?? false))>
            <span>⭐ Feature this post<br><span class="hint">Pins it to the top of the blog on every page. Only one post can be featured.</span></span>
          </label>
        </div>

        <div class="panel panel-pad panel-hero">
          <h2 class="panel-head"><i data-lucide="image"></i> Hero image</h2>

          @php($heroVal = old('image', $post['image']))
          <div class="hero-tabs">
            <button type="button" class="hero-tab" data-hero-tab="upload"><i data-lucide="upload"></i> Upload</button>
            <button type="button" class="hero-tab" data-hero-tab="link"><i data-lucide="link"></i> Link</button>
          </div>

          <div data-hero-pane="upload">
            <label class="filedrop" for="hero-file"><i data-lucide="upload" style="width:14px;height:14px;vertical-align:-2px;"></i> Choose an image to upload…</label>
            <input type="file" accept="image/*" id="hero-file" style="display:none;">
            <div class="uploading" id="hero-uploading" style="display:none;">Uploading…</div>
          </div>

          <div data-hero-pane="link">
            <input type="text" id="image" name="image" value="{{ $heroVal }}" placeholder="Paste an image URL (https://…)">
          </div>

          <div class="hero-preview-wrap">
            <img id="hero-preview" alt="" @if($heroVal) src="{{ \Illuminate\Support\Str::startsWith($heroVal, ['http','/']) ? $heroVal : asset($heroVal) }}" @endif>
            <div class="hero-preview-empty" id="hero-preview-empty">No image selected</div>
            <button type="button" class="hero-remove" id="hero-remove"><i data-lucide="x" style="width:13px;height:13px;"></i> Remove</button>
          </div>

          <div class="field" style="margin-top:12px;">
            <label for="alt">Image alt text</label>
            <input type="text" id="alt" name="alt" value="{{ old('alt', $post['alt']) }}">
          </div>
        </div>
      </div>
    </div>
  </form>
@endsection

@push('scripts')
<script>
(function () {
  const UPLOAD_URL = @json(route('admin.blog.upload'));
  const CSRF = document.querySelector('meta[name=csrf-token]').content;
  const existing = @json($post['body'] ?? []);

  const blocksEl = document.getElementById('blocks');

  const esc = (s) => (s == null ? '' : String(s));

  function uploadImage(file, onStart, onDone, onFail) {
    onStart && onStart();
    const fd = new FormData();
    fd.append('file', file);
    fetch(UPLOAD_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: fd })
      .then(r => r.ok ? r.json() : Promise.reject(r))
      .then(d => onDone(d.url))
      .catch(() => { onFail && onFail(); alert('Image upload failed. Use a JPG/PNG under 5 MB.'); });
  }

  // ── Hero image: Upload / Link toggle, shared preview ──
  const heroFile = document.getElementById('hero-file');
  const heroPreview = document.getElementById('hero-preview');
  const heroEmpty = document.getElementById('hero-preview-empty');
  const heroRemove = document.getElementById('hero-remove');
  const heroUploading = document.getElementById('hero-uploading');
  const imageInput = document.getElementById('image'); // the single source of truth (name="image")
  let heroUploadId = 0; // bumped to invalidate an in-flight upload if the value changes

  function updateHeroPreview() {
    const v = imageInput.value.trim();
    if (v) { heroPreview.src = v; heroEmpty.style.display = 'none'; heroRemove.style.display = 'inline-flex'; }
    else { heroPreview.removeAttribute('src'); heroEmpty.style.display = 'block'; heroRemove.style.display = 'none'; }
  }

  // Tab switching (Upload | Link)
  const panes = { upload: document.querySelector('[data-hero-pane="upload"]'), link: document.querySelector('[data-hero-pane="link"]') };
  function setHeroTab(tab) {
    document.querySelectorAll('.hero-tab').forEach(b => b.classList.toggle('active', b.dataset.heroTab === tab));
    panes.upload.hidden = tab !== 'upload';
    panes.link.hidden = tab !== 'link';
  }
  document.querySelectorAll('.hero-tab').forEach(b => b.addEventListener('click', () => setHeroTab(b.dataset.heroTab)));
  setHeroTab(imageInput.value.trim() ? 'link' : 'upload'); // show the URL if one already exists

  heroFile.addEventListener('change', () => {
    if (!heroFile.files[0]) return;
    const id = ++heroUploadId;
    uploadImage(heroFile.files[0],
      () => heroUploading.style.display = 'block',
      (url) => {
        heroUploading.style.display = 'none';
        if (id !== heroUploadId) return; // value changed meanwhile — don't clobber it
        imageInput.value = url;
        updateHeroPreview();
      },
      () => heroUploading.style.display = 'none');
  });

  // Typing/pasting a URL updates the preview live and cancels any pending upload result.
  imageInput.addEventListener('input', () => { heroUploadId++; updateHeroPreview(); });

  // Remove clears the chosen image (the stored file, if any, is left untouched).
  heroRemove.addEventListener('click', () => { imageInput.value = ''; heroUploadId++; updateHeroPreview(); heroFile.value = ''; });

  updateHeroPreview();

  // ── Slug helpers ──
  const titleEl = document.getElementById('title');
  const slugEl = document.getElementById('slug');
  const slugPreview = document.getElementById('slug-preview');
  const slugify = (s) => s.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  let slugTouched = {{ $post['slug'] ? 'true' : 'false' }};
  slugEl.addEventListener('input', () => { slugTouched = true; slugPreview.textContent = slugify(slugEl.value) || '…'; });
  titleEl.addEventListener('input', () => {
    if (!slugTouched) { slugEl.value = slugify(titleEl.value); slugPreview.textContent = slugEl.value || '…'; }
  });

  // ── Block templates ──
  function blockTemplate(kind, data) {
    data = data || {};
    switch (kind) {
      case 'p':
        return `<textarea class="b-text" rows="3" placeholder="Paragraph text…">${esc(data.text || data.html)}</textarea>`;
      case 'h2':
        return `<input type="text" class="b-text" placeholder="Section heading…" value="${esc(data.text).replace(/"/g,'&quot;')}">`;
      case 'list':
        return `<textarea class="b-items" rows="4" placeholder="One item per line">${(data.items || []).join('\n')}</textarea>
                <div class="hint">One list item per line.</div>`;
      case 'table': {
        const rows = (data.rows || []).map(r => r.join(' | ')).join('\n');
        return `<textarea class="b-rows" rows="5" placeholder="Header A | Header B&#10;Cell 1 | Cell 2">${esc(rows)}</textarea>
                <div class="hint">One row per line. Separate cells with " | ". First row is the header.</div>`;
      }
      case 'quote':
        return `<textarea class="b-text" rows="2" placeholder="Quote text…">${esc(data.text)}</textarea>
                <input type="text" class="b-attr" style="margin-top:8px;" placeholder="Attribution (optional)" value="${esc(data.attribution).replace(/"/g,'&quot;')}">`;
      case 'image':
        return `<input type="hidden" class="b-url" value="${esc(data.url).replace(/"/g,'&quot;')}">
                <input type="file" class="b-file" accept="image/*">
                <div class="uploading b-uploading" style="display:none;">Uploading…</div>
                <img class="img-preview b-preview" ${data.url ? `src="${esc(data.url).replace(/"/g,'&quot;')}"` : ''} alt="">
                <input type="text" class="b-alt" style="margin-top:8px;" placeholder="Alt text" value="${esc(data.alt).replace(/"/g,'&quot;')}">
                <input type="text" class="b-caption" style="margin-top:8px;" placeholder="Caption (optional)" value="${esc(data.caption).replace(/"/g,'&quot;')}">`;
    }
    return '';
  }

  const KIND_LABEL = { p: 'Paragraph', h2: 'Heading', list: 'List', table: 'Table', quote: 'Quote', image: 'Image' };

  function makeBlock(kind, data) {
    const el = document.createElement('div');
    el.className = 'block';
    el.dataset.kind = kind;
    el.innerHTML = `
      <div class="block-head">
        <span class="block-kind">${KIND_LABEL[kind] || kind}</span>
        <span class="block-ctl">
          <button type="button" class="icon-btn up" title="Move up">↑</button>
          <button type="button" class="icon-btn down" title="Move down">↓</button>
          <button type="button" class="icon-btn del" title="Remove">✕</button>
        </span>
      </div>
      <div class="block-body">${blockTemplate(kind, data)}</div>`;

    el.querySelector('.del').addEventListener('click', () => { el.remove(); refreshEmpty(); });
    el.querySelector('.up').addEventListener('click', () => { const p = el.previousElementSibling; if (p) blocksEl.insertBefore(el, p); });
    el.querySelector('.down').addEventListener('click', () => { const n = el.nextElementSibling; if (n) blocksEl.insertBefore(n, el); });

    if (kind === 'image') {
      const file = el.querySelector('.b-file');
      const urlInput = el.querySelector('.b-url');
      const preview = el.querySelector('.b-preview');
      const up = el.querySelector('.b-uploading');
      file.addEventListener('change', () => {
        if (!file.files[0]) return;
        uploadImage(file.files[0],
          () => up.style.display = 'block',
          (url) => { up.style.display = 'none'; urlInput.value = url; preview.src = url; },
          () => up.style.display = 'none');
      });
    }
    return el;
  }

  function addBlock(kind, data) { blocksEl.appendChild(makeBlock(kind, data)); refreshEmpty(); }

  function refreshEmpty() {
    const ph = blocksEl.querySelector('.empty-blocks');
    if (!blocksEl.querySelector('.block')) {
      if (!ph) { const d = document.createElement('div'); d.className = 'empty-blocks'; d.textContent = 'No content blocks yet — add one above.'; blocksEl.appendChild(d); }
    } else if (ph) { ph.remove(); }
  }

  document.querySelectorAll('[data-add]').forEach(btn =>
    btn.addEventListener('click', () => addBlock(btn.dataset.add, {})));

  // ── Serialize on submit ──
  function serialize() {
    const out = [];
    blocksEl.querySelectorAll('.block').forEach(el => {
      const kind = el.dataset.kind;
      if (kind === 'p' || kind === 'h2') {
        out.push({ kind, text: el.querySelector('.b-text').value });
      } else if (kind === 'list') {
        out.push({ kind, items: el.querySelector('.b-items').value.split('\n').map(s => s.trim()).filter(Boolean) });
      } else if (kind === 'table') {
        const rows = el.querySelector('.b-rows').value.split('\n').map(line => line.split('|').map(c => c.trim())).filter(r => r.join(''));
        out.push({ kind, rows });
      } else if (kind === 'quote') {
        out.push({ kind, text: el.querySelector('.b-text').value, attribution: el.querySelector('.b-attr').value });
      } else if (kind === 'image') {
        out.push({ kind, url: el.querySelector('.b-url').value, alt: el.querySelector('.b-alt').value, caption: el.querySelector('.b-caption').value });
      }
    });
    return out;
  }

  document.getElementById('post-form').addEventListener('submit', () => {
    document.getElementById('body-input').value = JSON.stringify(serialize());
  });

  // ── Hydrate existing content ──
  if (Array.isArray(existing) && existing.length) {
    existing.forEach(b => { if (b && b.kind) addBlock(b.kind, b); });
  }
  refreshEmpty();
})();
</script>

<script>
// ── Category tag input ──
(function () {
  const wrap = document.getElementById('tagbox');
  const tagsEl = document.getElementById('tags');
  const input = document.getElementById('cat-input');
  const hidden = document.getElementById('cat-hidden');
  let cats = @json(array_values($post['categories'] ?? array_filter([$post['category'] ?? null])));

  function render() {
    tagsEl.innerHTML = '';
    hidden.innerHTML = '';
    cats.forEach((c, i) => {
      const t = document.createElement('span');
      t.className = 'tag';
      const label = document.createElement('span');
      label.textContent = c;
      const x = document.createElement('button');
      x.type = 'button'; x.textContent = '×'; x.setAttribute('aria-label', 'Remove ' + c);
      x.addEventListener('click', () => { cats.splice(i, 1); render(); });
      t.append(label, x);
      tagsEl.appendChild(t);

      const h = document.createElement('input');
      h.type = 'hidden'; h.name = 'categories[]'; h.value = c;
      hidden.appendChild(h);
    });
  }

  function add(v) {
    v = (v || '').replace(/,$/, '').trim();
    if (!v) return;
    if (!cats.some(c => c.toLowerCase() === v.toLowerCase())) cats.push(v);
    input.value = '';
    render();
  }

  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); add(input.value); }
    else if (e.key === 'Backspace' && !input.value && cats.length) { cats.pop(); render(); }
  });
  input.addEventListener('change', () => add(input.value)); // datalist selection
  input.addEventListener('blur', () => { if (input.value.trim()) add(input.value); });
  wrap.addEventListener('click', () => input.focus());

  render();
})();
</script>
@endpush
