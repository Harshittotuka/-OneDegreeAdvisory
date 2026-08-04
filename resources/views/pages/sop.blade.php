{{-- Statement of Purpose — SOP / admissions-writing studio landing page under
     the Student Hub. This is the design of the standalone sop.html the client
     shared, kept verbatim (indigo / brass palette + two interactive games + a
     GSAP handwriting animation), but rendered on the shared site layout so the
     navbar + footer match the rest of the site. The type is the one exception to
     "verbatim": the original's Special Elite / Space Grotesk / Inter / IBM Plex
     Mono / Caveat were swapped for the site stack (Cormorant Garamond / Manrope
     / Jost) so Student Hub reads as the same site as home and About. The whole page is scoped under #sop-page so its generic class
     names (.hero, .btn, .wrap, section{}, .service-card, table …) never collide
     with the global styles.css / stripe-nav.css that style the shared chrome.

     Functional changes from the original file:
       • the standalone header + footer are dropped in favour of the site's
       • the "Book your strategy call" form now POSTs to /statement-of-purpose/lead
         through the shared AJAX handler (wireFormSubmit → success/fail popup) and
         is stored as a lead (source = sop); its fake client-side submit is gone
       • the three embedded base64 images are served as files under assets/sop/ --}}
@extends('layouts.app')

@php
    $waE164   = config('site.contact.phone_e164', '918233365888');
    $waEmail  = config('site.contact.email', 'admissions@onedegreeadvisory.com');
    $waLink   = 'https://wa.me/'.$waE164.'?text='.rawurlencode('Hi One Degree Advisory, I would like to talk to an advisor about my Statement of Purpose / admissions writing.');
    $mailLink = 'mailto:'.$waEmail.'?subject='.rawurlencode('Statement of Purpose enquiry').'&body='.rawurlencode("Hi One Degree Advisory,\n\nI'd like help with my application writing. A few details:\n\nService needed (SOP / Visa SOP / LOR / Resume / Essay): \nTarget program & intake: \n\nThanks!");
@endphp

