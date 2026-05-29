@php
    $pageTitle = 'Test Preparation — ACT, SAT, IELTS, GRE, GMAT & More | One Degree Advisory';
    $pageDescription = 'Expert-led test preparation for ACT, SAT, IELTS, PTE, TOEFL, GRE, GMAT, IMAT, UCAT, LNAT and more — study materials, mock tests, and strategic coaching to secure admissions worldwide.';
    $activeNav = 'services';
    $mainId = 'main';
    $bodyClass = 'tp-body';

    $benefits = [
        ['n' => '01', 'title' => 'Expert Guidance', 'text' => 'Receive personalized, expert-led coaching from experienced mentors to help you achieve the best possible scores across every standardized test.'],
        ['n' => '02', 'title' => 'Exclusive Study Materials', 'text' => 'Access meticulously curated study materials and resources, designed to cover every section of each exam for admission to leading universities worldwide.'],
        ['n' => '03', 'title' => 'Performance Tracking', 'text' => 'Track and analyse your progress with full-length mock tests, detailed analytics, and extra tutorials to steadily improve and attain a great score.'],
        ['n' => '04', 'title' => 'Personalized Strategies', 'text' => 'A flexible, one-on-one mentoring model ensures individualized attention with a strategy tailored to your starting level and target score.'],
        ['n' => '05', 'title' => 'Strategic Coaching', 'text' => 'Master time management, exam strategy, and concept clarity with proven techniques designed to help you maximise every point on test day.'],
    ];

    $tests = [
        ['title' => '(ACT) American College Testing', 'img' => asset('assets/test-prep/courses/ACT.webp'),
         'desc' => 'The ACT is a globally recognized undergraduate admission test for students planning to study in the USA and other international destinations. It evaluates English, Mathematics, Reading, Science, and optional Writing skills. Our ACT preparation focuses on concept clarity, time management, and exam strategies to maximize your score.'],
        ['title' => '(SAT) Scholastic Assessment Test', 'img' => asset('assets/test-prep/courses/SAT.webp'),
         'desc' => 'The SAT is one of the most important standardized tests for college admissions abroad. It measures critical reading, writing, and mathematical abilities. We help students build strong fundamentals, improve analytical thinking, and gain confidence through practice tests and expert mentoring.'],
        ['title' => '(IELTS) International English Language Testing System', 'img' => asset('assets/test-prep/courses/IELTS.webp'),
         'desc' => 'IELTS is an internationally accepted English proficiency test required for study, work, and migration purposes. It assesses Listening, Reading, Writing, and Speaking skills. Our IELTS coaching includes personalized speaking sessions, writing evaluations, and real exam simulations for better performance.'],
        ['title' => '(PTE) Pearson Test of English', 'img' => asset('assets/test-prep/courses/PTE.webp'),
         'desc' => 'The Pearson Test of English (PTE) is a computer-based English language exam accepted by universities and immigration authorities worldwide. Our PTE training helps students master speaking fluency, pronunciation, grammar, and AI-based test techniques for higher scores.'],
        ['title' => '(TOEFL) Test of English as a Foreign Language', 'img' => asset('assets/test-prep/courses/TOEFL.webp'),
         'desc' => 'TOEFL measures academic English proficiency for students applying to universities in English-speaking countries. We provide structured preparation covering all four modules along with practice exercises, vocabulary enhancement, and test-taking strategies.'],
        ['title' => '(GRE) Graduate Record Examinations', 'img' => asset('assets/test-prep/courses/GRE.webp'),
         'desc' => 'The GRE is required for admission into many graduate and postgraduate programs across the world. It evaluates verbal reasoning, quantitative reasoning, and analytical writing skills. Our GRE coaching includes advanced quantitative problem-solving, vocabulary development, and mock examinations.'],
        ['title' => '(GMAT) Graduate Management Admission Test', 'img' => asset('assets/test-prep/courses/GMAT.webp'),
         'desc' => 'The GMAT is specifically designed for MBA and business school aspirants. It assesses analytical, quantitative, verbal, and reasoning abilities. Our preparation program helps students improve logical thinking, data interpretation, and overall business aptitude.'],
        ['title' => '(IMAT) International Medical Admissions Test', 'img' => 'https://images.unsplash.com/photo-1581595219315-a187dd40c322?auto=format&fit=crop&w=800&q=80',
         'desc' => 'The International Medical Admissions Test (IMAT) is essential for students seeking admission to medical universities in Italy. Our IMAT preparation covers Biology, Chemistry, Physics, Mathematics, and Logical Reasoning with expert guidance and targeted practice sessions.'],
        ['title' => '(UCAT) University Clinical Aptitude Test', 'img' => 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?auto=format&fit=crop&w=800&q=80',
         'desc' => 'The UCAT is used by medical and dental schools primarily in the UK to assess aptitude, cognitive ability, and professional behavior. We provide strategic coaching for verbal reasoning, decision making, quantitative reasoning, abstract reasoning, and situational judgment.'],
        ['title' => 'EU Medicine Entrance Exams', 'img' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=800&q=80',
         'desc' => 'European medical universities often require entrance examinations for international students. Our specialized preparation programs help students succeed in Biology, Chemistry, Physics, and medical aptitude tests required by top European institutions.'],
        ['title' => '(LNAT) Law National Aptitude Test', 'img' => 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?auto=format&fit=crop&w=800&q=80',
         'desc' => 'The LNAT is an admissions test for law programs at leading universities. It evaluates reading comprehension, logical reasoning, and essay-writing skills. Our LNAT coaching develops critical thinking abilities and teaches effective legal argumentation techniques.'],
        ['title' => 'Tutoring', 'img' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=800&q=80',
         'desc' => 'We offer personalized tutoring services tailored to individual learning styles and academic goals. Whether students need subject-specific support, homework assistance, or exam preparation, our experienced mentors provide one-on-one guidance for continuous improvement and success.'],
    ];
