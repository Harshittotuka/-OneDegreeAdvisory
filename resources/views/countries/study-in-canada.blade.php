@php
    $pageTitle = 'Study in Canada | OneDegreeAdvisory';
    $pageDescription = 'Study in Canada with OneDegreeAdvisory. Top universities, tuition, Study Permit, co-op pathways, and PGWP work options up to 3 years.';
    $activeNav = 'destinations';
    $mainId = 'country-main';
@endphp

@extends('layouts.app')

@section('content')
<main id="country-main" class="country-main">
      <section class="country-hero country-hero--canada" id="top">
        <div class="container country-hero-grid">
          <div class="country-hero-copy">
            <a class="country-back" href="{{ route('home') }}#destinations"><i data-lucide="arrow-left"></i><span>All destinations</span></a>
            <span class="country-flag-lg"><img src="https://flagcdn.com/w160/ca.png" alt="Canada flag"></span>
            <span class="eyebrow">Country guide</span>
            <h1>Study in <span class="gold-text">Canada</span></h1>
            <p class="country-lede">Canada pairs academic quality with co-op pathways and clear settlement options &mdash; ideal for students who want safe, multicultural campuses with long-term career and PR potential.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Plan your Canada application</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="#top-universities"><i data-lucide="building-2"></i><span>Top universities</span></a>
            </div>
          </div>
          <aside class="country-snapshot">
            <h2>At a glance</h2>
            <dl>
              <div><dt>Top universities</dt><dd>30+ globally ranked</dd></div>
              <div><dt>Main intakes</dt><dd>Fall, Winter, Summer</dd></div>
              <div><dt>Tuition / year</dt><dd><span data-money="15000" data-currency="CAD">CAD 15,000</span> &ndash; <span data-money="50000" data-currency="CAD">50,000</span></dd></div>
              <div><dt>Living cost / month</dt><dd><span data-money="1000" data-currency="CAD">CAD 1,000</span> &ndash; <span data-money="2000" data-currency="CAD">2,000</span></dd></div>
              <div><dt>Student visa</dt><dd>Study Permit</dd></div>
              <div><dt>Post-study work</dt><dd>PGWP &middot; up to 3 years</dd></div>
            </dl>
          </aside>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Why Canada</span><h2>Quality, co-ops, settlement</h2><p>Career-aligned learning with one of the clearest PR pathways for international graduates.</p></div>
          <div class="reasons-grid">
            <article class="reason-card"><span class="reason-icon"><i data-lucide="award"></i></span><h3>Globally ranked universities</h3><p>U15 research universities and strong polytechnics deliver employer-respected credentials.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="briefcase"></i></span><h3>Co-op &amp; internships</h3><p>Built-in paid work terms at many programs &mdash; especially at Waterloo, SFU, and Toronto Metropolitan.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="map"></i></span><h3>PGWP &amp; PR</h3><p>Up to 3-year Post-Graduation Work Permit, with Express Entry and provincial nomination pathways.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="users"></i></span><h3>Safe &amp; multicultural</h3><p>Welcoming cities like Toronto, Vancouver, Montreal, and Calgary with strong Indian diasporas.</p></article>
          </div>
        </div>
      </section>

      <section id="top-universities" class="country-section alt">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Universities</span><h2>Top universities in Canada</h2><p>A representative shortlist across research universities and applied institutions.</p></div>
          <div class="university-grid">
            <article class="university-card"><span class="uni-rank">01</span><div class="uni-info"><h3>University of Toronto</h3><p>Canada&rsquo;s largest research university with depth across business, engineering, and sciences.</p></div></article>
            <article class="university-card"><span class="uni-rank">02</span><div class="uni-info"><h3>McGill University</h3><p>Montreal-based with strengths in medicine, law, management, and the humanities.</p></div></article>
            <article class="university-card"><span class="uni-rank">03</span><div class="uni-info"><h3>University of British Columbia (UBC)</h3><p>Vancouver and Okanagan campuses &mdash; strong in business, engineering, and forestry.</p></div></article>
            <article class="university-card"><span class="uni-rank">04</span><div class="uni-info"><h3>University of Waterloo</h3><p>Co-op pioneer with world-class computer science, engineering, and mathematics programs.</p></div></article>
            <article class="university-card"><span class="uni-rank">05</span><div class="uni-info"><h3>McMaster University</h3><p>Research-intensive Hamilton university with leading health sciences and engineering tracks.</p></div></article>
            <article class="university-card"><span class="uni-rank">06</span><div class="uni-info"><h3>University of Alberta</h3><p>Edmonton-based research powerhouse in energy, engineering, and life sciences.</p></div></article>
            <article class="university-card"><span class="uni-rank">07</span><div class="uni-info"><h3>Queen&rsquo;s University</h3><p>Kingston, Ontario &mdash; respected for business (Smith), engineering, and law.</p></div></article>
            <article class="university-card"><span class="uni-rank">08</span><div class="uni-info"><h3>Western University</h3><p>London, Ontario &mdash; Ivey Business School, medicine, and engineering.</p></div></article>
          </div>
        </div>
      </section>
      <section class="country-section full-bleed" aria-label="Country highlight banner">
        <div class="container">
          <div class="country-band">
            <img src="https://images.unsplash.com/photo-1517935706615-2717063c2225?auto=format&fit=crop&w=2000&q=80" alt="">
            <div class="band-inner">
              <span class="eyebrow">Co-op &amp; PR pathways</span>
              <h2>Earn, learn, then settle &mdash; the Canadian model.</h2>
              <p>U15 research depth, paid co-op terms, and a 3-year PGWP that opens Express Entry for international graduates.</p>
              <div class="band-stats"><div class="band-stat"><strong>30+</strong><span>Globally ranked</span></div><div class="band-stat"><strong>3 yrs</strong><span>PGWP</span></div><div class="band-stat"><strong>U15</strong><span>Research</span></div><div class="band-stat"><strong>24 hrs</strong><span>Off-campus work</span></div></div>
            </div>
          </div>
        </div>
      </section>


      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Programs</span><h2>Popular programs</h2><p>Sectors with strong co-op options and PR-aligned employment outcomes.</p></div>
          <div class="programs-grid">
            <span class="program-chip"><i data-lucide="cpu"></i>Computer Science &amp; AI</span>
            <span class="program-chip"><i data-lucide="cog"></i>Engineering</span>
            <span class="program-chip"><i data-lucide="briefcase-business"></i>Business &amp; Management</span>
            <span class="program-chip"><i data-lucide="stethoscope"></i>Health Sciences &amp; Nursing</span>
            <span class="program-chip"><i data-lucide="bar-chart-3"></i>Data Science &amp; Analytics</span>
            <span class="program-chip"><i data-lucide="line-chart"></i>Finance &amp; Accounting</span>
            <span class="program-chip"><i data-lucide="leaf"></i>Environmental &amp; Energy</span>
            <span class="program-chip"><i data-lucide="hotel"></i>Hospitality &amp; Tourism</span>
          </div>
        </div>
      </section>

      <section class="country-section alt">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Costs</span><h2>Tuition and cost of living</h2><p>CAD ranges &mdash; final costs vary by province, campus, and program.</p></div>
          <div class="cost-grid">
            <div class="cost-card"><span class="cost-label">Undergraduate tuition</span><span class="cost-value"><span data-money="15000" data-currency="CAD">CAD 15,000</span>&ndash;<span data-money="40000" data-currency="CAD">40,000</span></span><span class="cost-note">Per year &middot; engineering and business at upper end.</span></div>
            <div class="cost-card"><span class="cost-label">Postgraduate tuition</span><span class="cost-value"><span data-money="18000" data-currency="CAD">CAD 18,000</span>&ndash;<span data-money="50000" data-currency="CAD">50,000</span></span><span class="cost-note">Per year &middot; MBA highest; many master&rsquo;s under <span data-money="30000" data-currency="CAD" data-money-hint="k">CAD 30k</span>.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Toronto/Vancouver</span><span class="cost-value"><span data-money="1400" data-currency="CAD">CAD 1,400</span>&ndash;<span data-money="2000" data-currency="CAD">2,000</span></span><span class="cost-note">Per month &middot; accommodation, food, transport.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Other cities</span><span class="cost-value"><span data-money="1000" data-currency="CAD">CAD 1,000</span>&ndash;<span data-money="1400" data-currency="CAD">1,400</span></span><span class="cost-note">Per month &middot; Montreal, Calgary, Ottawa, smaller cities.</span></div>
          </div>
        </div>
      </section>

      <section class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Funding &amp; visa</span>
            <h2>Scholarships, funding &amp; the Canada visa</h2>
            <p>Two parallel tracks &mdash; how to fund the degree, and how to secure your stay.</p>
          </div>
          <div class="split-info">
            <div class="info-card">
              <h3>Scholarships &amp; funding</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Vanier Canada Graduate Scholarships for doctoral students.</span></li>
                <li><i data-lucide="check"></i><span>Lester B. Pearson International Scholarship at University of Toronto.</span></li>
                <li><i data-lucide="check"></i><span>Trudeau Foundation Doctoral Scholarships.</span></li>
                <li><i data-lucide="check"></i><span>University-specific entrance scholarships and graduate teaching assistantships.</span></li>
                <li><i data-lucide="check"></i><span>GIC + education loan combinations through major Indian lenders.</span></li>
              </ul>
            </div>
            <div class="info-card">
              <h3>Student visa snapshot</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Visa name: <strong>Study Permit</strong> via IRCC.</span></li>
                <li><i data-lucide="check"></i><span>Requires a Letter of Acceptance from a Designated Learning Institution (DLI).</span></li>
                <li><i data-lucide="check"></i><span>Proof of funds: typically <span data-money="20635" data-currency="CAD">CAD 20,635</span>/year + tuition (2024 update).</span></li>
                <li><i data-lucide="check"></i><span>GIC (Guaranteed Investment Certificate) of <span data-money="20635" data-currency="CAD">CAD 20,635</span> for SDS pathway.</span></li>
                <li><i data-lucide="check"></i><span>Work allowance: up to 24 hours/week off-campus during term.</span></li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section class="country-section alt">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Timeline</span><h2>Application timeline</h2><p>A typical 10&ndash;14 month roadmap for a Fall intake.</p></div>
          <div class="timeline-list">
            <div class="timeline-item"><h4>Profile review &amp; shortlist</h4><p>12&ndash;14 months out: program direction, budget, target provinces.</p></div>
            <div class="timeline-item"><h4>Tests &amp; documents</h4><p>8&ndash;12 months out: IELTS/PTE; GRE/GMAT only if program requires.</p></div>
            <div class="timeline-item"><h4>Applications</h4><p>October&ndash;February: most graduate programs; UG via OUAC for Ontario.</p></div>
            <div class="timeline-item"><h4>Offers &amp; GIC</h4><p>January&ndash;April: confirm offer, pay deposit, open GIC, finalise loan.</p></div>
            <div class="timeline-item"><h4>Study Permit</h4><p>April&ndash;July: biometrics, medical, application via SDS or non-SDS.</p></div>
            <div class="timeline-item"><h4>Pre-departure &amp; arrival</h4><p>July&ndash;September: housing, banking, insurance, flight, campus arrival.</p></div>
          </div>
        </div>
      </section>

      <section class="country-cta">
        <div class="container country-cta-grid">
          <div>
            <h2>Plan your Canada application with us</h2>
            <p>From DLI selection to PGWP planning, OneDegree maps a Canada-ready application.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Book a Canada call</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="{{ route('home') }}#destinations"><i data-lucide="compass"></i><span>Compare destinations</span></a>
            </div>
          </div>
          <div class="country-cta-card">
            <h3>What you&rsquo;ll get</h3><p>A 30-minute counselor-led session, free of cost.</p>
            <ul>
              <li><i data-lucide="check"></i><span>Province &amp; co-op fit</span></li>
              <li><i data-lucide="check"></i><span>GIC &amp; SDS readiness</span></li>
              <li><i data-lucide="check"></i><span>PGWP &amp; PR roadmap</span></li>
            </ul>
          </div>
        </div>
      </section>
    </main>
@endsection