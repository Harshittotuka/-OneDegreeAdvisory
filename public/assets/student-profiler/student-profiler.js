/* ==========================================================================
   Student Profiler module — wizard engine (isolated, no globals).

   The EXACT Student Profiler questions (degree-adaptive set), rendered in the
   clean light/orange ODA form design. Reads window.__PROFILER__ (config +
   state), renders the degree picker, plays a circular expand reveal on
   selection, then drives the degree-adaptive section-by-section wizard with
   staggered key-drop field animations, a locked progress stepper, review and
   submit. There is NO scoring: on submit the profile is handed to the team for
   a manual review.

   NOTE: progress is NOT cached. Nothing is persisted to the server as you go —
   the wizard always starts fresh; only the final submit records the profile.
   ========================================================================== */
(function () {
    "use strict";

    var DATA = window.__PROFILER__;
    if (!DATA || !DATA.config) return;
    var root = document.querySelector("[data-sp-root]");
    var stage = root && root.querySelector("[data-sp-stage]");
    if (!stage) return;

    var cfg = DATA.config;
    var S = DATA.state || {};
    var state = {
        degree: S.degree || null,
        section: typeof S.section === "number" ? S.section : 0,
        // Always a plain object. PHP serialises an empty answers map as a JSON
        // array ([]), which would arrive here as an Array — and JSON.stringify
        // silently DROPS the string-keyed properties we set on an array, so
        // answers would never persist. Coerce any array (only ever the empty
        // case) to {} so set()/save() round-trip correctly.
        answers: (S.answers && typeof S.answers === "object" && !Array.isArray(S.answers)) ? S.answers : {},
        // Lead contact (name/email/phone), captured on the review screen. Same
        // array-vs-object guard as answers so it round-trips through the session.
        contact: (S.contact && typeof S.contact === "object" && !Array.isArray(S.contact)) ? S.contact : {}
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
            body: JSON.stringify({ action: action || "save", degree: state.degree, section: state.section, answers: state.answers, contact: state.contact })
        }).then(function (r) { return r.ok ? r.json() : null; }).catch(function () { return null; });
    }

    /* ===================== ENTRY (degree select) ===================== */
    // Same first step as the Student Profiler: pick a degree level, which then
    // drives the degree-adaptive question set. Rendered in the Evaluator's
    // light/indigo design language (sp- card style).
    function renderEntry() {
        stage.innerHTML = "";
        var cards = (cfg.degreeOrder || []).map(function (key, i) {
            var d = cfg.degrees[key];
            return E("button", { class: "sp-card", type: "button", "data-featured": d.featured ? "1" : "0", style: "--i:" + i, "aria-label": "Choose " + d.label,
                onclick: function (e) { selectDegree(key, e.currentTarget); } }, [
                d.featured ? E("span", { class: "sp-card__badge", html: "&#9733; MOST CHOSEN" }) : null,
                E("span", { class: "sp-card__chip", text: d.initial }),
                E("span", { class: "sp-card__title", text: d.label }),
                E("p", { class: "sp-card__tag", text: d.tag }),
                E("div", { class: "sp-card__rule" }),
                E("p", { class: "sp-card__ex", text: d.examples }),
                E("span", { class: "sp-card__cta", html: "Choose &nbsp;&rarr;" })
            ]);
        });
        stage.appendChild(E("section", { class: "sp-entry" }, [
            E("div", { class: "sp-entry__inner" }, [
                E("span", { class: "sp-eyebrow", html: "ODA &middot; STUDENT PROFILER" }),
                E("h1", { text: "What would you like to study abroad?" }),
                E("p", { class: "sp-entry__sub", text: "Pick your degree level to begin. We’ll tailor every question to your path — it only takes a few minutes." }),
                E("div", { class: "sp-cards" }, cards),
                E("p", { class: "sp-entry__note", text: "Free · No spam · Takes a few minutes" })
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
        var reveal = E("div", { class: "sp-reveal", style: "--ox:" + ox + ";--oy:" + oy }, [
            E("div", {}, [
                E("div", { class: "sp-reveal__mark", text: d.initial }),
                E("h2", { class: "sp-reveal__t", text: d.label }),
                E("p", { class: "sp-reveal__s", text: "Tailoring your questionnaire…" })
            ])
        ]);
        root.appendChild(reveal);
        var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        requestAnimationFrame(function () { requestAnimationFrame(function () { reveal.classList.add("is-on"); }); });
        setTimeout(function () {
            renderWizard();
            reveal.classList.add("is-out");
            setTimeout(function () { reveal.remove(); }, reduce ? 0 : 400);
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
        window.scrollTo({ top: 0 });
    }

    /* ===================== WIZARD SHELL ===================== */
    function renderWizard() {
        var d = cfg.degrees[state.degree] || {};
        stage.innerHTML = "";
        var rail = E("div", { class: "sp-rail", "data-sp-rail": "" });
        var main = E("div", { class: "sp-main", "data-sp-main": "" });
        stage.appendChild(E("section", { class: "sp-wizard" }, [
            E("header", { class: "sp-topbar" }, [
                E("div", { class: "sp-brand" }, [
                    E("span", { class: "sp-brand__mark", html: "1&deg;" }),
                    E("span", { class: "sp-brand__name", text: "ODA Profiler" }),
                    E("span", { class: "sp-degchip", text: d.label || "" })
                ]),
                E("div", { class: "sp-topbar__right" }, [
                    E("span", { class: "sp-step-count", "data-sp-count": "" }),
                    E("button", { class: "sp-exit", type: "button", html: "&#8592;&nbsp; Select course", onclick: backToEntry })
                ])
            ]),
            E("div", { class: "sp-body" }, [rail, main])
        ]));
        buildRail();
        renderSectionInto();
    }

    function buildRail() {
        var rail = stage.querySelector("[data-sp-rail]");
        if (!rail) return;
        rail.innerHTML = "";
        // Top horizontal stepper (desktop/tablet).
        var steps = E("div", { class: "sp-steps" }, sects().map(function (s, i) {
            var li = E("div", { class: "sp-stepli", "data-step": i }, [
                E("span", { class: "sp-stepdot", "data-dot": i }),
                E("div", { class: "sp-steptxt" }, [E("b", { text: s.eyebrow }), E("span", { "data-status": i })])
            ]);
            li.addEventListener("click", function () { if (i < state.section) navigate(i); });
            return li;
        }));
        // Compact progress bar (mobile) — mirrors the stepper state.
        var progress = E("div", { class: "sp-progress" }, [
            E("div", { class: "sp-progress__txt" }, [E("span", { "data-sp-ptext": "" }), E("b", { "data-sp-ppct": "" })]),
            E("div", { class: "sp-progress__bar" }, [E("div", { class: "sp-progress__fill", "data-sp-pfill": "" })])
        ]);
        rail.appendChild(steps);
        rail.appendChild(progress);
        updateRail();
    }

    function updateRail() {
        var total = sects().length;
        var atReview = state.section >= total;
        var count = stage.querySelector("[data-sp-count]");
        if (count) count.textContent = atReview ? "Review" : "Step " + (state.section + 1) + " of " + total;
        sects().forEach(function (s, i) {
            var li = stage.querySelector('.sp-stepli[data-step="' + i + '"]');
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
        var ptext = stage.querySelector("[data-sp-ptext]");
        var ppct = stage.querySelector("[data-sp-ppct]");
        var pfill = stage.querySelector("[data-sp-pfill]");
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
        // Skip fields flagged hidden (e.g. the standalone "Overall …" box that
        // the engscore widget has absorbed) — they stay in the config for the
        // data model but are never rendered.
        var fieldEls = (sec.fields || []).filter(function (f) { return !f.hidden; }).map(function (f, k) { return renderField(f, k); });

        var newMain = E("div", { class: "sp-main", "data-sp-main": "" }, [
            E("p", { class: "sp-sec-eyebrow", text: "Section " + (i + 1) + " of " + arr.length + " · " + sec.eyebrow }),
            E("h2", { class: "sp-sec-title", text: sec.title }),
            sec.subtitle ? E("p", { class: "sp-sec-sub", text: sec.subtitle }) : null,
            E("div", { class: "sp-fields" }, fieldEls),
            E("div", { class: "sp-foot" }, [
                E("span", { class: "sp-save" }),
                E("div", { class: "sp-nav" }, [
                    E("button", { class: "sp-btn sp-btn--ghost", type: "button", disabled: i === 0 ? "" : false, html: "&larr; Back", onclick: function () { if (i > 0) navigate(i - 1); } }),
                    E("button", { class: "sp-btn sp-btn--primary", type: "button", html: (last ? "Review" : "Continue") + " &nbsp;&rarr;", onclick: function () { onContinue(i); } })
                ])
            ])
        ]);
        swapMain(newMain);
    }

    function swapMain(newMain) {
        var old = stage.querySelector("[data-sp-main]");
        if (old) old.parentNode.replaceChild(newMain, old);
    }

    /* ---------- field renderers ---------- */
    function get(key) { return state.answers[key]; }
    function set(key, val) { state.answers[key] = val; }

    function wrap(f, idx, control) {
        return E("div", { class: "sp-field", "data-key": f.key, style: "--i:" + idx }, [
            E("label", { class: "sp-field__label", text: f.label }),
            control,
            f.help ? E("p", { class: "sp-field__help", text: f.help }) : null
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
        return E("button", { type: "button", class: "sp-opt sp-opt--card" + (on ? " is-sel" : ""), style: "--c:" + (idx || 0), onclick: onclick }, [
            icon ? E("span", { class: "sp-opt__icon", text: icon }) : null,
            E("span", { class: "sp-opt__txt", text: label }),
            E("span", { class: "sp-opt__badge", html: "&#10003;" })
        ]);
    }

    function renderField(f, i) {
        switch (f.type) {
            case "radio": {
                var cur = get(f.key);
                var opts = E("div", { class: "sp-opts" }, (f.options || []).map(function (o, ci) {
                    var label = optLabel(o);
                    var opt = optCard(label, optIcon(o), cur === label, function () {
                        set(f.key, label);
                        opts.querySelectorAll(".sp-opt").forEach(function (b) { b.classList.remove("is-sel"); });
                        opt.classList.add("is-sel");
                        clearErr(f.key);
                    }, ci);
                    return opt;
                }));
                return wrap(f, i, opts);
            }
            case "chips": {
                var sel = Array.isArray(get(f.key)) ? get(f.key).slice() : [];
                var opts = E("div", { class: "sp-opts" }, (f.options || []).map(function (o, ci) {
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
            case "engscore": return wrap(f, i, renderEng(f));
            case "text": case "email": case "tel": default: {
                var input = E("input", { type: (f.type === "text" ? "text" : f.type), placeholder: f.placeholder || "", value: get(f.key) || "" });
                input.addEventListener("input", function () { set(f.key, input.value); clearErr(f.key); });
                var ctl = E("div", { class: "sp-control sp-control--input", "data-key": f.key }, [input, f.unit ? E("span", { class: "sp-unit", text: f.unit }) : null]);
                input.addEventListener("focus", function () { ctl.classList.add("is-focus"); });
                input.addEventListener("blur", function () { ctl.classList.remove("is-focus"); });
                return wrap(f, i, ctl);
            }
        }
    }

    /* ---------- English sub-scores widget (test type + L/R/W/S) ---------- */
    // The stored value is a flat array of "<TEST> <Skill>: <score>" strings that
    // can span ALL three tests at once (IELTS + TOEFL + PTE), so a candidate may
    // enter and submit scores for any combination — each renders as a clear,
    // self-describing chip in review/admin with no extra handling.
    var ENG_TESTS = [
        { code: "IELTS", icon: "📘", scale: "/ 9",  max: "9",  step: "0.5", oScale: "/ 9",   oMax: "9",   oStep: "0.5" },
        { code: "TOEFL", icon: "📗", scale: "/ 30", max: "30", step: "1",   oScale: "/ 120", oMax: "120", oStep: "1" },
        { code: "PTE",   icon: "📙", scale: "/ 90", max: "90", step: "1",   oScale: "/ 90",  oMax: "90",  oStep: "1" }
    ];
    var ENG_COMPS = [
        { code: "L", label: "Listening", icon: "👂" },
        { code: "R", label: "Reading",   icon: "📖" },
        { code: "W", label: "Writing",   icon: "✍️" },
        { code: "S", label: "Speaking",  icon: "🗣️" }
    ];

    // Parse the stored "<TEST> <Skill>: <score>" array back into a per-test map:
    // { IELTS: { Overall: "7.5", Listening: "7" }, TOEFL: { … }, … }.
    function engParseAll(v) {
        var byTest = {};
        if (Array.isArray(v)) v.forEach(function (s) {
            var str = String(s), ci = str.indexOf(":");
            if (ci === -1) return;                          // ignore stray tokens
            var left = str.slice(0, ci).trim(), val = str.slice(ci + 1).trim();
            var sp = left.indexOf(" ");
            if (sp === -1) return;                           // need "<TEST> <Skill>"
            var test = left.slice(0, sp).trim(), label = left.slice(sp + 1).trim();
            (byTest[test] || (byTest[test] = {}))[label] = val;
        });
        return byTest;
    }
    // Serialise EVERY test that has data — "Overall" first, then each filled skill,
    // each prefixed with the test code so they render as self-describing chips.
    function engSerializeAll(tests, scoresByTest, comps, hasOverall) {
        var arr = [];
        tests.forEach(function (t) {
            var sc = scoresByTest[t.code] || {};
            if (hasOverall) { var o = (sc.Overall || "").trim(); if (o !== "") arr.push(t.code + " Overall: " + o); }
            comps.forEach(function (c) {
                var val = (sc[c.label] || "").trim();
                if (val !== "") arr.push(t.code + " " + c.label + ": " + val);
            });
        });
        return arr;
    }
    // True when ANY test has a non-empty overall score.
    function engHasOverall(f) {
        var byTest = engParseAll(get(f.key));
        return Object.keys(byTest).some(function (t) { return (byTest[t].Overall || "").trim() !== ""; });
    }

    function renderEng(f) {
        var tests = f.tests || ENG_TESTS;
        var comps = f.components || ENG_COMPS;
        var hasOverall = f.overall !== false;
        // Per-test score memory: every test keeps its OWN scores, and ALL filled
        // tests are saved together (a candidate can submit IELTS + TOEFL + PTE at
        // once). The tabs just switch which test you're currently editing.
        var seeded = engParseAll(get(f.key));
        var scoresByTest = {};
        tests.forEach(function (t) { scoresByTest[t.code] = seeded[t.code] || {}; });
        // Default to the first test that already has data, else the first test
        // (IELTS) so its inputs + scales show immediately without forcing a pick.
        var firstFilled = null;
        tests.forEach(function (t) { if (!firstFilled && Object.keys(scoresByTest[t.code]).length) firstFilled = t.code; });
        var cur = { test: firstFilled || (tests[0] && tests[0].code) || "" };
        var scaleEls = {}, inputEls = {}, inputLabel = {}, testBtnByCode = {};

        function curScores() {
            if (!scoresByTest[cur.test]) scoresByTest[cur.test] = {};
            return scoresByTest[cur.test];
        }
        function activeTest() {
            for (var i = 0; i < tests.length; i++) if (tests[i].code === cur.test) return tests[i];
            return null;
        }
        function tune(code, scale, max, step) {
            var span = scaleEls[code], inp = inputEls[code];
            if (!span || !inp) return;
            if (activeTest()) { span.textContent = scale; inp.setAttribute("max", max); inp.setAttribute("step", step); }
            else { span.textContent = ""; inp.removeAttribute("max"); }
            span.classList.remove("is-pop"); void span.offsetWidth; span.classList.add("is-pop");
        }
        function applyScale() {
            var t = activeTest();
            if (hasOverall) tune("__overall", t && t.oScale, t && t.oMax, t && t.oStep);
            comps.forEach(function (c) { tune(c.code, t && t.scale, t && t.max, t && t.step); });
        }
        // Save EVERY filled test together (not just the active one).
        function commit() { set(f.key, engSerializeAll(tests, scoresByTest, comps, hasOverall)); clearErr(f.key); updateTabBadges(); }
        // Repaint every input from the active test's stored scores (on tab switch).
        function repaint() {
            var sc = curScores();
            Object.keys(inputEls).forEach(function (code) { inputEls[code].value = sc[inputLabel[code]] || ""; });
        }
        // Flag tabs that already hold scores with a ✓, so multi-test entry is obvious.
        function tabHasData(code) {
            var sc = scoresByTest[code] || {};
            return Object.keys(sc).some(function (k) { return (sc[k] || "").trim() !== ""; });
        }
        function updateTabBadges() {
            tests.forEach(function (t) { if (testBtnByCode[t.code]) testBtnByCode[t.code].classList.toggle("is-filled", tabHasData(t.code)); });
        }

        // Build one labelled score input (Overall or a skill).
        function cell(code, label, icon, extraClass) {
            var inp = E("input", { class: "sp-eng__in", type: "text", inputmode: "decimal", value: curScores()[label] || "",
                "aria-label": label + " score", placeholder: "—" });
            inputEls[code] = inp;
            inputLabel[code] = label;
            var scale = E("span", { class: "sp-eng__scale" });
            scaleEls[code] = scale;
            inp.addEventListener("input", function () { curScores()[label] = inp.value; commit(); });
            return E("label", { class: "sp-eng__cell" + (extraClass ? " " + extraClass : "") }, [
                E("span", { class: "sp-eng__cellhead" }, [
                    E("i", { class: "sp-eng__cellicon", text: icon }),
                    E("span", { text: label })
                ]),
                E("div", { class: "sp-eng__inwrap" }, [inp, scale])
            ]);
        }

        var grid;
        var testBtns = tests.map(function (t, ci) {
            var btn = E("button", { type: "button", class: "sp-eng__test" + (cur.test === t.code ? " is-sel" : ""), style: "--c:" + ci,
                "aria-pressed": cur.test === t.code ? "true" : "false",
                onclick: function () {
                    cur.test = t.code;
                    testRow.querySelectorAll(".sp-eng__test").forEach(function (b) { b.classList.remove("is-sel"); b.setAttribute("aria-pressed", "false"); });
                    btn.classList.add("is-sel"); btn.setAttribute("aria-pressed", "true");
                    grid.classList.add("is-live");
                    if (overallEl) overallEl.classList.add("is-live");
                    // Show THIS test's own remembered scores, then apply its scale.
                    repaint();
                    applyScale(); commit();
                } }, [
                E("span", { class: "sp-eng__testicon", text: t.icon }),
                E("span", { text: t.code })
            ]);
            testBtnByCode[t.code] = btn;
            return btn;
        });
        var testRow = E("div", { class: "sp-eng__tests", role: "group", "aria-label": "Test type" }, testBtns);

        var overallEl = hasOverall ? cell("__overall", "Overall", "⭐", "sp-eng__cell--overall" + (cur.test ? " is-live" : "")) : null;

        grid = E("div", { class: "sp-eng__grid" + (cur.test ? " is-live" : "") },
            comps.map(function (c) { return cell(c.code, c.label, c.icon); }));

        applyScale();
        updateTabBadges();
        return E("div", { class: "sp-eng" }, [testRow, overallEl, grid]);
    }

    /* ---------- validation + navigation ---------- */
    function isEmpty(v) { return v == null || v === "" || (Array.isArray(v) && v.length === 0); }
    function clearErr(key) { var el = stage.querySelector('.sp-field[data-key="' + cssEsc(key) + '"]'); if (el) el.classList.remove("is-error"); }
    function cssEsc(s) { return String(s).replace(/"/g, '\\"'); }

    function validateSection(sec) {
        var ok = true, first = null;
        (sec.fields || []).forEach(function (f) {
            if (f.hidden) return;
            // engscore: a mandatory section requires the OVERALL score to be filled.
            var bad = (f.type === "engscore" && f.overallRequired)
                ? !engHasOverall(f)
                : (f.required && isEmpty(get(f.key)));
            if (bad) {
                ok = false;
                var el = stage.querySelector('.sp-field[data-key="' + cssEsc(f.key) + '"]');
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
        var main = stage.querySelector("[data-sp-main]");
        var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        if (main && !reduce) main.classList.add("is-leaving");
        setTimeout(function () {
            state.section = target;
            if (target >= reviewIndex()) renderReview(); else renderSectionInto();
            updateRail();
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

    /* ---------- lead contact (name / email / phone) ---------- */
    function cfield(labelText, key, type, required) {
        var input = E("input", { class: "sp-cinput", type: type, value: (state.contact[key] || ""),
            placeholder: labelText, autocomplete: key === "name" ? "name" : key, required: required,
            "aria-label": labelText, "aria-required": required ? "true" : "false", "aria-invalid": "false" });
        var field = E("label", { class: "sp-cfield" }, [
            E("span", { class: "sp-cfield__lab", text: labelText + (required ? " *" : " (optional)") }),
            input,
            E("span", { class: "sp-cfield__err", "data-cerr": key })
        ]);
        input.addEventListener("input", function () { state.contact[key] = input.value; field.classList.remove("is-error"); input.setAttribute("aria-invalid", "false"); });
        return field;
    }

    function contactCard() {
        return E("div", { class: "sp-contact" }, [
            cfield("Full name", "name", "text", true),
            cfield("Email", "email", "email", true),
            cfield("Phone", "phone", "tel", true)
        ]);
    }

    function setCErr(key, msg) {
        var span = stage.querySelector('[data-cerr="' + key + '"]');
        if (span) span.textContent = msg;
        var fld = span && span.parentNode;
        if (fld) {
            fld.classList.add("is-error");
            var input = fld.querySelector(".sp-cinput");
            if (input) input.setAttribute("aria-invalid", "true");
        }
        return fld;
    }

    // Name, valid email, and phone are required. Returns false (and flags the
    // fields) when the lead details are incomplete.
    function validateContact() {
        Array.prototype.forEach.call(stage.querySelectorAll(".sp-cfield"), function (f) {
            f.classList.remove("is-error");
            var input = f.querySelector(".sp-cinput");
            if (input) input.setAttribute("aria-invalid", "false");
        });
        var bad = [];
        if (!(state.contact.name || "").trim()) bad.push(setCErr("name", "Please enter your name"));
        if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test((state.contact.email || "").trim())) bad.push(setCErr("email", "Please enter a valid email"));
        if (!(state.contact.phone || "").trim()) bad.push(setCErr("phone", "Please enter your phone number"));
        bad = bad.filter(Boolean);
        if (bad.length) { bad[0].scrollIntoView({ behavior: "smooth", block: "center" }); return false; }
        return true;
    }

    function renderReview() {
        var arr = sects();
        var rows = arr.map(function (s, i) {
            var vals = [];
            (s.fields || []).forEach(function (f) { var a = answerText(f); if (a) vals = vals.concat(a.filter(Boolean)); });
            return E("div", { class: "sp-sumrow" }, [
                E("div", { class: "sp-sumrow__head" }, [
                    E("div", { class: "sp-num" }, [E("span", { text: i + 1 }), E("b", { text: s.eyebrow })]),
                    E("button", { class: "sp-edit", type: "button", html: "Edit &#9998;", onclick: function () { navigate(i); } })
                ]),
                E("div", { class: "sp-sumchips" }, (vals.length ? vals : ["—"]).map(function (v) { return E("span", { class: "sp-sumchip", text: v }); }))
            ]);
        });
        var newMain = E("div", { class: "sp-main", "data-sp-main": "" }, [
            E("p", { class: "sp-sec-eyebrow", text: "Final step · Review" }),
            E("h2", { class: "sp-sec-title", text: "Review & submit" }),
            E("p", { class: "sp-sec-sub", text: "Check your answers — you can edit any section before we evaluate your profile." }),
            E("div", { class: "sp-review" }, [
                E("div", { class: "sp-summary" }, rows),
                E("div", { class: "sp-result" }, [
                    E("p", { class: "sp-result__eyebrow", text: "ALMOST THERE" }),
                    E("h3", { style: "margin:0;font-size:24px;font-weight:800;font-family:'Cormorant Garamond',Georgia,serif", text: "Submit for an expert evaluation" }),
                    E("p", { class: "sp-result__p", text: "Our advisors will personally evaluate your profile and get back to you with detailed, tailored guidance." }),
                    E("div", { class: "sp-result__rule" }),
                    E("div", { class: "sp-result__li", html: "&#9989; &nbsp; Reviewed by an ODA advisor" }),
                    E("div", { class: "sp-result__li", html: "&#127891; &nbsp; Best-fit schools & next steps" }),
                    E("div", { class: "sp-result__li", html: "&#9993;&#65039; &nbsp; We’ll reach out to you shortly" }),
                    E("div", { class: "sp-result__spacer" }),
                    contactCard(),
                    E("button", { class: "sp-btn sp-btn--white sp-submit", type: "button", html: "<span>Evaluate my profile</span><span class=\"sp-submit__arrow\" aria-hidden=\"true\">&rarr;</span>", onclick: submit }),
                    E("p", { class: "sp-result__note", text: "Free · No spam · We’ll be in touch" })
                ])
            ]),
            E("div", { class: "sp-foot" }, [
                E("span", { class: "sp-save" }),
                E("div", { class: "sp-nav" }, [E("button", { class: "sp-btn sp-btn--ghost", type: "button", html: "&larr; Back", onclick: function () { navigate(arr.length - 1); } })])
            ])
        ]);
        swapMain(newMain);
    }

    /* ===================== SUBMIT + SUCCESS POPUP ===================== */
    function submit(e) {
        var btn = e && e.currentTarget;
        if (!validateContact()) return;
        var originalHtml = btn ? btn.innerHTML : "";
        if (btn) { btn.disabled = true; btn.innerHTML = "Submitting…"; }
        save("submit").then(function () {
            if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
            showSuccess();
        });
    }

    // No score, no rating — a celebratory "we'll evaluate & get back to you" popup.
    function showSuccess() {
        var confetti = E("div", { class: "sp-confetti" });
        var colors = ["#ff5e32", "#ff8a5c", "#f7da82", "#1a0088", "#2a16a0", "#c7924a"];
        for (var i = 0; i < 18; i++) {
            confetti.appendChild(E("i", { style: "left:" + (Math.random() * 100).toFixed(1) + "%;background:" + colors[i % colors.length] + ";animation-delay:" + (Math.random() * .5).toFixed(2) + "s;transform:rotate(" + Math.floor(Math.random() * 360) + "deg)" }));
        }
        var modal = E("div", { class: "sp-modal", role: "dialog", "aria-modal": "true", "aria-label": "Profile submitted" }, [
            E("div", { class: "sp-modal__card" }, [
                confetti,
                E("button", { class: "sp-modal__close", type: "button", "aria-label": "Close", html: "&times;", onclick: function () { modal.remove(); } }),
                E("div", { class: "sp-check" }),
                E("h2", { text: "Thank you — your profile is in!" }),
                E("p", { text: "Our team will evaluate your profile and get back to you with detailed, tailored guidance. Stay tuned — we’ll reach out shortly." }),
                E("div", { class: "sp-modal__actions" }, [
                    E("a", { class: "sp-btn sp-btn--primary", href: "/", html: "Back to home" }),
                    E("a", { class: "sp-btn sp-btn--ghost", href: "/study-abroad", text: "Explore destinations" })
                ])
            ])
        ]);
        modal.addEventListener("click", function (e) { if (e.target === modal) modal.remove(); });
        root.appendChild(modal);
    }

    /* ===================== BOOT ===================== */
    // Progress is not cached, so always begin at the degree picker.
    renderEntry();
})();
