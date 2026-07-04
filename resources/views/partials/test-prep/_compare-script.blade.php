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

  /* ---- Exam info modal ---- */
  var examData = {
    ielts: {
      eyebrow: "IELTS",
      title: "IELTS - International English Language Testing System",
      tagline: "The world's most widely taken English proficiency test for study, work and migration.",
      facts: [["Score", "Band 0-9 (0.5 increments)"], ["Duration", "~2 hrs 44 min"], ["Versions", "Academic & General Training"], ["Validity", "2 years"]],
      advantage: "Accepted by universities, employers and immigration authorities across the UK, Canada, Australia, New Zealand and beyond. It is the only major English test with a face-to-face Speaking interview, and results are issued as a Band Score rather than pass/fail.",
      syllabus: ["Listening (~30 min) - 4 recorded sections, 40 questions", "Reading (60 min) - 3 passages or texts, 40 questions", "Writing (60 min) - a report/letter and an essay", "Speaking (11-14 min) - face-to-face interview in 3 parts"],
      source: "General exam-format information - verify current details with the official IELTS test body.",
      matches: ["IELTS"]
    },
    pte: {
      eyebrow: "PTE Academic",
      title: "PTE - Pearson Test of English Academic",
      tagline: "A fully computer-delivered, AI-scored English test known for fast turnaround.",
      facts: [["Score", "10-90 (Global Scale of English)"], ["Duration", "~2 hrs"], ["Delivery", "100% computer-based"], ["Results", "Typically within 1-2 days"]],
      advantage: "Because every part of the test, including Speaking, is scored by AI, results arrive far faster than many English tests. It is widely accepted by universities and immigration authorities across major study destinations.",
      syllabus: ["Speaking & Writing - read aloud, repeat, describe image, essay and more", "Reading - multiple choice, reorder paragraphs, fill in blanks", "Listening - summarise, fill in blanks, highlight correct summary"],
      source: "General exam-format information - verify current details with the official PTE test body.",
      matches: ["PTE"]
    },
    toefl: {
      eyebrow: "TOEFL iBT",
      title: "TOEFL iBT - Test of English as a Foreign Language",
      tagline: "A widely accepted internet-based English test for university admissions.",
      facts: [["Score", "0-120 scale"], ["Duration", "Under 2 hrs"], ["Format", "Reading, Listening, Speaking, Writing"], ["Recognition", "Trusted by US & Canadian universities"]],
      advantage: "Widely accepted for university admissions in the US and Canada, with academic tasks designed around classroom listening, reading, speaking and writing.",
      syllabus: ["Reading - academic passages and questions", "Listening - lectures and conversations", "Speaking - independent and integrated responses", "Writing - academic discussion and integrated writing tasks"],
      source: "General exam-format information - verify current details with the official TOEFL test body.",
      matches: ["TOEFL"]
    },
    duolingo: {
      eyebrow: "Duolingo English Test",
      title: "Duolingo - Online English Proficiency Test",
      tagline: "A convenient online English test used by many institutions for admissions screening.",
      facts: [["Score", "10-160 scale"], ["Delivery", "Online"], ["Format", "Adaptive test + writing/speaking sample"], ["Best for", "Fast, flexible English proof"]],
      advantage: "Duolingo is useful when students need a flexible English-proficiency option with remote testing and quick planning around application timelines.",
      syllabus: ["Literacy - reading and vocabulary tasks", "Comprehension - listening and meaning-based questions", "Conversation - speaking prompts and responses", "Production - writing sample and extended responses"],
      source: "General exam-format information - verify current details with the official Duolingo English Test body.",
      matches: ["Duolingo"]
    },
    german: {
      eyebrow: "German Language",
      title: "German A1-B1 (CEFR Levels)",
      tagline: "Structured beginner-to-intermediate German, aligned to the Common European Framework.",
      facts: [["Levels", "A1 -> A2 -> B1"], ["Framework", "CEFR"], ["Focus", "Daily communication"], ["Typical need", "Visas / vocational training"]],
      advantage: "A1-B1 German is commonly required for German student visas, vocational-training applications and job-seeker pathways. Recognised language certificates can support embassy and institution requirements.",
      syllabus: ["Grammar, vocabulary and pronunciation for each level", "Reading and listening comprehension", "Writing - everyday letters, emails and short texts", "Speaking - everyday conversation and exam tasks"],
      source: "General language-format information - verify current certificate requirements with the official certifying body.",
      matches: ["German A1", "German A2", "German B1"]
    },
    french: {
      eyebrow: "French Language",
      title: "French - Beginner to Intermediate Pathway",
      tagline: "French language coaching for academic, visa and everyday communication goals.",
      facts: [["Levels", "A1 -> A2 -> B1"], ["Framework", "CEFR"], ["Skills", "Reading, Listening, Writing, Speaking"], ["Best for", "French-taught and dual-language routes"]],
      advantage: "French proficiency helps students access French-taught, bilingual and scholarship-linked opportunities while strengthening day-to-day communication for study abroad.",
      syllabus: ["Grammar, vocabulary and pronunciation", "Reading and listening comprehension", "Writing - emails, notes and short essays", "Speaking - guided conversation and exam-style prompts"],
      source: "General language-format information - verify current certificate requirements with the official certifying body.",
      matches: ["French A1", "French A2", "French B1"]
    },
    japanese: {
      eyebrow: "Japanese Language",
      title: "Japanese - Foundation Levels",
      tagline: "Beginner Japanese for students planning academic, work or cultural pathways in Japan.",
      facts: [["Levels", "N5 -> N4 foundation"], ["Framework", "JLPT-oriented"], ["Skills", "Vocabulary, grammar, reading, listening"], ["Best for", "Japan study pathways"]],
      advantage: "Japanese foundation training helps students build the language confidence needed for daily life, interviews and future progression toward recognised proficiency levels.",
      syllabus: ["Hiragana, Katakana and essential Kanji", "Core grammar and sentence patterns", "Reading and listening practice", "Conversation drills for everyday situations"],
      source: "General language-format information - verify current certificate requirements with the official certifying body.",
      matches: ["Japanese N5", "Japanese N4"]
    },
    sat: {
      eyebrow: "SAT",
      title: "SAT - Digital Scholastic Assessment Test",
      tagline: "A fully digital, adaptive US undergraduate admissions test.",
      facts: [["Score", "400-1600 total"], ["Sections", "Reading & Writing + Math"], ["Duration", "~2 hrs 14 min"], ["Penalty", "None for wrong answers"]],
      advantage: "Used for undergraduate admissions and merit scholarships at US universities. The Digital SAT is section-adaptive, so early module performance influences later module difficulty.",
      syllabus: ["Reading & Writing - 2 adaptive modules", "Math - 2 adaptive modules with calculator support", "Evidence, expression, algebra, data and problem solving"],
      source: "General exam-format information - verify current details with the official College Board SAT program.",
      matches: ["SAT"]
    },
    act: {
      eyebrow: "ACT",
      title: "ACT - American College Testing",
      tagline: "A curriculum-based US admissions test with an optional Writing test.",
      facts: [["Score", "1-36 composite"], ["Sections", "English, Math, Reading, Science"], ["Composite", "Average of 4 section scores"], ["Writing", "Optional essay"]],
      advantage: "A well-established alternative to the SAT for US admissions; many universities accept either test. The Science section can suit students confident in data interpretation.",
      syllabus: ["English - grammar, usage and rhetorical skills", "Math - algebra, geometry and trigonometry", "Reading - comprehension across passage types", "Science - interpreting data, experiments and viewpoints"],
      source: "General exam-format information - verify current details with the official ACT program.",
      matches: ["ACT"]
    },
    gre: {
      eyebrow: "GRE",
      title: "GRE General Test",
      tagline: "A graduate admissions test used for master's, PhD and some MBA programs.",
      facts: [["Verbal / Quant", "130-170 each"], ["Analytical Writing", "0-6"], ["Duration", "~1 hr 58 min"], ["Delivery", "Computer-based"]],
      advantage: "Required or accepted by many graduate schools worldwide for master's and PhD programs, and by some MBA programs as a GMAT alternative.",
      syllabus: ["Analytical Writing - one issue task", "Verbal Reasoning - reading comprehension and text completion", "Quantitative Reasoning - arithmetic, algebra, geometry and data analysis"],
      source: "General exam-format information - verify current details with the official GRE program.",
      matches: ["GRE"]
    },
    gmat: {
      eyebrow: "GMAT",
      title: "GMAT - Graduate Management Admission Test",
      tagline: "A computer-adaptive test built for MBA and business master's admissions.",
      facts: [["Score", "205-805 total"], ["Sections", "Quant, Verbal & Data Insights"], ["Duration", "~2 hrs 15 min"], ["Essay/IR", "No longer scored separately"]],
      advantage: "The primary admissions test for MBA and specialised business master's programs globally, with a strong focus on reasoning, data interpretation and business-school readiness.",
      syllabus: ["Quantitative Reasoning - problem solving", "Verbal Reasoning - reading comprehension and critical reasoning", "Data Insights - data sufficiency, tables, graphs and multi-source reasoning"],
      source: "General exam-format information - verify current details with the official GMAT program.",
      matches: ["GMAT"]
    }
  };

  var examOverlay = root.querySelector('[data-tpc-exam-overlay]');
  var examLastFocus = null;
  var examCloseTimer = null;

  function closeExamModal() {
    if (!examOverlay || examOverlay.hidden) return;
    examOverlay.classList.remove('is-open');
    examOverlay.setAttribute('aria-hidden', 'true');
    document.documentElement.classList.remove('tpc-exam-open');
    clearTimeout(examCloseTimer);
    examCloseTimer = setTimeout(function () { examOverlay.hidden = true; }, 280);
    if (examLastFocus && typeof examLastFocus.focus === 'function') {
      try { examLastFocus.focus({ preventScroll: true }); } catch (e) { examLastFocus.focus(); }
    }
  }

  function selectProgramByNames(names) {
    if (!select || !names || !names.length) return false;
    for (var i = 0; i < select.options.length; i += 1) {
      var opt = select.options[i];
      var programName = opt.dataset.name || opt.textContent || '';
      for (var j = 0; j < names.length; j += 1) {
        if (programName.toLowerCase().indexOf(String(names[j]).toLowerCase()) === 0) {
          select.value = opt.value;
          syncAmount();
          return true;
        }
      }
    }
    return false;
  }

  function openExamModal(key) {
    if (!examOverlay || !examData[key]) return;
    var data = examData[key];
    examLastFocus = document.activeElement;
    clearTimeout(examCloseTimer);
    examOverlay.querySelector('[data-tpc-exam-eyebrow]').textContent = data.eyebrow;
    examOverlay.querySelector('[data-tpc-exam-title]').textContent = data.title;
    examOverlay.querySelector('[data-tpc-exam-tagline]').textContent = data.tagline;
    var grid = examOverlay.querySelector('[data-tpc-exam-grid]');
    grid.innerHTML = '';
    data.facts.forEach(function (fact) {
      var cell = document.createElement('div');
      cell.className = 'tpc-exam-fact';
      var keyEl = document.createElement('small');
      var valueEl = document.createElement('span');
      keyEl.textContent = fact[0];
      valueEl.textContent = fact[1];
      cell.appendChild(keyEl);
      cell.appendChild(valueEl);
      grid.appendChild(cell);
    });
    examOverlay.querySelector('[data-tpc-exam-advantage]').textContent = data.advantage;
    var syllabus = examOverlay.querySelector('[data-tpc-exam-syllabus]');
    syllabus.innerHTML = '';
    data.syllabus.forEach(function (item) {
      var li = document.createElement('li');
      li.textContent = item;
      syllabus.appendChild(li);
    });
    examOverlay.querySelector('[data-tpc-exam-source]').textContent = data.source;
    var cta = examOverlay.querySelector('[data-tpc-exam-cta]');
    cta.onclick = function () {
      selectProgramByNames(data.matches);
      closeExamModal();
      var pay = document.getElementById('tp-enrol');
      if (pay) pay.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };
    examOverlay.hidden = false;
    examOverlay.setAttribute('aria-hidden', 'false');
    document.documentElement.classList.add('tpc-exam-open');
    requestAnimationFrame(function () {
      examOverlay.classList.add('is-open');
      var card = examOverlay.querySelector('.tpc-exam-modal__card');
      if (card) card.focus();
    });
  }

  root.querySelectorAll('[data-tpc-exam]').forEach(function (btn) {
    btn.addEventListener('click', function () { openExamModal(btn.getAttribute('data-tpc-exam')); });
  });
  if (examOverlay) {
    examOverlay.addEventListener('click', function (event) {
      if (event.target.closest('[data-tpc-exam-close]')) closeExamModal();
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeExamModal();
    });
  }

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
