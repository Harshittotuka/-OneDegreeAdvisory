{{-- Referral Program (/referral-program) — Student Hub.

     The design the client shared, rendered on the shared site layout so the
     navbar, footer and form popup match the rest of the site.

     Kept from the original: the flag marquee with its per-country "why study
     here" popup, the spin-the-wheel destination game, the six "who can refer"
     cards, the two four-step timelines, the referral form and the terms
     accordion.

     Changed on purpose:
       • the standalone fixed nav / footer are dropped for the site's own
       • type follows the site's two faces (Cormorant Garamond for display
         headings, Manrope for everything else) instead of Poppins / Inter
       • emoji icons became the site's already-loaded Lucide set, and
         .ref-who-icon joins the shared UNIFIED ICON-TILE SYSTEM
       • the 23 flags were pulled from flagcdn.com — 23 cross-origin images, and
         46 <img> elements once the marquee duplicates them. They are now ONE
         21 KB local sprite (assets/referral/flags.webp), positioned by index
       • AOS (a CDN animation library) is gone; reveal-on-scroll is a dozen lines
         of IntersectionObserver, matching the other Student Hub pages
       • the wheel keeps the reference design's pastel wedge palette exactly
       • the form was a client-side fake that only changed a caption; it now
         POSTs to the CRM through the site's shared AJAX handler
       • the inline base64 photo is served as a file under assets/referral/

     Everything is scoped under #ref-page so the design's generic class names
     never collide with the shared styles.css / stripe-nav.css chrome. --}}
@extends('layouts.app')

@php
    /**
     * The destinations behind the marquee and the popup. `sprite` is the flag's
     * index in assets/referral/flags.webp — the sheet is generated in that exact
     * order, so adding a country here means regenerating the sprite too.
     */
    $destinations = [
        ['code' => 'us', 'name' => 'USA', 'why' => ['Home to the largest number of globally ranked universities', 'Strong graduate employability across STEM and business', 'Optional Practical Training (OPT) extends post-study work rights', 'Extensive scholarship and assistantship opportunities']],
        ['code' => 'gb', 'name' => 'United Kingdom', 'why' => ['One to three year programs shorten time to degree', 'Globally recognised universities across every discipline', 'Graduate Route offers post-study work rights', 'Strong research funding and innovation ecosystem']],
        ['code' => 'ca', 'name' => 'Canada', 'why' => ['Post-Graduation Work Permit supports post-study work rights', 'Multiple pathways toward permanent residency', 'Affordable tuition relative to other English-speaking destinations', 'Strong co-op and applied-learning programs']],
        ['code' => 'de', 'name' => 'Germany', 'why' => ['Low or no tuition at most public universities', 'World-class engineering, research and innovation culture', '18-month post-study job-search visa for graduates', 'Growing number of English-taught Master programs']],
        ['code' => 'fr', 'name' => 'France', 'why' => ['Prestigious Grandes Écoles and business schools', 'Post-study residence permit to seek employment', 'Strong scholarships through Campus France partners', 'Renowned programs in business, design and engineering']],
        ['code' => 'ie', 'name' => 'Ireland', 'why' => ['English-speaking gateway to the European Union', 'Stay Back visa for post-study work rights', 'Home to European HQs of major global companies', 'Strong pathways in tech, pharma and finance']],
        ['code' => 'au', 'name' => 'Australia', 'why' => ['Multiple universities ranked among the global top tier', 'Post-Study Work stream visa for eligible graduates', 'Strong research funding across sciences and health', 'Clear pathways toward skilled migration']],
        ['code' => 'nz', 'name' => 'New Zealand', 'why' => ['Post-study work rights for eligible graduates', 'Small class sizes with high faculty access', 'Safe, nature-rich environment for international students', 'Straightforward visa and compliance processes']],
        ['code' => 'sg', 'name' => 'Singapore', 'why' => ['Regional hub for finance, tech and logistics', 'Highly ranked universities with strong industry links', 'Safe, English-speaking, multicultural environment', 'Gateway location for Southeast Asian careers']],
        ['code' => 'ae', 'name' => 'UAE', 'why' => ['Fast-growing hub for business and international trade', 'Branch campuses of respected global universities', 'Tax-free income supports post-study opportunities', 'Strong pathways in business, hospitality and technology']],
        ['code' => 'eu', 'name' => 'Europe', 'why' => ['Wide choice of countries, cultures and English-taught programs', 'Many destinations offer low-cost or tuition-free public universities', 'Schengen access makes travel across the region easy', 'Strong scholarship and Erasmus-linked opportunities']],
        ['code' => 'it', 'name' => 'Italy', 'why' => ['Historic universities strong in arts, design and engineering', 'Affordable tuition compared to many Western destinations', 'Rich cultural environment and central European location', 'Strong reputation in fashion, design and heritage fields']],
        ['code' => 'nl', 'name' => 'Netherlands', 'why' => ['Large number of English-taught programs', 'Highly ranked universities in business and engineering', 'Search Year visa supports post-study job hunting', 'Strong culture of group work and applied research']],
        ['code' => 'se', 'name' => 'Sweden', 'why' => ['Renowned for innovation, design and sustainability research', 'Progressive, English-friendly academic environment', 'Strong industry links in tech and engineering', 'High quality of life and safety']],
        ['code' => 'fi', 'name' => 'Finland', 'why' => ['World-recognised education system and teaching quality', 'Strong focus on technology, design and sustainability', 'English-taught Master programs across most fields', 'Post-study residence permit for job seeking']],
        ['code' => 'no', 'name' => 'Norway', 'why' => ['Many public universities charge no tuition fees', 'Strong research funding in energy and marine sciences', 'High standard of living and safety', 'Welcoming, well-regulated visa process']],
        ['code' => 'dk', 'name' => 'Denmark', 'why' => ['Consistently ranked among the happiest, safest countries', 'Strong programs in design, engineering and sustainability', 'English-taught degrees across many universities', 'Post-study job-search visa available']],
        ['code' => 'ch', 'name' => 'Switzerland', 'why' => ["Home to some of the world's top-ranked universities", 'Renowned hospitality, finance and engineering programs', 'Strong industry connections across Europe', 'High standard of research infrastructure']],
        ['code' => 'at', 'name' => 'Austria', 'why' => ['Affordable, high-quality public university system', 'Central European location with strong travel access', 'Growing number of English-taught programs', 'Rich academic tradition in music, law and sciences']],
        ['code' => 'es', 'name' => 'Spain', 'why' => ['Affordable tuition and cost of living', 'Strong programs in business, design and hospitality', 'Vibrant culture and a widely spoken global language', 'Central access to the rest of Europe']],
        ['code' => 'be', 'name' => 'Belgium', 'why' => ['Home to major EU institutions and policy hubs', 'Strong programs in law, business and international relations', 'Central European location for travel and internships', 'Respected research universities']],
        ['code' => 'jp', 'name' => 'Japan', 'why' => ['World-leading research in robotics and technology', 'Government scholarships for international students', 'Safe, highly organised student environment', 'Growing number of English-taught programs']],
        ['code' => 'kr', 'name' => 'South Korea', 'why' => ['Rapidly growing hub for technology and innovation', 'Strong government scholarship support', 'Safe, modern, well-connected cities', 'Strong industry links in electronics and entertainment']],
    ];

    // Flag sprite geometry — must match the generated sheet.
    $spriteCells = count($destinations);
    $spriteCell = 112;
    $spriteHeight = 76;

    foreach ($destinations as $i => $d) {
        $destinations[$i]['sprite'] = $i;
    }

    // The wheel's eight segments, by index into $destinations.
    $wheelCodes = ['au', 'gb', 'us', 'ca', 'eu', 'ae', 'sg', 'nz'];
    $wheel = [];
    foreach ($wheelCodes as $code) {
        foreach ($destinations as $d) {
            if ($d['code'] === $code) {
                $wheel[] = ['name' => $d['name'] === 'United Kingdom' ? 'UK' : $d['name'], 'code' => $code, 'sprite' => $d['sprite']];
                break;
            }
        }
    }

    $whoCanRefer = [
        ['icon' => 'graduation-cap', 'title' => 'Current students', 'text' => 'Studying with a partner university? Refer juniors or friends back home.'],
        ['icon' => 'award', 'title' => 'Alumni', 'text' => 'Already abroad and settled? Guide the next batch of applicants.'],
        ['icon' => 'users', 'title' => 'Parents', 'text' => 'Know another family weighing up options for their child? Point them our way.'],
        ['icon' => 'briefcase', 'title' => 'Working professionals', 'text' => "Colleagues eyeing an MBA or Master's abroad? Refer and earn."],
        ['icon' => 'megaphone', 'title' => 'Creators & influencers', 'text' => 'Have an audience that trusts your advice? Share your referral.'],
        ['icon' => 'handshake', 'title' => 'Friends & family', 'text' => 'The simplest referrals come from people who already trust you.'],
    ];

    $howItWorks = [
        ['n' => 1, 'icon' => 'send', 'title' => 'Submit the referral', 'text' => "Share the student's details through the form on this page.", 'link' => true],
        ['n' => 2, 'icon' => 'compass', 'title' => 'We guide the student', 'text' => 'Our counsellors take over — profiling, shortlisting and applications.'],
        ['n' => 3, 'icon' => 'graduation-cap', 'title' => 'The student enrols', 'text' => 'They secure an offer, clear the visa, and confirm enrolment.'],
        ['n' => 4, 'icon' => 'gift', 'title' => 'You get rewarded', 'text' => 'Your reward is processed once the enrolment is verified.'],
    ];

    $rewardSteps = [
        ['n' => 1, 'icon' => 'stamp', 'title' => 'Student receives visa', 'text' => "The referred student's visa is approved and confirmed."],
        ['n' => 2, 'icon' => 'school', 'title' => 'Student reports to university', 'text' => 'Enrolment is confirmed once they join their program.'],
        ['n' => 3, 'icon' => 'shield-check', 'title' => 'Verification', 'text' => 'Our team verifies the enrolment with the university.'],
        ['n' => 4, 'icon' => 'banknote', 'title' => 'Reward processed', 'text' => 'Your reward is released by bank transfer or UPI.', 'tag' => '4–8 weeks'],
    ];

    // The popup payload for the script, built here rather than inline in @json:
    // Blade's @json does plain bracket-matching on its argument, so a closure
    // containing its own array literals makes it emit unbalanced PHP.
    $countryJs = [];
    foreach ($destinations as $d) {
        $countryJs[] = [
            'code' => $d['code'],
            'name' => $d['name'],
            'sprite' => $d['sprite'],
            'why' => $d['why'],
        ];
    }

    $terms = [
        ['q' => 'Who qualifies as an official referral?', 'a' => 'Only referrals submitted through the form on this page, before the student contacts One Degree Advisory independently, qualify for the program.'],
        ['q' => 'Can I refer myself?', 'a' => 'No. Self-referrals are not eligible — the referrer and the student must be two different people.'],
        ['q' => 'Does the student need to be a new lead?', 'a' => 'Yes. Referrals are only valid for students who are not already registered with One Degree Advisory.'],
        ['q' => 'What if two people refer the same student?', 'a' => 'The reward is credited to whichever referral was submitted first, based on our records.'],
        ['q' => 'Is there a limit on how many students I can refer?', 'a' => 'No — you may submit unlimited referrals, and each successful enrolment is rewarded independently.'],
        ['q' => 'How is the reward paid out?', 'a' => 'Rewards are transferred by bank account or UPI, after the enrolment has been verified.'],
        ['q' => 'Does the reward amount ever change?', 'a' => 'Reward structures may vary by destination, program level or promotional period, and are confirmed with you at the time of referral.'],
        ['q' => 'Can the terms of this program change?', 'a' => 'One Degree Advisory reserves the right to modify or discontinue the referral program at its discretion.'],
    ];
