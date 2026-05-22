@php
    $pageTitle = 'Study in Georgia | OneDegreeAdvisory';
    $pageDescription = 'Study in Georgia with OneDegreeAdvisory. NMC-recognised English-medium MD, affordable tuition, top medical universities, D3 visa, FAQs.';
    $activeNav = 'destinations';
    $mainId = 'country-main';
@endphp

@extends('layouts.app')

@section('content')
<main id="country-main" class="country-main">
      <section class="country-hero country-hero--georgia" id="top">
        <div class="container country-hero-grid">
          <div class="country-hero-copy">
            <a class="country-back" href="{{ route('home') }}#destinations"><i data-lucide="arrow-left"></i><span>All destinations</span></a>
            <span class="country-flag-lg"><img src="https://flagcdn.com/w160/ge.png" alt="Georgia flag"></span>
            <span class="eyebrow">Country guide</span>
            <h1>Study in <span class='gold-text'>Georgia</span></h1>
            <p class="country-lede">Georgia is a fast-rising study destination for medicine and dentistry &mdash; with English-medium MD programs at MCI/NMC-listed universities, low tuition, and visa-on-arrival access for Indian students.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Plan your Georgia application</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="#top-universities"><i data-lucide="building-2"></i><span>Top universities</span></a>
            </div>
          </div>
          <aside class="country-snapshot">
            <h2>At a glance</h2>
            <dl>
              <div><dt>Top universities</dt><dd>20+ international medical schools</dd></div>
              <div><dt>Main intakes</dt><dd>September, February</dd></div>
              <div><dt>Tuition / year</dt><dd><span data-money="4000" data-currency="USD">$4,000</span> &ndash; <span data-money="9000" data-currency="USD">$9,000</span></dd></div>
              <div><dt>Living cost / month</dt><dd><span data-money="300" data-currency="USD">$300</span> &ndash; <span data-money="500" data-currency="USD">$500</span></dd></div>
              <div><dt>Student visa</dt><dd>D3 study visa</dd></div>
              <div><dt>Post-study work</dt><dd>Stay-back via work permit</dd></div>
            </dl>
          </aside>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Why Georgia</span><h2>Affordable English-medium MD on the EU&rsquo;s doorstep.</h2><p>Recognised medical degrees, low tuition, and easy entry for Indian students.</p></div>
          <div class="reasons-grid">
            <article class="reason-card"><span class="reason-icon"><i data-lucide="stethoscope"></i></span><h3>MCI / NMC recognised</h3><p>Top Georgian medical universities are on the Indian recognition list.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="banknote"></i></span><h3>Affordable tuition</h3><p>MD programs typically <span data-money="4000" data-currency="USD">$4,000</span>&ndash;<span data-money="8000" data-currency="USD">$8,000</span>/year.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="languages"></i></span><h3>English-medium MD</h3><p>Full 6-year MD programs taught in English for international students.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="plane"></i></span><h3>Easy entry</h3><p>Visa-on-arrival or simplified D3 visa for Indian students.</p></article>
          </div>
        </div>
      </section>

      <section class="country-section full-bleed" aria-label="Country highlight banner">
        <div class="container">
          <div class="country-band">
            <img src="https://images.unsplash.com/photo-1539037116277-4db20889f2d4?auto=format&fit=crop&w=2000&q=80" alt="">
            <div class="band-inner">
              <span class="eyebrow">Medical-degree value pick</span>
              <h2>MCI / NMC recognised. English-medium. <span data-money="5000" data-currency="USD" data-money-hint="k">$5k</span>/year tuition.</h2>
              <p>Georgia&rsquo;s English-medium MD programs are listed by the National Medical Commission of India &mdash; offering recognised degrees at one of the lowest price points in Europe.</p>
              <div class="band-stats"><div class="band-stat"><strong><span data-money="5000" data-currency="USD" data-money-hint="k">$5k</span>/yr</strong><span>Median MD tuition</span></div><div class="band-stat"><strong>NMC</strong><span>Recognised</span></div><div class="band-stat"><strong>6 yrs</strong><span>MD duration</span></div><div class="band-stat"><strong>EN</strong><span>Medium</span></div></div>
            </div>
          </div>
        </div>
      </section>

      <section id="top-universities" class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left"><span class="eyebrow">Universities</span><h2>Top universities in Georgia</h2><p>A representative shortlist across research universities and specialist institutions.</p></div>
          <div class="university-grid">
            <article class="university-card"><span class="uni-rank">01</span><div class="uni-info"><h3>Tbilisi State Medical University (TSMU)</h3><p>Oldest medical university in Georgia &mdash; NMC-listed.</p></div></article>
            <article class="university-card"><span class="uni-rank">02</span><div class="uni-info"><h3>New Vision University</h3><p>Modern English-medium MD program in Tbilisi.</p></div></article>
            <article class="university-card"><span class="uni-rank">03</span><div class="uni-info"><h3>David Tvildiani Medical University</h3><p>Top private medical university with US-style curriculum.</p></div></article>
            <article class="university-card"><span class="uni-rank">04</span><div class="uni-info"><h3>European University</h3><p>English-medium MD &amp; dentistry programs.</p></div></article>
            <article class="university-card"><span class="uni-rank">05</span><div class="uni-info"><h3>Caucasus International University</h3><p>Multi-disciplinary; MD, dentistry, pharmacy.</p></div></article>
            <article class="university-card"><span class="uni-rank">06</span><div class="uni-info"><h3>Ivane Javakhishvili Tbilisi State University</h3><p>Flagship public university with broad programs.</p></div></article>
            <article class="university-card"><span class="uni-rank">07</span><div class="uni-info"><h3>Georgian National University SEU</h3><p>Business, IT, and law tracks in English.</p></div></article>
            <article class="university-card"><span class="uni-rank">08</span><div class="uni-info"><h3>BAU International University Batumi</h3><p>Coastal campus with MD &amp; dentistry programs.</p></div></article>
          </div>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Programs</span><h2>Popular programs</h2><p>The most-applied tracks for international students.</p></div>
          <div class="programs-grid">
            <span class="program-chip"><i data-lucide="stethoscope"></i>MD</span>
            <span class="program-chip"><i data-lucide="stethoscope"></i>Dentistry</span>
            <span class="program-chip"><i data-lucide="flask-conical"></i>Pharmacy</span>
            <span class="program-chip"><i data-lucide="cpu"></i>Computer Science</span>
            <span class="program-chip"><i data-lucide="briefcase-business"></i>Business &amp; IT</span>
            <span class="program-chip"><i data-lucide="scale"></i>Law</span>
            <span class="program-chip"><i data-lucide="globe"></i>International Relations</span>
            <span class="program-chip"><i data-lucide="hotel"></i>Tourism &amp; Hospitality</span>
          </div>
        </div>
      </section>

      <section class="country-section paper">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Costs</span><h2>Tuition and cost of living</h2><p>Indicative ranges &mdash; final figures depend on program and city.</p></div>
          <div class="cost-grid">
            <div class="cost-card"><span class="cost-label">MD English-medium</span><span class="cost-value"><span data-money="4500" data-currency="USD">$4,500</span> &ndash; <span data-money="9000" data-currency="USD">$9,000</span></span><span class="cost-note">Per year &middot; 6-year program.</span></div>
            <div class="cost-card"><span class="cost-label">Dentistry</span><span class="cost-value"><span data-money="5000" data-currency="USD">$5,000</span> &ndash; <span data-money="9000" data-currency="USD">$9,000</span></span><span class="cost-note">Per year &middot; 5-year program.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Tbilisi</span><span class="cost-value"><span data-money="400" data-currency="USD">$400</span> &ndash; <span data-money="500" data-currency="USD">$500</span></span><span class="cost-note">Per month &middot; rent + food + transport.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Other cities</span><span class="cost-value"><span data-money="300" data-currency="USD">$300</span> &ndash; <span data-money="400" data-currency="USD">$400</span></span><span class="cost-note">Per month &middot; Batumi, Kutaisi, Gori.</span></div>
          </div>
        </div>
      </section>

      <section class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Funding &amp; visa</span>
            <h2>Scholarships, funding &amp; the Georgia student visa</h2>
            <p>Two parallel tracks &mdash; how to fund the degree, and how to secure your stay.</p>
          </div>
          <div class="split-info">
            <div class="info-card">
              <h3>Scholarships &amp; funding</h3>
              <ul>
                <li><i data-lucide="check"></i><span>University-specific tuition reductions for high-NEET-scoring candidates.</span></li>
                <li><i data-lucide="check"></i><span>Sibling and family discounts at several private MD universities.</span></li>
                <li><i data-lucide="check"></i><span>Early-bird tuition discounts for first-intake applications.</span></li>
                <li><i data-lucide="check"></i><span>NMC-recognition list maintained by India&rsquo;s health ministry.</span></li>
                <li><i data-lucide="check"></i><span>Education loans through major Indian and international lenders.</span></li>
              </ul>
            </div>
            <div class="info-card">
              <h3>Student visa snapshot</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Visa name: <strong>D3 study visa</strong> via the Georgian e-visa portal or consulate.</span></li>
                <li><i data-lucide="check"></i><span>Requires admission letter and a Georgian institutional invitation.</span></li>
                <li><i data-lucide="check"></i><span>Proof of funds: <span data-money="2500" data-currency="USD">$2,500</span>&ndash;<span data-money="4000" data-currency="USD">$4,000</span> for the first year.</span></li>
                <li><i data-lucide="check"></i><span>Standard processing: 5&ndash;10 working days online; 30 days at consulate.</span></li>
                <li><i data-lucide="check"></i><span>Work allowance: part-time allowed alongside studies.</span></li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left"><span class="eyebrow">Timeline</span><h2>Application timeline</h2><p>A typical roadmap from profile review to arrival.</p></div>
          <div class="timeline-list">
            <div class="timeline-item"><h4>Profile review &amp; shortlist</h4><p>10&ndash;12 months out: MD university shortlist + NMC verification.</p></div>
            <div class="timeline-item"><h4>Documents &amp; tests</h4><p>6&ndash;10 months out: NEET prep + IELTS where required.</p></div>
            <div class="timeline-item"><h4>Applications</h4><p>Mar&ndash;Aug: most MD universities accept rolling applications.</p></div>
            <div class="timeline-item"><h4>Offers &amp; invitation</h4><p>May&ndash;Aug: admission letter + invitation for visa.</p></div>
            <div class="timeline-item"><h4>D3 visa</h4><p>Aug&ndash;Sep: e-visa or consulate; expedited if invitation in hand.</p></div>
            <div class="timeline-item"><h4>Pre-departure &amp; arrival</h4><p>Sep: flights, hostel allocation, university orientation.</p></div>
          </div>
        </div>
      </section>

      <section class="country-cta">
        <div class="container country-cta-grid">
          <div>
            <h2>Plan your Georgia application with us</h2>
            <p>From NMC-list verification to D3 visa &mdash; OneDegree maps a Georgia-ready application.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Book a Georgia call</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="{{ route('home') }}#destinations"><i data-lucide="compass"></i><span>Compare destinations</span></a>
            </div>
          </div>
          <div class="country-cta-card">
            <h3>What you&rsquo;ll get</h3>
            <p>A 30-minute counselor-led session, free of cost.</p>
            <ul>
              <li><i data-lucide="check"></i><span>NMC-list MD shortlist</span></li>
              <li><i data-lucide="check"></i><span>Tbilisi vs Batumi fit</span></li>
              <li><i data-lucide="check"></i><span>D3 visa roadmap</span></li>
            </ul>
          </div>
        </div>
      </section>
    </main>
@endsection