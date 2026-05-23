@php
    $pageTitle = 'About | OneDegreeAdvisory';
    $pageDescription = 'Meet OneDegreeAdvisory — a senior global education advisory helping students architect study-abroad futures with strategy, evidence, and care.';
    $activeNav = 'about';
    $mainId = 'main';
@endphp

@extends('layouts.app')

@section('content')
<main id="main" class="insights-page-main">
      <section class="insights-page-hero" id="top" aria-labelledby="about-page-title">
        <div class="container insights-page-hero-grid">
          <div class="insights-page-copy">
            <span class="eyebrow">Meet OneDegree</span>
            <h1 id="about-page-title">One careful method. Every file.</h1>
            <p>
              The advisor who builds your shortlist is the same person reviewing your essays in November and preparing your visa story in April. No handoffs. No volume targets.
            </p>
            <div class="insights-page-actions">
              <a class="btn btn-primary" href="#partners">
                <span>Meet the partners</span>
                <i data-lucide="arrow-down"></i>
              </a>
              <a class="btn btn-ghost" href="{{ route('contact') }}">
                <i data-lucide="message-circle"></i>
                <span>Talk to a partner</span>
              </a>
            </div>
          </div>

          <aside class="insights-hero-panel" aria-label="What you can expect from OneDegree">
            <div class="insights-hero-image" style="background-image: url('https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=1400&q=82');"></div>
            <div class="insights-hero-note">
              <span>What to expect</span>
              <ul>
                <li><i data-lucide="check"></i> A senior partner on every file</li>
                <li><i data-lucide="check"></i> No commissions, no kickbacks</li>
                <li><i data-lucide="check"></i> Capped intake by design</li>
              </ul>
            </div>
          </aside>
        </div>
      </section>

      <section class="about-stats-band" aria-label="OneDegree by the numbers">
        <div class="container about-stats-grid">
          <div class="about-stat">
            <span class="about-stat-num">25<small>+</small></span>
            <span class="about-stat-label">Combined years of partner experience</span>
          </div>
          <div class="about-stat">
            <span class="about-stat-num">20<small>+</small></span>
            <span class="about-stat-label">Study destinations</span>
          </div>
          <div class="about-stat">
            <span class="about-stat-num">96<small>%</small></span>
            <span class="about-stat-label">Top-choice visa approvals</span>
          </div>
          <div class="about-stat">
            <span class="about-stat-num">01</span>
            <span class="about-stat-label">Senior advisor per student</span>
          </div>
        </div>
      </section>

      <section class="origin-section" id="story" aria-labelledby="about-story-title">
        <div class="container origin-shell">
          <header class="origin-head">
            <span class="origin-eyebrow">Our origin</span>
            <span class="origin-meta">A new advisory &middot; Global desk</span>
          </header>

          <div class="origin-lede">
            <span class="origin-quote-mark" aria-hidden="true">&ldquo;</span>
            <h2 id="about-story-title">
              We started OneDegree because the study-abroad conversation had grown loud, transactional, and quietly unfair to families.
            </h2>
            <p class="origin-signature">&mdash; The four partners</p>
          </div>

          <div class="origin-body">
            <div class="origin-body-col">
              <p>Our partners spent careers inside admissions offices, consular desks, and test-prep rooms &mdash; reading the files most students never get a second look at, and watching how small choices early on quietly redirected entire lives.</p>
            </div>
            <div class="origin-body-col">
              <p>So we are beginning small, capped, and senior-led. No volume targets, no junior account managers, no commissions tilting us toward the wrong campus. Every file gets the attention rankings tables never could.</p>
            </div>
          </div>

          <div class="origin-pillars" aria-label="What we stand for">
            <article class="origin-pillar">
              <span class="origin-pillar-num">01</span>
              <h3>Profile first</h3>
              <p>We build outward from the student &mdash; strengths, constraints, ambitions &mdash; not inward from a brochure list.</p>
            </article>
            <article class="origin-pillar">
              <span class="origin-pillar-num">02</span>
              <h3>No commissions</h3>
              <p>Families pay us, not universities. The recommendation you get is the one we actually believe in.</p>
            </article>
            <article class="origin-pillar">
              <span class="origin-pillar-num">03</span>
              <h3>Outcomes lens</h3>
              <p>A campus is a five-year decision, not a four-year one. We plan for what happens after the admit letter.</p>
            </article>
          </div>

          <a class="origin-cta" href="{{ route('contact') }}">
            <span>Talk to a partner</span>
            <i data-lucide="arrow-up-right"></i>
          </a>
        </div>
      </section>

      <section class="about-team-section" id="partners" aria-labelledby="about-team-title">
        <div class="container">
          <div class="insights-section-head">
            <div>
              <span class="insights-eyebrow">The partners</span>
              <h2 id="about-team-title">The advisors who will actually read your file.</h2>
            </div>
            <p>No junior account managers between you and the people making decisions.</p>
          </div>

          <div class="about-team-grid">
            <article class="about-team-card">
              <div class="about-team-portrait" style="background-image: url('https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?auto=format&fit=crop&w=600&h=720&q=82');" aria-hidden="true"></div>
              <div class="about-team-body">
                <h3>Aanya Mehra</h3>
                <span class="about-team-role">Managing partner</span>
                <p>Former admissions reader at a top-ten US university. Leads strategy for selective undergraduate and MBA files.</p>
                <span class="about-team-desk">US &middot; Canada</span>
              </div>
            </article>
            <article class="about-team-card">
              <div class="about-team-portrait" style="background-image: url('https://images.unsplash.com/photo-1544723795-3fb6469f5b39?auto=format&fit=crop&w=600&h=720&q=82');" aria-hidden="true"></div>
              <div class="about-team-body">
                <h3>Rohan Iyer</h3>
                <span class="about-team-role">Partner</span>
                <p>Oxford alum and 15-year admissions interviewer. Runs the Europe desk and the scholarship lab.</p>
                <span class="about-team-desk">UK &middot; Europe</span>
              </div>
            </article>
            <article class="about-team-card">
              <div class="about-team-portrait" style="background-image: url('https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=600&h=720&q=82');" aria-hidden="true"></div>
              <div class="about-team-body">
                <h3>Priya Raghavan</h3>
                <span class="about-team-role">Partner</span>
                <p>Test-prep strategist who has coached 500+ students through SAT, GRE, GMAT, and IELTS without the cram-school treadmill.</p>
                <span class="about-team-desk">Tests &middot; Profile</span>
              </div>
            </article>
            <article class="about-team-card">
              <div class="about-team-portrait" style="background-image: url('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=600&h=720&q=82');" aria-hidden="true"></div>
              <div class="about-team-body">
                <h3>Daniel Okafor</h3>
                <span class="about-team-role">Partner</span>
                <p>Former consular officer. Builds visa narratives and pre-departure plans that hold up under real interview pressure.</p>
                <span class="about-team-desk">Visa &middot; Arrival</span>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section class="about-principles-section" aria-labelledby="about-principles-title">
        <div class="container">
          <div class="insights-section-head">
            <div>
              <span class="insights-eyebrow">Principles</span>
              <h2 id="about-principles-title">Three rules we will not break for any file.</h2>
            </div>
            <p>The non-negotiables that shape every conversation, draft, and decision we make alongside the families we work with.</p>
          </div>

          <div class="about-principles-grid">
            <article class="about-principle-card">
              <span class="about-principle-num">01</span>
              <h3>If it does not fit, we say so.</h3>
              <p>We will tell you when a country, course, or test is wrong &mdash; even if it ends the conversation. Honesty is the whole product.</p>
            </article>
            <article class="about-principle-card">
              <span class="about-principle-num">02</span>
              <h3>The advisor who starts with you, finishes with you.</h3>
              <p>No mid-cycle handoffs. The senior partner who builds your shortlist is the one reviewing your essays at midnight in November.</p>
            </article>
            <article class="about-principle-card">
              <span class="about-principle-num">03</span>
              <h3>The student writes. We coach.</h3>
              <p>We will not ghostwrite. Strong essays come from real reflection, and admissions readers can always tell the difference.</p>
            </article>
          </div>
        </div>
      </section>

      <section class="insights-cta-section" aria-labelledby="about-cta-title">
        <div class="container insights-cta-panel">
          <div>
            <span class="insights-eyebrow">Bring us the messy draft</span>
            <h2 id="about-cta-title">We will turn scattered research into a decision map.</h2>
          </div>
          <a class="btn btn-primary" href="{{ route('contact') }}">
            <span>Book a profile review</span>
            <i data-lucide="arrow-up-right"></i>
          </a>
        </div>
      </section>
    </main>
@endsection
