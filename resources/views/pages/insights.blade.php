@php
    $pageTitle = 'Insights | OneDegreeAdvisory';
    $pageDescription = 'Read OneDegreeAdvisory insights on admissions strategy, tests, student visas, scholarships, and study-abroad planning.';
    $activeNav = 'insights';
    $mainId = 'main';
@endphp

@extends('layouts.app')

@section('content')
<main id="main" class="insights-page-main">
      <section class="insights-page-hero" id="top" aria-labelledby="insights-page-title">
        <div class="container insights-page-hero-grid">
          <div class="insights-page-copy">
            <span class="eyebrow">OneDegree insights</span>
            <h1 id="insights-page-title">Clear thinking for every study-abroad decision.</h1>
            <p>
              Practical reads for applications, tests, visas, scholarships, and the choices families need to make before they commit.
            </p>
            <div class="insights-page-actions">
              <a class="btn btn-primary" href="#latest">
                <span>Read latest notes</span>
                <i data-lucide="arrow-down"></i>
              </a>
              <a class="btn btn-ghost" href="{{ route('contact') }}">
                <i data-lucide="message-circle"></i>
                <span>Ask an advisor</span>
              </a>
            </div>
          </div>

          <aside class="insights-hero-panel" aria-label="How OneDegree frames advice">
            <div class="insights-hero-image" style="background-image: url('https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1400&q=82');"></div>
            <div class="insights-hero-note">
              <span>Our editorial lens</span>
              <ul>
                <li><i data-lucide="check"></i> Fit before rankings</li>
                <li><i data-lucide="check"></i> Budget before offers</li>
                <li><i data-lucide="check"></i> Evidence before urgency</li>
              </ul>
            </div>
          </aside>
        </div>
      </section>

      <section class="insights-directory" aria-label="Insight categories">
        <div class="container insights-directory-grid">
          <div class="insights-directory-item"><span>01</span> Strategy</div>
          <div class="insights-directory-item"><span>02</span> Tests</div>
          <div class="insights-directory-item"><span>03</span> Visa</div>
          <div class="insights-directory-item"><span>04</span> Finance</div>
          <div class="insights-directory-item"><span>05</span> Planning</div>
        </div>
      </section>

      <section class="insights-feature-section" id="latest" aria-labelledby="featured-title">
        <div class="container">
          <article class="insights-feature-card" id="ivy-league">
            <div class="insights-feature-media" style="background-image: url('https://images.unsplash.com/photo-1606326608606-aa0b62935f2b?auto=format&fit=crop&w=1500&q=82');">
              <span>Featured read</span>
            </div>
            <div class="insights-feature-copy">
              <span class="insights-meta">Strategy &middot; 9 min read</span>
              <h2 id="featured-title">The 2026 selective admissions playbook.</h2>
              <p>
                A calmer way to plan ambitious applications: profile positioning, testing choices, essays, recommendations, shortlist balance, and the risks to settle before deadlines arrive.
              </p>
              <div class="insights-feature-points" aria-label="Featured article points">
                <span>Profile story</span>
                <span>Shortlist balance</span>
                <span>Deadline rhythm</span>
              </div>
              <a href="{{ route('contact') }}">Discuss your profile <i data-lucide="arrow-up-right"></i></a>
            </div>
          </article>
        </div>
      </section>

      <section class="insights-page-section" aria-labelledby="latest-title">
        <div class="container">
          <div class="insights-section-head">
            <div>
              <span class="insights-eyebrow">Latest notes</span>
              <h2 id="latest-title">Read before you apply.</h2>
            </div>
            <p>Short, practical explainers for the decisions that usually create the most confusion at home.</p>
          </div>

          <div class="insights-article-grid">
            <article class="insights-article-card" id="tests">
              <div class="insights-article-media" style="background-image: url('https://images.unsplash.com/photo-1606761568499-6d2451b23c66?auto=format&fit=crop&w=1000&q=80');"></div>
              <div class="insights-article-body">
                <span class="insights-meta">Tests &middot; 5 min read</span>
                <h3>How to approach high-pressure entrance papers without losing accuracy.</h3>
                <p>Question order, time blocks, review habits, rough work discipline, and the small choices that protect marks.</p>
                <a href="{{ route('contact') }}">Build a test plan <i data-lucide="arrow-right"></i></a>
              </div>
            </article>

            <article class="insights-article-card" id="visa">
              <div class="insights-article-media" style="background-image: url('https://images.unsplash.com/photo-1571260899304-425eee4c7efc?auto=format&fit=crop&w=1000&q=80');"></div>
              <div class="insights-article-body">
                <span class="insights-meta">Visa &middot; 7 min read</span>
                <h3>What a credible student visa story needs before the interview.</h3>
                <p>Funding clarity, academic logic, family context, post-study intent, and answers that sound prepared rather than memorized.</p>
                <a href="{{ route('contact') }}">Review visa readiness <i data-lucide="arrow-right"></i></a>
              </div>
            </article>

            <article class="insights-article-card" id="finance">
              <div class="insights-article-media" style="background-image: url('https://images.unsplash.com/photo-1532619675605-1ede6c2ed2b0?auto=format&fit=crop&w=1000&q=80');"></div>
              <div class="insights-article-body">
                <span class="insights-meta">Finance &middot; 6 min read</span>
                <h3>How families should compare scholarships, net cost, and real affordability.</h3>
                <p>Look past headline awards and build a cost view that includes tuition, housing, deposits, insurance, travel, and currency movement.</p>
                <a href="{{ route('contact') }}">Map scholarship options <i data-lucide="arrow-right"></i></a>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section class="insights-decision-band" id="strategy" aria-labelledby="decision-title">
        <div class="container insights-decision-grid">
          <div class="insights-decision-copy">
            <span class="insights-eyebrow">Decision framework</span>
            <h2 id="decision-title">Good advice should make the next step obvious.</h2>
            <p>
              We sort every student decision through five filters so families can move with confidence instead of reacting to noise.
            </p>
          </div>

          <div class="insights-decision-list">
            <article>
              <span>01</span>
              <div>
                <h3>Academic fit</h3>
                <p>Program structure, entry requirements, curriculum depth, and the student&rsquo;s long-term direction.</p>
              </div>
            </article>
            <article>
              <span>02</span>
              <div>
                <h3>Admissions probability</h3>
                <p>Reach, target, and safer options built from evidence instead of hope.</p>
              </div>
            </article>
            <article>
              <span>03</span>
              <div>
                <h3>Financial comfort</h3>
                <p>Net cost, payment timing, scholarship odds, and what the family can sustain.</p>
              </div>
            </article>
            <article>
              <span>04</span>
              <div>
                <h3>Visa readiness</h3>
                <p>Funds, intent, documentation, and the story behind the chosen country and course.</p>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section class="insights-planning-section" id="planning" aria-labelledby="planning-title">
        <div class="container">
          <div class="insights-section-head">
            <div>
              <span class="insights-eyebrow">Planning desk</span>
              <h2 id="planning-title">Questions worth answering early.</h2>
            </div>
            <p>These are the conversations that prevent rushed choices later in the application cycle.</p>
          </div>

          <div class="insights-planning-grid">
            <article>
              <i data-lucide="calendar-days"></i>
              <h3>Which intake gives us enough time?</h3>
              <p>Work backwards from tests, transcripts, essays, scholarships, visa windows, and housing deadlines.</p>
            </article>
            <article>
              <i data-lucide="landmark"></i>
              <h3>Which countries fit the budget?</h3>
              <p>Compare tuition, living cost, work rules, scholarship probability, and family cashflow before applying.</p>
            </article>
            <article>
              <i data-lucide="graduation-cap"></i>
              <h3>What makes the profile distinct?</h3>
              <p>Build a positioning note before essays begin so each application strengthens the same story.</p>
            </article>
            <article>
              <i data-lucide="passport"></i>
              <h3>Is the visa story credible?</h3>
              <p>Prepare funds, intent, academic logic, and post-study answers before the file reaches the final stage.</p>
            </article>
          </div>
        </div>
      </section>

      <section class="insights-cta-section" aria-labelledby="insights-cta-title">
        <div class="container insights-cta-panel">
          <div>
            <span class="insights-eyebrow">Bring us the messy draft</span>
            <h2 id="insights-cta-title">We will turn scattered research into a decision map.</h2>
          </div>
          <a class="btn btn-primary" href="{{ route('contact') }}">
            <span>Book a profile review</span>
            <i data-lucide="arrow-up-right"></i>
          </a>
        </div>
      </section>
    </main>
@endsection