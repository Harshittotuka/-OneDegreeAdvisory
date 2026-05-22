@php
    $pageTitle = 'OneDegreeAdvisory | Global Education Advisory';
    $pageDescription = 'OneDegreeAdvisory is a premium global education advisory helping students choose universities, strengthen profiles, apply with confidence, and prepare for arrival.';
    $activeNav = 'home';
    $mainId = 'main';
@endphp

@extends('layouts.app')

@section('content')
<main id="main">
      <section class="hero" id="top" aria-label="OneDegreeAdvisory">
        <div class="hero-media" aria-hidden="true"></div>
        <div class="hero-overlay" aria-hidden="true"></div>

        <div class="container hero-grid">
          <div class="hero-copy reveal">
            <span class="eyebrow">Global Admissions &middot; Est. 2018</span>
            <h1 class="hero-headline">You are <span class="gold-text">one degree</span><br />away from the world.</h1>
            <blockquote class="hero-quote">
              <p>&ldquo;They don&rsquo;t just place students &mdash; they architect futures.&rdquo;</p>
              <cite>&mdash; Aanya M., Class of 2025 &middot; Harvard University</cite>
            </blockquote>
            <div class="hero-actions">
              <a class="btn btn-primary" href="#contact">
                <span>Start Your Roadmap</span>
                <i data-lucide="arrow-up-right"></i>
              </a>
              <a class="btn btn-ghost" href="#method">
                <i data-lucide="compass"></i>
                <span>Explore Method</span>
              </a>
            </div>
          </div>

          <form class="hero-consult reveal" data-hero-consult aria-label="Book your free consultation">
            <h2 class="hero-consult-title">Book your free Consultation</h2>

            <label class="hc-field">
              <input type="text" name="fullName" autocomplete="name" required placeholder=" ">
              <span class="hc-label">Full Name<em>*</em></span>
            </label>

            <label class="hc-field">
              <input type="email" name="email" autocomplete="email" required placeholder=" ">
              <span class="hc-label">Email address<em>*</em></span>
            </label>

            <div class="hc-field hc-phone">
              <div class="hc-phone-country" data-country-select></div>
              <input type="hidden" name="phoneCountry" data-phone-country-input value="+91">
              <input type="tel" name="phone" data-phone-input autocomplete="tel" inputmode="numeric" placeholder=" " maxlength="10" aria-label="Phone number (10 digits)" required>
            </div>

            <label class="hc-field hc-select">
              <select name="state" data-state-select required>
                <option value="" disabled selected hidden></option>
              </select>
              <span class="hc-label">Current State</span>
              <i class="hc-chevron" data-lucide="chevron-down"></i>
            </label>

            <label class="hc-field hc-select">
              <select name="city" data-city-select required disabled>
                <option value="" disabled selected hidden></option>
              </select>
              <span class="hc-label">Current City</span>
              <i class="hc-chevron" data-lucide="chevron-down"></i>
            </label>

            <label class="hc-consent">
              <input type="checkbox" required>
              <span>I have read and agreed to <a href="#terms">terms</a> &amp; <a href="#privacy">privacy policy</a></span>
            </label>

            <button type="submit" class="hc-submit">SUBMIT</button>
          </form>
        </div>
      </section>

      <section class="pathfinder" aria-labelledby="pathfinder-title">
        <div class="container pathfinder-shell reveal">
          <div>
            <span class="eyebrow">Dream search</span>
            <h2 id="pathfinder-title">Find the right first move.</h2>
          </div>
          <form class="finder-form" data-finder-form>
            <label>
              <span>Destination</span>
              <select name="destination" aria-label="Preferred destination" data-finder-field>
                <option>United Kingdom</option>
                <option>Canada</option>
                <option>United States</option>
                <option>Australia</option>
                <option>Germany</option>
                <option>Ireland</option>
              </select>
            </label>
            <label>
              <span>Program level</span>
              <select name="program" aria-label="Program level" data-finder-field>
                <option>Undergraduate</option>
                <option>Masters</option>
                <option>MBA</option>
                <option>Foundation</option>
                <option>Diploma</option>
              </select>
            </label>
            <label>
              <span>Intake</span>
              <select name="intake" aria-label="Target intake" data-finder-field>
                <option>Fall 2027</option>
                <option>Spring 2027</option>
                <option>Fall 2028</option>
                <option>Flexible</option>
              </select>
            </label>
            <button class="btn btn-secondary" type="submit">
              <span>Build Plan</span>
              <i data-lucide="sparkles"></i>
            </button>
          </form>
          <p class="finder-note" data-finder-note>
            Your first roadmap starts with country fit, course direction, and intake timing.
          </p>
        </div>
      </section>

      <section class="signal-strip" aria-label="What OneDegreeAdvisory brings">
        <div class="container signal-grid">
          <div class="signal reveal">
            <span>01</span>
            <strong>Strategy before forms</strong>
            <p>Every shortlist begins with profile fit, budget clarity, career intent, and risk review.</p>
          </div>
          <div class="signal reveal">
            <span>360</span>
            <strong>Application-to-arrival</strong>
            <p>University selection, essays, scholarships, loans, visa prep, and pre-departure support.</p>
          </div>
          <div class="signal reveal">
            <span>1:1</span>
            <strong>Human advisory</strong>
            <p>Thoughtful counseling for students and parents, with each next step made visible.</p>
          </div>
          <div class="signal reveal">
            <span>8</span>
            <strong>Decision checkpoints</strong>
            <p>A guided journey that turns uncertainty into a sequence of confident choices.</p>
          </div>
        </div>
      </section>

      <section class="audience-section" aria-labelledby="audience-title">
        <div class="container wide-container">
          <div class="section-lead split reveal">
            <div>
              <span class="eyebrow">Guidance for every stage</span>
              <h2 id="audience-title">Uncertain about your future? We are here to help.</h2>
            </div>
            <p>
              Whether you are choosing subjects, planning postgraduate study, or switching careers,
              OneDegreeAdvisory turns a wide world of options into a personally sequenced plan.
            </p>
          </div>

          <div class="audience-grid">
            <article class="audience-card audience-card-mbbs reveal" data-mbbs-card tabindex="0" aria-expanded="false" aria-controls="mbbs-country-panel">
              <img src="https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=640&q=82" alt="Medical students planning international study pathways">
              <div>
                <h3>MBBS Students</h3>
                <p>Clinical pathways, licensing exams, postgraduate options, research profiles, and country fit.</p>
              </div>
              <div class="mbbs-routes-panel" id="mbbs-country-panel" aria-hidden="true">
                <div class="mbbs-routes-head">
                  <span>MBBS country shortlist</span>
                  <button class="mbbs-routes-close" type="button" aria-label="Close MBBS country shortlist" data-mbbs-close>
                    <i data-lucide="x"></i>
                  </button>
                </div>
                <div class="mbbs-routes-grid">
                  <span>Georgia</span>
                  <span>Kazakhstan</span>
                  <span>Poland</span>
                  <span>Germany</span>
                  <span>Malta</span>
                  <span>UAE</span>
                </div>
              </div>
              <a href="#contact" aria-label="Explore support for MBBS students">
                <i data-lucide="arrow-right"></i>
              </a>
            </article>
            <article class="audience-card reveal">
              <img src="https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=640&q=82" alt="School students discussing study options">
              <div>
                <h3>School Students</h3>
                <p>Subject choices, profile building, summer plans, and early university direction.</p>
              </div>
              <a href="#contact" aria-label="Explore support for school students">
                <i data-lucide="arrow-right"></i>
              </a>
            </article>
            <article class="audience-card reveal">
              <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=640&q=82" alt="College students working together">
              <div>
                <h3>College Students</h3>
                <p>Masters planning, internships, research direction, portfolio polish, and tests.</p>
              </div>
              <a href="#contact" aria-label="Explore support for college students">
                <i data-lucide="arrow-right"></i>
              </a>
            </article>
            <article class="audience-card reveal">
              <img src="https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=640&q=82" alt="Graduate celebrating at university">
              <div>
                <h3>Graduates</h3>
                <p>Career-led university choices, essays, recommendation strategy, and funding.</p>
              </div>
              <a href="#contact" aria-label="Explore support for graduates">
                <i data-lucide="arrow-right"></i>
              </a>
            </article>
            <article class="audience-card reveal">
              <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=640&q=82" alt="Working professionals in advisory conversation">
              <div>
                <h3>Working Professionals</h3>
                <p>MBA, executive programs, career pivot narratives, and return-on-investment planning.</p>
              </div>
              <a href="#contact" aria-label="Explore support for working professionals">
                <i data-lucide="arrow-right"></i>
              </a>
            </article>
          </div>
        </div>
      </section>

      <section class="method-section" id="method" aria-labelledby="method-title">
        <div class="container method-layout">
          <div class="section-lead sticky-lead reveal">
            <span class="eyebrow">The OneDegree method</span>
            <h2 id="method-title">A calm, exacting process for one of life's biggest decisions.</h2>
            <p>
              Inspired by elite admissions consulting, but built for modern global education:
              transparent, data-aware, deeply personal, and practical enough for real deadlines.
            </p>
            <a class="text-link" href="#contact">
              <span>Request your diagnostic session</span>
              <i data-lucide="arrow-up-right"></i>
            </a>
          </div>

          <div class="journey-steps">
            <article class="journey-step reveal">
              <span>01</span>
              <h3>Profile diagnostic</h3>
              <p>We review academics, interests, constraints, ambitions, budget, and family priorities before recommending a path.</p>
            </article>
            <article class="journey-step reveal">
              <span>02</span>
              <h3>Country and program fit</h3>
              <p>Destinations are compared by curriculum, outcomes, work pathways, scholarships, lifestyle, and visa reality.</p>
            </article>
            <article class="journey-step reveal">
              <span>03</span>
              <h3>Application architecture</h3>
              <p>Your story, documents, essays, test plan, and university list are aligned into one credible application system.</p>
            </article>
            <article class="journey-step reveal">
              <span>04</span>
              <h3>Decision and departure</h3>
              <p>Offers are compared, finances are organized, visa readiness is checked, and arrival planning becomes simple.</p>
            </article>
          </div>
        </div>
      </section>

      <section class="solutions-section" id="services" aria-labelledby="services-title">
        <div class="container edge-container">
          <div class="section-lead centered reveal">
            <span class="eyebrow">360 solutions</span>
            <h2 id="services-title">Find every solution, from applications to accommodations.</h2>
            <p>
              One connected advisory layer for the whole student journey: program discovery,
              applications, scholarships, financial planning, visa readiness, and arrival support.
            </p>
            <a class="btn btn-secondary solution-cta" href="#contact">
              <span>Plan with OneDegree</span>
              <i data-lucide="arrow-up-right"></i>
            </a>
          </div>

          <!--
          <div class="solution-ecosystem reveal">
            <div class="orbit orbit-one" aria-hidden="true"></div>
            <div class="orbit orbit-two" aria-hidden="true"></div>
            <div class="orbit orbit-three" aria-hidden="true"></div>
            <div class="solution-person">
              <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=84" alt="Students planning their global education pathway">
            </div>
            <div class="solution-chip chip-a"><i data-lucide="search-check"></i><span>Program Search</span></div>
            <div class="solution-chip chip-b"><i data-lucide="file-check-2"></i><span>Instant Applications</span></div>
            <div class="solution-chip chip-c"><i data-lucide="book-open-check"></i><span>Language Tests</span></div>
            <div class="solution-chip chip-d"><i data-lucide="hand-coins"></i><span>Student Loans</span></div>
            <div class="solution-chip chip-e"><i data-lucide="landmark"></i><span>Banking</span></div>
            <div class="solution-chip chip-f"><i data-lucide="repeat-2"></i><span>Foreign Exchange</span></div>
            <div class="solution-chip chip-g"><i data-lucide="passport"></i><span>Visa Services</span></div>
            <div class="solution-chip chip-h"><i data-lucide="home"></i><span>Accommodations</span></div>
            <div class="solution-chip chip-i"><i data-lucide="badge-check"></i><span>Quality Checks</span></div>
            <div class="solution-chip chip-j"><i data-lucide="graduation-cap"></i><span>GIC Program</span></div>
          </div>
          -->

          <div class="service-grid">
            <article class="service-card reveal">
              <i data-lucide="compass"></i>
              <h3>Course and Career Mapping</h3>
              <p>Turn interests, marks, experience, and career goals into country and program options worth pursuing.</p>
            </article>
            <article class="service-card reveal">
              <i data-lucide="building-2"></i>
              <h3>University Shortlisting</h3>
              <p>Build ambitious, realistic, and secure lists with admissions fit, costs, city life, and outcomes in view.</p>
            </article>
            <article class="service-card reveal">
              <i data-lucide="file-text"></i>
              <h3>Applications and Essays</h3>
              <p>Shape documents, SOPs, personal statements, activity records, and recommendations into a coherent story.</p>
            </article>
            <article class="service-card reveal">
              <i data-lucide="wallet-cards"></i>
              <h3>Scholarship and Finance</h3>
              <p>Compare scholarships, assistantships, payment timelines, and loan readiness before final decisions.</p>
            </article>
            <article class="service-card reveal">
              <i data-lucide="stamp"></i>
              <h3>Visa Counseling</h3>
              <p>Prepare documents, interview answers, intent clarity, and compliance basics with a structured checklist.</p>
            </article>
            <article class="service-card reveal">
              <i data-lucide="plane-takeoff"></i>
              <h3>Pre-Departure Support</h3>
              <p>Plan accommodation, packing, banking, insurance, campus arrival, and the first 30 days abroad.</p>
            </article>
          </div>
        </div>
      </section>

      <section class="destinations-section" id="destinations" aria-labelledby="destinations-title">
        <div class="container edge-container">
          <div class="section-lead split reveal">
            <div>
              <span class="eyebrow">University destinations</span>
              <h2 id="destinations-title">Browse countries with the lens that matters: fit.</h2>
            </div>
            <p>
              We help students compare destinations by academic culture, post-study opportunities,
              scholarships, safety, budget, and long-term career pathways.
            </p>
          </div>

          <div class="destination-controls reveal" role="tablist" aria-label="Destination filters">
            <button class="is-active" type="button" data-filter="all">All</button>
            <button type="button" data-filter="popular">Popular</button>
            <button type="button" data-filter="value">Value-led</button>
            <button type="button" data-filter="career">Career-led</button>
          </div>

          <div class="destination-layout">
            <div class="world-map-panel reveal" aria-label="Interactive world education map">
              <div class="map-topline">
                <div>
                  <span class="eyebrow">Interactive world map</span>
                  <h3>Tap a destination to compare fit.</h3>
                </div>
                <strong data-map-count>6 pathways</strong>
              </div>

              <div class="world-map" aria-label="Clickable destination map">
                <svg viewBox="0 0 1000 520" role="img" aria-label="Stylized world map">
                  <path class="map-land" d="M119 184c41-58 113-81 178-51 25 12 42 31 68 35 30 5 57-11 83 7 27 18 21 56-7 69-28 13-61-3-87 13-27 16-22 54-51 66-28 12-49-20-75-14-32 7-44 64-78 57-27-6-31-47-55-60-25-14-72 2-83-25-10-26 37-48 27-97z"/>
                  <path class="map-land" d="M401 121c46-31 119-30 159 5 29 25 36 65 78 76 38 10 72-12 101 8 34 23 23 77-19 94-47 20-84-18-128 2-41 19-41 72-81 80-36 7-49-34-88-35-30-1-54 22-79 9-25-13-24-52-3-72 23-21 60-12 76-39 21-36-45-72-16-128z"/>
                  <path class="map-land" d="M694 133c61-43 161-30 201 20 30 38 10 88 42 122 22 24 63 25 72 56 11 35-29 70-68 64-45-8-56-61-94-67-46-7-71 62-115 45-35-14-28-64-57-88-27-23-74-10-86-39-13-32 43-65 105-113z"/>
                  <path class="map-land" d="M480 356c41-18 81 8 88 43 8 39-28 74-55 96-26-18-56-48-57-83-1-24 8-47 24-56z"/>
                  <path class="map-land" d="M756 393c53-21 118-3 146 38-20 40-94 52-143 31-31-13-44-48-3-69z"/>
                </svg>
                <button class="map-pin pin-uk is-active" type="button" data-map-target="uk" aria-label="United Kingdom">
                  <span>UK</span>
                </button>
                <button class="map-pin pin-canada" type="button" data-map-target="canada" aria-label="Canada">
                  <span>CA</span>
                </button>
                <button class="map-pin pin-usa" type="button" data-map-target="usa" aria-label="United States">
                  <span>US</span>
                </button>
                <button class="map-pin pin-germany" type="button" data-map-target="germany" aria-label="Germany">
                  <span>DE</span>
                </button>
                <button class="map-pin pin-ireland" type="button" data-map-target="ireland" aria-label="Ireland">
                  <span>IE</span>
                </button>
                <button class="map-pin pin-australia" type="button" data-map-target="australia" aria-label="Australia">
                  <span>AU</span>
                </button>
              </div>

              <div class="map-insight" data-map-insight>
                <span data-map-region>Europe</span>
                <h3 data-map-title>United Kingdom</h3>
                <p data-map-copy>Focused degrees, one-year masters, strong global recognition, and city-led career exploration.</p>
                <div>
                  <strong data-map-meta>Best for: focused programs, brand recognition, faster postgraduate timelines</strong>
                </div>
              </div>
            </div>

            <div class="destination-grid" data-destination-grid>
              <article class="destination-card reveal" data-group="popular career">
                <img src="https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=640&q=82" alt="Historic university campus in the United Kingdom">
                <div>
                  <span>United Kingdom</span>
                  <h3>Depth, speed, global recognition</h3>
                  <p>Strong fit for focused degrees, one-year masters, and city-led career exploration.</p>
                </div>
              </article>
              <article class="destination-card reveal" data-group="popular career">
                <img src="https://images.unsplash.com/photo-1523580846011-d3a5bc25702b?auto=format&fit=crop&w=640&q=82" alt="Students walking across an international campus">
                <div>
                  <span>Canada</span>
                  <h3>Academic quality with settlement pathways</h3>
                  <p>Balanced planning around programs, co-op options, cost, and long-term eligibility.</p>
                </div>
              </article>
              <article class="destination-card reveal" data-group="career">
                <img src="https://images.unsplash.com/photo-1492538368677-f6e0afe31dcc?auto=format&fit=crop&w=640&q=82" alt="Students studying outdoors on a campus lawn">
                <div>
                  <span>United States</span>
                  <h3>Choice, research, and specialization</h3>
                  <p>Best served by a precise list strategy, strong narrative, and early scholarship planning.</p>
                </div>
              </article>
              <article class="destination-card reveal" data-group="popular value">
                <img src="https://images.unsplash.com/photo-1571260899304-425eee4c7efc?auto=format&fit=crop&w=640&q=82" alt="University student in a library">
                <div>
                  <span>Australia</span>
                  <h3>Practical learning and vibrant cities</h3>
                  <p>Excellent for career-aligned programs, flexible intakes, and structured student support.</p>
                </div>
              </article>
              <article class="destination-card reveal" data-group="value career">
                <img src="https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?auto=format&fit=crop&w=640&q=82" alt="Library shelves at a university">
                <div>
                  <span>Germany</span>
                  <h3>Value-led public education</h3>
                  <p>Ideal when language, academic prerequisites, documentation, and timing are planned early.</p>
                </div>
              </article>
              <article class="destination-card reveal" data-group="value">
                <img src="https://images.unsplash.com/photo-1606761568499-6d2451b23c66?auto=format&fit=crop&w=640&q=82" alt="Graduates standing near campus">
                <div>
                  <span>Ireland</span>
                  <h3>Compact market, strong industry access</h3>
                  <p>Promising for technology, business, healthcare, and applied postgraduate programs.</p>
                </div>
              </article>
            </div>
          </div>
        </div>
      </section>

      <section class="outcomes-section" id="outcomes" aria-labelledby="outcomes-title">
        <div class="container">
          <div class="section-lead centered reveal">
            <span class="eyebrow">Trust-building guidance</span>
            <h2 id="outcomes-title">Clear decisions beat rushed applications.</h2>
            <p>
              Students and families come to us when the internet feels crowded with options.
              We create a clean signal: what is possible, what is wise, and what must happen next.
            </p>
          </div>

          <div class="outcome-band reveal">
            <div>
              <strong>Profile clarity</strong>
              <span>Strengths, gaps, and non-negotiables surfaced early.</span>
            </div>
            <div>
              <strong>University realism</strong>
              <span>Reach, target, and secure options balanced with outcomes.</span>
            </div>
            <div>
              <strong>Parent confidence</strong>
              <span>Costs, timelines, and risks explained in plain language.</span>
            </div>
          </div>

          <div class="testimonial-shell reveal">
            <button class="carousel-btn" type="button" aria-label="Previous testimonial" data-testimonial-prev>
              <i data-lucide="arrow-left"></i>
            </button>
            <div class="testimonial-track" data-testimonial-track>
              <article class="testimonial is-active">
                <p>
                  "OneDegree helped us stop chasing random rankings. The final shortlist finally made sense
                  for my course, budget, and career goals."
                </p>
                <div>
                  <strong>Aarav M.</strong>
                  <span>Masters applicant, UK and Ireland track</span>
                </div>
              </article>
              <article class="testimonial">
                <p>
                  "The essay process felt serious without becoming stressful. I understood my story better
                  after every session."
                </p>
                <div>
                  <strong>Naina R.</strong>
                  <span>Undergraduate applicant, Canada track</span>
                </div>
              </article>
              <article class="testimonial">
                <p>
                  "As parents, we needed numbers, deadlines, and honest advice. They gave us a plan we could
                  actually follow."
                </p>
                <div>
                  <strong>Mehta Family</strong>
                  <span>Engineering applicant, Australia track</span>
                </div>
              </article>
            </div>
            <button class="carousel-btn" type="button" aria-label="Next testimonial" data-testimonial-next>
              <i data-lucide="arrow-right"></i>
            </button>
          </div>
        </div>
      </section>

      <section class="about-section" id="about" aria-labelledby="about-title">
        <div class="container about-grid">
          <div class="about-media reveal">
            <img src="https://images.unsplash.com/photo-1543269865-cbf427effbad?auto=format&fit=crop&w=1050&q=84" alt="Students and advisor in a university setting">
            <span class="badge badge-one">Student victory</span>
            <span class="badge badge-two">Dream alignment</span>
            <span class="badge badge-three">AI-assisted research</span>
          </div>

          <div class="about-copy reveal">
            <span class="eyebrow">Why we do it</span>
            <h2 id="about-title">Because ambition deserves a better map.</h2>
            <p>
              Academic journeys can feel overwhelming: hundreds of countries, changing visa rules,
              hidden costs, unclear rankings, and conflicting advice. OneDegreeAdvisory exists to make
              that complexity navigable.
            </p>
            <p>
              We combine human counseling, structured research, and modern planning tools to help each
              student move from possibility to confident action.
            </p>
            <div class="about-points">
              <span><i data-lucide="check"></i> Thoughtful profile review</span>
              <span><i data-lucide="check"></i> Transparent country comparison</span>
              <span><i data-lucide="check"></i> End-to-end execution support</span>
            </div>
          </div>
        </div>
      </section>

      <section class="insights-section" id="insights" aria-labelledby="insights-title">
        <div class="container">
          <header class="insights-head reveal">
            <div>
              <span class="insights-eyebrow">Insights</span>
              <h2 class="insights-title" id="insights-title">Notes from the admissions desk.</h2>
              <p class="insights-intro">
                Practical reads for applications, tests, visas, scholarships, and the decisions families ask us about every week.
              </p>
            </div>
            <a class="insights-button" href="{{ route('insights') }}">
              <span>All articles</span>
              <i data-lucide="arrow-up-right"></i>
            </a>
          </header>

          <div class="insights-grid">
            <article class="insight-card insight-card-feature reveal">
              <div class="insight-card-media" style="background-image: url('https://images.unsplash.com/photo-1606326608606-aa0b62935f2b?auto=format&fit=crop&w=1400&q=80');">
                <span class="insight-card-tag">Featured</span>
              </div>
              <div class="insight-card-body">
                <span class="insight-card-meta">Strategy &middot; 9 min read</span>
                <h3>The 2026 Ivy League admissions playbook (what changed since SAT-optional).</h3>
                <p>Our heads of strategy unpack the testing reversal, the AI-essay disclosure rules and the four profile pillars that now matter most.</p>
                <a href="{{ route('insights') }}#ivy-league">Read article <i data-lucide="arrow-right"></i></a>
              </div>
            </article>

            <article class="insight-card reveal">
              <div class="insight-card-media" style="background-image: url('https://images.unsplash.com/photo-1606761568499-6d2451b23c66?auto=format&fit=crop&w=900&q=80');"></div>
              <div class="insight-card-body">
                <span class="insight-card-meta">Tests &middot; 5 min</span>
                <h3>Smart paper attempt tips to score high on JEE Advanced 2026.</h3>
                <a href="{{ route('insights') }}#tests">Read article <i data-lucide="arrow-right"></i></a>
              </div>
            </article>

            <article class="insight-card reveal">
              <div class="insight-card-media" style="background-image: url('https://images.unsplash.com/photo-1571260899304-425eee4c7efc?auto=format&fit=crop&w=900&q=80');"></div>
              <div class="insight-card-body">
                <span class="insight-card-meta">Visa &middot; 7 min</span>
                <h3>F-1 visa interviews in 2026 &mdash; what consular officers are actually asking now.</h3>
                <a href="{{ route('insights') }}#visa">Read article <i data-lucide="arrow-right"></i></a>
              </div>
            </article>

            <article class="insight-card reveal">
              <div class="insight-card-media" style="background-image: url('https://images.unsplash.com/photo-1532619675605-1ede6c2ed2b0?auto=format&fit=crop&w=900&q=80');"></div>
              <div class="insight-card-body">
                <span class="insight-card-meta">Finance &middot; 6 min</span>
                <h3>The complete 2026 guide to merit scholarships at US private universities.</h3>
                <a href="{{ route('insights') }}#finance">Read article <i data-lucide="arrow-right"></i></a>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section class="contact-section" id="contact" aria-labelledby="contact-title">
        <div class="container contact-grid">
          <aside class="contact-card reveal">
            <span class="eyebrow">Contact us</span>
            <h2 id="contact-title">Get a premium profile review.</h2>
            <p>
              Tell us where you are in the journey. We will respond with the next practical step:
              diagnostic call, shortlist review, or application roadmap.
            </p>
            <div class="contact-list">
              <a href="mailto:admissions@onedegreeadvisory.com">
                <i data-lucide="mail"></i>
                admissions@onedegreeadvisory.com
              </a>
              <a href="tel:8233365888">
                <i data-lucide="phone"></i>
                8233365888
              </a>
              <a href="{{ route('contact') }}">
                <i data-lucide="map-pin"></i>
                A-16A, Van Vihar colony, Tonk Road, Jaipur, Rajasthan, 302018
              </a>
              <a href="#method">
                <i data-lucide="clock"></i>
                Intake planning, application reviews, visa readiness
              </a>
            </div>
          </aside>

          <form class="consult-form reveal" data-consult-form>
            <div class="form-row">
              <label>
                <span>Name</span>
                <input name="name" type="text" autocomplete="name" required placeholder="Your full name">
              </label>
              <label>
                <span>Email</span>
                <input name="email" type="email" autocomplete="email" required placeholder="you@example.com">
              </label>
            </div>
            <div class="form-row">
              <div class="contact-phone" data-phone-group>
                <span class="contact-phone-label">Phone</span>
                <div class="contact-phone-fields">
                  <div class="contact-phone-country" data-country-select></div>
                  <input type="hidden" name="phoneCountry" data-phone-country-input value="+91">
                  <input name="phone" type="tel" data-phone-input autocomplete="tel" inputmode="numeric" placeholder="Phone number" maxlength="10" aria-label="Phone number (10 digits)">
                </div>
              </div>
              <label>
                <span>Current stage</span>
                <select name="stage" required>
                  <option value="">Select one</option>
                  <option>Exploring countries</option>
                  <option>Shortlisting universities</option>
                  <option>Preparing applications</option>
                  <option>Visa or pre-departure</option>
                </select>
              </label>
            </div>
            <label>
              <span>What are you planning?</span>
              <textarea name="message" rows="5" required placeholder="Destination, program level, intake, and anything you are unsure about"></textarea>
            </label>
            <label class="consent">
              <input type="checkbox" required>
              <span>I agree to be contacted about OneDegreeAdvisory services by email, phone, or messaging.</span>
            </label>
            <button class="btn btn-primary" type="submit">
              <span>Submit Enquiry</span>
              <i data-lucide="send"></i>
            </button>
            <p class="form-status" role="status" aria-live="polite" data-form-status></p>
          </form>
        </div>
      </section>

      <section class="footer-marquee" aria-hidden="true">
        <div class="footer-marquee-viewport">
          <div class="footer-marquee-track">
            <div class="footer-marquee-group">
              <img src="https://images.unsplash.com/photo-1606761568499-6d2451b23c66?auto=format&fit=crop&w=520&q=78" alt="" loading="lazy">
              <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=520&q=78" alt="" loading="lazy">
              <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=520&q=78" alt="" loading="lazy">
              <img src="https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?auto=format&fit=crop&w=520&q=78" alt="" loading="lazy">
              <img src="https://images.unsplash.com/photo-1492538368677-f6e0afe31dcc?auto=format&fit=crop&w=520&q=78" alt="" loading="lazy">
              <img src="https://images.unsplash.com/photo-1571260899304-425eee4c7efc?auto=format&fit=crop&w=520&q=78" alt="" loading="lazy">
              <img src="https://images.unsplash.com/photo-1543269865-cbf427effbad?auto=format&fit=crop&w=520&q=78" alt="" loading="lazy">
            </div>
            <div class="footer-marquee-group">
              <img src="https://images.unsplash.com/photo-1606761568499-6d2451b23c66?auto=format&fit=crop&w=520&q=78" alt="" loading="lazy">
              <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=520&q=78" alt="" loading="lazy">
              <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=520&q=78" alt="" loading="lazy">
              <img src="https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?auto=format&fit=crop&w=520&q=78" alt="" loading="lazy">
              <img src="https://images.unsplash.com/photo-1492538368677-f6e0afe31dcc?auto=format&fit=crop&w=520&q=78" alt="" loading="lazy">
              <img src="https://images.unsplash.com/photo-1571260899304-425eee4c7efc?auto=format&fit=crop&w=520&q=78" alt="" loading="lazy">
              <img src="https://images.unsplash.com/photo-1543269865-cbf427effbad?auto=format&fit=crop&w=520&q=78" alt="" loading="lazy">
            </div>
          </div>
        </div>
      </section>
    </main>
@endsection