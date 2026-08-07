{{-- Career Counselling (/career-counselling) — the design the client shared for
     career counselling / assessments / guidance, rendered on the shared site
     layout so its navbar, footer and form popup match the rest of the site.

     Kept from the original: the navy WebGL globe hero (aurora, twinkling stars,
     drifting planes, shooting stars), the three alternating explainer blocks, the
     stage-tabbed plan cards with a session picker, the India map band and the
     contact cards.

     Changed on purpose:
       • the standalone header / nav / footer are dropped for the site's own
       • type follows the site's two faces (Cormorant Garamond for display
         headings, Manrope for everything else) instead of Space Grotesk / Inter
       • Material Symbols icons became the site's already-loaded Lucide set
       • every price now comes from the CMS (CareerCounsellingStore) instead of
         being hard-coded in the design's component state, and "Buy now" opens a
         real Razorpay checkout priced server-side — the browser only ever sends
         the option index it rendered
       • the three inline base64 images are served as files under
         assets/career-counselling/
       • a "book your consultation" form was added (the design had CTAs with
         nothing behind them) so the counselling CTAs record a CRM lead

     Everything is scoped under #cc-page so the design's generic names never
     collide with the shared styles.css / stripe-nav.css chrome. --}}
@extends('layouts.app')

@php
    use App\Support\CareerCounsellingStore;

    $heading = $cc['heading'] ?? [];
    $payCopy = $cc['payment'] ?? [];
    $accent = preg_match('/^#[0-9a-f]{6}$/i', (string) ($payCopy['accent'] ?? '')) ? $payCopy['accent'] : '#ff5e32';
    $stageList = $cc['stages'] ?? [];

    // Visible plans, in stored order — this order is authoritative for the
    // payment option indices the browser will send back.
    $visiblePlans = array_values(array_filter($cc['plans'] ?? [], fn ($p) => ($p['visible'] ?? true) && trim((string) ($p['name'] ?? '')) !== ''));

    // "plan index : tier index" → flat payable option index. Built from the
    // server's own enumeration (CareerCounsellingStore::payableOptions) rather
    // than re-derived here, so the index a button carries cannot drift from the
    // index the resolver will price. A tier missing from this map is priced 0 and
    // is not payable online.
    $optionIndex = [];
    foreach ($payableOptions as $option) {
        $optionIndex[$option['plan_index'].':'.$option['tier_index']] = $option['index'];
    }

    // Group the plans under their stage tab, keeping stored order within a stage.
    $byStage = [];
    foreach ($visiblePlans as $planIndex => $plan) {
        $byStage[(int) $plan['stage']][] = ['pi' => $planIndex, 'plan' => $plan];
    }
    $tabs = array_values(array_filter(array_keys($byStage), fn ($key) => isset($stageList[$key])));
    sort($tabs);

    $rupees = fn ($n) => '₹'.number_format((int) $n);

@endphp

@push('head')
@if($paymentEnabled)
  {{-- Razorpay Checkout is fetched on demand (see loadRazorpay), so warm its DNS
       + TLS handshake here instead: no bytes now, no cold connect later. --}}
  <link rel="preconnect" href="https://checkout.razorpay.com" crossorigin>
