@php
    $pageTitle = 'Study in Netherlands | One Degree Advisory';
    $pageDescription = 'Study in the Netherlands with One Degree Advisory. English-taught programs, tuition, scholarships, MVV residence permit, orientation year, costs, FAQs.';
    $activeNav = 'destinations';
    $mainId = 'country-main';
@endphp

@extends('layouts.app')

@section('content')
<main id="country-main" class="country-main">
      <section class="country-hero country-hero--netherlands" id="top">
        <div class="container country-hero-grid">
          <div class="country-hero-copy">
            <a class="country-back" href="{{ route('home') }}#destinations"><i data-lucide="arrow-left"></i><span>All destinations</span></a>
            <span class="country-flag-lg"><img src="https://flagcdn.com/w160/nl.png" alt="Netherlands flag"></span>
            <span class="eyebrow">Country guide</span>
            <h1>Study in the <span class='gold-text'>Netherlands</span></h1>
            <p class="country-lede">The Netherlands is one of the most English-friendly EU destinations &mdash; with 2,000+ English-taught programs, top engineering and business schools, and a 1-year orientation visa for graduates.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Plan your Netherlands application</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="#top-universities"><i data-lucide="building-2"></i><span>Top universities</span></a>
            </div>
          </div>
          <aside class="country-snapshot">
            <h2>At a glance</h2>
            <dl>
              <div><dt>Top universities</dt><dd>13 research universities</dd></div>
              <div><dt>Main intakes</dt><dd>September, February</dd></div>
              <div><dt>Tuition / year</dt><dd><span>&euro;8,000</span> &ndash; <span>&euro;22,000</span></dd></div>
              <div><dt>Living cost / month</dt><dd><span>&euro;900</span> &ndash; <span>&euro;1,400</span></dd></div>
              <div><dt>Student visa</dt><dd>MVV + residence permit</dd></div>
              <div><dt>Post-study work</dt><dd>Orientation year &middot; 12 months</dd></div>
            </dl>
          </aside>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Why the Netherlands</span><h2>Globally taught. Industry-aligned. EU-mobile.</h2><p>Practical, English-taught degrees with deep links to multinational employers across Europe.</p></div>
          <div class="reasons-grid">
            <article class="reason-card"><span class="reason-icon"><i data-lucide="languages"></i></span><h3>2,000+ English programs</h3><p>More English-taught degrees than any non-Anglophone country.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="award"></i></span><h3>Top-ranked universities</h3><p>TU Delft, Erasmus, Wageningen, Utrecht consistently in the global top 100.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="briefcase"></i></span><h3>Orientation year</h3><p>12 months post-study to find work; counts toward EU residence.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="globe"></i></span><h3>Global multinationals</h3><p>Shell, Philips, ASML, Unilever, Booking.com all headquartered nearby.</p></article>
          </div>
        </div>
      </section>

      <section class="country-section full-bleed" aria-label="Country highlight banner">
        <div class="container">
          <div class="country-band">
            <img src="https://images.unsplash.com/photo-1467269204594-9661b134dd2b?auto=format&fit=crop&w=2000&q=80" alt="">
            <div class="band-inner">
              <span class="eyebrow">English-taught EU hub</span>
              <h2>Globally ranked. English-taught. Orientation visa included.</h2>
              <p>Dutch research universities pioneered problem-based learning &mdash; with hands-on labs, small cohorts, and 1-year stay-back rights.</p>
              <div class="band-stats"><div class="band-stat"><strong>13</strong><span>Research unis</span></div><div class="band-stat"><strong>2000+</strong><span>English programs</span></div><div class="band-stat"><strong>1 yr</strong><span>Orientation visa</span></div><div class="band-stat"><strong>EU</strong><span>Member state</span></div></div>
            </div>
          </div>
        </div>
      </section>

      <section id="top-universities" class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left"><span class="eyebrow">Universities</span><h2>Top universities in Netherlands</h2><p>A representative shortlist across research universities and specialist institutions.</p></div>
          <div class="university-grid">
            <article class="university-card"><span class="uni-rank">01</span><div class="uni-info"><h3>Delft University of Technology (TU Delft)</h3><p>Europe&rsquo;s leading engineering university &mdash; aerospace, civil, electrical.</p></div></article>
            <article class="university-card"><span class="uni-rank">02</span><div class="uni-info"><h3>University of Amsterdam (UvA)</h3><p>Strong in social sciences, business, communication, and law.</p></div></article>
            <article class="university-card"><span class="uni-rank">03</span><div class="uni-info"><h3>Erasmus University Rotterdam</h3><p>Rotterdam School of Management (RSM) and Erasmus MC.</p></div></article>
            <article class="university-card"><span class="uni-rank">04</span><div class="uni-info"><h3>Utrecht University</h3><p>Top in life sciences, geosciences, and humanities.</p></div></article>
            <article class="university-card"><span class="uni-rank">05</span><div class="uni-info"><h3>Leiden University</h3><p>Oldest university in the Netherlands &mdash; humanities, law, sciences.</p></div></article>
            <article class="university-card"><span class="uni-rank">06</span><div class="uni-info"><h3>Wageningen University</h3><p>World-leading in agriculture, food, and environmental sciences.</p></div></article>
            <article class="university-card"><span class="uni-rank">07</span><div class="uni-info"><h3>Eindhoven University of Technology (TU/e)</h3><p>Engineering and design with deep industry partnerships.</p></div></article>
            <article class="university-card"><span class="uni-rank">08</span><div class="uni-info"><h3>University of Groningen</h3><p>Broad-based research university with strong international cohort.</p></div></article>
          </div>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Programs</span><h2>Popular programs</h2><p>The most-applied tracks for international students.</p></div>
          <div class="programs-grid">
            <span class="program-chip"><i data-lucide="cpu"></i>Computer Science &amp; AI</span>
            <span class="program-chip"><i data-lucide="cog"></i>Engineering</span>
            <span class="program-chip"><i data-lucide="briefcase-business"></i>Business &amp; MBA</span>
            <span class="program-chip"><i data-lucide="leaf"></i>Environmental Sciences</span>
            <span class="program-chip"><i data-lucide="flask-conical"></i>Life Sciences &amp; Biotech</span>
            <span class="program-chip"><i data-lucide="bar-chart-3"></i>Data Science</span>
            <span class="program-chip"><i data-lucide="scale"></i>Law &amp; Policy</span>
            <span class="program-chip"><i data-lucide="palette"></i>Design &amp; Architecture</span>
          </div>
        </div>
      </section>

      <section class="country-section paper">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Costs</span><h2>Tuition and cost of living</h2><p>Indicative ranges &mdash; final figures depend on program and city.</p></div>
          <div class="cost-grid">
            <div class="cost-card"><span class="cost-label">Bachelor&rsquo;s tuition</span><span class="cost-value"><span>&euro;8,000</span> &ndash; <span>&euro;15,000</span></span><span class="cost-note">Per year &middot; research universities.</span></div>
            <div class="cost-card"><span class="cost-label">Master&rsquo;s tuition</span><span class="cost-value"><span>&euro;13,000</span> &ndash; <span>&euro;22,000</span></span><span class="cost-note">Per year &middot; MBA and specialist tracks higher.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Amsterdam</span><span class="cost-value"><span>&euro;1,200</span> &ndash; <span>&euro;1,500</span></span><span class="cost-note">Per month &middot; tight rental market.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Other cities</span><span class="cost-value"><span>&euro;900</span> &ndash; <span>&euro;1,200</span></span><span class="cost-note">Per month &middot; Utrecht, Groningen, Eindhoven.</span></div>
          </div>
        </div>
      </section>

      <section class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Funding &amp; visa</span>
            <h2>Scholarships, funding &amp; the Netherlands student visa</h2>
            <p>Two parallel tracks &mdash; how to fund the degree, and how to secure your stay.</p>
          </div>
          <div class="split-info">
            <div class="info-card">
              <h3>Scholarships &amp; funding</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Holland Scholarship &mdash; <span>&euro;5,000</span> for non-EU bachelor&rsquo;s and master&rsquo;s students.</span></li>
                <li><i data-lucide="check"></i><span>Orange Tulip Scholarship for students from select countries (India included).</span></li>
                <li><i data-lucide="check"></i><span>Erasmus Mundus Joint Master&rsquo;s for top candidates.</span></li>
                <li><i data-lucide="check"></i><span>University-specific awards (e.g. Amsterdam Excellence, TU Delft Justus & Louise van Effen).</span></li>
                <li><i data-lucide="check"></i><span>Education loans through major Indian and international lenders.</span></li>
              </ul>
            </div>
            <div class="info-card">
              <h3>Student visa snapshot</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Visa name: <strong>MVV (entry visa) + residence permit</strong> via the university (sponsor).</span></li>
                <li><i data-lucide="check"></i><span>Requires a letter of admission and proof of paid tuition / deposit.</span></li>
                <li><i data-lucide="check"></i><span>Proof of funds: roughly <span>&euro;14,000</span>/year for living costs.</span></li>
                <li><i data-lucide="check"></i><span>Standard processing: 4&ndash;8 weeks &mdash; university handles most paperwork.</span></li>
                <li><i data-lucide="check"></i><span>Work allowance: 16 hours/week in term, full-time in summer.</span></li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left"><span class="eyebrow">Timeline</span><h2>Application timeline</h2><p>A typical roadmap from profile review to arrival.</p></div>
          <div class="timeline-list">
            <div class="timeline-item"><h4>Profile review &amp; shortlist</h4><p>12&ndash;14 months out: program fit, budget, English requirement.</p></div>
            <div class="timeline-item"><h4>Tests &amp; documents</h4><p>8&ndash;12 months out: IELTS/TOEFL; GMAT/GRE if program requires.</p></div>
            <div class="timeline-item"><h4>Applications</h4><p>Oct&ndash;Apr (Studielink for UG, university portals for PG).</p></div>
            <div class="timeline-item"><h4>Offers &amp; deposit</h4><p>Feb&ndash;May: accept offer, pay tuition deposit, sign housing contract.</p></div>
            <div class="timeline-item"><h4>MVV via university</h4><p>May&ndash;Jul: university applies on your behalf; collect at consulate.</p></div>
            <div class="timeline-item"><h4>Pre-departure &amp; arrival</h4><p>Jul&ndash;Sep: BSN registration, banking, insurance, arrival.</p></div>
          </div>
        </div>
      </section>

      <section class="country-cta">
        <div class="container country-cta-grid">
          <div>
            <h2>Plan your Netherlands application with us</h2>
            <p>From English-taught shortlists to MVV via Studielink &mdash; we map a Netherlands-ready application.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Book a Netherlands call</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="{{ route('home') }}#destinations"><i data-lucide="compass"></i><span>Compare destinations</span></a>
            </div>
          </div>
          <div class="country-cta-card">
            <h3>What you&rsquo;ll get</h3>
            <p>A 30-minute counselor-led session, free of cost.</p>
            <ul>
              <li><i data-lucide="check"></i><span>UG vs PG strategy</span></li>
              <li><i data-lucide="check"></i><span>Tuition + Holland Scholarship plan</span></li>
              <li><i data-lucide="check"></i><span>Orientation year roadmap</span></li>
            </ul>
          </div>
        </div>
      </section>
    </main>
@endsection