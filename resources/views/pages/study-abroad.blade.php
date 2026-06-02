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
          <a class="btn btn-ghost" href="{{ route('home') }}#services">
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

  {{-- OLD first-move CTA (white card) — kept for comparison --}}
  <section class="pathfinder" aria-labelledby="pathfinder-title">
    <div class="container pathfinder-shell reveal">
      <div class="pathfinder-lead">
        <span class="eyebrow">Dream search</span>
        <h2 id="pathfinder-title">Find the right first move.</h2>
      </div>
      <div class="pathfinder-action">
        <a class="btn btn-secondary" href="https://gatewayhub.onedegreeadvisory.com/student-profiler/?channel_id=NDg4OQ==" target="_blank" rel="noopener">
          <span>Profiling</span>
          <i data-lucide="sparkles"></i>
        </a>
        <p class="finder-note">
          Your first roadmap starts with country fit, course direction, and intake timing.
        </p>
      </div>
    </div>
  </section>

  {{-- First-move CTA — start the student profiler --}}
  <section class="va-cta" aria-labelledby="va-cta-title">
    <div class="container va-cta-grid">
      <div class="va-cta-copy">
        <span class="va-eyebrow va-eyebrow--light">Dream Search</span>
        <h2 id="va-cta-title">Find the right first move.</h2>
        <p>
          Your first roadmap starts with country fit, course direction, and intake timing.
          Build a personalised profile and we'll map the path from shortlist to arrival.
        </p>

        <div class="va-cta-actions">
          <a class="btn btn-primary" href="https://gatewayhub.onedegreeadvisory.com/student-profiler/?channel_id=NDg4OQ==" target="_blank" rel="noopener">
            <span>Profiling</span>
            <i data-lucide="sparkles"></i>
          </a>
          <a class="btn btn-ghost va-cta-ghost" href="{{ route('contact') }}">
            <i data-lucide="messages-square"></i>
            <span>Talk to an advisor</span>
          </a>
        </div>

        <ul class="va-cta-tags">
          <li><i data-lucide="globe"></i> Country fit</li>
          <li><i data-lucide="compass"></i> Course direction</li>
          <li><i data-lucide="calendar-clock"></i> Intake timing</li>
        </ul>
      </div>

      <figure class="va-cta-media" aria-hidden="true">
        <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=84" alt="">
        <div class="va-cta-card">
          <span class="va-cta-card-num">1&deg;</span>
          <span class="va-cta-card-label">Away from the world</span>
        </div>
      </figure>
    </div>
  </section>

</main>
@endsection