@endif
<style>
  /* The shared layout fades the body dark toward the footer; the design's cream
     runs unbroken instead, right down to the footer. */
  body.cc-page-body{ background:#fcf9f4; background-image:none; }

  #cc-page{
    --cc-navy:#0a004d; --cc-navy-mid:#12085f; --cc-navy-lift:#2a178d;
    --cc-orange:{{ $accent }};
    --cc-cream:#fcf9f4; --cc-ink:#1c1c19; --cc-muted:#474553;
    --cc-line:#e5e2dd; --cc-soft:#f6f3ee; --cc-edge:#c8c4d5;
    --cc-serif:"Cormorant Garamond", Georgia, serif;
    --cc-sans:"Manrope", system-ui, -apple-system, "Segoe UI", sans-serif;
    --cc-pad:clamp(20px, 5vw, 64px);
    font-family:var(--cc-sans);
    color:var(--cc-ink);
    background:var(--cc-cream);
    overflow-x:clip;
  }
  #cc-page *{ box-sizing:border-box; }
  #cc-page h1, #cc-page h2{ font-family:var(--cc-serif); font-weight:700; line-height:1.08; color:var(--cc-navy); margin:0; letter-spacing:-.01em; }
  #cc-page h3, #cc-page h4{ font-family:var(--cc-sans); font-weight:700; line-height:1.25; color:var(--cc-navy); margin:0; }
  #cc-page p{ margin:0; color:var(--cc-muted); line-height:1.65; }
  #cc-page a{ color:var(--cc-navy); text-decoration:none; }
  #cc-page a:hover{ color:var(--cc-orange); }
  #cc-page .cc-wrap{ max-width:1180px; margin:0 auto; padding-inline:var(--cc-pad); }
  #cc-page .cc-eyebrow{
    font-family:var(--cc-sans); font-size:12px; font-weight:800; letter-spacing:.14em;
    text-transform:uppercase; color:var(--cc-orange); margin:0 0 10px;
  }
  #cc-page .cc-btn{
    display:inline-flex; align-items:center; justify-content:center; gap:9px;
    font-family:var(--cc-sans); font-size:15.5px; font-weight:700; line-height:1;
    padding:16px 30px; border-radius:3px; border:1px solid transparent; cursor:pointer;
    transition:transform .18s ease, box-shadow .18s ease, background .18s ease, color .18s ease;
  }
  #cc-page .cc-btn i{ width:17px; height:17px; }
  #cc-page .cc-btn:hover{ transform:translateY(-2px); }
  #cc-page .cc-btn--orange{ background:var(--cc-orange); color:#fff; box-shadow:0 10px 26px -12px rgba(255,94,50,.85); }
  #cc-page .cc-btn--orange:hover{ color:#fff; box-shadow:0 16px 32px -12px rgba(255,94,50,.9); }
  #cc-page .cc-btn--navy{ background:var(--cc-navy); color:#fff; box-shadow:0 10px 26px -14px rgba(10,0,77,.9); }
  #cc-page .cc-btn--navy:hover{ color:#fff; }
  #cc-page .cc-btn--outline{ background:transparent; color:#fff; border-color:rgba(255,255,255,.7); }
  #cc-page .cc-btn--outline:hover{ background:#fff; color:var(--cc-navy); }
  #cc-page .cc-btn[disabled]{ opacity:.6; cursor:not-allowed; transform:none; }

  /* ── Reveal on scroll ── */
  #cc-page .cc-reveal{ opacity:0; transform:translateY(26px); transition:opacity .8s cubic-bezier(.2,.7,.2,1), transform .8s cubic-bezier(.2,.7,.2,1); }
  #cc-page .cc-reveal.is-in{ opacity:1; transform:none; }

  /* ══ Hero ══ */
  #cc-page .cc-hero{ position:relative; width:100%; overflow:hidden;
    background:radial-gradient(circle at top, var(--cc-navy-lift) 0%, var(--cc-navy-mid) 35%, var(--cc-navy) 100%); }
  #cc-page .cc-hero__layer{ position:absolute; inset:0; overflow:hidden; pointer-events:none; }
  #cc-page .cc-hero__aurora{ z-index:1; }
  /* will-change keeps each blurred layer rasterised once and moved on the
     compositor; without it the 60px blur is re-rendered as the layer drifts. */
  #cc-page .cc-hero__aurora span{ position:absolute; left:50%; width:160%; height:60%; border-radius:50%;
    filter:blur(60px); opacity:.35; transform:translateX(-50%); will-change:transform; }
  #cc-page .cc-hero__aurora span:nth-child(1){ top:-22%; background:radial-gradient(ellipse at center, rgba(111,130,255,.5) 0%, rgba(111,130,255,0) 70%); animation:ccAurora 16s ease-in-out infinite; }
  #cc-page .cc-hero__aurora span:nth-child(2){ top:-10%; background:radial-gradient(ellipse at center, rgba(175,200,255,.4) 0%, rgba(175,200,255,0) 70%); animation:ccAurora 22s ease-in-out infinite 3s; }
  #cc-page .cc-hero__aurora span:nth-child(3){ top:-28%; background:radial-gradient(ellipse at center, rgba(255,94,50,.18) 0%, rgba(255,94,50,0) 70%); animation:ccAurora 19s ease-in-out infinite 6s; }
  #cc-page .cc-globe{ position:absolute; inset:0; z-index:1; }
  #cc-page .cc-globe canvas{ width:100%; height:100%; display:block; }
  #cc-page .cc-hero__sky{ z-index:2; }
  #cc-page .cc-star{ position:absolute; border-radius:50%; background:#fff; }
  #cc-page .cc-star--sm{ width:3px; height:3px; box-shadow:0 0 6px 1px rgba(255,255,255,.8); }
  #cc-page .cc-star--lg{ width:5px; height:5px; box-shadow:0 0 10px 2px rgba(255,255,255,.9); }
  #cc-page .cc-mote{ position:absolute; width:4px; height:4px; border-radius:50%;
    background:rgba(175,200,255,.75); box-shadow:0 0 8px 2px rgba(175,200,255,.5); }
  /* left:0 + a translateX keyframe: the transform is what moves them (see
     ccFlyRight / ccFlyLeft), so `will-change` promotes each to its own layer. */
  #cc-page .cc-plane{ position:absolute; left:0; opacity:0; color:#fff; will-change:transform, opacity; }
  #cc-page .cc-plane i{ display:block; width:44px; height:44px;
    filter:drop-shadow(0 2px 4px rgba(0,0,0,.35)) drop-shadow(0 0 7px rgba(175,200,255,.45)); }
  #cc-page .cc-plane--lead{ color:var(--cc-orange); }
  #cc-page .cc-plane--lead i{ width:58px; height:58px;
    filter:drop-shadow(0 2px 4px rgba(0,0,0,.35)) drop-shadow(0 0 10px rgba(255,94,50,.55)); }
  #cc-page .cc-hero__veil{ position:absolute; inset:0; z-index:3; pointer-events:none;
    background:linear-gradient(to top, var(--cc-navy), transparent 50%, rgba(10,0,77,.4)); }
  #cc-page .cc-hero__inner{ position:relative; z-index:10; max-width:1180px; margin:0 auto;
    padding:clamp(88px,11vw,128px) var(--cc-pad) clamp(76px,9vw,104px);
    display:flex; flex-direction:column; align-items:center; text-align:center; }
  #cc-page .cc-hero__inner .cc-eyebrow{ color:#ffb59d; }
  /* max-width in ch, not px: the display serif runs narrow, so a px cap that
     looked right at 64px wrapped to five lines at the clamp's low end. */
  #cc-page .cc-hero h1{ color:#fff; font-size:clamp(38px,5.6vw,64px); max-width:26ch; margin-bottom:20px; }
  #cc-page .cc-hero__lead{ color:rgba(255,255,255,.78); font-size:clamp(16px,1.4vw,18.5px);
    max-width:60ch; text-align:center; margin-bottom:30px; }
  #cc-page .cc-hero__cta{ display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:15px; }
  #cc-page .cc-hero__cue{ margin-top:30px; color:rgba(255,255,255,.7); display:inline-flex;
    animation:ccBounce 2s ease-in-out infinite; }
  #cc-page .cc-hero__cue i{ width:28px; height:28px; }

  @keyframes ccAurora{ 0%,100%{ transform:translateX(-54%) translateY(0) scale(1); } 50%{ transform:translateX(-46%) translateY(18px) scale(1.08); } }
  @keyframes ccTwinkle{ 0%,100%{ opacity:.15; transform:scale(1); } 50%{ opacity:1; transform:scale(1.9); } }
  /* The planes cross on translateX, not `left`. Animating `left` on four
     absolutely-positioned elements forced a layout + paint on every frame of a
     continuous 18-24s loop; transform stays on the compositor. The 116vw span
     and the -8vw start keep the original off-screen entry and exit. */
  @keyframes ccFlyRight{ 0%{ opacity:0; transform:translate(-8vw,0) rotate(35deg); } 8%{ opacity:.55; } 92%{ opacity:.55; } 100%{ opacity:0; transform:translate(108vw,-60px) rotate(35deg); } }
  @keyframes ccFlyLeft{ 0%{ opacity:0; transform:translate(108vw,0) rotate(215deg) scaleX(-1); } 8%{ opacity:.45; } 92%{ opacity:.45; } 100%{ opacity:0; transform:translate(-8vw,50px) rotate(215deg) scaleX(-1); } }
  @keyframes ccMote{ 0%{ transform:translateY(0) translateX(0); opacity:0; } 10%{ opacity:.8; } 50%{ transform:translateY(-40px) translateX(14px); opacity:.9; } 90%{ opacity:.5; } 100%{ transform:translateY(-90px) translateX(-10px); opacity:0; } }
  @keyframes ccShoot{ 0%{ transform:translate(0,0); opacity:0; } 10%{ opacity:1; } 100%{ transform:translate(-320px,200px); opacity:0; } }
  @keyframes ccBounce{ 0%,100%{ transform:translateY(0); opacity:.6; } 50%{ transform:translateY(8px); opacity:1; } }

  /* ══ Explainer blocks ══ */
  #cc-page .cc-about{ padding:clamp(64px,8vw,96px) 0; background:#fff; border-bottom:1px solid var(--cc-line); }
  #cc-page .cc-about__stack{ max-width:1020px; margin:0 auto; display:flex; flex-direction:column; gap:clamp(56px,7vw,80px); }
  #cc-page .cc-block{ display:flex; flex-wrap:wrap; align-items:center; gap:clamp(28px,4vw,44px); }
  #cc-page .cc-block--flip{ flex-wrap:wrap-reverse; }
  #cc-page .cc-block__body{ flex:1 1 340px; min-width:min(100%,300px); }
  /* Tile size / radius / colours come from the site-wide "UNIFIED ICON-TILE
     SYSTEM" block at the end of styles.css (.cc-block__icon is listed there);
     only the layout it needs in this page lives here. Deliberately no ID-scoped
     background or icon-size rule: an `#cc-page` selector would outrank that
     shared layer and this tile would drift away from every other one. */
  #cc-page .cc-block__icon{ display:inline-flex; align-items:center; justify-content:center; margin-bottom:16px; }
  #cc-page .cc-block h3{ font-size:clamp(21px,2.1vw,25px); margin-bottom:12px; }
  #cc-page .cc-block__body p + p{ margin-top:12px; }
  #cc-page .cc-block__art{ flex:0 1 300px; min-width:min(100%,230px); }
  /* <picture> is inline by default, which would leave a baseline gap under it. */
  #cc-page .cc-block__art picture{ display:block; }
  #cc-page .cc-block__art img{ width:100%; height:auto; display:block; border-radius:14px;
    box-shadow:0 14px 34px rgba(10,0,77,.14); object-fit:cover; }

  /* ══ Plans & pricing ══ */
  #cc-page .cc-pricing{ padding:clamp(64px,8vw,96px) 0; background:var(--cc-cream); }
  #cc-page .cc-head{ text-align:center; max-width:640px; margin:0 auto clamp(34px,4vw,46px); }
  #cc-page .cc-head h2{ font-size:clamp(30px,3.6vw,44px); margin-bottom:14px; }
  #cc-page .cc-head p{ font-size:17.5px; }
  #cc-page .cc-tabs{ display:flex; justify-content:center; margin-bottom:clamp(30px,4vw,44px); }
  #cc-page .cc-tabs__inner{ display:inline-flex; background:var(--cc-soft); border:1px solid var(--cc-line);
    border-radius:10px; padding:5px; gap:5px; flex-wrap:wrap; justify-content:center; }
  #cc-page .cc-tab{ font-family:var(--cc-sans); font-size:15.5px; font-weight:700; color:var(--cc-navy);
    background:#fff; border:none; border-radius:7px; padding:12px 24px; cursor:pointer;
    transition:background .18s ease, color .18s ease, box-shadow .18s ease; }
  #cc-page .cc-tab:hover{ box-shadow:0 4px 14px -8px rgba(10,0,77,.5); }
  #cc-page .cc-tab.is-on{ background:#1a0088; color:#fff; box-shadow:0 8px 20px -12px rgba(26,0,136,.95); }
  #cc-page .cc-panel[hidden]{ display:none; }
  /* align-items:stretch (the default, restated for intent): a plan with a
     session picker is taller than one without, and `margin-top:auto` on the CTA
     only bottom-aligns the buttons if the cards themselves share a height. */
  #cc-page .cc-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr));
    gap:clamp(20px,2.6vw,32px); max-width:840px; margin:0 auto; align-items:stretch; }
  #cc-page .cc-plan{ position:relative; display:flex; flex-direction:column;
    background:#fff; border:1px solid var(--cc-edge); border-radius:14px; padding:clamp(24px,3vw,34px); }
  #cc-page .cc-plan--featured{ border:2px solid var(--cc-navy); box-shadow:0 20px 44px -28px rgba(10,0,77,.7); }
  #cc-page .cc-plan__badge{ position:absolute; top:-12px; right:22px; background:var(--cc-orange); color:#fff;
    font-size:11.5px; font-weight:800; letter-spacing:.07em; text-transform:uppercase;
    padding:5px 13px; border-radius:3px; }
  #cc-page .cc-plan h3{ font-size:23px; color:var(--cc-orange); margin-bottom:5px; }
  #cc-page .cc-plan__sub{ margin-bottom:20px; font-size:15px; }
  #cc-page .cc-plan__price{ display:flex; align-items:baseline; gap:9px; margin-bottom:22px; }
  #cc-page .cc-plan__amount{ font-family:var(--cc-serif); font-size:clamp(34px,3.6vw,42px); font-weight:700;
    line-height:1; color:var(--cc-ink); transition:transform .22s ease; }
  #cc-page .cc-plan__amount.is-bumped{ transform:scale(1.07); }
  #cc-page .cc-plan__unit{ font-size:13.5px; font-weight:600; color:var(--cc-muted); }
  #cc-page .cc-plan__list{ list-style:none; padding:0; margin:0 0 22px;
    display:flex; flex-direction:column; gap:12px; }
  #cc-page .cc-plan__list li{ display:flex; gap:9px; color:var(--cc-muted); font-size:14.8px; line-height:1.55; text-align:left; }
  #cc-page .cc-plan__list li i{ width:19px; height:19px; flex-shrink:0; margin-top:2px; color:var(--cc-navy); }
  #cc-page .cc-plan__list li strong{ color:var(--cc-ink); font-weight:700; }
  #cc-page .cc-plan__list li.is-locked{ opacity:.5; }
  #cc-page .cc-plan__list li.is-locked i{ color:#777584; }
  #cc-page .cc-plan__tiers{ margin-bottom:22px; }
  #cc-page .cc-plan__tiers-label{ font-size:11.5px; font-weight:800; letter-spacing:.07em;
    text-transform:uppercase; color:var(--cc-muted); margin:0 0 9px; }
  #cc-page .cc-plan__pills{ display:flex; flex-wrap:wrap; gap:10px; }
  #cc-page .cc-pill{ font-family:var(--cc-sans); font-size:13.5px; font-weight:700; color:var(--cc-navy);
    background:#fff; border:1px solid var(--cc-edge); border-radius:22px; padding:9px 17px; cursor:pointer;
    transition:background .16s ease, color .16s ease, border-color .16s ease; }
  #cc-page .cc-pill:hover{ border-color:var(--cc-navy); }
  #cc-page .cc-pill.is-on{ background:var(--cc-navy); border-color:var(--cc-navy); color:#fff; }
  #cc-page .cc-plan__cta{ width:100%; margin-top:auto; padding:14px; font-size:15.5px; }

  /* ══ Checkout dialog ══ */
  #cc-page .cc-modal{ position:fixed; inset:0; z-index:1200; display:flex; align-items:center;
    justify-content:center; padding:20px; opacity:0; transition:opacity .24s ease; }
  #cc-page .cc-modal[hidden]{ display:none; }
  #cc-page .cc-modal.is-open{ opacity:1; }
  #cc-page .cc-modal__scrim{ position:absolute; inset:0; background:rgba(12,6,40,.62); backdrop-filter:blur(3px); }
  #cc-page .cc-modal__card{ position:relative; width:min(100%,470px); max-height:90vh; overflow-y:auto;
    background:#fff; border-radius:16px; padding:clamp(24px,3vw,32px);
    box-shadow:0 30px 80px -24px rgba(10,0,77,.6); transform:translateY(16px) scale(.98);
    transition:transform .26s cubic-bezier(.2,.8,.3,1.1); }
  #cc-page .cc-modal.is-open .cc-modal__card{ transform:none; }
  #cc-page .cc-modal__x{ position:absolute; top:12px; right:12px; width:34px; height:34px; border-radius:9px;
    border:1px solid var(--cc-line); background:#fff; color:var(--cc-muted); cursor:pointer;
    display:inline-flex; align-items:center; justify-content:center; }
  #cc-page .cc-modal__x:hover{ border-color:var(--cc-orange); color:var(--cc-orange); }
  #cc-page .cc-modal__x i{ width:16px; height:16px; }
  #cc-page .cc-modal h3{ font-size:20px; margin-bottom:8px; padding-right:34px; }
  #cc-page .cc-modal__desc{ font-size:14.5px; margin-bottom:18px; text-align:left; }
  #cc-page .cc-sum{ display:flex; align-items:center; justify-content:space-between; gap:14px;
    background:var(--cc-soft); border:1px solid var(--cc-line); border-radius:11px;
    padding:14px 16px; margin-bottom:18px; }
  #cc-page .cc-sum small{ display:block; font-size:11.5px; font-weight:800; letter-spacing:.07em;
    text-transform:uppercase; color:var(--cc-muted); margin-bottom:3px; }
  #cc-page .cc-sum b{ font-size:14.5px; color:var(--cc-ink); font-weight:700; }
  #cc-page .cc-sum strong{ font-family:var(--cc-serif); font-size:27px; font-weight:700; color:var(--cc-navy); white-space:nowrap; }
  #cc-page .cc-field{ margin-bottom:13px; }
  #cc-page .cc-field label{ display:block; font-size:11.5px; font-weight:800; letter-spacing:.06em;
    text-transform:uppercase; color:var(--cc-muted); margin-bottom:6px; }
  #cc-page .cc-field input, #cc-page .cc-field select, #cc-page .cc-field textarea{
    width:100%; font-family:var(--cc-sans); font-size:15px; color:var(--cc-ink);
    background:#fff; border:1px solid var(--cc-edge); border-radius:9px; padding:12px 14px;
    transition:border-color .16s ease, box-shadow .16s ease; }
  #cc-page .cc-field input:focus, #cc-page .cc-field select:focus, #cc-page .cc-field textarea:focus{
    outline:none; border-color:var(--cc-orange); box-shadow:0 0 0 3px rgba(255,94,50,.16); }
  #cc-page .cc-field textarea{ resize:vertical; min-height:96px; }
  #cc-page .cc-modal__pay{ width:100%; margin-top:6px; }
  #cc-page .cc-modal__status{ font-size:13.5px; margin-top:11px; min-height:1.2em; text-align:left; }
  #cc-page .cc-modal__status.is-error{ color:#c0392b; }
  #cc-page .cc-modal__status.is-success{ color:#1f7a4d; }
  #cc-page .cc-modal__note{ font-size:12.5px; color:#777584; margin-top:12px; text-align:left; }
  #cc-page .cc-done{ text-align:center; }
  #cc-page .cc-done__mark{ width:58px; height:58px; margin:0 auto 16px; border-radius:50%;
    display:inline-flex; align-items:center; justify-content:center;
    background:rgba(31,122,77,.12); color:#1f7a4d; }
  #cc-page .cc-done__mark i{ width:29px; height:29px; }
  #cc-page .cc-done p{ text-align:center; }
  #cc-page .cc-done__id{ font-size:12.5px; color:#777584; margin-top:10px; }
  #cc-page .cc-done .cc-btn{ margin-top:20px; }

  /* ══ Across India ══
     A two-column band on a tinted surface. The artwork is a plain <img> now, so
     it carries its own srcset and lazy-loads; nothing here paints a large
     blurred or backdrop-filtered layer. */
  #cc-page .cc-india{ padding:clamp(60px,7.5vw,92px) 0; border-top:1px solid var(--cc-line);
    background:linear-gradient(180deg, var(--cc-soft) 0%, var(--cc-cream) 100%); }
  #cc-page .cc-india__grid{ display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1.04fr);
    gap:clamp(30px,4.5vw,64px); align-items:center; max-width:1080px; margin:0 auto; }
  #cc-page .cc-india__copy h2{ font-size:clamp(28px,3.4vw,42px); margin-bottom:16px; }
  #cc-page .cc-india__copy > p{ font-size:17px; }
  #cc-page .cc-india__copy .cc-btn{ margin-top:28px; }
  #cc-page .cc-india__facts{ list-style:none; padding:0; margin:26px 0 0;
    display:flex; flex-direction:column; gap:2px; }
  #cc-page .cc-india__facts li{ display:flex; gap:14px; align-items:flex-start;
    padding:14px 0; border-top:1px solid var(--cc-line); text-align:left; }
  #cc-page .cc-india__facts li:last-child{ border-bottom:1px solid var(--cc-line); }
  #cc-page .cc-india__facts i{ width:20px; height:20px; flex-shrink:0; margin-top:2px; color:var(--cc-orange); }
  #cc-page .cc-india__facts span{ font-size:14.6px; color:var(--cc-muted); line-height:1.5; }
  #cc-page .cc-india__facts strong{ display:block; font-size:15.4px; font-weight:700;
    color:var(--cc-navy); margin-bottom:2px; }
  /* The illustration's own backing is near-white, so it sits on a white rounded
     panel rather than straight on the tinted band — that turns what used to read
     as a seam into a deliberate edge. */
  #cc-page .cc-india__art{ background:#fff; border:1px solid var(--cc-line); border-radius:20px;
    padding:clamp(16px,2.2vw,28px); box-shadow:0 18px 44px -30px rgba(10,0,77,.5); }
  #cc-page .cc-india__art picture{ display:block; }
  #cc-page .cc-india__art img{ display:block; width:100%; height:auto; }

  /* ══ Consultation form ══ */
  #cc-page .cc-consult{ padding:clamp(64px,8vw,96px) 0; background:#fff; border-top:1px solid var(--cc-line); }
  #cc-page .cc-consult__grid{ display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1.05fr);
    gap:clamp(28px,4vw,54px); align-items:start; max-width:1020px; margin:0 auto; }
  #cc-page .cc-consult__copy h2{ font-size:clamp(28px,3.2vw,40px); margin-bottom:16px; }
  #cc-page .cc-consult__points{ list-style:none; padding:0; margin:22px 0 0;
    display:flex; flex-direction:column; gap:13px; }
  #cc-page .cc-consult__points li{ display:flex; gap:11px; font-size:15px; color:var(--cc-muted); line-height:1.55; text-align:left; }
  #cc-page .cc-consult__points i{ width:19px; height:19px; flex-shrink:0; margin-top:2px; color:var(--cc-orange); }
  #cc-page .cc-consult__form{ background:var(--cc-soft); border:1px solid var(--cc-line);
    border-radius:16px; padding:clamp(22px,3vw,32px); }
  #cc-page .cc-consult__row{ display:grid; grid-template-columns:1fr 1fr; gap:13px; }
  #cc-page .cc-consult__form .cc-btn{ width:100%; margin-top:6px; }
  #cc-page .cc-consult__small{ font-size:12.5px; color:#777584; margin-top:12px; text-align:left; }

  @media (max-width:900px){
    #cc-page .cc-consult__grid{ grid-template-columns:1fr; }
    /* Copy first, artwork under it — the illustration is decorative, so it
       should never push the heading and CTA down the page on a phone. */
    #cc-page .cc-india__grid{ grid-template-columns:1fr; }
    #cc-page .cc-india__art{ order:2; max-width:520px; margin:0 auto; }
  }
  @media (max-width:560px){
    #cc-page .cc-consult__row{ grid-template-columns:1fr; }
    #cc-page .cc-plan__badge{ right:16px; }
    #cc-page .cc-hero__cta .cc-btn{ width:100%; }
  }

  @media (prefers-reduced-motion: reduce){
    #cc-page .cc-hero__aurora span,
    #cc-page .cc-star, #cc-page .cc-mote, #cc-page .cc-plane, #cc-page .cc-hero__cue{ animation:none !important; }
    #cc-page .cc-reveal{ opacity:1; transform:none; transition:none; }
    #cc-page .cc-btn:hover{ transform:none; }
  }
