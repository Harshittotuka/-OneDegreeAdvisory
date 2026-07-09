{{-- Visa — Student Hub landing page with a free visa-eligibility pre-check.
     This is the design of the standalone visa.html the client shared, kept
     verbatim (Fraunces / Work Sans / Space Mono, navy / red-orange / gold), but
     rendered on the shared site layout so the navbar + footer match the rest of
     the site. The whole page is scoped under #visa-page so its generic class
     names (.hero, .btn, .wrap, section{}, .service-card …) never collide with the
     global styles.css / stripe-nav.css that style the shared chrome.

     Two functional changes from the original file:
       • the "Talk to a visa advisor" form is now a button that routes to /contact
       • the checker result's "Connect with a counsellor" opens an in-page
         WhatsApp / email popup (openCounsellorModal) instead of a bare wa.me link --}}
@extends('layouts.app')

@php
    $waE164  = config('site.contact.phone_e164', '918233365888');
    $waPhone = config('site.contact.phone', '+91 8233365888');
    $waEmail = config('site.contact.email', 'admissions@onedegreeadvisory.com');
    $waLink  = 'https://wa.me/'.$waE164.'?text='.rawurlencode('Hi One Degree Advisory, I would like to talk to a visa counsellor about my student-visa file.');
    $mailLink = 'mailto:'.$waEmail.'?subject='.rawurlencode('Student visa guidance enquiry').'&body='.rawurlencode("Hi One Degree Advisory,\n\nI'd like guidance on my student visa. A few details about me:\n\nDestination country: \nLevel (Bachelors/Masters/MBA/PhD): \nCurrent status: \n\nThanks!");
@endphp

