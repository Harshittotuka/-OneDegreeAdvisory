/*
 * Journey planner — an admin-panel style app drawn from the JSON payload the
 * server embeds (App\Support\JourneyPlanner::payload). Every edit is a small
 * JSON call. Three readers share it:
 *   counsellor  edits everything (CRM, signed in)
 *   partner     reads everything the counsellor switched on (CRM, signed in)
 *   student     reads the same, and moves the status of their own tasks
 *               (student portal, signed in with their own password)
 * The server enforces all of that again; the page only decides what to offer.
 */
(function () {
    'use strict';

    var P = JSON.parse(document.getElementById('jp-payload').textContent);
    var T = P.template;
    var MODE = P.mode;
    var CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content || P.csrf || '';
    var CORE_DEFS = {}, APP_DEFS = {}, PHASE_OF = {};
    T.phases.forEach(function (p) { p.activities.forEach(function (a) { CORE_DEFS[a.key] = a; PHASE_OF[a.key] = p; }); });
    T.appGroups.forEach(function (g) { g.activities.forEach(function (a) { APP_DEFS[a.key] = a; }); });

    var UI = {
        view: 'dashboard', openPhases: {}, openRows: {}, appId: P.apps[0] ? P.apps[0].id : null,
        owner: 'all', status: 'all', showExcluded: true, dl: 'open', armed: null, busy: false, menu: false,
        credentials: P.credentials || null,
        docTab: 'all', essayId: null, essayDirty: false
    };
    P.documents = P.documents || [];
    var DT = P.docTemplate || { fileCategories: [], essayCategories: [], essayStatuses: [], limits: { maxKb: 10240, extensions: [] } };

    /* ------------------------------------------------------------ helpers */
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function isC() { return MODE === 'counsellor'; }
    function isStudent() { return MODE === 'student'; }
    function studentOwns(owner) { return owner.indexOf('Student') >= 0; }
    function closed(st) { return st === 'Completed' || st === 'Not Applicable'; }
    function canEdit(a) { return isC() || (isStudent() && a.inc && studentOwns(a.owner) && a.status !== 'Not Applicable'); }
    function today() { var n = new Date(); n.setHours(0, 0, 0, 0); return n; }
    function daysUntil(iso) { if (!iso) return null; return Math.round((new Date(iso + 'T00:00:00') - today()) / 86400000); }
    function fmt(iso, opts) { if (!iso) return ''; var d = new Date(iso + 'T00:00:00'); return isNaN(d) ? iso : d.toLocaleDateString('en-GB', opts || { day: 'numeric', month: 'short', year: 'numeric' }); }
    function when(isoTime) { return new Date(isoTime).toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit' }); }
    function stClass(st) { return { 'Completed': 's-done', 'In Progress': 's-prog', 'Submitted': 's-prog', 'Not Applicable': 's-na' }[st] || ''; }
    function stPill(st) { return { 'Completed': 'good', 'In Progress': 'amber', 'Submitted': 'amber', 'Not Applicable': 'na' }[st] || 'wait'; }
    function due(days) {
        if (days == null) return null;
        if (days < 0) return { cls: 'danger', label: Math.abs(days) + (Math.abs(days) === 1 ? ' day late' : ' days late') };
        if (days === 0) return { cls: 'amber', label: 'Due today' };
        if (days <= 14) return { cls: 'amber', label: 'In ' + days + (days === 1 ? ' day' : ' days') };
        if (days <= 60) return { cls: 'wait', label: 'In ' + Math.round(days / 7) + ' weeks' };
        return { cls: 'wait', label: 'In ' + Math.round(days / 30) + ' months' };
    }
    function plural(n, one, many) { return n + ' ' + (n === 1 ? one : many); }
    function initials(name) { return String(name || '?').trim().split(/\s+/).map(function (w) { return w[0]; }).slice(0, 2).join('').toUpperCase(); }

    var ICO = {
        check: '<svg viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M3 7.3l2.6 2.6L11 4.4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        chev: '<svg class="chev" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        plane: '<svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M15 8.7V7.3L9.2 4.6V1.8a1.2 1.2 0 0 0-2.4 0v2.8L1 7.3v1.4l5.8-1.2v3.3l-1.6 1.1v1.1L8 12.4l2.8.6v-1.1l-1.6-1.1V7.5z"/></svg>',
        plus: '<svg viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M7 2.5v9M2.5 7h9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        menu: '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 6h14M3 10h14M3 14h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        dashboard: '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="3" y="3" width="6" height="6" rx="1.6" stroke="currentColor" stroke-width="1.5"/><rect x="11" y="3" width="6" height="4" rx="1.6" stroke="currentColor" stroke-width="1.5"/><rect x="11" y="9" width="6" height="8" rx="1.6" stroke="currentColor" stroke-width="1.5"/><rect x="3" y="11" width="6" height="6" rx="1.6" stroke="currentColor" stroke-width="1.5"/></svg>',
        journey: '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="5" cy="15" r="2" stroke="currentColor" stroke-width="1.5"/><circle cx="15" cy="5" r="2" stroke="currentColor" stroke-width="1.5"/><path d="M7 15h4.5a2.5 2.5 0 0 0 0-5h-3a2.5 2.5 0 0 1 0-5H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
        uni: '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M2.5 7.5L10 3.5l7.5 4-7.5 4-7.5-4z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M5.5 9.2v4.3c1.2 1.2 2.7 1.8 4.5 1.8s3.3-.6 4.5-1.8V9.2" stroke="currentColor" stroke-width="1.5"/></svg>',
        calendar: '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="3" y="4.5" width="14" height="12.5" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M3 8.5h14M7 2.8v3M13 2.8v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
        people: '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="7.5" cy="7" r="2.8" stroke="currentColor" stroke-width="1.5"/><path d="M2.5 16.5c.6-2.8 2.5-4.3 5-4.3s4.4 1.5 5 4.3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="14" cy="7.5" r="2.2" stroke="currentColor" stroke-width="1.5"/><path d="M13.5 12.3c2 0 3.5 1.2 4 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
        help: '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="7.2" stroke="currentColor" stroke-width="1.5"/><path d="M7.8 8a2.3 2.3 0 1 1 3.3 2.1c-.7.3-1.1.9-1.1 1.6v.4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="10" cy="14.4" r=".9" fill="currentColor"/></svg>',
        key: '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="7" cy="12.5" r="3.5" stroke="currentColor" stroke-width="1.5"/><path d="M9.6 10l6-6M13.5 6l2 2M12 7.6l1.5 1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
        back: '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M11.5 5L6.5 10l5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        doc: '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5.5 2.8h6l3.7 3.7v10.7H5.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M11.3 2.8v3.9h3.9M8 10.5h5M8 13.5h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
        pen: '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M13.6 3.6l2.8 2.8-8.6 8.6-3.6.8.8-3.6z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>',
        upload: '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 13V4M6.3 7.6L10 3.9l3.7 3.7M4 13.5v2.7h12v-2.7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        spark: '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 14l4-4 3 3 7-7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="M12.5 6H17v4.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        alarm: '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="11" r="6" stroke="currentColor" stroke-width="1.5"/><path d="M10 8v3.2l2 1.3M3.5 4.5l2-1.8M16.5 4.5l-2-1.8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
        out: '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M8 4H5a1.5 1.5 0 0 0-1.5 1.5v9A1.5 1.5 0 0 0 5 16h3M12 6.5L15.5 10 12 13.5M15.3 10H8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
    };

    function toast(msg, bad) {
        var t = document.getElementById('jp-toast');
        t.textContent = msg; t.classList.toggle('bad', !!bad); t.classList.add('show');
        clearTimeout(t._h); t._h = setTimeout(function () { t.classList.remove('show'); }, bad ? 4200 : 2200);
    }

    function firstError(json) {
        if (json && json.errors) { var k = Object.keys(json.errors)[0]; if (k && json.errors[k][0]) return json.errors[k][0]; }
        return json && json.message;
    }
    function api(method, url, body) {
        return fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body: body ? JSON.stringify(body) : undefined
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (json) {
                if (res.ok) return json;
                var msg = res.status === 419 ? 'This page has been open a long time. Refresh it and try again.'
                    : res.status === 401 ? 'You\'ve been signed out. Refresh the page and sign in again.'
                    : res.status === 403 ? (json.message && isStudent() ? json.message : 'You can\'t change that one. Your counsellor looks after it.')
                    : res.status === 404 ? 'That item no longer exists. Refresh the page.'
                    : res.status === 429 ? 'Too many changes in a minute. Wait a moment and try again.'
                    : res.status === 413 ? 'That file is too large to upload. Upload a smaller copy.'
                    : res.status === 422 ? (firstError(json) || 'Check the details and try again.')
                    : 'Couldn\'t save. Check your connection and try again.';
                throw new Error(msg);
            });
        }, function () { throw new Error('Couldn\'t reach the server. Check your connection and try again.'); });
    }
    /* A file upload: the same handling as api(), sent as a form. */
    function upload(url, form) {
        return fetch(url, {
            method: 'POST', body: form, credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (json) {
                if (res.ok) return json;
                throw new Error(res.status === 413 ? 'That file is too large to upload. Upload a smaller copy.'
                    : res.status === 422 ? (firstError(json) || 'Check the file and try again.')
                    : res.status === 419 ? 'This page has been open a long time. Refresh it and try again.'
                    : 'Couldn\'t upload. Check your connection and try again.');
            });
        }, function () { throw new Error('Couldn\'t reach the server. Check your connection and try again.'); });
    }

    /* ------------------------------------------------------------ rollups (workbook formulas) */
    function roll(list) {
        var r = { inc: 0, done: 0, prog: 0, ns: 0, na: 0 };
        list.forEach(function (a) {
            if (!a.inc) return; r.inc++;
            if (a.status === 'Completed') r.done++;
            else if (a.status === 'In Progress' || a.status === 'Submitted') r.prog++;
            else if (a.status === 'Not Applicable') r.na++;
            else r.ns++;
        });
        var den = r.inc - r.na; r.den = den; r.pct = den > 0 ? Math.round(r.done / den * 100) : 0;
        return r;
    }
    function sum(rs) {
        var t = { inc: 0, done: 0, prog: 0, ns: 0, na: 0 };
        rs.forEach(function (r) { t.inc += r.inc; t.done += r.done; t.prog += r.prog; t.ns += r.ns; t.na += r.na; });
        t.den = t.inc - t.na; t.pct = t.den > 0 ? Math.round(t.done / t.den * 100) : 0; return t;
    }
    function phaseActs(p) { return p.activities.map(function (a) { return P.core[a.key]; }); }
    function appActs(ap) { return Object.keys(ap.acts).map(function (k) { return ap.acts[k]; }); }
    function currentPhase() {
        for (var i = 0; i < T.phases.length; i++) { var r = roll(phaseActs(T.phases[i])); if (r.den > 0 && r.done < r.den) return i; }
        return -1;
    }
    function openItems() {
        var out = [];
        T.phases.forEach(function (p, pi) {
            p.activities.forEach(function (d) {
                var a = P.core[d.key];
                if (a.inc && !closed(a.status)) out.push({ scope: 'core', appId: null, key: d.key, name: d.name, where: 'Stage ' + (pi + 1) + ' · ' + p.short, a: a });
            });
        });
        P.apps.forEach(function (ap) {
            Object.keys(ap.acts).forEach(function (k) {
                var a = ap.acts[k];
                if (a.inc && !closed(a.status)) out.push({ scope: 'app', appId: ap.id, key: k, name: APP_DEFS[k].name, where: ap.university, a: a });
            });
        });
        return out;
    }
    function byDate(x, y) {
        var a = x.a.target, b = y.a.target;
        if (!a && !b) return 0; if (!a) return 1; if (!b) return -1; return a < b ? -1 : a > b ? 1 : 0;
    }
    function overall() {
        return sum(T.phases.map(function (p) { return roll(phaseActs(p)); }).concat(P.apps.map(function (a) { return roll(appActs(a)); })));
    }
    function lateItems() {
        return openItems().filter(function (i) { var d = daysUntil(i.a.target); return d != null && d < 0 && (!isStudent() || studentOwns(i.a.owner)); });
    }
    function actFor(scope, appId, key) {
        if (scope === 'core') return P.core[key];
        var ap = P.apps.find(function (x) { return x.id === appId; });
        return ap ? ap.acts[key] : null;
    }
    function rowKey(scope, appId, key) { return scope + ':' + (appId || '') + ':' + key; }

    /* ------------------------------------------------------------ views */
    var VIEWS = [
        { key: 'dashboard', icon: 'dashboard', label: 'Dashboard' },
        { key: 'journey', icon: 'journey', label: function () { return isStudent() ? 'My journey' : 'Core journey'; } },
        { key: 'universities', icon: 'uni', label: 'Universities' },
        { key: 'documents', icon: 'doc', label: function () { return isStudent() ? 'My documents' : 'Documents'; } },
        { key: 'deadlines', icon: 'calendar', label: 'Deadlines' },
        { key: 'owners', icon: 'people', label: 'Who\'s on it' },
        { key: 'login', icon: 'key', label: 'Student login', only: 'counsellor' },
        { key: 'help', icon: 'help', label: 'Help' }
    ];
    function views() { return VIEWS.filter(function (v) { return !v.only || v.only === MODE; }); }
    function label(v) { return typeof v.label === 'function' ? v.label() : v.label; }
    function fromHash() {
        var h = (location.hash || '').replace('#', '');
        UI.view = views().some(function (v) { return v.key === h; }) ? h : 'dashboard';
    }

    /* ------------------------------------------------------------ render */
    function render() {
        var y = window.scrollY;
        if (UI.essayId && UI.essayDirty && document.getElementById('es-body')) {
            UI.draft = Object.assign({ id: UI.essayId, feedback: (document.getElementById('es-feedback') || {}).value }, essayValues());
        }
        var v = views().find(function (x) { return x.key === UI.view; });
        var body = {
            dashboard: renderDashboard, journey: renderJourney, universities: renderUnis,
            deadlines: renderDeadlines, owners: renderOwners, login: renderLogin, help: renderGuide, documents: renderDocuments
        }[UI.view]();
        document.getElementById('planner').innerHTML =
            '<div class="jp-app' + (UI.menu ? ' menu-open' : '') + '">' + renderSidebar() +
            '<div class="jp-scrim" data-act="close-menu"></div>' +
            '<div class="jp-body">' + renderBar(v) + '<main class="jp-content" id="jp-content">' + body + '</main>' +
            '<footer class="jp-foot">One Degree Advisory · Overseas Admission Journey Planner' +
            (isStudent() ? ' · Your plan is private to you.' : '') + '</footer></div></div>';
        window.scrollTo(0, y);
    }

    function renderSidebar() {
        var s = P.student, late = lateItems().length;
        var nav = views().map(function (v) {
            return '<a class="jp-nav-link' + (UI.view === v.key ? ' active' : '') + '" href="#' + v.key + '"' + (UI.view === v.key ? ' aria-current="page"' : '') + '>' +
                '<span class="jp-nav-ico ico-' + v.key + '">' + ICO[v.icon] + '</span><span>' + label(v) + '</span>' +
                (v.key === 'deadlines' && late ? '<b class="jp-badge num">' + late + '</b>' : '') +
                (v.key === 'documents' && docAttention() ? '<b class="jp-badge gold num" title="' + (isStudent() ? 'Essays with feedback to act on' : 'Essays waiting for your review') + '">' + docAttention() + '</b>' : '') +
                (v.key === 'login' && P.login && !P.login.active ? '<b class="jp-badge num">off</b>' : '') + '</a>';
        }).join('');
        var foot = isStudent()
            ? '<a class="jp-nav-link" href="' + esc(P.endpoints.password) + '"><span class="jp-nav-ico ico-login">' + ICO.key + '</span><span>Change password</span></a>' +
              '<form method="post" action="' + esc(P.endpoints.logout) + '"><input type="hidden" name="_token" value="' + esc(CSRF) + '"><button class="jp-nav-link" type="submit"><span class="jp-nav-ico ico-out">' + ICO.out + '</span><span>Sign out</span></button></form>'
            : '<a class="jp-nav-link" href="' + esc(P.endpoints.back) + '"><span class="jp-nav-ico ico-out">' + ICO.back + '</span><span>Back to CRM</span></a>';
        return '<aside class="jp-side" aria-label="Planner navigation">' +
            '<div class="jp-brand"><span class="jp-brand-mark"><img src="' + esc(window.JP_LOGO) + '" alt=""></span><span><b>One Degree</b><small>' + (isStudent() ? 'Student portal' : 'Journey planner') + '</small></span></div>' +
            '<div class="jp-who"><div class="jp-who-top"><span class="jp-avatar">' + esc(initials(s.name)) + '</span><div><b>' + esc(s.name) + '</b><small>' + esc([s.level, s.intake].filter(Boolean).join(' · ') || 'Plan details not set') + '</small></div></div>' +
            '<div class="jp-who-bar"><span>Overall progress</span><b class="num">' + overall().pct + '%</b></div><div class="jp-bar side"><i style="width:' + overall().pct + '%"></i></div></div>' +
            '<div class="jp-nav-label">' + (isStudent() ? 'My plan' : 'Planner') + '</div><nav class="jp-nav">' + nav + '</nav>' +
            '<div class="jp-side-foot">' + (s.counsellor ? '<div class="jp-counsellor"><small>' + (isStudent() ? 'Your counsellor' : 'Counsellor') + '</small><b>' + esc(s.counsellor) + '</b></div>' : '') + foot + '</div>' +
            '</aside>';
    }

    function renderBar(v) {
        var s = P.student;
        var sub = {
            dashboard: isStudent() ? 'Hi ' + s.firstName + ', here\'s where your journey stands today.' : 'Where ' + s.firstName + '\'s journey stands today.',
            journey: (T.phases.length === 7 ? 'Seven stages' : T.phases.length + ' stages') + ', done once, however many universities you apply to.',
            universities: 'One checklist per university, from requirements to visa.',
            deadlines: 'Every open task with a target date.',
            documents: isStudent() ? 'Upload your documents and write your essays. Your counsellor sees everything here.' : 'Everything ' + s.firstName + ' has uploaded or written, and what you have added.',
            owners: 'Open tasks by who does them.',
            login: 'How ' + s.firstName + ' signs in to their own planner.',
            help: 'How this planner works.'
        }[v.key];
        var right = isStudent()
            ? '<span class="jp-user"><span class="jp-avatar sm">' + esc(initials(s.name)) + '</span><span><b>' + esc(s.name) + '</b><small>Student</small></span></span>'
            : '<span class="jp-user"><span class="jp-avatar sm alt">' + esc(initials(s.counsellor || 'C')) + '</span><span><b>' + (MODE === 'partner' ? 'Partner view' : esc(s.counsellor || 'Counsellor')) + '</b><small>' + (MODE === 'partner' ? 'Read only' : 'Counsellor view') + '</small></span></span>';
        return '<header class="jp-topbar"><button class="jp-menu" data-act="menu" aria-label="Open menu">' + ICO.menu + '</button>' +
            '<div class="jp-topbar-title"><span class="jp-crumb">' + (isStudent() ? 'My plan' : esc(s.name) + (s.leadNumber ? ' · ' + esc(s.leadNumber) : '')) + '</span><h1>' + label(v) + '</h1><p>' + esc(sub) + '</p></div>' +
            '<div class="jp-topbar-actions">' + right + '</div></header>';
    }

    /* ---- dashboard ---- */
    function renderDashboard() {
        var pr = T.phases.map(function (p) { return roll(phaseActs(p)); });
        var core = sum(pr), apps = sum(P.apps.map(function (a) { return roll(appActs(a)); })), all = sum([core, apps]);
        var cur = currentPhase();
        var items = openItems();
        var late = lateItems();
        var upcoming = items.filter(function (i) { var d = daysUntil(i.a.target); return d != null && d >= 0; }).sort(byDate);
        var C = 2 * Math.PI * 42, off = C * (1 - all.pct / 100);

        var creds = isC() && UI.credentials ? credentialsCard(UI.credentials, true) : '';
        if (isC() && !P.login) creds += '<div class="jp-alert">This plan has no student login yet. It\'s created when the planner is started from the CRM.</div>';

        var s = P.student, nextUp = upcoming[0];
        var hour = new Date().getHours(), greet = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening';
        var chips = [
            s.intake ? '<span class="jp-chip">' + ICO.calendar + esc(s.intake) + '</span>' : '',
            s.level ? '<span class="jp-chip">' + ICO.uni + esc(s.level) + '</span>' : '',
            s.focus ? '<span class="jp-chip">' + ICO.journey + esc(s.focus) + '</span>' : '',
            isC() && P.login ? '<span class="jp-chip ' + (P.login.active ? '' : 'off') + '">' + ICO.key + (P.login.active ? (P.login.lastLoginAt ? 'Signed in ' + esc(fmt(P.login.lastLoginAt.slice(0, 10), { day: 'numeric', month: 'short' })) : 'Login not used yet') : 'Login switched off') + '</span>' : ''
        ].join('');
        var actions = isC()
            ? '<div class="jp-hero-actions"><button class="jp-btn light sm" data-act="add-uni">' + ICO.plus + ' Add university</button><button class="jp-btn outline sm" data-act="edit-details">Edit plan details</button><a class="jp-btn outline sm" href="#login">Student login</a></div>'
            : (nextUp ? '<button class="jp-hero-next" data-act="jump-item" data-scope="' + nextUp.scope + '" data-app="' + (nextUp.appId || '') + '" data-key="' + esc(nextUp.key) + '"><small>Next up · ' + esc(fmt(nextUp.a.target, { day: 'numeric', month: 'short' })) + '</small><b>' + esc(nextUp.name) + '</b></button>' : '');
        var hero = '<section class="jp-hero"><div class="jp-hero-main">' +
            '<span class="jp-hero-kicker">' + (isStudent() ? greet : 'Journey planner') + '</span>' +
            '<h2>' + (isStudent() ? esc(s.firstName) + ', you\'re ' + all.pct + '% of the way there' : esc(s.name) + ' is ' + all.pct + '% of the way there') + '</h2>' +
            '<p>' + (cur >= 0 ? 'Stage ' + (cur + 1) + ' of ' + T.phases.length + ' · ' + esc(T.phases[cur].name) + (late.length ? ' · ' + plural(late.length, 'task is', 'tasks are') + ' late' : ' · everything is on time') : 'Every stage of the journey is complete.') + '</p>' +
            '<div class="jp-chips">' + chips + '</div>' + actions + '</div>' +
            '<div class="jp-hero-ring"><svg viewBox="0 0 100 100" role="img" aria-label="' + all.pct + '% complete"><circle class="trk" cx="50" cy="50" r="42" fill="none" stroke-width="9"/><circle class="val" cx="50" cy="50" r="42" fill="none" stroke-width="9" stroke-linecap="round" stroke-dasharray="' + C.toFixed(1) + '" stroke-dashoffset="' + off.toFixed(1) + '"/></svg>' +
            '<div><b class="num">' + all.pct + '%</b><small>' + all.done + ' of ' + all.den + ' done</small></div></div></section>';

        var kpis = '<div class="jp-kpis">' +
            kpi('journey', isStudent() ? 'My journey' : 'Core journey', core.pct + '%', core.done + ' of ' + core.den + ' tasks done', '', core.pct) +
            kpi('uni', 'University applications', apps.pct + '%', P.apps.length ? plural(P.apps.length, 'university', 'universities') + ' · ' + apps.done + ' of ' + apps.den + ' done' : 'No universities yet', '', apps.pct) +
            kpi('alarm', isStudent() ? 'Running late' : 'Overdue', String(late.length), late.length ? late.sort(byDate)[0].name : 'Nothing late', late.length ? 'alert' : 'good') +
            kpi('calendar', 'Next date', nextUp ? fmt(nextUp.a.target, { day: 'numeric', month: 'short' }) : '—', nextUp ? nextUp.name : 'Nothing scheduled', 'sun') +
            '</div>';

        var reach = cur >= 0 ? cur : T.phases.length - 1;
        var nStages = T.phases.length;
        var fill = nStages > 1 ? (reach / (nStages - 1)) * (100 - 100 / nStages) : 0;
        var route = T.phases.map(function (p, i) {
            var r = pr[i], complete = r.den > 0 && r.done === r.den;
            var cls = complete ? 'done' : (i === cur ? 'now' : '');
            return '<li class="' + cls + '"><button data-act="jump-phase" data-p="' + p.key + '" aria-label="' + esc(p.name) + ', ' + r.pct + '% done">' +
                (i === cur ? '<span class="here">You are here</span>' : '') +
                '<span class="stop">' + (complete ? ICO.check : (i === cur ? ICO.plane : (i + 1))) + '</span>' +
                '<span class="name">' + esc(p.short) + '</span><span class="pct num">' + (r.den ? r.done + '/' + r.den : '—') + '</span></button></li>';
        }).join('');
        var progress = '<section class="jp-card jp-progress-card"><div class="jp-card-h"><div><h2>Journey progress</h2><p>' +
            (T.phases.length === 7 ? 'Seven stages' : T.phases.length + ' stages') + ', done once. Choose one to see its tasks.</p></div><a class="jp-link" href="#journey">Open journey</a></div>' +
            '<ol class="jp-route" style="--n:' + nStages + '; --fill:' + fill.toFixed(2) + '%">' + route + '</ol></section>';

        var pool = isStudent() ? items.filter(function (i) { return studentOwns(i.a.owner); }) : items;
        var next = pool.slice().sort(byDate).slice(0, 6);
        var nextCard = '<section class="jp-card"><div class="jp-card-h"><div><h2>' + (isStudent() ? 'Your next steps' : 'What needs doing next') + '</h2><p>' +
            (isStudent() ? 'Your tasks, earliest first. Tick one when it\'s done.' : 'The earliest open tasks across the plan.') + '</p></div>' +
            '<a class="jp-link" href="#journey">View all</a></div>' +
            (next.length ? '<div class="jp-tasks">' + next.map(taskRow).join('') + '</div>'
                : '<div class="jp-celebrate">' + (items.length ? 'Nothing on your side right now. Your counsellor is working on the next part.' : 'Everything on the plan is done. Brilliant work!') + '</div>') + '</section>';

        var dlCard = '<section class="jp-card"><div class="jp-card-h"><div><h2>Coming up</h2><p>The next dates on the plan.</p></div><a class="jp-link" href="#deadlines">All deadlines</a></div>' +
            (upcoming.length ? '<div class="jp-mini-dl">' + upcoming.slice(0, 5).map(function (i) {
                var dt = new Date(i.a.target + 'T00:00:00'), d = due(daysUntil(i.a.target));
                return '<button class="jp-mini-row" data-act="jump-item" data-scope="' + i.scope + '" data-app="' + (i.appId || '') + '" data-key="' + esc(i.key) + '">' +
                    '<span class="jp-cal" aria-hidden="true"><span>' + dt.toLocaleDateString('en-GB', { month: 'short' }) + '</span><b class="num">' + dt.getDate() + '</b></span>' +
                    '<span class="w"><span class="t">' + esc(i.name) + '</span><span class="s">' + esc(i.where) + '</span></span><span class="pill ' + d.cls + '">' + d.label + '</span></button>';
            }).join('') + '</div>' : '<div class="jp-empty">No dates set yet.</div>') + '</section>';

        var uniCard = '<section class="jp-card"><div class="jp-card-h"><div><h2>Universities</h2><p>' + (P.apps.length ? 'Progress on each application.' : 'None added yet.') + '</p></div><a class="jp-link" href="#universities">Open</a></div>' +
            (P.apps.length ? '<div class="jp-uni-mini">' + P.apps.map(function (a) {
                var r = roll(appActs(a));
                return '<button class="jp-uni-row" data-act="open-uni" data-u="' + a.id + '"><span class="w"><span class="t">' + esc(a.university) + '</span><span class="s">' + esc([a.country, a.program].filter(Boolean).join(' · ')) + '</span></span>' +
                    '<span class="fit ' + (a.fit || 'unset') + '">' + esc(T.fits[a.fit] || '—') + '</span><span class="jp-progress num"><span class="jp-bar"><i style="width:' + r.pct + '%"></i></span>' + r.pct + '%</span></button>';
            }).join('') + '</div>' : (isC() ? '<div class="jp-empty"><button class="jp-btn sm" data-act="add-uni">' + ICO.plus + ' Add university</button></div>' : '<div class="jp-empty">Your counsellor adds universities once your shortlist is agreed.</div>')) + '</section>';

        return creds + hero + kpis + progress + '<div class="jp-grid-2">' + nextCard + dlCard + '</div>' + uniCard + (isStudent() ? '' : phaseTable(pr, core));
    }

    function kpi(icon, k, v, s, cls, pct) {
        return '<div class="jp-kpi ' + (cls || '') + '"><div class="jp-kpi-h"><span class="jp-kpi-ico">' + ICO[icon] + '</span><span class="k">' + esc(k) + '</span></div><div class="v num">' + esc(v) + '</div><div class="s">' + esc(s) + '</div>' +
            (pct != null ? '<div class="jp-bar thin"><i style="width:' + pct + '%"></i></div>' : '') + '</div>';
    }

    function taskRow(i) {
        var d = due(daysUntil(i.a.target)), editable = canEdit(i.a);
        return '<div class="jp-task' + (d && d.cls === 'danger' ? ' over' : '') + '">' +
            '<button class="jp-tick" data-act="tick" data-scope="' + i.scope + '" data-app="' + (i.appId || '') + '" data-key="' + esc(i.key) + '"' + (editable ? '' : ' disabled title="Your counsellor completes this one"') + ' aria-label="Mark ' + esc(i.name) + ' as done">' + ICO.check + '</button>' +
            '<div><div class="t">' + esc(i.name) + '</div><div class="w">' + esc(i.where) + (isStudent() ? '' : ' · ' + esc(i.a.owner)) + (i.a.status !== 'Not Started' ? ' · <span class="jp-state ' + stPill(i.a.status) + '">' + esc(i.a.status) + '</span>' : '') + '</div></div>' +
            (d ? '<span class="pill ' + d.cls + '">' + d.label + '</span>' : '<span class="pill wait">No date</span>') + '</div>';
    }

    function cnt(n) { return '<td class="r num' + (n ? '' : ' zero') + '">' + n + '</td>'; }
    function pctCell(p) { return '<div class="jp-pct"><div class="jp-bar"><i style="width:' + p + '%"></i></div><b class="num">' + p + '%</b></div>'; }
    function phaseTable(pr, core) {
        var rows = T.phases.map(function (p, i) {
            var r = pr[i];
            return '<tr class="click" data-act="jump-phase" data-p="' + p.key + '"><td>' + (i + 1) + '. ' + esc(p.name) + '</td>' + cnt(r.inc) + cnt(r.done) + cnt(r.prog) + cnt(r.ns) + cnt(r.na) + '<td>' + pctCell(r.pct) + '</td></tr>';
        }).join('');
        return '<section class="jp-card flush"><div class="jp-card-h pad"><div><h2>Progress by phase</h2><p>Only switched-on activities count. % complete = Completed ÷ (Included − Not Applicable).</p></div></div><div class="jp-table-wrap"><table class="jp-table"><thead><tr><th>Phase</th><th class="r">Included</th><th class="r">Completed</th><th class="r">In progress / Submitted</th><th class="r">Not started</th><th class="r">Not applicable</th><th>% complete</th></tr></thead><tbody>' +
            rows + '<tr class="total"><td>Total</td>' + cnt(core.inc) + cnt(core.done) + cnt(core.prog) + cnt(core.ns) + cnt(core.na) + '<td>' + pctCell(core.pct) + '</td></tr></tbody></table></div></section>';
    }

    function rowHead() {
        return '<div class="jp-row jp-row-head" aria-hidden="true">' + (isC() ? '<span></span>' : '') +
            '<span>Task</span><span>Owner</span><span>Status</span><span>Target date</span><span>Timing</span><span></span></div>';
    }

    /* ---- one activity row, shared by the journey and every university ---- */
    function actRow(scope, appId, def, a, timeline, filtered) {
        if (!a.inc && (!isC() || !UI.showExcluded)) return '';
        if (filtered) {
            if (UI.owner !== 'all' && a.owner.indexOf(UI.owner) < 0) return '';
            if (UI.status === 'open' && closed(a.status)) return '';
            if (UI.status !== 'all' && UI.status !== 'open' && a.status !== UI.status) return '';
        }
        var k = rowKey(scope, appId, def.key), open = !!UI.openRows[k];
        var data = ' data-scope="' + scope + '" data-app="' + (appId || '') + '" data-key="' + esc(def.key) + '"';
        var d = (a.inc && !closed(a.status)) ? due(daysUntil(a.target)) : null;
        var editable = canEdit(a);
        var statuses = isC() ? T.statuses : T.studentStatuses;

        var status = editable
            ? '<select class="jp-st ' + stClass(a.status) + '" data-f="status"' + data + (a.inc ? '' : ' disabled') + ' aria-label="Status">' +
                statuses.map(function (s) { return '<option' + (s === a.status ? ' selected' : '') + '>' + s + '</option>'; }).join('') + '</select>'
            : '<span class="jp-st jp-st-ro ' + stClass(a.status) + '">' + esc(a.status) + '</span>';
        var date = isC() && !a.inc
            ? '<div class="jp-dcell jp-dtxt muted">—</div>'
            : isC()
            ? '<div class="jp-dcell"><input class="jp-date" type="date" data-f="target"' + data + ' value="' + esc(a.target || '') + '" aria-label="Target date"' + (a.inc ? '' : ' disabled') + '></div>'
            : '<div class="jp-dcell jp-dtxt num">' + (a.target ? fmt(a.target) : '<span style="color:var(--muted)">No date yet</span>') + '</div>';
        var after = d ? '<span class="pill ' + d.cls + '">' + d.label + '</span>'
            : (a.status === 'Completed' && a.done ? '<span class="jp-owner num">Done ' + fmt(a.done, { day: 'numeric', month: 'short' }) + '</span>' : '');

        var detail = '';
        if (open) {
            detail = '<div class="jp-detail"><div><p class="desc">' + esc(def.desc) + '</p><dl class="jp-kv">' +
                '<dt>Documents</dt><dd>' + esc(def.docs) + '</dd>' +
                (timeline ? '<dt>Best time</dt><dd>' + esc(timeline) + '</dd>' : '') +
                (!isC() ? '<dt>Who does it</dt><dd>' + esc(a.owner) + '</dd><dt>Completed on</dt><dd>' + (a.done ? fmt(a.done) : '—') + '</dd>' : '') +
                (isC() && a.by === 'student' && a.at ? '<dt>Last change</dt><dd>By the student, ' + esc(when(a.at)) + '</dd>' : '') +
                '</dl></div>' +
                (isC()
                    ? '<div class="jp-fields">' +
                        '<div><label for="ow-' + esc(k) + '">Owner</label><select id="ow-' + esc(k) + '" data-f="owner"' + data + '>' + T.owners.map(function (o) { return '<option' + (o === a.owner ? ' selected' : '') + '>' + esc(o) + '</option>'; }).join('') + '</select></div>' +
                        '<div><label for="dn-' + esc(k) + '">Completion date</label><input id="dn-' + esc(k) + '" type="date" data-f="done"' + data + ' value="' + esc(a.done || '') + '"></div>' +
                        '<div class="full"><label for="nt-' + esc(k) + '">Notes <span style="font-weight:500">(the student sees these)</span></label><textarea id="nt-' + esc(k) + '" data-f="notes"' + data + ' maxlength="1000" placeholder="Scores, offer conditions, what to chase…">' + esc(a.notes) + '</textarea></div>' +
                        (def.custom ? '<div class="full jp-task-tools"><span class="muted">You added this task for this student.</span><button class="jp-btn ghost sm" data-act="edit-task" data-key="' + esc(def.key) + '">Edit task</button>' +
                            '<button class="jp-btn danger sm' + (UI.armed === 'rm-task-' + def.key ? ' armed' : '') + '" data-act="rm-task" data-key="' + esc(def.key) + '">' + (UI.armed === 'rm-task-' + def.key ? 'Click again to remove' : 'Remove task') + '</button></div>' : '') +
                        '</div>'
                    : (a.notes ? '<div><span class="jp-note-lbl">Note from your counsellor</span><div class="jp-note">' + esc(a.notes) + '</div></div>' : '<div></div>')) +
                '</div>';
        }
        return '<div class="jp-act' + (a.inc ? '' : ' off') + (open ? ' open' : '') + '"><div class="jp-row">' +
            (isC() ? '<button class="jp-inc' + (a.inc ? ' on' : '') + '" data-act="toggle-inc"' + data + ' aria-pressed="' + a.inc + '" title="' + (a.inc ? 'Included for this student. Click to exclude.' : 'Excluded. Click to include.') + '">' + (a.inc ? ICO.check : '') + '</button>' : '') +
            '<button class="jp-name" data-act="toggle-row"' + data + ' aria-expanded="' + open + '"><span class="n">' + esc(def.name) + (def.custom && !isStudent() ? ' <span class="jp-added">Added</span>' : '') + '</span><span class="d">' + esc(a.notes || def.desc) + '</span></button>' +
            '<span class="jp-ocell"><span class="jp-owner-chip' + (isStudent() && studentOwns(a.owner) ? ' mine' : '') + '">' + esc(isStudent() && studentOwns(a.owner) ? a.owner.replace('Student', 'You') : a.owner) + '</span></span>' +
            status + date + '<span class="jp-due">' + after + '</span>' +
            '<button class="jp-chev" data-act="toggle-row"' + data + ' aria-label="Show details">' + ICO.chev + '</button></div>' + detail + '</div>';
    }

    /* ---- core journey ---- */
    function renderJourney() {
        var cur = currentPhase();
        var owners = ['Student', 'Counsellor', 'University', 'Bank/Financial Institution'];
        var tools = '<div class="jp-tools">' +
            '<select id="f-owner" aria-label="Filter by owner"><option value="all">Everyone\'s tasks</option>' + owners.map(function (o) { return '<option value="' + esc(o) + '"' + (UI.owner === o ? ' selected' : '') + '>' + (isStudent() && o === 'Student' ? 'My tasks' : (o === 'Bank/Financial Institution' ? 'Bank' : o)) + '</option>'; }).join('') + '</select>' +
            '<select id="f-status" aria-label="Filter by status"><option value="all">Any status</option><option value="open"' + (UI.status === 'open' ? ' selected' : '') + '>Still to do</option>' + T.statuses.map(function (s) { return '<option' + (UI.status === s ? ' selected' : '') + '>' + s + '</option>'; }).join('') + '</select>' +
            (isC() ? '<label><input type="checkbox" id="f-excl"' + (UI.showExcluded ? ' checked' : '') + '> Show excluded</label>' : '') +
            '<span class="sp"></span>' + (isC() ? '<button class="jp-btn sm" data-act="add-stage">' + ICO.plus + ' Add a stage</button>' : '') + '<button class="jp-btn ghost sm" data-act="expand-all">Open all</button><button class="jp-btn ghost sm" data-act="collapse-all">Close all</button></div>';

        var panels = T.phases.map(function (p, i) {
            var r = roll(phaseActs(p));
            var open = UI.openPhases[p.key] != null ? UI.openPhases[p.key] : i === cur;
            var complete = r.den > 0 && r.done === r.den;
            var inc = phaseActs(p).filter(function (a) { return a.inc; }).length;
            if (!isC() && inc === 0) return '';
            var body = '';
            if (open) {
                body = p.activities.map(function (d) { return actRow('core', null, d, P.core[d.key], p.timeline, true); }).join('') ||
                    (p.custom && !p.activities.length ? '<div class="jp-empty"><b>No tasks in this stage yet</b>Add the first one below.</div>' : '<div class="jp-empty">Nothing here matches those filters.</div>');
                var stageTools = '';
                if (p.custom && isC()) {
                    var armedStage = UI.armed === 'rm-stage-' + p.key;
                    stageTools = '<div class="jp-stage-tools"><span class="muted">You added this stage for this student.</span>' +
                        '<button class="jp-btn ghost sm" data-act="edit-stage" data-p="' + p.key + '">Edit stage</button>' +
                        '<button class="jp-btn danger sm' + (armedStage ? ' armed' : '') + '" data-act="rm-stage" data-p="' + p.key + '">' + (armedStage ? 'Click again: removes the stage and its tasks' : 'Remove stage') + '</button></div>';
                }
                body = '<div class="jp-acts' + (isC() ? '' : ' ro') + '">' + stageTools + rowHead() + body +
                    (isC() ? '<div class="jp-add-task"><button class="jp-btn ghost sm" data-act="add-task" data-p="' + p.key + '">' + ICO.plus + ' Add a task to this stage</button></div>' : '') + '</div>';
            }
            return '<div class="jp-phase ph-c' + (i % 7) + (open ? ' open' : '') + (complete ? ' complete' : '') + (i === cur ? ' current' : '') + '" id="ph-' + p.key + '">' +
                '<button class="jp-phase-h" data-act="toggle-phase" data-p="' + p.key + '" aria-expanded="' + open + '">' +
                '<span class="jp-phase-no">' + (complete ? ICO.check : (i + 1)) + '</span>' +
                '<span><span class="ttl">' + esc(p.name) + (p.custom && isC() ? ' <span class="jp-added">Added</span>' : '') + '</span><span class="tl">' + esc(p.timeline) + (isC() ? ' · ' + inc + ' of ' + p.activities.length + ' included' : ' · ' + plural(inc, 'task', 'tasks')) + '</span></span>' +
                '<span class="jp-progress num"><span class="jp-bar"><i style="width:' + r.pct + '%"></i></span>' + r.pct + '%</span>' + ICO.chev +
                '</button>' + body + '</div>';
        }).join('');

        var intro = isC() ? '<p class="jp-intro">Tick the box on the left to include an activity for this student. Excluded activities drop out of every count and the student doesn\'t see them.</p>' : '';
        return intro + tools + panels;
    }

    /* ---- universities ---- */
    function curApp() { return P.apps.find(function (a) { return a.id === UI.appId; }) || P.apps[0] || null; }
    function renderUnis() {
        var ap = curApp();
        var tickets = P.apps.map(function (a) {
            var r = roll(appActs(a));
            return '<button class="jp-ticket' + (ap && a.id === ap.id ? ' on' : '') + '" data-act="pick-uni" data-u="' + a.id + '">' +
                '<span class="top"><span class="code">' + esc(a.country || 'Country not set') + '</span><span class="fit ' + (a.fit || 'unset') + '">' + esc(T.fits[a.fit] || 'Fit not set') + '</span></span>' +
                '<h3>' + esc(a.university) + '</h3><span class="prog">' + esc(a.program || 'Programme not set') + '</span>' +
                '<span class="foot num"><span class="jp-bar"><i style="width:' + r.pct + '%"></i></span>' + r.pct + '%' + (a.offerType ? ' · ' + esc(a.offerType) : '') + '</span></button>';
        }).join('') + (isC() ? '<button class="jp-ticket add" data-act="add-uni">' + ICO.plus.replace('<svg', '<svg width="16" height="16"') + ' Add university</button>' : '');

        if (!P.apps.length) {
            return isC() ? '<div class="jp-tickets">' + tickets + '</div>' : '<div class="jp-card jp-empty"><b>No universities yet</b>Your counsellor adds them here once your shortlist is agreed.</div>';
        }
        var summary = '';
        if (!isStudent()) {
            var rs = P.apps.map(function (a) { return roll(appActs(a)); }), tot = sum(rs);
            summary = '<details class="jp-details-toggle"><summary>Applications summary</summary><div class="jp-table-wrap jp-card flush"><table class="jp-table"><thead><tr><th>University</th><th>Country</th><th>Program / course</th><th>Fit</th><th class="r">Included</th><th class="r">Completed</th><th class="r">In progress / Submitted</th><th class="r">Not started</th><th class="r">N/A</th><th>% complete</th></tr></thead><tbody>' +
                P.apps.map(function (a, i) {
                    var r = rs[i];
                    return '<tr class="click" data-act="pick-uni" data-u="' + a.id + '"><td><b>' + esc(a.university) + '</b></td><td>' + esc(a.country) + '</td><td>' + esc(a.program) + '</td><td><span class="fit ' + (a.fit || 'unset') + '">' + esc(T.fits[a.fit] || '—') + '</span></td>' +
                        cnt(r.inc) + cnt(r.done) + cnt(r.prog) + cnt(r.ns) + cnt(r.na) + '<td>' + pctCell(r.pct) + '</td></tr>';
                }).join('') +
                '<tr class="total"><td colspan="4">Total</td>' + cnt(tot.inc) + cnt(tot.done) + cnt(tot.prog) + cnt(tot.ns) + cnt(tot.na) + '<td>' + pctCell(tot.pct) + '</td></tr></tbody></table></div></details>';
        }
        return '<div class="jp-tickets">' + tickets + '</div>' + summary + (ap ? renderBlock(ap) : '');
    }

    function renderBlock(a) {
        var r = roll(appActs(a));
        var seat = a.acts['seat-confirmed'].status === 'Completed';
        var visaOff = !a.acts['visa-submitted'].inc || !a.acts['visa-approved'].inc;
        var banner = isC() && seat && visaOff
            ? '<div class="jp-banner"><span><b>Seat confirmed.</b> This is the chosen university, so its visa steps now apply.</span><button class="jp-btn sm" data-act="visa-on" data-u="' + a.id + '">Turn on visa steps</button></div>' : '';
        var groups = T.appGroups.map(function (g) {
            var rows = g.activities.map(function (d) { return actRow('app', a.id, d, a.acts[d.key], '', false); }).join('');
            return rows ? '<div class="jp-group">' + esc(g.name) + '</div>' + rows : '';
        }).join('') || '<div class="jp-empty">Nothing switched on for this university yet.</div>';
        var armed = UI.armed === 'rm-' + a.id;
        var meta = isC()
            ? '<span><span class="lbl">Fit</span><select class="jp-select" data-uf="fit" data-u="' + a.id + '" aria-label="Programme fit"><option value="">Not set</option>' + Object.keys(T.fits).map(function (f) { return '<option value="' + f + '"' + (a.fit === f ? ' selected' : '') + '>' + T.fits[f] + '</option>'; }).join('') + '</select></span>' +
              '<span><span class="lbl">Offer</span><select class="jp-select" data-uf="offer_type" data-u="' + a.id + '" aria-label="Offer type"><option value="">No decision yet</option>' + T.offerTypes.map(function (o) { return '<option' + (a.offerType === o ? ' selected' : '') + '>' + o + '</option>'; }).join('') + '</select></span>' +
              '<button class="jp-link" data-act="edit-uni" data-u="' + a.id + '">Edit name, country, programme</button>'
            : '<span><span class="lbl">Fit</span><span class="fit ' + (a.fit || 'unset') + '">' + esc(T.fits[a.fit] || 'Not set') + '</span></span>' +
              (a.offerType ? '<span><span class="lbl">Offer</span><span class="pill ' + (a.offerType === 'Rejected' ? 'danger' : a.offerType === 'Waitlist' ? 'amber' : 'good') + '">' + esc(a.offerType) + '</span></span>' : '');
        return '<div class="jp-block" id="jp-block"><div class="jp-block-h"><div><span class="jp-eyebrow">' + esc(a.country || '') + '</span><h3>' + esc(a.university) + '</h3><div class="where">' + esc(a.program || 'Programme not set') + '</div>' +
            '<div class="jp-block-meta">' + meta + '</div></div>' +
            '<div class="jp-block-side"><div class="jp-big num">' + r.pct + '%<small>' + r.done + ' of ' + r.den + ' done</small></div>' +
            (isC() ? '<button class="jp-btn danger sm' + (armed ? ' armed' : '') + '" data-act="rm-uni" data-u="' + a.id + '">' + (armed ? 'Click again to remove' : 'Remove') + '</button>' : '') + '</div></div>' +
            banner + '<div class="jp-acts' + (isC() ? '' : ' ro') + '">' + rowHead() + groups + '</div></div>';
    }

    /* ---- deadlines ---- */
    function renderDeadlines() {
        var all = openItems(), dated = all.filter(function (i) { return i.a.target; }).sort(byDate);
        var list = dated.filter(function (i) { var d = daysUntil(i.a.target); return UI.dl === 'late' ? d < 0 : UI.dl === '30' ? d <= 30 : true; });
        var undated = all.length - dated.length;
        var rows = list.map(function (i) {
            var d = due(daysUntil(i.a.target)), dt = new Date(i.a.target + 'T00:00:00');
            return '<div class="jp-dl' + (d.cls === 'danger' ? ' over' : '') + '"><div class="jp-cal" aria-hidden="true"><span>' + dt.toLocaleDateString('en-GB', { month: 'short' }) + '</span><b class="num">' + dt.getDate() + '</b></div>' +
                '<button class="w" data-act="jump-item" data-scope="' + i.scope + '" data-app="' + (i.appId || '') + '" data-key="' + esc(i.key) + '"><span class="t">' + esc(i.name) + '</span><span class="s">' + esc(i.where) + ' · ' + fmt(i.a.target) + '</span></button>' +
                '<span class="o"><span class="jp-owner-chip' + (isStudent() && studentOwns(i.a.owner) ? ' mine' : '') + '">' + esc(isStudent() && studentOwns(i.a.owner) ? i.a.owner.replace('Student', 'You') : i.a.owner) + '</span></span><span class="pill ' + d.cls + '">' + d.label + '</span></div>';
        }).join('');
        return '<div class="jp-tools"><div class="jp-seg" role="group" aria-label="Show">' + [['late', 'Late'], ['30', 'Next 30 days'], ['open', 'All']].map(function (x) { return '<button class="' + (UI.dl === x[0] ? 'on' : '') + '" data-act="dl" data-v="' + x[0] + '">' + x[1] + '</button>'; }).join('') + '</div>' +
            '<span class="sp"></span><span class="jp-owner">' + (undated ? plural(undated, 'open task has', 'open tasks have') + ' no date yet' : '') + '</span></div>' +
            '<div class="jp-card flush">' + (rows || '<div class="jp-empty"><b>' + (UI.dl === 'late' ? 'Nothing is late' : 'Nothing in this range') + '</b>' + (UI.dl === 'late' ? 'Every dated task is on time.' : 'No open tasks have a date in this range.') + '</div>') + '</div>';
    }

    /* ---- who's on it ---- */
    function renderOwners() {
        var items = openItems().sort(byDate);
        var parties = [
            [isStudent() ? 'You' : 'Student', function (o) { return o.indexOf('Student') >= 0; }, 'student'],
            ['Counsellor', function (o) { return o.indexOf('Counsellor') >= 0; }, 'counsellor'],
            ['University / bank', function (o) { return o === 'University' || o.indexOf('Bank') === 0; }, 'other']
        ];
        return '<p class="jp-intro">A shared task (for example Student &amp; Counsellor) shows for each of them.</p><div class="jp-owners">' +
            parties.map(function (p) {
                var mine = items.filter(function (i) { return p[1](i.a.owner); });
                return '<div class="jp-own jp-own-' + p[2] + '"><h3><span class="jp-own-name"><i></i>' + p[0] + '</span><span class="num">' + mine.length + '</span></h3><div class="sub">' + (mine.length === 1 ? 'open task' : 'open tasks') + (mine.length > 6 ? ' · next 6 shown' : '') + '</div>' +
                    (mine.length ? '<ul>' + mine.slice(0, 6).map(function (i) { var d = daysUntil(i.a.target); return '<li><div>' + esc(i.name) + '<small>' + esc(i.where) + '</small></div><span class="when num' + (d != null && d < 0 ? ' over' : '') + '">' + (i.a.target ? fmt(i.a.target, { day: 'numeric', month: 'short' }) : 'No date') + '</span></li>'; }).join('') + '</ul>'
                        : '<p style="font-size:.85rem;color:var(--muted)">Nothing open.</p>') + '</div>';
            }).join('') + '</div>';
    }

    /* ---- student login (counsellor) ---- */
    function credentialsCard(c, fresh) {
        var url = P.login ? P.login.loginUrl : '';
        return '<section class="jp-card jp-creds"><div class="jp-card-h"><div><h2>' + (fresh ? 'Student login ready' : 'New password issued') + '</h2><p>Send these to ' + esc(P.student.firstName) + '. The password is shown only now; they\'ll choose their own the first time they sign in.</p></div>' +
            '<button class="jp-btn sm" data-act="copy-creds">Copy all</button></div>' +
            '<dl class="jp-cred-list"><div class="wide"><dt>Sign-in page</dt><dd><code>' + esc(url) + '</code></dd></div><div><dt>Email</dt><dd><code>' + esc(c.email) + '</code></dd></div><div><dt>Temporary password</dt><dd><code class="pw">' + esc(c.password) + '</code></dd></div></dl>' +
            '<button class="jp-link" data-act="dismiss-creds">I\'ve sent them. Hide this.</button></section>';
    }
    function renderLogin() {
        var L = P.login;
        if (!L) return '<div class="jp-card jp-empty"><b>No student login</b>The login is created when the planner is started from the CRM\'s Student tab.</div>';
        var armedReset = UI.armed === 'reset', armedOff = UI.armed === 'toggle';
        return (UI.credentials ? credentialsCard(UI.credentials, false) : '') +
            '<section class="jp-card"><div class="jp-card-h"><div><h2>Sign-in details</h2><p>' + esc(P.student.firstName) + ' signs in at the student portal with this email.</p></div>' +
            '<span class="pill ' + (L.active ? 'good' : 'danger') + '">' + (L.active ? 'Active' : 'Switched off') + '</span></div>' +
            '<dl class="jp-cred-list"><div class="wide"><dt>Sign-in page</dt><dd><code>' + esc(L.loginUrl) + '</code></dd></div>' +
            '<div><dt>Email</dt><dd><code>' + esc(L.email) + '</code></dd></div>' +
            '<div><dt>Password</dt><dd>' + (L.mustChange ? 'Temporary. They haven\'t chosen their own yet.' : 'Chosen by the student.') + '</dd></div>' +
            '<div><dt>Last signed in</dt><dd>' + (L.lastLoginAt ? esc(when(L.lastLoginAt)) : 'Not yet') + '</dd></div></dl>' +
            '<div class="jp-actions left">' +
            '<button class="jp-btn' + (armedReset ? ' armed' : '') + '" data-act="reset-password">' + (armedReset ? 'Click again: the current password stops working' : 'Reset password') + '</button>' +
            '<button class="jp-btn ' + (L.active ? 'danger' : 'ghost') + (armedOff ? ' armed' : '') + '" data-act="toggle-login">' + (L.active ? (armedOff ? 'Click again to switch off' : 'Switch off login') : 'Switch login back on') + '</button>' +
            '</div><p class="jp-hint">Switching the login off keeps the plan; ' + esc(P.student.firstName) + ' just can\'t sign in until you switch it back on. Every student update appears on the lead timeline in the CRM.</p></section>';
    }

    /* ---- documents & essays ---- */
    function docUrl(id) { return P.endpoints.document.replace('__ID__', id); }
    function findDoc(id) { return P.documents.find(function (d) { return d.id === id; }); }
    function replaceDoc(d) { var i = P.documents.findIndex(function (x) { return x.id === d.id; }); if (i >= 0) P.documents[i] = d; else P.documents.unshift(d); }
    function docAttention() {
        return P.documents.filter(function (d) { return d.kind === 'essay' && (isStudent() ? d.status === 'Needs changes' : isC() && d.status === 'Submitted'); }).length;
    }
    function essayPill(st) { return { 'Draft': 'wait', 'Submitted': 'amber', 'Needs changes': 'danger', 'Approved': 'good' }[st] || 'wait'; }
    function essayLabel(st) { return st === 'Submitted' ? (isStudent() ? 'Sent for review' : 'Waiting for review') : st; }
    function kb(n) { if (!n && n !== 0) return ''; return n >= 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB'; }
    function fileKind(d) {
        var n = (d.fileName || '').toLowerCase();
        return /\.pdf$/.test(n) ? 'pdf' : /\.(docx?|rtf)$/.test(n) ? 'word' : /\.(jpe?g|png)$/.test(n) ? 'image' : 'file';
    }
    function when2(iso) { return iso ? new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : ''; }
    function uniOptions(selected) {
        return '<option value="">Not for a specific university</option>' + P.apps.map(function (a) { return '<option value="' + a.id + '"' + (selected === a.id ? ' selected' : '') + '>' + esc(a.university) + '</option>'; }).join('');
    }
    function words(text) { var t = String(text || '').trim(); return t ? t.split(/\s+/).length : 0; }

    function renderDocuments() {
        if (UI.essayId) { var e = findDoc(UI.essayId); if (e) return renderEssayEditor(e); UI.essayId = null; }
        var canAdd = isC() || isStudent();
        var files = P.documents.filter(function (d) { return d.kind === 'file'; });
        var essays = P.documents.filter(function (d) { return d.kind === 'essay'; });
        var tab = UI.docTab;

        var tools = '<div class="jp-tools"><div class="jp-seg" role="group" aria-label="Show">' +
            [['all', 'All'], ['essays', 'Essays · ' + essays.length], ['files', 'Files · ' + files.length]].map(function (x) { return '<button class="' + (tab === x[0] ? 'on' : '') + '" data-act="doc-tab" data-v="' + x[0] + '">' + x[1] + '</button>'; }).join('') + '</div>' +
            '<span class="sp"></span>' +
            (canAdd ? '<button class="jp-btn ghost" data-act="new-essay">' + ICO.pen + ' Write an essay</button><button class="jp-btn" data-act="upload-doc">' + ICO.upload + ' Upload a file</button>' : '') + '</div>';

        var essayHtml = '';
        if (tab !== 'files') {
            essayHtml = '<section class="jp-card"><div class="jp-card-h"><div><h2>Essays</h2><p>' +
                (isStudent() ? 'Write your SOP and essays here. Send one for review when it\'s ready and your counsellor\'s feedback shows up on it.'
                    : 'Essays ' + esc(P.student.firstName) + ' has written. Open one to read it, then approve it or send it back with feedback.') + '</p></div></div>' +
                (essays.length ? '<div class="jp-essays">' + essays.map(function (d) {
                    return '<button class="jp-essay" data-act="open-essay" data-id="' + d.id + '"><span class="top"><span class="cat">' + esc(d.category) + '</span><span class="pill ' + essayPill(d.status) + '">' + esc(essayLabel(d.status)) + '</span></span>' +
                        '<h3>' + esc(d.title) + '</h3><span class="meta">' + (d.university ? esc(d.university) + ' · ' : '') + plural(d.words || 0, 'word', 'words') + ' · updated ' + esc(when2(d.updatedAt)) + '</span>' +
                        (d.feedback ? '<span class="fb"><b>Feedback:</b> ' + esc(d.feedback.length > 110 ? d.feedback.slice(0, 110) + '…' : d.feedback) + '</span>' : '') + '</button>';
                }).join('') + '</div>' : '<div class="jp-empty"><b>No essays yet</b>' + (canAdd ? 'Choose "Write an essay" to start one.' : 'Nothing has been written yet.') + '</div>') + '</section>';
        }

        var fileHtml = '';
        if (tab !== 'essays') {
            fileHtml = '<section class="jp-card flush"><div class="jp-card-h pad"><div><h2>Files</h2><p>' +
                (isStudent() ? 'Transcripts, score reports, passport, financial papers and anything else your applications need. PDF, Word or JPG/PNG, up to ' + Math.round(DT.limits.maxKb / 1024) + ' MB each.'
                    : 'Documents stored on this plan, by the student and by the team.') + '</p></div></div>' +
                (files.length ? '<div class="jp-files">' + files.map(function (d) {
                    var armed = UI.armed === 'rm-doc-' + d.id;
                    return '<div class="jp-file"><span class="jp-file-ico k-' + fileKind(d) + '">' + ICO.doc + '<small>' + fileKind(d).toUpperCase() + '</small></span>' +
                        '<div class="w"><span class="t">' + esc(d.title) + '</span><span class="s">' + esc(d.fileName || '') + ' · ' + esc(kb(d.size)) + '</span></div>' +
                        '<span class="jp-owner-chip">' + esc(d.category) + '</span>' +
                        '<span class="u">' + (d.university ? esc(d.university) : '<span class="muted">All universities</span>') + '</span>' +
                        '<span class="by"><b>' + esc(d.byName) + '</b><small>' + esc(when2(d.createdAt)) + '</small></span>' +
                        '<span class="acts"><a class="jp-btn ghost sm" href="' + esc(d.url) + '">Download</a>' +
                        (d.canDelete ? '<button class="jp-btn danger sm' + (armed ? ' armed' : '') + '" data-act="rm-doc" data-id="' + d.id + '">' + (armed ? 'Click again' : 'Remove') + '</button>' : '') + '</span></div>';
                }).join('') + '</div>' : '<div class="jp-empty"><b>No files yet</b>' + (canAdd ? 'Choose "Upload a file" to add one.' : 'Nothing has been uploaded yet.') + '</div>') + '</section>';
        }
        return tools + essayHtml + fileHtml;
    }

    function renderEssayEditor(saved) {
        var editable = saved.canEdit;
        var dr = UI.draft && UI.draft.id === saved.id ? UI.draft : null;
        var d = dr ? Object.assign({}, saved, { title: dr.title, category: dr.category, applicationId: dr.application_id, body: dr.body, feedback: dr.feedback != null ? dr.feedback : saved.feedback }) : saved;
        var armedBack = UI.armed === 'essay-back';
        var head = '<div class="jp-tools"><button class="jp-btn ghost sm' + (armedBack ? ' armed' : '') + '" data-act="essay-back">' + (armedBack ? 'Click again to leave without saving' : '← All documents') + '</button><span class="sp"></span>' +
            '<span class="pill ' + essayPill(d.status) + '">' + esc(essayLabel(d.status)) + '</span></div>';
        var feedback = saved.feedback && !isC()
            ? '<div class="jp-feedback ' + (d.status === 'Approved' ? 'ok' : '') + '"><b>' + (d.status === 'Approved' ? 'Approved' : 'Feedback') + (d.feedbackBy ? ' from ' + esc(d.feedbackBy) : '') + (d.feedbackAt ? ' · ' + esc(when2(d.feedbackAt)) : '') + '</b><p>' + esc(d.feedback) + '</p></div>' : '';
        var meta = '<div class="jp-essay-meta">' +
            '<div><label for="es-title">Title</label><input id="es-title" maxlength="190" value="' + esc(d.title) + '"' + (editable ? '' : ' disabled') + '></div>' +
            '<div><label for="es-cat">Type</label><select id="es-cat"' + (editable ? '' : ' disabled') + '>' + DT.essayCategories.map(function (c) { return '<option' + (c === d.category ? ' selected' : '') + '>' + esc(c) + '</option>'; }).join('') + '</select></div>' +
            '<div><label for="es-uni">University</label><select id="es-uni"' + (editable ? '' : ' disabled') + '>' + uniOptions(d.applicationId) + '</select></div></div>';
        var editor = '<textarea id="es-body" class="jp-essay-body" maxlength="' + DT.limits.essayMaxChars + '" placeholder="Start writing here…"' + (editable ? '' : ' readonly') + '>' + esc(d.body || '') + '</textarea>' +
            '<div class="jp-essay-foot"><span class="num" id="es-count">' + plural(words(d.body), 'word', 'words') + '</span><span id="es-state" class="muted">' + (d.updatedAt ? 'Saved ' + esc(when(d.updatedAt)) : '') + '</span></div>';
        var actions = '';
        if (editable) {
            actions += '<button class="jp-btn ghost" data-act="essay-save">Save draft</button>';
            if (isStudent() && d.status !== 'Submitted') actions += '<button class="jp-btn" data-act="essay-submit">Send for review</button>';
            if (d.canDelete) actions += '<button class="jp-btn danger' + (UI.armed === 'rm-doc-' + d.id ? ' armed' : '') + '" data-act="rm-doc" data-id="' + d.id + '">' + (UI.armed === 'rm-doc-' + d.id ? 'Click again to delete' : 'Delete essay') + '</button>';
        }
        var review = isC() ? '<section class="jp-card jp-review"><div class="jp-card-h"><div><h2>Your review</h2><p>The student sees this feedback on the essay.</p></div></div>' +
            '<textarea id="es-feedback" maxlength="5000" placeholder="What works, what to change…">' + esc(d.feedback || '') + '</textarea>' +
            '<div class="jp-actions left"><button class="jp-btn danger" data-act="essay-review" data-v="Needs changes">Send back for changes</button><button class="jp-btn" data-act="essay-review" data-v="Approved">Approve essay</button></div></section>' : '';
        var info = isStudent() && d.status === 'Submitted' ? '<div class="jp-alert">Sent to your counsellor for review. You can still keep improving it while you wait.</div>' : '';
        return head + '<section class="jp-card">' + meta + feedback + info + editor + (actions ? '<div class="jp-actions left">' + actions + '</div>' : '') + '</section>' + review;
    }

    function essayValues() {
        var uni = val('es-uni');
        return { title: val('es-title'), category: val('es-cat'), application_id: uni ? parseInt(uni, 10) : null, body: (document.getElementById('es-body') || {}).value || '' };
    }
    function saveEssay(extra, okMsg) {
        var d = findDoc(UI.essayId); if (!d) return Promise.resolve();
        var body = Object.assign(essayValues(), extra || {});
        return api('PATCH', docUrl(d.id), body).then(function (res) {
            replaceDoc(res.document); UI.essayDirty = false; UI.draft = null; render(); if (okMsg) toast(okMsg);
        }, function (err) { toast(err.message, true); });
    }

    /* ---- help ---- */
    function renderGuide() {
        var g = isStudent() ? [
            ['Your journey', 'Seven stages you go through once, from the first consultation to flying out. The Dashboard shows where you are.'],
            ['Your universities', 'Each university has its own checklist: requirements, documents, the application itself, and the offer and visa.'],
            ['Ticking things off', 'You can update the tasks that are yours. When one is done, tick it and your counsellor is told.'],
            ['Dates and notes', 'Your counsellor sets the target dates and leaves notes on tasks. Open any task to see what to prepare.'],
            ['Late tasks', 'Anything past its date turns red. If a date no longer works, talk to your counsellor and they\'ll move it.'],
            ['Documents & essays', 'Upload your documents and write your SOP and essays under My documents. Send an essay for review and your counsellor\'s feedback appears on it.'],
            ['Your password', 'Change it any time from the menu. If you forget it, ask your counsellor to reset it.']
        ] : [
            ['Include or exclude', 'Every activity has an Include box. Untick what this student or university doesn\'t need; it greys out and drops out of every count.'],
            ['Your own tasks and stages', 'Open a stage and choose “Add a task to this stage”, or use “Add a stage” for a whole new stage. Added tasks and stages can be edited or removed; ODA\'s own can only be switched off.'],
            ['Core journey', 'Seven one-time phases, from Discovery to Pre-Departure, completed once per student however many universities they apply to.'],
            ['University blocks', 'One per university or programme. Switch on Interview and Portfolio only where required, and visa steps once a seat is confirmed.'],
            ['Pre-filled guidance', 'Activity, description, owner and document checklist are ODA\'s standard content. Change the owner only when a case differs.'],
            ['The student\'s login', 'Created when the planner is started. The student sees what is switched on and updates the tasks they own; each update lands on the lead timeline.'],
            ['Documents & essays', 'Everything the student uploads or writes is under Documents. Open an essay to approve it or send it back with feedback; uploads and essays sent for review land on the lead timeline.'],
            ['Progress', '% complete = Completed ÷ (Included − Not Applicable), the same rule as the planner workbook.']
        ];
        return '<div class="jp-guide">' + g.map(function (x) { return '<div><h4>' + esc(x[0]) + '</h4><p>' + esc(x[1]) + '</p></div>'; }).join('') + '</div>';
    }

    /* ------------------------------------------------------------ saving */
    function saveActivity(scope, appId, key, changes, okMsg) {
        var a = actFor(scope, appId, key);
        if (!a) return Promise.resolve();
        var before = JSON.parse(JSON.stringify(a));
        Object.keys(changes).forEach(function (f) { a[f] = changes[f]; });
        if (changes.status === 'Completed' && !a.done) a.done = fmtIso(today());
        render();
        var body = Object.assign({ scope: scope, key: key }, scope === 'app' ? { application_id: appId } : {}, changes);
        return api('PATCH', P.endpoints.activity, body).then(function (res) {
            if (res.activity) Object.assign(a, res.activity);
            render();
            if (okMsg) toast(okMsg);
        }, function (err) {
            Object.assign(a, before); render(); toast(err.message, true);
        });
    }
    function fmtIso(d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }

    function appUrl(id) { return P.endpoints.application.replace('__ID__', id); }
    function replaceApp(app) {
        var i = P.apps.findIndex(function (x) { return x.id === app.id; });
        if (i >= 0) P.apps[i] = app; else P.apps.push(app);
    }
    function copyText(text, okMsg) {
        if (navigator.clipboard) navigator.clipboard.writeText(text).then(function () { toast(okMsg); }, function () { toast('Couldn\'t copy. Select the text and copy it yourself.', true); });
        else toast('Couldn\'t copy. Select the text and copy it yourself.', true);
    }

    /* ------------------------------------------------------------ modals */
    function modal(html) {
        document.getElementById('jp-modal').innerHTML = '<div class="jp-backdrop" data-act="close-modal"><div class="jp-dialog" role="dialog" aria-modal="true">' + html + '</div></div>';
        var f = document.querySelector('.jp-dialog input, .jp-dialog select'); if (f) setTimeout(function () { f.focus(); }, 20);
    }
    function closeModal() { document.getElementById('jp-modal').innerHTML = ''; }
    function val(id) { var e = document.getElementById(id); return e ? e.value.trim() : ''; }
    function modalError(msg) { var e = document.querySelector('.jp-dialog .err'); if (e) e.textContent = msg; }
    function uniForm(a) {
        a = a || { university: '', country: '', program: '', fit: '' };
        return '<label for="u-name">University</label><input id="u-name" maxlength="150" value="' + esc(a.university) + '" placeholder="e.g. University of Melbourne">' +
            '<div class="two"><div><label for="u-country">Country</label><input id="u-country" maxlength="80" value="' + esc(a.country) + '" placeholder="e.g. Australia"></div>' +
            '<div><label for="u-fit">Fit</label><select id="u-fit"><option value="">Not set</option>' + Object.keys(T.fits).map(function (f) { return '<option value="' + f + '"' + (a.fit === f ? ' selected' : '') + '>' + T.fits[f] + '</option>'; }).join('') + '</select></div></div>' +
            '<label for="u-prog">Programme / course</label><input id="u-prog" maxlength="190" value="' + esc(a.program) + '" placeholder="e.g. Master of Data Science"><p class="err" role="alert"></p>';
    }

    function goTo(view) {
        UI.menu = false;
        if (location.hash !== '#' + view) { location.hash = view; } else { fromHash(); render(); }
    }
    function scrollToEl(id) { setTimeout(function () { var el = document.getElementById(id); if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 60); }

    /* ------------------------------------------------------------ events */
    window.addEventListener('hashchange', function () { fromHash(); UI.menu = false; UI.armed = null; if (UI.view !== 'documents') { UI.essayId = null; UI.essayDirty = false; UI.draft = null; } render(); window.scrollTo(0, 0); });

    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-act]'); if (!el) return;
        var act = el.dataset.act;
        if (UI.armed && ['rm-uni', 'reset-password', 'toggle-login', 'rm-doc', 'essay-back', 'rm-task', 'rm-stage'].indexOf(act) < 0) { UI.armed = null; }
        var appId = el.dataset.app ? parseInt(el.dataset.app, 10) : null;

        switch (act) {
            case 'menu': UI.menu = !UI.menu; render(); return;
            case 'close-menu': UI.menu = false; render(); return;
            case 'close-modal': if (e.target === el) closeModal(); return;
            case 'cancel': closeModal(); return;
            case 'toggle-phase': {
                var isOpen = !!document.querySelector('#ph-' + el.dataset.p + '.open');
                UI.openPhases[el.dataset.p] = !isOpen; render(); return;
            }
            case 'expand-all': case 'collapse-all':
                T.phases.forEach(function (p) { UI.openPhases[p.key] = act === 'expand-all'; }); render(); return;
            case 'jump-phase':
                UI.openPhases[el.dataset.p] = true;
                if (UI.view !== 'journey') goTo('journey'); else render();
                scrollToEl('ph-' + el.dataset.p);
                return;
            case 'toggle-row': { var k = rowKey(el.dataset.scope, appId, el.dataset.key); UI.openRows[k] = !UI.openRows[k]; render(); return; }
            case 'jump-item': {
                UI.owner = 'all'; UI.status = 'all';
                UI.openRows[rowKey(el.dataset.scope, appId, el.dataset.key)] = true;
                if (el.dataset.scope === 'core') {
                    var p = PHASE_OF[el.dataset.key]; UI.openPhases[p.key] = true;
                    goTo('journey'); scrollToEl('ph-' + p.key);
                } else { UI.appId = appId; goTo('universities'); scrollToEl('jp-block'); }
                return;
            }
            case 'dl': UI.dl = el.dataset.v; render(); return;
            case 'doc-tab': UI.docTab = el.dataset.v; render(); return;
            case 'open-essay': UI.essayId = parseInt(el.dataset.id, 10); UI.essayDirty = false; UI.draft = null; render(); window.scrollTo(0, 0); return;
            case 'essay-back':
                if (UI.essayDirty && UI.armed !== 'essay-back') { UI.armed = 'essay-back'; render(); return; }
                UI.armed = null; UI.essayId = null; UI.essayDirty = false; UI.draft = null; render(); return;
            case 'pick-uni': UI.appId = parseInt(el.dataset.u, 10); render(); scrollToEl('jp-block'); return;
            case 'open-uni': UI.appId = parseInt(el.dataset.u, 10); goTo('universities'); return;
            case 'tick': {
                var a = actFor(el.dataset.scope, appId, el.dataset.key);
                if (a && canEdit(a)) saveActivity(el.dataset.scope, appId, el.dataset.key, { status: 'Completed' }, isStudent() ? 'Nice one. Marked as done.' : 'Completed');
                return;
            }
        }

        if (isC() || isStudent()) {
            switch (act) {
                case 'essay-save': saveEssay({}, 'Draft saved'); return;
                case 'essay-submit': saveEssay({ submit: true }, 'Sent to your counsellor for review'); return;
                case 'essay-review': {
                    var fb = (document.getElementById('es-feedback') || {}).value || '';
                    if (el.dataset.v === 'Needs changes' && !fb.trim()) { toast('Write some feedback so the student knows what to change.', true); return; }
                    saveEssay({ review_status: el.dataset.v, feedback: fb }, el.dataset.v === 'Approved' ? 'Essay approved' : 'Sent back with your feedback');
                    return;
                }
                case 'rm-doc': {
                    var did = parseInt(el.dataset.id, 10);
                    if (UI.armed !== 'rm-doc-' + did) { UI.armed = 'rm-doc-' + did; render(); return; }
                    UI.armed = null;
                    api('DELETE', docUrl(did)).then(function () {
                        P.documents = P.documents.filter(function (x) { return x.id !== did; });
                        if (UI.essayId === did) UI.essayId = null;
                        render(); toast('Removed');
                    }, function (err) { render(); toast(err.message, true); });
                    return;
                }
                case 'upload-doc':
                    modal('<h3>Upload a file</h3><p class="sub">PDF, Word or JPG/PNG, up to ' + Math.round(DT.limits.maxKb / 1024) + ' MB.</p>' +
                        '<label for="up-file">File</label><input id="up-file" type="file" accept="' + DT.limits.extensions.map(function (x) { return '.' + x; }).join(',') + '">' +
                        '<label for="up-cat">What is it?</label><select id="up-cat">' + DT.fileCategories.map(function (c) { return '<option>' + esc(c) + '</option>'; }).join('') + '</select>' +
                        '<label for="up-title">Name <span style="font-weight:500">(optional)</span></label><input id="up-title" maxlength="190" placeholder="e.g. Class 12 mark sheet">' +
                        '<label for="up-uni">University</label><select id="up-uni">' + uniOptions(null) + '</select><p class="err" role="alert"></p>' +
                        '<div class="jp-actions"><button class="jp-btn ghost" data-act="cancel">Cancel</button><button class="jp-btn" data-act="save-upload">Upload</button></div>');
                    return;
                case 'save-upload': {
                    if (UI.busy) return;
                    var input = document.getElementById('up-file'), f = input && input.files[0];
                    if (!f) { modalError('Choose a file to upload.'); return; }
                    if (f.size > DT.limits.maxKb * 1024) { modalError('That file is larger than ' + Math.round(DT.limits.maxKb / 1024) + ' MB. Upload a smaller copy.'); return; }
                    var form = new FormData();
                    form.append('kind', 'file'); form.append('file', f); form.append('category', val('up-cat'));
                    if (val('up-title')) form.append('title', val('up-title'));
                    if (val('up-uni')) form.append('application_id', val('up-uni'));
                    UI.busy = true; el.disabled = true; el.textContent = 'Uploading…';
                    upload(P.endpoints.documents, form).then(function (res) {
                        replaceDoc(res.document); closeModal(); UI.docTab = UI.docTab === 'essays' ? 'all' : UI.docTab; render(); toast('Uploaded');
                    }, function (err) { modalError(err.message); el.disabled = false; el.textContent = 'Upload'; }).then(function () { UI.busy = false; });
                    return;
                }
                case 'new-essay':
                    modal('<h3>Write an essay</h3><p class="sub">Start a draft. You can come back to it any time.</p>' +
                        '<label for="ne-cat">Type</label><select id="ne-cat">' + DT.essayCategories.map(function (c) { return '<option>' + esc(c) + '</option>'; }).join('') + '</select>' +
                        '<label for="ne-title">Title</label><input id="ne-title" maxlength="190" placeholder="e.g. HEC Paris — why this programme">' +
                        '<label for="ne-uni">University</label><select id="ne-uni">' + uniOptions(null) + '</select><p class="err" role="alert"></p>' +
                        '<div class="jp-actions"><button class="jp-btn ghost" data-act="cancel">Cancel</button><button class="jp-btn" data-act="save-new-essay">Start writing</button></div>');
                    return;
                case 'save-new-essay': {
                    if (UI.busy) return;
                    var body = { kind: 'essay', category: val('ne-cat'), title: val('ne-title') || null, application_id: val('ne-uni') ? parseInt(val('ne-uni'), 10) : null };
                    UI.busy = true; el.disabled = true;
                    api('POST', P.endpoints.documents, body).then(function (res) {
                        replaceDoc(res.document); closeModal(); UI.essayId = res.document.id; UI.essayDirty = false;
                        if (UI.view !== 'documents') goTo('documents'); else render();
                    }, function (err) { modalError(err.message); el.disabled = false; }).then(function () { UI.busy = false; });
                    return;
                }
            }
        }

        if (!isC()) return; /* everything below is the counsellor's */

        switch (act) {
            case 'add-stage': case 'edit-stage': {
                var st = act === 'edit-stage' ? T.phases.find(function (x) { return x.key === el.dataset.p; }) : null;
                var standard = T.phases.filter(function (x) { return !x.custom; });
                var after = st ? (st.after || '') : (standard.length ? standard[standard.length - 1].key : '');
                modal('<h3>' + (st ? 'Edit stage' : 'Add a stage') + '</h3><p class="sub">' + (st ? 'Rename this stage or move it.' : 'Adds a stage to this student\'s journey only. Then add tasks to it.') + '</p>' +
                    '<label for="sg-name">Stage name</label><input id="sg-name" maxlength="80" value="' + esc(st ? st.name : '') + '" placeholder="e.g. Scholarship interviews">' +
                    '<label for="sg-when">When <span style="font-weight:500">(optional)</span></label><input id="sg-when" maxlength="120" value="' + esc(st && st.timeline !== 'Added by your counsellor' ? st.timeline : '') + '" placeholder="e.g. 4–6 months before intake">' +
                    '<label for="sg-after">Place it</label><select id="sg-after">' + standard.map(function (x, i) { return '<option value="' + x.key + '"' + (after === x.key ? ' selected' : '') + '>After ' + (i + 1) + '. ' + esc(x.name) + '</option>'; }).join('') +
                    '<option value=""' + (st && !st.after ? ' selected' : '') + '>At the end of the journey</option></select>' +
                    '<p class="err" role="alert"></p><div class="jp-actions"><button class="jp-btn ghost" data-act="cancel">Cancel</button><button class="jp-btn" data-act="save-stage"' + (st ? ' data-p="' + st.key + '"' : '') + '>' + (st ? 'Save' : 'Add stage') + '</button></div>');
                return;
            }
            case 'save-stage': {
                if (UI.busy) return;
                var sgName = val('sg-name');
                if (!sgName) { modalError('Give the stage a name.'); return; }
                var sgKey = el.dataset.p;
                var sgBody = { name: sgName, timeline: val('sg-when'), after: val('sg-after') || null };
                UI.busy = true; el.disabled = true;
                api(sgKey ? 'PATCH' : 'POST', sgKey ? P.endpoints.stage.replace('__KEY__', sgKey) : P.endpoints.stages, sgBody).then(function (res) {
                    var byKey = {}; T.phases.forEach(function (x) { byKey[x.key] = x; });
                    var old = byKey[res.stage.key];
                    byKey[res.stage.key] = Object.assign({}, res.stage, { activities: old ? old.activities : [] });
                    T.phases = res.order.map(function (k) { return byKey[k]; }).filter(Boolean);
                    T.phases.forEach(function (x) { x.activities.forEach(function (a) { PHASE_OF[a.key] = x; }); });
                    UI.openPhases[res.stage.key] = true; closeModal(); render();
                    toast(sgKey ? 'Stage updated' : 'Stage added. Now add its tasks.');
                    if (!sgKey) scrollToEl('ph-' + res.stage.key);
                }, function (err) { modalError(err.message); el.disabled = false; }).then(function () { UI.busy = false; });
                return;
            }
            case 'rm-stage': {
                var rs = el.dataset.p;
                if (UI.armed !== 'rm-stage-' + rs) { UI.armed = 'rm-stage-' + rs; render(); return; }
                UI.armed = null;
                api('DELETE', P.endpoints.stage.replace('__KEY__', rs)).then(function () {
                    var gone = T.phases.find(function (x) { return x.key === rs; });
                    (gone ? gone.activities : []).forEach(function (a) { delete CORE_DEFS[a.key]; delete PHASE_OF[a.key]; delete P.core[a.key]; });
                    T.phases = T.phases.filter(function (x) { return x.key !== rs; });
                    render(); toast('Stage removed');
                }, function (err) { render(); toast(err.message, true); });
                return;
            }
            case 'add-task': case 'edit-task': {
                var editing = act === 'edit-task' ? CORE_DEFS[el.dataset.key] : null;
                var phaseKey = editing ? editing.phase : el.dataset.p;
                var ph = T.phases.find(function (x) { return x.key === phaseKey; });
                var tAct = editing ? P.core[editing.key] : null;
                modal('<h3>' + (editing ? 'Edit task' : 'Add a task') + '</h3><p class="sub">' + (editing ? 'Stage: ' : 'Adds a task to ') + esc(ph ? ph.name : '') + (editing ? '' : ' for this student only. It counts toward progress and the student sees it.') + '</p>' +
                    '<label for="tk-name">Task</label><input id="tk-name" maxlength="150" value="' + esc(editing ? editing.name : '') + '" placeholder="e.g. Book a campus visit">' +
                    '<label for="tk-desc">What to do <span style="font-weight:500">(optional)</span></label><input id="tk-desc" maxlength="500" value="' + esc(editing ? editing.desc : '') + '" placeholder="A line the student will read">' +
                    '<label for="tk-docs">Documents needed <span style="font-weight:500">(optional)</span></label><input id="tk-docs" maxlength="190" value="' + esc(editing && editing.docs !== 'None' ? editing.docs : '') + '" placeholder="e.g. Visit confirmation">' +
                    (editing ? '' : '<div class="two"><div><label for="tk-owner">Who does it</label><select id="tk-owner">' + T.owners.map(function (o) { return '<option' + (o === 'Student' ? ' selected' : '') + '>' + esc(o) + '</option>'; }).join('') + '</select></div>' +
                        '<div><label for="tk-target">Target date <span style="font-weight:500">(optional)</span></label><input id="tk-target" type="date"></div></div>') +
                    '<p class="err" role="alert"></p><div class="jp-actions"><button class="jp-btn ghost" data-act="cancel">Cancel</button><button class="jp-btn" data-act="save-task" data-p="' + esc(phaseKey) + '"' + (editing ? ' data-key="' + esc(editing.key) + '"' : '') + '>' + (editing ? 'Save' : 'Add task') + '</button></div>');
                return;
            }
            case 'save-task': {
                if (UI.busy) return;
                var name = val('tk-name');
                if (!name) { modalError('Give the task a name.'); return; }
                var key = el.dataset.key, pk = el.dataset.p;
                UI.busy = true; el.disabled = true;
                if (key) {
                    api('PATCH', P.endpoints.task.replace('__KEY__', key), { name: name, desc: val('tk-desc'), docs: val('tk-docs') }).then(function (res) {
                        var ph2 = T.phases.find(function (x) { return x.key === res.task.phase; });
                        var i = ph2.activities.findIndex(function (a) { return a.key === key; });
                        if (i >= 0) ph2.activities[i] = res.task;
                        CORE_DEFS[key] = res.task; closeModal(); render(); toast('Task updated');
                    }, function (err) { modalError(err.message); el.disabled = false; }).then(function () { UI.busy = false; });
                } else {
                    api('POST', P.endpoints.tasks, { phase: pk, name: name, desc: val('tk-desc'), docs: val('tk-docs'), owner: val('tk-owner'), target: val('tk-target') || null }).then(function (res) {
                        var ph3 = T.phases.find(function (x) { return x.key === pk; });
                        ph3.activities.push(res.task);
                        CORE_DEFS[res.task.key] = res.task; PHASE_OF[res.task.key] = ph3; P.core[res.task.key] = res.activity;
                        UI.openPhases[pk] = true; UI.openRows[rowKey('core', null, res.task.key)] = false;
                        closeModal(); render(); toast('Task added');
                    }, function (err) { modalError(err.message); el.disabled = false; }).then(function () { UI.busy = false; });
                }
                return;
            }
            case 'rm-task': {
                var rk = el.dataset.key;
                if (UI.armed !== 'rm-task-' + rk) { UI.armed = 'rm-task-' + rk; render(); return; }
                UI.armed = null;
                api('DELETE', P.endpoints.task.replace('__KEY__', rk)).then(function () {
                    T.phases.forEach(function (x) { x.activities = x.activities.filter(function (a) { return a.key !== rk; }); });
                    delete CORE_DEFS[rk]; delete PHASE_OF[rk]; delete P.core[rk];
                    render(); toast('Task removed');
                }, function (err) { render(); toast(err.message, true); });
                return;
            }
            case 'copy-creds': {
                var c = UI.credentials;
                copyText('Your One Degree journey planner\nSign in at: ' + P.login.loginUrl + '\nEmail: ' + c.email + '\nTemporary password: ' + c.password + '\nYou\'ll choose your own password the first time you sign in.', 'Copied. Paste it into a message to the student.');
                return;
            }
            case 'dismiss-creds': UI.credentials = null; render(); return;
            case 'toggle-inc': {
                var ai = actFor(el.dataset.scope, appId, el.dataset.key);
                if (ai) saveActivity(el.dataset.scope, appId, el.dataset.key, { inc: !ai.inc }, ai.inc ? 'Excluded from the plan' : 'Included in the plan');
                return;
            }
            case 'visa-on':
                saveActivity('app', parseInt(el.dataset.u, 10), 'visa-submitted', { inc: true })
                    .then(function () { return saveActivity('app', parseInt(el.dataset.u, 10), 'visa-approved', { inc: true }, 'Visa steps are on'); });
                return;
            case 'reset-password':
                if (UI.armed !== 'reset') { UI.armed = 'reset'; render(); return; }
                UI.armed = null;
                api('POST', P.endpoints.resetPassword).then(function (res) {
                    UI.credentials = res.credentials; P.login.mustChange = true; render(); toast('New password ready. Send it to the student.');
                }, function (err) { render(); toast(err.message, true); });
                return;
            case 'toggle-login': {
                var turnOn = !P.login.active;
                if (!turnOn && UI.armed !== 'toggle') { UI.armed = 'toggle'; render(); return; }
                UI.armed = null;
                api('PATCH', P.endpoints.toggleLogin, { active: turnOn }).then(function (res) {
                    P.login.active = res.active; render(); toast(res.active ? 'Login switched on' : 'Login switched off. The student can\'t sign in.');
                }, function (err) { render(); toast(err.message, true); });
                return;
            }
            case 'rm-uni': {
                var id = parseInt(el.dataset.u, 10);
                if (UI.armed !== 'rm-' + id) { UI.armed = 'rm-' + id; render(); return; }
                UI.armed = null;
                var gone = P.apps.find(function (x) { return x.id === id; });
                api('DELETE', appUrl(id)).then(function () {
                    P.apps = P.apps.filter(function (x) { return x.id !== id; });
                    UI.appId = P.apps[0] ? P.apps[0].id : null; render(); toast((gone ? gone.university : 'University') + ' removed');
                }, function (err) { render(); toast(err.message, true); });
                return;
            }
            case 'add-uni':
                modal('<h3>Add a university</h3><p class="sub">Creates a checklist with ODA\'s 22 standard application activities. Switch activities on or off afterwards.</p>' + uniForm() +
                    '<div class="jp-actions"><button class="jp-btn ghost" data-act="cancel">Cancel</button><button class="jp-btn" data-act="save-uni">Add university</button></div>');
                return;
            case 'edit-uni': {
                var ea = P.apps.find(function (x) { return x.id === parseInt(el.dataset.u, 10); });
                if (ea) modal('<h3>Edit university</h3>' + uniForm(ea) + '<div class="jp-actions"><button class="jp-btn ghost" data-act="cancel">Cancel</button><button class="jp-btn" data-act="save-uni" data-u="' + ea.id + '">Save</button></div>');
                return;
            }
            case 'save-uni': {
                if (UI.busy) return;
                var body = { university: val('u-name'), country: val('u-country'), program: val('u-prog'), fit: val('u-fit') || null };
                if (!body.university) { modalError('Enter the university\'s name.'); return; }
                UI.busy = true; el.disabled = true;
                var editing = el.dataset.u ? parseInt(el.dataset.u, 10) : null;
                api(editing ? 'PATCH' : 'POST', editing ? appUrl(editing) : P.endpoints.applications, body).then(function (res) {
                    replaceApp(res.application); UI.appId = res.application.id; closeModal();
                    if (UI.view !== 'universities') goTo('universities'); else render();
                    toast(editing ? 'Saved' : res.application.university + ' added');
                }, function (err) { modalError(err.message); el.disabled = false; }).then(function () { UI.busy = false; });
                return;
            }
            case 'edit-details': {
                var s = P.student;
                modal('<h3>Plan details</h3><p class="sub">Shown on the student\'s dashboard.</p>' +
                    '<div class="two"><div><label for="d-level">Study level</label><select id="d-level"><option value="">Not set</option>' + T.levels.map(function (l) { return '<option' + (s.level === l ? ' selected' : '') + '>' + l + '</option>'; }).join('') + '</select></div>' +
                    '<div><label for="d-intake">Target intake</label><input id="d-intake" maxlength="60" value="' + esc(s.intake) + '" placeholder="e.g. Fall 2027"></div></div>' +
                    '<label for="d-focus">Course direction</label><input id="d-focus" maxlength="150" value="' + esc(s.focus) + '" placeholder="e.g. MiM · MFin"><p class="err" role="alert"></p>' +
                    '<div class="jp-actions"><button class="jp-btn ghost" data-act="cancel">Cancel</button><button class="jp-btn" data-act="save-details">Save</button></div>');
                return;
            }
            case 'save-details': {
                var d = { level: val('d-level') || null, intake: val('d-intake'), focus: val('d-focus') };
                el.disabled = true;
                api('PATCH', P.endpoints.details, d).then(function () {
                    P.student.level = d.level || ''; P.student.intake = d.intake; P.student.focus = d.focus; closeModal(); render(); toast('Saved');
                }, function (err) { modalError(err.message); el.disabled = false; });
                return;
            }
        }
    });

    document.addEventListener('change', function (e) {
        var el = e.target;
        if (el.id === 'f-owner') { UI.owner = el.value; render(); return; }
        if (el.id === 'f-status') { UI.status = el.value; render(); return; }
        if (el.id === 'f-excl') { UI.showExcluded = el.checked; render(); return; }
        if (el.id === 'es-cat' || el.id === 'es-uni') { UI.essayDirty = true; var st = document.getElementById('es-state'); if (st) st.textContent = 'Unsaved changes'; return; }
        if (el.dataset.uf && isC()) {
            var id = parseInt(el.dataset.u, 10), body = {}; body[el.dataset.uf] = el.value || null;
            api('PATCH', appUrl(id), body).then(function (res) { replaceApp(res.application); render(); toast('Saved'); }, function (err) { render(); toast(err.message, true); });
            return;
        }
        var f = el.dataset.f; if (!f) return;
        var appId = el.dataset.app ? parseInt(el.dataset.app, 10) : null;
        var a = actFor(el.dataset.scope, appId, el.dataset.key);
        if (!a || !canEdit(a)) return;
        var changes = {}; changes[f] = el.value === '' && (f === 'target' || f === 'done') ? null : el.value;
        if (a[f] === changes[f]) return;
        saveActivity(el.dataset.scope, appId, el.dataset.key, changes,
            f === 'status' && el.value === 'Completed' ? (isStudent() ? 'Nice one. Marked as done.' : 'Completed') : 'Saved');
    });

    document.addEventListener('input', function (e) {
        if (!UI.essayId) return;
        if (['es-body', 'es-title', 'es-feedback'].indexOf(e.target.id) >= 0) {
            UI.essayDirty = true;
            var st = document.getElementById('es-state'); if (st) st.textContent = 'Unsaved changes';
            if (e.target.id === 'es-body') { var c = document.getElementById('es-count'); if (c) c.textContent = plural(words(e.target.value), 'word', 'words'); }
        }
    });
    window.addEventListener('beforeunload', function (e) { if (UI.essayId && UI.essayDirty) { e.preventDefault(); e.returnValue = ''; } });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeModal(); if (UI.menu) { UI.menu = false; render(); } }
    });

    fromHash();
    render();
})();
