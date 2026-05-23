@php
    $pageTitle = 'About | OneDegreeAdvisory';
    $pageDescription = 'Meet OneDegreeAdvisory — a senior, partner-led education advisory architecting study-abroad futures with strategy, evidence, and care.';
    $activeNav = 'about';
    $mainId = 'main';
@endphp

@extends('layouts.app')

@section('content')
<main id="main" class="va-about-page">

  {{-- ───────────────────────── HERO ───────────────────────── --}}
  <section class="va-hero" id="top" aria-labelledby="va-hero-title">
    <div class="container va-hero-grid">
      <div class="va-hero-copy">
        <span class="va-eyebrow">About OneDegree</span>
        <h1 id="va-hero-title">
          Join <span class="va-hero-num">12,000+ students</span>
          architecting their <em>global careers.</em>
        </h1>
        <p class="va-hero-lede">
          We are a senior, partner-led advisory built on a simple promise — the person who designs your shortlist is the same one reading your final draft and rehearsing your visa interview. No handoffs. No volume targets. Just one careful method, every file.
        </p>
        <div class="va-hero-actions">
          <a class="btn btn-primary" href="{{ route('contact') }}">
            <span>Talk to a partner</span>
            <i data-lucide="arrow-up-right"></i>
          </a>
          <a class="btn btn-ghost" href="#founders">
            <i data-lucide="users"></i>
            <span>Meet the team</span>
          </a>
        </div>
        <div class="va-hero-trust">
          <div><strong>20+</strong><span>Study destinations</span></div>
          <div><strong>96%</strong><span>Visa approvals</span></div>
          <div><strong>01</strong><span>Senior per file</span></div>
        </div>
      </div>

      <aside class="va-hero-collage" aria-hidden="true">
        <div class="va-hero-photo va-hero-photo--lg" style="background-image: url('https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=900&h=1100&q=82');"></div>
        <div class="va-hero-photo va-hero-photo--sm" style="background-image: url('https://images.unsplash.com/photo-1521737852567-6949f3f9f2b5?auto=format&fit=crop&w=600&h=600&q=82');"></div>
        <div class="va-hero-badge">
          <i data-lucide="sparkles"></i>
          <div>
            <strong>Partner-led</strong>
            <span>No junior account managers</span>
          </div>
        </div>
      </aside>
    </div>
  </section>

  {{-- ─────────────────── VISION & MISSION ─────────────────── --}}
  <section class="va-vm" aria-labelledby="va-vm-title">
    <div class="container">
      <header class="va-vm-head">
        <span class="va-eyebrow">Vision &amp; Mission</span>
        <h2 id="va-vm-title">Two ideas anchor everything we do.</h2>
      </header>

      <div class="va-vm-grid">
        <article class="va-vm-card va-vm-card--vision">
          <span class="va-vm-icon" aria-hidden="true"><i data-lucide="telescope"></i></span>
          <span class="va-vm-tag">Our Vision</span>
          <h3>To be the most trusted education partner — helping every student unlock their full potential through the right opportunities.</h3>
          <p>A future where the next generation chooses programs by evidence, not by ad spend; and where every family gets the same careful read top universities give their own admits.</p>
        </article>

        <article class="va-vm-card va-vm-card--mission">
          <span class="va-vm-icon" aria-hidden="true"><i data-lucide="compass"></i></span>
          <span class="va-vm-tag">Our Mission</span>
          <h3>To champion student success — guiding them toward academic and career goals so every step leads to real achievement.</h3>
          <p>We turn scattered research into a decision map, defend the file from bad advice, and stay in the room from profile build to pre-departure.</p>
        </article>
      </div>
    </div>
  </section>

  {{-- ─────────────────── WHO / WHY / WHAT ─────────────────── --}}
  <section class="va-pillars" aria-label="Who we are, why we do it, what we do">
    <div class="container">

      {{-- WHO --}}
      <article class="va-pillar va-pillar--row" id="who">
        <div class="va-pillar-copy">
          <span class="va-eyebrow">Who We Are</span>
          <h2>A small bench of senior advisors — not a referral machine.</h2>
          <p>At OneDegreeAdvisory, we are a dedicated team of education experts who believe your future deserves more than promises &mdash; it deserves the best read, the best draft, and the best plan. Our partners have sat inside admissions offices, consular desks, and test-prep rooms. They know how files actually get read.</p>
          <ul class="va-chips">
            <li><i data-lucide="badge-check"></i> Dream Enablers</li>
            <li><i data-lucide="graduation-cap"></i> Education Experts</li>
            <li><i data-lucide="shield-check"></i> Partner-only Reviews</li>
          </ul>
        </div>
        <figure class="va-pillar-media">
          <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&h=720&q=82" alt="OneDegree advisors working with a student">
          <span class="va-pillar-tag"><i data-lucide="users-round"></i> The team</span>
        </figure>
      </article>

      {{-- WHY --}}
      <article class="va-pillar va-pillar--row va-pillar--reverse" id="why">
        <div class="va-pillar-copy">
          <span class="va-eyebrow">Why We Do It</span>
          <h2>Because academic journeys feel overwhelming &mdash; and most advice is built to sell, not to fit.</h2>
          <p>Countless options. Unexpected costs. Counsellors paid by the school they steer you to. We started OneDegree because the conversation around studying abroad had grown loud, transactional, and quietly unfair to families. We wanted a desk where the advice is independent &mdash; and accountable.</p>
          <ul class="va-chips">
            <li><i data-lucide="trophy"></i> Student Victory</li>
            <li><i data-lucide="target"></i> Goal &amp; Dream Alignment</li>
            <li><i data-lucide="hand-coins"></i> Zero Commissions</li>
          </ul>
        </div>
        <figure class="va-pillar-media">
          <img src="https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=900&h=720&q=82" alt="Student reviewing study-abroad plans">
          <span class="va-pillar-tag"><i data-lucide="heart-handshake"></i> Why we exist</span>
        </figure>
      </article>

      {{-- WHAT --}}
      <article class="va-pillar va-pillar--row" id="what">
        <div class="va-pillar-copy">
          <span class="va-eyebrow">What We Do</span>
          <h2>One method. Profile to pre-departure.</h2>
          <p>OneDegree is your end-to-end partner for further studies. We connect you to the right programs and stay with you through every milestone &mdash; profile build, shortlist, applications, scholarships, tests, visas, and the first month abroad. One file. One partner. One careful plan.</p>
          <ul class="va-chips">
            <li><i data-lucide="sparkles"></i> Evidence-led Match</li>
            <li><i data-lucide="route"></i> End-to-end Support</li>
            <li><i data-lucide="plane-takeoff"></i> Pre-departure Lab</li>
          </ul>
        </div>
        <figure class="va-pillar-media">
          <img src="https://images.unsplash.com/photo-1607013251379-e6eecfffe234?auto=format&fit=crop&w=900&h=720&q=82" alt="Advisor and student mapping a university shortlist">
          <span class="va-pillar-tag"><i data-lucide="layers"></i> What we deliver</span>
        </figure>
      </article>

    </div>
  </section>

  {{-- ─────────────────── GLOBAL IMPACT ─────────────────── --}}
  <section class="va-impact" aria-labelledby="va-impact-title">
    <div class="container">
      <header class="va-impact-head">
        <span class="va-eyebrow va-eyebrow--light">Our Global Impact</span>
        <h2 id="va-impact-title">Quietly, the numbers add up.</h2>
        <p>At OneDegree, we have helped thousands of students earn seats at the world&rsquo;s most selective programs. Our track record is built file by file &mdash; not by chasing volume.</p>
      </header>

      <div class="va-impact-grid">
        <article class="va-impact-card">
          <span class="va-impact-icon" aria-hidden="true"><i data-lucide="globe-2"></i></span>
          <strong>20<small>+</small></strong>
          <span>Countries accessible for study-abroad opportunities</span>
        </article>
        <article class="va-impact-card">
          <span class="va-impact-icon" aria-hidden="true"><i data-lucide="users"></i></span>
          <strong>12,000<small>+</small></strong>
          <span>Students guided on their global education journey</span>
        </article>
        <article class="va-impact-card">
          <span class="va-impact-icon" aria-hidden="true"><i data-lucide="building-2"></i></span>
          <strong>9,400<small>+</small></strong>
          <span>Students successfully placed in top institutions</span>
        </article>
        <article class="va-impact-card">
          <span class="va-impact-icon" aria-hidden="true"><i data-lucide="award"></i></span>
          <strong>34<small>%</small></strong>
          <span>Higher acceptance rate than the industry average</span>
        </article>
      </div>
    </div>
  </section>

  {{-- ─────────────────── FOUNDERS ─────────────────── --}}
  <section class="va-team" id="founders" aria-labelledby="va-founders-title">
    <div class="container">
      <header class="va-team-head">
        <span class="va-eyebrow">Founders</span>
        <h2 id="va-founders-title">The partners who actually read your file.</h2>
        <p>Senior advisors with two decades each inside admissions, consular work, and test strategy &mdash; not a sales floor.</p>
      </header>

      <div class="va-team-grid va-team-grid--3">
        <article class="va-team-card">
          <div class="va-team-photo" style="background-image: url('https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?auto=format&fit=crop&w=600&h=720&q=82');"></div>
          <div class="va-team-body">
            <h3>Aanya Mehra</h3>
            <span class="va-team-role">Founder &middot; Managing Partner</span>
            <p>Edtech leader and former admissions reader at a top-ten US university. TEDx speaker; mentors first-generation applicants worldwide. Leads strategy for selective undergraduate and MBA files.</p>
            <div class="va-team-meta">
              <span class="va-team-desk">US &middot; Canada</span>
              <a class="va-team-social" href="#" aria-label="Aanya Mehra on LinkedIn">
                <i data-lucide="linkedin"></i>
              </a>
            </div>
          </div>
        </article>

        <article class="va-team-card">
          <div class="va-team-photo" style="background-image: url('https://images.unsplash.com/photo-1544723795-3fb6469f5b39?auto=format&fit=crop&w=600&h=720&q=82');"></div>
          <div class="va-team-body">
            <h3>Rohan Iyer</h3>
            <span class="va-team-role">Founder &middot; Partner</span>
            <p>Serial entrepreneur with 25+ years in global education. Oxford alum and 15-year admissions interviewer. Runs the Europe desk and the in-house scholarship lab.</p>
            <div class="va-team-meta">
              <span class="va-team-desk">UK &middot; Europe</span>
              <a class="va-team-social" href="#" aria-label="Rohan Iyer on LinkedIn">
                <i data-lucide="linkedin"></i>
              </a>
            </div>
          </div>
        </article>

        <article class="va-team-card">
          <div class="va-team-photo" style="background-image: url('https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=600&h=720&q=82');"></div>
          <div class="va-team-body">
            <h3>Navyata Goenka</h3>
            <span class="va-team-role">Founder &middot; Partner</span>
            <p>Investment banking and equity research background. Kelley School of Business alumna. Advisor to Mount Litera School International (4 years); recognised among &ldquo;Young Women Entrepreneurs Leading a New India.&rdquo;</p>
            <div class="va-team-meta">
              <span class="va-team-desk">Profile &middot; Finance</span>
              <a class="va-team-social" href="#" aria-label="Navyata Goenka on LinkedIn">
                <i data-lucide="linkedin"></i>
              </a>
            </div>
          </div>
        </article>
      </div>
    </div>
  </section>

  {{-- ─────────────────── FINAL CTA ─────────────────── --}}
  <section class="va-cta" aria-labelledby="va-cta-title">
    <div class="container va-cta-grid">
      <div class="va-cta-copy">
        <span class="va-eyebrow va-eyebrow--light">Ready to turn your dream into reality?</span>
        <h2 id="va-cta-title">Bring us the messy draft. We will turn it into a decision map.</h2>
        <p>One call with a senior partner. No sales pitch, no commissions, no junior account managers between you and the people making decisions on your file.</p>
        <div class="va-cta-actions">
          <a class="btn btn-primary" href="{{ route('contact') }}">
            <span>Get in touch</span>
            <i data-lucide="arrow-up-right"></i>
          </a>
          <a class="btn btn-ghost va-cta-ghost" href="#founders">
            <i data-lucide="user-check"></i>
            <span>Meet a partner first</span>
          </a>
        </div>
        <ul class="va-cta-tags">
          <li><i data-lucide="sparkle"></i> AI-driven evaluation</li>
          <li><i data-lucide="life-buoy"></i> Comprehensive support</li>
          <li><i data-lucide="lock"></i> Confidential review</li>
        </ul>
      </div>

      <figure class="va-cta-media" aria-hidden="true">
        <img src="https://images.unsplash.com/photo-1523580494863-6f3031224c94?auto=format&fit=crop&w=900&h=1100&q=82" alt="">
        <div class="va-cta-card">
          <span class="va-cta-card-num">96%</span>
          <span class="va-cta-card-label">Top-choice visa approvals last cycle</span>
        </div>
      </figure>
    </div>
  </section>

</main>
@endsection
