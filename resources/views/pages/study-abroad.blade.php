@php
    $pageTitle = 'Study Abroad | One Degree Advisory';
    $pageDescription = 'One connected advisory for your study-abroad journey — profile diagnostics, country fit, applications, scholarships, visas, and arrival support.';
    $activeNav = 'services';
    $mainId = 'main';
@endphp

@extends('layouts.app')

@section('content')
<main id="main" class="va-about-page study-abroad-page">

  {{-- Hero (mirrors the About page hero) --}}
  <section class="va-hero" id="top" aria-labelledby="va-hero-title">
    <div class="container va-hero-grid">
      <div class="va-hero-copy">
        <span class="va-eyebrow">Study Abroad</span>
        <h1 id="va-hero-title">One connected advisory for your <em>study-abroad journey</em>.</h1>
        <p class="va-hero-lede">
          From profile diagnostics and country fit to applications, scholarships, visas, and arrival —
          a calm, exacting process for one of life's biggest decisions.
        </p>

        <div class="va-hero-actions">
          <a class="btn btn-primary" href="{{ route('contact') }}">
            <span>Request your diagnostic session</span>
            <i data-lucide="arrow-up-right"></i>
          </a>
          <a class="btn btn-ghost" href="#services">
            <i data-lucide="compass"></i>
            <span>Explore solutions</span>
          </a>
        </div>

        <div class="va-hero-trust">
          <div><strong>360&deg;</strong><span>End-to-end advisory</span></div>
          <div><strong>1:1</strong><span>Senior, partner-led guidance</span></div>
          <div><strong>30+</strong><span>Destinations covered</span></div>
        </div>
      </div>

      <aside class="va-hero-collage" aria-hidden="true">
        <div class="va-hero-photo va-hero-photo--lg" style="background-image: url('https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=900&q=82');"></div>
        <div class="va-hero-photo va-hero-photo--sm" style="background-image: url('https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=640&q=82');"></div>
        <div class="va-hero-badge">
          <i data-lucide="graduation-cap"></i>
          <div>
            <strong>Application-to-arrival</strong>
            <span>Strategy before forms</span>
          </div>
        </div>
      </aside>
    </div>
  </section>

  {{-- First-move CTA — start the student profiler --}}
  <section class="pathfinder" aria-labelledby="pathfinder-title">
    <div class="container pathfinder-shell reveal">
      <div class="pathfinder-top">
        <div class="pathfinder-lead">
          <span class="eyebrow">Dream search</span>
          <h2 id="pathfinder-title">Find the right first move.</h2>
          <p class="finder-note">
            Start with a quick profile and we&rsquo;ll map your path from shortlist to arrival &mdash;
            beginning with the three things that shape every plan.
          </p>
        </div>

        <div class="pathfinder-action">
          <a class="btn btn-secondary" href="https://gatewayhub.onedegreeadvisory.com/student-profiler/?channel_id=NDg4OQ==" target="_blank" rel="noopener">
            <span>Profiling</span>
            <i data-lucide="sparkles"></i>
          </a>
          <span class="pathfinder-meta">
            <i data-lucide="external-link"></i>
            <span>Opens the profiler in a new tab</span>
          </span>
        </div>
      </div>

      <ul class="pathfinder-steps">
        <li class="pathfinder-step">
          <span class="pf-step-ico"><i data-lucide="globe"></i></span>
          <span class="pf-step-copy">
            <strong>Country fit</strong>
            <small>Match budget, intake, and goals to the right destinations.</small>
          </span>
        </li>
        <li class="pathfinder-step">
          <span class="pf-step-ico"><i data-lucide="compass"></i></span>
          <span class="pf-step-copy">
            <strong>Course direction</strong>
            <small>Turn your interests and marks into programs worth pursuing.</small>
          </span>
        </li>
        <li class="pathfinder-step">
          <span class="pf-step-ico"><i data-lucide="calendar-clock"></i></span>
          <span class="pf-step-copy">
            <strong>Intake timing</strong>
            <small>Lock the intake and deadlines that fit your plan.</small>
          </span>
        </li>
      </ul>
    </div>
  </section>

      <section class="method-section" id="method" aria-labelledby="method-title">
        <div class="container method-layout">
          <div class="section-lead sticky-lead reveal">
            <span class="eyebrow">The One Degree method</span>
            <h2 id="method-title">A calm, exacting process for one of life's biggest decisions.</h2>
            <p>
              Inspired by elite admissions consulting, but built for modern global education:
              transparent, data-aware, deeply personal, and practical enough for real deadlines.
            </p>
            <a class="text-link" href="{{ route('contact') }}">
              <span>Request your diagnostic session</span>
              <i data-lucide="arrow-up-right"></i>
            </a>
          </div>

          <div class="journey-steps">
            <article class="journey-step reveal">
              <span>01</span>
              <h3>Profile diagnostic</h3>
              <p>We review academics, interests, constraints, ambitions, budget, and family priorities before recommending a path.</p>
            </article>
            <article class="journey-step reveal">
              <span>02</span>
              <h3>Country and program fit</h3>
              <p>Destinations are compared by curriculum, outcomes, work pathways, scholarships, lifestyle, and visa reality.</p>
            </article>
            <article class="journey-step reveal">
              <span>03</span>
              <h3>Application architecture</h3>
              <p>Your story, documents, essays, test plan, and university list are aligned into one credible application system.</p>
            </article>
            <article class="journey-step reveal">
              <span>04</span>
              <h3>Decision and departure</h3>
              <p>Offers are compared, finances are organized, visa readiness is checked, and arrival planning becomes simple.</p>
            </article>
          </div>
        </div>
      </section>

      <section class="solutions-section" id="services" aria-labelledby="services-title">
        <div class="container edge-container">
          <div class="section-lead centered reveal">
            <span class="eyebrow">360 solutions</span>
            <h2 id="services-title">Find every solution, from applications to accommodations.</h2>
            <p>
              One connected advisory layer for the whole student journey: program discovery,
              applications, scholarships, financial planning, visa readiness, and arrival support.
            </p>
            <a class="btn btn-secondary solution-cta" href="{{ route('contact') }}">
              <span>Plan with One Degree</span>
              <i data-lucide="arrow-up-right"></i>
            </a>
          </div>

          <div class="service-grid">
            <article class="service-card reveal">
              <i data-lucide="compass"></i>
              <h3>Course and Career Mapping</h3>
              <p>Turn interests, marks, experience, and career goals into country and program options worth pursuing.</p>
            </article>
            <article class="service-card reveal">
              <i data-lucide="building-2"></i>
              <h3>University Shortlisting</h3>
              <p>Build ambitious, realistic, and secure lists with admissions fit, costs, city life, and outcomes in view.</p>
            </article>
            <article class="service-card service-card--cta reveal">
              <i data-lucide="file-text"></i>
              <h3>Applications and Essays</h3>
              <p>Shape documents, SOPs, personal statements, activity records, and recommendations into a coherent story.</p>
              <a class="service-card-cta" href="{{ route('services.test-prep') }}">
                <i data-lucide="clipboard-check"></i>
                <span>Test Preparation</span>
              </a>
            </article>
            <article class="service-card reveal">
              <i data-lucide="wallet-cards"></i>
              <h3>Scholarship and Finance</h3>
              <p>Compare scholarships, assistantships, payment timelines, and loan readiness before final decisions.</p>
            </article>
            <article class="service-card reveal">
              <i data-lucide="stamp"></i>
              <h3>Visa Counseling</h3>
              <p>Prepare documents, interview answers, intent clarity, and compliance basics with a structured checklist.</p>
            </article>
            <article class="service-card reveal">
              <i data-lucide="plane-takeoff"></i>
              <h3>Pre and Post Departure Support</h3>
              <p>Plan accommodation, packing, banking, insurance, campus arrival, and the first 30 days abroad.</p>
            </article>
          </div>
        </div>
      </section>

</main>
@endsection
