/* ==========================================================================
   Profile Evaluator module — wizard engine (isolated, no globals).

   A native rebuild of the mim-essay "Evaluate My Profile" tool. Reads
   window.__EVALUATOR__ (config + restored session state), renders the
   "Evaluate Me" hero, plays a circular expand reveal on click, then drives the
   section-by-section wizard (Academics → Extra Curricular → Differentiators →
   Work Experience → Test Scores → Degree of Interest) with staggered key-drop
   field animations, a locked progress rail, review and submit. Questions and
   options are verbatim from mim-essay. There is NO scoring: on submit the
   profile is handed to the team for a manual review (same ending as the
   Student Profiler). Answers autosave to the PHP session via /evaluate-my-profile.
   ========================================================================== */
(function () {
    "use strict";

    var DATA = window.__EVALUATOR__;
    if (!DATA || !DATA.config) return;
    var root = document.querySelector("[data-pe-root]");
    var stage = root && root.querySelector("[data-pe-stage]");
    if (!stage) return;

    var cfg = DATA.config;
    var hero = cfg.hero || {};
    var S = DATA.state || {};
    var state = {
        started: false,
        section: typeof S.section === "number" ? S.section : 0,
        answers: S.answers && typeof S.answers === "object" ? S.answers : {}
    };

    function sects() { return cfg.sections || []; }
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
            body: JSON.stringify({ action: action || "save", section: state.section, answers: state.answers })
        }).then(function (r) { return r.ok ? r.json() : null; }).catch(function () { return null; });
    }

    /* ===================== ENTRY (Evaluate-Me hero) ===================== */
    function renderEntry() {
        stage.innerHTML = "";
        var points = (hero.points || []).map(function (p) {
            return E("li", { class: "pe-point", html: "<span class='pe-point__tick'>&#10003;</span>" + p });
        });
        var cta = E("button", { class: "pe-hero-cta", type: "button", "aria-label": hero.cta || "Evaluate Me",
            onclick: function (e) { start(e.currentTarget); } }, [
            E("span", { text: hero.cta || "Evaluate Me" }),
            E("span", { class: "pe-hero-cta__arrow", html: "&rarr;" })
        ]);
        stage.appendChild(E("section", { class: "pe-entry" }, [
            E("div", { class: "pe-entry__inner" }, [
                hero.eyebrow ? E("span", { class: "pe-eyebrow", text: hero.eyebrow }) : null,
                E("h1", { text: hero.title || "Evaluate your profile" }),
                hero.subtitle ? E("p", { class: "pe-entry__sub", text: hero.subtitle }) : null,
                points.length ? E("ul", { class: "pe-points" }, points) : null,
                E("div", { class: "pe-hero-cta-wrap" }, [cta]),
                E("p", { class: "pe-entry__note", text: "Free · No spam · Takes a few minutes" })
            ])
        ]));
    }

    /* ===================== EXPAND REVEAL ===================== */
    function start(btnEl) {
        state.started = true;
        var rect = btnEl.getBoundingClientRect();
        var ox = ((rect.left + rect.width / 2) / window.innerWidth * 100).toFixed(1) + "%";
        var oy = ((rect.top + rect.height / 2) / window.innerHeight * 100).toFixed(1) + "%";
        var reveal = E("div", { class: "pe-reveal", style: "--ox:" + ox + ";--oy:" + oy }, [
            E("div", {}, [
                E("div", { class: "pe-reveal__mark", html: "1&deg;" }),
                E("h2", { class: "pe-reveal__t", text: "Let’s evaluate your profile" }),
                E("p", { class: "pe-reveal__s", text: "Preparing your questionnaire…" })
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

    /* ===================== BACK TO HERO ===================== */
    // "Start over" — wipe answers and return to the hero.
    function startOver() {
        state.started = false;
        state.section = 0;
        state.answers = {};
        renderEntry();
        save("reset");
        window.scrollTo({ top: 0 });
    }

    /* ===================== WIZARD SHELL ===================== */
    function renderWizard() {
        stage.innerHTML = "";
        var rail = E("div", { class: "pe-rail", "data-pe-rail": "" });
        var main = E("div", { class: "pe-main", "data-pe-main": "" });
        stage.appendChild(E("section", { class: "pe-wizard" }, [
            E("header", { class: "pe-topbar" }, [
                E("div", { class: "pe-brand" }, [
                    E("span", { class: "pe-brand__mark", html: "1&deg;" }),
                    E("span", { class: "pe-brand__name", text: "ODA Profile Evaluator" }),
                    E("span", { class: "pe-degchip", text: "Profile Evaluation" })
                ]),
                E("div", { class: "pe-topbar__right" }, [
                    E("span", { class: "pe-step-count", "data-pe-count": "" }),
                    E("button", { class: "pe-exit", type: "button", html: "&#8634;&nbsp; Start over", onclick: startOver })
                ])
            ]),
            E("div", { class: "pe-body" }, [rail, main])
        ]));
        buildRail();
        renderSectionInto();
    }

    function buildRail() {
        var rail = stage.querySelector("[data-pe-rail]");
        if (!rail) return;
        rail.innerHTML = "";
        // Top horizontal stepper (desktop/tablet).
        var steps = E("div", { class: "pe-steps" }, sects().map(function (s, i) {
            var li = E("div", { class: "pe-stepli", "data-step": i }, [
                E("span", { class: "pe-stepdot", "data-dot": i }),
                E("div", { class: "pe-steptxt" }, [E("b", { text: s.eyebrow }), E("span", { "data-status": i })])
            ]);
            li.addEventListener("click", function () { if (i < state.section) navigate(i); });
            return li;
        }));
        // Compact progress bar (mobile) — mirrors the stepper state.
        var progress = E("div", { class: "pe-progress" }, [
            E("div", { class: "pe-progress__txt" }, [E("span", { "data-pe-ptext": "" }), E("b", { "data-pe-ppct": "" })]),
            E("div", { class: "pe-progress__bar" }, [E("div", { class: "pe-progress__fill", "data-pe-pfill": "" })])
        ]);
        rail.appendChild(steps);
        rail.appendChild(progress);
        updateRail();
    }

    function updateRail() {
        var total = sects().length;
        var atReview = state.section >= total;
        var count = stage.querySelector("[data-pe-count]");
        if (count) count.textContent = atReview ? "Review" : "Step " + (state.section + 1) + " of " + total;
        sects().forEach(function (s, i) {
            var li = stage.querySelector('.pe-stepli[data-step="' + i + '"]');
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
        var ptext = stage.querySelector("[data-pe-ptext]");
        var ppct = stage.querySelector("[data-pe-ppct]");
        var pfill = stage.querySelector("[data-pe-pfill]");
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

        var newMain = E("div", { class: "pe-main", "data-pe-main": "" }, [
            E("p", { class: "pe-sec-eyebrow", text: "Section " + (i + 1) + " of " + arr.length + " · " + sec.eyebrow }),
            E("h2", { class: "pe-sec-title", text: sec.title }),
            sec.subtitle ? E("p", { class: "pe-sec-sub", text: sec.subtitle }) : null,
            E("div", { class: "pe-fields" }, fieldEls),
            E("div", { class: "pe-foot" }, [
                E("span", { class: "pe-save" }, [E("i", {}), "Progress saved automatically"]),
                E("div", { class: "pe-nav" }, [
                    E("button", { class: "pe-btn pe-btn--ghost", type: "button", disabled: i === 0 ? "" : false, html: "&larr; Back", onclick: function () { if (i > 0) navigate(i - 1); } }),
                    E("button", { class: "pe-btn pe-btn--primary", type: "button", html: (last ? "Review" : "Continue") + " &nbsp;&rarr;", onclick: function () { onContinue(i); } })
                ])
            ])
        ]);
        swapMain(newMain);
    }

    function swapMain(newMain) {
        var old = stage.querySelector("[data-pe-main]");
        if (old) old.parentNode.replaceChild(newMain, old);
    }

    /* ---------- field renderers ---------- */
    function get(key) { return state.answers[key]; }
    function set(key, val) { state.answers[key] = val; }

    function wrap(f, idx, control) {
        return E("div", { class: "pe-field", "data-key": f.key, style: "--i:" + idx }, [
            E("label", { class: "pe-field__label", text: f.label }),
            control,
            f.help ? E("p", { class: "pe-field__help", text: f.help }) : null
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
        return E("button", { type: "button", class: "pe-opt pe-opt--card" + (on ? " is-sel" : ""), style: "--c:" + (idx || 0), onclick: onclick }, [
            icon ? E("span", { class: "pe-opt__icon", text: icon }) : null,
            E("span", { class: "pe-opt__txt", text: label }),
            E("span", { class: "pe-opt__badge", html: "&#10003;" })
        ]);
    }

    function renderField(f, i) {
        switch (f.type) {
            case "radio": {
                var cur = get(f.key);
                var opts = E("div", { class: "pe-opts" }, (f.options || []).map(function (o, ci) {
                    var label = optLabel(o);
                    var opt = optCard(label, optIcon(o), cur === label, function () {
                        set(f.key, label);
                        opts.querySelectorAll(".pe-opt").forEach(function (b) { b.classList.remove("is-sel"); });
                        opt.classList.add("is-sel");
                        clearErr(f.key);
                    }, ci);
                    return opt;
                }));
                return wrap(f, i, opts);
            }
            case "chips": {
                var sel = Array.isArray(get(f.key)) ? get(f.key).slice() : [];
                var opts = E("div", { class: "pe-opts" }, (f.options || []).map(function (o, ci) {
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
                var ctl = E("div", { class: "pe-control pe-control--input", "data-key": f.key }, [input, f.unit ? E("span", { class: "pe-unit", text: f.unit }) : null]);
                input.addEventListener("focus", function () { ctl.classList.add("is-focus"); });
                input.addEventListener("blur", function () { ctl.classList.remove("is-focus"); });
                return wrap(f, i, ctl);
            }
        }
    }

    /* ---------- validation + navigation ---------- */
    function isEmpty(v) { return v == null || v === "" || (Array.isArray(v) && v.length === 0); }
    function clearErr(key) { var el = stage.querySelector('.pe-field[data-key="' + cssEsc(key) + '"]'); if (el) el.classList.remove("is-error"); }
    function cssEsc(s) { return String(s).replace(/"/g, '\\"'); }

    function validateSection(sec) {
        var ok = true, first = null;
        (sec.fields || []).forEach(function (f) {
            if (f.required && isEmpty(get(f.key))) {
                ok = false;
                var el = stage.querySelector('.pe-field[data-key="' + cssEsc(f.key) + '"]');
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
        var main = stage.querySelector("[data-pe-main]");
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
            return E("div", { class: "pe-sumrow" }, [
                E("div", { class: "pe-sumrow__head" }, [
                    E("div", { class: "pe-num" }, [E("span", { text: i + 1 }), E("b", { text: s.eyebrow })]),
                    E("button", { class: "pe-edit", type: "button", html: "Edit &#9998;", onclick: function () { navigate(i); } })
                ]),
                E("div", { class: "pe-sumchips" }, (vals.length ? vals : ["—"]).map(function (v) { return E("span", { class: "pe-sumchip", text: v }); }))
            ]);
        });
        var newMain = E("div", { class: "pe-main", "data-pe-main": "" }, [
            E("p", { class: "pe-sec-eyebrow", text: "Final step · Review" }),
            E("h2", { class: "pe-sec-title", text: "Review & submit" }),
            E("p", { class: "pe-sec-sub", text: "Check your answers — you can edit any section before we evaluate your profile." }),
            E("div", { class: "pe-review" }, [
                E("div", { class: "pe-summary" }, rows),
                E("div", { class: "pe-result" }, [
                    E("p", { class: "pe-result__eyebrow", text: "ALMOST THERE" }),
                    E("h3", { style: "margin:0;font-size:24px;font-weight:800;font-family:'Cormorant Garamond',Georgia,serif", text: "Submit for an expert evaluation" }),
                    E("p", { class: "pe-result__p", text: "Our advisors will personally evaluate your profile and get back to you with detailed, tailored guidance." }),
                    E("div", { class: "pe-result__rule" }),
                    E("div", { class: "pe-result__li", html: "&#9989; &nbsp; Reviewed by an ODA advisor" }),
                    E("div", { class: "pe-result__li", html: "&#127891; &nbsp; Best-fit schools & next steps" }),
                    E("div", { class: "pe-result__li", html: "&#9993;&#65039; &nbsp; We’ll reach out to you shortly" }),
                    E("div", { class: "pe-result__spacer" }),
                    E("button", { class: "pe-btn pe-btn--white", type: "button", html: "Evaluate my profile &nbsp;&rarr;", onclick: submit }),
                    E("p", { class: "pe-result__note", text: "Free · No spam · We’ll be in touch" })
                ])
            ]),
            E("div", { class: "pe-foot" }, [
                E("span", { class: "pe-save" }, [E("i", {}), "Progress saved automatically"]),
                E("div", { class: "pe-nav" }, [E("button", { class: "pe-btn pe-btn--ghost", type: "button", html: "&larr; Back", onclick: function () { navigate(arr.length - 1); } })])
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
        var confetti = E("div", { class: "pe-confetti" });
        var colors = ["#ff5e32", "#ff8a5c", "#f7da82", "#1a0088", "#2a16a0", "#c7924a"];
        for (var i = 0; i < 18; i++) {
            confetti.appendChild(E("i", { style: "left:" + (Math.random() * 100).toFixed(1) + "%;background:" + colors[i % colors.length] + ";animation-delay:" + (Math.random() * .5).toFixed(2) + "s;transform:rotate(" + Math.floor(Math.random() * 360) + "deg)" }));
        }
        var modal = E("div", { class: "pe-modal", role: "dialog", "aria-modal": "true", "aria-label": "Profile submitted" }, [
            E("div", { class: "pe-modal__card" }, [
                confetti,
                E("button", { class: "pe-modal__close", type: "button", "aria-label": "Close", html: "&times;", onclick: function () { modal.remove(); } }),
                E("div", { class: "pe-check" }),
                E("h2", { text: "Thank you — your profile is in!" }),
                E("p", { text: "Our team will evaluate your profile and get back to you with detailed, tailored guidance. Stay tuned — we’ll reach out shortly." }),
                E("div", { class: "pe-modal__actions" }, [
                    E("a", { class: "pe-btn pe-btn--primary", href: "/", html: "Back to home" }),
                    E("a", { class: "pe-btn pe-btn--ghost", href: "/study-abroad", text: "Explore destinations" })
                ])
            ])
        ]);
        modal.addEventListener("click", function (e) { if (e.target === modal) modal.remove(); });
        root.appendChild(modal);
    }

    /* ===================== BOOT ===================== */
    var resuming = state.section > 0 || Object.keys(state.answers).length > 0;
    if (resuming) { state.started = true; renderWizard(); } else renderEntry();
})();
