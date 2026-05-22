@php
    $pageTitle = 'Study in Australia | OneDegreeAdvisory';
    $pageDescription = 'Study in Australia with OneDegreeAdvisory. Group of Eight universities, tuition, scholarships, Subclass 500 visa, TGV 485, PR pathway, FAQs.';
    $activeNav = 'destinations';
    $mainId = 'country-main';
@endphp

@extends('layouts.app')

@section('content')
<main id="country-main" class="country-main">
      <section class="country-hero country-hero--australia" id="top">
        <div class="container country-hero-grid">
          <div class="country-hero-copy">
            <a class="country-back" href="{{ route('home') }}#destinations"><i data-lucide="arrow-left"></i><span>All destinations</span></a>
            <span class="country-flag-lg"><img src="https://flagcdn.com/w160/au.png" alt="Australia flag"></span>
            <span class="eyebrow">Country guide</span>
            <h1>Study in <span class='gold-text'>Australia</span></h1>
            <p class="country-lede">Australia combines globally-ranked Group of Eight universities with a clear post-study work pathway of 2&ndash;6 years &mdash; ideal for students targeting career outcomes and long-term migration.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Plan your Australia application</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="#top-universities"><i data-lucide="building-2"></i><span>Top universities</span></a>
            </div>
          </div>
          <aside class="country-snapshot">
            <h2>At a glance</h2>
            <dl>
              <div><dt>Top universities</dt><dd>Group of 8 + 30+ globally ranked</dd></div>
              <div><dt>Main intakes</dt><dd>February, July</dd></div>
              <div><dt>Tuition / year</dt><dd><span data-money="30000" data-currency="AUD">AUD 30,000</span> &ndash; <span data-money="55000" data-currency="AUD">AUD 55,000</span></dd></div>
              <div><dt>Living cost / month</dt><dd><span data-money="1800" data-currency="AUD">AUD 1,800</span> &ndash; <span data-money="2600" data-currency="AUD">AUD 2,600</span></dd></div>
              <div><dt>Student visa</dt><dd>Subclass 500</dd></div>
              <div><dt>Post-study work</dt><dd>TGV 485 &middot; 2 to 6 years</dd></div>
            </dl>
          </aside>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Why Australia</span><h2>Globally ranked. Practical. Migration-friendly.</h2><p>Quality degrees, real work rights during and after study, and one of the clearest skilled migration pathways for graduates.</p></div>
          <div class="reasons-grid">
            <article class="reason-card"><span class="reason-icon"><i data-lucide="award"></i></span><h3>Group of Eight</h3><p>Eight research-intensive universities consistently in the global top 100.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="briefcase"></i></span><h3>Post-study work</h3><p>2&ndash;6 year Temporary Graduate visa (Subclass 485).</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="hourglass"></i></span><h3>Work while studying</h3><p>Up to 48 hours/fortnight during semester, unlimited in breaks.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="map"></i></span><h3>Skilled migration</h3><p>PR pathways via skilled occupation lists and state nominations.</p></article>
          </div>
        </div>
      </section>

      <section class="country-section full-bleed" aria-label="Country highlight banner">
        <div class="container">
          <div class="country-band">
            <img src="https://images.unsplash.com/photo-1523482580672-f109ba8cb9be?auto=format&fit=crop&w=2000&q=80" alt="">
            <div class="band-inner">
              <span class="eyebrow">Group of Eight + PR pathway</span>
              <h2>Top-100 universities. Real work rights. Migration on the table.</h2>
              <p>The Group of Eight (Go8) drives 70% of Australian research output &mdash; with a 2-to-6-year Temporary Graduate visa supporting your transition to skilled migration.</p>
              <div class="band-stats"><div class="band-stat"><strong>8</strong><span>Group of Eight</span></div><div class="band-stat"><strong>2-6 yr</strong><span>TGV 485</span></div><div class="band-stat"><strong>48 hr/2wk</strong><span>Work rights</span></div><div class="band-stat"><strong>PR</strong><span>Skilled pathway</span></div></div>
            </div>
          </div>
        </div>
      </section>

      <section id="top-universities" class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left"><span class="eyebrow">Universities</span><h2>Top universities in Australia</h2><p>A representative shortlist across research universities and specialist institutions.</p></div>
          <div class="university-grid">
            <article class="university-card"><span class="uni-rank">01</span><div class="uni-info"><h3>University of Melbourne</h3><p>QS top 15 globally &mdash; medicine, business, law, sciences.</p></div></article>
            <article class="university-card"><span class="uni-rank">02</span><div class="uni-info"><h3>Australian National University (ANU)</h3><p>Canberra-based; top in sciences, policy, and international relations.</p></div></article>
            <article class="university-card"><span class="uni-rank">03</span><div class="uni-info"><h3>University of Sydney</h3><p>Broad research strength across medicine, engineering, business.</p></div></article>
            <article class="university-card"><span class="uni-rank">04</span><div class="uni-info"><h3>UNSW Sydney</h3><p>Strong in engineering, business (AGSM MBA), and computing.</p></div></article>
            <article class="university-card"><span class="uni-rank">05</span><div class="uni-info"><h3>Monash University</h3><p>Pharmacy, engineering, business; one of the largest Go8.</p></div></article>
            <article class="university-card"><span class="uni-rank">06</span><div class="uni-info"><h3>University of Queensland (UQ)</h3><p>Top in biomedicine, environmental sciences, business.</p></div></article>
            <article class="university-card"><span class="uni-rank">07</span><div class="uni-info"><h3>University of Western Australia (UWA)</h3><p>Perth-based research-intensive Go8.</p></div></article>
            <article class="university-card"><span class="uni-rank">08</span><div class="uni-info"><h3>University of Adelaide</h3><p>Wine, agriculture, and engineering strengths.</p></div></article>
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
            <span class="program-chip"><i data-lucide="stethoscope"></i>Medicine &amp; Health</span>
            <span class="program-chip"><i data-lucide="flask-conical"></i>Biomedicine &amp; Pharma</span>
            <span class="program-chip"><i data-lucide="bar-chart-3"></i>Data Science &amp; Analytics</span>
            <span class="program-chip"><i data-lucide="leaf"></i>Environmental Sciences</span>
            <span class="program-chip"><i data-lucide="scale"></i>Law</span>
          </div>
        </div>
      </section>

      <section class="country-section paper">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Costs</span><h2>Tuition and cost of living</h2><p>Indicative ranges &mdash; final figures depend on program and city.</p></div>
          <div class="cost-grid">
            <div class="cost-card"><span class="cost-label">Undergraduate tuition</span><span class="cost-value"><span data-money="30000" data-currency="AUD">AUD 30,000</span> &ndash; <span data-money="50000" data-currency="AUD">AUD 50,000</span></span><span class="cost-note">Per year &middot; medicine higher.</span></div>
            <div class="cost-card"><span class="cost-label">Postgraduate tuition</span><span class="cost-value"><span data-money="35000" data-currency="AUD">AUD 35,000</span> &ndash; <span data-money="55000" data-currency="AUD">AUD 55,000</span></span><span class="cost-note">Per year &middot; MBA at the upper end.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Sydney/Melbourne</span><span class="cost-value"><span data-money="2000" data-currency="AUD">AUD 2,000</span> &ndash; <span data-money="2600" data-currency="AUD">AUD 2,600</span></span><span class="cost-note">Per month &middot; rents lead the budget.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Other cities</span><span class="cost-value"><span data-money="1500" data-currency="AUD">AUD 1,500</span> &ndash; <span data-money="2000" data-currency="AUD">AUD 2,000</span></span><span class="cost-note">Per month &middot; Adelaide, Brisbane, Perth.</span></div>
          </div>
        </div>
      </section>

      <section class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Funding &amp; visa</span>
            <h2>Scholarships, funding &amp; the Australia student visa</h2>
            <p>Two parallel tracks &mdash; how to fund the degree, and how to secure your stay.</p>
          </div>
          <div class="split-info">
            <div class="info-card">
              <h3>Scholarships &amp; funding</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Australia Awards Scholarships (fully-funded; select countries).</span></li>
                <li><i data-lucide="check"></i><span>Destination Australia Scholarships for regional study.</span></li>
                <li><i data-lucide="check"></i><span>University-specific entrance and merit scholarships (e.g. UoM International Scholarship).</span></li>
                <li><i data-lucide="check"></i><span>Research Training Program (RTP) for PhD candidates.</span></li>
                <li><i data-lucide="check"></i><span>Education loans through major Indian and international lenders.</span></li>
              </ul>
            </div>
            <div class="info-card">
              <h3>Student visa snapshot</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Visa name: <strong>Student visa (Subclass 500)</strong> via the Department of Home Affairs.</span></li>
                <li><i data-lucide="check"></i><span>Requires CoE (Confirmation of Enrolment) from an approved provider.</span></li>
                <li><i data-lucide="check"></i><span>Proof of funds: roughly <span data-money="29710" data-currency="AUD">AUD 29,710</span>/year + tuition + travel.</span></li>
                <li><i data-lucide="check"></i><span>Standard processing: 4&ndash;8 weeks (varies by sector and country).</span></li>
                <li><i data-lucide="check"></i><span>Work allowance: 48 hours/fortnight during term; unlimited during breaks.</span></li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left"><span class="eyebrow">Timeline</span><h2>Application timeline</h2><p>A typical roadmap from profile review to arrival.</p></div>
          <div class="timeline-list">
            <div class="timeline-item"><h4>Profile review &amp; shortlist</h4><p>12&ndash;14 months out: program direction, GTE narrative, budget.</p></div>
            <div class="timeline-item"><h4>Tests &amp; documents</h4><p>8&ndash;12 months out: IELTS/PTE; GMAT/GRE if program requires.</p></div>
            <div class="timeline-item"><h4>Applications</h4><p>Feb&ndash;Jul: most postgraduate programs.</p></div>
            <div class="timeline-item"><h4>Offers &amp; CoE</h4><p>Apr&ndash;Sep: accept offer, pay deposit, receive CoE.</p></div>
            <div class="timeline-item"><h4>Subclass 500 visa</h4><p>Aug&ndash;Dec: submit application, OSHC, biometrics.</p></div>
            <div class="timeline-item"><h4>Pre-departure &amp; arrival</h4><p>Dec&ndash;Feb: housing, banking, insurance, flights, arrival.</p></div>
          </div>
        </div>
      </section>

      <section class="country-cta">
        <div class="container country-cta-grid">
          <div>
            <h2>Plan your Australia application with us</h2>
            <p>From Go8 shortlists to GTE-ready Subclass 500 submissions &mdash; we map an Australia-ready application.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Book an Australia call</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="{{ route('home') }}#destinations"><i data-lucide="compass"></i><span>Compare destinations</span></a>
            </div>
          </div>
          <div class="country-cta-card">
            <h3>What you&rsquo;ll get</h3>
            <p>A 30-minute counselor-led session, free of cost.</p>
            <ul>
              <li><i data-lucide="check"></i><span>Go8 vs regional fit</span></li>
              <li><i data-lucide="check"></i><span>GTE narrative review</span></li>
              <li><i data-lucide="check"></i><span>TGV 485 + PR roadmap</span></li>
            </ul>
          </div>
        </div>
      </section>
    </main>
@endsection