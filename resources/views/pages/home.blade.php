@php
    $pageTitle = 'One Degree Advisory | Global Education Advisory';
    $pageDescription = 'One Degree Advisory is a premium global education advisory helping students choose universities, strengthen profiles, apply with confidence, and prepare for arrival.';
    $activeNav = 'home';
    $mainId = 'main';
@endphp

@extends('layouts.app')

@section('content')
<main id="main" class="home-page">
      @include('partials.home.hero', ['data' => $hero, 'edit' => ($heroEdit ?? false)])

      <section class="signal-strip" aria-labelledby="signal-title">
        <div class="container">
          <div class="section-lead centered reveal">
            <span class="eyebrow">The One Degree difference</span>
            <h2 id="signal-title">More than an application service.</h2>
            <p>
              Strategy, end-to-end execution, and human guidance — so every decision along the way
              is made with clarity and confidence.
            </p>
          </div>

          <div class="signal-grid">
            <div class="signal reveal">
              <span class="signal-icon" aria-hidden="true"><i data-lucide="compass"></i></span>
              <strong>Strategy before forms</strong>
              <p>Every shortlist begins with profile fit, budget clarity, career intent, and risk review.</p>
            </div>
            <div class="signal reveal">
              <span class="signal-icon" aria-hidden="true"><i data-lucide="route"></i></span>
              <strong>Application-to-arrival</strong>
              <p>University selection, essays, scholarships, loans, visa prep, and pre-departure support.</p>
            </div>
            <div class="signal reveal">
              <span class="signal-icon" aria-hidden="true"><i data-lucide="heart-handshake"></i></span>
              <strong>Human advisory</strong>
              <p>Thoughtful counseling for students and parents, with each next step made visible.</p>
            </div>
            <div class="signal reveal">
              <span class="signal-icon" aria-hidden="true"><i data-lucide="flag"></i></span>
              <strong>Decision checkpoints</strong>
              <p>A guided journey that turns uncertainty into a sequence of confident choices.</p>
            </div>
          </div>
        </div>
      </section>

      @include('partials.home.method-compass')

      <section class="audience-section" aria-labelledby="audience-title">
        <div class="container wide-container">
          <div class="section-lead split reveal">
            <div>
              <span class="eyebrow">Guidance for every stage</span>
              <h2 id="audience-title">Uncertain about your future? We are here to help.</h2>
            </div>
            <p>
              Whether you are choosing subjects, planning postgraduate study, or switching careers,
              One Degree Advisory turns a wide world of options into a personally sequenced plan.
            </p>
          </div>

          <div class="audience-grid">
            {{--
            <article class="audience-card audience-card-mbbs reveal" data-mbbs-card tabindex="0" aria-expanded="false" aria-controls="mbbs-country-panel">
              <img src="https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=640&q=82" alt="Medical students planning international study pathways" loading="lazy" decoding="async">
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
            --}}
            <article class="audience-card reveal">
              <img src="https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=640&q=82" alt="School students discussing study options" loading="lazy" decoding="async">
              <div>
                <h3>School Students</h3>
                <p>Subject choices, profile building, summer plans, and early university direction.</p>
              </div>
              <a href="#contact" aria-label="Explore support for school students">
                <i data-lucide="arrow-right"></i>
              </a>
            </article>
            <article class="audience-card reveal">
              <img src="https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=640&q=82" alt="College students working together" loading="lazy" decoding="async">
              <div>
                <h3>College Students</h3>
                <p>Masters planning, internships, research direction, portfolio polish, and tests.</p>
              </div>
              <a href="#contact" aria-label="Explore support for college students">
                <i data-lucide="arrow-right"></i>
              </a>
            </article>
            {{--
            <article class="audience-card reveal">
              <img src="https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=640&q=82" alt="Graduate celebrating at university" loading="lazy" decoding="async">
              <div>
                <h3>Graduates</h3>
                <p>Career-led university choices, essays, recommendation strategy, and funding.</p>
              </div>
              <a href="#contact" aria-label="Explore support for graduates">
                <i data-lucide="arrow-right"></i>
              </a>
            </article>
            --}}
            <article class="audience-card reveal">
              <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=640&q=82" alt="Working professionals in advisory conversation" loading="lazy" decoding="async">
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

      <section class="whyus-section" aria-labelledby="whyus-title">
        <div class="container edge-container">
          <div class="section-lead centered reveal">
            <span class="eyebrow">Why One Degree</span>
            <h2 id="whyus-title">What Makes us The Best Study Abroad Consultants?</h2>
            <p>Amid the sea of education consultants, what sets us apart? Here's the answer:</p>
          </div>

          <div class="whyus-carousel">
            <div class="whyus-grid">
            <article class="whyus-card reveal">
              <span class="whyus-icon"><i data-lucide="headphones"></i></span>
              <h3>Comprehensive Support</h3>
              <p>Get a dedicated consultant, founding and support team for fast and reliable process</p>
            </article>
            <article class="whyus-card reveal">
              <span class="whyus-icon"><i data-lucide="notebook-pen"></i></span>
              <h3>Unlimited Revisions</h3>
              <p>Unlimited edits to perfect your documents, no matter how many reviews it takes</p>
            </article>
            <article class="whyus-card reveal">
              <span class="whyus-icon"><i data-lucide="hand-helping"></i></span>
              <h3>Expert Guidance</h3>
              <p>Our Experts know exactly what top schools seek and help you present your best</p>
            </article>
            <article class="whyus-card reveal">
              <span class="whyus-icon"><i data-lucide="piggy-bank"></i></span>
              <h3>Affordable Pricing</h3>
              <p>Designed for recent grads, our services are both effective and budget-friendly</p>
            </article>
            <article class="whyus-card reveal">
              <span class="whyus-icon"><i data-lucide="thumbs-up"></i></span>
              <h3>Personalized Services</h3>
              <p>Get fully personalized essays &amp; materials through One-on-One Consultations</p>
            </article>
            <article class="whyus-card reveal">
              <span class="whyus-icon"><i data-lucide="timer"></i></span>
              <h3>Fast Turnaround</h3>
              <p>Benefit from a quick 36 hrs response time on edits to keep your application on track</p>
            </article>
            </div>
          </div>
        </div>
      </section>

      {{--
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
                <img src="https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=640&q=82" alt="Historic university campus in the United Kingdom" loading="lazy" decoding="async">
                <div>
                  <span>United Kingdom</span>
                  <h3>Depth, speed, global recognition</h3>
                  <p>Strong fit for focused degrees, one-year masters, and city-led career exploration.</p>
                </div>
              </article>
              <article class="destination-card reveal" data-group="popular career">
                <img src="https://images.unsplash.com/photo-1523580846011-d3a5bc25702b?auto=format&fit=crop&w=640&q=82" alt="Students walking across an international campus" loading="lazy" decoding="async">
                <div>
                  <span>Canada</span>
                  <h3>Academic quality with settlement pathways</h3>
                  <p>Balanced planning around programs, co-op options, cost, and long-term eligibility.</p>
                </div>
              </article>
              <article class="destination-card reveal" data-group="career">
                <img src="https://images.unsplash.com/photo-1492538368677-f6e0afe31dcc?auto=format&fit=crop&w=640&q=82" alt="Students studying outdoors on a campus lawn" loading="lazy" decoding="async">
                <div>
                  <span>United States</span>
                  <h3>Choice, research, and specialization</h3>
                  <p>Best served by a precise list strategy, strong narrative, and early scholarship planning.</p>
                </div>
              </article>
              <article class="destination-card reveal" data-group="popular value">
                <img src="https://images.unsplash.com/photo-1571260899304-425eee4c7efc?auto=format&fit=crop&w=640&q=82" alt="University student in a library" loading="lazy" decoding="async">
                <div>
                  <span>Australia</span>
                  <h3>Practical learning and vibrant cities</h3>
                  <p>Excellent for career-aligned programs, flexible intakes, and structured student support.</p>
                </div>
              </article>
              <article class="destination-card reveal" data-group="value career">
                <img src="https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?auto=format&fit=crop&w=640&q=82" alt="Library shelves at a university" loading="lazy" decoding="async">
                <div>
                  <span>Germany</span>
                  <h3>Value-led public education</h3>
                  <p>Ideal when language, academic prerequisites, documentation, and timing are planned early.</p>
                </div>
              </article>
              <article class="destination-card reveal" data-group="value">
                <img src="https://images.unsplash.com/photo-1606761568499-6d2451b23c66?auto=format&fit=crop&w=640&q=82" alt="Graduates standing near campus" loading="lazy" decoding="async">
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
      --}}

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
                  "One Degree helped us stop chasing random rankings. The final shortlist finally made sense
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
          <div class="about-head reveal">
            <span class="eyebrow">Why we do it</span>
            <h2 id="about-title">Because ambition deserves a better map.</h2>
          </div>

          <div class="about-media reveal">
            <img src="https://images.unsplash.com/photo-1543269865-cbf427effbad?auto=format&fit=crop&w=1050&q=84" alt="Students and advisor in a university setting" loading="lazy" decoding="async">
            <span class="badge badge-one">Student victory</span>
            <span class="badge badge-two">Dream alignment</span>
            <span class="badge badge-three">AI-assisted research</span>
          </div>

          <div class="about-copy about-body reveal">
            <p>
              Academic journeys can feel overwhelming: hundreds of countries, changing visa rules,
              hidden costs, unclear rankings, and conflicting advice. One Degree Advisory exists to make
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
            <a class="insights-button" href="{{ route('blog.index') }}">
              <span>Read the journal</span>
              <i data-lucide="arrow-up-right"></i>
            </a>
          </header>

          @php $featureInsight = $insights[0] ?? null; @endphp
          <div class="insights-grid">
            @if($featureInsight)
              <article class="insight-card insight-card-feature reveal">
                <div class="insight-card-media" style="background-image: url('{{ $featureInsight['image'] }}');">
                  <span class="insight-card-tag">Featured</span>
                </div>
                <div class="insight-card-body">
                  <span class="insight-card-meta">{{ $featureInsight['category'] }}@if(! empty($featureInsight['read_time'])) &middot; {{ $featureInsight['read_time'] }} min read@endif</span>
                  <h3>{{ $featureInsight['title'] }}</h3>
                  @if(! empty($featureInsight['excerpt']))
                    <p>{{ $featureInsight['excerpt'] }}</p>
                  @endif
                  <a href="{{ \App\Support\BlogContent::url($featureInsight) }}">Read the article <i data-lucide="arrow-right"></i></a>
                </div>
              </article>
            @endif

            @foreach(array_slice($insights, 1, 3) as $insight)
              <article class="insight-card reveal">
                <div class="insight-card-media" style="background-image: url('{{ $insight['image'] }}');"></div>
                <div class="insight-card-body">
                  <span class="insight-card-meta">{{ $insight['category'] }}@if(! empty($insight['read_time'])) &middot; {{ $insight['read_time'] }} min@endif</span>
                  <h3>{{ $insight['title'] }}</h3>
                  <a href="{{ \App\Support\BlogContent::url($insight) }}">Read the article <i data-lucide="arrow-right"></i></a>
                </div>
              </article>
            @endforeach

            <a class="insight-card insight-card-more reveal" href="{{ route('blog.index') }}" aria-label="Read more articles on the journal">
              <span class="insight-card-more-icon" aria-hidden="true"><i data-lucide="newspaper"></i></span>
              <span class="insight-card-more-meta">The journal</span>
              <span class="insight-card-more-title">Read more articles</span>
              <span class="insight-card-more-link">Read More <i data-lucide="arrow-right"></i></span>
            </a>
          </div>
        </div>
      </section>

      <section class="contact-section" id="contact" aria-labelledby="contact-title">
        <div class="container contact-grid" data-contact-collapsible>
          <button type="button" class="contact-card-handle" aria-expanded="false" aria-controls="home-contact-card" aria-label="Show contact details">
            <span>Contact us</span>
          </button>
          <aside class="contact-card reveal" id="home-contact-card">
            <button type="button" class="contact-card-close" aria-label="Hide contact details">
              <i data-lucide="x"></i>
            </button>
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
              <a href="https://wa.me/{{ config('site.contact.phone_e164') }}" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
                <i data-lucide="phone"></i>
                {{ config('site.contact.phone') }}
              </a>
              <a href="https://www.google.com/maps/search/?api=1&amp;query=26.8692893,75.7895342" target="_blank" rel="noopener" aria-label="Open One Degree Advisory office in Google Maps">
                <i data-lucide="map-pin"></i>
                A-16A, Van Vihar colony, Tonk Road, Jaipur, Rajasthan, 302018
              </a>
              <a href="{{ route('study-abroad') }}">
                <i data-lucide="clock"></i>
                Intake planning, application reviews, visa readiness
              </a>
            </div>

            <div class="contact-aside-socials">
              <span class="contact-aside-socials-label">Follow us</span>
              @include('partials.socials', ['variant' => 'aside'])
            </div>
          </aside>

          <div class="contact-form-panel reveal">
            @include('partials.contact-form', ['formId' => 'home'])
          </div>
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
