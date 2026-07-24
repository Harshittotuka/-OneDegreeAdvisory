@php
    $pageTitle = 'Terms & Conditions | One Degree Advisory';
    $pageDescription = 'Read the One Degree Advisory Terms & Conditions — the terms that govern your access to and use of onedegreeadvisory.com and our educational consulting, career guidance, admissions, and related services.';
    $activeNav = '';
    $mainId = 'main';

    // The source document carries a placeholder effective date; we surface a real,
    // human date and keep the machine-readable value for structured data.
    $effectiveDate = 'July 24, 2026';
    $effectiveDateIso = '2026-07-24';

    // Section map — single source of truth for the table of contents, anchors,
    // and the rendered headings.
    $sections = [
        'eligibility'             => 'Eligibility',
        'our-services'            => 'Our Services',
        'user-responsibilities'   => 'User Responsibilities',
        'user-accounts'           => 'User Accounts',
        'payments-refunds'        => 'Payments & Refunds',
        'accuracy-of-information' => 'Accuracy of Information',
        'intellectual-property'   => 'Intellectual Property',
        'third-party-websites'    => 'Third-Party Websites',
        'limitation-of-liability' => 'Limitation of Liability',
        'privacy'                 => 'Privacy',
        'suspension-termination'  => 'Suspension or Termination',
        'changes'                 => 'Changes to the Terms',
        'governing-law'           => 'Governing Law & Jurisdiction',
        'contact-us'              => 'Contact Us',
    ];
@endphp

