@once
<style>
/* ══════════════════════════════════════════════════════════════════
   Test-Prep "Compare & enrol" section — fully self-contained.
   Scoped under .tpc so it never collides with the edwise page styles.
   ══════════════════════════════════════════════════════════════════ */
.tpc{
  --tpc-navy-deep:#12082E; --tpc-navy:#1B0F5C; --tpc-ink:#150C46;
  --tpc-muted:#635D86; --tpc-line:#E6E2F5; --tpc-lav:#F5F3FC; --tpc-paper:#fff;
  --tpc-accent:#ff5a2e;
  /* Slightly darker shade of the accent for hovers, derived at runtime is
     not possible in CSS, so we lean on the navy for depth instead. */
  position:relative; padding:78px 0; background:var(--tpc-lav);
  font-family:'Poppins','Inter',system-ui,sans-serif; color:var(--tpc-ink);
  -webkit-font-smoothing:antialiased;
}
.tpc *{box-sizing:border-box;}
.tpc-wrap{max-width:1280px; margin:0 auto; padding:0 24px;}

/* ── Header ── */
.tpc-head{max-width:660px; margin:0 auto 40px; text-align:center;}
.tpc-eyebrow{display:inline-flex; align-items:center; gap:8px; background:#FFEEE7; color:#E8461C;
  padding:7px 14px; border-radius:999px; font-size:12px; font-weight:700; letter-spacing:.05em;
  text-transform:uppercase; margin-bottom:16px;}