@endphp

@push('head')
<style>
  /* The shared layout fades the body dark toward the footer; this page keeps its
     own cream running unbroken down to the footer. */
  body.ref-page-body{ background:#fcf9f4; background-image:none; }

  #ref-page{
    /* The design's navy (#1A0088) and orange (#FF5E32) are already the site's
       cream-theme --navy / --teal, so the palette needed no reconciling; only
       the page background moved onto the site's cream. */
    --rf-navy:#1a0088; --rf-navy-deep:#0e0052; --rf-navy-soft:#3a1fb8;
    --rf-orange:#ff5e32; --rf-orange-soft:rgba(255,94,50,.1);
    --rf-cream:#fcf9f4; --rf-paper:#ffffff;
    --rf-ink:#140b33; --rf-muted:#5b5478;
    --rf-line:rgba(20,11,51,.11);
    --rf-shadow-md:0 16px 40px -18px rgba(26,0,136,.22);
    --rf-shadow-lg:0 30px 60px -20px rgba(26,0,136,.25);
    --rf-serif:"Cormorant Garamond", Georgia, serif;
    --rf-sans:"Manrope", system-ui, -apple-system, "Segoe UI", sans-serif;
    --rf-pad:clamp(20px,5vw,64px);
    /* Flag sprite geometry, shared by the marquee, wheel and popup. */
    --rf-flag-cells:{{ $spriteCells }};
    font-family:var(--rf-sans); color:var(--rf-ink); background:var(--rf-cream);
    overflow-x:clip;
  }
  #ref-page *{ box-sizing:border-box; }
  #ref-page h1, #ref-page h2{ font-family:var(--rf-serif); font-weight:700; line-height:1.08;
    color:var(--rf-navy); margin:0; letter-spacing:-.01em; }
  #ref-page h3, #ref-page h4{ font-family:var(--rf-sans); font-weight:700; line-height:1.25;
    color:var(--rf-navy); margin:0; }
  #ref-page p{ margin:0; color:var(--rf-muted); line-height:1.65; }
  #ref-page a{ color:var(--rf-navy); text-decoration:none; }
  #ref-page a:hover{ color:var(--rf-orange); }
  #ref-page ul{ list-style:none; padding:0; margin:0; }
  #ref-page .rf-wrap{ max-width:1180px; margin:0 auto; padding-inline:var(--rf-pad); }
  #ref-page section{ padding:clamp(56px,7vw,86px) 0; }
  #ref-page .rf-paper{ background:var(--rf-paper); border-block:1px solid var(--rf-line); }

  #ref-page .rf-eyebrow{
    display:inline-flex; align-items:center; gap:9px; font-size:12px; font-weight:800;
    letter-spacing:.14em; text-transform:uppercase; color:var(--rf-orange); margin:0 0 12px;
  }
  #ref-page .rf-eyebrow::before{ content:""; width:22px; height:2px; border-radius:2px; background:currentColor; }
  #ref-page .rf-head{ text-align:center; max-width:660px; margin:0 auto clamp(34px,4vw,46px); }
  #ref-page .rf-head .rf-eyebrow{ justify-content:center; }
  #ref-page .rf-head h2{ font-size:clamp(29px,3.5vw,43px); margin-bottom:14px; }
  #ref-page .rf-head p{ font-size:17px; }

  #ref-page .rf-btn{
    display:inline-flex; align-items:center; justify-content:center; gap:9px;
    font-family:var(--rf-sans); font-size:15.5px; font-weight:700; line-height:1;
    padding:16px 30px; border-radius:999px; border:1px solid transparent; cursor:pointer;
    transition:transform .22s ease, box-shadow .22s ease, background .22s ease, color .22s ease;
  }
  #ref-page .rf-btn i{ width:17px; height:17px; }
  #ref-page .rf-btn:hover{ transform:translateY(-2px); }
  #ref-page .rf-btn--primary{ background:var(--rf-orange); color:#fff; box-shadow:0 14px 30px -10px rgba(255,94,50,.62); }
  #ref-page .rf-btn--primary:hover{ color:#fff; box-shadow:0 20px 36px -10px rgba(255,94,50,.72); }
  #ref-page .rf-btn--ghost{ background:var(--rf-paper); color:var(--rf-navy); border-color:var(--rf-line); }
  #ref-page .rf-btn--ghost:hover{ background:var(--rf-cream); }
  #ref-page .rf-btn[disabled]{ opacity:.6; cursor:not-allowed; transform:none; }

  #ref-page .rf-reveal{ opacity:0; transform:translateY(24px);
    transition:opacity .7s cubic-bezier(.2,.7,.2,1), transform .7s cubic-bezier(.2,.7,.2,1); }
  #ref-page .rf-reveal.is-in{ opacity:1; transform:none; }

  /* ══ Flag sprite ══
     One WebP strip holds all {{ $spriteCells }} flags. Each cell is shown by shifting the
     background by the flag's index, so the marquee, the wheel and the popup all
     share a single cached image and a single request.

     Each cell carries a transparent gutter around its flag. Without it the cells
     sat edge to edge, and scaling a 120px cell down to ~47px on screen made the
     browser resample ACROSS the cell boundary — pulling a sliver of the next
     country into every flag. The gutter means that bleed lands on transparency.
     Element boxes below are therefore sized to the whole cell (3:2), which is
     slightly larger than the flag it frames. */
  #ref-page .rf-flag{
    display:block; background-image:url('{{ asset('assets/referral/flags.webp') }}');
    background-repeat:no-repeat;
    background-size:calc(100% * var(--rf-flag-cells)) 100%;
    background-position:calc(var(--i) * 100% / (var(--rf-flag-cells) - 1)) 0;
  }

  /* ══ Hero ══ */
  #ref-page .rf-hero{
    position:relative; overflow:hidden; padding:clamp(40px,5vw,58px) 0 clamp(48px,6vw,70px);
    background:linear-gradient(168deg, #ffffff 0%, var(--rf-cream) 52%, #f2eee6 100%);
  }
  #ref-page .rf-hero__dots{
    position:absolute; inset:0; pointer-events:none; opacity:.6;
    background-image:radial-gradient(rgba(26,0,136,.08) 1.5px, transparent 1.5px);
    background-size:26px 26px;
    -webkit-mask-image:radial-gradient(circle at 50% 34%, rgba(0,0,0,.9), transparent 72%);
    mask-image:radial-gradient(circle at 50% 34%, rgba(0,0,0,.9), transparent 72%);
  }
  #ref-page .rf-hero__glow{
    position:absolute; inset:0; pointer-events:none;
    background:radial-gradient(circle at 18% 12%, rgba(58,31,184,.10), transparent 50%),
               radial-gradient(circle at 86% 78%, rgba(255,94,50,.10), transparent 50%);
  }

  /* Flight-board style destination marquee */
  #ref-page .rf-board{ position:relative; z-index:5; max-width:1180px; margin:0 auto clamp(26px,3vw,34px);
    padding-inline:var(--rf-pad); }
  #ref-page .rf-board__inner{
    background:var(--rf-paper); border:1px solid var(--rf-line); border-radius:20px;
    padding:15px 0; box-shadow:var(--rf-shadow-md); overflow:hidden;
    /* Fade both ends so flags enter and leave instead of being chopped off. Kept
       narrow (2%): a wider fade left the very first flag washed out on load,
       before the marquee had moved. */
    -webkit-mask-image:linear-gradient(90deg, transparent, #000 2%, #000 98%, transparent);
    mask-image:linear-gradient(90deg, transparent, #000 2%, #000 98%, transparent);
  }
  #ref-page .rf-board__track{ display:flex; gap:34px; width:max-content; padding:0 20px;
    animation:rfMarquee 48s linear infinite; }
  #ref-page .rf-board__inner:hover .rf-board__track,
  #ref-page .rf-board__inner:focus-within .rf-board__track{ animation-play-state:paused; }
  @keyframes rfMarquee{ from{ transform:translateX(0); } to{ transform:translateX(-50%); } }

  #ref-page .rf-flagbtn{
    display:flex; flex-direction:column; align-items:center; gap:7px; flex-shrink:0;
    background:none; border:0; padding:4px 6px; border-radius:12px; cursor:pointer;
    transition:transform .2s ease;
  }
  #ref-page .rf-flagbtn:hover{ transform:translateY(-4px); }
  #ref-page .rf-pole{ position:relative; width:2px; height:34px; border-radius:2px;
    background:linear-gradient(180deg, var(--rf-navy-soft), rgba(58,31,184,.15)); }
  /* Sized to the sprite cell (3:2), so the flag inside reads at ~42x28. No
     box-shadow: it would trace the element box, including the transparent
     gutter, rather than the flag. A drop-shadow filter would follow the alpha
     correctly but there are 46 of these and they animate, so the shadow is
     simply dropped here — at this size on a white card it added nothing. */
  #ref-page .rf-cloth{
    position:absolute; top:-1px; left:1px; width:47px; height:31px;
    transform-origin:left center; animation:rfWave 2.6s ease-in-out infinite;
  }
  #ref-page .rf-flagbtn:nth-child(3n) .rf-cloth{ animation-delay:.3s; }
  #ref-page .rf-flagbtn:nth-child(3n+1) .rf-cloth{ animation-delay:.7s; }
  @keyframes rfWave{ 0%,100%{ transform:skewY(4deg) scaleY(1); } 50%{ transform:skewY(-4deg) scaleY(.97); } }
  #ref-page .rf-flagname{ font-size:11.5px; font-weight:700; color:var(--rf-ink); white-space:nowrap; }

  #ref-page .rf-hero__grid{
    position:relative; z-index:5; display:grid; grid-template-columns:1.12fr .88fr;
    gap:clamp(32px,4vw,52px); align-items:center;
  }
  #ref-page .rf-hero h1{ font-size:clamp(38px,5.2vw,62px); margin-bottom:18px; }
  #ref-page .rf-hero h1 .rf-accent{ color:var(--rf-orange); }
  #ref-page .rf-hero__sub{ font-size:17.5px; max-width:50ch; margin-bottom:30px; }
  #ref-page .rf-hero__ctas{ display:flex; gap:14px; flex-wrap:wrap; margin-bottom:38px; }
  #ref-page .rf-stats{ display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; max-width:560px; }
  /* The four cards share a height and put their labels on a common baseline, so
     a two-line label cannot make one card taller than its neighbours. */
  #ref-page .rf-stat{ background:var(--rf-paper); border:1px solid var(--rf-line); border-radius:16px;
    padding:18px 12px; text-align:center; box-shadow:var(--rf-shadow-md);
    display:flex; flex-direction:column; justify-content:center; }
  #ref-page .rf-stat b{ display:block; font-family:var(--rf-serif); font-size:clamp(24px,2.1vw,30px);
    font-weight:700; line-height:1.1; color:var(--rf-navy); }
  /* One of the four values is a word, not a figure. In the display serif at
     figure size it read as a mismatched headline rather than a stat, so words
     take the body face a size down — same weight on the page, no pretence of
     being a number. */
  #ref-page .rf-stat b.rf-stat--word{ font-family:var(--rf-sans); font-size:clamp(17px,1.5vw,20px);
    font-weight:800; letter-spacing:-.01em; }
  #ref-page .rf-stat small{ display:block; font-size:11.5px; color:var(--rf-muted);
    margin-top:7px; line-height:1.35; text-wrap:balance; min-height:2.7em; }

  /* ══ Spin & discover ══ */
  #ref-page .rf-game{
    background:var(--rf-paper); border:1px solid var(--rf-line); border-radius:28px;
    padding:clamp(24px,3vw,32px) clamp(20px,2.5vw,26px); box-shadow:var(--rf-shadow-lg);
    display:flex; flex-direction:column; align-items:center; gap:13px; text-align:center;
  }
  #ref-page .rf-game .rf-eyebrow{ justify-content:center; margin-bottom:0; }
  #ref-page .rf-game h3{ font-size:18px; }
  #ref-page .rf-wheel-outer{ position:relative; width:min(250px,66vw); aspect-ratio:1; }
  #ref-page .rf-wheel-outer::before{
    content:""; position:absolute; inset:-14px; border-radius:50%;
    background:radial-gradient(circle, rgba(255,94,50,.32), transparent 68%);
    animation:rfPulse 2.4s ease-in-out infinite;
  }
  @keyframes rfPulse{ 0%,100%{ opacity:.5; transform:scale(.96); } 50%{ opacity:1; transform:scale(1.04); } }
  #ref-page .rf-wheel-pointer{
    position:absolute; top:-9px; left:50%; transform:translateX(-50%); z-index:3;
    width:0; height:0; border-left:11px solid transparent; border-right:11px solid transparent;
    border-top:18px solid var(--rf-orange); filter:drop-shadow(0 3px 4px rgba(0,0,0,.32));
  }
  #ref-page .rf-wheel{
    position:relative; width:100%; height:100%; border-radius:50%; overflow:hidden;
    border:5px solid var(--rf-cream); box-shadow:0 20px 44px -12px rgba(26,0,136,.35);
    transition:transform 4.2s cubic-bezier(.15,.65,.2,1);
  }
  #ref-page .rf-seg{
    position:absolute; top:50%; left:50%; width:64px; margin-left:-32px; height:0;
    transform-origin:50% 0; pointer-events:none;
    display:flex; flex-direction:column; align-items:center; gap:3px;
  }
  /* Rotated the long way round (see the Blade note), so its children stack away
     from the hub rather than toward it — reversing the column puts the flag back
     on the outside and the name inside, matching the upper half. */
  #ref-page .rf-seg--flipped{ flex-direction:column-reverse; }
  /* flex-shrink:0 is load-bearing. .rf-seg is height:0 on purpose (the rotate +
     translateY places the label at a radius), and in a zero-height column flex
     container the default flex-shrink:1 crushes every child to zero height. The
     text labels survived by overflowing; the flags, having no content, vanished. */
  #ref-page .rf-seg > *{ flex-shrink:0; }
  #ref-page .rf-seg .rf-flag{ width:24px; height:16px; }
  /* Dark ink on the pastel wedges, per the reference design — the wedge colours
     are light, so white labels washed out against them. */
  #ref-page .rf-seg span{ font-size:9.5px; font-weight:800; color:#1a0f4d; line-height:1.05;
    white-space:nowrap; text-shadow:0 1px 2px rgba(255,255,255,.55); }
  #ref-page .rf-wheel-hub{
    position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); z-index:2;
    width:40px; height:40px; border-radius:50%; background:#fff; color:var(--rf-navy);
    display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(0,0,0,.28);
  }
  #ref-page .rf-wheel-hub i{ width:20px; height:20px; }
  #ref-page .rf-game__result{ min-height:24px; font-size:14px; font-weight:700; color:var(--rf-ink); }
  #ref-page .rf-game__result button{
    background:none; border:0; padding:0; font:inherit; color:var(--rf-orange);
    text-decoration:underline; cursor:pointer;
  }

  /* ══ Who can refer ══ */
  #ref-page .rf-who__layout{ display:grid; grid-template-columns:1.08fr .92fr;
    gap:clamp(30px,4vw,48px); align-items:stretch; }
  #ref-page .rf-who__list{ display:flex; flex-direction:column; gap:13px; justify-content:center; }
  #ref-page .rf-who-card{
    display:flex; align-items:center; gap:17px; padding:17px 21px;
    background:var(--rf-paper); border:1px solid var(--rf-line); border-radius:18px;
    transition:transform .28s ease, box-shadow .28s ease;
  }
  #ref-page .rf-who-card:hover{ transform:translateX(6px); box-shadow:var(--rf-shadow-md); }
  /* Tile size / radius / colours come from the site-wide UNIFIED ICON-TILE
     SYSTEM block at the end of styles.css (.rf-who-icon is listed there);
     only what that block does not set lives here. */
  #ref-page .rf-who-icon{ display:inline-flex; align-items:center; justify-content:center;
    width:46px; height:46px; flex-shrink:0; }
  #ref-page .rf-who-card h3{ font-size:16px; margin-bottom:3px; }
  #ref-page .rf-who-card p{ font-size:13.8px; line-height:1.5; }
  #ref-page .rf-who__art{ position:relative; border-radius:26px; overflow:hidden;
    border:1px solid var(--rf-line); background:var(--rf-cream); box-shadow:var(--rf-shadow-md); min-height:100%; }
  #ref-page .rf-who__art picture, #ref-page .rf-who__art img{ display:block; }
  #ref-page .rf-who__art img{ position:absolute; inset:0; width:100%; height:100%;
    object-fit:cover; object-position:top center; }

  /* ══ Timelines ══
     One flow component, two variants. Each step is a marker sitting on a
     continuous rail with a card beneath it. The marker carries the step's icon
     with the number as a corner chip, so a glance gets both the sequence and
     what each stage actually is — the old bare numbered discs carried only the
     count, and with two four-step timelines on one page they read as the same
     thing twice.

     --rf-flow-bg is the ring colour that punches each marker out of the rail;
     it has to follow whichever surface the section sits on. */
  #ref-page .rf-flow{
    --rf-flow-bg:var(--rf-cream);
    --rf-flow-accent:var(--rf-navy);
    --rf-flow-glow:rgba(26,0,136,.45);
    list-style:none; margin:0; padding:0; position:relative;
    display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:clamp(14px,1.8vw,24px);
  }
  #ref-page .rf-paper .rf-flow{ --rf-flow-bg:var(--rf-paper); }
  #ref-page .rf-flow--reward{ --rf-flow-accent:var(--rf-orange); --rf-flow-glow:rgba(255,94,50,.5); }

  /* The rail. Runs between the first and last marker centres (a quarter of the
     grid in from each edge) and warms navy → orange along its length, so both
     timelines read as travelling toward the reward. */
  #ref-page .rf-flow::before{
    content:""; position:absolute; top:29px; left:12.5%; right:12.5%; height:2px; border-radius:2px;
    background:linear-gradient(90deg, rgba(26,0,136,.30), rgba(26,0,136,.22) 45%, rgba(255,94,50,.55));
  }
  /* All orange, deepening left to right: the reward rail sits under orange
     milestones, so a navy start read as a stray colour rather than a journey. */
  #ref-page .rf-flow--reward::before{
    background:linear-gradient(90deg, rgba(255,94,50,.22), rgba(255,94,50,.65));
  }

  /* Column flex, not a plain block: the card takes the leftover height with
     flex:1, so all four end level. `height:100%` on the card cannot do that —
     it resolves against the whole stretched row, which also holds the marker,
     so every card came out a marker taller than its content needed. */
  #ref-page .rf-flow__step{ position:relative; display:flex; flex-direction:column; }

  #ref-page .rf-flow__marker{
    position:relative; z-index:2; margin:0 auto 20px;
    width:60px; height:60px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    background:var(--rf-flow-accent); color:#fff;
    box-shadow:0 0 0 6px var(--rf-flow-bg), 0 14px 26px -12px var(--rf-flow-glow);
    transition:transform .28s ease, background .28s ease, color .28s ease, box-shadow .28s ease;
  }
  #ref-page .rf-flow__marker i{ width:25px; height:25px; }
  #ref-page .rf-flow__num{
    position:absolute; top:-3px; right:-3px; width:23px; height:23px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    background:var(--rf-flow-bg); color:var(--rf-flow-accent);
    border:1.5px solid currentColor; font-size:11.5px; font-weight:800; line-height:1;
  }

  /* Reward variant: hollow milestones that fill only at the payout, so the row
     reads as a progression rather than four equal beats. */
  #ref-page .rf-flow--reward .rf-flow__marker{
    background:var(--rf-flow-bg); color:var(--rf-orange);
    border:2px solid var(--rf-orange);
    box-shadow:0 0 0 6px var(--rf-flow-bg);
  }
  #ref-page .rf-flow--reward .rf-flow__step:last-child .rf-flow__marker{
    background:var(--rf-orange); color:#fff;
    box-shadow:0 0 0 6px var(--rf-flow-bg), 0 14px 26px -12px var(--rf-flow-glow);
  }

  /* Transparent card so one component works on both the cream and paper
     sections without inverting its background. */
  #ref-page .rf-flow__card{
    display:block; text-align:center; flex:1;
    padding:20px 16px 22px; border:1px solid var(--rf-line); border-radius:18px;
    background:transparent; transition:background .25s ease, border-color .25s ease,
      transform .25s ease, box-shadow .25s ease;
  }
  #ref-page .rf-flow__card h3{ font-size:15.5px; margin-bottom:7px; }
  #ref-page .rf-flow__card p{ font-size:13.8px; }

  #ref-page a.rf-flow__card:hover{
    background:var(--rf-paper); border-color:transparent;
    transform:translateY(-4px); box-shadow:var(--rf-shadow-md);
  }
  #ref-page .rf-flow__step:has(a.rf-flow__card:hover) .rf-flow__marker{
    transform:scale(1.07); background:var(--rf-orange); color:#fff;
    box-shadow:0 0 0 6px var(--rf-flow-bg), 0 14px 26px -12px rgba(255,94,50,.55);
  }

  #ref-page .rf-step__link{ display:inline-flex; align-items:center; gap:5px; margin-top:11px;
    font-size:13px; font-weight:800; color:var(--rf-orange); }
  #ref-page .rf-step__link i{ width:14px; height:14px; }
  #ref-page .rf-step__tag{ display:inline-block; margin-top:11px; padding:5px 12px; border-radius:999px;
    font-size:12px; font-weight:800; color:var(--rf-orange); background:var(--rf-orange-soft); }

  /* ══ Form ══ */
  #ref-page .rf-form{
    max-width:860px; margin:0 auto; background:var(--rf-paper); border:1px solid var(--rf-line);
    border-radius:26px; padding:clamp(24px,3.4vw,42px); box-shadow:var(--rf-shadow-lg);
  }
  #ref-page .rf-form__legend{
    display:flex; align-items:center; gap:10px; margin:0 0 16px;
    font-size:12px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:var(--rf-navy);
  }
  #ref-page .rf-form__legend i{ width:16px; height:16px; color:var(--rf-orange); }
  #ref-page .rf-form__legend::after{ content:""; flex:1; height:1px; background:var(--rf-line); }
  #ref-page .rf-form__legend + .rf-grid{ margin-bottom:26px; }
  #ref-page .rf-grid{ display:grid; grid-template-columns:1fr 1fr; gap:16px; }
  #ref-page .rf-field{ display:flex; flex-direction:column; gap:6px; }
  #ref-page .rf-field--full{ grid-column:1 / -1; }
  #ref-page .rf-field label{ font-size:11.5px; font-weight:800; letter-spacing:.06em;
    text-transform:uppercase; color:var(--rf-muted); }
  #ref-page .rf-field input, #ref-page .rf-field select, #ref-page .rf-field textarea{
    width:100%; font-family:var(--rf-sans); font-size:15px; color:var(--rf-ink);
    background:#fff; border:1.5px solid var(--rf-line); border-radius:12px; padding:12px 15px;
    transition:border-color .18s ease, box-shadow .18s ease;
  }
  #ref-page .rf-field textarea{ resize:vertical; min-height:88px; }
  #ref-page .rf-field input:focus, #ref-page .rf-field select:focus, #ref-page .rf-field textarea:focus{
    outline:none; border-color:var(--rf-orange); box-shadow:0 0 0 4px rgba(255,94,50,.14);
  }
  #ref-page .rf-consent{ display:flex; gap:11px; align-items:flex-start; margin:22px 0 24px;
    font-size:13.8px; color:var(--rf-muted); line-height:1.55; }
  #ref-page .rf-consent input{ width:18px; height:18px; margin-top:2px; flex-shrink:0; accent-color:var(--rf-orange); }
  #ref-page .rf-form__submit{ display:flex; justify-content:center; }
  #ref-page .rf-form__note{ text-align:center; font-size:12.5px; color:#777584; margin-top:14px; }

  /* ══ Terms accordion ══ */
  #ref-page .rf-acc{ max-width:1000px; margin:0 auto; display:grid; grid-template-columns:1fr 1fr;
    column-gap:clamp(28px,4vw,52px); grid-auto-flow:column; grid-template-rows:repeat(4, auto); }
  #ref-page .rf-acc__item{ border-bottom:1px solid var(--rf-line); }
  #ref-page .rf-acc__q{
    width:100%; display:flex; justify-content:space-between; align-items:center; gap:16px;
    background:none; border:0; padding:19px 4px; cursor:pointer; text-align:left;
    font-family:var(--rf-sans); font-size:15.2px; font-weight:700; color:var(--rf-navy);
  }
  #ref-page .rf-acc__q:hover{ color:var(--rf-orange); }
  #ref-page .rf-acc__q i{ width:17px; height:17px; flex-shrink:0; color:var(--rf-orange);
    transition:transform .3s ease; }
  #ref-page .rf-acc__item.is-open .rf-acc__q i{ transform:rotate(45deg); }
  #ref-page .rf-acc__a{ overflow:hidden; max-height:0; transition:max-height .34s ease; }
  #ref-page .rf-acc__a p{ padding:0 4px 19px; font-size:14px; text-align:left; }

  /* ══ Country popup ══ */
  #ref-page .rf-modal{ position:fixed; inset:0; z-index:1200; display:flex; align-items:center;
    justify-content:center; padding:20px; opacity:0; transition:opacity .24s ease; }
  #ref-page .rf-modal[hidden]{ display:none; }
  #ref-page .rf-modal.is-open{ opacity:1; }
  #ref-page .rf-modal__scrim{ position:absolute; inset:0; background:rgba(14,0,82,.55); backdrop-filter:blur(5px); }
  #ref-page .rf-modal__card{
    position:relative; width:min(100%,440px); max-height:88vh; overflow-y:auto;
    background:#fff; border-radius:24px; padding:clamp(24px,3vw,34px);
    box-shadow:0 40px 80px -20px rgba(0,0,0,.4);
    transform:translateY(16px) scale(.97); transition:transform .3s ease;
  }
  #ref-page .rf-modal.is-open .rf-modal__card{ transform:none; }
  #ref-page .rf-modal__x{ position:absolute; top:14px; right:14px; width:34px; height:34px; border-radius:50%;
    background:var(--rf-cream); border:0; color:var(--rf-navy); cursor:pointer;
    display:inline-flex; align-items:center; justify-content:center; }
  #ref-page .rf-modal__x:hover{ background:var(--rf-orange); color:#fff; }
  #ref-page .rf-modal__x i{ width:16px; height:16px; }
  /* Cell-sized like the others; drop-shadow (not box-shadow) so it traces the
     flag rather than the transparent gutter. Only one of these, and static. */
  #ref-page .rf-modal__flag{ width:64px; height:43px; margin-bottom:16px;
    filter:drop-shadow(0 4px 8px rgba(0,0,0,.22)); }
  #ref-page .rf-modal__card h3{ font-size:21px; margin-bottom:3px; padding-right:36px; }
  #ref-page .rf-modal__q{ font-size:14px; margin-bottom:16px; }
  #ref-page .rf-modal__list{ display:flex; flex-direction:column; gap:10px; margin-bottom:22px; }
  #ref-page .rf-modal__list li{ display:flex; gap:10px; align-items:flex-start;
    font-size:14px; color:var(--rf-ink); line-height:1.55; text-align:left; }
  #ref-page .rf-modal__list i{ width:16px; height:16px; flex-shrink:0; margin-top:3px; color:var(--rf-orange); }
  #ref-page .rf-modal__card .rf-btn{ width:100%; }

  @media (max-width:1000px){
    #ref-page .rf-hero__grid{ grid-template-columns:1fr; }
    #ref-page .rf-game{ max-width:420px; margin:0 auto; }
    #ref-page .rf-stats{ max-width:none; }
  }
  @media (max-width:900px){
    #ref-page .rf-who__layout{ grid-template-columns:1fr; }
    #ref-page .rf-who__art{ min-height:300px; order:2; }
    #ref-page .rf-acc{ grid-template-columns:1fr; grid-auto-flow:row; grid-template-rows:none; }
  }
  /* Below the four-across breakpoint the flow becomes a real vertical timeline —
     marker column on the left, card on the right, rail running down between the
     markers. The previous version simply hid the connector, which left four
     loose blocks with nothing tying them together on a phone. */
  @media (max-width:840px){
    #ref-page .rf-flow{ grid-template-columns:1fr; gap:0; }
    #ref-page .rf-flow::before{
      top:30px; bottom:30px; left:29px; right:auto; width:2px; height:auto;
      background:linear-gradient(180deg, rgba(26,0,136,.30), rgba(26,0,136,.22) 45%, rgba(255,94,50,.55));
    }
    #ref-page .rf-flow--reward::before{
      background:linear-gradient(180deg, rgba(255,94,50,.22), rgba(255,94,50,.65));
    }
    #ref-page .rf-flow__step{
      display:grid; grid-template-columns:60px minmax(0,1fr); align-items:start;
      column-gap:18px; padding-bottom:22px;
    }
    #ref-page .rf-flow__step:last-child{ padding-bottom:0; }
    #ref-page .rf-flow__marker{ margin:0; }
    #ref-page .rf-flow__card{ text-align:left; padding:14px 16px 16px; }
  }
  @media (max-width:620px){
    #ref-page .rf-grid{ grid-template-columns:1fr; }
    #ref-page .rf-stats{ grid-template-columns:repeat(2,minmax(0,1fr)); }
    #ref-page .rf-hero__ctas .rf-btn{ width:100%; }
  }

  @media (prefers-reduced-motion: reduce){
    #ref-page .rf-board__track, #ref-page .rf-cloth, #ref-page .rf-wheel-outer::before{ animation:none !important; }
    #ref-page .rf-wheel{ transition:none !important; }
    #ref-page .rf-reveal{ opacity:1; transform:none; transition:none; }
    #ref-page .rf-btn:hover, #ref-page .rf-who-card:hover{ transform:none; }
  }
