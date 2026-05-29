@php
    $pageTitle = 'Postgraduate (PG) Programs | One Degree Advisory';
    $pageDescription = 'Master\'s and postgraduate study abroad — program selection, applications, research fit, scholarships, and visas, led by a senior partner.';
    $activeNav = 'courses';
    $mainId = 'main';
@endphp

@extends('layouts.app')

@section('content')
<main id="main" class="va-about-page">

  {{-- ───────────────────────── HERO ───────────────────────── --}}
  <section class="va-hero" id="top" aria-labelledby="va-hero-title">
    <div class="container va-hero-grid">
      <div class="va-hero-copy">
        <span class="va-eyebrow">Postgraduate · PG</span>
        <h1 id="va-hero-title">
          Turn your degree into a <span class="va-hero-num">global</span>
          <em>career move.</em>
        </h1>
        <p class="va-hero-lede">
          Master's and postgraduate study is about fit — the right program, the right faculty, the right outcome. We help you position your experience, target programs that match your goals, and build applications that stand up to scrutiny.
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
        <div class="va-hero-photo va-hero-photo--lg" style="background-image: url('https://images.unsplash.com/photo-1532619675605-1ede6c2ed2b0?auto=format&fit=crop&w=900&h=1100&q=82');"></div>
        <div class="va-hero-photo va-hero-photo--sm" style="background-image: url('https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&w=600&h=600&q=82');"></div>
        <div class="va-hero-badge">
          <i data-lucide="award"></i>
          <div>
            <strong>Postgraduate</strong>
            <span>Master's &amp; beyond</span>
          </div>
        </div>
      </aside>
    </div>
  </section>

  {{-- ─────────────────── WHAT WE COVER ─────────────────── --}}
  <section class="va-pillars" id="what" aria-label="What postgraduate guidance covers">
    <div class="container">

      <article class="va-pillar va-pillar--row">
        <div class="va-pillar-copy">
          <span class="va-eyebrow">Program &amp; faculty fit</span>
          <h2>Target programs by outcome, faculty, and research fit.</h2>
          <p>We go beyond rankings — matching your background and career goals to specific programs, supervisors, and specialisations across 20+ countries.</p>
          <ul class="va-chips">
            <li><i data-lucide="microscope"></i> Research alignment</li>
            <li><i data-lucide="briefcase"></i> Career outcomes</li>
            <li><i data-lucide="globe-2"></i> 20+ destinations</li>
          </ul>
        </div>
        <figure class="va-pillar-media">
          <img src="https://images.unsplash.com/photo-1531545514256-b1400bc00f31?auto=format&fit=crop&w=900&h=720&q=82" alt="Postgraduate students collaborating">
          <span class="va-pillar-tag"><i data-lucide="compass"></i> Step 01</span>
        </figure>
      </article>

      <article class="va-pillar va-pillar--row va-pillar--reverse">
        <div class="va-pillar-copy">
          <span class="va-eyebrow">SOP, LORs &amp; applications</span>
          <h2>Statements of purpose that make your case clearly.</h2>
          <p>We shape your SOP, guide recommenders, and assemble a coherent application — drafted, reviewed, and refined by the same senior advisor from start to submit.</p>
          <ul class="va-chips">
            <li><i data-lucide="pen-line"></i> SOP strategy</li>
            <li><i data-lucide="users"></i> LOR guidance</li>
            <li><i data-lucide="file-check"></i> Application review</li>
          </ul>
        </div>
        <figure class="va-pillar-media">
          <img src="https://images.unsplash.com/photo-1434030216411-0b793f4b4173?auto=format&fit=crop&w=900&h=720&q=82" alt="Writing a statement of purpose">
          <span class="va-pillar-tag"><i data-lucide="edit-3"></i> Step 02</span>
        </figure>
      </article>

      <article class="va-pillar va-pillar--row">
        <div class="va-pillar-copy">
          <span class="va-eyebrow">Funding &amp; visas</span>
          <h2>Assistantships, scholarships, and a confident visa interview.</h2>
          <p>We surface funding you qualify for — including assistantships and grants — compare offers objectively, and rehearse your visa interview until it feels routine.</p>
          <ul class="va-chips">
            <li><i data-lucide="hand-coins"></i> Funding search</li>
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
        <span class="va-eyebrow va-eyebrow--light">Ready to plan your master's?</span>
        <h2 id="va-cta-title">One call with a senior partner — no sales pitch.</h2>
        <p>Bring us your degree, experience, and goals. We will turn them into a focused postgraduate plan.</p>
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
