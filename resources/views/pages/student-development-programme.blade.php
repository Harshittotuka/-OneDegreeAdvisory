{{-- Student Development Programme (/student-development-programme).

     Reached from the home hero's "Student Development Programme" button, which
     shipped as a "coming soon" placeholder — HeroContent::linkShippedActions
     back-fills the href on read, because storage/app/home-hero.json is
     gitignored and would otherwise keep the button dead after a deploy.

     The page introduces Infolith and presents its technology courses.

     Sections: hero → course mosaic → course benefits → skills marquee → FAQ.

     Everything is scoped under #sdp-page so the generic class names can never
     collide with the shared styles.css / stripe-nav.css chrome, and so the page
     opts out of the site-wide `main p { text-align: justify }` rule by setting
     its own alignment (an ID-scoped declaration on the <p> itself, which is
     what that rule's comment asks for). --}}
@extends('layouts.app')

@php
    $infolithUrl = 'https://infolith.in/';
    $coursesUrl = 'https://infolith.in/internship/cs';

    /**
     * The track mosaic.
     *
     * `c` / `r` are column / row spans on a six-column grid, and they are chosen
     * so the tiles fill the grid exactly — no `grid-auto-flow:
     * dense` guesswork, no orphan gap in the last row:
     *
     *   row 1-2 : About Infolith (3x2) │ AI & ML (3x1)
     *                                  │ Full Stack (3x1)
     *   row 3-4 : three equal course cards per row (2x1 each)
     * `kind` picks the card shape: `branch` = the big dark discipline card,
     * `track` = a full card with copy, `mini` = a narrow icon + title tile.
     * `tone` picks the surface: navy / accent / paper.
     */
    $tracks = [
        [
            'kind' => 'branch', 'tone' => 'navy', 'c' => 3, 'r' => 2,
            'icon' => 'building-2', 'badge' => 'Course partner',
            'title' => 'About Infolith',
            'text' => 'Infolith is a product studio that designs and builds websites, internal dashboards and automation tools. It also offers practical technology courses across development, data, design, AI and security.',
            'chips' => ['Development', 'AI / ML', 'Data', 'Design', 'Security', 'Mobile'],
            'cta' => 'Visit Infolith', 'href' => $infolithUrl,
        ],
        [
            'kind' => 'track', 'tone' => 'paper', 'c' => 3, 'r' => 1,
            'icon' => 'brain-circuit', 'title' => 'AI & Machine Learning',
            'text' => 'Regression, classification, model evaluation and LLM-powered applications.',
            'meta' => 'Infolith course', 'href' => $coursesUrl,
        ],
        [
            'kind' => 'track', 'tone' => 'paper', 'c' => 3, 'r' => 1,
            'icon' => 'code-2', 'title' => 'Full-Stack Web Development',
            'text' => 'Build modern full-stack web applications with the MERN stack and current tooling.',
            'meta' => 'Infolith course', 'href' => $coursesUrl,
        ],
        [
            'kind' => 'track', 'tone' => 'paper', 'c' => 2, 'r' => 1,
            'icon' => 'component', 'title' => 'React.js Development',
            'text' => 'Learn JSX, components, state management, application testing and React tooling.',
            'meta' => 'Infolith course', 'href' => $coursesUrl,
        ],
        [
            'kind' => 'track', 'tone' => 'paper', 'c' => 2, 'r' => 1,
            'icon' => 'terminal', 'title' => 'Python Backend Development',
            'text' => 'Build Python backends with Django, FastAPI, databases and deployment tooling.',
            'meta' => 'Infolith course', 'href' => $coursesUrl,
        ],
        [
            'kind' => 'track', 'tone' => 'paper', 'c' => 2, 'r' => 1,
            'icon' => 'database', 'title' => 'Data Science & Analytics',
            'text' => 'Explore SQL, data analysis, visualisation and data-driven storytelling.',
            'meta' => 'Infolith course', 'href' => $coursesUrl,
        ],
        [
            'kind' => 'track', 'tone' => 'paper', 'c' => 2, 'r' => 1,
            'icon' => 'palette', 'title' => 'UI / UX Design',
            'text' => 'Create user flows, wireframes, prototypes and clear interfaces for web and mobile.',
            'meta' => 'Infolith course', 'href' => $coursesUrl,
        ],
        [
            'kind' => 'track', 'tone' => 'paper', 'c' => 2, 'r' => 1,
            'icon' => 'shield-check', 'title' => 'Cybersecurity Fundamentals',
            'text' => 'Study ethical-hacking basics, defensive thinking and secure coding practices.',
            'meta' => 'Infolith course', 'href' => $coursesUrl,
        ],
        [
            'kind' => 'track', 'tone' => 'paper', 'c' => 2, 'r' => 1,
            'icon' => 'smartphone', 'title' => 'Flutter App Development',
            'text' => 'Develop cross-platform mobile applications with Flutter and modern app tooling.',
            'meta' => 'Infolith course', 'href' => $coursesUrl,
        ],
    ];

    $benefits = [
        [
            'icon' => 'compass', 'kicker' => 'Programme direction',
            'title' => 'Choose a more informed specialisation',
            'text' => 'Exploring development, data, design, mobile, AI and security can help you identify which subject area genuinely fits your intended degree abroad.',
        ],
        [
            'icon' => 'file-pen-line', 'kicker' => 'Application story',
            'title' => 'Write a more specific statement of purpose',
            'text' => 'Relevant course knowledge gives you genuine topics to discuss when explaining your academic interests, intended specialisation and future direction.',
        ],
        [
            'icon' => 'book-open-check', 'kicker' => 'Academic readiness',
            'title' => 'Prepare for related university modules',
            'text' => 'Familiarity with core terminology and tools can make it easier to understand module descriptions and prepare for coursework in your chosen field.',
        ],
        [
            'icon' => 'search-check', 'kicker' => 'University research',
            'title' => 'Compare curricula with more confidence',
            'text' => 'Knowing the difference between areas such as machine learning, analytics, backend development and UI / UX helps you compare university modules more carefully.',
        ],
        [
            'icon' => 'messages-square', 'kicker' => 'Interviews',
            'title' => 'Discuss your interests more clearly',
            'text' => 'Subject knowledge can help you give focused answers about why you selected a programme instead of relying on broad or generic statements.',
        ],
        [
            'icon' => 'route', 'kicker' => 'Course transition',
            'title' => 'Explore a new field before applying',
            'text' => 'If you are considering a shift toward computing, these courses can help you explore the subject before committing to an overseas programme.',
        ],
    ];

    $marquee = ['Full-Stack Development', 'React.js', 'Python', 'Django', 'FastAPI', 'Machine Learning', 'Data Analytics', 'SQL', 'UI / UX Design', 'Figma', 'Cybersecurity', 'Secure Coding', 'Flutter', 'Mobile Development'];

    $faqs = [
        [
            'q' => 'How can these courses help me plan for study abroad?',
            'a' => 'They can help you explore possible specialisations, understand the language used in university curricula and decide whether your interests are closer to software development, AI, data, design, mobile applications or cybersecurity.',
        ],
        [
            'q' => 'Which courses relate to AI or data-focused degrees?',
            'a' => 'AI and Machine Learning introduces model building and evaluation. Data Science and Analytics covers SQL, analysis and visualisation, while Python Backend Development adds useful programming and server-side foundations.',
        ],
        [
            'q' => 'Which courses relate to software or application development?',
            'a' => 'Full-Stack Web Development provides a broad application-development view, React.js Development focuses on front-end applications, Python Backend Development covers server-side systems and Flutter App Development focuses on cross-platform mobile applications.',
        ],
        [
            'q' => 'Can course knowledge support my statement of purpose?',
            'a' => 'It can give you specific and truthful material for explaining what you want to study and why. It should support your academic story rather than replace grades, university prerequisites, language scores or other required evidence.',
        ],
        [
            'q' => 'How should I use these courses when shortlisting universities?',
            'a' => 'Compare the subjects you find most relevant with the modules offered by each university. Look beyond programme titles and check whether the curriculum actually covers your preferred areas, tools and academic direction.',
        ],
        [
            'q' => 'Do these courses guarantee admission to an overseas university?',
            'a' => 'No. Admission decisions depend on the university and the complete application, including academic history, prerequisites and required documents. These courses are best used to explore subjects and communicate a clearer academic direction.',
        ],
        [
            'q' => 'Where can I review each course before choosing one?',
            'a' => 'Select a course card to open the detailed Infolith catalogue, then compare that subject with the entry requirements and modules of the overseas programmes you are considering.',
        ],
    ];

@endphp

@push('head')
<style>
  /* The shared layout fades the body dark toward the footer; this page runs its
     own cream unbroken down to the footer instead. */
  body.sdp-page-body{ background:#fcf9f4; background-image:none; }

  #sdp-page{
    /* The design's navy and orange are the site's own cream-theme --navy /
       --teal, so nothing here invents a new palette. */
    --sdp-navy:#0a004d; --sdp-navy-mid:#12085f; --sdp-navy-lift:#2a178d;
    --sdp-orange:#ff5e32; --sdp-orange-deep:#d8431d;
    --sdp-cream:#fcf9f4; --sdp-ink:#1c1c19; --sdp-muted:#474553;
    --sdp-line:#e5e2dd; --sdp-soft:#f6f3ee;
    --sdp-serif:"Cormorant Garamond", Georgia, serif;
    --sdp-sans:"Manrope", system-ui, -apple-system, "Segoe UI", sans-serif;
    --sdp-pad:clamp(20px, 5vw, 64px);
    --sdp-ease:cubic-bezier(.2,.7,.2,1);
    /* Motion vocabulary. --sdp-in is a long decelerate for anything arriving,
       --sdp-out a sharp accelerate for anything leaving, and --sdp-morph the
       single curve the FLIP uses, so the grid's height and the card travelling
       inside it are always on the same timing. */
    --sdp-in:cubic-bezier(.16,1,.3,1);
    --sdp-out:cubic-bezier(.4,0,.2,1);
    --sdp-morph:cubic-bezier(.2,0,0,1);
    font-family:var(--sdp-sans);
    color:var(--sdp-ink);
    background:var(--sdp-cream);
    overflow-x:clip;
  }
  #sdp-page *{ box-sizing:border-box; }
  #sdp-page h1, #sdp-page h2{ font-family:var(--sdp-serif); font-weight:700; line-height:1.06;
    color:var(--sdp-navy); margin:0; letter-spacing:-.01em; }
  #sdp-page h3, #sdp-page h4{ font-family:var(--sdp-sans); font-weight:700; line-height:1.25;
    color:var(--sdp-navy); margin:0; }
  /* Explicit alignment, not inherited: styles.css justifies every `main p`, and a
     declaration on the paragraph itself beats an inherited value from any
     wrapper however specific. Left here, re-centred per block below. */
  #sdp-page p{ margin:0; color:var(--sdp-muted); line-height:1.65; text-align:left; }
  #sdp-page li{ text-align:left; }
  #sdp-page a{ color:var(--sdp-navy); text-decoration:none; }
  #sdp-page a:hover{ color:var(--sdp-orange); }
  #sdp-page .sdp-wrap{ max-width:1440px; margin:0 auto; padding-inline:var(--sdp-pad); }
  #sdp-page .sdp-eyebrow{ font-size:12px; font-weight:800; letter-spacing:.14em;
    text-transform:uppercase; color:var(--sdp-orange); margin:0 0 12px; }
  #sdp-page .sdp-head{ text-align:center; max-width:660px; margin:0 auto clamp(34px,4vw,50px); }
  #sdp-page .sdp-head h2{ font-size:clamp(30px,3.6vw,44px); margin-bottom:14px; }
  #sdp-page .sdp-head p{ font-size:17.5px; text-align:center; }
  #sdp-page .sdp-section{ padding:clamp(62px,8vw,96px) 0; }

  /* ── Buttons ── */
  #sdp-page .sdp-btn{ display:inline-flex; align-items:center; justify-content:center; gap:9px;
    font-size:15.5px; font-weight:700; line-height:1; padding:16px 30px; border-radius:3px;
    border:1px solid transparent; cursor:pointer; text-align:center;
    transition:transform .18s ease, box-shadow .18s ease, background .18s ease, color .18s ease; }
  #sdp-page .sdp-btn i{ width:17px; height:17px; }
  #sdp-page .sdp-btn:hover{ transform:translateY(-2px); }
  #sdp-page .sdp-btn--orange{ background:var(--sdp-orange); color:#fff;
    box-shadow:0 10px 26px -12px rgba(255,94,50,.85); }
  #sdp-page .sdp-btn--orange:hover{ color:#fff; box-shadow:0 16px 32px -12px rgba(255,94,50,.9); }
  #sdp-page .sdp-btn--navy{ background:var(--sdp-navy); color:#fff;
    box-shadow:0 10px 26px -14px rgba(10,0,77,.9); }
  #sdp-page .sdp-btn--navy:hover{ color:#fff; }
  #sdp-page .sdp-btn--outline{ background:transparent; color:#fff; border-color:rgba(255,255,255,.62); }
  #sdp-page .sdp-btn--outline:hover{ background:#fff; color:var(--sdp-navy); }

  /* ── Reveal on scroll · SECTION HEADS ONLY ──────────────────────────────
     The mosaic cards deliberately do not use this. A card already carries a
     hover `transition` for transform/box-shadow, and `#sdp-page .sdp-card` and
     `#sdp-page .sdp-reveal` have identical specificity — so on source order the
     card's short hover transition won, `transform` entered over .34s with the
     wrong curve, and `opacity` (absent from that list) was never transitioned
     at all. It snapped. Cards use the keyframed entrance further down, which
     cannot collide with a transition. */
  /* Gated on html.js (set inline in <head> by the layout) for the same reason the
     card entrance is: without it, a page whose scripts are blocked renders the
     section heads and the in-grid counsellor CTA as invisible-but-clickable
     blank space. No script, no hiding. */
  html.js #sdp-page .sdp-reveal{ opacity:0; transform:translateY(26px);
    transition:opacity .8s var(--sdp-in), transform .8s var(--sdp-in); }
  html.js #sdp-page .sdp-reveal.is-in{ opacity:1; transform:none; }

  /* ══════════════════ Hero ══════════════════ */
  #sdp-page .sdp-hero{ position:relative; overflow:hidden;
    background:radial-gradient(120% 90% at 50% -10%, var(--sdp-navy-lift) 0%, var(--sdp-navy-mid) 42%, var(--sdp-navy) 100%); }
  #sdp-page .sdp-hero__layer{ position:absolute; inset:0; pointer-events:none; }
  /* The receding grid: two repeating gradients on one element, rotated into
     perspective and drifting on background-position. Masked so the lines fade
     out before they reach the copy. */
  #sdp-page .sdp-hero__grid{
    background-image:
      repeating-linear-gradient(90deg, rgba(255,255,255,.13) 0 1px, transparent 1px 74px),
      repeating-linear-gradient(0deg,  rgba(255,255,255,.13) 0 1px, transparent 1px 74px);
    transform:perspective(700px) rotateX(62deg) scale(2.4); transform-origin:50% 100%;
    -webkit-mask-image:linear-gradient(to top, #000 0%, rgba(0,0,0,.35) 46%, transparent 78%);
    mask-image:linear-gradient(to top, #000 0%, rgba(0,0,0,.35) 46%, transparent 78%);
    animation:sdpGrid 9s linear infinite; will-change:background-position;
  }
  #sdp-page .sdp-hero__orbs span{ position:absolute; border-radius:50%; filter:blur(66px);
    opacity:.42; will-change:transform; }
  #sdp-page .sdp-hero__orbs span:nth-child(1){ top:-14%; left:-6%; width:46%; height:70%;
    background:radial-gradient(circle at center, rgba(111,130,255,.6), rgba(111,130,255,0) 70%);
    animation:sdpDrift 17s ease-in-out infinite; }
  #sdp-page .sdp-hero__orbs span:nth-child(2){ top:-24%; right:-8%; width:44%; height:74%;
    background:radial-gradient(circle at center, rgba(255,94,50,.42), rgba(255,94,50,0) 70%);
    animation:sdpDrift 21s ease-in-out infinite 3s; }
  #sdp-page .sdp-hero__orbs span:nth-child(3){ bottom:-30%; left:30%; width:44%; height:66%;
    background:radial-gradient(circle at center, rgba(175,200,255,.34), rgba(175,200,255,0) 70%);
    animation:sdpDrift 24s ease-in-out infinite 6s; }
  /* Floating skill chips. Decorative and dropped below 900px, where they would
     either overlap the headline or shrink into noise. */
  #sdp-page .sdp-hero__chip{ position:absolute; display:inline-flex; align-items:center; gap:8px;
    padding:9px 15px; border-radius:999px; font-size:13px; font-weight:700; color:#fff;
    background:rgba(255,255,255,.09); border:1px solid rgba(255,255,255,.2);
    backdrop-filter:blur(6px); white-space:nowrap; will-change:transform;
    animation:sdpFloat 8s ease-in-out infinite; }
  #sdp-page .sdp-hero__chip i{ width:15px; height:15px; color:#ffb59d; }
  #sdp-page .sdp-hero__chip:nth-of-type(1){ top:19%; left:5%;  animation-delay:0s; }
  #sdp-page .sdp-hero__chip:nth-of-type(2){ top:66%; left:8%;  animation-delay:1.4s; }
  #sdp-page .sdp-hero__chip:nth-of-type(3){ top:26%; right:6%; animation-delay:.7s; }
  #sdp-page .sdp-hero__chip:nth-of-type(4){ top:71%; right:9%; animation-delay:2.1s; }
  #sdp-page .sdp-hero__inner{ position:relative; z-index:5; max-width:1180px; margin:0 auto;
    padding:clamp(84px,11vw,124px) var(--sdp-pad) clamp(72px,9vw,100px);
    display:flex; flex-direction:column; align-items:center; text-align:center; }
  #sdp-page .sdp-hero .sdp-eyebrow{ color:#ffb59d; }
  /* Capped in ch, not px: the display serif runs narrow, so a px cap tuned at the
     top of the clamp wraps to five lines at the bottom of it. */
  #sdp-page .sdp-hero h1{ color:#fff; font-size:clamp(38px,5.6vw,64px); max-width:24ch; margin-bottom:20px; }
  #sdp-page .sdp-hero h1 em{ font-style:italic; color:#ffb59d; }
  #sdp-page .sdp-hero__lead{ color:rgba(255,255,255,.8); font-size:clamp(16px,1.4vw,18.5px);
    max-width:62ch; text-align:center; margin-bottom:30px; }
  @keyframes sdpGrid{ from{ background-position:0 0, 0 0; } to{ background-position:0 74px, 0 74px; } }
  @keyframes sdpDrift{ 0%,100%{ transform:translate3d(0,0,0) scale(1); } 50%{ transform:translate3d(3%,5%,0) scale(1.1); } }
  @keyframes sdpFloat{ 0%,100%{ transform:translateY(0); } 50%{ transform:translateY(-14px); } }

  /* ══════════════════ Track mosaic ══════════════════ */
  /* minmax(0,1fr), not a bare 1fr: `1fr` means minmax(AUTO,1fr), so a wide
     child's min-content size can inflate its own track and leave the columns
     unequal. That silently broke the width parity the collapse relies on — the
     About card measured 633px expanded against 619px collapsed, one whole gap
     out, which forced its FLIP to scale. Pinning the floor to 0 keeps every
     track at exactly 1/n and the two layouts at exactly the same width. */
  #sdp-page .sdp-mosaic{ display:grid; grid-template-columns:repeat(6,minmax(0,1fr));
    grid-auto-rows:minmax(178px,auto); gap:14px;
    /* Containing block for a card that is lifted out of flow mid-toggle. */
    position:relative; }
  #sdp-page .sdp-card{
    /* --c / --r are the column / row spans, set per card in the markup. */
    grid-column:span var(--c,2); grid-row:span var(--r,1);
    position:relative; overflow:hidden; display:flex; flex-direction:column;
    padding:clamp(20px,2.2vw,28px); border-radius:18px; border:1px solid var(--sdp-line);
    background:#fff; isolation:isolate;
    transition:transform .34s var(--sdp-ease), box-shadow .34s var(--sdp-ease), border-color .34s ease;
  }
  /* Cursor spotlight. --mx/--my are written by the pointer handler; without JS
     the gradient simply sits at the card's top-centre and never shows, because
     the layer only fades in on hover. */
  #sdp-page .sdp-card::before{ content:""; position:absolute; inset:0; z-index:-1; opacity:0;
    background:radial-gradient(340px circle at var(--mx,50%) var(--my,0%), rgba(255,94,50,.16), transparent 68%);
    transition:opacity .34s ease; }
  #sdp-page .sdp-card:hover{ transform:translateY(-5px); border-color:rgba(255,94,50,.42);
    box-shadow:0 22px 44px -22px rgba(10,0,77,.32); }
  #sdp-page .sdp-card:hover::before{ opacity:1; }
  #sdp-page .sdp-card:focus-visible{ outline:3px solid var(--sdp-orange); outline-offset:3px; }
  #sdp-page .sdp-card h3{ font-size:clamp(17px,1.7vw,20px); }
  #sdp-page .sdp-card p{ margin-top:9px; font-size:14.5px; }
  #sdp-page .sdp-card__meta{ margin-top:auto; padding-top:14px; font-size:11.5px; font-weight:800;
    letter-spacing:.1em; text-transform:uppercase; color:var(--sdp-muted); }
  #sdp-page .sdp-card__go{ margin-top:14px; display:inline-flex; align-items:center; gap:7px;
    font-size:14px; font-weight:800; color:var(--sdp-orange); }
  #sdp-page .sdp-card__go i{ width:15px; height:15px; transition:transform .28s var(--sdp-ease); }
  #sdp-page .sdp-card:hover .sdp-card__go i{ transform:translate(3px,-3px); }
  /* `display:flex` on the card beats the `hidden` attribute, so it needs saying. */
  #sdp-page .sdp-card[hidden]{ display:none !important; }

  /* ── Card entrance ──────────────────────────────────────────────────────
     Keyframes rather than a transition, so the entrance can never fight the
     card's hover transition. `backwards` fill holds the from-state through the
     stagger delay, and there is deliberately NO forwards fill: the animation
     finishes on the card's own natural state, which hands `transform` straight
     back to :hover with nothing left over to block it.

     Gated on html.js (the layout sets that class inline in <head>) so a page
     whose script never runs shows the cards instead of a blank grid. Retired by
     .has-entered once it has played, because un-hiding an element restarts its
     CSS animations — without that kill switch every expand would re-trigger the
     scroll entrance underneath the toggle's own animation. */
  @keyframes sdpCardIn{
    from{ opacity:0; transform:translate3d(0,30px,0) scale(.972); }
    to{ opacity:1; transform:none; }
  }
  html.js #sdp-page .sdp-mosaic:not(.is-in) .sdp-card{ opacity:0; }
  html.js #sdp-page .sdp-mosaic.is-in:not(.has-entered) .sdp-card{
    animation:sdpCardIn .68s var(--sdp-in) backwards;
    animation-delay:calc(var(--i, 0) * 58ms);
  }

  /* ── Collapse / expand ──────────────────────────────────────────────────
     While the toggle runs, the grid is pinned to a pixel height that is itself
     being animated, which needs three things said explicitly:

       • align-content:start — auto rows otherwise STRETCH to fill that pinned
         height, so the About card would grow and shrink with the box while the
         FLIP is trying to move it. This is the rule that makes the whole thing
         hold still.
       • overflow:clip — turns the height animation into the reveal itself:
         cards laid out beyond the current box are clipped rather than spilling
         over the section below while they wait their turn.
       • overflow-anchor:none — scroll anchoring otherwise tries to compensate
         for the very height change being animated, which reads as jitter. */
  /* The frame, not the grid, is the box whose height is animated — see the note
     in the markup. The grid inside keeps `height: auto` throughout, so its row
     tracks are sized from content on every frame and the boxes the FLIP measured
     stay the boxes that render.

     `align-content` is deliberately NOT set here any more. It was guarding auto
     rows against stretching inside a pinned container, and with the pin moved out
     of the grid there is no pinned container left to guard against.

     `overflow:hidden` first as a fallback for engines without `clip` (Safari under
     16): it crops identically here, it only also makes the box a scroll container,
     and this lasts one animation. Clip is applied on expand only — on collapse the
     frame starts tall and the outgoing cards need to stay visible while they fly. */
  #sdp-page .sdp-mosaic-frame.is-animating{ overflow-anchor:none; }
  #sdp-page .sdp-mosaic-frame.is-expanding{ overflow:hidden; overflow:clip; }
  #sdp-page .sdp-mosaic-frame.is-animating .sdp-card{ will-change:transform, opacity; }
  #sdp-page .sdp-mosaic-frame.is-animating .sdp-card--track{ pointer-events:none; z-index:3; }

  /* An outgoing card is lifted out of grid flow so the reflow happening behind
     it cannot move it mid-flight. `grid-area:auto` is load-bearing: an
     absolutely positioned grid child that still holds a grid placement is
     positioned against that grid AREA rather than the grid's padding box, which
     would offset every measured box by one card. */
  #sdp-page .sdp-card.is-flying{ position:absolute; grid-area:auto; margin:0; z-index:4; }
  /* FLIP maths is written against the top-left corner. */
  #sdp-page .sdp-card.is-flipping{ transform-origin:0 0; }
  /* The pointer is necessarily still on the About card when the toggle inside it
     is clicked, so :hover is live throughout its own FLIP. A WAAPI transform
     with no fill hands control back to CSS the instant it ends — straight onto
     the hover lift — and a change caused by an animation ENDING does not
     transition, so the card jerked 5px in a single frame after the motion had
     visibly finished. Suppressing the lift for the duration means the hand-back
     lands on `none`; the lift then eases in normally once the class is dropped.
     The course cards are immune because .is-animating already makes them
     pointer-events:none. */
  #sdp-page .sdp-mosaic-frame.is-animating .sdp-card--branch:hover{ transform:none; }

  /* Collapsed: four columns with the card on tracks 2-3. `span 3 of 6` and
     `span 2 of 4` are the SAME width for any container width and any gap —
     both resolve to (W - gap) / 2 — so between the two states the About card
     only ever moves, it never resizes. That is what keeps its FLIP a pure
     compositor translate instead of a scale that would smear the text. */
  #sdp-page .sdp-mosaic.is-collapsed{ grid-template-columns:repeat(4,minmax(0,1fr)); }
  #sdp-page .sdp-mosaic.is-collapsed .sdp-card--branch{ grid-column:2 / span 2; }
  /* Tile size, radius and colour for .sdp-track__icon come from the site-wide
     UNIFIED ICON-TILE SYSTEM at the end of styles.css (it is listed there).
     Deliberately no background or icon-size rule here: an #sdp-page selector
     would outrank that shared layer and this tile would drift off-system. */
  #sdp-page .sdp-track__icon{ display:inline-flex; align-items:center; justify-content:center;
    margin-bottom:14px; flex:0 0 auto; }

  /* Dark and accent cards keep their own translucent tile: the shared recipe's
     pale tile + orange glyph is built for light surfaces, and on navy or orange
     it reads as a hole in the card. Same exception the hero drawer takes. */
  #sdp-page .sdp-card__bicon{ display:inline-flex; align-items:center; justify-content:center;
    width:52px; height:52px; border-radius:16px; margin-bottom:14px; flex:0 0 auto;
    background:rgba(255,255,255,.13); border:1px solid rgba(255,255,255,.22); color:#fff;
    transition:transform .3s ease, background .3s ease; }
  #sdp-page .sdp-card__bicon i{ width:24px; height:24px; }
  #sdp-page .sdp-card:hover .sdp-card__bicon{ transform:scale(1.06) rotate(-3deg); background:rgba(255,255,255,.2); }

  /* The main Infolith introduction card. */
  #sdp-page .sdp-card--navy{ border-color:transparent; color:#fff;
    background:
      linear-gradient(145deg, rgba(10,0,77,.86), rgba(18,8,95,.68)),
      url('{{ asset('assets/student-development/infolith-hero-bg.png') }}') 24% center / cover no-repeat; }
  #sdp-page .sdp-card--navy::after{ content:""; position:absolute; inset:0; z-index:-1;
    background:radial-gradient(circle at 88% 8%, rgba(255,94,50,.24), transparent 58%); }
  #sdp-page .sdp-card--navy h3{ color:#fff; font-size:clamp(21px,2.3vw,27px); }
  #sdp-page .sdp-card--navy p{ color:rgba(255,255,255,.76); font-size:15px; }
  #sdp-page .sdp-card--navy:hover{ box-shadow:0 26px 54px -22px rgba(10,0,77,.6); }
  #sdp-page .sdp-card__badge{ align-self:flex-start; margin-bottom:16px; padding:6px 13px;
    border-radius:999px; font-size:11px; font-weight:800; letter-spacing:.12em; text-transform:uppercase;
    background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.24); color:#fff; }
  #sdp-page .sdp-card__chips{ display:flex; flex-wrap:wrap; gap:7px; margin-top:16px; padding:0; list-style:none; }
  #sdp-page .sdp-card__chips li{ padding:5px 11px; border-radius:999px; font-size:12px; font-weight:700;
    color:rgba(255,255,255,.9); background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.16); }
  #sdp-page .sdp-card--navy .sdp-card__go{ color:#ffb59d; margin-top:auto; padding-top:18px; }
  #sdp-page .sdp-course-toggle{ position:absolute; z-index:4; top:20px; right:20px;
    display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:42px;
    padding:10px 13px; border:1px solid rgba(255,255,255,.3); border-radius:999px;
    background:rgba(255,255,255,.14); color:#fff; font:700 12px/1 var(--sdp-sans);
    cursor:pointer; backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px);
    transition:background .22s ease, border-color .22s ease, transform .22s ease; }
  #sdp-page .sdp-course-toggle:hover{ background:rgba(255,255,255,.24); border-color:rgba(255,255,255,.48); transform:translateY(-2px); }
  #sdp-page .sdp-course-toggle:focus-visible{ outline:3px solid #ffb59d; outline-offset:3px; }
  /* The button is never disabled — a mid-flight click is queued instead (see
     the script), so it always answers. aria-busy only softens it slightly. */
  #sdp-page .sdp-course-toggle[aria-busy="true"]{ opacity:.82; }
  #sdp-page .sdp-course-toggle i{ width:16px; height:16px; transition:transform .34s var(--sdp-morph); }
  /* Driven off the attribute rather than a class, so the caret can never
     desynchronise from the state assistive tech is being told about. */
  #sdp-page .sdp-course-toggle[aria-expanded="true"] i{ transform:rotate(180deg); }

  /* ══════════════════ Course guidance strip ══════════════════ */
  /* 1 / -1 rather than a column count: the collapsed grid has four tracks and
     the phone one a single track, and a `span 6` would be clamped differently
     in each — which is how it ended up influencing the column arithmetic that
     the collapse depends on. */
  #sdp-page .sdp-strip{ grid-column:1 / -1; display:flex; flex-wrap:wrap; align-items:center;
    justify-content:space-between; gap:20px; padding:clamp(22px,2.6vw,32px);
    border-radius:18px; border:1px dashed rgba(26,0,136,.28); background:#fff; }
  #sdp-page .sdp-strip h3{ font-size:clamp(18px,2vw,22px); }
  #sdp-page .sdp-strip p{ margin-top:7px; font-size:14.5px; max-width:60ch; }

  /* ══════════════════ Benefits carousel ══════════════════ */
  #sdp-page .sdp-benefits{ background:var(--sdp-cream); border-top:1px solid var(--sdp-line); }
  #sdp-page .sdp-carousel{ position:relative; }
  #sdp-page .sdp-rail{ display:flex; gap:18px; overflow-x:auto; scroll-snap-type:x mandatory;
    scroll-behavior:smooth; padding:6px 4px 22px; margin-inline:-4px;
    scrollbar-width:none; -ms-overflow-style:none; cursor:grab; touch-action:pan-y;
    overscroll-behavior-inline:contain; -webkit-overflow-scrolling:touch; }
  #sdp-page .sdp-rail.is-dragging{ cursor:grabbing; scroll-snap-type:none; scroll-behavior:auto;
    user-select:none; -webkit-user-select:none; }
  #sdp-page .sdp-rail::-webkit-scrollbar{ display:none; }
  #sdp-page .sdp-benefit{ scroll-snap-align:start; flex:0 0 clamp(268px,31%,352px);
    display:flex; flex-direction:column; padding:clamp(24px,2.4vw,30px); border-radius:18px;
    border:1px solid var(--sdp-line); background:#fff; position:relative; overflow:hidden;
    transition:transform .3s var(--sdp-ease), box-shadow .3s var(--sdp-ease), border-color .3s ease; }
  #sdp-page .sdp-benefit:hover{ transform:translateY(-5px); border-color:rgba(255,94,50,.4);
    box-shadow:0 22px 44px -24px rgba(10,0,77,.3); }
  #sdp-page .sdp-benefit__icon{ display:inline-flex; align-items:center; justify-content:center;
    width:52px; height:52px; margin-bottom:16px; border-radius:16px;
    background:var(--sdp-soft); color:var(--sdp-orange); }
  #sdp-page .sdp-benefit__icon i{ width:24px; height:24px; }
  #sdp-page .sdp-benefit__kicker{ font-size:11px; font-weight:800; letter-spacing:.13em;
    text-transform:uppercase; color:var(--sdp-orange); margin-bottom:8px; }
  #sdp-page .sdp-benefit h3{ font-size:18.5px; margin-bottom:10px; }
  #sdp-page .sdp-benefit p{ font-size:14.5px; }
  #sdp-page .sdp-carousel__controls{ display:flex; align-items:center; justify-content:center; gap:16px; margin-top:6px; }
  #sdp-page .sdp-arrow{ width:46px; height:46px; flex:0 0 auto; display:inline-flex; align-items:center;
    justify-content:center; border-radius:50%; border:1px solid rgba(26,0,136,.2); background:#fff;
    color:var(--sdp-navy); cursor:pointer; transition:background .22s ease, color .22s ease, transform .22s ease, opacity .22s ease; }
  #sdp-page .sdp-arrow i{ width:20px; height:20px; }
  #sdp-page .sdp-arrow:hover:not(:disabled){ background:var(--sdp-navy); color:#fff; transform:translateY(-2px); }
  #sdp-page .sdp-arrow:disabled{ opacity:.34; cursor:default; }
  #sdp-page .sdp-dots{ display:flex; align-items:center; gap:8px; }
  #sdp-page .sdp-dots button{ width:9px; height:9px; padding:0; border:0; border-radius:50%;
    background:rgba(26,0,136,.22); cursor:pointer; transition:width .26s var(--sdp-ease), background .26s ease; }
  #sdp-page .sdp-dots button.is-on{ width:26px; border-radius:999px; background:var(--sdp-orange); }

  /* ══════════════════ Skills marquee ══════════════════ */
  #sdp-page .sdp-marquee{ overflow:hidden; padding:26px 0; background:var(--sdp-navy);
    -webkit-mask-image:linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent);
    mask-image:linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent); }
  #sdp-page .sdp-marquee__track{ display:flex; width:max-content; gap:14px;
    animation:sdpTicker 34s linear infinite; will-change:transform; }
  #sdp-page .sdp-marquee:hover .sdp-marquee__track{ animation-play-state:paused; }
  #sdp-page .sdp-marquee__item{ display:inline-flex; align-items:center; gap:9px; padding:10px 18px;
    border-radius:999px; font-size:14px; font-weight:700; color:#fff;
    background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.16); white-space:nowrap; }
  #sdp-page .sdp-marquee__item i{ width:14px; height:14px; color:#ffb59d; }
  @keyframes sdpTicker{ from{ transform:translateX(0); } to{ transform:translateX(-50%); } }

  /* ══════════════════ FAQ ══════════════════ */
  #sdp-page .sdp-faq{ background:#fff; border-top:1px solid var(--sdp-line); }
  #sdp-page .sdp-faq__list{ max-width:900px; margin:0 auto; border-top:1px solid var(--sdp-line); }
  #sdp-page .sdp-faq__row{ border-bottom:1px solid var(--sdp-line); }
  #sdp-page .sdp-faq__q{ width:100%; display:flex; align-items:flex-start; justify-content:space-between;
    gap:18px; padding:22px 4px; border:0; background:none; color:var(--sdp-navy);
    font:700 clamp(16px,1.6vw,18px)/1.4 var(--sdp-sans); text-align:left; cursor:pointer;
    transition:color .2s ease; }
  #sdp-page .sdp-faq__q:hover{ color:var(--sdp-orange); }
  #sdp-page .sdp-faq__q:focus-visible{ outline:3px solid var(--sdp-orange); outline-offset:4px; }
  #sdp-page .sdp-faq__q i{ flex:0 0 auto; width:20px; height:20px; margin-top:2px;
    color:var(--sdp-orange); transition:transform .3s var(--sdp-ease); }
  #sdp-page .sdp-faq__q[aria-expanded="true"] i{ transform:rotate(180deg); }
  #sdp-page .sdp-faq__a{ display:grid; grid-template-rows:0fr; visibility:hidden;
    transition:grid-template-rows .36s var(--sdp-ease), visibility 0s linear .36s; }
  #sdp-page .sdp-faq__a.is-open{ grid-template-rows:1fr; visibility:visible;
    transition:grid-template-rows .36s var(--sdp-ease), visibility 0s; }
  #sdp-page .sdp-faq__a > div{ min-height:0; overflow:hidden; }
  #sdp-page .sdp-faq__a p{ padding:0 44px 24px 4px; font-size:15.5px; }

  /* Mini cards — one grid column wide, so icon + title only. */
  #sdp-page .sdp-card--mini{ justify-content:flex-end; background:var(--sdp-soft); }
  #sdp-page .sdp-card--mini h3{ font-size:15.5px; line-height:1.3; }
  #sdp-page .sdp-card--mini .sdp-track__icon{ margin-bottom:auto; }
  #sdp-page .sdp-card--mini .sdp-card__meta{ margin-top:6px; padding-top:0; font-size:10.5px; }

  /* ══════════════════ Responsive ══════════════════ */
  @media (max-width:1079px){
    /* Four columns: the wide branch cards go full width and everything else
       pairs up. Row spans collapse so a card is only ever as tall as its copy. */
    #sdp-page .sdp-mosaic{ grid-template-columns:repeat(4,minmax(0,1fr)); }
    /* Row spans go flat and the inline --c is overridden by class, so the
       four-column band still tiles: branch 4 + (track|mini) 2 + 2. */
    #sdp-page .sdp-card{ grid-row:span 1; }
    #sdp-page .sdp-card--branch{ grid-column:span 4; }
    #sdp-page .sdp-card--track, #sdp-page .sdp-card--mini{ grid-column:span 2; }
    /* A mini card is half the row here, so the tall stacked layout leaves it
       mostly empty — icon and title sit side by side instead. */
    #sdp-page .sdp-card--mini{ flex-direction:row; align-items:center;
      justify-content:flex-start; gap:14px; }
    #sdp-page .sdp-card--mini .sdp-track__icon{ margin:0; }
    /* The About card is already full width here, so the collapsed state has to
       match it — otherwise collapsing would halve the card and the FLIP would
       have to scale it. Ties on specificity with the base .is-collapsed rule
       and wins on source order, being inside this later media block. */
    #sdp-page .sdp-mosaic.is-collapsed{ grid-template-columns:repeat(4,minmax(0,1fr)); }
    #sdp-page .sdp-mosaic.is-collapsed .sdp-card--branch{ grid-column:1 / -1; }
  }
  /* The chips are pinned to the hero's outer edges; below this the centred
     headline reaches them and the two overlap, so they go rather than shrink. */
  @media (max-width:1180px){
    #sdp-page .sdp-hero__chip{ display:none; }
  }
  @media (max-width:680px){
    #sdp-page .sdp-mosaic{ grid-template-columns:1fr; grid-auto-rows:auto; gap:12px; }
    #sdp-page .sdp-card,
    #sdp-page .sdp-card--branch,
    #sdp-page .sdp-card--track,
    #sdp-page .sdp-card--mini{ grid-column:span 1; }
    #sdp-page .sdp-card--mini{ padding:16px; }
    #sdp-page .sdp-card--mini .sdp-card__meta{ display:none; }
    /* Single column: the collapsed grid must not re-introduce four of them. */
    #sdp-page .sdp-mosaic.is-collapsed{ grid-template-columns:1fr; }
    #sdp-page .sdp-mosaic.is-collapsed .sdp-card--branch{ grid-column:span 1; }
    #sdp-page .sdp-btn{ width:100%; }
    #sdp-page .sdp-benefit{ flex:0 0 84%; }
    #sdp-page .sdp-faq__a p{ padding-right:4px; }
  }
  @media (max-width:420px){
    #sdp-page .sdp-course-toggle{ width:42px; padding:0; }
    #sdp-page .sdp-course-toggle span{ position:absolute; width:1px; height:1px; overflow:hidden;
      clip:rect(0 0 0 0); clip-path:inset(50%); white-space:nowrap; }
  }

  @media (prefers-reduced-motion:reduce){
    #sdp-page .sdp-hero__grid,
    #sdp-page .sdp-hero__orbs span,
    #sdp-page .sdp-hero__chip,
    #sdp-page .sdp-marquee__track{ animation:none; }
    html.js #sdp-page .sdp-reveal{ opacity:1; transform:none; transition:none; }
    #sdp-page .sdp-faq__a, #sdp-page .sdp-faq__a.is-open{ transition:none; }
    /* Cards are simply present. The script skips its own animations to match,
       so the toggle becomes an instant show/hide. */
    html.js #sdp-page .sdp-mosaic:not(.is-in) .sdp-card{ opacity:1; }
    html.js #sdp-page .sdp-mosaic.is-in:not(.has-entered) .sdp-card{ animation:none; }
    #sdp-page .sdp-course-toggle i{ transition:none; }
    #sdp-page .sdp-rail{ scroll-behavior:auto; }
  }
