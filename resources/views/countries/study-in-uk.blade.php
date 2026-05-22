@php
    $pageTitle = 'Study in the UK | OneDegreeAdvisory';
    $pageDescription = 'Study in the UK with OneDegreeAdvisory. Top universities, tuition, scholarships, Student Route visa, Graduate Route work pathway, intakes, costs, FAQs.';
    $activeNav = 'destinations';
    $mainId = 'country-main';
@endphp

@extends('layouts.app')

@section('content')
<main id="country-main" class="country-main">
      <section class="country-hero country-hero--uk" id="top">
        <div class="container country-hero-grid">
          <div class="country-hero-copy">
            <a class="country-back" href="{{ route('home') }}#destinations"><i data-lucide="arrow-left"></i><span>All destinations</span></a>
            <span class="country-flag-lg"><img src="https://flagcdn.com/w160/gb.png" alt="UK flag"></span>
            <span class="eyebrow">Country guide</span>
            <h1>Study in the <span class="gold-text">United Kingdom</span></h1>
            <p class="country-lede">
              Gain internationally recognised degrees, unlock global career pathways, and join 600,000+ international
              students at world-leading universities &mdash; with a clear 2-year post-study work route.
            </p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Start your journey</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="{{ route('contact') }}"><i data-lucide="phone-call"></i><span>Talk to an expert</span></a>
            </div>
          </div>
          <aside class="country-snapshot">
            <h2>At a glance</h2>
            <dl>
              <div><dt>Top universities</dt><dd>90+ globally ranked</dd></div>
              <div><dt>Main intakes</dt><dd>September, January</dd></div>
              <div><dt>Tuition / year</dt><dd><span data-money="15000" data-currency="GBP">&pound;15,000</span> &ndash; <span data-money="45000" data-currency="GBP">&pound;45,000</span></dd></div>
              <div><dt>Living cost / month</dt><dd><span data-money="900" data-currency="GBP">&pound;900</span> &ndash; <span data-money="1800" data-currency="GBP">&pound;1,800</span></dd></div>
              <div><dt>Student visa</dt><dd>Student Route</dd></div>
              <div><dt>Post-study work</dt><dd>Graduate Route &middot; 2 years</dd></div>
            </dl>
          </aside>
        </div>
      </section>

      <section class="country-stat-strip">
        <div class="container">
          <div class="stat-strip-grid">
            <div class="stat-strip-item"><span class="stat-icon"><i data-lucide="globe-2"></i></span><div class="stat-text"><span class="stat-value">600K+</span><span class="stat-label">Intl. students</span></div></div>
            <div class="stat-strip-item"><span class="stat-icon"><i data-lucide="trophy"></i></span><div class="stat-text"><span class="stat-value">4 in Top 10</span><span class="stat-label">QS World Rankings</span></div></div>
            <div class="stat-strip-item"><span class="stat-icon"><i data-lucide="clock"></i></span><div class="stat-text"><span class="stat-value">1 Year</span><span class="stat-label">Taught Master&rsquo;s</span></div></div>
            <div class="stat-strip-item"><span class="stat-icon"><i data-lucide="briefcase"></i></span><div class="stat-text"><span class="stat-value">2 Years</span><span class="stat-label">Graduate Route</span></div></div>
            <div class="stat-strip-item"><span class="stat-icon"><i data-lucide="languages"></i></span><div class="stat-text"><span class="stat-value">English</span><span class="stat-label">Medium</span></div></div>
            <div class="stat-strip-item"><span class="stat-icon"><i data-lucide="calendar-check"></i></span><div class="stat-text"><span class="stat-value">3 Intakes</span><span class="stat-label">Sep / Jan / May</span></div></div>
          </div>
        </div>
      </section>

      <nav class="country-nav" aria-label="On-page sections">
        <div class="container">
          <div class="country-nav-track">
            <a href="#why">Why UK</a>
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
            <span class="eyebrow">Why the UK</span>
            <h2>A confident choice for ambitious students</h2>
            <p>Focused degrees, faster timelines, and globally portable credentials.</p>
          </div>
          <div class="reasons-grid">
            <article class="reason-card"><span class="reason-icon"><i data-lucide="award"></i></span><h3>World-class universities</h3><p>Four UK institutions consistently sit in the global top 10 across major league tables.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="clock"></i></span><h3>One-year master&rsquo;s</h3><p>Most taught postgraduate programs run for 12 months &mdash; saving time, tuition, and living costs.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="briefcase"></i></span><h3>Graduate Route</h3><p>Stay and work for 2 years after graduation (3 years for doctoral candidates) without sponsorship.</p></article>
            <article class="reason-card"><span class="reason-icon"><i data-lucide="globe"></i></span><h3>Global cities</h3><p>London, Manchester, Edinburgh, Birmingham, and Glasgow offer rich industry exposure.</p></article>
          </div>
        </div>
      </section>

      <section id="education-system" class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left">
            <span class="eyebrow">Academic structure</span>
            <h2>UK education system at a glance</h2>
            <p>How degrees are organised, what they cost, and how long they take.</p>
          </div>
          <div class="edu-system-grid">
            <article class="edu-level-card">
              <span class="edu-level-tag">Undergraduate</span>
              <h3>Bachelor&rsquo;s (Honours)</h3>
              <p class="edu-level-meta">3 years &middot; 4 in Scotland</p>
              <p>BA, BSc, BEng programs through UCAS. Year-long industry placements common in business, computing, and engineering.</p>
            </article>
            <article class="edu-level-card">
              <span class="edu-level-tag">Postgraduate</span>
              <h3>Master&rsquo;s (MA / MSc / MBA)</h3>
              <p class="edu-level-meta">1 year (12 months) &middot; MBA 12&ndash;21 months</p>
              <p>Taught and research master&rsquo;s. Most one-year format ends with a dissertation in late summer.</p>
            </article>
            <article class="edu-level-card">
              <span class="edu-level-tag">Doctorate</span>
              <h3>PhD / DPhil</h3>
              <p class="edu-level-meta">3&ndash;4 years full-time</p>
              <p>Research-led with annual progression reviews. Funding via UKRI studentships, university scholarships, or external grants.</p>
            </article>
            <article class="edu-level-card">
              <span class="edu-level-tag">Pre-degree</span>
              <h3>Foundation &amp; Pre-Masters</h3>
              <p class="edu-level-meta">9&ndash;12 months</p>
              <p>Pathway programs for students who need an academic or English bridge before undergraduate or master&rsquo;s entry.</p>
            </article>
          </div>
        </div>
      </section>

      <section class="country-section full-bleed" aria-label="UK universities banner">
        <div class="container">
          <div class="country-band">
            <img src="https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=2000&q=80" alt="">
            <div class="band-inner">
              <span class="eyebrow">Russell Group &amp; beyond</span>
              <h2>Globally ranked. Research intensive. Career-ready.</h2>
              <p>Twenty-four Russell Group universities anchor the UK&rsquo;s research output &mdash; alongside specialist institutions in business, art, and STEM.</p>
              <div class="band-stats">
                <div class="band-stat"><strong>24</strong><span>Russell Group</span></div>
                <div class="band-stat"><strong>4</strong><span>In Top 10 QS</span></div>
                <div class="band-stat"><strong>600K+</strong><span>Intl. students</span></div>
                <div class="band-stat"><strong>90+</strong><span>Globally ranked</span></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="top-universities" class="country-section">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Universities</span>
            <h2>Top universities in the UK</h2>
            <p>A representative shortlist across Russell Group and specialist institutions.</p>
          </div>
          <div class="uni-image-grid">
            <article class="uni-img-card">
              <div class="uni-photo"><span class="uni-rank-badge">QS #3</span><img src="https://images.unsplash.com/photo-1566438480900-0609be27a4be?auto=format&fit=crop&w=900&q=80" alt="University of Oxford" loading="lazy"></div>
              <div class="uni-body">
                <h3>University of Oxford</h3>
                <span class="uni-loc"><i data-lucide="map-pin"></i>Oxford, England</span>
                <div class="uni-meta"><span>IELTS 7.0+</span><span>UG &middot; PG &middot; PhD</span></div>
              </div>
            </article>
            <article class="uni-img-card">
              <div class="uni-photo"><span class="uni-rank-badge">QS #5</span><img src="https://images.unsplash.com/photo-1543007630-9710e4a00a20?auto=format&fit=crop&w=900&q=80" alt="University of Cambridge" loading="lazy"></div>
              <div class="uni-body">
                <h3>University of Cambridge</h3>
                <span class="uni-loc"><i data-lucide="map-pin"></i>Cambridge, England</span>
                <div class="uni-meta"><span>IELTS 7.5</span><span>UG &middot; PG &middot; PhD</span></div>
              </div>
            </article>
            <article class="uni-img-card">
              <div class="uni-photo"><span class="uni-rank-badge">QS #2</span><img src="https://images.unsplash.com/photo-1607013251379-e6eecfffe234?auto=format&fit=crop&w=900&q=80" alt="Imperial College London" loading="lazy"></div>
              <div class="uni-body">
                <h3>Imperial College London</h3>
                <span class="uni-loc"><i data-lucide="map-pin"></i>London</span>
                <div class="uni-meta"><span>IELTS 6.5&ndash;7.0</span><span>STEM-focused</span></div>
              </div>
            </article>
            <article class="uni-img-card">
              <div class="uni-photo"><span class="uni-rank-badge">QS #9</span><img src="https://images.unsplash.com/photo-1505761671935-60b3a7427bad?auto=format&fit=crop&w=900&q=80" alt="University College London" loading="lazy"></div>
              <div class="uni-body">
                <h3>University College London (UCL)</h3>
                <span class="uni-loc"><i data-lucide="map-pin"></i>London</span>
                <div class="uni-meta"><span>IELTS 6.5&ndash;7.5</span><span>Multidisciplinary</span></div>
              </div>
            </article>
            <article class="uni-img-card">
              <div class="uni-photo"><span class="uni-rank-badge">QS #50</span><img src="https://images.unsplash.com/photo-1581094271901-8022df4466f9?auto=format&fit=crop&w=900&q=80" alt="London School of Economics" loading="lazy"></div>
              <div class="uni-body">
                <h3>London School of Economics (LSE)</h3>
                <span class="uni-loc"><i data-lucide="map-pin"></i>London</span>
                <div class="uni-meta"><span>IELTS 7.0</span><span>Social sciences</span></div>
              </div>
            </article>
            <article class="uni-img-card">
              <div class="uni-photo"><span class="uni-rank-badge">QS #40</span><img src="https://images.unsplash.com/photo-1568667256549-094345857637?auto=format&fit=crop&w=900&q=80" alt="King's College London" loading="lazy"></div>
              <div class="uni-body">
                <h3>King&rsquo;s College London</h3>
                <span class="uni-loc"><i data-lucide="map-pin"></i>London</span>
                <div class="uni-meta"><span>IELTS 6.5&ndash;7.5</span><span>Health &middot; Law &middot; Business</span></div>
              </div>
            </article>
            <article class="uni-img-card">
              <div class="uni-photo"><span class="uni-rank-badge">QS #27</span><img src="https://images.unsplash.com/photo-1620207418302-439b387441b0?auto=format&fit=crop&w=900&q=80" alt="University of Edinburgh" loading="lazy"></div>
              <div class="uni-body">
                <h3>University of Edinburgh</h3>
                <span class="uni-loc"><i data-lucide="map-pin"></i>Edinburgh, Scotland</span>
                <div class="uni-meta"><span>IELTS 6.5&ndash;7.0</span><span>AI &middot; Data &middot; Informatics</span></div>
              </div>
            </article>
            <article class="uni-img-card">
              <div class="uni-photo"><span class="uni-rank-badge">QS #34</span><img src="https://images.unsplash.com/photo-1567427017947-545c5f8d16ad?auto=format&fit=crop&w=900&q=80" alt="University of Manchester" loading="lazy"></div>
              <div class="uni-body">
                <h3>University of Manchester</h3>
                <span class="uni-loc"><i data-lucide="map-pin"></i>Manchester</span>
                <div class="uni-meta"><span>IELTS 6.5</span><span>Engineering &middot; Business</span></div>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section id="top-courses" class="country-section alt">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Courses</span>
            <h2>Top courses to study in the UK</h2>
            <p>The programs international students apply for most often &mdash; with strong outcomes and Graduate Route eligibility.</p>
          </div>
          <div class="courses-grid">
            <article class="course-card">
              <span class="course-tag">Master&rsquo;s</span>
              <h3>MSc Computer Science</h3>
              <p>Conversion and specialist tracks across Oxford, Imperial, UCL, Edinburgh, and Manchester.</p>
              <div class="course-foot"><span><i data-lucide="clock"></i>12 months</span><span><i data-lucide="banknote"></i><span data-money="28000" data-currency="GBP" data-money-hint="k">&pound;28k</span>&ndash;<span data-money="42000" data-currency="GBP" data-money-hint="k">&pound;42k</span></span></div>
            </article>
            <article class="course-card">
              <span class="course-tag">Master&rsquo;s</span>
              <h3>MSc Data Science &amp; Analytics</h3>
              <p>Quantitative tracks combining statistics, ML, and business analytics for global hiring.</p>
              <div class="course-foot"><span><i data-lucide="clock"></i>12 months</span><span><i data-lucide="banknote"></i><span data-money="25000" data-currency="GBP" data-money-hint="k">&pound;25k</span>&ndash;<span data-money="38000" data-currency="GBP" data-money-hint="k">&pound;38k</span></span></div>
            </article>
            <article class="course-card">
              <span class="course-tag">MBA</span>
              <h3>Master of Business Administration</h3>
              <p>1-year MBA programs at LBS, Said, Judge, Warwick, and Manchester for experienced professionals.</p>
              <div class="course-foot"><span><i data-lucide="clock"></i>12&ndash;21 months</span><span><i data-lucide="banknote"></i><span data-money="35000" data-currency="GBP" data-money-hint="k">&pound;35k</span>&ndash;<span data-money="110000" data-currency="GBP" data-money-hint="k">&pound;110k</span></span></div>
            </article>
            <article class="course-card">
              <span class="course-tag">Master&rsquo;s</span>
              <h3>MSc Finance &amp; Investment</h3>
              <p>Quant finance, asset management, and FinTech tracks aligned with London&rsquo;s financial sector.</p>
              <div class="course-foot"><span><i data-lucide="clock"></i>12 months</span><span><i data-lucide="banknote"></i><span data-money="28000" data-currency="GBP" data-money-hint="k">&pound;28k</span>&ndash;<span data-money="55000" data-currency="GBP" data-money-hint="k">&pound;55k</span></span></div>
            </article>
            <article class="course-card">
              <span class="course-tag">Master&rsquo;s</span>
              <h3>MA International Relations</h3>
              <p>Globally recognised programs at LSE, KCL, SOAS, and St Andrews for policy and diplomacy paths.</p>
              <div class="course-foot"><span><i data-lucide="clock"></i>12 months</span><span><i data-lucide="banknote"></i><span data-money="22000" data-currency="GBP" data-money-hint="k">&pound;22k</span>&ndash;<span data-money="33000" data-currency="GBP" data-money-hint="k">&pound;33k</span></span></div>
            </article>
            <article class="course-card">
              <span class="course-tag">Bachelor&rsquo;s</span>
              <h3>BSc Engineering (multiple)</h3>
              <p>3&ndash;4 year integrated programs with placement years across UCL, Bristol, Edinburgh, and Manchester.</p>
              <div class="course-foot"><span><i data-lucide="clock"></i>3&ndash;4 years</span><span><i data-lucide="banknote"></i><span data-money="28000" data-currency="GBP" data-money-hint="k">&pound;28k</span>&ndash;<span data-money="38000" data-currency="GBP" data-money-hint="k">&pound;38k</span>/yr</span></div>
            </article>
          </div>
        </div>
      </section>

      <section id="costs" class="country-section paper">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Costs</span>
            <h2>Tuition and cost of living</h2>
            <p>Indicative ranges &mdash; final figures depend on program, city, and lifestyle.</p>
          </div>
          <div class="cost-grid">
            <div class="cost-card"><span class="cost-label">Undergraduate tuition</span><span class="cost-value"><span data-money="15000" data-currency="GBP">&pound;15,000</span>&ndash;<span data-money="35000" data-currency="GBP">&pound;35,000</span></span><span class="cost-note">Per year &middot; higher for medicine and select STEM courses.</span></div>
            <div class="cost-card"><span class="cost-label">Postgraduate tuition</span><span class="cost-value"><span data-money="18000" data-currency="GBP">&pound;18,000</span>&ndash;<span data-money="45000" data-currency="GBP">&pound;45,000</span></span><span class="cost-note">Per year &middot; MBA and specialist masters at the upper end.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; London</span><span class="cost-value"><span data-money="1300" data-currency="GBP">&pound;1,300</span>&ndash;<span data-money="1800" data-currency="GBP">&pound;1,800</span></span><span class="cost-note">Per month &middot; covers accommodation, food, transport.</span></div>
            <div class="cost-card"><span class="cost-label">Living &mdash; Other cities</span><span class="cost-value"><span data-money="900" data-currency="GBP">&pound;900</span>&ndash;<span data-money="1300" data-currency="GBP">&pound;1,300</span></span><span class="cost-note">Per month &middot; Manchester, Birmingham, Glasgow, Edinburgh.</span></div>
          </div>
        </div>
      </section>

      <section id="scholarships" class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Funding &amp; visa</span>
            <h2>Scholarships, funding &amp; the Student Route</h2>
            <p>Two parallel tracks &mdash; how to fund the degree, and how to secure your visa.</p>
          </div>
          <div class="split-info">
            <div class="info-card">
              <h3>Scholarships &amp; funding</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Chevening Scholarships &mdash; fully-funded UK government awards for one-year masters.</span></li>
                <li><i data-lucide="check"></i><span>Commonwealth Scholarships for students from Commonwealth countries.</span></li>
                <li><i data-lucide="check"></i><span>GREAT Scholarships for select countries and universities.</span></li>
                <li><i data-lucide="check"></i><span>University merit and need-based bursaries &mdash; vary by institution.</span></li>
                <li><i data-lucide="check"></i><span>Education loans through major Indian and international lenders.</span></li>
              </ul>
            </div>
            <div class="info-card">
              <h3>Student visa snapshot</h3>
              <ul>
                <li><i data-lucide="check"></i><span>Visa name: <strong>Student Route</strong> (formerly Tier 4).</span></li>
                <li><i data-lucide="check"></i><span>Requires a Confirmation of Acceptance for Studies (CAS) from your university.</span></li>
                <li><i data-lucide="check"></i><span>Financial proof: roughly <span data-money="1334" data-currency="GBP">&pound;1,334</span>/month for London, <span data-money="1023" data-currency="GBP">&pound;1,023</span>/month outside London.</span></li>
                <li><i data-lucide="check"></i><span>Standard processing: about 3 weeks from your home country.</span></li>
                <li><i data-lucide="check"></i><span>Work allowance: up to 20 hours/week in term, full-time during vacations.</span></li>
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
            <p>Three windows a year &mdash; not every program runs in every intake.</p>
          </div>
          <table class="intake-table">
            <thead>
              <tr>
                <th>Intake</th>
                <th>Months</th>
                <th>Apply by</th>
                <th>Best for</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><span class="pill">Autumn</span></td>
                <td>September &mdash; October</td>
                <td>UCAS UG: Jan; PG: rolling, Mar&ndash;Jun</td>
                <td>Primary intake &mdash; widest program and scholarship choice.</td>
              </tr>
              <tr>
                <td><span class="pill secondary">Winter</span></td>
                <td>January &mdash; February</td>
                <td>Sep &ndash; Oct of previous year</td>
                <td>Business and CS conversion master&rsquo;s. Limited UG options.</td>
              </tr>
              <tr>
                <td><span class="pill secondary">Spring</span></td>
                <td>April &mdash; May</td>
                <td>Dec &ndash; Feb</td>
                <td>Pathway, foundation, and a small set of postgraduate programs.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section id="cities" class="country-section">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Cities</span>
            <h2>Popular student cities in the UK</h2>
            <p>From London&rsquo;s global pull to Edinburgh&rsquo;s historic charm &mdash; pick the city that fits your study and lifestyle goals.</p>
          </div>
          <div class="cities-mosaic">
            <a class="city-card span-2 row-2" href="{{ route('contact') }}">
              <img src="https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?auto=format&fit=crop&w=1400&q=80" alt="London" loading="lazy">
              <div class="city-info"><h3>London</h3><p>30+ universities &middot; global financial &amp; cultural hub</p></div>
            </a>
            <a class="city-card" href="{{ route('contact') }}">
              <img src="https://images.unsplash.com/photo-1506377585622-bedcbb027afc?auto=format&fit=crop&w=900&q=80" alt="Edinburgh" loading="lazy">
              <div class="city-info"><h3>Edinburgh</h3><p>AI &amp; research strength</p></div>
            </a>
            <a class="city-card" href="{{ route('contact') }}">
              <img src="https://images.unsplash.com/photo-1518837695005-2083093ee35b?auto=format&fit=crop&w=900&q=80" alt="Manchester" loading="lazy">
              <div class="city-info"><h3>Manchester</h3><p>Engineering &amp; business hiring</p></div>
            </a>
            <a class="city-card" href="{{ route('contact') }}">
              <img src="https://images.unsplash.com/photo-1605379399843-5870eea9b74e?auto=format&fit=crop&w=900&q=80" alt="Birmingham" loading="lazy">
              <div class="city-info"><h3>Birmingham</h3><p>5 universities</p></div>
            </a>
            <a class="city-card" href="{{ route('contact') }}">
              <img src="https://images.unsplash.com/photo-1577741314755-048d8525d31e?auto=format&fit=crop&w=900&q=80" alt="Glasgow" loading="lazy">
              <div class="city-info"><h3>Glasgow</h3><p>Creative &amp; engineering</p></div>
            </a>
            <a class="city-card span-2" href="{{ route('contact') }}">
              <img src="https://images.unsplash.com/photo-1574870111867-089730e5a72b?auto=format&fit=crop&w=1400&q=80" alt="Bristol" loading="lazy">
              <div class="city-info"><h3>Bristol</h3><p>Aerospace &middot; tech &middot; vibrant creative scene</p></div>
            </a>
          </div>
        </div>
      </section>

      <section id="visa-process" class="country-section">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Visa process</span>
            <h2>How to get your UK Student visa</h2>
            <p>The Student Route is one of the most processed visas in the world &mdash; here&rsquo;s the typical path.</p>
          </div>
          <div class="visa-stats">
            <div class="visa-stat">
              <span class="visa-stat-value">97%</span>
              <span class="visa-stat-label">Visa success rate</span>
              <span class="visa-stat-note">For complete, well-prepared applications (Home Office data).</span>
            </div>
            <div class="visa-stat">
              <span class="visa-stat-value"><span data-money="524" data-currency="GBP">&pound;524</span></span>
              <span class="visa-stat-label">Application fee</span>
              <span class="visa-stat-note">Applying from outside the UK; in-country fee is higher.</span>
            </div>
            <div class="visa-stat">
              <span class="visa-stat-value">3 Weeks</span>
              <span class="visa-stat-label">Standard processing</span>
              <span class="visa-stat-note">Priority and super-priority options available for an extra fee.</span>
            </div>
          </div>
          <div class="visa-steps">
            <article class="visa-step"><h4>Receive your CAS</h4><p>Pay your tuition deposit and accept your offer to receive a Confirmation of Acceptance for Studies from your university.</p></article>
            <article class="visa-step"><h4>Prove funds &amp; English</h4><p>Show 28 consecutive days of bank statements covering tuition + living costs, plus UKVI IELTS / PTE / TOEFL.</p></article>
            <article class="visa-step"><h4>Pay IHS &amp; visa fee</h4><p>Pay the Immigration Health Surcharge (~<span data-money="776" data-currency="GBP">&pound;776</span>/year) plus the visa application fee online.</p></article>
            <article class="visa-step"><h4>Submit Student Route application</h4><p>Apply on the gov.uk portal, upload documents, and book your biometrics appointment.</p></article>
            <article class="visa-step"><h4>Biometrics &amp; TB test</h4><p>Attend the VFS appointment for fingerprints; submit a TB test certificate where required.</p></article>
            <article class="visa-step"><h4>Collect your BRP</h4><p>Receive the visa decision in ~3 weeks; pick up your Biometric Residence Permit after arrival in the UK.</p></article>
          </div>
        </div>
      </section>

      <section id="work" class="country-section dark">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">Work &amp; settlement</span>
            <h2>Work rights and post-study pathways</h2>
            <p>From in-study employment to long-term settlement.</p>
          </div>
          <div class="work-pr-grid">
            <article class="work-pr-card">
              <span class="work-pr-icon"><i data-lucide="briefcase"></i></span>
              <h3>Graduate Route</h3>
              <span class="work-pr-tag">2 years</span>
              <p>Stay and work in any role after graduation. 3 years for PhD holders. No employer sponsorship required.</p>
            </article>
            <article class="work-pr-card">
              <span class="work-pr-icon"><i data-lucide="hourglass"></i></span>
              <h3>In-study work</h3>
              <span class="work-pr-tag">20 hrs/week</span>
              <p>Up to 20 hours per week during term, full-time in vacations. Internships and placements are typically allowed.</p>
            </article>
            <article class="work-pr-card">
              <span class="work-pr-icon"><i data-lucide="building-2"></i></span>
              <h3>Skilled Worker visa</h3>
              <span class="work-pr-tag">Sponsored</span>
              <p>Transition to a long-term work visa with a Home Office-licensed sponsor; counts toward Indefinite Leave to Remain.</p>
            </article>
            <article class="work-pr-card">
              <span class="work-pr-icon"><i data-lucide="key-round"></i></span>
              <h3>Settlement (ILR)</h3>
              <span class="work-pr-tag">5 years</span>
              <p>Indefinite Leave to Remain after 5 qualifying years; pathway to British citizenship after a further year.</p>
            </article>
          </div>
        </div>
      </section>

      <section id="student-life" class="country-section paper">
        <div class="container">
          <div class="section-head section-head--left">
            <span class="eyebrow">Student life</span>
            <h2>Living and studying in the UK</h2>
            <p>What daily life looks like outside the lecture theatre.</p>
          </div>
          <div class="life-grid">
            <article class="life-card"><span class="life-icon"><i data-lucide="train"></i></span><h3>Getting around</h3><p>Reliable rail and city transport. Discounted 16&ndash;25 / 26&ndash;30 Railcard saves ~33% on most train fares.</p></article>
            <article class="life-card"><span class="life-icon"><i data-lucide="utensils"></i></span><h3>Food &amp; culture</h3><p>Strong South Asian, halal, and vegetarian options in most cities. Active Indian and international societies on every campus.</p></article>
            <article class="life-card"><span class="life-icon"><i data-lucide="cloud-sun"></i></span><h3>Climate</h3><p>Mild temperate climate &mdash; cool, often wet winters and pleasant summers. Layered clothing year-round.</p></article>
            <article class="life-card"><span class="life-icon"><i data-lucide="shield-check"></i></span><h3>Healthcare</h3><p>Immigration Health Surcharge (~<span data-money="776" data-currency="GBP">&pound;776</span>/year) covers NHS access &mdash; GP visits, A&amp;E, and most hospital care.</p></article>
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
                <li><i data-lucide="check"></i><span>Globally recognised degrees with short, intensive formats.</span></li>
                <li><i data-lucide="check"></i><span>Graduate Route gives 2&ndash;3 years to job-hunt without sponsorship pressure.</span></li>
                <li><i data-lucide="check"></i><span>Strong industry exposure via London, Manchester, and other commercial hubs.</span></li>
                <li><i data-lucide="check"></i><span>Established Indian and South Asian communities in most cities.</span></li>
                <li><i data-lucide="check"></i><span>Centralised UG admissions via UCAS keeps the process predictable.</span></li>
              </ul>
            </div>
            <div class="pc-card cons">
              <h3><i data-lucide="alert-triangle"></i>Trade-offs</h3>
              <ul>
                <li><i data-lucide="dot"></i><span>High tuition for international students &mdash; especially at London universities.</span></li>
                <li><i data-lucide="dot"></i><span>One-year format leaves little buffer if you change direction mid-course.</span></li>
                <li><i data-lucide="dot"></i><span>Skilled Worker salary thresholds are rising &mdash; plan the transition early.</span></li>
                <li><i data-lucide="dot"></i><span>London cost of living can absorb your living budget quickly.</span></li>
                <li><i data-lucide="dot"></i><span>Visa policy changes can move quickly &mdash; track Home Office updates.</span></li>
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
            <p>A typical 12&ndash;18 month roadmap for a September intake.</p>
          </div>
          <div class="timeline-list">
            <div class="timeline-item"><h4>Profile review &amp; shortlist</h4><p>12&ndash;18 months out: clarify program direction, budget, and target universities.</p></div>
            <div class="timeline-item"><h4>Standardised tests</h4><p>8&ndash;12 months out: IELTS or PTE for English; GMAT/GRE if your program requires it.</p></div>
            <div class="timeline-item"><h4>Applications</h4><p>Undergrad through UCAS (deadline mid-January); postgraduate applications mostly rolling.</p></div>
            <div class="timeline-item"><h4>Offers &amp; funding</h4><p>December&ndash;April: review offers, confirm scholarships, finalise loan sanction if needed.</p></div>
            <div class="timeline-item"><h4>CAS &amp; visa</h4><p>April&ndash;August: pay tuition deposit, receive CAS, submit Student Route application.</p></div>
            <div class="timeline-item"><h4>Pre-departure &amp; arrival</h4><p>June&ndash;September: accommodation, banking, insurance, flights, campus arrival.</p></div>
          </div>
        </div>
      </section>

      <section id="faq" class="country-section alt">
        <div class="container">
          <div class="section-head">
            <span class="eyebrow">FAQs</span>
            <h2>Common questions about studying in the UK</h2>
            <p>Short answers to what students and parents ask us most often.</p>
          </div>
          <div class="faq-list">
            <details class="faq-item">
              <summary>Is the UK a good place to study for Indian students?</summary>
              <div class="faq-answer">Yes &mdash; one-year master&rsquo;s, the Graduate Route, and a large Indian community make the UK one of the most popular destinations. The application process is also relatively predictable through UCAS (UG) and direct university portals (PG).</div>
            </details>
            <details class="faq-item">
              <summary>How much does it cost to study in the UK?</summary>
              <div class="faq-answer">Budget <span data-money="15000" data-currency="GBP">&pound;15,000</span>&ndash;<span data-money="45000" data-currency="GBP">&pound;45,000</span>/year in tuition plus <span data-money="11000" data-currency="GBP">&pound;11,000</span>&ndash;<span data-money="21000" data-currency="GBP">&pound;21,000</span>/year for living. London is significantly more expensive than most other UK cities.</div>
            </details>
            <details class="faq-item">
              <summary>Do I need IELTS to study in the UK?</summary>
              <div class="faq-answer">Most universities accept IELTS, PTE Academic, TOEFL iBT, or Cambridge English. A small number waive English tests if your medium of instruction was English. UKVI-approved tests are required for the Student Route visa.</div>
            </details>
            <details class="faq-item">
              <summary>Can I work while studying in the UK?</summary>
              <div class="faq-answer">Yes &mdash; up to 20 hours per week during term-time and full-time during vacations. Internships and placements arranged through your university are typically allowed.</div>
            </details>
            <details class="faq-item">
              <summary>What is the Graduate Route visa?</summary>
              <div class="faq-answer">A post-study work visa that lets you stay in the UK for 2 years (3 for PhD holders) after graduation to work or look for work, without needing employer sponsorship.</div>
            </details>
            <details class="faq-item">
              <summary>Can I get permanent residency in the UK after studying?</summary>
              <div class="faq-answer">Yes, but not directly via the Student or Graduate Route. You typically need to transition to a Skilled Worker visa and complete 5 qualifying years to apply for Indefinite Leave to Remain (ILR).</div>
            </details>
            <details class="faq-item">
              <summary>What is the IELTS score required for UK universities?</summary>
              <div class="faq-answer">Most undergraduate programs ask for IELTS 6.0&ndash;6.5 overall (no band below 5.5). Postgraduate programs typically require 6.5&ndash;7.0. Specialist courses such as law, medicine, and journalism can ask for higher.</div>
            </details>
            <details class="faq-item">
              <summary>What scholarships are available?</summary>
              <div class="faq-answer">Chevening, Commonwealth, GREAT Scholarships, and a wide range of university-specific awards. Most are merit-based and require a strong academic profile and clear post-study plan.</div>
            </details>
          </div>
        </div>
      </section>

      <section class="country-cta">
        <div class="container country-cta-grid">
          <div>
            <h2>Ready to map your UK application?</h2>
            <p>Talk to a OneDegree advisor about university fit, scholarships, and Student Route timing.</p>
            <div class="country-actions">
              <a class="btn btn-primary" href="{{ route('contact') }}"><span>Book a UK call</span><i data-lucide="arrow-up-right"></i></a>
              <a class="btn btn-ghost" href="{{ route('home') }}#destinations"><i data-lucide="compass"></i><span>Compare destinations</span></a>
            </div>
          </div>
          <div class="country-cta-card">
            <h3>What you&rsquo;ll get</h3>
            <p>A 30-minute counselor-led session, free of cost.</p>
            <ul>
              <li><i data-lucide="check"></i><span>3 university shortlists with fit notes</span></li>
              <li><i data-lucide="check"></i><span>Scholarship signal &amp; loan check</span></li>
              <li><i data-lucide="check"></i><span>Graduate Route &amp; PR clarity</span></li>
            </ul>
          </div>
        </div>
      </section>
    </main>
@endsection