@endphp

@extends('layouts.app')

@push('head')
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js" defer></script>
  <script>
    window.addEventListener('load', function () {
      if (window.AOS) { AOS.init({ duration: 800, once: true, offset: 60 }); }
    });
  </script>
@endpush

@section('content')
<main id="main" class="tp-edwise">

  {{-- ───────────────────────── HERO ───────────────────────── --}}
  <div class="inner-hero inner-hero-2" data-aos="fade-in" data-duration="0">
    <div class="container">
      <div class="inner-hero-wrap">
        <div class="inner-hero-left">
          <h2 class="hero-title-txt">Your <br> <strong>One Stop Solution</strong> For All
            Standardized <br> <span class="highlighter">Tests</span></h2>
          <div class="comm-para">
            <p>Prepare for global academic and professional success with our expert-led test preparation programs. We
              provide comprehensive study materials, personalized guidance, mock tests, and strategic coaching to help
              students achieve top scores and secure admissions in leading universities worldwide.</p>
            <a class="button" href="{{ route('contact') }}">Free Expert Consultation</a>
          </div>
        </div>
        <div class="inner-hero-right">
          <img src="{{ asset('assets/test-prep/test-prep-bnr-img.webp') }}" alt="Test Preparation to Study Abroad">
        </div>
      </div>
    </div>
  </div>

  {{-- ───────────────────────── BENEFITS ───────────────────────── --}}
  <div class="comm-section" data-aos="fade-in" data-duration="200">
    <div class="container">
      <div class="benefit-hdr">
        <h2 class="sec-title">Unlock the unparalleled benefits of <span class="highlighter">Test Prep</span> with One Degree</h2>
        <div class="comm-para">
          <p>Our expert-led coaching provides complete preparation for study abroad tests like TOEFL, PTE, IELTS, GMAT, GRE, ACT, SAT and more.</p>
        </div>
      </div>
      <div class="benefit-content-wrap">
        <div class="benefit-img-wrap">
          <div class="benef-sticky-div">
            <div class="benefit-img"><img src="{{ asset('assets/test-prep/benefit-img.webp') }}" alt=""></div>
          </div>
        </div>
        <div class="benefit-content">
          @foreach ($benefits as $benefit)
            <div class="benefit-box">
              <div class="benefit-count">{{ $benefit['n'] }}</div>
              <h3 class="benef-title">{{ $benefit['title'] }}</h3>
              <div class="benef-dtl">
                <p>{{ $benefit['text'] }}</p>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  {{-- ───────────────────────── PREPARATION COURSES ───────────────────────── --}}
  <div class="comm-section" data-aos="fade-in" data-duration="200">
    <div class="container">
      <div class="text-center prep-hdr">
        <h1 class="sec-title">Explore Test <span class="highlighter">Preparation Courses</span> with One Degree</h1>
        <div class="comm-para">
          <p style="text-align:center">One Degree offers comprehensive, economical and rigorous test prep training for all the standardized tests based on adaptive learning with a strong focus on concepts and special test-taking strategies.</p>
        </div>
      </div>
      <div class="prop-corse-wrap">
        <div class="f-row f-2 f-768-1">
          @foreach ($tests as $test)
            <div class="f-col">
              <div class="prep-corse-box">
                <div class="prep-cors-img"><img src="{{ $test['img'] }}" alt="{{ $test['title'] }}" loading="lazy"></div>
                <div class="prep-corse-content">
                  <h4 class="prep-cors-title">{{ $test['title'] }}</h4>
                  <div class="comm-para">
                    <p>{{ $test['desc'] }}</p>
                  </div>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

</main>
@endsection
