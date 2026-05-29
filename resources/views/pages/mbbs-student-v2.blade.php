@php
    $pageTitle = 'MBBS Abroad Route Desk | One Degree Advisory';
    $pageDescription = 'A focused MBBS abroad comparison page with six destination desks, admission support services, and profile-led counselling for Indian students.';
    $activeNav = 'mbbs-v2';
    $mainId = 'mbbs-v2-main';

    $mbbsCountries = [
        [
            'slug' => 'russia',
            'name' => 'Russia',
            'desk' => 'Largest MBBS corridor',
            'capital' => 'Moscow',
            'duration' => '6 years',
            'medium' => 'English + clinical Russian',
            'feeLow' => 3500,
            'feeHigh' => 6000,
            'intake' => 'Sep',
            'note' => 'Established public medical universities, large Indian student communities, and strong hospital exposure in bigger cities.',
            'flag' => 'ru',
        ],
        [
            'slug' => 'georgia',
            'name' => 'Georgia',
            'desk' => 'European-track curriculum',
            'capital' => 'Tbilisi',
            'duration' => '6 years',
            'medium' => 'English',
            'feeLow' => 4500,
            'feeHigh' => 7000,
            'intake' => 'Sep / Feb',
            'note' => 'Modern campuses, English-medium teaching, and a compact student-life setup for families who want a European route.',
            'flag' => 'ge',
        ],
        [
            'slug' => 'kazakhstan',
            'name' => 'Kazakhstan',
            'desk' => 'Modern, low-cost route',
            'capital' => 'Astana',
            'duration' => '5-6 years',
            'medium' => 'English',
            'feeLow' => 3000,
            'feeHigh' => 4800,
            'intake' => 'Sep',
            'note' => 'Affordable tuition, Indian food access in major cities, and medical universities with practical hospital networks.',
            'flag' => 'kz',
        ],
        [
            'slug' => 'kyrgyzstan',
            'name' => 'Kyrgyzstan',
            'desk' => 'Most affordable corridor',
            'capital' => 'Bishkek',
            'duration' => '5-6 years',
            'medium' => 'English',
            'feeLow' => 2800,
            'feeHigh' => 4200,
            'intake' => 'Sep',
            'note' => 'Budget-led option with established Indian batches, hostel access, and a familiar consultant ecosystem.',
            'flag' => 'kg',
        ],
        [
            'slug' => 'tajikistan',
            'name' => 'Tajikistan',
            'desk' => 'Emerging pathway',
            'capital' => 'Dushanbe',
            'duration' => '6 years',
            'medium' => 'English',
            'feeLow' => 3200,
            'feeHigh' => 4800,
            'intake' => 'Sep',
            'note' => 'Smaller batches, state medical institutions, and an improving route for families comparing Central Asia carefully.',
            'flag' => 'tj',
        ],
        [
            'slug' => 'uzbekistan',
            'name' => 'Uzbekistan',
            'desk' => 'Heritage medical schools',
            'capital' => 'Tashkent',
            'duration' => '6 years',
            'medium' => 'English',
            'feeLow' => 3500,
            'feeHigh' => 5500,
            'intake' => 'Sep / Oct',
            'note' => 'A practical balance of cost, travel time, established universities, and a growing Indian student network.',
            'flag' => 'uz',
        ],
    ];

    $fmtFee = fn ($v) => '$' . number_format($v, 0, '.', ',');

    $services = [
        ['icon' => 'handshake', 'title' => 'Introduce', 'copy' => 'We introduce you to the universities and countries that fit your preferred course.'],
        ['icon' => 'users', 'title' => 'Advice', 'copy' => 'We advise you on the strongest university fit according to your profile.'],
        ['icon' => 'clipboard-pen', 'title' => 'Registration', 'copy' => 'Our team helps with college and admission registration documents.'],
        ['icon' => 'badge-check', 'title' => 'Apply', 'copy' => 'Once you decide, we apply to the selected university on your behalf.'],
        ['icon' => 'contact', 'title' => 'Admission', 'copy' => 'We check documentation carefully so the seat process stays clean.'],
        ['icon' => 'globe', 'title' => 'Visa', 'copy' => 'We prepare the embassy file and guide you through visa stamping.'],
        ['icon' => 'plane', 'title' => 'Fly', 'copy' => 'We help you compare practical and economical flight options.'],
        ['icon' => 'car', 'title' => 'Airport Pick Up', 'copy' => 'We arrange someone to welcome you at the airport after arrival.'],
        ['icon' => 'home', 'title' => 'Accommodation', 'copy' => 'We support hostel, university housing, and private stay choices.'],
        ['icon' => 'book-open-check', 'title' => 'Study', 'copy' => 'We wish you the best and keep your academic start grounded.'],
    ];
