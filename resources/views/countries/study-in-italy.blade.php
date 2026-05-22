@php
    $pageTitle = 'Study in Italy | OneDegreeAdvisory';
    $pageDescription = 'Study in Italy with OneDegreeAdvisory. Heritage universities, English-taught programs, affordable public tuition, and a 12-month post-study residence.';
    $activeNav = 'destinations';
    $mainId = 'country-main';
@endphp

@extends('layouts.app')

@section('content')
<main id="country-main" class="country-main">
      <section class="country-hero country-hero--italy" id="top">
        <div class="container country-hero-grid">
          <div class="country-hero-copy">
            <a class="country-back" href="{{ route('home') }}#destinations"><i data-lucide="arrow-left"></i><span>All destinations</span></a>
            <span class="country-flag-lg"><img src="https://flagcdn.com/w160/it.png" alt="Italy flag"></span>
            <span class="eyebrow">Country guide</span>
            <h1>Study in <span class="gold-text">Italy</span></h1>
            <p class="country-lede">Italy pairs centuries-old heritage universities with leading programs in design, fashion, architecture, and business &mdash; with English-taught options and some of the lowest public tuition in Western Europe.</p>
            <div class="country-actions"><a class="btn btn-primary" href="{{ route('contact') }}"><span>Plan your Italy application</span><i data-lucide="arrow-up-right"></i></a><a class="btn btn-ghost" href="#top-universities"><i data-lucide="building-2"></i><span>Top universities</span></a></div>
          </div>
          <aside class="country-snapshot"><h2>At a glance</h2>
            <dl>
              <div><dt>Top universities</dt><dd>25+ globally ranked</dd></div>
              <div><dt>Main intakes</dt><dd>September, February</dd></div>
              <div><dt>Tuition / year</dt><dd><span data-money="900" data-currency="EUR">&euro;900</span> &ndash; <span data-money="20000" data-currency="EUR">&euro;20,000</span></dd></div>
              <div><dt>Living cost / month</dt><dd><span data-money="700" data-currency="EUR">&euro;700</span> &ndash; <span data-money="1200" data-currency="EUR">&euro;1,200</span></dd></div>
              <div><dt>Student visa</dt><dd>National D Visa</dd></div>
              <div><dt>Post-study work</dt><dd>12-month residence permit</dd></div>
            </dl>
          </aside>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Why Italy</span><h2>Design, heritage, value</h2><p>Where deep academic tradition meets some of the world&rsquo;s most influential creative industries.</p></div>
          <div class="reasons-grid">
            <article class="reason-card"><span class="reason-icon"><i data-lucide="landmark"></i></span><h3>Historic universities</h3><p>The University of Bologna, founded in 1088, is the oldest continuously operating university in the world.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="palette"></i></span><h3>Design &amp; fashion</h3><p>Milan and Florence host some of the world&rsquo;s top fashion, design, and architecture schools.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="piggy-bank"></i></span><h3>Affordable public tuition</h3><p>Public universities charge income-linked fees starting around <span data-money="900" data-currency="EUR">&euro;900</span>/year &mdash; with strong DSU regional grants.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="languages"></i></span><h3>English-taught programs</h3><p>Hundreds of English-medium bachelor&rsquo;s and master&rsquo;s programs &mdash; especially in business and STEM.</p></article>
          </div>
        </div>
      </section>

      <section id="top-universities" class="country-section alt">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Universities</span><h2>Top universities in Italy</h2><p>A representative shortlist of public and private institutions.</p></div>
          <div class="university-grid">
            <article class="university-card"><span class="uni-rank">01</span><div class="uni-info"><h3>Politecnico di Milano</h3><p>Italy&rsquo;s leading technical university in engineering, design, and architecture.</p></div></article>
            <article class="university-card"><span class="uni-rank">02</span><div class="uni-info"><h3>University of Bologna</h3><p>Western world&rsquo;s oldest university &mdash; broad coverage including law, medicine, and humanities.</p></div></article>
            <article class="university-card"><span class="uni-rank">03</span><div class="uni-info"><h3>Sapienza University of Rome</h3><p>Largest university in Europe by enrolment &mdash; strong in classics, archaeology, and engineering.</p></div></article>
            <article class="university-card"><span class="uni-rank">04</span><div class="uni-info"><h3>University of Milan (UniMi)</h3><p>Major research university with leading programs in life sciences, law, and humanities.</p></div></article>
            <article class="university-card"><span class="uni-rank">05</span><div class="uni-info"><h3>Bocconi University</h3><p>Italy&rsquo;s premier private institution for economics, finance, and management.</p></div></article>
            <article class="university-card"><span class="uni-rank">06</span><div class="uni-info"><h3>University of Padua</h3><p>One of the world&rsquo;s oldest universities with strengths in sciences and medicine.</p></div></article>
            <article class="university-card"><span class="uni-rank">07</span><div class="uni-info"><h3>University of Pisa</h3><p>Historic Tuscan university and home of the elite Scuola Normale Superiore.</p></div></article>
            <article class="university-card"><span class="uni-rank">08</span><div class="uni-info"><h3>Politecnico di Torino</h3><p>Engineering-focused with strong automotive, aerospace, and ICT programs.</p></div></article>
          </div>
        </div>
      </section>
      <section class="country-section full-bleed" aria-label="Country highlight banner">
        <div class="container">
          <div class="country-band">
            <img src="https://images.unsplash.com/photo-1531572753322-ad063cecc140?auto=format&fit=crop&w=2000&q=80" alt="">
            <div class="band-inner">
              <span class="eyebrow">Design, fashion &amp; the arts</span>
              <h2>Centuries of academia, in cities you&rsquo;ll never forget.</h2>
              <p>From Bologna (Europe&rsquo;s oldest university) to Milan&rsquo;s design schools &mdash; with EU-recognised degrees and low tuition.</p>
              <div class="band-stats"><div class="band-stat"><strong><span data-money="900" data-currency="EUR" data-money-hint="k">&euro;0.9k</span></strong><span>Min. tuition</span></div><div class="band-stat"><strong>12 mo</strong><span>Stay-back</span></div><div class="band-stat"><strong>90+</strong><span>Universities</span></div><div class="band-stat"><strong>EU</strong><span>Member state</span></div></div>
            </div>
          </div>
        </div>
      </section>


      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Programs</span><h2>Popular programs</h2><p>Where Italy&rsquo;s strengths attract the most international applicants.</p></div>
          <div class="programs-grid">
            <span class="program-chip"><i data-lucide="palette"></i>Design &amp; Fashion</span>
            <span class="program-chip"><i data-lucide="landmark"></i>Architecture</span>
            <span class="program-chip"><i data-lucide="briefcase-business"></i>Business &amp; Economics</span>
            <span class="program-chip"><i data-lucide="cog"></i>Engineering</span>
            <span class="program-chip"><i data-lucide="utensils"></i>Culinary &amp; Hospitality</span>
            <span class="program-chip"><i data-lucide="book"></i>Art History &amp; Humanities</span>
            <span class="program-chip"><i data-lucide="stethoscope"></i>Medicine (IMAT)</span>
            <span class="program-chip"><i data-lucide="line-chart"></i>Finance &amp; Management</span>
          </div>
        </div>
      </section>

      <section class="country-section alt">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Costs</span><h2>Tuition and cost of living</h2><p>Euro ranges &mdash; public universities use income-linked (ISEE) fee bands.</p></div>
          <div class="cost-grid">
            <div class="cost-card"><span class="cost-label">Public university tuition</span><span class="cost-value"><span data-money="900" data-currency="EUR">&euro;900</span>&ndash;<span data-money="4000" data-currency="EUR">&euro;4,000</span></span><span class="cost-note">Per year &middot; income-linked; can be lower with DSU regional aid.</span></div>
            <div class="cost-card"><span class="cost-label">Private university tuition</span><span class="cost-value"><span data-money="6000" data-currency="EUR">&euro;6,000</span>&ndash;<span data-money="20000" data-currency="EUR">&euro;20,000</span></span><span class="cost-note">Per year &middot; Bocconi, LUISS, and specialist design schools at upper end.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Milan/Rome</span><span class="cost-value"><span data-money="900" data-currency="EUR">&euro;900</span>&ndash;<span data-money="1200" data-currency="EUR">&euro;1,200</span></span><span class="cost-note">Per month &middot; major cities tend to be costliest.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Other cities</span><span class="cost-value"><span data-money="700" data-currency="EUR">&euro;700</span>&ndash;<span data-money="900" data-currency="EUR">&euro;900</span></span><span class="cost-note">Per month &middot; Bologna, Padua, Turin, smaller university towns.</span></div>
          </div>
        </div>
      </section>

      <section class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Funding &amp; visa</span>
            <h2>Scholarships, funding &amp; the Italy visa</h2>
            <p>Two parallel tracks &mdash; how to fund the degree, and how to secure your stay.</p>
          </div>
          <div class="split-info">
            <div class="info-card">
              <h3>Scholarships &amp; funding</h3>
              <ul>
                <li><i data-lucide="check"></i><span>MAECI &mdash; Italian government scholarships for international students.</span></li>
                <li><i data-lucide="check"></i><span>Regional DSU grants &mdash; need and merit-based stipends with fee waivers.</span></li>
                <li><i data-lucide="check"></i><span>Bocconi merit awards and need-based partial fee waivers.</span></li>
                <li><i data-lucide="check"></i><span>Politecnico di Milano international scholarships.</span></li>
                <li><i data-lucide="check"></i><span>Erasmus+ for exchange and partner-university mobility.</span></li>
              </ul>
            </div>
            <div class="info-card">
              <h3>Student visa snapshot</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Visa name: <strong>National Visa (Type D)</strong> for studies.</span></li>
                <li><i data-lucide="check"></i><span>Pre-enrolment via Universitaly portal mandatory before visa application.</span></li>
                <li><i data-lucide="check"></i><span>Proof of funds: ~<span data-money="6000" data-currency="EUR">&euro;6,000</span>+ per year, plus health insurance.</span></li>
                <li><i data-lucide="check"></i><span>Permesso di Soggiorno (residence permit) within 8 days of arrival.</span></li>
                <li><i data-lucide="check"></i><span>Work allowance: up to 20 hours/week alongside studies.</span></li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section class="country-section alt">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Timeline</span><h2>Application timeline</h2><p>A typical 10&ndash;14 month roadmap for a September intake.</p></div>
          <div class="timeline-list">
            <div class="timeline-item"><h4>Profile review &amp; shortlist</h4><p>12&ndash;14 months out: program direction; check English/Italian language requirements.</p></div>
            <div class="timeline-item"><h4>Tests &amp; portfolios</h4><p>8&ndash;12 months out: IELTS/TOEFL; portfolio for design; IMAT for medicine.</p></div>
            <div class="timeline-item"><h4>Universitaly pre-enrolment</h4><p>April&ndash;July: pre-enrol via Universitaly after admission.</p></div>
            <div class="timeline-item"><h4>Visa appointment</h4><p>June&ndash;August: book embassy slot, gather documents, attend interview.</p></div>
            <div class="timeline-item"><h4>Permit on arrival</h4><p>Within 8 days of arrival: submit Permesso di Soggiorno application.</p></div>
            <div class="timeline-item"><h4>Enrolment &amp; orientation</h4><p>September: complete enrolment, codice fiscale, health registration.</p></div>
          </div>
        </div>
      </section>

      <section class="country-cta">
        <div class="container country-cta-grid">
          <div>
            <h2>Plan your Italy application with us</h2>
            <p>From Universitaly pre-enrolment to DSU grants, OneDegree maps your full Italy plan.</p>
            <div class="country-actions"><a class="btn btn-primary" href="{{ route('contact') }}"><span>Book an Italy call</span><i data-lucide="arrow-up-right"></i></a><a class="btn btn-ghost" href="{{ route('home') }}#destinations"><i data-lucide="compass"></i><span>Compare destinations</span></a></div>
          </div>
          <div class="country-cta-card"><h3>What you&rsquo;ll get</h3><p>A 30-minute counselor-led session, free of cost.</p><ul><li><i data-lucide="check"></i><span>Public vs. private fit</span></li><li><i data-lucide="check"></i><span>DSU &amp; MAECI signal</span></li><li><i data-lucide="check"></i><span>Universitaly &amp; visa map</span></li></ul></div>
        </div>
      </section>
    </main>
@endsection