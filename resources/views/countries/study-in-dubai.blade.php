@php
    $pageTitle = 'Study in Dubai | OneDegreeAdvisory';
    $pageDescription = 'Study in Dubai with OneDegreeAdvisory. International branch campuses, English-medium programs, UAE student visa, and tax-free career pathways.';
    $activeNav = 'destinations';
    $mainId = 'country-main';
@endphp

@extends('layouts.app')

@section('content')
<main id="country-main" class="country-main">
      <section class="country-hero country-hero--dubai" id="top">
        <div class="container country-hero-grid">
          <div class="country-hero-copy">
            <a class="country-back" href="{{ route('home') }}#destinations"><i data-lucide="arrow-left"></i><span>All destinations</span></a>
            <span class="country-flag-lg"><img src="https://flagcdn.com/w160/ae.png" alt="UAE flag"></span>
            <span class="eyebrow">Country guide</span>
            <h1>Study in <span class="gold-text">Dubai</span></h1>
            <p class="country-lede">
              Dubai brings international branch campuses, English-medium programs, and tax-free career prospects together in one of
              the fastest-growing global hubs &mdash; close to home, globally connected, and built around employability.
            </p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Plan your Dubai application</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="#top-universities"><i data-lucide="building-2"></i><span>Top universities</span></a>
            </div>
          </div>
          <aside class="country-snapshot">
            <h2>At a glance</h2>
            <dl>
              <div><dt>Top universities</dt><dd>30+ campuses &amp; institutions</dd></div>
              <div><dt>Main intakes</dt><dd>September, January</dd></div>
              <div><dt>Tuition / year</dt><dd><span data-money="35000" data-currency="AED">AED 35,000</span> &ndash; <span data-money="95000" data-currency="AED">95,000</span></dd></div>
              <div><dt>Living cost / month</dt><dd><span data-money="4000" data-currency="AED">AED 4,000</span> &ndash; <span data-money="8000" data-currency="AED">8,000</span></dd></div>
              <div><dt>Student visa</dt><dd>UAE Student Visa</dd></div>
              <div><dt>Post-study work</dt><dd>Job-seeker &amp; Green Visa</dd></div>
            </dl>
          </aside>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Why Dubai</span><h2>A global hub close to home</h2><p>Internationally accredited campuses, multicultural student life, and direct industry exposure.</p></div>
          <div class="reasons-grid">
            <article class="reason-card"><span class="reason-icon"><i data-lucide="globe"></i></span><h3>Global branch campuses</h3><p>UK, Australian, and Indian universities run accredited campuses with international curricula in Dubai.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="briefcase"></i></span><h3>Tax-free careers</h3><p>Strong job markets in finance, tech, hospitality, logistics, and aviation with zero personal income tax.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="plane"></i></span><h3>Connected to the world</h3><p>One of the world&rsquo;s top travel hubs &mdash; easy access to South Asia, Europe, and Africa.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="languages"></i></span><h3>English-medium</h3><p>Most programs are taught in English; vibrant multicultural student community.</p></article>
          </div>
        </div>
      </section>

      <section id="top-universities" class="country-section alt">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Universities</span><h2>Top universities in Dubai</h2><p>A representative mix of branch campuses and home-grown institutions.</p></div>
          <div class="university-grid">
            <article class="university-card"><span class="uni-rank">01</span><div class="uni-info"><h3>Heriot-Watt University Dubai</h3><p>Scottish university with strong engineering, business, and design programs.</p></div></article>
            <article class="university-card"><span class="uni-rank">02</span><div class="uni-info"><h3>Middlesex University Dubai</h3><p>UK branch campus offering business, computing, media, and law programs.</p></div></article>
            <article class="university-card"><span class="uni-rank">03</span><div class="uni-info"><h3>Curtin University Dubai</h3><p>Australian university with engineering, business, and health sciences offerings.</p></div></article>
            <article class="university-card"><span class="uni-rank">04</span><div class="uni-info"><h3>University of Wollongong in Dubai</h3><p>Long-established Australian campus with business, IT, and engineering tracks.</p></div></article>
            <article class="university-card"><span class="uni-rank">05</span><div class="uni-info"><h3>BITS Pilani Dubai Campus</h3><p>Indian institute of repute &mdash; engineering and computing in Dubai.</p></div></article>
            <article class="university-card"><span class="uni-rank">06</span><div class="uni-info"><h3>Manipal Academy of Higher Education Dubai</h3><p>Indian university campus with engineering, design, and management programs.</p></div></article>
            <article class="university-card"><span class="uni-rank">07</span><div class="uni-info"><h3>MBZUAI</h3><p>Mohamed bin Zayed University of Artificial Intelligence &mdash; graduate-only AI research institute.</p></div></article>
            <article class="university-card"><span class="uni-rank">08</span><div class="uni-info"><h3>American University in Dubai</h3><p>Liberal arts university offering business, communication, architecture, and engineering.</p></div></article>
          </div>
        </div>
      </section>
      <section class="country-section full-bleed" aria-label="Country highlight banner">
        <div class="container">
          <div class="country-band">
            <img src="https://images.unsplash.com/photo-1518684079-3c830dcef090?auto=format&fit=crop&w=2000&q=80" alt="">
            <div class="band-inner">
              <span class="eyebrow">Gateway between East &amp; West</span>
              <h2>Western degrees, taught in a global business hub.</h2>
              <p>Branch campuses of UK, US, and Australian universities &mdash; with tax-free careers and a 5-year post-study Green Visa.</p>
              <div class="band-stats"><div class="band-stat"><strong>70+</strong><span>Universities</span></div><div class="band-stat"><strong>Tax-free</strong><span>Salaries</span></div><div class="band-stat"><strong>5 yrs</strong><span>Green Visa</span></div><div class="band-stat"><strong>EN</strong><span>Medium</span></div></div>
            </div>
          </div>
        </div>
      </section>


      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Programs</span><h2>Popular programs</h2><p>Where Dubai&rsquo;s industries align most strongly with student demand.</p></div>
          <div class="programs-grid">
            <span class="program-chip"><i data-lucide="briefcase-business"></i>Business &amp; Management</span>
            <span class="program-chip"><i data-lucide="cog"></i>Engineering</span>
            <span class="program-chip"><i data-lucide="cpu"></i>Computer Science &amp; AI</span>
            <span class="program-chip"><i data-lucide="hotel"></i>Hospitality &amp; Tourism</span>
            <span class="program-chip"><i data-lucide="truck"></i>Logistics &amp; Supply Chain</span>
            <span class="program-chip"><i data-lucide="landmark"></i>Architecture &amp; Design</span>
            <span class="program-chip"><i data-lucide="plane"></i>Aviation</span>
            <span class="program-chip"><i data-lucide="line-chart"></i>Finance &amp; FinTech</span>
          </div>
        </div>
      </section>

      <section class="country-section alt">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Costs</span><h2>Tuition and cost of living</h2><p>Indicative AED ranges &mdash; varies by campus and accommodation choice.</p></div>
          <div class="cost-grid">
            <div class="cost-card"><span class="cost-label">Undergraduate tuition</span><span class="cost-value"><span data-money="35000" data-currency="AED">AED 35,000</span>&ndash;<span data-money="75000" data-currency="AED">75,000</span></span><span class="cost-note">Per year &middot; branch campuses generally aligned with UK/AU fees.</span></div>
            <div class="cost-card"><span class="cost-label">Postgraduate tuition</span><span class="cost-value"><span data-money="50000" data-currency="AED">AED 50,000</span>&ndash;<span data-money="95000" data-currency="AED">95,000</span></span><span class="cost-note">Per year &middot; MBA and specialist masters at upper end.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Dubai/Abu Dhabi</span><span class="cost-value"><span data-money="4500" data-currency="AED">AED 4,500</span>&ndash;<span data-money="8000" data-currency="AED">8,000</span></span><span class="cost-note">Per month &middot; shared accommodation, food, transport.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Other emirates</span><span class="cost-value"><span data-money="3500" data-currency="AED">AED 3,500</span>&ndash;<span data-money="5500" data-currency="AED">5,500</span></span><span class="cost-note">Per month &middot; Sharjah, Ajman more affordable.</span></div>
          </div>
        </div>
      </section>

      <section class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Funding &amp; visa</span>
            <h2>Scholarships, funding &amp; the Dubai visa</h2>
            <p>Two parallel tracks &mdash; how to fund the degree, and how to secure your stay.</p>
          </div>
          <div class="split-info">
            <div class="info-card">
              <h3>Scholarships &amp; funding</h3>
              <ul>
                <li><i data-lucide="check"></i><span>University merit scholarships &mdash; up to 50% off tuition at many campuses.</span></li>
                <li><i data-lucide="check"></i><span>Sheikh Mohammed bin Rashid Smart Learning grants for select courses.</span></li>
                <li><i data-lucide="check"></i><span>Sports, cultural, and need-based bursaries vary by university.</span></li>
                <li><i data-lucide="check"></i><span>Employer sponsorships available for working professionals.</span></li>
                <li><i data-lucide="check"></i><span>Education loans through Indian and UAE-based lenders.</span></li>
              </ul>
            </div>
            <div class="info-card">
              <h3>Student visa snapshot</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Visa name: <strong>UAE Student Residence Visa</strong>.</span></li>
                <li><i data-lucide="check"></i><span>Sponsored by the university after admission and tuition payment.</span></li>
                <li><i data-lucide="check"></i><span>Valid for 1 year, renewable for the duration of the program.</span></li>
                <li><i data-lucide="check"></i><span>Medical check and Emirates ID required after arrival.</span></li>
                <li><i data-lucide="check"></i><span>Top performers can transition to the 5-year Green Visa post-graduation.</span></li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section class="country-section alt">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Timeline</span><h2>Application timeline</h2><p>A typical 8&ndash;12 month roadmap for a September intake.</p></div>
          <div class="timeline-list">
            <div class="timeline-item"><h4>Profile review &amp; shortlist</h4><p>10&ndash;12 months out: program direction, budget, and target campuses.</p></div>
            <div class="timeline-item"><h4>English proficiency</h4><p>6&ndash;10 months out: IELTS or PTE for non-native speakers.</p></div>
            <div class="timeline-item"><h4>Applications</h4><p>Rolling for most programs &mdash; submit at least 4&ndash;6 months before intake.</p></div>
            <div class="timeline-item"><h4>Offers &amp; tuition deposit</h4><p>2&ndash;4 months out: confirm offer and pay deposit to start visa process.</p></div>
            <div class="timeline-item"><h4>Visa &amp; Emirates ID</h4><p>1&ndash;2 months out: university sponsors student residence visa.</p></div>
            <div class="timeline-item"><h4>Pre-departure &amp; arrival</h4><p>Last 4 weeks: accommodation, banking, insurance, arrival to campus.</p></div>
          </div>
        </div>
      </section>

      <section class="country-cta">
        <div class="container country-cta-grid">
          <div>
            <h2>Plan your Dubai application with us</h2>
            <p>From branch campus selection to UAE residence visa, OneDegree guides you end to end.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Book a Dubai call</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="{{ route('home') }}#destinations"><i data-lucide="compass"></i><span>Compare destinations</span></a>
            </div>
          </div>
          <div class="country-cta-card">
            <h3>What you&rsquo;ll get</h3>
            <p>A 30-minute counselor-led session, free of cost.</p>
            <ul>
              <li><i data-lucide="check"></i><span>Branch campus vs. local fit</span></li>
              <li><i data-lucide="check"></i><span>Scholarship &amp; ROI signal</span></li>
              <li><i data-lucide="check"></i><span>Green Visa &amp; career pathway</span></li>
            </ul>
          </div>
        </div>
      </section>
    </main>
@endsection