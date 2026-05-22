@php
    $pageTitle = 'Study in Belgium | OneDegreeAdvisory';
    $pageDescription = 'Study in Belgium with OneDegreeAdvisory. KU Leuven, Ghent, ULB, tuition, VLIR-UOS scholarships, Type D visa, job-seeker pathway, FAQs.';
    $activeNav = 'destinations';
    $mainId = 'country-main';
@endphp

@extends('layouts.app')

@section('content')
<main id="country-main" class="country-main">
      <section class="country-hero country-hero--belgium" id="top">
        <div class="container country-hero-grid">
          <div class="country-hero-copy">
            <a class="country-back" href="{{ route('home') }}#destinations"><i data-lucide="arrow-left"></i><span>All destinations</span></a>
            <span class="country-flag-lg"><img src="https://flagcdn.com/w160/be.png" alt="Belgium flag"></span>
            <span class="eyebrow">Country guide</span>
            <h1>Study in <span class='gold-text'>Belgium</span></h1>
            <p class="country-lede">Belgium hosts some of Europe&rsquo;s oldest research universities &mdash; with affordable tuition, English-taught master&rsquo;s, and a 12-month job-seeker residence in the heart of the EU.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Plan your Belgium application</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="#top-universities"><i data-lucide="building-2"></i><span>Top universities</span></a>
            </div>
          </div>
          <aside class="country-snapshot">
            <h2>At a glance</h2>
            <dl>
              <div><dt>Top universities</dt><dd>10+ research universities</dd></div>
              <div><dt>Main intakes</dt><dd>September, February</dd></div>
              <div><dt>Tuition / year</dt><dd><span data-money="1000" data-currency="EUR">&euro;1,000</span> &ndash; <span data-money="6000" data-currency="EUR">&euro;6,000</span></dd></div>
              <div><dt>Living cost / month</dt><dd><span data-money="900" data-currency="EUR">&euro;900</span> &ndash; <span data-money="1300" data-currency="EUR">&euro;1,300</span></dd></div>
              <div><dt>Student visa</dt><dd>Type D student visa</dd></div>
              <div><dt>Post-study work</dt><dd>Job-seeker residence &middot; 12 months</dd></div>
            </dl>
          </aside>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Why Belgium</span><h2>World-class research. EU institutions. Low tuition.</h2><p>Affordable degrees in the EU capital, with strong English-taught programs and a clear job-seeker pathway.</p></div>
          <div class="reasons-grid">
            <article class="reason-card"><span class="reason-icon"><i data-lucide="award"></i></span><h3>Top research</h3><p>KU Leuven and Ghent consistently in the global top 100 for research.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="banknote"></i></span><h3>Low tuition</h3><p>Most public master&rsquo;s programs sit at <span data-money="1000" data-currency="EUR">&euro;1,000</span>&ndash;<span data-money="4000" data-currency="EUR">&euro;4,000</span>/year.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="languages"></i></span><h3>English-taught</h3><p>200+ English master&rsquo;s programs across Flemish and French universities.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="briefcase"></i></span><h3>EU institutions</h3><p>Brussels hosts EU institutions, NATO, and global multinationals.</p></article>
          </div>
        </div>
      </section>

      <section class="country-section full-bleed" aria-label="Country highlight banner">
        <div class="container">
          <div class="country-band">
            <img src="https://images.unsplash.com/photo-1503416997304-7f8bf166c121?auto=format&fit=crop&w=2000&q=80" alt="">
            <div class="band-inner">
              <span class="eyebrow">EU capital, research-led</span>
              <h2>KU Leuven, Ghent, ULB &mdash; research at the EU&rsquo;s core.</h2>
              <p>Belgium&rsquo;s universities pair centuries of academic depth with practical, English-taught master&rsquo;s &mdash; and a 12-month job-seeker residence in the EU capital.</p>
              <div class="band-stats"><div class="band-stat"><strong>10+</strong><span>Research unis</span></div><div class="band-stat"><strong>200+</strong><span>English master&rsquo;s</span></div><div class="band-stat"><strong>12 mo</strong><span>Job-seeker</span></div><div class="band-stat"><strong>EU</strong><span>Capital</span></div></div>
            </div>
          </div>
        </div>
      </section>

      <section id="top-universities" class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left"><span class="eyebrow">Universities</span><h2>Top universities in Belgium</h2><p>A representative shortlist across research universities and specialist institutions.</p></div>
          <div class="university-grid">
            <article class="university-card"><span class="uni-rank">01</span><div class="uni-info"><h3>KU Leuven</h3><p>Belgium&rsquo;s top-ranked university &mdash; sciences, engineering, medicine.</p></div></article>
            <article class="university-card"><span class="uni-rank">02</span><div class="uni-info"><h3>Ghent University</h3><p>Broad research strength across humanities and sciences.</p></div></article>
            <article class="university-card"><span class="uni-rank">03</span><div class="uni-info"><h3>Universit&eacute; libre de Bruxelles (ULB)</h3><p>Brussels-based; politics, economics, sciences.</p></div></article>
            <article class="university-card"><span class="uni-rank">04</span><div class="uni-info"><h3>Universit&eacute; catholique de Louvain (UCLouvain)</h3><p>Sister university to KU Leuven; medicine, law, sciences.</p></div></article>
            <article class="university-card"><span class="uni-rank">05</span><div class="uni-info"><h3>University of Antwerp</h3><p>Strong in sciences, design, and business.</p></div></article>
            <article class="university-card"><span class="uni-rank">06</span><div class="uni-info"><h3>Vrije Universiteit Brussel (VUB)</h3><p>Dutch-medium counterpart of ULB in Brussels.</p></div></article>
            <article class="university-card"><span class="uni-rank">07</span><div class="uni-info"><h3>University of Li&egrave;ge</h3><p>French-medium; engineering, veterinary, sciences.</p></div></article>
            <article class="university-card"><span class="uni-rank">08</span><div class="uni-info"><h3>Vlerick Business School</h3><p>Top Belgian business school with MBA tracks.</p></div></article>
          </div>
        </div>
      </section>

      <section class="country-section">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Programs</span><h2>Popular programs</h2><p>The most-applied tracks for international students.</p></div>
          <div class="programs-grid">
            <span class="program-chip"><i data-lucide="flask-conical"></i>Sciences &amp; Biotech</span>
            <span class="program-chip"><i data-lucide="cog"></i>Engineering</span>
            <span class="program-chip"><i data-lucide="cpu"></i>Computer Science</span>
            <span class="program-chip"><i data-lucide="briefcase-business"></i>Business &amp; Management</span>
            <span class="program-chip"><i data-lucide="scale"></i>Law &amp; Policy</span>
            <span class="program-chip"><i data-lucide="globe"></i>International Relations</span>
            <span class="program-chip"><i data-lucide="palette"></i>Design &amp; Arts</span>
            <span class="program-chip"><i data-lucide="stethoscope"></i>Medicine &amp; Veterinary</span>
          </div>
        </div>
      </section>

      <section class="country-section paper">
        <div class="container">
          <div class="section-head"><span class="eyebrow">Costs</span><h2>Tuition and cost of living</h2><p>Indicative ranges &mdash; final figures depend on program and city.</p></div>
          <div class="cost-grid">
            <div class="cost-card"><span class="cost-label">Public university tuition</span><span class="cost-value"><span data-money="1000" data-currency="EUR">&euro;1,000</span> &ndash; <span data-money="4200" data-currency="EUR">&euro;4,200</span></span><span class="cost-note">Per year &middot; non-EU; lower for EU students.</span></div>
            <div class="cost-card"><span class="cost-label">Specialist / MBA tuition</span><span class="cost-value"><span data-money="6000" data-currency="EUR">&euro;6,000</span> &ndash; <span data-money="30000" data-currency="EUR">&euro;30,000</span></span><span class="cost-note">Per year &middot; private schools and MBAs higher.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Brussels</span><span class="cost-value"><span data-money="1000" data-currency="EUR">&euro;1,000</span> &ndash; <span data-money="1300" data-currency="EUR">&euro;1,300</span></span><span class="cost-note">Per month &middot; capital-city rents.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Other cities</span><span class="cost-value"><span data-money="800" data-currency="EUR">&euro;800</span> &ndash; <span data-money="1100" data-currency="EUR">&euro;1,100</span></span><span class="cost-note">Per month &middot; Leuven, Ghent, Antwerp.</span></div>
          </div>
        </div>
      </section>

      <section class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Funding &amp; visa</span>
            <h2>Scholarships, funding &amp; the Belgium student visa</h2>
            <p>Two parallel tracks &mdash; how to fund the degree, and how to secure your stay.</p>
          </div>
          <div class="split-info">
            <div class="info-card">
              <h3>Scholarships &amp; funding</h3>
              <ul>
                <li><i data-lucide="check"></i><span>VLIR-UOS Master&rsquo;s Scholarships for students from developing countries.</span></li>
                <li><i data-lucide="check"></i><span>ARES Scholarships for French-language programs (Wallonia-Brussels).</span></li>
                <li><i data-lucide="check"></i><span>University-specific awards (e.g. KU Leuven Science@Leuven Scholarship).</span></li>
                <li><i data-lucide="check"></i><span>Erasmus Mundus Joint Master&rsquo;s for top candidates.</span></li>
                <li><i data-lucide="check"></i><span>Education loans through major Indian and international lenders.</span></li>
              </ul>
            </div>
            <div class="info-card">
              <h3>Student visa snapshot</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Visa name: <strong>Type D student visa</strong> via the Belgian consulate.</span></li>
                <li><i data-lucide="check"></i><span>Requires admission letter and proof of paid tuition.</span></li>
                <li><i data-lucide="check"></i><span>Proof of funds: roughly <span data-money="730" data-currency="EUR">&euro;730</span>/month for the duration of study.</span></li>
                <li><i data-lucide="check"></i><span>Standard processing: 4&ndash;8 weeks; biometrics at VFS Global.</span></li>
                <li><i data-lucide="check"></i><span>Work allowance: 20 hours/week during term, full-time in summer.</span></li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left"><span class="eyebrow">Timeline</span><h2>Application timeline</h2><p>A typical roadmap from profile review to arrival.</p></div>
          <div class="timeline-list">
            <div class="timeline-item"><h4>Profile review &amp; shortlist</h4><p>12&ndash;14 months out: Flemish vs French region, language fit.</p></div>
            <div class="timeline-item"><h4>Tests &amp; documents</h4><p>8&ndash;12 months out: IELTS/TOEFL; transcripts, SOP, LORs.</p></div>
            <div class="timeline-item"><h4>Applications</h4><p>Oct&ndash;Apr: most master&rsquo;s programs; UG via direct application.</p></div>
            <div class="timeline-item"><h4>Offers &amp; deposit</h4><p>Mar&ndash;Jun: accept offer, pay deposit, finalise scholarship.</p></div>
            <div class="timeline-item"><h4>Type D visa</h4><p>Jun&ndash;Aug: submit at consulate, biometrics, processing.</p></div>
            <div class="timeline-item"><h4>Pre-departure &amp; arrival</h4><p>Aug&ndash;Sep: housing, commune registration, insurance, arrival.</p></div>
          </div>
        </div>
      </section>

      <section class="country-cta">
        <div class="container country-cta-grid">
          <div>
            <h2>Plan your Belgium application with us</h2>
            <p>From Flemish vs French region fit to Type D visa &mdash; we map a Belgium-ready application.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Book a Belgium call</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="{{ route('home') }}#destinations"><i data-lucide="compass"></i><span>Compare destinations</span></a>
            </div>
          </div>
          <div class="country-cta-card">
            <h3>What you&rsquo;ll get</h3>
            <p>A 30-minute counselor-led session, free of cost.</p>
            <ul>
              <li><i data-lucide="check"></i><span>KU Leuven vs ULB fit</span></li>
              <li><i data-lucide="check"></i><span>VLIR-UOS scholarship plan</span></li>
              <li><i data-lucide="check"></i><span>Job-seeker visa roadmap</span></li>
            </ul>
          </div>
        </div>
      </section>
    </main>
@endsection