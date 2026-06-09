@php
    $pageTitle = 'Europe Advisory Packages | One Degree Advisory';
    $pageDescription = 'Transparent study-abroad packages for public universities in Europe — admission strategy, applications, documentation, and visa support, with an Admission Guarantee track.';
    $activeNav = 'packages';
    $mainId = 'main';

    $enrolLink = 'https://wa.me/' . config('site.contact.phone_e164');

    $destinations = [
        ['flag' => 'DE', 'name' => 'Germany'],
        ['flag' => 'FR', 'name' => 'France'],
        ['flag' => 'NL', 'name' => 'Netherlands'],
        ['flag' => 'IT', 'name' => 'Italy'],
        ['flag' => 'PL', 'name' => 'Poland'],
        ['flag' => 'AT', 'name' => 'Austria'],
        ['flag' => 'SE', 'name' => 'Sweden'],
        ['flag' => 'LV', 'name' => 'Latvia'],
        ['flag' => 'LT', 'name' => 'Lithuania'],
        ['flag' => 'FI', 'name' => 'Finland'],
        ['flag' => '', 'name' => 'More Destinations Available'],
    ];

    $journey = [
        [
            'label' => 'Step 1',
            'heading' => 'Admission Strategy & Shortlisting',
            'items' => [
                ['name' => 'Student Profile Analysis', 'desc' => 'Discuss your preferences, get expert guidance, answers to your questions, and a personalized study abroad roadmap.'],
                ['name' => 'University Shortlisting', 'desc' => 'Our experts will provide a personalized list of universities best matched to your profile and career goals.'],
                ['name' => 'University Finalization Session', 'desc' => 'Discuss your shortlisted universities and finalize the best options for your applications.'],
            ],
        ],
        [
            'label' => 'Step 2',
            'heading' => 'Application, SOP & Documentation Support',
            'items' => [
                ['name' => 'SOP, LOR & Resume Building Support', 'desc' => 'Access premium templates and professional editing support to craft impactful SOPs, LORs, and resumes tailored to your profile.'],
                ['name' => 'Application Submission Support', 'desc' => 'You’re nearly there — our expert team will take care of your application submission from here.'],
            ],
        ],
        [
            'label' => 'Step 3',
            'heading' => 'Visa Application Filing & Interview Preparation',
            'items' => [
                ['name' => 'Visa Filing', 'desc' => 'Once your admission is received, a visa expert will review your documents.'],
                ['name' => 'Visa Interview Preparation', 'desc' => 'Prepare for your interview with our expert visa counsellors.'],
            ],
        ],
    ];

    $vouchers = [
        ['tier' => 'Explorer', 'icon' => '🌍', 'amount' => '₹1,000', 'variant' => 'explorer'],
        ['tier' => 'Achiever', 'icon' => '🏆', 'amount' => '₹2,000', 'variant' => 'achiever-r', 'badge' => '⭐ Popular'],
        ['tier' => 'Infinity', 'icon' => '♾️', 'amount' => '₹3,000', 'variant' => 'infinity'],
    ];

    $packages = [
        [
            'variant' => 'starter',
            'name' => 'Explorer',
            'badge' => 'Explorer',
            'price' => '₹54,999 + Gst',
            'desc' => 'Perfect for students applying to one European country.',
            'features' => [
                'Profile Evaluation',
                'University Shortlisting',
                'Document Preparation Assistance',
                'Country-specific Doc Guidance (APS, Blocked Account etc.)',
                'Interview Assistance',
                'Application Assistance',
                'Visa Assistance',
                'Pre-departure Guidance',
                'Counsellor Support',
                'IELTS Preparation Included',
                'Education Loan Assistance',
            ],
        ],
        [
            'variant' => 'achiever',
            'name' => 'Achiever',
            'badge' => '⭐ Most Popular',
            'price' => '₹69,999+ Gst',
            'desc' => 'Best for students targeting multiple European countries. (Access to 2 European Countries)',
            'features' => [
                'Everything in Explorer',
                'IELTS Preparation Included',
                'Priority Counsellor Support',
                'Backup Country Planning',
            ],
        ],
        [
            'variant' => 'elite',
            'name' => 'Infinity',
            'badge' => '♾️ Infinity',
            'price' => '₹99,999 + Gst',
            'descHtml' => '<em><em>Admission Guarantee</em> Within Europe – Focused on Your First-Priority Destination</em>*',
            'features' => [
                ['html' => 'Everything in Achiever, <strong>Explore Up to 5 European Countries</strong>'],
                'Premium Counsellor Access',
                'Extended Post-application Support',
            ],
        ],
    ];

    $notIncluded = [
        'IELTS / PTE / language exam fees',
        'University application fees (varies by country/university)',
        'Visa fees (Schengen or country-specific)',
        'APS certificate fee (Germany)',
        'Blocked account deposit (Germany)',
        'HRD apostille / document legalisation charges',
        'CIMEA evaluation fees (Italy)',
        'DOV or equivalent authentication fees',
        'Any consulate or embassy service charges',
        ['html' => '<em><em>Admission Guarantee</em> with opportunities to explore multiple European destinations while prioritizing your preferred study destination.</em>*'],
    ];