@push('head')
{{-- No font <link> here: the page now uses the site stack, already loaded by the
     shared layout. --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<style>
  /* Neutralise the site's body gradient (fades dark toward the footer) so the
     page's parchment colour runs uninterrupted down to the shared footer. */
  body.sop-page-body{
    background:#F4F1EC;
    background-image:
      radial-gradient(circle at 15% 8%, rgba(255,94,50,0.10), transparent 42%),
      radial-gradient(circle at 88% 26%, rgba(26,0,136,0.08), transparent 45%);
  }
  html{scroll-behavior:smooth;}

  /* ============================================================
     Everything below is scoped under #sop-page so it can never
     touch the shared navbar / footer (which sit outside it).
     ============================================================ */
  #sop-page{
    --ink:#1A0088;
    --ink-soft:#4E4A85;
    --parchment:#F4F1EC;
    --parchment-dark:#E7E2F5;
    --paper:#FFFFFF;
    --ribbon:#1A0088;
    --ribbon-light:#4B46B3;
    --brass:#FF5E32;
    --brass-light:#FF8760;
    --crimson:#FF5E32;
    --crimson-light:#FF8760;
    --shadow: 0 20px 60px -20px rgba(26,0,136,0.4);
    /* Site type stack — same as home / About, which use exactly two faces.
       --type was the display "typewriter" face and --mono a label/eyebrow role
       (never code), so all four roles collapse onto those two rather than
       pulling in a third and fourth family. */
    --serif: "Cormorant Garamond", Georgia, serif;
    --type: "Cormorant Garamond", Georgia, serif;
    --sans: "Manrope", system-ui, -apple-system, "Segoe UI", sans-serif;
    --mono: "Manrope", system-ui, -apple-system, "Segoe UI", sans-serif;
    color:var(--ink);
    font-family:var(--sans);
    line-height:1.6;
    overflow-x:hidden;
  }
  #sop-page *{margin:0;padding:0;box-sizing:border-box;}
  @media (prefers-reduced-motion: reduce){
    #sop-page *{animation-duration:0.01ms !important; animation-iteration-count:1 !important; transition-duration:0.01ms !important; scroll-behavior:auto !important;}
  }
  #sop-page a{color:inherit;text-decoration:none;}
  #sop-page ul{list-style:none;}
  #sop-page img, #sop-page svg{display:block;max-width:100%;}
  #sop-page .wrap{max-width:1180px;margin:0 auto;padding:0 32px;}
  #sop-page .eyebrow{
    font-family:var(--mono);
    font-size:12px;
    letter-spacing:0.14em;
    text-transform:uppercase;
    color:var(--crimson);
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:16px;
  }
  #sop-page .eyebrow::before{
    content:"";
    width:26px;height:1px;background:var(--crimson);
    display:inline-block;
  }
  {{-- 700, the weight every display heading on the site uses (Cormorant Garamond
       ships only 600 and 700). --}}
  #sop-page h1, #sop-page h2, #sop-page h3, #sop-page h4{font-family:var(--serif);font-weight:700;line-height:1.1;color:var(--ink);}
  #sop-page .btn{
    display:inline-flex;align-items:center;gap:10px;
    font-family:var(--sans);font-weight:600;font-size:14.5px;
    padding:14px 26px;border-radius:2px;cursor:pointer;
    border:1px solid var(--ink);
    transition:transform .35s cubic-bezier(.2,.8,.2,1), box-shadow .35s ease, background .35s ease, color .35s ease;
    letter-spacing:0.01em;
  }
  #sop-page .btn-primary{background:var(--ink);color:var(--parchment);}
  #sop-page .btn-primary:hover{transform:translateY(-3px);box-shadow:0 14px 30px -12px rgba(26,0,136,0.55);}
  #sop-page .btn-ghost{background:transparent;color:var(--ink);border-color:rgba(26,0,136,0.35);}
  #sop-page .btn-ghost:hover{background:var(--ink);color:var(--parchment);transform:translateY(-3px);}
  #sop-page .btn-brass{background:var(--brass);color:var(--ink);border-color:var(--brass);}
  #sop-page .btn-brass:hover{transform:translateY(-3px);box-shadow:0 14px 30px -12px rgba(255,94,50,0.6);}

  /* SECTION anchor offset — the site navbar is sticky, so in-page jumps
     (#services, #game, #reviews, #contact, #difference) must clear it. */
  #sop-page section{position:relative;padding:110px 0;scroll-margin-top:100px;}

  /* HERO */
  #sop-page .hero{
    position:relative;
    min-height:88vh;
    display:flex;align-items:center;
    padding:70px 0 90px;
    overflow:hidden;
  }
  #sop-page .hero .wrap{display:grid;grid-template-columns:1.05fr 0.95fr;gap:60px;align-items:center;}
  #sop-page .hero-copy .eyebrow{opacity:0;animation:sopRiseIn .8s ease forwards .15s;}
  #sop-page .hero h1{
    font-family:var(--type);
    font-size:clamp(28px, 3.6vw, 45px);
    line-height:1.28;
    opacity:0;
    animation:sopRiseIn .9s cubic-bezier(.2,.8,.2,1) forwards .3s;
    /* Reserve for the JS typing effect, so the paragraph below does not jump as
       characters appear. 4.6em was sized for the old typewriter face; on the
       serif this phrase measures 2 lines (2.56em) at every width from 390px to
       1440px, and the extra 2 lines of reserve read as a hole under the
       headline. Re-measure this if the phrase changes. */
    min-height:2.6em;
  }
  #sop-page .hero h1 .cursor{display:inline-block;width:0.5ch;background:var(--crimson);animation:sopBlink 0.9s steps(1) infinite;margin-left:1px;}
  #sop-page .hero h1 .accent{color:var(--crimson);}
  @keyframes sopBlink{50%{opacity:0;}}
  #sop-page .hero p.lead{
    margin-top:22px;font-size:18px;color:var(--ink-soft);max-width:480px;
    opacity:0;animation:sopRiseIn .9s ease forwards .5s;
  }
  #sop-page .hero-actions{margin-top:36px;display:flex;gap:16px;flex-wrap:wrap;opacity:0;animation:sopRiseIn .9s ease forwards .68s;}
  #sop-page .hero-stats{display:flex;gap:36px;margin-top:56px;opacity:0;animation:sopRiseIn .9s ease forwards .85s;flex-wrap:wrap;}
  #sop-page .hero-stats div{border-left:2px solid var(--brass);padding-left:14px;}
  #sop-page .hero-stats .num{font-family:var(--serif);font-size:30px;font-weight:700;color:var(--ink);}
  #sop-page .hero-stats .lbl{font-size:12px;color:var(--ink-soft);font-family:var(--mono);text-transform:uppercase;letter-spacing:.05em;}
  @keyframes sopRiseIn{from{opacity:0;transform:translateY(22px);}to{opacity:1;transform:translateY(0);}}

  /* HERO IMAGE */
  #sop-page .desk-stage{position:relative;width:100%;max-width:560px;min-height:340px;margin:0 auto;}
  #sop-page .desk-stage img{width:100%;height:auto;display:block;}
  #sop-page .hero-photo-frame{
    background:var(--paper);
    padding:10px;
    border-radius:4px;
    box-shadow:0 26px 46px rgba(26,0,136,0.32);
    border:1px solid rgba(26,0,136,0.12);
    position:relative;
  }
  #sop-page .hero-photo-frame::after{
    content:"";
    position:absolute;
    left:24px;right:24px;bottom:-6px;
    height:10px;
    background:var(--brass);
    border-radius:0 0 4px 4px;
    z-index:-1;
  }
  #sop-page .hero-photo-frame img{border-radius:2px;}

  /* SECTION GENERIC */
  #sop-page .section-head{max-width:640px;margin-bottom:56px;}
  #sop-page .section-head h2{font-size:clamp(30px,3.6vw,44px);}
  #sop-page .section-head p{margin-top:16px;color:var(--ink-soft);font-size:16.5px;}
  #sop-page .reveal{opacity:0;transform:translateY(28px);transition:opacity .8s cubic-bezier(.2,.8,.2,1), transform .8s cubic-bezier(.2,.8,.2,1);}
  #sop-page .reveal.in{opacity:1;transform:translateY(0);}

  /* SERVICES */
  #sop-page .services{background:var(--parchment-dark);color:var(--ink);padding:44px 0;}
  #sop-page .services .section-head{margin-bottom:32px;}
  #sop-page .services .section-head h2{color:var(--ink);font-size:clamp(24px,2.8vw,32px);}
  #sop-page .services .section-head p{color:var(--ink-soft);font-size:15px;margin-top:10px;}
  #sop-page .services .eyebrow{color:var(--crimson);margin-bottom:10px;}
  #sop-page .services .eyebrow::before{background:var(--crimson);}
  #sop-page .service-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:rgba(26,0,136,0.12);border:1px solid rgba(26,0,136,0.12);}
  #sop-page .service-card{
    background:var(--paper);padding:22px 20px;
    transition:background .4s ease;
    position:relative;overflow:hidden;
  }
  #sop-page .service-card:hover{background:#FFFFFF;box-shadow:inset 0 0 0 1px rgba(26,0,136,0.14);}
  #sop-page .service-card .icon{width:30px;height:30px;color:var(--crimson);margin-bottom:14px;transition:transform .4s cubic-bezier(.2,.8,.2,1);}
  #sop-page .service-card:hover .icon{transform:translateY(-4px) rotate(-4deg);}
  #sop-page .service-card .idx{font-family:var(--mono);font-size:10px;color:var(--crimson);letter-spacing:.1em;margin-bottom:6px;display:block;}
  #sop-page .service-card h3{font-family:var(--sans);font-weight:700;color:var(--ink);font-size:16px;margin-bottom:8px;line-height:1.25;}
  #sop-page .service-card p{color:var(--ink-soft);font-size:13px;line-height:1.5;}
  #sop-page .service-card-image{padding:0;min-height:160px;}
  #sop-page .service-card-image img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .5s cubic-bezier(.2,.8,.2,1);}
  #sop-page .service-card-image:hover img{transform:scale(1.04);}
  #sop-page .service-carousel-nav{display:none;}

  /* HANDWRITING COMPARISON */
  #sop-page .hw-compare{background:var(--parchment);}
  #sop-page .hw-compare .section-head p{max-width:640px;}
  #sop-page .hw-card{
    position:relative;
    background:rgba(255,255,255,0.72);
    backdrop-filter: blur(18px) saturate(160%);
    -webkit-backdrop-filter: blur(18px) saturate(160%);
    border-radius:6px;
    border:1px solid rgba(26,0,136,0.1);
    box-shadow:
      0 1px 2px rgba(26,0,136,0.05),
      0 24px 60px -20px rgba(26,0,136,0.22),
      0 8px 24px -8px rgba(26,0,136,0.1);
    padding:44px 40px 50px;
    overflow:hidden;
  }
  #sop-page .hw-rows{display:flex;flex-direction:column;position:relative;}
  #sop-page .hw-row{
    display:grid;
    grid-template-columns:240px 1fr;
    gap:28px;
    align-items:start;
    padding:16px 14px;
    border-radius:4px;
    border-bottom:1px solid rgba(26,0,136,0.08);
    position:relative;
    transition:box-shadow .35s ease, transform .35s ease, border-color .25s ease;
    will-change:transform;
  }
  #sop-page .hw-row:last-child{border-bottom:none;}
  #sop-page .hw-row:hover{
    border-color:rgba(255,94,50,0.35);
    box-shadow:0 10px 30px -14px rgba(26,0,136,0.18);
    transform:translateY(-1px);
  }
  #sop-page .hw-row::before{
    content:"";
    position:absolute;
    left:0;top:8px;bottom:8px;
    width:3px;
    border-radius:4px;
    background:var(--brass);
    transform:scaleY(0);
    transform-origin:top;
    transition:transform .28s ease;
  }
  #sop-page .hw-row:hover::before{transform:scaleY(1);}
  #sop-page .hw-title{
    font-family:var(--sans);
    font-weight:600;
    font-size:15.5px;
    color:var(--ink);
    padding-top:2px;
    opacity:0;
    transform:translateX(-6px);
  }
  #sop-page .hw-desc-cell{position:relative;min-height:26px;display:flex;align-items:flex-start;gap:10px;}
  #sop-page .hw-text-wrap{position:relative;flex:1;min-height:26px;}
  #sop-page .hw-cursive-text{
    /* Was Caveat cursive. Annotation copy, not a heading — it sits below the
       22px floor where home/About stop using the display serif, and it swaps to
       .hw-crisp-text (also --sans) as the reveal finishes, so the two halves of
       the animation have to share one face. */
    font-family:var(--sans);
    font-size:21px;
    font-weight:600;
    color:var(--ink-soft);
    white-space:pre-wrap;
    line-height:1.35;
    position:absolute;
    inset:0;
  }
  #sop-page .hw-cursive-text .ch{display:inline-block;opacity:0;transform:translateY(4px) scale(0.7) rotate(-2deg);}
  #sop-page .hw-crisp-text{
    font-family:var(--sans);
    font-weight:450;
    font-size:14.5px;
    line-height:1.5;
    color:var(--ink-soft);
    opacity:0;
    position:relative;
  }
  #sop-page .hw-check{flex:none;width:20px;height:20px;margin-top:1px;transform:scale(0) rotate(-25deg);transform-origin:center;}
  #sop-page .hw-check circle{fill:rgba(255,94,50,0.12);}
  #sop-page .hw-check path{fill:none;stroke:var(--brass);stroke-width:2.4;stroke-linecap:round;stroke-linejoin:round;}
  #sop-page .hw-row:hover .hw-check{transform:scale(1) rotate(8deg);}
  #sop-page .hw-sweep{
    position:absolute;
    left:0;top:-4px;bottom:-4px;
    width:60px;
    border-radius:8px;
    background:linear-gradient(90deg, transparent, rgba(255,94,50,0.16), rgba(26,0,136,0.14), transparent);
    opacity:0;
    pointer-events:none;
  }
  #sop-page .hw-pencil{
    position:absolute;
    top:0;left:0;
    width:44px;height:44px;
    pointer-events:none;
    opacity:0;
    transform:translate(-40px,-40px) rotate(-38deg);
    z-index:5;
    filter:drop-shadow(0 6px 8px rgba(26,0,136,0.25));
  }
  #sop-page .hw-pencil svg{width:100%;height:100%;display:block;}
  #sop-page .hw-dust{position:absolute;width:3px;height:3px;border-radius:50%;background:var(--ink-soft);opacity:0;pointer-events:none;z-index:4;}
  #sop-page .hw-signature{position:absolute;right:40px;bottom:16px;width:64px;height:24px;opacity:0;}
  #sop-page .hw-signature path{fill:none;stroke:var(--ink);stroke-width:2;stroke-linecap:round;}
  #sop-page .hw-shimmer{
    position:absolute;top:0;bottom:0;left:-40%;width:40%;
    background:linear-gradient(100deg, transparent, rgba(255,255,255,0.55), transparent);
    pointer-events:none;
    z-index:6;
  }

  /* GAME */
  #sop-page .game-section{background:linear-gradient(180deg,#EEEAF8 0%,#E7E2F5 100%);}
  /* Widen the game section beyond the standard container so the shell has more room. */
  #sop-page .game-section .wrap{max-width:1340px;}
  #sop-page .game-tabs{display:flex;gap:10px;margin-bottom:26px;}
  #sop-page .game-tab{
    font-family:var(--mono);font-size:12.5px;letter-spacing:.04em;text-transform:uppercase;
    padding:11px 20px;border:1px solid rgba(26,0,136,0.28);background:rgba(255,255,255,0.55);color:var(--ink);
    cursor:pointer;border-radius:22px;transition:transform .25s ease, box-shadow .25s ease, background .25s ease, color .25s ease, border-color .25s ease;
  }
  #sop-page .game-tab:hover{border-color:var(--ink);transform:translateY(-1px);}
  #sop-page .game-tab.active{background:var(--ink);color:var(--parchment);border-color:var(--ink);box-shadow:0 10px 22px -12px rgba(26,0,136,0.6);}
  #sop-page .game-shell{
    background:var(--paper);border:1px solid rgba(26,0,136,0.12);
    border-radius:6px;overflow:hidden;
    box-shadow:0 30px 70px -34px rgba(26,0,136,0.5), 0 2px 6px rgba(26,0,136,0.05);
    display:grid;grid-template-columns:0.92fr 1.3fr;
    min-height:460px;
  }
  /* Left "briefing" panel — dark indigo card that reads like a studio brief and
     gives the white game area a strong, premium counterweight. */
  #sop-page .game-side{
    position:relative;
    background:linear-gradient(165deg,#211A6B 0%,#160F52 100%);
    color:#EDEAFB;padding:40px 36px;
    display:flex;flex-direction:column;justify-content:space-between;gap:28px;
    overflow:hidden;
  }
  /* faint ruled-paper lines, masked so they fade at the top and bottom edges */
  #sop-page .game-side::after{
    content:"";position:absolute;inset:0;pointer-events:none;
    background-image:repeating-linear-gradient(180deg, transparent 0 33px, rgba(255,255,255,0.05) 33px 34px);
    -webkit-mask-image:linear-gradient(180deg,transparent,#000 22%,#000 78%,transparent);
    mask-image:linear-gradient(180deg,transparent,#000 22%,#000 78%,transparent);
  }
  #sop-page .game-side > *{position:relative;z-index:1;}
  #sop-page .game-side-eyebrow{
    font-family:var(--mono);font-size:11px;letter-spacing:.16em;text-transform:uppercase;
    color:var(--brass-light);display:inline-flex;align-items:center;gap:9px;margin-bottom:14px;
  }
  #sop-page .game-side-eyebrow::before{content:"";width:22px;height:1px;background:var(--brass);}
  #sop-page .game-side h3{color:#FFFFFF;font-size:27px;margin-bottom:14px;letter-spacing:-.01em;}
  #sop-page .game-side p{color:rgba(237,234,251,0.72);font-size:14.5px;}
  #sop-page .game-side p strong{color:#FFFFFF;font-weight:600;}
  #sop-page .rules-label{
    font-family:var(--mono);font-size:10.5px;letter-spacing:.14em;text-transform:uppercase;
    color:rgba(237,234,251,0.55);display:block;margin-bottom:14px;
    padding-top:18px;border-top:1px solid rgba(255,255,255,0.14);
  }
  #sop-page .game-side .rules{font-size:13.5px;}
  #sop-page .game-side .rules li{margin-bottom:12px;padding-left:24px;position:relative;color:rgba(237,234,251,0.82);line-height:1.45;}
  #sop-page .game-side .rules li:last-child{margin-bottom:0;}
  #sop-page .game-side .rules li::before{content:"";position:absolute;left:0;top:9px;width:13px;height:2px;background:var(--brass);border-radius:2px;}
  #sop-page .game-main{
    padding:40px;display:flex;flex-direction:column;position:relative;overflow:hidden;
    isolation:isolate;
    background:linear-gradient(135deg,#FCFBFF 0%,#FFFFFF 52%,#FFF7F3 100%);
  }
  #sop-page .game-panel{display:none;flex-direction:column;flex:1;position:relative;z-index:1;}
  #sop-page .game-panel.active{display:flex;}
  /* The original game artwork works as an offset watermark instead of
     competing with the interactive content. */
  #sop-page .game-main::before{
    content:"";
    position:absolute;
    inset:0;
    background-image:url("{{ asset('assets/sop/game-bg.jpg') }}");
    background-size:460px;
    background-position:calc(100% + 115px) 50%;
    background-repeat:no-repeat;
    opacity:.095;
    pointer-events:none;
    z-index:0;
    filter:saturate(.9);
  }
  /* Fine editorial grid + ambient brand glows give the right panel depth. */
  #sop-page .game-main::after{
    content:"";
    position:absolute;
    inset:0;
    pointer-events:none;
    z-index:0;
    background-image:
      radial-gradient(52% 60% at 92% 4%,rgba(255,94,50,.14),transparent 66%),
      radial-gradient(58% 58% at 4% 100%,rgba(26,0,136,.11),transparent 68%),
      linear-gradient(rgba(26,0,136,.032) 1px,transparent 1px),
      linear-gradient(90deg,rgba(26,0,136,.032) 1px,transparent 1px);
    background-size:auto,auto,32px 32px,32px 32px;
    -webkit-mask-image:linear-gradient(135deg,rgba(0,0,0,.28),#000 62%,rgba(0,0,0,.62));
    mask-image:linear-gradient(135deg,rgba(0,0,0,.28),#000 62%,rgba(0,0,0,.62));
    opacity:.9;
  }
  /* Clearly visible foreground graphics carry the motion; the background itself
     stays completely still. */
  #sop-page .game-stage-decor{position:absolute;inset:0;z-index:0;pointer-events:none;overflow:hidden;}
  #sop-page .game-decor{position:absolute;display:block;}
  #sop-page .game-decor-paper{
    top:28px;right:24px;width:96px;height:74px;
    border:1px solid rgba(26,0,136,.18);border-radius:9px;
    background:rgba(255,255,255,.9);
    box-shadow:0 16px 34px -20px rgba(26,0,136,.58);
    animation:sopDecorPaper 4.8s ease-in-out infinite;
  }
  #sop-page .game-decor-paper i{
    position:absolute;left:16px;height:3px;border-radius:3px;
    background:rgba(26,0,136,.19);transform-origin:left;
    animation:sopProofLine 3.4s ease-in-out infinite;
  }
  #sop-page .game-decor-paper i:nth-child(1){top:18px;width:53px;}
  #sop-page .game-decor-paper i:nth-child(2){top:29px;width:42px;animation-delay:.16s;}
  #sop-page .game-decor-paper i:nth-child(3){top:40px;width:47px;animation-delay:.32s;}
  #sop-page .game-decor-paper b{
    position:absolute;right:9px;bottom:7px;width:25px;height:25px;border-radius:50%;
    display:grid;place-items:center;background:var(--crimson);color:#fff;
    font-family:var(--sans);font-size:15px;line-height:1;
    box-shadow:0 7px 14px -7px rgba(255,94,50,.8);
    animation:sopProofCheck 3.4s cubic-bezier(.2,.8,.2,1) infinite;
  }
  #sop-page .game-decor-quote{
    left:25px;top:34px;color:rgba(255,94,50,.32);
    font-family:var(--serif);font-size:82px;line-height:1;
    animation:sopDecorQuote 3.8s ease-in-out infinite;
  }
  #sop-page .game-decor-pencil{
    left:34px;bottom:42px;width:92px;height:17px;border-radius:4px 10px 10px 4px;
    background:linear-gradient(180deg,#FF8760 0%,#FF5E32 100%);
    box-shadow:0 10px 20px -12px rgba(255,94,50,.75);
    transform-origin:center;
    animation:sopDecorPencil 4.4s ease-in-out infinite;
  }
  #sop-page .game-decor-pencil::before{
    content:"";position:absolute;left:-18px;top:0;
    border-top:8.5px solid transparent;border-bottom:8.5px solid transparent;border-right:18px solid #E5B888;
  }
  #sop-page .game-decor-pencil::after{
    content:"";position:absolute;left:-18px;top:6px;
    border-top:2.5px solid transparent;border-bottom:2.5px solid transparent;border-right:6px solid var(--ink);
  }
  #sop-page .game-decor-spark{
    width:18px;height:18px;background:var(--brass);
    clip-path:polygon(50% 0,61% 38%,100% 50%,61% 62%,50% 100%,39% 62%,0 50%,39% 38%);
    animation:sopDecorSpark 2.4s ease-in-out infinite;
  }
  #sop-page .game-decor-spark.one{left:143px;top:58px;animation-delay:.2s;}
  #sop-page .game-decor-spark.two{right:138px;bottom:48px;width:14px;height:14px;background:var(--ribbon);animation-delay:.75s;}
  #sop-page .game-decor-spark.three{right:36px;top:154px;width:11px;height:11px;background:#E8A33D;animation-delay:1.25s;}
  @keyframes sopDecorPaper{0%,100%{transform:translateY(0) rotate(4deg);}50%{transform:translateY(11px) rotate(1deg);}}
  @keyframes sopProofLine{0%,15%{transform:scaleX(.25);opacity:.25;}40%,76%{transform:scaleX(1);opacity:1;}100%{transform:scaleX(.25);opacity:.25;}}
  @keyframes sopProofCheck{0%,100%{transform:scale(.72) rotate(-10deg);opacity:.7;}46%,80%{transform:scale(1) rotate(0);opacity:1;}}
  @keyframes sopDecorQuote{0%,100%{transform:translateY(0) rotate(-5deg);opacity:.62;}50%{transform:translateY(-9px) rotate(1deg);opacity:1;}}
  @keyframes sopDecorPencil{0%,100%{transform:translateX(0) rotate(-16deg);}50%{transform:translateX(22px) translateY(-7px) rotate(-10deg);}}
  @keyframes sopDecorSpark{0%,100%{transform:scale(.55) rotate(0);opacity:.35;}50%{transform:scale(1.2) rotate(90deg);opacity:1;}}
  #sop-page .game-hud{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;font-family:var(--mono);font-size:13px;color:var(--ink-soft);flex-wrap:wrap;gap:8px;}
  #sop-page .game-hud .hearts{color:var(--crimson);font-size:26px;line-height:1;letter-spacing:5px;}
  #sop-page .progress-bar{height:5px;background:rgba(26,0,136,0.1);border-radius:3px;overflow:hidden;margin-bottom:28px;}
  #sop-page .progress-fill{height:100%;background:linear-gradient(90deg,var(--brass),var(--brass-light));width:100%;transition:width .3s linear;}
  #sop-page #game-card{
    position:relative;
    background:linear-gradient(180deg,#FFFFFF,var(--parchment));
    border:1px solid rgba(26,0,136,0.12);
    padding:40px 36px;border-radius:6px;flex:1;display:flex;align-items:center;justify-content:center;text-align:center;
    font-family:var(--sans);font-size:21px;line-height:1.45;color:var(--ink);
    min-height:150px;box-shadow:0 18px 44px -26px rgba(26,0,136,0.42);
  }
  #sop-page #game-card::before{
    content:"\201C";
    position:absolute;top:2px;left:16px;
    font-family:var(--serif);font-size:52px;line-height:1;color:var(--brass);opacity:.3;
  }
  #sop-page .game-buttons{display:flex;gap:16px;margin-top:24px;}
  #sop-page .game-buttons button{flex:1;padding:16px;font-family:var(--sans);font-weight:700;font-size:14.5px;border:none;cursor:pointer;border-radius:3px;transition:transform .2s ease, filter .2s ease, box-shadow .2s ease;}
  #sop-page .game-buttons button:hover{transform:translateY(-2px);filter:brightness(1.06);box-shadow:0 14px 26px -14px rgba(26,0,136,0.5);}
  #sop-page .btn-cliche{background:var(--crimson);color:#FFFFFF;}
  #sop-page .btn-strong{background:var(--ribbon);color:var(--parchment);}
  #sop-page .game-feedback{margin-top:14px;font-family:var(--mono);font-size:12.5px;min-height:18px;text-align:center;}
  #sop-page .feedback-good{color:var(--ribbon);}
  #sop-page .feedback-bad{color:var(--crimson);}
  #sop-page .game-start, #sop-page .game-end{
    flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:18px;
  }
  #sop-page .game-start-content{
    position:relative;
    z-index:1;
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:14px;
    max-width:480px;
    padding:40px 40px 36px;
    border-radius:10px;
    background:rgba(255,255,255,0.62);
    border:1px solid rgba(255,255,255,0.7);
    box-shadow:0 30px 64px -34px rgba(26,0,136,0.5), inset 0 1px 0 rgba(255,255,255,0.6);
    -webkit-backdrop-filter:blur(9px) saturate(120%);
    backdrop-filter:blur(9px) saturate(120%);
    animation:sopCardIn .7s cubic-bezier(.2,.8,.2,1) both;
  }
  @keyframes sopCardIn{from{opacity:0;transform:translateY(16px) scale(.97);}to{opacity:1;transform:translateY(0) scale(1);}}
  /* Keep the copy crisp over the frosted glass + graphic. */
  #sop-page .game-start-content h3,
  #sop-page .game-start-content p{text-shadow:0 1px 2px rgba(255,255,255,0.7);}
  /* Animated "reviewing a draft" scene — an underline sweeps under a line, then
     an approval check draws in, on a gentle loop. */
  #sop-page .game-start-icon{width:92px;height:92px;display:flex;align-items:center;justify-content:center;margin-bottom:2px;}
  #sop-page .game-start-icon svg{width:100%;height:100%;overflow:visible;}
  #sop-page .gs-doc{fill:#fff;stroke:rgba(26,0,136,0.25);stroke-width:2;filter:drop-shadow(0 10px 18px rgba(26,0,136,0.18));}
  #sop-page .gs-line{stroke:rgba(26,0,136,0.20);stroke-width:3.2;stroke-linecap:round;}
  #sop-page .gs-underline{fill:var(--brass);animation:sopUnderline 3.6s ease-in-out infinite;}
  #sop-page .gs-badge{fill:var(--crimson);transform-box:fill-box;transform-origin:center;animation:sopBadgePop 3.6s ease-in-out infinite;}
  #sop-page .gs-tick{fill:none;stroke:#fff;stroke-width:3.4;stroke-linecap:round;stroke-linejoin:round;stroke-dasharray:24;stroke-dashoffset:24;animation:sopTick 3.6s ease-in-out infinite;}
  @keyframes sopUnderline{0%,10%{width:0;}30%,66%{width:26px;}84%,100%{width:0;}}
  @keyframes sopBadgePop{0%,30%{transform:scale(0);}44%,100%{transform:scale(1);}}
  @keyframes sopTick{0%,40%{stroke-dashoffset:24;}56%,92%{stroke-dashoffset:0;}100%{stroke-dashoffset:24;}}
  #sop-page .game-start-content h3{font-family:var(--serif);font-size:26px;color:var(--ink);}
  #sop-page .game-start-content p{color:#3C366F;max-width:360px;font-size:14.5px;font-weight:500;}
  /* Draw the eye to the primary action with a soft pulsing ring. */
  #sop-page #start-btn{animation:sopPulse 2.6s ease-in-out infinite;}
  @keyframes sopPulse{0%,100%{box-shadow:0 0 0 0 rgba(255,94,50,0.5);}70%{box-shadow:0 0 0 15px rgba(255,94,50,0);}}
  #sop-page .game-end-content{
    position:relative;z-index:1;
    width:min(100%,480px);
    padding:30px 38px 34px;
    display:flex;flex-direction:column;align-items:center;gap:16px;
    border:1px solid rgba(255,255,255,.86);
    border-radius:16px;
    background:rgba(255,255,255,.68);
    box-shadow:0 26px 70px -40px rgba(26,0,136,.58),inset 0 1px 0 rgba(255,255,255,.9);
    -webkit-backdrop-filter:blur(14px) saturate(125%);
    backdrop-filter:blur(14px) saturate(125%);
  }
  #sop-page .score-medallion{
    position:relative;
    width:128px;height:128px;border-radius:50%;
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    background:linear-gradient(145deg,rgba(255,255,255,.98),rgba(244,241,252,.92));
    border:1px solid rgba(26,0,136,.12);
    box-shadow:0 18px 36px -22px rgba(26,0,136,.62),0 0 0 9px rgba(255,94,50,.055);
  }
  #sop-page .score-medallion::before{
    content:"";position:absolute;inset:-7px;border-radius:inherit;
    border:1px dashed rgba(255,94,50,.5);
    animation:sopMedallionOrbit 18s linear infinite;
  }
  #sop-page .score-label{
    font-family:var(--mono);font-size:9px;line-height:1;
    letter-spacing:.16em;text-transform:uppercase;color:var(--crimson);
    margin-bottom:3px;
  }
  #sop-page .game-end .score-big{font-family:var(--serif);font-size:58px;line-height:.95;font-weight:700;color:var(--ink);}
  #sop-page .game-end-message{color:var(--ink-soft);max-width:360px;font-size:15px;line-height:1.6;}
  #sop-page .game-end-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:2px;}
  @keyframes sopMedallionOrbit{to{transform:rotate(360deg);}}

  /* ── Phrase Checker / Sprint interaction animations (replay every step) ── */
  #sop-page #game-play{position:relative;}
  #sop-page #score, #sop-page #hearts{display:inline-block;}
  #sop-page #game-card.card-in,
  #sop-page .sprint-target.card-in{animation:sopCardEnter .7s cubic-bezier(.2,.8,.2,1);}
  #sop-page #game-card.flash-ok{animation:sopFlashOk .9s ease;}
  #sop-page #game-card.flash-bad{animation:sopShake .9s ease;}
  #sop-page #score.bump{animation:sopBump .8s ease;}
  #sop-page #hearts.lose{animation:sopHeartLose .9s ease;}
  #sop-page .game-feedback.pop{animation:sopPop .6s ease;}
  #sop-page .game-buttons button:active{transform:translateY(0) scale(.97);}
  #sop-page .points-pop{
    position:absolute;left:50%;top:42%;
    font-family:var(--serif);font-weight:700;font-size:32px;color:var(--ribbon);
    pointer-events:none;z-index:5;text-shadow:0 2px 10px rgba(255,255,255,0.85);
    animation:sopFloatPoints 1.5s cubic-bezier(.2,.8,.2,1) forwards;
  }
  @keyframes sopCardEnter{from{opacity:0;transform:translateY(14px) scale(.98);}to{opacity:1;transform:translateY(0) scale(1);}}
  @keyframes sopFlashOk{
    0%{box-shadow:0 18px 44px -26px rgba(26,0,136,0.42),0 0 0 0 rgba(31,158,90,0);}
    30%{box-shadow:0 18px 44px -26px rgba(26,0,136,0.42),0 0 0 5px rgba(31,158,90,0.5);transform:scale(1.02);}
    100%{box-shadow:0 18px 44px -26px rgba(26,0,136,0.42),0 0 0 0 rgba(31,158,90,0);transform:scale(1);}
  }
  @keyframes sopShake{
    0%,100%{transform:translateX(0);box-shadow:0 18px 44px -26px rgba(26,0,136,0.42),0 0 0 0 rgba(255,94,50,0);}
    15%{transform:translateX(-8px);box-shadow:0 18px 44px -26px rgba(26,0,136,0.42),0 0 0 5px rgba(255,94,50,0.5);}
    30%{transform:translateX(7px);}45%{transform:translateX(-5px);}60%{transform:translateX(4px);}75%{transform:translateX(-2px);}
  }
  @keyframes sopBump{0%{transform:scale(1);}35%{transform:scale(1.28);color:var(--ribbon);}100%{transform:scale(1);}}
  @keyframes sopHeartLose{0%,100%{transform:translateX(0);}20%{transform:translateX(-4px) scale(1.12);}40%{transform:translateX(4px);}60%{transform:translateX(-3px);}80%{transform:translateX(2px);}}
  @keyframes sopPop{0%{opacity:0;transform:translateY(4px) scale(.9);}100%{opacity:1;transform:translateY(0) scale(1);}}
  @keyframes sopFloatPoints{0%{opacity:0;transform:translate(-50%,10px) scale(.8);}25%{opacity:1;transform:translate(-50%,-6px) scale(1.1);}100%{opacity:0;transform:translate(-50%,-54px) scale(1);}}

  /* Round complete transitions the watermark into a focused result stage. */
  #sop-page .game-main.round-over::before{animation:sopImgOut .75s cubic-bezier(.4,0,.2,1) forwards;}
  #sop-page .game-main.round-over::after{animation:sopResultWash .8s cubic-bezier(.2,.8,.2,1) forwards;}
  #sop-page .game-end.end-in .game-end-content{animation:sopEndIn .72s cubic-bezier(.2,.8,.2,1) both;}
  #sop-page .game-end.end-in .game-end-content > *{animation:sopResultItem .62s cubic-bezier(.2,.8,.2,1) both;}
  #sop-page .game-end.end-in .game-end-content > *:nth-child(1){animation-delay:.12s;}
  #sop-page .game-end.end-in .game-end-content > *:nth-child(2){animation-delay:.2s;}
  #sop-page .game-end.end-in .game-end-content > *:nth-child(3){animation-delay:.28s;}
  #sop-page .game-end.end-in .game-end-content > *:nth-child(4){animation-delay:.36s;}
  @keyframes sopImgOut{from{opacity:.095;transform:scale(1);filter:blur(0);}to{opacity:0;transform:scale(1.12);filter:blur(7px);}}
  @keyframes sopResultWash{from{opacity:.72;transform:scale(1);}to{opacity:1;transform:scale(1.03);}}
  @keyframes sopEndIn{from{opacity:0;transform:translateY(18px) scale(.97);}to{opacity:1;transform:translateY(0) scale(1);}}
  @keyframes sopResultItem{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}

  #sop-page .end-fx{position:absolute;inset:0;z-index:0;pointer-events:none;overflow:hidden;}
  #sop-page .game-end > *:not(.end-fx){position:relative;z-index:1;}
  #sop-page .end-glow{
    position:absolute;left:50%;top:50%;width:480px;height:480px;transform:translate(-50%,-50%);
    border-radius:50%;
    background:radial-gradient(circle,rgba(255,255,255,.92) 0 18%,rgba(255,94,50,.16) 34%,rgba(26,0,136,.07) 53%,transparent 72%);
  }
  #sop-page .end-glow::after{
    content:"";position:absolute;inset:86px;border-radius:50%;
    border:1px dashed rgba(255,94,50,.2);
  }
  #sop-page .end-confetti i{position:absolute;left:50%;top:50%;width:9px;height:9px;border-radius:2px;opacity:0;}
  #sop-page .game-end.end-in .end-confetti i{animation:sopConfettiBurst 1.45s cubic-bezier(.12,.7,.25,1) var(--delay,0s) both;}
  #sop-page .end-confetti i:nth-child(3n+1){background:var(--brass);}
  #sop-page .end-confetti i:nth-child(3n+2){background:var(--ribbon);}
  #sop-page .end-confetti i:nth-child(3n){background:#E8A33D;border-radius:50%;}
  @keyframes sopConfettiBurst{
    0%{opacity:0;transform:translate(-50%,-50%) scale(.25) rotate(0);}
    18%{opacity:.95;}
    100%{opacity:0;transform:translate(calc(-50% + var(--tx)),calc(-50% + var(--ty))) scale(.8) rotate(var(--turn,240deg));}
  }
  #sop-page .hidden{display:none !important;}

  /* Typewriter Sprint game */
  #sop-page .sprint-target{
    background:var(--parchment);border:1px solid rgba(26,0,136,0.12);border-radius:2px;
    padding:30px 28px;font-family:var(--sans);font-size:18px;line-height:2;letter-spacing:0.02em;
    min-height:110px;
  }
  #sop-page .sprint-target span{transition:color .1s ease;}
  #sop-page .sprint-target .word-group{display:inline-block;white-space:nowrap;}
  #sop-page .sprint-target .ok{color:var(--ribbon);}
  #sop-page .sprint-target .bad{color:var(--crimson);background:rgba(255,94,50,0.12);}
  #sop-page .sprint-target .pending{color:var(--ink-soft);opacity:.55;}
  #sop-page .sprint-input{
    margin-top:22px;width:100%;background:var(--paper);border:1px solid rgba(26,0,136,0.25);
    padding:14px 16px;font-family:var(--sans);font-size:15px;color:var(--ink);outline:none;border-radius:2px;
  }
  #sop-page .sprint-input:focus{border-color:var(--brass);}
  #sop-page .sprint-stats{display:flex;gap:26px;margin-top:20px;font-family:var(--mono);font-size:12.5px;color:var(--ink-soft);flex-wrap:wrap;}
  #sop-page .sprint-stats b{color:var(--ink);font-family:var(--sans);font-size:18px;display:block;}

  /* TESTIMONIALS */
  #sop-page .testimonial-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:26px;}
  #sop-page .t-card{
    background:var(--paper);border:1px solid rgba(26,0,136,0.1);padding:32px 28px;
    transition:transform .4s cubic-bezier(.2,.8,.2,1), box-shadow .4s ease;
  }
  #sop-page .t-card:hover{transform:translateY(-6px);box-shadow:var(--shadow);}
  #sop-page .t-card .stars{color:var(--brass);font-size:14px;margin-bottom:16px;letter-spacing:2px;}
  #sop-page .t-card p{font-size:14.8px;color:var(--ink-soft);margin-bottom:20px;font-style:italic;}
  #sop-page .t-card .who{font-family:var(--mono);font-size:12px;color:var(--ribbon);text-transform:uppercase;letter-spacing:.05em;}

  /* FAQ — minimalist list, exactly as in the original design (just a thin
     bottom rule per item). The explicit resets neutralise the site's global
     .faq-item card styling (white bg / border / radius / shadow) that would
     otherwise bleed in from styles.css. */
  #sop-page .faq-item{
    background:transparent;
    border:none;
    border-bottom:1px solid rgba(26,0,136,0.14);
    border-radius:0;
    box-shadow:none;
  }
  #sop-page .faq-q{
    display:flex;justify-content:space-between;align-items:center;
    padding:14px 4px;cursor:pointer;font-family:var(--sans);font-size:15.5px;font-weight:600;
    gap:12px;
  }
  #sop-page .faq-q .plus{font-family:var(--mono);font-size:18px;transition:transform .3s ease;color:var(--crimson);flex-shrink:0;}
  #sop-page .faq-item.open .plus{transform:rotate(135deg);}
  #sop-page .faq-a{max-height:0;overflow:hidden;transition:max-height .4s ease;}
  #sop-page .faq-a p{padding:0 4px 14px;color:var(--ink-soft);font-size:13.5px;line-height:1.5;max-width:640px;}

  /* CONTACT */
  #sop-page .contact{background:var(--parchment-dark);color:var(--ink);padding:64px 0;}
  #sop-page .contact .section-head h2{color:var(--ink);}
  #sop-page .contact .section-head p{color:var(--ink-soft);}
  #sop-page .contact .eyebrow{color:var(--crimson);}
  #sop-page .contact .eyebrow::before{background:var(--crimson);}
  #sop-page .combo-grid{display:grid;grid-template-columns:0.9fr 1.1fr;gap:56px;align-items:start;}
  #sop-page .faq-col .section-head{margin-bottom:18px;}
  #sop-page .booking-col .section-head{margin-bottom:22px;}
  #sop-page .field{margin-bottom:14px;}
  #sop-page .field label{display:block;font-family:var(--mono);font-size:11.5px;text-transform:uppercase;letter-spacing:.06em;color:var(--crimson);margin-bottom:6px;}
  #sop-page .field input, #sop-page .field select, #sop-page .field textarea{
    width:100%;background:transparent;border:none;border-bottom:1px solid rgba(26,0,136,0.3);
    color:var(--ink);font-family:var(--sans);font-size:15px;padding:8px 2px;outline:none;
    transition:border-color .3s ease;
  }
  #sop-page .field select option{color:var(--ink);}
  #sop-page .field input:focus, #sop-page .field select:focus, #sop-page .field textarea:focus{border-color:var(--crimson);}
  #sop-page .field textarea{resize:vertical;min-height:56px;}
  #sop-page .submit-row{display:flex;align-items:center;gap:18px;margin-top:6px;}
  #sop-page .sop-fallback{margin-top:16px;font-size:13px;color:var(--ink-soft);font-family:var(--mono);}
  #sop-page .sop-fallback a{color:var(--crimson);text-decoration:underline;text-underline-offset:2px;}

  /* RESPONSIVE */
  @media (max-width:640px){
    #sop-page .hw-row{grid-template-columns:1fr;gap:6px;padding:14px 10px;}
    #sop-page .hw-card{padding:32px 20px 40px;}
    #sop-page .game-main{padding:26px 18px;min-height:500px;}
    #sop-page .game-end-content{padding:26px 18px 28px;border-radius:12px;}
    #sop-page .score-medallion{width:112px;height:112px;}
    #sop-page .game-end .score-big{font-size:50px;}
    #sop-page .game-end-actions{width:100%;}
    #sop-page .game-end-actions .btn{flex:1;justify-content:center;padding:13px 12px;}
  }
  @media (max-width:960px){
    #sop-page .hero .wrap{grid-template-columns:1fr;}
    #sop-page .desk-stage{max-width:420px;margin-top:40px;}
    #sop-page .service-grid{grid-template-columns:repeat(2,1fr);}
    #sop-page .testimonial-grid{grid-template-columns:1fr;}
    #sop-page .combo-grid{grid-template-columns:1fr;}
    #sop-page .game-shell{grid-template-columns:1fr;}
  }
  @media (max-width:680px){
    #sop-page .service-grid{
      display:flex;
      gap:12px;
      overflow-x:auto;
      scroll-snap-type:x mandatory;
      scroll-behavior:smooth;
      overscroll-behavior-x:contain;
      scrollbar-width:none;
      background:transparent;
      border:0;
      padding:1px 28px 14px 1px;
    }
    #sop-page .service-grid::-webkit-scrollbar{display:none;}
    #sop-page .service-card{
      flex:0 0 calc(100% - 28px);
      scroll-snap-align:start;
      scroll-snap-stop:always;
      border:1px solid rgba(26,0,136,0.12);
      border-radius:4px;
    }
    #sop-page .service-card .idx{font-size:11px;margin-bottom:8px;}
    #sop-page .service-card .icon{width:34px;height:34px;margin-bottom:16px;}
    #sop-page .service-card h3{
      font-size:clamp(18px,4.6vw,20px);
      line-height:1.3;
      margin-bottom:10px;
    }
    #sop-page .service-card p{
      font-size:clamp(15px,3.8vw,16px);
      line-height:1.6;
    }
    #sop-page .service-card-image{min-height:360px;}
    #sop-page .service-carousel-nav{
      display:flex;
      align-items:center;
      justify-content:space-between;
      margin-top:8px;
      padding-right:58px;
    }
    #sop-page .service-carousel-status{
      font-family:var(--mono);
      color:var(--ink-soft);
      font-size:11px;
      letter-spacing:.12em;
    }
    #sop-page .service-carousel-buttons{display:flex;gap:8px;}
    #sop-page .service-carousel-btn{
      width:42px;
      height:42px;
      display:grid;
      place-items:center;
      border:1px solid rgba(26,0,136,0.24);
      border-radius:50%;
      background:rgba(255,255,255,.7);
      color:var(--ink);
      font-size:20px;
      line-height:1;
      cursor:pointer;
    }
    #sop-page .service-carousel-btn:disabled{opacity:.35;cursor:default;}
    #sop-page .hero-stats{flex-wrap:wrap;gap:22px;}
  }
