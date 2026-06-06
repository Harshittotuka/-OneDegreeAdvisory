<!doctype html>
{{-- data-color-theme MUST match the live site (layouts/app.blade.php → "cream").
     The theme CSS variables (--coral, --theme-*, etc.) are only defined under
     html[data-color-theme="cream"]; any other value leaves them undefined and
     section text/colours vanish. --}}
<html lang="en" data-color-theme="cream">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Live Edit · About · {{ config('site.name') }}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  {{-- Same font set as layouts/app.blade.php so type renders exactly like /about --}}
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;450;500;600;700&family=Jost:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
  {{-- The real site stylesheets, so the page looks exactly like /about --}}
  <link rel="stylesheet" href="{{ asset('styles.css') }}">
  <link rel="stylesheet" href="{{ asset('stripe-nav.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
  <style>
    :root { --le-accent: #666cff; --le-accent-2: #666cff; --le-bar: #0e1f3d; }
    .le-body { margin: 0; padding-bottom: 70px; }

    /* ── Bottom toolbar (matches the home-hero editor) ── */
    .le-top { position: fixed; inset: auto 0 0 0; height: 60px; z-index: 9000; display: flex; align-items: center;
      gap: 14px; padding: 0 16px; background: var(--le-bar); color: #fff;
      font-family: "Manrope", system-ui, sans-serif; box-shadow: 0 -6px 22px rgba(8,20,40,.34); }
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
    .le-tbtn.primary:hover { background: #5256e0; }
    .le-tbtn i { width: 16px; height: 16px; }

    /* Add-section dropdown */
    .le-add-wrap { position: relative; }
    .le-add-menu { position: absolute; right: 0; bottom: 52px; min-width: 290px; max-height: 60vh; overflow-y: auto;
      background: #fff; color: #14253e; border-radius: 12px; box-shadow: 0 -10px 44px rgba(8,20,40,.35); padding: 7px; display: none; }
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

    /* Founder "hand" icon — click to set hover help text + redirect URL */
    .le-sec [data-ed-hand] { cursor: pointer; }
    .le-sec [data-ed-hand]:hover { outline: 2px solid var(--le-accent); outline-offset: 3px; }

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

    /* Options popover controls (select / checkbox / link / anchor / alt text) */
    .le-pop select, .le-pop textarea { width: 100%; box-sizing: border-box; padding: 9px 11px; border: 1px solid #e5e8ee;
      border-radius: 9px; font-family: inherit; font-size: .9rem; margin-bottom: 11px; color: #14253e; background: #fff; }
    .le-pop textarea { resize: vertical; min-height: 64px; }
    .le-pop select:focus, .le-pop textarea:focus { outline: none; border-color: var(--le-accent); box-shadow: 0 0 0 3px rgba(13,122,120,.15); }
    .le-check { display: flex; align-items: center; gap: 9px; font-size: .9rem; font-weight: 700; color: #14253e;
      background: #f4f7f8; border: 1px solid #e5e8ee; border-radius: 9px; padding: 10px 12px; margin-bottom: 11px; cursor: pointer; }
    .le-check input { width: auto; margin: 0; }
    .le-note { margin: 0; color: #6a7686; font-size: .85rem; font-weight: 600; }
    .le-item-move { cursor: grab; }
    .le-item-move:active { cursor: grabbing; }
    .le-item-dragging { opacity: .5; }

    /* ── Crop modal (Cropper.js) — box locked to the card it edits ── */
    .le-crop { position: fixed; inset: 0; z-index: 9800; background: rgba(8,18,33,.82); display: none; align-items: center; justify-content: center; padding: 24px; font-family: "Manrope", sans-serif; }
    .le-crop.open { display: flex; }
    .le-crop-card { width: 760px; max-width: 96vw; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 40px 90px rgba(0,0,0,.5); }
    .le-crop-head { display: flex; align-items: center; gap: 9px; padding: 14px 18px; font-weight: 800; border-bottom: 1px solid #eef1f4; color: #14253e; }
    .le-crop-head .le-sp { flex: 1; }
    .le-crop-head button { border: 0; background: none; cursor: pointer; color: #6a7686; display: inline-flex; }
    .le-crop-head i { width: 18px; height: 18px; }
    .le-crop-stage { background: #11202f; max-height: 56vh; }
    .le-crop-stage img { max-width: 100%; display: block; }
    .le-crop-foot { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; padding: 13px 18px; border-top: 1px solid #eef1f4; }
    .le-ar-locked { display: inline-flex; align-items: center; gap: 7px; font: 700 .82rem "Manrope", sans-serif; color: #5256e0;
      background: #ebecff; border: 1px solid #cdd0ff; border-radius: 8px; padding: 7px 12px; }
    .le-ar-locked i { width: 14px; height: 14px; }
    .le-crop-foot .le-sp { flex: 1; }
    .le-zoom { display: inline-flex; gap: 5px; }
    .le-zoom button { width: 34px; height: 34px; border: 1px solid #e5e8ee; background: #fff; border-radius: 8px; cursor: pointer; color: #14253e; display: inline-flex; align-items: center; justify-content: center; }
    .le-zoom button:hover { border-color: var(--le-accent); color: var(--le-accent); }
    .le-zoom i { width: 16px; height: 16px; }
    .le-crop-foot .le-go { display: inline-flex; align-items: center; gap: 7px; border: 0; background: var(--le-accent); color: #fff; border-radius: 9px; padding: 10px 16px; font: 800 .88rem "Manrope", sans-serif; cursor: pointer; }
    .le-crop-foot .le-go:hover { background: #5256e0; }
    .le-crop-foot .le-go i { width: 16px; height: 16px; }
    .le-crop-busy { font-size: .8rem; color: var(--le-accent); font-weight: 700; }

    /* Edit-mode only: the impact value is wrapped in a <span data-ed> which would
       otherwise be caught by `.va-impact-card span{display:block;…}` (the label
       style) and render tiny. Re-inline ONLY that span (not the <small> suffix)
       so it keeps the big-number look. */
    .va-impact-card strong span[data-ed] { display: inline; color: inherit; font-size: inherit; font-family: inherit; font-weight: inherit; line-height: inherit; }

    /* ── Toasts ── */
    .le-toasts { position: fixed; bottom: 74px; left: 50%; transform: translateX(-50%); z-index: 9900; display: flex; flex-direction: column; gap: 9px; align-items: center; }
    .le-toast { background: #14253e; color: #fff; font: 700 .88rem "Manrope",sans-serif; padding: 11px 18px; border-radius: 11px;
      box-shadow: 0 14px 34px rgba(8,20,40,.32); opacity: 0; transform: translateY(10px); transition: opacity .25s, transform .25s; }
    .le-toast.show { opacity: 1; transform: none; }
    .le-toast.err { background: #c0392b; }

    @media (max-width: 720px) { .le-top .le-hint, .le-drag-label { display: none; } }
  </style>
</head>
<body class="le-body">

  <header class="le-top">
    <a class="le-home" href="{{ route('admin.dashboard') }}" title="Back to dashboard"><i data-lucide="arrow-left"></i> Dashboard</a>
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
    <a class="le-tbtn" href="{{ route('about') }}" target="_blank" rel="noopener"><i data-lucide="external-link"></i> View live page</a>
    <button class="le-tbtn primary" type="button" id="le-save"><i data-lucide="save"></i> Save</button>
  </header>

  <main class="va-about-page" id="live-root">
    @foreach($sections as $section)
      @include('admin.about._live_section', ['section' => $section, 'types' => $types, 'isNew' => false])
    @endforeach
  </main>

  <div class="le-toasts" id="le-toasts"></div>
  <div class="le-pop" id="le-pop" hidden></div>

  <div class="le-crop" id="le-crop">
    <div class="le-crop-card">
      <div class="le-crop-head"><i data-lucide="crop"></i> Crop to fit the image <span class="le-sp"></span><button type="button" id="le-crop-close" title="Cancel"><i data-lucide="x"></i></button></div>
      <div class="le-crop-stage"><img id="le-crop-img" alt=""></div>
      <div class="le-crop-foot">
        <span class="le-ar-locked" id="le-crop-ratio"><i data-lucide="lock"></i> Locked to the card size</span>
        <span class="le-sp"></span>
        <div class="le-zoom">
          <button type="button" id="le-crop-zoomout" title="Zoom out"><i data-lucide="minus"></i></button>
          <button type="button" id="le-crop-zoomin" title="Zoom in"><i data-lucide="plus"></i></button>
          <button type="button" id="le-crop-reset" title="Reset"><i data-lucide="rotate-ccw"></i></button>
        </div>
        <span class="le-crop-busy" id="le-crop-busy" hidden>Saving…</span>
        <button type="button" class="le-go" id="le-crop-apply"><i data-lucide="check"></i> Apply crop</button>
      </div>
    </div>
  </div>

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
  <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
  <script>
  (function () {
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const SAVE_URL = @json(route('admin.about.live.save'));
    const SECTION_URL = @json(route('admin.about.live.section'));
    const UPLOAD_URL = @json(route('admin.about.upload'));
    const IMPORT_URL = @json(route('admin.about.import'));
    const SCHEMA = @json($types); // section type → { fields: [...] }, for the Options popover
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

    const esc = (s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    const escAttr = (s) => esc(s).replace(/"/g, '&quot;');

    /* ════════ Schema helpers: which fields need the Options popover ════════ */
    function findRepeaterFields(fields, key) {
      for (const f of (fields || [])) {
        if (f.type === 'repeater') {
          if (f.key === key) return f.fields;
          const nested = findRepeaterFields(f.fields, key);
          if (nested) return nested;
        }
      }
      return null;
    }
    function fieldsForScope(scope) {
      const sec = scope.closest('[data-le-sec]');
      const def = sec && SCHEMA[sec.getAttribute('data-ed-type')];
      if (!def) return [];
      if (scope.hasAttribute('data-le-sec')) return def.fields || [];
      const rep = scope.closest('[data-ed-rep]');
      return (rep && findRepeaterFields(def.fields, rep.getAttribute('data-ed-rep'))) || [];
    }
    function inlineKeysOf(scope) {
      const keys = new Set();
      scope.querySelectorAll('[data-ed],[data-ed-img],[data-ed-icon]').forEach(el => {
        if (ownerScope(el) !== scope) return;
        keys.add(el.getAttribute('data-ed') || el.getAttribute('data-ed-img') || el.getAttribute('data-ed-icon'));
      });
      return keys;
    }
    // Fields the inline editor can't reach: selects, checkboxes, and text/textarea
    // used only as attributes (links, anchors, alt text). Icon/image/repeater stay inline.
    function nonInlineFields(scope) {
      const inline = inlineKeysOf(scope);
      // The founder "hand" carries its own popover, so don't surface its fields
      // (hover text + link) again in the generic ⚙ Options popover.
      const hasHand = !!scope.querySelector('[data-ed-hand]');
      return fieldsForScope(scope).filter(f => {
        if (f.type === 'repeater' || f.type === 'image' || f.type === 'icon') return false;
        if (hasHand && (f.key === 'hand_text' || f.key === 'linkedin')) return false;
        if (f.type === 'select' || f.type === 'checkbox') return true;
        return !inline.has(f.key);
      });
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
        tools.innerHTML =
          '<button type="button" class="le-item-move" data-le-tool="move" title="Drag to reorder"><i data-lucide="move"></i></button>'
          + (nonInlineFields(item).length ? '<button type="button" data-le-tool="opts" title="Options"><i data-lucide="sliders-horizontal"></i></button>' : '')
          + '<button type="button" class="le-item-dup" data-le-tool="dup" title="Duplicate"><i data-lucide="copy"></i></button>'
          + '<button type="button" class="le-item-del" data-le-tool="del" title="Remove"><i data-lucide="x"></i></button>';
        item.appendChild(tools);
      });
    }
    // Mark decorated scopes as such (for the whole root initially)
    decorate(root);
    refreshIcons();
    // (refreshSectionOptionTriggers runs once the helpers below are defined)

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
      // keep clear of the fixed bottom toolbar (60px)
      if (top + h > innerHeight - 72) top = Math.max(12, anchorRect.top - h - 8);
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
        '<input type="text" id="le-img-url" value="' + cur.replace(/"/g, '&quot;') + '" placeholder="https://… or upload a file">' +
        '<div class="le-pop-row">' +
          '<label class="le-pp-btn primary"><i data-lucide="upload"></i> Upload &amp; crop<input type="file" accept="image/*" id="le-img-file" hidden></label>' +
          '<button type="button" class="le-pp-btn" id="le-img-crop"><i data-lucide="crop"></i> Crop current</button>' +
          '<button type="button" class="le-pp-btn" id="le-img-apply"><i data-lucide="link"></i> Use URL</button>' +
        '</div>';
      refreshIcons();
      positionPop(el.getBoundingClientRect());
      const urlInput = pop.querySelector('#le-img-url');
      urlInput.focus();
      pop.querySelector('#le-img-apply').onclick = () => { setImage(popTarget, urlInput.value.trim()); closePop(); toast('Image updated'); };
      pop.querySelector('#le-img-crop').onclick = () => {
        const src = (urlInput.value.trim() || cur);
        if (!src) { toast('Add or upload an image first.', true); return; }
        openCropper(src);
      };
      // Uploading a local file goes straight into the cropper (same-origin, never
      // tainted) so the photo is always cropped to the card's exact shape.
      pop.querySelector('#le-img-file').onchange = (e) => {
        const f = e.target.files[0]; if (!f) return;
        openCropper(URL.createObjectURL(f));
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

    /* ════════ Hand-icon popover (founder card: hover help text + click URL) ════════ */
    // The "say hi" hand on each founder card carries two member fields: hand_text
    // (a tooltip shown on hover) and linkedin (the URL it links to). Both live in
    // the item's le-extra JSON; this popover writes them and previews live.
    function openHandPopover(el) {
      popTarget = el;
      const item = el.closest('[data-ed-item]');
      const data = item ? readExtra(item) : {};
      const tip = data.hand_text || '';
      const url = (data.linkedin && data.linkedin !== '#') ? data.linkedin : '';
      pop.innerHTML =
        '<h4><i data-lucide="hand"></i> Hand icon</h4>' +
        '<label>Hover help text</label>' +
        '<input type="text" id="le-hand-tip" value="' + escAttr(tip) + '" placeholder="e.g. Say hi on LinkedIn">' +
        '<label>Click link (redirect URL)</label>' +
        '<input type="text" id="le-hand-url" value="' + escAttr(url) + '" placeholder="https://…">' +
        '<p class="le-note">On the live site, hovering the hand shows the help text and clicking it opens this link.</p>' +
        '<div class="le-pop-row"><button type="button" class="le-pp-btn primary" id="le-hand-done"><i data-lucide="check"></i> Done</button></div>';
      refreshIcons();
      positionPop(el.getBoundingClientRect());
      const tipInput = pop.querySelector('#le-hand-tip');
      const urlInput = pop.querySelector('#le-hand-url');
      const applyHand = () => {
        const t = tipInput.value.trim();
        const u = urlInput.value.trim();
        if (item) { writeExtra(item, 'hand_text', t); writeExtra(item, 'linkedin', u); }
        if (t) { el.setAttribute('data-tip', t); el.setAttribute('aria-label', t); }
        else { el.removeAttribute('data-tip'); }
        // Blank link → no href at all, so the hand never redirects anywhere.
        if (u) el.setAttribute('href', u); else el.removeAttribute('href');
        markDirty();
      };
      tipInput.addEventListener('input', applyHand);
      urlInput.addEventListener('input', applyHand);
      tipInput.focus();
      pop.querySelector('#le-hand-done').onclick = closePop;
    }

    /* ════════ Crop (Cropper.js) — box locked to the edited card's shape ════════ */
    const cropEl = document.getElementById('le-crop');
    const cropImg = document.getElementById('le-crop-img');
    const cropBusy = document.getElementById('le-crop-busy');
    let cropper = null, cropTarget = null, cropAspect = 1;
    const isLocalUrl = (u) => u.startsWith('blob:') || u.startsWith('data:') || u.startsWith('/') || u.startsWith(location.origin);

    // The crop box is locked to the on-screen proportions of the exact image
    // element being edited, so every photo fills its card (hero collage, pillar,
    // team, CTA) with no stretching, letterboxing or blur.
    function targetAspect(el) {
      const r = el.getBoundingClientRect();
      return (r.width > 1 && r.height > 1) ? r.width / r.height : 1;
    }
    function openCropper(src) {
      cropTarget = popTarget;
      cropAspect = cropTarget ? targetAspect(cropTarget) : 1;
      // Remote images (e.g. Unsplash) would taint the crop canvas, so pull them
      // to local storage via the server first, then crop the same-origin copy.
      if (!isLocalUrl(src)) {
        toast('Preparing image for cropping…');
        fetch(IMPORT_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ url: src }) })
          .then(r => r.ok ? r.json() : Promise.reject()).then(d => startCropper(d.url))
          .catch(() => toast('Could not load that remote image — upload the file instead.', true));
        return;
      }
      startCropper(src);
    }
    function startCropper(src) {
      cropEl.classList.add('open');
      if (cropper) { cropper.destroy(); cropper = null; }
      cropImg.crossOrigin = 'anonymous';
      cropImg.onerror = () => { toast('Could not load that image for cropping.', true); closeCrop(); };
      cropImg.src = src;
      const lbl = document.getElementById('le-crop-ratio');
      if (lbl) lbl.innerHTML = '<i data-lucide="lock"></i> Locked to the card (' + cropAspect.toFixed(2) + ':1)';
      cropper = new Cropper(cropImg, { viewMode: 1, aspectRatio: cropAspect, autoCropArea: 1, background: true, movable: true, zoomable: true, responsive: true });
      refreshIcons();
    }
    function closeCrop() { cropEl.classList.remove('open'); cropBusy.hidden = true; if (cropper) { cropper.destroy(); cropper = null; } }
    document.getElementById('le-crop-close').onclick = closeCrop;
    document.getElementById('le-crop-zoomin').onclick = () => cropper && cropper.zoom(0.1);
    document.getElementById('le-crop-zoomout').onclick = () => cropper && cropper.zoom(-0.1);
    document.getElementById('le-crop-reset').onclick = () => cropper && cropper.reset();
    document.getElementById('le-crop-apply').onclick = () => {
      if (!cropper) return;
      // maxWidth/maxHeight cap large images but never upscale, so the crop is
      // only ever as sharp as the source — never artificially blurred.
      const canvas = cropper.getCroppedCanvas({ maxWidth: 2400, maxHeight: 2400, imageSmoothingQuality: 'high' });
      if (!canvas) { toast('Could not crop this image.', true); return; }
      if (Math.min(canvas.width, canvas.height) < 600) toast('Heads up: this crop is low-resolution and may look soft.', true);
      cropBusy.hidden = false;
      canvas.toBlob((blob) => {
        if (!blob) { cropBusy.hidden = true; toast('This remote image blocks cropping — upload the file instead.', true); return; }
        const fd = new FormData(); fd.append('file', blob, 'about.jpg');
        fetch(UPLOAD_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: fd })
          .then(r => r.ok ? r.json() : Promise.reject()).then(d => {
            cropBusy.hidden = true;
            if (cropTarget) setImage(cropTarget, d.url);
            closeCrop(); closePop(); toast('Cropped & applied');
          }).catch(() => { cropBusy.hidden = true; toast('Upload failed (JPG/PNG/WebP under 5 MB).', true); });
      }, 'image/jpeg', 0.9);
    };

    /* ════════ Options popover (select / checkbox / link / anchor / alt) ════════ */
    function writeExtra(scope, key, val) {
      let s = scope.querySelector(':scope > script.le-extra');
      let obj = {};
      if (s) { try { obj = JSON.parse(s.textContent || '{}') || {}; } catch (e) {} }
      obj[key] = val;
      if (!s) { s = document.createElement('script'); s.type = 'application/json'; s.className = 'le-extra'; scope.insertBefore(s, scope.firstChild); }
      s.textContent = JSON.stringify(obj);
    }
    // Reflect select/checkbox/attribute changes live in the DOM.
    function applyFieldVisual(scope, key, val) {
      const sec = scope.closest('[data-le-sec]');
      const type = sec ? sec.getAttribute('data-ed-type') : '';
      if (key === 'accent') {
        scope.classList.remove('va-vm-card--vision', 'va-vm-card--mission');
        if (val === 'vision') scope.classList.add('va-vm-card--vision');
        else if (val === 'mission') scope.classList.add('va-vm-card--mission');
      } else if (key === 'reverse') {
        scope.classList.toggle('va-pillar--reverse', !!val);
      } else if (key === 'style') {
        scope.classList.remove('btn-primary', 'btn-ghost', 'va-cta-ghost');
        if (val === 'primary') scope.classList.add('btn-primary');
        else { scope.classList.add('btn-ghost'); if (type === 'cta') scope.classList.add('va-cta-ghost'); }
      } else if (key === 'href') {
        if (scope.tagName === 'A') scope.setAttribute('href', val || '#');
      } else if (key === 'anchor') {
        scope.id = val || '';
      } else if (key === 'linkedin') {
        const a = scope.querySelector('.va-team-social'); if (a) a.setAttribute('href', val || '#');
      } else if (key === 'image_alt') {
        const img = scope.querySelector('img[data-ed-img]') || scope.querySelector('img'); if (img) img.setAttribute('alt', val || '');
      }
    }
    function openOptionsPopover(scope) {
      popTarget = scope;
      const fields = nonInlineFields(scope);
      const data = readExtra(scope);
      let html = '<h4><i data-lucide="sliders-horizontal"></i> Options</h4>';
      if (!fields.length) {
        html += '<p class="le-note">No extra options for this part.</p>';
      } else {
        fields.forEach(f => {
          const v = data[f.key];
          if (f.type === 'select') {
            html += '<label>' + esc(f.label) + '</label><select class="le-opt" data-opt-key="' + escAttr(f.key) + '">'
              + Object.entries(f.options || {}).map(([val, lbl]) => '<option value="' + escAttr(val) + '"' + (String(v ?? '') === String(val) ? ' selected' : '') + '>' + esc(lbl) + '</option>').join('')
              + '</select>';
          } else if (f.type === 'checkbox') {
            html += '<label class="le-check"><input type="checkbox" class="le-opt" data-opt-key="' + escAttr(f.key) + '"' + (v ? ' checked' : '') + '> ' + esc(f.label) + '</label>';
          } else if (f.type === 'textarea') {
            html += '<label>' + esc(f.label) + '</label><textarea class="le-opt" data-opt-key="' + escAttr(f.key) + '" rows="3">' + esc(v || '') + '</textarea>';
          } else {
            html += '<label>' + esc(f.label) + '</label><input type="text" class="le-opt" data-opt-key="' + escAttr(f.key) + '" value="' + escAttr(v || '') + '">';
          }
        });
      }
      html += '<div class="le-pop-row"><button type="button" class="le-pp-btn primary" id="le-opt-done"><i data-lucide="check"></i> Done</button></div>';
      pop.innerHTML = html;
      refreshIcons();
      positionPop(scope.getBoundingClientRect());
      pop.querySelectorAll('.le-opt').forEach(ctrl => {
        const evt = (ctrl.tagName === 'SELECT' || ctrl.type === 'checkbox') ? 'change' : 'input';
        ctrl.addEventListener(evt, () => {
          const key = ctrl.dataset.optKey;
          const val = ctrl.type === 'checkbox' ? ctrl.checked : ctrl.value;
          writeExtra(popTarget, key, val);
          applyFieldVisual(popTarget, key, val);
          markDirty();
        });
      });
      pop.querySelector('#le-opt-done').onclick = closePop;
    }

    /* Hide the section ⚙ when a section has no non-inline fields. */
    function refreshSectionOptionTriggers(scope) {
      (scope || root).querySelectorAll('[data-le-sec]').forEach(sec => {
        const btn = sec.querySelector(':scope > .le-sec-bar [data-act="opts"]');
        if (btn) btn.style.display = nonInlineFields(sec).length ? '' : 'none';
      });
    }

    /* ════════ Repeater item drag-to-reorder (like the home editor) ════════ */
    let drag = null;
    root.addEventListener('pointerdown', (e) => {
      const handle = e.target.closest('[data-le-tool="move"]');
      if (!handle) return;
      e.preventDefault(); e.stopPropagation();
      const item = handle.closest('[data-ed-item]');
      const rep = item && item.closest('[data-ed-rep]');
      if (!rep) return;
      item.classList.add('le-item-dragging');
      drag = { item, rep, handle, pid: e.pointerId };
      try { handle.setPointerCapture(e.pointerId); } catch (err) {}
    });
    root.addEventListener('pointermove', (e) => {
      if (!drag) return;
      drag.item.style.pointerEvents = 'none';
      const under = document.elementFromPoint(e.clientX, e.clientY);
      drag.item.style.pointerEvents = '';
      if (!under) return;
      // Find the hovered sibling that belongs to the SAME repeater (walk past
      // nested items, e.g. a chip inside another pillar).
      let t = under.closest('[data-ed-item]');
      while (t && t.closest('[data-ed-rep]') !== drag.rep) t = t.parentElement && t.parentElement.closest('[data-ed-item]');
      if (!t || t === drag.item || t.closest('[data-ed-rep]') !== drag.rep) return;
      // Swap-on-hover: move the dragged item past the target toward the cursor.
      const after = drag.item.compareDocumentPosition(t) & Node.DOCUMENT_POSITION_FOLLOWING;
      if (after) t.after(drag.item); else t.before(drag.item);
      markDirty();
    });
    function endItemDrag() {
      if (!drag) return;
      drag.item.classList.remove('le-item-dragging');
      try { drag.handle.releasePointerCapture(drag.pid); } catch (err) {}
      drag = null;
    }
    root.addEventListener('pointerup', endItemDrag);
    root.addEventListener('pointercancel', endItemDrag);

    document.addEventListener('mousedown', (e) => {
      if (!pop.hidden && !pop.contains(e.target) && !e.target.closest('#le-crop,[data-ed-img],[data-ed-icon],[data-ed-hand],[data-le-tool],[data-act="opts"]')) closePop();
    });

    /* ════════ Click handling inside the page ════════ */
    root.addEventListener('click', (e) => {
      // Item tools (options / reorder / duplicate / delete)
      const tool = e.target.closest('[data-le-tool]');
      if (tool) {
        e.preventDefault(); e.stopPropagation();
        const item = tool.closest('[data-ed-item]');
        const kind = tool.dataset.leTool;
        if (kind === 'move') {
          return; // reordering is handled by the pointer-drag below
        } else if (kind === 'del') {
          if (confirm('Remove this item?')) { item.remove(); markDirty(); toast('Item removed'); }
        } else if (kind === 'opts') {
          openOptionsPopover(item);
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
        if (act.dataset.act === 'opts') {
          openOptionsPopover(sec);
        } else if (act.dataset.act === 'vis') {
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
      // Image / icon editors
      const img = e.target.closest('[data-ed-img]');
      if (img) { e.preventDefault(); openImagePopover(img); return; }
      const icon = e.target.closest('[data-ed-icon]');
      if (icon) { e.preventDefault(); openIconPopover(icon); return; }
      // Founder "hand" icon → its own help-text + link popover
      const hand = e.target.closest('[data-ed-hand]');
      if (hand) { e.preventDefault(); openHandPopover(hand); return; }
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
          refreshSectionOptionTriggers();
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

    refreshSectionOptionTriggers(); // hide the ⚙ on sections with no non-inline fields

    window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  })();
  </script>
</body>
</html>
