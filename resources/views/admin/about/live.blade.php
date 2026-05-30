<!doctype html>
{{-- data-color-theme must match the live site (layouts/app.blade.php) so the
     preview renders in the navy-orange "signature" theme, not the green default. --}}
<html lang="en" data-color-theme="signature">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Live Edit · About · {{ config('site.name') }}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  {{-- The real site stylesheet, so the page looks exactly like /about --}}
  <link rel="stylesheet" href="{{ asset('styles.css') }}">
  <style>
    :root { --le-accent: #0d7a78; --le-accent-2: #ef6c1a; --le-bar: #0e1f3d; }
    .le-body { margin: 0; padding-top: 58px; }

    /* ── Top bar ── */
    .le-top { position: fixed; inset: 0 0 auto 0; height: 58px; z-index: 9000; display: flex; align-items: center;
      gap: 14px; padding: 0 16px; background: var(--le-bar); color: #fff;
      font-family: "Manrope", system-ui, sans-serif; box-shadow: 0 4px 18px rgba(8,20,40,.28); }
    .le-top a, .le-top button { font-family: inherit; }
    .le-top .le-home { display: inline-flex; align-items: center; gap: 8px; color: #cdd9ef; text-decoration: none; font-weight: 700; font-size: .9rem; }
    .le-top .le-home:hover { color: #fff; }
    .le-top .le-title { font-weight: 800; letter-spacing: -.01em; display: flex; align-items: center; gap: 9px; }
    .le-top .le-badge { font-size: .62rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase;
      background: var(--le-accent-2); color: #fff; padding: 3px 8px; border-radius: 999px; }
    .le-top .le-hint { color: #9fb0d0; font-size: .82rem; font-weight: 600; }
    .le-top .le-sp { flex: 1; }
    .le-top .le-status { font-size: .8rem; font-weight: 700; color: #9fb0d0; min-width: 70px; text-align: right; }
    .le-tbtn { display: inline-flex; align-items: center; gap: 7px; border: 1px solid rgba(255,255,255,.18);
      background: rgba(255,255,255,.06); color: #fff; border-radius: 10px; padding: 9px 14px; font-weight: 700; font-size: .88rem; cursor: pointer; }
    .le-tbtn:hover { background: rgba(255,255,255,.14); }
    .le-tbtn.primary { background: var(--le-accent-2); border-color: var(--le-accent-2); }
    .le-tbtn.primary:hover { background: #d65f12; }
    .le-tbtn i { width: 16px; height: 16px; }

    /* Add-section dropdown */
    .le-add-wrap { position: relative; }
    .le-add-menu { position: absolute; right: 0; top: 46px; min-width: 290px; background: #fff; color: #14253e;
      border-radius: 12px; box-shadow: 0 18px 44px rgba(8,20,40,.35); padding: 7px; display: none; }
    .le-add-wrap.open .le-add-menu { display: block; }
    .le-add-item { display: flex; align-items: flex-start; gap: 11px; padding: 10px 11px; border-radius: 9px; cursor: pointer; }
    .le-add-item:hover { background: #f3f6f8; }
    .le-add-item i { width: 18px; height: 18px; color: var(--le-accent-2); margin-top: 2px; flex-shrink: 0; }
    .le-add-item b { display: block; font-size: .9rem; font-weight: 800; }
    .le-add-item span { display: block; font-size: .76rem; color: #6a7686; line-height: 1.35; }

    /* ── Section wrapper ── */
    .le-sec { position: relative; }
    .le-sec.le-hidden { opacity: .5; }
    .le-sec.le-hidden::after { content: ""; position: absolute; inset: 0; background: repeating-linear-gradient(45deg, rgba(192,57,43,.04) 0 12px, transparent 12px 24px); pointer-events: none; }
    .le-hidden-flag { position: absolute; top: 10px; left: 50%; transform: translateX(-50%); z-index: 40;
      background: #c0392b; color: #fff; font: 800 .68rem/1 "Manrope",sans-serif; letter-spacing: .08em; text-transform: uppercase;
      padding: 5px 11px; border-radius: 999px; }
    .le-sec.le-dragging { opacity: .35; }
    .le-sec.le-drop-before { box-shadow: inset 0 4px 0 var(--le-accent); }
    .le-sec.le-drop-after  { box-shadow: inset 0 -4px 0 var(--le-accent); }

    .le-sec-bar { position: absolute; top: 10px; right: 10px; z-index: 60; display: flex; align-items: center; gap: 6px;
      font-family: "Manrope", sans-serif; opacity: 0; transform: translateY(-4px); transition: opacity .15s, transform .15s; }
    .le-sec:hover > .le-sec-bar { opacity: 1; transform: none; }
    .le-drag { display: inline-flex; align-items: center; gap: 6px; cursor: grab; background: var(--le-bar); color: #fff;
      border-radius: 9px; padding: 6px 11px; font-size: .76rem; font-weight: 800; box-shadow: 0 6px 16px rgba(8,20,40,.3); }
    .le-drag:active { cursor: grabbing; }
    .le-drag i { width: 14px; height: 14px; }
    .le-bbtn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px;
      border: 0; border-radius: 9px; background: var(--le-bar); color: #fff; cursor: pointer; box-shadow: 0 6px 16px rgba(8,20,40,.3); }
    .le-bbtn:hover { background: #1b366b; }
    .le-bbtn.le-del:hover { background: #c0392b; }
    .le-bbtn i { width: 16px; height: 16px; }

    /* ── Inline editables ── */
    .le-sec [data-ed] { transition: outline-color .12s, background .12s; border-radius: 3px; }
    .le-sec [data-ed]:hover { outline: 1px dashed rgba(13,122,120,.55); outline-offset: 2px; cursor: text; }
    .le-sec [data-ed]:focus { outline: 2px solid var(--le-accent); outline-offset: 2px; background: rgba(13,122,120,.07); }
    .le-sec [data-ed]:empty { min-width: 46px; min-height: 1em; display: inline-block; outline: 1px dashed rgba(13,122,120,.55); }
    .le-sec [data-ed]:empty::before { content: "edit…"; color: rgba(13,122,120,.6); font-size: .8em; }

    .le-ic { display: contents; }
    .le-sec [data-ed-icon] { cursor: pointer; }
    .le-sec [data-ed-icon] svg { transition: outline-color .12s; }
    .le-sec [data-ed-icon]:hover svg { outline: 2px solid var(--le-accent); outline-offset: 3px; border-radius: 5px; }

    .le-sec [data-ed-img] { cursor: pointer; position: relative; }
    .le-sec img[data-ed-img] { outline-offset: -2px; }
    .le-sec [data-ed-img]:hover { outline: 2px solid var(--le-accent); }
    .le-sec [data-ed-img]::after { content: "✎ Change"; position: absolute; top: 8px; left: 8px; z-index: 30;
      background: var(--le-bar); color: #fff; font: 700 .72rem/1 "Manrope",sans-serif; padding: 5px 9px; border-radius: 8px;
      opacity: 0; transition: opacity .15s; pointer-events: none; }
    .le-sec [data-ed-img]:hover::after { opacity: 1; }

    /* ── Repeater item tools ── */
    .le-sec [data-ed-item] { position: relative; }
    .le-item-tools { position: absolute; top: 4px; right: 4px; z-index: 55; display: flex; gap: 4px;
      opacity: 0; transition: opacity .12s; font-family: "Manrope", sans-serif; }
    [data-ed-item]:hover > .le-item-tools { opacity: 1; }
    .le-item-tools button { width: 26px; height: 26px; border: 0; border-radius: 7px; cursor: pointer;
      background: rgba(14,31,61,.92); color: #fff; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(8,20,40,.3); }
    .le-item-tools button:hover { background: var(--le-accent); }
    .le-item-tools button.le-item-del:hover { background: #c0392b; }
    .le-item-tools svg { width: 14px; height: 14px; }

    /* ── Popover (image / icon) ── */
    .le-pop { position: fixed; z-index: 9500; width: 320px; max-width: calc(100vw - 24px); background: #fff; color: #14253e;
      border-radius: 13px; box-shadow: 0 24px 60px rgba(8,20,40,.4); padding: 15px; font-family: "Manrope", sans-serif; }
    .le-pop h4 { margin: 0 0 11px; font-size: .95rem; font-weight: 800; display: flex; align-items: center; gap: 7px; }
    .le-pop label { display: block; font-size: .76rem; font-weight: 800; color: #6a7686; margin: 0 0 5px; text-transform: uppercase; letter-spacing: .05em; }
    .le-pop input[type=text] { width: 100%; box-sizing: border-box; padding: 10px 11px; border: 1px solid #e5e8ee;
      border-radius: 9px; font-family: inherit; font-size: .9rem; margin-bottom: 11px; }
    .le-pop input:focus { outline: none; border-color: var(--le-accent); box-shadow: 0 0 0 3px rgba(13,122,120,.15); }
    .le-pop .le-pop-row { display: flex; gap: 8px; }
    .le-pop .le-pop-row .le-tbtn { color: #14253e; }
    .le-pp-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; flex: 1; border: 1px solid #e5e8ee;
      background: #fff; border-radius: 9px; padding: 9px; font-weight: 700; font-size: .85rem; cursor: pointer; color: #14253e; }
    .le-pp-btn:hover { border-color: var(--le-accent); color: var(--le-accent); }
    .le-pp-btn.primary { background: var(--le-accent); border-color: var(--le-accent); color: #fff; }
    .le-pp-btn.primary:hover { background: #0a605e; color: #fff; }
    .le-pp-btn svg { width: 15px; height: 15px; }
    .le-pop-prev { display: flex; align-items: center; justify-content: center; height: 48px; border: 1px dashed #e5e8ee;
      border-radius: 9px; margin-bottom: 11px; color: var(--le-accent); }
    .le-pop-prev svg { width: 26px; height: 26px; }
    .le-pop-prev img { max-height: 46px; border-radius: 7px; }
    .le-uploading { font-size: .78rem; color: var(--le-accent); font-weight: 700; margin-bottom: 8px; }

    /* ── Toasts ── */
    .le-toasts { position: fixed; bottom: 18px; left: 50%; transform: translateX(-50%); z-index: 9600; display: flex; flex-direction: column; gap: 9px; align-items: center; }
    .le-toast { background: #14253e; color: #fff; font: 700 .88rem "Manrope",sans-serif; padding: 11px 18px; border-radius: 11px;
      box-shadow: 0 14px 34px rgba(8,20,40,.32); opacity: 0; transform: translateY(10px); transition: opacity .25s, transform .25s; }
    .le-toast.show { opacity: 1; transform: none; }
    .le-toast.err { background: #c0392b; }

    @media (max-width: 720px) { .le-top .le-hint, .le-drag-label { display: none; } }
  </style>
</head>
<body class="le-body">

  <header class="le-top">
    <a class="le-home" href="{{ route('admin.about.index') }}" title="Back to dashboard"><i data-lucide="arrow-left"></i> Dashboard</a>
    <span class="le-title">About <span class="le-badge">Live Edit</span></span>
    <span class="le-hint">Click any text, image or icon to edit. Hover a section for its controls.</span>
    <span class="le-sp"></span>
    <span class="le-status" id="le-status"></span>
    <div class="le-add-wrap" id="le-add-wrap">
      <button class="le-tbtn" type="button" id="le-add-btn"><i data-lucide="plus"></i> Add section</button>
      <div class="le-add-menu">
        @foreach($types as $key => $t)
          <div class="le-add-item" data-add-type="{{ $key }}">
            <i data-lucide="{{ $t['icon'] }}"></i>
            <span><b>{{ $t['label'] }}</b><span>{{ $t['desc'] }}</span></span>
          </div>
        @endforeach
      </div>
    </div>
    <button class="le-tbtn primary" type="button" id="le-save"><i data-lucide="save"></i> Save</button>
  </header>

  <main class="va-about-page" id="live-root">
    @foreach($sections as $section)
      @include('admin.about._live_section', ['section' => $section, 'types' => $types, 'isNew' => false])
    @endforeach
  </main>

  <div class="le-toasts" id="le-toasts"></div>
  <div class="le-pop" id="le-pop" hidden></div>

  <datalist id="le-icons">
    @foreach(['sparkles','sparkle','star','award','trophy','target','users','users-round','user-check','user',
              'graduation-cap','book-open','briefcase','globe-2','map-pin','building-2','compass','telescope',
              'badge-check','shield-check','check-circle-2','heart-handshake','hand-coins','life-buoy','lock',
              'route','layers','plane-takeoff','arrow-up-right','arrow-right','phone','mail','linkedin',
              'lightbulb','rocket','gem','flag','calendar','clock','bar-chart-3','trending-up','message-circle'] as $ic)
      <option value="{{ $ic }}">
    @endforeach
  </datalist>

  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <script>
  (function () {
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const SAVE_URL = @json(route('admin.about.live.save'));
    const SECTION_URL = @json(route('admin.about.live.section'));
    const UPLOAD_URL = @json(route('admin.about.upload'));
    const root = document.getElementById('live-root');
    const statusEl = document.getElementById('le-status');
    let dirty = false;
    const refreshIcons = () => { if (window.lucide) lucide.createIcons(); };
    const markDirty = () => { dirty = true; statusEl.textContent = '● Unsaved'; statusEl.style.color = '#ffd9a8'; };

    /* ── Toasts ── */
    const toasts = document.getElementById('le-toasts');
    function toast(msg, err) {
      const t = document.createElement('div');
      t.className = 'le-toast' + (err ? ' err' : '');
      t.textContent = msg;
      toasts.appendChild(t);
      requestAnimationFrame(() => t.classList.add('show'));
      setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 2600);
    }

    /* ════════ Decoration: contenteditable + per-item tools ════════ */
    function decorate(scope) {
      scope.querySelectorAll('[data-ed]').forEach(el => el.setAttribute('contenteditable', 'true'));
      scope.querySelectorAll('img[data-ed-img]').forEach(img => img.setAttribute('draggable', 'false'));
      scope.querySelectorAll('[data-ed-item]').forEach(item => {
        if (item.dataset.leDeco === '1') return;
        item.dataset.leDeco = '1';
        const tools = document.createElement('span');
        tools.className = 'le-item-tools';
        tools.setAttribute('contenteditable', 'false');
        tools.innerHTML = '<button type="button" class="le-item-dup" data-le-tool="dup" title="Duplicate"><i data-lucide="copy"></i></button>'
                        + '<button type="button" class="le-item-del" data-le-tool="del" title="Remove"><i data-lucide="x"></i></button>';
        item.appendChild(tools);
      });
    }
    // Mark decorated scopes as such (for the whole root initially)
    decorate(root);
    refreshIcons();

    /* ════════ Serialization (DOM → schema) ════════ */
    function readExtra(scope) {
      const s = scope.querySelector(':scope > script.le-extra');
      if (!s) return {};
      try { return JSON.parse(s.textContent || '{}') || {}; } catch (e) { return {}; }
    }
    function ownerScope(el) { return el.closest('[data-ed-item]') || el.closest('[data-le-sec]'); }
    function readVal(el) {
      if (el.hasAttribute('data-ed-img')) return el.getAttribute('data-ed-imgval') || '';
      if (el.hasAttribute('data-ed-icon')) return el.getAttribute('data-ed-iconname') || '';
      return (el.textContent || '').replace(/ /g, ' ').trim();
    }
    function serializeScope(scope) {
      const data = Object.assign({}, readExtra(scope));
      scope.querySelectorAll('[data-ed],[data-ed-img],[data-ed-icon]').forEach(el => {
        if (ownerScope(el) !== scope) return;
        const key = el.getAttribute('data-ed') || el.getAttribute('data-ed-img') || el.getAttribute('data-ed-icon');
        data[key] = readVal(el);
      });
      scope.querySelectorAll('[data-ed-rep]').forEach(rep => {
        if ((rep.closest('[data-ed-item]') || rep.closest('[data-le-sec]')) !== scope) return;
        const key = rep.getAttribute('data-ed-rep');
        const items = [...rep.querySelectorAll('[data-ed-item]')].filter(it => it.closest('[data-ed-rep]') === rep);
        data[key] = items.map(serializeScope);
      });
      return data;
    }
    function buildPayload() {
      return [...root.querySelectorAll('[data-le-sec]')].map(sec => ({
        id: sec.getAttribute('data-ed-id'),
        type: sec.getAttribute('data-ed-type'),
        visible: sec.getAttribute('data-ed-visible') === '1',
        data: serializeScope(sec),
      }));
    }

    /* ════════ Image / icon setters ════════ */
    function setImage(el, url) {
      el.setAttribute('data-ed-imgval', url);
      if (el.hasAttribute('data-ed-bg')) el.style.backgroundImage = url ? "url('" + url.replace(/'/g, "\\'") + "')" : 'none';
      else el.setAttribute('src', url);
      markDirty();
    }
    function setIcon(el, name) {
      name = (name || '').toLowerCase().replace(/[^a-z0-9-]/g, '');
      el.setAttribute('data-ed-iconname', name);
      el.innerHTML = '<i data-lucide="' + (name || 'square') + '"></i>';
      refreshIcons();
      markDirty();
    }

    /* ════════ Popover (shared by image + icon) ════════ */
    const pop = document.getElementById('le-pop');
    let popTarget = null;
    function positionPop(anchorRect) {
      pop.hidden = false;
      const w = pop.offsetWidth, h = pop.offsetHeight;
      let left = anchorRect.left, top = anchorRect.bottom + 8;
      if (left + w > innerWidth - 12) left = innerWidth - w - 12;
      if (left < 12) left = 12;
      if (top + h > innerHeight - 12) top = Math.max(64, anchorRect.top - h - 8);
      pop.style.left = left + 'px';
      pop.style.top = top + 'px';
    }
    function closePop() { pop.hidden = true; popTarget = null; pop.innerHTML = ''; }

    function openImagePopover(el) {
      popTarget = el;
      const cur = el.getAttribute('data-ed-imgval') || '';
      pop.innerHTML =
        '<h4><i data-lucide="image"></i> Image</h4>' +
        '<div class="le-pop-prev">' + (cur ? '<img src="' + cur.replace(/"/g, '&quot;') + '" alt="">' : '<i data-lucide="image"></i>') + '</div>' +
        '<label>Image URL</label>' +
        '<input type="text" id="le-img-url" value="' + cur.replace(/"/g, '&quot;') + '" placeholder="https://… or upload">' +
        '<div class="le-uploading" id="le-img-up" hidden>Uploading…</div>' +
        '<div class="le-pop-row">' +
          '<label class="le-pp-btn"><i data-lucide="upload"></i> Upload<input type="file" accept="image/*" id="le-img-file" hidden></label>' +
          '<button type="button" class="le-pp-btn primary" id="le-img-apply"><i data-lucide="check"></i> Apply</button>' +
        '</div>';
      refreshIcons();
      positionPop(el.getBoundingClientRect());
      const urlInput = pop.querySelector('#le-img-url');
      urlInput.focus();
      pop.querySelector('#le-img-apply').onclick = () => { setImage(popTarget, urlInput.value.trim()); closePop(); };
      pop.querySelector('#le-img-file').onchange = (e) => {
        const f = e.target.files[0]; if (!f) return;
        const up = pop.querySelector('#le-img-up'); up.hidden = false;
        const fd = new FormData(); fd.append('file', f);
        fetch(UPLOAD_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: fd })
          .then(r => r.ok ? r.json() : Promise.reject()).then(d => { up.hidden = true; if (popTarget) setImage(popTarget, d.url); closePop(); })
          .catch(() => { up.hidden = true; toast('Upload failed (JPG/PNG/WebP under 5 MB).', true); });
      };
    }

    function openIconPopover(el) {
      popTarget = el;
      const cur = el.getAttribute('data-ed-iconname') || '';
      pop.innerHTML =
        '<h4><i data-lucide="shapes"></i> Icon</h4>' +
        '<div class="le-pop-prev" id="le-ic-prev">' + (cur ? '<i data-lucide="' + cur + '"></i>' : '<i data-lucide="square"></i>') + '</div>' +
        '<label>Lucide icon name</label>' +
        '<input type="text" id="le-ic-name" list="le-icons" value="' + cur.replace(/"/g, '&quot;') + '" placeholder="e.g. users, award" spellcheck="false" autocomplete="off">' +
        '<div class="le-pop-row"><button type="button" class="le-pp-btn primary" id="le-ic-apply"><i data-lucide="check"></i> Apply</button></div>';
      refreshIcons();
      positionPop(el.getBoundingClientRect());
      const nameInput = pop.querySelector('#le-ic-name');
      const prev = pop.querySelector('#le-ic-prev');
      nameInput.focus();
      nameInput.addEventListener('input', () => {
        const n = nameInput.value.toLowerCase().replace(/[^a-z0-9-]/g, '') || 'square';
        prev.innerHTML = '<i data-lucide="' + n + '"></i>'; refreshIcons();
      });
      const apply = () => { setIcon(popTarget, nameInput.value); closePop(); };
      pop.querySelector('#le-ic-apply').onclick = apply;
      nameInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); apply(); } });
    }

    document.addEventListener('mousedown', (e) => {
      if (!pop.hidden && !pop.contains(e.target) && !e.target.closest('[data-ed-img],[data-ed-icon]')) closePop();
    });

    /* ════════ Click handling inside the page ════════ */
    root.addEventListener('click', (e) => {
      // Item tools (duplicate / delete)
      const tool = e.target.closest('[data-le-tool]');
      if (tool) {
        e.preventDefault(); e.stopPropagation();
        const item = tool.closest('[data-ed-item]');
        if (tool.dataset.leTool === 'del') {
          if (confirm('Remove this item?')) { item.remove(); markDirty(); toast('Item removed'); }
        } else {
          const clone = item.cloneNode(true);
          clone.querySelectorAll('.le-item-tools').forEach(t => t.remove());
          clone.removeAttribute('data-le-deco');
          clone.querySelectorAll('[data-le-deco]').forEach(n => n.removeAttribute('data-le-deco'));
          item.after(clone);
          decorate(clone.parentElement); // (re)decorate siblings incl. the clone
          refreshIcons(); markDirty(); toast('Item duplicated — edit it');
        }
        return;
      }
      // Section bar buttons
      const act = e.target.closest('.le-sec-bar [data-act]');
      if (act) {
        const sec = act.closest('[data-le-sec]');
        if (act.dataset.act === 'vis') {
          const vis = sec.getAttribute('data-ed-visible') === '1';
          sec.setAttribute('data-ed-visible', vis ? '0' : '1');
          sec.classList.toggle('le-hidden', vis);
          let flag = sec.querySelector(':scope > .le-hidden-flag');
          if (vis && !flag) { flag = document.createElement('div'); flag.className = 'le-hidden-flag'; flag.setAttribute('contenteditable','false'); flag.textContent = 'Hidden on the live site'; sec.appendChild(flag); }
          if (!vis && flag) flag.remove();
          act.innerHTML = '<i data-lucide="' + (vis ? 'eye-off' : 'eye') + '"></i>'; refreshIcons();
          markDirty();
        } else if (act.dataset.act === 'del-sec') {
          if (confirm('Delete this whole section?')) { sec.remove(); markDirty(); toast('Section deleted'); }
        }
        return;
      }
      // New (unsaved) sections: the Fields editor needs a saved id first
      const fields = e.target.closest('.le-fields');
      if (fields && fields.closest('.le-new')) { e.preventDefault(); toast('Save first, then open the field editor.', true); return; }
      // Image / icon editors
      const img = e.target.closest('[data-ed-img]');
      if (img) { e.preventDefault(); openImagePopover(img); return; }
      const icon = e.target.closest('[data-ed-icon]');
      if (icon) { e.preventDefault(); openIconPopover(icon); return; }
      // Never navigate away from in-page links while editing
      const a = e.target.closest('a');
      if (a && !a.closest('.le-sec-bar')) e.preventDefault();
    });

    // Track text edits
    root.addEventListener('input', (e) => { if (e.target.isContentEditable) markDirty(); });

    /* ════════ Section drag-to-reorder ════════ */
    let dragSec = null;
    function bindDrag(handle) {
      handle.addEventListener('dragstart', (e) => {
        dragSec = handle.closest('[data-le-sec]');
        dragSec.classList.add('le-dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', dragSec.getAttribute('data-ed-id'));
      });
      handle.addEventListener('dragend', () => {
        if (dragSec) dragSec.classList.remove('le-dragging');
        root.querySelectorAll('.le-drop-before,.le-drop-after').forEach(s => s.classList.remove('le-drop-before', 'le-drop-after'));
        dragSec = null;
      });
    }
    root.querySelectorAll('.le-drag').forEach(bindDrag);
    root.addEventListener('dragover', (e) => {
      if (!dragSec) return;
      e.preventDefault();
      const over = e.target.closest('[data-le-sec]');
      root.querySelectorAll('.le-drop-before,.le-drop-after').forEach(s => { if (s !== over) s.classList.remove('le-drop-before', 'le-drop-after'); });
      if (!over || over === dragSec) return;
      const r = over.getBoundingClientRect();
      const after = (e.clientY - r.top) > r.height / 2;
      over.classList.toggle('le-drop-after', after);
      over.classList.toggle('le-drop-before', !after);
      root.insertBefore(dragSec, after ? over.nextSibling : over);
    });
    root.addEventListener('drop', (e) => {
      e.preventDefault();
      root.querySelectorAll('.le-drop-before,.le-drop-after').forEach(s => s.classList.remove('le-drop-before', 'le-drop-after'));
      markDirty();
    });

    /* ════════ Add section ════════ */
    const addWrap = document.getElementById('le-add-wrap');
    document.getElementById('le-add-btn').addEventListener('click', (e) => { e.stopPropagation(); addWrap.classList.toggle('open'); });
    document.addEventListener('click', () => addWrap.classList.remove('open'));
    addWrap.querySelector('.le-add-menu').addEventListener('click', (e) => {
      const item = e.target.closest('[data-add-type]');
      if (!item) return;
      addWrap.classList.remove('open');
      fetch(SECTION_URL + '?type=' + encodeURIComponent(item.dataset.addType), { headers: { 'Accept': 'text/html' } })
        .then(r => r.ok ? r.text() : Promise.reject()).then(html => {
          const tmp = document.createElement('div');
          tmp.innerHTML = html.trim();
          const sec = tmp.firstElementChild;
          root.appendChild(sec);
          decorate(sec);
          sec.querySelectorAll('.le-drag').forEach(bindDrag);
          refreshIcons(); markDirty();
          sec.scrollIntoView({ behavior: 'smooth', block: 'center' });
          toast('Section added — edit it, then Save');
        }).catch(() => toast('Could not add section.', true));
    });

    /* ════════ Save ════════ */
    document.getElementById('le-save').addEventListener('click', () => {
      closePop();
      const payload = { sections: buildPayload() };
      statusEl.textContent = 'Saving…'; statusEl.style.color = '#9fb0d0';
      fetch(SAVE_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(payload) })
        .then(r => r.ok ? r.json() : Promise.reject()).then(d => {
          dirty = false; statusEl.textContent = '✓ Saved'; statusEl.style.color = '#9ee6b8';
          toast(d.message || 'Saved'); setTimeout(() => { if (!dirty) statusEl.textContent = ''; }, 2500);
        }).catch(() => { statusEl.textContent = '● Unsaved'; statusEl.style.color = '#ffd9a8'; toast('Save failed. Try again.', true); });
    });

    window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  })();
  </script>
</body>
</html>
