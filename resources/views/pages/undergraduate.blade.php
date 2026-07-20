@php
    $pageTitle = 'Undergraduate (UG) Programs | One Degree Advisory';
    $pageDescription = 'Plan your undergraduate journey abroad — shortlist, applications, essays, scholarships, and visas, guided file by file by a senior partner.';
    $activeNav = 'undergraduate';
    $mainId = 'main';
@endphp

@extends('layouts.app')

@section('content')
<main id="main" class="va-about-page">

  {{-- ───────────────────────── HERO ───────────────────────── --}}
  <section class="va-hero" id="top" aria-labelledby="va-hero-title">
    <div class="container va-hero-grid">
      <div class="va-hero-copy">
        <span class="va-eyebrow">Undergraduate · UG</span>
        <h1 id="va-hero-title">
          Build a <span class="va-hero-num">stand-out</span>
          undergraduate <em>application.</em>
        </h1>
        <p class="va-hero-lede">
          Bachelor's degrees abroad reward early, intentional planning. We help you choose the right country and major, sharpen your profile and essays, and submit applications that read the way admissions readers expect.
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
        <div class="va-hero-photo va-hero-photo--lg" style="background-image: url('https://images.unsplash.com/photo-1627556704302-624286467c65?auto=format&fit=crop&w=900&h=1100&q=82');"></div>
        <div class="va-hero-photo va-hero-photo--sm" style="background-image: url('https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=600&h=600&q=82');"></div>
        <div class="va-hero-badge">
          <i data-lucide="graduation-cap"></i>
          <div>
            <strong>Undergraduate</strong>
            <span>Bachelor's degrees worldwide</span>
          </div>
        </div>
      </aside>
    </div>
  </section>

  {{-- ─────────────────── WHAT WE COVER ─────────────────── --}}
  <section class="va-pillars" id="what" aria-label="What undergraduate guidance covers">
    <div class="container">

      <article class="va-pillar va-pillar--row">
        <div class="va-pillar-copy">
          <span class="va-eyebrow">Course &amp; country match</span>
          <h2>Pick the major and destination that fit you — not the brochure.</h2>
          <p>We map your interests, budget, and target outcomes against real programs across 20+ countries, then build a balanced shortlist of reach, match, and safe options.</p>
          <ul class="va-chips">
            <li><i data-lucide="target"></i> Profile-led shortlist</li>
            <li><i data-lucide="globe-2"></i> 20+ destinations</li>
            <li><i data-lucide="wallet"></i> Budget-aware</li>
          </ul>
        </div>
        <figure class="va-pillar-media">
          <img src="https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=900&h=720&q=82" alt="Student reviewing undergraduate options">
          <span class="va-pillar-tag"><i data-lucide="compass"></i> Step 01</span>
        </figure>
      </article>

      <article class="va-pillar va-pillar--row va-pillar--reverse">
        <div class="va-pillar-copy">
          <span class="va-eyebrow">Applications &amp; essays</span>
          <h2>Essays and applications that read the way readers expect.</h2>
          <p>From the Common App to UCAS and direct portals, we keep your timeline tight and your story coherent — drafted, reviewed, and polished by the same senior advisor.</p>
          <ul class="va-chips">
            <li><i data-lucide="pen-line"></i> Essay strategy</li>
            <li><i data-lucide="file-check"></i> Application review</li>
            <li><i data-lucide="calendar-clock"></i> Deadline tracking</li>
          </ul>
        </div>
        <figure class="va-pillar-media">
          <img src="https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=900&h=720&q=82" alt="Drafting an undergraduate application essay">
          <span class="va-pillar-tag"><i data-lucide="edit-3"></i> Step 02</span>
        </figure>
      </article>

      <article class="va-pillar va-pillar--row">
        <div class="va-pillar-copy">
          <span class="va-eyebrow">Scholarships &amp; visas</span>
          <h2>Funding, offers, and a visa interview you walk in ready for.</h2>
          <p>We surface scholarships you actually qualify for, compare offers objectively, and rehearse your visa interview until it feels routine.</p>
          <ul class="va-chips">
            <li><i data-lucide="hand-coins"></i> Scholarship search</li>
            <li><i data-lucide="scale"></i> Offer comparison</li>
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
        <span class="va-eyebrow va-eyebrow--light">Ready to start your UG journey?</span>
        <h2 id="va-cta-title">One call with a senior partner — no sales pitch.</h2>
        <p>Bring us your grades, interests, and questions. We will turn them into a clear undergraduate plan.</p>
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
