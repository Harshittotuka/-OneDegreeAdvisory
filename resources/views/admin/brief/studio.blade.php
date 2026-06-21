<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Studio · {{ $page['title'] ?? 'Page' }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
  @include('partials.brief._styles')
  @stack('head')
  <style>
    /* ════════════════════ Studio shell (CSS grid — cannot collapse) ════════════════════ */
    :root{
      --bg:#edeef5; --panel:#fff; --ink:#23243a; --muted:#6e6b86; --line:#e7e7f1;
      --vio:#6a4cff; --vio-d:#5840e0; --vio-s:#efeaff; --danger:#e9536b;
      --ai1:#7b2ff7; --ai2:#f05a28;
      --top-h:56px; --pal-w:288px;
    }
    *{box-sizing:border-box}
    html,body{margin:0;height:100%}
    body{font-family:Manrope,system-ui,sans-serif;color:var(--ink);background:var(--bg);overflow:hidden}
    button{font-family:inherit}

    #studio{display:grid;grid-template-rows:var(--top-h) minmax(0,1fr);height:100vh}

    /* ── Top bar ── */
    .tb{display:flex;align-items:center;gap:10px;padding:0 14px;background:#14122a;color:#fff;min-width:0}
    .tb-back{display:inline-flex;align-items:center;gap:6px;color:#b9b9dd;text-decoration:none;font-weight:700;font-size:.84rem;flex:none}
    .tb-back:hover{color:#fff}
    .tb-back i{width:16px;height:16px}
    .tb-title{font-weight:800;font-size:.95rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:40px;max-width:26vw}
    .tb-badge{flex:none;font-size:.58rem;font-weight:800;letter-spacing:.1em;background:rgba(255,255,255,.14);padding:3px 8px;border-radius:999px}
    .tb-sp{flex:1;min-width:8px}
    .tb-status{flex:none;font-size:.78rem;color:#a7a8d4;min-width:54px;text-align:right}
    .tb-btn{flex:none;display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.1);color:#fff;border:0;border-radius:9px;
      padding:8px 12px;font-weight:700;font-size:.82rem;cursor:pointer;text-decoration:none;white-space:nowrap;transition:background .15s}
    .tb-btn:hover{background:rgba(255,255,255,.2)}
    .tb-btn i{width:15px;height:15px}
    .tb-btn.is-save{background:var(--vio)} .tb-btn.is-save:hover{background:var(--vio-d)}
    .tb-btn.is-ai{background:linear-gradient(135deg,var(--ai1),var(--ai2))} .tb-btn.is-ai:hover{filter:brightness(1.12)}
    .tb-dev{flex:none;display:inline-flex;border:1px solid rgba(255,255,255,.18);border-radius:9px;overflow:hidden}
    .tb-dev button{border:0;background:none;color:#b9b9dd;cursor:pointer;padding:7px 10px;display:inline-flex}
    .tb-dev button.is-active{background:var(--vio);color:#fff}
    .tb-dev i{width:14px;height:14px}
    .tb-burger{display:none}

    /* ── Body: palette | canvas ── */
    .body{display:grid;grid-template-columns:var(--pal-w) minmax(0,1fr);min-height:0}

    /* ── Palette ── */
    .pal{min-height:0;overflow-y:auto;background:var(--panel);border-right:1px solid var(--line);padding:14px 13px 40px}
    .pal-search{position:relative;margin-bottom:6px}
    .pal-search input{width:100%;padding:9px 12px 9px 32px;border:1px solid var(--line);border-radius:10px;font-family:inherit;font-size:.84rem;background:#fafafe}
    .pal-search i{position:absolute;left:10px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:#a7a3c8}
    .pal h4{margin:16px 4px 8px;font-size:.64rem;font-weight:800;letter-spacing:.11em;text-transform:uppercase;color:#9a96b5}
    .pal-hint{display:block;margin-top:3px;font-size:.62rem;font-weight:600;letter-spacing:0;text-transform:none;color:#b3afd0}
    .pal-basics{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px}
    .pal-tile{display:flex;flex-direction:column;align-items:center;gap:6px;border:1px solid var(--line);border-radius:11px;background:#fbfbfe;
      padding:12px 4px;cursor:grab;font-size:.66rem;font-weight:700;color:var(--ink);text-align:center;transition:border-color .15s,background .15s}
    .pal-tile:hover{border-color:var(--vio);background:var(--vio-s);color:var(--vio-d)}
    .pal-tile i{width:18px;height:18px;color:var(--vio)}
    .pal-pres{display:flex;flex-direction:column;gap:10px}
    .pre{display:flex;flex-direction:column;gap:6px;border:1px solid var(--line);border-radius:12px;background:#fff;padding:7px;cursor:grab;
      text-align:left;transition:border-color .15s,box-shadow .15s}
    .pre:hover{border-color:var(--vio);box-shadow:0 8px 22px rgba(106,76,255,.16)}
    .pre-thumb{position:relative;display:block;height:106px;overflow:hidden;border-radius:8px;border:1px solid #f1eff9;background:#fff;pointer-events:none}
    .pre-scale{position:absolute;top:0;left:0;display:block;width:1180px;transform:scale(.2);transform-origin:0 0;padding:12px!important;background:#fff!important}
    .pre-name{font-size:.72rem;font-weight:700;color:var(--ink);padding:0 3px 2px}

    /* ── Canvas ── */
    .cv-wrap{min-height:0;overflow-y:auto;overflow-x:hidden;padding:22px;-webkit-overflow-scrolling:touch}
    .cv{width:100%;margin:0 auto;background:#fff;border-radius:16px;box-shadow:0 16px 46px rgba(20,19,43,.12);overflow:hidden;transition:max-width .25s ease}
    .cv.is-phone{max-width:414px}

    /* studio chrome over the real page */
    .st-canvas-page{min-height:240px}
    .st-rows{display:flex;flex-direction:column;gap:14px}
    .st-lock{display:flex;align-items:center;justify-content:center;gap:9px;margin:16px 0;padding:15px 14px;border:1.5px dashed #d6d2ec;border-radius:12px;
      color:#8d89aa;font-size:.8rem;font-weight:600;user-select:none;cursor:not-allowed;
      background:repeating-linear-gradient(45deg,#f8f7fc,#f8f7fc 12px,#f2f0fa 12px,#f2f0fa 24px)}
    .st-lock i{width:15px;height:15px;flex:none}
    .st-lock b{font-weight:800;color:#6e6a8e}
    .st-row{position:relative;border:1.5px dashed transparent;border-radius:12px;padding:8px;transition:border-color .15s,background .15s}
    .st-row:hover{border-color:#d4cff2;background:#fdfdff}
    /* Full-page-width rows: stretch to the canvas edges (mirrors the live page) */
    .st-canvas-page .st-row[data-width="full"]{margin-left:-28px;margin-right:-28px;border-radius:0}
    .st-row[data-width="full"] .st-row-drag::after{content:"FULL WIDTH";margin-left:6px;font-size:.56rem;font-weight:800;letter-spacing:.05em;
      background:var(--vio);color:#fff;padding:2px 7px;border-radius:999px}
    .st-row-tools{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:7px;opacity:.45;transition:opacity .15s}
    .st-row:hover .st-row-tools{opacity:1}
    .st-row-drag{display:inline-flex;align-items:center;gap:5px;cursor:grab;font-size:.68rem;font-weight:800;color:var(--muted);
      text-transform:uppercase;letter-spacing:.07em;touch-action:none}
    .st-row-drag i{width:14px;height:14px}
    .st-sp{flex:1}
    .st-presets{display:inline-flex;gap:4px}
    .st-presets button{min-width:30px;height:27px;border:1px solid var(--line);background:#fff;border-radius:7px;cursor:pointer;
      font-size:.72rem;font-weight:800;color:var(--muted);transition:border-color .12s,color .12s}
    .st-presets button:hover{border-color:var(--vio);color:var(--vio)}
    .st-rowai{display:inline-flex;align-items:center;gap:5px;border:0;border-radius:7px;padding:6px 11px;cursor:pointer;
      font-size:.72rem;font-weight:800;color:#fff;background:linear-gradient(135deg,var(--ai1),var(--ai2))}
    .st-rowai:hover{filter:brightness(1.12)}
    .st-rowai i{width:13px;height:13px}
    .st-tbtn{border:0;background:none;cursor:pointer;color:var(--muted);padding:5px;border-radius:7px;display:inline-flex}
    .st-tbtn:hover{background:#efeef7;color:var(--ink)}
    .st-tbtn i{width:15px;height:15px}
    .st-del:hover{background:var(--danger);color:#fff}
    .st-cols{display:grid;grid-template-columns:repeat(12,1fr);gap:18px}
    .st-col{grid-column:span var(--span,12);min-width:0}
    .st-col-blocks{min-height:64px;border:1.5px dashed #ddd9f3;border-radius:10px;padding:6px;display:flex;flex-direction:column;gap:10px;transition:border-color .15s,background .15s}
    .st-col-blocks:empty::before{content:"Drop a block here";display:flex;align-items:center;justify-content:center;min-height:54px;color:#aaa6cb;font-size:.8rem;font-weight:600}
    .st-block{position:relative;border:2px solid transparent;border-radius:10px;transition:border-color .12s}
    .st-block:hover{border-color:#cfc9f5}
    .st-block.is-selected{border-color:var(--vio);box-shadow:0 0 0 3px var(--vio-s)}
    .st-block-tools{position:absolute;top:6px;right:6px;z-index:6;display:flex;align-items:center;gap:3px;background:#14122a;border-radius:8px;padding:3px;
      opacity:0;transform:translateY(-3px);transition:opacity .12s,transform .12s}
    .st-block:hover .st-block-tools,.st-block.is-selected .st-block-tools{opacity:1;transform:none}
    .st-block-tag{display:inline-flex;align-items:center;gap:4px;color:#cfd0ee;font-size:.64rem;font-weight:700;padding:0 4px}
    .st-block-tag i{width:12px;height:12px}
    .st-drag{display:inline-flex;cursor:grab;color:#cfd0ee;padding:3px;touch-action:none}
    .st-drag i{width:14px;height:14px}
    .st-block-tools .st-tbtn{color:#cfd0ee;padding:4px}
    .st-block-tools .st-tbtn:hover{background:rgba(255,255,255,.18);color:#fff}
    .st-block-tools .st-tbtn i{width:14px;height:14px}
    /* Block content is interactive: links/buttons are click-to-edit, embeds are inline-editable */
    .st-block-node a,.st-block-node button{cursor:pointer}
    .st-block[data-type="embed"] .st-block-node{cursor:text}
    .field-flash{outline:2px solid var(--ai2)!important;box-shadow:0 0 0 4px rgba(240,90,40,.22)!important;border-radius:9px}
    .st-inline-edit{outline:2px dashed var(--ai2)!important;outline-offset:3px;border-radius:4px;cursor:text}
    .st-embed-hint{position:absolute;left:8px;top:8px;z-index:5;background:linear-gradient(135deg,var(--ai1),var(--ai2));color:#fff;
      font-size:.62rem;font-weight:800;border-radius:999px;padding:3px 10px;opacity:0;transition:opacity .15s;pointer-events:none}
    .st-block[data-type="embed"]:hover .st-embed-hint{opacity:1}
    .st-addrow{margin:18px auto 4px;display:flex;align-items:center;gap:7px;border:1.5px dashed #c9c4ec;background:#fff;color:var(--vio-d);
      border-radius:10px;padding:11px 20px;font-weight:800;font-size:.85rem;cursor:pointer}
    .st-addrow:hover{border-color:var(--vio);background:var(--vio-s)}
    .st-addrow i{width:16px;height:16px}
    .st-dragging{opacity:.45}
    /* Empty page state */
    .cv-empty{display:flex;flex-direction:column;align-items:center;gap:14px;padding:46px 20px 30px;text-align:center}
    .cv-empty h3{margin:0;font-family:Manrope,sans-serif;font-size:1.25rem;font-weight:800;color:var(--ink)}
    .cv-empty p{margin:0;color:var(--muted);font-size:.9rem;max-width:46ch}
    .cv-empty-actions{display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:6px}
    .cv-empty-actions .big-ai{display:inline-flex;align-items:center;gap:8px;border:0;border-radius:12px;padding:13px 22px;cursor:pointer;color:#fff;
      font-weight:800;font-size:.95rem;background:linear-gradient(135deg,var(--ai1),var(--ai2));box-shadow:0 14px 30px rgba(123,47,247,.3)}
    .cv-empty-actions .big-ai:hover{filter:brightness(1.1)}
    .cv-empty-actions .big-row{display:inline-flex;align-items:center;gap:8px;border:1.5px dashed #c9c4ec;background:#fff;color:var(--vio-d);
      border-radius:12px;padding:13px 22px;cursor:pointer;font-weight:800;font-size:.95rem}
    .cv-empty-actions i{width:17px;height:17px}

    /* ── Settings drawer (fixed overlay — can never break the grid) ── */
    .drawer{position:fixed;top:var(--top-h);right:0;bottom:0;width:min(372px,94vw);z-index:60;background:#fff;border-left:1px solid var(--line);
      display:flex;flex-direction:column;transform:translateX(105%);transition:transform .22s ease;box-shadow:-14px 0 44px rgba(20,19,43,.16)}
    .drawer.open{transform:none}
    .drawer-head{display:flex;align-items:center;gap:8px;padding:14px 16px;border-bottom:1px solid var(--line);font-weight:800;font-size:.9rem;flex:none}
    .drawer-head i{width:16px;height:16px;color:var(--vio)}
    .drawer-head button{margin-left:auto;border:0;background:#f3f2fb;border-radius:8px;width:30px;height:30px;cursor:pointer;color:var(--muted);display:inline-flex;align-items:center;justify-content:center}
    .drawer-body{min-height:0;overflow-y:auto;padding:14px;flex:1}
    .st-form-head{display:flex;align-items:center;gap:8px;font-weight:800;font-size:.84rem;color:var(--vio-d);margin-bottom:12px}
    .st-form-head i{width:16px;height:16px}
    .drawer-empty{color:var(--muted);font-size:.85rem;text-align:center;padding:34px 12px}

    /* ── Field panel ── */
    .bp-field{margin-bottom:13px}
    .bp-field>label{display:block;font-weight:700;font-size:.76rem;margin-bottom:5px;color:var(--ink)}
    .bp-hint{font-weight:500;color:var(--muted);font-size:.68rem}
    .bp-field input[type=text],.bp-field textarea,.bp-field select{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:9px;
      font-family:inherit;font-size:.86rem;color:var(--ink);background:#fff}
    .bp-field input:focus,.bp-field textarea:focus,.bp-field select:focus{outline:none;border-color:var(--vio);box-shadow:0 0 0 3px rgba(106,76,255,.13)}
    .bp-field textarea{resize:vertical;min-height:60px}
    .bp-mono{font-family:ui-monospace,Menlo,monospace;font-size:.76rem!important}
    .bp-field--check label{display:flex;align-items:center;gap:8px;font-weight:600;font-size:.84rem;cursor:pointer}
    .bp-check input{width:16px;height:16px}
    .bp-color{display:flex;align-items:center;gap:8px}
    .bp-color-pick{width:38px;height:34px;padding:0;border:1px solid var(--line);border-radius:8px;background:none;cursor:pointer}
    .bp-color-hex{flex:1}
    .bp-color-clear{border:1px solid var(--line);background:#fff;border-radius:8px;width:34px;height:34px;cursor:pointer;color:var(--muted)}
    .bp-color-clear:hover{color:var(--danger);border-color:#f0c4be}
    .bp-icon{display:flex;align-items:center;gap:9px}
    .bp-icon-prev{width:38px;height:34px;border:1px solid var(--line);border-radius:8px;display:inline-flex;align-items:center;justify-content:center;color:var(--vio);background:var(--vio-s)}
    .bp-icon-prev i{width:18px;height:18px}
    .bp-icon-name{flex:1}
    .bp-image{display:flex;gap:10px}
    .bp-image-prev{flex:none;width:70px;height:54px;border:1px solid var(--line);border-radius:9px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#f6f5fb;color:#b7b3c9}
    .bp-image-prev img{width:100%;height:100%;object-fit:cover}
    .bp-image-ctrls{flex:1;display:flex;flex-direction:column;gap:7px}
    .bp-image-btns{display:flex;gap:7px}
    .bp-mini{display:inline-flex;align-items:center;gap:6px;cursor:pointer;border:1px solid var(--line);background:#fff;border-radius:8px;padding:6px 9px;font-size:.74rem;font-weight:700;color:var(--ink)}
    .bp-mini:hover{border-color:var(--vio);color:var(--vio)}
    .bp-mini i{width:13px;height:13px}
    .bp-field--rep>label{margin-bottom:7px}
    .bp-rep-items{display:flex;flex-direction:column;gap:9px}
    .bp-rep-item{border:1px solid var(--line);border-radius:10px;background:#fbfbfe}
    .bp-rep-item-bar{display:flex;align-items:center;gap:6px;padding:5px 8px;border-bottom:1px dashed var(--line)}
    .bp-rep-grip{cursor:grab;color:#b7b3c9;display:inline-flex;touch-action:none}
    .bp-rep-del{margin-left:auto;border:0;background:none;cursor:pointer;color:var(--muted);padding:3px;border-radius:6px;display:inline-flex}
    .bp-rep-del:hover{background:#fdecea;color:var(--danger)}
    .bp-rep-del i,.bp-rep-grip i{width:14px;height:14px}
    .bp-rep-item-body{padding:10px 10px 1px}
    .bp-rep-add{margin-top:9px;display:inline-flex;align-items:center;gap:6px;border:1px dashed #c9c6e0;background:#fff;color:var(--vio-d);
      border-radius:9px;padding:7px 11px;font-weight:700;font-size:.78rem;cursor:pointer}
    .bp-rep-add:hover{border-color:var(--vio);background:var(--vio-s)}
    .bp-rep-add i{width:14px;height:14px}
    .bp-appear{margin:6px 0 12px;border:1px solid var(--line);border-radius:10px;background:#fbfbfe}
    .bp-appear>summary{cursor:pointer;list-style:none;display:flex;align-items:center;gap:8px;padding:9px 12px;font-weight:700;font-size:.8rem;color:var(--muted)}
    .bp-appear>summary i{width:15px;height:15px}
    .bp-appear[open]>summary{border-bottom:1px solid var(--line);color:var(--ink)}
    .bp-appear-body{padding:11px 11px 1px}

    /* ── Modals ── */
    .modal{position:fixed;inset:0;z-index:90;background:rgba(16,8,40,.62);display:none;align-items:center;justify-content:center;padding:18px}
    .modal.open{display:flex}
    .modal-card{width:min(740px,96vw);max-height:92vh;display:flex;flex-direction:column;background:#fff;border-radius:16px;overflow:hidden}
    .modal-head{display:flex;align-items:center;gap:9px;padding:15px 18px;font-weight:800;color:#fff;flex:none;background:linear-gradient(135deg,#2b1fa8,#7b2ff7)}
    .modal-head i{width:18px;height:18px}
    .modal-head button{margin-left:auto;border:0;background:rgba(255,255,255,.18);color:#fff;border-radius:8px;width:30px;height:30px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}
    .modal-body{padding:16px 18px;overflow-y:auto}
    .ai-step{display:flex;align-items:center;gap:8px;margin:15px 0 7px;font-weight:800;font-size:.84rem}
    .ai-step:first-child{margin-top:0}
    .ai-step span{flex:none;display:inline-flex;align-items:center;justify-content:center;width:21px;height:21px;border-radius:50%;background:var(--vio-s);color:var(--vio-d);font-size:.72rem}
    .modal-body textarea{width:100%;border:1px solid var(--line);border-radius:10px;padding:11px 13px;font-family:inherit;font-size:.87rem;resize:vertical}
    #ai-content{min-height:96px}
    #ai-prompt{min-height:150px;font-family:ui-monospace,Menlo,monospace;font-size:.76rem;background:#faf9ff}
    #ai-code{min-height:118px;font-family:ui-monospace,Menlo,monospace;font-size:.76rem}
    .modal-body .btn{margin-top:9px}
    .ai-foot{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
    .ai-note{color:var(--muted);font-size:.76rem}
    .crop-stage{padding:14px;background:#f4f0ff;overflow:auto}
    .crop-stage img{max-width:100%;display:block}
    .crop-foot{display:flex;gap:8px;justify-content:flex-end;padding:12px 16px;border-top:1px solid var(--line)}
    .btn{display:inline-flex;align-items:center;gap:7px;cursor:pointer;border:1px solid transparent;border-radius:9px;padding:9px 14px;font-size:.86rem;font-weight:700}
    .btn-ghost{background:#fff;border-color:var(--line);color:var(--ink)}
    .btn-primary{background:var(--vio);color:#fff}
    .btn i{width:15px;height:15px}

    .toasts{position:fixed;top:12px;left:50%;transform:translateX(-50%);z-index:200;display:flex;flex-direction:column;gap:8px;align-items:center}
    .toast{background:#14122a;color:#fff;border-radius:10px;padding:11px 16px;font-weight:700;font-size:.85rem;box-shadow:0 10px 30px rgba(0,0,0,.3);
      opacity:0;transform:translateY(-8px);transition:.25s;max-width:88vw}
    .toast.show{opacity:1;transform:none}
    .toast.error{background:var(--danger)}

    /* ── Responsive ── */
    @media (max-width:900px){
      .body{grid-template-columns:minmax(0,1fr)}
      .pal{position:fixed;left:0;top:var(--top-h);bottom:0;width:min(320px,86vw);z-index:55;transform:translateX(-105%);transition:transform .2s;box-shadow:14px 0 44px rgba(20,19,43,.2)}
      #studio.pal-open .pal{transform:none}
      .tb-burger{display:inline-flex}
      .tb-title{max-width:32vw}
      .cv-wrap{padding:12px}
    }
  </style>
</head>
<body>
@php $basics = array_filter($types, fn ($d) => ($d['cat'] ?? '') === 'content'); @endphp

<div id="studio">
  <header class="tb">
    <button class="tb-btn tb-burger" id="tb-burger" title="Blocks"><i data-lucide="panel-left"></i></button>
    <a class="tb-back" href="{{ route('admin.pages.index') }}"><i data-lucide="arrow-left"></i><span>Pages</span></a>
    <span class="tb-title">{{ $page['title'] ?? 'Page' }}</span>
    <span class="tb-badge">STUDIO</span>
    <span class="tb-sp"></span>
    <span class="tb-status" id="tb-status"></span>
    <div class="tb-dev" id="tb-dev">
      <button type="button" data-dev="web" class="is-active" title="Desktop preview"><i data-lucide="monitor"></i></button>
      <button type="button" data-dev="phone" title="Mobile preview"><i data-lucide="smartphone"></i></button>
    </div>
    <button class="tb-btn is-ai" id="tb-ai"><i data-lucide="sparkles"></i><span>Build with AI</span></button>
    <a class="tb-btn" id="tb-view" href="{{ $page['path'] ?? ('/briefs/'.$page['slug']) }}" target="_blank"><i data-lucide="external-link"></i><span>View</span></a>
    <button class="tb-btn" id="tb-settings" title="Page settings"><i data-lucide="settings"></i></button>
    <button class="tb-btn is-save" id="tb-save"><i data-lucide="save"></i><span>Save</span></button>
  </header>

  <div class="body">
    <aside class="pal" id="pal">
      <div class="pal-search">
        <i data-lucide="search"></i>
        <input type="text" id="pal-q" placeholder="Search components…">
      </div>

      <h4>Basics</h4>
      <div class="pal-basics">
        @foreach($basics as $slug => $d)
          <button type="button" class="pal-tile" data-add-type="{{ $slug }}" data-name="{{ strtolower($d['label']) }}">
            <i data-lucide="{{ $d['icon'] ?? 'square' }}"></i>{{ $d['label'] }}
          </button>
        @endforeach
      </div>

      <h4>Components <span class="pal-hint">ready-made sections — drag onto the page, then click any text to edit</span></h4>
      <div class="pal-pres">
        {{-- Each preset is a <div role=button>: its live thumbnail embeds real
             sections (with <a>/<button> inside), which are not allowed inside a
             native <button> — nesting them breaks the HTML parse of the page. --}}
        @foreach($presets as $key => $p)
          <div class="pre" role="button" tabindex="0" data-preset="{{ $key }}" data-name="{{ strtolower($p['label']) }}" title="{{ $p['label'] }}">
            <span class="pre-thumb"><span class="pre-scale odp-file-page">{!! $p['node'] !!}</span></span>
            <span class="pre-name">{{ $p['label'] }}</span>
          </div>
        @endforeach
      </div>
    </aside>

    <main class="cv-wrap" id="cv-wrap">
      <div class="cv" id="cv">
        @include('admin.brief._canvas', ['page' => $page, 'types' => $types])
      </div>
    </main>
  </div>
</div>

{{-- Settings drawer (fixed overlay) --}}
<aside class="drawer" id="drawer">
  <div class="drawer-head"><i data-lucide="settings-2"></i> <span id="drawer-title">Settings</span>
    <button type="button" id="drawer-close"><i data-lucide="x"></i></button>
  </div>
  <div class="drawer-body" id="drawer-body"><div class="drawer-empty">Select a block to edit it.</div></div>
</aside>

{{-- Page settings form (parked here when drawer shows something else) --}}
<div id="page-form-park" hidden>
  <div class="st-form" id="page-settings">
    <div class="st-form-head"><i data-lucide="file-cog"></i> Page settings</div>
    <div class="bp-fields">
      <div class="bp-field"><label>Page title</label><input type="text" id="bp-title" value="{{ $page['title'] ?? '' }}"></div>
      <div class="bp-field"><label>SEO title <span class="bp-hint">optional</span></label><input type="text" id="bp-page-title" value="{{ $page['page_title'] ?? '' }}" placeholder="Auto: page title + One Degree Advisory"></div>
      <div class="bp-field"><label>Meta description <span class="bp-hint">optional</span></label><textarea id="bp-meta-desc" placeholder="Auto: compact summary from visible page content">{{ $page['meta_description'] ?? '' }}</textarea></div>
      <div class="bp-field bp-field--check"><label class="bp-check"><input type="checkbox" id="bp-visible" @checked($page['visible'] ?? false)> <span>Published (visible to everyone)</span></label></div>
      <div class="bp-field">
        <label>URL path <span class="bp-hint">e.g. /destination-canada — letters, numbers, dashes</span></label>
        <input type="text" id="bp-path" value="{{ $page['path'] ?? ('/briefs/'.$page['slug']) }}" spellcheck="false">
      </div>
    </div>
  </div>
</div>

{{-- Build-with-AI modal --}}
<div class="modal" id="ai-modal">
  <div class="modal-card">
    <div class="modal-head"><i data-lucide="sparkles"></i> <span id="ai-title">Build with AI — whole page</span>
      <button type="button" data-ai-close><i data-lucide="x"></i></button>
    </div>
    <div class="modal-body">
      <p class="ai-step"><span>1</span> <span id="ai-step1">Describe the page and paste your content / data</span></p>
      <textarea id="ai-content" placeholder="e.g. A landing page for our IELTS coaching: hero with “Score 8+ with ODA”, 3 feature cards (Live classes, Mock tests, 1-on-1 feedback), pricing ₹9,999, student quotes, closing CTA to book a demo."></textarea>
      <button class="btn btn-primary" id="ai-gen"><i data-lucide="wand-2"></i> Generate prompt</button>

      <div id="ai-prompt-wrap" hidden>
        <p class="ai-step"><span>2</span> Copy this prompt into ChatGPT / Claude / Gemini — any AI</p>
        <textarea id="ai-prompt" readonly></textarea>
        <button class="btn btn-ghost" id="ai-copy"><i data-lucide="copy"></i> Copy prompt</button>
      </div>

      <p class="ai-step"><span>3</span> Paste the code the AI returns, then add it</p>
      <textarea id="ai-code" placeholder="Paste the AI's code here — fences/extra wrappers are cleaned automatically"></textarea>
      <div class="ai-foot">
        <button class="btn btn-primary" id="ai-add"><i data-lucide="plus"></i> Add to page</button>
        <span class="ai-note">After adding: click any text in the section to edit it · click an image to replace it. Site header &amp; footer wrap the page automatically.</span>
      </div>
    </div>
  </div>
</div>

<div class="modal" id="pay-otp-modal">
  <div class="modal-card">
    <div class="modal-head"><i data-lucide="shield-check"></i> <span>Authorize payment section</span>
      <button type="button" data-payotp-close><i data-lucide="x"></i></button>
    </div>
    <div class="modal-body">
      <p class="ai-step"><span>!</span> This page contains a <strong>payment section</strong>. To publish it, enter the one-time authorization code emailed to the payment approver.</p>
      <button class="btn btn-primary" id="payotp-send"><i data-lucide="mail"></i> Email the authorization code</button>
      <div id="payotp-wrap" hidden>
        <p class="ai-step"><span>2</span> Enter the 6-digit code</p>
        <input id="payotp-code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="6-digit code"
               style="width:100%;padding:12px 14px;border:1px solid #d8d4ea;border-radius:10px;font:700 18px/1 'Poppins',sans-serif;letter-spacing:.2em;text-align:center;">
        <div class="ai-foot">
          <button class="btn btn-primary" id="payotp-verify"><i data-lucide="lock-open"></i> Verify &amp; Save</button>
          <span class="ai-note" id="payotp-status"></span>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Crop modal (drawer image fields + embed images) --}}
<div class="modal" id="crop-modal">
  <div class="modal-card">
    <div class="modal-head" style="background:#14122a"><i data-lucide="crop"></i> Crop image
      <button type="button" id="crop-x"><i data-lucide="x"></i></button>
    </div>
    <div class="crop-stage"><img id="crop-img" alt=""></div>
    <div class="crop-foot">
      <button type="button" class="btn btn-ghost" id="crop-cancel">Cancel</button>
      <button type="button" class="btn btn-primary" id="crop-apply"><i data-lucide="check"></i> Use image</button>
    </div>
  </div>
</div>

<input type="file" id="embed-img-file" accept="image/*" hidden>
<div class="toasts" id="toasts"></div>

<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script>
(function(){
  'use strict';
  /* ════════ constants & helpers ════════ */
  var CSRF=document.querySelector('meta[name=csrf-token]').content;
  var SAVE=@json(route('admin.pages.save', $page['slug']));
  var BLOCK=@json(route('admin.pages.block'));
  var PRESET=@json(route('admin.pages.preset'));
  var RENDER=@json(route('admin.pages.render'));
  var UPLOAD=@json(route('admin.pages.upload'));
  var PAY_OTP_REQ=@json(route('admin.pages.payment-otp.request'));
  var PAY_OTP_VERIFY=@json(route('admin.pages.payment-otp.verify'));
  var PAGE_SLUG=@json($page['slug']);
  var TYPES=@json(collect($types)->map(fn($d)=>['label'=>$d['label'],'icon'=>$d['icon']??'square'])->all());

  var rowsEl=document.getElementById('st-rows');
  var formsEl=document.getElementById('st-forms');
  var drawer=document.getElementById('drawer');
  var drawerBody=document.getElementById('drawer-body');
  var isDirty=false;

  function refresh(){ if(window.lucide) lucide.createIcons(); }
  function rnd(p){ return p+Math.random().toString(36).slice(2,9); }
  function toast(msg,err){
    var w=document.getElementById('toasts'),t=document.createElement('div');
    t.className='toast'+(err?' error':''); t.textContent=msg; w.appendChild(t);
    requestAnimationFrame(function(){t.classList.add('show');});
    setTimeout(function(){t.classList.remove('show');setTimeout(function(){t.remove();},300);},2800);
  }
  function dirtyMark(){ isDirty=true; document.getElementById('tb-status').textContent='Unsaved'; updateEmpty(); }
  window.addEventListener('beforeunload',function(e){ if(isDirty){ e.preventDefault(); e.returnValue=''; } });

  /* ════════ serialize ════════ */
  function ownerScope(el){ return el.closest('.bp-rep-item, .bp-fields'); }
  function serializeScope(scope){
    var data={};
    if(!scope) return data;
    scope.querySelectorAll('[data-field]').forEach(function(el){
      if(ownerScope(el)!==scope) return;
      data[el.getAttribute('data-field')]= el.type==='checkbox' ? el.checked : (el.value||'');
    });
    scope.querySelectorAll('.bp-rep').forEach(function(rep){
      if(ownerScope(rep)!==scope) return;
      var wrap=rep.querySelector(':scope > .bp-rep-items');
      var items=wrap?[].slice.call(wrap.children).filter(function(c){return c.classList.contains('bp-rep-item');}):[];
      data[rep.getAttribute('data-rep')]=items.map(serializeScope);
    });
    return data;
  }
  function formFor(id){ return document.querySelector('.st-form[data-for="'+id+'"]'); }
  function buildLayout(){
    return [].slice.call(rowsEl.querySelectorAll(':scope > .st-row')).map(function(row){
      return { id:row.dataset.id||rnd('r'), width:row.dataset.width==='full'?'full':'',
        cols: [].slice.call(row.querySelectorAll(':scope > .st-cols > .st-col')).map(function(col){
          return { id:col.dataset.id||rnd('c'), span:parseInt(col.dataset.span||'12',10),
            blocks: [].slice.call(col.querySelectorAll(':scope > .st-col-blocks > .st-block')).map(function(b){
              var f=formFor(b.dataset.id);
              return { id:b.dataset.id, type:b.dataset.type, visible:true, data:f?serializeScope(f.querySelector('.bp-fields')):{} };
            }) };
        }) };
    });
  }

  /* ════════ block render after edit ════════ */
  var rtimers={};
  function renderBlock(id){
    clearTimeout(rtimers[id]);
    rtimers[id]=setTimeout(function(){
      var form=formFor(id); if(!form) return;
      var node=document.querySelector('.st-block[data-id="'+id+'"] > .st-block-node'); if(!node) return;
      if(form.dataset.type==='embed'){
        var ta=form.querySelector('[data-field="html"]');
        node.innerHTML=ta?ta.value:''; refresh(); return;
      }
      var data=serializeScope(form.querySelector('.bp-fields'));
      fetch(RENDER,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json'},body:JSON.stringify({type:form.dataset.type,data:data})})
        .then(function(r){return r.json();}).then(function(d){ node.innerHTML=d.node; refresh(); });
    },260);
  }

  /* ════════ add blocks ════════ */
  function studioBlockHtml(id,type,node){
    var meta=TYPES[type]||{}, ic=meta.icon||'square', lb=meta.label||type;
    var hint=type==='embed'?'<span class="st-embed-hint">✦ click text to edit · click image to replace</span>':'';
    return '<div class="st-block" data-id="'+id+'" data-type="'+type+'">'
      +'<div class="st-block-tools"><span class="st-drag" title="Drag block"><i data-lucide="grip-vertical"></i></span>'
      +'<span class="st-block-tag"><i data-lucide="'+ic+'"></i> '+lb+'</span>'
      +'<button type="button" class="st-tbtn" data-st="dup" title="Duplicate"><i data-lucide="copy"></i></button>'
      +'<button type="button" class="st-tbtn" data-st="edit" title="Settings"><i data-lucide="settings-2"></i></button>'
      +'<button type="button" class="st-tbtn st-del" data-st="del" title="Delete"><i data-lucide="trash-2"></i></button></div>'
      +hint+'<div class="st-block-node">'+node+'</div></div>';
  }
  function specOf(it){
    return it.hasAttribute('data-preset') ? {preset:it.getAttribute('data-preset')} : {type:it.getAttribute('data-add-type')};
  }
  function setScopedField(scope,key,value){
    if(!scope||value==null) return;
    var field=[].slice.call(scope.querySelectorAll('[data-field="'+key+'"]')).find(function(el){
      return ownerScope(el)===scope;
    });
    if(!field) return;
    var next=String(value);
    if(field.tagName==='SELECT'&&!Array.from(field.options).some(function(option){return option.value===next;})) return;
    field.value=next;
    field.dispatchEvent(new Event('input',{bubbles:true}));
    field.dispatchEvent(new Event('change',{bubbles:true}));
  }
  function applyPaymentSpec(form,spec){
    if(!form||form.dataset.type!=='payment'||!spec) return;
    var fields=form.querySelector('.bp-fields');
    ['eyebrow','title','description','layout','button_label','note','accent','accent2'].forEach(function(key){
      setScopedField(fields,key,spec[key]);
    });
    var option=form.querySelector('.bp-rep[data-rep="options"] > .bp-rep-items > .bp-rep-item');
    if(option){
      setScopedField(option,'label',spec.plan||spec.label||'Payment');
      setScopedField(option,'amount',spec.amount||'');
      setScopedField(option,'description',spec.plan_description||'');
      setScopedField(option,'badge',spec.badge||'');
    }
  }
  function addBlock(colBlocks,spec,index,openIt,code,initialData){
    var url=spec.preset?PRESET+'?key='+encodeURIComponent(spec.preset):BLOCK+'?type='+encodeURIComponent(spec.type);
    return fetch(url,{headers:{Accept:'application/json'}}).then(function(r){return r.json();}).then(function(d){
      var tmp=document.createElement('div'); tmp.innerHTML=studioBlockHtml(d.id,d.type,d.node);
      var node=tmp.firstElementChild;
      if(index!=null&&colBlocks.children[index]) colBlocks.insertBefore(node,colBlocks.children[index]); else colBlocks.appendChild(node);
      var ftmp=document.createElement('div'); ftmp.innerHTML=d.form;
      var form=ftmp.firstElementChild; formsEl.appendChild(form);
      if(code){ var ta=form.querySelector('[data-field="html"]'); if(ta){ ta.value=code; node.querySelector('.st-block-node').innerHTML=code; } }
      if(initialData){ applyPaymentSpec(form,initialData); renderBlock(d.id); }
      refresh(); dirtyMark();
      if(openIt){ node.scrollIntoView({behavior:'smooth',block:'center'}); if(d.type!=='embed') selectBlock(node); }
      return node;
    });
  }

  /* ════════ drawer / selection ════════ */
  function parkDrawerForm(){
    var f=drawerBody.querySelector('.st-form'); if(!f) return;
    if(f.id==='page-settings') document.getElementById('page-form-park').appendChild(f); else formsEl.appendChild(f);
  }
  function closeDrawer(){
    parkDrawerForm(); drawer.classList.remove('open');
    document.querySelectorAll('.st-block.is-selected').forEach(function(b){b.classList.remove('is-selected');});
    drawerBody.innerHTML='<div class="drawer-empty">Select a block to edit it.</div>';
  }
  document.getElementById('drawer-close').addEventListener('click',closeDrawer);
  function selectBlock(node){
    parkDrawerForm();
    document.querySelectorAll('.st-block.is-selected').forEach(function(b){b.classList.remove('is-selected');});
    node.classList.add('is-selected');
    var form=formFor(node.dataset.id);
    document.getElementById('drawer-title').textContent=(TYPES[node.dataset.type]||{}).label||'Block';
    drawerBody.innerHTML='';
    if(form) drawerBody.appendChild(form); else drawerBody.innerHTML='<div class="drawer-empty">No settings.</div>';
    refresh(); drawer.classList.add('open');
  }
  document.getElementById('tb-settings').addEventListener('click',function(){
    parkDrawerForm();
    document.querySelectorAll('.st-block.is-selected').forEach(function(b){b.classList.remove('is-selected');});
    document.getElementById('drawer-title').textContent='Page settings';
    drawerBody.innerHTML=''; drawerBody.appendChild(document.getElementById('page-settings'));
    refresh(); drawer.classList.add('open');
  });

  /* ════════ inline editing of AI / embed sections ════════ */
  var INLINE_TAGS='h1,h2,h3,h4,h5,h6,p,a,span,li,button,td,th,dt,dd,figcaption,blockquote,strong,em,b,i,small,label';
  var inlineEl=null;
  function syncEmbed(blockEl){
    var node=blockEl.querySelector(':scope > .st-block-node');
    var form=formFor(blockEl.dataset.id);
    var ta=form&&form.querySelector('[data-field="html"]');
    if(node&&ta){ ta.value=node.innerHTML; dirtyMark(); }
  }
  function endInlineEdit(){
    if(!inlineEl) return;
    var el=inlineEl; inlineEl=null;
    el.removeAttribute('contenteditable'); el.classList.remove('st-inline-edit');
    if(el.getAttribute('class')==='') el.removeAttribute('class');
    var blockEl=el.closest('.st-block'); if(blockEl) syncEmbed(blockEl);
  }
  function startInlineEdit(el){
    if(inlineEl===el) return;
    endInlineEdit();
    inlineEl=el;
    el.setAttribute('contenteditable','true'); el.classList.add('st-inline-edit');
    el.focus();
    el.addEventListener('blur',endInlineEdit,{once:true});
  }
  var embedImgTarget=null;
  rowsEl.addEventListener('click',function(e){
    var node=e.target.closest('.st-block-node'); if(!node) return;
    var block=node.closest('.st-block');
    if(block.dataset.type==='embed'){
      e.preventDefault(); e.stopPropagation();
      var img=e.target.closest('img');
      if(img){ embedImgTarget=img; document.getElementById('embed-img-file').click(); return; }
      var t=e.target.closest(INLINE_TAGS);
      if(t&&node.contains(t)) startInlineEdit(t);
      return;
    }
    // Schema component: clicking a button/link opens its settings with the
    // matching link field highlighted, so individual links are 1-click editable.
    var a=e.target.closest('a,button');
    if(a&&node.contains(a)){
      e.preventDefault(); e.stopPropagation();
      selectBlock(block);
      var href=(a.getAttribute('href')||'').trim();
      setTimeout(function(){
        var form=drawerBody.querySelector('.st-form'); if(!form) return;
        var inputs=[].slice.call(form.querySelectorAll('input[data-field]'));
        var hit=null;
        if(href) hit=inputs.find(function(i){ return i.value.trim()===href; });
        if(!hit) hit=inputs.find(function(i){ return /href|link/i.test(i.getAttribute('data-field')); });
        if(hit){
          hit.scrollIntoView({behavior:'smooth',block:'center'});
          try{ hit.focus({preventScroll:true}); }catch(_){ hit.focus(); }
          hit.classList.add('field-flash');
          setTimeout(function(){ hit.classList.remove('field-flash'); },1900);
        }
      },140);
      return;
    }
    e.preventDefault(); // plain content click: bubble handler selects the block
  },true);
  rowsEl.addEventListener('input',function(e){
    if(inlineEl&&inlineEl.contains(e.target)){ var b=inlineEl.closest('.st-block'); if(b){ var f=formFor(b.dataset.id); if(f){ /* live sync on type */ syncEmbed(b);} } }
  });
  document.getElementById('embed-img-file').addEventListener('change',function(e){
    var f=e.target.files[0]; if(!f||!embedImgTarget) return;
    openCrop(URL.createObjectURL(f),null,embedImgTarget);
    e.target.value='';
  });

  /* ════════ canvas clicks (tools, rows, selection) ════════ */
  rowsEl.addEventListener('click',function(e){
    var tool=e.target.closest('[data-st]');
    if(tool){
      var block=tool.closest('.st-block'), act=tool.dataset.st;
      if(act==='del'){ if(confirm('Delete this block?')){ var f=formFor(block.dataset.id); if(f&&drawerBody.contains(f)) closeDrawer(); var ff=formFor(block.dataset.id); if(ff) ff.remove(); block.remove(); dirtyMark(); } }
      else if(act==='edit'){ selectBlock(block); }
      else if(act==='dup'){ duplicateBlock(block); }
      return;
    }
    var strow=e.target.closest('[data-strow]');
    if(strow){
      if(strow.dataset.strow==='del'){
        if(confirm('Delete this row and its blocks?')){
          var row=strow.closest('.st-row');
          row.querySelectorAll('.st-block').forEach(function(b){ var f=formFor(b.dataset.id); if(f) f.remove(); });
          row.remove(); dirtyMark();
        }
      } else if(strow.dataset.strow==='ai'){ openAi('row',strow.closest('.st-row')); }
      else if(strow.dataset.strow==='width'){
        var wrow=strow.closest('.st-row');
        var full=wrow.dataset.width!=='full';
        wrow.dataset.width=full?'full':'';
        strow.innerHTML='<i data-lucide="'+(full?'minimize-2':'maximize-2')+'"></i>';
        refresh(); dirtyMark();
        toast(full?'Row set to full page width':'Row set to contained width');
      }
      return;
    }
    var preset=e.target.closest('.st-presets button');
    if(preset){ applyPreset(preset.closest('.st-row'),preset.dataset.cols); return; }
    var blk=e.target.closest('.st-block');
    if(blk&&blk.dataset.type!=='embed') selectBlock(blk);
  });

  function duplicateBlock(block){
    var id=block.dataset.id, type=block.dataset.type, form=formFor(id); if(!form) return;
    var newId=rnd('b');
    var nf=form.cloneNode(true); nf.setAttribute('data-for',newId); formsEl.appendChild(nf);
    if(type==='embed'){
      var html=block.querySelector(':scope > .st-block-node').innerHTML;
      var ta=nf.querySelector('[data-field="html"]'); if(ta) ta.value=html;
      var tmp=document.createElement('div'); tmp.innerHTML=studioBlockHtml(newId,type,html);
      block.after(tmp.firstElementChild); refresh(); dirtyMark(); return;
    }
    var data=serializeScope(form.querySelector('.bp-fields'));
    fetch(RENDER,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json'},body:JSON.stringify({type:type,data:data})})
      .then(function(r){return r.json();}).then(function(d){
        var tmp=document.createElement('div'); tmp.innerHTML=studioBlockHtml(newId,type,d.node);
        block.after(tmp.firstElementChild); refresh(); dirtyMark();
      });
  }

  /* ════════ rows & columns ════════ */
  function applyPreset(row,spansStr){
    var spans=spansStr.split(',').map(function(n){return parseInt(n,10);});
    var colsWrap=row.querySelector(':scope > .st-cols');
    var curLists=[].slice.call(colsWrap.querySelectorAll(':scope > .st-col > .st-col-blocks'));
    var newCols=spans.map(function(span){
      var col=document.createElement('div'); col.className='st-col'; col.dataset.span=span; col.dataset.id=rnd('c'); col.style.setProperty('--span',span);
      var bl=document.createElement('div'); bl.className='st-col-blocks'; col.appendChild(bl);
      return {col:col,bl:bl};
    });
    curLists.forEach(function(bl,i){
      var target=newCols[Math.min(i,newCols.length-1)].bl;
      [].slice.call(bl.children).forEach(function(ch){target.appendChild(ch);});
    });
    colsWrap.innerHTML='';
    newCols.forEach(function(nc){ colsWrap.appendChild(nc.col); initColSortable(nc.bl); });
    refresh(); dirtyMark();
  }
  function createRow(full){
    var row=document.createElement('div'); row.className='st-row'; row.dataset.id=rnd('r'); row.dataset.width=full?'full':'';
    row.innerHTML='<div class="st-row-tools"><span class="st-row-drag" title="Drag row"><i data-lucide="grip-vertical"></i> Row</span><span class="st-sp"></span>'
      +'<button type="button" class="st-tbtn" data-strow="width" title="Toggle full-page width"><i data-lucide="'+(full?'minimize-2':'maximize-2')+'"></i></button>'
      +'<div class="st-presets"><button type="button" data-cols="12">1</button><button type="button" data-cols="6,6">2</button><button type="button" data-cols="4,4,4">3</button><button type="button" data-cols="3,3,3,3">4</button><button type="button" data-cols="8,4">⅔·⅓</button><button type="button" data-cols="4,8">⅓·⅔</button></div>'
      +'<button type="button" class="st-rowai" data-strow="ai" title="Build this row with AI"><i data-lucide="sparkles"></i> AI</button>'
      +'<button type="button" class="st-tbtn st-del" data-strow="del" title="Delete row"><i data-lucide="trash-2"></i></button></div>'
      +'<div class="st-cols"><div class="st-col" data-span="12" style="--span:12" data-id="'+rnd('c')+'"><div class="st-col-blocks"></div></div></div>';
    rowsEl.appendChild(row); initColSortable(row.querySelector('.st-col-blocks'));
    refresh(); updateEmpty();
    return row;
  }
  document.getElementById('st-addrow').addEventListener('click',function(){
    var row=createRow(); dirtyMark(); row.scrollIntoView({behavior:'smooth',block:'center'});
  });
  function updateEmpty(){
    var empty=document.getElementById('cv-empty');
    if(empty) empty.hidden=rowsEl.children.length>0;
  }
  var emptyAi=document.getElementById('cv-empty-ai');
  if(emptyAi) emptyAi.addEventListener('click',function(){ openAi('page'); });
  var emptyRow=document.getElementById('cv-empty-row');
  if(emptyRow) emptyRow.addEventListener('click',function(){ var r=createRow(); dirtyMark(); r.scrollIntoView({behavior:'smooth'}); });

  /* ════════ drag & drop ════════ */
  function initColSortable(el){
    new Sortable(el,{group:'block',handle:'.st-drag',draggable:'.st-block',animation:150,ghostClass:'st-dragging',
      onAdd:function(evt){
        var it=evt.item;
        if(it.classList.contains('pal-tile')||it.classList.contains('pre')){
          var spec=specOf(it), idx=evt.newIndex; it.remove(); addBlock(el,spec,idx,true);
        } else dirtyMark();
      },
      onUpdate:dirtyMark,onRemove:dirtyMark});
  }
  rowsEl.querySelectorAll('.st-col-blocks').forEach(initColSortable);
  new Sortable(rowsEl,{group:'row',handle:'.st-row-drag',draggable:'.st-row',animation:150,onUpdate:dirtyMark});
  document.querySelectorAll('.pal-basics,.pal-pres').forEach(function(pl){
    new Sortable(pl,{group:{name:'block',pull:'clone',put:false},sort:false,draggable:'.pal-tile,.pre'});
  });
  function lastCol(){
    var cols=rowsEl.querySelectorAll('.st-col-blocks');
    if(!cols.length){ return createRow().querySelector('.st-col-blocks'); }
    return cols[cols.length-1];
  }
  document.querySelectorAll('.pal-tile,.pre').forEach(function(it){
    it.addEventListener('click',function(){ addBlock(lastCol(),specOf(it),null,true); });
  });

  /* palette search */
  document.getElementById('pal-q').addEventListener('input',function(){
    var q=this.value.trim().toLowerCase();
    document.querySelectorAll('.pal-tile,.pre').forEach(function(it){
      it.style.display=(!q||(it.dataset.name||'').indexOf(q)>-1)?'':'none';
    });
  });
  /* palette toggle (mobile) */
  document.getElementById('tb-burger').addEventListener('click',function(){ document.getElementById('studio').classList.toggle('pal-open'); });

  /* ════════ drawer field controls ════════ */
  drawerBody.addEventListener('input',function(e){
    var form=e.target.closest('.st-form');
    if(e.target.classList.contains('bp-color-pick')) e.target.closest('.bp-color').querySelector('.bp-color-hex').value=e.target.value;
    else if(e.target.classList.contains('bp-icon-name')){
      var p=e.target.closest('.bp-icon').querySelector('.bp-icon-prev'), v=e.target.value.trim().replace(/[^a-z0-9-]/g,'');
      p.innerHTML=v?'<i data-lucide="'+v+'"></i>':''; refresh();
    } else if(e.target.classList.contains('bp-image-url')){
      var pr=e.target.closest('.bp-image').querySelector('.bp-image-prev');
      pr.innerHTML=e.target.value?'<img src="'+e.target.value.replace(/"/g,'&quot;')+'">':'<i data-lucide="image"></i>'; refresh();
    }
    if(form&&form.dataset.for) renderBlock(form.dataset.for);
  });
  drawerBody.addEventListener('change',function(e){
    var form=e.target.closest('.st-form'); if(form&&form.dataset.for) renderBlock(form.dataset.for);
  });
  drawerBody.addEventListener('click',function(e){
    var form=e.target.closest('.st-form');
    if(e.target.closest('.bp-color-clear')){ e.target.closest('.bp-color').querySelector('.bp-color-hex').value=''; if(form) renderBlock(form.dataset.for); }
    else if(e.target.closest('.bp-image-clear')){
      var im=e.target.closest('.bp-image'); im.querySelector('.bp-image-url').value='';
      im.querySelector('.bp-image-prev').innerHTML='<i data-lucide="image"></i>'; refresh(); if(form) renderBlock(form.dataset.for);
    }
    var add=e.target.closest('.bp-rep-add');
    if(add){
      var rep=add.closest('.bp-rep'), tpl=rep.querySelector(':scope > .bp-rep-tpl'), items=rep.querySelector(':scope > .bp-rep-items');
      items.appendChild(tpl.content.firstElementChild.cloneNode(true)); refresh(); if(form) renderBlock(form.dataset.for);
    }
    var del=e.target.closest('.bp-rep-del');
    if(del){ del.closest('.bp-rep-item').remove(); if(form) renderBlock(form.dataset.for); }
  });
  drawerBody.addEventListener('change',function(e){
    if(!e.target.classList.contains('bp-image-file')) return;
    var f=e.target.files[0]; if(!f) return;
    openCrop(URL.createObjectURL(f),e.target.closest('.bp-image').querySelector('.bp-image-url'),null);
    e.target.value='';
  });

  /* ════════ crop (drawer fields + embed images) ════════ */
  var cropper=null,cropInput=null,cropImgEl=null;
  var cropModal=document.getElementById('crop-modal'),cropImg=document.getElementById('crop-img');
  function openCrop(src,input,imgEl){
    cropInput=input||null; cropImgEl=imgEl||null;
    cropModal.classList.add('open');
    if(cropper){cropper.destroy();cropper=null;}
    cropImg.src=src;
    cropper=new Cropper(cropImg,{viewMode:1,autoCropArea:1,background:true});
  }
  function closeCrop(){ if(cropper){cropper.destroy();cropper=null;} cropModal.classList.remove('open'); cropInput=null; cropImgEl=null; }
  document.getElementById('crop-x').addEventListener('click',closeCrop);
  document.getElementById('crop-cancel').addEventListener('click',closeCrop);
  document.getElementById('crop-apply').addEventListener('click',function(){
    if(!cropper) return;
    var canvas=cropper.getCroppedCanvas({maxWidth:2200,maxHeight:1600,imageSmoothingQuality:'high'});
    if(!canvas){closeCrop();return;}
    var input=cropInput,imgEl=cropImgEl;
    canvas.toBlob(function(blob){
      var fd=new FormData(); fd.append('file',blob,'image.jpg');
      toast('Uploading image…');
      fetch(UPLOAD,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF},body:fd}).then(function(r){return r.json();}).then(function(d){
        if(!d.url) return;
        if(input){
          input.value=d.url;
          input.closest('.bp-image').querySelector('.bp-image-prev').innerHTML='<img src="'+d.url+'">';
          refresh(); var f=input.closest('.st-form'); if(f) renderBlock(f.dataset.for);
        }
        if(imgEl){
          imgEl.src=d.url; imgEl.removeAttribute('srcset');
          var b=imgEl.closest('.st-block'); if(b) syncEmbed(b);
          toast('Image replaced');
        }
      }).catch(function(){toast('Upload failed',1);});
      closeCrop();
    },'image/jpeg',0.9);
  });

  /* ════════ Build with AI ════════ */
  var aiModal=document.getElementById('ai-modal');
  var aiMode='page',aiRow=null,aiPaymentBlockId=null;
  function openAi(mode,arg){
    aiMode=mode==='row'?'row':(mode==='payment'?'payment':'page');
    aiRow=(aiMode==='row')?(arg||null):null;
    aiPaymentBlockId=(aiMode==='payment')?(arg||null):null;
    document.getElementById('ai-title').textContent=
      aiMode==='payment'?'Design payment section with AI'
      :aiMode==='row'?'Build with AI — this row'
      :'Build with AI — whole page';
    document.getElementById('ai-step1').textContent=
      aiMode==='payment'?'Describe your payment section — what they pay for, the plan name & amount in ₹, who it is for, and the benefits / trust points to highlight'
      :aiMode==='row'?'Describe this section and paste its content / data'
      :'Describe the whole page and paste your content / data';
    if(aiMode==='payment'){ // fresh slate for a payment design
      document.getElementById('ai-content').value='';
      document.getElementById('ai-prompt-wrap').hidden=true;
      document.getElementById('ai-code').value='';
    }
    aiModal.classList.add('open'); refresh();
  }
  document.getElementById('tb-ai').addEventListener('click',function(){openAi('page');});
  // Per-section button rendered inside a payment block's settings drawer.
  document.addEventListener('click',function(e){
    var b=e.target.closest('[data-paysec-ai]'); if(!b) return;
    e.preventDefault(); openAi('payment',b.getAttribute('data-block'));
  });
  document.querySelectorAll('[data-ai-close]').forEach(function(b){b.addEventListener('click',function(){aiModal.classList.remove('open');});});
  aiModal.addEventListener('click',function(e){ if(e.target===aiModal) aiModal.classList.remove('open'); });

  document.getElementById('ai-gen').addEventListener('click',function(){
    var content=document.getElementById('ai-content').value.trim();
    document.getElementById('ai-prompt').value=buildAiPrompt(content,aiMode);
    document.getElementById('ai-prompt-wrap').hidden=false;
    document.getElementById('ai-prompt').scrollIntoView({behavior:'smooth',block:'nearest'});
  });
  document.getElementById('ai-copy').addEventListener('click',function(){
    var ta=document.getElementById('ai-prompt'); ta.select();
    var p=navigator.clipboard?navigator.clipboard.writeText(ta.value):Promise.reject();
    p.then(function(){toast('Prompt copied — paste it into any AI');})
     .catch(function(){ try{document.execCommand('copy');toast('Prompt copied');}catch(err){toast('Select and copy manually',1);} });
  });
  function cleanPastedCode(s){
    s=(s||'').trim();
    s=s.replace(/^`{3,}[a-zA-Z]*\s*\n?/,'').replace(/\n?`{3,}\s*$/,'').trim();
    var bm=s.match(/<body[^>]*>([\s\S]*?)<\/body>/i); if(bm) s=bm[1].trim();
    s=s.replace(/<!doctype[^>]*>/ig,'').replace(/<\/?(html|head|body)\b[^>]*>/ig,'').trim();
    return s;
  }
  function extractPaymentSpec(s){
    var source=s||'', match=source.match(/<!--\s*ODA_PAYMENT\s+(\{[\s\S]*?\})\s*-->/i);
    if(!match) return {code:source,spec:null};
    try{
      return {code:source.replace(match[0],'').trim(),spec:JSON.parse(match[1])};
    }catch(error){
      return {code:source,spec:null,error:true};
    }
  }
  document.getElementById('ai-add').addEventListener('click',function(){
    var extracted=extractPaymentSpec(document.getElementById('ai-code').value);
    if(extracted.error){ toast('The AI payment settings could not be read. Ask the AI to regenerate the code.',1); return; }
    var code=cleanPastedCode(extracted.code);
    if(!code&&!extracted.spec){ toast('Paste the AI-generated code first',1); return; }

    // Payment mode: configure THIS payment block from the marker, and drop the
    // AI-designed copy in as an embed directly above it — no duplicate block.
    if(aiMode==='payment'&&aiPaymentBlockId){
      var target=document.querySelector('.st-block[data-id="'+aiPaymentBlockId+'"]');
      if(target){
        if(extracted.spec){ var pf=formFor(aiPaymentBlockId); if(pf){ applyPaymentSpec(pf,extracted.spec); renderBlock(aiPaymentBlockId); } }
        var pwork=[];
        if(code){
          var pcol=target.parentElement; // .st-col-blocks
          var pidx=Array.prototype.indexOf.call(pcol.children,target);
          pwork.push(addBlock(pcol,{type:'embed'},pidx,false,code));
        }
        aiModal.classList.remove('open');
        document.getElementById('ai-code').value='';
        Promise.all(pwork).then(function(){
          toast(extracted.spec?'Payment section designed — review and Save':'AI design added above the payment block — Save when ready');
          selectBlock(target); dirtyMark();
        }).catch(function(){ toast('Part of the AI design could not be added. Please try again.',1); });
        return;
      }
      // target gone — fall through to the generic add below
    }

    var col=null;
    if(code&&aiMode==='row'&&aiRow&&document.contains(aiRow)){
      col=aiRow.querySelector('.st-col-blocks');
      // AI sections are designed full-bleed — widen the host row too.
      aiRow.dataset.width='full';
      var wbtn=aiRow.querySelector('[data-strow="width"]'); if(wbtn) wbtn.innerHTML='<i data-lucide="minimize-2"></i>';
    }
    var work=[];
    if(code){
      if(!col) col=createRow(true).querySelector('.st-col-blocks');
      work.push(addBlock(col,{type:'embed'},null,!extracted.spec,code));
    }
    if(extracted.spec){
      var paymentCol=createRow(false).querySelector('.st-col-blocks');
      work.push(addBlock(paymentCol,{type:'payment'},null,true,null,extracted.spec));
    }
    aiModal.classList.remove('open');
    document.getElementById('ai-code').value='';
    Promise.all(work).then(function(){
      toast(extracted.spec?'AI design added with a secure, editable payment block':'AI section added — click any text on it to edit, then Save');
    }).catch(function(){toast('Part of the AI design could not be added. Please try again.',1);});
  });
  function buildPaymentPrompt(content){
    var L=[
      'You are a senior front-end engineer and product designer. Build ONE polished, production-quality PAYMENT SECTION for my website — premium copy and layout that builds trust and drives the visitor to pay. I will paste it straight into my page builder.',
      '',
      '────────── WHAT THIS SECTION IS FOR ──────────',
      'Use this exact information — do not invent prices, names or links:',
      (content||'(Describe what the visitor is paying for, the plan name and amount in ₹, who it is for, and the benefits / trust points to highlight.)'),
      '',
      '────────── OUTPUT RULES (STRICT) ──────────',
      '1. Reply with RAW CODE ONLY. No markdown, no code fences, no commentary. First character must be "<".',
      '2. Do NOT output <!doctype>, <html>, <head>, <body>, or any navbar/header/footer. My site wraps your code with its own header, navigation and footer automatically.',
      '3. Emit exactly, in this order: one <link> (Poppins font), one <style>, one <section class="ai-sec"> (your designed copy/layout), then the ONE payment marker described below, then at most one <script>.',
      '',
      '────────── PAYMENT (THE MOST IMPORTANT RULE) ──────────',
      '• Do NOT build any real payment UI: no payment form, no card / UPI / netbanking fields, no checkout or Razorpay JavaScript, no QR code, no payment links. Collect nothing and link to no payment page.',
      '• Design ONLY the persuasive section AROUND the payment — headline, what is included, benefits, trust badges, FAQ, etc.',
      '• Immediately AFTER your closing <\/section> (and before any <script>), append EXACTLY ONE HTML comment marker on its own line. My page builder replaces this marker with the REAL secure Razorpay payment block (amount priced on my server, payment signature-verified). Never imitate that block yourself.',
      '<!-- ODA_PAYMENT {"eyebrow":"Secure payment","title":"Complete your payment","description":"Short supporting line","plan":"Plan name","amount":"9999","plan_description":"What this payment covers","badge":"Popular","layout":"split","button_label":"Pay securely","note":"Any tax / terms note","accent":"#F05A28","accent2":"#2B1FA8"} -->',
      'Fill the marker with MY plan name and amount. amount = plain INR digits only (e.g. "9999"), no symbol or commas. layout = "split", "centered" or "compact". I can add more plan options afterwards in the builder.',
      '',
      '────────── CSS SCOPING (so it never breaks my page) ──────────',
      '• Prefix EVERY selector with ".ai-sec" — e.g. ".ai-sec .grid{}", ".ai-sec h2{}". Never style html, body, *, :root, nav, footer or bare elements. Put CSS vars on ".ai-sec{ --x:… }", never :root. No @import.',
      '',
      '────────── DESIGN BAR (make it impressive) ──────────',
      '• Modern, premium, generous whitespace, clear hierarchy, a strong headline and an obvious path to pay.',
      '• Brand palette: indigo #2B1FA8, orange #F05A28, ink #181134 on light surfaces. Tasteful gradients, soft shadows, rounded corners (14–22px), inline-SVG icons, subtle hover + transition states.',
      '• Type: "Poppins"; fluid sizes via clamp(); generous line-height.',
      '',
      '────────── RESPONSIVE (required) ──────────',
      '• Mobile-first; must look great 320px → 1440px. Inner wrapper centered, max-width ~1200px, fluid padding. Every multi-column layout collapses to ONE column under 720px. img{max-width:100%}.',
      '',
      '────────── COPY THIS SHAPE EXACTLY ──────────',
      '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">',
      '<style>',
      '  .ai-sec{font-family:"Poppins",sans-serif;color:#181134}',
      '  .ai-sec .wrap{max-width:1200px;margin:0 auto;padding:clamp(48px,7vw,96px) clamp(16px,4vw,32px)}',
      '  /* …every other rule ALSO prefixed with .ai-sec … */',
      '  @media(max-width:720px){ .ai-sec [class*="grid"]{grid-template-columns:1fr} }',
      '<\/style>',
      '<section class="ai-sec">',
      '  <div class="wrap"> … your designed payment section built from MY info … <\/div>',
      '<\/section>',
      '<!-- ODA_PAYMENT {…as above, with MY plan name & amount…} -->',
      '<script>(function(){ /* optional, scoped to .ai-sec */ })();<\/script>'
    ];
    return L.join('\n');
  }
  function buildAiPrompt(content,mode){
    if(mode==='payment') return buildPaymentPrompt(content);
    var isPage=mode!=='row';
    var task=isPage
      ?'Build the COMPLETE BODY of a landing page — 3 to 6 distinct, polished sections (e.g. hero, features/cards, comparison or stats, testimonial, FAQ, closing call-to-action) chosen to fit my content. All sections live inside ONE wrapper.'
      :'Build ONE polished, production-quality website SECTION.';
    var shape=isPage
      ?['<div class="ai-sec">','  <section class="hero"> … </section>','  <section class="features"> … </section>','  <!-- more <section>s as needed -->','</div>']
      :['<section class="ai-sec">','  <div class="wrap"> … your design built from MY content … </div>','</section>'];
    var L=[
      'You are a senior front-end engineer and product designer. '+task+' Use HTML + scoped CSS + optional vanilla JS that I will paste straight into my page builder. Make it genuinely beautiful — not a rough draft.',
      '',
      '────────── CONTENT TO FEATURE ──────────',
      'Use this exact copy/data — do not invent lorem-ipsum or change my numbers, names or links:',
      (content||(isPage?'(Describe the page, its goal and audience, and paste the exact text / numbers / links for every section.)':'(Describe the section and paste the exact text / numbers / links you want shown.)')),
      '',
      '────────── OUTPUT RULES (STRICT) ──────────',
      '1. Reply with RAW CODE ONLY. No markdown, no triple-backtick code fences, no language tag, no commentary. Your very first character must be "<" and your reply must END with "<\/script>" (or the closing wrapper tag if you add no JS).',
      '2. Do NOT output <!doctype>, <html>, <head>, <body>, or any navbar/site-header/site-footer. My site automatically wraps your code with its own header, navigation and footer'+(isPage?' — a page hero section is fine, a nav bar is not.':'.'),
      '3. Emit exactly, in this order: one <link> (Poppins font), one <style>, one '+(isPage?'<div class="ai-sec"> containing your <section>s':'<section class="ai-sec">')+', and at most one <script>.',
      '4. PAYMENT SAFETY: If my content requests a price, fee, checkout, payment or Razorpay button, do NOT create a payment form, checkout JavaScript, QR code, payment link, or collect card/UPI details in your HTML. Design only the surrounding copy. Then append exactly one editable CMS marker after the closing wrapper (and before the optional script):',
      '<!-- ODA_PAYMENT {"eyebrow":"Secure payment","title":"Complete your payment","description":"Your short supporting copy","plan":"Application fee","amount":"9999","plan_description":"What this payment covers","badge":"Popular","layout":"split","button_label":"Pay securely","note":"Any terms or tax note","accent":"#F05A28","accent2":"#2B1FA8"} -->',
      'Use a plain INR amount such as "9999" with no currency symbol or commas. Use layout "split", "centered", or "compact". The page builder converts this marker into the real secure Razorpay block; never imitate that block yourself.',
      '',
      '────────── CSS SCOPING (so it never breaks my page) ──────────',
      '• Prefix EVERY selector with ".ai-sec" — e.g. ".ai-sec .grid{}", ".ai-sec h2{}". No exceptions.',
      '• Never style html, body, *, :root, nav, footer, or bare element selectors outside .ai-sec.',
      '• Put any CSS variables on ".ai-sec{ --x:… }", never on :root. No @import.',
      '',
      '────────── DESIGN BAR (make it impressive) ──────────',
      '• Modern, premium, generous whitespace, clear hierarchy'+(isPage?', a strong opening hero and a clear closing call-to-action. Vary section backgrounds (light / soft tint / gradient) so the page has rhythm.':', a strong headline and a clear call-to-action.'),
      '• Brand palette: indigo #2B1FA8, orange #F05A28, ink #181134 on light surfaces. Tasteful gradients, soft shadows, rounded corners (14–22px).',
      '• Real layout: CSS grid/flex, cards/badges/pills, inline-SVG icons, subtle hover + transition states.',
      '• Type: "Poppins"; fluid sizes via clamp(); generous line-height.',
      '',
      '────────── RESPONSIVE (required) ──────────',
      '• Mobile-first; must look great 320px → 1440px. Inner wrappers centered, max-width ~1200px, fluid padding.',
      '• Every multi-column layout collapses to ONE column under 720px. No fixed widths that overflow. img{max-width:100%}.',
      '',
      '────────── JS (only if it adds value) ──────────',
      '• One <script> at the very end, vanilla, wrapped in (function(){ … })(), querying only inside .ai-sec, and safe if elements are missing.',
      '',
      '────────── COPY THIS SHAPE EXACTLY ──────────',
      '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">',
      '<style>',
      '  .ai-sec{font-family:"Poppins",sans-serif;color:#181134}',
      '  .ai-sec .wrap{max-width:1200px;margin:0 auto;padding:clamp(48px,7vw,96px) clamp(16px,4vw,32px)}',
      '  /* …every other rule ALSO prefixed with .ai-sec … */',
      '  @media(max-width:720px){ .ai-sec [class*="grid"]{grid-template-columns:1fr} }',
      '<\/style>'
    ].concat(shape).concat([
      '<script>(function(){ /* optional, scoped to .ai-sec */ })();<\/script>',
      '',
      'Reminder: output the code only — start with "<link", end with "<\/script>". Absolutely no ``` anywhere.'
    ]);
    return L.join('\n');
  }

  /* ════════ device toggle & save ════════ */
  document.getElementById('tb-dev').addEventListener('click',function(e){
    var b=e.target.closest('[data-dev]'); if(!b) return;
    document.querySelectorAll('#tb-dev button').forEach(function(x){x.classList.toggle('is-active',x===b);});
    document.getElementById('cv').classList.toggle('is-phone',b.dataset.dev==='phone');
  });
  function savePage(){
    endInlineEdit(); parkDrawerForm();
    var payload={
      title:document.getElementById('bp-title').value,
      page_title:document.getElementById('bp-page-title').value,
      meta_description:document.getElementById('bp-meta-desc').value,
      visible:document.getElementById('bp-visible').checked?1:0,
      path:document.getElementById('bp-path').value,
      layout:buildLayout()
    };
    document.getElementById('tb-status').textContent='Saving…';
    fetch(SAVE,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json'},body:JSON.stringify(payload)})
      .then(function(r){return r.json().then(function(d){return {status:r.status,d:(d||{})};});})
      .then(function(res){
        var d=res.d;
        if(d.need_payment_otp){ document.getElementById('tb-status').textContent=''; openPayOtp(); return; }
        if(res.status<200||res.status>=300||d.ok===false){ document.getElementById('tb-status').textContent='Error'; toast(d.message||'Save failed',1); return; }
        isDirty=false;
        document.getElementById('tb-status').textContent='✓ Saved';
        toast(d.message||'Saved');
        if(d.path){
          if(d.path!==payload.path) toast('That URL was not available — kept '+d.path,1);
          document.getElementById('bp-path').value=d.path;
          document.getElementById('tb-view').href=d.path;
        }
      }).catch(function(){ document.getElementById('tb-status').textContent='Error'; toast('Save failed',1); });
  }

  /* ════════ payment-section authorization OTP ════════ */
  var payModal=document.getElementById('pay-otp-modal');
  function setPayStatus(msg,err){ var s=document.getElementById('payotp-status'); if(s){ s.textContent=msg||''; s.style.color=err?'#b42318':''; } }
  function openPayOtp(){
    document.getElementById('payotp-wrap').hidden=true;
    document.getElementById('payotp-code').value='';
    setPayStatus('');
    payModal.classList.add('open'); refresh();
  }
  function closePayOtp(){ payModal.classList.remove('open'); }
  document.querySelectorAll('[data-payotp-close]').forEach(function(b){b.addEventListener('click',closePayOtp);});
  payModal.addEventListener('click',function(e){ if(e.target===payModal) closePayOtp(); });
  document.getElementById('payotp-send').addEventListener('click',function(){
    var btn=this; btn.disabled=true; setPayStatus('Sending…');
    fetch(PAY_OTP_REQ,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json'},body:JSON.stringify({slug:PAGE_SLUG,title:document.getElementById('bp-title').value})})
      .then(function(r){return r.json();}).then(function(d){
        if(d.ok){ document.getElementById('payotp-wrap').hidden=false; document.getElementById('payotp-code').focus(); setPayStatus(d.message||'Code sent.'); }
        else { setPayStatus(d.message||'Could not send the code.',1); }
      }).catch(function(){ setPayStatus('Could not send the code.',1); }).finally(function(){ btn.disabled=false; });
  });
  document.getElementById('payotp-verify').addEventListener('click',function(){
    var code=document.getElementById('payotp-code').value.trim();
    if(!/^\d{6}$/.test(code)){ setPayStatus('Enter the 6-digit code.',1); return; }
    var btn=this; btn.disabled=true; setPayStatus('Verifying…');
    fetch(PAY_OTP_VERIFY,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json'},body:JSON.stringify({otp:code})})
      .then(function(r){return r.json();}).then(function(d){
        if(d.ok){ closePayOtp(); toast(d.message||'Authorized'); savePage(); }
        else { setPayStatus(d.message||'Incorrect code.',1); }
      }).catch(function(){ setPayStatus('Verification failed.',1); }).finally(function(){ btn.disabled=false; });
  });
  document.getElementById('payotp-code').addEventListener('keydown',function(e){ if(e.key==='Enter'){ e.preventDefault(); document.getElementById('payotp-verify').click(); } });

  document.getElementById('tb-save').addEventListener('click',savePage);
  document.addEventListener('keydown',function(e){
    if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'){ e.preventDefault(); savePage(); }
  });

  updateEmpty();
  refresh();
})();
</script>
</body>
</html>
