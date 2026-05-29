@php
    $pageTitle = 'Study in Spain | One Degree Advisory';
    $pageDescription = 'Study in Spain with One Degree Advisory. Top universities, tuition, scholarships, Type D student visa, job-seeker permit, intakes, costs, FAQs.';
    $activeNav = 'destinations';
    $mainId = 'country-main';
@endphp

@extends('layouts.app')

@section('content')
<main id="country-main" class="country-main">
      <section class="country-hero country-hero--spain" id="top">
        <div class="container country-hero-grid">
          <div class="country-hero-copy">
            <a class="country-back" href="{{ route('home') }}#destinations"><i data-lucide="arrow-left"></i><span>All destinations</span></a>
            <span class="country-flag-lg"><img src="https://flagcdn.com/w160/es.png" alt="Spain flag"></span>
            <span class="eyebrow">Country guide</span>
            <h1>Study in <span class='gold-text'>Spain</span></h1>
            <p class="country-lede">Spain pairs world-recognised business schools and ancient universities with affordable tuition, a year-round Mediterranean lifestyle, and a 1-year post-study job-seeker route in the EU.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Plan your Spain application</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="#top-universities"><i data-lucide="building-2"></i><span>Top universities</span></a>
            </div>
          </div>
          <aside class="country-snapshot">
            <h2>At a glance</h2>
            <dl>
              <div><dt>Top universities</dt><dd>75+ globally ranked</dd></div>
              <div><dt>Main intakes</dt><dd>September, February</dd></div>
              <div><dt>Tuition / year</dt><dd><span>&euro;1,500</span> &ndash; <span>&euro;20,000</span></dd></div>
              <div><dt>Living cost / month</dt><dd><span>&euro;800</span> &ndash; <span>&euro;1,200</span></dd></div>
              <div><dt>Student visa</dt><dd>Type D student visa</dd></div>
              <div><dt>Post-study work</dt><dd>Job-seeker year &middot; 12 months</dd></div>
            </dl>
          </aside>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Why Spain</span><h2>Heritage universities. Modern industries. EU mobility.</h2><p>Affordable degrees, sun-soaked lifestyle, and access to the EU job market after graduation.</p></div>
          <div class="reasons-grid">
            <article class="reason-card"><span class="reason-icon"><i data-lucide="award"></i></span><h3>Top-ranked schools</h3><p>IE, ESADE, IESE, and the University of Barcelona consistently rank among Europe&rsquo;s best.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="languages"></i></span><h3>English-taught options</h3><p>Master&rsquo;s in business, engineering, and tourism widely taught in English.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="sun"></i></span><h3>Lifestyle &amp; culture</h3><p>Year-round sun, world-class food, and a deep international community.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="briefcase"></i></span><h3>EU work pathway</h3><p>1-year job-seeker permit after graduation, then EU Blue Card eligibility.</p></article>
          </div>
        </div>
      </section>

      <section class="country-section full-bleed" aria-label="Country highlight banner">
        <div class="container">
          <div class="country-band">
            <img src="https://images.unsplash.com/photo-1539037116277-4db20889f2d4?auto=format&fit=crop&w=2000&q=80" alt="">
            <div class="band-inner">
              <span class="eyebrow">Mediterranean meets EU</span>
              <h2>Affordable tuition, EU mobility, world-class business schools.</h2>
              <p>Three of the world&rsquo;s top 30 MBA programs are in Spain &mdash; IE, ESADE, IESE &mdash; alongside public universities charging as little as <span>&euro;1,500</span>/year.</p>
              <div class="band-stats"><div class="band-stat"><strong>75+</strong><span>Universities</span></div><div class="band-stat"><strong>3</strong><span>Top-30 MBAs</span></div><div class="band-stat"><strong>EU</strong><span>Member state</span></div><div class="band-stat"><strong>1 yr</strong><span>Job-seeker visa</span></div></div>
            </div>
          </div>
        </div>
      </section>

      <section id="top-universities" class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left"><span class="eyebrow">Universities</span><h2>Top universities in Spain</h2><p>A representative shortlist across research universities and specialist institutions.</p></div>
          <div class="university-grid">
            <article class="university-card"><span class="uni-rank">01</span><div class="uni-info"><h3>University of Barcelona (UB)</h3><p>Spain&rsquo;s largest public university &mdash; medicine, sciences, and humanities.</p></div></article>
            <article class="university-card"><span class="uni-rank">02</span><div class="uni-info"><h3>Autonomous University of Madrid (UAM)</h3><p>Strong in law, economics, and sciences.</p></div></article>
            <article class="university-card"><span class="uni-rank">03</span><div class="uni-info"><h3>Pompeu Fabra University (UPF)</h3><p>Barcelona-based, top-ranked in economics and political science.</p></div></article>
            <article class="university-card"><span class="uni-rank">04</span><div class="uni-info"><h3>Complutense University of Madrid</h3><p>Historic Madrid university with broad UG and PG programs.</p></div></article>
            <article class="university-card"><span class="uni-rank">05</span><div class="uni-info"><h3>IE University</h3><p>Private international university with English-taught programs across Madrid and Segovia.</p></div></article>
            <article class="university-card"><span class="uni-rank">06</span><div class="uni-info"><h3>ESADE Business School</h3><p>Top European business school with 1-year MBA tracks.</p></div></article>
            <article class="university-card"><span class="uni-rank">07</span><div class="uni-info"><h3>IESE Business School</h3><p>Globally ranked MBA at the University of Navarra.</p></div></article>
            <article class="university-card"><span class="uni-rank">08</span><div class="uni-info"><h3>Carlos III University of Madrid (UC3M)</h3><p>Engineering, economics, and law &mdash; bilingual programs.</p></div></article>
          </div>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Programs</span><h2>Popular programs</h2><p>The most-applied tracks for international students.</p></div>
          <div class="programs-grid">
            <span class="program-chip"><i data-lucide="briefcase-business"></i>Business &amp; MBA</span>
            <span class="program-chip"><i data-lucide="hotel"></i>Hospitality &amp; Tourism</span>
            <span class="program-chip"><i data-lucide="palette"></i>Architecture &amp; Design</span>
            <span class="program-chip"><i data-lucide="cog"></i>Engineering</span>
            <span class="program-chip"><i data-lucide="cpu"></i>Computer Science</span>
            <span class="program-chip"><i data-lucide="stethoscope"></i>Medicine &amp; Health</span>
            <span class="program-chip"><i data-lucide="languages"></i>Languages &amp; Linguistics</span>
            <span class="program-chip"><i data-lucide="globe"></i>International Relations</span>
          </div>
        </div>
      </section>

      <section class="country-section paper">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Costs</span><h2>Tuition and cost of living</h2><p>Indicative ranges &mdash; final figures depend on program and city.</p></div>
          <div class="cost-grid">
            <div class="cost-card"><span class="cost-label">Public university tuition</span><span class="cost-value"><span>&euro;1,500</span> &ndash; <span>&euro;3,500</span></span><span class="cost-note">Per year &middot; among the lowest in Europe.</span></div>
            <div class="cost-card"><span class="cost-label">Private university tuition</span><span class="cost-value"><span>&euro;8,000</span> &ndash; <span>&euro;25,000</span></span><span class="cost-note">Per year &middot; IE, ESADE, IESE at upper end.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Madrid/Barcelona</span><span class="cost-value"><span>&euro;1,000</span> &ndash; <span>&euro;1,400</span></span><span class="cost-note">Per month &middot; major-city rents lead the budget.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Other cities</span><span class="cost-value"><span>&euro;700</span> &ndash; <span>&euro;1,000</span></span><span class="cost-note">Per month &middot; Valencia, Granada, Seville.</span></div>
          </div>
        </div>
      </section>

      <section class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Funding &amp; visa</span>
            <h2>Scholarships, funding &amp; the Spain student visa</h2>
            <p>Two parallel tracks &mdash; how to fund the degree, and how to secure your stay.</p>
          </div>
          <div class="split-info">
            <div class="info-card">
              <h3>Scholarships &amp; funding</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Spanish Government Scholarships (Becas MAEC-AECID) for international students.</span></li>
                <li><i data-lucide="check"></i><span>Fundaci&oacute;n Carolina master&rsquo;s and PhD scholarships for Latin American and EU students.</span></li>
                <li><i data-lucide="check"></i><span>University-specific entrance scholarships (e.g. IE Foundation, ESADE Talent grants).</span></li>
                <li><i data-lucide="check"></i><span>ERASMUS+ mobility funding for joint EU master&rsquo;s programs.</span></li>
                <li><i data-lucide="check"></i><span>Education loans through major Indian and international lenders.</span></li>
              </ul>
            </div>
            <div class="info-card">
              <h3>Student visa snapshot</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Visa name: <strong>Type D Student visa</strong> via the Spanish consulate.</span></li>
                <li><i data-lucide="check"></i><span>Requires a letter of admission from an accredited Spanish institution.</span></li>
                <li><i data-lucide="check"></i><span>Proof of funds: roughly <span>&euro;600</span>&ndash;<span>&euro;700</span>/month for the duration of study.</span></li>
                <li><i data-lucide="check"></i><span>Standard processing: 4&ndash;8 weeks &mdash; apply early in the cycle.</span></li>
                <li><i data-lucide="check"></i><span>Work allowance: up to 30 hours/week alongside studies.</span></li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left"><span class="eyebrow">Timeline</span><h2>Application timeline</h2><p>A typical roadmap from profile review to arrival.</p></div>
          <div class="timeline-list">
            <div class="timeline-item"><h4>Profile review &amp; shortlist</h4><p>12&ndash;14 months out: program direction, budget, target cities.</p></div>
            <div class="timeline-item"><h4>Tests &amp; documents</h4><p>8&ndash;12 months out: IELTS/DELE if required; transcripts, SOP, LORs.</p></div>
            <div class="timeline-item"><h4>Applications</h4><p>Oct&ndash;May: most master&rsquo;s programs; UG via UNED for international students.</p></div>
            <div class="timeline-item"><h4>Offers &amp; deposit</h4><p>Mar&ndash;Jun: accept offer, pay deposit, finalise loan/scholarship.</p></div>
            <div class="timeline-item"><h4>Type D visa</h4><p>May&ndash;Aug: book appointment, submit at consulate, await processing.</p></div>
            <div class="timeline-item"><h4>Pre-departure &amp; arrival</h4><p>Aug&ndash;Sep: housing, NIE, insurance, flights, arrival.</p></div>
          </div>
        </div>
      </section>

      <section class="country-cta">
        <div class="container country-cta-grid">
          <div>
            <h2>Plan your Spain application with us</h2>
            <p>From IELTS to NIE registration &mdash; One Degree maps a clean Spain-ready application.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Book a Spain call</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="{{ route('home') }}#destinations"><i data-lucide="compass"></i><span>Compare destinations</span></a>
            </div>
          </div>
          <div class="country-cta-card">
            <h3>What you&rsquo;ll get</h3>
            <p>A 30-minute counselor-led session, free of cost.</p>
            <ul>
              <li><i data-lucide="check"></i><span>Madrid vs Barcelona fit</span></li>
              <li><i data-lucide="check"></i><span>Public vs private tuition</span></li>
              <li><i data-lucide="check"></i><span>Job-seeker visa roadmap</span></li>
            </ul>
          </div>
        </div>
      </section>
    </main>
@endsection