{{-- Important Links — a compact quick-link row to every tool in the Student Hub
     nav dropdown (see partials/header-stripe.blade.php). Icons intentionally
     match the dropdown cards so the two read as the same set of destinations.
     Add or remove an entry here AND in the dropdown to keep them in step. --}}
@php
    $quickLinks = [
        ['label' => 'Test Preparation',     'icon' => 'book-open-check', 'url' => route('services.test-prep'), 'note' => 'IELTS, TOEFL, SAT, GRE & GMAT'],
        ['label' => 'Loan & Acco',          'icon' => 'wallet',          'url' => route('loan-acco.index'),    'note' => 'Education loans & housing'],
        ['label' => 'Visa',                 'icon' => 'stamp',           'url' => route('visa'),               'note' => 'Eligibility & expert guidance'],
        ['label' => 'Visa Mock Interview',  'icon' => 'video',           'url' => route('visa-mock'),          'note' => 'AI practice with feedback'],
        ['label' => 'Statement of Purpose', 'icon' => 'feather',         'url' => route('sop.index'),          'note' => 'SOPs, LORs, resumes & essays'],
    ];
@endphp

<section class="quick-links" aria-labelledby="quick-links-title">
  <div class="container">
    <div class="section-lead centered reveal">
      <span class="eyebrow">Student Hub</span>
      <h2 id="quick-links-title">Important Links</h2>
      <span class="ql-divider" aria-hidden="true"><i></i></span>
      <p>Quick access to the tools and services students reach for most.</p>
    </div>

    <div class="ql-grid reveal">
      @foreach ($quickLinks as $link)
        <a class="ql-card" href="{{ $link['url'] }}">
          <span class="ql-card__icon" aria-hidden="true"><i data-lucide="{{ $link['icon'] }}"></i></span>
          <strong>{{ $link['label'] }}</strong>
          <small>{{ $link['note'] }}</small>
        </a>
      @endforeach
    </div>
  </div>
</section>
