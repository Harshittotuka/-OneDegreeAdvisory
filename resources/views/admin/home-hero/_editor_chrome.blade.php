{{-- Home-hero live-editor chrome. Injected by layouts/app.blade.php only when
     $cmsEdit is true, on top of the REAL home page. Greys/locks every section
     except the hero, and adds inline editing, image crop and a phone preview. --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<style>
  :root { --le-accent: #666cff; --le-bar: #0e1f3d; }

  /* ── Lock the page: grey everything except the hero ── */
  body.cms-editing { padding-bottom: 64px; }
  .cms-editing main.home-page > *:not(.hero) { opacity: .4; filter: grayscale(.6); pointer-events: none; user-select: none; }
  .cms-editing footer { opacity: .4; filter: grayscale(.6); pointer-events: none; user-select: none; }
  .cms-editing .contact-fab, .cms-editing .ui-switch, .cms-editing .students-hub-overlay { display: none !important; }
  /* hero stays fully lit + always visible (no scroll-reveal gating) */
  .cms-editing main.home-page > .hero { outline: 3px solid rgba(102,108,255,.55); outline-offset: -3px; }
  .cms-editing .hero .reveal { opacity: 1 !important; transform: none !important; }

  .he-bg-edit { position: absolute; top: 16px; right: 16px; z-index: 6; display: inline-flex; align-items: center; gap: 7px;
    border: 1px solid rgba(255,255,255,.4); background: rgba(14,31,61,.62); backdrop-filter: blur(6px); color: #fff;
    border-radius: 10px; padding: 9px 13px; font: 800 .82rem "Manrope", sans-serif; cursor: pointer; }
  .he-bg-edit:hover { background: var(--le-accent); border-color: var(--le-accent); }
  .he-bg-edit i { width: 16px; height: 16px; }

  /* ── Inline editables ── */
  .cms-editing .hero [data-ed] { transition: outline-color .12s, background .12s; border-radius: 3px; }
  .cms-editing .hero [data-ed]:hover { outline: 1px dashed rgba(255,255,255,.8); outline-offset: 2px; cursor: text; }
  .cms-editing .hero [data-ed]:focus { outline: 2px solid var(--le-accent); outline-offset: 2px; background: rgba(102,108,255,.18); }
  .cms-editing .hero [data-ed]:empty { min-width: 46px; min-height: 1em; display: inline-block; outline: 1px dashed rgba(255,255,255,.7); }
  .cms-editing .hero [data-ed]:empty::before { content: "edit…"; color: rgba(255,255,255,.7); font-size: .8em; }
  .cms-editing .hero .le-ic { display: contents; }
  .cms-editing .hero [data-ed-icon] { cursor: pointer; }
  .cms-editing .hero [data-ed-icon]:hover svg { outline: 2px solid var(--le-accent); outline-offset: 3px; border-radius: 5px; }

  /* ── Button (repeater) tools ── */
  .cms-editing .hero-actions [data-ed-item] { position: relative; }
  .le-item-tools { position: absolute; top: -14px; right: -8px; z-index: 55; display: flex; gap: 4px; opacity: 0; transition: opacity .12s; }
  [data-ed-item]:hover > .le-item-tools { opacity: 1; }
  .le-item-tools button { width: 26px; height: 26px; border: 0; border-radius: 7px; cursor: pointer; background: rgba(14,31,61,.95);
    color: #fff; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(8,20,40,.3); }
  .le-item-tools button:hover { background: var(--le-accent); }
  .le-item-tools button.le-item-del:hover { background: #c0392b; }
  .le-item-tools svg { width: 14px; height: 14px; }
  .he-add-btn { display: inline-flex; align-items: center; gap: 7px; min-height: 46px; border-radius: 999px;
    border: 1px dashed rgba(255,255,255,.55); background: rgba(255,255,255,.08); color: #fff; padding: 0 16px;
    font: 800 .9rem "Manrope", sans-serif; cursor: pointer; }
  .he-add-btn:hover { border-color: var(--le-accent); background: rgba(102,108,255,.22); }
  .he-add-btn i { width: 17px; height: 17px; }

  /* Buttons must stay clickable/editable in the editor even when styled
     "disabled" (which sets pointer-events:none + cursor:not-allowed on the site). */
  .cms-editing .hero-actions [data-ed-item],
  .cms-editing .hero-actions [data-ed-item] * { pointer-events: auto !important; cursor: pointer; }
  .cms-editing .hero-actions [data-ed-item] [data-ed] { cursor: text; }

  /* Empty rows still need a drop target while editing. */
  .cms-editing .hero-actions[data-he-row] { min-height: 46px; min-width: 60px; border-radius: 12px; }
  .cms-editing .hero-actions[data-he-row]:empty { border: 1px dashed rgba(255,255,255,.3); }

  /* Drag-to-reorder handle + dragging state. */
  .le-item-move { cursor: grab; }
  .le-item-move:active { cursor: grabbing; }
  .cms-editing [data-ed-item].he-dragging { opacity: .45; }

  /* Add button / add row controls. */
  .he-add-controls { display: flex; gap: 10px; flex-wrap: wrap; }

  /* Inline text-style triggers. */
  .cms-editing .hero .he-text-wrap { position: relative; display: inline-block; }
  .cms-editing .hero .he-text-wrap--eyebrow { display: inline-flex; align-items: center; }
  .cms-editing .hero .he-text-wrap--heading { display: inline-block; }
  .he-style-trigger { position: absolute; z-index: 70; width: 30px; height: 30px; border: 1px solid rgba(255,255,255,.48);
    border-radius: 999px; background: rgba(14,31,61,.78); color: #fff; display: inline-flex; align-items: center; justify-content: center;
    padding: 0; cursor: pointer; box-shadow: 0 8px 22px rgba(8,20,40,.32); backdrop-filter: blur(6px); }
  .he-style-trigger:hover, .he-style-trigger:focus-visible { background: var(--le-accent); border-color: var(--le-accent); outline: none; }
  .he-style-trigger svg { width: 14px; height: 14px; }
  .he-style-trigger-swatch { position: absolute; right: -2px; bottom: -2px; width: 10px; height: 10px; border: 1px solid #fff;
    border-radius: 999px; background: var(--he-swatch, var(--le-accent)); box-shadow: 0 1px 4px rgba(8,20,40,.3); }
  .he-style-trigger--eyebrow { top: -20px; right: -38px; }
  .he-style-trigger--heading { top: -24px; right: -36px; }
  .he-style-trigger--highlight { top: -24px; right: -36px; }

  /* Colour-picker rows (text colours popover) */
  .le-color-row { display: flex; align-items: center; gap: 9px; margin-bottom: 11px; }
  .le-color-row input[type=color] { width: 44px; height: 34px; padding: 2px; border: 1px solid #e5e8ee; border-radius: 8px; background: #fff; cursor: pointer; }
  .le-color-val { flex: 1; font-size: .8rem; color: #6a7686; font-weight: 700; }
  .le-color-row .le-mini { flex: 0 0 auto; border: 1px solid #e5e8ee; background: #fff; border-radius: 8px; padding: 7px 10px; font: 700 .78rem "Manrope", sans-serif; color: #6a7686; cursor: pointer; }
  .le-color-row .le-mini:hover { border-color: var(--le-accent); color: var(--le-accent); }
  .le-style-panel { display: none; margin-bottom: 12px; padding: 10px; border: 1px solid #eef1f4; border-radius: 10px; background: #fafbfc; }
  .le-style-panel.on { display: block; }
  .le-style-note { margin: -3px 0 11px; color: #6a7686; font-size: .78rem; font-weight: 600; line-height: 1.4; }
  .le-style-colors { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .le-style-field input[type=color] { width: 100%; height: 38px; padding: 2px; border: 1px solid #e5e8ee; border-radius: 8px; background: #fff; cursor: pointer; }
  .le-style-field .le-color-val { display: block; margin-top: 5px; }

  /* ── Bottom toolbar ── */
  .le-bottombar { position: fixed; left: 0; right: 0; bottom: 0; height: 60px; z-index: 9000; display: flex; align-items: center;
    gap: 14px; padding: 0 16px; background: var(--le-bar); color: #fff; font-family: "Manrope", system-ui, sans-serif;
    box-shadow: 0 -6px 22px rgba(8,20,40,.34); }
  .le-bottombar a, .le-bottombar button { font-family: inherit; }
  .le-bottombar .le-home { display: inline-flex; align-items: center; gap: 8px; color: #cdd9ef; text-decoration: none; font-weight: 700; font-size: .9rem; }
  .le-bottombar .le-home:hover { color: #fff; }
  .le-bottombar .le-title { font-weight: 800; display: flex; align-items: center; gap: 9px; }
  .le-bottombar .le-badge { font-size: .62rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; background: var(--le-accent); color: #fff; padding: 3px 8px; border-radius: 999px; }
  .le-bottombar .le-hint { color: #9fb0d0; font-size: .82rem; font-weight: 600; }
  .le-bottombar .le-sp { flex: 1; }
  .le-bottombar .le-status { font-size: .8rem; font-weight: 700; color: #9fb0d0; min-width: 70px; text-align: right; }
  .le-tbtn { display: inline-flex; align-items: center; gap: 7px; border: 1px solid rgba(255,255,255,.18);
    background: rgba(255,255,255,.06); color: #fff; border-radius: 10px; padding: 9px 14px; font-weight: 700; font-size: .88rem; cursor: pointer; }
  .le-tbtn:hover { background: rgba(255,255,255,.14); }
  .le-tbtn.primary { background: var(--le-accent); border-color: var(--le-accent); }
  .le-tbtn.primary:hover { background: #5256e0; }
  .le-tbtn i { width: 16px; height: 16px; }

  /* ── Popover ── */
  .le-pop { position: fixed; z-index: 9500; width: 322px; max-width: calc(100vw - 24px); background: #fff; color: #14253e;
    border-radius: 13px; box-shadow: 0 24px 60px rgba(8,20,40,.4); padding: 15px; font-family: "Manrope", sans-serif; }
  .le-pop h4 { margin: 0 0 11px; font-size: .95rem; font-weight: 800; display: flex; align-items: center; gap: 7px; }
  .le-pop label { display: block; font-size: .76rem; font-weight: 800; color: #6a7686; margin: 0 0 5px; text-transform: uppercase; letter-spacing: .05em; }
  .le-pop input[type=text] { width: 100%; box-sizing: border-box; padding: 10px 11px; border: 1px solid #e5e8ee; border-radius: 9px;
    font-family: inherit; font-size: .9rem; margin-bottom: 11px; }
  .le-pop input:focus { outline: none; border-color: var(--le-accent); box-shadow: 0 0 0 3px rgba(102,108,255,.18); }
  .le-pop .le-pop-row { display: flex; gap: 8px; }
  .le-pp-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; flex: 1; border: 1px solid #e5e8ee;
    background: #fff; border-radius: 9px; padding: 9px; font-weight: 700; font-size: .85rem; cursor: pointer; color: #14253e; }
  .le-pp-btn:hover { border-color: var(--le-accent); color: var(--le-accent); }
  .le-pp-btn.primary { background: var(--le-accent); border-color: var(--le-accent); color: #fff; }
  .le-pp-btn.primary:hover { background: #5256e0; color: #fff; }
  .le-pp-btn svg { width: 15px; height: 15px; }
  .le-pop-prev { display: flex; align-items: center; justify-content: center; height: 56px; border: 1px dashed #e5e8ee; border-radius: 9px; margin-bottom: 11px; color: var(--le-accent); overflow: hidden; }
  .le-pop-prev svg { width: 26px; height: 26px; }
  .le-pop-prev img { max-height: 54px; border-radius: 7px; }
  .le-seg { display: flex; gap: 6px; margin-bottom: 12px; }
  .le-seg button { flex: 1; text-transform: capitalize; border: 1px solid #e5e8ee; background: #fff; border-radius: 9px; padding: 9px 6px; font: 700 .82rem "Manrope", sans-serif; color: #6a7686; cursor: pointer; }
  .le-seg button.on { border-color: var(--le-accent); background: #ebecff; color: #5256e0; }
  .le-seg[data-style-animation] { flex-wrap: wrap; }
  .le-seg[data-style-animation] button { flex: 1 1 calc(33.333% - 6px); }

  /* ── Crop modal ── */
  .le-crop { position: fixed; inset: 0; z-index: 9800; background: rgba(8,18,33,.82); display: none; align-items: center; justify-content: center; padding: 24px; font-family: "Manrope", sans-serif; }
  .le-crop.open { display: flex; }
  .le-crop-card { width: 760px; max-width: 96vw; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 40px 90px rgba(0,0,0,.5); }
  .le-crop-head { display: flex; align-items: center; gap: 9px; padding: 14px 18px; font-weight: 800; border-bottom: 1px solid #eef1f4; }
  .le-crop-head .le-sp { flex: 1; }
  .le-crop-head button { border: 0; background: none; cursor: pointer; color: #6a7686; display: inline-flex; }
  .le-crop-stage { background: #11202f; max-height: 56vh; }
  .le-crop-stage img { max-width: 100%; display: block; }
  .le-crop-foot { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; padding: 13px 18px; border-top: 1px solid #eef1f4; }
  .le-ar-locked { display: inline-flex; align-items: center; gap: 7px; font: 700 .82rem "Manrope", sans-serif; color: #5256e0;
    background: #ebecff; border: 1px solid #cdd0ff; border-radius: 8px; padding: 7px 12px; }
  .le-ar-locked i { width: 14px; height: 14px; }
  .le-crop-foot .le-sp { flex: 1; }
  .le-zoom { display: inline-flex; gap: 5px; }
  .le-zoom button { width: 34px; height: 34px; border: 1px solid #e5e8ee; background: #fff; border-radius: 8px; cursor: pointer; color: #14253e; display: inline-flex; align-items: center; justify-content: center; }
  .le-crop-foot .le-go { display: inline-flex; align-items: center; gap: 7px; border: 0; background: var(--le-accent); color: #fff; border-radius: 9px; padding: 10px 16px; font: 800 .88rem "Manrope", sans-serif; cursor: pointer; }
  .le-crop-foot .le-go:hover { background: #5256e0; }
  .le-crop-foot .le-go i { width: 16px; height: 16px; }
  .le-crop-busy { font-size: .8rem; color: var(--le-accent); font-weight: 700; }

  /* ── Phone preview overlay ── */
  .le-ph { position: fixed; inset: 0; z-index: 9700; background: rgba(8,18,33,.74); backdrop-filter: blur(5px); display: none; flex-direction: column; align-items: center; justify-content: center; gap: 16px; padding: 20px; }
  .le-ph.open { display: flex; }
  .le-ph-bar { display: flex; align-items: center; gap: 12px; color: #fff; font-family: "Manrope", sans-serif; font-weight: 700; }
  .le-ph-bar a, .le-ph-bar button { display: inline-flex; align-items: center; gap: 7px; border: 1px solid rgba(255,255,255,.25); background: rgba(255,255,255,.08); color: #fff; border-radius: 9px; padding: 8px 13px; font: 700 .85rem "Manrope", sans-serif; cursor: pointer; text-decoration: none; }
  .le-ph-bar a:hover, .le-ph-bar button:hover { background: rgba(255,255,255,.18); }
  .le-ph-bar i { width: 16px; height: 16px; }
  .le-ph-frame { position: relative; width: 392px; max-width: 92vw; height: 80vh; max-height: 850px; background: #0b1220; border: 13px solid #0b1220; border-radius: 52px; box-shadow: 0 40px 90px rgba(0,0,0,.55); }
  .le-ph-frame::before { content: ""; position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 44%; height: 26px; background: #0b1220; border-radius: 0 0 16px 16px; z-index: 3; }
  .le-ph-screen { position: absolute; inset: 0; border-radius: 40px; overflow: hidden; background: #fff; }
  .le-ph-screen iframe { width: 100%; height: 100%; border: 0; display: block; }
  .le-ph-spin { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: #8aa0c4; font: 700 .85rem "Manrope", sans-serif; }

  /* ── Toasts ── */
  .le-toasts { position: fixed; bottom: 74px; left: 50%; transform: translateX(-50%); z-index: 9900; display: flex; flex-direction: column; gap: 9px; align-items: center; }
  .le-toast { background: #14253e; color: #fff; font: 700 .88rem "Manrope", sans-serif; padding: 11px 18px; border-radius: 11px; box-shadow: 0 14px 34px rgba(8,20,40,.32); opacity: 0; transform: translateY(10px); transition: opacity .25s, transform .25s; }
  .le-toast.show { opacity: 1; transform: none; }
  .le-toast.err { background: #c0392b; }

  @media (max-width: 760px) { .le-bottombar .le-hint, .le-bottombar .le-home span { display: none; } }
</style>

<div class="le-bottombar">
  <a class="le-home" href="{{ route('admin.blog.index') }}" title="Back to dashboard"><i data-lucide="arrow-left"></i> <span>Dashboard</span></a>
  <span class="le-title">Home page <span class="le-badge">Live Edit</span></span>
  <span class="le-hint">Only the hero is editable - click text, its palette dot, the photo, or an icon. Other sections are locked.</span>
  <span class="le-sp"></span>
  <span class="le-status" id="le-status"></span>
  <button class="le-tbtn" type="button" id="le-phone-btn"><i data-lucide="smartphone"></i> Phone preview</button>
  <button class="le-tbtn primary" type="button" id="le-save"><i data-lucide="save"></i> Save</button>
</div>

<div class="le-toasts" id="le-toasts"></div>
<div class="le-pop" id="le-pop" hidden></div>

<div class="le-crop" id="le-crop">
  <div class="le-crop-card">
    <div class="le-crop-head"><i data-lucide="crop"></i> Crop to fit the hero <span class="le-sp"></span><button type="button" id="le-crop-close" title="Cancel"><i data-lucide="x"></i></button></div>
    <div class="le-crop-stage"><img id="le-crop-img" alt=""></div>
    <div class="le-crop-foot">
      <span class="le-ar-locked" id="le-crop-ratio"><i data-lucide="lock"></i> Locked to hero size</span>
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

<div class="le-ph" id="le-ph">
  <div class="le-ph-bar">
    <span><i data-lucide="smartphone"></i> Full home page · phone preview</span>
    <a id="le-ph-open" href="#" target="_blank"><i data-lucide="external-link"></i> Open in new tab</a>
    <button type="button" id="le-ph-close"><i data-lucide="x"></i> Close</button>
  </div>
  <div class="le-ph-frame">
    <div class="le-ph-screen">
      <div class="le-ph-spin" id="le-ph-spin">Loading preview…</div>
      <iframe id="le-ph-iframe" title="Home page phone preview"></iframe>
    </div>
  </div>
</div>

<datalist id="le-icons">
  @foreach(['compass','graduation-cap','globe','arrow-right','arrow-up-right','book-open','briefcase','users',
            'user-check','award','trophy','sparkles','map-pin','plane-takeoff','phone','mail','calendar','clock',
            'rocket','target','route','layers','shield-check','badge-check','heart-handshake','star','flag',
            'lightbulb','message-circle','play','download','search','chevron-right'] as $ic)
    <option value="{{ $ic }}">
  @endforeach
</datalist>

<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script>
(function () {
  const root = document.querySelector('main.home-page .hero');
  if (!root) return;
  const CSRF = @json(csrf_token()); // layouts/app has no csrf meta tag, so emit it directly
  const SAVE_URL = @json(route('admin.home-hero.live.save'));
  const UPLOAD_URL = @json(route('admin.home-hero.upload'));
  const IMPORT_URL = @json(route('admin.home-hero.import'));
  const PREVIEW_URL = @json(route('admin.home-hero.preview'));
  const statusEl = document.getElementById('le-status');
  const STYLE_KEYS = ['eyebrow', 'heading', 'highlight'];
  const STYLE_LABELS = { eyebrow: 'Eyebrow', heading: 'Headline', highlight: 'Highlighted words' };
  const STYLE_DEFAULTS = @json(\App\Support\HeroContent::TEXT_STYLE_DEFAULTS);
  const rawHeroStyles = @json($hero['styles'] ?? new \stdClass);
  const legacyColors = Object.assign({ eyebrow: '', heading: '', highlight: '' }, @json($hero['colors'] ?? new \stdClass));
  const isHex = (v) => /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(String(v || '').trim());
  function cleanHex(v, fallback) {
    v = String(v || '').trim();
    return isHex(v) ? v : (fallback || '');
  }
  function normalizeTextStyle(key, raw) {
    const def = STYLE_DEFAULTS[key] || STYLE_DEFAULTS.highlight;
    raw = raw && typeof raw === 'object' ? raw : {};
    const legacy = cleanHex(legacyColors[key], '');
    let mode = ['default', 'solid', 'gradient'].includes(raw.mode) ? raw.mode : (legacy ? 'solid' : def.mode);
    const hasRawColor = Object.prototype.hasOwnProperty.call(raw, 'color');
    const color = cleanHex(raw.color, '') || (hasRawColor ? '' : legacy);
    if (mode === 'solid' && !color) mode = 'default';
    return {
      mode,
      color,
      gradient_start: cleanHex(raw.gradient_start, def.gradient_start),
      gradient_end: cleanHex(raw.gradient_end, def.gradient_end),
      animation: ['theme', 'none', 'shimmer', 'pulse', 'lift'].includes(raw.animation) ? raw.animation : def.animation,
    };
  }
  const heroStyles = {};
  STYLE_KEYS.forEach(key => { heroStyles[key] = normalizeTextStyle(key, rawHeroStyles[key]); });
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

  /* ── Decoration ── */
  const STYLE_CLASS = { orange: 'btn-orange', ghost: 'btn-ghost', disabled: 'btn-disabled' };
  function decorate(scope) {
    scope.querySelectorAll('[data-ed]').forEach(el => el.setAttribute('contenteditable', 'true'));
    scope.querySelectorAll('[data-ed-item]').forEach(item => {
      if (item.dataset.leDeco === '1') return;
      item.dataset.leDeco = '1';
      const tools = document.createElement('span');
      tools.className = 'le-item-tools';
      tools.setAttribute('contenteditable', 'false');
      tools.innerHTML =
        '<button type="button" class="le-item-move" data-le-tool="move" title="Drag to reorder / move between rows"><i data-lucide="move"></i></button>' +
        '<button type="button" data-le-tool="cfg" title="Style & link"><i data-lucide="settings-2"></i></button>' +
        '<button type="button" data-le-tool="dup" title="Duplicate"><i data-lucide="copy"></i></button>' +
        '<button type="button" class="le-item-del" data-le-tool="del" title="Remove"><i data-lucide="x"></i></button>';
      item.appendChild(tools);
    });
  }
  decorate(root);
  refreshIcons();

  /* ── Serialization (DOM → hero data) ── */
  function readExtra(scope) {
    const s = scope.querySelector(':scope > script.le-extra');
    if (!s) return {};
    try { return JSON.parse(s.textContent || '{}') || {}; } catch (e) { return {}; }
  }
  function closestScope(el) {
    const item = el.closest('[data-ed-item]');
    return (item && root.contains(item)) ? item : root;
  }
  function readVal(el) {
    if (el.hasAttribute('data-ed-img')) return el.getAttribute('data-ed-imgval') || '';
    if (el.hasAttribute('data-ed-icon')) return el.getAttribute('data-ed-iconname') || '';
    return (el.textContent || '').replace(/\u00a0/g, ' ').trim();
  }
  function serializeScope(scope) {
    const data = Object.assign({}, readExtra(scope));
    scope.querySelectorAll('[data-ed],[data-ed-img],[data-ed-icon]').forEach(el => {
      if (closestScope(el) !== scope) return;
      const key = el.getAttribute('data-ed') || el.getAttribute('data-ed-img') || el.getAttribute('data-ed-icon');
      data[key] = readVal(el);
    });
    scope.querySelectorAll('[data-ed-rep]').forEach(rep => {
      if (closestScope(rep) !== scope) return;
      const key = rep.getAttribute('data-ed-rep');
      const items = [...rep.querySelectorAll('[data-ed-item]')].filter(it => it.closest('[data-ed-rep]') === rep);
      data[key] = items.map(it => {
        const obj = serializeScope(it);
        if (it.dataset.heStyle) obj.style = it.dataset.heStyle;
        if (it.hasAttribute('data-he-href')) obj.href = it.getAttribute('data-he-href');
        return obj;
      });
    });
    return data;
  }
  // Collect every button across the stacked rows; row index = the row's order.
  function collectActions() {
    const out = [];
    root.querySelectorAll('.hero-actions[data-he-row]').forEach((rowEl, rowIdx) => {
      rowEl.querySelectorAll('[data-he-action]').forEach(it => {
        const o = Object.assign({}, readExtra(it));
        const lab = it.querySelector('[data-ed="label"]');
        if (lab) o.label = (lab.textContent || '').replace(/\u00a0/g, ' ').trim();
        const ic = it.querySelector('[data-ed-icon]');
        if (ic) o.icon = ic.getAttribute('data-ed-iconname') || '';
        o.style = it.dataset.heStyle || o.style || 'orange';
        o.href = it.hasAttribute('data-he-href') ? it.getAttribute('data-he-href') : (o.href || '');
        o.row = rowIdx;
        out.push(o);
      });
    });
    return out;
  }
  const buildHero = () => {
    const d = serializeScope(root);
    d.styles = heroStyles;
    d.colors = {
      eyebrow: heroStyles.eyebrow.mode === 'solid' ? heroStyles.eyebrow.color : '',
      heading: heroStyles.heading.mode === 'solid' ? heroStyles.heading.color : '',
      highlight: heroStyles.highlight.mode === 'solid' ? heroStyles.highlight.color : '',
    };
    d.actions = collectActions();
    return d;
  };

  /* ── Setters ── */
  function setImage(el, url) {
    el.setAttribute('data-ed-imgval', url);
    el.style.backgroundImage = url ? "url('" + url.replace(/'/g, "\\'") + "')" : 'none';
    markDirty();
  }
  function setIcon(el, name) {
    name = (name || '').toLowerCase().replace(/[^a-z0-9-]/g, '');
    el.setAttribute('data-ed-iconname', name);
    el.innerHTML = '<i data-lucide="' + (name || 'square') + '"></i>';
    refreshIcons();
    markDirty();
  }

  /* ── Popover ── */
  const pop = document.getElementById('le-pop');
  let popTarget = null;
  function positionPop(anchorRect) {
    pop.hidden = false;
    const w = pop.offsetWidth, h = pop.offsetHeight;
    let left = anchorRect.left, top = anchorRect.bottom + 8;
    if (left + w > innerWidth - 12) left = innerWidth - w - 12;
    if (left < 12) left = 12;
    if (top + h > innerHeight - 70) top = Math.max(12, anchorRect.top - h - 8);
    pop.style.left = left + 'px';
    pop.style.top = top + 'px';
  }
  function closePop() { pop.hidden = true; popTarget = null; pop.innerHTML = ''; }

  function uploadFile(fileOrBlob, name) {
    const fd = new FormData(); fd.append('file', fileOrBlob, name || 'hero.jpg');
    return fetch(UPLOAD_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: fd })
      .then(r => r.ok ? r.json() : Promise.reject()).then(d => d.url);
  }

  function openImagePopover(el, anchor) {
    popTarget = el;
    const cur = el.getAttribute('data-ed-imgval') || '';
    pop.innerHTML =
      '<h4><i data-lucide="image"></i> Background photo</h4>' +
      '<div class="le-pop-prev">' + (cur ? '<img src="' + cur.replace(/"/g, '&quot;') + '" alt="">' : '<i data-lucide="image"></i>') + '</div>' +
      '<label>Image URL</label>' +
      '<input type="text" id="le-img-url" value="' + cur.replace(/"/g, '&quot;') + '" placeholder="https://… or upload a file">' +
      '<div class="le-pop-row">' +
        '<label class="le-pp-btn primary"><i data-lucide="upload"></i> Upload &amp; crop<input type="file" accept="image/*" id="le-img-file" hidden></label>' +
        '<button type="button" class="le-pp-btn" id="le-img-crop"><i data-lucide="crop"></i> Crop current</button>' +
        '<button type="button" class="le-pp-btn" id="le-img-apply"><i data-lucide="link"></i> Use URL</button>' +
      '</div>';
    refreshIcons();
    positionPop((anchor || el).getBoundingClientRect());
    const urlInput = pop.querySelector('#le-img-url');
    urlInput.focus();
    pop.querySelector('#le-img-apply').onclick = () => { setImage(popTarget, urlInput.value.trim()); closePop(); toast('Background updated'); };
    pop.querySelector('#le-img-crop').onclick = () => {
      const src = (urlInput.value.trim() || cur);
      if (!src) { toast('Add or upload an image first.', true); return; }
      openCropper(src);
    };
    // Uploading goes straight into the cropper (local file = same-origin, never
    // tainted) so the photo is always cropped to the hero's exact shape.
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
      '<input type="text" id="le-ic-name" list="le-icons" value="' + cur.replace(/"/g, '&quot;') + '" placeholder="e.g. globe, compass" spellcheck="false" autocomplete="off">' +
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

  function applyButtonConfig(item, style, href) {
    item.dataset.heStyle = style;
    item.setAttribute('data-he-href', href);
    if (item.tagName === 'A') item.setAttribute('href', href || '#');
    item.classList.remove('btn-orange', 'btn-ghost', 'btn-disabled');
    item.classList.add(STYLE_CLASS[style] || 'btn-orange');
    markDirty();
  }
  function openCfgPopover(item) {
    popTarget = item;
    const curStyle = item.dataset.heStyle || 'orange';
    const curHref = item.getAttribute('data-he-href') || '';
    pop.innerHTML =
      '<h4><i data-lucide="settings-2"></i> Button</h4>' +
      '<label>Style</label>' +
      '<div class="le-seg" id="le-cfg-style">' +
        [['orange', 'Solid'], ['ghost', 'Outline'], ['disabled', 'Disabled']].map(([s, lbl]) => '<button type="button" data-style="' + s + '"' + (s === curStyle ? ' class="on"' : '') + '>' + lbl + '</button>').join('') +
      '</div>' +
      '<label>Link (URL or #anchor)</label>' +
      '<input type="text" id="le-cfg-href" value="' + curHref.replace(/"/g, '&quot;') + '" placeholder="/study-abroad or #contact">' +
      '<div class="le-pop-row"><button type="button" class="le-pp-btn primary" id="le-cfg-apply"><i data-lucide="check"></i> Apply</button></div>';
    refreshIcons();
    positionPop(item.getBoundingClientRect());
    let chosen = curStyle;
    pop.querySelectorAll('#le-cfg-style button').forEach(b => b.onclick = () => {
      chosen = b.dataset.style;
      pop.querySelectorAll('#le-cfg-style button').forEach(x => x.classList.toggle('on', x === b));
    });
    pop.querySelector('#le-cfg-apply').onclick = () => {
      applyButtonConfig(popTarget, chosen, pop.querySelector('#le-cfg-href').value.trim());
      closePop();
    };
  }

  document.addEventListener('mousedown', (e) => {
    if (!pop.hidden && !pop.contains(e.target) && !e.target.closest('[data-ed-img],[data-ed-icon],[data-le-tool],[data-he-bg-edit],[data-he-style-open]')) closePop();
  });

  /* ── Drag-to-reorder: swap a button with others / move it between rows ── */
  function newButton() {
    const tpl = document.getElementById('he-action-tpl');
    return tpl.content.firstElementChild.cloneNode(true);
  }
  function cleanupEmptyRows() {
    const rows = [...root.querySelectorAll('.hero-actions[data-he-row]')];
    rows.forEach((r, i) => { if (i > 0 && !r.querySelector('[data-he-action]')) r.remove(); });
    // keep at least one row
    if (!root.querySelector('.hero-actions[data-he-row]')) {
      const row = document.createElement('div'); row.className = 'hero-actions'; row.setAttribute('data-he-row', '');
      root.querySelector('.hero-actions-stack').insertBefore(row, root.querySelector('.he-add-controls'));
    }
  }
  function directActionRow(item) {
    const parent = item && item.parentElement;
    return parent && parent.matches('.hero-actions[data-he-row]') ? parent : null;
  }
  let drag = null;
  root.addEventListener('pointerdown', (e) => {
    const handle = e.target.closest('[data-le-tool="move"]');
    if (!handle) return;
    e.preventDefault(); e.stopPropagation();
    const item = handle.closest('[data-ed-item]'); if (!item) return;
    item.classList.add('he-dragging');
    drag = { item, handle, pid: e.pointerId };
    try { handle.setPointerCapture(e.pointerId); } catch (err) {}
  });
  root.addEventListener('pointermove', (e) => {
    if (!drag) return;
    // Find what's under the cursor (ignoring the dragged item itself).
    drag.item.style.pointerEvents = 'none';
    const under = document.elementFromPoint(e.clientX, e.clientY);
    drag.item.style.pointerEvents = '';
    if (!under) return;
    const target = under.closest('[data-he-action]');
    if (target && target !== drag.item) {
      const targetRow = directActionRow(target);
      if (!targetRow) return;
      const r = target.getBoundingClientRect();
      const after = e.clientX > r.left + r.width / 2;
      targetRow.insertBefore(drag.item, after ? target.nextSibling : target);
      return;
    }
    const row = under.closest('.hero-actions[data-he-row]');
    if (row && !under.closest('[data-he-action]') && directActionRow(drag.item) !== row) {
      row.appendChild(drag.item); // dropped into an empty part of another row
    }
  });
  function endDrag() {
    if (!drag) return;
    drag.item.classList.remove('he-dragging');
    try { drag.handle.releasePointerCapture(drag.pid); } catch (err) {}
    drag = null;
    cleanupEmptyRows();
    markDirty();
  }
  root.addEventListener('pointerup', endDrag);
  root.addEventListener('pointercancel', endDrag);

  /* Text styles */
  function textTargets(key) {
    return [...root.querySelectorAll('[data-he-style-part="' + key + '"]')];
  }
  function swatchFor(key) {
    const s = heroStyles[key] || STYLE_DEFAULTS[key];
    if (s.mode === 'solid') return s.color || '#ffffff';
    if (s.mode === 'gradient') return 'linear-gradient(135deg, ' + s.gradient_start + ', ' + s.gradient_end + ')';
    return key === 'heading' ? '#ffffff' : 'var(--hero-gold-gradient)';
  }
  function solidFallback(key) {
    const def = STYLE_DEFAULTS[key] || STYLE_DEFAULTS.highlight;
    return key === 'heading' ? '#ffffff' : (def.gradient_start || '#ffffff');
  }
  function updateStyleTriggers(key) {
    root.querySelectorAll('[data-he-style-open="' + key + '"]').forEach(btn => btn.style.setProperty('--he-swatch', swatchFor(key)));
  }
  function applyTextStyle(key, next, quiet) {
    heroStyles[key] = normalizeTextStyle(key, Object.assign({}, heroStyles[key] || {}, next || {}));
    const s = heroStyles[key];
    textTargets(key).forEach(el => {
      el.classList.remove('he-text-solid', 'he-text-gradient', 'he-anim-none', 'he-anim-shimmer', 'he-anim-pulse', 'he-anim-lift');
      el.style.removeProperty('--he-text-color');
      el.style.removeProperty('--he-grad-a');
      el.style.removeProperty('--he-grad-b');
      if (s.mode === 'solid') {
        el.classList.add('he-text-solid');
        el.style.setProperty('--he-text-color', s.color);
      } else if (s.mode === 'gradient') {
        el.classList.add('he-text-gradient');
        el.style.setProperty('--he-grad-a', s.gradient_start);
        el.style.setProperty('--he-grad-b', s.gradient_end);
      }
      if (s.animation !== 'theme') el.classList.add('he-anim-' + s.animation);
    });
    updateStyleTriggers(key);
    if (!quiet) markDirty();
  }
  function segButton(value, label, current) {
    return '<button type="button" data-value="' + value + '"' + (value === current ? ' class="on"' : '') + '>' + label + '</button>';
  }
  function openTextStylePopover(key, anchor) {
    popTarget = anchor;
    const s = heroStyles[key];
    const label = STYLE_LABELS[key] || 'Text';
    pop.innerHTML =
      '<h4><i data-lucide="palette"></i> ' + label + ' style</h4>' +
      '<label>Color</label>' +
      '<div class="le-seg" data-style-mode>' +
        [['default', 'Theme'], ['solid', 'Solid'], ['gradient', 'Gradient']].map(([v, l]) => segButton(v, l, s.mode)).join('') +
      '</div>' +
      '<div class="le-style-panel" data-style-panel="solid">' +
        '<label>Solid color</label>' +
        '<div class="le-color-row">' +
          '<input type="color" id="le-style-solid" value="' + (s.color || solidFallback(key)) + '">' +
          '<span class="le-color-val">' + (s.color || solidFallback(key)) + '</span>' +
        '</div>' +
      '</div>' +
      '<div class="le-style-panel" data-style-panel="gradient">' +
        '<div class="le-style-colors">' +
          '<div class="le-style-field"><label>Gradient start</label><input type="color" id="le-style-grad-a" value="' + s.gradient_start + '"><span class="le-color-val">' + s.gradient_start + '</span></div>' +
          '<div class="le-style-field"><label>Gradient end</label><input type="color" id="le-style-grad-b" value="' + s.gradient_end + '"><span class="le-color-val">' + s.gradient_end + '</span></div>' +
        '</div>' +
      '</div>' +
      '<label>Animation</label>' +
      '<div class="le-seg" data-style-animation>' +
        [['theme', 'Theme'], ['none', 'None'], ['shimmer', 'Shimmer'], ['pulse', 'Pulse'], ['lift', 'Lift']].map(([v, l]) => segButton(v, l, s.animation)).join('') +
      '</div>' +
      '<p class="le-style-note">Changes apply to this text only.</p>' +
      '<div class="le-pop-row">' +
        '<button type="button" class="le-pp-btn" data-style-reset><i data-lucide="rotate-ccw"></i> Reset</button>' +
        '<button type="button" class="le-pp-btn primary" data-style-done><i data-lucide="check"></i> Done</button>' +
      '</div>';
    refreshIcons();
    positionPop(anchor.getBoundingClientRect());
    function refreshForm() {
      const current = heroStyles[key];
      pop.querySelectorAll('[data-style-mode] button').forEach(b => b.classList.toggle('on', b.dataset.value === current.mode));
      pop.querySelectorAll('[data-style-animation] button').forEach(b => b.classList.toggle('on', b.dataset.value === current.animation));
      pop.querySelectorAll('[data-style-panel]').forEach(panel => panel.classList.toggle('on', panel.dataset.stylePanel === current.mode));
      const solid = pop.querySelector('#le-style-solid');
      const gradA = pop.querySelector('#le-style-grad-a');
      const gradB = pop.querySelector('#le-style-grad-b');
      if (solid) { solid.value = current.color || solidFallback(key); solid.parentElement.querySelector('.le-color-val').textContent = current.color || solidFallback(key); }
      if (gradA) { gradA.value = current.gradient_start; gradA.parentElement.querySelector('.le-color-val').textContent = current.gradient_start; }
      if (gradB) { gradB.value = current.gradient_end; gradB.parentElement.querySelector('.le-color-val').textContent = current.gradient_end; }
    }
    refreshForm();
    pop.querySelectorAll('[data-style-mode] button').forEach(b => b.addEventListener('click', () => {
      const next = { mode: b.dataset.value };
      if (b.dataset.value === 'solid') next.color = heroStyles[key].color || solidFallback(key);
      applyTextStyle(key, next);
      refreshForm();
    }));
    pop.querySelectorAll('[data-style-animation] button').forEach(b => b.addEventListener('click', () => {
      applyTextStyle(key, { animation: b.dataset.value });
      refreshForm();
    }));
    pop.querySelector('#le-style-solid').addEventListener('input', (e) => {
      applyTextStyle(key, { mode: 'solid', color: e.target.value });
      refreshForm();
    });
    pop.querySelector('#le-style-grad-a').addEventListener('input', (e) => {
      applyTextStyle(key, { mode: 'gradient', gradient_start: e.target.value });
      refreshForm();
    });
    pop.querySelector('#le-style-grad-b').addEventListener('input', (e) => {
      applyTextStyle(key, { mode: 'gradient', gradient_end: e.target.value });
      refreshForm();
    });
    pop.querySelector('[data-style-reset]').addEventListener('click', () => {
      applyTextStyle(key, Object.assign({}, STYLE_DEFAULTS[key], { color: '' }));
      openTextStylePopover(key, anchor);
    });
    pop.querySelector('[data-style-done]').addEventListener('click', closePop);
  }
  STYLE_KEYS.forEach(key => updateStyleTriggers(key));

  /* ── Crop (Cropper.js) ── */
  const cropEl = document.getElementById('le-crop');
  const cropImg = document.getElementById('le-crop-img');
  const cropBusy = document.getElementById('le-crop-busy');
  let cropper = null;
  const isLocalUrl = (u) => u.startsWith('blob:') || u.startsWith('data:') || u.startsWith('/') || u.startsWith(location.origin);
  function openCropper(src) {
    // Remote images (e.g. Unsplash) would taint the crop canvas, so pull them
    // to local storage first via the server, then crop the same-origin copy.
    if (!isLocalUrl(src)) {
      toast('Preparing image for cropping…');
      fetch(IMPORT_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ url: src }) })
        .then(r => r.ok ? r.json() : Promise.reject()).then(d => startCropper(d.url))
        .catch(() => toast('Could not load that remote image — upload the file instead.', true));
      return;
    }
    startCropper(src);
  }
  // The crop box is locked to the hero's real on-screen proportions so the
  // photo fills the section exactly — no stretching, letterboxing or blur.
  function heroAspect() {
    const r = root.getBoundingClientRect();
    return (r.width > 0 && r.height > 0) ? r.width / r.height : 16 / 9;
  }
  function startCropper(src) {
    cropEl.classList.add('open');
    if (cropper) { cropper.destroy(); cropper = null; }
    cropImg.crossOrigin = 'anonymous';
    cropImg.onerror = () => { toast('Could not load that image for cropping.', true); closeCrop(); };
    cropImg.src = src;
    const ar = heroAspect();
    const lbl = document.getElementById('le-crop-ratio');
    if (lbl) lbl.innerHTML = '<i data-lucide="lock"></i> Locked to hero (' + ar.toFixed(2) + ':1)';
    cropper = new Cropper(cropImg, { viewMode: 1, aspectRatio: ar, autoCropArea: 1, background: true, movable: true, zoomable: true, responsive: true });
    refreshIcons();
  }
  function closeCrop() { cropEl.classList.remove('open'); cropBusy.hidden = true; if (cropper) { cropper.destroy(); cropper = null; } }
  document.getElementById('le-crop-close').onclick = closeCrop;
  document.getElementById('le-crop-zoomin').onclick = () => cropper && cropper.zoom(0.1);
  document.getElementById('le-crop-zoomout').onclick = () => cropper && cropper.zoom(-0.1);
  document.getElementById('le-crop-reset').onclick = () => cropper && cropper.reset();
  document.getElementById('le-crop-apply').onclick = () => {
    if (!cropper) return;
    // maxWidth (not width) caps big images but never upscales small ones, so a
    // crop is only ever as sharp as the source — never artificially blurred.
    const canvas = cropper.getCroppedCanvas({ maxWidth: 2600, maxHeight: 1600, imageSmoothingQuality: 'high' });
    if (!canvas) { toast('Could not crop this image.', true); return; }
    if (canvas.width < 1100) toast('Heads up: this crop is low-resolution and may look soft on large screens.', true);
    cropBusy.hidden = false;
    canvas.toBlob((blob) => {
      if (!blob) { cropBusy.hidden = true; toast('This remote image blocks cropping — upload the file instead.', true); return; }
      const fd = new FormData(); fd.append('file', blob, 'hero.jpg');
      fetch(UPLOAD_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: fd })
        .then(r => r.ok ? r.json() : Promise.reject()).then(d => {
          cropBusy.hidden = true;
          if (popTarget) setImage(popTarget, d.url);
          closeCrop(); closePop(); toast('Cropped & applied');
        }).catch(() => { cropBusy.hidden = true; toast('Upload failed (max 8 MB).', true); });
    }, 'image/jpeg', 0.88);
  };

  /* ── Clicks inside the hero ── */
  root.addEventListener('click', (e) => {
    const tool = e.target.closest('[data-le-tool]');
    if (tool) {
      e.preventDefault(); e.stopPropagation();
      const item = tool.closest('[data-ed-item]');
      const kind = tool.dataset.leTool;
      if (kind === 'move') {
        return; // dragging is handled by the pointer events below
      } else if (kind === 'del') {
        if (confirm('Remove this button?')) { item.remove(); markDirty(); toast('Button removed'); }
      } else if (kind === 'cfg') {
        openCfgPopover(item);
      } else {
        const clone = item.cloneNode(true);
        clone.querySelectorAll('.le-item-tools').forEach(t => t.remove());
        clone.removeAttribute('data-le-deco');
        // Nudge a duplicated free-positioned button so it doesn't hide the original.
        if (clone.dataset.heX !== undefined && clone.dataset.heX !== '') {
          const nx = Math.min(95, parseFloat(clone.dataset.heX) + 4);
          const ny = Math.min(95, parseFloat(clone.dataset.heY) + 4);
          clone.dataset.heX = nx.toFixed(2); clone.dataset.heY = ny.toFixed(2);
          clone.style.left = nx.toFixed(2) + '%'; clone.style.top = ny.toFixed(2) + '%';
        }
        item.after(clone);
        decorate(clone.parentElement);
        refreshIcons(); markDirty(); toast('Button duplicated — edit it');
      }
      return;
    }
    const bgEdit = e.target.closest('[data-he-bg-edit]');
    if (bgEdit) { e.preventDefault(); const media = root.querySelector('[data-ed-img]'); if (media) openImagePopover(media, bgEdit); return; }
    const styleOpen = e.target.closest('[data-he-style-open]');
    if (styleOpen) {
      e.preventDefault();
      e.stopPropagation();
      openTextStylePopover(styleOpen.dataset.heStyleOpen, styleOpen);
      return;
    }
    const addRow = e.target.closest('[data-he-add-row]');
    if (addRow) {
      e.preventDefault();
      const row = document.createElement('div'); row.className = 'hero-actions'; row.setAttribute('data-he-row', '');
      const controls = root.querySelector('.he-add-controls');
      controls.parentElement.insertBefore(row, controls);
      const node = newButton(); row.appendChild(node);
      decorate(row); refreshIcons(); markDirty();
      node.scrollIntoView({ behavior: 'smooth', block: 'center' });
      toast('New row added — edit the button');
      return;
    }
    const add = e.target.closest('[data-he-add]');
    if (add) {
      e.preventDefault();
      const rows = root.querySelectorAll('.hero-actions[data-he-row]');
      const lastRow = rows[rows.length - 1];
      if (!lastRow) return;
      const node = newButton();
      lastRow.appendChild(node);
      decorate(lastRow);
      refreshIcons(); markDirty(); toast('Button added — edit it');
      return;
    }
    const img = e.target.closest('[data-ed-img]');
    if (img) { e.preventDefault(); openImagePopover(img); return; }
    const icon = e.target.closest('[data-ed-icon]');
    if (icon) { e.preventDefault(); openIconPopover(icon); return; }
    const a = e.target.closest('a');
    if (a) e.preventDefault();
  });
  root.addEventListener('input', (e) => { if (e.target.isContentEditable) markDirty(); });

  /* ── Save ── */
  document.getElementById('le-save').addEventListener('click', () => {
    closePop();
    statusEl.textContent = 'Saving…'; statusEl.style.color = '#9fb0d0';
    fetch(SAVE_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ data: buildHero() }) })
      .then(r => r.ok ? r.json() : Promise.reject()).then(d => {
        dirty = false; statusEl.textContent = '✓ Saved'; statusEl.style.color = '#9ee6b8';
        toast(d.message || 'Saved'); setTimeout(() => { if (!dirty) statusEl.textContent = ''; }, 2500);
      }).catch(() => { statusEl.textContent = '● Unsaved'; statusEl.style.color = '#ffd9a8'; toast('Save failed. Try again.', true); });
  });

  /* ── Phone preview (full home page, current data) ── */
  const phEl = document.getElementById('le-ph');
  const phFrame = document.getElementById('le-ph-iframe');
  const phSpin = document.getElementById('le-ph-spin');
  const phOpen = document.getElementById('le-ph-open');
  document.getElementById('le-phone-btn').addEventListener('click', () => {
    closePop();
    toast('Building preview…');
    fetch(PREVIEW_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ data: buildHero() }) })
      .then(r => r.ok ? r.json() : Promise.reject()).then(d => {
        const url = d.url + (d.url.indexOf('?') === -1 ? '?' : '&') + 't=' + Date.now();
        phSpin.style.display = 'flex';
        phFrame.onload = () => { phSpin.style.display = 'none'; };
        phFrame.src = url;
        phOpen.href = d.url;
        phEl.classList.add('open');
      }).catch(() => toast('Could not build preview.', true));
  });
  function closePhone() { phEl.classList.remove('open'); phFrame.src = 'about:blank'; }
  document.getElementById('le-ph-close').onclick = closePhone;
  phEl.addEventListener('click', (e) => { if (e.target === phEl) closePhone(); });

  window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
})();
</script>