</style>
@endpush

@section('content')
<main id="{{ $mainId ?? 'main' }}">
<div id="cc-page">

  {{-- ═══════════════════ Hero ═══════════════════ --}}
  <section class="cc-hero">
    <div class="cc-hero__layer cc-hero__aurora" aria-hidden="true"><span></span><span></span><span></span></div>

    {{-- The WebGL globe is decorative: if three.js fails to load the hero keeps
         its gradient, aurora and stars and nothing else changes. --}}
    <div class="cc-globe" aria-hidden="true" data-cc-globe><canvas data-cc-globe-canvas></canvas></div>

    <div class="cc-hero__layer cc-hero__sky" aria-hidden="true">
      <span class="cc-star cc-star--sm" style="top:14%; left:10%; animation:ccTwinkle 2.6s ease-in-out infinite .2s;"></span>
      <span class="cc-star cc-star--sm" style="top:22%; left:82%; animation:ccTwinkle 3.2s ease-in-out infinite .9s;"></span>
      <span class="cc-star cc-star--sm" style="top:8%;  left:46%; animation:ccTwinkle 2.2s ease-in-out infinite .4s;"></span>
      <span class="cc-star cc-star--sm" style="top:60%; left:8%;  animation:ccTwinkle 3.4s ease-in-out infinite .6s;"></span>
      <span class="cc-star cc-star--sm" style="top:70%; left:90%; animation:ccTwinkle 2.4s ease-in-out infinite 1.1s;"></span>
      <span class="cc-star cc-star--lg" style="top:18%; left:30%; animation:ccTwinkle 3.6s ease-in-out infinite .1s;"></span>
      <span class="cc-star cc-star--lg" style="top:76%; left:55%; animation:ccTwinkle 3.1s ease-in-out infinite 1.9s;"></span>
      <span class="cc-star cc-star--lg" style="top:30%; left:92%; animation:ccTwinkle 2.8s ease-in-out infinite .7s;"></span>
      <span class="cc-mote" style="top:20%; left:12%; animation:ccMote 9s ease-in-out infinite;"></span>
      <span class="cc-mote" style="top:65%; left:20%; animation:ccMote 11s ease-in-out infinite 1.5s;"></span>
      <span class="cc-mote" style="top:40%; left:78%; animation:ccMote 10s ease-in-out infinite .8s;"></span>
      <span class="cc-mote" style="top:80%; left:60%; animation:ccMote 12s ease-in-out infinite 2.2s;"></span>
      <span class="cc-plane cc-plane--lead" style="top:70%; animation:ccFlyRight 18s linear infinite;"><i data-lucide="plane"></i></span>
      <span class="cc-plane" style="top:22%; animation:ccFlyRight 24s linear infinite 6s;"><i data-lucide="plane"></i></span>
      <span class="cc-plane" style="top:48%; animation:ccFlyLeft 21s linear infinite 3s;"><i data-lucide="plane"></i></span>
      <span class="cc-plane" style="top:60%; animation:ccFlyLeft 23s linear infinite 9s;"><i data-lucide="plane"></i></span>
      <div style="position:absolute; inset:0; overflow:hidden;" data-cc-shoot></div>
    </div>

    <div class="cc-hero__veil" aria-hidden="true"></div>

    <div class="cc-hero__inner">
      <p class="cc-eyebrow">Career counselling &amp; assessments</p>
      <h1>Clarity, Confidence, and the Right Guidance for Your Future</h1>
      <p class="cc-hero__lead">Structured counselling, scientific assessments and continuous guidance — so every
        subject, stream and career decision is made on evidence rather than guesswork.</p>
      <div class="cc-hero__cta">
        <a class="cc-btn cc-btn--orange" href="#cc-consult"><i data-lucide="calendar-check"></i> Book a consultation</a>
        <a class="cc-btn cc-btn--outline" href="#cc-pricing"><i data-lucide="compass"></i> See plans &amp; pricing</a>
      </div>
      <a class="cc-hero__cue" href="#cc-about" aria-label="Scroll to what career counselling is"><i data-lucide="chevron-down"></i></a>
    </div>
  </section>

  {{-- ═══════════════════ What it is ═══════════════════ --}}
  <section class="cc-about" id="cc-about">
    <div class="cc-wrap">
      <div class="cc-about__stack">

        <div class="cc-block cc-reveal">
          <div class="cc-block__body">
            <span class="cc-block__icon" aria-hidden="true"><i data-lucide="messages-square"></i></span>
            <h3>What is career counselling?</h3>
            <p>Career counselling is a structured process that helps students and professionals identify the right
              academic and career path based on their interests, strengths, abilities and aspirations. Many students
              face real uncertainty while choosing subjects after Class 10, selecting a stream after Class 12, or
              deciding on higher education and career options.</p>
            <p>At One Degree Advisory, our experienced career counsellors work closely with every individual to
              understand their goals, address their concerns, and provide practical, unbiased guidance.</p>
          </div>
          {{-- WebP first, JPEG fallback: these are flat-ish poster art, which
               WebP encodes ~40% smaller at the same quality. --}}
          <div class="cc-block__art">
            <picture>
              <source type="image/webp" srcset="{{ asset('assets/career-counselling/counselling-clarity.webp') }}">
              <img src="{{ asset('assets/career-counselling/counselling-clarity.jpg') }}" width="700" height="874" loading="lazy" decoding="async"
                   alt="A counselling session turning a student's confusion into a clear plan">
            </picture>
          </div>
        </div>

        <div class="cc-block cc-block--flip cc-reveal">
          <div class="cc-block__body">
            <span class="cc-block__icon" aria-hidden="true"><i data-lucide="trending-up"></i></span>
            <h3>What are career assessments?</h3>
            <p>Career assessments are scientifically designed tools that identify a student's natural abilities,
              interests, personality traits and career preferences — making decisions objective and informed rather
              than a matter of opinion.</p>
            <p>We use comprehensive psychometric and aptitude assessments covering personality, aptitude, interests,
              emotional intelligence and work preferences, which produce personalised recommendations and a detailed
              written report.</p>
          </div>
          <div class="cc-block__art">
            <picture>
              <source type="image/webp" srcset="{{ asset('assets/career-counselling/assessment-report.webp') }}">
              <img src="{{ asset('assets/career-counselling/assessment-report.jpg') }}" width="700" height="874" loading="lazy" decoding="async"
                   alt="A career assessment report with recommended paths and a development roadmap">
            </picture>
          </div>
        </div>

        <div class="cc-block cc-reveal">
          <div class="cc-block__body">
            <span class="cc-block__icon" aria-hidden="true"><i data-lucide="route"></i></span>
            <h3>What is career guidance?</h3>
            <p>Career guidance goes beyond a single conversation: it is continuous support across a student's
              educational and professional journey, building the skills, knowledge and action plan that long-term
              goals actually require.</p>
            <p>We help students develop a personalised roadmap, prepare for admissions, explore scholarships,
              understand emerging careers and make strategic decisions at every stage.</p>
          </div>
          <div class="cc-block__art">
            <picture>
              <source type="image/webp" srcset="{{ asset('assets/career-counselling/guidance-roadmap.webp') }}">
              <img src="{{ asset('assets/career-counselling/guidance-roadmap.jpg') }}" width="700" height="700" loading="lazy" decoding="async"
                   alt="A mentor guiding a student along a mapped career roadmap">
            </picture>
          </div>
        </div>

      </div>
    </div>
  </section>

  {{-- ═══════════════════ Plans & pricing (CMS-managed) ═══════════════════ --}}
  <section class="cc-pricing" id="cc-pricing"
           data-cc-plans
           data-page-slug="{{ CareerCounsellingStore::PAGE_SLUG }}"
           data-block-id="{{ CareerCounsellingStore::BLOCK_ID }}">
    <div class="cc-wrap">
      @if(trim((string) ($heading['eyebrow'] ?? '')) !== '' || trim((string) ($heading['title'] ?? '')) !== '' || trim((string) ($heading['subtitle'] ?? '')) !== '')
        <div class="cc-head">
          @if(trim((string) ($heading['eyebrow'] ?? '')) !== '')
            <p class="cc-eyebrow">{{ $heading['eyebrow'] }}</p>
          @endif
          @if(trim((string) ($heading['title'] ?? '')) !== '')
            <h2>{{ $heading['title'] }}</h2>
          @endif
          @if(trim((string) ($heading['subtitle'] ?? '')) !== '')
            <p>{{ $heading['subtitle'] }}</p>
          @endif
        </div>
      @endif

      @if(count($tabs) > 1)
        <div class="cc-tabs">
          <div class="cc-tabs__inner" role="tablist" aria-label="School stage">
            @foreach($tabs as $key)
              <button type="button" class="cc-tab @if($loop->first) is-on @endif"
                      role="tab" id="cc-tab-{{ $key }}" aria-controls="cc-panel-{{ $key }}"
                      aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                      data-cc-tab="{{ $key }}">{{ $stageList[$key]['label'] }}</button>
            @endforeach
          </div>
        </div>
      @endif

      @foreach($tabs as $position => $stageKey)
        <div class="cc-panel" id="cc-panel-{{ $stageKey }}" role="tabpanel"
             aria-labelledby="cc-tab-{{ $stageKey }}" data-cc-panel="{{ $stageKey }}"
             @if($position > 0) hidden @endif>
          <div class="cc-grid">
            @foreach($byStage[$stageKey] as $entry)
              @php
                $plan = $entry['plan'];
                $planIndex = $entry['pi'];
                $tiers = $plan['tiers'] ?? [];
                // The tier a card opens on, and the amount printed above the list.
                $firstTier = $tiers[0] ?? ['label' => '', 'price' => 0];
              @endphp
              <div class="cc-plan @if($plan['featured'] ?? false) cc-plan--featured @endif cc-reveal"
                   data-cc-plan
                   data-plan-name="{{ $plan['name'] }}"
                   data-stage-label="{{ $stageList[$stageKey]['label'] }}">
                @if(trim((string) ($plan['badge'] ?? '')) !== '')
                  <span class="cc-plan__badge">{{ $plan['badge'] }}</span>
                @endif
                <h3>{{ $plan['name'] }}</h3>
                @if(trim((string) ($plan['subtitle'] ?? '')) !== '')
                  <p class="cc-plan__sub">{{ $plan['subtitle'] }}</p>
                @endif

                <div class="cc-plan__price">
                  <span class="cc-plan__amount" data-cc-amount>{{ (int) $firstTier['price'] > 0 ? $rupees($firstTier['price']) : 'On request' }}</span>
                  @if(trim((string) $firstTier['label']) !== '')
                    <span class="cc-plan__unit" data-cc-unit>{{ $firstTier['label'] }}</span>
                  @else
                    <span class="cc-plan__unit" data-cc-unit hidden></span>
                  @endif
                </div>

                @if(! empty($plan['features']))
                  <ul class="cc-plan__list">
                    @foreach($plan['features'] as $feature)
                      <li @class(['is-locked' => $feature['locked']])>
                        <i data-lucide="{{ $feature['locked'] ? 'lock' : 'check-circle-2' }}"></i>
                        <span>@if($feature['title'] !== '')<strong>{{ $feature['title'] }}</strong>@endif
                          @if($feature['title'] !== '' && $feature['text'] !== '') — @endif
                          {{ $feature['text'] }}</span>
                      </li>
                    @endforeach
                  </ul>
                @endif

                @if(count($tiers) > 1)
                  <div class="cc-plan__tiers">
                    <p class="cc-plan__tiers-label">Number of counselling sessions</p>
                    <div class="cc-plan__pills">
                      @foreach($tiers as $tierIndex => $tier)
                        <button type="button" class="cc-pill @if($tierIndex === 0) is-on @endif"
                                data-cc-tier
                                data-option="{{ $optionIndex[$planIndex.':'.$tierIndex] ?? -1 }}"
                                data-price="{{ (int) $tier['price'] }}"
                                data-amount="{{ (int) $tier['price'] > 0 ? $rupees($tier['price']) : 'On request' }}"
                                data-label="{{ $tier['label'] }}"
                                aria-pressed="{{ $tierIndex === 0 ? 'true' : 'false' }}">{{ $tier['label'] !== '' ? $tier['label'] : 'Option '.($tierIndex + 1) }}</button>
                      @endforeach
                    </div>
                  </div>
                @else
                  {{-- Single-tier plan: no picker, but the card still carries the
                       option it would buy so the CTA behaves identically. --}}
                  <span hidden data-cc-tier
                        data-option="{{ $optionIndex[$planIndex.':0'] ?? -1 }}"
                        data-price="{{ (int) $firstTier['price'] }}"
                        data-amount="{{ (int) $firstTier['price'] > 0 ? $rupees($firstTier['price']) : 'On request' }}"
                        data-label="{{ $firstTier['label'] }}"></span>
                @endif

                <button type="button" class="cc-btn cc-btn--navy cc-plan__cta" data-cc-buy>
                  <span data-cc-buy-label>{{ (int) $firstTier['price'] > 0 && $paymentEnabled ? 'Buy now' : ($payCopy['enquiry_label'] ?? 'Request a callback') }}</span>
                </button>
              </div>
            @endforeach
          </div>
        </div>
      @endforeach

      @if($tabs === [])
        <p style="text-align:center;">Our counselling plans are being updated. Please
          <a href="#cc-consult">request a callback</a> and we will share current pricing.</p>
      @endif
    </div>
  </section>

  {{-- ═══════════════════ Checkout dialog ═══════════════════ --}}
  <div class="cc-modal" data-cc-modal hidden>
    <div class="cc-modal__scrim" data-cc-close></div>
    <div class="cc-modal__card" role="dialog" aria-modal="true" aria-labelledby="cc-modal-title" tabindex="-1">
      <button type="button" class="cc-modal__x" data-cc-close aria-label="Close"><i data-lucide="x"></i></button>

      <div data-cc-modal-form>
        <h3 id="cc-modal-title">{{ $payCopy['title'] ?? 'Confirm your counselling plan' }}</h3>
        @if(trim((string) ($payCopy['description'] ?? '')) !== '')
          <p class="cc-modal__desc">{{ $payCopy['description'] }}</p>
        @endif

        <div class="cc-sum">
          <span><small>Selected plan</small><b data-cc-sum-label></b></span>
          <strong data-cc-sum-amount></strong>
        </div>

        <div class="cc-field">
          <label for="cc-buyer-name">Student / parent name</label>
          <input type="text" id="cc-buyer-name" data-cc-name maxlength="160" required autocomplete="name">
        </div>
        <div class="cc-field">
          <label for="cc-buyer-email">Email</label>
          <input type="email" id="cc-buyer-email" data-cc-email maxlength="190" required autocomplete="email">
        </div>
        <div class="cc-field">
          <label for="cc-buyer-phone">Phone</label>
          <input type="tel" id="cc-buyer-phone" data-cc-phone maxlength="40" pattern="[0-9+()\-\s]{7,40}" required autocomplete="tel">
        </div>

        <button type="button" class="cc-btn cc-btn--orange cc-modal__pay" data-cc-pay>
          <i data-lucide="shield-check"></i> <span>{{ $payCopy['button_label'] ?? 'Pay securely' }}</span>
        </button>
        <p class="cc-modal__status" data-cc-status role="status" aria-live="polite"></p>
        @if(trim((string) ($payCopy['note'] ?? '')) !== '')
          <p class="cc-modal__note">{{ $payCopy['note'] }}</p>
        @endif
      </div>

      <div class="cc-done" data-cc-modal-done hidden>
        <span class="cc-done__mark" aria-hidden="true"><i data-lucide="check-circle-2"></i></span>
        <h3>Payment successful</h3>
        <p data-cc-done-msg></p>
        <p class="cc-done__id" data-cc-done-id></p>
        <button type="button" class="cc-btn cc-btn--navy" data-cc-close><span>Done</span></button>
      </div>
    </div>
  </div>

  {{-- ═══════════════════ Across India ═══════════════════
       A real two-column band, not a floating glass card over a full-bleed
       background image. That change is what lets the artwork be an <img> with a
       srcset (so retina gets the 2x file and everyone else does not pay for it),
       gives the copy a proper measure, and drops the backdrop-filter. --}}
  <section class="cc-india" id="cc-india">
    <div class="cc-wrap">
      <div class="cc-india__grid">
        <div class="cc-india__copy cc-reveal">
          <p class="cc-eyebrow">Nationwide reach</p>
          <h2>Career counselling across India</h2>
          <p>One Degree Advisory works with students, graduates and working professionals in every state — online
            from anywhere, or in person at our Jaipur office. Wherever the session happens, it is the same
            certified counsellors and the same written report.</p>

          <ul class="cc-india__facts">
            <li>
              <i data-lucide="video" aria-hidden="true"></i>
              <span><strong>Online or in person</strong>Video call, phone, or face to face in Jaipur.</span>
            </li>
            <li>
              <i data-lucide="languages" aria-hidden="true"></i>
              <span><strong>English &amp; Hindi</strong>Sessions run in whichever the student is comfortable in.</span>
            </li>
            <li>
              <i data-lucide="clock" aria-hidden="true"></i>
              <span><strong>Within one working day</strong>How quickly a counsellor comes back to you.</span>
            </li>
          </ul>

          <a class="cc-btn cc-btn--navy" href="#cc-consult"><i data-lucide="calendar-check"></i> Connect with us today</a>
        </div>

        {{-- Flat vector-style art, so WebP is both smaller and cleaner than the
             source JPEG; the 2x file is a Lanczos upscale with a light unsharp
             pass, which holds its hard edges on a retina screen where the 900px
             original was visibly soft. The JPEG stays as the fallback. --}}
        <div class="cc-india__art cc-reveal" aria-hidden="true">
          <picture>
            <source type="image/webp"
                    srcset="{{ asset('assets/career-counselling/india-map.webp') }} 900w,
                            {{ asset('assets/career-counselling/india-map@2x.webp') }} 1800w"
                    sizes="(max-width: 900px) 92vw, 46vw">
            <img src="{{ asset('assets/career-counselling/india-map.jpg') }}"
                 width="900" height="656" loading="lazy" decoding="async" alt="">
          </picture>
        </div>
      </div>
    </div>
  </section>

  {{-- ═══════════════════ Consultation request (CRM lead) ═══════════════════ --}}
  <section class="cc-consult" id="cc-consult">
    <div class="cc-wrap">
      <div class="cc-consult__grid">
        <div class="cc-consult__copy cc-reveal">
          <p class="cc-eyebrow">Book a consultation</p>
          <h2>Talk to a certified career counsellor</h2>
          <p>Tell us a little about the student and we will match them with the right counsellor. No obligation to
            buy a plan — the first conversation is about understanding where they are.</p>
          <ul class="cc-consult__points">
            <li><i data-lucide="badge-check"></i><span>Certified counsellors, not salespeople — guidance stays unbiased.</span></li>
            <li><i data-lucide="clock"></i><span>We respond within one working day to schedule the session.</span></li>
            <li><i data-lucide="users"></i><span>Sessions available for students, graduates and working professionals.</span></li>
          </ul>
        </div>

        <form class="cc-consult__form cc-reveal" method="POST"
              action="{{ route('career-counselling.lead') }}" data-career-counselling-form novalidate>
          @csrf
          <div class="cc-consult__row">
            <div class="cc-field">
              <label for="cc-lead-name">Full name</label>
              <input type="text" id="cc-lead-name" name="name" maxlength="120" required autocomplete="name">
            </div>
            <div class="cc-field">
              <label for="cc-lead-phone">Phone</label>
              <input type="tel" id="cc-lead-phone" name="phone" maxlength="40" autocomplete="tel">
            </div>
          </div>
          <div class="cc-field">
            <label for="cc-lead-email">Email</label>
            <input type="email" id="cc-lead-email" name="email" maxlength="190" required autocomplete="email">
          </div>
          <div class="cc-field">
            <label for="cc-lead-stage">Current stage</label>
            <select id="cc-lead-stage" name="stage">
              <option value="">Select a stage</option>
              @foreach($stageList as $stage)
                <option value="{{ $stage['label'] }}">{{ $stage['label'] }}</option>
              @endforeach
              <option value="Undergraduate">Undergraduate</option>
              <option value="Graduate / postgraduate">Graduate / postgraduate</option>
              <option value="Working professional">Working professional</option>
            </select>
          </div>
          <div class="cc-field">
            <label for="cc-lead-message">What would you like help with?</label>
            <textarea id="cc-lead-message" name="message" maxlength="2000"
                      placeholder="e.g. choosing a stream after Class 10, or deciding between engineering and design."></textarea>
          </div>
          <button type="submit" class="cc-btn cc-btn--orange"><i data-lucide="arrow-right"></i> <span>Request a callback</span></button>
          <p class="cc-consult__small">We use your details only to contact you about this enquiry.</p>
        </form>
      </div>
    </div>
  </section>

  {{-- The standalone "Prefer to reach out directly?" contact band was removed:
       the phone, email and office address are already in the shared footer on
       every page, and the consultation form above is the CTA this page wants. --}}

