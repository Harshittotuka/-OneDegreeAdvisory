@php
    $pageTitle = 'Study in Finland | One Degree Advisory';
    $pageDescription = 'Study in Finland with One Degree Advisory. English-taught programs, tuition waivers, residence permit, 2-year job-seeker, FAQs.';
    $activeNav = 'destinations';
    $mainId = 'country-main';
@endphp

@extends('layouts.app')

@section('content')
<main id="country-main" class="country-main">
      <section class="country-hero country-hero--finland" id="top">
        <div class="container country-hero-grid">
          <div class="country-hero-copy">
            <a class="country-back" href="{{ route('home') }}#destinations"><i data-lucide="arrow-left"></i><span>All destinations</span></a>
            <span class="country-flag-lg"><img src="https://flagcdn.com/w160/fi.png" alt="Finland flag"></span>
            <span class="eyebrow">Country guide</span>
            <h1>Study in <span class='gold-text'>Finland</span></h1>
            <p class="country-lede">Finland blends Nordic quality of life with world-leading engineering and design programs &mdash; English-taught, low-cost, and offering a 2-year job-seeker permit after graduation.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Plan your Finland application</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="#top-universities"><i data-lucide="building-2"></i><span>Top universities</span></a>
            </div>
          </div>
          <aside class="country-snapshot">
            <h2>At a glance</h2>
            <dl>
              <div><dt>Top universities</dt><dd>13 universities + 22 UAS</dd></div>
              <div><dt>Main intakes</dt><dd>September, January</dd></div>
              <div><dt>Tuition / year</dt><dd><span>&euro;8,000</span> &ndash; <span>&euro;18,000</span></dd></div>
              <div><dt>Living cost / month</dt><dd><span>&euro;700</span> &ndash; <span>&euro;1,100</span></dd></div>
              <div><dt>Student visa</dt><dd>Residence permit for studies</dd></div>
              <div><dt>Post-study work</dt><dd>Job-seeker permit &middot; 2 years</dd></div>
            </dl>
          </aside>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Why Finland</span><h2>Nordic quality. Engineering depth. EU mobility.</h2><p>World-leading universities, English-taught degrees, and a 2-year post-study window in the EU.</p></div>
          <div class="reasons-grid">
            <article class="reason-card"><span class="reason-icon"><i data-lucide="award"></i></span><h3>Top engineering schools</h3><p>Aalto, Tampere, and LUT rank among Europe&rsquo;s best for engineering &amp; design.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="languages"></i></span><h3>English-taught programs</h3><p>500+ master&rsquo;s and bachelor&rsquo;s programs taught in English.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="leaf"></i></span><h3>Quality of life</h3><p>Consistently ranked the world&rsquo;s happiest country &mdash; safe, clean, well-connected.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="briefcase"></i></span><h3>Job-seeker permit</h3><p>2 years after graduation to find work in Finland or the EU.</p></article>
          </div>
        </div>
      </section>

      <section class="country-section full-bleed" aria-label="Country highlight banner">
        <div class="container">
          <div class="country-band">
            <img src="https://images.unsplash.com/photo-1530908295418-a12e326966ba?auto=format&fit=crop&w=2000&q=80" alt="">
            <div class="band-inner">
              <span class="eyebrow">Nordic engineering &amp; design</span>
              <h2>Aalto, Tampere, LUT &mdash; engineering at its best.</h2>
              <p>Finland&rsquo;s research universities pioneered design thinking and circular-economy engineering &mdash; with a 2-year stay-back to put it into practice.</p>
              <div class="band-stats"><div class="band-stat"><strong>500+</strong><span>English programs</span></div><div class="band-stat"><strong>2 yrs</strong><span>Job-seeker</span></div><div class="band-stat"><strong>EU</strong><span>Member state</span></div><div class="band-stat"><strong>#1</strong><span>Happiness index</span></div></div>
            </div>
          </div>
        </div>
      </section>

      <section id="top-universities" class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left"><span class="eyebrow">Universities</span><h2>Top universities in Finland</h2><p>A representative shortlist across research universities and specialist institutions.</p></div>
          <div class="university-grid">
            <article class="university-card"><span class="uni-rank">01</span><div class="uni-info"><h3>Aalto University</h3><p>Helsinki-based; top European engineering, business, and design.</p></div></article>
            <article class="university-card"><span class="uni-rank">02</span><div class="uni-info"><h3>University of Helsinki</h3><p>Finland&rsquo;s largest research university &mdash; sciences, humanities, law.</p></div></article>
            <article class="university-card"><span class="uni-rank">03</span><div class="uni-info"><h3>Tampere University</h3><p>Strong in engineering, computing, and medical sciences.</p></div></article>
            <article class="university-card"><span class="uni-rank">04</span><div class="uni-info"><h3>University of Turku</h3><p>Broad research strength &mdash; biosciences, law, business.</p></div></article>
            <article class="university-card"><span class="uni-rank">05</span><div class="uni-info"><h3>LUT University</h3><p>Engineering and business focus, with sustainability emphasis.</p></div></article>
            <article class="university-card"><span class="uni-rank">06</span><div class="uni-info"><h3>University of Oulu</h3><p>Northern Finland; ICT, computing, and natural sciences.</p></div></article>
            <article class="university-card"><span class="uni-rank">07</span><div class="uni-info"><h3>University of Jyv&auml;skyl&auml;</h3><p>Education, sport sciences, and humanities.</p></div></article>
            <article class="university-card"><span class="uni-rank">08</span><div class="uni-info"><h3>&Aring;bo Akademi University</h3><p>Swedish-medium with international research strength.</p></div></article>
          </div>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Programs</span><h2>Popular programs</h2><p>The most-applied tracks for international students.</p></div>
          <div class="programs-grid">
            <span class="program-chip"><i data-lucide="cog"></i>Engineering</span>
            <span class="program-chip"><i data-lucide="cpu"></i>Computer Science &amp; AI</span>
            <span class="program-chip"><i data-lucide="palette"></i>Design &amp; Media</span>
            <span class="program-chip"><i data-lucide="briefcase-business"></i>Business &amp; Management</span>
            <span class="program-chip"><i data-lucide="leaf"></i>Sustainable Tech</span>
            <span class="program-chip"><i data-lucide="flask-conical"></i>Biotech &amp; Sciences</span>
            <span class="program-chip"><i data-lucide="graduation-cap"></i>Education</span>
            <span class="program-chip"><i data-lucide="scale"></i>Law &amp; Policy</span>
          </div>
        </div>
      </section>

      <section class="country-section paper">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Costs</span><h2>Tuition and cost of living</h2><p>Indicative ranges &mdash; final figures depend on program and city.</p></div>
          <div class="cost-grid">
            <div class="cost-card"><span class="cost-label">Bachelor&rsquo;s tuition</span><span class="cost-value"><span>&euro;8,000</span> &ndash; <span>&euro;14,000</span></span><span class="cost-note">Per year &middot; for non-EU/EEA students.</span></div>
            <div class="cost-card"><span class="cost-label">Master&rsquo;s tuition</span><span class="cost-value"><span>&euro;10,000</span> &ndash; <span>&euro;18,000</span></span><span class="cost-note">Per year &middot; many universities offer scholarship-discounted rates.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Helsinki</span><span class="cost-value"><span>&euro;900</span> &ndash; <span>&euro;1,100</span></span><span class="cost-note">Per month &middot; capital-city budget.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Other cities</span><span class="cost-value"><span>&euro;700</span> &ndash; <span>&euro;900</span></span><span class="cost-note">Per month &middot; Tampere, Turku, Jyv&auml;skyl&auml;.</span></div>
          </div>
        </div>
      </section>

      <section class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Funding &amp; visa</span>
            <h2>Scholarships, funding &amp; the Finland student visa</h2>
            <p>Two parallel tracks &mdash; how to fund the degree, and how to secure your stay.</p>
          </div>
          <div class="split-info">
            <div class="info-card">
              <h3>Scholarships &amp; funding</h3>
              <ul>
                <li><i data-lucide="check"></i><span>University-specific tuition waivers &mdash; many cover 50%&ndash;100% of fees.</span></li>
                <li><i data-lucide="check"></i><span>Finland Scholarship for top non-EU/EEA master&rsquo;s students.</span></li>
                <li><i data-lucide="check"></i><span>Erasmus Mundus Joint Master&rsquo;s for high-merit candidates.</span></li>
                <li><i data-lucide="check"></i><span>PhD positions are typically fully-funded with stipend.</span></li>
                <li><i data-lucide="check"></i><span>Education loans through major Indian and international lenders.</span></li>
              </ul>
            </div>
            <div class="info-card">
              <h3>Student visa snapshot</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Visa name: <strong>Residence permit for studies</strong> via Migri (Finnish Immigration Service).</span></li>
                <li><i data-lucide="check"></i><span>Requires admission letter and proof of paid tuition.</span></li>
                <li><i data-lucide="check"></i><span>Proof of funds: <span>&euro;800</span>/month for 12 months (<span>&euro;9,600</span>).</span></li>
                <li><i data-lucide="check"></i><span>Standard processing: 1&ndash;3 months; apply via Enter Finland.</span></li>
                <li><i data-lucide="check"></i><span>Work allowance: 30 hours/week on average during studies.</span></li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left"><span class="eyebrow">Timeline</span><h2>Application timeline</h2><p>A typical roadmap from profile review to arrival.</p></div>
          <div class="timeline-list">
            <div class="timeline-item"><h4>Profile review &amp; shortlist</h4><p>12&ndash;14 months out: program direction, English requirement.</p></div>
            <div class="timeline-item"><h4>Tests &amp; documents</h4><p>8&ndash;12 months out: IELTS/TOEFL; program-specific entrance tests.</p></div>
            <div class="timeline-item"><h4>Applications</h4><p>Dec&ndash;Jan via Studyinfo (most master&rsquo;s programs).</p></div>
            <div class="timeline-item"><h4>Offers &amp; deposit</h4><p>Apr&ndash;Jun: accept offer, pay tuition deposit.</p></div>
            <div class="timeline-item"><h4>Residence permit</h4><p>May&ndash;Aug: apply via Enter Finland, biometrics at VFS.</p></div>
            <div class="timeline-item"><h4>Pre-departure &amp; arrival</h4><p>Aug&ndash;Sep: housing, KELA registration, insurance, arrival.</p></div>
          </div>
        </div>
      </section>

      <section class="country-cta">
        <div class="container country-cta-grid">
          <div>
            <h2>Plan your Finland application with us</h2>
            <p>From English master&rsquo;s shortlists to Enter Finland submissions &mdash; we map a clean Finland-ready application.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Book a Finland call</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="{{ route('home') }}#destinations"><i data-lucide="compass"></i><span>Compare destinations</span></a>
            </div>
          </div>
          <div class="country-cta-card">
            <h3>What you&rsquo;ll get</h3>
            <p>A 30-minute counselor-led session, free of cost.</p>
            <ul>
              <li><i data-lucide="check"></i><span>Aalto vs Tampere fit</span></li>
              <li><i data-lucide="check"></i><span>Tuition + waiver plan</span></li>
              <li><i data-lucide="check"></i><span>Job-seeker permit roadmap</span></li>
            </ul>
          </div>
        </div>
      </section>
    </main>
@endsection