@extends('layouts.app')

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => 'Terms & Conditions',
    'description' => $pageDescription,
    'url' => route('terms'),
    'inLanguage' => 'en',
    'dateModified' => $effectiveDateIso,
    'isPartOf' => [
        '@type' => 'WebSite',
        'name' => config('site.name'),
        'url' => url('/'),
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => config('site.name'),
        'url' => url('/'),
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
<main id="main" class="legal-page">
  <section class="legal-wrap" id="top">
    <div class="container">

      {{-- ── Header ── --}}
      <header class="legal-head">
        <span class="eyebrow">Legal</span>
        <h1>Terms &amp; Conditions</h1>
        <p class="legal-lead">
          Welcome to {{ config('site.name') }} (&ldquo;Company&rdquo;, &ldquo;we&rdquo;, &ldquo;our&rdquo;, or &ldquo;us&rdquo;).
          These Terms &amp; Conditions (&ldquo;Terms&rdquo;) govern your access to and use of onedegreeadvisory.com and our
          educational consulting, career guidance, admissions support, and related services. By accessing or using our
          Website or services, you agree to comply with these Terms. If you do not agree with any part of these Terms,
          please discontinue use of our Website and services.
        </p>
        <p class="legal-date">Effective Date: <time datetime="{{ $effectiveDateIso }}">{{ $effectiveDate }}</time></p>
      </header>

      <div class="legal-grid">

        {{-- ── Table of contents ── --}}
        <aside class="legal-toc" aria-label="On this page">
          <div class="legal-toc-inner">
            <span class="legal-toc-title">Contents</span>
            <nav>
              <ol>
                @foreach ($sections as $anchor => $title)
                  <li><a href="#{{ $anchor }}" data-toc-link="{{ $anchor }}">{{ $title }}</a></li>
                @endforeach
              </ol>
            </nav>
          </div>
        </aside>

        {{-- ── Terms content ── --}}
        <article class="legal-content">

          <section id="eligibility" aria-labelledby="eligibility-title">
            <h2 id="eligibility-title">1. Eligibility</h2>
            <p>By using our Website, you confirm that:</p>
            <ul>
              <li>You are at least 18 years of age, or you are using our services with the consent of a parent or legal guardian.</li>
              <li>The information you provide is accurate, complete, and up to date.</li>
            </ul>
          </section>

          <section id="our-services" aria-labelledby="our-services-title">
            <h2 id="our-services-title">2. Our Services</h2>
            <p>{{ config('site.name') }} provides educational consultancy and related support services, including but not limited to:</p>
            <ul>
              <li>Career counselling</li>
              <li>University and course guidance</li>
              <li>Admission assistance</li>
              <li>Scholarship guidance</li>
              <li>Visa guidance (where applicable)</li>
              <li>Test preparation support</li>
              <li>Profile evaluation and document review</li>
              <li>Other educational advisory services</li>
            </ul>
            <p>We assist students throughout their application journey but <strong>do not guarantee admission, scholarships, visas, employment, or any specific outcome</strong>, as these decisions are solely made by universities, educational institutions, embassies, employers, or other relevant authorities.</p>
          </section>

          <section id="user-responsibilities" aria-labelledby="user-responsibilities-title">
            <h2 id="user-responsibilities-title">3. User Responsibilities</h2>
            <p>By using our services, you agree to:</p>
            <ul>
              <li>Provide genuine, complete, and accurate information and documents.</li>
              <li>Update your information whenever required.</li>
              <li>Cooperate with our team during the admission process.</li>
              <li>Use the Website only for lawful purposes.</li>
            </ul>
            <p>You agree <strong>not</strong> to:</p>
            <ul>
              <li>Submit false, forged, or misleading documents.</li>
              <li>Misuse or attempt to interfere with the Website or its security.</li>
              <li>Use our services for fraudulent or unlawful purposes.</li>
              <li>Copy or reproduce Website content without written permission.</li>
            </ul>
          </section>

          <section id="user-accounts" aria-labelledby="user-accounts-title">
            <h2 id="user-accounts-title">4. User Accounts</h2>
            <p>Some services may require you to create an account. You are responsible for:</p>
            <ul>
              <li>Maintaining the confidentiality of your login credentials.</li>
              <li>All activities conducted through your account.</li>
            </ul>
            <p>We reserve the right to suspend or terminate accounts that violate these Terms.</p>
          </section>

          <section id="payments-refunds" aria-labelledby="payments-refunds-title">
            <h2 id="payments-refunds-title">5. Payments &amp; Refunds</h2>
            <p>Where applicable:</p>
            <ul>
              <li>Fees for consultancy or other paid services will be communicated before payment.</li>
              <li>Payments made are subject to the applicable refund policy.</li>
              <li>Government fees, university application fees, visa fees, examination fees, courier charges, or third-party charges are generally non-refundable unless specifically stated otherwise.</li>
            </ul>
          </section>

          <section id="accuracy-of-information" aria-labelledby="accuracy-of-information-title">
            <h2 id="accuracy-of-information-title">6. Accuracy of Information</h2>
            <p>While we make every reasonable effort to provide accurate and updated information regarding universities, courses, scholarships, admissions, and immigration processes, we do not guarantee that all information is always complete, current, or error-free.</p>
            <p>Users are encouraged to verify important information directly with the respective institutions or authorities.</p>
          </section>

          <section id="intellectual-property" aria-labelledby="intellectual-property-title">
            <h2 id="intellectual-property-title">7. Intellectual Property</h2>
            <p>All content available on this Website, including text, graphics, logos, designs, images, videos, documents, and software, is the intellectual property of {{ config('site.name') }} unless otherwise stated.</p>
            <p>No content may be copied, reproduced, distributed, or modified without our prior written permission.</p>
          </section>

          <section id="third-party-websites" aria-labelledby="third-party-websites-title">
            <h2 id="third-party-websites-title">8. Third-Party Websites</h2>
            <p>Our Website may contain links to third-party websites, universities, service providers, or payment gateways.</p>
            <p>We are not responsible for the content, privacy policies, security, or practices of any third-party website.</p>
          </section>

          <section id="limitation-of-liability" aria-labelledby="limitation-of-liability-title">
            <h2 id="limitation-of-liability-title">9. Limitation of Liability</h2>
            <p>To the maximum extent permitted by law, {{ config('site.name') }} shall not be liable for any direct, indirect, incidental, consequential, or special damages arising from:</p>
            <ul>
              <li>Admission decisions</li>
              <li>Visa refusals</li>
              <li>Scholarship decisions</li>
              <li>Delays caused by universities or government authorities</li>
              <li>Errors resulting from incorrect information provided by users</li>
              <li>Technical interruptions or Website downtime</li>
            </ul>
            <p>Users acknowledge that all final decisions rest with the respective institutions or authorities.</p>
          </section>

          <section id="privacy" aria-labelledby="privacy-title">
            <h2 id="privacy-title">10. Privacy</h2>
            <p>Your use of our Website is also governed by our <a href="{{ route('privacy') }}">Privacy Policy</a>, which explains how we collect, use, and protect your personal information.</p>
          </section>

          <section id="suspension-termination" aria-labelledby="suspension-termination-title">
            <h2 id="suspension-termination-title">11. Suspension or Termination</h2>
            <p>We reserve the right to suspend or terminate access to our Website or services without prior notice if:</p>
            <ul>
              <li>These Terms are violated.</li>
              <li>Fraudulent or illegal activities are suspected.</li>
              <li>Misuse of our services is identified.</li>
            </ul>
          </section>

          <section id="changes" aria-labelledby="changes-title">
            <h2 id="changes-title">12. Changes to the Terms</h2>
            <p>We may update these Terms &amp; Conditions from time to time.</p>
            <p>The updated version will be published on this page with the revised Effective Date. Continued use of the Website constitutes acceptance of the updated Terms.</p>
          </section>

          <section id="governing-law" aria-labelledby="governing-law-title">
            <h2 id="governing-law-title">13. Governing Law &amp; Jurisdiction</h2>
            <p>These Terms shall be governed by and interpreted in accordance with the laws of India.</p>
            <p>Any dispute arising out of or relating to these Terms or our services shall be subject to the exclusive jurisdiction of the competent courts located in <strong>Jaipur, Rajasthan, India</strong>.</p>
          </section>

          <section id="contact-us" aria-labelledby="contact-us-title">
            <h2 id="contact-us-title">14. Contact Us</h2>
            <p>If you have any questions regarding these Terms &amp; Conditions, please contact us:</p>
            <p class="legal-contact">
              <strong>{{ config('site.name') }}</strong><br>
              Website: <a href="{{ url('/') }}" rel="noopener">https://onedegreeadvisory.com</a><br>
              Email: <a href="mailto:{{ config('site.contact.email') }}">{{ config('site.contact.email') }}</a><br>
              Phone: <a href="https://wa.me/{{ config('site.contact.phone_e164') }}" target="_blank" rel="noopener">{{ config('site.contact.phone') }}</a>
            </p>
          </section>

        </article>
      </div>
    </div>
  </section>
</main>

<script>
  // Scrollspy — highlight the current section in the contents list.
  (function () {
    var run = function () {
      var links = Array.prototype.slice.call(document.querySelectorAll('[data-toc-link]'));
      if (!links.length || !('IntersectionObserver' in window)) return;
      var map = {};
      links.forEach(function (l) { map[l.getAttribute('data-toc-link')] = l; });
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            links.forEach(function (l) { l.classList.remove('is-active'); });
            if (map[entry.target.id]) map[entry.target.id].classList.add('is-active');
          }
        });
      }, { rootMargin: '-25% 0px -65% 0px', threshold: 0 });
      document.querySelectorAll('.legal-content > section').forEach(function (s) { observer.observe(s); });
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
    else run();
  })();
