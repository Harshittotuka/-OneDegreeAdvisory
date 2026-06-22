@php
    $blkStyle = $blkStyle ?? '';
    $blkSurface = $blkSurface ?? '';
    $pageSlug = trim((string) ($pageSlug ?? ''));
    $blockId = trim((string) ($blockId ?? ''));
    $cmsPreview = (bool) ($cmsPreview ?? false) || $pageSlug === '' || $blockId === '';
    $layout = in_array(($data['layout'] ?? ''), ['split', 'centered', 'compact'], true) ? $data['layout'] : 'split';
    $rawOptions = array_values(array_filter($data['options'] ?? [], fn ($option) => is_array($option) && trim((string) ($option['label'] ?? '')) !== ''));
    $options = [];
    foreach ($rawOptions as $optionIndex => $option) {
        $paise = \App\Services\PaymentBlockResolver::rupeesToPaise((string) ($option['amount'] ?? ''));
        if ($paise === null && ! $cmsPreview) {
            continue;
        }
        $options[] = $option + ['_paise' => $paise, '_index' => $optionIndex];
    }
    $formatAmount = static function (?int $paise): string {
        if ($paise === null) {
            return 'Set amount';
        }
        $formatted = number_format($paise / 100, 2);
        return '₹'.rtrim(rtrim($formatted, '0'), '.');
    };
@endphp

<section class="odp-payment odp-payment--{{ $layout }} {{ $blkSurface }}"
         id="{{ $blockId }}"
         @if($blkStyle) style="{{ $blkStyle }}" @endif
         data-oda-payment
         data-page-slug="{{ $pageSlug }}"
         data-block-id="{{ $blockId }}">
  <div class="odp-payment-copy">
    @if(!empty($data['eyebrow']))<span class="odp-payment-eyebrow">{{ $data['eyebrow'] }}</span>@endif
    @if(!empty($data['title']))<h2>{{ $data['title'] }}</h2>@endif
    @if(!empty($data['description']))<p>{{ $data['description'] }}</p>@endif
    <div class="odp-payment-trust">
      <span><i data-lucide="lock-keyhole"></i> Razorpay secure checkout</span>
      <span><i data-lucide="badge-indian-rupee"></i> Server-verified amount</span>
      <span><i data-lucide="zap"></i> Instant online payment</span>
    </div>
  </div>

  <div class="odp-payment-card">
    @if(count($options))
      <fieldset class="odp-payment-options">
        <legend>Select payment option</legend>
        <div class="odp-payment-option-grid">
          @foreach($options as $displayIndex => $option)
            <label class="odp-payment-option" id="{{ $blockId }}-option-{{ $option['_index'] }}">
              <input type="radio" name="oda-payment-option-{{ $blockId ?: 'preview' }}" value="{{ $option['_index'] }}" @checked($displayIndex === 0)>
              <span class="odp-payment-option-body">
                <span class="odp-payment-option-top">
                  <strong>{{ $option['label'] }}</strong>
                  @if(!empty($option['badge']))<em>{{ $option['badge'] }}</em>@endif
                </span>
                <span class="odp-payment-amount">{{ $formatAmount($option['_paise']) }}</span>
                @if(!empty($option['description']))<small>{{ $option['description'] }}</small>@endif
              </span>
            </label>
          @endforeach
        </div>
      </fieldset>

      <div class="odp-payment-fields">
        <label><span>Student name</span><input type="text" data-pay-name maxlength="160" autocomplete="name" required></label>
        <label><span>Email</span><input type="email" data-pay-email maxlength="190" autocomplete="email" required></label>
        <label><span>Phone</span><input type="tel" data-pay-phone maxlength="40" autocomplete="tel" placeholder="+91"></label>
      </div>

      <button class="odp-payment-action" type="button" data-pay-now @disabled($cmsPreview)>
        <i data-lucide="lock-keyhole"></i>
        <span>{{ trim((string) ($data['button_label'] ?? '')) ?: 'Pay securely' }}</span>
      </button>

      <p class="odp-payment-status" data-pay-status aria-live="polite">
        @if($cmsPreview) Preview mode — save and open the public page to test the secure flow. @endif
      </p>
    @else
      <div class="odp-payment-empty">
        <i data-lucide="settings-2"></i>
        <strong>Add at least one plan and valid INR amount in the payment block settings.</strong>
      </div>
    @endif

    @if(!empty($data['note']))<p class="odp-payment-note"><i data-lucide="info"></i><span>{{ $data['note'] }}</span></p>@endif
  </div>
</section>