</div>
</main>

{{-- No third-party <script> here on purpose. The hero globe is drawn in plain
     canvas 2D below (it used to pull 150 KB of three.js from a CDN), and
     Razorpay Checkout is fetched the first time someone actually opens the
     checkout dialog — see loadRazorpay(). Most visitors never do, and it is
     60 KB from a third origin. --}}
<script>
(function () {
  'use strict';

  var root = document.getElementById('cc-page');
  if (!root) return;

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var plansRoot = root.querySelector('[data-cc-plans]');
  var paymentEnabled = @json((bool) $paymentEnabled);
  var orderUrl = @json(route('payments.order'));
  var confirmUrl = @json(route('payments.confirm'));

  /* ─────────────── Reveal on scroll ─────────────── */
  var reveals = root.querySelectorAll('.cc-reveal');
  if ('IntersectionObserver' in window && reveals.length && !reduceMotion) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) { entry.target.classList.add('is-in'); io.unobserve(entry.target); }
      });
    }, { threshold: 0.14 });
    reveals.forEach(function (el) { io.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add('is-in'); });
  }

  /* ─────────────── Shooting stars ─────────────── */
  var shootLayer = root.querySelector('[data-cc-shoot]');
  if (shootLayer && !reduceMotion) {
    var spawn = function () {
      var star = document.createElement('span');
      star.style.cssText = 'position:absolute;width:2px;height:2px;border-radius:50%;background:#fff;' +
        'box-shadow:0 0 6px 1px rgba(255,255,255,.9);top:' + (10 + Math.random() * 40) + '%;' +
        'left:' + (55 + Math.random() * 35) + '%;animation:ccShoot 1.3s linear forwards;';
      shootLayer.appendChild(star);
      setTimeout(function () { star.remove(); }, 1400);
      setTimeout(spawn, 10000 + Math.random() * 5000);
    };
    setTimeout(spawn, 3000);
  }

  /* ─────────────── Stage tabs ─────────────── */
  var tabs = root.querySelectorAll('[data-cc-tab]');
  var panels = root.querySelectorAll('[data-cc-panel]');
  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      var key = tab.getAttribute('data-cc-tab');
      tabs.forEach(function (t) {
        var on = t.getAttribute('data-cc-tab') === key;
        t.classList.toggle('is-on', on);
        t.setAttribute('aria-selected', String(on));
      });
      panels.forEach(function (panel) {
        panel.hidden = panel.getAttribute('data-cc-panel') !== key;
      });
      // Cards inside a panel that was hidden on load never crossed the observer.
      root.querySelectorAll('[data-cc-panel]:not([hidden]) .cc-reveal').forEach(function (el) {
        el.classList.add('is-in');
      });
    });
  });

  /* ─────────────── Session tier picker ─────────────── */
  function activeTier(card) {
    return card.querySelector('[data-cc-tier].is-on') || card.querySelector('[data-cc-tier]');
  }

  function syncCard(card) {
    var tier = activeTier(card);
    if (!tier) return;
    var amount = card.querySelector('[data-cc-amount]');
    var unit = card.querySelector('[data-cc-unit]');
    var label = card.querySelector('[data-cc-buy-label]');
    var payable = Number(tier.dataset.option) >= 0 && Number(tier.dataset.price) > 0;

    if (amount) {
      amount.textContent = tier.dataset.amount || amount.textContent;
      amount.classList.add('is-bumped');
      setTimeout(function () { amount.classList.remove('is-bumped'); }, 240);
    }
    if (unit) {
      unit.textContent = tier.dataset.label || '';
      unit.hidden = !tier.dataset.label;
    }
    if (label) {
      label.textContent = payable && paymentEnabled ? 'Buy now' : @json($payCopy['enquiry_label'] ?? 'Request a callback');
    }
  }

  root.querySelectorAll('[data-cc-plan]').forEach(function (card) {
    card.querySelectorAll('.cc-pill[data-cc-tier]').forEach(function (pill) {
      pill.addEventListener('click', function () {
        card.querySelectorAll('.cc-pill[data-cc-tier]').forEach(function (p) {
          p.classList.remove('is-on');
          p.setAttribute('aria-pressed', 'false');
        });
        pill.classList.add('is-on');
        pill.setAttribute('aria-pressed', 'true');
        syncCard(card);
      });
    });
  });

  /* ─────────────── Checkout dialog ─────────────── */
  var modal = root.querySelector('[data-cc-modal]');
  var modalForm = root.querySelector('[data-cc-modal-form]');
  var modalDone = root.querySelector('[data-cc-modal-done]');
  var sumLabel = root.querySelector('[data-cc-sum-label]');
  var sumAmount = root.querySelector('[data-cc-sum-amount]');
  var nameInput = root.querySelector('[data-cc-name]');
  var emailInput = root.querySelector('[data-cc-email]');
  var phoneInput = root.querySelector('[data-cc-phone]');
  var payBtn = root.querySelector('[data-cc-pay]');
  var status = root.querySelector('[data-cc-status]');
  var chosenOption = -1;
  var lastFocus = null;
  var closeTimer = null;

  function setStatus(message, tone) {
    if (!status) return;
    status.textContent = message || '';
    status.classList.toggle('is-error', tone === 'error');
    status.classList.toggle('is-success', tone === 'success');
  }

  function closeModal() {
    if (!modal || modal.hidden) return;
    modal.classList.remove('is-open');
    clearTimeout(closeTimer);
    closeTimer = setTimeout(function () { modal.hidden = true; }, 260);
    document.body.style.overflow = '';
    if (lastFocus && lastFocus.focus) { try { lastFocus.focus({ preventScroll: true }); } catch (e) { lastFocus.focus(); } }
  }

  function openModal(card, tier) {
    if (!modal) return;
    lastFocus = document.activeElement;
    chosenOption = Number(tier.dataset.option);
    var name = [card.dataset.stageLabel, card.dataset.planName].filter(Boolean).join(' · ');
    if (tier.dataset.label) name += ' (' + tier.dataset.label + ')';
    if (sumLabel) sumLabel.textContent = name;
    if (sumAmount) sumAmount.textContent = tier.dataset.amount || '';
    if (modalForm) modalForm.hidden = false;
    if (modalDone) modalDone.hidden = true;
    setStatus('', '');
    modal.hidden = false;
    clearTimeout(closeTimer);
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(function () {
      modal.classList.add('is-open');
      if (window.lucide) window.lucide.createIcons();
      if (nameInput) { try { nameInput.focus({ preventScroll: true }); } catch (e) {} }
    });
    // Cue the on-demand Razorpay fetch (see loadRazorpay).
    modal.dispatchEvent(new CustomEvent('cc:open'));
  }

  root.querySelectorAll('[data-cc-buy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var card = btn.closest('[data-cc-plan]');
      var tier = card && activeTier(card);
      if (!tier) return;

      // Not payable online (fee on request, or Razorpay unconfigured) → send
      // them to the consultation form instead of opening a dead checkout.
      if (!paymentEnabled || Number(tier.dataset.option) < 0 || Number(tier.dataset.price) <= 0) {
        var consult = document.getElementById('cc-consult');
        if (consult) consult.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
        var firstField = document.getElementById('cc-lead-name');
        if (firstField) setTimeout(function () { try { firstField.focus({ preventScroll: true }); } catch (e) {} }, 500);
        return;
      }

      openModal(card, tier);
    });
  });

  if (modal) {
    modal.addEventListener('click', function (event) {
      if (event.target.closest('[data-cc-close]')) closeModal();
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeModal();
    });
  }

  function csrf() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
  }

  function post(url, body) {
    return fetch(url, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify(body)
    }).then(async function (response) {
      var json = await response.json().catch(function () { return {}; });
      if (!response.ok) {
        var first = json.errors ? Object.values(json.errors).flat()[0] : '';
        throw new Error(first || json.message || 'The request could not be completed.');
      }
      return json;
    });
  }

  function valid(input) {
    if (!input || input.checkValidity()) return true;
    input.reportValidity();
    return false;
  }

  function showDone(message, paymentId) {
    if (modalForm) modalForm.hidden = true;
    if (!modalDone) return;
    modalDone.hidden = false;
    var msg = modalDone.querySelector('[data-cc-done-msg]');
    var id = modalDone.querySelector('[data-cc-done-id]');
    if (msg) msg.textContent = message || 'Payment verified successfully. Our team will contact you to schedule the session.';
    if (id) {
      id.textContent = paymentId ? 'Payment ID: ' + paymentId : '';
      id.hidden = !paymentId;
    }
    if (window.lucide) window.lucide.createIcons();
  }

  /* Razorpay Checkout, fetched the first time it is actually needed rather than
     on every page view — 60 KB from a third origin that most visitors never
     reach. The <link rel=preconnect> in the head means the handshake is already
     warm by the time this runs. Resolves immediately once loaded, and a second
     click while it is still in flight reuses the same promise. */
  var razorpayPromise = null;
  function loadRazorpay() {
    if (window.Razorpay) return Promise.resolve();
    if (razorpayPromise) return razorpayPromise;

    razorpayPromise = new Promise(function (resolve, reject) {
      var script = document.createElement('script');
      script.src = 'https://checkout.razorpay.com/v1/checkout.js';
      script.async = true;
      script.onload = function () {
        window.Razorpay ? resolve() : reject(new Error('Razorpay Checkout could not be loaded.'));
      };
      script.onerror = function () {
        // Let a later click retry instead of caching the failure forever.
        razorpayPromise = null;
        reject(new Error('Razorpay Checkout could not be loaded. Check your connection and try again.'));
      };
      document.head.appendChild(script);
    });

    return razorpayPromise;
  }

  // Start fetching as soon as the dialog opens, so the script downloads while
  // the customer is still typing their details.
  if (modal && paymentEnabled) {
    modal.addEventListener('cc:open', function () { loadRazorpay().catch(function () {}); });
  }

  if (payBtn && paymentEnabled) {
    payBtn.addEventListener('click', function () {
      if (chosenOption < 0) return;
      if (!valid(nameInput) || !valid(emailInput) || !valid(phoneInput)) return;

      var label = payBtn.querySelector('span');
      var original = label ? label.textContent : '';
      payBtn.disabled = true;
      if (label) label.textContent = 'Starting secure checkout…';
      setStatus('', '');

      // The order and the checkout script are independent — fetch both at once.
      Promise.all([
        post(orderUrl, {
          page_slug: plansRoot.dataset.pageSlug,
          block_id: plansRoot.dataset.blockId,
          option_index: chosenOption,
          name: nameInput.value.trim(),
          email: emailInput.value.trim(),
          phone: phoneInput.value.trim()
        }),
        loadRazorpay()
      ]).then(function (results) {
        var data = results[0];
        if (!window.Razorpay || !data.checkout) throw new Error('Razorpay Checkout could not be loaded.');
        var token = data.token;
        var checkout = data.checkout;
        var rzp = new window.Razorpay({
          key: checkout.key, amount: checkout.amount, currency: checkout.currency, order_id: checkout.order_id,
          name: checkout.name, description: checkout.description, image: checkout.image, prefill: checkout.prefill,
          theme: { color: checkout.theme_color, backdrop_color: checkout.backdrop_color },
          handler: function (response) {
            setStatus('Verifying payment…', '');
            post(confirmUrl, {
              token: token,
              razorpay_payment_id: response.razorpay_payment_id,
              razorpay_order_id: response.razorpay_order_id,
              razorpay_signature: response.razorpay_signature
            }).then(function (result) {
              setStatus('', '');
              showDone(result.message, result.payment_id);
            }).catch(function (error) { setStatus(error.message, 'error'); });
          },
          modal: { confirm_close: true, ondismiss: function () { setStatus('Checkout closed before payment. You can try again.', ''); } }
        });
        rzp.open();
      }).catch(function (error) {
        setStatus(error.message, 'error');
      }).finally(function () {
        payBtn.disabled = false;
        if (label) label.textContent = original;
      });
    });
  }

  /* ─────────────── Decorative globe (canvas 2D) ───────────────
     The design's globe is a wireframe sphere, two orbit rings, great-circle
     arcs and point markers — lines and dots, no textures, lighting or shading.
     That needs no 3D engine, so it is drawn directly instead: three.js cost
     150 KB gzipped from a third-party origin on every page view, more than the
     rest of the page put together, for a decoration.

     Orthographic projection is enough at this camera distance, and it buys the
     one thing the three.js version never had: cheap depth sorting, so the far
     half of the sphere is drawn dim BEHIND the globe body and the near half
     bright in front of it. That reads as a solid object rather than a
     see-through cage. */
  function initGlobe() {
    var container = root.querySelector('[data-cc-globe]');
    var canvas = root.querySelector('[data-cc-globe-canvas]');
    if (!container || !canvas || reduceMotion) return;

    var ctx = canvas.getContext('2d');
    if (!ctx) return;

    var TAU = Math.PI * 2;
    var TILT = 0.15;                       // the design's fixed x-rotation
    var cosT = Math.cos(TILT), sinT = Math.sin(TILT);
    var cx = 0, cy = 0, R = 0, dpr = 1;

    // Cheap devices get one device pixel per CSS pixel: the globe is a
    // background decoration, and 4x the fill cost is not worth it there.
    var maxDpr = (navigator.hardwareConcurrency || 4) <= 4 ? 1 : 2;

    function resize() {
      var w = container.clientWidth, h = container.clientHeight;
      if (!w || !h) return false;
      dpr = Math.min(window.devicePixelRatio || 1, maxDpr);
      canvas.width = Math.round(w * dpr);
      canvas.height = Math.round(h * dpr);
      cx = canvas.width / 2;
      cy = canvas.height / 2;
      // Matches the framing three.js gave at camera z=6.2 with a 45° fov:
      // a radius-2 sphere subtended ~0.39 of the viewport half-height.
      R = Math.min(canvas.width, canvas.height) * 0.385;
      return true;
    }

    // Rotate a unit vector by the current spin, apply the fixed tilt, and
    // project. Returns screen x/y plus z (>0 = near half, facing the viewer).
    function project(v, sinY, cosY) {
      var x = v[0] * cosY + v[2] * sinY;
      var z = v[2] * cosY - v[0] * sinY;
      var y = v[1] * cosT - z * sinT;
      return { x: cx + x * R, y: cy + y * R, z: z * cosT + v[1] * sinT };
    }

    function latLon(lat, lon) {
      var phi = (90 - lat) * Math.PI / 180;
      var theta = (lon + 180) * Math.PI / 180;
      var sp = Math.sin(phi);
      return [-sp * Math.cos(theta), Math.cos(phi), sp * Math.sin(theta)];
    }

    /* ── Geometry, built once ── */

    // Wireframe: 28 meridians × 20 parallels, the segment counts the three.js
    // SphereGeometry used, so the mesh density is unchanged.
    var wire = [];
    for (var m = 0; m < 28; m++) {
      var lon = (m / 28) * 360 - 180, line = [];
      for (var p = 0; p <= 20; p++) line.push(latLon(90 - (p / 20) * 180, lon));
      wire.push(line);
    }
    for (var p2 = 1; p2 < 20; p2++) {
      var lat = 90 - (p2 / 20) * 180, ring = [];
      for (var m2 = 0; m2 <= 28; m2++) ring.push(latLon(lat, (m2 / 28) * 360 - 180));
      wire.push(ring);
    }

    // Jaipur → the study destinations the advisory actually places students in.
    var ORIGIN = latLon(26.9, 75.8);
    var DESTINATIONS = [
      [51.5, -0.12], [38.9, -77.0], [45.4, -75.7], [-35.3, 149.1],
      [52.5, 13.4], [48.8, 2.3], [52.2, 21.0], [-41.3, 174.8],
      [25.2, 55.3], [40.4, -3.7], [55.7, 37.6], [41.7, 44.8], [51.2, 71.4]
    ].map(function (d) { return latLon(d[0], d[1]); });

    // Great-circle arcs, lifted off the surface by their span — the quadratic
    // bezier the original used, sampled once into plain point lists.
    var arcs = DESTINATIONS.map(function (end) {
      var mid = [0, 0, 0], span = 0, i;
      for (i = 0; i < 3; i++) {
        mid[i] = (ORIGIN[i] + end[i]) / 2;
        span += (ORIGIN[i] - end[i]) * (ORIGIN[i] - end[i]);
      }
      span = Math.sqrt(span);
      var lift = (1 + span * 0.45) / (Math.hypot(mid[0], mid[1], mid[2]) || 1);
      for (i = 0; i < 3; i++) mid[i] *= lift;

      var points = [];
      for (var t = 0; t <= 32; t++) {
        var u = t / 32, iu = 1 - u, a = iu * iu, b = 2 * iu * u, c = u * u;
        points.push([
          a * ORIGIN[0] + b * mid[0] + c * end[0],
          a * ORIGIN[1] + b * mid[1] + c * end[1],
          a * ORIGIN[2] + b * mid[2] + c * end[2]
        ]);
      }
      return points;
    });

    // Backdrop starfield. Fixed (it does not spin with the globe), so these are
    // plain screen-space fractions rather than projected 3D points.
    var stars = [];
    for (var s = 0; s < 90; s++) {
      stars.push([Math.random(), Math.random(), 0.35 + Math.random() * 0.5]);
    }

    /* ── Drawing ── */

    function strokePath(points, sinY, cosY, near) {
      // One path per half so each takes a single stroke() call: the near/far
      // split is what makes the sphere read as solid.
      ctx.beginPath();
      var drawing = false;
      for (var i = 0; i < points.length; i++) {
        var q = project(points[i], sinY, cosY);
        if ((q.z >= 0) !== near) { drawing = false; continue; }
        if (drawing) ctx.lineTo(q.x, q.y);
        else { ctx.moveTo(q.x, q.y); drawing = true; }
      }
      ctx.stroke();
    }

    function draw(time, spin) {
      var sinY = Math.sin(spin), cosY = Math.cos(spin);
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      // Fixed starfield.
      for (var i = 0; i < stars.length; i++) {
        ctx.fillStyle = 'rgba(255,255,255,' + (stars[i][2] * 0.4).toFixed(3) + ')';
        ctx.fillRect(stars[i][0] * canvas.width, stars[i][1] * canvas.height, dpr, dpr);
      }

      ctx.lineWidth = dpr;

      // FAR half — wireframe then arcs, dimmed, before the body is painted.
      ctx.strokeStyle = 'rgba(111,130,255,0.13)';
      for (i = 0; i < wire.length; i++) strokePath(wire[i], sinY, cosY, false);
      ctx.strokeStyle = 'rgba(255,94,50,0.10)';
      for (i = 0; i < arcs.length; i++) strokePath(arcs[i], sinY, cosY, false);

      // The globe body. The original built this from three overlapping spheres:
      // a solid navy interior, an outer blue atmosphere and a white back-side
      // shell that showed only at the limb. Seen from outside, a back-side shell
      // IS just a bright rim, so all three collapse to a fill plus two strokes.
      var halo = ctx.createRadialGradient(cx, cy, R * 0.80, cx, cy, R * 1.09);
      halo.addColorStop(0, 'rgba(175,200,255,0.20)');
      halo.addColorStop(1, 'rgba(175,200,255,0)');
      ctx.fillStyle = halo;
      ctx.beginPath(); ctx.arc(cx, cy, R * 1.09, 0, TAU); ctx.fill();

      // Slightly lighter at the centre than the edge, so it reads as a sphere
      // rather than the flat disc a single flat fill gave.
      var body = ctx.createRadialGradient(cx - R * 0.25, cy - R * 0.3, R * 0.1, cx, cy, R);
      body.addColorStop(0, 'rgba(32,17,124,0.92)');
      body.addColorStop(1, 'rgba(12,4,74,0.94)');
      ctx.fillStyle = body;
      ctx.beginPath(); ctx.arc(cx, cy, R * 0.985, 0, TAU); ctx.fill();

      // The limb: what the original's white back-side shell actually rendered as.
      ctx.strokeStyle = 'rgba(255,255,255,0.30)';
      ctx.lineWidth = 1.2 * dpr;
      ctx.beginPath(); ctx.arc(cx, cy, R * 0.99, 0, TAU); ctx.stroke();
      ctx.strokeStyle = 'rgba(175,200,255,0.22)';
      ctx.lineWidth = 3 * dpr;
      ctx.beginPath(); ctx.arc(cx, cy, R * 1.02, 0, TAU); ctx.stroke();

      // Orbit rings — tilted circles, so ellipses. They breathe on the same
      // sine the original animated the ring material opacity with.
      ctx.save();
      ctx.translate(cx, cy);
      var rings = [
        [R * 1.22, 0.15, 0.22 + Math.sin(time * 0.0007) * 0.08],
        [R * 1.32, -0.35, 0.12 + Math.sin(time * 0.0005 + 1.2) * 0.06]
      ];
      for (i = 0; i < rings.length; i++) {
        ctx.save();
        ctx.rotate(rings[i][1] - spin * 0.06);
        ctx.strokeStyle = 'rgba(255,94,50,' + rings[i][2].toFixed(3) + ')';
        ctx.lineWidth = 1.4 * dpr;
        ctx.beginPath();
        ctx.ellipse(0, 0, rings[i][0], rings[i][0] * 0.30, 0, 0, TAU);
        ctx.stroke();
        ctx.restore();
      }
      ctx.restore();

      // NEAR half — the bright wireframe and arcs, over the body.
      ctx.lineWidth = 1.1 * dpr;
      ctx.strokeStyle = 'rgba(126,146,255,0.52)';
      for (i = 0; i < wire.length; i++) strokePath(wire[i], sinY, cosY, true);
      ctx.strokeStyle = 'rgba(255,94,50,0.50)';
      for (i = 0; i < arcs.length; i++) strokePath(arcs[i], sinY, cosY, true);

      // Destination markers, then Jaipur — only while facing the viewer.
      ctx.fillStyle = 'rgba(255,255,255,0.85)';
      for (i = 0; i < DESTINATIONS.length; i++) {
        var d = project(DESTINATIONS[i], sinY, cosY);
        if (d.z < 0) continue;
        ctx.beginPath(); ctx.arc(d.x, d.y, 2.1 * dpr, 0, TAU); ctx.fill();
      }
      var o = project(ORIGIN, sinY, cosY);
      if (o.z >= 0) {
        // A pulsing halo makes the home marker findable as the globe turns.
        var pulse = 4.6 + Math.sin(time * 0.0022) * 1.5;
        ctx.fillStyle = 'rgba(255,94,50,0.22)';
        ctx.beginPath(); ctx.arc(o.x, o.y, pulse * dpr, 0, TAU); ctx.fill();
        ctx.fillStyle = '#ff5e32';
        ctx.beginPath(); ctx.arc(o.x, o.y, 3.1 * dpr, 0, TAU); ctx.fill();
      }
    }

    if (!resize()) return;
    window.addEventListener('resize', function () { resize(); }, { passive: true });

    // Drag to spin.
    var dragRot = 0, dragging = false, lastX = 0;
    container.style.pointerEvents = 'auto';
    container.addEventListener('pointerdown', function (event) { dragging = true; lastX = event.clientX; });
    window.addEventListener('pointerup', function () { dragging = false; });
    window.addEventListener('pointermove', function (event) {
      if (!dragging) return;
      dragRot += (event.clientX - lastX) * 0.004;
      lastX = event.clientX;
    }, { passive: true });

    // Pause whenever the hero is off-screen or the tab is hidden — a spinning
    // globe nobody can see is pure battery drain.
    var visible = true;
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (entries) {
        visible = entries[0].isIntersecting;
      }, { threshold: 0 }).observe(container);
    }

    var autoRot = 0;
    (function frame(time) {
      requestAnimationFrame(frame);
      if (!visible || document.hidden) return;
      autoRot += 0.0016;
      draw(time || 0, autoRot + dragRot);
    })(0);
  }

  // Decoration: start it once the page has finished its real work, so it never
  // competes with first paint.
  function startGlobe() {
    if (window.requestIdleCallback) window.requestIdleCallback(initGlobe, { timeout: 1200 });
    else setTimeout(initGlobe, 200);
  }
  if (document.readyState === 'complete') startGlobe();
  else window.addEventListener('load', startGlobe);

  // Draw this page's icons. Lucide loads deferred too, so try now and again on
  // load as a fallback.
  function drawIcons() { if (window.lucide) window.lucide.createIcons(); }
  drawIcons();
  window.addEventListener('load', drawIcons);
})();
</script>
@endsection
