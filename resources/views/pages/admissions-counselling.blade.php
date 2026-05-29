@php
    $pageTitle = 'Admissions Counselling | One Degree Advisory';
    $pageDescription = 'Personalized admissions counselling for Australia, Canada, Europe, New Zealand, UK, USA, masters and medicine admissions abroad.';
    $activeNav = 'services';
    $mainId = 'main';

    $tracks = [
        [
            'title' => 'Australian Admissions',
            'kicker' => 'Australia',
            'icon' => 'map-pin',
            'image' => asset('assets/heroes/australia.jpg'),
            'alt' => 'Australian university campus and city skyline',
            'text' => 'Australia is one of the most preferred destinations for international education due to its globally recognized universities and excellent career opportunities. We assist students with university selection, course guidance, application processing, and visa support for successful admissions in Australia.',
        ],
        [
            'title' => 'Canadian Admissions',
            'kicker' => 'Canada',
            'icon' => 'leaf',
            'image' => asset('assets/heroes/canada.jpg'),
            'alt' => 'Students exploring study opportunities in Canada',
            'text' => 'Canada offers world-class education, affordable tuition, and excellent post-study work opportunities. Our counselling services help students choose the right colleges and universities while guiding them through applications, documentation, and admission procedures.',
        ],
        [
            'title' => 'Europe Admissions',
            'kicker' => 'Europe',
            'icon' => 'landmark',
            'image' => asset('assets/heroes/europe.jpg'),
            'alt' => 'Historic European university buildings',
            'text' => 'European universities provide high-quality education, globally accepted degrees, and affordable study options. We help students explore top institutions across Europe and assist with admissions, entrance exams, documentation, and visa requirements.',
        ],
        [
            'title' => 'New Zealand Admissions',
            'kicker' => 'New Zealand',
            'icon' => 'mountain',
            'image' => asset('assets/heroes/new-zealand.jpg'),
            'alt' => 'New Zealand landscape near an international study destination',
            'text' => 'New Zealand is known for its student-friendly environment and internationally recognized qualifications. Our experts provide complete support for course selection, university applications, scholarships, and student visa processes.',
        ],
        [
            'title' => 'UK Admissions',
            'kicker' => 'United Kingdom',
            'icon' => 'building-2',
            'image' => asset('assets/heroes/uk.jpg'),
            'alt' => 'University buildings in the United Kingdom',
            'text' => 'The United Kingdom is home to some of the world\'s leading universities and academic institutions. We guide students through every stage of the UK admission process including university selection, SOP preparation, applications, and visa assistance.',
        ],
        [
            'title' => 'USA Admissions',
            'kicker' => 'United States',
            'icon' => 'badge-check',
            'image' => asset('assets/heroes/usa.jpg'),
            'alt' => 'University campus in the United States',
            'text' => 'Studying in the USA offers students access to globally ranked universities, advanced research opportunities, and diverse career pathways. Our counselling services help students with university shortlisting, application strategies, test preparation guidance, and visa support.',
        ],
        [
            'title' => 'Master\'s Admissions',
            'kicker' => 'Postgraduate',
            'icon' => 'graduation-cap',
            'image' => 'https://images.unsplash.com/photo-1523580846011-d3a5bc25702b?auto=format&fit=crop&w=900&q=82',
            'alt' => 'Postgraduate students walking across an international campus',
            'text' => 'We assist students aspiring to pursue postgraduate and master\'s programs abroad in various specializations. Our counsellors help with program selection, profile evaluation, university applications, SOPs, recommendation letters, and scholarship guidance.',
        ],
        [
            'title' => 'Medicine Admissions',
            'kicker' => 'Medicine',
            'icon' => 'stethoscope',
            'image' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=900&q=82',
            'alt' => 'Medical students preparing for clinical learning',
            'text' => 'Medical education abroad provides students with international exposure and advanced clinical learning opportunities. We support aspiring medical students with university selection, entrance exam preparation, application procedures, and admission guidance for leading medical universities worldwide.',
        ],
    ];

    $steps = [
        ['label' => 'Profile clarity', 'icon' => 'scan-search', 'text' => 'We understand academics, goals, budget, preferred countries, deadlines, and eligibility before recommending universities.'],
        ['label' => 'University selection', 'icon' => 'building-2', 'text' => 'Your shortlist is built around course fit, rankings, outcomes, scholarships, intakes, and realistic admission chances.'],
        ['label' => 'Application readiness', 'icon' => 'file-check-2', 'text' => 'We guide applications, SOPs, documents, recommendation letters, entrance exams, and submission timelines.'],
        ['label' => 'Visa and decision support', 'icon' => 'plane-takeoff', 'text' => 'After offers arrive, we help with offer comparison, visa documentation, interview readiness, and next steps.'],
    ];

    $assurances = [
        'Country, course and university alignment',
        'Documents, SOPs and recommendation letters',
        'Scholarships, entrance exams and visa readiness',
    ];
@endphp

@extends('layouts.app')