@endphp

@extends('layouts.app')

@section('content')
<main id="mbbs-v2-main" class="mbbs-page mbbs-v2-page">
  <section class="mbbs-v2-hero" id="top" aria-labelledby="mbbs-v2-title">
    <div class="container mbbs-v2-hero-layout">
      <div class="mbbs-v2-hero-copy">
        <div class="mbbs-hero-chips">
          <span class="mbbs-chip">
            <i data-lucide="stethoscope"></i>
            <span>MBBS Route Desk</span>
          </span>
          <span class="mbbs-chip mbbs-chip-warm">
            <i data-lucide="layers-3"></i>
            <span>Comparison View</span>
          </span>
        </div>

        <h1 id="mbbs-v2-title">Choose your path with <span class="mbbs-gold">One Degree</span> Advisory.</h1>
        <p class="mbbs-v2-lede">
          We help students compare destinations, budgets, and timelines &mdash; then build one clear, evidence-led plan that takes you from shortlist to seat with confidence.
        </p>

        <div class="mbbs-hero-actions">
          <a class="btn btn-primary mbbs-hero-cta" href="{{ route('contact') }}">
            <span>Book free counselling</span>
            <i data-lucide="arrow-up-right"></i>
          </a>
        </div>
      </div>

      <aside class="mbbs-v2-country-panel" id="corridor" aria-label="MBBS country comparison board">
        <div class="mbbs-v2-country-carousel" aria-label="MBBS country comparison cards">
          @foreach ($mbbsCountries as $i => $c)
            <article class="mbbs-country-card mbbs-v2-country-card">
              <header class="mbbs-country-head">
                <span class="mbbs-country-flag">
                  <img src="https://flagcdn.com/w80/{{ $c['flag'] }}.png" alt="">
                </span>
                <div>
                  <span class="mbbs-country-index">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                  <h3>{{ $c['name'] }}</h3>
                  <p>{{ $c['desk'] }}</p>
                </div>
              </header>
              <p class="mbbs-country-note">{{ $c['note'] }}</p>
              <dl class="mbbs-country-grid-meta">
                <div><dt>Capital</dt><dd>{{ $c['capital'] }}</dd></div>
                <div><dt>Duration</dt><dd>{{ $c['duration'] }}</dd></div>
                <div><dt>Medium</dt><dd>{{ $c['medium'] }}</dd></div>
                <div><dt>Tuition</dt><dd><span>{{ $fmtFee($c['feeLow']) }}</span>&ndash;<span>{{ $fmtFee($c['feeHigh']) }}</span> / yr</dd></div>
                <div><dt>Intake</dt><dd>{{ $c['intake'] }}</dd></div>
                <div><dt>Fit</dt><dd>Profile-led</dd></div>
              </dl>
              <a href="{{ route('contact') }}" class="mbbs-country-cta">
                <span>Study in {{ $c['name'] }}</span>
                <i data-lucide="arrow-up-right"></i>
              </a>
            </article>
          @endforeach
        </div>
      </aside>
    </div>
  </section>

  @php
      $careersBeyondMbbs = [
          ['title' => 'BDS – Dentistry',                       'body' => 'Build a respected clinical career in oral healthcare, surgery, aesthetics, and private practice.',                  'icon' => 'smile',       'tint' => 'teal'],
          ['title' => 'PharmD / Pharmacy',                     'body' => 'Work in clinical research, pharmaceuticals, hospitals, healthcare innovation, and global pharma companies.',         'icon' => 'pill',        'tint' => 'green'],
          ['title' => 'Psychology / Clinical Psychology',      'body' => 'Growing field focused on mental health, therapy, human behavior, and wellness.',                                     'icon' => 'brain',       'tint' => 'lavender'],
          ['title' => 'Physiotherapy',                         'body' => 'High-demand profession in sports rehab, hospitals, pain management, and mobility care.',                            'icon' => 'activity',    'tint' => 'peach'],
          ['title' => 'Biotechnology / Biomedical Sciences',   'body' => 'Ideal for students interested in research, genetics, diagnostics, and healthcare innovation.',                       'icon' => 'dna',         'tint' => 'aqua'],
          ['title' => 'BSc Allied Health Sciences',            'body' => 'Premium specializations like Radiology, OT Technology, Cardiac Care, Medical Lab Sciences.',                         'icon' => 'microscope',  'tint' => 'rose'],
          ['title' => 'Doctor of Occupational Therapy',        'body' => 'Emerging global field helping patients regain independence and functionality.',                                      'icon' => 'hand-heart',  'tint' => 'sky'],
          ['title' => 'Public Health / Healthcare Management', 'body' => 'Perfect for students interested in healthcare leadership, hospitals, policy, and impact at scale.',                  'icon' => 'building-2',  'tint' => 'mint'],
      ];
  @endphp

  <section id="careers-beyond-mbbs" class="mbbsx-section mbbsx-section--careers" aria-labelledby="careers-beyond-mbbs-title">
    <div class="container">
      <div class="mbbsx-careers__head">
        <div class="mbbsx-careers__copy reveal">
          <span class="eyebrow mbbsx-careers__eyebrow">
            <i data-lucide="target"></i>
            <span>Explore your options</span>
          </span>
          <h2 id="careers-beyond-mbbs-title">Exploring Careers<br><span class="gold-text mbbsx-careers__accent">Beyond MBBS?</span></h2>
          <p>Medicine is one path &mdash; healthcare has many powerful careers with strong growth, impact, and global opportunities.</p>
        </div>

        <aside class="mbbsx-careers__callout reveal">
          <span class="mbbsx-careers__callout-icon" aria-hidden="true">
            <i data-lucide="trophy"></i>
          </span>
          <div class="mbbsx-careers__callout-copy">
            <p>Success in healthcare is not limited to MBBS.</p>
            <strong>It depends on choosing the right fit.</strong>
          </div>
        </aside>
      </div>

      <div class="mbbsx-careers__grid">
        @foreach($careersBeyondMbbs as $i => $career)
          <article class="mbbsx-career-card reveal" data-tint="{{ $career['tint'] }}" style="--i: {{ $i }};">
            <div class="mbbsx-career-card__top">
              <span class="mbbsx-career-card__icon" aria-hidden="true">
                <i data-lucide="{{ $career['icon'] }}"></i>
              </span>
              <span class="mbbsx-career-card__no">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
            </div>
            <h3>{{ $career['title'] }}</h3>
            <span class="mbbsx-career-card__rule" aria-hidden="true"></span>
            <p>{{ $career['body'] }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  <section class="mbbs-v2-services" id="services" aria-labelledby="mbbs-v2-services-title">
    <div class="container">
      <div class="mbbs-v2-services-head">
        <span class="insights-eyebrow">Support desk</span>
        <h2 id="mbbs-v2-services-title">Our Services</h2>
        <p>From country fit to campus arrival, every step is handled as one coordinated MBBS admission file.</p>
      </div>

      <div class="mbbs-v2-services-grid">
        @foreach ($services as $service)
          <article class="mbbs-v2-service-card">
            <span class="mbbs-v2-service-icon" aria-hidden="true">
              <i data-lucide="{{ $service['icon'] }}"></i>
            </span>
            <h3>{{ $service['title'] }}</h3>
            <p>{{ $service['copy'] }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </section>
</main>
@endsection
