@php
    $pageTitle = 'Study in Poland | One Degree Advisory';
    $pageDescription = 'Study in Poland with One Degree Advisory. Affordable EU degrees, English-medium MD, top universities, scholarships, visa, costs, FAQs.';
    $activeNav = 'destinations';
    $mainId = 'country-main';
@endphp

@extends('layouts.app')

@section('content')
<main id="country-main" class="country-main">
      <section class="country-hero country-hero--poland" id="top">
        <div class="container country-hero-grid">
          <div class="country-hero-copy">
            <a class="country-back" href="{{ route('home') }}#destinations"><i data-lucide="arrow-left"></i><span>All destinations</span></a>
            <span class="country-flag-lg"><img src="https://flagcdn.com/w160/pl.png" alt="Poland flag"></span>
            <span class="eyebrow">Country guide</span>
            <h1>Study in <span class='gold-text'>Poland</span></h1>
            <p class="country-lede">Poland is one of the most affordable EU destinations &mdash; with English-taught medicine, engineering, and business programs at globally-recognised universities, plus a 9-month post-study stay-back.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Plan your Poland application</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="#top-universities"><i data-lucide="building-2"></i><span>Top universities</span></a>
            </div>
          </div>
          <aside class="country-snapshot">
            <h2>At a glance</h2>
            <dl>
              <div><dt>Top universities</dt><dd>20+ globally ranked</dd></div>
              <div><dt>Main intakes</dt><dd>October, February</dd></div>
              <div><dt>Tuition / year</dt><dd><span>&euro;2,500</span> &ndash; <span>&euro;15,000</span></dd></div>
              <div><dt>Living cost / month</dt><dd><span>&euro;500</span> &ndash; <span>&euro;800</span></dd></div>
              <div><dt>Student visa</dt><dd>Type D long-stay visa</dd></div>
              <div><dt>Post-study work</dt><dd>Stay-back &middot; 9 months</dd></div>
            </dl>
          </aside>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Why Poland</span><h2>EU degrees at a fraction of the cost.</h2><p>Affordable tuition, low living costs, English-taught programs, and an EU work pathway.</p></div>
          <div class="reasons-grid">
            <article class="reason-card"><span class="reason-icon"><i data-lucide="banknote"></i></span><h3>Lowest EU tuition</h3><p>Public universities from <span>&euro;2,000</span>/year; private schools from <span>&euro;3,000</span>.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="languages"></i></span><h3>English-taught programs</h3><p>300+ programs taught in English across medicine, business, and engineering.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="stethoscope"></i></span><h3>MD English-medium</h3><p>6-year MD programs at Jagiellonian, MUW, and MU&Lacute;&oacute;d&zacute; for international students.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="globe"></i></span><h3>EU Schengen access</h3><p>Travel and work across the EU after graduation.</p></article>
          </div>
        </div>
      </section>

      <section class="country-section full-bleed" aria-label="Country highlight banner">
        <div class="container">
          <div class="country-band">
            <img src="https://images.unsplash.com/photo-1539020140153-e479b8c22e70?auto=format&fit=crop&w=2000&q=80" alt="">
            <div class="band-inner">
              <span class="eyebrow">Affordable. English-taught. EU.</span>
              <h2>Top-ranked EU degrees &mdash; without UK or German prices.</h2>
              <p>Polish public universities charge <span>&euro;2,000</span>&ndash;<span>&euro;5,000</span>/year for international students &mdash; with living costs half of Berlin or Paris.</p>
              <div class="band-stats"><div class="band-stat"><strong>20+</strong><span>Globally ranked</span></div><div class="band-stat"><strong>300+</strong><span>English programs</span></div><div class="band-stat"><strong>9 mo</strong><span>Stay-back</span></div><div class="band-stat"><strong>EU</strong><span>Member state</span></div></div>
            </div>
          </div>
        </div>
      </section>

      <section id="top-universities" class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left"><span class="eyebrow">Universities</span><h2>Top universities in Poland</h2><p>A representative shortlist across research universities and specialist institutions.</p></div>
          <div class="university-grid">
            <article class="university-card"><span class="uni-rank">01</span><div class="uni-info"><h3>University of Warsaw</h3><p>Poland&rsquo;s flagship &mdash; social sciences, humanities, computing.</p></div></article>
            <article class="university-card"><span class="uni-rank">02</span><div class="uni-info"><h3>Jagiellonian University (Krakow)</h3><p>Oldest university in Poland &mdash; medicine, law, sciences.</p></div></article>
            <article class="university-card"><span class="uni-rank">03</span><div class="uni-info"><h3>Warsaw University of Technology</h3><p>Top engineering, computing, and architecture.</p></div></article>
            <article class="university-card"><span class="uni-rank">04</span><div class="uni-info"><h3>AGH University of Krakow</h3><p>Mining, metallurgy, and applied sciences powerhouse.</p></div></article>
            <article class="university-card"><span class="uni-rank">05</span><div class="uni-info"><h3>SGH Warsaw School of Economics</h3><p>Top Polish business school with English MBA tracks.</p></div></article>
            <article class="university-card"><span class="uni-rank">06</span><div class="uni-info"><h3>Medical University of Warsaw</h3><p>6-year English-medium MD and dentistry programs.</p></div></article>
            <article class="university-card"><span class="uni-rank">07</span><div class="uni-info"><h3>University of Wroc&lstrok;aw</h3><p>Strong sciences, philology, and management.</p></div></article>
            <article class="university-card"><span class="uni-rank">08</span><div class="uni-info"><h3>Adam Mickiewicz University (Pozna&nacute;)</h3><p>Broad-based research university.</p></div></article>
          </div>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Programs</span><h2>Popular programs</h2><p>The most-applied tracks for international students.</p></div>
          <div class="programs-grid">
            <span class="program-chip"><i data-lucide="stethoscope"></i>Medicine &amp; Dentistry</span>
            <span class="program-chip"><i data-lucide="cog"></i>Engineering</span>
            <span class="program-chip"><i data-lucide="cpu"></i>Computer Science</span>
            <span class="program-chip"><i data-lucide="briefcase-business"></i>Business &amp; Management</span>
            <span class="program-chip"><i data-lucide="flask-conical"></i>Biotech &amp; Life Sciences</span>
            <span class="program-chip"><i data-lucide="scale"></i>Law &amp; Politics</span>
            <span class="program-chip"><i data-lucide="languages"></i>Linguistics &amp; Translation</span>
            <span class="program-chip"><i data-lucide="palette"></i>Architecture &amp; Design</span>
          </div>
        </div>
      </section>

      <section class="country-section paper">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Costs</span><h2>Tuition and cost of living</h2><p>Indicative ranges &mdash; final figures depend on program and city.</p></div>
          <div class="cost-grid">
            <div class="cost-card"><span class="cost-label">Public university tuition</span><span class="cost-value"><span>&euro;2,000</span> &ndash; <span>&euro;5,000</span></span><span class="cost-note">Per year &middot; for international students.</span></div>
            <div class="cost-card"><span class="cost-label">Private &amp; medical tuition</span><span class="cost-value"><span>&euro;6,000</span> &ndash; <span>&euro;15,000</span></span><span class="cost-note">Per year &middot; MD English-medium at the upper end.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Warsaw/Krakow</span><span class="cost-value"><span>&euro;700</span> &ndash; <span>&euro;900</span></span><span class="cost-note">Per month &middot; major-city rents lead the budget.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Other cities</span><span class="cost-value"><span>&euro;500</span> &ndash; <span>&euro;700</span></span><span class="cost-note">Per month &middot; Wroc&lstrok;aw, Pozna&nacute;, Gda&nacute;sk.</span></div>
          </div>
        </div>
      </section>

      <section class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Funding &amp; visa</span>
            <h2>Scholarships, funding &amp; the Poland student visa</h2>
            <p>Two parallel tracks &mdash; how to fund the degree, and how to secure your stay.</p>
          </div>
          <div class="split-info">
            <div class="info-card">
              <h3>Scholarships &amp; funding</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Polish NAWA Government Scholarships for international students.</span></li>
                <li><i data-lucide="check"></i><span>Banach Scholarship Programme for developing countries.</span></li>
                <li><i data-lucide="check"></i><span>Stefan Banach &amp; Lukasiewicz Programmes for specific regions.</span></li>
                <li><i data-lucide="check"></i><span>University-specific tuition reductions for high-merit applicants.</span></li>
                <li><i data-lucide="check"></i><span>Education loans through major Indian and international lenders.</span></li>
              </ul>
            </div>
            <div class="info-card">
              <h3>Student visa snapshot</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Visa name: <strong>National (Type D) long-stay visa</strong> via the Polish consulate.</span></li>
                <li><i data-lucide="check"></i><span>Requires admission letter and proof of paid tuition.</span></li>
                <li><i data-lucide="check"></i><span>Proof of funds: roughly <span>&euro;200</span>&ndash;<span>&euro;300</span>/month plus return airfare.</span></li>
                <li><i data-lucide="check"></i><span>Standard processing: 15&ndash;30 working days; biometrics at VFS.</span></li>
                <li><i data-lucide="check"></i><span>Work allowance: full-time in term and holidays (with proper registration).</span></li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left"><span class="eyebrow">Timeline</span><h2>Application timeline</h2><p>A typical roadmap from profile review to arrival.</p></div>
          <div class="timeline-list">
            <div class="timeline-item"><h4>Profile review &amp; shortlist</h4><p>10&ndash;12 months out: identify English-medium programs.</p></div>
            <div class="timeline-item"><h4>Tests &amp; documents</h4><p>6&ndash;10 months out: IELTS/TOEFL or program-specific test.</p></div>
            <div class="timeline-item"><h4>Applications</h4><p>Feb&ndash;Jul: most master&rsquo;s and MD English programs.</p></div>
            <div class="timeline-item"><h4>Offers &amp; deposit</h4><p>Apr&ndash;Aug: accept offer, pay tuition, finalise loan.</p></div>
            <div class="timeline-item"><h4>Type D visa</h4><p>Jul&ndash;Sep: submit at consulate, biometrics, processing.</p></div>
            <div class="timeline-item"><h4>Pre-departure &amp; arrival</h4><p>Sep&ndash;Oct: housing, PESEL, insurance, arrival.</p></div>
          </div>
        </div>
      </section>

      <section class="country-cta">
        <div class="container country-cta-grid">
          <div>
            <h2>Plan your Poland application with us</h2>
            <p>From English-medium MD shortlists to Type D visa &mdash; One Degree maps an Poland-ready application.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Book a Poland call</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="{{ route('home') }}#destinations"><i data-lucide="compass"></i><span>Compare destinations</span></a>
            </div>
          </div>
          <div class="country-cta-card">
            <h3>What you&rsquo;ll get</h3>
            <p>A 30-minute counselor-led session, free of cost.</p>
            <ul>
              <li><i data-lucide="check"></i><span>MD vs Engineering tracks</span></li>
              <li><i data-lucide="check"></i><span>Public vs private fit</span></li>
              <li><i data-lucide="check"></i><span>Visa &amp; PESEL roadmap</span></li>
            </ul>
          </div>
        </div>
      </section>
    </main>
@endsection