@section('content')
<main id="main" class="adm-page">

  {{-- ───────────────────────── HERO ───────────────────────── --}}
  <section class="adm-hero" aria-labelledby="adm-hero-title">
    <span class="adm-hero__glow" aria-hidden="true"></span>
    <span class="adm-hero__rule" aria-hidden="true"></span>
    <div class="container adm-hero__inner">
      <div class="adm-hero__copy reveal">
        <span class="adm-eyebrow">University Admissions Counselling</span>
        <h1 id="adm-hero-title">Admissions counselling for every <em>study&#8209;abroad</em> dream.</h1>
        <p>
          From the first conversation to your offer letter, our senior counsellors guide every decision with clarity and
          care — university selection, applications, scholarships, and visas — so you apply to the world's leading
          institutions with confidence.
        </p>
        <div class="adm-hero__actions">
          <a class="adm-btn adm-btn--gold" href="{{ route('contact') }}">
            <span>Book free counselling</span>
            <i data-lucide="arrow-up-right" aria-hidden="true"></i>
          </a>
        </div>
      </div>

      <aside class="adm-hero__media reveal" aria-hidden="true">
        <div class="adm-hero__frame">
          <img src="{{ asset('assets/heroes/uk.jpg') }}" alt="">
        </div>
        <div class="adm-hero__badge">
          <span class="adm-hero__badge-icon"><i data-lucide="award"></i></span>
          <span class="adm-hero__badge-copy"><strong>One senior advisor</strong><small>with you, file to offer</small></span>
        </div>
      </aside>
    </div>
  </section>

  {{-- ───────────────────────── OVERVIEW ───────────────────────── --}}
  <section class="adm-overview" aria-labelledby="adm-overview-title">
    <div class="container adm-overview__grid">
      <figure class="adm-overview__media reveal">
        <img src="{{ asset('assets/heroes/canada.jpg') }}" alt="Students planning international admissions with a counsellor" loading="lazy">
        <figcaption class="adm-overview__stamp">
          <i data-lucide="route" aria-hidden="true"></i>
          <span>One journey, carefully sequenced</span>
        </figcaption>
      </figure>

      <div class="adm-overview__copy reveal">
        <span class="adm-eyebrow adm-eyebrow--dark">Guided from shortlist to submit</span>
        <h2 id="adm-overview-title">A clear plan before every application.</h2>
        <p>
          Studying abroad is a major decision for students and families. We make it calm and considered — aligning
          country, course, university, documents, scholarships, entrance exams, and visa requirements into one
          practical, beautifully organised roadmap.
        </p>
        <ul class="adm-checklist">
          @foreach ($assurances as $assurance)
            <li><i data-lucide="check" aria-hidden="true"></i><span>{{ $assurance }}</span></li>
          @endforeach
        </ul>
      </div>
    </div>
  </section>

  {{-- ───────────────────────── TRACKS ───────────────────────── --}}
  <section class="adm-tracks" id="admission-tracks" aria-labelledby="adm-tracks-title">
    <div class="container">
      <div class="adm-head reveal">
        <span class="adm-eyebrow adm-eyebrow--dark">Admission Pathways</span>
        <h2 id="adm-tracks-title">Choose the counselling track that fits your destination and degree.</h2>
        <p>Every track includes personal guidance for university selection, applications, documents, entrance requirements, offer decisions, and visa preparation.</p>
      </div>

      <div class="adm-track-grid">
        @foreach ($tracks as $track)
          <article class="adm-track reveal">
            <div class="adm-track__media">
              <img src="{{ $track['image'] }}" alt="{{ $track['alt'] }}" loading="lazy">
              <span class="adm-track__icon" aria-hidden="true"><i data-lucide="{{ $track['icon'] }}"></i></span>
              <span class="adm-track__kicker">{{ $track['kicker'] }}</span>
            </div>
            <div class="adm-track__body">
              <h3>{{ $track['title'] }}</h3>
              <p>{{ $track['text'] }}</p>
            </div>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ───────────────────────── METHOD ───────────────────────── --}}
  <section class="adm-method" aria-labelledby="adm-method-title">
    <span class="adm-method__glow" aria-hidden="true"></span>
    <div class="container adm-method__layout">
      <div class="adm-method__lead reveal">
        <span class="adm-eyebrow">How counselling works</span>
        <h2 id="adm-method-title">Every student gets a practical admission roadmap.</h2>
        <p>We combine personal counselling with deadline discipline, so you always know what to prepare, why it matters, and exactly what happens next.</p>
        <a class="adm-btn adm-btn--gold" href="{{ route('contact') }}">
          <span>Start your profile review</span>
          <i data-lucide="arrow-up-right" aria-hidden="true"></i>
        </a>
      </div>

      <ol class="adm-method__steps">
        @foreach ($steps as $index => $step)
          <li class="adm-step reveal">
            <span class="adm-step__num">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
            <span class="adm-step__icon" aria-hidden="true"><i data-lucide="{{ $step['icon'] }}"></i></span>
            <div class="adm-step__copy">
              <h3>{{ $step['label'] }}</h3>
              <p>{{ $step['text'] }}</p>
            </div>
          </li>
        @endforeach
      </ol>
    </div>
  </section>

  {{-- ───────────────────────── CTA ───────────────────────── --}}
  <section class="adm-cta" aria-labelledby="adm-cta-title">
    <div class="container">
      <div class="adm-cta__inner reveal">
        <span class="adm-cta__crest" aria-hidden="true"><i data-lucide="graduation-cap"></i></span>
        <span class="adm-eyebrow">Ready to begin?</span>
        <h2 id="adm-cta-title">Build your admissions plan with One Degree Advisory.</h2>
        <p>Share your goals, preferred countries, and current profile. We will help you identify the next best step.</p>
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
