{{-- Home · Hero → Student Hub drawer ("Important Links").
     Renders inside <section class="hero"> and is pinned to the hero's right
     edge: collapsed only the vertical handle shows, open the panel slides in
     over the hero's empty right column. Replaces the standalone .quick-links
     band that used to sit between the signal strip and the Method compass.

     Icons intentionally match the Student Hub nav dropdown cards (see
     partials/header-stripe.blade.php) so the two read as the same set of
     destinations. Add or remove an entry here AND in the dropdown to keep them
     in step. Deliberately carries no data-ed hooks — it is fixed chrome, not
     part of the CMS-editable hero, so the live editor leaves it alone. --}}
@php
    $hubLinks = [
        ['label' => 'Test Preparation',     'icon' => 'book-open-check', 'url' => route('services.test-prep'), 'note' => 'IELTS, TOEFL, SAT, GRE & GMAT'],
        ['label' => 'Loan & Acco',          'icon' => 'wallet',          'url' => route('loan-acco.index'),    'note' => 'Education loans & housing'],
        ['label' => 'Visa',                 'icon' => 'stamp',           'url' => route('visa'),               'note' => 'Eligibility & expert guidance'],
        ['label' => 'Visa Mock Interview',  'icon' => 'video',           'url' => route('visa-mock'),          'note' => 'AI practice with feedback'],
        ['label' => 'Statement of Purpose', 'icon' => 'feather',         'url' => route('sop.index'),          'note' => 'SOPs, LORs, resumes & essays'],
    ];
@endphp

<aside class="hero-hub" data-hero-hub aria-labelledby="hero-hub-title">
  {{-- The handle slides left with the panel, so it doubles as the close
       control — no separate close button. --}}
  <button class="hero-hub-handle" type="button" data-hero-hub-toggle
          aria-expanded="false" aria-controls="hero-hub-panel">
    <span class="hero-hub-handle-icon" aria-hidden="true"><i data-lucide="layout-grid"></i></span>
    <span class="hero-hub-handle-label">Student Hub</span>
    <span class="hero-hub-handle-caret" aria-hidden="true"><i data-lucide="chevron-left"></i></span>
  </button>

  <div class="hero-hub-panel" id="hero-hub-panel">
    <div class="hero-hub-head">
      <span class="hero-hub-eyebrow">Student Hub</span>
      <h2 class="hero-hub-title" id="hero-hub-title">Important Links</h2>
      <p>Quick access to the tools and services students reach for most.</p>
    </div>

    <div class="hero-hub-list">
      @foreach ($hubLinks as $link)
        <a class="hero-hub-link" href="{{ $link['url'] }}">
          <span class="hero-hub-link-icon" aria-hidden="true"><i data-lucide="{{ $link['icon'] }}"></i></span>
          <span class="hero-hub-link-text">
            <strong>{{ $link['label'] }}</strong>
            <small>{{ $link['note'] }}</small>
          </span>
          <span class="hero-hub-link-arrow" aria-hidden="true"><i data-lucide="arrow-right"></i></span>
        </a>
      @endforeach
    </div>
  </div>
</aside>
