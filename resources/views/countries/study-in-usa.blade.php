@php
    $pageTitle = 'Study in the USA | OneDegreeAdvisory';
    $pageDescription = 'Study in the USA with OneDegreeAdvisory. Top universities, tuition, scholarships, F-1 visa, and OPT/STEM extension work pathway.';
    $activeNav = 'destinations';
    $mainId = 'country-main';
@endphp

@extends('layouts.app')

@section('content')
<main id="country-main" class="country-main">
      <section class="country-hero country-hero--usa" id="top">
        <div class="container country-hero-grid">
          <div class="country-hero-copy">
            <a class="country-back" href="{{ route('home') }}#destinations">
              <i data-lucide="arrow-left"></i><span>All destinations</span>
            </a>
            <span class="country-flag-lg"><img src="https://flagcdn.com/w160/us.png" alt="USA flag"></span>
            <span class="eyebrow">Country guide</span>
            <h1>Study in the <span class="gold-text">United States</span></h1>
            <p class="country-lede">
              The United States offers unmatched program choice, research scale, and specialization &mdash; ideal for
              students who want flexibility, world-class faculty, and powerful post-study work options through OPT and STEM extension.
            </p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Plan your USA application</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="#top-universities"><i data-lucide="building-2"></i><span>Top universities</span></a>
            </div>
          </div>
          <aside class="country-snapshot">
            <h2>At a glance</h2>
            <dl>
              <div><dt>Top universities</dt><dd>200+ globally ranked</dd></div>
              <div><dt>Main intakes</dt><dd>Fall, Spring, Summer</dd></div>
              <div><dt>Tuition / year</dt><dd><span data-money="25000" data-currency="USD">$25,000</span> &ndash; <span data-money="70000" data-currency="USD">$70,000</span></dd></div>
              <div><dt>Living cost / month</dt><dd><span data-money="1000" data-currency="USD">$1,000</span> &ndash; <span data-money="2500" data-currency="USD">$2,500</span></dd></div>
              <div><dt>Student visa</dt><dd>F-1</dd></div>
              <div><dt>Post-study work</dt><dd>OPT 12 mo + 24 mo STEM</dd></div>
            </dl>
          </aside>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Why the USA</span>
            <h2>Choice, research, specialization</h2>
            <p>Built for students who want depth, optionality, and global brand value.</p>
          </div>
          <div class="reasons-grid">
            <article class="reason-card"><span class="reason-icon"><i data-lucide="award"></i></span><h3>Elite research</h3><p>Ivy League, MIT, Stanford, and large state research universities lead globally in funding and output.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="layout-grid"></i></span><h3>Flexible curriculum</h3><p>Major-minor combinations, double majors, and broad first-year exposure let you shape your direction.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="briefcase"></i></span><h3>OPT + STEM extension</h3><p>Up to 36 months of post-completion work for STEM graduates &mdash; a clear bridge to careers.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="hand-coins"></i></span><h3>Funding depth</h3><p>Assistantships, fellowships, and large endowments often offset tuition meaningfully at graduate level.</p></article>
          </div>
        </div>
      </section>

      <section id="top-universities" class="country-section alt">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Universities</span>
            <h2>Top universities in the USA</h2>
            <p>A representative sample &mdash; final shortlists depend on program, location, and fit.</p>
          </div>
          <div class="university-grid">
            <article class="university-card"><span class="uni-rank">01</span><div class="uni-info"><h3>Massachusetts Institute of Technology (MIT)</h3><p>Global leader in engineering, computer science, economics, and applied research.</p></div></article>
            <article class="university-card"><span class="uni-rank">02</span><div class="uni-info"><h3>Stanford University</h3><p>Silicon Valley anchor with strengths in CS, business, engineering, and the social sciences.</p></div></article>
            <article class="university-card"><span class="uni-rank">03</span><div class="uni-info"><h3>Harvard University</h3><p>Renowned across business, law, medicine, public policy, and the humanities.</p></div></article>
            <article class="university-card"><span class="uni-rank">04</span><div class="uni-info"><h3>California Institute of Technology (Caltech)</h3><p>Small, intense STEM university with leading physics, chemistry, and engineering programs.</p></div></article>
            <article class="university-card"><span class="uni-rank">05</span><div class="uni-info"><h3>Princeton University</h3><p>Strong undergraduate experience and elite graduate research in mathematics and economics.</p></div></article>
            <article class="university-card"><span class="uni-rank">06</span><div class="uni-info"><h3>Yale University</h3><p>Historic Ivy with leading law, drama, management, and liberal arts programs.</p></div></article>
            <article class="university-card"><span class="uni-rank">07</span><div class="uni-info"><h3>Columbia University</h3><p>New York City research university across journalism, business, engineering, and policy.</p></div></article>
            <article class="university-card"><span class="uni-rank">08</span><div class="uni-info"><h3>University of Chicago</h3><p>Rigorous, ideas-first culture with leading economics, statistics, and policy programs.</p></div></article>
          </div>
        </div>
      </section>
      <section class="country-section full-bleed" aria-label="Country highlight banner">
        <div class="container">
          <div class="country-band">
            <img src="https://images.unsplash.com/photo-1496588152823-86ff7695e68f?auto=format&fit=crop&w=2000&q=80" alt="">
            <div class="band-inner">
              <span class="eyebrow">World&rsquo;s leading universities</span>
              <h2>Ivy League, top engineering, and OPT-ready master&rsquo;s.</h2>
              <p>Eight of the global top ten universities call the US home &mdash; with up to 36 months of post-study work for STEM graduates.</p>
              <div class="band-stats"><div class="band-stat"><strong>8 of 10</strong><span>QS Top 10</span></div><div class="band-stat"><strong>36 mo</strong><span>STEM OPT</span></div><div class="band-stat"><strong>4,000+</strong><span>Universities</span></div><div class="band-stat"><strong>F-1</strong><span>Student visa</span></div></div>
            </div>
          </div>
        </div>
      </section>


      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Programs</span><h2>Popular programs</h2><p>Where Indian and international applicants most often build their USA shortlist.</p></div>
          <div class="programs-grid">
            <span class="program-chip"><i data-lucide="cpu"></i>Computer Science &amp; AI</span>
            <span class="program-chip"><i data-lucide="briefcase-business"></i>Business &amp; MBA</span>
            <span class="program-chip"><i data-lucide="cog"></i>Engineering</span>
            <span class="program-chip"><i data-lucide="bar-chart-3"></i>Data Science &amp; Analytics</span>
            <span class="program-chip"><i data-lucide="stethoscope"></i>Public Health &amp; Bio</span>
            <span class="program-chip"><i data-lucide="line-chart"></i>Finance &amp; Economics</span>
            <span class="program-chip"><i data-lucide="scale"></i>Law &amp; Policy</span>
            <span class="program-chip"><i data-lucide="palette"></i>Design &amp; Media</span>
          </div>
        </div>
      </section>

      <section class="country-section alt">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Costs</span><h2>Tuition and cost of living</h2><p>Indicative USD ranges &mdash; private universities sit at the higher end of tuition.</p></div>
          <div class="cost-grid">
            <div class="cost-card"><span class="cost-label">Undergraduate tuition</span><span class="cost-value"><span data-money="25000" data-currency="USD">$25,000</span>&ndash;<span data-money="60000" data-currency="USD">$60,000</span></span><span class="cost-note">Per year &middot; public state universities cheaper for in-state, but international rate applies.</span></div>
            <div class="cost-card"><span class="cost-label">Postgraduate tuition</span><span class="cost-value"><span data-money="30000" data-currency="USD">$30,000</span>&ndash;<span data-money="70000" data-currency="USD">$70,000</span></span><span class="cost-note">Per year &middot; STEM and MBA at top schools at upper range.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Tier 1 cities</span><span class="cost-value"><span data-money="1500" data-currency="USD">$1,500</span>&ndash;<span data-money="2500" data-currency="USD">$2,500</span></span><span class="cost-note">Per month &middot; NYC, SF Bay Area, Boston, LA.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Other cities</span><span class="cost-value"><span data-money="1000" data-currency="USD">$1,000</span>&ndash;<span data-money="1600" data-currency="USD">$1,600</span></span><span class="cost-note">Per month &middot; college towns and mid-size metros.</span></div>
          </div>
        </div>
      </section>

      <section class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Funding &amp; visa</span>
            <h2>Scholarships, funding &amp; the US F-1 visa</h2>
            <p>Two parallel tracks &mdash; how to fund the degree, and how to secure your stay.</p>
          </div>
          <div class="split-info">
            <div class="info-card">
              <h3>Scholarships &amp; funding</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Fulbright Foreign Student Program &mdash; flagship US government award.</span></li>
                <li><i data-lucide="check"></i><span>Inlaks Shivdasani &amp; Tata Scholarships for Indian students.</span></li>
                <li><i data-lucide="check"></i><span>University fellowships, assistantships (TA/RA), and need-based aid.</span></li>
                <li><i data-lucide="check"></i><span>Departmental research funding &mdash; especially for PhD applicants.</span></li>
                <li><i data-lucide="check"></i><span>Education loans through Indian and US lenders.</span></li>
              </ul>
            </div>
            <div class="info-card">
              <h3>Student visa snapshot</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Visa name: <strong>F-1 Student Visa</strong>.</span></li>
                <li><i data-lucide="check"></i><span>Requires Form I-20 from a SEVP-certified institution after admission.</span></li>
                <li><i data-lucide="check"></i><span>SEVIS fee + visa application fee, plus consular interview.</span></li>
                <li><i data-lucide="check"></i><span>Strong financial proof of tuition + living expenses for first year.</span></li>
                <li><i data-lucide="check"></i><span>On-campus work allowed up to 20 hours/week during term.</span></li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section class="country-section alt">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Timeline</span><h2>Application timeline</h2><p>A typical 14&ndash;18 month roadmap for a Fall intake.</p></div>
          <div class="timeline-list">
            <div class="timeline-item"><h4>Profile review &amp; test plan</h4><p>14&ndash;18 months out: clarify direction, pick test (GRE/GMAT/SAT/ACT), assess profile.</p></div>
            <div class="timeline-item"><h4>Standardised tests</h4><p>10&ndash;14 months out: TOEFL/IELTS plus GRE, GMAT, SAT, or ACT depending on program.</p></div>
            <div class="timeline-item"><h4>Essays, LORs, applications</h4><p>August&ndash;December: SOPs, recommendations, transcripts, and submissions.</p></div>
            <div class="timeline-item"><h4>Decisions &amp; financial aid</h4><p>January&ndash;April: review admits, funding offers, and finalize school.</p></div>
            <div class="timeline-item"><h4>I-20 &amp; F-1 visa</h4><p>April&ndash;July: receive I-20, pay SEVIS, complete visa interview.</p></div>
            <div class="timeline-item"><h4>Pre-departure &amp; arrival</h4><p>July&ndash;August: housing, banking, insurance, flight, campus arrival.</p></div>
          </div>
        </div>
      </section>

      <section class="country-cta">
        <div class="container country-cta-grid">
          <div>
            <h2>Plan your USA application with us</h2>
            <p>From test choice to I-20, OneDegree helps you build a precise, fundable USA application.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Book a USA call</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="{{ route('home') }}#destinations"><i data-lucide="compass"></i><span>Compare destinations</span></a>
            </div>
          </div>
          <div class="country-cta-card">
            <h3>What you&rsquo;ll get</h3>
            <p>A 30-minute counselor-led session, free of cost.</p>
            <ul>
              <li><i data-lucide="check"></i><span>School-tier strategy &amp; fit</span></li>
              <li><i data-lucide="check"></i><span>Funding &amp; assistantship signal</span></li>
              <li><i data-lucide="check"></i><span>OPT &amp; STEM pathway clarity</span></li>
            </ul>
          </div>
        </div>
      </section>
    </main>
@endsection