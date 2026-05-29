@php
    $pageTitle = 'Study in France | One Degree Advisory';
    $pageDescription = 'Study in France with One Degree Advisory. Grandes ecoles, public universities, affordable tuition, VLS-TS visa, and a 2-year post-study residence.';
    $activeNav = 'destinations';
    $mainId = 'country-main';
@endphp

@extends('layouts.app')

@section('content')
<main id="country-main" class="country-main">
      <section class="country-hero country-hero--france" id="top">
        <div class="container country-hero-grid">
          <div class="country-hero-copy">
            <a class="country-back" href="{{ route('home') }}#destinations"><i data-lucide="arrow-left"></i><span>All destinations</span></a>
            <span class="country-flag-lg"><img src="https://flagcdn.com/w160/fr.png" alt="France flag"></span>
            <span class="eyebrow">Country guide</span>
            <h1>Study in <span class="gold-text">France</span></h1>
            <p class="country-lede">France blends grandes &eacute;coles excellence with affordable public universities and a strong post-study residence option &mdash; a top choice for business, sciences, and the arts.</p>
            <div class="country-actions"><a class="btn btn-primary" href="{{ route('contact') }}"><span>Plan your France application</span><i data-lucide="arrow-up-right"></i></a><a class="btn btn-ghost" href="#top-universities"><i data-lucide="building-2"></i><span>Top universities</span></a></div>
          </div>
          <aside class="country-snapshot"><h2>At a glance</h2>
            <dl>
              <div><dt>Top universities</dt><dd>30+ globally ranked</dd></div>
              <div><dt>Main intakes</dt><dd>September, some January</dd></div>
              <div><dt>Tuition / year</dt><dd><span>&euro;170</span> &ndash; <span>&euro;25,000</span></dd></div>
              <div><dt>Living cost / month</dt><dd><span>&euro;800</span> &ndash; <span>&euro;1,500</span></dd></div>
              <div><dt>Student visa</dt><dd>VLS-TS Long-stay</dd></div>
              <div><dt>Post-study work</dt><dd>APS &middot; 2-year residence</dd></div>
            </dl>
          </aside>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Why France</span><h2>Grandes &eacute;coles, value, lifestyle</h2><p>Where elite specialist schools meet large, affordable public universities.</p></div>
          <div class="reasons-grid">
            <article class="reason-card"><span class="reason-icon"><i data-lucide="award"></i></span><h3>Grandes &eacute;coles</h3><p>Specialist schools in business (HEC, INSEAD, ESSEC), engineering (Polytechnique, CentraleSup&eacute;lec), and sciences.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="piggy-bank"></i></span><h3>Affordable public tuition</h3><p>Most public universities charge only a few hundred euros per year for bachelor and master programs.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="languages"></i></span><h3>English-taught options</h3><p>Strong English-medium offering in business, sciences, and international relations.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="briefcase"></i></span><h3>2-year post-study residence</h3><p>APS (Autorisation Provisoire de S&eacute;jour) lets master&rsquo;s graduates stay 2 years to find a job.</p></article>
          </div>
        </div>
      </section>

      <section id="top-universities" class="country-section alt">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Universities</span><h2>Top universities &amp; schools in France</h2><p>A shortlist across public universities and grandes &eacute;coles.</p></div>
          <div class="university-grid">
            <article class="university-card"><span class="uni-rank">01</span><div class="uni-info"><h3>Universit&eacute; PSL</h3><p>Paris Sciences et Lettres &mdash; collegiate group including ENS, Dauphine, MINES Paris.</p></div></article>
            <article class="university-card"><span class="uni-rank">02</span><div class="uni-info"><h3>Sorbonne University</h3><p>Major Paris research university spanning humanities, sciences, and medicine.</p></div></article>
            <article class="university-card"><span class="uni-rank">03</span><div class="uni-info"><h3>&Eacute;cole Polytechnique</h3><p>France&rsquo;s flagship engineering grande &eacute;cole &mdash; deep math, physics, and applied sciences.</p></div></article>
            <article class="university-card"><span class="uni-rank">04</span><div class="uni-info"><h3>HEC Paris</h3><p>Top European business school for MBA, MiM, and specialised finance master&rsquo;s.</p></div></article>
            <article class="university-card"><span class="uni-rank">05</span><div class="uni-info"><h3>INSEAD (Fontainebleau)</h3><p>Global one-year MBA with French and Singapore campuses.</p></div></article>
            <article class="university-card"><span class="uni-rank">06</span><div class="uni-info"><h3>Sciences Po</h3><p>Leading political science, public affairs, and international relations institution.</p></div></article>
            <article class="university-card"><span class="uni-rank">07</span><div class="uni-info"><h3>ESSEC Business School</h3><p>Selective business school with MBA, MiM, and specialised programs.</p></div></article>
            <article class="university-card"><span class="uni-rank">08</span><div class="uni-info"><h3>Universit&eacute; Paris-Saclay</h3><p>Comprehensive research university with leading mathematics and physics programs.</p></div></article>
          </div>
        </div>
      </section>
      <section class="country-section full-bleed" aria-label="Country highlight banner">
        <div class="container">
          <div class="country-band">
            <img src="https://images.unsplash.com/photo-1499856871958-5b9627545d1a?auto=format&fit=crop&w=2000&q=80" alt="">
            <div class="band-inner">
              <span class="eyebrow">Grandes &Eacute;coles &amp; world heritage</span>
              <h2>Heritage of academia. Industries of the future.</h2>
              <p>Sorbonne, HEC, INSEAD, Polytechnique &mdash; backed by 1,500+ English-taught programs and an APS post-study work visa.</p>
              <div class="band-stats"><div class="band-stat"><strong><span>&euro;3k</span></strong><span>Public tuition</span></div><div class="band-stat"><strong>APS</strong><span>Stay-back</span></div><div class="band-stat"><strong>1500+</strong><span>English programs</span></div><div class="band-stat"><strong>EU</strong><span>Member state</span></div></div>
            </div>
          </div>
        </div>
      </section>


      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Programs</span><h2>Popular programs</h2><p>Where France&rsquo;s strengths align most with international demand.</p></div>
          <div class="programs-grid">
            <span class="program-chip"><i data-lucide="briefcase-business"></i>Business &amp; MBA</span>
            <span class="program-chip"><i data-lucide="cog"></i>Engineering</span>
            <span class="program-chip"><i data-lucide="line-chart"></i>Finance &amp; Economics</span>
            <span class="program-chip"><i data-lucide="utensils"></i>Culinary Arts</span>
            <span class="program-chip"><i data-lucide="palette"></i>Fashion &amp; Luxury</span>
            <span class="program-chip"><i data-lucide="globe"></i>International Relations</span>
            <span class="program-chip"><i data-lucide="cpu"></i>Computer Science &amp; AI</span>
            <span class="program-chip"><i data-lucide="leaf"></i>Sustainability &amp; Energy</span>
          </div>
        </div>
      </section>

      <section class="country-section alt">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Costs</span><h2>Tuition and cost of living</h2><p>Public universities are very affordable; grandes &eacute;coles sit higher.</p></div>
          <div class="cost-grid">
            <div class="cost-card"><span class="cost-label">Public bachelor</span><span class="cost-value"><span>&euro;170</span>&ndash;<span>&euro;2,770</span></span><span class="cost-note">Per year &middot; non-EU rate at public universities.</span></div>
            <div class="cost-card"><span class="cost-label">Public master</span><span class="cost-value"><span>&euro;243</span>&ndash;<span>&euro;3,770</span></span><span class="cost-note">Per year &middot; specialist programs may charge more.</span></div>
            <div class="cost-card"><span class="cost-label">Grandes &eacute;coles tuition</span><span class="cost-value"><span>&euro;5,000</span>&ndash;<span>&euro;25,000</span></span><span class="cost-note">Per year &middot; MBA, MiM, and engineering schools.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Paris/other cities</span><span class="cost-value"><span>&euro;800</span>&ndash;<span>&euro;1,500</span></span><span class="cost-note">Per month &middot; Paris highest, regional cities ~<span>&euro;800</span>.</span></div>
          </div>
        </div>
      </section>

      <section class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Funding &amp; visa</span>
            <h2>Scholarships, funding &amp; the France visa</h2>
            <p>Two parallel tracks &mdash; how to fund the degree, and how to secure your stay.</p>
          </div>
          <div class="split-info">
            <div class="info-card"><h3>Scholarships &amp; funding</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Eiffel Excellence Scholarship for international master&rsquo;s and PhD students.</span></li>
                <li><i data-lucide="check"></i><span>Charpak Scholarship for Indian students (Campus France).</span></li>
                <li><i data-lucide="check"></i><span>French Government Scholarships via embassies.</span></li>
                <li><i data-lucide="check"></i><span>University and grande &eacute;cole merit awards.</span></li>
                <li><i data-lucide="check"></i><span>CAF housing allowance can offset rent costs.</span></li>
              </ul>
            </div>
            <div class="info-card"><h3>Student visa snapshot</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Visa name: <strong>VLS-TS &eacute;tudiant</strong> (long-stay study visa equivalent to a residence permit).</span></li>
                <li><i data-lucide="check"></i><span>Apply through Campus France &mdash; mandatory for most countries.</span></li>
                <li><i data-lucide="check"></i><span>Proof of funds: ~<span>&euro;615</span>/month for the duration of study.</span></li>
                <li><i data-lucide="check"></i><span>Validate the visa online within 3 months of arrival.</span></li>
                <li><i data-lucide="check"></i><span>Work allowance: up to 964 hours/year (~20 hours/week).</span></li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section class="country-section alt">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Timeline</span><h2>Application timeline</h2><p>A typical 10&ndash;14 month roadmap for a September intake.</p></div>
          <div class="timeline-list">
            <div class="timeline-item"><h4>Profile review &amp; shortlist</h4><p>12&ndash;14 months out: public vs. grande &eacute;cole choice, language plan.</p></div>
            <div class="timeline-item"><h4>Tests &amp; documents</h4><p>8&ndash;12 months out: IELTS/TOEFL; GRE/GMAT for grandes &eacute;coles; French B1/B2 where required.</p></div>
            <div class="timeline-item"><h4>Campus France &amp; applications</h4><p>October&ndash;February: Etudes en France procedure, parallel direct applications.</p></div>
            <div class="timeline-item"><h4>Interviews &amp; admits</h4><p>March&ndash;May: Campus France interview, offer confirmations.</p></div>
            <div class="timeline-item"><h4>VLS-TS visa</h4><p>June&ndash;August: book visa appointment, biometrics, attend interview.</p></div>
            <div class="timeline-item"><h4>Arrival &amp; OFII validation</h4><p>Within 3 months: online VLS-TS validation, accommodation, CAF, social security.</p></div>
          </div>
        </div>
      </section>

      <section class="country-cta">
        <div class="container country-cta-grid">
          <div>
            <h2>Plan your France application with us</h2>
            <p>From Campus France to grande &eacute;cole essays, One Degree maps each step.</p>
            <div class="country-actions"><a class="btn btn-primary" href="{{ route('contact') }}"><span>Book a France call</span><i data-lucide="arrow-up-right"></i></a><a class="btn btn-ghost" href="{{ route('home') }}#destinations"><i data-lucide="compass"></i><span>Compare destinations</span></a></div>
          </div>
          <div class="country-cta-card"><h3>What you&rsquo;ll get</h3><p>A 30-minute counselor-led session, free of cost.</p><ul><li><i data-lucide="check"></i><span>Public vs. grande &eacute;cole fit</span></li><li><i data-lucide="check"></i><span>Eiffel &amp; Charpak signal</span></li><li><i data-lucide="check"></i><span>APS &amp; PR pathway</span></li></ul></div>
        </div>
      </section>
    </main>
@endsection