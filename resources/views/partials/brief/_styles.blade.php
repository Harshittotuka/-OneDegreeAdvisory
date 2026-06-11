{{-- Shared "ODP file page" design system — used by the Europe packages page and
     the three intelligence-brief pages (Wednesday Briefings, Medicine & Beyond,
     Destination New Zealand). Purple #2B1FA8 / orange #F05A28, Poppins. The base
     .odp-* classes were moved here verbatim from the old packages page; the
     .odp-section/.odp-brief-* etc. block below is added for brief content. --}}
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
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 28px;
    color: var(--file-blue);
    font-size: 28px;
    font-weight: 800;
    line-height: 1;
  }

  .odp-dest-flag img {
    width: 36px;
    height: auto;
    border-radius: 4px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.18);
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

  /* ============================================================
     BRIEF CONTENT — shared by NZ / Medicine / Wednesday pages
     ============================================================ */
  .odp-section { margin-bottom: 40px; }

  .odp-surface-pad { padding: 26px 30px; border-radius: 22px; margin-bottom: 28px; }

  .odp-section-label {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 16px;
    color: #8a86a8;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.6px;
    text-transform: uppercase;
  }

  .odp-section-label::before {
    content: "";
    width: 22px;
    height: 2px;
    border-radius: 999px;
    background: var(--file-orange);
  }

  .odp-block-title {
    margin: 0 0 18px;
    color: var(--file-blue);
    font-size: 22px;
    font-weight: 700;
    line-height: 1.3;
  }

  .odp-block-sub {
    margin: -8px 0 18px;
    color: #888;
    font-size: 14px;
    line-height: 1.6;
  }

  /* Highlight cards */
  .odp-brief-cards { display: grid; gap: 14px; }

  .odp-brief-card {
    position: relative;
    padding: 18px 20px;
    border: 1px solid var(--file-line);
    border-left: 4px solid var(--file-orange);
    border-radius: 14px;
    background: linear-gradient(135deg, #fbf9ff, #fff6f1);
  }

  .odp-brief-card--medium { border-left-color: #f0b15a; }

  .odp-brief-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
  }

  .odp-brief-card h3 {
    margin: 0;
    color: var(--file-blue);
    font-size: 15px;
    font-weight: 700;
    line-height: 1.35;
  }

  .odp-brief-badge {
    flex: none;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.5px;
    background: rgba(240, 90, 40, 0.14);
    color: #b53d12;
  }

  .odp-brief-badge--medium { background: rgba(240, 177, 90, 0.22); color: #8a5a12; }

  .odp-brief-card p { margin: 8px 0 0; color: #5b5b6b; font-size: 13.5px; line-height: 1.65; }

  .odp-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 12px; }

  .odp-tag {
    padding: 3px 10px;
    border-radius: 999px;
    background: #efe9ff;
    color: var(--file-blue);
    font-size: 10px;
    font-weight: 600;
  }

  /* 2-col split + info cards */
  .odp-split { display: grid; grid-template-columns: repeat(auto-fit, minmax(258px, 1fr)); gap: 18px; }

  .odp-info-card {
    padding: 18px 20px;
    border: 1px solid var(--file-line);
    border-radius: 14px;
    background: linear-gradient(135deg, #f8f5ff, #fff4ef);
  }

  .odp-info-card--warn { border-left: 4px solid var(--file-orange); background: #fff3ee; }
  .odp-info-card--good { border-left: 4px solid #2faa6e; background: #f0faf4; }

  .odp-info-card h3 { margin: 0 0 8px; color: var(--file-blue); font-size: 14px; font-weight: 700; }
  .odp-info-card p { margin: 0; color: #5b5b6b; font-size: 13px; line-height: 1.65; }
  .odp-info-card ul { margin: 0; padding-left: 18px; display: grid; gap: 6px; }
  .odp-info-card li { color: #5b5b6b; font-size: 12.5px; line-height: 1.55; }

  /* Gradient callout (action box) */
  .odp-callout {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 14px;
    align-items: start;
    padding: 16px 20px;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--file-blue), var(--file-orange));
    color: #fff;
  }

  .odp-callout svg { width: 22px; height: 22px; margin-top: 2px; }
  .odp-callout strong {
    display: block;
    margin-bottom: 5px;
    color: #ffe0b2;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1px;
    text-transform: uppercase;
  }
  .odp-callout p { margin: 0; font-size: 13px; line-height: 1.65; color: rgba(255, 255, 255, 0.95); }

  /* Why-pitch gradient panel with two columns */
  .odp-pitch {
    padding: 24px 26px;
    border-radius: 20px;
    background: linear-gradient(135deg, var(--file-blue), var(--file-orange));
    color: #fff;
  }
  .odp-pitch h2 { margin: 0 0 10px; font-size: 18px; font-weight: 700; line-height: 1.35; }
  .odp-pitch > p { margin: 0 0 18px; font-size: 13px; line-height: 1.7; color: rgba(255, 255, 255, 0.92); }
  .odp-pitch-cols { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
  .odp-pitch-col h3 { margin: 0 0 8px; color: #ffe0b2; font-size: 11px; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase; }
  .odp-pitch-col p { margin: 0 0 5px; font-size: 12.5px; color: rgba(255, 255, 255, 0.92); line-height: 1.5; }

  /* Table */
  .odp-table-wrap { overflow-x: auto; border-radius: 14px; border: 1px solid var(--file-line); }
  .odp-table { width: 100%; border-collapse: collapse; min-width: 520px; }
  .odp-table thead th {
    background: linear-gradient(135deg, var(--file-blue), #5540d8);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.4px;
    text-align: left;
    padding: 11px 14px;
  }
  .odp-table tbody td { padding: 11px 14px; font-size: 12.5px; color: #555; border-bottom: 1px solid #eee; line-height: 1.5; }
  .odp-table tbody tr:nth-child(even) { background: #f8f5ff; }
  .odp-table tbody tr:last-child td { border-bottom: 0; }
  .odp-table .odp-td-key { color: var(--file-blue); font-weight: 700; }
  .odp-table .odp-td-good { color: #0f6e56; font-weight: 600; }
  .odp-table .odp-td-warn { color: #b53d12; font-weight: 600; }

  /* Numbered talking points */
  .odp-talk { display: grid; gap: 14px; }
  .odp-talk-item { display: grid; grid-template-columns: 38px minmax(0, 1fr); gap: 14px; align-items: start; }
  .odp-talk-num {
    display: inline-flex;
    width: 34px;
    height: 34px;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--file-blue), var(--file-orange));
    color: #fff;
    font-size: 14px;
    font-weight: 700;
  }
  .odp-talk-item p { margin: 0; color: #444; font-size: 13.5px; line-height: 1.7; }

  /* Timeline */
  .odp-timeline { display: grid; }
  .odp-tl-row { display: grid; grid-template-columns: 96px minmax(0, 1fr); gap: 16px; padding: 11px 0; border-bottom: 1px solid var(--file-line); }
  .odp-tl-row:last-child { border-bottom: 0; }
  .odp-tl-date { color: var(--file-orange); font-weight: 800; font-size: 13px; }
  .odp-tl-row p { margin: 0; color: #555; font-size: 13px; line-height: 1.55; }

  /* Pathway-map cards / small country cards */
  .odp-pathmap { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 14px; }
  .odp-cc-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; }
  .odp-cc {
    padding: 18px 14px;
    border-radius: 16px;
    text-align: center;
    background: linear-gradient(135deg, var(--file-blue), #3a23b8);
    color: #fff;
  }
  .odp-cc-emoji { font-size: 24px; line-height: 1; }
  .odp-cc h3 { margin: 8px 0 4px; color: #ffd9a8; font-size: 13px; font-weight: 700; }
  .odp-cc-price { margin: 0 0 6px; font-size: 11px; color: rgba(255, 255, 255, 0.6); }
  .odp-cc p { margin: 0; font-size: 11px; line-height: 1.5; color: rgba(255, 255, 255, 0.78); }

  /* Country headline banner (NZ) */
  .odp-country-banner {
    display: flex;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
    padding: 18px 22px;
    border-radius: 18px;
    margin-bottom: 32px;
    background: linear-gradient(135deg, var(--file-orange), #f4a15a);
    color: #fff;
  }
  .odp-country-banner img { width: 120px; height: auto; border-radius: 8px; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2); }
  .odp-cb-body { flex: 1; min-width: 240px; }
  .odp-cb-kicker { margin: 0 0 4px; font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; color: #fde8d0; }
  .odp-country-banner h2 { margin: 0; font-size: 18px; font-weight: 700; line-height: 1.4; }

  /* Sources */
  .odp-sources { display: grid; gap: 7px; }
  .odp-sources a { color: var(--file-orange); font-size: 12px; text-decoration: underline; line-height: 1.5; }

  /* Tip / quote */
  .odp-tip { border-left: 4px solid var(--file-orange); padding: 4px 0 4px 16px; }
  .odp-tip-kicker { margin: 0 0 6px; color: var(--file-blue); font-size: 11px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; }
  .odp-tip p { margin: 0 0 6px; color: #555; font-size: 13.5px; line-height: 1.65; }
  .odp-tip p.is-quote { font-style: italic; }

  /* CTA band */
  .odp-cta-band {
    margin-top: 8px;
    padding: 30px;
    border-radius: 24px;
    text-align: center;
    background: linear-gradient(135deg, var(--file-blue), var(--file-orange));
    color: #fff;
  }
  .odp-cta-band h2 { margin: 0 0 8px; font-size: 22px; font-weight: 700; }
  .odp-cta-band p { margin: 0 auto 18px; max-width: 60ch; font-size: 14px; line-height: 1.6; color: rgba(255, 255, 255, 0.9); }
  .odp-cta-band .odp-web-btn-secondary { background: #fff; border-color: #fff; }

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

    .odp-surface-pad { padding: 22px 18px; }

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
