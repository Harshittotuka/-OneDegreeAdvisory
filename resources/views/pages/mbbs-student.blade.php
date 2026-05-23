@php
    $pageTitle = 'MBBS Abroad for Indian Students | OneDegreeAdvisory';
    $pageDescription = 'Plan MBBS in India or abroad with OneDegreeAdvisory: NEET profile review, verified country shortlists, NMC-aligned eligibility checks, finance planning, and FMGE/NExT readiness.';
    $activeNav = 'mbbs';
    $mainId = 'main';

    // x/y are percentages over the CodePen map viewBox (845.2 x 458),
    // projected from capital coordinates with an equirectangular formula.
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
            'x' => 60.4,
            'y' => 19.0,
            'dir' => 'up',
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
            'x' => 62.4,
            'y' => 26.8,
            'dir' => 'left',
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
            'x' => 69.8,
            'y' => 21.6,
            'dir' => 'up',
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
            'x' => 71.3,
            'y' => 26.1,
            'dir' => 'right',
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
            'x' => 69.1,
            'y' => 28.6,
            'dir' => 'down',
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
            'x' => 66.5,
            'y' => 27.5,
            'dir' => 'left',
        ],
    ];

    $fmtFee = function ($v) {
        return '$' . number_format($v, 0, '.', ',');
    };

    $studentPaths = [
        [
            'title' => 'India path',
            'score' => 'NEET 530',
            'steps' => ['Score read', 'State quota strategy', 'Counselling rounds', 'Document lock', 'Reporting'],
        ],
        [
            'title' => 'Abroad path',
            'score' => 'NEET 430',
            'steps' => ['Eligibility audit', 'Country fit', 'University shortlist', 'Visa file', 'Arrival support'],
        ],
    ];

    $universityGroups = [
        ['country' => 'Georgia', 'items' => ['Georgian American University', 'East European University', 'ALTE University', 'SEU University']],
        ['country' => 'Kazakhstan', 'items' => ['Caspian International School of Medicine', 'Kazakh Russian Medical University', 'Kokshetau State University']],
        ['country' => 'Kyrgyzstan', 'items' => ['IHSM Central Campus', 'IHSM Issyk-Kul Campus', 'Kyrgyz State Medical Academy']],
        ['country' => 'Tajikistan', 'items' => ['Avicenna Tajik State Medical University', 'Tajik National University', 'Medical-Social Institute of Tajikistan']],
        ['country' => 'Russia', 'items' => ['Siberian State Medical University', 'Novosibirsk State University', 'Orenburg State Medical University', 'Perm State Medical University']],
    ];

    $practiceChecks = [
        ['title' => 'NEET qualified', 'copy' => 'Indian citizens and OCI students need NEET qualification for MBBS-equivalent study abroad and India practice eligibility.'],
        ['title' => 'English medium', 'copy' => 'The full medical course must be taught in English for current foreign medical graduate compliance.'],
        ['title' => 'Course duration', 'copy' => 'We check that the program length, internship structure, and clinical training align with NMC expectations.'],
        ['title' => 'License readiness', 'copy' => 'FMGE/NExT preparation is treated as part of the degree plan, not an afterthought in final year.'],
        ['title' => 'Documentation', 'copy' => 'Admission letters, apostille, passport, visa, medicals, and university recognition documents are tracked in one file.'],
        ['title' => 'Clinical exposure', 'copy' => 'Shortlists prefer universities with hospital access, real patient load, and clear internship pathways.'],
    ];

    $careerSteps = [
        ['kicker' => '12th', 'title' => 'Class 12 PCB', 'copy' => 'Build Biology, Chemistry, Physics, and English eligibility.'],
        ['kicker' => 'NEET', 'title' => 'NEET-UG', 'copy' => 'Your score, rank, category, and domicile decide the realistic India/abroad route.'],
        ['kicker' => 'Admit', 'title' => 'Admission', 'copy' => 'Choose government/private India seats or a verified international university.'],
        ['kicker' => 'MBBS', 'title' => 'Medical degree', 'copy' => 'Complete academic study, labs, clinical rotations, and university exams.'],
        ['kicker' => 'Intern', 'title' => 'Internship', 'copy' => 'Plan local and India-facing internship requirements before enrolling.'],
        ['kicker' => 'License', 'title' => 'FMGE / NExT path', 'copy' => 'Prepare for the current licensing exam pathway and any notified transition.'],
        ['kicker' => 'Dr.', 'title' => 'Registration', 'copy' => 'Complete NMC/State Medical Council steps before independent practice.'],
        ['kicker' => 'PG', 'title' => 'Specialisation', 'copy' => 'Move toward MD/MS, public health, research, hospital management, or global exams.'],
    ];

    $compareRows = [
        ['factor' => 'Tuition', 'india' => 'Govt. low-cost; private can be high', 'abroad' => 'Often Rs 25L-60L total depending on country'],
        ['factor' => 'Seat access', 'india' => 'Highly competitive by rank, state, and category', 'abroad' => 'More seats, but quality varies sharply'],
        ['factor' => 'Duration', 'india' => '4.5 years + internship', 'abroad' => 'Usually 5-6 years plus internship rules'],
        ['factor' => 'Recognition', 'india' => 'Indian colleges follow domestic regulation', 'abroad' => 'Must clear NMC eligibility and licensing path'],
        ['factor' => 'Clinical exposure', 'india' => 'Strong in busy government hospitals', 'abroad' => 'Depends on university hospital network'],
        ['factor' => 'Environment', 'india' => 'Closer to home and language familiarity', 'abroad' => 'International exposure with adaptation needs'],
    ];

    $financeCards = [
        ['title' => 'Scholarship scan', 'copy' => 'We look for merit aid, tuition support, country-specific grants, and university discounts before finalising the list.'],
        ['title' => 'Loan planning', 'copy' => 'Families compare secured/unsecured education loans, moratorium, collateral, co-applicant, and repayment pressure.'],
        ['title' => 'Full budget sheet', 'copy' => 'Tuition, hostel, food, insurance, travel, visa, exam prep, winter clothing, and emergency buffer are shown upfront.'],
    ];

    $parentChecks = [
        'Verify the university against current NMC and local medical council requirements.',
        'Check Indian Embassy access, city safety, hostel rules, and Indian student support.',
        'Compare total cost, not only first-year tuition.',
        'Understand the licensing exam path before paying the first deposit.',
        'Keep documents, receipts, invitations, and visa records in one cloud folder.',
        'Treat multiple NEET attempts calmly; rushed decisions are expensive decisions.',
    ];

    $indiaTimeline = ['NEET result', 'Rank and category read', 'AIQ/state counselling', 'Choice filling', 'Seat allotment', 'Reporting'];
    $abroadTimeline = ['NEET qualification', 'Country shortlist', 'University offer', 'Fee and visa file', 'Travel briefing', 'Arrival and registration'];

    $faqs = [
        ['q' => 'Is MBBS abroad valid for India?', 'a' => 'It can be, but only when the student meets current NMC rules, completes the required degree/internship path, and clears the applicable Indian licensing exam.'],
        ['q' => 'Can a low NEET score still work?', 'a' => 'Yes, a qualifying score can open abroad options. The right answer depends on budget, risk tolerance, country fit, and licensing discipline.'],
        ['q' => 'Do you recommend only one country?', 'a' => 'No. We compare countries, universities, fee schedules, clinical exposure, travel, safety, and India-return pathway before recommending a shortlist.'],
        ['q' => 'When should a family start?', 'a' => 'Start as soon as NEET score/rank is clear. For abroad, document readiness and university deadlines matter as much as the score.'],
    ];