@if(! $cmsPreview && count($options))
  @once
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
      (function () {
        'use strict';
        var orderUrl = @json(route('payments.order'));
        var confirmUrl = @json(route('payments.confirm'));

        function post(url, data) {
          var csrf = document.querySelector('meta[name="csrf-token"]');
          return fetch(url, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf ? csrf.content : ''
            },
            body: JSON.stringify(data)
          }).then(async function (response) {
            var json = await response.json().catch(function () { return {}; });
            if (!response.ok) {
              var validation = json.errors ? Object.values(json.errors).flat()[0] : '';
              throw new Error(validation || json.message || 'The request could not be completed.');
            }
            return json;
          });
        }

        function setStatus(root, message, tone) {
          var status = root.querySelector('[data-pay-status]');
          if (!status) return;
          status.textContent = message || '';
          status.classList.toggle('is-error', tone === 'error');
          status.classList.toggle('is-success', tone === 'success');
        }

        function busy(button, on, label) {
          if (!button) return;
          if (!button.dataset.originalHtml) button.dataset.originalHtml = button.innerHTML;
          button.disabled = on;
          if (label) button.textContent = label;
          else if (!on) button.innerHTML = button.dataset.originalHtml;
        }

        function validInput(input) {
          if (!input || input.checkValidity()) return true;
          input.reportValidity();
          return false;
        }

        function init(root) {
          if (root.dataset.paymentReady === '1') return;
          root.dataset.paymentReady = '1';
          var payButton = root.querySelector('[data-pay-now]');
          var nameInput = root.querySelector('[data-pay-name]');
          var emailInput = root.querySelector('[data-pay-email]');
          var phoneInput = root.querySelector('[data-pay-phone]');
          if (!payButton) return;

          payButton.addEventListener('click', function () {
            var selected = root.querySelector('.odp-payment-option input:checked');
            if (!selected || !validInput(nameInput) || !validInput(emailInput) || !validInput(phoneInput)) return;
            busy(payButton, true, 'Starting secure checkout…');
            setStatus(root, '', '');
            post(orderUrl, {
              page_slug: root.dataset.pageSlug,
              block_id: root.dataset.blockId,
              option_index: Number(selected.value),
              name: nameInput.value.trim(),
              email: emailInput.value.trim(),
              phone: phoneInput.value.trim()
            }).then(function (data) {
              if (!window.Razorpay || !data.checkout) throw new Error('Razorpay Checkout could not be loaded.');
              var token = data.token;
              var checkout = data.checkout;
              var razorpay = new window.Razorpay({
                key: checkout.key,
                amount: checkout.amount,
                currency: checkout.currency,
                order_id: checkout.order_id,
                name: checkout.name,
                description: checkout.description,
                prefill: checkout.prefill,
                theme: { color: checkout.theme_color },
                handler: function (response) {
                  setStatus(root, 'Verifying payment…', '');
                  post(confirmUrl, {
                    token: token,
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_signature: response.razorpay_signature
                  }).then(function (result) {
                    payButton.disabled = true;
                    setStatus(root, '', '');
                    closeModal();
                    showResult(result.message, result.payment_id);
                  }).catch(function (error) {
                    setStatus(root, error.message, 'error');
                  });
                },
                modal: {
                  ondismiss: function () { setStatus(root, 'Checkout closed before payment. You can try again.', ''); }
                }
              });
              razorpay.open();
            }).catch(function (error) {
              setStatus(root, error.message, 'error');
            }).finally(function () {
              busy(payButton, false);
            });
          });
        }

        var activeModal = null;

        function closeModal() {
          if (!activeModal) return;
          activeModal.setAttribute('hidden', '');
          document.documentElement.classList.remove('odp-pay-modal-open');
          activeModal = null;
        }

        function openModal(payment) {
          var overlay = payment.odpModalOverlay;
          if (!overlay) return;
          if (activeModal && activeModal !== overlay) closeModal();
          overlay.removeAttribute('hidden');
          document.documentElement.classList.add('odp-pay-modal-open');
          activeModal = overlay;
          overlay.scrollTop = 0;
          var field = payment.querySelector('.odp-payment-fields input');
          if (field) { try { field.focus({ preventScroll: true }); } catch (e) { field.focus(); } }
        }

        // Success confirmation popup, shown instead of an inline status line.
        var resultOverlay = null;

        function showResult(message, paymentId) {
          if (!resultOverlay) {
            resultOverlay = document.createElement('div');
            resultOverlay.className = 'odp-pay-modal odp-pay-result';
            resultOverlay.setAttribute('hidden', '');
            resultOverlay.innerHTML =
              '<div class="odp-pay-modal__scrim" data-pay-close></div>' +
              '<div class="odp-pay-result__card" role="dialog" aria-modal="true" aria-live="polite">' +
                '<div class="odp-pay-result__badge" aria-hidden="true">' +
                  '<svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>' +
                '</div>' +
                '<h3>Payment successful</h3>' +
                '<p class="odp-pay-result__msg"></p>' +
                '<p class="odp-pay-result__id"></p>' +
                '<button type="button" class="odp-pay-result__done" data-pay-close>Done</button>' +
              '</div>';
            document.body.appendChild(resultOverlay);
            resultOverlay.addEventListener('click', function (event) {
              if (event.target.closest('[data-pay-close]')) hideResult();
            });
          }
          resultOverlay.querySelector('.odp-pay-result__msg').textContent = message || 'Payment verified successfully.';
          var idEl = resultOverlay.querySelector('.odp-pay-result__id');
          if (paymentId) { idEl.textContent = 'Payment ID: ' + paymentId; idEl.hidden = false; }
          else idEl.hidden = true;
          resultOverlay.removeAttribute('hidden');
          document.documentElement.classList.add('odp-pay-modal-open');
        }

        function hideResult() {
          if (resultOverlay) resultOverlay.setAttribute('hidden', '');
          if (!activeModal) document.documentElement.classList.remove('odp-pay-modal-open');
        }

        // Turn an inline payment section into a popup, but ONLY when something on
        // the page (e.g. a pricing "Enrol" button) links to one of its options.
        // Otherwise the block stays inline exactly as before.
        function setupModal(payment) {
          var blockId = payment.dataset.blockId;
          if (!blockId || !document.querySelector('a[href*="#' + blockId + '-option-"]')) return;

          payment.dataset.payModal = '1';

          var overlay = document.createElement('div');
          overlay.className = 'odp-pay-modal';
          overlay.setAttribute('hidden', '');

          var scrim = document.createElement('div');
          scrim.className = 'odp-pay-modal__scrim';
          scrim.setAttribute('data-pay-close', '');

          var shell = document.createElement('div');
          shell.className = 'odp-pay-modal__shell';
          shell.setAttribute('role', 'dialog');
          shell.setAttribute('aria-modal', 'true');
          shell.setAttribute('aria-label', 'Secure online enrolment');

          var closeBtn = document.createElement('button');
          closeBtn.type = 'button';
          closeBtn.className = 'odp-pay-modal__close';
          closeBtn.setAttribute('aria-label', 'Close');
          closeBtn.setAttribute('data-pay-close', '');
          closeBtn.innerHTML = '&times;';

          if (payment.parentNode) payment.parentNode.removeChild(payment);
          shell.appendChild(closeBtn);
          shell.appendChild(payment);
          overlay.appendChild(scrim);
          overlay.appendChild(shell);
          document.body.appendChild(overlay);
          payment.odpModalOverlay = overlay;

          overlay.addEventListener('click', function (event) {
            if (event.target.closest('[data-pay-close]')) closeModal();
          });
        }

        function findOption(hash) {
          if (!hash || hash.charAt(0) !== '#') return null;
          var target = document.getElementById(hash.slice(1));
          return target && target.classList.contains('odp-payment-option') ? target : null;
        }

        function activateOption(target, smooth) {
          var radio = target.querySelector('input[type="radio"]');
          if (radio) radio.checked = true;
          var payment = target.closest('[data-oda-payment]');
          if (!payment) return;
          if (payment.dataset.payModal === '1') openModal(payment);
          else payment.scrollIntoView({ behavior: smooth ? 'smooth' : 'auto', block: 'start' });
        }

        function boot() {
          document.querySelectorAll('[data-oda-payment]').forEach(init);
          document.querySelectorAll('[data-oda-payment]').forEach(setupModal);

          document.addEventListener('click', function (event) {
            var link = event.target.closest('a[href^="#"]');
            if (!link) return;
            var target = findOption(link.getAttribute('href'));
            if (!target) return;
            event.preventDefault();
            activateOption(target, true);
          });
          document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            if (resultOverlay && !resultOverlay.hasAttribute('hidden')) hideResult();
            else closeModal();
          });
          window.addEventListener('hashchange', function () {
            var target = findOption(window.location.hash);
            if (target) activateOption(target, true);
          });
          // On first load only honour a hash for inline blocks, so the page never
          // pops a modal open unprompted.
          var initial = findOption(window.location.hash);
          if (initial) {
            var payment = initial.closest('[data-oda-payment]');
            if (payment && payment.dataset.payModal !== '1') activateOption(initial, false);
          }
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
        else boot();
      })();
    </script>
  @endonce
@endif