</script>

@push('head')
<style>
  /* ── Terms / legal page — plain, matches the site's light pages ── */
  .legal-page { background: #fbf9f5; }
  .legal-wrap { padding: 104px 0 120px; }

  .legal-head {
    max-width: 940px;
    margin: 0 0 48px;
    padding-bottom: 28px;
    border-bottom: 1px solid var(--line);
  }
  .legal-head h1 {
    margin: 12px 0 22px;
    color: var(--navy);
    font-size: clamp(2.8rem, 6vw, 4.4rem);
  }
  .legal-lead {
    color: var(--muted);
    font-size: 1.08rem;
    line-height: 1.7;
    margin: 0 0 18px;
  }
  .legal-date {
    margin: 0;
    font-size: 0.9rem;
    color: var(--muted);
  }
  .legal-date time { color: var(--ink); font-weight: 600; }

  .legal-grid {
    display: grid;
    grid-template-columns: 252px minmax(0, 1fr);
    gap: 56px;
    align-items: start;
  }

  /* Contents — plain text list that stays pinned while the page scrolls.
     The column must stretch to the full row height (align-self: stretch) or
     the sticky inner has no room to travel. */
  .legal-toc { align-self: stretch; }
  .legal-toc-inner { position: sticky; top: calc(var(--header-offset) + 16px); }
  .legal-toc-title {
    display: block;
    font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.1em;
    font-weight: 700; color: var(--muted);
    margin: 0 0 14px;
  }
  .legal-toc ol {
    list-style: none; margin: 0; padding: 0;
    border-left: 1px solid var(--line);
  }
  /* Override the global `main li { text-align: justify }` so wrapped TOC
     items don't stretch their words across the line. */
  .legal-toc li { text-align: left; }
  .legal-toc a {
    display: block;
    padding: 7px 0 7px 16px;
    margin-left: -1px;
    border-left: 2px solid transparent;
    color: var(--muted);
    text-decoration: none;
    text-align: left;
    font-size: 0.92rem;
    line-height: 1.4;
    transition: color .15s, border-color .15s;
  }
  .legal-toc a:hover { color: var(--ink); }
  .legal-toc a.is-active {
    color: var(--teal-dark);
    border-left-color: var(--teal-dark);
    font-weight: 600;
  }

  /* Content prose */
  .legal-content { max-width: 100%; }
  .legal-content > section { scroll-margin-top: calc(var(--header-offset) + 20px); }
  .legal-content > section + section { margin-top: 40px; }
  .legal-content h2 {
    color: var(--navy);
    font-size: clamp(1.5rem, 2.4vw, 1.9rem);
    line-height: 1.15;
    margin: 0 0 14px;
  }
  .legal-content p {
    color: var(--muted);
    font-size: 1.02rem;
    line-height: 1.75;
    margin: 0 0 12px;
  }
  .legal-content p strong { color: var(--ink); }
  .legal-content ul {
    margin: 0 0 12px;
    padding-left: 20px;
  }
  .legal-content ul li {
    color: var(--muted);
    font-size: 1.02rem;
    line-height: 1.7;
    margin-bottom: 8px;
    padding-left: 4px;
  }
  .legal-content ul li::marker { color: var(--teal-dark); }
  .legal-contact { line-height: 2; }
  .legal-contact a { color: var(--teal-dark); }

  @media (max-width: 860px) {
    .legal-wrap { padding: 88px 0 80px; }
    .legal-grid { grid-template-columns: 1fr; gap: 36px; }
    .legal-toc-inner { position: static; }
    .legal-toc ol {
      display: flex; flex-wrap: wrap; gap: 2px 20px;
      border-left: none;
      padding-bottom: 16px;
      border-bottom: 1px solid var(--line);
    }
    .legal-toc a { padding: 4px 0; border-left: none; }
    .legal-toc a.is-active { border-left: none; }
  }
</style>
@endpush
@endsection