@push('head')
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Work+Sans:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
  /* Neutralise the site's body gradient (fades dark toward the footer) so the
     page's paper colour runs uninterrupted down to the shared footer. */
  body.visa-page-body{ background:#F7F5F1; }

  /* ============================================================
     Everything below is scoped under #visa-page so it can never
     touch the shared navbar / footer (which sit outside it).
     ============================================================ */
  #visa-page{
    --navy:#1B1552;
    --navy-deep:#100C38;
    --red:#FF6A39;
    --red-deep:#E24E1B;
    --gold:#E8A33D;
    --ink:#221E3B;
    --paper:#F7F5F1;
    --card:#FFFFFF;
    --altbg:#EFECF6;
    --line:#DDD8EC;
    --muted:#635E7C;
    --success:#1F7A4C;
    --success-bg:#E4F3EA;
    --warning:#B9790B;
    --warning-bg:#FBF0DC;
    --danger:#B23A2F;
    --danger-bg:#FBEAE7;
    --font-head:'Fraunces',serif;
    --font-body:'Work Sans',sans-serif;
    --font-mono:'Space Mono',monospace;
    --grad-hero:radial-gradient(1100px 520px at 85% -10%,rgba(255,106,57,.14),transparent 60%),radial-gradient(800px 460px at -5% 10%,rgba(27,21,82,.10),transparent 55%);
    --grad-navy:linear-gradient(135deg,var(--navy) 0%,var(--navy-deep) 100%);
    --grad-brand:linear-gradient(135deg,var(--red) 0%,var(--gold) 100%);
    --shadow-soft:0 20px 50px rgba(27,21,82,.12);
    --shadow-lift:0 14px 30px rgba(27,21,82,.16);

    font-family:var(--font-body);
    color:var(--ink);
    background:var(--paper);
    line-height:1.65;
    font-size:17px;
    -webkit-font-smoothing:antialiased;
  }
  #visa-page *{box-sizing:border-box;}
  #visa-page h1,#visa-page h2,#visa-page h3,#visa-page h4{font-family:var(--font-head);margin:0;line-height:1.14;max-width:none;}
  #visa-page p{margin:0;}
  #visa-page a{color:inherit;text-decoration:none;}
  #visa-page img{display:block;}
  #visa-page button{font-family:var(--font-body);cursor:pointer;}
  #visa-page .wrap{max-width:1180px;margin:0 auto;padding:0 24px;}
  #visa-page .eyebrow{font-family:var(--font-mono);font-size:12.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--red-deep);font-weight:600;}
  #visa-page .sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);}

  /* ===== scroll reveal (uses the site engine — .is-visible added by script.js) ===== */
  #visa-page .reveal{opacity:0;transform:translateY(18px);transition:opacity .7s ease,transform .7s ease;}
  #visa-page .reveal.is-visible{opacity:1;transform:translateY(0);}
  @media(prefers-reduced-motion:reduce){#visa-page .reveal{opacity:1;transform:none;transition:none;}}

  /* ===== BUTTONS ===== */
  #visa-page .btn{display:inline-flex;align-items:center;gap:8px;padding:14px 24px;border-radius:999px;font-weight:600;font-size:15.5px;border:1.5px solid transparent;transition:transform .15s ease,box-shadow .15s ease,background .15s ease,color .15s ease;}
  #visa-page .btn:active{transform:scale(.97);}
  #visa-page .btn-primary{background:var(--red);background-image:linear-gradient(135deg,var(--red),var(--red-deep));color:#fff;border-color:var(--red);box-shadow:0 10px 22px rgba(217,136,41,.28);}
  #visa-page .btn-primary:hover{filter:brightness(1.04);box-shadow:0 14px 28px rgba(217,136,41,.34);}
  #visa-page .btn-ghost{background:transparent;color:var(--navy);border-color:var(--navy);}
  #visa-page .btn-ghost:hover{background:var(--navy);color:#fff;}

  /* ===== HERO: editorial ===== */
  #visa-page .hero{padding:68px 0 64px;background-image:var(--grad-hero);}
  #visa-page .hero-grid{display:flex;align-items:center;justify-content:space-between;gap:40px;}
  #visa-page .hero-copy{max-width:700px;flex:1 1 auto;}
  #visa-page .hero-side-image{width:286px;flex-shrink:0;border-radius:16px;overflow:hidden;box-shadow:0 20px 45px rgba(27,46,94,.25);border:3px solid #fff;}
  #visa-page .hero-side-image img{display:block;width:100%;height:auto;}
  @media(max-width:960px){#visa-page .hero-side-image{display:none;}}
  #visa-page .hero-copy h1{font-size:47px;color:var(--navy);letter-spacing:-.01em;margin:16px 0 18px;}
  @media(max-width:640px){#visa-page .hero-copy h1{font-size:32px;}}
  #visa-page .hero-copy .lead{font-size:18.5px;color:var(--muted);max-width:820px;}
  #visa-page .hero-ctas{display:flex;gap:12px;margin-top:28px;flex-wrap:wrap;}

  /* ===== SECTION BASE ===== */
  #visa-page section{padding:80px 0;}
  #visa-page .section-head{max-width:820px;margin-bottom:40px;}
  #visa-page .section-head h2{font-size:32px;color:var(--navy);margin-top:9px;}
  #visa-page .section-head .lead{color:var(--muted);font-size:16.5px;margin-top:13px;}
  #visa-page .altbg{background:var(--altbg);}

  #visa-page .btn svg{width:18px;height:18px;flex-shrink:0;}

  /* ===== card entrance (staggered, animation-based so delays never lag hover) ===== */
  @keyframes v-card-in{from{opacity:0;transform:translateY(22px);}to{opacity:1;transform:translateY(0);}}
  #visa-page .services-grid .reveal,
  #visa-page .stat-grid .reveal,
  #visa-page .checker-explainer .reveal{opacity:0;transform:none;transition:transform .25s ease,box-shadow .25s ease,border-color .25s ease;}
  #visa-page .services-grid .reveal.is-visible,
  #visa-page .stat-grid .reveal.is-visible,
  #visa-page .checker-explainer .reveal.is-visible{opacity:1;animation:v-card-in .65s cubic-bezier(.22,1,.36,1) backwards;}
  #visa-page .services-grid .reveal.is-visible:nth-child(2){animation-delay:.07s}
  #visa-page .services-grid .reveal.is-visible:nth-child(3){animation-delay:.14s}
  #visa-page .services-grid .reveal.is-visible:nth-child(4){animation-delay:.21s}
  #visa-page .services-grid .reveal.is-visible:nth-child(5){animation-delay:.1s}
  #visa-page .services-grid .reveal.is-visible:nth-child(6){animation-delay:.17s}
  #visa-page .services-grid .reveal.is-visible:nth-child(7){animation-delay:.24s}
  #visa-page .services-grid .reveal.is-visible:nth-child(8){animation-delay:.31s}
  #visa-page .stat-grid .reveal.is-visible:nth-child(2){animation-delay:.08s}
  #visa-page .stat-grid .reveal.is-visible:nth-child(3){animation-delay:.16s}
  #visa-page .stat-grid .reveal.is-visible:nth-child(4){animation-delay:.24s}
  #visa-page .checker-explainer .reveal.is-visible:nth-child(2){animation-delay:.12s}

  /* ===== SERVICE CARDS ===== */
  #visa-page .services-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-top:32px;}
  @media(max-width:900px){#visa-page .services-grid{grid-template-columns:repeat(2,1fr);}}
  @media(max-width:560px){#visa-page .services-grid{grid-template-columns:1fr;}}
  #visa-page .service-card{position:relative;overflow:hidden;display:flex;flex-direction:column;border:1px solid var(--line);border-radius:12px;padding:20px 19px 24px;background:var(--card);}
  #visa-page .service-card::before{content:"";position:absolute;top:0;left:0;right:0;height:3px;background:var(--grad-brand);transform:scaleX(0);transform-origin:left;transition:transform .35s ease;}
  #visa-page .service-card::after{content:attr(data-num);position:absolute;right:10px;bottom:-20px;font-family:var(--font-head);font-weight:700;font-size:76px;line-height:1;color:var(--navy);opacity:.05;pointer-events:none;transition:opacity .3s ease,color .3s ease;}
  #visa-page .service-card:hover{transform:translateY(-5px);box-shadow:var(--shadow-lift);border-color:rgba(255,106,57,.42);}
  #visa-page .service-card:hover::before{transform:scaleX(1);}
  #visa-page .service-card:hover::after{color:var(--red-deep);opacity:.09;}
  #visa-page .sc-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
  /* Icon tiles follow the site-wide UNIFIED ICON-TILE SYSTEM (home "signal"
     style, end of styles.css): 52px var(--aqua) tile, radius 16, 24px
     var(--teal-dark) icon, hover scale(1.06) rotate(-3deg) + warm tint.
     Both tokens cascade from the cream theme on <html>, so these tiles
     render identically to the home page icons. */
  #visa-page .sc-icon{width:52px;height:52px;border-radius:16px;display:inline-flex;align-items:center;justify-content:center;background:var(--aqua);color:var(--teal-dark);transition:transform .28s ease,background .28s ease;}
  #visa-page .sc-icon svg{width:24px;height:24px;stroke-width:2;}
  #visa-page .service-card:hover .sc-icon{transform:scale(1.06) rotate(-3deg);background:color-mix(in srgb,#ff5e32 18%,#e8f7f3);}
  #visa-page .service-card .num{font-family:var(--font-mono);color:var(--red);font-size:11.5px;font-weight:700;letter-spacing:.08em;}
  #visa-page .service-card h4{font-size:15px;margin:0 0 6px;color:var(--navy);}
  #visa-page .service-card p{font-size:13px;color:var(--muted);}

  /* ===== EXPLAINER (edu) CARDS ===== */
  #visa-page .edu-card{display:flex;gap:16px;align-items:flex-start;background:var(--card);border:1px solid var(--line);border-left:3px solid var(--red);border-radius:0 12px 12px 0;padding:20px 22px;}
  #visa-page .edu-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-lift);}
  /* same unified icon-tile recipe as .sc-icon above */
  #visa-page .edu-icon{flex-shrink:0;width:52px;height:52px;border-radius:16px;background:var(--aqua);color:var(--teal-dark);display:inline-flex;align-items:center;justify-content:center;transition:transform .28s ease,background .28s ease;}
  #visa-page .edu-icon svg{width:24px;height:24px;stroke-width:2;}
  #visa-page .edu-card:hover .edu-icon{transform:scale(1.06) rotate(-3deg);background:color-mix(in srgb,#ff5e32 18%,#e8f7f3);}
  #visa-page .edu-card h4{font-size:15px;color:var(--navy);margin-bottom:7px;}
  #visa-page .edu-card p{font-size:14px;color:var(--muted);}

  /* ===== STAT CARDS (hover polish) ===== */
  #visa-page .stat-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-lift);border-color:rgba(255,106,57,.35);}

  /* ===== ADVISOR PANEL (Talk to a visa advisor) ===== */
  @keyframes v-spin-c{from{transform:translate(-50%,-50%) rotate(0deg);}to{transform:translate(-50%,-50%) rotate(360deg);}}
  @keyframes v-bob{0%,100%{transform:translate(-50%,-50%) translateY(0);}50%{transform:translate(-50%,-50%) translateY(-8px);}}
  @keyframes v-float{0%,100%{transform:translateY(0);}50%{transform:translateY(-9px);}}
  @keyframes v-orbit2{from{transform:translate(-50%,-50%) rotate(0deg) translateX(86px) rotate(0deg);}to{transform:translate(-50%,-50%) rotate(360deg) translateX(86px) rotate(-360deg);}}
  #visa-page .advisor-card{position:relative;overflow:hidden;display:grid;grid-template-columns:minmax(0,1.2fr) minmax(240px,.8fr);gap:30px;align-items:center;background:var(--card);border:1.5px solid var(--line);border-radius:16px;padding:38px;margin-top:34px;box-shadow:var(--shadow-soft);}
  #visa-page .advisor-card::before{content:"";position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad-brand);}
  #visa-page .advisor-card h3{font-size:26px;color:var(--navy);margin:10px 0 12px;}
  #visa-page .advisor-lead{color:var(--muted);font-size:15.5px;max-width:560px;}
  #visa-page .advisor-points{list-style:none;display:flex;flex-wrap:wrap;gap:10px;margin:18px 0 24px;padding:0;}
  #visa-page .advisor-points li{display:inline-flex;align-items:center;gap:7px;font-size:13.5px;font-weight:600;color:var(--navy);background:var(--altbg);border-radius:999px;padding:8px 14px;}
  #visa-page .advisor-points li::before{content:"\2713";color:var(--red-deep);font-weight:700;}
  #visa-page .advisor-art{position:relative;height:280px;min-width:240px;}
  #visa-page .advisor-art .ring{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);border-radius:50%;}
  #visa-page .advisor-art .ring.r1{width:236px;height:236px;border:1.5px solid var(--line);}
  #visa-page .advisor-art .ring.r2{width:172px;height:172px;border:1.5px dashed rgba(255,106,57,.55);animation:v-spin-c 26s linear infinite;}
  #visa-page .advisor-art .ring.r3{width:112px;height:112px;border:1.5px solid rgba(27,21,82,.16);}
  #visa-page .advisor-art .stamp{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:86px;height:86px;border-radius:50%;background:var(--grad-navy);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 16px 32px rgba(27,21,82,.28);animation:v-bob 5s ease-in-out infinite;}
  #visa-page .advisor-art .stamp svg{width:34px;height:34px;}
  #visa-page .advisor-art .orbit-dot2{position:absolute;top:50%;left:50%;width:11px;height:11px;border-radius:50%;background:var(--gold);box-shadow:0 0 16px rgba(232,163,61,.7);animation:v-orbit2 12s linear infinite;}
  #visa-page .art-chip{position:absolute;display:inline-flex;align-items:center;gap:7px;background:#fff;border:1px solid var(--line);border-radius:999px;padding:8px 13px;font-size:12.5px;font-weight:700;color:var(--navy);box-shadow:var(--shadow-lift);white-space:nowrap;z-index:1;}
  #visa-page .art-chip svg{width:14px;height:14px;color:var(--red-deep);flex-shrink:0;}
  #visa-page .art-chip.c1{top:16px;right:2px;animation:v-float 6s ease-in-out infinite;}
  #visa-page .art-chip.c2{bottom:18px;left:0;animation:v-float 7s ease-in-out 1.2s infinite;}
  @media(max-width:820px){
    #visa-page .advisor-card{grid-template-columns:1fr;padding:28px 22px;}
    #visa-page .advisor-art{display:none;}
  }

  /* ===== VISA ELIGIBILITY CHECKER ===== */
  #visa-page .checker-wrap{background:var(--card);border:1.5px solid var(--line);border-radius:14px;padding:32px;margin-top:34px;box-shadow:var(--shadow-soft);}
  #visa-page .checker-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:6px;}
  @media(max-width:640px){#visa-page .checker-grid{grid-template-columns:1fr;}}
  #visa-page .checker-grid label{font-size:13px;font-weight:600;color:var(--muted);display:block;margin-bottom:7px;}
  #visa-page .checker-grid select,#visa-page .checker-grid input{width:100%;padding:12px 14px;border-radius:6px;border:1.5px solid var(--line);font-size:15px;font-family:var(--font-body);background:var(--paper);}
  #visa-page .checker-grid select:focus,#visa-page .checker-grid input:focus{outline:none;border-color:var(--red);}
  #visa-page .checker-actions{margin-top:26px;display:flex;gap:12px;flex-wrap:wrap;align-items:center;}
  #visa-page .checker-note{font-size:13px;color:var(--muted);margin-top:14px;}
  #visa-page .result-card{margin-top:28px;border-radius:12px;padding:26px;border:1.5px solid var(--line);display:none;}
  #visa-page .result-card.show{display:block;animation:v-result-in .45s cubic-bezier(0.22,1,0.36,1);}
  @keyframes v-result-in{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:translateY(0);}}
  #visa-page .result-card.green{background:var(--success-bg);border-color:var(--success);}
  #visa-page .result-card.yellow{background:var(--warning-bg);border-color:var(--warning);}
  #visa-page .result-card.red{background:var(--danger-bg);border-color:var(--danger);}
  #visa-page .result-top{display:flex;align-items:center;gap:12px;}
  #visa-page .result-dot{width:14px;height:14px;border-radius:50%;flex:none;}
  #visa-page .result-card.green .result-dot{background:var(--success);}
  #visa-page .result-card.yellow .result-dot{background:var(--warning);}
  #visa-page .result-card.red .result-dot{background:var(--danger);}
  #visa-page .result-title{font-family:var(--font-head);font-size:20px;color:var(--navy);}
  #visa-page .result-sub{font-size:14.5px;color:var(--ink);margin-top:4px;}
  #visa-page .result-reasons{margin-top:16px;padding-left:20px;}
  #visa-page .result-reasons li{font-size:14px;color:var(--ink);margin-bottom:7px;}
  #visa-page .result-cta{margin-top:20px;padding-top:18px;border-top:1px solid rgba(0,0,0,.08);display:flex;gap:12px;flex-wrap:wrap;align-items:center;}
  #visa-page .result-cta p{font-size:14px;color:var(--muted);flex:1;min-width:220px;}
  #visa-page .checker-explainer{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:32px;}
  @media(max-width:760px){#visa-page .checker-explainer{grid-template-columns:1fr;}}

  /* ===== MARKET SNAPSHOT ===== */
  #visa-page .stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-top:32px;}
  @media(max-width:900px){#visa-page .stat-grid{grid-template-columns:1fr 1fr;}}
  @media(max-width:560px){#visa-page .stat-grid{grid-template-columns:1fr;}}
  #visa-page .stat-card{background:var(--card);border:1px solid var(--line);border-radius:8px;padding:22px 20px;}
  #visa-page .stat-card .stat-num{font-family:var(--font-head);font-size:29px;font-weight:700;color:var(--navy);}
  #visa-page .stat-card .stat-label{font-size:14px;color:var(--ink);font-weight:600;margin-top:5px;}
  #visa-page .stat-card .stat-src{font-family:var(--font-mono);font-size:11px;color:var(--muted);margin-top:11px;letter-spacing:.02em;}
  #visa-page .snapshot-take{margin-top:26px;background:var(--altbg);border-radius:10px;padding:24px 26px;font-size:15.5px;color:var(--ink);max-width:820px;}
  #visa-page .snapshot-take strong{color:var(--navy);}

  /* ===== COUNSELLOR MODAL (WhatsApp / Email) ===== */
  #visa-page .vc-modal{position:fixed;inset:0;z-index:1000;display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;transition:opacity .28s ease;font-family:var(--font-body);}
  /* The explicit display:flex above would override the `hidden` attribute's UA
     display:none — without this rule the closed modal sits invisibly over the
     whole page and swallows every click (navbar/footer included). */
  #visa-page .vc-modal[hidden]{display:none;}
  #visa-page .vc-modal.is-open{opacity:1;}
  #visa-page .vc-backdrop{position:absolute;inset:0;background:rgba(16,12,56,.55);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);}
  #visa-page .vc-card{position:relative;width:min(440px,100%);background:var(--card);border-radius:18px;padding:36px 30px 26px;text-align:center;box-shadow:0 40px 90px rgba(16,12,56,.42);transform:translateY(22px) scale(.94);opacity:0;transition:transform .4s cubic-bezier(.34,1.56,.64,1),opacity .3s ease;overflow:hidden;}
  #visa-page .vc-modal.is-open .vc-card{transform:translateY(0) scale(1);opacity:1;}
  #visa-page .vc-card::before{content:"";position:absolute;top:-70px;left:50%;transform:translateX(-50%);width:280px;height:180px;background:radial-gradient(circle,rgba(255,106,57,.16),transparent 70%);pointer-events:none;}
  #visa-page .vc-close{position:absolute;top:16px;right:16px;width:36px;height:36px;border-radius:50%;border:none;background:var(--altbg);color:var(--navy);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .15s ease,transform .15s ease;}
  #visa-page .vc-close:hover{background:#e3def3;transform:rotate(90deg);}
  #visa-page .vc-close svg{width:18px;height:18px;}
  #visa-page .vc-badge{position:relative;width:74px;height:74px;margin:4px auto 20px;border-radius:20px;background:var(--grad-navy);color:#fff;display:flex;align-items:center;justify-content:center;}
  #visa-page .vc-badge svg{width:34px;height:34px;position:relative;z-index:2;}
  #visa-page .vc-badge .pulse{position:absolute;inset:0;border-radius:20px;border:2px solid var(--red);opacity:0;animation:vc-pulse 2.2s ease-out infinite;}
  #visa-page .vc-badge .pulse.d2{animation-delay:1.1s;}
  @keyframes vc-pulse{0%{transform:scale(1);opacity:.6;}100%{transform:scale(1.55);opacity:0;}}
  #visa-page .vc-card h3{font-family:var(--font-head);font-size:24px;color:var(--navy);margin-bottom:8px;}
  #visa-page .vc-sub{font-size:14.5px;color:var(--muted);margin-bottom:24px;}
  #visa-page .vc-options{display:flex;flex-direction:column;gap:12px;text-align:left;}
  #visa-page .vc-opt{display:flex;align-items:center;gap:15px;padding:15px 18px;border-radius:12px;border:1.5px solid var(--line);background:var(--card);transform:translateY(14px);opacity:0;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease,background .18s ease;}
  #visa-page .vc-modal.is-open .vc-opt{transform:translateY(0);opacity:1;transition:transform .45s cubic-bezier(.22,1,.36,1),opacity .45s ease,box-shadow .18s ease,border-color .18s ease,background .18s ease;}
  #visa-page .vc-modal.is-open .vc-opt.o1{transition-delay:.12s;}
  #visa-page .vc-modal.is-open .vc-opt.o2{transition-delay:.2s;}
  #visa-page .vc-modal.is-open .vc-opt.o3{transition-delay:.28s;}
  #visa-page .vc-opt:hover{transform:translateY(-2px);box-shadow:var(--shadow-lift);}
  #visa-page .vc-opt-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;}
  #visa-page .vc-opt-icon svg{width:24px;height:24px;}
  #visa-page .vc-opt--form:hover{border-color:var(--navy);background:#F4F3FB;}
  #visa-page .vc-opt--form .vc-opt-icon{background:var(--grad-navy);}
  #visa-page .vc-opt--wa:hover{border-color:#1F9D65;background:#F1FBF5;}
  #visa-page .vc-opt--wa .vc-opt-icon{background:linear-gradient(135deg,#25D366,#128C7E);}
  #visa-page .vc-opt--mail:hover{border-color:var(--red);background:#FFF4EF;}
  #visa-page .vc-opt--mail .vc-opt-icon{background:var(--grad-brand);}
  #visa-page .vc-opt-text{flex:1;min-width:0;}
  #visa-page .vc-opt-text strong{display:block;font-family:var(--font-head);font-size:16px;color:var(--navy);}
  #visa-page .vc-opt-text small{display:block;font-size:12.5px;color:var(--muted);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
  #visa-page .vc-opt-arrow{color:var(--muted);flex-shrink:0;transition:transform .18s ease,color .18s ease;}
  #visa-page .vc-opt-arrow svg{width:18px;height:18px;}
  #visa-page .vc-opt:hover .vc-opt-arrow{transform:translateX(4px);color:var(--red-deep);}
  #visa-page .vc-foot{font-size:13px;color:var(--muted);margin-top:20px;}
  #visa-page .vc-foot a{color:var(--navy);font-weight:600;}
  @media(prefers-reduced-motion:reduce){
    #visa-page .vc-badge .pulse{animation:none;}
    #visa-page .vc-card,#visa-page .vc-opt{transition:opacity .2s ease;}
    #visa-page .result-card.show{animation:none;}
    #visa-page .services-grid .reveal,
    #visa-page .stat-grid .reveal,
    #visa-page .checker-explainer .reveal{opacity:1;animation:none;}
    #visa-page .services-grid .reveal.is-visible,
    #visa-page .stat-grid .reveal.is-visible,
    #visa-page .checker-explainer .reveal.is-visible{animation:none;}
    #visa-page .advisor-art .ring.r2,
    #visa-page .advisor-art .stamp,
    #visa-page .advisor-art .orbit-dot2,
    #visa-page .art-chip{animation:none;}
  }
</style>
@endpush

@section('content')
<main id="{{ $mainId ?? 'main' }}">
  <div id="visa-page">

    <section class="hero" id="top">
      <div class="wrap hero-grid">
        <div class="hero-copy">
          <p class="eyebrow">Global education advisory · Jaipur</p>
          <h1>You're one degree away from the world.</h1>
          <p class="lead">A visa readiness pre-check and expert visa guidance — for any university you're aiming for, ranked or not. Built around your actual profile, not a template.</p>
          <div class="hero-ctas">
            <button type="button" class="btn btn-primary" data-visa-jump="checker">Check my visa eligibility</button>
            <button type="button" class="btn btn-ghost" data-visa-jump="visa">Talk to a visa advisor</button>
          </div>
        </div>
        <div class="hero-side-image">
          <img src="{{ asset('assets/visa/visa-approval.jpg') }}" alt="Visa approval manifestation collage" onerror="this.closest('.hero-side-image').style.display='none'">
        </div>
      </div>
    </section>

    {{-- ================= VISA ELIGIBILITY CHECKER ================= --}}
    <section id="checker">
      <div class="wrap">
        <div class="section-head reveal">
          <p class="eyebrow">Free 60-second pre-check</p>
          <h2>Where does your file stand, before you spend a rupee on applications?</h2>
          <p class="lead">Answer a few basic questions about your profile — no university selection needed here, just you. We'll give you an instant read on your destination, then connect you with a counsellor to go deeper.</p>
        </div>

        <div class="checker-wrap">
          <div class="checker-grid">
            <div>
              <label for="ckCountry">Destination country</label>
              <select id="ckCountry"></select>
            </div>
            <div>
              <label for="ckLevel">Level you're applying for</label>
              <select id="ckLevel">
                <option>Bachelors</option>
                <option selected>Masters</option>
                <option>MBA</option>
                <option>PhD</option>
              </select>
            </div>
            <div>
              <label for="ckIelts">IELTS / PTE band (enter IELTS-equivalent)</label>
              <select id="ckIelts">
                <option value="5">Below 5.5</option>
                <option value="5.5">5.5</option>
                <option value="6" selected>6.0</option>
                <option value="6.5">6.5</option>
                <option value="7">7.0</option>
                <option value="7.5">7.5+</option>
                <option value="0">Not taken yet / Planning MOI waiver</option>
              </select>
            </div>
            <div>
              <label for="ckGap">Academic gap since last qualification</label>
              <select id="ckGap">
                <option value="0" selected>No gap</option>
                <option value="1">1 year</option>
                <option value="2">2 years</option>
                <option value="3">3 years</option>
                <option value="4">4 years</option>
                <option value="6">5+ years</option>
              </select>
            </div>
            <div>
              <label for="ckBacklog">Active academic backlogs</label>
              <select id="ckBacklog">
                <option value="0" selected>0</option>
                <option value="2">1–2</option>
                <option value="5">3–5</option>
                <option value="9">6–9</option>
                <option value="12">10+</option>
              </select>
            </div>
            <div>
              <label for="ckFunds">Funds readily available (₹)</label>
              <select id="ckFunds">
                <option value="500000">Under ₹5,00,000</option>
                <option value="1000000">₹5,00,000 – ₹10,00,000</option>
                <option value="1800000" selected>₹10,00,000 – ₹20,00,000</option>
                <option value="2800000">₹20,00,000 – ₹30,00,000</option>
                <option value="3500000">Above ₹30,00,000</option>
              </select>
            </div>
          </div>

          <div class="checker-actions">
            <button type="button" class="btn btn-primary" data-visa-checker>Check my eligibility</button>
            <span class="checker-note" style="margin-top:0;">Takes under a minute. No document upload required.</span>
          </div>
          <p class="checker-note">This is a directional pre-check based on commonly published thresholds — not a guarantee of visa approval or university admission. Only an embassy or immigration authority makes the final call.</p>

          <div class="result-card" id="ckResult" role="status" aria-live="polite">
            <div class="result-top">
              <span class="result-dot" id="ckDot"></span>
              <div>
                <div class="result-title" id="ckTitle"></div>
                <div class="result-sub" id="ckSub"></div>
              </div>
            </div>
            <ul class="result-reasons" id="ckReasons"></ul>
            <div class="result-cta">
              <p id="ckCtaText">Get a proper read on your specific file — a counsellor can catch things this quick check can't.</p>
              <button type="button" class="btn btn-primary" data-counsellor-open>Connect with a counsellor</button>
            </div>
          </div>
        </div>

        <div class="checker-explainer">
          <div class="edu-card reveal">
            <span class="edu-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="m9 12 2 2 4-4"/></svg></span>
            <div><h4>What this checker looks at</h4><p>Four things officers weigh heavily: your English test score (or MOI eligibility), any gap since your last qualification, active academic backlogs, and visible, traceable funds — checked against typical thresholds for your chosen destination.</p></div>
          </div>
          <div class="edu-card reveal">
            <span class="edu-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><path d="m2 2 20 20"/></svg></span>
            <div><h4>What it can't see</h4><p>Interview performance, prior visa refusals, the strength of your SOP, and university-specific admission criteria all matter just as much — and none of those show up in a quick form. That's what a counsellor conversation is for.</p></div>
          </div>
        </div>
      </div>
    </section>

    {{-- ================= MARKET SNAPSHOT (sourced) ================= --}}
    <section class="altbg" id="snapshot">
      <div class="wrap">
        <div class="section-head reveal">
          <p class="eyebrow">Why this matters right now</p>
          <h2>The application climate, in numbers — not opinions.</h2>
          <p class="lead">For students preparing their visa file, and for anyone sizing up the market: here's what's actually happening, sourced.</p>
        </div>
        <div class="stat-grid">
          <div class="stat-card reveal">
            <div class="stat-num">1.3M+</div>
            <div class="stat-label">Indian students studying abroad as of 2024</div>
            <div class="stat-src">Source: Ministry of External Affairs, via NITI Aayog report</div>
          </div>
          <div class="stat-card reveal">
            <div class="stat-num">7.6L</div>
            <div class="stat-label">Indian students who went abroad in 2024 alone — down from 2023's peak of 8.9L</div>
            <div class="stat-src">Source: Bureau of Immigration, Lok Sabha data</div>
          </div>
          <div class="stat-card reveal">
            <div class="stat-num">32% → 74%</div>
            <div class="stat-label">Canada's refusal rate for Indian study-permit applicants, Aug 2023 vs Aug 2025</div>
            <div class="stat-src">Source: IRCC data, reported by Reuters</div>
          </div>
          <div class="stat-card reveal">
            <div class="stat-num">Top 3</div>
            <div class="stat-label">Where a weak SOP now ranks among global visa refusal reasons</div>
            <div class="stat-src">Source: ICEF Monitor</div>
          </div>
        </div>
        <div class="snapshot-take">
          <strong>What this means for you:</strong> officers are refusing more files, faster, and a generic or copy-pasted SOP is now a genuine liability rather than a formality. It also means the biggest cost of poor guidance rarely falls on students at globally top-ranked universities — it falls on the much larger group applying to public and private colleges outside the QS Top 50, who often get less scrutiny on their SOP and more scrutiny at the visa desk. That's the gap our service is built to close.
        </div>
      </div>
    </section>

    {{-- ================= VISA SEGMENT ================= --}}
    <section id="visa">
      <div class="wrap">
        <p class="eyebrow">Visa services</p>
        <div class="services-grid">
          <div class="service-card reveal" data-num="01">
            <div class="sc-top">
              <span class="sc-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="m9 15 2 2 4-4"/></svg></span>
              <span class="num">01</span>
            </div>
            <h4>Student visa assessment</h4><p>A full read of your file against the destination country's current requirements.</p>
          </div>
          <div class="service-card reveal" data-num="02">
            <div class="sc-top">
              <span class="sc-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m13.5 8.5-5 5"/><path d="m8.5 8.5 5 5"/><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg></span>
              <span class="num">02</span>
            </div>
            <h4>Visa refusal analysis</h4><p>Line-by-line review of a refusal letter to identify exactly what went wrong.</p>
          </div>
          <div class="service-card reveal" data-num="03">
            <div class="sc-top">
              <span class="sc-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><path d="M12 19v4"/></svg></span>
              <span class="num">03</span>
            </div>
            <h4>Mock visa interview</h4><p>Practice rounds with the kind of questions officers actually ask.</p>
          </div>
          <div class="service-card reveal" data-num="04">
            <div class="sc-top">
              <span class="sc-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M6 15h4"/></svg></span>
              <span class="num">04</span>
            </div>
            <h4>Financial document review</h4><p>Checking fund visibility, seasoning, and traceability before you submit.</p>
          </div>
          <div class="service-card reveal" data-num="05">
            <div class="sc-top">
              <span class="sc-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg></span>
              <span class="num">05</span>
            </div>
            <h4>Visa documentation &amp; filing</h4><p>End-to-end paperwork assembly and submission support.</p>
          </div>
          <div class="service-card reveal" data-num="06">
            <div class="sc-top">
              <span class="sc-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg></span>
              <span class="num">06</span>
            </div>
            <h4>Visa reapplication support</h4><p>Rebuilding a stronger file after a prior refusal.</p>
          </div>
          <div class="service-card reveal" data-num="07">
            <div class="sc-top">
              <span class="sc-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 22h18"/><path d="M6 18v-7"/><path d="M10 18v-7"/><path d="M14 18v-7"/><path d="M18 18v-7"/><path d="m12 2 9 5H3z"/></svg></span>
              <span class="num">07</span>
            </div>
            <h4>Embassy interview preparation</h4><p>Country-specific coaching on tone, documents, and likely questions.</p>
          </div>
          <div class="service-card reveal" data-num="08">
            <div class="sc-top">
              <span class="sc-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></span>
              <span class="num">08</span>
            </div>
            <h4>Country-specific guidance</h4><p>Rules and expectations that differ meaningfully by destination.</p>
          </div>
        </div>

        <div class="advisor-card reveal">
          <div class="advisor-copy">
            <p class="eyebrow">Talk to a visa advisor</p>
            <h3>Bring us your file — we'll tell you exactly where it stands.</h3>
            <p class="advisor-lead">Share your destination, level and current status with a senior advisor — no sales pitch, just a clear read on your visa file and the fastest route forward.</p>
            <ul class="advisor-points">
              <li>Any destination</li>
              <li>Any university — ranked or not</li>
              <li>One senior advisor per file</li>
            </ul>
            <button type="button" class="btn btn-primary" data-counsellor-open>
              <span>Contact us</span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
            </button>
          </div>
          <div class="advisor-art" aria-hidden="true">
            <span class="ring r1"></span>
            <span class="ring r2"></span>
            <span class="ring r3"></span>
            <span class="orbit-dot2"></span>
            <div class="stamp">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/></svg>
            </div>
            <div class="art-chip c1">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
              96% visa approvals
            </div>
            <div class="art-chip c2">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
              20+ destinations
            </div>
          </div>
        </div>
      </div>
    </section>

    {{-- ================= COUNSELLOR MODAL (WhatsApp / Email) =================
         Kept inside #visa-page so the scoped .vc-* styles apply; it is
         position:fixed, so its place in the flow does not affect layout. --}}
    <div class="vc-modal" id="visaCounsellorModal" role="dialog" aria-modal="true" aria-labelledby="vcTitle" aria-hidden="true" hidden>
      <div class="vc-backdrop" data-vc-close></div>
      <div class="vc-card" role="document">
        <button type="button" class="vc-close" data-vc-close aria-label="Close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>

        <div class="vc-badge" aria-hidden="true">
          <span class="pulse"></span>
          <span class="pulse d2"></span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
        </div>

        <h3 id="vcTitle">Connect with a counsellor</h3>
        <p class="vc-sub">Pick how you'd like to reach us — we usually reply within a few hours.</p>

        <div class="vc-options">
          <a class="vc-opt vc-opt--form o1" href="{{ route('contact') }}" data-vc-go>
            <span class="vc-opt-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/></svg>
            </span>
            <span class="vc-opt-text">
              <strong>Contact us form</strong>
              <small>Fill a quick form — we'll call you back</small>
            </span>
            <span class="vc-opt-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg></span>
          </a>

          <a class="vc-opt vc-opt--wa o2" href="{{ $waLink }}" target="_blank" rel="noopener" data-vc-go>
            <span class="vc-opt-icon">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.39 1.26 4.82L2 22l5.4-1.42a9.9 9.9 0 0 0 4.64 1.18h.01c5.46 0 9.9-4.45 9.9-9.9 0-2.65-1.03-5.13-2.9-7-1.87-1.87-4.35-2.86-7.01-2.86Zm5.8 14.13c-.24.68-1.4 1.3-1.93 1.34-.5.05-.97.24-3.26-.68-2.75-1.08-4.5-3.9-4.63-4.08-.14-.18-1.12-1.49-1.12-2.84 0-1.35.71-2.02.96-2.29.25-.27.55-.34.73-.34h.53c.17 0 .4-.06.62.48.24.58.8 2.02.87 2.17.07.14.12.31.02.5-.09.18-.14.3-.28.46-.14.16-.29.36-.42.48-.14.14-.28.29-.12.57.16.27.72 1.18 1.55 1.92 1.06.94 1.96 1.24 2.24 1.38.27.14.43.12.59-.07.16-.18.68-.79.86-1.06.18-.27.36-.23.61-.14.25.09 1.6.75 1.87.89.27.14.45.2.52.32.07.11.07.66-.17 1.34Z"/></svg>
            </span>
            <span class="vc-opt-text">
              <strong>Chat on WhatsApp</strong>
              <small>{{ $waPhone }} · fastest reply</small>
            </span>
            <span class="vc-opt-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg></span>
          </a>

          <a class="vc-opt vc-opt--mail o3" href="{{ $mailLink }}" data-vc-go>
            <span class="vc-opt-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
            </span>
            <span class="vc-opt-text">
              <strong>Email us</strong>
              <small>{{ $waEmail }}</small>
            </span>
            <span class="vc-opt-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg></span>
          </a>
        </div>

        <p class="vc-foot">Prefer a call? <a href="tel:+{{ $waE164 }}">{{ $waPhone }}</a></p>
      </div>
    </div>

  </div>{{-- /#visa-page --}}
</main>

<script>
(function(){
  /* ---------- shared data ---------- */
  var destCountries = ['USA','UK','Canada','Australia','Germany','Ireland','New Zealand','France','Italy','Netherlands','Poland','Finland','Belgium','Spain','Dubai','Malta'];

  var ckThresholds = {
    USA:{gap:5,backlog:5,ielts:6.5,funds:2500000,moi:false},
    UK:{gap:8,backlog:10,ielts:6.0,funds:2800000,moi:true},
    Canada:{gap:3,backlog:2,ielts:6.0,funds:2000000,moi:false},
    Australia:{gap:5,backlog:8,ielts:6.0,funds:2200000,moi:false},
    Germany:{gap:8,backlog:15,ielts:6.0,funds:1200000,moi:true},
    Ireland:{gap:8,backlog:10,ielts:6.0,funds:1500000,moi:true},
    'New Zealand':{gap:5,backlog:8,ielts:6.0,funds:2000000,moi:false},
    France:{gap:8,backlog:10,ielts:6.0,funds:900000,moi:true},
    Italy:{gap:8,backlog:15,ielts:6.0,funds:900000,moi:true},
    Netherlands:{gap:8,backlog:10,ielts:6.0,funds:1300000,moi:true},
    Poland:{gap:8,backlog:15,ielts:5.5,funds:700000,moi:true},
    Finland:{gap:8,backlog:15,ielts:6.0,funds:700000,moi:true},
    Belgium:{gap:8,backlog:10,ielts:6.0,funds:900000,moi:true},
    Spain:{gap:8,backlog:15,ielts:5.5,funds:800000,moi:true},
    Dubai:{gap:8,backlog:15,ielts:5.5,funds:700000,moi:true},
    Malta:{gap:8,backlog:15,ielts:6.0,funds:700000,moi:true}
  };

  /* ---------- nav / smooth scroll ---------- */
  window.visaJumpTo = function(id){
    var el = document.getElementById(id);
    if (el) el.scrollIntoView({behavior:'smooth', block:'start'});
  };

  /* ---------- build the country dropdown ---------- */
  var sel = document.getElementById('ckCountry');
  if (sel) {
    sel.innerHTML = destCountries.map(function(c){ return '<option value="'+c+'">'+c+'</option>'; }).join('');
  }

  /* ---------- visa eligibility checker ---------- */
  window.runChecker = function(){
    var country = document.getElementById('ckCountry').value;
    var t = ckThresholds[country] || ckThresholds['UK'];
    var ielts = parseFloat(document.getElementById('ckIelts').value);
    var gap = parseFloat(document.getElementById('ckGap').value);
    var backlog = parseFloat(document.getElementById('ckBacklog').value);
    var funds = parseFloat(document.getElementById('ckFunds').value);

    var reasons = [];
    var hard = false, soft = false;

    if (ielts === 0) {
      if (t.moi) { reasons.push("No test score entered — this destination sometimes accepts a Medium-of-Instruction (MOI) waiver instead of IELTS/PTE, but eligibility for that depends on your school's certification and 12th-grade English score."); soft = true; }
      else { reasons.push('This destination does not generally accept an MOI waiver in place of IELTS/PTE — a test score will be needed.'); hard = true; }
    } else if (ielts < t.ielts - 0.5) {
      hard = true; reasons.push('Your band is meaningfully below the '+t.ielts.toFixed(1)+' commonly expected for '+country+'.');
    } else if (ielts < t.ielts) {
      soft = true; reasons.push('Your band is slightly under the '+t.ielts.toFixed(1)+' benchmark for '+country+' — retaking or a strong overall file can offset this.');
    }

    if (gap > t.gap) {
      hard = true; reasons.push('A gap of '+gap+'+ years is longer than '+country+' typically tolerates without a very strong, documented explanation.');
    } else if (gap >= Math.max(t.gap-2,1)) {
      soft = true; reasons.push('Your gap is on the higher side for '+country+' — a clear SOP explanation (work, further study, family) will matter here.');
    }

    if (backlog > t.backlog) {
      hard = true; reasons.push('Your backlog count is above what '+country+' usually accepts, especially at the '+document.getElementById('ckLevel').value+' level.');
    } else if (backlog >= Math.max(t.backlog-3,1)) {
      soft = true; reasons.push('Your backlog count is borderline for '+country+' — clearing a couple before you apply would meaningfully help.');
    }

    if (funds < t.funds*0.7) {
      hard = true; reasons.push('Visible funds look short of what '+country+' typically expects for a file like this — this is the single most common refusal reason worldwide.');
    } else if (funds < t.funds) {
      soft = true; reasons.push('Funds are close but a little under typical expectations for '+country+' — how long the money has been seasoned in the account also matters.');
    }

    if (reasons.length === 0) {
      reasons.push('Your English score, gap, backlogs, and funds all sit within the commonly expected range for '+country+'.');
    }

    var card = document.getElementById('ckResult');
    var level = hard ? 'red' : (soft ? 'yellow' : 'green');
    card.classList.remove('green','yellow','red','show');
    void card.offsetWidth; // restart entrance animation on each run
    card.classList.add(level,'show');

    var titles = {
      green:'Looks eligible on the basics',
      yellow:'Borderline — this can likely be strengthened',
      red:'Significant gaps against typical thresholds'
    };
    var subs = {
      green:'Your profile clears the common visa thresholds for '+country+'. Eligibility is not the same as admission — an SOP and full document review still matter.',
      yellow:'One or more inputs sit near the edge of what '+country+' typically accepts. With the right paperwork and framing, this is often workable.',
      red:'One or more inputs are outside what '+country+' typically accepts. This usually needs either a stronger case built around it, or a look at a different destination.'
    };
    document.getElementById('ckTitle').textContent = titles[level];
    document.getElementById('ckSub').textContent = subs[level];
    document.getElementById('ckReasons').innerHTML = reasons.map(function(r){ return '<li>'+r+'</li>'; }).join('');
    document.getElementById('ckCtaText').textContent = level === 'green'
      ? "You're in a good starting position — a counsellor can help you move fast and lock in the right intake."
      : "Don't stop at this quick read — a counsellor can build a real plan around your specific numbers.";

    card.scrollIntoView({behavior:'smooth', block:'nearest'});
  };

  /* ---------- counsellor modal (WhatsApp / Email popup) ---------- */
  var modal = document.getElementById('visaCounsellorModal');
  var lastFocus = null, closeTimer = null;

  window.openCounsellorModal = function(){
    if (!modal) return;
    if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
    lastFocus = document.activeElement;
    modal.hidden = false;
    modal.setAttribute('aria-hidden','false');
    void modal.offsetWidth;                 // reflow so the transition runs
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    var btn = modal.querySelector('.vc-close');
    if (btn) btn.focus();
  };

  window.closeCounsellorModal = function(){
    if (!modal || modal.hidden) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden','true');
    document.body.style.overflow = '';
    closeTimer = setTimeout(function(){ modal.hidden = true; }, 300);
    if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
  };

  if (modal) {
    modal.querySelectorAll('[data-vc-close]').forEach(function(el){
      el.addEventListener('click', window.closeCounsellorModal);
    });
    modal.querySelectorAll('[data-vc-go]').forEach(function(el){
      el.addEventListener('click', function(){ setTimeout(window.closeCounsellorModal, 60); });
    });
    document.addEventListener('keydown', function(e){
      if (e.key === 'Escape' && !modal.hidden) window.closeCounsellorModal();
    });
  }

  /* ---------- wire the page buttons (data-attribute triggers) ---------- */
  document.querySelectorAll('[data-visa-jump]').forEach(function(btn){
    btn.addEventListener('click', function(){ window.visaJumpTo(btn.getAttribute('data-visa-jump')); });
  });
  document.querySelectorAll('[data-visa-checker]').forEach(function(btn){
    btn.addEventListener('click', window.runChecker);
  });
  document.querySelectorAll('[data-counsellor-open]').forEach(function(btn){
    btn.addEventListener('click', window.openCounsellorModal);
  });
})();
</script>
@endsection