</style>
@endpush

@section('content')
<main id="{{ $mainId ?? 'main' }}">
<div id="ref-page">

  {{-- ═══════════════════ Hero ═══════════════════ --}}
  <section class="rf-hero" id="rf-top">
    <div class="rf-hero__dots" aria-hidden="true"></div>
    <div class="rf-hero__glow" aria-hidden="true"></div>

    {{-- Destination marquee. The list is rendered twice so the translateX(-50%)
         loop is seamless; the second copy is hidden from assistive tech. --}}
    <div class="rf-board">
      <div class="rf-board__inner">
        <div class="rf-board__track">
          @foreach([false, true] as $isClone)
            @foreach($destinations as $d)
              <button type="button" class="rf-flagbtn" data-rf-country="{{ $d['code'] }}"
                      @if($isClone) aria-hidden="true" tabindex="-1" @endif>
                <span class="rf-pole" aria-hidden="true">
                  <span class="rf-cloth rf-flag" style="--i:{{ $d['sprite'] }}"></span>
                </span>
                <span class="rf-flagname">{{ $d['name'] }}</span>
              </button>
            @endforeach
          @endforeach
        </div>
      </div>
    </div>

    <div class="rf-wrap">
      <div class="rf-hero__grid">
        <div class="rf-hero__copy">
          <p class="rf-eyebrow">Referral program</p>
          <h1>Refer a student.<br>Change a future.<br><span class="rf-accent">Earn rewards.</span></h1>
          <p class="rf-hero__sub">Know someone planning to study abroad? Refer them to One Degree Advisory —
            we guide them from shortlist to visa, and you earn a reward once they enrol.</p>

          <div class="rf-hero__ctas">
            <a class="rf-btn rf-btn--primary" href="#rf-form"><i data-lucide="gift"></i> Refer a student</a>
            <a class="rf-btn rf-btn--ghost" href="#rf-how"><i data-lucide="arrow-right"></i> How it works</a>
          </div>

          <div class="rf-stats">
            <div class="rf-stat"><b>500+</b><small>Students guided</small></div>
            <div class="rf-stat"><b>{{ count($destinations) }}+</b><small>Study destinations</small></div>
            <div class="rf-stat"><b>95%</b><small>Visa success</small></div>
            <div class="rf-stat"><b class="rf-stat--word">Unlimited</b><small>Referrals per person</small></div>
          </div>
        </div>

        <div class="rf-game">
          <p class="rf-eyebrow">Click to play</p>
          <h3>Spin &amp; discover your destination</h3>
          <div class="rf-wheel-outer">
            <div class="rf-wheel-pointer" aria-hidden="true"></div>
            <div class="rf-wheel" data-rf-wheel>
              @foreach($wheel as $i => $seg)
                @php
                  // Labels run radially from the hub. Past the horizontal a
                  // straight rotate() would print them upside down (the source
                  // design had exactly that), so the lower half is rotated the
                  // long way round and pushed outward instead of inward — same
                  // physical position, text the right way up.
                  $angle = $i * (360 / count($wheel)) + (360 / count($wheel)) / 2;
                  $flipped = $angle > 90 && $angle < 270;
                  $transform = $flipped
                      ? 'rotate('.($angle + 180).'deg) translateY(92px)'
                      : 'rotate('.$angle.'deg) translateY(-92px)';
                @endphp
                {{-- The coloured wedges come from a conic-gradient set in the script. --}}
                <div class="rf-seg @if($flipped) rf-seg--flipped @endif" style="transform:{{ $transform }};">
                  <span class="rf-flag" style="--i:{{ $seg['sprite'] }}"></span>
                  <span>{{ $seg['name'] }}</span>
                </div>
              @endforeach
            </div>
            <span class="rf-wheel-hub" aria-hidden="true"><i data-lucide="globe"></i></span>
          </div>
          <button type="button" class="rf-btn rf-btn--primary" data-rf-spin>
            <i data-lucide="sparkles"></i> Spin the Wheel
          </button>
          <p class="rf-game__result" data-rf-result role="status" aria-live="polite">Give it a spin!</p>
        </div>
      </div>
    </div>
  </section>

  {{-- ═══════════════════ Who can refer ═══════════════════ --}}
  <section class="rf-paper">
    <div class="rf-wrap">
      <div class="rf-head rf-reveal">
        <p class="rf-eyebrow">Who can refer</p>
        <h2>Anyone in your circle can refer</h2>
        <p>If you know someone dreaming of studying abroad, you are eligible — whatever your relationship to us.</p>
      </div>

      <div class="rf-who__layout">
        <div class="rf-who__list">
          @foreach($whoCanRefer as $who)
            <div class="rf-who-card rf-reveal">
              <span class="rf-who-icon" aria-hidden="true"><i data-lucide="{{ $who['icon'] }}"></i></span>
              <div>
                <h3>{{ $who['title'] }}</h3>
                <p>{{ $who['text'] }}</p>
              </div>
            </div>
          @endforeach
        </div>

        <div class="rf-who__art rf-reveal">
          <picture>
            <source type="image/webp" srcset="{{ asset('assets/referral/refer-and-earn.webp') }}">
            <img src="{{ asset('assets/referral/refer-and-earn.jpg') }}" width="700" height="1050"
                 loading="lazy" decoding="async"
                 alt="A student sharing One Degree Advisory with a friend and earning a referral reward">
          </picture>
        </div>
      </div>
    </div>
  </section>

  {{-- ═══════════════════ How it works ═══════════════════ --}}
  <section id="rf-how">
    <div class="rf-wrap">
      <div class="rf-head rf-reveal">
        <p class="rf-eyebrow">How it works</p>
        <h2>Four simple steps</h2>
        <p>From referral to reward — a transparent process, start to finish.</p>
      </div>

      <ol class="rf-flow rf-flow--process">
        @foreach($howItWorks as $step)
          <li class="rf-flow__step rf-reveal">
            <span class="rf-flow__marker" aria-hidden="true">
              <i data-lucide="{{ $step['icon'] }}"></i>
              <span class="rf-flow__num">{{ $step['n'] }}</span>
            </span>
            {{-- Step one is the only actionable stage, so only it is a link. --}}
            <{{ ($step['link'] ?? false) ? 'a' : 'div' }} class="rf-flow__card"
              @if($step['link'] ?? false) href="#rf-form" @endif>
              <h3>{{ $step['title'] }}</h3>
              <p>{{ $step['text'] }}</p>
              @if($step['link'] ?? false)
                <span class="rf-step__link">Fill the form <i data-lucide="arrow-right"></i></span>
              @endif
            </{{ ($step['link'] ?? false) ? 'a' : 'div' }}>
          </li>
        @endforeach
      </ol>
    </div>
  </section>

  {{-- ═══════════════════ Reward timeline ═══════════════════ --}}
  <section class="rf-paper">
    <div class="rf-wrap">
      <div class="rf-head rf-reveal">
        <p class="rf-eyebrow">Reward timeline</p>
        <h2>When does the reward arrive?</h2>
        <p>The reward follows the student's enrolment, so here is exactly what has to happen first.</p>
      </div>

      <ol class="rf-flow rf-flow--reward">
        @foreach($rewardSteps as $step)
          <li class="rf-flow__step rf-reveal">
            <span class="rf-flow__marker" aria-hidden="true">
              <i data-lucide="{{ $step['icon'] }}"></i>
              <span class="rf-flow__num">{{ $step['n'] }}</span>
            </span>
            <div class="rf-flow__card">
              <h3>{{ $step['title'] }}</h3>
              <p>{{ $step['text'] }}</p>
              @if($step['tag'] ?? false)
                <span class="rf-step__tag">{{ $step['tag'] }}</span>
              @endif
            </div>
          </li>
        @endforeach
      </ol>
    </div>
  </section>

  {{-- ═══════════════════ Referral form (CRM lead) ═══════════════════ --}}
  <section id="rf-form">
    <div class="rf-wrap">
      <div class="rf-head rf-reveal">
        <p class="rf-eyebrow">Referral form</p>
        <h2>Submit a referral in minutes</h2>
        <p>We only need enough to reach the student and to know who to credit.</p>
      </div>

      <form class="rf-form rf-reveal" method="POST" action="{{ route('referral.submit') }}"
            data-referral-form novalidate>
        @csrf

        <p class="rf-form__legend"><i data-lucide="user-check"></i> Your details</p>
        <div class="rf-grid">
          <div class="rf-field">
            <label for="rf-referrer-name">Your full name</label>
            <input type="text" id="rf-referrer-name" name="referrer_name" maxlength="120" required autocomplete="name">
          </div>
          <div class="rf-field">
            <label for="rf-referrer-phone">Your mobile number</label>
            <input type="tel" id="rf-referrer-phone" name="referrer_phone" maxlength="40"
                   pattern="[0-9+()\-\s]{7,40}" required autocomplete="tel">
          </div>
          <div class="rf-field rf-field--full">
            <label for="rf-referrer-email">Your email address</label>
            <input type="email" id="rf-referrer-email" name="referrer_email" maxlength="190" required autocomplete="email">
          </div>
        </div>

        <p class="rf-form__legend"><i data-lucide="graduation-cap"></i> The student you are referring</p>
        <div class="rf-grid">
          <div class="rf-field">
            <label for="rf-student-name">Student's full name</label>
            <input type="text" id="rf-student-name" name="student_name" maxlength="120" required>
          </div>
          <div class="rf-field">
            <label for="rf-student-phone">Student's mobile number</label>
            <input type="tel" id="rf-student-phone" name="student_phone" maxlength="40"
                   pattern="[0-9+()\-\s]{7,40}" required>
          </div>
          <div class="rf-field rf-field--full">
            <label for="rf-student-email">Student's email address</label>
            <input type="email" id="rf-student-email" name="student_email" maxlength="190" required>
          </div>
          <div class="rf-field">
            <label for="rf-level">Preferred study level</label>
            <select id="rf-level" name="level" required>
              <option value="">Choose one</option>
              @foreach($levels as $level)
                <option value="{{ $level }}">{{ $level }}</option>
              @endforeach
            </select>
          </div>
          <div class="rf-field">
            <label for="rf-country">Preferred country</label>
            <select id="rf-country" name="country" required>
              <option value="">Choose one</option>
              @foreach($countries as $country)
                <option value="{{ $country }}">{{ $country }}</option>
              @endforeach
            </select>
          </div>
          <div class="rf-field rf-field--full">
            <label for="rf-notes">Anything we should know? — optional</label>
            <textarea id="rf-notes" name="notes" maxlength="1000"
                      placeholder="e.g. they are targeting a September intake, or already have IELTS."></textarea>
          </div>
        </div>

        <label class="rf-consent">
          <input type="checkbox" name="consent" value="1" required>
          <span>I confirm I have the student's permission to share their contact details with One Degree Advisory,
            and that I am not referring myself.</span>
        </label>

        <div class="rf-form__submit">
          <button type="submit" class="rf-btn rf-btn--primary"><i data-lucide="gift"></i> <span>Submit referral</span></button>
        </div>
        <p class="rf-form__note">Your details are used only to process this referral and to pay your reward.</p>
      </form>
    </div>
  </section>

  {{-- ═══════════════════ Terms ═══════════════════ --}}
  <section class="rf-paper">
    <div class="rf-wrap">
      <div class="rf-head rf-reveal">
        <p class="rf-eyebrow">Terms &amp; conditions</p>
        <h2>The fine print, made simple</h2>
      </div>

      <div class="rf-acc rf-reveal">
        @foreach($terms as $i => $term)
          <div class="rf-acc__item">
            <h3>
              <button type="button" class="rf-acc__q" data-rf-acc
                      aria-expanded="false" aria-controls="rf-acc-{{ $i }}">
                {{ $term['q'] }}
                <i data-lucide="plus" aria-hidden="true"></i>
              </button>
            </h3>
            <div class="rf-acc__a" id="rf-acc-{{ $i }}" role="region">
              <p>{{ $term['a'] }}</p>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ═══════════════════ Country popup ═══════════════════ --}}
  <div class="rf-modal" data-rf-modal hidden>
    <div class="rf-modal__scrim" data-rf-close></div>
    <div class="rf-modal__card" role="dialog" aria-modal="true" aria-labelledby="rf-modal-title" tabindex="-1">
      <button type="button" class="rf-modal__x" data-rf-close aria-label="Close"><i data-lucide="x"></i></button>
      <span class="rf-modal__flag rf-flag" data-rf-modal-flag aria-hidden="true"></span>
      <h3 id="rf-modal-title" data-rf-modal-name></h3>
      <p class="rf-modal__q" data-rf-modal-q></p>
      <ul class="rf-modal__list" data-rf-modal-list></ul>
      <a class="rf-btn rf-btn--primary" href="#rf-form" data-rf-modal-cta>
        <i data-lucide="gift"></i> Refer a student here
      </a>
    </div>
  </div>

