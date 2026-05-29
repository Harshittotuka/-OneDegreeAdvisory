@php
    $pageTitle = 'Study in Malta | One Degree Advisory';
    $pageDescription = 'Study in Malta with One Degree Advisory. English-medium EU degrees, University of Malta, tuition, scholarships, visa, post-study, FAQs.';
    $activeNav = 'destinations';
    $mainId = 'country-main';
@endphp

@extends('layouts.app')

@section('content')
<main id="country-main" class="country-main">
      <section class="country-hero country-hero--malta" id="top">
        <div class="container country-hero-grid">
          <div class="country-hero-copy">
            <a class="country-back" href="{{ route('home') }}#destinations"><i data-lucide="arrow-left"></i><span>All destinations</span></a>
            <span class="country-flag-lg"><img src="https://flagcdn.com/w160/mt.png" alt="Malta flag"></span>
            <span class="eyebrow">Country guide</span>
            <h1>Study in <span class='gold-text'>Malta</span></h1>
            <p class="country-lede">Malta combines an English-medium EU education with Mediterranean lifestyle and a clear post-study work pathway &mdash; ideal for students looking for an EU degree without a language barrier.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Plan your Malta application</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="#top-universities"><i data-lucide="building-2"></i><span>Top universities</span></a>
            </div>
          </div>
          <aside class="country-snapshot">
            <h2>At a glance</h2>
            <dl>
              <div><dt>Top universities</dt><dd>University of Malta + private institutions</dd></div>
              <div><dt>Main intakes</dt><dd>October, February</dd></div>
              <div><dt>Tuition / year</dt><dd><span>&euro;9,000</span> &ndash; <span>&euro;18,000</span></dd></div>
              <div><dt>Living cost / month</dt><dd><span>&euro;800</span> &ndash; <span>&euro;1,200</span></dd></div>
              <div><dt>Student visa</dt><dd>Maltese study visa</dd></div>
              <div><dt>Post-study work</dt><dd>Job-seeking residence</dd></div>
            </dl>
          </aside>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Why Malta</span><h2>English-medium EU degrees in the Mediterranean.</h2><p>Quality EU education, low language friction, and a clear post-study pathway in a sunny island lifestyle.</p></div>
          <div class="reasons-grid">
            <article class="reason-card"><span class="reason-icon"><i data-lucide="languages"></i></span><h3>English-medium</h3><p>All public and most private universities teach in English.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="globe"></i></span><h3>EU member state</h3><p>Recognised across the EU and EEA after graduation.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="sun"></i></span><h3>Lifestyle &amp; safety</h3><p>Mild Mediterranean climate; among Europe&rsquo;s safest countries.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="briefcase"></i></span><h3>Post-study work</h3><p>Job-seeking residence with clear pathways to work permits.</p></article>
          </div>
        </div>
      </section>

      <section class="country-section full-bleed" aria-label="Country highlight banner">
        <div class="container">
          <div class="country-band">
            <img src="https://images.unsplash.com/photo-1503416997304-7f8bf166c121?auto=format&fit=crop&w=2000&q=80" alt="">
            <div class="band-inner">
              <span class="eyebrow">EU + English-medium</span>
              <h2>Mediterranean campus, EU-recognised degrees.</h2>
              <p>The University of Malta and private institutions offer English-taught programs across business, IT, and digital arts &mdash; with EU work rights after graduation.</p>
              <div class="band-stats"><div class="band-stat"><strong>EU</strong><span>Member state</span></div><div class="band-stat"><strong>EN</strong><span>Medium</span></div><div class="band-stat"><strong><span>&euro;9k</span>+</strong><span>Tuition</span></div><div class="band-stat"><strong>PSW</strong><span>Pathway</span></div></div>
            </div>
          </div>
        </div>
      </section>

      <section id="top-universities" class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left"><span class="eyebrow">Universities</span><h2>Top universities in Malta</h2><p>A representative shortlist across research universities and specialist institutions.</p></div>
          <div class="university-grid">
            <article class="university-card"><span class="uni-rank">01</span><div class="uni-info"><h3>University of Malta</h3><p>Public flagship; medicine, law, IT, business, sciences.</p></div></article>
            <article class="university-card"><span class="uni-rank">02</span><div class="uni-info"><h3>Malta College of Arts, Science and Technology (MCAST)</h3><p>Industry-focused diplomas and degrees.</p></div></article>
            <article class="university-card"><span class="uni-rank">03</span><div class="uni-info"><h3>American University of Malta</h3><p>Liberal-arts and business programs in English.</p></div></article>
            <article class="university-card"><span class="uni-rank">04</span><div class="uni-info"><h3>STC Higher Education</h3><p>Private institution; business, hospitality, gaming.</p></div></article>
            <article class="university-card"><span class="uni-rank">05</span><div class="uni-info"><h3>EIE Institute of Education</h3><p>Private; tourism, hospitality, finance.</p></div></article>
            <article class="university-card"><span class="uni-rank">06</span><div class="uni-info"><h3>Global College Malta</h3><p>Business and IT programs with internships.</p></div></article>
            <article class="university-card"><span class="uni-rank">07</span><div class="uni-info"><h3>Idea Academy</h3><p>Specialist creative-industries college.</p></div></article>
            <article class="university-card"><span class="uni-rank">08</span><div class="uni-info"><h3>Pegaso International</h3><p>Private online and blended programs.</p></div></article>
          </div>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Programs</span><h2>Popular programs</h2><p>The most-applied tracks for international students.</p></div>
          <div class="programs-grid">
            <span class="program-chip"><i data-lucide="cpu"></i>Computer Science &amp; IT</span>
            <span class="program-chip"><i data-lucide="briefcase-business"></i>Business &amp; Hospitality</span>
            <span class="program-chip"><i data-lucide="palette"></i>Digital Arts &amp; Gaming</span>
            <span class="program-chip"><i data-lucide="stethoscope"></i>Medicine &amp; Health</span>
            <span class="program-chip"><i data-lucide="scale"></i>Law</span>
            <span class="program-chip"><i data-lucide="globe"></i>International Relations</span>
            <span class="program-chip"><i data-lucide="flask-conical"></i>Pharmacy</span>
            <span class="program-chip"><i data-lucide="languages"></i>TESOL &amp; Education</span>
          </div>
        </div>
      </section>

      <section class="country-section paper">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Costs</span><h2>Tuition and cost of living</h2><p>Indicative ranges &mdash; final figures depend on program and city.</p></div>
          <div class="cost-grid">
            <div class="cost-card"><span class="cost-label">Public university tuition</span><span class="cost-value"><span>&euro;9,000</span> &ndash; <span>&euro;14,000</span></span><span class="cost-note">Per year &middot; for non-EU students at UoM.</span></div>
            <div class="cost-card"><span class="cost-label">Private university tuition</span><span class="cost-value"><span>&euro;10,000</span> &ndash; <span>&euro;18,000</span></span><span class="cost-note">Per year &middot; AUM, STC, and specialist colleges.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Valletta/Sliema</span><span class="cost-value"><span>&euro;1,000</span> &ndash; <span>&euro;1,200</span></span><span class="cost-note">Per month &middot; rent leads the budget.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Other towns</span><span class="cost-value"><span>&euro;800</span> &ndash; <span>&euro;1,000</span></span><span class="cost-note">Per month &middot; Mosta, Birkirkara, Gozo.</span></div>
          </div>
        </div>
      </section>

      <section class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Funding &amp; visa</span>
            <h2>Scholarships, funding &amp; the Malta student visa</h2>
            <p>Two parallel tracks &mdash; how to fund the degree, and how to secure your stay.</p>
          </div>
          <div class="split-info">
            <div class="info-card">
              <h3>Scholarships &amp; funding</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Malta Government Scholarship Scheme (limited; specific programs).</span></li>
                <li><i data-lucide="check"></i><span>University of Malta merit scholarships for high-CGPA applicants.</span></li>
                <li><i data-lucide="check"></i><span>Erasmus+ mobility funding for joint-degree programs.</span></li>
                <li><i data-lucide="check"></i><span>Private-college tuition discounts for early applications.</span></li>
                <li><i data-lucide="check"></i><span>Education loans through major Indian and international lenders.</span></li>
              </ul>
            </div>
            <div class="info-card">
              <h3>Student visa snapshot</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Visa name: <strong>National long-stay visa</strong> + residence permit via Identity Malta.</span></li>
                <li><i data-lucide="check"></i><span>Requires admission letter and proof of paid tuition.</span></li>
                <li><i data-lucide="check"></i><span>Proof of funds: <span>&euro;14</span>&ndash;<span>&euro;19</span>/day for the duration of stay.</span></li>
                <li><i data-lucide="check"></i><span>Standard processing: 4&ndash;6 weeks at consulate; permit on arrival.</span></li>
                <li><i data-lucide="check"></i><span>Work allowance: 20 hours/week after first 90 days of studies.</span></li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left"><span class="eyebrow">Timeline</span><h2>Application timeline</h2><p>A typical roadmap from profile review to arrival.</p></div>
          <div class="timeline-list">
            <div class="timeline-item"><h4>Profile review &amp; shortlist</h4><p>10&ndash;12 months out: public vs private fit, budget.</p></div>
            <div class="timeline-item"><h4>Tests &amp; documents</h4><p>6&ndash;10 months out: IELTS/PTE; transcripts and SOP.</p></div>
            <div class="timeline-item"><h4>Applications</h4><p>Mar&ndash;Aug: most master&rsquo;s and bachelor programs.</p></div>
            <div class="timeline-item"><h4>Offers &amp; deposit</h4><p>May&ndash;Aug: accept offer, pay deposit, finalise loan.</p></div>
            <div class="timeline-item"><h4>National visa</h4><p>Jul&ndash;Sep: book consulate appointment, biometrics, processing.</p></div>
            <div class="timeline-item"><h4>Pre-departure &amp; arrival</h4><p>Sep&ndash;Oct: housing, residence permit, banking, arrival.</p></div>
          </div>
        </div>
      </section>

      <section class="country-cta">
        <div class="container country-cta-grid">
          <div>
            <h2>Plan your Malta application with us</h2>
            <p>From English-medium shortlists to Identity Malta paperwork &mdash; we map a Malta-ready application.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Book a Malta call</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="{{ route('home') }}#destinations"><i data-lucide="compass"></i><span>Compare destinations</span></a>
            </div>
          </div>
          <div class="country-cta-card">
            <h3>What you&rsquo;ll get</h3>
            <p>A 30-minute counselor-led session, free of cost.</p>
            <ul>
              <li><i data-lucide="check"></i><span>UoM vs private fit</span></li>
              <li><i data-lucide="check"></i><span>Cost &amp; scholarship plan</span></li>
              <li><i data-lucide="check"></i><span>Visa &amp; residence roadmap</span></li>
            </ul>
          </div>
        </div>
      </section>
    </main>
@endsection