</style>
@endpush

@section('content')
<main id="{{ $mainId ?? 'main' }}">
  <div id="sop-page">

  <!-- HERO -->
  <section class="hero">
    <div class="wrap">
      <div class="hero-copy">
        <h1 id="typed-headline" aria-label="Every acceptance letter starts as a first draft."></h1>
        <p class="lead">OneDegreeAdvisory drafts Statement of Purpose, Ivy League strategy, visa SOPs, resumes, letters of recommendation and scholarship essays — one advisor, one consistent story, from first line to final submission.</p>
        <div class="hero-actions">
          <a href="#contact" class="btn btn-primary">Book Your Strategy Call</a>
          <a href="#game" class="btn btn-ghost">Play the Writing Games →</a>
        </div>
        <div class="hero-stats">
          <div><div class="num" data-count="95">0</div><div class="lbl">% Admit Rate*</div></div>
          <div><div class="num" data-count="40">0</div><div class="lbl">+ Countries Served</div></div>
          <div><div class="num" data-count="3000">0</div><div class="lbl">+ Documents Drafted</div></div>
        </div>
      </div>
      <div class="desk-stage">
        <div class="hero-photo-frame">
          <img src="{{ asset('assets/sop/hero.jpg') }}" alt="Can't find the right words for your SOP" loading="lazy">
        </div>
      </div>
    </div>
  </section>

  <!-- SERVICES -->
  <section class="services" id="services">
    <div class="wrap">
      <div class="section-head reveal">
        <div class="eyebrow">What We Draft</div>
        <h2>Every document. One coherent story.</h2>
        <p>Admissions committees and visa officers review hundreds of applications each day. We ensure every document in your application is aligned, accurate, and consistent—strengthening your overall case and minimizing inconsistencies that can prompt additional scrutiny.</p>
      </div>
      <div class="service-grid">

        <div class="service-card reveal">
          <span class="idx">01</span>
          <svg class="icon" viewBox="0 0 48 48" fill="none"><path d="M8 40 L20 12 C22 7 26 7 28 12 L40 40" stroke="currentColor" stroke-width="2" fill="none"/><path d="M14 28 H34" stroke="currentColor" stroke-width="2"/></svg>
          <h3>Statement of Purpose (SOP)</h3>
          <p>A personalized Statement of Purpose that reflects your academic journey, career aspirations, achievements, and motivation. Every SOP is written from scratch to present a compelling and authentic story that aligns with your chosen program and university.</p>
        </div>

        <div class="service-card reveal">
          <span class="idx">02</span>
          <svg class="icon" viewBox="0 0 48 48" fill="none"><path d="M24 4 L44 14 L24 24 L4 14 Z" stroke="currentColor" stroke-width="2"/><path d="M12 20 V32 C12 36 36 36 36 32 V20" stroke="currentColor" stroke-width="2"/></svg>
          <h3>University-Specific SOP Customization</h3>
          <p>Each university has different expectations. We tailor your SOP to highlight the academic strengths, research interests, faculty alignment, and career goals that matter most to your target institution.</p>
        </div>

        <div class="service-card reveal">
          <span class="idx">03</span>
          <svg class="icon" viewBox="0 0 48 48" fill="none"><rect x="8" y="10" width="32" height="28" rx="2" stroke="currentColor" stroke-width="2"/><circle cx="18" cy="24" r="5" stroke="currentColor" stroke-width="2"/><path d="M28 20 H34 M28 28 H32" stroke="currentColor" stroke-width="2"/></svg>
          <h3>Visa SOP &amp; Study Plan</h3>
          <p>Professionally drafted visa Statements of Purpose and study plans designed to meet the expectations of visa officers. We clearly demonstrate your academic intent, financial preparedness, career progression, and genuine temporary entrant requirements where applicable.</p>
        </div>

        <div class="service-card reveal">
          <span class="idx">04</span>
          <svg class="icon" viewBox="0 0 48 48" fill="none"><rect x="10" y="6" width="28" height="36" rx="1" stroke="currentColor" stroke-width="2"/><path d="M16 14 H32 M16 20 H32 M16 26 H26 M16 32 H22" stroke="currentColor" stroke-width="2"/></svg>
          <h3>ATS-Optimized Resume &amp; Academic CV</h3>
          <p>Professionally structured resumes and academic CVs that showcase your education, work experience, research, projects, leadership, and achievements in globally accepted formats for university admissions and scholarships.</p>
        </div>

        <div class="service-card reveal">
          <span class="idx">05</span>
          <svg class="icon" viewBox="0 0 48 48" fill="none"><path d="M8 12 H40 V36 H8 Z" stroke="currentColor" stroke-width="2"/><path d="M8 12 L24 26 L40 12" stroke="currentColor" stroke-width="2"/></svg>
          <h3>Letters of Recommendation (LOR) Assistance</h3>
          <p>We help develop impactful recommendation letters that highlight your academic ability, professional strengths, leadership qualities, and potential—ensuring each LOR adds unique value to your application.</p>
        </div>

        <div class="service-card reveal">
          <span class="idx">06</span>
          <svg class="icon" viewBox="0 0 48 48" fill="none"><circle cx="24" cy="24" r="16" stroke="currentColor" stroke-width="2"/><path d="M24 16 V24 L30 28" stroke="currentColor" stroke-width="2"/></svg>
          <h3>Scholarship &amp; Personal Essays</h3>
          <p>Compelling scholarship essays and personal statements that effectively communicate your achievements, leadership, community impact, and future goals to maximize your scholarship opportunities.</p>
        </div>

        <div class="service-card reveal">
          <span class="idx">07</span>
          <svg class="icon" viewBox="0 0 48 48" fill="none"><path d="M30 6 L42 18 L16 44 H6 V34 Z" stroke="currentColor" stroke-width="2" fill="none"/><path d="M26 10 L38 22" stroke="currentColor" stroke-width="2"/></svg>
          <h3>SOP Review &amp; Professional Editing</h3>
          <p>Already have a draft? Our experts provide detailed content review, structural improvements, grammar correction, plagiarism checks, and language refinement while preserving your authentic voice and strengthening your overall application.</p>
        </div>

        <div class="service-card service-card-image reveal">
          <img src="{{ asset('assets/sop/service.jpg') }}" alt="Think outside the box" loading="lazy">
        </div>

      </div>
      <div class="service-carousel-nav" aria-label="Document services carousel controls">
        <span class="service-carousel-status" aria-live="polite">01 / 08</span>
        <div class="service-carousel-buttons">
          <button class="service-carousel-btn service-carousel-prev" type="button" aria-label="Previous service">&#8592;</button>
          <button class="service-carousel-btn service-carousel-next" type="button" aria-label="Next service">&#8594;</button>
        </div>
      </div>
    </div>
  </section>

  <!-- GAME -->
  <section class="game-section" id="game">
    <div class="wrap">
      <div class="section-head reveal">
        <div class="eyebrow">Test Your Eye &amp; Your Hand</div>
        <h2>Two games, one writer's studio</h2>
        <p>Every SOP we edit starts with a generic-phrase check and ends with a clean, error-free draft. Try both sides of the process yourself below.</p>
      </div>

      <div class="game-tabs reveal">
        <button class="game-tab active" data-tab="cliche">Phrase Checker</button>
        <button class="game-tab" data-tab="sprint">Typewriter Sprint</button>
      </div>

      <div class="game-shell reveal">

        <!-- CLICHE SIDE PANEL -->
        <div class="game-side" id="side-cliche">
          <div class="game-side-top">
            <span class="game-side-eyebrow">Game 01 · Editor's eye</span>
            <h3>Phrase Checker</h3>
            <p>Now it's your turn — sort real opening lines into <strong>Generic</strong> or <strong>Strong</strong> before the clock runs out.</p>
          </div>
          <div class="game-side-rules">
            <span class="rules-label">How it works</span>
            <ul class="rules">
              <li>45 seconds, unlimited sentences</li>
              <li>3 lives — three wrong answers ends the round</li>
              <li>+10 points per correct call, streak bonus at 5 in a row</li>
            </ul>
          </div>
        </div>

        <!-- SPRINT SIDE PANEL -->
        <div class="game-side hidden" id="side-sprint">
          <div class="game-side-top">
            <span class="game-side-eyebrow">Game 02 · Clean draft</span>
            <h3>Typewriter Sprint</h3>
            <p>Type a real SOP-style line as fast and cleanly as you can. Speed matters — but a <strong>clean draft</strong> matters more.</p>
          </div>
          <div class="game-side-rules">
            <span class="rules-label">How it works</span>
            <ul class="rules">
              <li>Timer starts on your first keystroke</li>
              <li>Score = words per minute × accuracy</li>
              <li>Every typo is highlighted live, just like a line edit</li>
            </ul>
          </div>
        </div>

        <div class="game-main">
          <div class="game-stage-decor" aria-hidden="true">
            <span class="game-decor game-decor-quote">&ldquo;</span>
            <span class="game-decor game-decor-paper"><i></i><i></i><i></i><b>&#10003;</b></span>
            <span class="game-decor game-decor-pencil"></span>
            <span class="game-decor game-decor-spark one"></span>
            <span class="game-decor game-decor-spark two"></span>
            <span class="game-decor game-decor-spark three"></span>
          </div>

          <!-- CLICHE PANEL -->
          <div class="game-panel active" id="panel-cliche">
            <div id="game-start" class="game-start">
              <div class="game-start-content">
                <span class="game-start-icon" aria-hidden="true">
                  <svg viewBox="0 0 92 92" fill="none">
                    <rect class="gs-doc" x="18" y="14" width="46" height="58" rx="5"/>
                    <line class="gs-line" x1="27" y1="28" x2="55" y2="28"/>
                    <line class="gs-line" x1="27" y1="37" x2="51" y2="37"/>
                    <line class="gs-line" x1="27" y1="46" x2="47" y2="46"/>
                    <rect class="gs-underline" x="27" y="52" width="0" height="3" rx="1.5"/>
                    <circle class="gs-badge" cx="61" cy="60" r="15"/>
                    <path class="gs-tick" d="M53.5 60.5 l4.5 4.5 8.5 -9.5"/>
                  </svg>
                </span>
                <h3>Ready to play?</h3>
                <p>You'll see a real-style SOP opening line. Decide fast: generic, or strong enough to submit?</p>
                <button class="btn btn-brass" id="start-btn">Start Round</button>
              </div>
            </div>

            <div id="game-play" style="display:none;flex-direction:column;flex:1;">
              <div class="game-hud">
                <span id="timer">⏱ 45s</span>
                <span class="hearts" id="hearts">♥♥♥</span>
                <span id="score">Score: 0</span>
              </div>
              <div class="progress-bar"><div class="progress-fill" id="progress-fill"></div></div>
              <div id="game-card">Loading sentence…</div>
              <div class="game-feedback" id="game-feedback">&nbsp;</div>
              <div class="game-buttons">
                <button class="btn-cliche" id="btn-cliche">🚫 Generic</button>
                <button class="btn-strong" id="btn-strong">✅ Strong</button>
              </div>
            </div>

            <div id="game-end" class="game-end hidden">
              {{-- Refined result-stage backdrop with orbit rings and a short particle burst. --}}
              <div class="end-fx" aria-hidden="true">
                <span class="end-glow"></span>
                <div class="end-confetti">
                  <i style="--tx:-220px;--ty:-112px;--turn:-260deg;--delay:.04s;"></i>
                  <i style="--tx:-155px;--ty:-172px;--turn:310deg;--delay:.1s;"></i>
                  <i style="--tx:-72px;--ty:-205px;--turn:-220deg;--delay:.16s;"></i>
                  <i style="--tx:46px;--ty:-206px;--turn:280deg;--delay:.07s;"></i>
                  <i style="--tx:148px;--ty:-164px;--turn:-300deg;--delay:.18s;"></i>
                  <i style="--tx:222px;--ty:-88px;--turn:250deg;--delay:.12s;"></i>
                  <i style="--tx:230px;--ty:86px;--turn:-270deg;--delay:.02s;"></i>
                  <i style="--tx:142px;--ty:165px;--turn:300deg;--delay:.14s;"></i>
                  <i style="--tx:42px;--ty:205px;--turn:-240deg;--delay:.08s;"></i>
                  <i style="--tx:-82px;--ty:196px;--turn:290deg;--delay:.2s;"></i>
                  <i style="--tx:-174px;--ty:146px;--turn:-320deg;--delay:.11s;"></i>
                  <i style="--tx:-232px;--ty:52px;--turn:260deg;--delay:.05s;"></i>
                </div>
              </div>
              <div class="game-end-content">
                <div class="eyebrow" style="margin:0;">Round Complete</div>
                <div class="score-medallion">
                  <span class="score-label">Final score</span>
                  <div class="score-big" id="final-score">0</div>
                </div>
                <p class="game-end-message" id="end-message">Nice instincts.</p>
                <div class="game-end-actions">
                  <button class="btn btn-ghost" id="replay-btn">Play Again</button>
                  <a href="#contact" class="btn btn-primary">Get an Original SOP →</a>
                </div>
              </div>
            </div>
          </div>

          <!-- SPRINT PANEL -->
          <div class="game-panel" id="panel-sprint">
            <div class="game-hud">
              <span id="sprint-status">Start typing to begin</span>
              <button class="btn btn-ghost" id="sprint-new" style="padding:8px 16px;font-size:12.5px;">New Line</button>
            </div>
            <div class="sprint-target" id="sprint-target"></div>
            <input type="text" class="sprint-input" id="sprint-input" placeholder="Start typing the line above…" autocomplete="off" spellcheck="false">
            <div class="sprint-stats" id="sprint-stats-live">
              <div><b id="sprint-wpm">0</b>WPM</div>
              <div><b id="sprint-acc">—</b>Accuracy</div>
              <div><b id="sprint-time">0.0s</b>Elapsed</div>
            </div>
            <div id="sprint-result" class="hidden" style="margin-top:20px;padding-top:20px;border-top:1px dashed rgba(26,0,136,0.2);">
              <p style="font-family:var(--sans);font-size:18px;margin-bottom:14px;" id="sprint-verdict">Nice typing.</p>
              <a href="#contact" class="btn btn-primary">Get Every Line This Clean →</a>
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>

  <!-- HANDWRITING COMPARISON -->
  <section class="hw-compare" id="difference">
    <div class="wrap">
      <div class="section-head reveal">
        <div class="eyebrow">The One Degree Difference</div>
        <h2>How One Degree Advisory compares</h2>
        <p>Every document is planned, drafted, and reviewed by a real writer who knows what admissions and visa officers actually look for — not a template.</p>
      </div>

      <div class="hw-card" id="hw-card">
        <div class="hw-pencil" id="hw-pencil">
          <svg viewBox="0 0 64 64">
            <g>
              <rect x="8" y="26" width="40" height="10" rx="2" transform="rotate(-38 8 26)" fill="#1A0088"/>
              <polygon points="8,26 -2,34 3,40" transform="rotate(-38 8 26)" fill="#12005E"/>
              <rect x="8" y="26" width="9" height="10" rx="1.5" transform="rotate(-38 8 26)" fill="#FF5E32"/>
              <rect x="44" y="26" width="6" height="10" rx="1.5" transform="rotate(-38 8 26)" fill="#E7E2F5"/>
            </g>
          </svg>
        </div>

        <svg class="hw-signature" id="hw-signature" viewBox="0 0 64 24">
          <path id="hw-sig-path" d="M2 18 C 10 4, 16 4, 20 14 S 30 22, 34 10 S 44 2, 50 12 S 58 18, 62 8" />
        </svg>

        <div class="hw-shimmer" id="hw-shimmer"></div>

        <div class="hw-rows" id="hw-rows"></div>
      </div>
    </div>
  </section>

  <!-- TESTIMONIALS -->
  <section id="reviews">
    <div class="wrap">
      <div class="section-head reveal">
        <div class="eyebrow">Client Outcomes</div>
        <h2>Stories our clients told — and got in with</h2>
      </div>
      <div class="testimonial-grid">
        <div class="t-card reveal">
          <div class="stars">★★★★★</div>
          <p>"My advisor found the actual thread in my three years of lab work that I couldn't see myself. The SOP finally sounded like an argument, not a diary."</p>
          <div class="who">Admitted — MS Computer Science, Class of 2026</div>
        </div>
        <div class="t-card reveal">
          <div class="stars">★★★★★</div>
          <p>"I had a prior German visa refusal on record, and I was sure it would sink every application after it. My advisor turned it into a paragraph about resilience instead of something to hide."</p>
          <div class="who">Admitted — M.Sc. Computer Science, Poland, Class of 2026</div>
        </div>
        <div class="t-card reveal">
          <div class="stars">★★★★★</div>
          <p>"The Spain visa checklist looked impossible until we went through it document by document. My advisor knew exactly what the Delhi consulate would flag before they ever asked."</p>
          <div class="who">Student Visa Approved — Spain, 2026</div>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ + CONTACT (combined, compact) -->
  <section class="contact" id="contact">
    <div class="wrap">
      <div class="combo-grid">

        <!-- LEFT: FAQ -->
        <div class="faq-col reveal">
          <div class="section-head" style="margin-bottom:18px;">
            <div class="eyebrow">Questions</div>
            <h2 style="font-size:clamp(24px,2.6vw,30px);">Before you book</h2>
          </div>
          <div>
            <div class="faq-item open">
              <div class="faq-q"><span>Do you write the SOP for me, or with me?</span><span class="plus">+</span></div>
              <div class="faq-a"><p>With you. We run a discovery call and structured questionnaire first, then draft from your actual material. Nothing goes out that you haven't reviewed line by line.</p></div>
            </div>
            <div class="faq-item">
              <div class="faq-q"><span>Can you help with visa refusal cases?</span><span class="plus">+</span></div>
              <div class="faq-a"><p>Yes — our Visa SOP service includes a dedicated track for genuine student requirement (GSR) refusals, rebuilding the file to directly address the refusal reasons on record.</p></div>
            </div>
            <div class="faq-item">
              <div class="faq-q"><span>Is any of the writing AI-generated?</span><span class="plus">+</span></div>
              <div class="faq-a"><p>No. Every draft is human-written by your assigned advisor. We use software only for plagiarism and consistency checks, never for first-draft generation.</p></div>
            </div>
          </div>
        </div>

        <!-- RIGHT: BOOKING FORM -->
        <div class="booking-col reveal">
          <div class="section-head" style="margin-bottom:18px;">
            <div class="eyebrow">Start Your Application</div>
            <h2 style="font-size:clamp(24px,2.6vw,30px);">Book your strategy call</h2>
            <p style="margin-top:8px;font-size:14.5px;">Tell us where you're applying. We'll reply within one business day with next steps and a fit assessment.</p>
          </div>

          <form id="sop-contact-form" method="POST" action="{{ route('sop.lead') }}" data-sop-form novalidate>
            @csrf
            <div class="field">
              <label for="sop-name">Full Name</label>
              <input type="text" id="sop-name" name="name" placeholder="Ananya Sharma" required maxlength="120">
            </div>
            <div class="field">
              <label for="sop-email">Email Address</label>
              <input type="email" id="sop-email" name="email" placeholder="you@email.com" required maxlength="190">
            </div>
            <div class="field">
              <label for="sop-service">Service Needed</label>
              <select id="sop-service" name="service">
                <option value="">Select a service…</option>
                <option>Statement of Purpose (SOP)</option>
                <option>University-Specific SOP Customization</option>
                <option>Visa SOP &amp; Study Plan</option>
                <option>ATS-Optimized Resume / Academic CV</option>
                <option>Letters of Recommendation (LOR) Assistance</option>
                <option>Scholarship &amp; Personal Essays</option>
                <option>SOP Review &amp; Professional Editing</option>
              </select>
            </div>
            <div class="field">
              <label for="sop-message">Tell us about your target program</label>
              <textarea id="sop-message" name="message" placeholder="e.g. Applying to MS Data Science programs in the US, Fall 2027 intake…" required minlength="10" maxlength="2000"></textarea>
            </div>
            <div class="submit-row">
              <button type="submit" class="btn btn-brass" id="sop-submit-btn"><span>Request Call Back</span></button>
              <span style="font-size:12.5px;color:#9A9280;">No spam. Ever.</span>
            </div>
            <p class="sop-fallback">Prefer to talk now? <a href="{{ $waLink }}" target="_blank" rel="noopener">WhatsApp an advisor</a> or <a href="{{ $mailLink }}">email us</a>.</p>
          </form>
        </div>

      </div>
    </div>
  </section>

  </div>{{-- /#sop-page --}}
</main>

<script>
/* Statement of Purpose page — self-contained behaviour (typed headline, scroll
   reveal, counters, FAQ accordion, and the two writing games). The booking form
   is handled by the site's shared wireFormSubmit (public/script.js), so there's
   no form JS here. All collection queries are rooted at #sop-page so they can't
   touch the shared chrome. Wrapped in an IIFE to avoid leaking globals. */
(function(){
  const root = document.getElementById('sop-page');
  if(!root) return;

  /* ---------- HERO TYPED HEADLINE ---------- */
  (function(){
    const el = document.getElementById('typed-headline');
    if(!el) return;
    const parts = [
      {text:"Every ", accent:false},
      {text:"acceptance letter", accent:true},
      {text:" starts as a ", accent:false},
      {text:"first draft.", accent:true}
    ];
    const full = parts.map(p => p.text).join('');
    let i = 0;
    function typeStep(){
      let rendered = '';
      let count = 0;
      for(const p of parts){
        for(const ch of p.text){
          if(count < i){
            rendered += p.accent ? ('<span class="accent">'+ch+'</span>') : ch;
          }
          count++;
        }
      }
      el.innerHTML = rendered + '<span class="cursor">&nbsp;</span>';
      if(i < full.length){
        i++;
        setTimeout(typeStep, 34);
      }
    }
    setTimeout(typeStep, 500);
  })();

  /* ---------- SCROLL REVEAL ---------- */
  const revealEls = root.querySelectorAll('.reveal');
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if(e.isIntersecting){
        e.target.classList.add('in');
        io.unobserve(e.target);
      }
    });
  }, {threshold:0.15});
  revealEls.forEach(el => io.observe(el));

  /* ---------- MOBILE SERVICES CAROUSEL ---------- */
  (function(){
    const track = root.querySelector('.service-grid');
    const slides = track ? Array.from(track.querySelectorAll('.service-card')) : [];
    const prev = root.querySelector('.service-carousel-prev');
    const next = root.querySelector('.service-carousel-next');
    const status = root.querySelector('.service-carousel-status');
    if(!track || !slides.length || !prev || !next || !status) return;

    let activeIndex = 0;
    let frame;

    function slideLeft(index){
      return slides[index].offsetLeft - track.offsetLeft;
    }

    function updateCarousel(){
      activeIndex = slides.reduce((nearest, slide, index) => {
        return Math.abs(slideLeft(index) - track.scrollLeft) < Math.abs(slideLeft(nearest) - track.scrollLeft)
          ? index
          : nearest;
      }, 0);
      status.textContent = String(activeIndex + 1).padStart(2, '0') + ' / ' + String(slides.length).padStart(2, '0');
      prev.disabled = activeIndex === 0;
      next.disabled = activeIndex === slides.length - 1;
    }

    function goTo(index){
      const target = Math.max(0, Math.min(index, slides.length - 1));
      track.scrollTo({left:slideLeft(target), behavior:'smooth'});
    }

    prev.addEventListener('click', () => goTo(activeIndex - 1));
    next.addEventListener('click', () => goTo(activeIndex + 1));
    track.addEventListener('scroll', () => {
      cancelAnimationFrame(frame);
      frame = requestAnimationFrame(updateCarousel);
    }, {passive:true});
    window.addEventListener('resize', updateCarousel, {passive:true});
    updateCarousel();
  })();

  /* ---------- COUNTERS ---------- */
  const counters = root.querySelectorAll('[data-count]');
  const counterIO = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if(e.isIntersecting){
        const el = e.target;
        const target = parseInt(el.getAttribute('data-count'), 10);
        let cur = 0;
        const step = Math.max(1, Math.round(target/60));
        const iv = setInterval(() => {
          cur += step;
          if(cur >= target){ cur = target; clearInterval(iv); }
          el.textContent = cur;
        }, 22);
        counterIO.unobserve(el);
      }
    });
  }, {threshold:0.5});
  counters.forEach(c => counterIO.observe(c));

  /* ---------- FAQ ACCORDION ---------- */
  root.querySelectorAll('.faq-item').forEach(item => {
    const q = item.querySelector('.faq-q');
    const a = item.querySelector('.faq-a');
    if(item.classList.contains('open')){ a.style.maxHeight = a.scrollHeight + 'px'; }
    q.addEventListener('click', () => {
      const isOpen = item.classList.contains('open');
      root.querySelectorAll('.faq-item').forEach(other => {
        other.classList.remove('open');
        other.querySelector('.faq-a').style.maxHeight = null;
      });
      if(!isOpen){
        item.classList.add('open');
        a.style.maxHeight = a.scrollHeight + 'px';
      }
    });
  });

  /* ---------- GAME TABS ---------- */
  const tabs = root.querySelectorAll('.game-tab');
  const panels = { cliche: document.getElementById('panel-cliche'), sprint: document.getElementById('panel-sprint') };
  const sides = { cliche: document.getElementById('side-cliche'), sprint: document.getElementById('side-sprint') };
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      const key = tab.getAttribute('data-tab');
      Object.keys(panels).forEach(k => panels[k].classList.toggle('active', k === key));
      Object.keys(sides).forEach(k => sides[k].classList.toggle('hidden', k !== key));
      if(gameMain) gameMain.classList.remove('round-over'); // restore backdrop when switching games
    });
  });

  /* ---------- GAME 1: PHRASE CHECKER ---------- */
  const SENTENCES = [
    {text:"Ever since I was a child, I have been passionate about science.", cliche:true},
    {text:"After debugging a production outage at 2am, I realized fault-tolerant systems fascinated me more than the panic did.", cliche:false},
    {text:"I have always had a burning desire to help people.", cliche:true},
    {text:"My professor's offhand comment about incomplete tax models sent me down a two-year rabbit hole in behavioral economics.", cliche:false},
    {text:"I am a highly motivated individual who works well in team and individual settings.", cliche:true},
    {text:"When our village's only water pump failed for a third summer, I mapped the failure points myself before I knew the word 'engineering.'", cliche:false},
    {text:"This program is the perfect stepping stone for my future career.", cliche:true},
    {text:"I chose this program because Professor Lin's 2024 paper on graph neural networks directly extends the bottleneck I hit in my thesis.", cliche:false},
    {text:"Since a young age, I have always dreamed of studying abroad.", cliche:true},
    {text:"The spreadsheet I built to track my grandmother's medication errors became the first dataset I ever cleaned.", cliche:false},
    {text:"I am confident that I will be a valuable asset to your esteemed institution.", cliche:true},
    {text:"Three failed prototypes taught me more about materials science than any lecture did.", cliche:false},
    {text:"I have a strong passion for learning and personal growth.", cliche:true},
    {text:"Losing the regional debate final on a technicality is why I now read contract law for fun.", cliche:false},
    {text:"In today's fast-paced world, education has never been more important.", cliche:true},
    {text:"Auditing my own startup's failed unit economics taught me more about finance than the internship did.", cliche:false},
  ];

  let gameState = {};
  const startBtn = document.getElementById('start-btn');
  const replayBtn = document.getElementById('replay-btn');
  const gameStart = document.getElementById('game-start');
  const gamePlay = document.getElementById('game-play');
  const gameMain = gamePlay ? gamePlay.closest('.game-main') : null;
  const gameEnd = document.getElementById('game-end');
  const gameCard = document.getElementById('game-card');
  const scoreEl = document.getElementById('score');
  const heartsEl = document.getElementById('hearts');
  const timerEl = document.getElementById('timer');
  const progressFill = document.getElementById('progress-fill');
  const feedbackEl = document.getElementById('game-feedback');
  const finalScoreEl = document.getElementById('final-score');
  const endMessage = document.getElementById('end-message');

  function shuffle(arr){
    const a = arr.slice();
    for(let i=a.length-1;i>0;i--){
      const j = Math.floor(Math.random()*(i+1));
      [a[i],a[j]] = [a[j],a[i]];
    }
    return a;
  }

  // ── Animation helpers ── re-trigger a one-shot CSS animation by removing the
  // class, forcing a reflow, then re-adding it, so it replays on every step.
  function retrigger(el, cls){ el.classList.remove(cls); void el.offsetWidth; el.classList.add(cls); }
  function animateCardIn(){ gameCard.classList.remove('flash-ok','flash-bad'); retrigger(gameCard, 'card-in'); }
  function flashCard(kind){
    gameCard.classList.remove('flash-ok','flash-bad','card-in');
    void gameCard.offsetWidth;
    gameCard.classList.add(kind === 'ok' ? 'flash-ok' : 'flash-bad');
  }
  function bumpScore(){ retrigger(scoreEl, 'bump'); }
  function popFeedback(){ retrigger(feedbackEl, 'pop'); }
  function shakeHearts(){ retrigger(heartsEl, 'lose'); }
  function floatPoints(pts){
    const el = document.createElement('span');
    el.className = 'points-pop';
    el.textContent = '+' + pts;
    gamePlay.appendChild(el);
    setTimeout(() => el.remove(), 1500);
  }

  function startGame(){
    gameState = {
      deck: shuffle(SENTENCES),
      idx: 0,
      score: 0,
      lives: 3,
      streak: 0,
      timeLeft: 45,
      ended: false
    };
    gameStart.style.display = 'none';
    gameEnd.classList.add('hidden');
    if(gameMain) gameMain.classList.remove('round-over'); // bring the backdrop back for a new round
    gamePlay.style.display = 'flex';
    scoreEl.textContent = 'Score: 0';
    heartsEl.textContent = '♥♥♥';
    timerEl.textContent = '⏱ 45s';
    progressFill.style.width = '100%';
    feedbackEl.textContent = ' ';
    showNextSentence();
    clearInterval(gameState.timerHandle);
    gameState.timerHandle = setInterval(tick, 1000);
  }

  function tick(){
    gameState.timeLeft -= 1;
    timerEl.textContent = '⏱ ' + gameState.timeLeft + 's';
    progressFill.style.width = (gameState.timeLeft/45*100) + '%';
    if(gameState.timeLeft <= 0){
      endGame('time');
    }
  }

  function showNextSentence(){
    if(gameState.idx >= gameState.deck.length){
      gameState.deck = shuffle(gameState.deck);
      gameState.idx = 0;
    }
    gameCard.textContent = gameState.deck[gameState.idx].text;
    animateCardIn();
  }

  function answer(isClicheGuess){
    if(gameState.ended) return;
    const current = gameState.deck[gameState.idx];
    const correct = (current.cliche === isClicheGuess);
    if(correct){
      gameState.streak += 1;
      const bonus = (gameState.streak > 0 && gameState.streak % 5 === 0) ? 15 : 0;
      gameState.score += 10 + bonus;
      feedbackEl.textContent = bonus ? ('Correct! Streak bonus +' + bonus) : 'Correct!';
      feedbackEl.className = 'game-feedback feedback-good';
      flashCard('ok');
      floatPoints(10 + bonus);
      bumpScore();
    } else {
      gameState.lives -= 1;
      gameState.streak = 0;
      heartsEl.textContent = '♥'.repeat(Math.max(gameState.lives,0)) + '♡'.repeat(3-Math.max(gameState.lives,0));
      feedbackEl.textContent = current.cliche ? 'That one was generic.' : 'That one was actually strong.';
      feedbackEl.className = 'game-feedback feedback-bad';
      flashCard('bad');
      shakeHearts();
    }
    scoreEl.textContent = 'Score: ' + gameState.score;
    popFeedback();
    gameState.idx += 1;

    if(gameState.lives <= 0){
      // Guard further clicks, but hold briefly so the losing shake is visible.
      gameState.ended = true;
      clearInterval(gameState.timerHandle);
      setTimeout(() => endGame('lives'), 950);
      return;
    }
    setTimeout(() => {
      feedbackEl.textContent = ' ';
      showNextSentence();
    }, 900);
  }

  function endGame(reason){
    gameState.ended = true;
    clearInterval(gameState.timerHandle);
    gamePlay.style.display = 'none';
    gameEnd.classList.remove('hidden');
    if(gameMain) gameMain.classList.add('round-over'); // animate the backdrop graphic out
    retrigger(gameEnd, 'end-in');
    finalScoreEl.textContent = gameState.score;
    let msg = 'Nice instincts.';
    if(gameState.score >= 100) msg = "Sharp eye — that's a better generic-line radar than most first drafts we see.";
    else if(gameState.score >= 50) msg = "Solid round. A trained editor still catches what speed-reading misses.";
    else msg = "Generic lines are sneakier than they look — even trained readers miss them under time pressure.";
    endMessage.textContent = msg;
  }

  if(startBtn) startBtn.addEventListener('click', startGame);
  if(replayBtn) replayBtn.addEventListener('click', startGame);
  const btnCliche = document.getElementById('btn-cliche');
  const btnStrong = document.getElementById('btn-strong');
  if(btnCliche) btnCliche.addEventListener('click', () => answer(true));
  if(btnStrong) btnStrong.addEventListener('click', () => answer(false));
  document.addEventListener('keydown', (e) => {
    if(panels.cliche && panels.cliche.classList.contains('active') && gamePlay && gamePlay.style.display === 'flex'){
      if(e.key === 'ArrowLeft') answer(true);
      if(e.key === 'ArrowRight') answer(false);
    }
  });

  /* ---------- GAME 2: TYPEWRITER SPRINT ---------- */
  const SPRINT_LINES = [
    "Three failed prototypes taught me more about materials science than any lecture did.",
    "I chose this program because Professor Lin's research directly extends my thesis.",
    "A pump failure in my village taught me the word engineering before any classroom did.",
    "Auditing my startup's failed unit economics taught me more than the internship itself.",
    "Losing the debate final on a technicality is why I now read contract law for fun."
  ];

  const sprintTarget = document.getElementById('sprint-target');
  const sprintInput = document.getElementById('sprint-input');
  const sprintStatus = document.getElementById('sprint-status');
  const sprintWpm = document.getElementById('sprint-wpm');
  const sprintAcc = document.getElementById('sprint-acc');
  const sprintTime = document.getElementById('sprint-time');
  const sprintResult = document.getElementById('sprint-result');
  const sprintVerdict = document.getElementById('sprint-verdict');
  const sprintNewBtn = document.getElementById('sprint-new');

  let sprintState = { text:'', startTime:null, finished:false, timerHandle:null };

  function renderSprintTarget(typed){
    const words = sprintState.text.split(' ');
    let html = '';
    let gi = 0;
    words.forEach((word, wi) => {
      html += '<span class="word-group">';
      for(let i = 0; i < word.length; i++){
        const ch = word[i];
        if(gi < typed.length){
          html += (typed[gi] === ch) ? ('<span class="ok">'+esc(ch)+'</span>') : ('<span class="bad">'+esc(ch)+'</span>');
        } else {
          html += '<span class="pending">'+esc(ch)+'</span>';
        }
        gi++;
      }
      html += '</span>';
      if(wi < words.length - 1){
        if(gi < typed.length){
          html += (typed[gi] === ' ') ? ('<span class="ok">&nbsp;</span>') : ('<span class="bad">&nbsp;</span>');
        } else {
          html += '<span class="pending">&nbsp;</span>';
        }
        gi++;
      }
    });
    sprintTarget.innerHTML = html;
  }
  function esc(ch){
    if(ch === '&') return '&amp;';
    if(ch === '<') return '&lt;';
    if(ch === '>') return '&gt;';
    return ch;
  }

  function newSprintLine(){
    clearInterval(sprintState.timerHandle);
    sprintState = { text: SPRINT_LINES[Math.floor(Math.random()*SPRINT_LINES.length)], startTime:null, finished:false, timerHandle:null };
    sprintInput.value = '';
    sprintInput.disabled = false;
    sprintStatus.textContent = 'Start typing to begin';
    sprintWpm.textContent = '0';
    sprintAcc.textContent = '—';
    sprintTime.textContent = '0.0s';
    sprintResult.classList.add('hidden');
    renderSprintTarget('');
    retrigger(sprintTarget, 'card-in');
  }

  function computeStats(typed, elapsedSec){
    let correct = 0;
    for(let i = 0; i < typed.length && i < sprintState.text.length; i++){
      if(typed[i] === sprintState.text[i]) correct++;
    }
    const accuracy = typed.length ? Math.round((correct/typed.length)*100) : 100;
    const minutes = Math.max(elapsedSec/60, 0.0166);
    const words = sprintState.text.split(' ').length;
    const wpm = Math.round((typed.length >= sprintState.text.length ? words : (typed.length/5)) / minutes);
    return { accuracy, wpm: Math.max(wpm,0) };
  }

  if(sprintInput){
    sprintInput.addEventListener('input', () => {
      if(sprintState.finished) return;
      const typed = sprintInput.value;
      if(sprintState.startTime === null){
        sprintState.startTime = Date.now();
        sprintStatus.textContent = 'Go — typing…';
        sprintState.timerHandle = setInterval(() => {
          const elapsed = (Date.now() - sprintState.startTime)/1000;
          sprintTime.textContent = elapsed.toFixed(1) + 's';
          const stats = computeStats(sprintInput.value, elapsed);
          sprintWpm.textContent = stats.wpm;
          sprintAcc.textContent = stats.accuracy + '%';
        }, 200);
      }
      renderSprintTarget(typed);

      if(typed.length >= sprintState.text.length){
        const elapsed = (Date.now() - sprintState.startTime)/1000;
        const stats = computeStats(typed, elapsed);
        sprintWpm.textContent = stats.wpm;
        sprintAcc.textContent = stats.accuracy + '%';
        sprintTime.textContent = elapsed.toFixed(1) + 's';
        sprintState.finished = true;
        clearInterval(sprintState.timerHandle);
        sprintInput.disabled = true;
        sprintStatus.textContent = 'Line complete';
        sprintResult.classList.remove('hidden');
        let verdict = 'Nice typing.';
        if(stats.accuracy >= 98 && stats.wpm >= 45) verdict = 'Fast and clean — that\'s submission-ready pace.';
        else if(stats.accuracy >= 90) verdict = 'Solid draft speed. A second pass would tighten the typos.';
        else verdict = 'Speed is there — accuracy is what separates a draft from a final.';
        sprintVerdict.textContent = verdict;
      }
    });
  }

  if(sprintNewBtn) sprintNewBtn.addEventListener('click', newSprintLine);
  newSprintLine();
})();
</script>

