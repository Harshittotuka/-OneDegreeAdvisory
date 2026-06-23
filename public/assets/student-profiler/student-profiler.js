/* ==========================================================================
   Student Profiler module — wizard engine (isolated, no globals).

   Reads window.__PROFILER__ (config + restored session state), renders the
   degree-select entry, plays the circular expand reveal on selection, then
   drives the section-by-section wizard with staggered key-drop field
   animations, a locked progress rail (no skipping ahead, no changing degree),
   review and a profile report. Questions/options come verbatim from the
   partner profiler. Answers autosave to the PHP session via /profiler.
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

    /* ===================== ENTRY ===================== */
    function renderEntry() {
        stage.innerHTML = "";
        var cards = (cfg.degreeOrder || []).map(function (key, i) {
            var d = cfg.degrees[key];
            return E("button", { class: "sp-card", type: "button", "data-accent": d.accent, "data-featured": d.featured ? "1" : "0", style: "--i:" + i, "aria-label": "Choose " + d.label,
                onclick: function (e) { selectDegree(key, e.currentTarget); } }, [
                d.featured ? E("span", { class: "sp-card__badge", html: "★ MOST CHOSEN" }) : null,
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
                E("div", { class: "sp-cards" }, cards)
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
        var reveal = E("div", { class: "sp-reveal", "data-accent": d.accent, style: "--ox:" + ox + ";--oy:" + oy }, [
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
        var d = cfg.degrees[state.degree];
        stage.innerHTML = "";
        var rail = E("aside", { class: "sp-rail", "data-sp-rail": "" });
        var main = E("div", { class: "sp-main", "data-sp-main": "" });
        stage.appendChild(E("section", { class: "sp-wizard" }, [
            E("div", { class: "sp-strip" }),
            E("header", { class: "sp-topbar" }, [
                E("div", { class: "sp-brand" }, [
                    E("span", { class: "sp-brand__mark", html: "1&deg;" }),
                    E("span", { class: "sp-brand__name", text: "ODA Profiler" }),
                    E("span", { class: "sp-degchip", text: d.label })
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
        rail.appendChild(E("p", { class: "sp-rail__eyebrow", text: "YOUR JOURNEY" }));
        sects().forEach(function (s, i) {
            var li = E("div", { class: "sp-stepli", "data-step": i }, [
                E("span", { class: "sp-stepdot", "data-dot": i }),
                E("div", { class: "sp-steptxt" }, [E("b", { text: s.eyebrow }), E("span", { "data-status": i })])
            ]);
            li.addEventListener("click", function () { if (i < state.section) navigate(i); });
            rail.appendChild(li);
        });
        rail.appendChild(E("p", { class: "sp-rail__note", text: "Sections unlock in order — you can’t skip ahead. Use “Select course” (top right) to change your degree anytime." }));
        updateRail();
    }

    function updateRail() {
        var total = sects().length;
        var count = stage.querySelector("[data-sp-count]");
        if (count) count.textContent = state.section >= total ? "Review" : "Step " + (state.section + 1) + " of " + total;
        sects().forEach(function (s, i) {
            var li = stage.querySelector('.sp-stepli[data-step="' + i + '"]');
            var dot = stage.querySelector('[data-dot="' + i + '"]');
            var st = stage.querySelector('[data-status="' + i + '"]');
            if (!li) return;
            var done = i < state.section, active = i === state.section;
            li.classList.toggle("is-done", done);
            li.classList.toggle("is-active", active);
            li.classList.toggle("is-locked", !done && !active);
            if (dot) dot.innerHTML = done ? "&#10003;" : (active ? (i + 1) : "&#128274;");
            if (st) st.textContent = done ? "Completed" : (active ? "In progress" : "Locked");
        });
    }

    /* ===================== SECTION RENDER ===================== */
    function renderSectionInto() {
        var arr = sects();
        if (state.section >= arr.length) return renderReview();
        var i = state.section;
        var sec = arr[i];
        var last = i === arr.length - 1;
        var fieldEls = (sec.fields || []).map(function (f, k) { return renderField(f, k); });

        var newMain = E("div", { class: "sp-main", "data-sp-main": "" }, [
            E("p", { class: "sp-sec-eyebrow", text: "Section " + (i + 1) + " of " + arr.length + " · " + sec.eyebrow }),
            E("h2", { class: "sp-sec-title", text: sec.title }),
            sec.subtitle ? E("p", { class: "sp-sec-sub", text: sec.subtitle }) : null,
            E("div", { class: "sp-fields" }, fieldEls),
            E("div", { class: "sp-foot" }, [
                E("span", { class: "sp-save" }, [E("i", {}), "Progress saved automatically"]),
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

    function renderField(f, i) {
        switch (f.type) {
            case "radio": {
                var cur = get(f.key);
                var group = E("div", { class: "sp-chips sp-radio" }, (f.options || []).map(function (o) {
                    return E("button", { type: "button", class: "sp-chip" + (cur === o ? " is-sel" : ""), html: (cur === o ? "&#10003; " : "") + o, onclick: function (e) {
                        set(f.key, o);
                        group.querySelectorAll(".sp-chip").forEach(function (b) { b.classList.remove("is-sel"); });
                        e.currentTarget.classList.add("is-sel"); e.currentTarget.innerHTML = "&#10003; " + o;
                        clearErr(f.key);
                    } });
                }));
                return wrap(f, i, group);
            }
            case "chips": {
                var sel = Array.isArray(get(f.key)) ? get(f.key).slice() : [];
                var chips = E("div", { class: "sp-chips" }, (f.options || []).map(function (o) {
                    var on = sel.indexOf(o) > -1;
                    return E("button", { type: "button", class: "sp-chip" + (on ? " is-sel" : ""), html: (on ? "&#10003; " : "") + o, onclick: function (e) {
                        var idx = sel.indexOf(o);
                        if (idx > -1) { sel.splice(idx, 1); e.currentTarget.classList.remove("is-sel"); e.currentTarget.innerHTML = o; }
                        else { sel.push(o); e.currentTarget.classList.add("is-sel"); e.currentTarget.innerHTML = "&#10003; " + o; }
                        set(f.key, sel.slice()); clearErr(f.key);
                    } });
                }));
                return wrap(f, i, chips);
            }
            case "select": {
                var curv = get(f.key) || "";
                var disp = E("span", { text: curv || "Select…", style: curv ? "" : "color:var(--muted)" });
                var select = E("select", {}, [E("option", { value: "", text: "Select…" })].concat((f.options || []).map(function (o) {
                    return E("option", { value: o, text: o, selected: o === curv ? "" : false });
                })));
                select.addEventListener("change", function () { set(f.key, select.value); disp.textContent = select.value || "Select…"; disp.setAttribute("style", select.value ? "" : "color:var(--muted)"); clearErr(f.key); });
                return wrap(f, i, E("div", { class: "sp-control sp-select-native" }, [disp, E("span", { class: "sp-chev", html: "&#9662;" }), select]));
            }
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

    /* ---------- validation + navigation ---------- */
    function isEmpty(v) { return v == null || v === "" || (Array.isArray(v) && v.length === 0); }
    function clearErr(key) { var el = stage.querySelector('.sp-field[data-key="' + cssEsc(key) + '"]'); if (el) el.classList.remove("is-error"); }
    function cssEsc(s) { return String(s).replace(/"/g, '\\"'); }

    function validateSection(sec) {
        var ok = true, first = null;
        (sec.fields || []).forEach(function (f) {
            if (f.required && isEmpty(get(f.key))) {
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

    /* ---------- lead contact (name / email / phone) ---------- */
    function cfield(labelText, key, type, required) {
        var input = E("input", { class: "sp-cinput", type: type, value: (state.contact[key] || ""),
            placeholder: labelText, autocomplete: key === "name" ? "name" : key, "aria-label": labelText });
        var field = E("label", { class: "sp-cfield" }, [
            E("span", { class: "sp-cfield__lab", text: labelText + (required ? " *" : " (optional)") }),
            input,
            E("span", { class: "sp-cfield__err", "data-cerr": key })
        ]);
        input.addEventListener("input", function () { state.contact[key] = input.value; field.classList.remove("is-error"); });
        input.addEventListener("blur", function () { save("save"); });
        return field;
    }

    function contactCard() {
        return E("div", { class: "sp-contact" }, [
            E("p", { class: "sp-contact__h", text: "Where should we send your report?" }),
            cfield("Full name", "name", "text", true),
            cfield("Email", "email", "email", true),
            cfield("Phone", "phone", "tel", false)
        ]);
    }

    function setCErr(key, msg) {
        var span = stage.querySelector('[data-cerr="' + key + '"]');
        if (span) span.textContent = msg;
        var fld = span && span.parentNode;
        if (fld) fld.classList.add("is-error");
        return fld;
    }

    // name + valid email required; phone optional. Returns false (and flags the
    // fields) when the lead details are incomplete.
    function validateContact() {
        Array.prototype.forEach.call(stage.querySelectorAll(".sp-cfield"), function (f) { f.classList.remove("is-error"); });
        var bad = [];
        if (!(state.contact.name || "").trim()) bad.push(setCErr("name", "Please enter your name"));
        if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test((state.contact.email || "").trim())) bad.push(setCErr("email", "Please enter a valid email"));
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
            E("p", { class: "sp-sec-sub", text: "Check your answers — you can edit any section before we generate your personalised report." }),
            E("div", { class: "sp-review" }, [
                E("div", { class: "sp-summary" }, rows),
                E("div", { class: "sp-result" }, [
                    E("p", { class: "sp-result__eyebrow", text: "ALMOST THERE" }),
                    E("h3", { style: "margin:0;font-size:24px;font-weight:800;font-family:'Cormorant Garamond',Georgia,serif", text: "Submit for an expert review" }),
                    E("p", { class: "sp-result__p", text: "Our advisors will personally review your profile and get back to you with detailed, tailored guidance." }),
                    E("div", { class: "sp-result__rule" }),
                    E("div", { class: "sp-result__li", html: "&#9989; &nbsp; Reviewed by an ODA advisor" }),
                    E("div", { class: "sp-result__li", html: "&#127891; &nbsp; Best-fit universities & next steps" }),
                    E("div", { class: "sp-result__li", html: "&#9993;&#65039; &nbsp; We’ll reach out to you shortly" }),
                    E("div", { class: "sp-result__spacer" }),
                    contactCard(),
                    E("button", { class: "sp-btn sp-btn--white", type: "button", html: "Submit my profile &nbsp;&rarr;", onclick: submit }),
                    E("p", { class: "sp-result__note", text: "Free · No spam · We’ll be in touch" })
                ])
            ]),
            E("div", { class: "sp-foot" }, [
                E("span", { class: "sp-save" }, [E("i", {}), "Progress saved automatically"]),
                E("div", { class: "sp-nav" }, [E("button", { class: "sp-btn sp-btn--ghost", type: "button", html: "&larr; Back", onclick: function () { navigate(arr.length - 1); } })])
            ])
        ]);
        swapMain(newMain);
    }

    /* ===================== SUBMIT + SUCCESS POPUP ===================== */
    function submit(e) {
        var btn = e && e.currentTarget;
        if (!validateContact()) return;
        if (btn) { btn.disabled = true; btn.innerHTML = "Submitting…"; }
        save("submit").then(function () { showSuccess(); });
    }

    // No score, no rating — a celebratory "we'll review & get back to you" popup.
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
                E("p", { text: "Our team will get back to you with a detailed review of your profile. Stay tuned — we’ll reach out shortly." }),
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
    var resuming = state.degree && (state.section > 0 || Object.keys(state.answers).length > 0);
    if (resuming) renderWizard(); else renderEntry();
})();