@endphp

@extends('layouts.app')

@push('head')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  .odp-file-page {
    --file-blue: #2B1FA8;
    --file-orange: #F05A28;
    --file-line: #ede8ff;
    padding: 0 20px 64px;
    background: linear-gradient(135deg, #f8f5ff, #fff4ef);
    color: #222;
    font-family: "Poppins", sans-serif;
    overflow-x: clip;
  }

  .odp-file-page *,
  .odp-file-page *::before,
  .odp-file-page *::after {
    box-sizing: border-box;
  }

  .odp-file-page :is(h1, h2, h3, p, div, span, a, li, strong, em) {
    font-family: "Poppins", sans-serif;
  }

  .odp-file-container {
    max-width: 1300px;
    margin: 0 auto;
  }

  .odp-web-hero {
    padding: clamp(72px, 8vw, 118px) 0 clamp(52px, 7vw, 86px);
  }

  .odp-web-hero-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.05fr) minmax(320px, 0.72fr);
    gap: clamp(32px, 5vw, 76px);
    align-items: center;
  }

  .odp-web-hero-grid > * {
    min-width: 0;
  }

  .odp-web-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
    color: var(--file-orange);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 1.8px;
    text-transform: uppercase;
  }

  .odp-web-eyebrow::before {
    content: "";
    width: 28px;
    height: 2px;
    border-radius: 999px;
    background: currentColor;
  }

  .odp-web-title {
    max-width: 820px;
    margin: 0;
    color: var(--file-blue);
    font-family: "Cormorant Garamond", Georgia, serif !important;
    font-size: clamp(3.1rem, 6vw, 5.8rem);
    font-weight: 700;
    line-height: 0.98;
    letter-spacing: 0;
    background: linear-gradient(90deg, var(--file-blue), var(--file-orange));
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  .odp-web-copy {
    max-width: 64ch;
    margin: 22px 0 0;
    color: #666;
    font-size: 16px;
    line-height: 1.7;
  }

  .odp-web-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 30px;
  }

  .odp-web-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    min-height: 46px;
    padding: 0 18px;
    border-radius: 999px;
    font-size: 0.9rem;
    font-weight: 800;
    line-height: 1;
    text-decoration: none;
    transition: transform 180ms ease, opacity 180ms ease;
  }

  .odp-web-btn:hover {
    transform: translateY(-2px);
  }

  .odp-web-btn svg {
    width: 18px;
    height: 18px;
  }

  .odp-web-btn-primary {
    background: linear-gradient(135deg, var(--file-blue), var(--file-orange));
    color: #fff;
    box-shadow: 0 18px 40px rgba(43, 31, 168, 0.18);
  }

  .odp-web-btn-secondary {
    border: 1px solid rgba(43, 31, 168, 0.16);
    background: #fff;
    color: var(--file-blue);
  }

  .odp-web-panel {
    border: 1px solid rgba(43, 31, 168, 0.14);
    border-radius: 18px;
    padding: clamp(24px, 4vw, 36px);
    background: linear-gradient(155deg, rgba(255, 255, 255, 0.94), #f7f5ff);
    box-shadow: 0 16px 38px rgba(40, 33, 22, 0.12);
  }

  .odp-web-panel h2 {
    margin: 0 0 16px;
    color: var(--file-blue);
    font-size: clamp(1.35rem, 2.6vw, 2rem);
    font-weight: 800;
    line-height: 1.12;
  }

  .odp-web-list {
    display: grid;
    gap: 12px;
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .odp-web-list li {
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr);
    gap: 12px;
    align-items: center;
    color: #666;
    font-size: 0.94rem;
  }

  .odp-web-list svg {
    width: 34px;
    height: 34px;
    padding: 8px;
    border-radius: 50%;
    background: rgba(240, 90, 40, 0.1);
    color: var(--file-orange);
  }

  .odp-file-surface,
  .odp-file-card,
  .odp-file-plan,
  .odp-file-disclaimer {
    background: #fff;
    box-shadow: 0 10px 35px rgba(0, 0, 0, 0.07);
  }

  .odp-dest-strip {
    margin-bottom: 36px;
    padding: 22px 28px;
    border-radius: 20px;
    text-align: center;
  }

  .odp-dest-label {
    margin: 0 0 16px;
    color: var(--file-blue);
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
  }

  .odp-dest-flags {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px 16px;
  }

  .odp-dest-item {
    display: flex;
    min-width: 80px;
    min-height: 68px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 10px 14px;
    border: 1px solid var(--file-line);
    border-radius: 12px;
    background: linear-gradient(135deg, #f8f5ff, #fff4ef);
    cursor: default;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .odp-dest-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 14px rgba(43, 31, 168, 0.13);
  }

  .odp-dest-flag {
    min-height: 28px;
    color: var(--file-blue);
    font-size: 28px;
    font-weight: 800;
    line-height: 1;
  }

  .odp-dest-name {
    color: var(--file-blue);
    font-size: 11px;
    font-weight: 600;
    text-align: center;
  }

  .odp-journey,
  .odp-referral {
    position: relative;
    overflow: hidden;
    margin-bottom: 48px;
    padding: 40px 36px;
    border-radius: 28px;
  }

  .odp-journey {
    width: min(calc(100vw - 24px), 1520px);
    margin-left: 50%;
    transform: translateX(-50%);
  }

  .odp-journey::before,
  .odp-referral::before {
    content: "";
    position: absolute;
    inset: 0 0 auto;
    height: 5px;
  }

  .odp-journey::before {
    background: linear-gradient(90deg, var(--file-blue), var(--file-orange));
  }

  .odp-referral::before {
    background: linear-gradient(90deg, var(--file-orange), var(--file-blue));
  }

  .odp-journey-title,
  .odp-referral-title {
    margin: 0;
    color: var(--file-blue);
    font-size: 22px;
    font-weight: 700;
    line-height: 1.3;
    text-align: center;
  }

  .odp-journey-title {
    margin-bottom: 32px;
  }

  .odp-referral-title {
    margin-bottom: 8px;
  }

  .odp-referral-sub {
    margin: 0 0 32px;
    color: #888;
    font-size: 14px;
    text-align: center;
  }

  .odp-journey-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 28px;
  }

  .odp-step {
    padding: 24px;
    border: 1px solid var(--file-line);
    border-radius: 18px;
    background: linear-gradient(135deg, #f8f5ff, #fff4ef);
  }

  .odp-step-num {
    display: inline-flex;
    width: 36px;
    height: 36px;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--file-blue), var(--file-orange));
    color: #fff;
    font-size: 14px;
    font-weight: 700;
  }

  .odp-step-label {
    margin-bottom: 6px;
    color: var(--file-orange);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
  }

  .odp-step-heading {
    margin: 0 0 14px;
    color: var(--file-blue);
    font-size: 16px;
    font-weight: 700;
    line-height: 1.25;
  }

  .odp-step-items,
  .odp-plan-list,
  .odp-disclaimer-list {
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .odp-step-items li {
    padding: 8px 0;
    border-bottom: 1px solid var(--file-line);
    color: #444;
    font-size: 13.5px;
    line-height: 1.5;
  }

  .odp-step-items li:last-child {
    border-bottom: 0;
  }

  .odp-si-name {
    color: var(--file-blue);
    font-size: 13px;
    font-weight: 600;
  }

  .odp-si-desc {
    margin-top: 2px;
    color: #888;
    font-size: 12px;
  }

  .odp-final-step {
    padding: 24px;
    border-radius: 18px;
    background: linear-gradient(135deg, var(--file-blue), #7B2FF7);
    color: #fff;
    text-align: center;
  }

  .odp-final-step .plane {
    margin-bottom: 8px;
    font-size: 36px;
  }

  .odp-final-step h3 {
    margin: 0 0 6px;
    font-size: 18px;
    font-weight: 700;
  }

  .odp-final-step p {
    margin: 0;
    font-size: 13px;
    opacity: 0.85;
  }

  .odp-referral-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
  }

  .odp-ref-controls {
    display: none;
  }

  .odp-ref-card {
    position: relative;
    overflow: hidden;
    padding: 28px 20px;
    border-radius: 18px;
    text-align: center;
  }

  .odp-ref-card.explorer {
    border: 2px solid #ddd;
    background: linear-gradient(135deg, #ece8ff, #f8f5ff);
  }

  .odp-ref-card.achiever-r {
    border: 2px solid var(--file-orange);
    background: linear-gradient(135deg, #fff0eb, #fff8f5);
  }

  .odp-ref-card.infinity {
    color: #fff;
    background: linear-gradient(135deg, var(--file-blue), #5540d8);
  }

  .odp-ref-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 3px 8px;
    border-radius: 999px;
    background: var(--file-orange);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
  }

  .odp-ref-icon {
    margin-bottom: 10px;
    font-size: 28px;
  }

  .odp-ref-plan {
    margin-bottom: 6px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    opacity: 0.7;
  }

  .odp-ref-voucher {
    margin-bottom: 4px;
    color: var(--file-blue);
    font-size: 36px;
    font-weight: 800;
  }

  .odp-ref-card.achiever-r .odp-ref-voucher {
    color: var(--file-orange);
  }

  .odp-ref-card.infinity .odp-ref-voucher {
    color: #fff;
  }

  .odp-ref-label {
    color: #888;
    font-size: 12px;
    font-weight: 500;
  }

  .odp-ref-card.infinity .odp-ref-label {
    color: rgba(255, 255, 255, 0.7);
  }

  .odp-packages-heading {
    margin-bottom: 32px;
    text-align: center;
  }

  .odp-packages-heading h2 {
    margin: 0;
    color: var(--file-blue);
    font-size: 28px;
    font-weight: 700;
    line-height: 1.25;
  }

  .odp-packages-heading p {
    margin: 6px 0 0;
    color: #888;
    font-size: 15px;
  }

  .odp-plans {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 30px;
  }

  .odp-file-plan {
    position: relative;
    overflow: hidden;
    padding: 35px;
    border: 2px solid transparent;
    border-radius: 28px;
    transition: transform 0.4s ease;
  }

  .odp-file-plan:hover {
    transform: translateY(-6px);
  }

  .odp-file-plan.starter {
    border-color: #ddd;
  }

  .odp-file-plan.achiever {
    border-color: var(--file-orange);
    transform: scale(1.03);
  }

  .odp-file-plan.achiever:hover {
    transform: scale(1.03) translateY(-6px);
  }

  .odp-file-plan.elite {
    border-color: var(--file-blue);
  }

  .odp-highlight {
    position: absolute;
    top: -60px;
    right: -60px;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: rgba(240, 90, 40, 0.08);
    pointer-events: none;
  }

  .odp-badge {
    display: inline-block;
    margin-bottom: 20px;
    padding: 8px 16px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 600;
  }

  .odp-file-plan.starter .odp-badge {
    background: #ece8ff;
    color: var(--file-blue);
  }

  .odp-file-plan.achiever .odp-badge {
    background: var(--file-orange);
    color: #fff;
  }

  .odp-file-plan.elite .odp-badge {
    background: linear-gradient(90deg, var(--file-blue), #5540d8);
    color: #fff;
  }

  .odp-plan-name {
    margin: 0 0 10px;
    color: #222;
    font-size: 34px;
    font-weight: 700;
    line-height: 1.2;
  }

  .odp-plan-price {
    margin-bottom: 8px;
    color: var(--file-blue);
    font-size: 42px;
    font-weight: 800;
    line-height: 1.08;
  }

  .odp-plan-desc {
    margin: 0 0 25px;
    color: #666;
    line-height: 1.6;
  }

  .odp-plan-list {
    margin-bottom: 24px;
  }

  .odp-plan-list li {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    padding: 11px 0;
    border-bottom: 1px solid #eee;
    font-size: 15px;
    line-height: 1.5;
  }

  .odp-plan-list li:last-child {
    border-bottom: 0;
  }

  .odp-check {
    flex-shrink: 0;
    color: var(--file-orange);
    font-weight: 700;
  }

  .odp-enrol {
    display: block;
    width: 100%;
    padding: 15px;
    border-radius: 16px;
    color: #fff;
    font-size: 15px;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    transition: opacity 0.3s ease, transform 0.3s ease;
  }

  .odp-file-plan.starter .odp-enrol {
    background: var(--file-blue);
  }

  .odp-file-plan.achiever .odp-enrol {
    background: var(--file-orange);
  }

  .odp-file-plan.elite .odp-enrol {
    background: linear-gradient(90deg, var(--file-blue), var(--file-orange));
  }

  .odp-enrol:hover {
    opacity: 0.88;
    transform: translateY(-1px);
  }

  .odp-file-disclaimer {
    margin-top: 40px;
    padding: 28px 32px;
    border-left: 5px solid var(--file-orange);
    border-radius: 24px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.06);
  }

  .odp-file-disclaimer h2 {
    margin: 0 0 12px;
    color: var(--file-blue);
    font-size: 16px;
    font-weight: 700;
    line-height: 1.35;
    text-align: center;
  }

  .odp-disclaimer-list li {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    padding: 5px 0;
    border-bottom: 1px solid #f5f5f5;
    color: #666;
    font-size: 13px;
    line-height: 1.5;
    break-inside: avoid;
  }

  .odp-disclaimer-list li:last-child {
    border-bottom: 0;
  }

  .odp-disclaimer-list {
    columns: 2;
    column-gap: 34px;
  }

  @media (max-width: 768px) {
    .odp-file-page {
      padding: 0 14px 48px;
    }

    .odp-web-hero {
      padding: 42px 0 34px;
    }

    .odp-web-hero-grid {
      grid-template-columns: 1fr;
      gap: 28px;
    }

    .odp-web-title {
      font-size: clamp(2.35rem, 11vw, 3.25rem);
      line-height: 1.02;
    }

    .odp-web-actions {
      display: grid;
    }

    .odp-web-btn {
      width: 100%;
    }

    .odp-journey,
    .odp-referral {
      padding: 30px 20px;
      border-radius: 22px;
    }

    .odp-journey {
      width: 100%;
      margin-left: 0;
      transform: none;
    }

    .odp-journey-steps,
    .odp-plans {
      grid-template-columns: 1fr;
    }

    .odp-referral-cards {
      display: flex;
      gap: 16px;
      margin-inline: -20px;
      padding: 2px 20px 4px;
      overflow-x: auto;
      overscroll-behavior-x: contain;
      scroll-padding-inline: 20px;
      scroll-snap-type: x mandatory;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: none;
    }

    .odp-referral-cards::-webkit-scrollbar {
      display: none;
    }

    .odp-ref-card {
      flex: 0 0 min(82vw, 320px);
      scroll-snap-align: center;
    }

    .odp-ref-controls {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 8px;
      margin-top: 18px;
    }

    .odp-ref-control {
      min-width: 0;
      min-height: 42px;
      border: 1px solid var(--file-line);
      border-radius: 999px;
      background: #fff;
      color: var(--file-blue);
      font-family: "Poppins", sans-serif;
      font-size: 12px;
      font-weight: 800;
      box-shadow: 0 8px 20px rgba(43, 31, 168, 0.08);
      transition: transform 0.2s ease, background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
    }

    .odp-ref-control:hover,
    .odp-ref-control:focus-visible,
    .odp-ref-control.is-active {
      background: linear-gradient(135deg, var(--file-blue), var(--file-orange));
      color: #fff;
      transform: translateY(-2px);
      box-shadow: 0 12px 26px rgba(43, 31, 168, 0.18);
      outline: none;
    }

    .odp-file-plan.achiever,
    .odp-file-plan.achiever:hover {
      transform: none;
    }

    .odp-file-plan {
      padding: 28px 22px;
    }

    .odp-plan-price {
      font-size: 34px;
    }

    .odp-disclaimer-list {
      columns: 1;
    }

    .odp-dest-strip {
      padding: 20px 14px;
    }
  }

  @media (max-width: 520px) {
    .odp-dest-label,
    .odp-journey-title,
    .odp-referral-title,
    .odp-packages-heading h2,
    .odp-file-disclaimer h2 {
      overflow-wrap: anywhere;
    }

    .odp-dest-label {
      font-size: 12px;
      letter-spacing: 0.7px;
    }

    .odp-dest-item {
      min-width: calc(50% - 10px);
    }

    .odp-packages-heading h2 {
      font-size: 23px;
    }

    .odp-journey-title,
    .odp-referral-title {
      font-size: 20px;
    }
  }
</style>
@endpush

@section('content')
<main id="main" class="odp-file-page">
  <div class="odp-file-container">
    <header class="odp-web-hero">
      <div class="odp-web-hero-grid">
        <div>
          <span class="odp-web-eyebrow">Europe advisory packages</span>
          <h1 class="odp-web-title">One degree closer to your dream university in Europe.</h1>
          <p class="odp-web-copy">Transparent, expert-led packages for public universities across Europe — from admission strategy and documentation to visa filing and arrival.</p>
          <div class="odp-web-actions">
            <a class="odp-web-btn odp-web-btn-secondary" href="{{ route('contact') }}">
              <i data-lucide="calendar-check" aria-hidden="true"></i>
              <span>Book a consultation</span>
            </a>
          </div>
        </div>

        <aside class="odp-web-panel" aria-label="Package highlights">
          <h2>Built for public university applications in Europe.</h2>
          <ul class="odp-web-list">
            <li><i data-lucide="map" aria-hidden="true"></i><span>Country-fit strategy before applications begin.</span></li>
            <li><i data-lucide="file-check-2" aria-hidden="true"></i><span>SOP, LOR, resume, application and visa support.</span></li>
            <li><i data-lucide="shield-check" aria-hidden="true"></i><span>Admission Guarantee track available with Infinity.</span></li>
          </ul>
        </aside>
      </div>
    </header>

    <section class="odp-file-surface odp-dest-strip" aria-labelledby="odp-dest-title">
      <h2 class="odp-dest-label" id="odp-dest-title">🌍 Top Study Destinations in Europe</h2>
      <div class="odp-dest-flags">
        @foreach ($destinations as $destination)
          <div class="odp-dest-item">
            <span class="odp-dest-flag">{{ $destination['flag'] }}</span>
            <span class="odp-dest-name">{{ $destination['name'] }}</span>
          </div>
        @endforeach
      </div>
    </section>

    <section class="odp-file-surface odp-journey" aria-labelledby="odp-journey-title">
      <h2 class="odp-journey-title" id="odp-journey-title">✈️ Start Your Study Abroad Journey with One Degree Advisory</h2>
      <div class="odp-journey-steps">
        @foreach ($journey as $index => $step)
          <article class="odp-step">
            <div class="odp-step-num">{{ $index + 1 }}</div>
            <div class="odp-step-label">{{ $step['label'] }}</div>
            <h3 class="odp-step-heading">{{ $step['heading'] }}</h3>
            <ul class="odp-step-items">
              @foreach ($step['items'] as $item)
                <li>
                  <div class="odp-si-name">{{ $item['name'] }}</div>
                  <div class="odp-si-desc">{{ $item['desc'] }}</div>
                </li>
              @endforeach
            </ul>
          </article>
        @endforeach

        <article class="odp-final-step">
          <div class="plane" aria-hidden="true">✈️</div>
          <h3>Fly to Your Dream University!</h3>
          <p>You’re ready to take off! The One Degree community is excited to welcome you to your dream college.</p>
        </article>
      </div>
    </section>

    <section class="odp-file-surface odp-referral" aria-labelledby="odp-referral-title">
      <h2 class="odp-referral-title" id="odp-referral-title">🎁 Refer &amp; Earn — Student Vouchers</h2>
      <p class="odp-referral-sub">Refer a friend and earn credits when they enrol. The more they grow, the more you earn!</p>
      <div class="odp-referral-cards">
        @foreach ($vouchers as $index => $voucher)
          <article class="odp-ref-card {{ $voucher['variant'] }}" id="odp-ref-{{ $voucher['variant'] }}" data-ref-slide="{{ $index }}">
            @isset($voucher['badge'])
              <div class="odp-ref-badge">{{ $voucher['badge'] }}</div>
            @endisset
            <div class="odp-ref-icon" aria-hidden="true">{{ $voucher['icon'] }}</div>
            <div class="odp-ref-plan">{{ $voucher['tier'] }}</div>
            <div class="odp-ref-voucher">{{ $voucher['amount'] }}</div>
            <div class="odp-ref-label">Credit per referral</div>
          </article>
        @endforeach
      </div>
      <div class="odp-ref-controls" aria-label="Voucher carousel controls">
        @foreach ($vouchers as $index => $voucher)
          <button class="odp-ref-control @if($index === 0) is-active @endif" type="button" data-ref-target="{{ $index }}" aria-label="Show {{ $voucher['tier'] }} voucher">
            {{ $voucher['tier'] }}
          </button>
        @endforeach
      </div>
    </section>

    <section aria-labelledby="odp-packages-title">
      <div class="odp-packages-heading">
        <h2 id="odp-packages-title">Europe Study Abroad Packages for Public University</h2>
        <p>Choose the plan that fits your ambition</p>
      </div>

      <div class="odp-plans">
        @foreach ($packages as $package)
          <article class="odp-file-plan {{ $package['variant'] }}">
            <div class="odp-highlight" aria-hidden="true"></div>
            <div class="odp-badge">{{ $package['badge'] }}</div>
            <h3 class="odp-plan-name">{{ $package['name'] }}</h3>
            <div class="odp-plan-price">{{ $package['price'] }}</div>
            <p class="odp-plan-desc">
              @if (isset($package['descHtml']))
                {!! $package['descHtml'] !!}
              @else
                {{ $package['desc'] }}
              @endif
            </p>
            <ul class="odp-plan-list">
              @foreach ($package['features'] as $feature)
                <li>
                  <span class="odp-check" aria-hidden="true">✓</span>
                  <span>
                    @if (is_array($feature))
                      {!! $feature['html'] !!}
                    @else
                      {{ $feature }}
                    @endif
                  </span>
                </li>
              @endforeach
            </ul>
            <a class="odp-enrol" href="{{ $enrolLink }}" target="_blank" rel="noopener">Enrol Now &nbsp;→</a>
          </article>
        @endforeach
      </div>
    </section>

    <section class="odp-file-disclaimer" aria-labelledby="odp-disclaimer-title">
      <h2 id="odp-disclaimer-title">⚠️ Not Included — Paid Directly by Student</h2>
      <ul class="odp-disclaimer-list">
        @foreach ($notIncluded as $item)
          <li>
            <span class="odp-check" aria-hidden="true">•</span>
            <span>
              @if (is_array($item))
                {!! $item['html'] !!}
              @else
                {{ $item }}
              @endif
            </span>
          </li>
        @endforeach
      </ul>
    </section>
  </div>
</main>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var carousel = document.querySelector('.odp-referral-cards');
    var buttons = Array.prototype.slice.call(document.querySelectorAll('.odp-ref-control'));
    var slides = Array.prototype.slice.call(document.querySelectorAll('[data-ref-slide]'));

    if (!carousel || buttons.length === 0 || slides.length === 0) {
      return;
    }

    function setActive(index) {
      buttons.forEach(function (button, buttonIndex) {
        button.classList.toggle('is-active', buttonIndex === index);
      });
    }

    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        var index = Number(button.getAttribute('data-ref-target'));
        var slide = slides[index];

        if (!slide) {
          return;
        }

        carousel.scrollTo({
          left: slide.offsetLeft - carousel.offsetLeft - 20,
          behavior: 'smooth'
        });
        setActive(index);
      });
    });

    carousel.addEventListener('scroll', function () {
      var center = carousel.scrollLeft + carousel.clientWidth / 2;
      var activeIndex = 0;
      var closest = Infinity;

      slides.forEach(function (slide, index) {
        var slideCenter = slide.offsetLeft + slide.offsetWidth / 2;
        var distance = Math.abs(center - slideCenter);

        if (distance < closest) {
          closest = distance;
          activeIndex = index;
        }
      });

      setActive(activeIndex);
    }, { passive: true });
  });
</script>
@endsection
