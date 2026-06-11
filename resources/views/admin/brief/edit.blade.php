@extends('admin.layout')
@section('title', 'Edit page')

@push('head')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<style>
  .bp-meta { display:grid; grid-template-columns:1.4fr 1.4fr .8fr; gap:14px; align-items:end; margin-bottom:16px; }
  .bp-meta .field { margin:0; }
  .bp-meta .bp-meta-actions { display:flex; gap:8px; justify-content:flex-end; align-items:center; }
  .bp-path { font-size:.78rem; color:var(--muted); }
  .bp-path code { background:#f3f2fb; padding:2px 7px; border-radius:6px; }
  .bp-vis { display:inline-flex; align-items:center; gap:8px; font-weight:700; font-size:.85rem; }

  .bp-layout { display:grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap:18px; align-items:start; }
  @media (max-width:980px){ .bp-layout{ grid-template-columns:1fr; } }

  /* Builder column */
  .bp-add { position:relative; margin-bottom:14px; }
  .bp-add-menu { position:absolute; z-index:30; top:calc(100% + 6px); left:0; width:320px; max-height:60vh; overflow:auto;
    background:#fff; border:1px solid var(--line); border-radius:12px; box-shadow:var(--shadow-lg); padding:8px; display:none; }
  .bp-add.open .bp-add-menu { display:block; }
  .bp-add-item { display:grid; grid-template-columns:30px 1fr; gap:10px; width:100%; text-align:left; border:0; background:none; cursor:pointer;
    padding:9px 10px; border-radius:9px; font-family:inherit; }
  .bp-add-item:hover { background:#f6f5fb; }
  .bp-add-item i { width:18px; height:18px; color:var(--teal); margin-top:3px; }
  .bp-add-item b { display:block; font-size:.9rem; color:var(--ink); }
  .bp-add-item small { color:var(--muted); font-size:.74rem; line-height:1.3; }

  .bp-blocks { display:flex; flex-direction:column; gap:12px; }
  .bp-block { border:1px solid var(--line); border-radius:12px; background:#fff; box-shadow:var(--shadow); overflow:hidden; }
  .bp-block.is-hidden { opacity:.62; }
  .bp-block-bar { display:flex; align-items:center; gap:9px; padding:10px 12px; background:#faf9ff; border-bottom:1px solid var(--line); }
  .bp-grip { cursor:grab; color:#b7b3c9; display:inline-flex; touch-action:none; }
  .bp-grip:active { cursor:grabbing; }
  .bp-block-ic { display:inline-flex; width:26px; height:26px; align-items:center; justify-content:center; border-radius:7px; background:var(--teal-soft); color:var(--teal); }
  .bp-block-ic i { width:15px; height:15px; }
  .bp-block-name { font-size:.92rem; }
  .bp-block-sp { flex:1; }
  .bp-icbtn { border:0; background:none; cursor:pointer; color:var(--muted); padding:5px; border-radius:7px; display:inline-flex; }
  .bp-icbtn:hover { background:#efeef7; color:var(--ink); }
  .bp-icbtn i { width:16px; height:16px; }
  .bp-del:hover { background:#fdecea; color:var(--danger); }
  .bp-block.is-collapsed .bp-fields { display:none; }
  .bp-fields { padding:14px 14px 4px; }

  .bp-field { margin-bottom:13px; }
  .bp-field > label { display:block; font-weight:700; font-size:.78rem; margin-bottom:5px; color:var(--ink); }
  .bp-hint { font-weight:500; color:var(--muted); font-size:.7rem; }
  .bp-field input[type=text], .bp-field textarea, .bp-field select { width:100%; padding:9px 11px; border:1px solid var(--line); border-radius:9px;
    font-family:inherit; font-size:.88rem; color:var(--ink); background:#fff; }
  .bp-field textarea { resize:vertical; min-height:62px; }
  .bp-mono { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.8rem !important; }
  .bp-field--check label { display:flex; align-items:center; gap:8px; font-weight:600; font-size:.86rem; cursor:pointer; }
  .bp-check input { width:16px; height:16px; }

  /* Color */
  .bp-color { display:flex; align-items:center; gap:8px; }
  .bp-color-pick { width:38px; height:34px; padding:0; border:1px solid var(--line); border-radius:8px; background:none; cursor:pointer; }
  .bp-color-hex { flex:1; }
  .bp-color-clear { border:1px solid var(--line); background:#fff; border-radius:8px; width:34px; height:34px; cursor:pointer; color:var(--muted); }
  .bp-color-clear:hover { color:var(--danger); border-color:#f0c4be; }

  /* Icon */
  .bp-icon { display:flex; align-items:center; gap:9px; }
  .bp-icon-prev { width:38px; height:34px; border:1px solid var(--line); border-radius:8px; display:inline-flex; align-items:center; justify-content:center; color:var(--teal); background:var(--teal-soft); }
  .bp-icon-prev i { width:18px; height:18px; }
  .bp-icon-name { flex:1; }

  /* Image */
  .bp-image { display:flex; gap:10px; }
  .bp-image-prev { flex:none; width:74px; height:56px; border:1px solid var(--line); border-radius:9px; overflow:hidden; display:flex; align-items:center; justify-content:center; background:#f6f5fb; color:#b7b3c9; }
  .bp-image-prev img { width:100%; height:100%; object-fit:cover; }
  .bp-image-ctrls { flex:1; display:flex; flex-direction:column; gap:7px; }
  .bp-image-btns { display:flex; gap:7px; }
  .bp-mini { display:inline-flex; align-items:center; gap:6px; cursor:pointer; border:1px solid var(--line); background:#fff; border-radius:8px; padding:7px 10px; font-size:.78rem; font-weight:700; color:var(--ink); }
  .bp-mini:hover { border-color:var(--teal); color:var(--teal); }
  .bp-mini i { width:14px; height:14px; }

  /* Repeater */
  .bp-field--rep > label { margin-bottom:7px; }
  .bp-rep-items { display:flex; flex-direction:column; gap:9px; }
  .bp-rep-item { border:1px solid var(--line); border-radius:10px; background:#fbfbfe; }
  .bp-rep-item-bar { display:flex; align-items:center; gap:6px; padding:5px 8px; border-bottom:1px dashed var(--line); }
  .bp-rep-grip { cursor:grab; color:#b7b3c9; display:inline-flex; touch-action:none; }
  .bp-rep-del { margin-left:auto; border:0; background:none; cursor:pointer; color:var(--muted); padding:3px; border-radius:6px; display:inline-flex; }
  .bp-rep-del:hover { background:#fdecea; color:var(--danger); }
  .bp-rep-del i, .bp-rep-grip i { width:14px; height:14px; }
  .bp-rep-item-body { padding:11px 11px 1px; }
  .bp-rep-add { margin-top:9px; display:inline-flex; align-items:center; gap:6px; border:1px dashed #c9c6e0; background:#fff; color:var(--teal-dark);
    border-radius:9px; padding:8px 12px; font-family:inherit; font-weight:700; font-size:.8rem; cursor:pointer; }
  .bp-rep-add:hover { border-color:var(--teal); background:var(--teal-soft); }
  .bp-rep-add i { width:14px; height:14px; }
  .bp-dragging { opacity:.5; outline:2px dashed var(--teal); }

  .bp-appear { margin:6px 0 12px; border:1px solid var(--line); border-radius:10px; background:#fbfbfe; }
  .bp-appear > summary { cursor:pointer; list-style:none; display:flex; align-items:center; gap:8px; padding:9px 12px; font-weight:700; font-size:.82rem; color:var(--muted); }
  .bp-appear > summary i { width:15px; height:15px; }
  .bp-appear[open] > summary { border-bottom:1px solid var(--line); color:var(--ink); }
  .bp-appear-body { padding:12px 12px 1px; }

  /* Preview */
  .bp-preview { position:sticky; top:84px; }
  @media (max-width:980px){ .bp-preview{ position:static; } }
  .bp-preview-bar { display:flex; align-items:center; gap:8px; margin-bottom:8px; }
  .bp-preview-bar .bp-pv-title { font-weight:800; font-size:.82rem; color:var(--muted); text-transform:uppercase; letter-spacing:.06em; }
  .bp-dev { margin-left:auto; display:inline-flex; border:1px solid var(--line); border-radius:9px; overflow:hidden; }
  .bp-dev button { border:0; background:#fff; cursor:pointer; padding:7px 10px; color:var(--muted); display:inline-flex; }
  .bp-dev button.is-active { background:var(--teal); color:#fff; }
  .bp-dev button i { width:15px; height:15px; }
  .bp-preview-stage { border:1px solid var(--line); border-radius:14px; overflow:hidden; background:#f4f0ff; box-shadow:var(--shadow); display:flex; justify-content:center; }
  #bp-frame { width:100%; height:calc(100vh - 200px); min-height:480px; border:0; background:#fff; transition:width .2s ease; }
  .bp-preview-stage.is-phone { padding:14px 0; }
  .bp-preview-stage.is-phone #bp-frame { width:390px; max-width:100%; height:calc(100vh - 230px); border:1px solid var(--line); border-radius:16px; }

  /* Crop modal */
  .bp-crop { position:fixed; inset:0; z-index:2000; background:rgba(16,8,40,.6); display:none; align-items:center; justify-content:center; padding:20px; }
  .bp-crop.open { display:flex; }
  .bp-crop-card { width:min(720px,96vw); background:#fff; border-radius:14px; overflow:hidden; display:flex; flex-direction:column; max-height:92vh; }
  .bp-crop-head { display:flex; align-items:center; gap:8px; padding:13px 16px; border-bottom:1px solid var(--line); font-weight:800; }
  .bp-crop-head button { margin-left:auto; border:0; background:none; cursor:pointer; color:var(--muted); }
  .bp-crop-stage { padding:14px; background:#f4f0ff; overflow:auto; }
  .bp-crop-stage img { max-width:100%; display:block; }
  .bp-crop-foot { display:flex; gap:8px; justify-content:flex-end; padding:12px 16px; border-top:1px solid var(--line); }
</style>
@endpush

@section('content')
@php $types = $types; @endphp
<form id="bp-meta-form" onsubmit="return false;">
  <div class="bp-meta">
    <div class="field">
      <label>Page title</label>
      <input type="text" id="bp-title" value="{{ $page['title'] ?? '' }}" placeholder="Page title">
    </div>
    <div class="field">
      <label>SEO title</label>
      <input type="text" id="bp-page-title" value="{{ $page['page_title'] ?? '' }}" placeholder="Browser/SEO title">
    </div>
    <div class="bp-meta-actions">
      <a class="btn btn-ghost" href="{{ route('admin.pages.index') }}"><i data-lucide="arrow-left"></i> All pages</a>
      <button type="button" class="btn btn-primary" id="bp-save"><i data-lucide="save"></i> Save</button>
    </div>
    <div class="field" style="grid-column:1 / -1;margin-top:-4px;">
      <label>Meta description</label>
      <input type="text" id="bp-meta-desc" value="{{ $page['meta_description'] ?? '' }}" placeholder="One-line description for search engines">
    </div>
    <div style="grid-column:1 / -1;display:flex;flex-wrap:wrap;gap:18px;align-items:center;">
      <label class="bp-vis"><input type="checkbox" id="bp-visible" @checked($page['visible'] ?? false) style="width:16px;height:16px;"> Published (visible on the site)</label>
      <span class="bp-path">URL: <code>{{ $page['path'] ?? ('/briefs/'.$page['slug']) }}</code></span>
      <a class="bp-path" href="{{ $page['path'] ?? ('/briefs/'.$page['slug']) }}" target="_blank" style="color:var(--teal);font-weight:700;">Open live page ↗</a>
    </div>
  </div>
</form>

<div class="bp-layout">
  <div class="bp-builder">
    <div class="bp-add" id="bp-add">
      <button type="button" class="btn btn-primary bp-add-btn" id="bp-add-btn"><i data-lucide="plus"></i> Add block</button>
      <div class="bp-add-menu">
        @foreach($types as $slug => $def)
          <button type="button" class="bp-add-item" data-add-type="{{ $slug }}">
            <i data-lucide="{{ $def['icon'] ?? 'square' }}"></i>
            <span><b>{{ $def['label'] }}</b><small>{{ $def['desc'] ?? '' }}</small></span>
          </button>
        @endforeach
      </div>
    </div>

    <div class="bp-blocks" id="bp-blocks">
      @foreach($page['sections'] ?? [] as $s)
        @continue(! isset($types[$s['type']]))
        @include('admin.brief._block', ['block' => $s, 'def' => $types[$s['type']]])
      @endforeach
    </div>
  </div>

  <div class="bp-preview">
    <div class="bp-preview-bar">
      <span class="bp-pv-title">Live preview</span>
      <div class="bp-dev" id="bp-dev">
        <button type="button" data-dev="web" class="is-active" title="Desktop"><i data-lucide="monitor"></i></button>
        <button type="button" data-dev="phone" title="Mobile"><i data-lucide="smartphone"></i></button>
      </div>
      <button type="button" class="bp-icbtn" id="bp-refresh" title="Refresh preview"><i data-lucide="refresh-cw"></i></button>
    </div>
    <div class="bp-preview-stage" id="bp-stage">
      <iframe id="bp-frame" title="Page preview"></iframe>
    </div>
  </div>
</div>

<div class="bp-crop" id="bp-crop">
  <div class="bp-crop-card">
    <div class="bp-crop-head"><i data-lucide="crop"></i> Crop image <button type="button" id="bp-crop-x"><i data-lucide="x"></i></button></div>
    <div class="bp-crop-stage"><img id="bp-crop-img" alt=""></div>
    <div class="bp-crop-foot">
      <button type="button" class="btn btn-ghost" id="bp-crop-cancel">Cancel</button>
      <button type="button" class="btn btn-primary" id="bp-crop-apply"><i data-lucide="check"></i> Use image</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script>
(function () {
  const CSRF = document.querySelector('meta[name=csrf-token]').content;
  const SAVE = @json(route('admin.pages.save', $page['slug']));
  const BLOCK = @json(route('admin.pages.block'));
  const PREVIEW = @json(route('admin.pages.preview'));
  const UPLOAD = @json(route('admin.pages.upload'));
  const blocksEl = document.getElementById('bp-blocks');
  const frame = document.getElementById('bp-frame');
  const refresh = () => { if (window.lucide) lucide.createIcons(); };

  /* ── Serialize DOM → sections ── */
  const ownerScope = (el) => el.closest('.bp-rep-item, .bp-fields');
  function serializeScope(scope) {
    const data = {};
    scope.querySelectorAll('[data-field]').forEach((el) => {
      if (ownerScope(el) !== scope) return;
      const k = el.getAttribute('data-field');
      data[k] = el.type === 'checkbox' ? el.checked : (el.value || '');
    });
    scope.querySelectorAll('.bp-rep').forEach((rep) => {
      if (ownerScope(rep) !== scope) return;
      const wrap = rep.querySelector(':scope > .bp-rep-items');
      const items = wrap ? [...wrap.children].filter((c) => c.classList.contains('bp-rep-item')) : [];
      data[rep.getAttribute('data-rep')] = items.map(serializeScope);
    });
    return data;
  }
  function buildPayload() {
    return [...blocksEl.children].filter((b) => b.classList.contains('bp-block')).map((b) => ({
      id: b.dataset.id,
      type: b.dataset.type,
      visible: b.dataset.visible !== '0',
      data: serializeScope(b.querySelector('.bp-fields')),
    }));
  }

  /* ── Live preview (debounced) ── */
  let pvTimer = null, dirty = false;
  function renderPreview() {
    fetch(PREVIEW, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' }, body: JSON.stringify({ sections: buildPayload() }) })
      .then((r) => r.text()).then((html) => { frame.srcdoc = html; }).catch(() => {});
  }
  function schedule() { dirty = true; clearTimeout(pvTimer); pvTimer = setTimeout(renderPreview, 550); }
  blocksEl.addEventListener('input', schedule);
  blocksEl.addEventListener('change', schedule);
  document.getElementById('bp-refresh').addEventListener('click', renderPreview);

  /* ── Device toggle ── */
  document.getElementById('bp-dev').addEventListener('click', (e) => {
    const b = e.target.closest('[data-dev]'); if (!b) return;
    document.querySelectorAll('#bp-dev button').forEach((x) => x.classList.toggle('is-active', x === b));
    document.getElementById('bp-stage').classList.toggle('is-phone', b.dataset.dev === 'phone');
  });

  /* ── Add block ── */
  const addWrap = document.getElementById('bp-add');
  document.getElementById('bp-add-btn').addEventListener('click', (e) => { e.stopPropagation(); addWrap.classList.toggle('open'); });
  document.addEventListener('click', (e) => { if (!addWrap.contains(e.target)) addWrap.classList.remove('open'); });
  addWrap.querySelector('.bp-add-menu').addEventListener('click', (e) => {
    const item = e.target.closest('[data-add-type]'); if (!item) return;
    addWrap.classList.remove('open');
    fetch(BLOCK + '?type=' + encodeURIComponent(item.dataset.addType), { headers: { Accept: 'text/html' } })
      .then((r) => r.text()).then((html) => {
        const tmp = document.createElement('div'); tmp.innerHTML = html.trim();
        const block = tmp.firstElementChild;
        blocksEl.appendChild(block); refresh(); schedule();
        block.scrollIntoView({ behavior: 'smooth', block: 'center' });
        window.cmsToast && window.cmsToast('Block added — fill it in, then Save');
      });
  });

  /* ── Block bar actions ── */
  blocksEl.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-act]'); if (!btn) return;
    const block = btn.closest('.bp-block');
    const act = btn.dataset.act;
    if (act === 'vis') {
      const vis = block.dataset.visible === '1' ? '0' : '1';
      block.dataset.visible = vis;
      block.classList.toggle('is-hidden', vis === '0');
      btn.innerHTML = '<i data-lucide="' + (vis === '1' ? 'eye' : 'eye-off') + '"></i>'; refresh(); schedule();
    } else if (act === 'collapse') {
      block.classList.toggle('is-collapsed');
      btn.innerHTML = '<i data-lucide="' + (block.classList.contains('is-collapsed') ? 'chevron-down' : 'chevron-up') + '"></i>'; refresh();
    } else if (act === 'del') {
      if (confirm('Delete this block?')) { block.remove(); schedule(); }
    }
  });

  /* ── Repeater add / remove ── */
  blocksEl.addEventListener('click', (e) => {
    const add = e.target.closest('.bp-rep-add');
    if (add) {
      const rep = add.closest('.bp-rep');
      const tpl = rep.querySelector(':scope > .bp-rep-tpl');
      const items = rep.querySelector(':scope > .bp-rep-items');
      const node = tpl.content.firstElementChild.cloneNode(true);
      items.appendChild(node); refresh(); schedule();
      return;
    }
    const del = e.target.closest('.bp-rep-del');
    if (del) { del.closest('.bp-rep-item').remove(); schedule(); }
  });

  /* ── Colour / icon / image controls (delegated) ── */
  blocksEl.addEventListener('input', (e) => {
    if (e.target.classList.contains('bp-color-pick')) {
      e.target.closest('.bp-color').querySelector('.bp-color-hex').value = e.target.value;
    } else if (e.target.classList.contains('bp-icon-name')) {
      const prev = e.target.closest('.bp-icon').querySelector('.bp-icon-prev');
      const v = e.target.value.trim();
      prev.innerHTML = v ? '<i data-lucide="' + v.replace(/[^a-z0-9-]/g, '') + '"></i>' : ''; refresh();
    } else if (e.target.classList.contains('bp-image-url')) {
      const prev = e.target.closest('.bp-image').querySelector('.bp-image-prev');
      prev.innerHTML = e.target.value ? '<img src="' + e.target.value.replace(/"/g, '&quot;') + '" alt="">' : '<i data-lucide="image"></i>'; refresh();
    }
  });
  blocksEl.addEventListener('click', (e) => {
    if (e.target.closest('.bp-color-clear')) {
      e.target.closest('.bp-color').querySelector('.bp-color-hex').value = ''; schedule();
    } else if (e.target.closest('.bp-image-clear')) {
      const img = e.target.closest('.bp-image');
      img.querySelector('.bp-image-url').value = '';
      img.querySelector('.bp-image-prev').innerHTML = '<i data-lucide="image"></i>'; refresh(); schedule();
    }
  });

  /* ── Image upload + crop ── */
  let cropper = null, cropTargetInput = null;
  const cropEl = document.getElementById('bp-crop');
  const cropImg = document.getElementById('bp-crop-img');
  function openCrop(src, input) {
    cropTargetInput = input;
    cropEl.classList.add('open');
    if (cropper) { cropper.destroy(); cropper = null; }
    cropImg.src = src;
    cropper = new Cropper(cropImg, { viewMode: 1, autoCropArea: 1, background: true, movable: true, zoomable: true });
  }
  function closeCrop() { if (cropper) { cropper.destroy(); cropper = null; } cropEl.classList.remove('open'); cropTargetInput = null; }
  document.getElementById('bp-crop-x').addEventListener('click', closeCrop);
  document.getElementById('bp-crop-cancel').addEventListener('click', closeCrop);
  blocksEl.addEventListener('change', (e) => {
    if (!e.target.classList.contains('bp-image-file')) return;
    const f = e.target.files[0]; if (!f) return;
    const input = e.target.closest('.bp-image').querySelector('.bp-image-url');
    openCrop(URL.createObjectURL(f), input);
    e.target.value = '';
  });
  document.getElementById('bp-crop-apply').addEventListener('click', () => {
    if (!cropper || !cropTargetInput) return;
    const canvas = cropper.getCroppedCanvas({ maxWidth: 2200, maxHeight: 1600, imageSmoothingQuality: 'high' });
    if (!canvas) { closeCrop(); return; }
    const input = cropTargetInput;
    canvas.toBlob((blob) => {
      const fd = new FormData(); fd.append('file', blob, 'image.jpg');
      window.cmsToast && window.cmsToast('Uploading image…');
      fetch(UPLOAD, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF }, body: fd })
        .then((r) => r.json()).then((d) => {
          if (d.url) {
            input.value = d.url;
            input.closest('.bp-image').querySelector('.bp-image-prev').innerHTML = '<img src="' + d.url + '" alt="">';
            refresh(); schedule();
          }
        }).catch(() => window.cmsToast && window.cmsToast('Upload failed', 'error'));
      closeCrop();
    }, 'image/jpeg', 0.9);
  });

  /* ── Drag reorder (blocks + repeater items) ── */
  let drag = null;
  document.addEventListener('pointerdown', (e) => {
    const bg = e.target.closest('.bp-grip');
    const rg = e.target.closest('.bp-rep-grip');
    if (bg) { const it = bg.closest('.bp-block'); drag = { it, container: blocksEl, sel: '.bp-block' }; }
    else if (rg) { const it = rg.closest('.bp-rep-item'); drag = { it, container: it.parentElement, sel: '.bp-rep-item' }; }
    else return;
    drag.it.classList.add('bp-dragging');
  });
  document.addEventListener('pointermove', (e) => {
    if (!drag) return;
    drag.it.style.pointerEvents = 'none';
    const under = document.elementFromPoint(e.clientX, e.clientY);
    drag.it.style.pointerEvents = '';
    if (!under) return;
    const t = under.closest(drag.sel);
    if (!t || t === drag.it || t.parentElement !== drag.container) return;
    const after = drag.it.compareDocumentPosition(t) & Node.DOCUMENT_POSITION_FOLLOWING;
    after ? t.after(drag.it) : t.before(drag.it);
  });
  document.addEventListener('pointerup', () => { if (drag) { drag.it.classList.remove('bp-dragging'); drag = null; schedule(); } });

  /* ── Save ── */
  document.getElementById('bp-save').addEventListener('click', () => {
    const payload = {
      title: document.getElementById('bp-title').value,
      page_title: document.getElementById('bp-page-title').value,
      meta_description: document.getElementById('bp-meta-desc').value,
      visible: document.getElementById('bp-visible').checked ? 1 : 0,
      sections: buildPayload(),
    };
    fetch(SAVE, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
      .then((r) => r.json()).then((d) => { dirty = false; window.cmsToast && window.cmsToast(d.message || 'Saved'); })
      .catch(() => window.cmsToast && window.cmsToast('Save failed', 'error'));
  });
  window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

  renderPreview();
})();
</script>
@endpush
