{{-- Career Library live-editor chrome. Included by career-library/layout.blade.php
     only when CareerLibraryCmsController::live() renders the REAL career page
     with $live = true. Follows the same hand-rolled engine as the Home-Hero and
     About live editors (data-ed / data-ed-item / data-ed-rep conventions), with
     Career-Library-specific popovers: stats (median + demand), video link,
     thumbnail image+crop, tile (icon/colours/flags) and a Page-SEO modal. --}}
@php
    // Blade's @json splits its argument on commas, so everything non-trivial is
    // precomputed here and emitted as a bare variable.
    $chromeSaveUrl = route('admin.career-library.live.save', $career['slug']);
    $chromeLiveUrl = route('admin.career-library.live', $career['slug']);
    $chromeVariantAddUrl = route('admin.career-library.variant', $career['slug']);
    $chromeVariantDelUrl = route('admin.career-library.variant.delete', $career['slug']);
    $chromeUploadUrl = route('admin.career-library.upload');
    $chromeImportUrl = route('admin.career-library.import');
    $chromeBackUrl = route('admin.career-library.index');
    $chromeCsrf = csrf_token();

    $chromeStats = [
        'median' => str_replace('?', '', $data['stats']['salary']['median']),
        'demandLevel' => $data['stats']['demandLevel'] !== '' ? $data['stats']['demandLevel'] : 'High',
    ];
    $chromeSeo = $data['seo'];
    $chromeTile = [
        'title' => $career['title'],
        'iconType' => $career['iconType'],
        'bg' => $career['bg'],
        'text' => $career['text'],
        'trending' => (bool) $career['trending'],
        'visible' => (bool) $career['visible'],
    ];
    $chromeTileIcons = \App\Support\CareerLibraryIcons::MAP;
    $chromeIconTypes = $iconTypes;
    $chromeVariants = $variants;
    $chromeVariant = $variant;
    $chromeCountries = $countries;
    $chromeLanguages = $languages;
