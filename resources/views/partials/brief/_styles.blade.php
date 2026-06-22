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
    max-width: 1240px;
    margin: 0 auto;
  }

  .odp-web-hero {
    padding: clamp(62px, 7vw, 96px) 0 clamp(46px, 6vw, 72px);
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
    text-align: left;
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
    text-align: left;
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
    border: 1px solid rgba(43, 31, 168, 0.08);
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
    border: 1px solid rgba(43, 31, 168, 0.08);
    border-radius: 28px;
  }

  .odp-journey {
    width: 100%;
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
    gap: 20px;
    align-items: stretch;
  }

  .odp-journey--balanced .odp-journey-steps {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .odp-step {
    display: flex;
    flex-direction: column;
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
    text-align: left;
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
    text-align: left;
  }

  .odp-si-desc {
    margin-top: 2px;
    color: #888;
    font-size: 12px;
    line-height: 1.6;
    text-align: left;
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

  .odp-journey--balanced .odp-final-step {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: 58px minmax(220px, 0.8fr) minmax(0, 1.2fr);
    gap: 20px;
    align-items: center;
    padding: 22px 28px;
    text-align: left;
  }

  .odp-journey--balanced .odp-final-step .plane {
    margin: 0;
  }

  .odp-journey--balanced .odp-final-step h3,
  .odp-journey--balanced .odp-final-step p {
    margin: 0;
    text-align: left;
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
    text-align: center;
  }

  .odp-plans {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 22px;
    align-items: stretch;
  }

  .odp-file-plan {
    position: relative;
    display: flex;
    overflow: hidden;
    min-height: 100%;
    flex-direction: column;
    padding: 32px;
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
    box-shadow: 0 18px 44px rgba(240, 90, 40, 0.14);
  }

  .odp-file-plan.achiever:hover {
    transform: translateY(-6px);
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
    text-align: left;
  }

  .odp-plan-list {
    flex: 1;
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
    margin-top: auto;
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
    text-align: left;
  }

  .odp-disclaimer-list li:last-child {
    border-bottom: 0;
  }

  .odp-disclaimer-list {
    columns: 2;
    column-gap: 34px;
  }

  @media (max-width: 980px) {
    .odp-journey--balanced .odp-journey-steps {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  /* ============================================================
     GRID — rows & columns (page builder layout)
     ============================================================ */
  .odp-row { display: grid; grid-template-columns: repeat(12, 1fr); gap: 22px; align-items: stretch; margin-bottom: 8px; }
  /* Full-bleed row (used by AI landing sections): breaks out of the centered
     container and the page side-padding to span the entire viewport. */
  .odp-row--full { width: 100vw; margin-left: calc(50% - 50vw); margin-bottom: 0; }
  .odp-col { grid-column: span var(--span, 12); min-width: 0; display: flex; flex-direction: column; }
  .odp-col > * { margin-bottom: 0; }
  .odp-col > * + * { margin-top: 18px; }
  /* A column that holds a card stretches the card to fill the row height. */
  .odp-col > .odp-card { flex: 1; }
  @media (max-width: 760px) { .odp-col { grid-column: span 12; } }

  /* ── Single Card block ── */
  .odp-card { display: flex; flex-direction: column; border: 1px solid var(--file-line); border-radius: 18px;
    background: #fff; box-shadow: 0 10px 30px rgba(40, 33, 22, 0.07); overflow: hidden; }
  .odp-card--tile { background: linear-gradient(135deg, var(--file-blue), #3a23b8); border-color: transparent; color: #fff; }
  .odp-card--outline { background: transparent; box-shadow: none; }
  .odp-card-img { aspect-ratio: 16 / 10; overflow: hidden; }
  .odp-card-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
  .odp-card-body { padding: 22px 22px 24px; display: flex; flex-direction: column; gap: 9px; flex: 1; }
  .odp-card-ic { display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px;
    border-radius: 14px; background: rgba(240, 90, 40, 0.12); color: var(--file-orange); font-size: 24px; margin-bottom: 4px; }
  .odp-card--tile .odp-card-ic { background: rgba(255, 255, 255, 0.16); color: #ffd9a8; }
  .odp-card-ic i { width: 24px; height: 24px; }
  .odp-card-eyebrow { font-size: 11px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; color: var(--file-orange); }
  .odp-card--tile .odp-card-eyebrow { color: #ffd9a8; }
  .odp-card-title { margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--file-blue); line-height: 1.25; }
  .odp-card--tile .odp-card-title { color: #fff; }
  .odp-card-text { margin: 0; color: #5b5b6b; font-size: 0.95rem; line-height: 1.65; }
  .odp-card--tile .odp-card-text { color: rgba(255, 255, 255, 0.82); }
  .odp-card-btn { display: inline-flex; align-items: center; gap: 7px; margin-top: auto; padding-top: 6px;
    color: var(--file-orange); font-weight: 800; font-size: 0.9rem; text-decoration: none; }
  .odp-card--tile .odp-card-btn { color: #ffd9a8; }
  .odp-card-btn i { width: 16px; height: 16px; }

  /* ── Text block ── */
  .odp-text { color: #4a4a59; font-size: 1rem; line-height: 1.75; }
  .odp-text--center { text-align: center; }
  .odp-text--right { text-align: right; }
  .odp-text--lg { font-size: 1.18rem; }
  .odp-text--sm { font-size: 0.88rem; }
  .odp-text :is(h1, h2, h3) { color: var(--file-blue); }
  .odp-text a { color: var(--file-orange); }

  /* ── Button block — restylable / reshapable / resizable CTA ──
     Colours follow the block's accent vars: accent → --file-orange,
     secondary → --file-blue, so the CMS colour pickers recolour it. */
  .odp-btnblk { display: flex; }
  .odp-btnblk--center { justify-content: center; }
  .odp-btnblk--right { justify-content: flex-end; }

  .odp-btn { display: inline-flex; align-items: center; justify-content: center; gap: 9px;
    padding: 14px 26px; font-family: "Poppins", sans-serif; font-size: 0.95rem; font-weight: 700;
    line-height: 1; text-decoration: none; cursor: pointer;
    transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease; }
  .odp-btn:hover { transform: translateY(-2px); }
  .odp-btn i, .odp-btn svg { width: 18px; height: 18px; }

  .odp-btn--sm { padding: 10px 17px; font-size: 0.82rem; }
  .odp-btn--sm i, .odp-btn--sm svg { width: 15px; height: 15px; }
  .odp-btn--lg { padding: 18px 36px; font-size: 1.08rem; }
  .odp-btn--lg i, .odp-btn--lg svg { width: 21px; height: 21px; }

  .odp-btn--shape-pill { border-radius: 999px; }
  .odp-btn--shape-rounded { border-radius: 12px; }
  .odp-btn--shape-square { border-radius: 0; }

  .odp-btn--gradient { background: linear-gradient(135deg, var(--file-blue), var(--file-orange));
    color: #fff; box-shadow: 0 14px 30px rgba(43, 31, 168, 0.25); }
  .odp-btn--gradient:hover { box-shadow: 0 18px 38px rgba(43, 31, 168, 0.32); }
  .odp-btn--solid { background: var(--file-orange); color: #fff; box-shadow: 0 12px 26px rgba(240, 90, 40, 0.28); }
  .odp-btn--outline { background: #fff; border: 2px solid var(--file-orange); color: var(--file-orange); }
  .odp-btn--outline:hover { background: var(--file-orange); color: #fff; }
  .odp-btn--ghost { background: none; color: var(--file-blue); font-weight: 800; padding-left: 4px; padding-right: 4px; }
  .odp-btn--ghost:hover { color: var(--file-orange); transform: none; text-decoration: underline; }

  .odp-btn--block { width: 100%; }

  /* ── Divider / spacer ── */
  .odp-divider { height: 0; border-top: 1px solid var(--file-line); margin: 6px 0; }
  .odp-divider--dashed { border-top-style: dashed; }
  .odp-divider--dots { border-top-style: dotted; border-top-width: 2px; }
  .odp-spacer { width: 100%; }

  /* ── AI / embedded code block ── */
  .odp-embed { width: 100%; }

  /* ============================================================
     BRIEF CONTENT — shared by NZ / Medicine / Wednesday pages
     ============================================================ */
  .odp-section { margin-bottom: 40px; }

  .odp-surface-pad { padding: 26px 30px; border-radius: 22px; margin-bottom: 28px; }

  /* Per-block background "surface" options (set by the CMS appearance controls) */
  .odp-blk--tint {
    padding: 24px 28px;
    border-radius: 20px;
    background: linear-gradient(135deg, #f3efff, #fff4ef);
    border: 1px solid var(--file-line);
  }
  .odp-blk--gradient {
    padding: 26px 30px;
    border-radius: 22px;
    background: linear-gradient(135deg, var(--file-blue), var(--file-orange));
    color: #fff;
  }
  .odp-blk--gradient .odp-section-label { color: rgba(255, 255, 255, 0.85); }
  .odp-blk--gradient .odp-section-label::before { background: #fff; }
  .odp-blk--gradient .odp-block-title,
  .odp-blk--gradient .odp-block-sub { color: #fff; }

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

  /* ============================================================
     SECURE PAYMENT — reusable Razorpay + admissions OTP block
     ============================================================ */
  .odp-payment {
    scroll-margin-top: 96px;
    display: grid;
    grid-template-columns: minmax(0, 0.86fr) minmax(460px, 1.14fr);
    gap: clamp(28px, 5vw, 66px);
    align-items: center;
    margin: 44px 0;
    padding: clamp(30px, 5vw, 58px);
    border: 1px solid rgba(43, 31, 168, 0.12);
    border-radius: 28px;
    background:
      radial-gradient(circle at 8% 10%, rgba(123, 47, 247, 0.09), transparent 32%),
      radial-gradient(circle at 92% 88%, rgba(240, 90, 40, 0.1), transparent 30%),
      #fff;
    box-shadow: 0 22px 54px rgba(38, 28, 93, 0.1);
  }

  .odp-payment.odp-blk--tint {
    background: linear-gradient(145deg, #f7f4ff, #fff5ef);
  }

  .odp-payment.odp-blk--gradient {
    background: linear-gradient(135deg, var(--file-blue), #3f28bd 58%, var(--file-orange));
  }

  .odp-payment--centered {
    grid-template-columns: 1fr;
    max-width: 880px;
    margin-inline: auto;
    text-align: center;
  }

  .odp-payment--compact {
    grid-template-columns: minmax(0, 0.72fr) minmax(420px, 1.28fr);
    padding: 30px;
  }

  /* Enrolment popup — the payment section is reparented to <body> and shown as a
     modal when a pricing CTA links to one of its options. Vars / box-sizing /
     font are redeclared here so it renders correctly outside .odp-file-page. */
  html.odp-pay-modal-open { overflow: hidden; }

  .odp-pay-modal {
    --file-blue: #2B1FA8;
    --file-orange: #F05A28;
    --file-line: #ede8ff;
    position: fixed;
    inset: 0;
    z-index: 2147483000;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: clamp(14px, 4vw, 48px);
    overflow-y: auto;
    font-family: "Poppins", sans-serif;
  }

  .odp-pay-modal[hidden] { display: none; }

  .odp-pay-modal *,
  .odp-pay-modal *::before,
  .odp-pay-modal *::after { box-sizing: border-box; }

  .odp-pay-modal__scrim {
    position: fixed;
    inset: 0;
    background: rgba(20, 14, 60, 0.55);
    -webkit-backdrop-filter: blur(4px);
    backdrop-filter: blur(4px);
  }

  .odp-pay-modal__shell {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 1040px;
    margin: auto;
    animation: odpPayModalIn 0.26s cubic-bezier(0.16, 1, 0.3, 1);
  }

  @keyframes odpPayModalIn {
    from { opacity: 0; transform: translateY(20px) scale(0.985); }
    to   { opacity: 1; transform: none; }
  }

  .odp-pay-modal .odp-payment {
    margin: 0;
    scroll-margin-top: 0;
  }

  .odp-pay-modal__close {
    position: absolute;
    top: 14px;
    right: 14px;
    z-index: 5;
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    border: 1px solid rgba(43, 31, 168, 0.16);
    border-radius: 999px;
    background: #fff;
    color: var(--file-blue);
    font-size: 26px;
    line-height: 1;
    cursor: pointer;
    box-shadow: 0 6px 18px rgba(38, 28, 93, 0.16);
    transition: background 0.15s ease, transform 0.15s ease;
  }

  .odp-pay-modal__close:hover { background: #f4f1ff; transform: scale(1.05); }

  /* Success confirmation popup (shown after payment is verified). */
  .odp-pay-result { align-items: center; }

  .odp-pay-result__card {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 440px;
    margin: auto;
    padding: clamp(28px, 5vw, 40px) clamp(24px, 4vw, 36px);
    border-radius: 24px;
    background: #fff;
    text-align: center;
    box-shadow: 0 26px 64px rgba(38, 28, 93, 0.26);
    animation: odpPayModalIn 0.26s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .odp-pay-result__badge {
    width: 76px;
    height: 76px;
    margin: 0 auto 18px;
    display: grid;
    place-items: center;
    border-radius: 999px;
    color: #fff;
    background: linear-gradient(135deg, #12b76a, #039855);
    box-shadow: 0 10px 26px rgba(5, 150, 105, 0.34);
  }

  .odp-pay-result__card h3 {
    margin: 0 0 10px;
    color: var(--file-blue);
    font-size: clamp(20px, 3vw, 24px);
    font-weight: 800;
  }

  .odp-pay-result__msg {
    margin: 0 0 6px;
    color: #4a4a5a;
    font-size: 15px;
    line-height: 1.55;
  }

  .odp-pay-result__id {
    margin: 0 0 22px;
    color: #8a8aa0;
    font-size: 13px;
    font-weight: 600;
    word-break: break-all;
  }

  .odp-pay-result__id[hidden] { display: none; }

  .odp-pay-result__done {
    appearance: none;
    border: 0;
    border-radius: 999px;
    padding: 13px 36px;
    background: var(--file-blue);
    color: #fff;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s ease, transform 0.15s ease;
  }

  .odp-pay-result__done:hover { background: #241a8d; transform: translateY(-1px); }

  .odp-payment-copy {
    min-width: 0;
  }

  .odp-payment-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 14px;
    color: var(--file-orange);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.5px;
    text-transform: uppercase;
  }

  .odp-payment-eyebrow::before {
    content: "";
    width: 24px;
    height: 2px;
    border-radius: 999px;
    background: currentColor;
  }

  .odp-payment-copy h2 {
    margin: 0;
    color: var(--file-blue);
    font-size: clamp(2rem, 4vw, 3.45rem);
    font-weight: 800;
    line-height: 1.06;
    letter-spacing: -0.035em;
    text-align: left;
  }

  .odp-payment-copy > p {
    margin: 18px 0 0;
    color: #626276;
    font-size: 15px;
    line-height: 1.75;
    text-align: left;
  }

  .odp-payment--centered .odp-payment-copy h2,
  .odp-payment--centered .odp-payment-copy > p {
    max-width: 680px;
    margin-inline: auto;
    text-align: center;
  }

  .odp-payment-trust {
    display: grid;
    gap: 10px;
    margin-top: 24px;
  }

  .odp-payment-trust span {
    display: flex;
    gap: 10px;
    align-items: center;
    color: #4e4d65;
    font-size: 12px;
    font-weight: 700;
    text-align: left;
  }

  .odp-payment-trust svg {
    width: 30px;
    height: 30px;
    flex: 0 0 30px;
    padding: 7px;
    border-radius: 10px;
    background: rgba(240, 90, 40, 0.1);
    color: var(--file-orange);
  }

  .odp-payment--centered .odp-payment-trust {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .odp-payment-card {
    min-width: 0;
    padding: clamp(20px, 3vw, 30px);
    border: 1px solid rgba(43, 31, 168, 0.12);
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.94);
    box-shadow: 0 16px 36px rgba(28, 22, 72, 0.1);
  }

  .odp-payment-options {
    min-width: 0;
    margin: 0;
    padding: 0;
    border: 0;
  }

  .odp-payment-options legend {
    margin-bottom: 11px;
    color: #26213e;
    font-size: 12px;
    font-weight: 800;
  }

  .odp-payment-option-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(145px, 1fr));
    gap: 10px;
  }

  .odp-payment-option {
    position: relative;
    display: block;
    min-width: 0;
    cursor: pointer;
  }

  .odp-payment-option input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
  }

  .odp-payment-option-body {
    display: flex;
    min-height: 132px;
    flex-direction: column;
    gap: 7px;
    padding: 15px;
    border: 1.5px solid #e7e2f7;
    border-radius: 15px;
    background: #faf9ff;
    text-align: left;
    transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease, background 160ms ease;
  }

  .odp-payment-option input:checked + .odp-payment-option-body {
    border-color: var(--file-orange);
    background: #fff7f2;
    box-shadow: 0 10px 22px rgba(240, 90, 40, 0.12);
    transform: translateY(-2px);
  }

  .odp-payment-option input:focus-visible + .odp-payment-option-body {
    outline: 3px solid rgba(43, 31, 168, 0.2);
    outline-offset: 2px;
  }

  .odp-payment-option-top {
    display: flex;
    gap: 8px;
    align-items: flex-start;
    justify-content: space-between;
  }

  .odp-payment-option-top strong {
    color: var(--file-blue);
    font-size: 13px;
  }

  .odp-payment-option-top em {
    padding: 3px 6px;
    border-radius: 999px;
    background: var(--file-orange);
    color: #fff;
    font-size: 8px;
    font-style: normal;
    font-weight: 800;
    line-height: 1.2;
    text-transform: uppercase;
  }

  .odp-payment-amount {
    color: #1f1a38;
    font-size: clamp(1.25rem, 2.4vw, 1.75rem);
    font-weight: 800;
    line-height: 1.15;
  }

  .odp-payment-option small {
    margin-top: auto;
    color: #747187;
    font-size: 10px;
    line-height: 1.45;
  }

  .odp-payment-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 18px;
  }

  .odp-payment-fields label:last-child {
    grid-column: 1 / -1;
  }

  .odp-payment-fields label {
    display: grid;
    gap: 6px;
    color: #4d4a62;
    font-size: 11px;
    font-weight: 800;
    text-align: left;
  }

  .odp-payment-fields input {
    width: 100%;
    min-height: 45px;
    padding: 0 13px;
    border: 1px solid #ded9ef;
    border-radius: 11px;
    background: #fff;
    color: #211d36;
    font: 500 14px/1.2 "Poppins", sans-serif;
    outline: none;
  }

  .odp-payment-fields input:focus {
    border-color: var(--file-blue);
    box-shadow: 0 0 0 3px rgba(43, 31, 168, 0.1);
  }

  .odp-payment-action {
    display: inline-flex;
    min-height: 48px;
    align-items: center;
    justify-content: center;
    gap: 9px;
    border: 0;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--file-blue), var(--file-orange));
    color: #fff;
    font: 800 13px/1 "Poppins", sans-serif;
    cursor: pointer;
    box-shadow: 0 13px 28px rgba(43, 31, 168, 0.2);
    transition: transform 160ms ease, opacity 160ms ease;
  }

  .odp-payment-action {
    width: 100%;
    margin-top: 15px;
  }

  .odp-payment-action svg {
    width: 17px;
    height: 17px;
  }

  .odp-payment-action:hover {
    transform: translateY(-2px);
  }

  .odp-payment-action:disabled {
    cursor: not-allowed;
    opacity: 0.56;
    transform: none;
  }

  .odp-payment-status {
    min-height: 20px;
    margin: 12px 0 0;
    color: #66627a;
    font-size: 11px;
    line-height: 1.5;
    text-align: center;
  }

  .odp-payment-status.is-error { color: #b42318; }
  .odp-payment-status.is-success { color: #087a55; }

  .odp-payment-note {
    display: flex;
    gap: 8px;
    align-items: flex-start;
    margin: 14px 0 0;
    color: #777386;
    font-size: 10px;
    line-height: 1.55;
    text-align: left;
  }

  .odp-payment-note svg {
    width: 15px;
    height: 15px;
    flex: 0 0 15px;
    margin-top: 1px;
    color: var(--file-orange);
  }

  .odp-payment-empty {
    display: grid;
    justify-items: center;
    gap: 10px;
    padding: 28px;
    color: #726e82;
    font-size: 12px;
    line-height: 1.5;
    text-align: center;
  }

  .odp-payment-empty svg {
    width: 28px;
    height: 28px;
    color: var(--file-orange);
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

    .odp-journey--balanced .odp-journey-steps {
      grid-template-columns: 1fr;
    }

    .odp-journey--balanced .odp-final-step {
      grid-template-columns: 1fr;
      gap: 8px;
      padding: 26px 22px;
      text-align: center;
    }

    .odp-journey--balanced .odp-final-step h3,
    .odp-journey--balanced .odp-final-step p {
      text-align: center;
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

    .odp-payment,
    .odp-payment--compact {
      grid-template-columns: 1fr;
      gap: 26px;
      margin: 32px 0;
      padding: 24px 18px;
      border-radius: 22px;
    }

    .odp-payment-copy h2,
    .odp-payment-copy > p {
      text-align: left;
    }

    .odp-payment--centered .odp-payment-trust,
    .odp-payment-trust {
      grid-template-columns: 1fr;
    }

    .odp-payment-card {
      padding: 18px 14px;
    }

    .odp-payment-option-grid,
    .odp-payment-fields {
      grid-template-columns: 1fr;
    }

    .odp-payment-fields label:last-child {
      grid-column: auto;
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
