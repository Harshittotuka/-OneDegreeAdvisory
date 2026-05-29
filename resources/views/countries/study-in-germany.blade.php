@php
    $pageTitle = 'Study in Germany | One Degree Advisory';
    $pageDescription = 'Study in Germany with One Degree Advisory. Tuition-free public universities, English-taught masters, National D visa, and 18-month job-search residence.';
    $activeNav = 'destinations';
    $mainId = 'country-main';
@endphp

@extends('layouts.app')

@section('content')
<main id="country-main" class="country-main">
      <section class="country-hero country-hero--germany" id="top">
        <div class="container country-hero-grid">
          <div class="country-hero-copy">
            <a class="country-back" href="{{ route('home') }}#destinations"><i data-lucide="arrow-left"></i><span>All destinations</span></a>
            <span class="country-flag-lg"><img src="https://flagcdn.com/w160/de.png" alt="Germany flag"></span>
            <span class="eyebrow">Country guide</span>
            <h1>Study in <span class="gold-text">Germany</span></h1>
            <p class="country-lede">Germany combines tuition-free public universities, deeply technical programs, and Europe&rsquo;s strongest engineering economy &mdash; an outstanding value choice for students who plan early.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Plan your Germany application</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="#top-universities"><i data-lucide="building-2"></i><span>Top universities</span></a>
            </div>
          </div>
          <aside class="country-snapshot">
            <h2>At a glance</h2>
            <dl>
              <div><dt>Top universities</dt><dd>40+ globally ranked</dd></div>
              <div><dt>Main intakes</dt><dd>Winter (Oct), Summer (Apr)</dd></div>
              <div><dt>Tuition / year</dt><dd><span>&euro;0</span> &ndash; <span>&euro;3,000</span> (public)</dd></div>
              <div><dt>Living cost / month</dt><dd><span>&euro;850</span> &ndash; <span>&euro;1,200</span></dd></div>
              <div><dt>Student visa</dt><dd>National D Visa</dd></div>
              <div><dt>Post-study work</dt><dd>18-month residence permit</dd></div>
            </dl>
          </aside>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Why Germany</span><h2>Value, engineering, employability</h2><p>Where public funding meets research strength and industrial scale.</p></div>
          <div class="reasons-grid">
            <article class="reason-card"><span class="reason-icon"><i data-lucide="piggy-bank"></i></span><h3>Low or no tuition</h3><p>Most public universities charge only a small semester fee &mdash; usually <span>&euro;100</span>&ndash;<span>&euro;350</span>.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="cog"></i></span><h3>Engineering powerhouse</h3><p>Germany leads Europe in mechanical, automotive, electrical, and renewable energy engineering.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="languages"></i></span><h3>English-taught masters</h3><p>Hundreds of master&rsquo;s programs are delivered in English &mdash; especially STEM.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="briefcase"></i></span><h3>Strong job market</h3><p>18-month post-study job-search residence, with EU Blue Card pathway for skilled roles.</p></article>
          </div>
        </div>
      </section>

      <section id="top-universities" class="country-section alt">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Universities</span><h2>Top universities in Germany</h2><p>A mix of TU9 technical universities and research-focused institutions.</p></div>
          <div class="university-grid">
            <article class="university-card"><span class="uni-rank">01</span><div class="uni-info"><h3>Technical University of Munich (TUM)</h3><p>Germany&rsquo;s top technical university with leading engineering, CS, and natural sciences programs.</p></div></article>
            <article class="university-card"><span class="uni-rank">02</span><div class="uni-info"><h3>Ludwig Maximilian University of Munich (LMU)</h3><p>Broad research university covering humanities, sciences, medicine, and business.</p></div></article>
            <article class="university-card"><span class="uni-rank">03</span><div class="uni-info"><h3>Heidelberg University</h3><p>Germany&rsquo;s oldest university &mdash; medicine, life sciences, physics, and humanities.</p></div></article>
            <article class="university-card"><span class="uni-rank">04</span><div class="uni-info"><h3>Humboldt University of Berlin</h3><p>Berlin-based with leading social sciences, humanities, and arts programs.</p></div></article>
            <article class="university-card"><span class="uni-rank">05</span><div class="uni-info"><h3>RWTH Aachen University</h3><p>Premier engineering university with deep ties to German industry.</p></div></article>
            <article class="university-card"><span class="uni-rank">06</span><div class="uni-info"><h3>Karlsruhe Institute of Technology (KIT)</h3><p>Combined university and research centre with strong engineering and computing.</p></div></article>
            <article class="university-card"><span class="uni-rank">07</span><div class="uni-info"><h3>University of Freiburg</h3><p>Comprehensive university with strengths in life sciences, medicine, and forestry.</p></div></article>
            <article class="university-card"><span class="uni-rank">08</span><div class="uni-info"><h3>TU Berlin</h3><p>Engineering-focused with leading programs in urban planning, transport, and energy.</p></div></article>
          </div>
        </div>
      </section>
      <section class="country-section full-bleed" aria-label="Country highlight banner">
        <div class="container">
          <div class="country-band">
            <img src="https://images.unsplash.com/photo-1467269204594-9661b134dd2b?auto=format&fit=crop&w=2000&q=80" alt="">
            <div class="band-inner">
              <span class="eyebrow">Engineering at its source</span>
              <h2>Low tuition. World-class engineering. Industry on tap.</h2>
              <p>Tuition-free public universities, 120+ English-taught master&rsquo;s, and 18-month post-study Job Seeker visa.</p>
              <div class="band-stats"><div class="band-stat"><strong><span>&euro;0</span></strong><span>Public tuition</span></div><div class="band-stat"><strong>18 mo</strong><span>Job seeker visa</span></div><div class="band-stat"><strong>120+</strong><span>English MS</span></div><div class="band-stat"><strong>TU9</strong><span>Top engineering</span></div></div>
            </div>
          </div>
        </div>
      </section>


      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Programs</span><h2>Popular programs</h2><p>Where Germany&rsquo;s research strength and industry align most clearly.</p></div>
          <div class="programs-grid">
            <span class="program-chip"><i data-lucide="cog"></i>Mechanical &amp; Automotive</span>
            <span class="program-chip"><i data-lucide="cpu"></i>Computer Science &amp; AI</span>
            <span class="program-chip"><i data-lucide="zap"></i>Electrical Engineering</span>
            <span class="program-chip"><i data-lucide="leaf"></i>Renewable Energy</span>
            <span class="program-chip"><i data-lucide="bar-chart-3"></i>Data Science &amp; Analytics</span>
            <span class="program-chip"><i data-lucide="briefcase-business"></i>Management &amp; MBA</span>
            <span class="program-chip"><i data-lucide="stethoscope"></i>Medicine &amp; Life Sciences</span>
            <span class="program-chip"><i data-lucide="landmark"></i>Architecture &amp; Urbanism</span>
          </div>
        </div>
      </section>

      <section class="country-section alt">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Costs</span><h2>Tuition and cost of living</h2><p>Euro ranges &mdash; public universities are the affordable default.</p></div>
          <div class="cost-grid">
            <div class="cost-card"><span class="cost-label">Public university fees</span><span class="cost-value"><span>&euro;100</span>&ndash;<span>&euro;350</span></span><span class="cost-note">Per semester &middot; covers administration and public transport.</span></div>
            <div class="cost-card"><span class="cost-label">Baden-W&uuml;rttemberg tuition</span><span class="cost-value"><span>&euro;1,500</span></span><span class="cost-note">Per semester &middot; non-EU students at public universities in BW.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Munich/Frankfurt</span><span class="cost-value"><span>&euro;1,100</span>&ndash;<span>&euro;1,400</span></span><span class="cost-note">Per month &middot; higher rent in major cities.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Other cities</span><span class="cost-value"><span>&euro;850</span>&ndash;<span>&euro;1,100</span></span><span class="cost-note">Per month &middot; Berlin, Leipzig, Bonn, smaller university towns.</span></div>
          </div>
        </div>
      </section>

      <section class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Funding &amp; visa</span>
            <h2>Scholarships, funding &amp; the Germany visa</h2>
            <p>Two parallel tracks &mdash; how to fund the degree, and how to secure your stay.</p>
          </div>
          <div class="split-info">
            <div class="info-card">
              <h3>Scholarships &amp; funding</h3>
              <ul>
                <li><i data-lucide="check"></i><span>DAAD scholarships for international students at all levels.</span></li>
                <li><i data-lucide="check"></i><span>Deutschlandstipendium &mdash; merit-based monthly stipend at participating universities.</span></li>
                <li><i data-lucide="check"></i><span>Erasmus+ for partner-university mobility.</span></li>
                <li><i data-lucide="check"></i><span>Heinrich B&ouml;ll Foundation, Konrad-Adenauer-Stiftung, and other political foundations.</span></li>
                <li><i data-lucide="check"></i><span>Blocked Account (Sperrkonto) as the standard proof-of-funds vehicle.</span></li>
              </ul>
            </div>
            <div class="info-card">
              <h3>Student visa snapshot</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Visa name: <strong>National Visa (D)</strong> for studies.</span></li>
                <li><i data-lucide="check"></i><span>Requires university admission and Blocked Account of ~<span>&euro;11,904</span>/year (2024).</span></li>
                <li><i data-lucide="check"></i><span>Health insurance is mandatory for the entire stay.</span></li>
                <li><i data-lucide="check"></i><span>Work allowance: 140 full days or 280 half days per year.</span></li>
                <li><i data-lucide="check"></i><span>18-month residence permit after graduation to seek a job.</span></li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section class="country-section alt">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Timeline</span><h2>Application timeline</h2><p>A typical 12&ndash;15 month roadmap for a Winter intake.</p></div>
          <div class="timeline-list">
            <div class="timeline-item"><h4>Profile review &amp; uni-assist</h4><p>12&ndash;15 months out: program shortlist, uni-assist preliminary check.</p></div>
            <div class="timeline-item"><h4>Tests &amp; documents</h4><p>8&ndash;12 months out: IELTS/TOEFL; GRE if program requests; APS certificate where required.</p></div>
            <div class="timeline-item"><h4>Applications</h4><p>April&ndash;July: most Winter intake deadlines; via uni-assist or directly to university.</p></div>
            <div class="timeline-item"><h4>Offers &amp; blocked account</h4><p>June&ndash;August: confirm offer, open Sperrkonto with <span>&euro;11,904</span>+.</p></div>
            <div class="timeline-item"><h4>Visa appointment</h4><p>July&ndash;September: book embassy slot, submit application, attend interview.</p></div>
            <div class="timeline-item"><h4>Arrival &amp; registration</h4><p>September&ndash;October: Anmeldung, residence permit, health insurance, university enrolment.</p></div>
          </div>
        </div>
      </section>

      <section class="country-cta">
        <div class="container country-cta-grid">
          <div>
            <h2>Plan your Germany application with us</h2>
            <p>From uni-assist to Sperrkonto, One Degree covers every step of your Germany journey.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Book a Germany call</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="{{ route('home') }}#destinations"><i data-lucide="compass"></i><span>Compare destinations</span></a>
            </div>
          </div>
          <div class="country-cta-card"><h3>What you&rsquo;ll get</h3><p>A 30-minute counselor-led session, free of cost.</p><ul><li><i data-lucide="check"></i><span>Program &amp; uni-assist plan</span></li><li><i data-lucide="check"></i><span>DAAD &amp; funding signal</span></li><li><i data-lucide="check"></i><span>Blue Card &amp; PR pathway</span></li></ul></div>
        </div>
      </section>
    </main>
@endsection