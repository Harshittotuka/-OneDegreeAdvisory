{{-- AI Visa Mock Interview — a self-contained, browser-based mock-interview tool
     under the Student Hub (/visa-mock-interview). Rebuilt on the shared site
     layout so the navbar + footer match the rest of the site, re-themed to the
     site palette (navy #1a0088 / orange #ff5e32, Cormorant Garamond + Manrope)
     with a richer, student-facing interactive design: animated hero scene,
     3-step tracker, tappable choice pills, scroll-reveal cards, animated
     count-up score ring + confetti, and animated score bars.

     The whole page is scoped under #vmi-page so its generic class names never
     collide with the global styles.css / stripe-nav.css.

     Video mode provides a live camera preview and voice transcription. Answer
     transcripts are assessed by the site's AI assessment service. The
     free round is capped at 10 questions and unlocking more posts a lead to
     /visa-mock-interview/lead (stored as a classified CRM lead with source
     "visa-mock", viewable in CRM → Leads). --}}
@extends('layouts.app')

@push('head')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" defer></script>
<style>
  body.vmi-page-body{ background:#fbf8f0; }

  /* ============================================================
     Scoped under #vmi-page.
     ============================================================ */
  #vmi-page{
    --navy:#1a0088;
    --navy-deep:#100258;
    --navy-soft:#3a2bb0;
    --orange:#ff5e32;
    --orange-soft:#ff8a5c;
    --orange-deep:#d8431d;
    --teal:#0f7a78;
    --cream:#fbf8f0;
    --paper:#fffdf8;
    --white:#ffffff;
    --ink:#101b2a;
    --muted:#5f6b78;
    --line:#e7e2f0;
    --line-soft:#f0ecf7;
    --good:#1f9d6b;
    --warn:#c07d18;
    --bad:#d64545;
    --gold:#ffe6a3;
    --radius:22px;
    --radius-sm:13px;
    --font-head:"Cormorant Garamond",Georgia,serif;
    --font-body:"Manrope",system-ui,-apple-system,"Segoe UI",sans-serif;
    --font-ui:"Jost","Manrope",system-ui,sans-serif;
    --shadow-card:0 22px 60px -38px rgba(16,2,88,.55);
    --shadow-pop:0 30px 70px -30px rgba(16,2,88,.6);

    font-family:var(--font-body);
    color:var(--ink);
    line-height:1.6;
    font-size:16px;
    -webkit-font-smoothing:antialiased;
  }
  #vmi-page *{box-sizing:border-box;}
  #vmi-page ::selection{background:var(--orange-soft);color:var(--navy-deep);}
  #vmi-page h1,#vmi-page h2,#vmi-page h3,#vmi-page h4{margin:0;max-width:none;}
  #vmi-page p{margin:0;}
  #vmi-page a{color:inherit;text-decoration:none;}
  #vmi-page img{display:block;max-width:100%;}
  #vmi-page button{font-family:var(--font-body);cursor:pointer;}
  #vmi-page .hidden{display:none !important;}
  #vmi-page .sr-only{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0;}

  .vmi-main{position:relative;background:
    radial-gradient(1100px 520px at 88% -6%,rgba(255,94,50,.12),transparent 60%),
    radial-gradient(760px 480px at -8% 4%,rgba(26,0,136,.10),transparent 55%),
    var(--cream);}
  /* Background blobs live in their own clipped layer so .vmi-wrap doesn't need a
     z-index — keeping it OUT of a stacking context means the fullscreen interview
     stage (position:fixed) can escape to the root layer and cover the navbar. */
  #vmi-page .vmi-bg{position:absolute;inset:0;overflow:hidden;z-index:0;pointer-events:none;}
  #vmi-page .vmi-wrap{max-width:1140px;margin:0 auto;padding:46px 22px 96px;position:relative;}

  #vmi-page .vmi-eyebrow{display:inline-flex;align-items:center;gap:7px;font-family:var(--font-ui);font-size:12px;
    letter-spacing:.16em;text-transform:uppercase;color:var(--orange-deep);font-weight:600;margin-bottom:10px;}
  #vmi-page .vmi-eyebrow i{width:15px;height:15px;}

  /* ---------- Entrance + motion ---------- */
  @keyframes vmiUp{from{opacity:0;transform:translateY(26px);}to{opacity:1;transform:none;}}
  @keyframes vmiPop{0%{opacity:0;transform:scale(.7);}60%{transform:scale(1.06);}100%{opacity:1;transform:scale(1);}}
  @keyframes vmiFloat{0%,100%{transform:translateY(0) rotate(var(--r,0deg));}50%{transform:translateY(-14px) rotate(var(--r,0deg));}}
  @keyframes vmiFloatB{0%,100%{transform:translateY(0) rotate(var(--r,0deg));}50%{transform:translateY(12px) rotate(var(--r,0deg));}}
  @keyframes vmiSpin{to{transform:rotate(360deg);}}
  @keyframes vmiPulse{0%,100%{opacity:1;}50%{opacity:.25;}}
  @keyframes vmiRipple{0%{box-shadow:0 0 0 0 rgba(255,94,50,.5);}70%{box-shadow:0 0 0 16px rgba(255,94,50,0);}100%{box-shadow:0 0 0 0 rgba(255,94,50,0);}}
  @keyframes vmiBar{0%,100%{transform:scaleY(.28);}50%{transform:scaleY(1);}}
  @keyframes vmiBlob{0%,100%{transform:translate(0,0) scale(1);}33%{transform:translate(24px,-18px) scale(1.08);}66%{transform:translate(-18px,14px) scale(.95);}}
  @keyframes chipFill{from{width:0;}to{width:var(--w,0%);}}
  @keyframes vmiShine{to{transform:translateX(260%);}}

  #vmi-page .vmi-load{animation:vmiUp .8s cubic-bezier(.2,.8,.2,1) both;}

  /* decorative background blobs */
  #vmi-page .vmi-bg::before,#vmi-page .vmi-bg::after{content:"";position:absolute;border-radius:50%;filter:blur(8px);opacity:.5;}
  #vmi-page .vmi-bg::before{width:340px;height:340px;left:-120px;top:120px;background:radial-gradient(circle at 30% 30%,rgba(255,94,50,.28),transparent 70%);animation:vmiBlob 16s ease-in-out infinite;}
  #vmi-page .vmi-bg::after{width:420px;height:420px;right:-160px;top:520px;background:radial-gradient(circle at 60% 40%,rgba(26,0,136,.22),transparent 70%);animation:vmiBlob 22s ease-in-out infinite reverse;}

  /* ---------- Hero ---------- */
  #vmi-page .vmi-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,#22119e 0%,var(--navy) 45%,var(--navy-deep) 100%);
    color:#fff;border-radius:28px;padding:46px 46px 44px;margin-bottom:26px;box-shadow:0 34px 80px -34px rgba(16,2,88,.7);}
  #vmi-page .vmi-hero::after{content:"";position:absolute;right:-90px;top:-90px;width:300px;height:300px;border-radius:50%;
    border:2px solid rgba(255,94,50,.25);}
  #vmi-page .vmi-hero::before{content:"";position:absolute;left:40%;bottom:-140px;width:260px;height:260px;border-radius:50%;
    border:2px solid rgba(255,255,255,.07);}
  #vmi-page .vmi-hero__grid{position:relative;z-index:1;display:grid;grid-template-columns:1.15fr .85fr;gap:34px;align-items:center;}
  #vmi-page .vmi-hero .vmi-eyebrow{color:var(--gold);}
  #vmi-page .vmi-hero h1{font-family:var(--font-head);font-weight:700;font-size:54px;line-height:1.02;letter-spacing:-.01em;margin:0 0 14px;}
  #vmi-page .vmi-hero h1 em{font-style:italic;color:var(--orange-soft);}
  #vmi-page .vmi-hero p.pagesub{color:rgba(255,255,255,.84);font-size:17px;max-width:520px;}
  #vmi-page .vmi-hero__stats{display:flex;flex-wrap:wrap;gap:10px;margin-top:24px;}
  #vmi-page .vmi-stat{display:inline-flex;align-items:center;gap:9px;background:rgba(255,255,255,.1);
    border:1px solid rgba(255,255,255,.16);border-radius:999px;padding:9px 15px;font-size:13px;font-weight:600;backdrop-filter:blur(4px);}
  #vmi-page .vmi-stat i{width:16px;height:16px;color:var(--gold);}
  #vmi-page .vmi-hero__cta{margin-top:26px;}

  /* hero illustrated scene */
  #vmi-page .vmi-hero__art{position:relative;height:300px;}
  #vmi-page .vmi-cam{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:232px;height:172px;border-radius:20px;
    background:linear-gradient(160deg,#2a1a9e,#150a55);border:1px solid rgba(255,255,255,.14);
    box-shadow:0 30px 60px -20px rgba(0,0,0,.55);overflow:hidden;animation:vmiFloat 6s ease-in-out infinite;}
  #vmi-page .vmi-cam__glow{position:absolute;inset:0;background:radial-gradient(120px 90px at 50% 34%,rgba(255,94,50,.35),transparent 70%);}
  #vmi-page .vmi-cam__avatar{position:absolute;left:50%;top:44%;transform:translate(-50%,-50%);width:64px;height:64px;border-radius:50%;
    background:linear-gradient(135deg,#fff,#ffdccf);display:grid;place-items:center;color:var(--navy);box-shadow:0 10px 24px rgba(0,0,0,.35);}
  #vmi-page .vmi-cam__avatar i{width:34px;height:34px;}
  #vmi-page .vmi-cam__rec{position:absolute;top:11px;left:11px;display:flex;align-items:center;gap:6px;font-size:10px;font-weight:800;
    letter-spacing:.06em;background:rgba(0,0,0,.45);border-radius:999px;padding:4px 9px;}
  #vmi-page .vmi-cam__rec span{width:7px;height:7px;border-radius:50%;background:var(--orange);animation:vmiPulse 1.2s infinite;}
  #vmi-page .vmi-cam__wave{position:absolute;left:0;right:0;bottom:14px;display:flex;justify-content:center;align-items:flex-end;gap:4px;height:30px;}
  #vmi-page .vmi-cam__wave i{width:4px;height:100%;border-radius:3px;background:linear-gradient(var(--orange),var(--gold));transform-origin:bottom;
    animation:vmiBar 1s ease-in-out infinite;}
  #vmi-page .vmi-cam__wave i:nth-child(2){animation-delay:.12s;}
  #vmi-page .vmi-cam__wave i:nth-child(3){animation-delay:.24s;}
  #vmi-page .vmi-cam__wave i:nth-child(4){animation-delay:.36s;}
  #vmi-page .vmi-cam__wave i:nth-child(5){animation-delay:.16s;}
  #vmi-page .vmi-cam__wave i:nth-child(6){animation-delay:.28s;}
  #vmi-page .vmi-cam__wave i:nth-child(7){animation-delay:.4s;}
  #vmi-page .vmi-cam__wave i:nth-child(8){animation-delay:.2s;}
  #vmi-page .vmi-badge{position:absolute;display:flex;align-items:center;gap:9px;background:#fff;color:var(--navy-deep);
    border-radius:14px;padding:10px 13px;font-size:12.5px;font-weight:700;box-shadow:0 18px 36px -16px rgba(0,0,0,.5);}
  #vmi-page .vmi-badge i{width:17px;height:17px;color:var(--orange-deep);}
  #vmi-page .vmi-badge--q{left:-14px;top:22px;max-width:190px;--r:-4deg;animation:vmiFloatB 7s ease-in-out infinite;}
  #vmi-page .vmi-badge--stamp{right:-6px;top:34px;--r:6deg;color:var(--good);animation:vmiFloat 8s ease-in-out infinite .4s;}
  #vmi-page .vmi-badge--stamp i{color:var(--good);}
  #vmi-page .vmi-badge--score{right:6px;bottom:14px;--r:-3deg;animation:vmiFloatB 6.5s ease-in-out infinite .2s;}
  #vmi-page .vmi-badge__ring{width:38px;height:38px;border-radius:50%;display:grid;place-items:center;color:#fff;
    background:conic-gradient(var(--orange) 0 82%,rgba(26,0,136,.15) 82% 100%);}
  #vmi-page .vmi-badge__ring b{width:28px;height:28px;border-radius:50%;background:var(--navy);display:grid;place-items:center;font-size:12px;}

  @media(max-width:860px){
    #vmi-page .vmi-hero__grid{grid-template-columns:1fr;}
    #vmi-page .vmi-hero__art{height:250px;margin-top:6px;}
  }
  @media(max-width:640px){
    #vmi-page .vmi-hero{padding:32px 24px;border-radius:22px;}
    #vmi-page .vmi-hero h1{font-size:38px;}
    #vmi-page .vmi-wrap{padding:32px 16px 80px;}
  }

  /* ---------- 3-step tracker ---------- */
  #vmi-page .vmi-steps{display:flex;align-items:center;list-style:none;margin:0 0 26px;padding:14px 18px;background:var(--white);
    border:1px solid var(--line);border-radius:18px;box-shadow:var(--shadow-card);gap:6px;}
  #vmi-page .vmi-steps li{display:flex;align-items:center;gap:11px;flex:1;min-width:0;}
  #vmi-page .vmi-steps li .vmi-step__dot{flex-shrink:0;width:42px;height:42px;border-radius:13px;display:grid;place-items:center;
    background:var(--line-soft);color:var(--muted);transition:all .35s ease;}
  #vmi-page .vmi-steps li .vmi-step__dot i{width:20px;height:20px;}
  #vmi-page .vmi-steps li .vmi-step__txt{min-width:0;}
  #vmi-page .vmi-steps li .vmi-step__txt b{display:block;font-size:13.5px;color:var(--ink);line-height:1.1;font-weight:800;}
  #vmi-page .vmi-steps li .vmi-step__txt small{color:var(--muted);font-size:11.5px;}
  #vmi-page .vmi-steps .vmi-step__bar{flex:0 0 34px;height:3px;border-radius:2px;background:var(--line);position:relative;overflow:hidden;}
  #vmi-page .vmi-steps .vmi-step__bar::after{content:"";position:absolute;inset:0;width:0;background:linear-gradient(90deg,var(--orange),var(--orange-soft));transition:width .5s ease;}
  #vmi-page .vmi-steps li.is-active .vmi-step__dot{background:linear-gradient(135deg,var(--orange),var(--orange-deep));color:#fff;box-shadow:0 10px 22px -8px rgba(255,94,50,.7);transform:translateY(-1px);}
  #vmi-page .vmi-steps li.is-done .vmi-step__dot{background:var(--navy);color:#fff;}
  #vmi-page .vmi-steps li.is-done + .vmi-step__bar::after,
  #vmi-page .vmi-steps li.is-active ~ .vmi-step__bar::after{}
  #vmi-page .vmi-steps .vmi-step__bar.is-filled::after{width:100%;}
  @media(max-width:640px){
    #vmi-page .vmi-steps li .vmi-step__txt small{display:none;}
    #vmi-page .vmi-steps{padding:12px;}
    #vmi-page .vmi-steps .vmi-step__bar{flex-basis:16px;}
  }

  /* ---------- reveal ---------- */
  #vmi-page.anim [data-reveal]{opacity:0;transform:translateY(24px);}
  #vmi-page.anim [data-reveal].in-view{opacity:1;transform:none;transition:opacity .7s cubic-bezier(.2,.8,.2,1),transform .7s cubic-bezier(.2,.8,.2,1);}

  /* ---------- Cards ---------- */
  #vmi-page .card{background:var(--white);border:1px solid var(--line);border-radius:var(--radius);padding:28px;margin-bottom:20px;
    box-shadow:var(--shadow-card);transition:box-shadow .3s ease,transform .3s ease;}
  #vmi-page .card:hover{box-shadow:var(--shadow-pop);}
  #vmi-page .card__head{display:flex;align-items:flex-start;gap:14px;margin-bottom:16px;}
  #vmi-page .card__ic{flex-shrink:0;width:46px;height:46px;border-radius:14px;display:grid;place-items:center;color:#fff;
    background:linear-gradient(135deg,var(--navy),var(--navy-soft));box-shadow:0 12px 26px -12px rgba(26,0,136,.7);}
  #vmi-page .card__ic.is-orange{background:linear-gradient(135deg,var(--orange),var(--orange-deep));box-shadow:0 12px 26px -12px rgba(255,94,50,.7);}
  #vmi-page .card__ic i{width:23px;height:23px;}
  #vmi-page .card h2{font-family:var(--font-head);font-weight:700;font-size:27px;color:var(--navy-deep);line-height:1.05;}
  #vmi-page .card__head p{color:var(--muted);font-size:13.5px;margin-top:3px;}
  #vmi-page .card h3{font-family:var(--font-ui);font-size:12.5px;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);font-weight:700;margin:0 0 12px;}

  /* ---------- Choice pills ---------- */
  #vmi-page .vmi-field-label{display:block;font-size:12px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:var(--navy-deep);margin:0 0 10px;}
  #vmi-page .vmi-pills{display:flex;flex-wrap:wrap;gap:10px;}
  #vmi-page .vmi-pill{position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;
    min-width:82px;padding:12px 16px;border-radius:15px;border:1.6px solid var(--line);background:var(--paper);
    font-family:var(--font-body);font-weight:800;font-size:18px;color:var(--navy-deep);line-height:1;
    transition:transform .16s ease,border-color .16s ease,box-shadow .16s ease,background .16s ease;}
  #vmi-page .vmi-pill small{font-size:10.5px;font-weight:700;letter-spacing:.02em;text-transform:uppercase;color:var(--muted);}
  #vmi-page .vmi-pill:hover{transform:translateY(-2px);border-color:var(--orange-soft);}
  #vmi-page .vmi-pill.is-active{border-color:var(--orange);background:#fff4ef;box-shadow:0 10px 22px -12px rgba(255,94,50,.6);}
  #vmi-page .vmi-pill.is-active small{color:var(--orange-deep);}
  #vmi-page .vmi-pill[data-locked]{opacity:.62;}
  #vmi-page .vmi-pill[data-locked] .vmi-pill__lock{position:absolute;top:6px;right:7px;width:14px;height:14px;color:var(--muted);}
  #vmi-page .vmi-pill[data-locked]:hover{border-color:var(--gold);opacity:.85;}
  #vmi-page .vmi-pill.vmi-pill--mode{flex-direction:row;min-width:0;flex:1;gap:8px;font-size:14px;padding:13px 16px;}
  #vmi-page .vmi-pill.vmi-pill--mode i{width:18px;height:18px;}
  #vmi-page .vmi-context-input{width:100%;border:1.6px solid var(--line);border-radius:14px;background:var(--paper);color:var(--ink);padding:13px 14px;font:600 14px/1.3 var(--font-body);outline:none;transition:border-color .16s ease,box-shadow .16s ease;}
  #vmi-page .vmi-context-input:focus{border-color:var(--navy-soft);box-shadow:0 0 0 4px rgba(58,43,176,.1);}

  /* ---------- Unlock banner ---------- */
  #vmi-page .vmi-unlock{margin-top:22px;position:relative;overflow:hidden;border-radius:16px;padding:18px 20px;
    display:flex;align-items:center;gap:16px;flex-wrap:wrap;color:#fff;
    background:linear-gradient(120deg,var(--navy) 0%,var(--navy-soft) 60%,var(--orange-deep) 130%);
    box-shadow:0 18px 40px -22px rgba(26,0,136,.7);}
  #vmi-page .vmi-unlock::after{content:"";position:absolute;right:-40px;top:-40px;width:150px;height:150px;border-radius:50%;border:2px solid rgba(255,255,255,.14);}
  #vmi-page .vmi-unlock__ic{flex-shrink:0;width:44px;height:44px;border-radius:50%;display:grid;place-items:center;background:rgba(255,255,255,.14);}
  #vmi-page .vmi-unlock__ic i{width:22px;height:22px;color:var(--gold);}
  #vmi-page .vmi-unlock__body{flex:1 1 240px;position:relative;z-index:1;}
  #vmi-page .vmi-unlock__body strong{display:block;font-size:15.5px;margin-bottom:2px;}
  #vmi-page .vmi-unlock__body span{color:rgba(255,255,255,.82);font-size:13px;}
  #vmi-page .vmi-unlock.is-unlocked{background:linear-gradient(120deg,#0f7a78,#1f9d6b);}
  #vmi-page .vmi-unlock.is-unlocked .vmi-unlock__ic i{color:#fff;}

  /* ---------- Buttons ---------- */
  #vmi-page .btn{position:relative;overflow:hidden;display:inline-flex;align-items:center;justify-content:center;gap:9px;border:none;
    border-radius:999px;padding:14px 28px;font-size:15px;font-weight:800;font-family:var(--font-body);cursor:pointer;
    transition:transform .14s ease,box-shadow .16s ease,background .16s ease,color .16s ease;letter-spacing:.2px;}
  #vmi-page .btn::before{content:"";position:absolute;top:0;left:-60%;width:40%;height:100%;transform:skewX(-20deg);
    background:linear-gradient(90deg,transparent,rgba(255,255,255,.28),transparent);opacity:0;}
  #vmi-page .btn:hover::before{opacity:1;animation:vmiShine .8s ease;}
  #vmi-page .btn:hover{transform:translateY(-2px);}
  #vmi-page .btn:active{transform:translateY(0) scale(.98);}
  #vmi-page .btn-primary{background:linear-gradient(135deg,var(--orange),var(--orange-deep));color:#fff;box-shadow:0 14px 28px -12px rgba(255,94,50,.85);}
  #vmi-page .btn-primary:disabled{background:#ded9ea;color:#9d97b8;box-shadow:none;cursor:not-allowed;transform:none;}
  #vmi-page .btn-primary:disabled::before{display:none;}
  #vmi-page .btn-navy{background:linear-gradient(135deg,var(--navy),var(--navy-deep));color:#fff;box-shadow:0 14px 28px -14px rgba(26,0,136,.85);}
  #vmi-page .btn-ghost{background:#fff;color:var(--navy);border:1.6px solid var(--line);}
  #vmi-page .btn-ghost:hover{border-color:var(--navy);}
  #vmi-page .btn-white{background:#fff;color:var(--navy-deep);}
  #vmi-page .btn-danger{background:#fff;color:var(--bad);border:1.6px solid #f3c9c9;}
  #vmi-page .btn-sm{padding:10px 16px;font-size:13px;}
  #vmi-page .btn-lg{padding:17px 34px;font-size:16.5px;}
  #vmi-page .btn-start{animation:vmiRipple 2.2s ease-in-out infinite;}
  #vmi-page .btn-start:disabled{animation:none;}
  #vmi-page .actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:24px;}

  /* ---------- Setup permissions ---------- */
  #vmi-page .perm-row{display:flex;align-items:center;gap:13px;padding:13px 15px;border:1.6px solid var(--line);border-radius:14px;margin-bottom:10px;font-size:14px;background:var(--paper);}
  #vmi-page .perm-dot{position:relative;width:12px;height:12px;border-radius:50%;background:#c9c9d6;flex-shrink:0;}
  #vmi-page .perm-dot.ok{background:var(--good);animation:vmiRipple 2s ease-out infinite;box-shadow:0 0 0 0 rgba(31,157,107,.5);}
  #vmi-page .perm-dot.bad{background:var(--bad);}
  #vmi-page .vmi-preview-frame{border-radius:16px;overflow:hidden;max-width:340px;background:#000;border:3px solid #fff;box-shadow:0 18px 40px -20px rgba(16,2,88,.6);position:relative;}

  /* ---------- Progress (interview) ---------- */
  #vmi-page .progress-wrap{display:flex;align-items:center;gap:14px;margin-bottom:18px;}
  #vmi-page .progress-track{flex:1;height:10px;background:var(--line);border-radius:99px;overflow:hidden;}
  #vmi-page .progress-fill{height:100%;width:0%;border-radius:99px;transition:width .45s cubic-bezier(.2,.8,.2,1);
    background:linear-gradient(90deg,var(--orange),var(--gold));background-size:200% 100%;box-shadow:0 0 14px rgba(255,94,50,.5);}
  #vmi-page .progress-label{font-size:13px;color:var(--navy-deep);white-space:nowrap;font-weight:800;}
  #vmi-page .cat-badge{display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,var(--navy),var(--navy-soft));color:#fff;
    font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;padding:7px 14px;border-radius:99px;margin-bottom:12px;}
  #vmi-page .cat-badge i{width:13px;height:13px;color:var(--gold);}

  /* ---------- Interview layout ---------- */
  #vmi-page .interview-grid{display:grid;grid-template-columns:340px 1fr;gap:20px;align-items:start;}
  @media(max-width:820px){#vmi-page .interview-grid{grid-template-columns:1fr;}}
  #vmi-page .video-panel{background:var(--navy-deep);border-radius:var(--radius);overflow:hidden;position:relative;
    border:1px solid rgba(255,255,255,.08);box-shadow:var(--shadow-card);position:sticky;top:18px;}
  #vmi-page video{width:100%;display:block;aspect-ratio:4/3;object-fit:cover;background:#000;transform:scaleX(-1);}
  #vmi-page .rec-badge{position:absolute;top:12px;left:12px;background:rgba(0,0,0,.55);color:#fff;font-size:12px;padding:6px 12px;border-radius:99px;display:flex;align-items:center;gap:7px;font-weight:800;backdrop-filter:blur(4px);}
  #vmi-page .rec-dot,#vmi-page .rec-dot-sm{width:9px;height:9px;border-radius:50%;background:var(--bad);animation:vmiPulse 1.2s infinite;}
  #vmi-page .rec-dot-sm{display:inline-block;}
  #vmi-page .timer-badge{position:absolute;top:12px;right:12px;background:rgba(0,0,0,.55);color:#fff;font-size:12px;padding:6px 12px;border-radius:99px;font-variant-numeric:tabular-nums;font-weight:800;backdrop-filter:blur(4px);}
  #vmi-page .waveform{width:100%;height:56px;display:block;background:var(--navy-deep);}
  #vmi-page .conf-meter{padding:13px 15px;background:var(--navy-deep);}
  #vmi-page .conf-label{color:rgba(255,255,255,.72);font-size:11px;text-transform:uppercase;letter-spacing:.08em;margin-bottom:7px;font-weight:700;}
  #vmi-page .conf-track{height:7px;background:rgba(255,255,255,.15);border-radius:99px;overflow:hidden;}
  #vmi-page .conf-fill{height:100%;background:linear-gradient(90deg,var(--orange),var(--gold));width:0%;transition:width .15s;border-radius:99px;}

  #vmi-page .qbox{background:var(--white);}
  #vmi-page .qtext{font-family:var(--font-head);font-size:30px;font-weight:700;color:var(--navy-deep);line-height:1.18;margin:4px 0 18px;}
  #vmi-page .transcript-box{background:var(--cream);border:1.6px solid var(--line);border-radius:14px;padding:15px 17px;min-height:96px;font-size:14.5px;color:var(--ink);line-height:1.6;margin-bottom:14px;}
  #vmi-page .transcript-box.empty{color:var(--muted);font-style:italic;}
  #vmi-page textarea.manual-answer{width:100%;min-height:96px;border:1.6px solid var(--line);border-radius:14px;padding:15px 17px;font-size:14.5px;font-family:var(--font-body);color:var(--ink);resize:vertical;background:var(--cream);outline:none;}
  #vmi-page textarea.manual-answer:focus{border-color:var(--orange);box-shadow:0 0 0 3px rgba(255,94,50,.15);}
  #vmi-page .mode-toggle{display:flex;gap:8px;margin-bottom:14px;}
  #vmi-page .mode-btn{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px;border-radius:12px;border:1.6px solid var(--line);background:var(--white);font-size:13px;font-weight:800;color:var(--muted);cursor:pointer;font-family:var(--font-body);transition:all .15s ease;}
  #vmi-page .mode-btn i{width:16px;height:16px;}
  #vmi-page .mode-btn:hover{border-color:var(--navy-soft);color:var(--navy);}
  #vmi-page .mode-btn.active{background:linear-gradient(135deg,var(--navy),var(--navy-soft));color:#fff;border-color:var(--navy);box-shadow:0 10px 20px -12px rgba(26,0,136,.8);}

  /* ---------- Score chips + bars ---------- */
  #vmi-page .score-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin:16px 0;}
  #vmi-page .score-chip{background:var(--cream);border-radius:14px;padding:13px 14px;border:1px solid var(--line);}
  #vmi-page .score-chip .chip-top{display:flex;align-items:baseline;justify-content:space-between;gap:8px;}
  #vmi-page .score-chip .lbl{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.03em;font-weight:700;}
  #vmi-page .score-chip .val{font-size:21px;font-weight:800;color:var(--navy-deep);}
  #vmi-page .score-chip .val.low{color:var(--bad);}
  #vmi-page .score-chip .val.mid{color:var(--warn);}
  #vmi-page .score-chip .val.high{color:var(--good);}
  #vmi-page .chip-bar{margin-top:9px;height:7px;border-radius:99px;background:rgba(16,2,88,.08);overflow:hidden;}
  #vmi-page .chip-bar__fill{display:block;height:100%;border-radius:99px;width:0;animation:chipFill 1s cubic-bezier(.2,.8,.2,1) forwards;background:linear-gradient(90deg,var(--navy-soft),var(--navy));}
  #vmi-page .chip-bar__fill.low{background:linear-gradient(90deg,#f19a9a,var(--bad));}
  #vmi-page .chip-bar__fill.mid{background:linear-gradient(90deg,#f0c877,var(--warn));}
  #vmi-page .chip-bar__fill.high{background:linear-gradient(90deg,#6fd3a9,var(--good));}
  #vmi-page .overall-pill{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--navy),var(--navy-soft));color:#fff;padding:11px 20px;border-radius:99px;font-weight:800;font-size:16px;margin-top:6px;}

  /* ---------- Final report ---------- */
  #vmi-page .report-head{margin-bottom:14px;}
  #vmi-page .report-head h1{font-family:var(--font-head);font-size:42px;color:var(--navy-deep);line-height:1.05;}
  #vmi-page .report-head p{color:var(--muted);font-size:15px;margin-top:6px;}
  #vmi-page .badge-wrap{display:flex;align-items:center;gap:30px;flex-wrap:wrap;padding:8px 0;}
  #vmi-page .badge-wrap .score-ring{margin:0;flex-shrink:0;}
  #vmi-page .badge-wrap__body{flex:1 1 260px;}
  #vmi-page .badge-wrap__body .overall-pill{margin:12px 0 0;}
  @media(max-width:560px){#vmi-page .badge-wrap{gap:14px;}}
  #vmi-page .score-ring{width:190px;height:190px;margin:0 auto 14px;filter:drop-shadow(0 14px 26px rgba(255,94,50,.35));}
  #vmi-page .ring-num{font-family:var(--font-head);font-size:48px;font-weight:700;fill:var(--navy-deep);}
  #vmi-page .ring-sub{font-size:12px;fill:var(--muted);font-family:var(--font-body);font-weight:600;}
  #vmi-page .readiness-tag{display:inline-block;padding:10px 24px;border-radius:99px;font-weight:800;font-size:14px;letter-spacing:.02em;animation:vmiPop .5s cubic-bezier(.2,.9,.3,1.2) both;}
  #vmi-page .rt-excellent{background:#dff6ea;color:var(--good);}
  #vmi-page .rt-good{background:#eaf3ff;color:#2563c7;}
  #vmi-page .rt-moderate{background:#fff3de;color:#b87411;}
  #vmi-page .rt-needs{background:#fdeaea;color:var(--bad);}
  #vmi-page .two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
  @media(max-width:680px){#vmi-page .two-col{grid-template-columns:1fr;}#vmi-page .score-row{grid-template-columns:1fr 1fr;}}
  #vmi-page .report-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin:0 0 16px;}
  #vmi-page .report-stat{min-height:132px;padding:20px;border:1px solid var(--line);border-radius:18px;background:linear-gradient(145deg,#fff,#f7f8fc);box-shadow:var(--shadow-card);}
  #vmi-page .report-stat__icon{width:36px;height:36px;border-radius:11px;display:grid;place-items:center;margin-bottom:14px;background:#eceeff;color:var(--navy);}
  #vmi-page .report-stat__icon i{width:18px;height:18px;}
  #vmi-page .report-stat__label{display:block;color:var(--muted);font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;}
  #vmi-page .report-stat__value{display:block;margin-top:4px;color:var(--navy-deep);font-size:25px;font-weight:850;letter-spacing:-.03em;line-height:1.1;}
  #vmi-page .report-stat__note{display:block;margin-top:7px;color:var(--muted);font-size:11px;line-height:1.45;}
  #vmi-page .report-section-note{color:var(--muted);font-size:12.5px;line-height:1.55;margin:-5px 0 16px;}
  #vmi-page .report-distribution__bar{display:flex;height:14px;border-radius:99px;overflow:hidden;background:var(--line);margin:8px 0 17px;}
  #vmi-page .report-distribution__bar span{display:block;height:100%;}
  #vmi-page .report-distribution__bar .is-strong{background:var(--good);}
  #vmi-page .report-distribution__bar .is-developing{background:var(--warn);}
  #vmi-page .report-distribution__bar .is-focus{background:var(--bad);}
  #vmi-page .report-distribution__legend{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;}
  #vmi-page .report-distribution__legend span{font-size:11px;color:var(--muted);line-height:1.35;}
  #vmi-page .report-distribution__legend b{display:block;margin-top:3px;color:var(--navy-deep);font-size:18px;}
  #vmi-page .report-distribution__legend i{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:5px;}
  #vmi-page .report-distribution__legend .is-strong{background:var(--good);}
  #vmi-page .report-distribution__legend .is-developing{background:var(--warn);}
  #vmi-page .report-distribution__legend .is-focus{background:var(--bad);}
  #vmi-page .report-insights{display:grid;gap:13px;}
  #vmi-page .report-insight{padding:13px 14px;border:1px solid var(--line);border-radius:14px;background:#f8f9fc;}
  #vmi-page .report-insight span{display:block;color:var(--muted);font-size:9.5px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;}
  #vmi-page .report-insight strong{display:block;margin-top:4px;color:var(--navy-deep);font-size:13.5px;line-height:1.45;}
  #vmi-page .report-insight small{display:block;margin-top:4px;color:var(--muted);font-size:11px;line-height:1.4;}
  #vmi-page .report-category-list{display:grid;gap:12px;}
  #vmi-page .report-category{display:grid;grid-template-columns:minmax(180px,1fr) minmax(180px,2fr) 56px;gap:14px;align-items:center;}
  #vmi-page .report-category__name strong{display:block;color:var(--navy-deep);font-size:13px;line-height:1.35;}
  #vmi-page .report-category__name small{color:var(--muted);font-size:10.5px;}
  #vmi-page .report-category__track{height:9px;border-radius:99px;background:#e8eaf2;overflow:hidden;}
  #vmi-page .report-category__track span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,var(--navy-soft),var(--navy));}
  #vmi-page .report-category__score{text-align:right;color:var(--navy-deep);font-size:14px;font-weight:850;}
  #vmi-page .report-answer-review{display:grid;gap:10px;}
  #vmi-page .report-answer{border:1px solid var(--line);border-radius:16px;background:#fff;overflow:hidden;}
  #vmi-page .report-answer summary{list-style:none;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:16px;align-items:center;padding:17px 18px;cursor:pointer;background:#fafbfe;}
  #vmi-page .report-answer summary::-webkit-details-marker{display:none;}
  #vmi-page .report-answer summary:hover{background:#f5f6fb;}
  #vmi-page .report-answer__title small{display:block;color:var(--muted);font-size:9.5px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;}
  #vmi-page .report-answer__title strong{display:block;color:var(--navy-deep);font-size:13.5px;line-height:1.4;}
  #vmi-page .report-answer__score{min-width:56px;text-align:center;padding:7px 9px;border-radius:10px;background:#edf0ff;color:var(--navy);font-size:13px;font-weight:850;}
  #vmi-page .report-answer__body{padding:18px;border-top:1px solid var(--line);}
  #vmi-page .report-answer__response{padding:13px 14px;border-radius:12px;background:#f7f8fc;color:var(--ink);font-size:12.5px;line-height:1.6;margin-bottom:14px;}
  #vmi-page .report-answer__response b{display:block;margin-bottom:4px;color:var(--muted);font-size:9.5px;letter-spacing:.08em;text-transform:uppercase;}
  #vmi-page .report-answer__feedback{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
  #vmi-page .report-answer__feedback div{padding:12px 13px;border-radius:12px;border:1px solid var(--line);font-size:12px;line-height:1.55;}
  #vmi-page .report-answer__feedback b{display:block;margin-bottom:4px;color:var(--navy-deep);font-size:10px;text-transform:uppercase;letter-spacing:.06em;}
  #vmi-page .report-answer__sample{margin-top:12px;padding:13px 14px;border-left:3px solid var(--orange);background:#fff8f5;border-radius:0 12px 12px 0;font-size:12px;line-height:1.6;}
  #vmi-page .report-answer__sample b{display:block;margin-bottom:4px;color:var(--orange-deep);font-size:10px;text-transform:uppercase;letter-spacing:.06em;}
  #vmi-page .report-method{display:flex;gap:13px;align-items:flex-start;padding:16px 18px;border-radius:16px;background:#f4f5fb;border:1px solid var(--line);color:var(--muted);font-size:12px;line-height:1.6;margin-bottom:16px;}
  #vmi-page .report-method i{flex:0 0 auto;width:18px;height:18px;color:var(--navy);margin-top:2px;}
  @media(max-width:760px){#vmi-page .report-stats{grid-template-columns:repeat(2,minmax(0,1fr));}#vmi-page .report-category{grid-template-columns:1fr 52px;}#vmi-page .report-category__track{grid-column:1/-1;grid-row:2;}#vmi-page .report-answer__feedback{grid-template-columns:1fr;}}
  @media(max-width:430px){#vmi-page .report-stats{grid-template-columns:1fr;}#vmi-page .report-distribution__legend{grid-template-columns:1fr;}}
  #vmi-page ul.plain{margin:0;padding-left:20px;}
  #vmi-page ul.plain li{margin-bottom:9px;font-size:14.5px;line-height:1.55;}
  #vmi-page ul.plain li::marker{color:var(--orange);}
  #vmi-page #report-plan p{margin-bottom:12px;}
  #vmi-page .footer-note{text-align:center;color:var(--muted);font-size:12.5px;margin-top:30px;line-height:1.6;}

  #vmi-page .spinner{width:16px;height:16px;border:2.5px solid rgba(26,0,136,.25);border-top-color:var(--navy);border-radius:50%;animation:vmiSpin .7s linear infinite;}
  #vmi-page .loading-row{display:flex;align-items:center;gap:10px;color:var(--muted);font-size:14px;padding:16px 0;font-weight:600;}

  /* ---------- Lead modal ---------- */
  #vmi-page .vmi-modal{position:fixed;inset:0;z-index:100001;display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;transition:opacity .28s ease;font-family:var(--font-body);}
  #vmi-page .vmi-modal[hidden]{display:none;}
  #vmi-page .vmi-modal.is-open{opacity:1;}
  #vmi-page .vmi-modal__backdrop{position:absolute;inset:0;background:rgba(16,2,88,.6);backdrop-filter:blur(4px);}
  #vmi-page .vmi-modal__card{position:relative;z-index:1;width:100%;max-width:530px;background:#fff;border-radius:24px;padding:36px 34px 30px;box-shadow:0 46px 100px -30px rgba(16,2,88,.75);transform:translateY(16px) scale(.97);opacity:0;transition:transform .34s cubic-bezier(.22,1,.36,1),opacity .34s ease;max-height:92vh;overflow-y:auto;}
  #vmi-page .vmi-modal.is-open .vmi-modal__card{transform:none;opacity:1;}
  #vmi-page .vmi-modal__close{position:absolute;top:15px;right:15px;width:36px;height:36px;border-radius:50%;border:1px solid var(--line);background:#fff;color:var(--muted);font-size:21px;line-height:1;display:grid;place-items:center;}
  #vmi-page .vmi-modal__close:hover{color:var(--navy);border-color:var(--navy);}
  #vmi-page .vmi-modal__card h3{font-family:var(--font-head);font-weight:700;font-size:30px;color:var(--navy-deep);line-height:1.08;margin:2px 0 8px;}
  #vmi-page .vmi-modal__card p.vmi-modal__sub{color:var(--muted);font-size:14.5px;margin-bottom:6px;}
  #vmi-page .vmi-modal label{display:block;font-size:12px;font-weight:800;letter-spacing:.03em;text-transform:uppercase;color:var(--navy-deep);margin:14px 0 6px;}
  #vmi-page .vmi-modal input,#vmi-page .vmi-modal select{width:100%;padding:12px 14px;border-radius:12px;border:1.6px solid var(--line);font-size:15px;font-family:var(--font-body);color:var(--ink);background:var(--cream);outline:none;transition:border-color .15s,box-shadow .15s;}
  #vmi-page .vmi-modal input:focus,#vmi-page .vmi-modal select:focus{border-color:var(--orange);box-shadow:0 0 0 3px rgba(255,94,50,.15);}
  #vmi-page .vmi-modal .grid2{display:grid;grid-template-columns:1fr 1fr;gap:0 16px;}
  @media(max-width:520px){#vmi-page .vmi-modal .grid2{grid-template-columns:1fr;}}
  #vmi-page .vmi-lead-error{background:#fdeded;border:1.6px solid #f3c9c9;color:#a12a2a;border-radius:11px;padding:11px 14px;font-size:13.5px;margin-top:14px;line-height:1.5;}
  #vmi-page .vmi-lead-success{text-align:center;padding:8px 4px 4px;}
  #vmi-page .vmi-lead-success .vmi-check{width:78px;height:78px;border-radius:50%;background:#e9f7f0;color:var(--good);display:grid;place-items:center;margin:0 auto 16px;animation:vmiPop .5s cubic-bezier(.2,.9,.3,1.2) both;}
  #vmi-page .vmi-lead-success .vmi-check svg{width:40px;height:40px;}

  /* ============================================================
     FULLSCREEN AI INTERVIEWER STAGE
     ============================================================ */
  @keyframes vmiEq{0%,100%{transform:scaleY(.35);}50%{transform:scaleY(1.6);}}
  @keyframes vmiRingPulse{0%{transform:scale(1);opacity:.55;}100%{transform:scale(1.5);opacity:0;}}
  @keyframes vmiIdleFloat{0%,100%{transform:translateY(0);}50%{transform:translateY(-8px);}}
  @keyframes vmiAur{0%,100%{transform:translate(0,0) scale(1);}50%{transform:translate(40px,30px) scale(1.15);}}
  @keyframes vmiStageIn{from{opacity:0;}to{opacity:1;}}
  @keyframes vmiCaretBlink{0%,100%{opacity:1;}50%{opacity:0;}}

  #vmi-page .vmi-stage{position:fixed;inset:0;z-index:100000;display:flex;flex-direction:column;overflow-y:auto;color:#fff;
    font-family:var(--font-body);
    background:radial-gradient(1200px 720px at 72% -12%,#2a1c8f,transparent 58%),radial-gradient(900px 600px at 0% 110%,#3a1470,transparent 55%),linear-gradient(160deg,#0e0840 0%,#080427 100%);
    animation:vmiStageIn .4s ease both;}
  #vmi-page .vmi-stage.hidden{display:none !important;}
  #vmi-page .vmi-stage__aurora{position:absolute;inset:0;overflow:hidden;pointer-events:none;z-index:0;}
  #vmi-page .vmi-stage__aurora span{position:absolute;border-radius:50%;filter:blur(70px);opacity:.5;}
  #vmi-page .vmi-stage__aurora span:nth-child(1){width:420px;height:420px;left:-80px;top:-60px;background:rgba(255,94,50,.5);animation:vmiAur 18s ease-in-out infinite;}
  #vmi-page .vmi-stage__aurora span:nth-child(2){width:460px;height:460px;right:-100px;top:20%;background:rgba(64,42,220,.55);animation:vmiAur 22s ease-in-out infinite reverse;}
  #vmi-page .vmi-stage__aurora span:nth-child(3){width:380px;height:380px;left:35%;bottom:-120px;background:rgba(15,122,120,.45);animation:vmiAur 26s ease-in-out infinite;}

  #vmi-page .vmi-stage__top{position:relative;z-index:2;display:flex;align-items:center;gap:20px;padding:16px 26px;
    border-bottom:1px solid rgba(255,255,255,.08);background:rgba(8,4,39,.4);backdrop-filter:blur(8px);}
  #vmi-page .vmi-stage__brand{display:flex;align-items:center;gap:10px;font-family:var(--font-ui);font-weight:700;font-size:14px;letter-spacing:.02em;white-space:nowrap;}
  #vmi-page .vmi-stage__logo{width:34px;height:34px;border-radius:10px;display:grid;place-items:center;background:linear-gradient(135deg,var(--orange),var(--orange-deep));box-shadow:0 8px 18px -8px rgba(255,94,50,.8);}
  #vmi-page .vmi-stage__logo i{width:19px;height:19px;}
  #vmi-page .vmi-stage__progress{flex:1;min-width:0;display:flex;flex-direction:column;gap:6px;max-width:520px;margin:0 auto;}
  #vmi-page .vmi-stage__progress .progress-track{height:8px;background:rgba(255,255,255,.14);border-radius:99px;overflow:hidden;}
  #vmi-page .vmi-stage__progress .progress-label{color:rgba(255,255,255,.8);font-size:12px;font-weight:700;text-align:center;}
  #vmi-page .vmi-stage__tools{display:flex;align-items:center;gap:8px;}
  #vmi-page .vmi-icbtn{display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.16);
    color:#fff;border-radius:10px;padding:9px 12px;font-family:var(--font-body);font-weight:700;font-size:13px;cursor:pointer;transition:background .15s,transform .12s;}
  #vmi-page .vmi-icbtn:hover{background:rgba(255,255,255,.2);transform:translateY(-1px);}
  #vmi-page .vmi-icbtn i{width:17px;height:17px;}
  #vmi-page .vmi-icbtn.is-off{opacity:.5;}
  #vmi-page .vmi-icbtn--exit:hover{background:var(--bad);border-color:var(--bad);}

  #vmi-page .vmi-stage__main{position:relative;z-index:1;flex:1;display:grid;grid-template-columns:1fr 1fr;gap:40px;
    align-items:center;max-width:1160px;width:100%;margin:0 auto;padding:34px 34px 120px;}
  @media(max-width:900px){#vmi-page .vmi-stage__main{grid-template-columns:1fr;gap:20px;padding:22px 18px 150px;align-items:start;}}

  #vmi-page .vmi-ai{text-align:center;}
  #vmi-page .vmi-ai__avatar{position:relative;width:172px;height:172px;margin:0 auto 20px;animation:vmiIdleFloat 5s ease-in-out infinite;}
  #vmi-page .vmi-ai__avatar .ring{position:absolute;border-radius:50%;border:2px solid rgba(255,255,255,.16);inset:0;}
  #vmi-page .vmi-ai__avatar .r2{inset:-20px;border-color:rgba(255,255,255,.1);}
  #vmi-page .vmi-ai__avatar .r3{inset:-40px;border-color:rgba(255,255,255,.06);}
  #vmi-page .vmi-ai__avatar .core{position:absolute;inset:24px;border-radius:50%;display:grid;place-items:center;
    background:radial-gradient(circle at 34% 28%,#ff9f78,#ff5e32 52%,#d8431d);
    box-shadow:0 0 55px rgba(255,94,50,.55),inset 0 8px 22px rgba(255,255,255,.35);transition:background .4s ease,box-shadow .4s ease;}
  #vmi-page .vmi-ai__avatar .eq{display:flex;align-items:center;gap:6px;height:46px;}
  #vmi-page .vmi-ai__avatar .eq i{width:7px;height:20px;background:#fff;border-radius:4px;transform:scaleY(.5);transform-origin:center;transition:transform .2s ease;}
  #vmi-page .vmi-ai__avatar.is-speaking .eq i{animation:vmiEq .6s ease-in-out infinite;}
  #vmi-page .vmi-ai__avatar.is-speaking .eq i:nth-child(2){animation-delay:.12s;}
  #vmi-page .vmi-ai__avatar.is-speaking .eq i:nth-child(3){animation-delay:.24s;}
  #vmi-page .vmi-ai__avatar.is-speaking .eq i:nth-child(4){animation-delay:.16s;}
  #vmi-page .vmi-ai__avatar.is-speaking .eq i:nth-child(5){animation-delay:.28s;}
  #vmi-page .vmi-ai__avatar.is-speaking .ring{animation:vmiRingPulse 1.7s ease-out infinite;}
  #vmi-page .vmi-ai__avatar.is-speaking .r2{animation-delay:.3s;}
  #vmi-page .vmi-ai__avatar.is-speaking .r3{animation-delay:.6s;}
  #vmi-page .vmi-ai__avatar.is-listening .core{background:radial-gradient(circle at 34% 28%,#7ff0dd,#0f7a78 55%,#0b5859);box-shadow:0 0 55px rgba(15,122,120,.6),inset 0 8px 22px rgba(255,255,255,.3);}
  #vmi-page .vmi-ai__avatar.is-listening .eq i{animation:vmiEq .5s ease-in-out infinite;}
  #vmi-page .vmi-ai__status{font-family:var(--font-ui);font-size:13px;letter-spacing:.04em;text-transform:uppercase;color:var(--gold);font-weight:600;margin-bottom:14px;min-height:18px;}
  #vmi-page .vmi-ai .cat-badge{background:rgba(255,255,255,.12);color:#fff;}
  #vmi-page .vmi-ai .cat-badge i{color:var(--gold);}
  #vmi-page .vmi-ai__bubble{position:relative;margin:16px auto 0;max-width:520px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.14);
    border-radius:18px;padding:22px 26px;backdrop-filter:blur(6px);box-shadow:0 20px 50px -30px rgba(0,0,0,.7);}
  #vmi-page .vmi-ai__bubble::before{content:"";position:absolute;top:-9px;left:50%;transform:translateX(-50%) rotate(45deg);width:18px;height:18px;background:rgba(255,255,255,.07);border-left:1px solid rgba(255,255,255,.14);border-top:1px solid rgba(255,255,255,.14);}
  #vmi-page .vmi-ai__bubble .qtext{font-family:var(--font-head);font-weight:700;font-size:26px;line-height:1.22;color:#fff;margin:0;min-height:32px;}
  #vmi-page .vmi-ai__avatar.is-speaking ~ .vmi-ai__bubble .qtext::after{content:"|";margin-left:2px;color:var(--orange-soft);animation:vmiCaretBlink 1s step-end infinite;}
  @media(max-width:900px){
    #vmi-page .vmi-ai__avatar{width:120px;height:120px;margin-bottom:14px;}
    #vmi-page .vmi-ai__bubble .qtext{font-size:21px;}
  }

  #vmi-page .vmi-answer .card{margin-bottom:0;box-shadow:0 30px 70px -30px rgba(0,0,0,.6);}
  #vmi-page .vmi-answer .qbox{background:var(--white);}

  #vmi-page .vmi-cam-pip{position:fixed;left:24px;bottom:22px;z-index:3;width:210px;border-radius:16px;overflow:hidden;
    background:#000;border:2px solid rgba(255,255,255,.2);box-shadow:0 24px 50px -20px rgba(0,0,0,.8);}
  #vmi-page .vmi-cam-pip video{aspect-ratio:4/3;}
  #vmi-page .vmi-cam-pip .waveform{height:44px;}
  #vmi-page .vmi-cam-pip .conf-meter{padding:9px 12px;}
  #vmi-page .vmi-cam-pip__you{position:absolute;left:10px;bottom:72px;background:rgba(0,0,0,.55);color:#fff;font-size:11px;font-weight:700;padding:3px 9px;border-radius:99px;backdrop-filter:blur(4px);}
  @media(max-width:900px){#vmi-page .vmi-cam-pip{width:132px;left:14px;bottom:14px;}#vmi-page .vmi-cam-pip__you{bottom:64px;}}

  /* ---------- Analysing transition ---------- */
  #vmi-page .vmi-analyze{position:fixed;inset:0;z-index:100003;display:flex;align-items:center;justify-content:center;padding:24px;color:#fff;
    font-family:var(--font-body);background:radial-gradient(1200px 720px at 72% -12%,#2a1c8f,transparent 58%),radial-gradient(900px 600px at 0% 110%,#3a1470,transparent 55%),linear-gradient(160deg,#0e0840 0%,#080427 100%);animation:vmiStageIn .35s ease both;}
  #vmi-page .vmi-analyze.hidden{display:none !important;}
  #vmi-page .vmi-analyze__inner{position:relative;z-index:1;text-align:center;max-width:470px;width:100%;}
  #vmi-page .vmi-analyze__orb{position:relative;width:132px;height:132px;margin:0 auto 26px;animation:vmiIdleFloat 4s ease-in-out infinite;}
  #vmi-page .vmi-analyze__spin{position:absolute;inset:0;border-radius:50%;background:conic-gradient(var(--orange) 0 28%,rgba(255,255,255,.12) 0 100%);
    -webkit-mask:radial-gradient(farthest-side,transparent calc(100% - 8px),#000 calc(100% - 8px));mask:radial-gradient(farthest-side,transparent calc(100% - 8px),#000 calc(100% - 8px));animation:vmiSpin 1s linear infinite;}
  #vmi-page .vmi-analyze__core{position:absolute;inset:18px;border-radius:50%;display:grid;place-items:center;color:#fff;
    background:radial-gradient(circle at 34% 28%,#ff9f78,#ff5e32 55%,#d8431d);box-shadow:0 0 46px rgba(255,94,50,.55),inset 0 6px 18px rgba(255,255,255,.3);}
  #vmi-page .vmi-analyze__core i{width:40px;height:40px;}
  #vmi-page .vmi-analyze__inner h2{font-family:var(--font-head);font-weight:700;font-size:34px;margin:0 0 8px;}
  #vmi-page .vmi-analyze__inner > p{color:rgba(255,255,255,.72);font-size:14.5px;margin:0 auto 24px;max-width:380px;}
  #vmi-page .vmi-analyze__time{display:inline-flex;align-items:center;justify-content:center;min-height:34px;margin:-8px auto 22px;padding:7px 14px;border-radius:999px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.16);color:rgba(255,255,255,.88);font-size:12.5px;font-weight:750;letter-spacing:.01em;}
  #vmi-page .vmi-analyze__steps{list-style:none;margin:0 auto 22px;padding:0;text-align:left;max-width:340px;display:flex;flex-direction:column;gap:11px;}
  #vmi-page .vmi-analyze__steps li{display:flex;align-items:center;gap:12px;font-size:14px;font-weight:600;color:rgba(255,255,255,.45);transition:color .3s ease;}
  #vmi-page .vmi-analyze__steps li .tick{flex-shrink:0;width:26px;height:26px;border-radius:50%;display:grid;place-items:center;
    background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.16);transition:all .3s ease;}
  #vmi-page .vmi-analyze__steps li .tick i{width:15px;height:15px;opacity:0;transform:scale(.4);transition:all .3s ease;}
  #vmi-page .vmi-analyze__steps li.is-active{color:#fff;}
  #vmi-page .vmi-analyze__steps li.is-active .tick{background:rgba(255,94,50,.25);border-color:var(--orange);animation:vmiRingPulse 1.2s ease-out infinite;}
  #vmi-page .vmi-analyze__steps li.is-done{color:rgba(255,255,255,.9);}
  #vmi-page .vmi-analyze__steps li.is-done .tick{background:var(--good);border-color:var(--good);animation:none;}
  #vmi-page .vmi-analyze__steps li.is-done .tick i{opacity:1;transform:scale(1);}
  #vmi-page .vmi-analyze__bar{height:6px;border-radius:99px;background:rgba(255,255,255,.14);overflow:hidden;max-width:340px;margin:0 auto;}
  #vmi-page .vmi-analyze__bar span{display:block;height:100%;width:0;border-radius:99px;background:linear-gradient(90deg,var(--orange),var(--gold));}

  /* ---------- Stage answer-card polish ---------- */
  #vmi-page .vmi-answer .qbox__head{display:flex;align-items:center;gap:10px;margin-bottom:14px;}
  #vmi-page .vmi-answer .qbox__head .ic{width:34px;height:34px;border-radius:10px;flex-shrink:0;display:grid;place-items:center;color:#fff;background:linear-gradient(135deg,var(--navy),var(--navy-soft));}
  #vmi-page .vmi-answer .qbox__head .ic i{width:17px;height:17px;}
  #vmi-page .vmi-answer .qbox__head b{font-size:15px;color:var(--navy-deep);display:block;line-height:1.1;}
  #vmi-page .vmi-answer .qbox__head small{color:var(--muted);font-size:12px;}
  #vmi-page .vmi-ai__hint{margin-top:16px;font-size:13px;color:rgba(255,255,255,.6);display:flex;align-items:center;justify-content:center;gap:7px;}
  #vmi-page .vmi-ai__hint i{width:14px;height:14px;color:var(--gold);}
  #vmi-page .transcript-box.is-live{border-color:var(--orange);box-shadow:0 0 0 3px rgba(255,94,50,.12);}

  html.vmi-lock{overflow:hidden !important;scrollbar-gutter:stable;}

  /* ============================================================
     2026 redesign layer — calm, cinematic and product-led.
     ============================================================ */
  body.vmi-page-body{background:#f4f6fb;}
  #vmi-page{
    --navy:#2323a9;
    --navy-deep:#10123b;
    --navy-soft:#5557e8;
    --orange:#ff6534;
    --orange-soft:#ff9772;
    --orange-deep:#e5481b;
    --cream:#f4f6fb;
    --paper:#f9faff;
    --ink:#15172b;
    --muted:#687086;
    --line:#e4e7f0;
    --line-soft:#eef0f6;
    --radius:24px;
    --radius-sm:14px;
    --font-head:"Manrope","Jost",system-ui,sans-serif;
    --shadow-card:0 18px 50px -34px rgba(18,22,61,.3);
    --shadow-pop:0 28px 70px -36px rgba(18,22,61,.4);
  }
  .vmi-main{background:radial-gradient(900px 520px at 50% -180px,rgba(85,87,232,.11),transparent 72%),#f4f6fb;}
  #vmi-page .vmi-bg{background-image:linear-gradient(rgba(20,24,64,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(20,24,64,.035) 1px,transparent 1px);background-size:42px 42px;mask-image:linear-gradient(to bottom,#000,transparent 72%);}
  #vmi-page .vmi-bg::before{width:520px;height:520px;left:-250px;top:100px;filter:blur(16px);opacity:.35;background:radial-gradient(circle,rgba(85,87,232,.24),transparent 68%);}
  #vmi-page .vmi-bg::after{width:560px;height:560px;right:-260px;top:600px;filter:blur(20px);opacity:.3;background:radial-gradient(circle,rgba(255,101,52,.2),transparent 68%);}
  #vmi-page .vmi-wrap{max-width:1240px;padding:34px 24px 100px;}

  @keyframes vmiConsoleFloat{0%,100%{transform:translateY(0) rotateX(0deg) rotateY(0deg)}50%{transform:translateY(-10px) rotateX(1.4deg) rotateY(-1.4deg)}}
  @keyframes vmiOrbit{to{transform:rotate(360deg)}}
  @keyframes vmiWaveModern{0%,100%{transform:scaleY(.28);opacity:.5}50%{transform:scaleY(1);opacity:1}}
  @keyframes vmiSignalIn{from{opacity:0;transform:translateY(14px) scale(.94)}to{opacity:1;transform:translateY(0) scale(1)}}
  @keyframes vmiDotPulse{0%,100%{box-shadow:0 0 0 0 rgba(87,255,172,.42)}50%{box-shadow:0 0 0 7px rgba(87,255,172,0)}}
  @keyframes vmiSoftGlow{0%,100%{opacity:.55;transform:scale(.96)}50%{opacity:.9;transform:scale(1.04)}}
  @keyframes vmiAgentBars{0%,100%{transform:scaleY(.28);opacity:.55}50%{transform:scaleY(1);opacity:1}}
  @keyframes vmiAgentHalo{0%,100%{box-shadow:0 0 0 0 rgba(100,105,255,.22)}50%{box-shadow:0 0 0 8px rgba(100,105,255,0)}}

  #vmi-page .vmi-hero{min-height:570px;display:flex;align-items:center;padding:68px 64px;margin-bottom:20px;border-radius:34px;background:radial-gradient(620px 430px at 88% 18%,rgba(93,96,255,.33),transparent 65%),radial-gradient(480px 360px at 10% 105%,rgba(255,101,52,.18),transparent 65%),linear-gradient(145deg,#151846 0%,#0b0d26 68%,#111335 100%);box-shadow:0 36px 100px -50px rgba(7,9,32,.82);isolation:isolate;}
  #vmi-page .vmi-hero::before{left:auto;right:31%;bottom:-240px;width:520px;height:520px;border:1px solid rgba(255,255,255,.07);box-shadow:0 0 0 70px rgba(255,255,255,.018),0 0 0 140px rgba(255,255,255,.012);}
  #vmi-page .vmi-hero::after{right:-80px;top:-190px;width:410px;height:410px;border:1px solid rgba(255,255,255,.09);}
  #vmi-page .vmi-hero__grid{width:100%;grid-template-columns:minmax(0,1fr) minmax(390px,.86fr);gap:60px;}
  #vmi-page .vmi-hero__copy{position:relative;z-index:2;}
  #vmi-page .vmi-hero .vmi-eyebrow{margin-bottom:22px;padding:8px 13px;border:1px solid rgba(255,255,255,.13);border-radius:999px;background:rgba(255,255,255,.055);color:rgba(255,255,255,.78);font-size:10px;letter-spacing:.14em;backdrop-filter:blur(12px);}
  #vmi-page .vmi-live-dot{width:7px;height:7px;border-radius:50%;background:#57ffac;animation:vmiDotPulse 2s ease-in-out infinite;}
  #vmi-page .vmi-hero h1{max-width:670px;font-family:var(--font-head);font-size:clamp(48px,5vw,72px);font-weight:800;line-height:1.01;letter-spacing:-.055em;margin-bottom:22px;}
  #vmi-page .vmi-hero h1 em{font-style:normal;color:transparent;background:linear-gradient(100deg,#ffb194,#ff6534 80%);background-clip:text;-webkit-background-clip:text;}
  #vmi-page .vmi-hero p.pagesub{max-width:620px;color:rgba(239,241,255,.7);font-size:16px;line-height:1.8;}
  #vmi-page .vmi-hero__cta{display:flex;align-items:center;gap:18px;flex-wrap:wrap;margin-top:30px;}
  #vmi-page .vmi-hero__microcopy{display:inline-flex;align-items:center;gap:7px;color:rgba(239,241,255,.6);font-size:12px;font-weight:650;}
  #vmi-page .vmi-hero__microcopy i{width:16px;height:16px;color:#75e8b6;}
  #vmi-page .vmi-hero__stats{gap:0;margin-top:38px;border-top:1px solid rgba(255,255,255,.1);max-width:570px;padding-top:22px;}
  #vmi-page .vmi-stat{display:flex;align-items:flex-start;flex-direction:column;gap:2px;min-width:0;flex:1;padding:0 20px;background:none;border:0;border-left:1px solid rgba(255,255,255,.1);border-radius:0;backdrop-filter:none;}
  #vmi-page .vmi-stat:first-child{padding-left:0;border-left:0;}
  #vmi-page .vmi-stat strong{font-size:22px;line-height:1;color:#fff;letter-spacing:-.04em;}
  #vmi-page .vmi-stat small{font-size:10px;color:rgba(239,241,255,.5);text-transform:uppercase;letter-spacing:.08em;white-space:nowrap;}

  #vmi-page .vmi-hero__art{height:430px;perspective:1100px;}
  #vmi-page .vmi-console{position:absolute;inset:22px 8px 24px 18px;display:flex;flex-direction:column;border:1px solid rgba(255,255,255,.14);border-radius:25px;background:linear-gradient(160deg,rgba(35,39,93,.94),rgba(13,15,44,.92));box-shadow:0 42px 80px -34px rgba(0,0,0,.8),inset 0 1px rgba(255,255,255,.08);overflow:hidden;backdrop-filter:blur(22px);animation:vmiConsoleFloat 7s ease-in-out infinite;transform-style:preserve-3d;}
  #vmi-page .vmi-console::before{content:"";position:absolute;width:250px;height:250px;left:50%;top:42%;transform:translate(-50%,-50%);border-radius:50%;background:rgba(88,91,255,.13);filter:blur(45px);animation:vmiSoftGlow 5s ease-in-out infinite;}
  #vmi-page .vmi-console__top{position:relative;z-index:1;height:47px;display:flex;align-items:center;justify-content:space-between;padding:0 16px;border-bottom:1px solid rgba(255,255,255,.085);color:rgba(255,255,255,.58);font-size:9px;letter-spacing:.12em;}
  #vmi-page .vmi-console__top>span{display:flex;gap:5px;}
  #vmi-page .vmi-console__top>span i{width:6px;height:6px;border-radius:50%;background:rgba(255,255,255,.22);}
  #vmi-page .vmi-console__top>span i:first-child{background:#ff7658;}
  #vmi-page .vmi-console__top b{font-size:9px;font-weight:800;color:#70f0b7;}
  #vmi-page .vmi-console__top small{font-size:9px;}
  #vmi-page .vmi-console__body{position:relative;z-index:1;flex:1;display:flex;align-items:center;flex-direction:column;padding:30px 28px 22px;text-align:center;}
  #vmi-page .vmi-officer{position:relative;width:112px;height:112px;margin-bottom:15px;display:grid;place-items:center;}
  #vmi-page .vmi-officer__orbit{position:absolute;inset:0;border:1px solid rgba(142,145,255,.45);border-radius:50%;animation:vmiOrbit 12s linear infinite;}
  #vmi-page .vmi-officer__orbit::before{content:"";position:absolute;left:13px;top:3px;width:9px;height:9px;border-radius:50%;background:#ff825c;box-shadow:0 0 18px #ff6534;}
  #vmi-page .vmi-officer__core{width:74px;height:74px;border-radius:24px;display:grid;place-items:center;color:#fff;background:linear-gradient(145deg,#686bff,#3336c6);box-shadow:0 18px 42px -14px rgba(82,85,240,.9),inset 0 1px rgba(255,255,255,.3);transform:rotate(8deg);}
  #vmi-page .vmi-officer__core i{width:30px;height:30px;transform:rotate(-8deg);}
  #vmi-page .vmi-console__label{font-size:9px;letter-spacing:.16em;font-weight:800;color:#9da0ff;margin-bottom:8px;}
  #vmi-page .vmi-console__body p{max-width:330px;color:#fff;font-family:var(--font-head);font-size:19px;font-weight:750;line-height:1.4;letter-spacing:-.025em;}
  #vmi-page .vmi-console__wave{height:27px;display:flex;align-items:center;gap:4px;margin-top:auto;}
  #vmi-page .vmi-console__wave i{width:3px;height:100%;border-radius:99px;background:linear-gradient(#8d8fff,#ff7c55);transform-origin:center;animation:vmiWaveModern 1s ease-in-out infinite;}
  #vmi-page .vmi-console__wave i:nth-child(2),#vmi-page .vmi-console__wave i:nth-child(8){animation-delay:.12s}#vmi-page .vmi-console__wave i:nth-child(3),#vmi-page .vmi-console__wave i:nth-child(7){animation-delay:.24s}#vmi-page .vmi-console__wave i:nth-child(4),#vmi-page .vmi-console__wave i:nth-child(6){animation-delay:.36s}#vmi-page .vmi-console__wave i:nth-child(5){animation-delay:.48s}
  #vmi-page .vmi-console__footer{position:relative;z-index:1;display:flex;justify-content:center;gap:22px;padding:12px 16px;border-top:1px solid rgba(255,255,255,.085);color:rgba(255,255,255,.62);font-size:9px;text-transform:uppercase;letter-spacing:.07em;}
  #vmi-page .vmi-console__footer span{display:flex;align-items:center;gap:5px;}#vmi-page .vmi-console__footer i{width:12px;height:12px;color:#72e7b4;}
  #vmi-page .vmi-signal-card{position:absolute;z-index:3;display:flex;align-items:center;background:rgba(255,255,255,.96);color:var(--ink);border:1px solid rgba(255,255,255,.7);box-shadow:0 24px 55px -23px rgba(0,0,0,.65);backdrop-filter:blur(16px);animation:vmiSignalIn .7s .7s both;}
  #vmi-page .vmi-signal-card--score{right:-19px;top:58px;width:135px;padding:13px 14px;border-radius:17px;flex-wrap:wrap;gap:0;}
  #vmi-page .vmi-signal-card--score>span{width:100%;font-size:9px;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);font-weight:800;}
  #vmi-page .vmi-signal-card--score>b{font-size:25px;line-height:1.1;letter-spacing:-.05em;color:var(--navy-deep);}
  #vmi-page .vmi-signal-card--score>b small{font-size:10px;color:var(--muted);letter-spacing:0;}
  #vmi-page .vmi-signal-card--score>i{width:17px;height:17px;margin-left:auto;color:var(--good);}
  #vmi-page .vmi-signal-card--feedback{left:-24px;bottom:48px;gap:10px;padding:12px 15px;border-radius:17px;animation-delay:1s;}
  #vmi-page .vmi-signal-card--feedback>i{width:29px;height:29px;padding:7px;border-radius:10px;color:#fff;background:var(--good);}
  #vmi-page .vmi-signal-card--feedback b,#vmi-page .vmi-signal-card--feedback small{display:block;line-height:1.3;}#vmi-page .vmi-signal-card--feedback b{font-size:11px;}#vmi-page .vmi-signal-card--feedback small{font-size:9px;color:var(--muted);}

  #vmi-page .btn{border-radius:13px;padding:13px 22px;font-size:13px;box-shadow:none;}
  #vmi-page .btn-lg{padding:16px 25px;font-size:14px;}
  #vmi-page .btn-primary{background:linear-gradient(135deg,#ff7447,#f04c1f);box-shadow:0 16px 32px -16px rgba(255,101,52,.85);}
  #vmi-page .btn-navy{background:linear-gradient(135deg,#3437c7,#24269c);box-shadow:0 16px 32px -18px rgba(35,38,169,.8);}
  #vmi-page .btn-ghost{background:rgba(255,255,255,.75);}
  #vmi-page .btn-start{animation:none;}

  #vmi-page .vmi-steps{position:relative;margin:0 0 34px;padding:12px 16px;border-color:rgba(220,223,235,.9);border-radius:19px;background:rgba(255,255,255,.78);box-shadow:0 15px 45px -36px rgba(20,24,64,.4);backdrop-filter:blur(14px);}
  #vmi-page .vmi-steps li .vmi-step__dot{width:38px;height:38px;border-radius:12px;background:#eff1f7;}
  #vmi-page .vmi-steps li .vmi-step__txt b{font-size:12px;letter-spacing:-.01em;}#vmi-page .vmi-steps li .vmi-step__txt small{font-size:10px;}
  #vmi-page .vmi-steps li.is-active .vmi-step__dot{background:var(--navy-deep);box-shadow:0 10px 24px -12px rgba(16,18,59,.65);}

  #vmi-page .vmi-setup-intro{max-width:720px;margin:0 auto 30px;text-align:center;}
  #vmi-page .vmi-setup-intro .vmi-eyebrow{margin-bottom:10px;color:var(--navy);}
  #vmi-page .vmi-setup-intro h2{font-family:var(--font-head);font-size:clamp(31px,4vw,46px);line-height:1.12;letter-spacing:-.045em;color:var(--navy-deep);}
  #vmi-page .vmi-setup-intro p{max-width:600px;margin:10px auto 0;color:var(--muted);font-size:14px;}
  #vmi-page .vmi-setup-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,.85fr);gap:20px;align-items:stretch;transition:grid-template-columns .58s cubic-bezier(.22,1,.36,1),gap .48s cubic-bezier(.22,1,.36,1);}
  #vmi-page .vmi-setup-grid>.card{height:100%;min-width:0;margin:0;}
  #vmi-page .vmi-options-card{transition:padding .5s cubic-bezier(.22,1,.36,1),background .4s ease,box-shadow .4s ease;}
  #vmi-page .vmi-setup-grid.is-text-mode{grid-template-columns:minmax(0,1fr) minmax(0,0fr);gap:0;}
  #vmi-page .vmi-setup-grid.is-text-mode .vmi-options-card{padding-inline:clamp(28px,5vw,58px);background:linear-gradient(135deg,rgba(255,255,255,.97),rgba(246,247,255,.94));box-shadow:0 26px 70px -44px rgba(20,24,64,.55);}
  #vmi-page .vmi-setup-grid.is-text-mode .vmi-options-card #vmi-count-pills{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));}
  #vmi-page .vmi-setup-grid.is-text-mode .vmi-options-card #vmi-mode-pills{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));}
  #vmi-page .card{border-color:rgba(220,223,235,.92);border-radius:24px;padding:28px;background:rgba(255,255,255,.88);box-shadow:0 18px 55px -42px rgba(20,24,64,.45);backdrop-filter:blur(12px);}
  #vmi-page .card:hover{box-shadow:0 24px 64px -40px rgba(20,24,64,.42);transform:translateY(-2px);}
  #vmi-page .card__ic{width:42px;height:42px;border-radius:13px;background:var(--navy-deep);box-shadow:none;}
  #vmi-page .card__ic.is-orange{background:#fff0eb;color:var(--orange);box-shadow:none;}
  #vmi-page .card h2{font-family:var(--font-head);font-size:23px;font-weight:800;letter-spacing:-.035em;color:var(--navy-deep);}
  #vmi-page .vmi-field-label{font-size:10px;letter-spacing:.1em;margin-bottom:9px;color:#545b72;}
  #vmi-page .vmi-pills{gap:8px;}
  #vmi-page .vmi-pill{min-width:74px;min-height:61px;padding:10px 14px;border:1px solid #e0e3ed;border-radius:14px;background:#f8f9fc;font-size:16px;}
  #vmi-page .vmi-pill small{font-size:8px;letter-spacing:.07em;}
  #vmi-page .vmi-pill:hover{transform:translateY(-1px);border-color:#a7a9f2;}
  #vmi-page .vmi-pill.is-active{border-color:#5154dc;background:#f0f0ff;box-shadow:inset 0 0 0 1px #5154dc;color:#24269c;}
  #vmi-page .vmi-pill.vmi-pill--mode{min-height:52px;background:#f8f9fc;}
  #vmi-page .vmi-unlock{margin-top:20px;padding:15px 16px;border:1px solid #dfe1ff;border-radius:17px;color:var(--ink);background:linear-gradient(130deg,#f3f3ff,#fff8f5);box-shadow:none;}
  #vmi-page .vmi-unlock::after{border-color:rgba(82,85,224,.08);}
  #vmi-page .vmi-unlock__ic{width:39px;height:39px;background:#e7e8ff;}#vmi-page .vmi-unlock__ic i{color:#4245cf;}
  #vmi-page .vmi-unlock__body strong{font-size:13px;color:var(--navy-deep);}#vmi-page .vmi-unlock__body span{color:var(--muted);font-size:11px;line-height:1.5;display:block;}
  #vmi-page .vmi-unlock .btn-white{background:var(--navy-deep);color:#fff;}
  #vmi-page .vmi-unlock.is-unlocked{color:#fff;background:linear-gradient(120deg,#0f7a78,#1f9d6b);border-color:transparent;}
  #vmi-page .vmi-unlock.is-unlocked .vmi-unlock__body strong{color:#fff;}#vmi-page .vmi-unlock.is-unlocked .vmi-unlock__body span{color:rgba(255,255,255,.75);}
  #vmi-page #vmi-device-card{display:flex;flex-direction:column;max-height:900px;overflow:hidden;opacity:1;transform:translateX(0) scale(1);visibility:visible;transition:opacity .3s ease,transform .5s cubic-bezier(.22,1,.36,1),max-height .55s cubic-bezier(.22,1,.36,1),padding .48s cubic-bezier(.22,1,.36,1),border-width .38s ease,visibility 0s;}
  #vmi-page #vmi-device-card.is-mode-hidden{max-height:0;padding-left:0;padding-right:0;border-left-width:0;border-right-width:0;opacity:0;transform:translateX(28px) scale(.97);visibility:hidden;pointer-events:none;transition:opacity .22s ease,transform .42s cubic-bezier(.22,1,.36,1),max-height .55s cubic-bezier(.22,1,.36,1),padding .48s cubic-bezier(.22,1,.36,1),border-width .38s ease,visibility 0s .55s;}
  #vmi-page .perm-row{padding:11px 13px;margin-bottom:8px;border:1px solid #e4e6ef;border-radius:12px;background:#fafbfe;font-size:12px;}
  #vmi-page .vmi-preview-frame{max-width:none;margin-top:auto;border:0;border-radius:16px;background:linear-gradient(145deg,#151846,#08091d);box-shadow:none;}
  #vmi-page .vmi-preview-frame::before{content:"Camera preview";position:absolute;left:12px;top:10px;z-index:1;padding:5px 8px;border-radius:8px;background:rgba(4,6,20,.55);color:rgba(255,255,255,.72);font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;backdrop-filter:blur(8px);}
  #vmi-page .vmi-preview-frame video{width:100%;min-height:194px;object-fit:cover;}
  #vmi-page #vmi-device-card>.actions{margin-top:14px;}#vmi-page #vmi-device-card>.actions .btn{width:100%;}
  #vmi-page .vmi-start-row{margin-top:20px;padding:20px 22px;border:1px solid #dfe1ed;border-radius:20px;background:rgba(255,255,255,.8);align-items:center;justify-content:center;box-shadow:0 18px 50px -40px rgba(20,24,64,.42);}

  #vmi-page .vmi-stage{background:linear-gradient(145deg,#11172a 0%,#090d18 70%,#0d1221 100%);}
  #vmi-page .vmi-stage__aurora{display:none;}
  #vmi-page .vmi-stage__top{height:72px;border-bottom:1px solid rgba(255,255,255,.08);background:rgba(8,10,29,.72);backdrop-filter:blur(20px);}
  #vmi-page .vmi-stage__logo{border-radius:10px;background:linear-gradient(145deg,#6467ff,#3538c5);}
  #vmi-page .vmi-stage__main{max-width:1260px;grid-template-columns:minmax(0,1.06fr) minmax(390px,.94fr);gap:56px;padding-top:50px;}
  #vmi-page .vmi-ai{min-width:0;}
  #vmi-page .vmi-ai__avatar.vmi-voice-agent{position:relative;width:100%;max-width:610px;height:auto;aspect-ratio:16/10;margin:0 auto 18px;border:1px solid rgba(255,255,255,.11);border-radius:22px;overflow:hidden;background:linear-gradient(145deg,#151d32,#0b1020);box-shadow:0 30px 70px -40px rgba(0,0,0,.82),inset 0 1px rgba(255,255,255,.045);animation:none;isolation:isolate;transition:border-color .4s ease,box-shadow .4s ease;}
  #vmi-page .vmi-voice-agent::before{content:"";position:absolute;z-index:0;inset:0;background:radial-gradient(circle at 50% 47%,rgba(79,101,168,.11),transparent 42%);}
  #vmi-page .vmi-voice-agent::after{content:"";position:absolute;z-index:3;inset:0;border-radius:inherit;box-shadow:inset 0 0 0 1px rgba(255,255,255,.035);pointer-events:none;}
  #vmi-page #vmi-voice-agent-canvas{position:absolute;z-index:1;inset:0;width:100%;height:100%;display:block;}
  #vmi-page .vmi-voice-agent__glass{position:absolute;z-index:2;inset:0;background:linear-gradient(180deg,rgba(5,9,18,.15),transparent 38%,transparent 66%,rgba(5,9,18,.55));pointer-events:none;}
  #vmi-page .vmi-voice-agent__top{position:absolute;z-index:4;left:15px;right:15px;top:14px;display:flex;align-items:center;justify-content:space-between;color:#fff;}
  #vmi-page .vmi-agent-live,#vmi-page .vmi-agent-secure{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border:1px solid rgba(255,255,255,.1);border-radius:8px;background:rgba(8,13,25,.58);backdrop-filter:blur(10px);font-size:8px;font-weight:750;letter-spacing:.08em;text-transform:uppercase;}
  #vmi-page .vmi-agent-live i{width:6px;height:6px;border-radius:50%;background:#7c93cf;animation:vmiAgentHalo 2.8s ease-in-out infinite;}
  #vmi-page .vmi-agent-secure{color:rgba(255,255,255,.68);}
  #vmi-page .vmi-agent-secure i{width:12px;height:12px;color:#73e9b3;}
  #vmi-page .vmi-voice-agent__identity{position:absolute;z-index:4;left:18px;bottom:16px;display:flex;align-items:center;gap:10px;color:#fff;text-align:left;}
  #vmi-page .vmi-voice-agent__mark{width:37px;height:37px;border:1px solid rgba(255,255,255,.13);border-radius:10px;display:grid;place-items:center;background:#34466f;backdrop-filter:blur(10px);}
  #vmi-page .vmi-voice-agent__mark i{width:18px;height:18px;}
  #vmi-page .vmi-voice-agent__identity b,#vmi-page .vmi-voice-agent__identity small{display:block;}
  #vmi-page .vmi-voice-agent__identity b{font-size:13px;line-height:1.35;}
  #vmi-page .vmi-voice-agent__identity small{font-size:8px;color:rgba(255,255,255,.58);text-transform:uppercase;letter-spacing:.1em;}
  #vmi-page .vmi-voice-agent__signal{position:absolute;z-index:4;right:17px;bottom:18px;width:58px;height:33px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.11);border-radius:8px;background:rgba(8,13,25,.62);backdrop-filter:blur(10px);transition:background .35s ease,box-shadow .35s ease;}
  #vmi-page .vmi-voice-agent__signal .eq{height:15px;display:flex;align-items:center;gap:3px;}
  #vmi-page .vmi-voice-agent__signal .eq i{width:3px;height:13px;border-radius:4px;background:linear-gradient(#fff,#9ca2ff);transform:scaleY(.28);transform-origin:center;}
  #vmi-page .vmi-voice-agent.is-speaking{border-color:rgba(115,142,205,.42);box-shadow:0 30px 70px -40px rgba(0,0,0,.82),0 0 0 3px rgba(92,119,181,.06);}
  #vmi-page .vmi-voice-agent.is-speaking .vmi-agent-live i{background:#8da8df;}
  #vmi-page .vmi-voice-agent.is-speaking .vmi-voice-agent__signal{background:#3b527f;box-shadow:none;}
  #vmi-page .vmi-voice-agent.is-speaking .vmi-voice-agent__signal .eq i{animation:vmiAgentBars .62s ease-in-out infinite;}
  #vmi-page .vmi-voice-agent.is-speaking .vmi-voice-agent__signal .eq i:nth-child(2){animation-delay:.1s;}#vmi-page .vmi-voice-agent.is-speaking .vmi-voice-agent__signal .eq i:nth-child(3){animation-delay:.2s;}#vmi-page .vmi-voice-agent.is-speaking .vmi-voice-agent__signal .eq i:nth-child(4){animation-delay:.3s;}#vmi-page .vmi-voice-agent.is-speaking .vmi-voice-agent__signal .eq i:nth-child(5){animation-delay:.16s;}
  #vmi-page .vmi-voice-agent.is-listening{border-color:rgba(68,220,194,.45);box-shadow:0 38px 90px -38px rgba(0,0,0,.88),0 0 0 4px rgba(68,220,194,.08);}
  #vmi-page .vmi-voice-agent.is-listening .vmi-agent-live i{background:#54e3b2;}
  #vmi-page .vmi-voice-agent.is-listening .vmi-voice-agent__signal{background:rgba(35,159,139,.65);}
  #vmi-page .vmi-ai__status{display:inline-flex;align-items:center;justify-content:center;min-height:0;margin:0 auto 12px;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.07);color:#f7dc84;font-size:9px;letter-spacing:.1em;}
  #vmi-page .vmi-ai .cat-badge{padding:7px 11px;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.065);font-size:9px;}
  #vmi-page .vmi-ai__bubble{border-radius:18px;padding:20px 24px;margin-top:12px;background:rgba(255,255,255,.065);backdrop-filter:blur(18px);}
  #vmi-page .vmi-ai__bubble .qtext{font-family:var(--font-head);font-size:25px;letter-spacing:-.035em;}
  #vmi-page .vmi-ai__hint{margin-top:12px;}
  #vmi-page .vmi-answer .qbox{border:1px solid rgba(255,255,255,.7);border-radius:25px;background:rgba(255,255,255,.95);box-shadow:0 35px 80px -36px rgba(0,0,0,.7);backdrop-filter:blur(22px);}
  #vmi-page .mode-toggle{background:#eff1f7;border-radius:13px;padding:4px;}
  #vmi-page .mode-btn{border:0;border-radius:10px;background:transparent;}
  #vmi-page .mode-btn.active{background:#fff;color:var(--navy-deep);box-shadow:0 5px 14px -8px rgba(16,18,59,.38);}
  #vmi-page .transcript-box,#vmi-page .manual-answer{border-radius:14px;background:#f7f8fc;border-color:#dfe2ec;}
  #vmi-page .vmi-cam-pip{border-radius:18px;border:1px solid rgba(255,255,255,.18);box-shadow:0 25px 60px -25px rgba(0,0,0,.85);}

  @media(max-width:900px){
    #vmi-page .vmi-ai__avatar.vmi-voice-agent{width:100%;max-width:560px;aspect-ratio:16/9;}
    #vmi-page .vmi-voice-agent__identity small{display:none;}
  }
  @media(max-width:560px){
    #vmi-page .vmi-ai__avatar.vmi-voice-agent{border-radius:18px;}
    #vmi-page .vmi-voice-agent__top{left:10px;right:10px;top:10px;}
    #vmi-page .vmi-agent-live,#vmi-page .vmi-agent-secure{padding:6px 8px;font-size:7px;}
    #vmi-page .vmi-voice-agent__identity{left:12px;bottom:11px;}
    #vmi-page .vmi-voice-agent__mark{width:31px;height:31px;border-radius:10px;}
    #vmi-page .vmi-voice-agent__signal{right:11px;bottom:11px;}
    #vmi-page .vmi-ai__bubble .qtext{font-size:20px;}
  }

  #vmi-page #screen-report{max-width:1080px;margin:0 auto;}
  #vmi-page .report-head{text-align:center;margin:18px auto 28px;}
  #vmi-page .report-head h1{font-family:var(--font-head);font-size:clamp(38px,5vw,56px);font-weight:800;letter-spacing:-.05em;color:var(--navy-deep);}
  #vmi-page #screen-report>.card,#vmi-page #screen-report .two-col>.card{margin-bottom:16px;}
  #vmi-page #screen-report .card h3{font-size:11px;letter-spacing:.11em;color:#565d74;}
  #vmi-page .score-chip{border-color:#e4e7ef;border-radius:14px;background:#f7f8fc;}
  #vmi-page .score-ring{filter:drop-shadow(0 18px 28px rgba(85,87,232,.16));}
  #vmi-page .ring-num{font-family:var(--font-head);fill:var(--navy-deep);}
  #vmi-page .overall-pill{border-radius:12px;background:var(--navy-deep);}
  #vmi-page .vmi-modal__card{border:1px solid rgba(255,255,255,.75);border-radius:26px;box-shadow:0 38px 100px -42px rgba(6,8,30,.7);}

  @media(max-width:980px){
    #vmi-page .vmi-hero{padding:52px 42px;}
    #vmi-page .vmi-hero__grid{grid-template-columns:1fr;gap:28px;}
    #vmi-page .vmi-hero__art{height:390px;max-width:540px;width:100%;margin:8px auto 0;}
    #vmi-page .vmi-setup-grid{grid-template-columns:1fr;}
  }
  @media(max-width:640px){
    #vmi-page .vmi-wrap{padding:20px 14px 76px;}
    #vmi-page .vmi-hero{min-height:auto;padding:38px 22px 28px;border-radius:25px;}
    #vmi-page .vmi-hero h1{font-size:43px;}
    #vmi-page .vmi-hero p.pagesub{font-size:14px;line-height:1.7;}
    #vmi-page .vmi-hero__cta{align-items:flex-start;flex-direction:column;}
    #vmi-page .vmi-hero__stats{margin-top:30px;}
    #vmi-page .vmi-stat{padding:0 10px;}#vmi-page .vmi-stat strong{font-size:18px;}#vmi-page .vmi-stat small{white-space:normal;font-size:8px;}
    #vmi-page .vmi-hero__art{height:330px;}
    #vmi-page .vmi-console{inset:15px 0 20px;}
    #vmi-page .vmi-console__body{padding:20px 18px 16px;}
    #vmi-page .vmi-officer{width:88px;height:88px;}#vmi-page .vmi-officer__core{width:60px;height:60px;border-radius:19px;}
    #vmi-page .vmi-console__body p{font-size:16px;}
    #vmi-page .vmi-signal-card--score{right:-7px;top:42px;}#vmi-page .vmi-signal-card--feedback{left:-6px;bottom:34px;}
    #vmi-page .vmi-steps{overflow-x:auto;padding:10px;}#vmi-page .vmi-steps li .vmi-step__txt b{font-size:10px;}
    #vmi-page .vmi-setup-intro{text-align:left;}#vmi-page .vmi-setup-intro p{font-size:13px;}
    #vmi-page .card{padding:21px;border-radius:20px;}
    #vmi-page .vmi-pill{flex:1;min-width:62px;padding-inline:8px;}
    #vmi-page .vmi-setup-grid.is-text-mode .vmi-options-card{padding-inline:21px;}
    #vmi-page .vmi-setup-grid.is-text-mode .vmi-options-card #vmi-count-pills{display:flex;}
    #vmi-page .vmi-unlock{align-items:flex-start;}
    #vmi-page .vmi-unlock .btn{width:100%;}
    #vmi-page .vmi-start-row{align-items:stretch;flex-direction:column;padding:17px;}#vmi-page .vmi-start-row .btn{width:100%;}
    #vmi-page .vmi-stage__top{height:auto;min-height:68px;}
  }

  @media(prefers-reduced-motion:reduce){
    #vmi-page .vmi-load,#vmi-page .rec-dot,#vmi-page .rec-dot-sm,#vmi-page .spinner,#vmi-page .btn-start,
    #vmi-page .perm-dot.ok,#vmi-page .vmi-cam,#vmi-page .vmi-cam__wave i,#vmi-page .vmi-badge,
    #vmi-page .readiness-tag,#vmi-page .vmi-lead-success .vmi-check,#vmi-page .vmi-bg::before,#vmi-page .vmi-bg::after,
    #vmi-page .vmi-ai__avatar,#vmi-page .vmi-ai__avatar .eq i,#vmi-page .vmi-ai__avatar .ring,
    #vmi-page .vmi-stage__aurora span,#vmi-page .vmi-analyze__orb,#vmi-page .vmi-analyze__spin,
    #vmi-page .vmi-analyze__steps li.is-active .tick,#vmi-page .vmi-console,#vmi-page .vmi-officer__orbit,
    #vmi-page .vmi-console__wave i,#vmi-page .vmi-signal-card,#vmi-page .vmi-live-dot{animation:none;}
    #vmi-page .vmi-agent-live i,#vmi-page .vmi-voice-agent.is-speaking .vmi-voice-agent__signal .eq i{animation:none !important;}
    #vmi-page .btn:hover::before{animation:none;}
    #vmi-page.anim [data-reveal]{opacity:1;transform:none;}
    #vmi-page .chip-bar__fill{animation:none;width:var(--w,0%);}
  }

  /* ============================================================
     PROFESSIONAL LAYER (2026-07) — calmer, lighter, less decorative.
     Overrides the two layers above; every functional hook is kept.
     Goal: modern, professional, low-motion.
     ============================================================ */

  /* -- Hero: static (no orbit / 3D float / floating badges / gradient text) -- */
  #vmi-page .vmi-hero h1 em{background:none;-webkit-text-fill-color:var(--orange-soft);color:var(--orange-soft);}
  #vmi-page .vmi-console{animation:none;transform:none;}
  #vmi-page .vmi-console::before{animation:none;}
  #vmi-page .vmi-officer__orbit{animation:none;border-color:rgba(142,145,255,.30);}
  #vmi-page .vmi-officer__orbit::before{display:none;}
  #vmi-page .vmi-signal-card{display:none;}
  #vmi-page .vmi-bg::before,#vmi-page .vmi-bg::after{animation:none;}

  /* -- Buttons: drop the moving shine sweep + hover lift -- */
  #vmi-page .btn::before{display:none!important;}
  #vmi-page .btn:hover{transform:none;}

  /* -- Interview stage → light & professional -- */
  /* Explicit opaque background so the site nav/footer never show through, and
     clip any horizontal overflow so nothing bleeds past the viewport edge. */
  #vmi-page .vmi-stage{background-color:#eef1f8;background-image:radial-gradient(1000px 560px at 50% -180px,rgba(85,87,232,.10),transparent 70%);color:var(--ink);overflow-x:hidden;}
  #vmi-page .vmi-stage__aurora{display:none!important;}
  #vmi-page .vmi-stage__top{height:66px;background:rgba(255,255,255,.94);border-bottom:1px solid var(--line);backdrop-filter:blur(14px);}
  #vmi-page .vmi-stage__brand{color:var(--navy-deep);}
  #vmi-page .vmi-stage__logo{background:var(--navy-deep);box-shadow:none;}
  #vmi-page .vmi-stage__progress .progress-track{background:var(--line);}
  #vmi-page .vmi-stage__progress .progress-label{color:var(--muted);}
  #vmi-page .vmi-icbtn{background:#fff;border:1px solid var(--line);color:var(--navy-deep);}
  #vmi-page .vmi-icbtn:hover{background:#f2f3f9;transform:none;}
  #vmi-page .vmi-icbtn.is-off{opacity:.55;}
  #vmi-page .vmi-icbtn--exit:hover{background:#fdeaea;border-color:#f3c9c9;color:var(--bad);}

  /* Two-column body: top-aligned + balanced, single column on small screens.
     (Replaces the fragile minmax(390px,…) column that overlapped on mobile.) */
  #vmi-page .vmi-stage__main{max-width:1180px;min-width:0;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:48px;
    align-items:start;align-content:start;padding:46px 40px 130px;}
  #vmi-page .vmi-ai{display:flex;flex-direction:column;align-items:center;text-align:center;min-width:0;}
  #vmi-page .vmi-answer{align-self:start;min-width:0;}
  @media(max-width:900px){
    #vmi-page .vmi-stage__main{grid-template-columns:minmax(0,1fr);gap:22px;padding:26px 16px 150px;align-items:stretch;}
    #vmi-page .vmi-ai,#vmi-page .vmi-answer{width:100%;max-width:100%;}
    #vmi-page .vmi-ai__avatar.vmi-interviewer,#vmi-page .vmi-ai__bubble{max-width:100%;}
  }

  /* interviewer panel (replaces the animated WebGL-style canvas orb) */
  #vmi-page .vmi-ai__avatar.vmi-interviewer{width:100%;max-width:520px;height:auto;aspect-ratio:auto;margin:0 auto 16px;padding:22px 24px 20px;
    display:flex;flex-direction:column;gap:16px;border:1px solid var(--line);border-radius:20px;background:#fff;box-shadow:var(--shadow-card);animation:none;}
  #vmi-page .vmi-interviewer__head{display:flex;align-items:center;gap:14px;text-align:left;}
  #vmi-page .vmi-interviewer__avatar{flex-shrink:0;width:54px;height:54px;border-radius:15px;display:grid;place-items:center;color:#fff;background:linear-gradient(135deg,var(--navy),var(--navy-soft));}
  #vmi-page .vmi-interviewer__avatar i{width:27px;height:27px;}
  #vmi-page .vmi-interviewer__id{min-width:0;}
  #vmi-page .vmi-interviewer__id b{display:block;font-size:16px;font-weight:800;color:var(--navy-deep);letter-spacing:-.01em;}
  #vmi-page .vmi-interviewer__id small{display:flex;align-items:center;gap:7px;margin-top:3px;font-size:12px;color:var(--muted);}
  #vmi-page .vmi-interviewer__dot{width:8px;height:8px;border-radius:50%;background:#c9ccd8;flex-shrink:0;transition:background .3s ease;}
  #vmi-page .vmi-interviewer.is-speaking .vmi-interviewer__dot{background:var(--orange);}
  #vmi-page .vmi-interviewer.is-listening .vmi-interviewer__dot{background:var(--good);}
  #vmi-page .vmi-interviewer__wave{display:flex;align-items:center;justify-content:center;gap:5px;height:40px;}
  #vmi-page .vmi-interviewer__wave i{width:5px;height:100%;border-radius:99px;background:var(--line);transform:scaleY(.28);transform-origin:center;transition:background .3s ease;}
  #vmi-page .vmi-interviewer.is-speaking .vmi-interviewer__wave i{background:linear-gradient(var(--orange),var(--orange-soft));animation:vmiWaveModern .9s ease-in-out infinite;}
  #vmi-page .vmi-interviewer.is-listening .vmi-interviewer__wave i{background:linear-gradient(var(--good),#6fd3a9);animation:vmiWaveModern 1.15s ease-in-out infinite;}
  #vmi-page .vmi-interviewer__wave i:nth-child(2),#vmi-page .vmi-interviewer__wave i:nth-child(8){animation-delay:.09s;}
  #vmi-page .vmi-interviewer__wave i:nth-child(3),#vmi-page .vmi-interviewer__wave i:nth-child(7){animation-delay:.18s;}
  #vmi-page .vmi-interviewer__wave i:nth-child(4),#vmi-page .vmi-interviewer__wave i:nth-child(6){animation-delay:.27s;}
  #vmi-page .vmi-interviewer__wave i:nth-child(5){animation-delay:.36s;}

  /* stage text blocks → light; category + status each on their own centered line */
  #vmi-page .vmi-ai__status{display:flex;width:fit-content;margin:0 auto 10px;background:#fff;border:1px solid var(--line);color:var(--orange-deep);box-shadow:var(--shadow-card);}
  #vmi-page .vmi-ai .cat-badge{display:flex;width:fit-content;margin:0 auto 14px;background:linear-gradient(135deg,var(--navy),var(--navy-soft));border:0;color:#fff;}
  #vmi-page .vmi-ai .cat-badge i{color:var(--gold);}
  #vmi-page .vmi-ai__bubble{width:100%;max-width:520px;margin:0 auto;background:#fff;border:1px solid var(--line);box-shadow:var(--shadow-card);backdrop-filter:none;}
  #vmi-page .vmi-ai__bubble::before{background:#fff;border-color:var(--line);}
  #vmi-page .vmi-ai__bubble .qtext{color:var(--navy-deep);}
  #vmi-page .vmi-ai__avatar.is-speaking ~ .vmi-ai__bubble .qtext::after{color:var(--orange);}
  #vmi-page .vmi-ai__hint{color:var(--muted);}
  #vmi-page .vmi-ai__hint i{color:var(--orange);}
  #vmi-page .vmi-answer .qbox{box-shadow:var(--shadow-pop);backdrop-filter:none;}
  #vmi-page .vmi-cam-pip{border:1px solid rgba(255,255,255,.9);box-shadow:0 18px 45px -26px rgba(18,22,61,.55);}

  /* -- Analysing transition → calm & light -- */
  #vmi-page .vmi-analyze{background-color:#eef1f8;background-image:radial-gradient(1000px 560px at 50% -180px,rgba(85,87,232,.10),transparent 70%);color:var(--ink);}
  #vmi-page .vmi-analyze .vmi-stage__aurora{display:none!important;}
  #vmi-page .vmi-analyze__orb{animation:none;}
  #vmi-page .vmi-analyze__spin{background:conic-gradient(var(--orange) 0 26%,rgba(20,24,64,.08) 0 100%);}
  #vmi-page .vmi-analyze__inner h2{color:var(--navy-deep);}
  #vmi-page .vmi-analyze__inner > p{color:var(--muted);}
  #vmi-page .vmi-analyze__time{background:#fff;border-color:var(--line);color:var(--navy-deep);box-shadow:var(--shadow-card);}
  #vmi-page .vmi-analyze__steps li{color:var(--muted);}
  #vmi-page .vmi-analyze__steps li .tick{background:#fff;border:1px solid var(--line);}
  #vmi-page .vmi-analyze__steps li.is-active{color:var(--navy-deep);}
  #vmi-page .vmi-analyze__steps li.is-active .tick{background:#fff0eb;border-color:var(--orange);animation:none;}
  #vmi-page .vmi-analyze__steps li.is-done{color:var(--navy-deep);}
  #vmi-page .vmi-analyze__steps li.is-done .tick{background:var(--good);border-color:var(--good);}
  #vmi-page .vmi-analyze__bar{background:var(--line);}

  /* -- Pre-interview preparation transition -- */
  @keyframes vmiPrepareOrbit{to{transform:rotate(360deg);}}
  @keyframes vmiPreparePulse{0%,100%{transform:scale(1);box-shadow:0 18px 44px -24px rgba(16,2,88,.35);}50%{transform:scale(1.04);box-shadow:0 22px 54px -20px rgba(85,87,232,.42);}}
  #vmi-page .vmi-prepare{position:fixed;inset:0;z-index:100004;display:flex;align-items:center;justify-content:center;padding:24px;
    color:var(--ink);font-family:var(--font-body);background-color:#eef1f8;background-image:radial-gradient(900px 520px at 50% -130px,rgba(85,87,232,.13),transparent 72%);
    opacity:0;visibility:hidden;pointer-events:none;will-change:opacity;transition:opacity .52s cubic-bezier(.22,1,.36,1),visibility 0s linear .52s;}
  #vmi-page .vmi-prepare.is-visible{opacity:1;visibility:visible;pointer-events:auto;transition:opacity .52s cubic-bezier(.22,1,.36,1),visibility 0s;}
  #vmi-page .vmi-prepare.is-leaving{opacity:0;visibility:visible;pointer-events:none;transition:opacity .46s cubic-bezier(.4,0,.2,1);}
  #vmi-page .vmi-prepare__inner{text-align:center;width:min(100%,460px);opacity:0;filter:blur(3px);transform:translateY(18px) scale(.985);will-change:opacity,transform,filter;
    transition:opacity .44s ease .08s,filter .44s ease .08s,transform .62s cubic-bezier(.22,1,.36,1) .04s;}
  #vmi-page .vmi-prepare.is-visible .vmi-prepare__inner{opacity:1;filter:blur(0);transform:translateY(0) scale(1);}
  #vmi-page .vmi-prepare.is-leaving .vmi-prepare__inner{opacity:.72;filter:blur(1px);transform:translateY(-8px) scale(.992);transition:opacity .38s ease,filter .38s ease,transform .46s ease;}
  #vmi-page .vmi-prepare__mark{position:relative;width:116px;height:116px;margin:0 auto 28px;display:grid;place-items:center;}
  #vmi-page .vmi-prepare__ring{position:absolute;inset:0;border-radius:50%;border:1px solid rgba(85,87,232,.16);border-top-color:var(--orange);animation:vmiPrepareOrbit 1.25s linear infinite;}
  #vmi-page .vmi-prepare__ring::after{content:"";position:absolute;inset:9px;border-radius:50%;border:1px dashed rgba(85,87,232,.22);}
  #vmi-page .vmi-prepare__core{position:relative;width:82px;height:82px;border-radius:24px;display:grid;place-items:center;color:#fff;background:linear-gradient(145deg,var(--navy),#5659e8);animation:vmiPreparePulse 2s ease-in-out infinite;}
  #vmi-page .vmi-prepare__core i{width:34px;height:34px;}
  #vmi-page .vmi-prepare h2{margin:0 0 9px;color:var(--navy-deep);font-family:var(--font-head);font-size:clamp(27px,4vw,36px);line-height:1.15;}
  #vmi-page .vmi-prepare__sub{margin:0 auto 26px;color:var(--muted);font-size:14.5px;line-height:1.6;}
  #vmi-page .vmi-prepare__steps{list-style:none;margin:0 auto 22px;padding:0;max-width:350px;display:flex;flex-direction:column;gap:9px;text-align:left;}
  #vmi-page .vmi-prepare__steps li{display:flex;align-items:center;gap:11px;padding:10px 13px;border:1px solid transparent;border-radius:12px;color:#9498aa;font-size:13.5px;font-weight:700;transition:color .3s ease,background .3s ease,border-color .3s ease,transform .3s ease;}
  #vmi-page .vmi-prepare__steps .dot{width:9px;height:9px;flex:0 0 9px;border-radius:50%;background:#c9ccd8;transition:background .3s ease,box-shadow .3s ease;}
  #vmi-page .vmi-prepare__steps li.is-active{color:var(--navy-deep);background:#fff;border-color:var(--line);transform:translateX(4px);}
  #vmi-page .vmi-prepare__steps li.is-active .dot{background:var(--orange);box-shadow:0 0 0 5px rgba(255,94,50,.12);}
  #vmi-page .vmi-prepare__steps li.is-done{color:var(--navy-deep);}
  #vmi-page .vmi-prepare__steps li.is-done .dot{background:var(--good);box-shadow:none;}
  #vmi-page .vmi-prepare__bar{height:5px;max-width:350px;margin:0 auto;overflow:hidden;border-radius:99px;background:var(--line);}
  #vmi-page .vmi-prepare__bar span{display:block;width:0;height:100%;border-radius:inherit;background:linear-gradient(90deg,var(--orange),var(--navy-soft));transition:width .55s cubic-bezier(.22,1,.36,1);}
  @media(prefers-reduced-motion:reduce){
    #vmi-page .vmi-prepare,#vmi-page .vmi-prepare.is-visible,#vmi-page .vmi-prepare.is-leaving,#vmi-page .vmi-prepare__inner,#vmi-page .vmi-prepare.is-visible .vmi-prepare__inner,#vmi-page .vmi-prepare.is-leaving .vmi-prepare__inner{transition:none;}
    #vmi-page .vmi-prepare__ring,#vmi-page .vmi-prepare__core{animation:none;}
    #vmi-page .vmi-prepare__steps li{transition:none;transform:none;}
    #vmi-page .vmi-prepare__bar span{transition:none;}
  }

  /* -- Report: gentler score ring, no big glow -- */
  #vmi-page .score-ring{filter:none;}

  /* ---------- Report layout + alignment refinements (2026-07) ---------- */
  /* Report spans the full content width */
  #vmi-page #screen-report{max-width:none;margin-left:0;margin-right:0;}
  /* Uniform vertical rhythm between EVERY top-level block (the .two-col rows
     previously had no bottom margin, so sections collided). */
  #vmi-page #screen-report > .card,
  #vmi-page #screen-report > .two-col,
  #vmi-page #screen-report > .report-stats,
  #vmi-page #screen-report > .report-method{margin-bottom:18px;}
  /* Two-column rows: equal-height cards sharing one gutter, no leftover per-card margin */
  #vmi-page #screen-report .two-col{gap:18px;align-items:stretch;}
  #vmi-page #screen-report .two-col > .card{margin-bottom:0;}
  /* Readiness header: even spacing, and stop long copy from squashing the ring */
  #vmi-page .badge-wrap{gap:32px;align-items:center;}
  #vmi-page .badge-wrap__body{min-width:0;}
  /* Consistent gutters for stat tiles */
  #vmi-page .report-stats{gap:16px;}
  /* Balanced, centred action buttons under the report */
  #vmi-page #screen-report > .actions{justify-content:center;margin-top:26px;}

  /* ---------- Step tracker redesign (2026-07) ---------- */
  #vmi-page .vmi-steps{gap:8px;padding:15px 22px;border-radius:20px;border:1px solid rgba(223,226,238,.9);
    background:linear-gradient(180deg,#ffffff,#f7f8fd);box-shadow:0 18px 46px -34px rgba(20,24,64,.55);backdrop-filter:none;margin-bottom:30px;}
  /* Steps size to their content; connectors stretch to fill the space between */
  #vmi-page .vmi-steps li{flex:0 1 auto;gap:12px;}
  #vmi-page .vmi-steps .vmi-step__bar{flex:1 1 24px;height:4px;border-radius:99px;background:#e6e9f2;min-width:18px;}
  #vmi-page .vmi-steps .vmi-step__bar::after{background:linear-gradient(90deg,var(--navy),var(--orange));border-radius:99px;}
  #vmi-page .vmi-steps .vmi-step__bar.is-filled::after{width:100%;}
  /* Default (upcoming) step */
  #vmi-page .vmi-steps li .vmi-step__dot{width:44px;height:44px;border-radius:14px;background:#eef0f7;color:var(--muted);position:relative;
    transition:transform .35s cubic-bezier(.2,.9,.3,1.2),background .3s ease,box-shadow .3s ease,color .3s ease;}
  #vmi-page .vmi-steps li .vmi-step__txt b{font-size:13px;font-weight:800;color:var(--navy-deep);}
  #vmi-page .vmi-steps li .vmi-step__txt small{font-size:11px;color:var(--muted);}
  /* Completed step: navy fill + a green completion check badge */
  #vmi-page .vmi-steps li.is-done .vmi-step__dot{background:linear-gradient(135deg,var(--navy-soft),var(--navy-deep));color:#fff;box-shadow:0 10px 22px -12px rgba(16,18,59,.6);}
  #vmi-page .vmi-steps li.is-done .vmi-step__dot::after{content:"";position:absolute;right:-4px;top:-4px;width:18px;height:18px;border-radius:50%;
    background-color:var(--good);border:2px solid #fff;background-repeat:no-repeat;background-position:center;background-size:10px;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'/%3E%3C/svg%3E");}
  /* Current step: orange gradient with a soft focus ring */
  #vmi-page .vmi-steps li.is-active .vmi-step__dot{background:linear-gradient(135deg,var(--orange),var(--orange-deep));color:#fff;
    box-shadow:0 12px 26px -10px rgba(255,94,50,.75),0 0 0 4px rgba(255,94,50,.14);transform:translateY(-2px) scale(1.04);}
  #vmi-page .vmi-steps li.is-active .vmi-step__txt b{color:var(--navy-deep);}

  /* ---------- Floating "new interview" mic button (navy theme, centre-right edge) ---------- */
  #vmi-page .vmi-restart-fab{position:fixed;right:24px;top:50%;bottom:auto;transform:translateY(-50%);z-index:81;display:inline-flex;align-items:center;gap:11px;
    padding:13px 22px 13px 14px;border-radius:999px;border:none;cursor:pointer;color:#fff;font-family:var(--font-body);
    font-size:13.5px;font-weight:800;letter-spacing:.01em;background:linear-gradient(135deg,var(--navy),var(--navy-deep));
    box-shadow:0 18px 40px -14px rgba(16,18,59,.55),0 4px 14px -8px rgba(16,2,88,.4);
    transition:transform .25s cubic-bezier(.2,.9,.3,1.2),box-shadow .25s ease;}
  /* Mic glyph in a soft disc with a live "listening" pulse ring */
  #vmi-page .vmi-restart-fab__mic{position:relative;flex:0 0 auto;width:30px;height:30px;border-radius:50%;display:grid;place-items:center;background:rgba(255,255,255,.18);}
  #vmi-page .vmi-restart-fab__mic i{width:16px;height:16px;}
  #vmi-page .vmi-restart-fab__mic::before{content:"";position:absolute;inset:0;border-radius:50%;border:2px solid rgba(255,255,255,.55);animation:vmiMicPulse 2s ease-out infinite;}
  @keyframes vmiMicPulse{0%{transform:scale(1);opacity:.7;}70%{transform:scale(1.6);opacity:0;}100%{opacity:0;}}
  #vmi-page .vmi-restart-fab:hover{transform:translateY(-50%) translateX(-4px);box-shadow:0 24px 50px -14px rgba(16,18,59,.62),0 6px 16px -8px rgba(16,2,88,.42);}
  #vmi-page .vmi-restart-fab:active{transform:translateY(-50%) translateX(-1px) scale(.99);}
  #vmi-page .vmi-restart-fab.hidden{display:none;}
  @media(max-width:560px){#vmi-page .vmi-restart-fab{right:14px;padding:11px;gap:0;}#vmi-page .vmi-restart-fab > span:last-child{display:none;}}
  @media(prefers-reduced-motion:reduce){#vmi-page .vmi-restart-fab{transition:none;}#vmi-page .vmi-restart-fab:hover{transform:translateY(-50%);}#vmi-page .vmi-restart-fab__mic::before{animation:none;}}
</style>
@endpush

@section('content')
<main id="main" class="vmi-main">
  <div id="vmi-page">
    <div class="vmi-bg" aria-hidden="true"></div>
    <div class="vmi-wrap">

      {{-- ===================== HERO ===================== --}}
      <header class="vmi-hero">
        <div class="vmi-hero__grid">
          <div class="vmi-hero__copy">
            <span class="vmi-eyebrow vmi-load" style="animation-delay:.05s"><span class="vmi-live-dot"></span> AI-powered visa interview simulator</span>
            <h1 class="vmi-load" style="animation-delay:.12s">Walk into your visa interview <em>ready.</em></h1>
            <p class="pagesub vmi-load" style="animation-delay:.2s">Rehearse real embassy-style questions in a realistic interview room. Get focused feedback on what you say, how you deliver it, and exactly where to improve.</p>
            <div class="vmi-hero__cta vmi-load" style="animation-delay:.28s">
              <button type="button" class="btn btn-primary btn-lg" data-scroll-setup><i data-lucide="play"></i> Start free interview</button>
              <span class="vmi-hero__microcopy"><i data-lucide="shield-check"></i> Private by design · no account needed</span>
            </div>
            <div class="vmi-hero__stats vmi-load" style="animation-delay:.28s">
              <span class="vmi-stat"><strong>10</strong><small>curated questions</small></span>
              <span class="vmi-stat"><strong>6</strong><small>visa categories</small></span>
              <span class="vmi-stat"><strong>360°</strong><small>readiness feedback</small></span>
            </div>
          </div>

          <div class="vmi-hero__art vmi-load" style="animation-delay:.3s" aria-hidden="true">
            <div class="vmi-console">
              <div class="vmi-console__top">
                <span><i></i><i></i><i></i></span>
                <b>LIVE INTERVIEW</b>
                <small>02:14</small>
              </div>
              <div class="vmi-console__body">
                <div class="vmi-officer">
                  <span class="vmi-officer__orbit"></span>
                  <span class="vmi-officer__core"><i data-lucide="sparkles"></i></span>
                </div>
                <span class="vmi-console__label">AI VISA OFFICER</span>
                <p>Why did you choose this university and programme?</p>
                <div class="vmi-console__wave"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
              </div>
              <div class="vmi-console__footer">
                <span><i data-lucide="mic"></i> Listening</span>
                <span><i data-lucide="message-square-text"></i> Transcript active</span>
              </div>
            </div>
            <div class="vmi-signal-card vmi-signal-card--score"><span>Readiness</span><b>88<small>/100</small></b><i data-lucide="trending-up"></i></div>
            <div class="vmi-signal-card vmi-signal-card--feedback"><i data-lucide="check"></i><span><b>Strong answer</b><small>Clear and well structured</small></span></div>
          </div>
        </div>
      </header>

      {{-- ===================== STEP TRACKER ===================== --}}
      <ol id="vmi-steps" class="vmi-steps" aria-label="Progress">
        <li data-step="1" class="is-active">
          <span class="vmi-step__dot"><i data-lucide="sliders-horizontal"></i></span>
          <span class="vmi-step__txt"><b>Set up</b><small>Choose &amp; check devices</small></span>
        </li>
        <li class="vmi-step__bar" data-bar="1" aria-hidden="true"></li>
        <li data-step="2">
          <span class="vmi-step__dot"><i data-lucide="video"></i></span>
          <span class="vmi-step__txt"><b>Interview</b><small>Answer the questions</small></span>
        </li>
        <li class="vmi-step__bar" data-bar="2" aria-hidden="true"></li>
        <li data-step="3">
          <span class="vmi-step__dot"><i data-lucide="award"></i></span>
          <span class="vmi-step__txt"><b>Report</b><small>Get your score</small></span>
        </li>
      </ol>

      {{-- ===================== SCREEN 1: SETUP ===================== --}}
      <div id="screen-setup">
        <div class="vmi-setup-intro" data-reveal>
          <span class="vmi-eyebrow"><i data-lucide="wand-sparkles"></i> Personalise your simulation</span>
          <h2>Build your practice round</h2>
          <p>Choose the depth and format. You can start with a quick warm-up or rehearse the complete interview.</p>
        </div>

        <div class="vmi-setup-grid">
        <div class="card vmi-options-card" data-reveal>
          <div class="card__head">
            <span class="card__ic is-orange"><i data-lucide="settings-2"></i></span>
            <div>
              <h2>Set up your practice</h2>
              <p>A few choices help the assessor ask the right questions and judge your answers fairly.</p>
            </div>
          </div>

          {{-- hidden real controls the JS reads; the pills below drive them --}}
          <div class="sr-only" aria-hidden="true">
            <select id="in-count">
              <option value="5">5 questions</option>
              <option value="10" selected>10 questions</option>
              <option value="15" disabled data-locked data-unlocked-label="15 questions (extended)">15 questions</option>
              <option value="20" disabled data-locked data-unlocked-label="20 questions (extended)">20 questions</option>
              <option value="39" disabled data-locked data-unlocked-label="39 questions — all categories">39 questions</option>
            </select>
            <select id="in-mode">
              <option value="video">Video + voice</option>
              <option value="text">Text only</option>
            </select>
          </div>

          <span class="vmi-field-label">Number of questions</span>
          <div class="vmi-pills" id="vmi-count-pills" role="group" aria-label="Number of questions">
            <button type="button" class="vmi-pill" data-count="5">5<small>Free</small></button>
            <button type="button" class="vmi-pill is-active" data-count="10">10<small>Free</small></button>
            <button type="button" class="vmi-pill" data-count="15" data-locked><i class="vmi-pill__lock" data-lucide="lock"></i>15<small>With team</small></button>
            <button type="button" class="vmi-pill" data-count="20" data-locked><i class="vmi-pill__lock" data-lucide="lock"></i>20<small>With team</small></button>
            <button type="button" class="vmi-pill" data-count="39" data-locked><i class="vmi-pill__lock" data-lucide="lock"></i>39<small>With team</small></button>
          </div>

          <span class="vmi-field-label" style="margin-top:22px;">Answer mode</span>
          <div class="vmi-pills" id="vmi-mode-pills" role="group" aria-label="Answer mode">
            <button type="button" class="vmi-pill vmi-pill--mode is-active" data-mode="video"><i data-lucide="video"></i> Video + voice</button>
            <button type="button" class="vmi-pill vmi-pill--mode" data-mode="text"><i data-lucide="keyboard"></i> Text only</button>
          </div>

          <label class="vmi-field-label" for="in-destination" style="margin-top:22px;">Target country <span style="text-transform:none;font-weight:600;color:var(--muted);">(optional, improves answer accuracy)</span></label>
          <input class="vmi-context-input" id="in-destination" type="text" maxlength="120" list="vmi-destinations" placeholder="For example: United Kingdom">
          <datalist id="vmi-destinations">
            <option value="United Kingdom"><option value="United States"><option value="Canada"><option value="Australia"><option value="Ireland"><option value="Germany"><option value="New Zealand"><option value="France"><option value="Italy"><option value="Netherlands">
          </datalist>

          <div class="vmi-unlock" id="vmi-unlock-cta">
            <span class="vmi-unlock__ic"><i data-lucide="headset"></i></span>
            <div class="vmi-unlock__body">
              <strong data-unlock-title>Preparing for the real interview?</strong>
              <span data-unlock-sub>The free round covers up to 10 questions. Share your details and our visa team will reach out with proper consulting, a full mock interview and your next steps.</span>
            </div>
            <button type="button" class="btn btn-white btn-sm" id="btn-unlock" data-lead-open>Talk to our team</button>
          </div>
        </div>

        <div class="card" id="vmi-device-card" data-reveal>
          <div class="card__head">
            <span class="card__ic"><i data-lucide="camera"></i></span>
            <div>
              <h2>Camera &amp; microphone check</h2>
              <p>The camera provides a live practice preview and the microphone creates your transcript. Raw video and audio are not saved; the AI assessor reviews only your submitted answers.</p>
            </div>
          </div>
          <div class="perm-row"><span class="perm-dot" id="dot-cam"></span><span id="txt-cam">Camera not tested yet</span></div>
          <div class="perm-row"><span class="perm-dot" id="dot-mic"></span><span id="txt-mic">Microphone not tested yet</span></div>
          <div id="perm-hint" class="hidden" style="background:#fff3de;border:1px solid #f5d9a6;border-radius:12px;padding:12px 14px;font-size:13px;color:#8a5a0f;margin-bottom:14px;line-height:1.6;">
            Your browser will show a small popup asking for permission — it may appear <strong>twice</strong> (once for the camera, once for the microphone). For each one, choose <strong>“Allow”</strong> so both are granted together.
          </div>
          <div class="vmi-preview-frame">
            <video id="setup-preview" autoplay muted playsinline style="aspect-ratio:4/3;"></video>
          </div>
          <div class="actions">
            <button class="btn btn-navy" id="btn-test-devices"><i data-lucide="camera"></i> Test camera &amp; mic</button>
          </div>
        </div>
        </div>

        <div class="actions vmi-start-row" data-reveal>
          <button class="btn btn-primary btn-lg btn-start" id="btn-start-interview" disabled><i data-lucide="play"></i> Start mock interview</button>
          <span style="align-self:center;color:var(--muted);font-size:13px;">Takes about 5–10 minutes · raw video is not stored</span>
        </div>
      </div>

      {{-- ============ PRE-INTERVIEW SETUP TRANSITION ============ --}}
      <div id="vmi-preparing" class="vmi-prepare" role="status" aria-live="polite" aria-label="Setting up your interview" aria-hidden="true">
        <div class="vmi-prepare__inner">
          <div class="vmi-prepare__mark" aria-hidden="true">
            <span class="vmi-prepare__ring"></span>
            <span class="vmi-prepare__core"><i data-lucide="user-check"></i></span>
          </div>
          <h2>We&rsquo;re setting up your interview</h2>
          <p class="vmi-prepare__sub">Just a moment while we prepare your private practice session.</p>
          <ul class="vmi-prepare__steps">
            <li data-pstep><span class="dot"></span>Confirming your interview settings</li>
            <li data-pstep><span class="dot"></span>Preparing your visa interviewer</li>
            <li data-pstep><span class="dot"></span>Loading your first question</li>
          </ul>
          <div class="vmi-prepare__bar" aria-hidden="true"><span></span></div>
        </div>
      </div>

      {{-- ===================== SCREEN 2: INTERVIEW (fullscreen AI stage) ===================== --}}
      <div id="screen-interview" class="vmi-stage hidden" role="dialog" aria-modal="true" aria-label="AI mock interview in progress">
        <div class="vmi-stage__aurora" aria-hidden="true"><span></span><span></span><span></span></div>

        <header class="vmi-stage__top">
          <div class="vmi-stage__brand"><span class="vmi-stage__logo"><i data-lucide="bot"></i></span> AI Visa Officer</div>
          <div class="vmi-stage__progress">
            <div class="progress-track"><div class="progress-fill" id="progress-fill"></div></div>
            <div class="progress-label" id="progress-label">Question 1 of 10</div>
          </div>
          <div class="vmi-stage__tools">
            <button type="button" class="vmi-icbtn" id="btn-ai-voice" title="Mute interviewer voice" aria-pressed="true"><i data-lucide="volume-2"></i></button>
            <button type="button" class="vmi-icbtn vmi-icbtn--exit" id="btn-exit-interview" title="Exit interview"><i data-lucide="x"></i> <span>Exit</span></button>
          </div>
        </header>

        <div class="vmi-stage__main">
          <section class="vmi-ai">
            <div class="vmi-ai__avatar vmi-interviewer is-idle" role="img" aria-label="AI visa interviewer">
              <div class="vmi-interviewer__head">
                <span class="vmi-interviewer__avatar"><i data-lucide="user-round"></i></span>
                <div class="vmi-interviewer__id">
                  <b>AI Visa Officer</b>
                  <small><span class="vmi-interviewer__dot"></span> Structured practice interview</small>
                </div>
              </div>
              <div class="vmi-interviewer__wave" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
            </div>
            <div class="vmi-ai__status" id="vmi-ai-status">Your interviewer is getting ready…</div>
            <span class="cat-badge" id="q-category"><i data-lucide="folder"></i> Personal &amp; Academic</span>
            <div class="vmi-ai__bubble"><p class="qtext" id="q-text">Loading question…</p></div>
            <div class="vmi-ai__hint"><i data-lucide="lightbulb"></i> Answer naturally in full sentences — aim for 30–45 seconds.</div>
          </section>

          <section class="vmi-answer">
            <div class="card qbox">
              <div class="qbox__head">
                <span class="ic"><i data-lucide="message-square-text"></i></span>
                <span><b>Your response</b><small id="answer-mode-copy">Speak your answer, or switch to typing.</small></span>
              </div>
              <div class="mode-toggle" id="mode-toggle">
                <button class="mode-btn active" id="mode-btn-speak" type="button"><i data-lucide="mic"></i> Speak</button>
                <button class="mode-btn" id="mode-btn-type" type="button"><i data-lucide="keyboard"></i> Type</button>
              </div>

              <div id="speak-ui">
                <div class="actions" style="margin-top:0;">
                  <button class="btn btn-primary" id="btn-record"><span class="rec-dot-sm"></span> Start recording</button>
                  <button class="btn btn-ghost hidden" id="btn-stop"><i data-lucide="square"></i> Stop</button>
                </div>
                <label style="display:block;font-size:12px;font-weight:800;letter-spacing:.03em;text-transform:uppercase;color:var(--navy-deep);margin:18px 0 7px;">Live transcript</label>
                <div class="transcript-box empty" id="transcript-box">Your spoken answer will appear here as you talk…</div>
              </div>

              <div id="type-ui" class="hidden">
                <label style="display:block;font-size:12px;font-weight:800;letter-spacing:.03em;text-transform:uppercase;color:var(--navy-deep);margin:0 0 7px;">Your answer</label>
                <textarea class="manual-answer" id="manual-answer" placeholder="Type your answer here…"></textarea>
              </div>

              <div class="actions">
                <button class="btn btn-primary" id="btn-submit-answer" disabled><i data-lucide="arrow-right"></i> Submit answer</button>
                <button class="btn btn-ghost" id="btn-skip">Skip</button>
              </div>

            </div>
          </section>
        </div>

        <aside class="vmi-cam-pip">
          <video id="live-video" autoplay muted playsinline></video>
          <div class="rec-badge hidden" id="rec-badge"><span class="rec-dot"></span>REC</div>
          <div class="timer-badge" id="timer-badge">00:00</div>
          <canvas class="waveform" id="waveform" height="44"></canvas>
          <div class="conf-meter">
            <div class="conf-label">Vocal energy</div>
            <div class="conf-track"><div class="conf-fill" id="conf-fill"></div></div>
          </div>
          <div class="vmi-cam-pip__you">You</div>
        </aside>

        <div class="hidden" id="feedback-card"></div>
      </div>

      {{-- ============ ANALYSING TRANSITION (before the report) ============ --}}
      <div id="vmi-analyzing" class="vmi-analyze hidden" role="status" aria-live="polite">
        <div class="vmi-stage__aurora" aria-hidden="true"><span></span><span></span><span></span></div>
        <div class="vmi-analyze__inner">
          <div class="vmi-analyze__orb" aria-hidden="true">
            <span class="vmi-analyze__spin"></span>
            <span class="vmi-analyze__core"><i data-lucide="brain-circuit"></i></span>
          </div>
          <h2>Analysing your interview</h2>
          <p>Your assessor is reviewing every answer and building your readiness report…</p>
          <div class="vmi-analyze__time" id="vmi-analysis-time">Calculating estimated completion time…</div>
          <ul class="vmi-analyze__steps">
            <li data-astep="transcribe"><span class="tick"><i data-lucide="check"></i></span> Reviewing your answers &amp; transcripts</li>
            <li data-astep="comm"><span class="tick"><i data-lucide="check"></i></span> Scoring communication &amp; delivery</li>
            <li data-astep="compile"><span class="tick"><i data-lucide="check"></i></span> Compiling your visa-readiness report</li>
          </ul>
          <div class="vmi-analyze__bar"><span></span></div>
          <button class="btn btn-primary hidden" id="btn-retry-ai-analysis" type="button" style="margin:22px auto 0;">Retry AI analysis</button>
        </div>
      </div>

      {{-- ===================== SCREEN 3: FINAL REPORT ===================== --}}
      <div id="screen-report" class="hidden">
        <div class="report-head" data-reveal>
          <h1>Your Visa Readiness Report</h1>
          <p id="report-sub">Based on your mock interview performance.</p>
        </div>

        <div class="card" data-reveal>
          <div class="badge-wrap">
            <svg class="score-ring" viewBox="0 0 200 200" id="score-ring-svg">
              <defs>
                <linearGradient id="vmiRingGrad" x1="0" y1="0" x2="1" y2="1">
                  <stop offset="0%" stop-color="#ff8a5c"/>
                  <stop offset="100%" stop-color="#ff5e32"/>
                </linearGradient>
              </defs>
              <circle cx="100" cy="100" r="86" fill="none" stroke="#ece8f5" stroke-width="15"/>
              <circle cx="100" cy="100" r="86" fill="none" stroke="url(#vmiRingGrad)" stroke-width="15" stroke-linecap="round"
                      id="ring-progress" stroke-dasharray="540" stroke-dashoffset="540" transform="rotate(-90 100 100)"/>
              <text x="100" y="98" text-anchor="middle" class="ring-num" id="ring-num">0</text>
              <text x="100" y="122" text-anchor="middle" class="ring-sub">out of 100</text>
            </svg>
            <div class="badge-wrap__body">
              <h3 style="margin-bottom:10px;">Overall visa readiness</h3>
              <div id="readiness-tag" class="readiness-tag rt-needs">Calculating…</div>
              <div class="overall-pill" id="readiness-score-pill">-- / 10</div>
              <p id="readiness-score-note" style="color:var(--muted);font-size:13.5px;margin-top:12px;margin-bottom:0;"></p>
            </div>
          </div>
        </div>

        <div class="report-stats" id="report-stats" data-reveal></div>

        <div class="two-col">
          <div class="card" data-reveal>
            <h3>Answer quality distribution</h3>
            <p class="report-section-note">Shows how consistently your answers met interview expectations.</p>
            <div id="report-distribution"></div>
          </div>
          <div class="card" data-reveal>
            <h3>Executive insights</h3>
            <p class="report-section-note">The most important results from this practice session.</p>
            <div class="report-insights" id="report-executive"></div>
          </div>
        </div>

        <div class="card" data-reveal>
          <h3>Communication assessment</h3>
          <p style="color:var(--muted);font-size:13px;margin-top:-6px;">Language · Grammar · Fluency · Pronunciation · Confidence · Tone · Clarity · Crispness</p>
          <div class="score-row" id="report-communication"></div>
        </div>

        <div class="card" data-reveal>
          <h3>Knowledge and answer quality</h3>
          <p class="report-section-note">Relevance and subject knowledge demonstrated in your submitted answers.</p>
          <div class="score-row" id="report-content"></div>
        </div>

        <div class="card" data-reveal>
          <h3>Performance by interview category</h3>
          <p class="report-section-note">Category averages help you prioritise the topics that need further preparation.</p>
          <div class="report-category-list" id="report-categories"></div>
        </div>

        <div class="two-col">
          <div class="card" data-reveal>
            <h3>Strengths</h3>
            <ul class="plain" id="report-strong"></ul>
          </div>
          <div class="card" data-reveal>
            <h3>Areas for improvement</h3>
            <ul class="plain" id="report-weak"></ul>
          </div>
        </div>

        <div class="card" data-reveal>
          <h3>Most common mistakes</h3>
          <ul class="plain" id="report-mistakes"></ul>
        </div>

        <div class="card" data-reveal>
          <h3>Questions that need more practice</h3>
          <ul class="plain" id="report-weak-questions"></ul>
        </div>

        <div class="card" data-reveal>
          <h3>Complete answer review</h3>
          <p class="report-section-note">Open any question to review your response, feedback, and a stronger answer structure.</p>
          <div class="report-answer-review" id="report-answer-review"></div>
        </div>

        <div class="card" data-reveal>
          <h3>Recommended practice questions</h3>
          <ul class="plain" id="report-practice"></ul>
        </div>

        <div class="card" data-reveal>
          <h3>Top 5 personalised recommendations</h3>
          <div id="report-plan" style="font-size:14.5px;line-height:1.7;"></div>
        </div>

        <div class="card" data-reveal>
          <h3>Overall interview summary</h3>
          <p id="report-summary" style="font-size:14.5px;line-height:1.7;margin:0;"></p>
        </div>

        <div class="report-method" data-reveal>
          <i data-lucide="info"></i>
          <span>{{ \App\Support\AiDisclaimer::TEXT }} Scores are practice indicators based on answer relevance, clarity, consistency, demonstrated knowledge, and available delivery signals. They are not a prediction or guarantee of a visa decision.</span>
        </div>

        <div class="actions">
          <button class="btn btn-primary" id="btn-download-pdf"><i data-lucide="download"></i> Download PDF report</button>
          <button class="btn btn-ghost" id="btn-restart"><i data-lucide="rotate-ccw"></i> New mock interview</button>
        </div>
      </div>

      <p class="footer-note">
        {{ config('site.name') }} — AI Visa Mock Interview. This tool gives AI-generated practice feedback to help you prepare;
        it is not a guarantee of any visa outcome and does not replace advice from your counsellor.
      </p>

    </div>{{-- /.vmi-wrap --}}

    {{-- ===================== LEAD MODAL (unlock the full interview) ===================== --}}
    <div class="vmi-modal" id="vmiLeadModal" role="dialog" aria-modal="true" aria-labelledby="vmiLeadTitle" aria-hidden="true" hidden>
      <div class="vmi-modal__backdrop" data-lead-close></div>
      <div class="vmi-modal__card" role="document">
        <button type="button" class="vmi-modal__close" data-lead-close aria-label="Close">&times;</button>

        <div data-lead-form-state>
          <span class="vmi-eyebrow"><i data-lucide="headset"></i> Talk to a visa expert</span>
          <h3 id="vmiLeadTitle">Get expert help with your interview</h3>
          <p class="vmi-modal__sub">The free round covers up to 10 questions. Share a few details and our visa team will reach out with proper consulting, a complete mock interview and the next steps to get you fully prepared.</p>

          <form id="vmiLeadForm" novalidate>
            <label for="vmi-lead-name">Full name</label>
            <input type="text" id="vmi-lead-name" name="name" autocomplete="name" required>

            <div class="grid2">
              <div>
                <label for="vmi-lead-email">Email</label>
                <input type="email" id="vmi-lead-email" name="email" autocomplete="email" required>
              </div>
              <div>
                <label for="vmi-lead-phone">Phone / WhatsApp</label>
                <input type="tel" id="vmi-lead-phone" name="phone" autocomplete="tel" required>
              </div>
            </div>

            <div class="grid2">
              <div>
                <label for="vmi-lead-dest">Destination country</label>
                <input type="text" id="vmi-lead-dest" name="destination" placeholder="e.g. UK, Canada, USA">
              </div>
              <div>
                <label for="vmi-lead-level">Study level</label>
                <select id="vmi-lead-level" name="level">
                  <option value="">Select…</option>
                  <option>Bachelors</option>
                  <option>Masters</option>
                  <option>MBA</option>
                  <option>PhD / Doctoral</option>
                  <option>Diploma / Foundation</option>
                </select>
              </div>
            </div>

            <div class="vmi-lead-error" id="vmiLeadError" hidden></div>

            <div class="actions" style="margin-top:22px;">
              <button type="submit" class="btn btn-primary" id="vmiLeadSubmit" style="flex:1;">
                <span class="vmi-lead-submit-label">Request a callback</span>
              </button>
            </div>
            <p style="color:var(--muted);font-size:12px;margin-top:12px;text-align:center;">We'll only use your details to help with your visa prep. No spam.</p>
          </form>
        </div>

        <div data-lead-success-state hidden>
          <div class="vmi-lead-success">
            <div class="vmi-check">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            </div>
            <h3 id="vmiLeadSuccessTitle">Thanks — we've got your details</h3>
            <p class="vmi-modal__sub" id="vmiLeadSuccessMsg" style="margin-bottom:20px;">Our visa team will reach out to you shortly with proper consulting and the next steps to prepare for your interview. In the meantime, you can keep practising the free round.</p>
            <div class="actions" style="justify-content:center;margin-top:0;">
              <button type="button" class="btn btn-primary" data-lead-close>Done</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Floating restart — revealed on the report so a new run is always one tap away.
         Sits above the site contact FAB and carries a mic motif for the voice interview. --}}
    <button type="button" id="vmi-restart-fab" class="vmi-restart-fab hidden" aria-label="Start a new mock interview">
      <span class="vmi-restart-fab__mic"><i data-lucide="mic"></i></span><span>New interview</span>
    </button>

  </div>{{-- /#vmi-page --}}
</main>

{{-- Dynamic values injected in a plain (Blade-compiled) script tag; the large
     functional script below is raw JS and reads window.VMI_CONFIG. --}}
<script>
  window.VMI_CONFIG = {
    leadUrl: @json(route('visa-mock.lead')),
    assessBatchUrl: @json(route('visa-mock.assess-batch')),
    csrf: (document.querySelector('meta[name="csrf-token"]') || {}).content || @json(csrf_token()),
    questionAudioBase: @json(asset('assets/audio/visa-mock-interview')),
    // True when the fast cloud assessor (Groq) is configured; drives the
    // completion-time estimate shown while analysing.
    fastAssessor: @json((bool) config('services.visa_mock_ai.groq.key')),
    // Stamped on the downloaded PDF report; the on-screen copy is in the markup.
    aiDisclaimer: @json(\App\Support\AiDisclaimer::TEXT)
  };
</script>

@verbatim
<script>
(function(){
"use strict";

/* ============================================================
   QUESTION BANK
   ============================================================ */
// Curated 10-question pool, balanced across all 6 categories. A free round asks
// 5 or 10 of these (drawn evenly by buildQueue). Extended/guided rounds are
// handled by our team via the consulting form, not in-app.
const QUESTION_BANK = [
  {cat:"Personal & Academic Background", items:[
    "Tell me about yourself.",
    "Why did you choose this course?"
  ]},
  {cat:"University & Course Related", items:[
    "Why did you choose this university?",
    "What is the duration of your course?"
  ]},
  {cat:"Country Knowledge", items:[
    "Why did you select this country for higher education?"
  ]},
  {cat:"Financial Questions", items:[
    "Who is sponsoring your education?",
    "How will you pay your tuition fees and living expenses?"
  ]},
  {cat:"Accommodation & Travel", items:[
    "Where will you stay after arriving?"
  ]},
  {cat:"Future Plans & Intentions", items:[
    "What will you do after completing your studies?",
    "Will you return to your home country after your studies?"
  ]}
];

const FREE_LIMIT = 10;
const PREFERS_REDUCED = !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);

/* ============================================================
   STATE
   ============================================================ */
const state = {
  mode:"video",
  destination:"",
  queue:[], qIndex:0, totalPlanned:10,
  unlocked:false, leadSubmitted:false, requestedLength:"",
  stream:null, streamReleased:false, audioCtx:null, analyser:null, sourceNode:null, rafId:null,
  recognition:null, recognitionReady:false, recognitionRunning:false,
  recognizing:false, wantRestart:false, transcript:"", finalTranscript:"",
  recStartTime:0, recordedDurationSec:0, questionStartedAt:0, timerInterval:null,
  volumeSamples:[],
  confidenceSamples:[],
  analysisRunning:false,
  results:[],
  answerMode:"speak",
  interviewActive:false
};

/* ============================================================
   HELPERS
   ============================================================ */
function $(id){ return document.getElementById(id); }
function show(el){ if(el) el.classList.remove("hidden"); }
function hide(el){ if(el) el.classList.add("hidden"); }
function clamp(n,lo,hi){ return Math.max(lo,Math.min(hi,n)); }
function scoreClass(v){ if(v>=7.5) return "high"; if(v>=5) return "mid"; return "low"; }
function fmtTime(sec){
  const m = Math.floor(sec/60).toString().padStart(2,"0");
  const s = Math.floor(sec%60).toString().padStart(2,"0");
  return m+":"+s;
}

/* ============================================================
   FRIENDLY ERROR BANNER
   ============================================================ */
function showRecError(message, level){
  // Most device notices are benign because Text mode remains available. Only
  // truly blocking cases, including an unavailable AI assessor, use red.
  const isError = level === "error";
  let banner = $("rec-error-banner");
  if(!banner){
    banner = document.createElement("div");
    banner.id = "rec-error-banner";
    const qbox = document.querySelector("#vmi-page .qbox");
    const anchor = qbox && qbox.querySelector(".mode-toggle");
    if(qbox){ qbox.insertBefore(banner, anchor || qbox.firstChild); }
  }
  banner.style.cssText = "display:flex;gap:10px;align-items:flex-start;border-radius:14px;padding:11px 12px;"+
    "font-size:11.5px;margin-bottom:14px;line-height:1.5;" + (isError
      ? "background:#fff2f1;border:1px solid #f4c7c3;color:#9f342e;"
      : "background:#fff9ed;border:1px solid #efdcae;color:#785c18;");
  const alertTone = isError ? "#c94f48" : "#aa7b1f";
  banner.innerHTML = '<span aria-hidden="true" style="flex:0 0 22px;width:22px;height:22px;border-radius:7px;display:grid;place-items:center;background:'+alertTone+';color:#fff;font-size:11px;font-weight:900;">!</span>'+ 
    '<span style="flex:1;padding-top:2px;">'+message+'</span>'+ 
    '<button type="button" aria-label="Dismiss" style="background:none;border:0;color:inherit;font-size:17px;line-height:1;cursor:pointer;opacity:.7;">&times;</button>';
  banner.querySelector("button").addEventListener("click", clearRecError);
  banner.style.display = "flex";
}
function clearRecError(){
  const banner = $("rec-error-banner");
  if(banner) banner.style.display = "none";
}

/* ============================================================
   MEDIA STREAM LIFECYCLE
   ============================================================ */
function releaseMediaStream(){
  try{
    if(state.stream){
      state.stream.getTracks().forEach(t=>{ try{ t.stop(); }catch(e){} });
    }
  }catch(e){ console.warn("Error releasing media stream", e); }
  state.stream = null;
  state.streamReleased = true;

  try{ if(state.rafId) cancelAnimationFrame(state.rafId); }catch(e){}
  try{ if(state.sourceNode) state.sourceNode.disconnect(); }catch(e){}
  try{ if(state.audioCtx && state.audioCtx.state !== "closed") state.audioCtx.close(); }catch(e){}
  state.audioCtx = null; state.analyser = null; state.sourceNode = null; state.rafId = null;

  try{
    if(state.recognition){
      state.recognizing = false; state.wantRestart = false;
      state.recognition.onend = null;
      state.recognition.stop();
    }
  }catch(e){}
}

window.addEventListener("pagehide", releaseMediaStream);
window.addEventListener("beforeunload", releaseMediaStream);

/* ============================================================
   SETUP SCREEN — device check
   ============================================================ */
$("btn-test-devices").addEventListener("click", async () => {
  const dotCam = $("dot-cam"), txtCam = $("txt-cam"), dotMic = $("dot-mic"), txtMic = $("txt-mic");
  const hint = $("perm-hint");
  show(hint);
  dotCam.classList.remove("ok","bad"); dotMic.classList.remove("ok","bad");
  txtCam.textContent = "Waiting for you to respond to the browser prompt…";
  txtMic.textContent = "Waiting for you to respond to the browser prompt…";

  if(!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia){
    dotCam.classList.add("bad"); txtCam.textContent = "This browser doesn't support camera/mic access — please use a recent Chrome, Edge, or Safari.";
    dotMic.classList.add("bad"); txtMic.textContent = "Try Chrome, Edge, or Safari (latest version) for the best experience.";
    hide(hint);
    return;
  }

  if(state.stream && state.stream.getTracks().some(t=>t.readyState==="live")){
    dotCam.classList.add("ok"); txtCam.textContent = "Camera working";
    dotMic.classList.add("ok"); txtMic.textContent = "Microphone working";
    $("setup-preview").srcObject = state.stream;
    hide(hint);
    $("btn-start-interview").disabled = false;
    return;
  }

  let stream = null, lastErr = null;
  try{
    stream = await navigator.mediaDevices.getUserMedia({video:true, audio:true});
  }catch(err){
    console.warn("Combined camera+mic request failed, trying microphone only:", err);
    lastErr = err;
    try{
      stream = await navigator.mediaDevices.getUserMedia({audio:true});
    }catch(err2){
      console.error(err2);
      lastErr = err2;
    }
  }

  if(!stream){
    const name = lastErr && lastErr.name;
    let camMsg = "Camera blocked — click the camera icon in your address bar, choose Allow, then retry";
    let micMsg = "Microphone blocked — click the icon in your address bar, choose Allow, then retry";
    if(name === "NotAllowedError" || name === "PermissionDeniedError"){
      camMsg = "Camera/microphone permission was denied. Click the padlock/camera icon in your address bar, allow access, then click Test camera & mic again.";
      micMsg = camMsg;
    } else if(name === "NotFoundError" || name === "DevicesNotFoundError"){
      camMsg = "No camera or microphone was found on this device. Connect one, or continue in Text mode instead.";
      micMsg = camMsg;
    } else if(name === "NotReadableError" || name === "TrackStartError"){
      camMsg = "Your camera or mic is already in use by another app or browser tab. Close it and retry.";
      micMsg = camMsg;
    }
    dotCam.classList.add("bad"); txtCam.textContent = camMsg;
    dotMic.classList.add("bad"); txtMic.textContent = micMsg;
    hide(hint);
    return;
  }

  state.stream = stream;
  state.streamReleased = false;
  $("setup-preview").srcObject = stream;

  const hasVideo = stream.getVideoTracks().length > 0;
  const hasAudio = stream.getAudioTracks().length > 0;

  if(hasVideo){ dotCam.classList.add("ok"); txtCam.textContent = "Camera working"; }
  else { dotCam.classList.add("bad"); txtCam.textContent = "No camera detected or camera permission was skipped — you can still continue with voice or text only."; }

  if(hasAudio){ dotMic.classList.add("ok"); txtMic.textContent = "Microphone working"; }
  else { dotMic.classList.add("bad"); txtMic.textContent = "Microphone blocked — allow it in your browser and retry for accurate voice scoring."; }

  hide(hint);
  $("btn-start-interview").disabled = !hasAudio;
});

// Text mode needs no camera/mic, so the whole device-check card is hidden and
// Start is enabled immediately. Video mode shows it again and (re)gates Start
// on a working device unless a live stream already exists.
function applyModeUi(mode){
  const card = $("vmi-device-card");
  const setupGrid = card ? card.closest(".vmi-setup-grid") : null;
  const startBtn = $("btn-start-interview");
  const streamLive = state.stream && state.stream.getTracks().some(t=>t.readyState==="live");
  const isTextMode = mode === "text";

  if(card){
    card.classList.remove("hidden");
    card.classList.toggle("is-mode-hidden", isTextMode);
    card.setAttribute("aria-hidden", isTextMode ? "true" : "false");
  }
  if(setupGrid) setupGrid.classList.toggle("is-text-mode", isTextMode);

  if(isTextMode){
    if(startBtn) startBtn.disabled = false;
  } else {
    if(startBtn) startBtn.disabled = !streamLive;
  }
}
$("in-mode").addEventListener("change", (e)=> applyModeUi(e.target.value));

/* ============================================================
   BUILD QUESTION QUEUE
   ============================================================ */
function buildQueue(totalWanted){
  const all = [];
  let questionNumber = 1;
  QUESTION_BANK.forEach(group=>{
    group.items.forEach(q=>{
      const audioBase = String(window.VMI_CONFIG.questionAudioBase || "").replace(/\/$/, "");
      const audio = audioBase ? audioBase + "/q" + questionNumber + ".mp3" : "";
      all.push({cat:group.cat, q, questionNumber, audio});
      questionNumber++;
    });
  });
  if(totalWanted >= all.length) return all.slice();
  const step = all.length / totalWanted;
  const picked = [];
  for(let i=0;i<totalWanted;i++){
    picked.push(all[Math.floor(i*step)]);
  }
  return picked;
}

/* ============================================================
   START INTERVIEW
   ============================================================ */
function runInterviewSetup(onCovered){
  return new Promise(function(resolve){
    const overlay = $("vmi-preparing");
    if(!overlay){ if(onCovered) onCovered(); resolve(); return; }

    const steps = Array.prototype.slice.call(overlay.querySelectorAll("[data-pstep]"));
    const bar = overlay.querySelector(".vmi-prepare__bar span");
    const minimumVisibleTime = 3000;
    const enterTime = PREFERS_REDUCED ? 60 : 540;
    const perStep = PREFERS_REDUCED ? 800 : 650;
    const fadeTime = PREFERS_REDUCED ? 0 : 460;
    let index = 0;
    let visibleAt = 0;

    overlay.classList.remove("is-visible", "is-leaving");
    overlay.setAttribute("aria-hidden", "false");
    steps.forEach(function(step){ step.classList.remove("is-active", "is-done"); });
    if(bar){ bar.style.transition = "none"; bar.style.width = "0%"; void bar.offsetWidth; bar.style.transition = ""; }
    if(window.lucide) try{ window.lucide.createIcons(); }catch(e){}

    function advance(){
      if(index > 0){
        steps[index - 1].classList.remove("is-active");
        steps[index - 1].classList.add("is-done");
      }
      if(index >= steps.length){
        const remainingTime = Math.max(0, minimumVisibleTime - (Date.now() - visibleAt));
        setTimeout(function(){
          overlay.classList.remove("is-visible");
          overlay.classList.add("is-leaving");
          overlay.setAttribute("aria-hidden", "true");
          resolve();
          setTimeout(function(){ overlay.classList.remove("is-leaving"); }, fadeTime);
        }, remainingTime);
        return;
      }
      steps[index].classList.add("is-active");
      if(bar) bar.style.width = (((index + 1) / steps.length) * 100) + "%";
      index++;
      setTimeout(advance, perStep);
    }

    /* Two paint frames guarantee that the browser sees the hidden starting
       state before transitioning the overlay over the still-visible setup. */
    requestAnimationFrame(function(){
      requestAnimationFrame(function(){
        visibleAt = Date.now();
        overlay.classList.add("is-visible");
        setTimeout(function(){
          if(onCovered) onCovered();
          advance();
        }, enterTime);
      });
    });
  });
}

$("btn-start-interview").addEventListener("click", async () => {
  if(state.interviewActive) return;
  state.mode = $("in-mode").value;
  state.destination = ($("in-destination").value || "").trim();
  let wanted = parseInt($("in-count").value,10) || FREE_LIMIT;
  if(!state.unlocked && wanted > FREE_LIMIT) wanted = FREE_LIMIT;
  state.totalPlanned = wanted;
  state.queue = buildQueue(state.totalPlanned);
  state.qIndex = 0;
  state.results = [];
  state.analysisRunning = false;
  state.interviewActive = true;
  document.documentElement.classList.add("vmi-lock");
  setAvatarState("idle");
  primeQuestionAudio((state.queue[0] || {}).audio);
  const preparing = runInterviewSetup(function(){
    hide($("screen-setup"));
    show($("screen-interview"));
  });

  const streamIsLive = state.stream && state.stream.getTracks().some(t=>t.readyState==="live");
  if(state.mode === "video" && streamIsLive){
    $("live-video").srcObject = state.stream;
    setupAudioAnalyser();
  } else if(state.mode === "video" && !streamIsLive){
    showRecError("Camera/microphone connection was lost — you can still answer using Text mode below, or refresh and re-test your devices.");
  }

  const cameraPip = document.querySelector("#vmi-page .vmi-cam-pip");
  if(cameraPip) cameraPip.classList.toggle("hidden", state.mode === "text");
  $("mode-btn-speak").classList.toggle("hidden", state.mode === "text");
  $("answer-mode-copy").textContent = state.mode === "text"
    ? "Type a clear, complete answer. Voice delivery metrics are intentionally omitted."
    : "Speak your answer, or switch to typing.";
  setAnswerMode(state.mode === "text" ? "type" : "speak");
  await preparing;
  loadQuestion();
});

/* ============================================================
   AUDIO ANALYSER for waveform + energy metric
   ============================================================ */
function setupAudioAnalyser(){
  if(!state.stream) return;
  try{
    state.audioCtx = new (window.AudioContext||window.webkitAudioContext)();
    state.analyser = state.audioCtx.createAnalyser();
    state.analyser.fftSize = 512;
    state.sourceNode = state.audioCtx.createMediaStreamSource(state.stream);
    state.sourceNode.connect(state.analyser);
    drawWaveform();
  }catch(e){ console.warn("Audio analyser unavailable", e); }
}

function drawWaveform(){
  const canvas = $("waveform");
  const ctx = canvas.getContext("2d");
  const bufferLength = state.analyser.frequencyBinCount;
  const dataArray = new Uint8Array(bufferLength);

  function loop(){
    state.rafId = requestAnimationFrame(loop);
    state.analyser.getByteTimeDomainData(dataArray);
    canvas.width = canvas.clientWidth;
    ctx.fillStyle = "#100258";
    ctx.fillRect(0,0,canvas.width,canvas.height);
    ctx.lineWidth = 2;
    ctx.strokeStyle = "#ff5e32";
    ctx.beginPath();
    const sliceWidth = canvas.width / bufferLength;
    let x = 0;
    let sumSq = 0;
    for(let i=0;i<bufferLength;i++){
      const v = dataArray[i]/128.0;
      sumSq += Math.pow(v-1,2);
      const y = v * canvas.height/2;
      if(i===0) ctx.moveTo(x,y); else ctx.lineTo(x,y);
      x += sliceWidth;
    }
    ctx.stroke();
    const rms = Math.sqrt(sumSq/bufferLength);
    const energyPct = clamp(rms*400,0,100);
    $("conf-fill").style.width = energyPct+"%";
    if(state.recognizing){
      state.volumeSamples.push(energyPct);
    }
  }
  loop();
}

/* ============================================================
   LOAD / RENDER CURRENT QUESTION
   ============================================================ */
const CATEGORY_ICONS = {
  "Personal & Academic Background":"user-round",
  "University & Course Related":"graduation-cap",
  "Country Knowledge":"globe",
  "Financial Questions":"wallet",
  "Accommodation & Travel":"plane",
  "Future Plans & Intentions":"compass"
};

/* ---- AI interviewer persona: speaking avatar + voice + typewriter ---- */
state.aiVoice = true;
let vmiQuestionAudio = null;
let vmiAudioRequest = 0;

function getQuestionAudio(){
  if(!vmiQuestionAudio){
    vmiQuestionAudio = new Audio();
    vmiQuestionAudio.preload = "auto";
  }
  return vmiQuestionAudio;
}

function stopInterviewerAudio(){
  vmiAudioRequest++;
  if(!vmiQuestionAudio) return;
  try{
    vmiQuestionAudio.pause();
    vmiQuestionAudio.currentTime = 0;
  }catch(e){}
}

/* Unlock the reusable audio element during the Start button's user gesture.
   This keeps question 1 playable after the preparation transition finishes. */
function primeQuestionAudio(audioUrl){
  if(!audioUrl) return;
  try{
    const audio = getQuestionAudio();
    const requestId = ++vmiAudioRequest;
    audio.src = audioUrl;
    audio.muted = true;
    const reset = function(){
      if(requestId !== vmiAudioRequest) return;
      try{ audio.pause(); audio.currentTime = 0; audio.muted = false; }catch(e){}
    };
    const playPromise = audio.play();
    if(playPromise && typeof playPromise.then === "function") playPromise.then(reset).catch(reset);
    setTimeout(reset, 300);
  }catch(e){}
}
function setAvatarState(s){
  const a = document.querySelector("#vmi-page .vmi-ai__avatar");
  const st = $("vmi-ai-status");
  if(a){ a.classList.remove("is-idle","is-speaking","is-listening"); a.classList.add("is-"+s); }
  if(st){ st.textContent = s === "speaking" ? "Your interviewer is asking…" : (s === "listening" ? "Listening to your answer…" : "Ready when you are"); }
}

/* Fluid voice-agent visual. It reads the same speaking/listening classes used
   by the interview flow, so the movement always follows the actual voice. */
function initVoiceAgentCanvas(){
  const canvas = $("vmi-voice-agent-canvas");
  if(!canvas || !canvas.getContext) return;
  const host = canvas.closest(".vmi-voice-agent");
  const ctx = canvas.getContext("2d");
  const TAU = Math.PI * 2;
  let width = 0, height = 0, dpr = 1;
  let energy = .16, hue = 247;

  const particles = Array.from({length:30}, function(_,i){
    return {
      angle:(i/30)*TAU,
      orbit:.29 + ((i*17)%23)/100,
      speed:.035 + ((i*11)%9)/500,
      size:1 + (i%4)*.45,
      phase:(i*1.91)%TAU
    };
  });

  function resize(rect){
    width = Math.max(1, Math.round(rect.width));
    height = Math.max(1, Math.round(rect.height));
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = Math.round(width*dpr);
    canvas.height = Math.round(height*dpr);
    ctx.setTransform(dpr,0,0,dpr,0,0);
  }

  function smoothHue(current,target,amount){
    let delta = ((target-current+540)%360)-180;
    return (current + delta*amount + 360)%360;
  }

  function drawBlob(cx,cy,radius,time,power,layer,baseHue){
    const count = 96;
    const points = [];
    const speed = .72 + layer*.16;
    for(let i=0;i<count;i++){
      const a = (i/count)*TAU;
      const organic = Math.sin(a*3 + time*speed + layer)*(.055 + power*.045) +
        Math.sin(a*5 - time*(.48+layer*.06) + layer*2.1)*(.028 + power*.035) +
        Math.sin(a*2 + time*.24)*.018;
      const voice = Math.sin(a*9 - time*3.4 + layer)*power*.022;
      const r = radius*(1 + organic + voice);
      points.push({x:cx+Math.cos(a)*r,y:cy+Math.sin(a)*r});
    }

    const first = points[0], last = points[points.length-1];
    ctx.beginPath();
    ctx.moveTo((last.x+first.x)/2,(last.y+first.y)/2);
    for(let i=0;i<points.length;i++){
      const p = points[i], n = points[(i+1)%points.length];
      ctx.quadraticCurveTo(p.x,p.y,(p.x+n.x)/2,(p.y+n.y)/2);
    }
    ctx.closePath();
    const gradient = ctx.createRadialGradient(cx-radius*.22,cy-radius*.28,radius*.08,cx,cy,radius*1.12);
    gradient.addColorStop(0,"hsla("+((baseHue+38+layer*14)%360)+",100%,76%,"+(.82-layer*.1)+")");
    gradient.addColorStop(.46,"hsla("+((baseHue+layer*24)%360)+",92%,61%,"+(.52-layer*.07)+")");
    gradient.addColorStop(1,"hsla("+((baseHue-42+layer*12+360)%360)+",88%,46%,0)");
    ctx.fillStyle = gradient;
    ctx.shadowColor = "hsla("+baseHue+",100%,65%,.38)";
    ctx.shadowBlur = 28 + power*22;
    ctx.fill();
    ctx.shadowBlur = 0;
  }

  function render(ms){
    const rect = canvas.getBoundingClientRect();
    if(rect.width < 2 || rect.height < 2){ requestAnimationFrame(render); return; }
    if(Math.abs(rect.width-width) > 1 || Math.abs(rect.height-height) > 1) resize(rect);

    const speaking = host.classList.contains("is-speaking");
    const listening = host.classList.contains("is-listening");
    const targetEnergy = speaking ? 1 : (listening ? .48 : .16);
    const targetHue = speaking ? 314 : (listening ? 168 : 247);
    energy += (targetEnergy-energy)*.065;
    hue = smoothHue(hue,targetHue,.045);
    const time = PREFERS_REDUCED ? 0 : ms/1000;
    const cx = width/2, cy = height*.46;
    const base = Math.min(width,height)*.155;

    ctx.clearRect(0,0,width,height);

    const ambient = ctx.createRadialGradient(cx,cy,base*.15,cx,cy,Math.min(width,height)*.55);
    ambient.addColorStop(0,"hsla("+hue+",95%,63%,"+(.15+energy*.08)+")");
    ambient.addColorStop(.5,"hsla("+((hue+54)%360)+",90%,50%,.055)");
    ambient.addColorStop(1,"rgba(0,0,0,0)");
    ctx.fillStyle = ambient;
    ctx.fillRect(0,0,width,height);

    ctx.globalCompositeOperation = "lighter";
    particles.forEach(function(p,i){
      const angle = p.angle + time*p.speed*(speaking ? 3.2 : 1.25);
      const orbit = Math.min(width,height)*p.orbit;
      const pulse = Math.sin(time*(.8+(i%5)*.08)+p.phase)*5*energy;
      const x = cx + Math.cos(angle)*(orbit+pulse);
      const y = cy + Math.sin(angle)*(orbit*.62+pulse*.5);
      ctx.beginPath();
      ctx.fillStyle = "hsla("+((hue+i*7)%360)+",100%,72%,"+(.14+energy*.18)+")";
      ctx.arc(x,y,p.size+energy*.7,0,TAU);
      ctx.fill();
    });

    for(let ring=0;ring<3;ring++){
      ctx.beginPath();
      ctx.strokeStyle = "hsla("+((hue+ring*32)%360)+",100%,70%,"+(.08-ring*.016+energy*.045)+")";
      ctx.lineWidth = 1;
      ctx.setLineDash([2+ring*2,8+ring*4]);
      ctx.lineDashOffset = time*(ring%2 ? 11 : -9);
      ctx.ellipse(cx,cy,base*(1.56+ring*.34),base*(1.02+ring*.23),time*.025*(ring+1),0,TAU);
      ctx.stroke();
    }
    ctx.setLineDash([]);

    const bars = 72;
    for(let i=0;i<bars;i++){
      const a = (i/bars)*TAU;
      const voiceNoise = Math.abs(Math.sin(time*(speaking?8.2:2.1)+i*.73)+Math.sin(time*3.7-i*.31)*.45);
      const amp = 4 + voiceNoise*(5+energy*15);
      const inner = base*1.23;
      ctx.beginPath();
      ctx.moveTo(cx+Math.cos(a)*inner,cy+Math.sin(a)*inner);
      ctx.lineTo(cx+Math.cos(a)*(inner+amp),cy+Math.sin(a)*(inner+amp));
      ctx.strokeStyle = "hsla("+((hue+i*1.7)%360)+",100%,74%,"+(.28+energy*.46)+")";
      ctx.lineWidth = speaking ? 2.1 : 1.35;
      ctx.lineCap = "round";
      ctx.stroke();
    }

    drawBlob(cx,cy,base*1.04,time,energy,2,hue);
    drawBlob(cx,cy,base*.9,time+1.2,energy,1,(hue+28)%360);
    drawBlob(cx,cy,base*.73,time+2.4,energy,0,(hue+62)%360);

    const core = ctx.createRadialGradient(cx-base*.16,cy-base*.2,2,cx,cy,base*.52);
    core.addColorStop(0,"rgba(255,255,255,.94)");
    core.addColorStop(.18,"hsla("+((hue+45)%360)+",100%,84%,.88)");
    core.addColorStop(1,"hsla("+hue+",95%,58%,.05)");
    ctx.beginPath();
    ctx.fillStyle = core;
    ctx.arc(cx,cy,base*(.38+energy*.035),0,TAU);
    ctx.fill();
    ctx.globalCompositeOperation = "source-over";

    requestAnimationFrame(render);
  }

  requestAnimationFrame(render);
}
initVoiceAgentCanvas();

function aiSpeak(audioUrl){
  stopInterviewerAudio();
  if(state.aiVoice === false || !audioUrl){
    setAvatarState("idle");
    return;
  }

  const requestId = vmiAudioRequest;
  try{
    const audio = getQuestionAudio();
    audio.src = audioUrl;
    audio.muted = false;
    audio.currentTime = 0;
    audio.onplaying = ()=>{ if(requestId === vmiAudioRequest) setAvatarState("speaking"); };
    audio.onended = ()=>{ if(requestId === vmiAudioRequest && !state.recognizing) setAvatarState("idle"); };
    audio.onerror = ()=>{
      if(requestId !== vmiAudioRequest) return;
      console.warn("Recorded interviewer audio could not be played:", audioUrl);
      if(!state.recognizing) setAvatarState("idle");
    };
    setAvatarState("speaking");
    const playPromise = audio.play();
    if(playPromise && typeof playPromise.catch === "function"){
      playPromise.catch(()=>{ if(requestId === vmiAudioRequest && !state.recognizing) setAvatarState("idle"); });
    }
  }catch(e){
    console.warn("Recorded interviewer audio is unavailable", e);
    if(!state.recognizing) setAvatarState("idle");
  }
}
function typeQuestion(text){
  const el = $("q-text");
  clearInterval(state.typeTimer);
  if(PREFERS_REDUCED){ el.textContent = text; return; }
  el.textContent = "";
  let i = 0;
  state.typeTimer = setInterval(function(){
    el.textContent = text.slice(0, ++i);
    if(i >= text.length) clearInterval(state.typeTimer);
  }, 24);
}
function presentQuestion(item){
  state.questionStartedAt = Date.now();
  const ic = CATEGORY_ICONS[item.cat] || "folder";
  $("q-category").innerHTML = '<i data-lucide="'+ic+'"></i> '+item.cat;
  if(window.lucide) try{ window.lucide.createIcons(); }catch(e){}
  const bubble = document.querySelector("#vmi-page .vmi-ai__bubble");
  if(bubble && bubble.animate && !PREFERS_REDUCED){
    try{ bubble.animate([{opacity:0,transform:"translateY(12px) scale(.98)"},{opacity:1,transform:"none"}],{duration:420,easing:"cubic-bezier(.2,.8,.2,1)"}); }catch(e){}
  }
  typeQuestion(item.q);
  aiSpeak(item.audio);
}

function loadQuestion(){
  hide($("feedback-card"));
  $("feedback-card").innerHTML = "";
  resetAnswerUI();

  if(state.qIndex >= state.queue.length){
    finishInterview();
    return;
  }
  presentQuestion(state.queue[state.qIndex]);
  updateProgress();
}

function updateProgress(){
  const pct = clamp((state.qIndex/state.queue.length)*100,0,100);
  $("progress-fill").style.width = pct+"%";
  $("progress-label").textContent = "Question "+(state.qIndex+1)+" of "+state.queue.length;
}

function resetAnswerUI(){
  state.recognizing = false;
  state.wantRestart = false;
  if(state.recognition && state.recognitionRunning){
    try{ state.recognition.stop(); }catch(e){}
  }
  state.finalTranscript = "";
  state.transcript = "";
  state.recStartTime = 0;
  state.recordedDurationSec = 0;
  state.volumeSamples = [];
  state.confidenceSamples = [];
  clearRecError();
  $("transcript-box").textContent = "Your spoken answer will appear here as you talk…";
  $("transcript-box").classList.add("empty");
  $("manual-answer").value = "";
  $("btn-submit-answer").disabled = true;
  hide($("btn-stop"));
  show($("btn-record"));
  hide($("rec-badge"));
  $("timer-badge").textContent = "00:00";
  clearInterval(state.timerInterval);
}

/* ============================================================
   MODE TOGGLE: speak vs type
   ============================================================ */
$("mode-btn-speak").addEventListener("click", ()=> setAnswerMode("speak"));
$("mode-btn-type").addEventListener("click", ()=> setAnswerMode("type"));

function setAnswerMode(mode){
  state.answerMode = mode;
  $("mode-btn-speak").classList.toggle("active", mode==="speak");
  $("mode-btn-type").classList.toggle("active", mode==="type");
  if(mode==="speak"){ show($("speak-ui")); hide($("type-ui")); }
  else { hide($("speak-ui")); show($("type-ui")); $("btn-submit-answer").disabled = $("manual-answer").value.trim().length < 3; }
}

$("manual-answer").addEventListener("input", (e)=>{
  $("btn-submit-answer").disabled = e.target.value.trim().length < 3;
});

/* ============================================================
   SPEECH RECOGNITION + RECORDING
   ============================================================ */
const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

function getRecognition(){
  if(!SpeechRecognition) return null;
  if(state.recognition) return state.recognition;

  const rec = new SpeechRecognition();
  rec.continuous = true;
  rec.interimResults = true;
  rec.lang = "en-US";

  rec.onstart = () => { state.recognitionRunning = true; };

  rec.onresult = (e) => {
    let interim = "";
    let final = state.finalTranscript;
    for(let i=e.resultIndex;i<e.results.length;i++){
      const res = e.results[i];
      if(res.isFinal){
        final += res[0].transcript + " ";
        if(typeof res[0].confidence === "number" && res[0].confidence > 0){
          state.confidenceSamples.push(res[0].confidence);
        }
      } else {
        interim += res[0].transcript;
      }
    }
    state.finalTranscript = final;
    const combined = (final+interim).trim();
    $("transcript-box").textContent = combined || "Listening…";
    $("transcript-box").classList.toggle("empty", combined.length===0);
    $("btn-submit-answer").disabled = combined.trim().length < 3;
  };

  rec.onerror = (e) => {
    console.warn("Speech recognition error", e.error);
    if(e.error === "not-allowed" || e.error === "service-not-allowed"){
      state.wantRestart = false;
      showRecError("Microphone access was blocked by the browser. Please allow microphone access and click Start recording again, or switch to Type mode.", "error");
    } else if(e.error === "audio-capture"){
      state.wantRestart = false;
      showRecError("No microphone could be found. Check your device connection or switch to Type mode.", "error");
    } else if(e.error === "no-speech"){
      /* benign — recognition will restart via onend if still recording */
    } else if(e.error === "network"){
      showRecError("Speech recognition lost its network connection. You can keep speaking — it will try to reconnect — or switch to Type mode.");
    }
  };

  rec.onend = () => {
    state.recognitionRunning = false;
    if(state.wantRestart && state.recognizing){
      try{
        rec.start();
      }catch(err){
        console.warn("Recognition restart failed", err);
      }
    }
  };

  state.recognition = rec;
  state.recognitionReady = true;
  return rec;
}

function safeStartRecognition(rec){
  try{
    rec.start();
  }catch(err){
    if(err && err.name === "InvalidStateError"){
      try{ rec.stop(); }catch(e2){}
      setTimeout(()=>{
        if(state.recognizing){
          try{ rec.start(); }catch(e3){ console.warn(e3); }
        }
      }, 150);
    } else {
      console.warn("Could not start speech recognition", err);
      showRecError("Couldn't start voice transcription. You can keep speaking and type your answer instead, or click Start recording again.");
    }
  }
}

$("btn-record").addEventListener("click", startRecording);
$("btn-stop").addEventListener("click", stopRecording);

function startRecording(){
  clearRecError();

  if(state.mode === "video" && (!state.stream || !state.stream.getTracks().some(t=>t.readyState==="live"))){
    showRecError("Your camera/microphone connection isn't active. Please switch to Text mode, or refresh and re-test your devices from the setup screen.");
    return;
  }

  state.recognizing = true;
  state.wantRestart = true;
  state.finalTranscript = "";
  state.volumeSamples = [];
  state.confidenceSamples = [];
  hide($("btn-record")); show($("btn-stop")); show($("rec-badge"));
  stopInterviewerAudio();
  clearInterval(state.typeTimer);
  setAvatarState("listening");
  $("transcript-box").textContent = "Listening…";
  $("transcript-box").classList.remove("empty");
  $("transcript-box").classList.add("is-live");

  if(SpeechRecognition){
    const rec = getRecognition();
    if(rec && !state.recognitionRunning){
      safeStartRecognition(rec);
    }
  } else {
    $("transcript-box").textContent = "Live transcription isn't supported in this browser — please switch to Type instead, or continue speaking and describe your answer in the box after stopping.";
  }

  state.recStartTime = Date.now();
  state.timerInterval = setInterval(()=>{
    $("timer-badge").textContent = fmtTime((Date.now()-state.recStartTime)/1000);
  },250);

}

function stopRecording(){
  state.recognizing = false;
  state.wantRestart = false;
  hide($("btn-stop")); show($("btn-record")); hide($("rec-badge"));
  setAvatarState("idle");
  $("transcript-box").classList.remove("is-live");
  clearInterval(state.timerInterval);
  if(state.recStartTime) state.recordedDurationSec = (Date.now()-state.recStartTime)/1000;
  if(state.recognition){
    try{ state.recognition.stop(); }catch(e){}
  }
  const finalText = (state.finalTranscript || $("transcript-box").textContent || "").trim();
  if(finalText && finalText !== "Listening…"){
    $("btn-submit-answer").disabled = finalText.length < 3;
  }
}

/* ============================================================
   LOCAL SIGNAL METRICS
   ============================================================ */
function computeLocalMetrics(transcript, durationSec){
  const words = transcript.trim().split(/\s+/).filter(Boolean);
  const wordCount = words.length;
  const wpm = durationSec>0 ? (wordCount/(durationSec/60)) : 0;
  const fillerRegex = /\b(um+|uh+|like|you know|basically|actually|so yeah)\b/gi;
  const fillerMatches = transcript.match(fillerRegex) || [];
  const fillerRate = wordCount>0 ? fillerMatches.length/wordCount : 0;

  let paceScore;
  if(wpm===0) paceScore = 5;
  else if(wpm>=100 && wpm<=160) paceScore = 9;
  else if(wpm>=80 && wpm<220) paceScore = 7;
  else paceScore = 5;

  const fillerScore = clamp(10 - fillerRate*40, 2, 10);

  const avgEnergy = state.volumeSamples.length
    ? state.volumeSamples.reduce((a,b)=>a+b,0)/state.volumeSamples.length : 0;
  const energyVariance = state.volumeSamples.length
    ? Math.sqrt(state.volumeSamples.reduce((a,b)=>a+Math.pow(b-avgEnergy,2),0)/state.volumeSamples.length) : 0;
  const energyScore = clamp((avgEnergy/45)*10, 2, 10);
  const dynamismScore = clamp((energyVariance/12)*10, 2, 10);

  const avgConfidence = state.confidenceSamples.length
    ? state.confidenceSamples.reduce((a,b)=>a+b,0)/state.confidenceSamples.length : 0;
  const pronunciationScore = avgConfidence > 0
    ? clamp(round1(avgConfidence*10), 2, 10)
    : null;

  return {
    wordCount, durationSec: Math.round(durationSec), wpm: Math.round(wpm),
    fillerCount: fillerMatches.length, fillerScore: round1(fillerScore),
    paceScore: round1(paceScore), energyScore: round1(energyScore), dynamismScore: round1(dynamismScore),
    pronunciationScore, hasVoiceMetrics: state.answerMode === "speak" && state.volumeSamples.length >= 8
  };
}
function round1(n){ return Math.round(n*10)/10; }

/* ============================================================
   CAPTURE ANSWER -> AI ASSESSMENT RUNS AFTER THE FINAL QUESTION
   ============================================================ */
$("btn-submit-answer").addEventListener("click", submitAnswer);
$("btn-skip").addEventListener("click", ()=>{
  state.qIndex++;
  loadQuestion();
});
$("btn-retry-ai-analysis").addEventListener("click", finishInterview);

function submitAnswer(){
  const item = state.queue[state.qIndex];
  let answerText;
  if(state.answerMode === "type"){
    answerText = $("manual-answer").value.trim();
  } else {
    answerText = (state.finalTranscript || $("transcript-box").textContent || "").trim();
    if(answerText === "Listening…") answerText = "";
  }
  if(!answerText || answerText.length < 3) return;

  const durationSec = state.recordedDurationSec || (state.recStartTime
    ? (Date.now()-state.recStartTime)/1000
    : state.questionStartedAt
      ? (Date.now()-state.questionStartedAt)/1000
      : answerText.split(" ").length/2.3);
  const localMetrics = computeLocalMetrics(answerText, durationSec);

  clearRecError();
  state.results.push({
    category:item.cat, question:item.q, answer:answerText,
    scores:null, feedback:null, overall:null,
    local:localMetrics, assessmentEngine:"pending-ai"
  });
  state.qIndex++;
  loadQuestion();
}

async function requestAiBatchAssessment(results){
  if(!CFG.assessBatchUrl) throw new Error("The assessment service is not configured.");
  const controller = "AbortController" in window ? new AbortController() : null;
  const timer = controller ? setTimeout(function(){ controller.abort(); }, 600000) : null;
  try{
    const response = await fetch(CFG.assessBatchUrl, {
      method:"POST",
      headers:{
        "Content-Type":"application/json",
        "Accept":"application/json",
        "X-Requested-With":"XMLHttpRequest",
        "X-CSRF-TOKEN":CFG.csrf || ""
      },
      signal:controller ? controller.signal : undefined,
      body:JSON.stringify({
        answers:results.map(function(result){
          return {
            question:result.question,
            answer:result.answer,
            category:result.category,
            mode:state.mode,
            destination:state.destination || null,
            metrics:{
              wordCount:result.local.wordCount,
              durationSec:result.local.durationSec,
              wpm:result.local.wpm,
              fillerCount:result.local.fillerCount
            }
          };
        })
      })
    });
    if(!response.ok){
      const errorPayload = await response.json().catch(function(){ return {}; });
      throw new Error(errorPayload.message || "The assessment service is temporarily unavailable. Please retry shortly.");
    }
    const payload = await response.json();
    if(!payload || !payload.ok || !Array.isArray(payload.assessments) || payload.assessments.length !== results.length){
      throw new Error("The assessment service returned an incomplete report. Please retry the analysis.");
    }
    return payload;
  }catch(error){
    if(error && error.name === "AbortError"){
      throw new Error("Report generation exceeded ten minutes. Please retry the analysis.");
    }
    throw error;
  }finally{
    if(timer) clearTimeout(timer);
  }
}

function mergeScores(ai, local){
  const s = Object.assign({}, ai.scores);
  if(local.hasVoiceMetrics){
    s.fluency = s.fluency!=null ? round1((s.fluency*0.6 + local.paceScore*0.4)) : local.paceScore;
    s.confidence = s.confidence!=null ? round1((s.confidence*0.72 + local.energyScore*0.28)) : local.energyScore;
    s.tone = s.tone!=null ? round1((s.tone*0.7 + local.dynamismScore*0.3)) : local.dynamismScore;
    s.pronunciation = local.pronunciationScore!=null ? round1(local.pronunciationScore) : null;
  }else{
    s.fluency = state.mode === "text" ? null : (s.fluency ?? null);
    s.tone = null;
    s.pronunciation = null;
  }
  s.language = s.language!=null ? round1((s.language*0.85 + local.fillerScore*0.15)) : local.fillerScore;

  const contentFields = ["language","grammar","confidence","clarity","crispness","relevance","courseKnowledge","universityKnowledge","countryKnowledge","financialAwareness"];
  const contentValues = contentFields.map(k=>s[k]).filter(v=>typeof v === "number");
  const contentOverall = typeof s.overall === "number"
    ? s.overall
    : (contentValues.length ? contentValues.reduce((a,b)=>a+b,0)/contentValues.length : 0);
  const deliveryValues = [s.fluency,s.tone,s.pronunciation].filter(v=>typeof v === "number");
  const overall = deliveryValues.length
    ? round1(contentOverall*0.76 + (deliveryValues.reduce((a,b)=>a+b,0)/deliveryValues.length)*0.24)
    : round1(contentOverall);
  s.overall = overall;

  return {scores:s, overall, feedback:ai, local};
}

function escapeHtml(str){
  const d = document.createElement("div");
  d.textContent = str;
  return d.innerHTML;
}

/* score chip with animated bar */
function chipHtml(k, v){
  const pct = clamp(v*10, 0, 100);
  const cls = scoreClass(v);
  return '<div class="score-chip">'+
    '<div class="chip-top"><span class="lbl">'+labelFor(k)+'</span><span class="val '+cls+'">'+v+'</span></div>'+
    '<div class="chip-bar"><span class="chip-bar__fill '+cls+'" style="--w:'+pct+'%"></span></div>'+
  '</div>';
}

/* ============================================================
   FINISH INTERVIEW -> BUILD FINAL REPORT
   ============================================================ */
const COMMUNICATION_FIELDS = ["language","grammar","fluency","pronunciation","confidence","tone","clarity","crispness"];
const CONTENT_FIELDS = ["relevance","courseKnowledge","universityKnowledge","countryKnowledge","financialAwareness"];
let analysisEstimateTimer = null;

function reportStatHtml(icon, label, value, note){
  return '<div class="report-stat">'+
    '<span class="report-stat__icon"><i data-lucide="'+icon+'"></i></span>'+
    '<span class="report-stat__label">'+escapeHtml(label)+'</span>'+
    '<strong class="report-stat__value">'+escapeHtml(value)+'</strong>'+
    '<small class="report-stat__note">'+escapeHtml(note)+'</small>'+
  '</div>';
}

function buildReportAnalytics(results, plannedQuestions){
  const answered = results.length;
  const planned = Math.max(answered, plannedQuestions || answered);
  const totalWords = results.reduce((sum,result)=>sum+(Number(result.local && result.local.wordCount)||0),0);
  const totalDuration = results.reduce((sum,result)=>sum+(Number(result.local && result.local.durationSec)||0),0);
  const fillerTotal = results.reduce((sum,result)=>sum+(Number(result.local && result.local.fillerCount)||0),0);
  const scores = results.map(result=>Number(result.overall)||0);
  const scoreMean = scores.length ? scores.reduce((sum,score)=>sum+score,0)/scores.length : 0;
  const deviation = scores.length
    ? Math.sqrt(scores.reduce((sum,score)=>sum+Math.pow(score-scoreMean,2),0)/scores.length)
    : 0;
  const consistency = deviation<=0.7 ? "Highly consistent" : deviation<=1.3 ? "Generally consistent" : "Variable performance";
  const distribution = {strong:0, developing:0, focus:0};
  results.forEach(result=>{
    if(result.overall>=7.5) distribution.strong++;
    else if(result.overall>=5.5) distribution.developing++;
    else distribution.focus++;
  });

  const categories = {};
  results.forEach(result=>{
    const name = result.category || "Other questions";
    if(!categories[name]) categories[name] = [];
    categories[name].push(Number(result.overall)||0);
  });
  const categoryPerformance = Object.entries(categories).map(([name,scoresInCategory])=>({
    name,
    count:scoresInCategory.length,
    average:round1(scoresInCategory.reduce((sum,score)=>sum+score,0)/scoresInCategory.length)
  })).sort((a,b)=>b.average-a.average);
  const rankedAnswers = results.slice().sort((a,b)=>b.overall-a.overall);

  return {
    answered,
    planned,
    coveragePct:planned ? Math.round((answered/planned)*100) : 0,
    totalWords,
    avgWords:answered ? Math.round(totalWords/answered) : 0,
    avgDuration:answered ? Math.round(totalDuration/answered) : 0,
    fillerTotal,
    deviation:round1(deviation),
    consistency,
    distribution,
    categoryPerformance,
    strongestAnswer:rankedAnswers[0] || null,
    priorityAnswer:rankedAnswers[rankedAnswers.length-1] || null
  };
}

function renderReportAnalytics(analytics){
  $("report-stats").innerHTML = [
    reportStatHtml("list-checks","Answer coverage",analytics.answered+" / "+analytics.planned,analytics.coveragePct+"% of planned questions completed"),
    reportStatHtml("timer","Average response",fmtTime(analytics.avgDuration),"Measured from question display to submission"),
    reportStatHtml("align-left","Average answer length",analytics.avgWords+" words","Average detail per completed response"),
    reportStatHtml("file-text","Total words",String(analytics.totalWords),"Across all submitted answers"),
    reportStatHtml("message-circle-more","Filler words",String(analytics.fillerTotal),"Lower is generally better"),
    reportStatHtml("activity","Score consistency",analytics.consistency,"Score deviation ±"+analytics.deviation)
  ].join("");

  const total = Math.max(1, analytics.answered);
  const strongPct = Math.round((analytics.distribution.strong/total)*100);
  const developingPct = Math.round((analytics.distribution.developing/total)*100);
  const focusPct = Math.max(0,100-strongPct-developingPct);
  $("report-distribution").innerHTML =
    '<div class="report-distribution__bar" role="img" aria-label="'+analytics.distribution.strong+' strong, '+analytics.distribution.developing+' developing, and '+analytics.distribution.focus+' answers needing focus">'+
      '<span class="is-strong" style="width:'+strongPct+'%"></span>'+
      '<span class="is-developing" style="width:'+developingPct+'%"></span>'+
      '<span class="is-focus" style="width:'+focusPct+'%"></span>'+
    '</div>'+
    '<div class="report-distribution__legend">'+
      '<span><i class="is-strong"></i>Strong (7.5–10)<b>'+analytics.distribution.strong+'</b></span>'+
      '<span><i class="is-developing"></i>Developing (5.5–7.4)<b>'+analytics.distribution.developing+'</b></span>'+
      '<span><i class="is-focus"></i>Needs focus (0–5.4)<b>'+analytics.distribution.focus+'</b></span>'+
    '</div>';

  const strongest = analytics.strongestAnswer;
  const priority = analytics.priorityAnswer;
  const leadingCategory = analytics.categoryPerformance[0] || null;
  $("report-executive").innerHTML = [
    strongest ? '<div class="report-insight"><span>Strongest answer</span><strong>'+escapeHtml(strongest.question)+'</strong><small>'+strongest.overall.toFixed(1)+'/10 · '+escapeHtml((strongest.feedback && strongest.feedback.good) || "Strong overall response")+'</small></div>' : '',
    priority ? '<div class="report-insight"><span>Priority answer</span><strong>'+escapeHtml(priority.question)+'</strong><small>'+priority.overall.toFixed(1)+'/10 · '+escapeHtml((priority.feedback && priority.feedback.improve) || "Add more specific supporting detail")+'</small></div>' : '',
    leadingCategory ? '<div class="report-insight"><span>Leading category</span><strong>'+escapeHtml(leadingCategory.name)+'</strong><small>'+leadingCategory.average.toFixed(1)+'/10 across '+leadingCategory.count+' answer'+(leadingCategory.count===1?'':'s')+'</small></div>' : ''
  ].join("");

  $("report-categories").innerHTML = analytics.categoryPerformance.map(category=>
    '<div class="report-category">'+
      '<div class="report-category__name"><strong>'+escapeHtml(category.name)+'</strong><small>'+category.count+' answer'+(category.count===1?'':'s')+'</small></div>'+
      '<div class="report-category__track"><span style="width:'+clamp(category.average*10,0,100)+'%"></span></div>'+
      '<div class="report-category__score">'+category.average.toFixed(1)+'</div>'+
    '</div>'
  ).join("") || '<p class="report-section-note">No category data is available.</p>';
}

function renderAnswerReview(results){
  $("report-answer-review").innerHTML = results.map((result,index)=>{
    const feedback = result.feedback || {};
    return '<details class="report-answer"'+(index===0?' open':'')+'>'+
      '<summary><span class="report-answer__title"><small>Question '+(index+1)+' · '+escapeHtml(result.category || "Interview question")+'</small><strong>'+escapeHtml(result.question)+'</strong></span><span class="report-answer__score">'+result.overall.toFixed(1)+'/10</span></summary>'+
      '<div class="report-answer__body">'+
        '<div class="report-answer__response"><b>Your response</b>'+escapeHtml(result.answer)+'</div>'+
        '<div class="report-answer__feedback">'+
          '<div><b>What worked</b>'+escapeHtml(feedback.good || "The response addressed the question.")+'</div>'+
          '<div><b>Improve next</b>'+escapeHtml(feedback.improve || feedback.mistakes || "Add one specific supporting detail.")+'</div>'+
        '</div>'+
        '<div class="report-answer__sample"><b>Stronger answer structure</b>'+escapeHtml(feedback.betterAnswer || "Give a direct answer, add one verifiable detail, and connect it to your study plan.")+'</div>'+
      '</div>'+
    '</details>';
  }).join("") || '<p class="report-section-note">No completed answers are available.</p>';
}

function formatAnalysisTime(seconds){
  const total = Math.max(1, Math.ceil(seconds));
  const minutes = Math.floor(total/60);
  const remainder = total%60;
  if(minutes && remainder) return minutes+" min "+remainder+" sec";
  if(minutes) return minutes+" min";
  return total+" sec";
}

function stopAnalysisEstimate(message){
  if(analysisEstimateTimer){
    clearInterval(analysisEstimateTimer);
    analysisEstimateTimer = null;
  }
  const timeLabel = $("vmi-analysis-time");
  if(timeLabel && message) timeLabel.textContent = message;
}

function startAnalysisEstimate(answerCount){
  stopAnalysisEstimate();
  const timeLabel = $("vmi-analysis-time");
  if(!timeLabel) return;
  // The cloud assessor answers in a couple of seconds; the on-server fallback
  // is far slower, so the estimate depends on which one is the primary.
  const fast = !!CFG.fastAssessor;
  const perAnswer = fast ? 1.4 : 13;
  const base = fast ? 3 : 8;
  const floor = fast ? 4 : 20;
  const estimatedSeconds = Math.max(floor, Math.round(answerCount*perAnswer + base));
  const startedAt = Date.now();
  const deadline = startedAt+(estimatedSeconds*1000);
  const refresh = function(){
    const now = Date.now();
    const remaining = Math.ceil((deadline-now)/1000);
    const elapsed = Math.max(0, Math.floor((now-startedAt)/1000));
    if(remaining>0){
      timeLabel.textContent = "Approximately "+formatAnalysisTime(remaining)+" remaining";
    } else {
      // Ran past the estimate — usually the on-server fallback is finishing the job.
      timeLabel.textContent = "Still working… "+formatAnalysisTime(elapsed)+" elapsed";
    }
  };
  refresh();
  analysisEstimateTimer = setInterval(refresh, 1000);
}

function startAnalyzing(){
  const overlay = $("vmi-analyzing");
  if(!overlay) return;
  show($("screen-interview"));
  show(overlay);
  hide($("btn-retry-ai-analysis"));
  overlay.querySelector("h2").textContent = "Analysing your interview";
  overlay.querySelector("p").textContent = "Your AI assessor is preparing to review every saved answer…";
  stopAnalysisEstimate("Calculating estimated completion time…");
  overlay.querySelectorAll("[data-astep]").forEach(function(step){
    step.classList.remove("is-active","is-done");
  });
  const first = overlay.querySelector('[data-astep="transcribe"]');
  if(first) first.classList.add("is-active");
  const bar = overlay.querySelector(".vmi-analyze__bar span");
  if(bar){ bar.style.transition = "width .45s ease"; bar.style.width = "0%"; }
  if(window.lucide) try{ window.lucide.createIcons(); }catch(e){}
}

function updateAnalyzingProgress(message, percent){
  const overlay = $("vmi-analyzing");
  if(!overlay) return;
  overlay.querySelector("p").textContent = message;
  const bar = overlay.querySelector(".vmi-analyze__bar span");
  if(bar) bar.style.width = clamp(percent, 0, 100)+"%";
}

async function assessSavedAnswers(){
  const pending = state.results.filter(function(result){ return result.assessmentEngine !== "ai-batch"; });
  if(!pending.length){
    updateAnalyzingProgress("All answered questions are already analysed. Building your report…", 100);
    stopAnalysisEstimate("Finalising your report…");
    return;
  }

  startAnalysisEstimate(pending.length);
  updateAnalyzingProgress("Submitting "+pending.length+" answers for a comprehensive assessment…", 12);
  const payload = await requestAiBatchAssessment(pending);
  const assessments = payload.assessments;
  stopAnalysisEstimate(payload.assessor ? "Assessed by "+payload.assessor : "Finalising your report…");
  updateAnalyzingProgress((payload.assessor ? "Assessed by "+payload.assessor+". Merging" : "AI review complete. Merging")+" communication and delivery results…", 88);

  pending.forEach(function(result, index){
    const combined = mergeScores(assessments[index], result.local);
    result.scores = combined.scores;
    result.feedback = combined.feedback;
    result.overall = combined.overall;
    result.assessmentEngine = "ai-batch";
  });

  const overlay = $("vmi-analyzing");
  if(overlay){
    overlay.querySelector("p").textContent = "All "+state.results.length+" answers have been assessed. Preparing your report…";
    overlay.querySelectorAll("[data-astep]").forEach(function(step){
      if(step.style.display !== "none"){
        step.classList.remove("is-active");
        step.classList.add("is-done");
      }
    });
    const bar = overlay.querySelector(".vmi-analyze__bar span");
    if(bar) bar.style.width = "100%";
  }
}

async function finishInterview(){
  if(state.analysisRunning) return;
  state.analysisRunning = true;
  state.interviewActive = false;
  stopInterviewerAudio();
  clearInterval(state.typeTimer);
  releaseMediaStream();

  startAnalyzing();
  try{
    await assessSavedAnswers();
    await new Promise(function(resolve){ setTimeout(resolve, PREFERS_REDUCED ? 50 : 350); });
  }catch(error){
    console.error("End-of-interview AI analysis failed", error);
    stopAnalysisEstimate("Analysis paused. Your completed answers remain saved.");
    const overlay = $("vmi-analyzing");
    if(overlay){
      overlay.querySelector("h2").textContent = "AI analysis paused";
      overlay.querySelector("p").textContent = (error && error.message ? error.message : "The assessment service could not complete the report.")+" Your completed answers remain saved; retry will continue from the remaining answers.";
    }
    show($("btn-retry-ai-analysis"));
    state.analysisRunning = false;
    return;
  }

  document.documentElement.classList.remove("vmi-lock");
  hide($("screen-interview"));
  $("vmi-analyzing").classList.add("hidden");
  // The landing hero is not relevant on the results view — hide it so the report
  // stands alone. "New mock interview" does a full reload, which restores it.
  var vmiHero = document.querySelector(".vmi-hero");
  if(vmiHero) vmiHero.style.display = "none";
  var restartFab = document.getElementById("vmi-restart-fab");
  if(restartFab) restartFab.classList.remove("hidden");
  show($("screen-report"));
  window.scrollTo({top:0, behavior:"smooth"});
  $("report-sub").textContent = state.results.length+" questions answered"+(state.destination ? " for "+state.destination : "")+" — assessment complete";
  state.analysisRunning = false;

  const allFieldTotals = {};
  const mistakeGroups = {};
  state.results.forEach(r=>{
    Object.entries(r.scores).forEach(([k,v])=>{
      if(v==null) return;
      if(!allFieldTotals[k]) allFieldTotals[k] = [];
      allFieldTotals[k].push(v);
    });
    if(r.feedback.mistakes){
      if(!mistakeGroups[r.feedback.mistakes]) mistakeGroups[r.feedback.mistakes] = [];
      mistakeGroups[r.feedback.mistakes].push(r.question);
    }
  });
  const mistakes = Object.entries(mistakeGroups)
    .sort((a,b)=> b[1].length - a[1].length)
    .map(([text,qs])=> qs.length>1 ? text+" ("+qs.length+" of your answers, e.g. \""+qs[0]+"\")" : text);

  const fieldAverages = {};
  Object.entries(allFieldTotals).forEach(([k,arr])=>{
    fieldAverages[k] = round1(arr.reduce((a,b)=>a+b,0)/arr.length);
  });

  const overallAvg = state.results.length
    ? round1(state.results.reduce((a,r)=>a+r.overall,0)/state.results.length) : 0;
  const overallScore100 = Math.round(overallAvg*10);

  const sorted = Object.entries(fieldAverages)
    .filter(([k])=> k!=="overall")
    .sort((a,b)=>b[1]-a[1]);
  const strongest = sorted.slice(0,3);
  const weakest = sorted.slice(-3).reverse();
  const weakestFive = sorted.slice(-5).reverse();

  renderRing(overallScore100);
  renderReadinessTag(overallScore100);
  $("readiness-score-pill").textContent = overallAvg.toFixed(1)+" / 10";
  $("readiness-score-note").textContent = overallAvg>=7.5
    ? "You're demonstrating strong visa-interview readiness."
    : overallAvg>=5.5
      ? "You're on the right track, but a few areas need sharpening before the real interview."
      : "Meaningful practice is recommended before your actual visa interview.";

  const analytics = buildReportAnalytics(state.results, state.totalPlanned);
  renderReportAnalytics(analytics);
  renderAnswerReview(state.results);

  $("report-communication").innerHTML = COMMUNICATION_FIELDS
    .filter(k=>fieldAverages[k]!=null)
    .map(k=>chipHtml(k, fieldAverages[k]))
    .join("") || "<p>Not enough data yet.</p>";

  $("report-content").innerHTML = CONTENT_FIELDS
    .filter(k=>fieldAverages[k]!=null)
    .map(k=>chipHtml(k, fieldAverages[k]))
    .join("") || '<p class="report-section-note">No knowledge-specific scores were available for this question set.</p>';

  const aiStrengths = Array.from(new Set(state.results.map(r=>r.feedback && r.feedback.good).filter(Boolean))).slice(0,3);
  const aiImprovements = Array.from(new Set(state.results.map(r=>r.feedback && r.feedback.improve).filter(Boolean))).slice(0,4);
  $("report-strong").innerHTML = strongest.map(([k,v])=>'<li><strong>'+labelFor(k)+'</strong> — averaging '+v+'/10</li>').join("")+aiStrengths.map(t=>'<li>'+escapeHtml(t)+'</li>').join("") || "<li>Not enough data yet.</li>";
  $("report-weak").innerHTML = weakest.map(([k,v])=>'<li><strong>'+labelFor(k)+'</strong> — averaging '+v+'/10</li>').join("")+aiImprovements.map(t=>'<li>'+escapeHtml(t)+'</li>').join("") || "<li>Not enough data yet.</li>";

  $("report-mistakes").innerHTML = mistakes.length
    ? mistakes.slice(0,6).map(m=>'<li>'+escapeHtml(m)+'</li>').join("")
    : "<li>No major recurring mistakes noticed — good consistency.</li>";

  const weakQuestions = state.results.filter(r=>r.overall < 6.5).sort((a,b)=>a.overall-b.overall);
  $("report-weak-questions").innerHTML = weakQuestions.length
    ? weakQuestions.map(r=>{
        const reason = r.feedback.mistakes || r.feedback.improve || "This answer could be stronger.";
        return '<li><strong>'+escapeHtml(r.question)+'</strong> — scored '+r.overall.toFixed(1)+'/10. '+escapeHtml(reason)+'</li>';
      }).join("")
    : "<li>No question stood out as needing extra practice — solid, consistent performance throughout.</li>";

  const askedQs = new Set(state.results.map(r=>r.question));
  const weakCats = weakestFive.map(w=>categoryForField(w[0])).filter(Boolean);
  const practicePool = [];
  QUESTION_BANK.forEach(group=>{
    if(weakCats.includes(group.cat)){
      group.items.forEach(q=>{ if(!askedQs.has(q)) practicePool.push(q); });
    }
  });
  $("report-practice").innerHTML = (practicePool.slice(0,6).length ? practicePool.slice(0,6) : QUESTION_BANK[0].items.slice(0,4))
    .map(q=>'<li>'+escapeHtml(q)+'</li>').join("");

  $("report-plan").innerHTML = buildPlan(weakestFive, overallScore100);

  const readinessPct = clamp(Math.round(overallAvg*10), 0, 100);
  const summaryTone = overallScore100>=85
    ? "excellent shape for the real interview"
    : overallScore100>=70
      ? "good shape, with a handful of areas to polish"
      : overallScore100>=55
        ? "a reasonable foundation, but you'll benefit from focused practice"
        : "an early stage of practice, with substantial work still needed";
  $("report-summary").textContent =
    "Across "+state.results.length+" questions, you're in "+summaryTone+". "+
    "Your estimated Visa Interview Readiness is "+readinessPct+"%. "+
    "Focus your next practice sessions on the weakest areas above, use the recommended sample answers as a guide, "+
    "and work through the suggested practice questions to build consistency before your real interview.";

  state._reportData = {overallScore100, fieldAverages, strongest, weakest, mistakes, overallAvg, readinessPct, analytics};

  if(window.lucide) try{ window.lucide.createIcons(); }catch(e){}
  if(overallScore100 >= 75) setTimeout(celebrate, 450);
}

function categoryForField(field){
  const map = {
    courseKnowledge:"University & Course Related", universityKnowledge:"University & Course Related",
    countryKnowledge:"Country Knowledge", financialAwareness:"Financial Questions",
    tone:"Personal & Academic Background", confidence:"Personal & Academic Background",
    pronunciation:"Personal & Academic Background",
    clarity:"Future Plans & Intentions", crispness:"Future Plans & Intentions", relevance:"Future Plans & Intentions"
  };
  return map[field] || null;
}

function labelFor(key){
  const labels = {
    language:"Language", grammar:"Grammar", fluency:"Fluency", pronunciation:"Pronunciation",
    confidence:state.mode === "video" ? "Delivery confidence" : "Answer confidence", tone:"Tone", clarity:"Clarity",
    crispness:"Crispness", relevance:"Relevance", courseKnowledge:"Course knowledge",
    universityKnowledge:"University knowledge", countryKnowledge:"Country knowledge",
    financialAwareness:"Financial awareness"
  };
  return labels[key] || key;
}

function renderRing(score100){
  const circumference = 2*Math.PI*86;
  const offset = circumference - (score100/100)*circumference;
  const prog = $("ring-progress");
  prog.setAttribute("stroke-dasharray", circumference.toFixed(1));
  prog.setAttribute("stroke-dashoffset", circumference.toFixed(1));
  const numEl = $("ring-num");

  if(PREFERS_REDUCED){
    prog.setAttribute("stroke-dashoffset", offset.toFixed(1));
    numEl.textContent = score100;
    return;
  }

  requestAnimationFrame(()=>{
    prog.style.transition = "stroke-dashoffset 1.2s cubic-bezier(.2,.8,.2,1)";
    prog.setAttribute("stroke-dashoffset", offset.toFixed(1));
  });

  const dur = 1200; let start = null;
  function tick(ts){
    if(start === null) start = ts;
    const p = Math.min((ts-start)/dur, 1);
    const eased = 1 - Math.pow(1-p, 3);
    numEl.textContent = Math.round(score100*eased);
    if(p < 1) requestAnimationFrame(tick);
    else numEl.textContent = score100;
  }
  requestAnimationFrame(tick);
}

function renderReadinessTag(score100){
  const tag = $("readiness-tag");
  let cls, txt;
  if(score100>=90){cls="rt-excellent"; txt="Excellent — Visa Ready ("+score100+"%)";}
  else if(score100>=75){cls="rt-good"; txt="Good — Nearly there ("+score100+"%)";}
  else if(score100>=60){cls="rt-moderate"; txt="Moderate — Keep practicing ("+score100+"%)";}
  else {cls="rt-needs"; txt="Needs Improvement ("+score100+"%)";}
  tag.className = "readiness-tag "+cls;
  tag.textContent = txt;
}

function buildPlan(weakestFive, score100){
  if(!weakestFive.length) return "<p>Complete a few more questions to generate a tailored plan.</p>";
  const items = weakestFive.slice(0,5).map(([k,v])=>{
    const tips = {
      countryKnowledge:"Spend 20 minutes reading your destination country's official study-visa page and note 3 facts about your city (cost of living, public transport, one landmark).",
      financialAwareness:"Write down your exact tuition fee, living cost estimate, and funding source in one sentence each — practice saying the numbers out loud without hesitation.",
      universityKnowledge:"Visit your university's website and memorize its ranking, one notable faculty member or research area, and why it fits your course.",
      courseKnowledge:"List 3 modules from your course and connect each one to your career goal in a single sentence.",
      fluency:"Practice answering out loud daily, aiming for a steady, natural pace — record yourself and check you're not rushing.",
      pronunciation:"Slow down on key words (names, numbers, university/course titles) and articulate each syllable clearly.",
      confidence:"Rehearse answers without a script, then say them again from memory — confidence comes from familiarity, not memorization.",
      clarity:"Structure answers as: direct answer first, then one supporting reason, then stop — avoid rambling.",
      crispness:"Time yourself — aim to answer most questions in under 30-40 seconds.",
      tone:"Vary your pitch slightly on key facts (fees, dates, university name) instead of speaking in a flat monotone.",
      relevance:"Before answering, silently repeat the question to yourself so your answer stays on-topic.",
      grammar:"Read your answers back after typing them once, fixing tense and article (a/the) errors.",
      language:"Reduce filler words (um, like, basically) by pausing silently instead of filling gaps with sound."
    };
    return '<li><strong>'+labelFor(k)+' ('+v+'/10):</strong> '+(tips[k]||"Practice this specifically in your next session.")+'</li>';
  }).join("");
  const overall = score100>=75
    ? "You're close to interview-ready. Focus on the few weak spots below over the next 2-3 practice sessions."
    : "You have a solid foundation but need focused practice. Revisit the weak areas below daily for the next week before your real interview.";
  return '<p>'+overall+'</p><ul class="plain">'+items+'</ul>';
}

/* ============================================================
   DOWNLOAD: PDF report
   ============================================================ */
$("btn-download-pdf").addEventListener("click", downloadPdfReport);
$("btn-restart").addEventListener("click", ()=>{ releaseMediaStream(); location.reload(); });
const restartFabEl = document.getElementById("vmi-restart-fab");
if(restartFabEl) restartFabEl.addEventListener("click", ()=>{ releaseMediaStream(); location.reload(); });

// This script block is @verbatim, so the text arrives via window.VMI_CONFIG.
const AI_DISCLAIMER = (window.VMI_CONFIG && window.VMI_CONFIG.aiDisclaimer)
  || "This report is based on an open-source AI model.";

function downloadPdfReport(){
  const jsPDF = window.jspdf && window.jspdf.jsPDF;
  if(!jsPDF){ alert("PDF library failed to load — please check your internet connection and try again."); return; }
  const doc = new jsPDF();
  const d = state._reportData;
  const navy = [26,0,136];
  let y = 20;

  doc.setFillColor(navy[0],navy[1],navy[2]); doc.rect(0,0,210,28,"F");
  doc.setTextColor(255,255,255); doc.setFontSize(16); doc.setFont(undefined,"bold");
  doc.text("One Degree Advisory", 14, 17);
  doc.setFontSize(10); doc.setFont(undefined,"normal");
  doc.text("AI Visa Mock Interview — Assessment Report", 14, 23);

  y = 40;
  doc.setTextColor(20,20,30); doc.setFontSize(13); doc.setFont(undefined,"bold");
  doc.text("Questions answered: "+state.results.length, 14, y); y+=7;
  doc.setFont(undefined,"normal"); doc.setFontSize(11);
  doc.text("Overall score: "+d.overallScore100+" / 100", 14, y); y+=10;

  const analytics = d.analytics;
  if(analytics){
    doc.setFont(undefined,"bold"); doc.text("Session statistics:", 14, y); y+=6;
    doc.setFont(undefined,"normal"); doc.setFontSize(10);
    doc.text("Completion: "+analytics.answered+" / "+analytics.planned+" ("+analytics.coveragePct+"%)", 18, y); y+=5;
    doc.text("Average response: "+fmtTime(analytics.avgDuration)+" | Average length: "+analytics.avgWords+" words | Total words: "+analytics.totalWords, 18, y); y+=5;
    doc.text("Filler words: "+analytics.fillerTotal+" | Consistency: "+analytics.consistency+" (deviation "+analytics.deviation+")", 18, y); y+=9;

    doc.setFont(undefined,"bold"); doc.setFontSize(11); doc.text("Category performance:", 14, y); y+=6;
    doc.setFont(undefined,"normal"); doc.setFontSize(10);
    analytics.categoryPerformance.forEach(category=>{
      doc.text("- "+category.name+": "+category.average.toFixed(1)+"/10 ("+category.count+" answer"+(category.count===1?"":"s")+")", 18, y);
      y+=5;
    });
    y+=4;
  }

  doc.setFont(undefined,"bold"); doc.text("Strongest areas:", 14, y); y+=6;
  doc.setFont(undefined,"normal");
  d.strongest.forEach(([k,v])=>{ doc.text("- "+labelFor(k)+": "+v+"/10", 18, y); y+=6; });
  y+=4;

  doc.setFont(undefined,"bold"); doc.text("Weakest areas:", 14, y); y+=6;
  doc.setFont(undefined,"normal");
  d.weakest.forEach(([k,v])=>{ doc.text("- "+labelFor(k)+": "+v+"/10", 18, y); y+=6; });
  y+=8;

  doc.setFont(undefined,"bold"); doc.text("Question-by-question notes:", 14, y); y+=7;
  doc.setFont(undefined,"normal"); doc.setFontSize(10);
  state.results.forEach((r,i)=>{
    if(y > 270){ doc.addPage(); y = 20; }
    doc.setFont(undefined,"bold");
    doc.text(("Q"+(i+1)+". "+r.question).slice(0,110), 14, y); y+=5;
    doc.setFont(undefined,"normal");
    const lines = doc.splitTextToSize("Overall: "+r.overall.toFixed(1)+"/10 — "+(r.feedback.improve || ""), 180);
    doc.text(lines, 18, y); y += lines.length*5 + 3;
  });

  // Stamp the AI-model disclaimer at the foot of every page, matching the
  // on-screen report and the profiler career-report PDF.
  const pageTotal = doc.internal.getNumberOfPages();
  for(let p = 1; p <= pageTotal; p++){
    doc.setPage(p);
    doc.setFont(undefined,"normal"); doc.setFontSize(8); doc.setTextColor(120,120,140);
    doc.text(AI_DISCLAIMER, 105, 289, { align: "center" });
  }

  doc.save("OneDegreeAdvisory_Visa_Interview_Report.pdf");
}

/* ============================================================
   CONFETTI (celebration on strong scores)
   ============================================================ */
function celebrate(){
  // Confetti removed for a calmer, more professional report. Kept as a no-op so
  // the existing call site (finishInterview) needs no change.
  return;
  /* eslint-disable no-unreachable */
  if(PREFERS_REDUCED) return;
  const canvas = document.createElement("canvas");
  canvas.style.cssText = "position:fixed;inset:0;pointer-events:none;z-index:2000;";
  document.body.appendChild(canvas);
  const ctx = canvas.getContext("2d");
  if(!ctx){ canvas.remove(); return; }
  let W, H;
  function size(){ W = canvas.width = window.innerWidth; H = canvas.height = window.innerHeight; }
  size();
  const colors = ["#ff5e32","#1a0088","#ffe6a3","#0f7a78","#ff8a5c"];
  const parts = [];
  for(let i=0;i<140;i++){
    parts.push({
      x: W/2 + (Math.random()-0.5)*W*0.35,
      y: H*0.26,
      vx: (Math.random()-0.5)*10,
      vy: Math.random()*-12 - 4,
      g: 0.30,
      s: 5 + Math.random()*7,
      rot: Math.random()*6.28,
      vr: (Math.random()-0.5)*0.35,
      col: colors[i % colors.length]
    });
  }
  let start = null;
  const dur = 2000;
  function frame(ts){
    if(start === null) start = ts;
    const t = ts - start;
    ctx.clearRect(0,0,W,H);
    parts.forEach(p=>{
      p.vy += p.g; p.x += p.vx; p.y += p.vy; p.rot += p.vr;
      ctx.save();
      ctx.translate(p.x, p.y);
      ctx.rotate(p.rot);
      ctx.globalAlpha = Math.max(0, 1 - t/dur);
      ctx.fillStyle = p.col;
      ctx.fillRect(-p.s/2, -p.s/2, p.s, p.s*0.6);
      ctx.restore();
    });
    if(t < dur) requestAnimationFrame(frame);
    else canvas.remove();
  }
  requestAnimationFrame(frame);
}

/* ============================================================
   LEAD CAPTURE + UNLOCK MORE QUESTIONS
   ============================================================ */
const CFG = window.VMI_CONFIG || {};
const leadModal = $("vmiLeadModal");
const leadForm = $("vmiLeadForm");
const leadFormState = leadModal.querySelector("[data-lead-form-state]");
const leadSuccessState = leadModal.querySelector("[data-lead-success-state]");
const leadError = $("vmiLeadError");
const leadSubmit = $("vmiLeadSubmit");
let lastFocus = null;

function openLead(){
  lastFocus = document.activeElement;
  // Once a lead has been submitted, re-opening shows the thank-you, not the form.
  if(leadFormState) leadFormState.style.display = state.leadSubmitted ? "none" : "";
  if(leadSuccessState) leadSuccessState.hidden = !state.leadSubmitted;
  if(leadError){ leadError.hidden = true; leadError.textContent = ""; }
  leadModal.hidden = false;
  requestAnimationFrame(()=>{
    leadModal.classList.add("is-open");
    leadModal.setAttribute("aria-hidden","false");
    const first = $("vmi-lead-name");
    if(first && !state.leadSubmitted) try{ first.focus(); }catch(e){}
  });
  document.addEventListener("keydown", onLeadKey);
}
function closeLead(){
  leadModal.classList.remove("is-open");
  leadModal.setAttribute("aria-hidden","true");
  document.removeEventListener("keydown", onLeadKey);
  setTimeout(()=>{ leadModal.hidden = true; }, 300);
  if(lastFocus && lastFocus.focus) try{ lastFocus.focus(); }catch(e){}
}
function onLeadKey(e){ if(e.key === "Escape") closeLead(); }

document.querySelectorAll("[data-lead-open]").forEach(b=> b.addEventListener("click", openLead));
document.querySelectorAll("[data-lead-close]").forEach(b=> b.addEventListener("click", closeLead));

// A lead was captured. We do NOT unlock extended questions in-app — the extended
// rounds are handled by our team as a consulting step. We just flip the CTA to a
// "request received" state and remember it so re-opening shows the thank-you.
function markLeadSubmitted(){
  state.leadSubmitted = true;

  const cta = $("vmi-unlock-cta");
  if(cta){
    cta.classList.add("is-unlocked");
    const t = cta.querySelector("[data-unlock-title]");
    const s = cta.querySelector("[data-unlock-sub]");
    const btn = $("btn-unlock");
    if(t) t.textContent = "Request received";
    if(s) s.textContent = "Our visa team will reach out to you shortly with the next steps.";
    if(btn){ btn.textContent = "We'll be in touch"; btn.disabled = true; }
  }
}

if(leadForm){
  leadForm.addEventListener("submit", async function(e){
    e.preventDefault();
    if(leadError){ leadError.hidden = true; leadError.textContent = ""; }

    const name  = ($("vmi-lead-name").value || "").trim();
    const email = ($("vmi-lead-email").value || "").trim();
    const phone = ($("vmi-lead-phone").value || "").trim();
    const destination = ($("vmi-lead-dest").value || "").trim();
    const level = ($("vmi-lead-level").value || "").trim();

    if(name.length < 2){ return showLeadError("Please enter your full name."); }
    if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){ return showLeadError("Please enter a valid email address."); }
    if(phone.replace(/\D/g,"").length < 7){ return showLeadError("Please enter a valid phone number."); }

    const label = leadSubmit.querySelector(".vmi-lead-submit-label");
    const prev = label ? label.textContent : "";
    leadSubmit.disabled = true;
    if(label) label.textContent = "Sending…";

    try{
      const res = await fetch(CFG.leadUrl, {
        method:"POST",
        headers:{
          "Content-Type":"application/json",
          "Accept":"application/json",
          "X-Requested-With":"XMLHttpRequest",
          "X-CSRF-TOKEN": CFG.csrf || ""
        },
        body: JSON.stringify({ name, email, phone, destination, level, plan: (state.requestedLength ? state.requestedLength + " questions (consulting)" : "full interview (consulting)") })
      });

      if(res.status === 422){
        const data = await res.json().catch(()=>({}));
        const first = data && data.errors ? Object.values(data.errors)[0][0] : (data.message || "Please check your details and try again.");
        throw new Error(first);
      }
      if(!res.ok) throw new Error("Something went wrong — please try again in a moment.");

      const data = await res.json().catch(()=>({ok:true}));

      markLeadSubmitted();
      if(data.title) $("vmiLeadSuccessTitle").textContent = data.title;
      if(data.message) $("vmiLeadSuccessMsg").textContent = data.message;
      if(leadFormState) leadFormState.style.display = "none";
      if(leadSuccessState) leadSuccessState.hidden = false;
    }catch(err){
      showLeadError(err && err.message ? err.message : "Something went wrong — please try again.");
    }finally{
      leadSubmit.disabled = false;
      if(label) label.textContent = prev || "Request a callback";
    }
  });
}

function showLeadError(msg){
  if(!leadError) return;
  leadError.textContent = msg;
  leadError.hidden = false;
}

/* ============================================================
   CHOICE PILLS (sync to the hidden selects)
   ============================================================ */
function setCount(v){
  const sel = $("in-count");
  sel.value = String(v);
  document.querySelectorAll("#vmi-count-pills [data-count]").forEach(function(b){
    b.classList.toggle("is-active", b.getAttribute("data-count") === String(v));
  });
}
document.querySelectorAll("#vmi-count-pills [data-count]").forEach(function(b){
  b.addEventListener("click", function(){
    // Locked lengths (15/20/39) are handled by our team as a consulting step —
    // clicking one records which length they wanted and opens the contact form.
    if(b.hasAttribute("data-locked")){ state.requestedLength = b.getAttribute("data-count"); openLead(); return; }
    setCount(b.getAttribute("data-count"));
  });
});

function setMode(v){
  const sel = $("in-mode");
  sel.value = v;
  document.querySelectorAll("#vmi-mode-pills [data-mode]").forEach(function(b){
    b.classList.toggle("is-active", b.getAttribute("data-mode") === v);
  });
  applyModeUi(v);
}
document.querySelectorAll("#vmi-mode-pills [data-mode]").forEach(function(b){
  b.addEventListener("click", function(){ setMode(b.getAttribute("data-mode")); });
});

/* ============================================================
   PROGRESSIVE ENHANCEMENTS: step tracker, reveal, question anim
   ============================================================ */
const scrollSetupBtn = document.querySelector("[data-scroll-setup]");
if(scrollSetupBtn){
  scrollSetupBtn.addEventListener("click", function(){
    const target = $("screen-setup");
    if(target) target.scrollIntoView({behavior:"smooth", block:"start"});
  });
}

// Exit the fullscreen interview stage → back to setup.
const btnExit = $("btn-exit-interview");
if(btnExit){
  btnExit.addEventListener("click", function(){
    if(state.results.length && !window.confirm("Exit the interview? Your progress in this round will be lost.")) return;
    stopInterviewerAudio();
    state.recognizing = false; state.wantRestart = false; state.interviewActive = false;
    if(state.recognition){ try{ state.recognition.stop(); }catch(e){} }
    clearInterval(state.timerInterval); clearInterval(state.typeTimer);
    document.documentElement.classList.remove("vmi-lock");
    hide($("screen-interview"));
    show($("screen-setup"));
    window.scrollTo({top:0});
  });
}

// Toggle the AI interviewer's spoken voice.
const btnVoice = $("btn-ai-voice");
if(btnVoice){
  btnVoice.addEventListener("click", function(){
    state.aiVoice = !state.aiVoice;
    btnVoice.classList.toggle("is-off", !state.aiVoice);
    btnVoice.setAttribute("aria-pressed", state.aiVoice ? "true" : "false");
    btnVoice.title = state.aiVoice ? "Mute interviewer voice" : "Unmute interviewer voice";
    btnVoice.innerHTML = state.aiVoice ? '<i data-lucide="volume-2"></i>' : '<i data-lucide="volume-x"></i>';
    if(window.lucide) try{ window.lucide.createIcons(); }catch(e){}
    if(!state.aiVoice) stopInterviewerAudio();
  });
}

// Step tracker follows the visible screen.
const stepEls = Array.prototype.slice.call(document.querySelectorAll("#vmi-steps [data-step]"));
const barEls = Array.prototype.slice.call(document.querySelectorAll("#vmi-steps [data-bar]"));
function setStep(n){
  stepEls.forEach(function(el){
    const s = parseInt(el.getAttribute("data-step"),10);
    el.classList.toggle("is-active", s === n);
    el.classList.toggle("is-done", s < n);
  });
  barEls.forEach(function(el){
    el.classList.toggle("is-filled", parseInt(el.getAttribute("data-bar"),10) < n);
  });
}
function currentScreen(){
  if(!$("screen-report").classList.contains("hidden")) return 3;
  if(!$("screen-interview").classList.contains("hidden")) return 2;
  return 1;
}
const screenObs = new MutationObserver(function(){ setStep(currentScreen()); });
["screen-setup","screen-interview","screen-report"].forEach(function(id){
  const el = $(id);
  if(el) screenObs.observe(el, {attributes:true, attributeFilter:["class"]});
});
setStep(1);

// (Question-change animation is handled per question in presentQuestion so it
// doesn't fire on every typewriter tick.)

// Scroll-reveal for static cards.
const vmiPage = document.getElementById("vmi-page");
if(!PREFERS_REDUCED && "IntersectionObserver" in window){
  vmiPage.classList.add("anim");
  const revObs = new IntersectionObserver(function(entries){
    entries.forEach(function(entry){
      if(entry.isIntersecting){ entry.target.classList.add("in-view"); revObs.unobserve(entry.target); }
    });
  }, {threshold:0.12, rootMargin:"0px 0px -40px 0px"});
  document.querySelectorAll("#vmi-page [data-reveal]").forEach(function(el){ revObs.observe(el); });
}

// Render lucide icons once the library is available (it loads deferred).
window.addEventListener("load", function(){
  if(window.lucide) try{ window.lucide.createIcons(); }catch(e){}
});

})();
</script>
@endverbatim
@endsection