<script>
/* GSAP handwriting-comparison animation (#difference). Plays once when the card
   scrolls into view; replays if it fully leaves and re-enters. */
(function(){
  const HW_DATA = [
    { title: "Expert Writers", desc: "Experienced admissions consultants, subject specialists, and professional SOP strategists" },
    { title: "Personalized Consultation", desc: "One-on-one strategy session and detailed profile assessment before writing" },
    { title: "Writing Methodology", desc: "Every document is crafted from scratch by experienced advisors with human expertise" },
    { title: "Quality Assurance", desc: "Multi-level review for clarity, consistency, originality, and impact" },
    { title: "Student Support", desc: "Continuous guidance from profile evaluation to final submission" }
  ];

  const hwRowsEl = document.getElementById('hw-rows');
  const hwCardEl = document.getElementById('hw-card');
  const hwPencilEl = document.getElementById('hw-pencil');
  const hwSignatureEl = document.getElementById('hw-signature');
  const hwSigPath = document.getElementById('hw-sig-path');
  const hwShimmerEl = document.getElementById('hw-shimmer');

  if(!hwRowsEl || typeof gsap === 'undefined') return;

  const HW_CHECK_SVG = `
    <circle cx="10" cy="10" r="10"></circle>
    <path d="M5.5 10.2l3 3 6-6.4"></path>
  `;

  HW_DATA.forEach((row, i) => {
    const rowEl = document.createElement('div');
    rowEl.className = 'hw-row';
    rowEl.dataset.index = i;

    const chars = row.desc.split('').map(c =>
      `<span class="ch">${c === ' ' ? '&nbsp;' : c}</span>`
    ).join('');

    rowEl.innerHTML = `
      <div class="hw-title">${row.title}</div>
      <div class="hw-desc-cell">
        <svg class="hw-check" viewBox="0 0 20 20">${HW_CHECK_SVG}</svg>
        <div class="hw-text-wrap">
          <div class="hw-cursive-text">${chars}</div>
          <div class="hw-crisp-text">${row.desc}</div>
        </div>
        <div class="hw-sweep"></div>
      </div>
    `;
    hwRowsEl.appendChild(rowEl);
  });

  const hwSigLen = hwSigPath.getTotalLength();
  hwSigPath.style.strokeDasharray = hwSigLen;
  hwSigPath.style.strokeDashoffset = hwSigLen;

  let hwMainTl = null;
  let hwHasPlayed = false;

  function hwSpawnDust(x, y) {
    for (let i = 0; i < 3; i++) {
      const d = document.createElement('div');
      d.className = 'hw-dust';
      d.style.left = (x + (Math.random() * 10 - 5)) + 'px';
      d.style.top = (y + (Math.random() * 10 - 5)) + 'px';
      hwCardEl.appendChild(d);
      gsap.fromTo(d,
        { opacity: 0.6, scale: 1 },
        {
          opacity: 0, scale: 0, y: '+=10', x: '+=' + (Math.random() * 8 - 4),
          duration: 0.5, ease: 'power1.out',
          onComplete: () => d.remove()
        }
      );
    }
  }

  function hwResetAll() {
    if (hwMainTl) { hwMainTl.kill(); }
    gsap.set(hwPencilEl, { opacity: 0, x: -40, y: -40, rotate: -38 });
    gsap.set(hwSignatureEl, { opacity: 0 });
    hwSigPath.style.strokeDashoffset = hwSigLen;
    gsap.set(hwShimmerEl, { left: '-40%' });

    document.querySelectorAll('.hw-row').forEach(rowEl => {
      gsap.set(rowEl, { y: 0, boxShadow: 'none' });
      gsap.set(rowEl.querySelector('.hw-title'), { opacity: 0, x: -6 });
      gsap.set(rowEl.querySelector('.hw-crisp-text'), { opacity: 0 });
      gsap.set(rowEl.querySelectorAll('.ch'), { opacity: 0, y: 4, scale: 0.7, rotate: -2 });
      gsap.set(rowEl.querySelector('.hw-check'), { scale: 0, rotate: -25 });
      gsap.set(rowEl.querySelector('.hw-sweep'), { opacity: 0, x: 0 });
    });
  }

  function hwPlayAnimation() {
    hwResetAll();
    hwMainTl = gsap.timeline({ defaults: { ease: 'power2.out' } });
    const cardRect = () => hwCardEl.getBoundingClientRect();

    HW_DATA.forEach((row, i) => {
      const rowEl = hwRowsEl.children[i];
      const titleEl = rowEl.querySelector('.hw-title');
      const cursiveEl = rowEl.querySelector('.hw-cursive-text');
      const crispEl = rowEl.querySelector('.hw-crisp-text');
      const chars = rowEl.querySelectorAll('.ch');
      const checkEl = rowEl.querySelector('.hw-check');
      const sweepEl = rowEl.querySelector('.hw-sweep');
      const textWrap = rowEl.querySelector('.hw-text-wrap');

      hwMainTl.to(hwPencilEl, {
        opacity: 1,
        duration: 0.25,
        ease: 'power2.out',
        onStart: () => {
          const cr = cardRect();
          const tr = textWrap.getBoundingClientRect();
          gsap.set(hwPencilEl, {
            x: tr.left - cr.left - 14,
            y: tr.top - cr.top - 6,
            rotate: -38
          });
        }
      }, i === 0 ? '+=0.1' : '+=0.05');

      hwMainTl.to(titleEl, { opacity: 1, x: 0, duration: 0.25 }, '<');

      const writeDuration = 0.85;
      hwMainTl.to(chars, {
        opacity: 1, y: 0, scale: 1, rotate: 0,
        duration: 0.18,
        stagger: {
          each: writeDuration / Math.max(chars.length, 1),
          onStart: function () {
            const cr = cardRect();
            const tr = textWrap.getBoundingClientRect();
            const idx = Array.prototype.indexOf.call(chars, this.targets()[0]);
            const progress = idx / Math.max(chars.length - 1, 1);
            const px = tr.left - cr.left + progress * tr.width;
            const py = tr.top - cr.top + Math.floor(progress * 1.2) * 2;
            gsap.to(hwPencilEl, {
              x: px - 4,
              y: py - 10,
              rotate: -38 + Math.sin(idx * 1.3) * 6,
              duration: writeDuration / Math.max(chars.length, 1),
              ease: 'sine.inOut'
            });
            if (idx % 3 === 0) hwSpawnDust(px + 6, py + 14 + (hwCardEl.getBoundingClientRect().top - cardRect().top));
          }
        }
      }, '>-0.05');

      hwMainTl.to(cursiveEl, { opacity: 0, duration: 0.25 }, '+=0.05');
      hwMainTl.to(crispEl, { opacity: 1, duration: 0.25 }, '<');

      hwMainTl.to(checkEl, {
        scale: 1, rotate: 0, duration: 0.2, ease: 'back.out(3)'
      }, '<0.05');

      hwMainTl.fromTo(sweepEl,
        { opacity: 0.9, x: -20 },
        { opacity: 0, x: () => textWrap.getBoundingClientRect().width + 20, duration: 0.3, ease: 'power1.in' },
        '<'
      );
    });

    hwMainTl.to(hwPencilEl, {
      opacity: 1,
      duration: 0.2,
      onStart: () => {
        const cr = cardRect();
        const sr = hwSignatureEl.getBoundingClientRect();
        gsap.set(hwPencilEl, { x: sr.left - cr.left - 10, y: sr.top - cr.top - 6, rotate: -30 });
      }
    }, '+=0.15');

    hwMainTl.to(hwSignatureEl, { opacity: 1, duration: 0.1 }, '<');
    hwMainTl.to(hwSigPath, {
      strokeDashoffset: 0,
      duration: 0.5,
      ease: 'power1.inOut'
    }, '<');

    hwMainTl.to(hwPencilEl, { opacity: 0, duration: 0.3, y: '-=20', x: '+=10' }, '-=0.1');

    hwMainTl.to(hwShimmerEl, {
      left: '140%',
      duration: 0.7,
      ease: 'power1.inOut'
    }, '+=0.05');

    hwMainTl.to(Array.from(hwRowsEl.children), {
      y: -3,
      boxShadow: '0 8px 20px -10px rgba(26,0,136,0.25)',
      duration: 0.4,
      stagger: 0.03,
      ease: 'power2.out'
    }, '<');
  }

  const hwObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting && entry.intersectionRatio >= 0.3) {
        if (!hwHasPlayed) {
          hwHasPlayed = true;
          hwPlayAnimation();
        }
      } else if (!entry.isIntersecting) {
        hwHasPlayed = false;
      }
    });
  }, { threshold: [0, 0.3] });

  hwObserver.observe(hwCardEl);
})();
</script>
@endsection
