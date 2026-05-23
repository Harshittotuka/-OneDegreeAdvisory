@php
    $pageTitle = 'Careers | OneDegreeAdvisory';
    $pageDescription = 'Join the team at OneDegreeAdvisory. We are always looking for passionate people to join us on our mission.';
    $activeNav = 'careers';
    $mainId = 'main';
@endphp

@extends('layouts.app')

@section('content')
<main id="main" class="careers-page">

  {{-- ──────────────── HERO (unchanged) ──────────────── --}}
  <section class="insights-page-hero" id="top" aria-labelledby="careers-page-title">
    <div class="container insights-page-hero-grid">
      <div class="insights-page-copy">
        <span class="eyebrow">Join the Team</span>
        <h1 id="careers-page-title">Build the future of global education advisory.</h1>
        <p>
          We are a team of passionate advisors and experts who believe in building long-term outcomes for our students. We are always on the lookout for bright minds.
        </p>
        <div class="insights-page-actions">
          <a class="btn btn-primary" href="#application-form">
            <span>Apply now</span>
            <i data-lucide="arrow-down"></i>
          </a>
          <a class="btn btn-ghost" href="#hiring-process">
            <i data-lucide="info"></i>
            <span>View hiring process</span>
          </a>
        </div>
      </div>

      <aside class="insights-hero-panel" aria-label="Why join us">
        <div class="insights-hero-image" style="background-image: url('https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1400&q=82');"></div>
        <div class="insights-hero-note">
          <span>Why OneDegree?</span>
          <ul>
            <li><i data-lucide="check"></i> Culture of excellence</li>
            <li><i data-lucide="check"></i> Continuous learning</li>
            <li><i data-lucide="check"></i> Real impact on student lives</li>
          </ul>
        </div>
      </aside>
    </div>
  </section>

  {{-- ──────────────── HIRING PROCESS (compact rail) ──────────────── --}}
  <section class="cr-rail" id="hiring-process" aria-labelledby="hiring-process-title" data-hiring-process>
    <div class="container">
      <header class="cr-rail-head">
        <div>
          <span class="cr-rail-eyebrow"><i data-lucide="route"></i> Hiring Process</span>
          <h2 id="hiring-process-title">Six careful steps from <em>hello</em> to first file.</h2>
        </div>
        <p>End-to-end usually takes <strong>two to three weeks.</strong> Tap a step to see what to expect.</p>
      </header>

      @php
        $steps = [
          ['n' => 1, 'icon' => 'user-check',  'title' => 'First Screening',       'meta' => '20-min call',         'body' => 'A brief call from our Talent Acquisition team for shortlisted applicants. We learn about your experience, your motivation, and the kind of student impact you want to build.'],
          ['n' => 2, 'icon' => 'puzzle',      'title' => 'Assignment',            'meta' => '2&ndash;3 days',      'body' => 'A real OneDegree assignment to work on. You experience how we think, and we see your reasoning in action. Time-boxed and graded by a partner.'],
          ['n' => 3, 'icon' => 'file-search', 'title' => 'Technical Interview',   'meta' => '60 minutes',          'body' => 'A deep read on the role-specific skills you bring &mdash; depth and breadth of knowledge, problem-solving, and how you reason under pressure. We value competence and agility.'],
          ['n' => 4, 'icon' => 'brain',       'title' => 'Behavioral Assessment', 'meta' => '45 minutes',          'body' => 'A focused conversation on cultural fit. We talk through your aspirations, long-term goals, and how you respond across the contexts an advisor sees &mdash; anxious parents to tight deadlines.'],
          ['n' => 5, 'icon' => 'users-round', 'title' => 'Leadership Round',      'meta' => 'With the partners',   'body' => 'You meet the partners at OneDegree. We discuss team experiences, conflict resolution, and difficult decision-making. This is also your chance to interview us.'],
          ['n' => 6, 'icon' => 'gem',         'title' => 'You are a OneDegreer!', 'meta' => 'Welcome aboard',      'body' => 'A rewarding career at OneDegree awaits you. We finalize the offer, plan your onboarding, and pair you with a senior partner-mentor for your first cycle.'],
        ];
      @endphp

      <div class="cr-rail-track" role="tablist" aria-label="Hiring process steps">
        <div class="cr-rail-line" aria-hidden="true">
          <span class="cr-rail-line-fill" data-rail-fill></span>
        </div>

        @foreach ($steps as $i => $step)
          <button type="button"
                  class="cr-rail-step {{ $i === 0 ? 'is-active' : '' }}"
                  role="tab"
                  aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                  aria-controls="cr-rail-panel"
                  data-step="{{ $i }}"
                  data-index="{{ $i }}"
                  data-title="{{ $step['title'] }}"
                  data-meta="{{ $step['meta'] }}"
                  data-body="{{ $step['body'] }}">
            <span class="cr-rail-step-num" aria-hidden="true">{{ str_pad($step['n'], 2, '0', STR_PAD_LEFT) }}</span>
            <span class="cr-rail-node" aria-hidden="true">
              <i data-lucide="{{ $step['icon'] }}"></i>
            </span>
            <span class="cr-rail-label">{{ $step['title'] }}</span>
          </button>
        @endforeach
      </div>

      <div class="cr-rail-panel" id="cr-rail-panel" role="tabpanel" aria-live="polite">
        <div class="cr-rail-panel-side">
          <span class="cr-rail-panel-stage" data-rail-stage>Step 01 of 06</span>
          <h3 class="cr-rail-panel-title" data-step-title>{{ $steps[0]['title'] }}</h3>
          <span class="cr-rail-panel-meta">
            <i data-lucide="clock"></i>
            <span data-rail-meta>{!! $steps[0]['meta'] !!}</span>
          </span>
        </div>
        <div class="cr-rail-panel-main">
          <p class="cr-rail-panel-body" data-step-body>{{ $steps[0]['body'] }}</p>
          <div class="cr-rail-panel-foot">
            <button type="button" class="cr-rail-nav" data-rail-prev aria-label="Previous step">
              <i data-lucide="arrow-left"></i>
            </button>
            <button type="button" class="cr-rail-nav" data-rail-next aria-label="Next step">
              <i data-lucide="arrow-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ──────────────── LIFE AT ONEDEGREE ──────────────── --}}
  <section class="cr-life" aria-labelledby="cr-life-title">
    <div class="container cr-life-grid">
      <div class="cr-life-copy">
        <span class="va-eyebrow">Life at OneDegree</span>
        <h2 id="cr-life-title">Problem solvers. Careful readers. Builders of student futures.</h2>
        <p>We look for people who can think on their feet, get things done, and read a student&rsquo;s file with the patience of a craftsperson. Creative thinkers who go beyond the obvious &mdash; and stay accountable to the families we serve.</p>
        <p>If that sounds like you, we would love to hear from you. We hire slowly, mentor closely, and trust our people with senior work from day one.</p>

        <ul class="cr-life-points">
          <li><i data-lucide="compass"></i> <span><strong>One-method culture.</strong> Every advisor learns the same careful playbook before they own a file.</span></li>
          <li><i data-lucide="users"></i> <span><strong>Senior-led teams.</strong> No volume targets &mdash; the partner you ship with is the partner who reads the work.</span></li>
          <li><i data-lucide="book-open"></i> <span><strong>Continuous craft.</strong> A weekly editorial review, two annual masterclasses, and a personal learning budget.</span></li>
        </ul>
      </div>

      <figure class="cr-life-media" aria-hidden="true">
        <img src="https://images.unsplash.com/photo-1556761175-5973dc0f32e7?auto=format&fit=crop&w=900&h=1080&q=82" alt="">
        <div class="cr-life-badge">
          <i data-lucide="sparkles"></i>
          <div>
            <strong>We are hiring</strong>
            <span>Open to senior advisors and craft generalists</span>
          </div>
        </div>
      </figure>
    </div>
  </section>

  {{-- ──────────────── APPLICATION FORM ──────────────── --}}
  <section class="cr-apply" id="application-form" aria-labelledby="apply-form-title">
    <div class="container cr-apply-grid">

      <aside class="cr-apply-aside">
        <span class="va-eyebrow">Submit your application</span>
        <h2 id="apply-form-title">No open list. Always open to talent.</h2>
        <p>We don&rsquo;t advertise specific openings &mdash; we hire when the right person walks through the door. Send us your story; if there is a fit, a partner will reach out within ten working days.</p>

        <ul class="cr-apply-points">
          <li><i data-lucide="mail"></i> careers@onedegreeadvisory.com</li>
          <li><i data-lucide="clock"></i> 10-day reply window</li>
          <li><i data-lucide="lock"></i> Reviewed by a partner, not a bot</li>
        </ul>

        <div class="cr-apply-quote">
          <span class="cr-apply-quote-mark" aria-hidden="true">&ldquo;</span>
          <p>We hire slowly so we can mentor closely. The smaller the bench, the sharper the work.</p>
          <span class="cr-apply-quote-sig">&mdash; The partners</span>
        </div>
      </aside>

      <div class="cr-apply-form-card">
        <form action="#" method="POST" class="cr-apply-form" novalidate>
          <div class="cr-form-row">
            <label class="cr-field" for="applicant-name">
              <span>Full name *</span>
              <input id="applicant-name" name="name" type="text" required placeholder="e.g. Aanya Mehta">
            </label>
            <label class="cr-field" for="applicant-email">
              <span>Email address *</span>
              <input id="applicant-email" name="email" type="email" required placeholder="you@example.com">
            </label>
          </div>

          <div class="cr-form-row">
            <label class="cr-field" for="applicant-phone">
              <span>Mobile number *</span>
              <input id="applicant-phone" name="phone" type="tel" required placeholder="+91 98xxxxxxxx">
            </label>
            <label class="cr-field" for="applicant-linkedin">
              <span>LinkedIn profile URL</span>
              <input id="applicant-linkedin" name="linkedin" type="url" placeholder="https://linkedin.com/in/...">
            </label>
          </div>

          <div class="cr-form-row">
            <label class="cr-field" for="applicant-role">
              <span>Role of interest *</span>
              <select id="applicant-role" name="role" required>
                <option value="">Select an area</option>
                <option>Senior Advisor &mdash; Undergraduate</option>
                <option>Senior Advisor &mdash; Graduate / MBA</option>
                <option>Visa &amp; Pre-departure Specialist</option>
                <option>Editorial &amp; Essays Lead</option>
                <option>Scholarships &amp; Aid Analyst</option>
                <option>Operations &amp; Client Success</option>
                <option>Open application</option>
              </select>
            </label>
            <label class="cr-field" for="applicant-experience">
              <span>Years of experience *</span>
              <select id="applicant-experience" name="experience" required>
                <option value="">Select range</option>
                <option>0&ndash;2 years</option>
                <option>3&ndash;5 years</option>
                <option>6&ndash;10 years</option>
                <option>10+ years</option>
              </select>
            </label>
          </div>

          <label class="cr-field cr-field-full" for="applicant-message">
            <span>Cover letter &mdash; why OneDegree? *</span>
            <textarea id="applicant-message" name="message" required placeholder="Tell us about your background and what you can bring to the team." rows="5"></textarea>
          </label>

          <label class="cr-field cr-field-full" for="applicant-resume">
            <span>Resume link &mdash; Google Drive, Dropbox, Notion *</span>
            <input id="applicant-resume" name="resume_link" type="url" required placeholder="Paste a link to your resume">
          </label>

          <label class="cr-checkbox">
            <input type="checkbox" name="consent" required>
            <span>I agree to OneDegree storing my application details for review and follow-up.</span>
          </label>

          <button class="btn btn-primary cr-apply-submit" type="submit">
            <span>Submit application</span>
            <i data-lucide="arrow-up-right"></i>
          </button>
        </form>
      </div>

    </div>
  </section>

</main>
@endsection
