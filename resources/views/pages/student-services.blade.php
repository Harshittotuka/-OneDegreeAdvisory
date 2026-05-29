@php
    $pageTitle = 'Student Services | One Degree Advisory';
    $pageDescription = 'Complete student support services — WorldGrad pathways, psychometric tests, profile building, internships, interview prep, accommodation, visa & ticketing, and ongoing support.';
    $activeNav = 'services';
    $mainId = 'main';

    $services = [
        [
            'title' => 'The WorldGrad Pathways',
            'image' => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?auto=format&fit=crop&w=600&h=600&q=80',
            'alt' => 'University campus pathway for international students',
            'text' => 'The WorldGrad Pathways program helps students access internationally recognized universities through structured academic pathways. It offers a seamless transition into undergraduate and postgraduate studies while improving academic readiness and career opportunities.',
        ],
        [
            'title' => 'Psychometric Tests',
            'image' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?auto=format&fit=crop&w=600&h=600&q=80',
            'alt' => 'Student completing a psychometric assessment',
            'text' => 'Our psychometric assessments help students identify their strengths, interests, personality traits, and career potential. These scientifically designed tests guide students toward suitable academic programs and future career paths.',
        ],
        [
            'title' => 'Profile Building',
            'image' => 'https://images.unsplash.com/photo-1517048676732-d65bc937f952?auto=format&fit=crop&w=600&h=600&q=80',
            'alt' => 'Students building academic profiles together',
            'text' => 'A strong student profile increases the chances of admission into top universities. We assist students in developing impactful academic and extracurricular profiles through certifications, projects, achievements, volunteering, and leadership activities.',
        ],
        [
            'title' => 'Internships',
            'image' => 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=600&h=600&q=80',
            'alt' => 'Students gaining industry experience through internships',
            'text' => 'We help students gain practical industry exposure through internship opportunities in various fields. Internships enhance professional skills, strengthen resumes, and prepare students for future global careers.',
        ],
        [
            'title' => 'Admission Interview Prep',
            'image' => 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=600&h=600&q=80',
            'alt' => 'Mentor coaching a student for an admission interview',
            'text' => 'University interviews can play a crucial role in the admission process. Our expert mentors provide interview training, mock sessions, communication improvement, and confidence-building techniques to help students perform successfully.',
        ],
        [
            'title' => 'University Accommodation',
            'image' => 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=600&h=600&q=80',
            'alt' => 'Comfortable student accommodation abroad',
            'text' => 'Finding safe and comfortable accommodation abroad is essential for students. We assist in selecting suitable housing options including university hostels, private apartments, and shared accommodations based on budget and preferences.',
        ],
        [
            'title' => 'Visa & Ticketing',
            'image' => 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=600&h=600&q=80',
            'alt' => 'Travel documents and tickets for studying abroad',
            'text' => 'Our visa assistance services guide students through documentation, application procedures, and interview preparation. We also help with travel planning and ticket booking to ensure a smooth journey to their study destination.',
        ],
        [
            'title' => 'Student Support Services',
            'image' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=600&h=600&q=80',
            'alt' => 'Ongoing support for students before and after arrival',
            'text' => 'We provide continuous support to students before and after arrival abroad. From travel guidance and orientation to emergency assistance and academic support, our team remains available throughout the student\'s international education journey.',
        ],
    ];
@endphp

@extends('layouts.app')

@section('content')
<main id="main" class="sserv-page">

  {{-- ───────────────────────── INTRO ───────────────────────── --}}
  <section class="sserv-intro" aria-labelledby="sserv-intro-title">
    <div class="container sserv-intro__inner">
      <div class="sserv-intro__copy reveal">
        <span class="adm-eyebrow adm-eyebrow--dark">Student Services</span>
        <h1 id="sserv-intro-title">Complete support for every step of your study&#8209;abroad journey.</h1>
        <p>
          We provide complete student support services designed to simplify every step of your international education
          journey. From career guidance to accommodation and visa assistance, our dedicated team ensures students receive
          personalized support for a smooth and successful experience abroad.
        </p>
        <a class="adm-btn adm-btn--gold" href="{{ route('contact') }}">
          <span>Talk to our team</span>
          <i data-lucide="arrow-up-right" aria-hidden="true"></i>
        </a>
      </div>

      <aside class="sserv-intro__media reveal" aria-hidden="true">
        <div class="sserv-intro__frame">
          <img src="https://images.unsplash.com/photo-1543269865-cbf427effbad?auto=format&fit=crop&w=1000&q=82" alt="">
        </div>
        <div class="sserv-intro__badge">
          <span class="sserv-intro__badge-icon"><i data-lucide="life-buoy"></i></span>
          <span class="sserv-intro__badge-copy"><strong>End-to-end support</strong><small>before, during &amp; after arrival</small></span>
        </div>
      </aside>
    </div>
  </section>

  {{-- ───────────────────────── SERVICES GRID ───────────────────────── --}}
  <section class="sserv-grid-sec" aria-labelledby="sserv-grid-title">
    <div class="container">
      <div class="sserv-grid-sec__head reveal">
        <h2 id="sserv-grid-title">Our student support services</h2>
      </div>

      <div class="sserv-grid">
        @foreach ($services as $i => $service)
          <article class="sserv-card reveal" style="transition-delay: {{ ($i % 4) * 70 }}ms;">
            <div class="sserv-card__media">
              <img src="{{ $service['image'] }}" alt="{{ $service['alt'] }}" loading="lazy">
            </div>
            <h3>{{ $service['title'] }}</h3>
            <p>{{ $service['text'] }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ───────────────────────── CTA ───────────────────────── --}}
  <section class="adm-cta" aria-labelledby="sserv-cta-title">
    <div class="container">
      <div class="adm-cta__inner reveal">
        <span class="adm-cta__crest" aria-hidden="true"><i data-lucide="life-buoy"></i></span>
        <span class="adm-eyebrow">Here to help</span>
        <h2 id="sserv-cta-title">From your first query to your first day abroad — we&rsquo;ve got you.</h2>
        <p>Tell us where you are in your journey and what you need. Our team will map the right support for you.</p>
        <div class="adm-cta__actions">
          <a class="adm-btn adm-btn--gold" href="{{ route('contact') }}">
            <span>Book a consultation</span>
            <i data-lucide="message-circle" aria-hidden="true"></i>
          </a>
          <a class="adm-btn adm-btn--ghost" href="https://wa.me/{{ config('site.contact.phone_e164') }}" target="_blank" rel="noopener">
            <i data-lucide="phone" aria-hidden="true"></i>
            <span>{{ config('site.contact.phone') }}</span>
          </a>
        </div>
      </div>
    </div>
  </section>

</main>
@endsection
