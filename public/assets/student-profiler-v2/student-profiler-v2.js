/* ==========================================================================
   Student Profiler V2 module — wizard engine (isolated, no globals).

   Profiler V2 = the EXACT Student Profiler questions (degree-adaptive set from
   /profiler), rendered in the EXACT Profile Evaluator design (the light/indigo
   mim-essay "Evaluate My Profile" look). Reads window.__PROFILER_V2__ (config +
   restored session state), renders the degree picker, plays a circular expand
   reveal on selection, then drives the degree-adaptive section-by-section wizard
   with staggered key-drop field animations, a locked progress stepper, review
   and submit. There is NO scoring: on submit the profile is handed to the team
   for a manual review. Answers autosave to the PHP session via /profiler-v2.

   v1 (/profiler) is left completely untouched — this is an additive sibling.
   ========================================================================== */
(function () {
    "use strict";

    var DATA = window.__PROFILER_V2__;
    if (!DATA || !DATA.config) return;
    var root = document.querySelector("[data-p2-root]");
    var stage = root && root.querySelector("[data-p2-stage]");
    if (!stage) return;

    var cfg = DATA.config;
    var S = DATA.state || {};
    var state = {
        degree: S.degree || null,
        section: typeof S.section === "number" ? S.section : 0,
        answers: S.answers && typeof S.answers === "object" ? S.answers : {}
    };

    // Degree-adaptive: the active section list depends on the chosen degree.
    function sects() { return (cfg.sections && cfg.sections[state.degree]) || []; }
    function reviewIndex() { return sects().length; }

    /* ---------- tiny hyperscript helper ---------- */
    function E(tag, opts, kids) {
        var n = document.createElement(tag);
        opts = opts || {};
        for (var k in opts) {
            if (!Object.prototype.hasOwnProperty.call(opts, k)) continue;
            var v = opts[k];
            if (k === "class") n.className = v;
            else if (k === "html") n.innerHTML = v;
            else if (k === "text") n.textContent = v;
            else if (k === "style") n.setAttribute("style", v);
            else if (k.slice(0, 2) === "on" && typeof v === "function") n.addEventListener(k.slice(2).toLowerCase(), v);
            else if (v != null && v !== false) n.setAttribute(k, v);
        }
        (kids || []).forEach(function (c) {
            if (c == null || c === false) return;
            n.appendChild(typeof c === "string" ? document.createTextNode(c) : c);
        });
        return n;
    }

    function save(action) {
        return fetch(DATA.endpoint, {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": DATA.csrf || "", "X-Requested-With": "XMLHttpRequest", Accept: "application/json" },
            body: JSON.stringify({ action: action || "save", degree: state.degree, section: state.section, answers: state.answers })
        }).then(function (r) { return r.ok ? r.json() : null; }).catch(function () { return null; });
    }

    /* ===================== ENTRY (degree select) ===================== */
    // Same first step as the Student Profiler: pick a degree level, which then
    // drives the degree-adaptive question set. Rendered in the Evaluator's
    // light/indigo design language (p2- card style).
    function renderEntry() {
        stage.innerHTML = "";
        var cards = (cfg.degreeOrder || []).map(function (key, i) {
            var d = cfg.degrees[key];
            return E("button", { class: "p2-card", type: "button", "data-featured": d.featured ? "1" : "0", style: "--i:" + i, "aria-label": "Choose " + d.label,
                onclick: function (e) { selectDegree(key, e.currentTarget); } }, [
                d.featured ? E("span", { class: "p2-card__badge", html: "&#9733; MOST CHOSEN" }) : null,
                E("span", { class: "p2-card__chip", text: d.initial }),
                E("span", { class: "p2-card__title", text: d.label }),
                E("p", { class: "p2-card__tag", text: d.tag }),
                E("div", { class: "p2-card__rule" }),
                E("p", { class: "p2-card__ex", text: d.examples }),
                E("span", { class: "p2-card__cta", html: "Choose &nbsp;&rarr;" })
            ]);
        });
        stage.appendChild(E("section", { class: "p2-entry" }, [
            E("div", { class: "p2-entry__inner" }, [
                E("span", { class: "p2-eyebrow", html: "ODA &middot; STUDENT PROFILER" }),
                E("h1", { text: "What would you like to study abroad?" }),
                E("p", { class: "p2-entry__sub", text: "Pick your degree level to begin. We’ll tailor every question to your path — it only takes a few minutes." }),
                E("div", { class: "p2-cards" }, cards),
                E("p", { class: "p2-entry__note", text: "Free · No spam · Takes a few minutes" })
            ])
        ]));
    }

    /* ===================== EXPAND REVEAL ===================== */
    function selectDegree(key, cardEl) {
        state.degree = key;
        state.section = 0;
        var d = cfg.degrees[key];
        var rect = cardEl.getBoundingClientRect();
        var ox = ((rect.left + rect.width / 2) / window.innerWidth * 100).toFixed(1) + "%";
        var oy = ((rect.top + rect.height / 2) / window.innerHeight * 100).toFixed(1) + "%";
        var reveal = E("div", { class: "p2-reveal", style: "--ox:" + ox + ";--oy:" + oy }, [
            E("div", {}, [
                E("div", { class: "p2-reveal__mark", text: d.initial }),
                E("h2", { class: "p2-reveal__t", text: d.label }),
                E("p", { class: "p2-reveal__s", text: "Tailoring your questionnaire…" })
            ])
        ]);
        root.appendChild(reveal);
        var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        requestAnimationFrame(function () { requestAnimationFrame(function () { reveal.classList.add("is-on"); }); });
        setTimeout(function () {
            renderWizard();
            reveal.classList.add("is-out");
            setTimeout(function () { reveal.remove(); }, reduce ? 0 : 400);
            save("save");
        }, reduce ? 50 : 680);
    }

    /* ===================== BACK TO COURSE PICKER ===================== */
    // "Select course" — return to the degree picker so the user can change their
    // course. Answers are kept (re-picking the same course resumes them); the
    // chosen degree is cleared so a refresh lands on the picker, not the wizard.
    function backToEntry() {
        state.degree = null;
        state.section = 0;
        renderEntry();
        save("save");
        window.scrollTo({ top: 0 });
    }

    /* ===================== WIZARD SHELL ===================== */
    function renderWizard() {
        var d = cfg.degrees[state.degree] || {};
        stage.innerHTML = "";
        var rail = E("div", { class: "p2-rail", "data-p2-rail": "" });
        var main = E("div", { class: "p2-main", "data-p2-main": "" });
        stage.appendChild(E("section", { class: "p2-wizard" }, [
            E("header", { class: "p2-topbar" }, [
                E("div", { class: "p2-brand" }, [
                    E("span", { class: "p2-brand__mark", html: "1&deg;" }),
                    E("span", { class: "p2-brand__name", text: "ODA Profiler" }),
                    E("span", { class: "p2-degchip", text: d.label || "" })
                ]),
                E("div", { class: "p2-topbar__right" }, [
                    E("span", { class: "p2-step-count", "data-p2-count": "" }),
                    E("button", { class: "p2-exit", type: "button", html: "&#8592;&nbsp; Select course", onclick: backToEntry })
                ])
            ]),
            E("div", { class: "p2-body" }, [rail, main])
        ]));
        buildRail();
        renderSectionInto();
    }

    function buildRail() {
        var rail = stage.querySelector("[data-p2-rail]");
        if (!rail) return;
        rail.innerHTML = "";
        // Top horizontal stepper (desktop/tablet).
        var steps = E("div", { class: "p2-steps" }, sects().map(function (s, i) {
            var li = E("div", { class: "p2-stepli", "data-step": i }, [
                E("span", { class: "p2-stepdot", "data-dot": i }),
                E("div", { class: "p2-steptxt" }, [E("b", { text: s.eyebrow }), E("span", { "data-status": i })])
            ]);
            li.addEventListener("click", function () { if (i < state.section) navigate(i); });
            return li;
        }));
        // Compact progress bar (mobile) — mirrors the stepper state.
        var progress = E("div", { class: "p2-progress" }, [
            E("div", { class: "p2-progress__txt" }, [E("span", { "data-p2-ptext": "" }), E("b", { "data-p2-ppct": "" })]),
            E("div", { class: "p2-progress__bar" }, [E("div", { class: "p2-progress__fill", "data-p2-pfill": "" })])
        ]);
        rail.appendChild(steps);
        rail.appendChild(progress);
        updateRail();
    }

    function updateRail() {
        var total = sects().length;
        var atReview = state.section >= total;
        var count = stage.querySelector("[data-p2-count]");
        if (count) count.textContent = atReview ? "Review" : "Step " + (state.section + 1) + " of " + total;
        sects().forEach(function (s, i) {
            var li = stage.querySelector('.p2-stepli[data-step="' + i + '"]');
            var dot = stage.querySelector('[data-dot="' + i + '"]');
            var st = stage.querySelector('[data-status="' + i + '"]');
            if (!li) return;
            var done = i < state.section, active = i === state.section;
            li.classList.toggle("is-done", done);
            li.classList.toggle("is-active", active);
            li.classList.toggle("is-locked", !done && !active);
            if (dot) { dot.innerHTML = ""; dot.textContent = done ? "✓" : (s.icon || (i + 1)); }
            if (st) st.textContent = done ? "Completed" : (active ? "In progress" : "Locked");
        });
        // mobile progress bar
        var pct = Math.round((atReview ? total : state.section) / total * 100);
        var ptext = stage.querySelector("[data-p2-ptext]");
        var ppct = stage.querySelector("[data-p2-ppct]");
        var pfill = stage.querySelector("[data-p2-pfill]");
        if (ptext) ptext.textContent = atReview ? "Review your answers" : ("Step " + (state.section + 1) + " of " + total + " · " + sects()[state.section].eyebrow);
        if (ppct) ppct.textContent = pct + "%";
        if (pfill) pfill.setAttribute("style", "width:" + pct + "%");
    }

    /* ===================== SECTION RENDER ===================== */
    function renderSectionInto() {
        var arr = sects();
        if (state.section >= arr.length) return renderReview();
        var i = state.section;
        var sec = arr[i];
        var last = i === arr.length - 1;
        var fieldEls = (sec.fields || []).map(function (f, k) { return renderField(f, k); });

        var newMain = E("div", { class: "p2-main", "data-p2-main": "" }, [
            E("p", { class: "p2-sec-eyebrow", text: "Section " + (i + 1) + " of " + arr.length + " · " + sec.eyebrow }),
            E("h2", { class: "p2-sec-title", text: sec.title }),
            sec.subtitle ? E("p", { class: "p2-sec-sub", text: sec.subtitle }) : null,
            E("div", { class: "p2-fields" }, fieldEls),
            E("div", { class: "p2-foot" }, [
                E("span", { class: "p2-save" }, [E("i", {}), "Progress saved automatically"]),
                E("div", { class: "p2-nav" }, [
                    E("button", { class: "p2-btn p2-btn--ghost", type: "button", disabled: i === 0 ? "" : false, html: "&larr; Back", onclick: function () { if (i > 0) navigate(i - 1); } }),
                    E("button", { class: "p2-btn p2-btn--primary", type: "button", html: (last ? "Review" : "Continue") + " &nbsp;&rarr;", onclick: function () { onContinue(i); } })
                ])
            ])
        ]);
        swapMain(newMain);
    }

    function swapMain(newMain) {
        var old = stage.querySelector("[data-p2-main]");
        if (old) old.parentNode.replaceChild(newMain, old);
    }

    /* ---------- field renderers ---------- */
    function get(key) { return state.answers[key]; }
    function set(key, val) { state.answers[key] = val; }

    function wrap(f, idx, control) {
        return E("div", { class: "p2-field", "data-key": f.key, style: "--i:" + idx }, [
            E("label", { class: "p2-field__label", text: f.label }),
            control,
            f.help ? E("p", { class: "p2-field__help", text: f.help }) : null
        ]);
    }

    // Options may be plain strings or { label, icon } objects. The stored answer
    // value is ALWAYS the label string, so icons stay presentation-only.
    function optLabel(o) { return (o && typeof o === "object") ? o.label : o; }
    function optIcon(o) { return (o && typeof o === "object") ? o.icon : null; }

    // Build one icon-card option button (mim-essay style: icon on top, label below,
    // a check badge in the corner when selected). `on` = currently selected; `idx`
    // drives the staggered entrance animation.
    function optCard(label, icon, on, onclick, idx) {
        return E("button", { type: "button", class: "p2-opt p2-opt--card" + (on ? " is-sel" : ""), style: "--c:" + (idx || 0), onclick: onclick }, [
            icon ? E("span", { class: "p2-opt__icon", text: icon }) : null,
            E("span", { class: "p2-opt__txt", text: label }),
            E("span", { class: "p2-opt__badge", html: "&#10003;" })
        ]);
    }

    function renderField(f, i) {
        switch (f.type) {
            case "radio": {
                var cur = get(f.key);
                var opts = E("div", { class: "p2-opts" }, (f.options || []).map(function (o, ci) {
                    var label = optLabel(o);
                    var opt = optCard(label, optIcon(o), cur === label, function () {
                        set(f.key, label);
                        opts.querySelectorAll(".p2-opt").forEach(function (b) { b.classList.remove("is-sel"); });
                        opt.classList.add("is-sel");
                        clearErr(f.key);
                    }, ci);
                    return opt;
                }));
                return wrap(f, i, opts);
            }
            case "chips": {
                var sel = Array.isArray(get(f.key)) ? get(f.key).slice() : [];
                var opts = E("div", { class: "p2-opts" }, (f.options || []).map(function (o, ci) {
                    var label = optLabel(o);
                    var opt = optCard(label, optIcon(o), sel.indexOf(label) > -1, function () {
                        var idx = sel.indexOf(label);
                        if (idx > -1) { sel.splice(idx, 1); opt.classList.remove("is-sel"); }
                        else { sel.push(label); opt.classList.add("is-sel"); }
                        set(f.key, sel.slice()); clearErr(f.key);
                    }, ci);
                    return opt;
                }));
                return wrap(f, i, opts);
            }
            case "text": case "email": case "tel": default: {
                var input = E("input", { type: (f.type === "text" ? "text" : f.type), placeholder: f.placeholder || "", value: get(f.key) || "" });
                input.addEventListener("input", function () { set(f.key, input.value); clearErr(f.key); });
                var ctl = E("div", { class: "p2-control p2-control--input", "data-key": f.key }, [input, f.unit ? E("span", { class: "p2-unit", text: f.unit }) : null]);
                input.addEventListener("focus", function () { ctl.classList.add("is-focus"); });
                input.addEventListener("blur", function () { ctl.classList.remove("is-focus"); });
                return wrap(f, i, ctl);
            }
        }
    }

    /* ---------- validation + navigation ---------- */
    function isEmpty(v) { return v == null || v === "" || (Array.isArray(v) && v.length === 0); }
    function clearErr(key) { var el = stage.querySelector('.p2-field[data-key="' + cssEsc(key) + '"]'); if (el) el.classList.remove("is-error"); }
    function cssEsc(s) { return String(s).replace(/"/g, '\\"'); }

    function validateSection(sec) {
        var ok = true, first = null;
        (sec.fields || []).forEach(function (f) {
            if (f.required && isEmpty(get(f.key))) {
                ok = false;
                var el = stage.querySelector('.p2-field[data-key="' + cssEsc(f.key) + '"]');
                if (el) { el.classList.add("is-error"); if (!first) first = el; }
            }
        });
        if (first) first.scrollIntoView({ behavior: "smooth", block: "center" });
        return ok;
    }

    function onContinue(i) {
        var sec = sects()[i];
        if (sec && !validateSection(sec)) return;
        navigate(i + 1);
    }

    function navigate(target) {
        var main = stage.querySelector("[data-p2-main]");
        var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        if (main && !reduce) main.classList.add("is-leaving");
        setTimeout(function () {
            state.section = target;
            if (target >= reviewIndex()) renderReview(); else renderSectionInto();
            updateRail();
            save("save");
            window.scrollTo({ top: 0, behavior: "smooth" });
        }, reduce ? 0 : 170);
    }

    /* ===================== REVIEW ===================== */
    function answerText(f) {
        var v = get(f.key);
        if (isEmpty(v)) return null;
        if (Array.isArray(v)) return v;
        if (typeof v === "boolean") return [v ? f.label : null];
        return [String(v)];
    }

    function renderReview() {
        var arr = sects();
        var rows = arr.map(function (s, i) {
            var vals = [];
            (s.fields || []).forEach(function (f) { var a = answerText(f); if (a) vals = vals.concat(a.filter(Boolean)); });
            return E("div", { class: "p2-sumrow" }, [
                E("div", { class: "p2-sumrow__head" }, [
                    E("div", { class: "p2-num" }, [E("span", { text: i + 1 }), E("b", { text: s.eyebrow })]),
                    E("button", { class: "p2-edit", type: "button", html: "Edit &#9998;", onclick: function () { navigate(i); } })
                ]),
                E("div", { class: "p2-sumchips" }, (vals.length ? vals : ["—"]).map(function (v) { return E("span", { class: "p2-sumchip", text: v }); }))
            ]);
        });
        var newMain = E("div", { class: "p2-main", "data-p2-main": "" }, [
            E("p", { class: "p2-sec-eyebrow", text: "Final step · Review" }),
            E("h2", { class: "p2-sec-title", text: "Review & submit" }),
            E("p", { class: "p2-sec-sub", text: "Check your answers — you can edit any section before we evaluate your profile." }),
            E("div", { class: "p2-review" }, [
                E("div", { class: "p2-summary" }, rows),
                E("div", { class: "p2-result" }, [
                    E("p", { class: "p2-result__eyebrow", text: "ALMOST THERE" }),
                    E("h3", { style: "margin:0;font-size:24px;font-weight:800;font-family:'Cormorant Garamond',Georgia,serif", text: "Submit for an expert evaluation" }),
                    E("p", { class: "p2-result__p", text: "Our advisors will personally evaluate your profile and get back to you with detailed, tailored guidance." }),
                    E("div", { class: "p2-result__rule" }),
                    E("div", { class: "p2-result__li", html: "&#9989; &nbsp; Reviewed by an ODA advisor" }),
                    E("div", { class: "p2-result__li", html: "&#127891; &nbsp; Best-fit schools & next steps" }),
                    E("div", { class: "p2-result__li", html: "&#9993;&#65039; &nbsp; We’ll reach out to you shortly" }),
                    E("div", { class: "p2-result__spacer" }),
                    E("button", { class: "p2-btn p2-btn--white", type: "button", html: "Evaluate my profile &nbsp;&rarr;", onclick: submit }),
                    E("p", { class: "p2-result__note", text: "Free · No spam · We’ll be in touch" })
                ])
            ]),
            E("div", { class: "p2-foot" }, [
                E("span", { class: "p2-save" }, [E("i", {}), "Progress saved automatically"]),
                E("div", { class: "p2-nav" }, [E("button", { class: "p2-btn p2-btn--ghost", type: "button", html: "&larr; Back", onclick: function () { navigate(arr.length - 1); } })])
            ])
        ]);
        swapMain(newMain);
    }

    /* ===================== SUBMIT + SUCCESS POPUP ===================== */
    function submit(e) {
        var btn = e && e.currentTarget;
        if (btn) { btn.disabled = true; btn.innerHTML = "Submitting…"; }
        save("submit").then(function () { showSuccess(); });
    }

    // No score, no rating — a celebratory "we'll evaluate & get back to you" popup.
    function showSuccess() {
        var confetti = E("div", { class: "p2-confetti" });
        var colors = ["#ff5e32", "#ff8a5c", "#f7da82", "#1a0088", "#2a16a0", "#c7924a"];
        for (var i = 0; i < 18; i++) {
            confetti.appendChild(E("i", { style: "left:" + (Math.random() * 100).toFixed(1) + "%;background:" + colors[i % colors.length] + ";animation-delay:" + (Math.random() * .5).toFixed(2) + "s;transform:rotate(" + Math.floor(Math.random() * 360) + "deg)" }));
        }
        var modal = E("div", { class: "p2-modal", role: "dialog", "aria-modal": "true", "aria-label": "Profile submitted" }, [
            E("div", { class: "p2-modal__card" }, [
                confetti,
                E("button", { class: "p2-modal__close", type: "button", "aria-label": "Close", html: "&times;", onclick: function () { modal.remove(); } }),
                E("div", { class: "p2-check" }),
                E("h2", { text: "Thank you — your profile is in!" }),
                E("p", { text: "Our team will evaluate your profile and get back to you with detailed, tailored guidance. Stay tuned — we’ll reach out shortly." }),
                E("div", { class: "p2-modal__actions" }, [
                    E("a", { class: "p2-btn p2-btn--primary", href: "/", html: "Back to home" }),
                    E("a", { class: "p2-btn p2-btn--ghost", href: "/study-abroad", text: "Explore destinations" })
                ])
            ])
        ]);
        modal.addEventListener("click", function (e) { if (e.target === modal) modal.remove(); });
        root.appendChild(modal);
    }

    /* ===================== BOOT ===================== */
    // Resume the wizard only when a degree was already chosen; otherwise land on
    // the degree picker.
    var resuming = state.degree && (state.section > 0 || Object.keys(state.answers).length > 0);
    if (resuming) renderWizard(); else renderEntry();
})();