@endphp

@extends('layouts.app')

@section('content')
<main id="main" class="mbbs-page">

  <section class="mbbs-hero" id="top" aria-labelledby="mbbs-hero-title">
    <div class="mbbs-hero-bg-map" data-codepen-map aria-hidden="true">
      @include('partials.codepen-dotted-world-map')
      <div class="mbbs-codepen-projection">
        @foreach ($mbbsCountries as $i => $c)
          <button type="button"
                  class="mbbs-pin mbbs-pin-{{ $c['slug'] }}"
                  style="--pin-x: {{ $c['x'] }}%; --pin-y: {{ $c['y'] }}%; --pin-delay: {{ $i * 0.18 }}s;"
                  data-card-dir="{{ $c['dir'] }}"
                  aria-label="{{ $c['name'] }} - {{ $c['desk'] }}">
            <span class="mbbs-pin-pulse"></span>
            <span class="mbbs-pin-dot"></span>
            <span class="mbbs-pin-card">
              <span class="mbbs-pin-flag">
                <img src="https://flagcdn.com/w40/{{ $c['flag'] }}.png" alt="">
              </span>
              <span class="mbbs-pin-meta">
                <strong>{{ $c['name'] }}</strong>
                <small>{{ $c['desk'] }}</small>
              </span>
            </span>
          </button>
        @endforeach
      </div>
    </div>

    <div class="container mbbs-hero-grid">
      <div class="mbbs-hero-copy">
        <div class="mbbs-hero-chips">
          <span class="mbbs-chip">
            <i data-lucide="stethoscope"></i>
            <span>MBBS Route Desk</span>
          </span>
          <span class="mbbs-chip mbbs-chip-warm">
            <i data-lucide="calendar-check"></i>
            <span>2027 Intake Open</span>
          </span>
        </div>

        <h1 id="mbbs-hero-title">
          Build a safer <span class="mbbs-gold">MBBS</span> route after NEET.
          <span class="mbbs-hero-sub">Not just the nearest seat.</span>
        </h1>

        <p class="mbbs-hero-lede">
          OneDegree reads your score, budget, country comfort, and India-practice plan before a family pays the first university deposit.
        </p>

        <div class="mbbs-hero-actions">
          <a class="btn btn-primary mbbs-hero-cta" href="{{ route('contact') }}">
            <span>Book free counselling</span>
            <i data-lucide="arrow-up-right"></i>
          </a>
          <a class="mbbs-hero-quietlink" href="#corridor">
            <span>Explore corridor</span>
            <i data-lucide="arrow-right"></i>
          </a>
        </div>

        <ul class="mbbs-hero-trust" aria-label="What we cover">
          <li>
            <span class="mbbs-trust-icon"><i data-lucide="shield-check"></i></span>
            <span>NMC-aligned</span>
          </li>
          <li>
            <span class="mbbs-trust-icon"><i data-lucide="wallet-cards"></i></span>
            <span>Finance planning</span>
          </li>
          <li>
            <span class="mbbs-trust-icon"><i data-lucide="badge-check"></i></span>
            <span>FMGE / NExT ready</span>
          </li>
        </ul>
      </div>
    </div>
  </section>

  <section class="mbbs-paths" id="roadmap" aria-labelledby="paths-title">
    <div class="container">
      <div class="mbbs-section-head">
        <span class="insights-eyebrow">Student journey</span>
        <h2 id="paths-title">Two common routes after the NEET score lands.</h2>
        <p>Inspired by the journey flow on Vidysea, rewritten here as a practical decision board for OneDegree families.</p>
      </div>

      <div class="mbbs-path-grid">
        @foreach ($studentPaths as $path)
          <article class="mbbs-path-card">
            <div class="mbbs-path-card-head">
              <span>{{ $path['title'] }}</span>
              <strong>{{ $path['score'] }}</strong>
            </div>
            <ol>
              @foreach ($path['steps'] as $step)
                <li>{{ $step }}</li>
              @endforeach
            </ol>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  <section class="mbbs-corridor" id="corridor" aria-labelledby="corridor-title">
    <div class="container">
      <div class="mbbs-section-head">
        <span class="insights-eyebrow">The corridor</span>
        <h2 id="corridor-title">Six destination desks, filtered before the family call.</h2>
        <p>We do not push every medical college with an English brochure. Each shortlist is checked for recognition, cost, clinical exposure, safety, and licensing practicality.</p>
      </div>

      <div class="mbbs-country-grid">
        @foreach ($mbbsCountries as $i => $c)
          <article class="mbbs-country-card">
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
              <div><dt>Tuition</dt><dd><span data-money="{{ $c['feeLow'] }}" data-currency="USD">{{ $fmtFee($c['feeLow']) }}</span>&ndash;<span data-money="{{ $c['feeHigh'] }}" data-currency="USD">{{ $fmtFee($c['feeHigh']) }}</span> / yr</dd></div>
              <div><dt>Intake</dt><dd>{{ $c['intake'] }}</dd></div>
              <div><dt>Fit</dt><dd>Profile-led</dd></div>
            </dl>
            <a href="{{ route('contact') }}" class="mbbs-country-cta">
              <span>Plan {{ $c['name'] }} file</span>
              <i data-lucide="arrow-up-right"></i>
            </a>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  <section class="mbbs-practice" id="practice-india" aria-labelledby="practice-title">
    <div class="container">
      <div class="mbbs-section-head">
        <span class="insights-eyebrow">Practice in India</span>
        <h2 id="practice-title">The India-return pathway must be built into the shortlist.</h2>
        <p>NMC rules make university choice, language, course duration, internship, and licensing preparation part of one decision. This is where many rushed MBBS abroad files go wrong.</p>
      </div>

      <div class="mbbs-check-grid">
        @foreach ($practiceChecks as $check)
          <article class="mbbs-check-card">
            <i data-lucide="badge-check"></i>
            <h3>{{ $check['title'] }}</h3>
            <p>{{ $check['copy'] }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  <section class="mbbs-career" id="career-roadmap" aria-labelledby="career-title">
    <div class="container">
      <div class="mbbs-section-head">
        <span class="insights-eyebrow">Career roadmap</span>
        <h2 id="career-title">From Class 12 PCB to specialist doctor.</h2>
        <p>A medical career is a sequence. We make every step visible before the family chooses a college or country.</p>
      </div>

      <ol class="mbbs-career-rail">
        @foreach ($careerSteps as $step)
          <li>
            <span>{{ $step['kicker'] }}</span>
            <h3>{{ $step['title'] }}</h3>
            <p>{{ $step['copy'] }}</p>
          </li>
        @endforeach
      </ol>
    </div>
  </section>

  <section class="mbbs-neet" id="everything-neet" aria-labelledby="neet-title">
    <div class="container mbbs-neet-grid">
      <div>
        <span class="insights-eyebrow">NEET guide</span>
        <h2 id="neet-title">NEET is the gateway. Strategy starts after the score, not after panic.</h2>
        <p>NEET is the common national entrance test for undergraduate medical admissions in India, and Indian citizens/OCI students also need it for MBBS-equivalent programs abroad when they intend to practice in India.</p>
      </div>

      <div class="mbbs-neet-panel">
        <article>
          <strong>17+</strong>
          <span>Minimum age by the admission-year deadline</span>
        </article>
        <article>
          <strong>720</strong>
          <span>Maximum score used for route modelling</span>
        </article>
        <article>
          <strong>3</strong>
          <span>Parallel decisions: AIQ, state quota, abroad</span>
        </article>
        <article>
          <strong>1</strong>
          <span>Official NTA portal to track notices and dates</span>
        </article>
      </div>
    </div>
  </section>

  <section class="mbbs-compare" id="compare" aria-labelledby="compare-title">
    <div class="container">
      <div class="mbbs-section-head">
        <span class="insights-eyebrow">India vs abroad</span>
        <h2 id="compare-title">Compare the route, not the brochure.</h2>
        <p>Tuition is only one variable. Families need a side-by-side read of seat access, total cost, licensing, clinical exposure, and student support.</p>
      </div>

      <div class="mbbs-compare-table" role="table" aria-label="MBBS India versus MBBS abroad comparison">
        <div class="mbbs-compare-row mbbs-compare-header" role="row">
          <span role="columnheader">Factor</span>
          <span role="columnheader">MBBS in India</span>
          <span role="columnheader">MBBS abroad</span>
        </div>
        @foreach ($compareRows as $row)
          <div class="mbbs-compare-row" role="row">
            <strong role="cell">{{ $row['factor'] }}</strong>
            <span role="cell">{{ $row['india'] }}</span>
            <span role="cell">{{ $row['abroad'] }}</span>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  <section class="mbbs-finance" id="finance" aria-labelledby="finance-title">
    <div class="container">
      <div class="mbbs-section-head">
        <span class="insights-eyebrow">Finance and loans</span>
        <h2 id="finance-title">Make the full MBBS budget visible before the first deposit.</h2>
        <p>Scholarship claims, first-year discounts, and hostel estimates can distort the real cost. We model the full family outflow.</p>
      </div>

      <div class="mbbs-finance-grid">
        @foreach ($financeCards as $card)
          <article class="mbbs-finance-card">
            <i data-lucide="wallet-cards"></i>
            <h3>{{ $card['title'] }}</h3>
            <p>{{ $card['copy'] }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  <section class="mbbs-parents" id="parents-guide" aria-labelledby="parents-title">
    <div class="container mbbs-parents-grid">
      <div>
        <span class="insights-eyebrow">For parents</span>
        <h2 id="parents-title">A calm checklist for a high-pressure decision.</h2>
        <p>When a medical seat feels urgent, families can get pushed into fast payments and vague promises. This list keeps the decision grounded.</p>
      </div>

      <ul class="mbbs-parent-list">
        @foreach ($parentChecks as $item)
          <li><i data-lucide="check-circle-2"></i>{{ $item }}</li>
        @endforeach
      </ul>
    </div>
  </section>

  <section class="mbbs-timeline" id="admission-timeline" aria-labelledby="timeline-title">
    <div class="container">
      <div class="mbbs-section-head">
        <span class="insights-eyebrow">Timeline</span>
        <h2 id="timeline-title">Two timelines, one decision room.</h2>
        <p>India counselling and abroad admissions move differently. We keep both visible so the student does not lose one option while chasing another.</p>
      </div>

      <div class="mbbs-timeline-grid">
        <article>
          <h3>MBBS India</h3>
          <ol>
            @foreach ($indiaTimeline as $item)
              <li>{{ $item }}</li>
            @endforeach
          </ol>
        </article>
        <article>
          <h3>MBBS abroad</h3>
          <ol>
            @foreach ($abroadTimeline as $item)
              <li>{{ $item }}</li>
            @endforeach
          </ol>
        </article>
      </div>
    </div>
  </section>

  <section class="mbbs-faq" id="neet-reality" aria-labelledby="faq-title">
    <div class="container">
      <div class="mbbs-section-head">
        <span class="insights-eyebrow">FAQ</span>
        <h2 id="faq-title">The questions families ask when the score is real.</h2>
      </div>

      <div class="mbbs-faq-grid">
        @foreach ($faqs as $faq)
          <article>
            <h3>{{ $faq['q'] }}</h3>
            <p>{{ $faq['a'] }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  <section class="insights-cta-section mbbs-final-cta" id="counseling" aria-labelledby="mbbs-cta-title">
    <div class="container insights-cta-panel">
      <div>
        <span class="insights-eyebrow">Free counselling</span>
        <h2 id="mbbs-cta-title">Bring your NEET score. We will bring the decision map.</h2>
      </div>
      <a class="btn btn-primary" href="{{ route('contact') }}">
        <span>Book a profile review</span>
        <i data-lucide="arrow-up-right"></i>
      </a>
    </div>
  </section>

</main>
@endsection
