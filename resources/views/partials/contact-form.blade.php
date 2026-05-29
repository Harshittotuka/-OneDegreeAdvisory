<form data-consult-form class="contact-form">
  <div class="contact-form-row">
    <label class="contact-field" for="{{ ($formId ?? 'contact').'-name' }}">
      <span>Full name *</span>
      <input id="{{ ($formId ?? 'contact').'-name' }}" name="name" type="text" required placeholder="e.g. Aanya Mehta">
    </label>
    <label class="contact-field" for="{{ ($formId ?? 'contact').'-email' }}">
      <span>Email address *</span>
      <input id="{{ ($formId ?? 'contact').'-email' }}" name="email" type="email" required placeholder="you@example.com">
    </label>
  </div>

  <div class="contact-form-row">
    <label class="contact-field" for="{{ ($formId ?? 'contact').'-phone' }}">
      <span>Mobile number *</span>
      <input id="{{ ($formId ?? 'contact').'-phone' }}" name="phone" type="tel" required placeholder="+91 98xxxxxxxx">
    </label>
    <label class="contact-field" for="{{ ($formId ?? 'contact').'-city' }}">
      <span>City</span>
      <input id="{{ ($formId ?? 'contact').'-city' }}" name="city" type="text" placeholder="e.g. Bengaluru">
    </label>
  </div>

  <div class="contact-form-row">
    <label class="contact-field" for="{{ ($formId ?? 'contact').'-destination' }}">
      <span>Preferred destination</span>
      <select id="{{ ($formId ?? 'contact').'-destination' }}" name="destination">
        <option>Not sure yet</option>
        <option>United States</option>
        <option>United Kingdom</option>
        <option>Canada</option>
        <option>Australia</option>
        <option>Germany</option>
        <option>Singapore</option>
        <option>Multiple countries</option>
      </select>
    </label>
    <label class="contact-field" for="{{ ($formId ?? 'contact').'-level' }}">
      <span>Current academic level *</span>
      <select id="{{ ($formId ?? 'contact').'-level' }}" name="level" required>
        <option value="">Choose one...</option>
        <option>Grade 9&ndash;10</option>
        <option>Grade 11&ndash;12</option>
        <option>Undergraduate (1&ndash;4 yr)</option>
        <option>Graduate</option>
        <option>Working professional</option>
      </select>
    </label>
  </div>

  <label class="contact-field contact-field-full" for="{{ ($formId ?? 'contact').'-message' }}">
    <span>Tell us about your dream</span>
    <textarea id="{{ ($formId ?? 'contact').'-message' }}" name="message" placeholder="What programs or universities are you considering? What's your timeline?"></textarea>
  </label>

  <label class="contact-consent">
    <input type="checkbox" name="consent" checked>
    <span>I agree to receive communications from One Degree Advisory including (but not limited to) WhatsApp, SMS, RCS and email about my admissions journey.</span>
  </label>

  <button class="btn btn-primary" type="submit">
    <span>Request my free profile review</span>
    <i data-lucide="arrow-up-right"></i>
  </button>
  <p class="contact-privacy">By submitting, you agree to our <a href="#">Privacy Policy</a>. We never share your data.</p>
  <p class="form-status" role="status" aria-live="polite" data-form-status></p>
</form>
