@php
    $pageTitle = 'Study in Europe | One Degree Advisory';
    $pageDescription = 'Study in Europe with One Degree Advisory. Compare destinations across the EU and EEA: Bologna-Process degrees, English-taught programs, Schengen mobility, and value tuition.';
    $activeNav = 'destinations';
    $mainId = 'country-main';
@endphp

@extends('layouts.app')

@section('content')
<main id="country-main" class="country-main">
      <section class="country-hero country-hero--europe" id="top">
        <div class="container country-hero-grid">
          <div class="country-hero-copy">
            <a class="country-back" href="{{ route('home') }}#destinations"><i data-lucide="arrow-left"></i><span>All destinations</span></a>
            <span class="country-flag-lg"><svg viewBox="0 0 24 24" width="44" height="44"><circle cx="12" cy="12" r="11" fill="#003399"/><g fill="#FFCC00"><circle cx="12" cy="3.6" r="1.1"/><circle cx="12" cy="20.4" r="1.1"/><circle cx="3.6" cy="12" r="1.1"/><circle cx="20.4" cy="12" r="1.1"/><circle cx="6" cy="6" r="1.1"/><circle cx="18" cy="6" r="1.1"/><circle cx="6" cy="18" r="1.1"/><circle cx="18" cy="18" r="1.1"/><circle cx="4.6" cy="9" r="1.1"/><circle cx="19.4" cy="9" r="1.1"/><circle cx="4.6" cy="15" r="1.1"/><circle cx="19.4" cy="15" r="1.1"/></g></svg></span>
            <span class="eyebrow">Region overview</span>
            <h1>Study in <span class="gold-text">Europe</span></h1>
            <p class="country-lede">Europe is a single, navigable region for global education &mdash; Bologna-Process degrees, English-taught programs, mobility within Schengen, and a wide value-to-prestige spectrum across 30+ countries.</p>
            <div class="country-actions"><a class="btn btn-primary" href="{{ route('contact') }}"><span>Plan your Europe shortlist</span><i data-lucide="arrow-up-right"></i></a><a class="btn btn-ghost" href="#top-universities"><i data-lucide="building-2"></i><span>Top hubs</span></a></div>
          </div>
          <aside class="country-snapshot"><h2>At a glance</h2>
            <dl>
              <div><dt>Countries covered</dt><dd>30+ (EU/EEA + UK + CH)</dd></div>
              <div><dt>Degree system</dt><dd>Bologna 3+2+3 cycle</dd></div>
              <div><dt>Tuition / year</dt><dd><span>&euro;0</span> &ndash; <span>&euro;25,000</span></dd></div>
              <div><dt>Living cost / month</dt><dd><span>&euro;600</span> &ndash; <span>&euro;1,800</span></dd></div>
              <div><dt>Mobility</dt><dd>Schengen + Erasmus+</dd></div>
              <div><dt>Post-study work</dt><dd>1&ndash;2 years (country specific)</dd></div>
            </dl>
          </aside>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Why Europe</span><h2>One region, many strong fits</h2><p>From tuition-free Germany to grande &eacute;cole France, English-taught Netherlands, and value-led Eastern Europe.</p></div>
          <div class="reasons-grid">
            <article class="reason-card"><span class="reason-icon"><i data-lucide="globe"></i></span><h3>Bologna-Process degrees</h3><p>3-year bachelors, 2-year masters, 3-year doctorates &mdash; portable credentials recognised across Europe.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="languages"></i></span><h3>English-taught at scale</h3><p>Thousands of English-medium programs across Germany, Netherlands, Nordic countries, and France.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="map"></i></span><h3>Schengen mobility</h3><p>Travel and short stays across 27 Schengen states with a single residence permit.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="piggy-bank"></i></span><h3>Value-to-prestige spectrum</h3><p>From <span>&euro;0</span> public tuition in Germany to elite grandes &eacute;coles and Bocconi-tier private schools.</p></article>
          </div>
        </div>
      </section>

      <section id="top-universities" class="country-section alt">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Hubs</span><h2>Top European study hubs</h2><p>A snapshot of strong destinations by theme.</p></div>
          <div class="university-grid">
            <article class="university-card"><span class="uni-rank">DE</span><div class="uni-info"><h3>Germany</h3><p>Tuition-free public universities, leading engineering, 18-month job-search visa.</p></div></article>
            <article class="university-card"><span class="uni-rank">FR</span><div class="uni-info"><h3>France</h3><p>Grandes &eacute;coles + affordable public universities, 2-year APS post-study.</p></div></article>
            <article class="university-card"><span class="uni-rank">NL</span><div class="uni-info"><h3>Netherlands</h3><p>English-taught, applied research, Orientation Year permit.</p></div></article>
            <article class="university-card"><span class="uni-rank">IE</span><div class="uni-info"><h3>Ireland</h3><p>Tech &amp; pharma industry access, 2-year Stay-Back.</p></div></article>
            <article class="university-card"><span class="uni-rank">IT</span><div class="uni-info"><h3>Italy</h3><p>Heritage universities, design and fashion strength, low public tuition.</p></div></article>
            <article class="university-card"><span class="uni-rank">ES</span><div class="uni-info"><h3>Spain</h3><p>Business schools (IE, ESADE), lifestyle, growing tech ecosystem.</p></div></article>
            <article class="university-card"><span class="uni-rank">FI</span><div class="uni-info"><h3>Finland</h3><p>Top tech &amp; design, Aalto and Helsinki, 2-year residence post-study.</p></div></article>
            <article class="university-card"><span class="uni-rank">PL</span><div class="uni-info"><h3>Poland</h3><p>Affordable EU degrees, English-taught business and engineering.</p></div></article>
          </div>
        </div>
      </section>
      <section class="country-section full-bleed" aria-label="Country highlight banner">
        <div class="container">
          <div class="country-band">
            <img src="https://images.unsplash.com/photo-1473951574080-01fe45ec8643?auto=format&fit=crop&w=2000&q=80" alt="">
            <div class="band-inner">
              <span class="eyebrow">One application, 27 destinations</span>
              <h2>Affordable, globally-recognised &mdash; and EU-mobile.</h2>
              <p>From Berlin&rsquo;s engineering schools to Bologna&rsquo;s liberal arts &mdash; the Bologna framework keeps degrees portable across the EU.</p>
              <div class="band-stats"><div class="band-stat"><strong>27</strong><span>EU countries</span></div><div class="band-stat"><strong>Bologna</strong><span>Framework</span></div><div class="band-stat"><strong>Schengen</strong><span>Mobility</span></div><div class="band-stat"><strong>EUR</strong><span>Tuition + living</span></div></div>
            </div>
          </div>
        </div>
      </section>


      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Programs</span><h2>What Europe is known for</h2><p>Subject strengths concentrated across the region.</p></div>
          <div class="programs-grid">
            <span class="program-chip"><i data-lucide="cog"></i>Engineering &amp; Automotive</span>
            <span class="program-chip"><i data-lucide="briefcase-business"></i>Business &amp; MBA</span>
            <span class="program-chip"><i data-lucide="cpu"></i>Computer Science &amp; AI</span>
            <span class="program-chip"><i data-lucide="palette"></i>Design &amp; Fashion</span>
            <span class="program-chip"><i data-lucide="leaf"></i>Sustainability &amp; Energy</span>
            <span class="program-chip"><i data-lucide="stethoscope"></i>Medicine &amp; Life Sciences</span>
            <span class="program-chip"><i data-lucide="globe"></i>International Relations</span>
            <span class="program-chip"><i data-lucide="bar-chart-3"></i>Data Science &amp; Analytics</span>
          </div>
        </div>
      </section>

      <section class="country-section alt">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Costs</span><h2>Tuition and cost of living spectrum</h2><p>Europe spans a wide cost range &mdash; positioning matters.</p></div>
          <div class="cost-grid">
            <div class="cost-card"><span class="cost-label">Lowest tuition</span><span class="cost-value"><span>&euro;0</span>&ndash;<span>&euro;1,500</span></span><span class="cost-note">Germany (public), Norway (English MA cap), Czechia (Czech-taught).</span></div>
            <div class="cost-card"><span class="cost-label">Mid tuition</span><span class="cost-value"><span>&euro;2,000</span>&ndash;<span>&euro;10,000</span></span><span class="cost-note">France, Italy, Spain (public), Poland, Hungary, Portugal.</span></div>
            <div class="cost-card"><span class="cost-label">Higher tuition</span><span class="cost-value"><span>&euro;10,000</span>&ndash;<span>&euro;25,000</span></span><span class="cost-note">Netherlands, Ireland, Belgium, top business schools, private universities.</span></div>
            <div class="cost-card"><span class="cost-label">Living cost band</span><span class="cost-value"><span>&euro;600</span>&ndash;<span>&euro;1,800</span></span><span class="cost-note">Per month &middot; Eastern Europe lowest, Switzerland and Nordic capitals highest.</span></div>
          </div>
        </div>
      </section>

      <section class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Funding &amp; visa</span>
            <h2>Scholarships, funding &amp; the EU student visa</h2>
            <p>Two parallel tracks &mdash; how to fund the degree, and how to secure your stay.</p>
          </div>
          <div class="split-info">
            <div class="info-card"><h3>Scholarships across Europe</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Erasmus Mundus Joint Masters &mdash; fully funded multi-country programs.</span></li>
                <li><i data-lucide="check"></i><span>DAAD (Germany), Eiffel (France), Holland Scholarship (NL), Finland Scholarship.</span></li>
                <li><i data-lucide="check"></i><span>Government scholarships from Italy (MAECI), Spain (MAEC-AECID), Poland (NAWA).</span></li>
                <li><i data-lucide="check"></i><span>Erasmus+ for partner mobility within EU/EEA universities.</span></li>
                <li><i data-lucide="check"></i><span>University and faculty-level merit and need-based awards across the region.</span></li>
              </ul>
            </div>
            <div class="info-card"><h3>Visa &amp; mobility snapshot</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Country-specific National D visas plus a residence permit on arrival.</span></li>
                <li><i data-lucide="check"></i><span>Schengen residence permit allows travel across 27 Schengen states.</span></li>
                <li><i data-lucide="check"></i><span>Post-study residence: 18 months (DE), 2 years (FR, FI, IE), 1 year (IT, BE).</span></li>
                <li><i data-lucide="check"></i><span>EU Blue Card pathway for skilled employment after graduation.</span></li>
                <li><i data-lucide="check"></i><span>Erasmus mobility windows within most master&rsquo;s programs.</span></li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section class="country-section alt">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Planning</span><h2>How to think about Europe</h2><p>A practical sequence for shortlisting and applying.</p></div>
          <div class="timeline-list">
            <div class="timeline-item"><h4>Country fit by goal</h4><p>Pick the country by program direction, budget, and career objective &mdash; not the reverse.</p></div>
            <div class="timeline-item"><h4>Language audit</h4><p>Identify whether English-only is enough or whether you need German/French/Italian for outcomes.</p></div>
            <div class="timeline-item"><h4>Application portals</h4><p>uni-assist (DE), Campus France, Universitaly (IT), Studielink (NL), national portals elsewhere.</p></div>
            <div class="timeline-item"><h4>Scholarship windows</h4><p>Erasmus Mundus and major national scholarships often close 6&ndash;9 months before intake.</p></div>
            <div class="timeline-item"><h4>Visa &amp; financial proof</h4><p>Each country has its own blocked account / proof-of-funds requirement.</p></div>
            <div class="timeline-item"><h4>PR pathway view</h4><p>EU Blue Card, country-specific permanent residence rules &mdash; plan from year one.</p></div>
          </div>
        </div>
      </section>

      <section class="country-cta">
        <div class="container country-cta-grid">
          <div>
            <h2>Build your Europe shortlist with us</h2>
            <p>Three countries, three programs, fit notes &mdash; One Degree helps you commit with confidence.</p>
            <div class="country-actions"><a class="btn btn-primary" href="{{ route('contact') }}"><span>Book a Europe call</span><i data-lucide="arrow-up-right"></i></a><a class="btn btn-ghost" href="{{ route('home') }}#destinations"><i data-lucide="compass"></i><span>Compare destinations</span></a></div>
          </div>
          <div class="country-cta-card"><h3>What you&rsquo;ll get</h3><p>A 30-minute counselor-led session, free of cost.</p><ul><li><i data-lucide="check"></i><span>3 country shortlist with fit notes</span></li><li><i data-lucide="check"></i><span>Erasmus Mundus signal</span></li><li><i data-lucide="check"></i><span>Blue Card &amp; PR clarity</span></li></ul></div>
        </div>
      </section>
    </main>
@endsection