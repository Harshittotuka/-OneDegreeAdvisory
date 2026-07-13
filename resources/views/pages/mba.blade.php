@php
    $pageTitle = 'MBA Programs Abroad | One Degree Advisory';
    $pageDescription = 'Study an MBA abroad — business school selection, applications, essays, GMAT/GRE strategy, scholarships, and visas, led by a senior partner.';
    $activeNav = 'mba';
    $mainId = 'main';
@endphp

@extends('layouts.app')

@section('content')
<main id="main" class="va-about-page">

  {{-- ───────────────────────── HERO ───────────────────────── --}}
  <section class="va-hero" id="top" aria-labelledby="va-hero-title">
    <div class="container va-hero-grid">
      <div class="va-hero-copy">
        <span class="va-eyebrow">Business · MBA</span>
        <h1 id="va-hero-title">
          Accelerate into a <span class="va-hero-num">global</span>
          <em>leadership role.</em>
        </h1>
        <p class="va-hero-lede">
          An MBA abroad is an investment in your network, your brand, and your trajectory. We help you position your experience, target the right schools, and build essays and applications that stand out.
        </p>
        <div class="va-hero-actions">
          <a class="btn btn-primary" href="{{ route('contact') }}">
            <span>Talk to a partner</span>
            <i data-lucide="arrow-up-right"></i>
          </a>
          <a class="btn btn-ghost" href="#what">
            <i data-lucide="list-checks"></i>
            <span>What we cover</span>
          </a>
        </div>
        <div class="va-hero-trust">
          <div><strong>20+</strong><span>Study destinations</span></div>
          <div><strong>96%</strong><span>Visa approvals</span></div>
          <div><strong>01</strong><span>Senior per file</span></div>
        </div>
      </div>

      <aside class="va-hero-collage" aria-hidden="true">
        <div class="va-hero-photo va-hero-photo--lg" style="background-image: url('https://images.unsplash.com/photo-1542744173-8e7e53415bb0?auto=format&fit=crop&w=900&h=1100&q=82');"></div>
        <div class="va-hero-photo va-hero-photo--sm" style="background-image: url('https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=600&h=600&q=82');"></div>
        <div class="va-hero-badge">
          <i data-lucide="trending-up"></i>
          <div>
            <strong>MBA</strong>
            <span>Business &amp; beyond</span>
          </div>
        </div>
      </aside>
    </div>
  </section>

  {{-- ─────────────────── WHAT WE COVER ─────────────────── --}}
  <section class="va-pillars" id="what" aria-label="What MBA guidance covers">
    <div class="container">

      <article class="va-pillar va-pillar--row">
        <div class="va-pillar-copy">
          <span class="va-eyebrow">School &amp; cohort fit</span>
          <h2>Target schools by outcome, network, and specialisation.</h2>
          <p>We go beyond rankings — matching your experience and career goals to specific programs, cohorts, and specialisations across 20+ countries.</p>
          <ul class="va-chips">
            <li><i data-lucide="network"></i> Network fit</li>
            <li><i data-lucide="briefcase"></i> Career outcomes</li>
            <li><i data-lucide="globe-2"></i> 20+ destinations</li>
          </ul>
        </div>
        <figure class="va-pillar-media">
          <img src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=900&h=720&q=82" alt="Business school cohort">
          <span class="va-pillar-tag"><i data-lucide="compass"></i> Step 01</span>
        </figure>
      </article>

      <article class="va-pillar va-pillar--row va-pillar--reverse">
        <div class="va-pillar-copy">
          <span class="va-eyebrow">Essays, GMAT/GRE &amp; applications</span>
          <h2>Essays that make your leadership story land.</h2>
          <p>We shape your essays, guide your test strategy and recommenders, and assemble a coherent application — drafted, reviewed, and refined by the same senior advisor from start to submit.</p>
          <ul class="va-chips">
            <li><i data-lucide="pen-line"></i> Essay strategy</li>
            <li><i data-lucide="target"></i> GMAT / GRE plan</li>
            <li><i data-lucide="file-check"></i> Application review</li>
          </ul>
        </div>
        <figure class="va-pillar-media">
          <img src="https://images.unsplash.com/photo-1434030216411-0b793f4b4173?auto=format&fit=crop&w=900&h=720&q=82" alt="Writing an MBA essay">
          <span class="va-pillar-tag"><i data-lucide="edit-3"></i> Step 02</span>
        </figure>
      </article>

      <article class="va-pillar va-pillar--row">
        <div class="va-pillar-copy">
          <span class="va-eyebrow">Funding &amp; visas</span>
          <h2>Scholarships, offers, and a confident visa interview.</h2>
          <p>We surface scholarships and fellowships you qualify for, compare offers and ROI objectively, and rehearse your visa interview until it feels routine.</p>
          <ul class="va-chips">
            <li><i data-lucide="hand-coins"></i> Funding search</li>
            <li><i data-lucide="scale"></i> Offer &amp; ROI comparison</li>
            <li><i data-lucide="plane-takeoff"></i> Visa prep</li>
          </ul>
        </div>
        <figure class="va-pillar-media">
          <img src="https://images.unsplash.com/photo-1606761568499-6d2451b23c66?auto=format&fit=crop&w=900&h=720&q=82" alt="Student preparing for a visa interview">
          <span class="va-pillar-tag"><i data-lucide="badge-check"></i> Step 03</span>
        </figure>
      </article>

    </div>
  </section>

  {{-- ─────────────────── FINAL CTA ─────────────────── --}}
  <section class="va-cta" aria-labelledby="va-cta-title">
    <div class="container va-cta-grid">
      <div class="va-cta-copy">
        <span class="va-eyebrow va-eyebrow--light">Ready to plan your MBA?</span>
        <h2 id="va-cta-title">One call with a senior partner — no sales pitch.</h2>
        <p>Bring us your experience and goals. We will turn them into a focused MBA plan.</p>
        <div class="va-cta-actions">
          <a class="btn btn-primary" href="{{ route('contact') }}">
            <span>Get in touch</span>
            <i data-lucide="arrow-up-right"></i>
          </a>
        </div>
        <ul class="va-cta-tags">
          <li><i data-lucide="sparkle"></i> Evidence-led match</li>
          <li><i data-lucide="life-buoy"></i> End-to-end support</li>
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