.tpc-eyebrow--dark{background:rgba(255,255,255,.12); color:#FFB27A;}
.tpc-dot{width:6px; height:6px; border-radius:50%; background:var(--tpc-accent); display:inline-block;}
.tpc-title{font-size:clamp(26px,3.2vw,38px); font-weight:700; line-height:1.15; margin:0; color:var(--tpc-navy-deep);
  letter-spacing:-.01em;}
.tpc-sub{margin:12px 0 0; font-size:15.5px; line-height:1.6; color:var(--tpc-muted);}
.tpc-empty{text-align:center; color:var(--tpc-muted); padding:40px 0;}

/* ── Price / duration toggle ── */
.tpc-controls{display:flex; justify-content:center; gap:10px; margin:0 auto 34px;}
.tpc-chip{background:#fff; border:1.5px solid var(--tpc-line); border-radius:10px; padding:9px 16px;
  font:600 13px/'1' 'Poppins',sans-serif; color:var(--tpc-navy-deep); cursor:pointer; transition:all .18s ease;}
.tpc-chip:hover{border-color:var(--tpc-accent);}
.tpc-chip.is-active{border-color:var(--tpc-accent); color:#E8461C; background:#FFEEE7;}

.tpc-bar-badge{display:inline-block; margin-left:8px; font-size:10px; font-weight:700; letter-spacing:.04em;
  text-transform:uppercase; color:var(--tpc-accent); background:#FFEEE7; padding:2px 7px; border-radius:6px;
  vertical-align:middle;}
.tpc-muted{color:var(--tpc-muted); font-weight:500;}

/* ── Reveal animation (respects reduced-motion) ── */
.tpc .reveal{opacity:0; transform:translateY(22px); transition:opacity .6s ease, transform .6s ease;}
.tpc .reveal.in{opacity:1; transform:none;}
@media (prefers-reduced-motion: reduce){
  .tpc .reveal{opacity:1; transform:none; transition:none;}
  .tpc-bar-fill{transition:none !important;}
}

/* ═══════════ VARIANT 1 · BARS ═══════════ */
.tpc-bars{display:flex; flex-direction:column; gap:13px; max-width:1040px; margin:0 auto;}
.tpc-bar-row{display:grid; grid-template-columns:150px 1fr 96px; align-items:center; gap:16px;}
.tpc-bar-name{font-weight:700; font-size:13px; color:var(--tpc-navy-deep);}
.tpc-bar-track{background:#fff; border:1px solid var(--tpc-line); border-radius:8px; height:14px; overflow:hidden;}
.tpc-bar-fill{height:100%; border-radius:8px; width:0; background:linear-gradient(90deg, var(--tpc-navy), var(--tpc-accent)); transition:width .9s cubic-bezier(.2,.8,.2,1);}
.tpc-bar-row.in .tpc-bar-fill{width:var(--w);}
.tpc-bar-val{font-size:12.5px; color:var(--tpc-muted); text-align:right; font-variant-numeric:tabular-nums;
  font-weight:600;}

/* ═══════════ VARIANT 2 · CARDS ═══════════ */
.tpc-cards{display:grid; grid-template-columns:repeat(auto-fill,minmax(232px,1fr)); gap:18px;}
.tpc-card{position:relative; background:#fff; border:1px solid var(--tpc-line); border-radius:16px; padding:20px 20px 18px;
  display:flex; flex-direction:column; transition:transform .25s ease, box-shadow .25s ease;}
.tpc-card:hover{transform:translateY(-5px); box-shadow:0 22px 44px -22px rgba(18,8,46,.32);}
.tpc-card-badge{position:absolute; top:14px; right:14px; font-size:10px; font-weight:800; letter-spacing:.04em;
  text-transform:uppercase; color:#fff; background:var(--tpc-accent); padding:4px 9px; border-radius:999px;}
.tpc-card-top{display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:12px;}
.tpc-card-name{font-family:'Poppins'; font-weight:700; font-size:17px; color:var(--tpc-navy-deep);}
.tpc-card-price{font-family:'Poppins'; font-size:25px; font-weight:700; color:#E8461C; line-height:1.1; margin-bottom:14px;}
.tpc-card-price span{display:block; font-size:11.5px; color:var(--tpc-muted); font-weight:500; margin-top:2px;}
.tpc-card-price .tpc-card-onreq{font-size:17px; color:var(--tpc-navy); display:inline;}
.tpc-card-meta{display:flex; flex-direction:column; gap:7px; margin-bottom:16px;}
.tpc-card-meta span{display:flex; align-items:center; gap:8px; font-size:12.5px; color:var(--tpc-muted);}
.tpc-card-meta i{width:15px; height:15px; stroke-width:1.8;}
.tpc-card-btn{margin-top:auto; display:flex; align-items:center; justify-content:center; gap:7px;
  background:var(--tpc-navy-deep); color:#fff; border:none; border-radius:11px; padding:12px;
  font:700 13px 'Poppins',sans-serif; cursor:pointer; transition:background .2s ease;}
.tpc-card-btn:hover{background:var(--tpc-accent);}
.tpc-card-btn i{width:15px; height:15px;}

/* ═══════════ VARIANT 3 · TABLE ═══════════ */
.tpc-table-scroll{overflow-x:auto; border:1px solid var(--tpc-line); border-radius:16px; background:#fff;}
.tpc-table{width:100%; border-collapse:collapse; min-width:640px;}
.tpc-table thead th{text-align:left; font-size:11px; font-weight:800; letter-spacing:.05em; text-transform:uppercase;
  color:var(--tpc-muted); padding:15px 18px; border-bottom:2px solid var(--tpc-line); background:var(--tpc-lav);}
.tpc-table th.tpc-num, .tpc-table td.tpc-num{text-align:right;}
.tpc-table tbody td{padding:14px 18px; border-bottom:1px solid var(--tpc-line); font-size:13.5px; color:var(--tpc-ink);
  vertical-align:middle;}
.tpc-table tbody tr:last-child td{border-bottom:none;}
.tpc-table tbody tr:hover{background:#faf9fe;}
.tpc-td-name{font-weight:700; color:var(--tpc-navy-deep);}
.tpc-td-price{font-variant-numeric:tabular-nums; font-weight:700; color:#E8461C;}
.tpc-table-btn{background:var(--tpc-navy-deep); color:#fff; border:none; border-radius:8px; padding:8px 14px;
  font:700 12px 'Poppins',sans-serif; cursor:pointer; transition:background .2s ease;}
.tpc-table-btn:hover{background:var(--tpc-accent);}

/* ═══════════ VARIANT 4 · TIER LIST ═══════════
   Bold full-width rows: name + duration on the left, a big price, an enrol
   button. Distinct from bars (chart), cards (grid) and table (dense data). */
.tpc-stack{display:flex; flex-direction:column; gap:12px; max-width:1040px; margin:0 auto;}
.tpc-tier{display:grid; grid-template-columns:1fr auto auto; align-items:center; gap:24px;
  padding:20px 24px; background:#fff; border:1px solid var(--tpc-line); border-left:4px solid var(--tpc-accent);
  border-radius:14px; box-shadow:0 10px 28px -22px rgba(18,8,46,.35);
  transition:transform .2s ease, box-shadow .2s ease;}
.tpc-tier:hover{transform:translateY(-3px); box-shadow:0 20px 40px -22px rgba(18,8,46,.4);}
.tpc-tier-lead{display:flex; flex-direction:column; gap:4px; min-width:0;}
.tpc-tier-name{font-family:'Poppins'; font-weight:700; font-size:19px; color:var(--tpc-navy-deep);}
.tpc-tier-meta{display:inline-flex; align-items:center; gap:7px; font-size:13px; color:var(--tpc-muted);}
.tpc-tier-meta i{width:15px; height:15px; stroke-width:1.8;}
.tpc-tier-price{font-family:'Poppins'; font-weight:700; font-size:26px; color:#E8461C; font-variant-numeric:tabular-nums;
  line-height:1; white-space:nowrap;}
.tpc-tier-btn{display:inline-flex; align-items:center; justify-content:center; gap:7px;
  background:var(--tpc-navy-deep); color:#fff; border:none; border-radius:10px; padding:12px 22px;
  font:700 13.5px 'Poppins',sans-serif; cursor:pointer; transition:background .2s ease; white-space:nowrap;}
.tpc-tier-btn:hover{background:var(--tpc-accent);}
.tpc-tier-btn i{width:15px; height:15px;}

/* ═══════════════════ PAYMENT ═══════════════════ */
.tpc-pay{margin-top:56px; display:grid; grid-template-columns:0.92fr 1.08fr; gap:0;
  border-radius:24px; overflow:hidden; box-shadow:0 34px 70px -30px rgba(18,8,46,.5);}
.tpc-pay-copy{background:var(--tpc-navy-deep); color:#fff; padding:40px 38px; position:relative; overflow:hidden;}
.tpc-pay-copy::after{content:''; position:absolute; right:-70px; bottom:-70px; width:240px; height:240px;
  border-radius:50%; background:radial-gradient(circle, color-mix(in srgb, var(--tpc-accent) 40%, transparent), transparent 65%);}
.tpc-pay-title{font-family:'Poppins'; color:#fff; font-size:24px; font-weight:700; margin:0 0 12px; position:relative; z-index:1;}
.tpc-pay-desc{color:#C9C4E8; font-size:14px; line-height:1.6; margin:0 0 24px; position:relative; z-index:1;}
.tpc-pay-trust{list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:12px; position:relative; z-index:1;}
.tpc-pay-trust li{display:flex; align-items:center; gap:11px; font-size:13.5px; font-weight:500; color:#EDEBFA;}
.tpc-pay-trust i{width:18px; height:18px; color:#7BE0C8; flex-shrink:0;}

/* ── Boarding-pass ticket (adapted from the site hero) ── */
.tpc-ticket{position:relative; z-index:1; background:#fff; color:var(--tpc-ink); border-radius:16px;
  margin:0 0 26px; box-shadow:0 24px 48px -20px rgba(0,0,0,.55); overflow:hidden;}
.tpc-ticket-top{padding:18px 20px 15px; border-bottom:1px dashed #D9D5EA; position:relative;}
/* Notch cut-outs at the perforation, tinted to the navy panel behind. */
.tpc-ticket-top::before, .tpc-ticket-top::after{content:''; position:absolute; bottom:-8px; width:16px; height:16px;
  border-radius:50%; background:var(--tpc-navy-deep);}
.tpc-ticket-top::before{left:-8px;} .tpc-ticket-top::after{right:-8px;}
.tpc-ticket-route{display:flex; align-items:flex-start; justify-content:space-between; position:relative; font-family:'Poppins';}
.tpc-ticket-goal{text-align:right;}
.tpc-ticket-city{font-family:'Poppins'; font-weight:700; font-size:20px; color:var(--tpc-navy-deep); line-height:1;}
.tpc-ticket-route small{display:block; font-size:9.5px; font-weight:600; letter-spacing:.07em; text-transform:uppercase;
  color:var(--tpc-muted); margin-top:5px;}
/* Dashed flight path with the plane flying HOME → GOAL along it. */
.tpc-ticket-line{position:absolute; left:26%; right:26%; top:11px; height:0; border-top:2px dashed #D9D5EA; z-index:1;}
.tpc-ticket-plane{position:absolute; top:50%; left:0; font-size:15px; line-height:1; color:var(--tpc-accent);
  background:#fff; padding:0 4px; /* ✈ points right toward GOAL */
  transform:translate(0,-50%) scaleX(-1); transform-origin:center;
  animation:tpcFly 3.6s cubic-bezier(.45,0,.55,1) infinite;}
@keyframes tpcFly{
  0%{left:0; transform:translate(0,-50%) scaleX(-1);}
  100%{left:100%; transform:translate(-100%,-50%) scaleX(-1);}
}
.tpc-ticket-body{padding:16px 20px 18px; display:grid; grid-template-columns:1fr 1fr; gap:14px 18px;}
.tpc-ticket-field small{display:block; font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.08em;
  color:var(--tpc-muted); margin-bottom:4px;}
.tpc-ticket-field span{font-family:'IBM Plex Mono','SFMono-Regular',monospace; font-weight:500; font-size:13.5px;
  color:var(--tpc-navy-deep); word-break:break-word;}
.tpc-ticket-ok{color:#1F9D55 !important;}
.tpc-ticket-barcode{height:30px; margin:0 20px 20px; border-radius:4px; opacity:.85;
  background:repeating-linear-gradient(90deg, var(--tpc-navy-deep) 0 2px, transparent 2px 5px, var(--tpc-navy-deep) 5px 7px, transparent 7px 11px);}
@media (prefers-reduced-motion: reduce){
  .tpc-ticket-plane{animation:none; left:50%; transform:translate(-50%,-50%) scaleX(-1);}
}

.tpc-pay-card{background:#fff; padding:36px 34px;}
.tpc-field-label{display:block; font-size:11.5px; font-weight:700; letter-spacing:.03em; text-transform:uppercase;
  color:var(--tpc-muted); margin-bottom:8px;}
.tpc-select-wrap{position:relative;}
/* Native <select> with a pure-CSS chevron baked into the background — no JS /
   icon-font dependency, so it's always centred on the right edge. */
.tpc-select{width:100%; appearance:none; -webkit-appearance:none; -moz-appearance:none;
  border:1.5px solid var(--tpc-line); border-radius:11px; padding:13px 42px 13px 14px;
  font:600 14px 'Poppins',sans-serif; color:var(--tpc-ink); background-color:var(--tpc-lav);
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23635D86' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat:no-repeat; background-position:right 14px center; background-size:16px;
  cursor:pointer; transition:border-color .15s, background-color .15s;}
.tpc-select:focus{outline:none; border-color:var(--tpc-accent); background-color:#fff;}
/* The dropdown list items (where supported) — keep them readable, not squished. */
.tpc-select option{color:var(--tpc-ink); background:#fff; font-weight:500; padding:8px;}

.tpc-pay-amount{display:flex; align-items:baseline; justify-content:space-between; gap:12px; margin:18px 0;
  padding:16px 18px; border-radius:12px; background:linear-gradient(120deg, #FFF4EE, #FFEAF0);
  border:1px solid #FFD9C4;}
.tpc-pay-amount-label{font-size:12.5px; font-weight:600; color:var(--tpc-muted); text-transform:uppercase; letter-spacing:.04em;}
.tpc-pay-amount-value{font-family:'Poppins'; font-size:28px; font-weight:700; color:#E8461C; font-variant-numeric:tabular-nums;
  transition:transform .25s ease;}
.tpc-pay-amount-value.tpc-bump{transform:scale(1.12);}

.tpc-pay-fields{display:flex; flex-direction:column; gap:12px; margin-bottom:18px;}
.tpc-pay-fields label{display:block;}
.tpc-pay-fields label span{display:block; font-size:11.5px; font-weight:700; letter-spacing:.03em; text-transform:uppercase;
  color:var(--tpc-muted); margin-bottom:6px;}
.tpc-pay-fields input{width:100%; border:1.5px solid var(--tpc-line); border-radius:10px; padding:11px 13px;
  font:500 14px 'Poppins',sans-serif; color:var(--tpc-ink); background:var(--tpc-lav); transition:border-color .15s;}
.tpc-pay-fields input:focus{outline:none; border-color:var(--tpc-accent); background:#fff;}

.tpc-pay-btn{width:100%; display:flex; align-items:center; justify-content:center; gap:9px;
  background:var(--tpc-accent); color:#fff; border:none; border-radius:999px; padding:15px;
  font:700 15px 'Poppins',sans-serif; cursor:pointer; text-decoration:none;
  box-shadow:0 16px 30px -14px color-mix(in srgb, var(--tpc-accent) 70%, transparent); transition:transform .15s ease, background .2s ease;}
.tpc-pay-btn:hover{transform:translateY(-2px);}
.tpc-pay-btn:disabled{opacity:.6; cursor:default; transform:none;}
.tpc-pay-btn i{width:17px; height:17px;}

.tpc-pay-status{margin:12px 0 0; font-size:13px; font-weight:600; min-height:1em; text-align:center;}
.tpc-pay-status.is-error{color:#c0392b;}
.tpc-pay-status.is-success{color:#146C37;}
.tpc-pay-note{display:flex; align-items:flex-start; gap:8px; margin:14px 0 0; font-size:12px; line-height:1.5;
  color:var(--tpc-muted);}
.tpc-pay-note i{width:14px; height:14px; margin-top:2px; flex-shrink:0;}

/* ── Success / result modal (shared, injected by JS) ── */
.tpc-result{position:fixed; inset:0; z-index:9998; display:flex; align-items:center; justify-content:center;
  padding:24px; opacity:0; pointer-events:none; transition:opacity .3s ease;}
.tpc-result.is-open{opacity:1; pointer-events:auto;}
.tpc-result__scrim{position:absolute; inset:0; background:rgba(18,8,46,.6); backdrop-filter:blur(3px);}
.tpc-result__card{position:relative; background:#fff; border-radius:20px; padding:36px 32px; max-width:400px; width:100%;
  text-align:center; box-shadow:0 40px 80px -20px rgba(0,0,0,.5); transform:translateY(16px) scale(.97); transition:transform .35s cubic-bezier(.2,.9,.3,1.2);}
.tpc-result.is-open .tpc-result__card{transform:none;}
.tpc-result__badge{width:64px; height:64px; margin:0 auto 18px; border-radius:50%; display:flex; align-items:center; justify-content:center;
  background:#EAFAF0; color:#1f9d57;}
.tpc-result__card h3{font-family:'Poppins'; font-size:21px; margin:0 0 8px; color:var(--tpc-navy-deep);}
.tpc-result__msg{color:var(--tpc-muted); font-size:14px; line-height:1.55; margin:0 0 6px;}
.tpc-result__id{font-size:12px; color:var(--tpc-muted); font-variant-numeric:tabular-nums; margin:0 0 20px;}
.tpc-result__done{background:var(--tpc-navy-deep); color:#fff; border:none; border-radius:999px; padding:12px 30px;
  font:700 14px 'Poppins',sans-serif; cursor:pointer;}
.tpc-result__done:hover{background:var(--tpc-accent);}

/* ── Responsive ── */
@media (max-width:820px){
  .tpc-pay{grid-template-columns:1fr;}
  .tpc-bar-row{grid-template-columns:104px 1fr 78px; gap:11px;}
  .tpc-bar-name{font-size:12px;}
}
@media (max-width:560px){
  .tpc{padding:60px 0;}
  .tpc-pay-copy, .tpc-pay-card{padding:28px 22px;}
  /* Tier-list rows stack: lead over price+button. */
  .tpc-tier{grid-template-columns:1fr auto; gap:12px 16px; padding:16px 18px;}
  .tpc-tier-lead{grid-column:1 / -1;}
}
</style>
@endonce