</div>
</main>

<script>
(function () {
  'use strict';

  var root = document.getElementById('ref-page');
  if (!root) return;

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var COUNTRIES = @json($countryJs);
  var WHEEL = @json($wheel);

  /* ─────────────── Reveal on scroll (replaces the AOS dependency) ─────────────── */
  var reveals = root.querySelectorAll('.rf-reveal');
  if ('IntersectionObserver' in window && reveals.length && !reduceMotion) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) { entry.target.classList.add('is-in'); io.unobserve(entry.target); }
      });
    }, { threshold: 0.12 });
    reveals.forEach(function (el) { io.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add('is-in'); });
  }

  /* ─────────────── Country popup ─────────────── */
  var modal = root.querySelector('[data-rf-modal]');
  var lastFocus = null;
  var closeTimer = null;

  function closeModal() {
    if (!modal || modal.hidden) return;
    modal.classList.remove('is-open');
    clearTimeout(closeTimer);
    closeTimer = setTimeout(function () { modal.hidden = true; }, 260);
    document.body.style.overflow = '';
    if (lastFocus && lastFocus.focus) { try { lastFocus.focus({ preventScroll: true }); } catch (e) {} }
  }

  function openModal(code) {
    var data = null;
    for (var i = 0; i < COUNTRIES.length; i++) { if (COUNTRIES[i].code === code) { data = COUNTRIES[i]; break; } }
    if (!data || !modal) return;

    lastFocus = document.activeElement;
    modal.querySelector('[data-rf-modal-flag]').style.setProperty('--i', data.sprite);
    modal.querySelector('[data-rf-modal-name]').textContent = data.name;
    modal.querySelector('[data-rf-modal-q]').textContent = 'Why study in ' + data.name + '?';

    var list = modal.querySelector('[data-rf-modal-list]');
    list.innerHTML = '';
    data.why.forEach(function (line) {
      var li = document.createElement('li');
      var icon = document.createElement('i');
      icon.setAttribute('data-lucide', 'check');
      var span = document.createElement('span');
      span.textContent = line;
      li.appendChild(icon);
      li.appendChild(span);
      list.appendChild(li);
    });

    modal.hidden = false;
    clearTimeout(closeTimer);
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(function () {
      modal.classList.add('is-open');
      if (window.lucide) window.lucide.createIcons();
      var card = modal.querySelector('.rf-modal__card');
      if (card) { try { card.focus({ preventScroll: true }); } catch (e) {} }
    });
  }

  root.querySelectorAll('[data-rf-country]').forEach(function (btn) {
    btn.addEventListener('click', function () { openModal(btn.getAttribute('data-rf-country')); });
  });

  if (modal) {
    modal.addEventListener('click', function (event) {
      if (event.target.closest('[data-rf-close]')) closeModal();
    });
    // The popup's CTA jumps to the form, so it must close on the way.
    var cta = modal.querySelector('[data-rf-modal-cta]');
    if (cta) cta.addEventListener('click', closeModal);
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeModal();
    });
  }

  /* ─────────────── Spin & discover ─────────────── */
  var wheel = root.querySelector('[data-rf-wheel]');
  var spinBtn = root.querySelector('[data-rf-spin]');
  var result = root.querySelector('[data-rf-result]');

  if (wheel && spinBtn && WHEEL.length) {
    // The reference design's wedge palette, kept exactly: periwinkle, orange,
    // teal, gold, lilac, pink, mint, sky — in this order, one per segment.
    var RAMP = ['#7c83fd', '#ff9f68', '#4ecdc4', '#ffd166', '#a39bff', '#ff8fa3', '#5fd9c4', '#74b9ff'];
    var segAngle = 360 / WHEEL.length;
    var stops = WHEEL.map(function (seg, i) {
      return RAMP[i % RAMP.length] + ' ' + (i * segAngle) + 'deg ' + ((i + 1) * segAngle) + 'deg';
    });
    wheel.style.background = 'conic-gradient(' + stops.join(', ') + ')';

    var rotation = 0;
    var timer = null;

    function land(index) {
      var seg = WHEEL[index];
      result.textContent = '';
      var lead = document.createElement('span');
      lead.textContent = 'You landed on ' + seg.name + '. ';
      var more = document.createElement('button');
      more.type = 'button';
      more.textContent = 'See why →';
      more.addEventListener('click', function () { openModal(seg.code); });
      result.appendChild(lead);
      result.appendChild(more);
      spinBtn.disabled = false;
    }

    spinBtn.addEventListener('click', function () {
      if (spinBtn.disabled) return;
      spinBtn.disabled = true;
      result.textContent = 'Spinning…';

      var index = Math.floor(Math.random() * WHEEL.length);
      // Bring the chosen wedge's centre under the pointer at 12 o'clock.
      var offset = (360 - (index * segAngle + segAngle / 2) + 360) % 360;
      rotation = rotation - (rotation % 360) + (5 * 360) + offset;
      wheel.style.transform = 'rotate(' + rotation + 'deg)';

      clearTimeout(timer);
      // Reduced motion skips the 4.2s spin transition, so resolve immediately.
      timer = setTimeout(function () { land(index); }, reduceMotion ? 60 : 4300);
    });
  }

  /* ─────────────── Terms accordion ─────────────── */
  root.querySelectorAll('[data-rf-acc]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var item = btn.closest('.rf-acc__item');
      var panel = document.getElementById(btn.getAttribute('aria-controls'));
      var isOpen = item.classList.contains('is-open');

      // One open at a time.
      root.querySelectorAll('.rf-acc__item.is-open').forEach(function (open) {
        open.classList.remove('is-open');
        open.querySelector('.rf-acc__q').setAttribute('aria-expanded', 'false');
        open.querySelector('.rf-acc__a').style.maxHeight = null;
      });

      if (!isOpen) {
        item.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
        panel.style.maxHeight = panel.scrollHeight + 'px';
      }
    });
  });

  /* Draw this page's icons. Lucide loads deferred, so try now and again on load. */
  function drawIcons() { if (window.lucide) window.lucide.createIcons(); }
  drawIcons();
  window.addEventListener('load', drawIcons);
})();
</script>
@endsection
