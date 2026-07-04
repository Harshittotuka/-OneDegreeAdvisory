@once
@if($paymentEnabled)
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
@endif
<script>
(function () {
  'use strict';
  var root = document.querySelector('[data-tpc]');
  if (!root) return;

  var INR = new Intl.NumberFormat('en-IN');
  var paymentEnabled = @json((bool) $paymentEnabled);
  var orderUrl = @json(route('payments.order'));
  var confirmUrl = @json(route('payments.confirm'));

  /* ── Reveal on scroll ── */
  var reveals = root.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && reveals.length) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
    }, { threshold: 0.12 });
    reveals.forEach(function (el) { io.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add('in'); });
  }

  /* ── Bar-fill grow animation (matches the source design) ──
     The fills start at width:0; on a second frame we set each to its --w so the
     CSS width transition always runs — independent of the scroll-reveal, so the
     bars animate even when the section is already in view on load. */
  var barFills = root.querySelectorAll('.tpc-bars .tpc-bar-fill');
  if (barFills.length) {
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        barFills.forEach(function (el) { el.style.width = getComputedStyle(el).getPropertyValue('--w') || '0%'; });
      });
    });
  }

  /* ── Price / Duration toggle (bars + table variants) ── */
  var chips = root.querySelectorAll('.tpc-chip[data-metric]');
  if (chips.length) {
    var bars = root.querySelector('[data-tpc-bars]');
    var table = root.querySelector('[data-tpc-table]');

    function applyMetric(metric) {
      // Bars: re-sort, swap the printed value, then re-grow the fills.
      if (bars) {
        var maxP = Number(bars.dataset.maxPrice) || 1;
        var maxM = Number(bars.dataset.maxMonths) || 1;
        var rows = Array.prototype.slice.call(bars.querySelectorAll('.tpc-bar-row'));

        // 1. Reorder + update labels FIRST. Re-parenting a node (appendChild)
        //    cancels any in-flight transition on its children, so we must finish
        //    all DOM moves before touching widths.
        rows.sort(function (a, b) {
          return (Number(b.dataset[metric]) || 0) - (Number(a.dataset[metric]) || 0);
        }).forEach(function (row) {
          bars.appendChild(row);
          var out = row.querySelector('.tpc-bar-val');
          if (out) out.textContent = out.dataset[metric] || out.textContent;
        });

        // 2. Collapse fills to 0 now, then on the next frame set the new target
        //    width — a genuine 0 → value change so the width transition replays.
        rows.forEach(function (row) {
          var fill = row.querySelector('.tpc-bar-fill');
          if (fill) fill.style.width = '0%';
        });
        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            rows.forEach(function (row) {
              var price = Number(row.dataset.price) || 0;
              var months = Number(row.dataset.months) || 0;
              var val = metric === 'price' ? price / maxP : months / maxM;
              var w = (val * 100).toFixed(1) + '%';
              var fill = row.querySelector('.tpc-bar-fill');
              if (fill) { fill.style.setProperty('--w', w); fill.style.width = w; }
            });
          });
        });
      }
      // Table: sort rows by the chosen metric (desc).
      if (table) {
        var body = table.querySelector('tbody');
        if (body) {
          Array.prototype.slice.call(body.querySelectorAll('tr'))
            .sort(function (a, b) { return (Number(b.dataset[metric]) || 0) - (Number(a.dataset[metric]) || 0); })
            .forEach(function (tr) { body.appendChild(tr); });
        }
      }
    }

    chips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        chips.forEach(function (c) { c.classList.remove('is-active'); c.setAttribute('aria-selected', 'false'); });
        chip.classList.add('is-active'); chip.setAttribute('aria-selected', 'true');
        applyMetric(chip.dataset.metric);
      });
    });
  }

  /* ── Payment picker ── */
  var select = root.querySelector('[data-tpc-prog]');
  var amountValue = root.querySelector('[data-tpc-amount-value]');
  var amountBox = root.querySelector('[data-tpc-amount]');
  var nameInput = root.querySelector('[data-tpc-name]');
  var emailInput = root.querySelector('[data-tpc-email]');
  var phoneInput = root.querySelector('[data-tpc-phone]');
  var payBtn = root.querySelector('[data-tpc-pay]');
  var status = root.querySelector('[data-tpc-status]');
  // Boarding-pass ticket fields (mirror the picked program).
  var tkTrack = root.querySelector('[data-tpc-tk-track]');
  var tkFare = root.querySelector('[data-tpc-tk-fare]');
  var tkDur = root.querySelector('[data-tpc-tk-dur]');

  function selectedOption() { return select ? select.options[select.selectedIndex] : null; }

  function syncAmount() {
    var opt = selectedOption();
    if (!opt || !amountValue) return;
    var payable = opt.dataset.payable === '1';
    var price = Number(opt.dataset.price) || 0;
    amountValue.textContent = payable ? '₹' + INR.format(price) : 'On request';
    if (amountBox) amountBox.style.display = payable ? '' : 'none';
    if (payBtn) {
      // Non-payable program → the pay button becomes an enquiry CTA.
      payBtn.dataset.enquire = payable ? '' : '1';
    }
    // Keep the boarding-pass ticket in sync with the selection.
    if (tkTrack) tkTrack.textContent = opt.dataset.name || tkTrack.textContent;
    if (tkFare) tkFare.textContent = payable ? '₹' + INR.format(price) : 'On request';
    if (tkDur && opt.dataset.dur) tkDur.textContent = opt.dataset.dur;
    amountValue.classList.add('tpc-bump');
    setTimeout(function () { amountValue.classList.remove('tpc-bump'); }, 250);
  }
  if (select) { select.addEventListener('change', syncAmount); syncAmount(); }

  // "Enrol" buttons in the compare visualisations select that program and scroll
  // the picker into view (so the compare + payment stay in sync).
  root.querySelectorAll('[data-tpc-enrol]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var idx = btn.getAttribute('data-tpc-enrol');
      if (select) { select.value = idx; syncAmount(); }
      var pay = document.getElementById('tp-enrol');
      if (pay) pay.scrollIntoView({ behavior: 'smooth', block: 'center' });
      if (nameInput) setTimeout(function () { try { nameInput.focus({ preventScroll: true }); } catch (e) {} }, 400);
    });
  });

  function setStatus(msg, tone) {
    if (!status) return;
    status.textContent = msg || '';
    status.classList.toggle('is-error', tone === 'error');
    status.classList.toggle('is-success', tone === 'success');
  }

  function validInput(input) {
    if (!input || input.checkValidity()) return true;
    input.reportValidity();
    return false;
  }

  function post(url, data) {
    var csrf = document.querySelector('meta[name="csrf-token"]');
    return fetch(url, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf ? csrf.content : '' },
      body: JSON.stringify(data)
    }).then(async function (res) {
      var json = await res.json().catch(function () { return {}; });
      if (!res.ok) {
        var v = json.errors ? Object.values(json.errors).flat()[0] : '';
        throw new Error(v || json.message || 'The request could not be completed.');
      }
      return json;
    });
  }

  /* ── Success modal ── */
  var resultOverlay = null;
  function showResult(message, paymentId) {
    if (!resultOverlay) {
      resultOverlay = document.createElement('div');
      resultOverlay.className = 'tpc-result';
      resultOverlay.innerHTML =
        '<div class="tpc-result__scrim" data-tpc-close></div>' +
        '<div class="tpc-result__card" role="dialog" aria-modal="true" aria-live="polite">' +
          '<div class="tpc-result__badge"><svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></div>' +
          '<h3>Payment successful</h3>' +
          '<p class="tpc-result__msg"></p>' +
          '<p class="tpc-result__id"></p>' +
          '<button type="button" class="tpc-result__done" data-tpc-close>Done</button>' +
        '</div>';
      document.body.appendChild(resultOverlay);
      resultOverlay.addEventListener('click', function (e) {
        if (e.target.closest('[data-tpc-close]')) resultOverlay.classList.remove('is-open');
      });
    }
    resultOverlay.querySelector('.tpc-result__msg').textContent = message || 'Payment verified successfully.';
    var idEl = resultOverlay.querySelector('.tpc-result__id');
    if (paymentId) { idEl.textContent = 'Payment ID: ' + paymentId; idEl.style.display = ''; }
    else idEl.style.display = 'none';
    requestAnimationFrame(function () { resultOverlay.classList.add('is-open'); });
  }

  /* ── Pay button ── */
  if (payBtn && paymentEnabled) {
    payBtn.addEventListener('click', function () {
      var opt = selectedOption();
      if (!opt) return;

      // Program with no online price → send them to enquiry instead of charging.
      if (payBtn.dataset.enquire === '1') {
        window.location.href = @json(route('contact'));
        return;
      }
      if (!validInput(nameInput) || !validInput(emailInput) || !validInput(phoneInput)) return;

      var original = payBtn.innerHTML;
      payBtn.disabled = true;
      payBtn.textContent = 'Starting secure checkout…';
      setStatus('', '');

      post(orderUrl, {
        page_slug: root.dataset.pageSlug,
        block_id: root.dataset.blockId,
        option_index: Number(select.value),
        name: nameInput.value.trim(),
        email: emailInput.value.trim(),
        phone: phoneInput.value.trim()
      }).then(function (data) {
        if (!window.Razorpay || !data.checkout) throw new Error('Razorpay Checkout could not be loaded.');
        var token = data.token, checkout = data.checkout;
        var rzp = new window.Razorpay({
          key: checkout.key, amount: checkout.amount, currency: checkout.currency, order_id: checkout.order_id,
          name: checkout.name, description: checkout.description, image: checkout.image, prefill: checkout.prefill,
          theme: { color: checkout.theme_color, backdrop_color: checkout.backdrop_color },
          handler: function (response) {
            setStatus('Verifying payment…', '');
            post(confirmUrl, {
              token: token,
              razorpay_payment_id: response.razorpay_payment_id,
              razorpay_order_id: response.razorpay_order_id,
              razorpay_signature: response.razorpay_signature
            }).then(function (result) {
              setStatus('', '');
              showResult(result.message, result.payment_id);
            }).catch(function (err) { setStatus(err.message, 'error'); });
          },
          modal: { confirm_close: true, ondismiss: function () { setStatus('Checkout closed before payment. You can try again.', ''); } }
        });
        rzp.open();
      }).catch(function (err) {
        setStatus(err.message, 'error');
      }).finally(function () {
        payBtn.disabled = false;
        payBtn.innerHTML = original;
        if (window.lucide) window.lucide.createIcons();
      });
    });
  }

  // Draw lucide icons inside this section. The library loads deferred, so it may
  // not be ready yet — try now, and again on window load as a fallback.
  function drawIcons() { if (window.lucide) window.lucide.createIcons(); }
  drawIcons();
  window.addEventListener('load', drawIcons);
})();
</script>
@endonce
