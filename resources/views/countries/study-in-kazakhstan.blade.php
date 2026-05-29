@php
    $pageTitle = 'Study in Kazakhstan | One Degree Advisory';
    $pageDescription = 'Study in Kazakhstan with One Degree Advisory. MCI-recognised English-medium MD, affordable tuition, top universities, M3 visa, FAQs.';
    $activeNav = 'destinations';
    $mainId = 'country-main';
@endphp

@extends('layouts.app')

@section('content')
<main id="country-main" class="country-main">
      <section class="country-hero country-hero--kazakhstan" id="top">
        <div class="container country-hero-grid">
          <div class="country-hero-copy">
            <a class="country-back" href="{{ route('home') }}#destinations"><i data-lucide="arrow-left"></i><span>All destinations</span></a>
            <span class="country-flag-lg"><img src="https://flagcdn.com/w160/kz.png" alt="Kazakhstan flag"></span>
            <span class="eyebrow">Country guide</span>
            <h1>Study in <span class='gold-text'>Kazakhstan</span></h1>
            <p class="country-lede">Kazakhstan is Central Asia&rsquo;s rising study destination &mdash; with English-medium programs at Nazarbayev University, affordable medical degrees, and a growing tech and energy economy.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Plan your Kazakhstan application</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="#top-universities"><i data-lucide="building-2"></i><span>Top universities</span></a>
            </div>
          </div>
          <aside class="country-snapshot">
            <h2>At a glance</h2>
            <dl>
              <div><dt>Top universities</dt><dd>10+ internationally accredited</dd></div>
              <div><dt>Main intakes</dt><dd>September, February</dd></div>
              <div><dt>Tuition / year</dt><dd><span>$2,500</span> &ndash; <span>$8,000</span></dd></div>
              <div><dt>Living cost / month</dt><dd><span>$300</span> &ndash; <span>$600</span></dd></div>
              <div><dt>Student visa</dt><dd>Educational visa &middot; M3</dd></div>
              <div><dt>Post-study work</dt><dd>Work permit pathway</dd></div>
            </dl>
          </aside>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Why Kazakhstan</span><h2>Affordable degrees. English medium. Strategic location.</h2><p>Quality MD and engineering programs at a fraction of Western prices, with strong industry ties.</p></div>
          <div class="reasons-grid">
            <article class="reason-card"><span class="reason-icon"><i data-lucide="banknote"></i></span><h3>Affordable tuition</h3><p>Most international programs sit at <span>$3,000</span>&ndash;<span>$6,000</span>/year.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="stethoscope"></i></span><h3>Recognised MD programs</h3><p>MCI/NMC-recognised medical degrees in English.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="languages"></i></span><h3>English-medium tracks</h3><p>Major universities offer English programs in business, engineering, and IT.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="globe"></i></span><h3>Gateway hub</h3><p>Central Asia&rsquo;s economic hub &mdash; close to India, China, Europe.</p></article>
          </div>
        </div>
      </section>

      <section class="country-section full-bleed" aria-label="Country highlight banner">
        <div class="container">
          <div class="country-band">
            <img src="https://images.unsplash.com/photo-1467269204594-9661b134dd2b?auto=format&fit=crop&w=2000&q=80" alt="">
            <div class="band-inner">
              <span class="eyebrow">Central Asia&rsquo;s study hub</span>
              <h2>Recognised MD. English-medium. <span>$5k</span>/year tuition.</h2>
              <p>Kazakhstan&rsquo;s English-medium MD programs are NMC/MCI-recognised and cost a fraction of UK or US equivalents &mdash; making it a strong value pick for medicine.</p>
              <div class="band-stats"><div class="band-stat"><strong><span>$5k</span>/yr</strong><span>Median MD tuition</span></div><div class="band-stat"><strong>MCI</strong><span>Recognised</span></div><div class="band-stat"><strong>EN</strong><span>Medium</span></div><div class="band-stat"><strong>6 yrs</strong><span>MD duration</span></div></div>
            </div>
          </div>
        </div>
      </section>

      <section id="top-universities" class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left"><span class="eyebrow">Universities</span><h2>Top universities in Kazakhstan</h2><p>A representative shortlist across research universities and specialist institutions.</p></div>
          <div class="university-grid">
            <article class="university-card"><span class="uni-rank">01</span><div class="uni-info"><h3>Nazarbayev University</h3><p>English-medium research university in Astana &mdash; sciences, engineering, business.</p></div></article>
            <article class="university-card"><span class="uni-rank">02</span><div class="uni-info"><h3>Kazakh National Medical University (KazNMU)</h3><p>Top medical university in Almaty &mdash; English MD program.</p></div></article>
            <article class="university-card"><span class="uni-rank">03</span><div class="uni-info"><h3>Astana Medical University</h3><p>Recognised English-medium MD program for international students.</p></div></article>
            <article class="university-card"><span class="uni-rank">04</span><div class="uni-info"><h3>Al-Farabi Kazakh National University</h3><p>Almaty&rsquo;s flagship &mdash; sciences, languages, economics.</p></div></article>
            <article class="university-card"><span class="uni-rank">05</span><div class="uni-info"><h3>Karaganda Medical University</h3><p>Established English-medium MD &amp; dentistry programs.</p></div></article>
            <article class="university-card"><span class="uni-rank">06</span><div class="uni-info"><h3>Kazakh-British Technical University</h3><p>Energy, IT, and business &mdash; British curriculum partnership.</p></div></article>
            <article class="university-card"><span class="uni-rank">07</span><div class="uni-info"><h3>Satbayev University</h3><p>Polytechnic with engineering and mining tracks.</p></div></article>
            <article class="university-card"><span class="uni-rank">08</span><div class="uni-info"><h3>KIMEP University</h3><p>Almaty-based; English-medium business and law.</p></div></article>
          </div>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Programs</span><h2>Popular programs</h2><p>The most-applied tracks for international students.</p></div>
          <div class="programs-grid">
            <span class="program-chip"><i data-lucide="stethoscope"></i>MD &amp; Dentistry</span>
            <span class="program-chip"><i data-lucide="cog"></i>Engineering</span>
            <span class="program-chip"><i data-lucide="cpu"></i>Computer Science &amp; IT</span>
            <span class="program-chip"><i data-lucide="briefcase-business"></i>Business &amp; Economics</span>
            <span class="program-chip"><i data-lucide="globe"></i>International Relations</span>
            <span class="program-chip"><i data-lucide="zap"></i>Energy &amp; Mining</span>
            <span class="program-chip"><i data-lucide="scale"></i>Law</span>
            <span class="program-chip"><i data-lucide="flask-conical"></i>Pharmacy &amp; Sciences</span>
          </div>
        </div>
      </section>

      <section class="country-section paper">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Costs</span><h2>Tuition and cost of living</h2><p>Indicative ranges &mdash; final figures depend on program and city.</p></div>
          <div class="cost-grid">
            <div class="cost-card"><span class="cost-label">MD English-medium</span><span class="cost-value"><span>$3,500</span> &ndash; <span>$7,000</span></span><span class="cost-note">Per year &middot; 6-year program.</span></div>
            <div class="cost-card"><span class="cost-label">Engineering / Business</span><span class="cost-value"><span>$2,500</span> &ndash; <span>$6,000</span></span><span class="cost-note">Per year &middot; English tracks at top universities.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Astana/Almaty</span><span class="cost-value"><span>$400</span> &ndash; <span>$600</span></span><span class="cost-note">Per month &middot; including hostel &amp; food.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Other cities</span><span class="cost-value"><span>$300</span> &ndash; <span>$450</span></span><span class="cost-note">Per month &middot; Karaganda, Semey, Shymkent.</span></div>
          </div>
        </div>
      </section>

      <section class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Funding &amp; visa</span>
            <h2>Scholarships, funding &amp; the Kazakhstan student visa</h2>
            <p>Two parallel tracks &mdash; how to fund the degree, and how to secure your stay.</p>
          </div>
          <div class="split-info">
            <div class="info-card">
              <h3>Scholarships &amp; funding</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Bolashak International Scholarship (limited; primarily citizens, some partner countries).</span></li>
                <li><i data-lucide="check"></i><span>Nazarbayev University full-tuition scholarships for high-merit applicants.</span></li>
                <li><i data-lucide="check"></i><span>University-specific tuition reductions for top NEET / SAT scorers.</span></li>
                <li><i data-lucide="check"></i><span>MCI-recognition list maintained by the Indian Ministry of Health.</span></li>
                <li><i data-lucide="check"></i><span>Education loans through major Indian and international lenders.</span></li>
              </ul>
            </div>
            <div class="info-card">
              <h3>Student visa snapshot</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Visa name: <strong>M3 Educational visa</strong> via the Kazakh consulate.</span></li>
                <li><i data-lucide="check"></i><span>Requires admission letter and university invitation letter.</span></li>
                <li><i data-lucide="check"></i><span>Proof of funds: typically <span>$2,500</span>&ndash;<span>$4,000</span> for the first year.</span></li>
                <li><i data-lucide="check"></i><span>Standard processing: 7&ndash;30 working days.</span></li>
                <li><i data-lucide="check"></i><span>Work allowance: limited &mdash; primarily on-campus and internships.</span></li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left"><span class="eyebrow">Timeline</span><h2>Application timeline</h2><p>A typical roadmap from profile review to arrival.</p></div>
          <div class="timeline-list">
            <div class="timeline-item"><h4>Profile review &amp; shortlist</h4><p>10&ndash;12 months out: NEET prep + MD university selection.</p></div>
            <div class="timeline-item"><h4>Documents &amp; tests</h4><p>6&ndash;10 months out: NEET (for MD), IELTS for some programs.</p></div>
            <div class="timeline-item"><h4>Applications</h4><p>Apr&ndash;Aug: most universities accept direct applications.</p></div>
            <div class="timeline-item"><h4>Offers &amp; invitation letter</h4><p>Jun&ndash;Aug: receive admission letter and invitation.</p></div>
            <div class="timeline-item"><h4>M3 visa</h4><p>Aug&ndash;Sep: submit at consulate, await processing.</p></div>
            <div class="timeline-item"><h4>Pre-departure &amp; arrival</h4><p>Sep: flights, hostel allocation, university registration.</p></div>
          </div>
        </div>
      </section>

      <section class="country-cta">
        <div class="container country-cta-grid">
          <div>
            <h2>Plan your Kazakhstan application with us</h2>
            <p>From NEET-aligned MD shortlists to M3 visa &mdash; One Degree maps a Kazakhstan-ready application.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Book a Kazakhstan call</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="{{ route('home') }}#destinations"><i data-lucide="compass"></i><span>Compare destinations</span></a>
            </div>
          </div>
          <div class="country-cta-card">
            <h3>What you&rsquo;ll get</h3>
            <p>A 30-minute counselor-led session, free of cost.</p>
            <ul>
              <li><i data-lucide="check"></i><span>MD MCI-recognition check</span></li>
              <li><i data-lucide="check"></i><span>NEET-aligned shortlist</span></li>
              <li><i data-lucide="check"></i><span>M3 visa roadmap</span></li>
            </ul>
          </div>
        </div>
      </section>
    </main>
@endsection