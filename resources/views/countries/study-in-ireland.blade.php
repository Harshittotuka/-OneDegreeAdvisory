@php
    $pageTitle = 'Study in Ireland | One Degree Advisory';
    $pageDescription = 'Study in Ireland with One Degree Advisory. Top universities, tuition, scholarships, Stamp 2 study permission, Third Level Graduate Scheme, intakes, costs, FAQs.';
    $activeNav = 'destinations';
    $mainId = 'country-main';
@endphp

@extends('layouts.app')

@section('content')
<main id="country-main" class="country-main">
      <section class="country-hero country-hero--ireland" id="top">
        <div class="container country-hero-grid">
          <div class="country-hero-copy">
            <a class="country-back" href="{{ route('home') }}#destinations"><i data-lucide="arrow-left"></i><span>All destinations</span></a>
            <span class="country-flag-lg"><img src="https://flagcdn.com/w160/ie.png" alt="Ireland flag"></span>
            <span class="eyebrow">Country guide</span>
            <h1>Study in <span class="gold-text">Ireland</span></h1>
            <p class="country-lede">
              English-speaking, EU-based, and home to global tech and pharma HQs &mdash; Ireland combines world-class universities
              with a generous 2-year post-study work pathway and a welcoming international community.
            </p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Start your journey</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="{{ route('contact') }}"><i data-lucide="phone-call"></i><span>Talk to an expert</span></a>
            </div>
          </div>
          <aside class="country-snapshot">
            <h2>At a glance</h2>
            <dl>
              <div><dt>Top universities</dt><dd>8 universities &middot; 14 IoTs</dd></div>
              <div><dt>Main intakes</dt><dd>September, January</dd></div>
              <div><dt>Tuition / year</dt><dd><span>&euro;10,000</span> &ndash; <span>&euro;35,000</span></dd></div>
              <div><dt>Living cost / month</dt><dd><span>&euro;900</span> &ndash; <span>&euro;1,500</span></dd></div>
              <div><dt>Student visa</dt><dd>Stamp 2 &middot; D study visa</dd></div>
              <div><dt>Post-study work</dt><dd>Third Level Graduate &middot; up to 2 years</dd></div>
            </dl>
          </aside>
        </div>
      </section>

      <section class="country-stat-strip">
        <div class="container">
          <div class="stat-strip-grid">
            <div class="stat-strip-item"><span class="stat-icon"><i data-lucide="globe-2"></i></span><div class="stat-text"><span class="stat-value">35,000+</span><span class="stat-label">Intl. students</span></div></div>
            <div class="stat-strip-item"><span class="stat-icon"><i data-lucide="building"></i></span><div class="stat-text"><span class="stat-value">9 of Top 10</span><span class="stat-label">Tech firms HQ&rsquo;d</span></div></div>
            <div class="stat-strip-item"><span class="stat-icon"><i data-lucide="languages"></i></span><div class="stat-text"><span class="stat-value">English</span><span class="stat-label">Medium</span></div></div>
            <div class="stat-strip-item"><span class="stat-icon"><i data-lucide="briefcase"></i></span><div class="stat-text"><span class="stat-value">2 Years</span><span class="stat-label">Stay back (Master&rsquo;s)</span></div></div>
            <div class="stat-strip-item"><span class="stat-icon"><i data-lucide="euro"></i></span><div class="stat-text"><span class="stat-value">EU Member</span><span class="stat-label">Schengen-friendly</span></div></div>
            <div class="stat-strip-item"><span class="stat-icon"><i data-lucide="calendar-check"></i></span><div class="stat-text"><span class="stat-value">2 Intakes</span><span class="stat-label">Sep / Jan</span></div></div>
          </div>
        </div>
      </section>

      <nav class="country-nav" aria-label="On-page sections">
        <div class="container">
          <div class="country-nav-track">
            <a href="#why">Why Ireland</a>
            <a href="#education-system">Education system</a>
            <a href="#top-universities">Top universities</a>
            <a href="#top-courses">Top courses</a>
            <a href="#costs">Costs</a>
            <a href="#scholarships">Scholarships</a>
            <a href="#intakes">Intakes</a>
            <a href="#cities">Popular cities</a>
            <a href="#visa-process">Visa process</a>
            <a href="#work">Work &amp; PR</a>
            <a href="#student-life">Student life</a>
            <a href="#pros-cons">Pros &amp; cons</a>
            <a href="#faq">FAQs</a>
          </div>
        </div>
      </nav>

      <section id="why" class="country-section">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Why Ireland</span>
            <h2>An English-speaking gateway to Europe</h2>
            <p>Top tech and pharma employers, EU access, and a 2-year stay-back for master&rsquo;s graduates.</p>
          </div>
          <div class="reasons-grid">
            <article class="reason-card"><span class="reason-icon"><i data-lucide="award"></i></span><h3>Globally ranked universities</h3><p>Trinity College Dublin and UCD consistently rank in the global top 200, with strong subject-level positions.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="cpu"></i></span><h3>Tech &amp; pharma capital</h3><p>European HQs of Google, Meta, Apple, Microsoft, Pfizer, and J&amp;J create deep industry pipelines.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="briefcase"></i></span><h3>2-year stay-back</h3><p>The Third Level Graduate Programme lets master&rsquo;s graduates stay 2 years to find skilled work.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="users"></i></span><h3>Welcoming &amp; safe</h3><p>One of Europe&rsquo;s safest countries with a friendly, English-speaking culture and active Indian community.</p></article>
          </div>
        </div>
      </section>

      <section id="education-system" class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left">
            <span class="eyebrow">Academic structure</span>
            <h2>Ireland&rsquo;s education system</h2>
            <p>Degrees follow the Bologna model with Honours, Master&rsquo;s, and Doctorate levels.</p>
          </div>
          <div class="edu-system-grid">
            <article class="edu-level-card">
              <span class="edu-level-tag">Undergraduate</span>
              <h3>Honours Bachelor&rsquo;s</h3>
              <p class="edu-level-meta">3&ndash;4 years</p>
              <p>Applications via CAO for school leavers; international applicants apply directly through universities or via agents.</p>
            </article>
            <article class="edu-level-card">
              <span class="edu-level-tag">Postgraduate</span>
              <h3>Master&rsquo;s (MSc / MA / MBA)</h3>
              <p class="edu-level-meta">1&ndash;2 years</p>
              <p>Most taught master&rsquo;s are one year. MBA programs typically span 12&ndash;18 months. Strong specialisation tracks.</p>
            </article>
            <article class="edu-level-card">
              <span class="edu-level-tag">Doctorate</span>
              <h3>PhD</h3>
              <p class="edu-level-meta">3&ndash;4 years</p>
              <p>Funded research positions through Science Foundation Ireland, Irish Research Council, and industry-linked CRTs.</p>
            </article>
            <article class="edu-level-card">
              <span class="edu-level-tag">Pre-degree</span>
              <h3>Foundation &amp; Pathway</h3>
              <p class="edu-level-meta">6&ndash;12 months</p>
              <p>International foundation and pre-master&rsquo;s pathways at major universities for academic and English bridging.</p>
            </article>
          </div>
        </div>
      </section>

      <section class="country-section full-bleed" aria-label="Ireland universities banner">
        <div class="container">
          <div class="country-band">
            <img src="https://images.unsplash.com/photo-1549918864-48ac978761a4?auto=format&fit=crop&w=2000&q=80" alt="">
            <div class="band-inner">
              <span class="eyebrow">EU&rsquo;s English-speaking hub</span>
              <h2>Tech, pharma &amp; finance &mdash; on the doorstep of Europe.</h2>
              <p>Ireland hosts European HQs for Google, Meta, Microsoft, Pfizer, and J&amp;J &mdash; with a clear 2-year stay-back for master&rsquo;s grads.</p>
              <div class="band-stats">
                <div class="band-stat"><strong>8</strong><span>Public universities</span></div>
                <div class="band-stat"><strong>2 yrs</strong><span>Master&rsquo;s stay-back</span></div>
                <div class="band-stat"><strong>EU</strong><span>Member state</span></div>
                <div class="band-stat"><strong>English</strong><span>Medium</span></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="top-universities" class="country-section">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Universities</span>
            <h2>Top universities in Ireland</h2>
            <p>Eight public universities and a strong network of technological universities.</p>
          </div>
          <div class="uni-image-grid">
            <article class="uni-img-card">
              <div class="uni-photo"><span class="uni-rank-badge">QS #87</span><img src="https://images.unsplash.com/photo-1549890762-0a3f8933bc76?auto=format&fit=crop&w=900&q=80" alt="Trinity College Dublin" loading="lazy"></div>
              <div class="uni-body">
                <h3>Trinity College Dublin (TCD)</h3>
                <span class="uni-loc"><i data-lucide="map-pin"></i>Dublin</span>
                <div class="uni-meta"><span>IELTS 6.5+</span><span>UG &middot; PG &middot; PhD</span></div>
              </div>
            </article>
            <article class="uni-img-card">
              <div class="uni-photo"><span class="uni-rank-badge">QS #126</span><img src="https://images.unsplash.com/photo-1590856029826-c7a73142bbf1?auto=format&fit=crop&w=900&q=80" alt="University College Dublin" loading="lazy"></div>
              <div class="uni-body">
                <h3>University College Dublin (UCD)</h3>
                <span class="uni-loc"><i data-lucide="map-pin"></i>Dublin</span>
                <div class="uni-meta"><span>IELTS 6.5+</span><span>Business &middot; Engineering</span></div>
              </div>
            </article>
            <article class="uni-img-card">
              <div class="uni-photo"><span class="uni-rank-badge">QS #284</span><img src="https://images.unsplash.com/photo-1531168556467-80aace0d0144?auto=format&fit=crop&w=900&q=80" alt="University of Galway" loading="lazy"></div>
              <div class="uni-body">
                <h3>University of Galway</h3>
                <span class="uni-loc"><i data-lucide="map-pin"></i>Galway</span>
                <div class="uni-meta"><span>IELTS 6.5</span><span>Life sciences &middot; Med devices</span></div>
              </div>
            </article>
            <article class="uni-img-card">
              <div class="uni-photo"><span class="uni-rank-badge">QS #292</span><img src="https://images.unsplash.com/photo-1571260899304-425eee4c7efc?auto=format&fit=crop&w=900&q=80" alt="University College Cork" loading="lazy"></div>
              <div class="uni-body">
                <h3>University College Cork (UCC)</h3>
                <span class="uni-loc"><i data-lucide="map-pin"></i>Cork</span>
                <div class="uni-meta"><span>IELTS 6.5</span><span>Food sci &middot; Pharmacy</span></div>
              </div>
            </article>
            <article class="uni-img-card">
              <div class="uni-photo"><span class="uni-rank-badge">QS #421</span><img src="https://images.unsplash.com/photo-1576495199011-eb94736d05d6?auto=format&fit=crop&w=900&q=80" alt="Dublin City University" loading="lazy"></div>
              <div class="uni-body">
                <h3>Dublin City University (DCU)</h3>
                <span class="uni-loc"><i data-lucide="map-pin"></i>Dublin</span>
                <div class="uni-meta"><span>IELTS 6.5</span><span>Computing &middot; Business</span></div>
              </div>
            </article>
            <article class="uni-img-card">
              <div class="uni-photo"><span class="uni-rank-badge">QS #421</span><img src="https://images.unsplash.com/photo-1607013251379-e6eecfffe234?auto=format&fit=crop&w=900&q=80" alt="University of Limerick" loading="lazy"></div>
              <div class="uni-body">
                <h3>University of Limerick (UL)</h3>
                <span class="uni-loc"><i data-lucide="map-pin"></i>Limerick</span>
                <div class="uni-meta"><span>IELTS 6.5</span><span>Co-op &middot; Engineering</span></div>
              </div>
            </article>
            <article class="uni-img-card">
              <div class="uni-photo"><span class="uni-rank-badge">QS #801</span><img src="https://images.unsplash.com/photo-1606761568499-6d2451b23c66?auto=format&fit=crop&w=900&q=80" alt="Maynooth University" loading="lazy"></div>
              <div class="uni-body">
                <h3>Maynooth University</h3>
                <span class="uni-loc"><i data-lucide="map-pin"></i>Maynooth</span>
                <div class="uni-meta"><span>IELTS 6.5</span><span>CS &middot; Geography &middot; Education</span></div>
              </div>
            </article>
            <article class="uni-img-card">
              <div class="uni-photo"><span class="uni-rank-badge">QS #258</span><img src="https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&w=900&q=80" alt="RCSI University" loading="lazy"></div>
              <div class="uni-body">
                <h3>RCSI University of Medicine &amp; Health Sciences</h3>
                <span class="uni-loc"><i data-lucide="map-pin"></i>Dublin</span>
                <div class="uni-meta"><span>IELTS 6.5+</span><span>Medicine &middot; Pharmacy</span></div>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section id="top-courses" class="country-section alt">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Courses</span>
            <h2>Top courses to study in Ireland</h2>
            <p>Industry-aligned programs with the strongest post-study hiring signals.</p>
          </div>
          <div class="courses-grid">
            <article class="course-card">
              <span class="course-tag">Master&rsquo;s</span>
              <h3>MSc Computer Science &amp; AI</h3>
              <p>TCD, UCD, DCU, and University of Galway offer specialist AI, software, and CS conversion tracks.</p>
              <div class="course-foot"><span><i data-lucide="clock"></i>12 months</span><span><i data-lucide="banknote"></i><span>&euro;18k</span>&ndash;<span>&euro;30k</span></span></div>
            </article>
            <article class="course-card">
              <span class="course-tag">Master&rsquo;s</span>
              <h3>MSc Data &amp; Business Analytics</h3>
              <p>Quantitative tracks aligned with FAANG, fintech, and consulting hiring in Dublin.</p>
              <div class="course-foot"><span><i data-lucide="clock"></i>12 months</span><span><i data-lucide="banknote"></i><span>&euro;18k</span>&ndash;<span>&euro;26k</span></span></div>
            </article>
            <article class="course-card">
              <span class="course-tag">MBA</span>
              <h3>Master of Business Administration</h3>
              <p>UCD Smurfit, TCD, and DCU offer one-year MBA programs with strong corporate connections.</p>
              <div class="course-foot"><span><i data-lucide="clock"></i>12&ndash;18 months</span><span><i data-lucide="banknote"></i><span>&euro;25k</span>&ndash;<span>&euro;42k</span></span></div>
            </article>
            <article class="course-card">
              <span class="course-tag">Master&rsquo;s</span>
              <h3>MSc Pharmaceutical Sciences</h3>
              <p>Strong specialist programs at UCC, TCD, and RCSI &mdash; aligned with Ireland&rsquo;s pharma cluster.</p>
              <div class="course-foot"><span><i data-lucide="clock"></i>12&ndash;24 months</span><span><i data-lucide="banknote"></i><span>&euro;16k</span>&ndash;<span>&euro;28k</span></span></div>
            </article>
            <article class="course-card">
              <span class="course-tag">Master&rsquo;s</span>
              <h3>MSc International Business</h3>
              <p>Globally-mobile MSc IB programs at UCD Smurfit and TCD with study-abroad terms.</p>
              <div class="course-foot"><span><i data-lucide="clock"></i>12 months</span><span><i data-lucide="banknote"></i><span>&euro;20k</span>&ndash;<span>&euro;30k</span></span></div>
            </article>
            <article class="course-card">
              <span class="course-tag">Bachelor&rsquo;s</span>
              <h3>BSc Engineering (multiple)</h3>
              <p>4-year programs with co-op placements at University of Limerick, UCD, TCD, and UCC.</p>
              <div class="course-foot"><span><i data-lucide="clock"></i>4 years</span><span><i data-lucide="banknote"></i><span>&euro;15k</span>&ndash;<span>&euro;25k</span>/yr</span></div>
            </article>
          </div>
        </div>
      </section>

      <section id="costs" class="country-section paper">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Costs</span>
            <h2>Tuition and cost of living</h2>
            <p>Ranges in Euros &mdash; final figures depend on program and city.</p>
          </div>
          <div class="cost-grid">
            <div class="cost-card"><span class="cost-label">Undergraduate tuition</span><span class="cost-value"><span>&euro;10,000</span>&ndash;<span>&euro;25,000</span></span><span class="cost-note">Per year &middot; higher for medicine and dentistry.</span></div>
            <div class="cost-card"><span class="cost-label">Postgraduate tuition</span><span class="cost-value"><span>&euro;12,000</span>&ndash;<span>&euro;35,000</span></span><span class="cost-note">Per year &middot; MBA at the upper end; many MSc programs <span>&euro;15</span>&ndash;<span>22k</span>.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Dublin</span><span class="cost-value"><span>&euro;1,200</span>&ndash;<span>&euro;1,500</span></span><span class="cost-note">Per month &middot; rent is the biggest line item.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Cork / Galway / Limerick</span><span class="cost-value"><span>&euro;900</span>&ndash;<span>&euro;1,200</span></span><span class="cost-note">Per month &middot; meaningfully cheaper than Dublin.</span></div>
          </div>
        </div>
      </section>

      <section id="scholarships" class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Funding &amp; visa</span>
            <h2>Scholarships, funding &amp; the D visa</h2>
            <p>Two parallel tracks &mdash; how to fund the degree, and how to secure your stay.</p>
          </div>
          <div class="split-info">
            <div class="info-card">
              <h3>Scholarships &amp; funding</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Government of Ireland International Education Scholarships.</span></li>
                <li><i data-lucide="check"></i><span>University-specific awards (e.g. TCD Global Excellence, UCD Global, UCC Centenary).</span></li>
                <li><i data-lucide="check"></i><span>Walsh Fellowships and Science Foundation Ireland postgraduate funding.</span></li>
                <li><i data-lucide="check"></i><span>Country-specific awards (e.g. Claddagh, Naughton Fellowships for select profiles).</span></li>
                <li><i data-lucide="check"></i><span>Education loans through major Indian and international lenders.</span></li>
              </ul>
            </div>
            <div class="info-card">
              <h3>Student visa snapshot</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Visa name: <strong>D study visa</strong> &middot; residence as Stamp 2.</span></li>
                <li><i data-lucide="check"></i><span>Apply via AVATS online portal; biometrics at VFS Global.</span></li>
                <li><i data-lucide="check"></i><span>Proof of funds: at least <span>&euro;10,000</span> per year of study (plus tuition).</span></li>
                <li><i data-lucide="check"></i><span>Standard processing: 6&ndash;8 weeks &mdash; apply early.</span></li>
                <li><i data-lucide="check"></i><span>Work allowance: 20 hours/week in term; 40 hours during holidays.</span></li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section id="intakes" class="country-section">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Intakes</span>
            <h2>Intake calendar</h2>
            <p>September is the main intake; January is growing for postgraduate programs.</p>
          </div>
          <table class="intake-table">
            <thead>
              <tr><th>Intake</th><th>Months</th><th>Apply by</th><th>Best for</th></tr>
            </thead>
            <tbody>
              <tr>
                <td><span class="pill">Autumn</span></td>
                <td>September &mdash; October</td>
                <td>CAO UG: Feb; PG: rolling, Mar&ndash;Jul</td>
                <td>Primary intake &mdash; widest program and scholarship choice.</td>
              </tr>
              <tr>
                <td><span class="pill secondary">Spring</span></td>
                <td>January &mdash; February</td>
                <td>Sep &ndash; Nov</td>
                <td>Selected PG programs (CS, business, analytics).</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section id="work" class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Work &amp; settlement</span>
            <h2>Work rights and post-study pathways</h2>
            <p>From in-study employment to long-term residence in Ireland.</p>
          </div>
          <div class="work-pr-grid">
            <article class="work-pr-card">
              <span class="work-pr-icon"><i data-lucide="briefcase"></i></span>
              <h3>Third Level Graduate Programme</h3>
              <span class="work-pr-tag">Up to 2 years</span>
              <p>12 months for Honours Bachelor&rsquo;s grads, 24 months for Master&rsquo;s and PhDs &mdash; full work rights, no sponsorship needed.</p>
            </article>
            <article class="work-pr-card">
              <span class="work-pr-icon"><i data-lucide="hourglass"></i></span>
              <h3>In-study work</h3>
              <span class="work-pr-tag">20 hrs/week</span>
              <p>20 hours/week in term, 40 hours/week during May&ndash;August and Dec&ndash;Jan holidays.</p>
            </article>
            <article class="work-pr-card">
              <span class="work-pr-icon"><i data-lucide="zap"></i></span>
              <h3>Critical Skills Employment Permit</h3>
              <span class="work-pr-tag">Fast-track</span>
              <p>For roles on the Critical Skills list &mdash; eligible to apply for residency after 2 years.</p>
            </article>
            <article class="work-pr-card">
              <span class="work-pr-icon"><i data-lucide="key-round"></i></span>
              <h3>Long-Term Residence</h3>
              <span class="work-pr-tag">5 years</span>
              <p>Reckonable residence on a work permit can lead to Stamp 4 / long-term residency and eventually citizenship.</p>
            </article>
          </div>
        </div>
      </section>

      <section id="student-life" class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left">
            <span class="eyebrow">Student life</span>
            <h2>Living and studying in Ireland</h2>
            <p>What daily life looks like beyond the lecture hall.</p>
          </div>
          <div class="life-grid">
            <article class="life-card"><span class="life-icon"><i data-lucide="train"></i></span><h3>Getting around</h3><p>Leap Card discounts public transport in major cities. Intercity rail and Bus &Eacute;ireann cover the country well.</p></article>
            <article class="life-card"><span class="life-icon"><i data-lucide="utensils"></i></span><h3>Food &amp; culture</h3><p>Diverse food scene with strong Indian, halal, and vegetarian options in Dublin, Cork, and Galway.</p></article>
            <article class="life-card"><span class="life-icon"><i data-lucide="cloud-sun"></i></span><h3>Climate</h3><p>Mild, rainy climate &mdash; rarely too hot or too cold. A waterproof jacket is essential year-round.</p></article>
            <article class="life-card"><span class="life-icon"><i data-lucide="shield-check"></i></span><h3>Healthcare</h3><p>Private medical insurance is required for the student visa &mdash; budget <span>&euro;150</span>&ndash;<span>&euro;500</span>/year.</p></article>
          </div>
        </div>
      </section>

      <section id="pros-cons" class="country-section alt">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Honest read</span>
            <h2>Pros &amp; trade-offs</h2>
            <p>What students love &mdash; and what to plan for.</p>
          </div>
          <div class="pros-cons-grid">
            <div class="pc-card pros">
              <h3><i data-lucide="thumbs-up"></i>Pros</h3>
              <ul>
                <li><i data-lucide="check"></i><span>English-speaking, EU country with low language friction.</span></li>
                <li><i data-lucide="check"></i><span>Strong industry presence in tech, pharma, and finance.</span></li>
                <li><i data-lucide="check"></i><span>2-year stay-back for master&rsquo;s with full work rights.</span></li>
                <li><i data-lucide="check"></i><span>Safer than most large European cities; welcoming culture.</span></li>
                <li><i data-lucide="check"></i><span>Clear PR pathway via Critical Skills Employment Permit.</span></li>
              </ul>
            </div>
            <div class="pc-card cons">
              <h3><i data-lucide="alert-triangle"></i>Trade-offs</h3>
              <ul>
                <li><i data-lucide="dot"></i><span>Dublin rental market is tight &mdash; secure accommodation early.</span></li>
                <li><i data-lucide="dot"></i><span>Tuition is high relative to other EU options.</span></li>
                <li><i data-lucide="dot"></i><span>Smaller number of universities compared to UK or Germany.</span></li>
                <li><i data-lucide="dot"></i><span>Visa processing can take 6&ndash;8 weeks &mdash; plan timelines tightly.</span></li>
                <li><i data-lucide="dot"></i><span>Cool, wet weather isn&rsquo;t for everyone.</span></li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      <section id="timeline" class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left">
            <span class="eyebrow">Timeline</span>
            <h2>Application timeline</h2>
            <p>A typical 10&ndash;14 month roadmap for a September intake.</p>
          </div>
          <div class="timeline-list">
            <div class="timeline-item"><h4>Profile review &amp; shortlist</h4><p>12&ndash;14 months out: clarify program, budget, and target universities.</p></div>
            <div class="timeline-item"><h4>Tests &amp; documents</h4><p>8&ndash;12 months out: IELTS / PTE; transcripts, SOP, LORs, CV.</p></div>
            <div class="timeline-item"><h4>Applications</h4><p>Oct&ndash;Mar: most postgraduate programs; CAO for UG (Indian students typically apply directly to universities).</p></div>
            <div class="timeline-item"><h4>Offers &amp; funding</h4><p>Jan&ndash;May: confirm offer, pay deposit, finalise scholarship and loan paperwork.</p></div>
            <div class="timeline-item"><h4>D visa via AVATS</h4><p>May&ndash;Aug: submit application, biometrics, await processing (6&ndash;8 weeks).</p></div>
            <div class="timeline-item"><h4>Pre-departure &amp; arrival</h4><p>Aug&ndash;Sep: accommodation, insurance, flights, GNIB registration on arrival.</p></div>
          </div>
        </div>
      </section>

      <section id="faq" class="country-section alt">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">FAQs</span>
            <h2>Common questions about studying in Ireland</h2>
            <p>Short answers to what students and parents ask us most often.</p>
          </div>
          <div class="faq-list">
            <details class="faq-item">
              <summary>Is Ireland a good destination for Indian students?</summary>
              <div class="faq-answer">Yes &mdash; Ireland is one of the fastest-growing study destinations for Indian students thanks to English-medium teaching, strong tech and pharma industries, and the 2-year stay-back for master&rsquo;s graduates.</div>
            </details>
            <details class="faq-item">
              <summary>How much does it cost to study in Ireland?</summary>
              <div class="faq-answer">Expect <span>&euro;10,000</span>&ndash;<span>&euro;35,000</span>/year in tuition plus <span>&euro;11,000</span>&ndash;<span>&euro;18,000</span>/year for living. Dublin is meaningfully more expensive than Cork, Galway, or Limerick.</div>
            </details>
            <details class="faq-item">
              <summary>Do I need IELTS to study in Ireland?</summary>
              <div class="faq-answer">Most universities accept IELTS 6.0&ndash;6.5 for UG and 6.5&ndash;7.0 for PG. PTE Academic and TOEFL iBT are also widely accepted.</div>
            </details>
            <details class="faq-item">
              <summary>Can I work while studying in Ireland?</summary>
              <div class="faq-answer">Yes &mdash; 20 hours/week during term and 40 hours/week during designated holiday periods (May&ndash;Aug and Dec&ndash;Jan).</div>
            </details>
            <details class="faq-item">
              <summary>What is the Third Level Graduate Programme?</summary>
              <div class="faq-answer">A post-study stay-back permission &mdash; 12 months for Honours Bachelor&rsquo;s grads and 24 months for Master&rsquo;s/PhD grads, with full work rights and no sponsorship requirement.</div>
            </details>
            <details class="faq-item">
              <summary>Can I get permanent residency in Ireland after studying?</summary>
              <div class="faq-answer">Yes, indirectly. Most students transition to a Critical Skills or General Employment Permit, and after 5 years of reckonable residence can apply for Stamp 4 / long-term residence.</div>
            </details>
            <details class="faq-item">
              <summary>How long does the Irish student visa take?</summary>
              <div class="faq-answer">Typically 6&ndash;8 weeks from a complete AVATS submission. Apply at least 3 months before your intended travel date.</div>
            </details>
            <details class="faq-item">
              <summary>Which Irish universities are best for international students?</summary>
              <div class="faq-answer">Trinity College Dublin, UCD, University of Galway, and UCC are consistently the top picks. Specialist institutions like RCSI for medicine and DCU for business and computing are also highly regarded.</div>
            </details>
          </div>
        </div>
      </section>

      <section class="country-cta">
        <div class="container country-cta-grid">
          <div>
            <h2>Plan your Ireland application with us</h2>
            <p>From university shortlisting to AVATS visa prep &mdash; One Degree builds an Ireland-ready application.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Book an Ireland call</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="{{ route('home') }}#destinations"><i data-lucide="compass"></i><span>Compare destinations</span></a>
            </div>
          </div>
          <div class="country-cta-card">
            <h3>What you&rsquo;ll get</h3>
            <p>A 30-minute counselor-led session, free of cost.</p>
            <ul>
              <li><i data-lucide="check"></i><span>3 university shortlists with fit notes</span></li>
              <li><i data-lucide="check"></i><span>Scholarship &amp; loan readiness check</span></li>
              <li><i data-lucide="check"></i><span>Stay-back &amp; PR roadmap</span></li>
            </ul>
          </div>
        </div>
      </section>
    </main>
@endsection