</style>
@endpush

@section('content')
<main id="{{ $mainId ?? 'main' }}">
<div id="sdp-page">

  {{-- ═══════════════════ Hero ═══════════════════ --}}
  <section class="sdp-hero">
    <div class="sdp-hero__layer sdp-hero__grid" aria-hidden="true"></div>
    <div class="sdp-hero__layer sdp-hero__orbs" aria-hidden="true"><span></span><span></span><span></span></div>

    <div class="sdp-hero__layer" aria-hidden="true">
      <span class="sdp-hero__chip"><i data-lucide="compass"></i> Programme direction</span>
      <span class="sdp-hero__chip"><i data-lucide="book-open-check"></i> Academic readiness</span>
      <span class="sdp-hero__chip"><i data-lucide="file-pen-line"></i> Application clarity</span>
      <span class="sdp-hero__chip"><i data-lucide="sparkles"></i> Future-ready skills</span>
    </div>

    <div class="sdp-hero__inner">
      <p class="sdp-eyebrow">Student Development Programme</p>
      <h1>Build your <em>skills.</em></h1>
      <p class="sdp-hero__lead">
        Explore practical learning that can help you identify the right specialisation,
        understand overseas curricula and present a clearer academic direction in university applications.
      </p>
    </div>
  </section>

  {{-- ═══════════════════ Track mosaic ═══════════════════ --}}
  <section class="sdp-section" id="sdp-tracks">
    <div class="sdp-wrap">
      <div class="sdp-head sdp-reveal">
        <p class="sdp-eyebrow">Course catalogue</p>
        <p>Choose the subject you want to explore.</p>
      </div>

      {{-- Spans come from each card's `c` / `r` in the @php block; `--i` is the
           entrance stagger index. The grid, the collapse and the FLIP that moves
           the About card between the two layouts are all documented in the
           stylesheet above and the script below. --}}
      {{-- The height animation belongs to this frame, never to the grid itself.
           `grid-auto-rows: minmax(178px, auto)` gives every row a FIXED 178px base
           size, and grid only grows a base size toward its limit when the
           container leaves positive free space. Pinning the grid's own height to
           the collapsed height therefore starved the tracks: all rows dropped to
           the 178px floor, cards were laid out ~100px shorter than their content,
           and overflow:hidden cropped the bottom-anchored CTA off cards that were
           already at 99% opacity. Cropping the grid from OUTSIDE leaves its row
           sizing alone, so the boxes the FLIP measured are the boxes that render. --}}
      <div class="sdp-mosaic-frame" data-sdp-mosaic-frame>
      <div class="sdp-mosaic" id="sdp-course-grid" data-sdp-mosaic>
        @foreach($tracks as $i => $track)
          @if($track['kind'] === 'branch')
            <article class="sdp-card sdp-card--{{ $track['kind'] }} sdp-card--{{ $track['tone'] }}"
                     style="--c:{{ $track['c'] }};--r:{{ $track['r'] }};--i:{{ $i }}" data-sdp-about>
              {{-- `chevrons-in` is not a Lucide icon, so this button shipped with
                   no caret at all; `chevron-down` is, and the stylesheet turns it
                   over from the button's own aria-expanded. --}}
              <button class="sdp-course-toggle" type="button" data-sdp-course-toggle
                      aria-expanded="true" aria-controls="sdp-course-grid">
                <span data-sdp-toggle-label>Hide courses</span>
                <i data-lucide="chevron-down" aria-hidden="true"></i>
              </button>
              <span class="sdp-card__badge">{{ $track['badge'] }}</span>
              <span class="sdp-card__bicon" aria-hidden="true"><i data-lucide="{{ $track['icon'] }}"></i></span>
              <h3>{{ $track['title'] }}</h3>
              <p>{{ $track['text'] }}</p>
              <ul class="sdp-card__chips">
                @foreach($track['chips'] as $chip)
                  <li>{{ $chip }}</li>
                @endforeach
              </ul>
              <a class="sdp-card__go" href="{{ $track['href'] }}" target="_blank" rel="noopener">
                {{ $track['cta'] }} <i data-lucide="arrow-up-right"></i>
              </a>
            </article>
          @else
            <a class="sdp-card sdp-card--{{ $track['kind'] }} sdp-card--{{ $track['tone'] }}"
               style="--c:{{ $track['c'] }};--r:{{ $track['r'] }};--i:{{ $i }}"
               href="{{ $track['href'] }}" target="_blank" rel="noopener" data-sdp-course-card>
              <span class="{{ $track['tone'] === 'paper' ? 'sdp-track__icon' : 'sdp-card__bicon' }}" aria-hidden="true">
                <i data-lucide="{{ $track['icon'] }}"></i>
              </span>
              <h3>{{ $track['title'] }}</h3>
              <p>{{ $track['text'] }}</p>
              <span class="sdp-card__meta">{{ $track['meta'] }}</span>
              <span class="sdp-card__go">Explore course <i data-lucide="arrow-up-right"></i></span>
            </a>
          @endif
        @endforeach

        <div class="sdp-strip sdp-reveal">
          <div>
            <h3>Not sure which course fits your goals?</h3>
            <p>Talk to a counsellor about your study-abroad plans, interests and intended specialisation.</p>
          </div>
          <a class="sdp-btn sdp-btn--navy" href="{{ route('contact') }}">
            <i data-lucide="messages-square"></i> Contact a counsellor
          </a>
        </div>
      </div>
      </div>
    </div>
  </section>

  {{-- ═══════════════════ Benefits carousel ═══════════════════ --}}
  <section class="sdp-section sdp-benefits" id="sdp-benefits">
    <div class="sdp-wrap">
      <div class="sdp-head sdp-reveal">
        <p class="sdp-eyebrow">Why explore</p>
        <h2>What an extra skill can add to your study-abroad plan</h2>
        <p>Use the courses to explore specialisations, understand overseas curricula and explain your academic direction more clearly.</p>
      </div>

      <div class="sdp-carousel sdp-reveal" data-sdp-carousel>
        <div class="sdp-rail" data-sdp-rail role="group" aria-label="Benefits of exploring technology courses" tabindex="0">
          @foreach($benefits as $benefit)
            <article class="sdp-benefit">
              <span class="sdp-benefit__icon" aria-hidden="true"><i data-lucide="{{ $benefit['icon'] }}"></i></span>
              <p class="sdp-benefit__kicker">{{ $benefit['kicker'] }}</p>
              <h3>{{ $benefit['title'] }}</h3>
              <p>{{ $benefit['text'] }}</p>
            </article>
          @endforeach
        </div>

        <div class="sdp-carousel__controls">
          <button type="button" class="sdp-arrow" data-sdp-prev aria-label="Previous benefits">
            <i data-lucide="chevron-left" aria-hidden="true"></i>
          </button>
          <div class="sdp-dots" data-sdp-dots></div>
          <button type="button" class="sdp-arrow" data-sdp-next aria-label="Next benefits">
            <i data-lucide="chevron-right" aria-hidden="true"></i>
          </button>
        </div>
      </div>
    </div>
  </section>

  {{-- ═══════════════════ Skills marquee ═══════════════════ --}}
  <section class="sdp-marquee" aria-label="Topics covered across the courses">
    <div class="sdp-marquee__track">
      @foreach([false, true] as $isClone)
        @foreach($marquee as $skill)
          <span class="sdp-marquee__item" @if($isClone) aria-hidden="true" @endif>
            <i data-lucide="zap" aria-hidden="true"></i> {{ $skill }}
          </span>
        @endforeach
      @endforeach
    </div>
  </section>

  {{-- ═══════════════════ FAQ ═══════════════════ --}}
  <section class="sdp-section sdp-faq" id="sdp-faq">
    <div class="sdp-wrap">
      <div class="sdp-head sdp-reveal">
        <p class="sdp-eyebrow">Study-abroad questions</p>
        <h2>Frequently asked questions</h2>
        <p>How these course areas can support programme research, university shortlisting and a more focused application story.</p>
      </div>

      <div class="sdp-faq__list sdp-reveal">
        @foreach($faqs as $i => $faq)
          <div class="sdp-faq__row">
            <button type="button" class="sdp-faq__q" data-sdp-faq
                    id="sdp-q{{ $i }}" aria-expanded="false" aria-controls="sdp-a{{ $i }}">
              <span>{{ $faq['q'] }}</span>
              <i data-lucide="chevron-down" aria-hidden="true"></i>
            </button>
            <div class="sdp-faq__a" id="sdp-a{{ $i }}" role="region" aria-labelledby="sdp-q{{ $i }}">
              <div><p>{{ $faq['a'] }}</p></div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

</div>
</main>

{{-- Course-card interactions use a small amount of vanilla JavaScript. --}}
<script>
(function () {
  'use strict';

  var root = document.getElementById('sdp-page');
  if (!root) return;

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ─────────────── Reveal on scroll · section heads ─────────────── */
  var reveals = root.querySelectorAll('.sdp-reveal');
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

  var mosaic = root.querySelector('[data-sdp-mosaic]');
  // The grid's height must stay `auto` so its rows keep sizing from content;
  // the frame around it is what gets the animated height. See the markup note.
  var frame = root.querySelector('[data-sdp-mosaic-frame]') || mosaic;

  /* ─────────────── Card entrance ───────────────
     One observer on the GRID rather than one per card. Cards in the same visual
     row differ wildly in height here, so a per-card percentage threshold fired
     each one at a different scroll position and the cascade came out random.
     Triggering the whole grid once and staggering in CSS makes the order
     deterministic. threshold 0 with a bottom inset fires as the grid's top edge
     crosses the lower part of the viewport, which is height-independent.

     .has-entered is latched afterwards because un-hiding an element restarts its
     CSS animations — without it every expand would replay this entrance
     underneath the toggle's own animation. */
  if (mosaic) {
    var entered = function () {
      mosaic.classList.add('is-in');
      if (reduceMotion) { mosaic.classList.add('has-entered'); return; }
      var entering = mosaic.querySelectorAll('.sdp-card');
      var latch = function () { mosaic.classList.add('has-entered'); };
      if (!entering.length) { latch(); return; }
      // The last card carries the longest delay, so its end is the group's end.
      entering[entering.length - 1].addEventListener('animationend', latch, { once: true });
      // Belt and braces: animationend never arrives for a card that is not being
      // rendered — a background tab throttling frames, say.
      window.setTimeout(latch, 2600);
    };

    if ('IntersectionObserver' in window && !reduceMotion) {
      var gridObserver = new IntersectionObserver(function (entries) {
        var seen = entries.some(function (entry) { return entry.isIntersecting; });
        if (seen) { gridObserver.disconnect(); entered(); }
      }, { threshold: 0, rootMargin: '0px 0px -12% 0px' });
      gridObserver.observe(mosaic);
    } else {
      entered();
    }
  }

  /* ─────────────── Cursor spotlight on the mosaic ───────────────
     One delegated listener on the grid handles every course card. The
     card only needs the pointer position as a percentage of its own box. */
  if (mosaic && window.matchMedia && window.matchMedia('(hover: hover)').matches) {
    mosaic.addEventListener('pointermove', function (event) {
      var card = event.target.closest('.sdp-card');
      if (!card) return;
      var box = card.getBoundingClientRect();
      card.style.setProperty('--mx', ((event.clientX - box.left) / box.width * 100).toFixed(1) + '%');
      card.style.setProperty('--my', ((event.clientY - box.top) / box.height * 100).toFixed(1) + '%');
    });
  }

  /* ─────────────── Course collapse / expand · FLIP ───────────────
     The previous version animated and then reflowed (collapse), or reflowed and
     then animated (expand). Either way the layout jumped in a single frame and
     the motion was decoration bolted onto that jump — the page grew or shrank by
     well over a thousand pixels instantly. On collapse it also released a
     forwards fill by cancelling the animations BEFORE hiding the cards, which
     flashed all nine back to full opacity for a frame.

     This is a FLIP: measure, apply the final layout in one frame, measure again,
     then animate from the inverted difference back to zero. The layout changes
     exactly once, and the grid's height, the About card and every course card are
     all driven from that single pair of measurements, so none of them can drift
     out of step with the others.

     Everything animated is `transform`, `opacity`, or the grid's own `height`.
     The first two never touch layout; the height is one element, and it is what
     turns the page-length change into something continuous instead of a jump. */
  var toggle = root.querySelector('[data-sdp-course-toggle]');
  var about = root.querySelector('[data-sdp-about]');
  var courses = Array.prototype.slice.call(root.querySelectorAll('[data-sdp-course-card]'));
  var canAnimate = !reduceMotion && typeof Element.prototype.animate === 'function';

  // Kept in step with --sdp-in / --sdp-out / --sdp-morph in the stylesheet.
  var EASE_ARRIVE = 'cubic-bezier(.16,1,.3,1)';
  var EASE_LEAVE = 'cubic-bezier(.4,0,.2,1)';
  var EASE_MORPH = 'cubic-bezier(.2,0,0,1)';
  var PINNED = ['left', 'top', 'width', 'height'];

  var run = 0;        // generation token — a superseded run must never settle
  var busy = false;
  var queued = null;  // one click banked while an animation is in flight

  /* Any toggle has to start from a SETTLED entrance, and nothing guaranteed that.
     Four separate faults came out of the same gap:

       • the entrance keyframe holds a transform through its `backwards` fill, so
         boxIn() measured where the animation had a card rather than where the
         layout had it — the FLIP then pinned outgoing cards ~34px low and ~3%
         small, and flew them from the wrong origin;
       • `hidden` cancels a running CSS animation, so the last card's
         `animationend` never arrived and the .has-entered latch never fired
         (until the 2.6s belt-and-braces timeout);
       • with the latch still open, un-hiding on expand restarted the entrance
         underneath the toggle's own animation, so every card faded in twice.

     Forcing the entrance to its end state first closes all four. finish() lands
     on the natural state, and since the entrance keeps no forwards fill it stops
     applying entirely once complete — which is exactly the state to measure. */
  /* Applying `inert` to an ancestor of the focused element blurs it immediately,
     and hiding a focused element throws focus to <body> — either way the next Tab
     restarts at the top of the document and a screen reader is left with nothing
     announced. So focus is handed back to the control that caused it, and it has
     to happen BEFORE inert or hidden lands, not after. */
  function rescueFocus() {
    var active = document.activeElement;
    if (active && courses.some(function (card) { return card.contains(active); })) {
      toggle.focus();
    }
  }

  function finishEntrance() {
    if (mosaic.classList.contains('has-entered')) return;
    if (typeof Element.prototype.getAnimations === 'function') {
      courses.concat([about]).forEach(function (card) {
        card.getAnimations().forEach(function (animation) {
          if (animation.animationName === 'sdpCardIn') {
            try { animation.finish(); } catch (e) { /* already done */ }
          }
        });
      });
    }
    // `is-in` too, in case the observer has not fired at all: without it the
    // :not(.is-in) rule would keep every card at opacity 0.
    mosaic.classList.add('is-in');
    mosaic.classList.add('has-entered');
  }

  function label(expanded) {
    toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    var text = expanded ? 'Hide courses' : 'Show courses';
    var span = toggle.querySelector('[data-sdp-toggle-label]');
    if (span) span.textContent = text;
    toggle.setAttribute('aria-label', text);
  }

  /* Boxes are measured against the GRID's own top-left, not the viewport. The
     reflow changes the grid's height and the document's, which can move the
     viewport underneath us; grid-local coordinates survive that, and they are the
     same space an absolutely positioned child is placed in. */
  function boxIn(el, host) {
    var r = el.getBoundingClientRect();
    return { left: r.left - host.left, top: r.top - host.top, width: r.width, height: r.height };
  }
  function centreOf(box) { return { x: box.left + box.width / 2, y: box.top + box.height / 2 }; }
  function gapTo(box, point) {
    var c = centreOf(box);
    return (c.x - point.x) * (c.x - point.x) + (c.y - point.y) * (c.y - point.y);
  }

  function settle(token, expanded, moves) {
    if (token !== run) return;

    /* Order matters twice over here.
       1. Hide before cancelling. The collapse keyframes hold their end state with
          fill:'forwards', and cancelling first hands every card back to full
          opacity for a frame — exactly the flash this replaces.
       2. Drop the inline height before cancelling too. The height animation also
          fills forwards, and its inline base is still the OLD height; clearing
          that base first means the cancel lands on the natural height rather than
          snapping back to where the animation started. */
    // Belt and braces for the reduced-motion / no-WAAPI path, which hides the
    // cards without ever applying inert.
    if (!expanded) { rescueFocus(); courses.forEach(function (card) { card.hidden = true; }); }
    frame.style.removeProperty('height');
    about.style.removeProperty('height');
    moves.forEach(function (move) { move.cancel(); });

    courses.forEach(function (card) {
      card.classList.remove('is-flying');
      PINNED.forEach(function (prop) { card.style.removeProperty(prop); });
    });
    about.classList.remove('is-flipping');
    frame.classList.remove('is-animating');
    frame.classList.remove('is-expanding');
    toggle.removeAttribute('aria-busy');
    busy = false;

    if (queued !== null) {
      var next = queued;
      queued = null;
      if (next !== expanded) setCourses(next);
    }
  }

  function setCourses(expanded) {
    // Never refuse a click. Banking the intent and replaying it on settle keeps
    // the button honest, without a disabled state that reads as broken. The bank
    // is relabelled immediately: a screen reader that hears "collapsed" and is
    // then expanded on ~690ms later was told the wrong thing in between.
    if (busy) { queued = expanded; label(expanded); return; }

    var token = ++run;
    busy = true;
    // Before ANY measurement: see finishEntrance().
    finishEntrance();
    // Announced up front in BOTH directions — the old collapse relabelled itself
    // only once the animation had finished, so the button lied for ~700ms.
    label(expanded);

    if (!canAnimate) {
      courses.forEach(function (card) {
        if (expanded) { card.hidden = false; card.removeAttribute('inert'); }
      });
      mosaic.classList.toggle('is-collapsed', !expanded);
      settle(token, expanded, []);
      return;
    }

    toggle.setAttribute('aria-busy', 'true');

    /* ── FIRST: the layout as it stands ── */
    var host0 = mosaic.getBoundingClientRect();
    var height0 = host0.height;
    var about0 = boxIn(about, host0);
    var from = expanded ? null : courses.map(function (card) { return boxIn(card, host0); });

    /* ── MUTATE: one frame, one reflow ──
       Collapsing lifts the outgoing cards out of grid flow FIRST, pinned to the
       boxes just measured, so the reflow closing up behind them cannot drag them
       along with it. Expanding puts them back into flow, so they can be measured
       where they are actually going. */
    if (expanded) {
      courses.forEach(function (card) {
        card.hidden = false;
        card.removeAttribute('inert');
      });
      frame.classList.add('is-expanding');
    } else {
      rescueFocus();
      courses.forEach(function (card, i) {
        var box = from[i];
        /* Out of the tab order and out of the accessibility tree NOW, not when the
           flight finishes: these cards are visually gone within ~100ms but stayed
           focusable for the full ~700ms, so Tab put a focus ring on nothing and
           screen readers kept announcing links that were being removed. `inert`
           is a no-op on engines that predate it, which is the old behaviour. */
        card.setAttribute('inert', '');
        card.classList.add('is-flying');
        card.style.left = box.left + 'px';
        card.style.top = box.top + 'px';
        card.style.width = box.width + 'px';
        card.style.height = box.height + 'px';
      });
    }
    frame.classList.add('is-animating');
    mosaic.classList.toggle('is-collapsed', !expanded);

    /* ── LAST: read the settled layout, height still unpinned ── */
    var host1 = mosaic.getBoundingClientRect();
    var height1 = host1.height;
    var about1 = boxIn(about, host1);
    var to = expanded ? courses.map(function (card) { return boxIn(card, host1); }) : null;

    /* ── INVERT + PLAY ── */
    frame.style.height = height0 + 'px';

    var moves = [];
    var hub = centreOf(about1);
    var boxes = expanded ? to : from;

    // Nearest card first, so the set reads as one movement into or out of the
    // About card rather than nine unrelated ones. The total is capped because
    // this catalogue is meant to grow: twenty cards must not mean a long wait.
    var order = courses.map(function (card, i) { return i; }).sort(function (a, b) {
      return gapTo(boxes[a], hub) - gapTo(boxes[b], hub);
    });
    var stagger = courses.length > 1 ? Math.min(38, 280 / (courses.length - 1)) : 0;

    order.forEach(function (index, slot) {
      var point = centreOf(boxes[index]);
      var dx = (hub.x - point.x).toFixed(1);
      var dy = (hub.y - point.y).toFixed(1);
      var atHub = { transform: 'translate(' + dx + 'px,' + dy + 'px) scale(.12)', opacity: 0 };
      var atRest = { transform: 'none', opacity: 1 };

      moves.push(courses[index].animate(
        expanded ? [atHub, atRest] : [atRest, atHub],
        {
          duration: expanded ? 460 : 420,
          delay: slot * stagger,
          easing: expanded ? EASE_ARRIVE : EASE_LEAVE,
          /* Expanding holds the from-state through the delay so no card sits at
             full size for a frame before its turn; collapsing holds the end state
             so none flashes back before settle() hides it. Neither keeps a
             forwards fill it does not need, so :hover gets `transform` back the
             moment a card is at rest. */
          fill: expanded ? 'backwards' : 'forwards'
        }
      ));
    });

    /* The About card moves between the two layouts, and its HEIGHT changes with
       them: expanded, rows 1-2 are sized by the course cards sitting beside it
       (519px here); collapsed, it is the only thing in them (394px).

       Position travels on `transform`, which is free. Height is animated as a
       real height instead of being folded into a scaleY, because a 32% vertical
       squash on a card full of text is exactly the kind of smeared, rubbery
       motion that reads as broken — and since the WIDTH is identical in both
       states, animating the height reflows nothing: no line re-wraps, the
       bottom-anchored CTA just travels up with the edge.

       scaleX is still computed as a safety net. It should always be 1 (all three
       breakpoints give the card the same width in both states), but if a future
       layout change breaks that parity this degrades to a stretch rather than to
       a jump. */
    var dx = about0.left - about1.left;
    var dy = about0.top - about1.top;
    var sx = about1.width > 0 ? about0.width / about1.width : 1;
    if (Math.abs(dx) > 0.5 || Math.abs(dy) > 0.5 || Math.abs(sx - 1) > 0.004) {
      about.classList.add('is-flipping');
      moves.push(about.animate([
        { transform: 'translate(' + dx.toFixed(1) + 'px,' + dy.toFixed(1) + 'px) scale(' + sx.toFixed(4) + ',1)' },
        { transform: 'none' }
      ], { duration: 600, easing: EASE_MORPH }));
    }
    if (Math.abs(about0.height - about1.height) > 0.5) {
      // fill:'forwards' holds the new height until settle() clears the inline
      // base, same ordering as the grid height below.
      moves.push(about.animate(
        [{ height: about0.height + 'px' }, { height: about1.height + 'px' }],
        { duration: 600, easing: EASE_MORPH, fill: 'forwards' }
      ));
    }

    // The frame's height carries the page with it, so nothing below it jumps.
    if (Math.abs(height1 - height0) > 0.5) {
      moves.push(frame.animate(
        [{ height: height0 + 'px' }, { height: height1 + 'px' }],
        { duration: 600, easing: EASE_MORPH, fill: 'forwards' }
      ));
    }

    if (!moves.length) { settle(token, expanded, moves); return; }

    Promise.all(moves.map(function (move) {
      return move.finished.catch(function () { /* cancelled by a newer run */ });
    })).then(function () { settle(token, expanded, moves); });
  }

  if (mosaic && about && toggle && courses.length) {
    toggle.addEventListener('click', function () {
      setCourses(toggle.getAttribute('aria-expanded') !== 'true');
    });
  }

  /* ─────────────── Benefits carousel ───────────────
     A scroll-snap rail: the arrows and dots drive scrollLeft, and the dots read
     their state back off the scroll position, so a touch swipe or a keyboard
     scroll keeps them in sync without any extra bookkeeping. */
  var rail = root.querySelector('[data-sdp-rail]');
  if (rail) {
    var cards = Array.prototype.slice.call(rail.querySelectorAll('.sdp-benefit'));
    var prev = root.querySelector('[data-sdp-prev]');
    var next = root.querySelector('[data-sdp-next]');
    var dotsBox = root.querySelector('[data-sdp-dots]');

    var step = function () {
      // One card plus the flex gap. Measured rather than hardcoded: the card
      // width is a clamp(), so it differs at every breakpoint.
      if (cards.length < 2) return rail.clientWidth;
      return cards[1].getBoundingClientRect().left - cards[0].getBoundingClientRect().left;
    };

    // `scroll-snap-type: mandatory` parks the rail on the first card, and the
    // rail's own left padding puts that card a few pixels in from zero — so the
    // resting scrollLeft is that inset, not 0. Without allowing for it the
    // "previous" arrow never reads as disabled at the start.
    var startInset = function () {
      return parseFloat(window.getComputedStyle(rail).paddingInlineStart) || 0;
    };

    var dots = cards.map(function (card, i) {
      var dot = document.createElement('button');
      dot.type = 'button';
      dot.setAttribute('aria-label', 'Show benefit ' + (i + 1) + ' of ' + cards.length);
      dot.addEventListener('click', function () {
        rail.scrollTo({ left: startInset() + i * step(), behavior: reduceMotion ? 'auto' : 'smooth' });
      });
      if (dotsBox) dotsBox.appendChild(dot);
      return dot;
    });

    var syncRail = function () {
      var max = rail.scrollWidth - rail.clientWidth;
      var rest = startInset();
      // A sub-pixel scrollWidth means the ends never compare exactly equal.
      if (prev) prev.disabled = rail.scrollLeft <= rest + 2;
      if (next) next.disabled = rail.scrollLeft >= max - 2;
      var active = Math.round((rail.scrollLeft - rest) / (step() || 1));
      dots.forEach(function (dot, i) {
        dot.classList.toggle('is-on', i === active);
        dot.setAttribute('aria-current', i === active ? 'true' : 'false');
      });
    };

    var nudge = function (dir) {
      rail.scrollBy({ left: dir * step(), behavior: reduceMotion ? 'auto' : 'smooth' });
    };
    if (prev) prev.addEventListener('click', function () { nudge(-1); });
    if (next) next.addEventListener('click', function () { nudge(1); });

    rail.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowRight') { event.preventDefault(); nudge(1); }
      if (event.key === 'ArrowLeft') { event.preventDefault(); nudge(-1); }
    });

    // Passive: this only reads layout and toggles classes, so it must never hold
    // up the scroll it is reacting to.
    rail.addEventListener('scroll', syncRail, { passive: true });
    window.addEventListener('resize', syncRail);
    syncRail();
  }

  /* ─────────────── FAQ accordion ─────────────── */
  var questions = Array.prototype.slice.call(root.querySelectorAll('[data-sdp-faq]'));
  questions.forEach(function (question) {
    question.addEventListener('click', function () {
      var panel = document.getElementById(question.getAttribute('aria-controls'));
      var wasOpen = question.getAttribute('aria-expanded') === 'true';

      questions.forEach(function (other) {
        var otherPanel = document.getElementById(other.getAttribute('aria-controls'));
        other.setAttribute('aria-expanded', 'false');
        if (otherPanel) otherPanel.classList.remove('is-open');
      });

      if (!wasOpen) {
        question.setAttribute('aria-expanded', 'true');
        if (panel) panel.classList.add('is-open');
      }
    });
  });

})();
</script>
@endsection
