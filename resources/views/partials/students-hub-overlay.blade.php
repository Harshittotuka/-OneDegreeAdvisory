{{-- "Coming soon" overlay for the navbar's Students-Hub triggers
     ([data-students-hub-trigger]). Shared by the main site layout
     (layouts.app, driven by public/script.js) and the Trending Career layout
     (career-library.layout, driven by its own inline handler). Styles live in
     public/styles.css (.students-hub-*). --}}
<div class="students-hub-overlay" id="students-hub-coming-soon" data-students-hub-overlay role="dialog" aria-modal="true" aria-labelledby="students-hub-title" aria-describedby="students-hub-desc" aria-hidden="true" hidden>
  <div class="students-hub-backdrop" data-students-hub-close></div>
  <div class="students-hub-dialog" role="document">
    <button class="students-hub-close" type="button" data-students-hub-close aria-label="Close Students Hub preview">
      <i data-lucide="x" aria-hidden="true"></i>
    </button>

    <div class="students-hub-ai-mark" aria-hidden="true">
      <span class="students-hub-chip"><i data-lucide="bot"></i></span>
    </div>

    <div class="students-hub-copy">
      <span class="students-hub-kicker">
        <i data-lucide="sparkles" aria-hidden="true"></i>
        AI-powered student tools
      </span>
      <h2 id="students-hub-title">Students Hub is coming soon</h2>
      <span class="students-hub-flourish" aria-hidden="true"></span>
      <p id="students-hub-desc">A smarter space for profile insights, best-fit university shortlists, application planning, and progress tracking.</p>

      <div class="students-hub-features" aria-label="Students Hub preview features">
        <span><i data-lucide="brain" aria-hidden="true"></i> Profile intelligence</span>
        <span><i data-lucide="target" aria-hidden="true"></i> Best-fit shortlists</span>
        <span><i data-lucide="list-checks" aria-hidden="true"></i> Application copilot</span>
      </div>
    </div>
  </div>
</div>