@endphp
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<style>
  :root { --le-accent: #666cff; --le-bar: #0e1f3d; --le-danger: #c0392b; }

  body.cms-editing { padding-bottom: 84px; }
  /* Calm the page down while editing */
  .cms-editing .animate-shake-card { animation: none !important; }
  .cms-editing .animate-bounce { animation: none !important; }
  .cms-editing .animate-fade-in-up { animation: none !important; opacity: 1 !important; }
  /* Accordions always open so descriptions are editable without toggling */
  .cms-editing .accordion-content { max-height: none !important; opacity: 1 !important; }
  .cms-editing .chevron-icon { display: none; }
  /* The sections that aren't career data stay visible but clearly locked */
  .cms-editing .next-steps { opacity: .45; filter: grayscale(.5); }
  .cms-editing .next-steps::before { content: "Links & year come from Career Library settings"; display: block; text-align: center;
    font: 700 .72rem/1.4 Inter, sans-serif; letter-spacing: .08em; text-transform: uppercase; color: #93a0b4; margin-bottom: 8px; }

  /* Demand tile: a real (popover-backed) field, so flag it as clickable. */
  .cms-editing [data-cl-demand-open] { cursor: pointer; transition: outline-color .12s; outline: 1.5px dashed transparent; outline-offset: 2px; }
  .cms-editing [data-cl-demand-open]:hover { outline-color: rgba(102,108,255,.75); }

  /* ── Inline editables ── */
  .cms-editing [data-ed] { transition: outline-color .12s, background .12s; border-radius: 3px; min-height: 1em; }
  .cms-editing [data-ed]:hover { outline: 1.5px dashed rgba(102,108,255,.75); outline-offset: 2px; cursor: text; }
  .cms-editing [data-ed]:focus { outline: 2px solid var(--le-accent); outline-offset: 2px; background: rgba(102,108,255,.08); }
  .cms-editing header [data-ed]:hover { outline-color: rgba(255,255,255,.8); }
  .cms-editing header [data-ed]:focus { background: rgba(255,255,255,.12); }
  .cms-editing [data-ed]:empty { display: inline-block; min-width: 60px; outline: 1.5px dashed rgba(102,108,255,.55); }
  .cms-editing [data-ed]:empty::before { content: "edit…"; color: #98a2b3; font-size: .85em; font-style: normal; }

  /* ── Repeater items + tools ── */
  .cms-editing [data-ed-item] { position: relative; }
  .le-item-tools { position: absolute; top: -13px; right: -6px; z-index: 55; display: flex; gap: 4px; opacity: 0; transition: opacity .12s; }
  [data-ed-item]:hover > .le-item-tools { opacity: 1; }
  .le-item-tools button { width: 26px; height: 26px; border: 0; border-radius: 7px; cursor: pointer; background: rgba(14,31,61,.95);
    color: #fff; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(8,20,40,.3); padding: 0; }
  .le-item-tools button:hover { background: var(--le-accent); }
  .le-item-tools button.le-item-del:hover { background: var(--le-danger); }
  .le-item-tools svg { width: 14px; height: 14px; }
  .le-item-move { cursor: grab; }
  .le-item-move:active { cursor: grabbing; }
  .cms-editing [data-ed-item].le-item-dragging { opacity: .45; }

  /* Editable video thumbnails */
  .cms-editing [data-ed-img] { cursor: pointer; }
  .cms-editing [data-ed-img]:hover { outline: 2px dashed var(--le-accent); outline-offset: 2px; }

  /* ── Add buttons ── */
  .cl-add-row { list-style: none; }
  .cl-add-btn { display: inline-flex; align-items: center; gap: 6px; border: 1.5px dashed #b6bdff; background: rgba(102,108,255,.06);
    color: #5256e0; border-radius: 10px; padding: 8px 14px; font: 700 .82rem Inter, sans-serif; cursor: pointer; }
  .cl-add-btn:hover { background: rgba(102,108,255,.14); border-color: var(--le-accent); }
  .divide-y > .cl-add-row { padding: 10px 24px; }
  .cl-add-chip { width: 24px; height: 24px; border: 1.5px dashed #b6bdff; background: rgba(102,108,255,.06); color: #5256e0;
    border-radius: 8px; font: 800 .8rem/1 Inter, sans-serif; cursor: pointer; padding: 0; }
  .cl-add-chip:hover { background: rgba(102,108,255,.14); }
  .cl-stats-gear { width: 24px; height: 24px; border: 1px solid #e2e6ef; background: #fff; border-radius: 7px; cursor: pointer;
    font-size: .8rem; line-height: 1; padding: 0; color: #5b6472; margin-left: 4px; }
  .cl-stats-gear:hover { border-color: var(--le-accent); color: var(--le-accent); }

  /* ── Bottom toolbar ── */
  .le-bottombar { position: fixed; left: 0; right: 0; bottom: 0; height: 62px; z-index: 9000; display: flex; align-items: center;
    gap: 12px; padding: 0 16px; background: var(--le-bar); color: #fff; font-family: Inter, system-ui, sans-serif;
    box-shadow: 0 -6px 22px rgba(8,20,40,.34); }
  .le-bottombar a, .le-bottombar button { font-family: inherit; }
  .le-bottombar .le-home { display: inline-flex; align-items: center; gap: 8px; color: #cdd9ef; text-decoration: none; font-weight: 700; font-size: .88rem; white-space: nowrap; }
  .le-bottombar .le-home:hover { color: #fff; }
  .le-bottombar .le-title { font-weight: 800; display: flex; align-items: center; gap: 9px; min-width: 0; }
  .le-bottombar .le-title b { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px; }
  .le-bottombar .le-badge { font-size: .62rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; background: var(--le-accent); color: #fff; padding: 3px 8px; border-radius: 999px; flex-shrink: 0; }
  .le-bottombar .le-sp { flex: 1; }
  .le-bottombar .le-status { font-size: .8rem; font-weight: 700; color: #9fb0d0; min-width: 74px; text-align: right; }
  .le-tbtn { display: inline-flex; align-items: center; gap: 7px; border: 1px solid rgba(255,255,255,.18);
    background: rgba(255,255,255,.06); color: #fff; border-radius: 10px; padding: 9px 13px; font-weight: 700; font-size: .85rem; cursor: pointer; text-decoration: none; white-space: nowrap; }
  .le-tbtn:hover { background: rgba(255,255,255,.14); }
  .le-tbtn.primary { background: var(--le-accent); border-color: var(--le-accent); }
  .le-tbtn.primary:hover { background: #5256e0; }
  .le-tbtn i { width: 16px; height: 16px; }

  /* Variant dropdown (opens upward) */
  .le-var-wrap { position: relative; }
  .le-var-menu { position: absolute; right: 0; bottom: 54px; min-width: 280px; background: #fff; color: #14253e; border-radius: 12px;
    box-shadow: 0 -10px 44px rgba(8,20,40,.35); padding: 8px; display: none; font-family: Inter, sans-serif; }
  .le-var-wrap.open .le-var-menu { display: block; }
  .le-var-item { display: flex; align-items: center; gap: 8px; width: 100%; text-align: left; padding: 9px 11px; border-radius: 8px;
    border: 0; background: none; cursor: pointer; font: 700 .86rem Inter, sans-serif; color: #14253e; }
  .le-var-item:hover { background: #f3f6f8; }
  .le-var-item.on { background: #ebecff; color: #5256e0; }
  .le-var-sep { height: 1px; background: #eef1f4; margin: 7px 4px; }
  .le-var-add { padding: 4px 11px 8px; display: none; }
  .le-var-add.open { display: block; }
  .le-var-add select { width: 100%; margin-bottom: 7px; padding: 8px 10px; border: 1px solid #e5e8ee; border-radius: 8px; font: 600 .84rem Inter, sans-serif; }
  .le-var-add-row { display: flex; gap: 7px; }
  .le-var-del { color: var(--le-danger) !important; }

  /* ── Popover ── */
  .le-pop { position: fixed; z-index: 9500; width: 330px; max-width: calc(100vw - 24px); background: #fff; color: #14253e;
    border-radius: 13px; box-shadow: 0 24px 60px rgba(8,20,40,.4); padding: 15px; font-family: Inter, sans-serif; }
  .le-pop h4 { margin: 0 0 11px; font-size: .95rem; font-weight: 800; display: flex; align-items: center; gap: 7px; }
  .le-pop h4 i { width: 16px; height: 16px; color: var(--le-accent); }
  .le-pop label { display: block; font-size: .74rem; font-weight: 800; color: #6a7686; margin: 0 0 5px; text-transform: uppercase; letter-spacing: .05em; }
  .le-pop input[type=text], .le-pop select { width: 100%; box-sizing: border-box; padding: 10px 11px; border: 1px solid #e5e8ee; border-radius: 9px;
    font-family: inherit; font-size: .9rem; margin-bottom: 11px; background: #fff; }
  .le-pop input:focus, .le-pop select:focus { outline: none; border-color: var(--le-accent); box-shadow: 0 0 0 3px rgba(102,108,255,.18); }
  .le-pop .le-pop-row { display: flex; gap: 8px; }
  .le-pop .le-check { display: flex; align-items: center; gap: 8px; font: 700 .86rem Inter, sans-serif; color: #14253e;
    text-transform: none; letter-spacing: 0; margin-bottom: 10px; cursor: pointer; }
  .le-pop .le-check input { width: 16px; height: 16px; accent-color: var(--le-accent); cursor: pointer; margin: 0; }
  .le-pp-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; flex: 1; border: 1px solid #e5e8ee;
    background: #fff; border-radius: 9px; padding: 9px; font-weight: 700; font-size: .85rem; cursor: pointer; color: #14253e; font-family: inherit; }
  .le-pp-btn:hover { border-color: var(--le-accent); color: var(--le-accent); }
  .le-pp-btn.primary { background: var(--le-accent); border-color: var(--le-accent); color: #fff; }
  .le-pp-btn.primary:hover { background: #5256e0; color: #fff; }
  .le-pp-btn svg { width: 15px; height: 15px; }
  .le-pop-prev { display: flex; align-items: center; justify-content: center; height: 58px; border: 1px dashed #e5e8ee; border-radius: 9px; margin-bottom: 11px; color: var(--le-accent); overflow: hidden; background: #fafbfc; }
  .le-pop-prev svg { width: 26px; height: 26px; }
  .le-pop-prev img { max-height: 56px; border-radius: 7px; }

  /* ── Modals (SEO + crop) ── */
  .le-modal { position: fixed; inset: 0; z-index: 9600; background: rgba(8,18,33,.82); display: none; align-items: center; justify-content: center; padding: 24px; font-family: Inter, sans-serif; }
  .le-modal.open { display: flex; }
  .le-modal-card { width: 720px; max-width: 96vw; max-height: 90vh; display: flex; flex-direction: column; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 40px 90px rgba(0,0,0,.5); color: #14253e; }
  .le-modal-head { display: flex; align-items: center; gap: 9px; padding: 14px 18px; font-weight: 800; border-bottom: 1px solid #eef1f4; }
  .le-modal-head i { width: 18px; height: 18px; color: var(--le-accent); }
  .le-modal-head .le-sp { flex: 1; }
  .le-modal-head button { border: 0; background: none; cursor: pointer; color: #6a7686; display: inline-flex; }
  .le-modal-body { padding: 16px 18px; overflow-y: auto; }
  .le-modal-body label { display: block; font-size: .74rem; font-weight: 800; color: #6a7686; margin: 0 0 5px; text-transform: uppercase; letter-spacing: .05em; }
  .le-modal-body input[type=text], .le-modal-body textarea { width: 100%; box-sizing: border-box; padding: 10px 11px; border: 1px solid #e5e8ee;
    border-radius: 9px; font-family: inherit; font-size: .9rem; margin-bottom: 13px; resize: vertical; }
  .le-modal-body input:focus, .le-modal-body textarea:focus { outline: none; border-color: var(--le-accent); box-shadow: 0 0 0 3px rgba(102,108,255,.18); }
  .le-faq-row { display: grid; grid-template-columns: 1fr auto; gap: 9px; align-items: start; padding: 11px; border: 1px solid #eef1f4; border-radius: 11px; background: #fafbfc; margin-bottom: 9px; }
  .le-faq-row input, .le-faq-row textarea { margin-bottom: 8px !important; background: #fff; }
  .le-faq-del { width: 30px; height: 30px; border: 1px solid #e5e8ee; background: #fff; border-radius: 8px; cursor: pointer; color: #6a7686; }
  .le-faq-del:hover { border-color: var(--le-danger); color: var(--le-danger); }
  .le-modal-foot { display: flex; align-items: center; gap: 10px; padding: 13px 18px; border-top: 1px solid #eef1f4; }
  .le-modal-foot .le-sp { flex: 1; }

  /* Crop modal internals */
  .le-crop-stage { background: #11202f; max-height: 56vh; }
  .le-crop-stage img { max-width: 100%; display: block; }
  .le-ar-locked { display: inline-flex; align-items: center; gap: 7px; font: 700 .8rem Inter, sans-serif; color: #5256e0;
    background: #ebecff; border: 1px solid #cdd0ff; border-radius: 8px; padding: 7px 12px; }
  .le-ar-locked i { width: 14px; height: 14px; }
  .le-zoom { display: inline-flex; gap: 5px; }
  .le-zoom button { width: 34px; height: 34px; border: 1px solid #e5e8ee; background: #fff; border-radius: 8px; cursor: pointer; color: #14253e; display: inline-flex; align-items: center; justify-content: center; }
  .le-go { display: inline-flex; align-items: center; gap: 7px; border: 0; background: var(--le-accent); color: #fff; border-radius: 9px; padding: 10px 16px; font: 800 .88rem Inter, sans-serif; cursor: pointer; }
  .le-go:hover { background: #5256e0; }
  .le-go i { width: 16px; height: 16px; }

  /* Tile icon preview */
  .le-tile-prev { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
  .le-tile-swatch { width: 52px; height: 52px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #eef1f4; }
  .le-tile-swatch svg { width: 24px; height: 24px; }

  /* ── Toasts ── */
  .le-toasts { position: fixed; bottom: 76px; left: 50%; transform: translateX(-50%); z-index: 9900; display: flex; flex-direction: column; gap: 9px; align-items: center; }
  .le-toast { background: #14253e; color: #fff; font: 700 .88rem Inter, sans-serif; padding: 11px 18px; border-radius: 11px; box-shadow: 0 14px 34px rgba(8,20,40,.32); opacity: 0; transform: translateY(10px); transition: opacity .25s, transform .25s; }
  .le-toast.show { opacity: 1; transform: none; }
  .le-toast.err { background: var(--le-danger); }

  @media (max-width: 900px) { .le-bottombar .le-home span, .le-bottombar .le-title b { display: none; } }
</style>

<div class="le-bottombar">
  <a class="le-home" href="{{ $chromeBackUrl }}" title="Back to the career list"><i data-lucide="arrow-left"></i> <span>Career Library</span></a>
  <span class="le-title"><b>{{ $career['title'] }}</b> <span class="le-badge">Live Edit</span></span>
  <span class="le-sp"></span>
  <span class="le-status" id="le-status"></span>

  <div class="le-var-wrap" id="le-var-wrap">
    <button type="button" class="le-tbtn" id="le-var-btn"><i data-lucide="languages"></i> {{ str_replace('|', ' · ', $chromeVariant) }}</button>
    <div class="le-var-menu">
      @foreach ($chromeVariants as $v)
        <button type="button" class="le-var-item {{ $v === $chromeVariant ? 'on' : '' }}" data-goto-variant="{{ $v }}">
          <i data-lucide="{{ $v === $chromeVariant ? 'check' : 'globe' }}"></i> {{ str_replace('|', ' · ', $v) }}
        </button>
      @endforeach
      <div class="le-var-sep"></div>
      <button type="button" class="le-var-item" id="le-var-add-toggle"><i data-lucide="plus"></i> Add variant (copies current)</button>
      <div class="le-var-add" id="le-var-add">
        <select id="le-var-country">
          @foreach ($chromeCountries as $c)<option @selected($c === 'India')>{{ $c }}</option>@endforeach
        </select>
        <select id="le-var-language">
          @foreach ($chromeLanguages as $l)<option>{{ $l }}</option>@endforeach
        </select>
        <div class="le-var-add-row">
          <button type="button" class="le-pp-btn primary" id="le-var-add-go">Add variant</button>
        </div>
      </div>
      @if (count($chromeVariants) > 1)
        <div class="le-var-sep"></div>
        <button type="button" class="le-var-item le-var-del" id="le-var-del"><i data-lucide="trash-2"></i> Delete this variant</button>
      @endif
    </div>
  </div>

  <button type="button" class="le-tbtn" id="le-tile-btn"><i data-lucide="app-window"></i> Tile</button>
  <button type="button" class="le-tbtn" id="le-seo-btn"><i data-lucide="search"></i> Page SEO</button>
  <a class="le-tbtn" href="{{ $publicUrl }}" target="_blank" rel="noopener"><i data-lucide="external-link"></i> View live page</a>
  <button class="le-tbtn primary" type="button" id="le-save"><i data-lucide="save"></i> Save</button>
</div>

<div class="le-toasts" id="le-toasts"></div>
<div class="le-pop" id="le-pop" hidden></div>

{{-- Crop modal (video thumbnails, locked to the card's 16:9) --}}
<div class="le-modal" id="le-crop">
  <div class="le-modal-card" style="width: 760px;">
    <div class="le-modal-head"><i data-lucide="crop"></i> Crop the thumbnail <span class="le-sp"></span><button type="button" id="le-crop-close" title="Cancel"><i data-lucide="x"></i></button></div>
    <div class="le-crop-stage"><img id="le-crop-img" alt=""></div>
    <div class="le-modal-foot">
      <span class="le-ar-locked"><i data-lucide="lock"></i> Locked to the video card (16:9)</span>
      <span class="le-sp"></span>
      <div class="le-zoom">
        <button type="button" id="le-crop-zoomout" title="Zoom out"><i data-lucide="minus"></i></button>
        <button type="button" id="le-crop-zoomin" title="Zoom in"><i data-lucide="plus"></i></button>
        <button type="button" id="le-crop-reset" title="Reset"><i data-lucide="rotate-ccw"></i></button>
      </div>
      <button type="button" class="le-go" id="le-crop-apply"><i data-lucide="check"></i> Apply crop</button>
    </div>
  </div>
</div>

{{-- Page SEO modal --}}
<div class="le-modal" id="le-seo">
  <div class="le-modal-card">
    <div class="le-modal-head"><i data-lucide="search"></i> Page SEO <span class="le-sp"></span><button type="button" id="le-seo-close" title="Close"><i data-lucide="x"></i></button></div>
    <div class="le-modal-body">
      <label>Meta title</label>
      <input type="text" id="le-seo-title" maxlength="200">
      <label>Meta description</label>
      <textarea id="le-seo-desc" rows="2" maxlength="400"></textarea>
      <label>Meta keywords (one per line)</label>
      <textarea id="le-seo-keywords" rows="3"></textarea>
      <label>FAQs (kept with the report)</label>
      <div id="le-seo-faqs"></div>
      <button type="button" class="cl-add-btn" id="le-seo-add-faq">+ Add FAQ</button>
    </div>
    <div class="le-modal-foot">
      <span style="color:#8a93a3; font: 600 .8rem Inter, sans-serif;">Saved together with the career when you press Save.</span>
      <span class="le-sp"></span>
      <button type="button" class="le-go" id="le-seo-done"><i data-lucide="check"></i> Done</button>
    </div>
  </div>
</div>

<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script>
(function () {
  const root = document.getElementById('app-container');
  if (!root) return;

  const CSRF = @json($chromeCsrf);
  const SAVE_URL = @json($chromeSaveUrl);
  const LIVE_URL = @json($chromeLiveUrl);
  const VARIANT_ADD_URL = @json($chromeVariantAddUrl);
  const VARIANT_DEL_URL = @json($chromeVariantDelUrl);
  const UPLOAD_URL = @json($chromeUploadUrl);
  const IMPORT_URL = @json($chromeImportUrl);
  const VARIANT = @json($chromeVariant);
  const TILE_ICONS = @json($chromeTileIcons);
  const ICON_TYPES = @json($chromeIconTypes);

  // Non-inline state (edited via popovers/modals, grafted into the payload on save)
  const statsExtra = @json($chromeStats);
  const seoState = @json($chromeSeo);
  const tileState = @json($chromeTile);

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

  /* ── Decoration: contenteditable + per-item tools ── */
  function decorate(scope) {
    scope.querySelectorAll('[data-ed]').forEach(el => el.setAttribute('contenteditable', 'true'));
    scope.querySelectorAll('[data-ed-item]').forEach(item => {
      if (item.dataset.leDeco === '1') return;
      item.dataset.leDeco = '1';
      const tools = document.createElement('span');
      tools.className = 'le-item-tools';
      tools.setAttribute('contenteditable', 'false');
      tools.innerHTML =
        '<button type="button" class="le-item-move" data-le-tool="move" title="Drag to reorder"><i data-lucide="move"></i></button>'
        + (item.hasAttribute('data-cl-video') ? '<button type="button" data-le-tool="link" title="Video link / upload"><i data-lucide="link"></i></button>' : '')
        + '<button type="button" data-le-tool="dup" title="Duplicate"><i data-lucide="copy"></i></button>'
        + '<button type="button" class="le-item-del" data-le-tool="del" title="Remove"><i data-lucide="x"></i></button>';
      item.appendChild(tools);
    });
  }
  decorate(root);
  refreshIcons();

  /* ── Serialization (DOM → report object) ── */
  function readExtra(scope) {
    const s = scope.querySelector(':scope > script.le-extra');
    if (!s) return {};
    try { return JSON.parse(s.textContent || '{}') || {}; } catch (e) { return {}; }
  }
  function writeExtra(scope, key, val) {
    let s = scope.querySelector(':scope > script.le-extra');
    let obj = {};
    if (s) { try { obj = JSON.parse(s.textContent || '{}') || {}; } catch (e) {} }
    obj[key] = val;
    if (!s) { s = document.createElement('script'); s.type = 'application/json'; s.className = 'le-extra'; scope.insertBefore(s, scope.firstChild); }
    s.textContent = JSON.stringify(obj);
  }
  function ownerScope(el) { return el.closest('[data-ed-item]') || root; }
  function readVal(el) {
    if (el.hasAttribute('data-ed-img')) return el.getAttribute('data-ed-imgval') || '';
    return (el.textContent || '').replace(/ /g, ' ').replace(/\s+/g, ' ').trim();
  }
  function serializeScope(scope) {
    const data = Object.assign({}, readExtra(scope));
    scope.querySelectorAll('[data-ed],[data-ed-img]').forEach(el => {
      if (ownerScope(el) !== scope) return;
      const key = el.getAttribute('data-ed') || el.getAttribute('data-ed-img');
      data[key] = readVal(el);
    });
    scope.querySelectorAll('[data-ed-rep]').forEach(rep => {
      if ((rep.closest('[data-ed-item]') || root) !== scope) return;
      const key = rep.getAttribute('data-ed-rep');
      const items = [...rep.querySelectorAll('[data-ed-item]')].filter(it => it.closest('[data-ed-rep]') === rep);
      data[key] = items.map(serializeScope);
    });
    return data;
  }
  const texts = list => (list || []).map(o => o.text || '').filter(s => s !== '');

  function buildReport() {
    const d = serializeScope(root);
    return {
      seo: {
        title: seoState.title || '',
        description: seoState.description || '',
        keywords: seoState.keywords || [],
        faqs: seoState.faqs || [],
      },
      title: d.title || '',
      introduction: d.introduction || '',
      whoShouldPursue: texts(d.whoShouldPursue),
      workNature: {
        description: d.workNature_description || '',
        examples: texts(d.workNature_examples),
      },
      eligibility: texts(d.eligibility),
      stats: {
        salary: {
          entry: d.salary_entry || '',
          median: statsExtra.median || '',
          senior: d.salary_senior || '',
          currency: d.salary_currency || 'INR',
        },
        jobGrowth: d.jobGrowth || '',
        demandLevel: statsExtra.demandLevel || 'High',
        topIndustries: texts(d.topIndustries),
        futureOutlook: d.futureOutlook || '',
      },
      pathways: (d.pathways || []).map(p => ({ name: p.name || '', steps: p.steps || [] })),
      conventionalOptions: d.conventionalOptions || [],
      newAgeOptions: d.newAgeOptions || [],
      aiRelatedOptions: d.aiRelatedOptions || [],
      videoRecommendations: (d.videoRecommendations || []).map(v => ({
        title: v.title || '', channelName: v.channelName || '', description: v.description || '',
        url: v.url || '', thumbnail: v.thumbnail || '',
      })),
    };
  }

  /* ── Page-specific visual sync ── */

  // Demand level drives the sidebar outlook bar + the Demand tile.
  const DEMAND_VIEW = {
    High:   { bar: 'bg-emerald-500', text: 'text-emerald-600', width: '90%' },
    Medium: { bar: 'bg-amber-500',   text: 'text-amber-600',   width: '60%' },
    Low:    { bar: 'bg-rose-500',    text: 'text-rose-600',    width: '30%' },
  };
  function applyDemand(level) {
    const view = DEMAND_VIEW[level] || DEMAND_VIEW.High;
    document.querySelectorAll('[data-cl-demand]').forEach(el => el.textContent = level);
    const bar = document.querySelector('[data-cl-outlook-bar]');
    if (bar) {
      bar.classList.remove('bg-emerald-500', 'bg-amber-500', 'bg-rose-500');
      bar.classList.add(view.bar);
      bar.style.width = view.width;
    }
    const label = document.querySelector('[data-cl-outlook-label]');
    if (label) {
      label.classList.remove('text-emerald-600', 'text-amber-600', 'text-rose-600');
      label.classList.add(view.text);
      label.textContent = level;
    }
  }

  // Industry chips keep the 6-colour cycle as chips are added/removed/reordered.
  const CHIP_CLASSES = [
    ['bg-blue-50', 'text-blue-700', 'border-blue-200'],
    ['bg-emerald-50', 'text-emerald-700', 'border-emerald-200'],
    ['bg-purple-50', 'text-purple-700', 'border-purple-200'],
    ['bg-orange-50', 'text-orange-700', 'border-orange-200'],
    ['bg-pink-50', 'text-pink-700', 'border-pink-200'],
    ['bg-cyan-50', 'text-cyan-700', 'border-cyan-200'],
  ];
  function recolorChips() {
    const all = CHIP_CLASSES.flat();
    document.querySelectorAll('[data-cl-chip]').forEach((chip, i) => {
      chip.classList.remove(...all);
      chip.classList.add(...CHIP_CLASSES[i % CHIP_CLASSES.length]);
    });
  }

  // Pathway route numbers (1, 2, 3…) after add/remove/reorder.
  function renumberPathways() {
    const rep = root.querySelector('[data-ed-rep="pathways"]');
    if (!rep) return;
    [...rep.querySelectorAll('[data-ed-item]')]
      .filter(it => it.closest('[data-ed-rep]') === rep)
      .forEach((it, i) => {
        const num = it.querySelector('[data-cl-pathnum]');
        if (num) num.textContent = i + 1;
      });
  }

  // Mirrors: nodes that display an editable value elsewhere on the page.
  function syncMirrors(el) {
    const key = el.getAttribute('data-ed');
    if (!key) return;
    document.querySelectorAll('[data-ed-mirror="' + key + '"]').forEach(m => { m.textContent = readVal(el); });
  }

  /* ── Popover plumbing ── */
  const pop = document.getElementById('le-pop');
  let popTarget = null;
  function positionPop(anchorRect) {
    pop.hidden = false;
    const w = pop.offsetWidth, h = pop.offsetHeight;
    let left = anchorRect.left, top = anchorRect.bottom + 8;
    if (left + w > innerWidth - 12) left = innerWidth - w - 12;
    if (left < 12) left = 12;
    if (top + h > innerHeight - 74) top = Math.max(12, anchorRect.top - h - 8);
    pop.style.left = left + 'px';
    pop.style.top = top + 'px';
  }
  function closePop() { pop.hidden = true; popTarget = null; pop.innerHTML = ''; }
  const escAttr = s => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');

  function uploadFile(fileOrBlob, name) {
    const fd = new FormData(); fd.append('file', fileOrBlob, name || 'upload.jpg');
    return fetch(UPLOAD_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: fd })
      .then(r => r.ok ? r.json() : Promise.reject()).then(d => d.url);
  }

  /* ── Thumbnail image popover + crop ── */
  function setImage(el, url) {
    el.setAttribute('data-ed-imgval', url);
    el.style.backgroundImage = url ? "url('" + url.replace(/'/g, "\\'") + "')" : 'none';
    if (url) { el.style.backgroundSize = 'cover'; el.style.backgroundPosition = 'center'; }
    const icon = el.querySelector('span');
    if (icon) {
      icon.classList.toggle('text-slate-400', !url);
      icon.classList.toggle('text-white/90', !!url);
      icon.classList.toggle('drop-shadow', !!url);
    }
    markDirty();
  }
  function openImagePopover(el) {
    popTarget = el;
    const cur = el.getAttribute('data-ed-imgval') || '';
    pop.innerHTML =
      '<h4><i data-lucide="image"></i> Video thumbnail</h4>' +
      '<div class="le-pop-prev">' + (cur ? '<img src="' + escAttr(cur) + '" alt="">' : '<i data-lucide="image"></i>') + '</div>' +
      '<label>Image URL</label>' +
      '<input type="text" id="le-img-url" value="' + escAttr(cur) + '" placeholder="https://… or upload a file">' +
      '<div class="le-pop-row">' +
        '<label class="le-pp-btn primary"><i data-lucide="upload"></i> Upload &amp; crop<input type="file" accept="image/*" id="le-img-file" hidden></label>' +
        '<button type="button" class="le-pp-btn" id="le-img-crop"><i data-lucide="crop"></i> Crop current</button>' +
        '<button type="button" class="le-pp-btn" id="le-img-apply"><i data-lucide="link"></i> Use URL</button>' +
      '</div>' +
      '<div class="le-pop-row" style="margin-top:8px;"><button type="button" class="le-pp-btn" id="le-img-clear"><i data-lucide="eraser"></i> Remove thumbnail</button></div>';
    refreshIcons();
    positionPop(el.getBoundingClientRect());
    const urlInput = pop.querySelector('#le-img-url');
    pop.querySelector('#le-img-apply').onclick = () => { setImage(popTarget, urlInput.value.trim()); closePop(); toast('Thumbnail updated'); };
    pop.querySelector('#le-img-clear').onclick = () => { setImage(popTarget, ''); closePop(); toast('Thumbnail removed'); };
    pop.querySelector('#le-img-crop').onclick = () => {
      const src = (urlInput.value.trim() || cur);
      if (!src) { toast('Add or upload an image first.', true); return; }
      openCropper(src);
    };
    pop.querySelector('#le-img-file').onchange = (e) => {
      const f = e.target.files[0]; if (!f) return;
      openCropper(URL.createObjectURL(f));
    };
  }

  const cropEl = document.getElementById('le-crop');
  const cropImg = document.getElementById('le-crop-img');
  let cropper = null, cropTarget = null;
  const isLocalUrl = u => u.startsWith('blob:') || u.startsWith('data:') || u.startsWith('/') || u.startsWith(location.origin);
  function openCropper(src) {
    cropTarget = popTarget; // popover may close while the crop modal is open
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
    cropImg.src = src;
    if (cropper) { cropper.destroy(); cropper = null; }
    cropper = new Cropper(cropImg, { aspectRatio: 16 / 9, viewMode: 1, autoCropArea: 1, background: false });
  }
  function closeCrop() {
    cropEl.classList.remove('open');
    if (cropper) { cropper.destroy(); cropper = null; }
    cropImg.src = '';
  }
  document.getElementById('le-crop-close').onclick = closeCrop;
  document.getElementById('le-crop-zoomin').onclick = () => cropper && cropper.zoom(0.1);
  document.getElementById('le-crop-zoomout').onclick = () => cropper && cropper.zoom(-0.1);
  document.getElementById('le-crop-reset').onclick = () => cropper && cropper.reset();
  document.getElementById('le-crop-apply').onclick = () => {
    if (!cropper) return;
    const canvas = cropper.getCroppedCanvas({ maxWidth: 1600, maxHeight: 900, imageSmoothingQuality: 'high' });
    if (!canvas) { toast('Could not crop this image.', true); return; }
    let dataUrl;
    try { dataUrl = canvas.toDataURL('image/jpeg', 0.88); }
    catch (e) { toast('This remote image blocks cropping — upload the file instead.', true); return; }
    if (cropTarget) setImage(cropTarget, dataUrl);
    closeCrop(); closePop(); toast('Cropped — stored when you press Save');
  };

  /* ── Video link popover (URL or uploaded video file) ── */
  function openLinkPopover(item, anchor) {
    popTarget = item;
    const cur = readExtra(item).url || '';
    pop.innerHTML =
      '<h4><i data-lucide="link"></i> Video link</h4>' +
      '<label>Link (YouTube, Vimeo, any URL)</label>' +
      '<input type="text" id="le-link-url" value="' + escAttr(cur) + '" placeholder="https://youtube.com/watch?v=…">' +
      '<div class="le-pop-row">' +
        '<label class="le-pp-btn"><i data-lucide="upload"></i> Upload video file<input type="file" accept="video/mp4,video/webm,video/quicktime,video/ogg" id="le-link-file" hidden></label>' +
        '<button type="button" class="le-pp-btn primary" id="le-link-apply"><i data-lucide="check"></i> Apply</button>' +
      '</div>' +
      '<p style="margin:10px 0 0; font: 600 .76rem Inter, sans-serif; color:#8a93a3;">Leave empty to fall back to a YouTube search for the video title.</p>';
    refreshIcons();
    positionPop((anchor || item).getBoundingClientRect());
    const input = pop.querySelector('#le-link-url');
    input.focus();
    pop.querySelector('#le-link-apply').onclick = () => {
      writeExtra(popTarget, 'url', input.value.trim());
      markDirty(); closePop(); toast('Video link updated');
    };
    pop.querySelector('#le-link-file').onchange = (e) => {
      const f = e.target.files[0]; if (!f) return;
      toast('Uploading video…');
      uploadFile(f, f.name).then(url => { input.value = url; toast('Uploaded — press Apply'); })
        .catch(() => toast('Upload failed (file too large?).', true));
    };
  }

  /* ── Stats popover (median salary + demand level) ──
     Opened from the ⚙ gear in the Market Snapshot header AND from a direct
     click on the Demand tile, so demand feels editable in place. --*/
  function openStatsPopover(anchor) {
    pop.innerHTML =
      '<h4><i data-lucide="bar-chart-3"></i> Market snapshot</h4>' +
      '<label>Median salary (not shown on the page)</label>' +
      '<input type="text" id="le-stats-median" value="' + escAttr(statsExtra.median) + '" placeholder="e.g. ₹9-15 LPA">' +
      '<label>Demand level</label>' +
      '<select id="le-stats-demand">' +
        ['High', 'Medium', 'Low'].map(l => '<option' + (statsExtra.demandLevel === l ? ' selected' : '') + '>' + l + '</option>').join('') +
      '</select>' +
      '<div class="le-pop-row"><button type="button" class="le-pp-btn primary" id="le-stats-done"><i data-lucide="check"></i> Done</button></div>';
    refreshIcons();
    positionPop(anchor.getBoundingClientRect());
    pop.querySelector('#le-stats-median').addEventListener('input', ev => { statsExtra.median = ev.target.value; markDirty(); });
    pop.querySelector('#le-stats-demand').addEventListener('change', ev => {
      statsExtra.demandLevel = ev.target.value;
      applyDemand(ev.target.value);
      markDirty();
    });
    pop.querySelector('#le-stats-done').onclick = closePop;
  }
  document.querySelector('[data-cl-stats-open]')?.addEventListener('click', function (e) {
    e.preventDefault(); e.stopPropagation();
    openStatsPopover(this);
  });
  const demandTile = document.querySelector('[data-cl-demand-open]');
  if (demandTile) {
    demandTile.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); openStatsPopover(demandTile); });
    demandTile.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openStatsPopover(demandTile); } });
  }

  /* ── Tile popover (icon / colours / flags) ── */
  function tileSwatchHtml() {
    return TILE_ICONS[tileState.iconType] || TILE_ICONS.generic;
  }
  document.getElementById('le-tile-btn').addEventListener('click', function (e) {
    e.stopPropagation();
    pop.innerHTML =
      '<h4><i data-lucide="app-window"></i> Landing tile</h4>' +
      '<div class="le-tile-prev"><span class="le-tile-swatch" id="le-tile-swatch" style="background:#eef0ff; color:#4f46e5;">' + tileSwatchHtml() + '</span>' +
      '<span style="font: 700 .85rem Inter, sans-serif; color:#6a7686;">How this career appears in the landing grid.</span></div>' +
      '<label>Career name (tile & URL text)</label>' +
      '<input type="text" id="le-tile-title" value="' + escAttr(tileState.title) + '" maxlength="120">' +
      '<label>Icon</label>' +
      '<select id="le-tile-icon">' +
        ICON_TYPES.map(i => '<option' + (tileState.iconType === i ? ' selected' : '') + '>' + i + '</option>').join('') +
      '</select>' +
      '<label>Tile background class</label>' +
      '<input type="text" id="le-tile-bg" value="' + escAttr(tileState.bg) + '" placeholder="bg-indigo-100">' +
      '<label>Tile icon colour class</label>' +
      '<input type="text" id="le-tile-text" value="' + escAttr(tileState.text) + '" placeholder="text-indigo-600">' +
      '<label class="le-check"><input type="checkbox" id="le-tile-trending"' + (tileState.trending ? ' checked' : '') + '> Trending</label>' +
      '<label class="le-check"><input type="checkbox" id="le-tile-visible"' + (tileState.visible ? ' checked' : '') + '> Visible on landing grid</label>' +
      '<div class="le-pop-row"><button type="button" class="le-pp-btn primary" id="le-tile-done"><i data-lucide="check"></i> Done</button></div>';
    refreshIcons();
    positionPop(this.getBoundingClientRect());
    pop.querySelector('#le-tile-title').addEventListener('input', ev => { tileState.title = ev.target.value; markDirty(); });
    pop.querySelector('#le-tile-icon').addEventListener('change', ev => {
      tileState.iconType = ev.target.value;
      pop.querySelector('#le-tile-swatch').innerHTML = tileSwatchHtml();
      markDirty();
    });
    pop.querySelector('#le-tile-bg').addEventListener('input', ev => { tileState.bg = ev.target.value; markDirty(); });
    pop.querySelector('#le-tile-text').addEventListener('input', ev => { tileState.text = ev.target.value; markDirty(); });
    pop.querySelector('#le-tile-trending').addEventListener('change', ev => { tileState.trending = ev.target.checked; markDirty(); });
    pop.querySelector('#le-tile-visible').addEventListener('change', ev => { tileState.visible = ev.target.checked; markDirty(); });
    pop.querySelector('#le-tile-done').onclick = closePop;
  });

  /* ── Page SEO modal ── */
  const seoEl = document.getElementById('le-seo');
  const seoFaqs = document.getElementById('le-seo-faqs');
  function faqRowHtml(q, a) {
    return '<div class="le-faq-row"><div>' +
      '<input type="text" class="le-faq-q" value="' + escAttr(q) + '" placeholder="Question" maxlength="300">' +
      '<textarea class="le-faq-a" rows="2" placeholder="Answer">' + String(a ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</textarea>' +
      '</div><button type="button" class="le-faq-del" title="Remove">✕</button></div>';
  }
  function syncSeoFromInputs() {
    seoState.title = document.getElementById('le-seo-title').value;
    seoState.description = document.getElementById('le-seo-desc').value;
    seoState.keywords = document.getElementById('le-seo-keywords').value.split(/\r\n|\r|\n/).map(s => s.trim()).filter(Boolean);
    seoState.faqs = [...seoFaqs.querySelectorAll('.le-faq-row')].map(row => ({
      question: row.querySelector('.le-faq-q').value,
      answer: row.querySelector('.le-faq-a').value,
    })).filter(f => f.question.trim() !== '');
    markDirty();
  }
  document.getElementById('le-seo-btn').addEventListener('click', () => {
    document.getElementById('le-seo-title').value = seoState.title || '';
    document.getElementById('le-seo-desc').value = seoState.description || '';
    document.getElementById('le-seo-keywords').value = (seoState.keywords || []).join('\n');
    seoFaqs.innerHTML = (seoState.faqs || []).map(f => faqRowHtml(f.question, f.answer)).join('');
    seoEl.classList.add('open');
  });
  seoEl.addEventListener('input', e => { if (e.target.matches('input,textarea')) syncSeoFromInputs(); });
  seoEl.addEventListener('click', e => {
    if (e.target.closest('.le-faq-del')) { e.target.closest('.le-faq-row').remove(); syncSeoFromInputs(); }
    if (e.target === seoEl) seoEl.classList.remove('open');
  });
  document.getElementById('le-seo-add-faq').addEventListener('click', () => {
    seoFaqs.insertAdjacentHTML('beforeend', faqRowHtml('', ''));
    seoFaqs.lastElementChild.querySelector('.le-faq-q').focus();
  });
  document.getElementById('le-seo-close').onclick = () => seoEl.classList.remove('open');
  document.getElementById('le-seo-done').onclick = () => seoEl.classList.remove('open');

  /* ── Variant menu ── */
  const varWrap = document.getElementById('le-var-wrap');
  document.getElementById('le-var-btn').addEventListener('click', e => { e.stopPropagation(); varWrap.classList.toggle('open'); });
  document.addEventListener('click', e => { if (!varWrap.contains(e.target)) varWrap.classList.remove('open'); });
  function postForm(action, fields) {
    const form = document.createElement('form');
    form.method = 'POST'; form.action = action;
    fields._token = CSRF;
    Object.entries(fields).forEach(([k, v]) => {
      const input = document.createElement('input');
      input.type = 'hidden'; input.name = k; input.value = v;
      form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
  }
  varWrap.querySelectorAll('[data-goto-variant]').forEach(btn => btn.addEventListener('click', () => {
    const v = btn.dataset.gotoVariant;
    if (v === VARIANT) { varWrap.classList.remove('open'); return; }
    if (dirty && !confirm('You have unsaved changes. Switch variant anyway?')) return;
    dirty = false;
    location.href = LIVE_URL + '?variant=' + encodeURIComponent(v);
  }));
  document.getElementById('le-var-add-toggle').addEventListener('click', () => {
    document.getElementById('le-var-add').classList.toggle('open');
  });
  document.getElementById('le-var-add-go').addEventListener('click', () => {
    if (dirty && !confirm('You have unsaved changes that will be lost. Add the variant anyway?')) return;
    dirty = false;
    postForm(VARIANT_ADD_URL, {
      country: document.getElementById('le-var-country').value,
      language: document.getElementById('le-var-language').value,
    });
  });
  document.getElementById('le-var-del')?.addEventListener('click', () => {
    if (!confirm('Remove the ' + VARIANT.replace('|', ' · ') + ' variant? This cannot be undone.')) return;
    dirty = false;
    postForm(VARIANT_DEL_URL, { _method: 'DELETE', variant: VARIANT });
  });

  /* ── Add / duplicate / delete repeater items ── */
  const TPL_ALIAS = { conventionalOptions: 'option', newAgeOptions: 'option', aiRelatedOptions: 'option' };
  function templateFor(key) {
    return document.querySelector('template[data-cl-tpl="' + (TPL_ALIAS[key] || key) + '"]');
  }
  function afterStructureChange() {
    renumberPathways();
    recolorChips();
    refreshIcons();
    markDirty();
  }
  root.addEventListener('click', (e) => {
    const add = e.target.closest('[data-cl-add]');
    if (add) {
      e.preventDefault(); e.stopPropagation();
      const key = add.dataset.clAdd;
      const tpl = templateFor(key);
      const row = add.closest('.cl-add-row') || add;
      if (!tpl || !row.parentElement) return;
      const item = tpl.content.firstElementChild.cloneNode(true);
      row.parentElement.insertBefore(item, row);
      decorate(row.parentElement);
      afterStructureChange();
      const first = item.querySelector('[data-ed]');
      if (first) { first.focus(); }
      return;
    }

    const tool = e.target.closest('[data-le-tool]');
    if (tool) {
      e.preventDefault(); e.stopPropagation();
      const item = tool.closest('[data-ed-item]');
      const kind = tool.dataset.leTool;
      if (kind === 'move') return; // handled by pointer drag
      if (kind === 'del') {
        if (confirm('Remove this item?')) { item.remove(); afterStructureChange(); toast('Item removed'); }
      } else if (kind === 'link') {
        openLinkPopover(item, tool);
      } else if (kind === 'dup') {
        const clone = item.cloneNode(true);
        clone.querySelectorAll('.le-item-tools').forEach(t => t.remove());
        clone.removeAttribute('data-le-deco');
        clone.querySelectorAll('[data-le-deco]').forEach(n => n.removeAttribute('data-le-deco'));
        item.after(clone);
        decorate(clone.parentElement);
        afterStructureChange();
        toast('Item duplicated — edit it');
      }
      return;
    }

    // Thumbnail click → image popover
    const img = e.target.closest('[data-ed-img]');
    if (img) { e.preventDefault(); e.stopPropagation(); openImagePopover(img); return; }

    // Never navigate away while editing
    const a = e.target.closest('a');
    if (a) e.preventDefault();
  });

  // Buttons baked into the page markup (accordion toggles, voice brief, back
  // button) must not fire while editing — accordions are forced open by CSS.
  root.addEventListener('click', (e) => {
    const b = e.target.closest('button[onclick]');
    if (b && !e.target.closest('[data-le-tool],[data-cl-add],[data-cl-stats-open]')) {
      e.preventDefault(); e.stopPropagation();
    }
  }, true);

  document.addEventListener('mousedown', (e) => {
    if (!pop.hidden && !pop.contains(e.target) && !e.target.closest('#le-crop,[data-ed-img],[data-le-tool],[data-cl-stats-open],[data-cl-demand-open],#le-tile-btn')) closePop();
  });

  /* ── Drag-to-reorder within a repeater ── */
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
    let t = under.closest('[data-ed-item]');
    while (t && t.closest('[data-ed-rep]') !== drag.rep) t = t.parentElement && t.parentElement.closest('[data-ed-item]');
    if (!t || t === drag.item || t.closest('[data-ed-rep]') !== drag.rep) return;
    const after = drag.item.compareDocumentPosition(t) & Node.DOCUMENT_POSITION_FOLLOWING;
    if (after) t.after(drag.item); else t.before(drag.item);
    markDirty();
  });
  function endItemDrag() {
    if (!drag) return;
    drag.item.classList.remove('le-item-dragging');
    try { drag.handle.releasePointerCapture(drag.pid); } catch (err) {}
    drag = null;
    renumberPathways();
    recolorChips();
  }
  root.addEventListener('pointerup', endItemDrag);
  root.addEventListener('pointercancel', endItemDrag);

  /* ── Track text edits + mirrors ── */
  root.addEventListener('input', (e) => {
    if (!e.target.isContentEditable) return;
    markDirty();
    if (e.target.hasAttribute('data-ed')) syncMirrors(e.target);
  });

  /* ── Save ── */
  document.getElementById('le-save').addEventListener('click', () => {
    closePop();
    statusEl.textContent = 'Saving…'; statusEl.style.color = '#9fb0d0';
    const payload = { variant: VARIANT, career: tileState, report: buildReport() };
    fetch(SAVE_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(payload) })
      .then(r => r.ok ? r.json() : Promise.reject()).then(d => {
        dirty = false; statusEl.textContent = '✓ Saved'; statusEl.style.color = '#9ee6b8';
        toast(d.message || 'Saved'); setTimeout(() => { if (!dirty) statusEl.textContent = ''; }, 2500);
      }).catch(() => { statusEl.textContent = '● Unsaved'; statusEl.style.color = '#ffd9a8'; toast('Save failed. Try again.', true); });
  });

  window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

  renumberPathways();
  recolorChips();
})();
</script>
