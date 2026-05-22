@php
    $pageTitle = 'Study in New Zealand | OneDegreeAdvisory';
    $pageDescription = 'Study in New Zealand with OneDegreeAdvisory. Eight globally-ranked universities, tuition, scholarships, fee-paying student visa, post-study work, PR.';
    $activeNav = 'destinations';
    $mainId = 'country-main';
@endphp

@extends('layouts.app')

@section('content')
<main id="country-main" class="country-main">
      <section class="country-hero country-hero--new-zealand" id="top">
        <div class="container country-hero-grid">
          <div class="country-hero-copy">
            <a class="country-back" href="{{ route('home') }}#destinations"><i data-lucide="arrow-left"></i><span>All destinations</span></a>
            <span class="country-flag-lg"><img src="https://flagcdn.com/w160/nz.png" alt="New Zealand flag"></span>
            <span class="eyebrow">Country guide</span>
            <h1>Study in <span class='gold-text'>New Zealand</span></h1>
            <p class="country-lede">New Zealand offers eight globally-ranked universities, strong work rights during study, and post-study work visas of up to 3 years &mdash; in one of the world&rsquo;s safest, most welcoming countries.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Plan your New Zealand application</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="#top-universities"><i data-lucide="building-2"></i><span>Top universities</span></a>
            </div>
          </div>
          <aside class="country-snapshot">
            <h2>At a glance</h2>
            <dl>
              <div><dt>Top universities</dt><dd>8 globally ranked</dd></div>
              <div><dt>Main intakes</dt><dd>February, July</dd></div>
              <div><dt>Tuition / year</dt><dd><span data-money="28000" data-currency="NZD">NZD 28,000</span> &ndash; <span data-money="50000" data-currency="NZD">NZD 50,000</span></dd></div>
              <div><dt>Living cost / month</dt><dd><span data-money="1500" data-currency="NZD">NZD 1,500</span> &ndash; <span data-money="2200" data-currency="NZD">NZD 2,200</span></dd></div>
              <div><dt>Student visa</dt><dd>Fee-paying student visa</dd></div>
              <div><dt>Post-study work</dt><dd>Up to 3 years</dd></div>
            </dl>
          </aside>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Why New Zealand</span><h2>Globally ranked. Safe. Migration-friendly.</h2><p>Quality degrees, real work rights, and one of the clearest skilled migration paths in the OECD.</p></div>
          <div class="reasons-grid">
            <article class="reason-card"><span class="reason-icon"><i data-lucide="award"></i></span><h3>All 8 in QS top 500</h3><p>Every Kiwi university ranks in the global QS top 500.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="briefcase"></i></span><h3>Post-study work</h3><p>Up to 3 years on the Post-Study Work Visa.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="shield-check"></i></span><h3>Safe &amp; welcoming</h3><p>Among the safest countries in the world &mdash; consistently top of peace indices.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="map"></i></span><h3>Skilled migration</h3><p>Clear residence pathway via the Green List occupations.</p></article>
          </div>
        </div>
      </section>

      <section class="country-section full-bleed" aria-label="Country highlight banner">
        <div class="container">
          <div class="country-band">
            <img src="https://images.unsplash.com/photo-1564507592333-c60657eea523?auto=format&fit=crop&w=2000&q=80" alt="">
            <div class="band-inner">
              <span class="eyebrow">8 globally-ranked unis</span>
              <h2>Quality degrees, 3-year stay-back, safest study destinations.</h2>
              <p>Every Kiwi university sits in the QS top 500 &mdash; with a 3-year post-study work visa for master&rsquo;s graduates and a clear PR pathway via Green List occupations.</p>
              <div class="band-stats"><div class="band-stat"><strong>8/8</strong><span>QS top 500</span></div><div class="band-stat"><strong>3 yrs</strong><span>Post-study work</span></div><div class="band-stat"><strong>Green List</strong><span>PR pathway</span></div><div class="band-stat"><strong>EN</strong><span>Medium</span></div></div>
            </div>
          </div>
        </div>
      </section>

      <section id="top-universities" class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left"><span class="eyebrow">Universities</span><h2>Top universities in New Zealand</h2><p>A representative shortlist across research universities and specialist institutions.</p></div>
          <div class="university-grid">
            <article class="university-card"><span class="uni-rank">01</span><div class="uni-info"><h3>University of Auckland</h3><p>QS top 100 &mdash; medicine, engineering, business.</p></div></article>
            <article class="university-card"><span class="uni-rank">02</span><div class="uni-info"><h3>University of Otago</h3><p>Oldest NZ university &mdash; medicine, dentistry, sciences.</p></div></article>
            <article class="university-card"><span class="uni-rank">03</span><div class="uni-info"><h3>Victoria University of Wellington</h3><p>Government-adjacent &mdash; law, public policy, sciences.</p></div></article>
            <article class="university-card"><span class="uni-rank">04</span><div class="uni-info"><h3>University of Canterbury</h3><p>Christchurch-based engineering, forestry, sciences.</p></div></article>
            <article class="university-card"><span class="uni-rank">05</span><div class="uni-info"><h3>Massey University</h3><p>Multi-campus; agriculture, veterinary sciences, aviation.</p></div></article>
            <article class="university-card"><span class="uni-rank">06</span><div class="uni-info"><h3>University of Waikato</h3><p>Strong in management, computing, Maori studies.</p></div></article>
            <article class="university-card"><span class="uni-rank">07</span><div class="uni-info"><h3>Lincoln University</h3><p>Specialist in agriculture, land use, and environment.</p></div></article>
            <article class="university-card"><span class="uni-rank">08</span><div class="uni-info"><h3>Auckland University of Technology (AUT)</h3><p>Industry-aligned design, business, and tech programs.</p></div></article>
          </div>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Programs</span><h2>Popular programs</h2><p>The most-applied tracks for international students.</p></div>
          <div class="programs-grid">
            <span class="program-chip"><i data-lucide="cpu"></i>Computer Science &amp; IT</span>
            <span class="program-chip"><i data-lucide="cog"></i>Engineering</span>
            <span class="program-chip"><i data-lucide="briefcase-business"></i>Business &amp; MBA</span>
            <span class="program-chip"><i data-lucide="stethoscope"></i>Medicine &amp; Health Sciences</span>
            <span class="program-chip"><i data-lucide="leaf"></i>Agriculture &amp; Environment</span>
            <span class="program-chip"><i data-lucide="plane"></i>Aviation</span>
            <span class="program-chip"><i data-lucide="scale"></i>Law</span>
            <span class="program-chip"><i data-lucide="palette"></i>Design &amp; Architecture</span>
          </div>
        </div>
      </section>

      <section class="country-section paper">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Costs</span><h2>Tuition and cost of living</h2><p>Indicative ranges &mdash; final figures depend on program and city.</p></div>
          <div class="cost-grid">
            <div class="cost-card"><span class="cost-label">Undergraduate tuition</span><span class="cost-value"><span data-money="28000" data-currency="NZD">NZD 28,000</span> &ndash; <span data-money="45000" data-currency="NZD">NZD 45,000</span></span><span class="cost-note">Per year &middot; medicine higher.</span></div>
            <div class="cost-card"><span class="cost-label">Postgraduate tuition</span><span class="cost-value"><span data-money="30000" data-currency="NZD">NZD 30,000</span> &ndash; <span data-money="50000" data-currency="NZD">NZD 50,000</span></span><span class="cost-note">Per year &middot; MBA at the upper end.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Auckland/Wellington</span><span class="cost-value"><span data-money="1800" data-currency="NZD">NZD 1,800</span> &ndash; <span data-money="2200" data-currency="NZD">NZD 2,200</span></span><span class="cost-note">Per month &middot; major-city rents.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Other cities</span><span class="cost-value"><span data-money="1500" data-currency="NZD">NZD 1,500</span> &ndash; <span data-money="1800" data-currency="NZD">NZD 1,800</span></span><span class="cost-note">Per month &middot; Christchurch, Hamilton, Dunedin.</span></div>
          </div>
        </div>
      </section>

      <section class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Funding &amp; visa</span>
            <h2>Scholarships, funding &amp; the New Zealand student visa</h2>
            <p>Two parallel tracks &mdash; how to fund the degree, and how to secure your stay.</p>
          </div>
          <div class="split-info">
            <div class="info-card">
              <h3>Scholarships &amp; funding</h3>
              <ul>
                <li><i data-lucide="check"></i><span>New Zealand Excellence Awards for Indian students (NZ<span data-money="5000" data-currency="USD">$5,000</span>&ndash;<span data-money="10000" data-currency="USD">$10,000</span>).</span></li>
                <li><i data-lucide="check"></i><span>Manaaki New Zealand Scholarships (fully-funded; select countries).</span></li>
                <li><i data-lucide="check"></i><span>University-specific entrance scholarships and PhD funding.</span></li>
                <li><i data-lucide="check"></i><span>Education NZ pathway scholarships for specific programs.</span></li>
                <li><i data-lucide="check"></i><span>Education loans through major Indian and international lenders.</span></li>
              </ul>
            </div>
            <div class="info-card">
              <h3>Student visa snapshot</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Visa name: <strong>Fee-paying student visa</strong> via Immigration New Zealand.</span></li>
                <li><i data-lucide="check"></i><span>Requires an offer letter and proof of paid tuition.</span></li>
                <li><i data-lucide="check"></i><span>Proof of funds: <span data-money="20000" data-currency="NZD">NZD 20,000</span>/year for living costs.</span></li>
                <li><i data-lucide="check"></i><span>Standard processing: 4&ndash;8 weeks.</span></li>
                <li><i data-lucide="check"></i><span>Work allowance: up to 20 hours/week during term, full-time during scheduled breaks.</span></li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left"><span class="eyebrow">Timeline</span><h2>Application timeline</h2><p>A typical roadmap from profile review to arrival.</p></div>
          <div class="timeline-list">
            <div class="timeline-item"><h4>Profile review &amp; shortlist</h4><p>10&ndash;12 months out: program direction, budget, target city.</p></div>
            <div class="timeline-item"><h4>Tests &amp; documents</h4><p>6&ndash;10 months out: IELTS/PTE; GMAT/GRE if program requires.</p></div>
            <div class="timeline-item"><h4>Applications</h4><p>Aug&ndash;Apr: most postgraduate programs.</p></div>
            <div class="timeline-item"><h4>Offers &amp; deposit</h4><p>Oct&ndash;May: accept offer, pay deposit, finalise loan.</p></div>
            <div class="timeline-item"><h4>Student visa</h4><p>May&ndash;Jul: submit application, biometrics if needed.</p></div>
            <div class="timeline-item"><h4>Pre-departure &amp; arrival</h4><p>Jul&ndash;Aug: housing, banking, insurance, flights, arrival.</p></div>
          </div>
        </div>
      </section>

      <section class="country-cta">
        <div class="container country-cta-grid">
          <div>
            <h2>Plan your New Zealand application with us</h2>
            <p>From Green List-aligned shortlists to a clean visa submission &mdash; OneDegree maps an NZ-ready application.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Book a New Zealand call</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="{{ route('home') }}#destinations"><i data-lucide="compass"></i><span>Compare destinations</span></a>
            </div>
          </div>
          <div class="country-cta-card">
            <h3>What you&rsquo;ll get</h3>
            <p>A 30-minute counselor-led session, free of cost.</p>
            <ul>
              <li><i data-lucide="check"></i><span>Auckland vs Wellington fit</span></li>
              <li><i data-lucide="check"></i><span>Green List occupation check</span></li>
              <li><i data-lucide="check"></i><span>PSW &amp; PR roadmap</span></li>
            </ul>
          </div>
        </div>
      </section>
    </main>
@endsection