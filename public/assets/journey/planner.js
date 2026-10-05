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
        docTab: 'all', essayId: null, essayDirty: false, openDoc: null, refreshing: false,
        cal: { y: null, m: null, sel: null },
        // The Google room made for the open meeting dialog, until it is saved.
        room: null, roomBusy: false, dialogSeq: 0
    };
    P.documents = P.documents || [];
    P.team = P.team || []; P.deadlines = P.deadlines || []; P.meetings = P.meetings || [];
    var DT = P.docTemplate || { fileCategories: [], essayCategories: [], essayStatuses: [], limits: { maxKb: 10240, extensions: [] } };

    /* ------------------------------------------------------------ helpers */
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function isC() { return MODE === 'counsellor'; }
    function isStudent() { return MODE === 'student'; }
    function studentOwns(owner) { return owner.indexOf('Student') >= 0; }
    /*
     * A task is owned either by a role ODA always has — the student, the
     * counsellor, the university — or by one of the people named on this
     * file. A person is stored as "member:<their key>", never as their name,
     * so renaming them doesn't orphan the work they hold; the name is looked
     * up here each time it is drawn.
     */
    var PEOPLE = {};
    function refreshPeople() {
        PEOPLE = {};
        T.ownerPeople = (P.team || []).map(function (m) { return { value: 'member:' + m.key, name: m.name, role: m.role }; });
        T.ownerPeople.forEach(function (m) { PEOPLE[m.value] = m; });
    }
    function isMemberOwner(owner) { return /^member:t-/.test(String(owner == null ? '' : owner)); }
    function ownerName(owner) {
        if (!isMemberOwner(owner)) return owner;
        var m = PEOPLE[owner];
        return m ? m.name : 'No longer on the file';
    }
    /** What the owner chip says to this reader. */
    function ownerLabel(owner) {
        if (isMemberOwner(owner)) return ownerName(owner);
        return isStudent() && studentOwns(owner) ? owner.replace('Student', 'You') : owner;
    }
    function ownerIsMine(owner) { return isStudent() && !isMemberOwner(owner) && studentOwns(owner); }
    /** The owner dropdown: ODA's roles, then everyone named on this file. */
    function ownerOptionsHtml(selected) {
        var html = '<optgroup label="Roles">' + T.owners.map(function (o) {
            return '<option value="' + esc(o) + '"' + (o === selected ? ' selected' : '') + '>' + esc(o) + '</option>';
        }).join('') + '</optgroup>';
        if (T.ownerPeople.length) {
            html += '<optgroup label="People on this file">' + T.ownerPeople.map(function (m) {
                return '<option value="' + esc(m.value) + '"' + (m.value === selected ? ' selected' : '') + '>' + esc(m.name) + ' · ' + esc(m.role) + '</option>';
            }).join('') + '</optgroup>';
        }
        if (isMemberOwner(selected) && !PEOPLE[selected]) {
            html += '<option value="' + esc(selected) + '" selected>No longer on the file</option>';
        }
        return html;
    }
    /** Does this activity match the owner filter? */
    function ownerMatches(owner) {
        if (UI.owner === 'all') return true;
        if (isMemberOwner(UI.owner)) return owner === UI.owner;
        return !isMemberOwner(owner) && owner.indexOf(UI.owner) >= 0;
    }
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
    /* A field holding several things is stored as one string and read back as
       a list, so "Transcripts, essays, 2 LORs" reads as three items rather
       than one run-on line. Commas, semicolons and new lines all separate. */
    function splitList(value) {
        return String(value == null ? '' : value).split(/[,;\n]+/).map(function (x) { return x.trim(); }).filter(Boolean);
    }
    function tagList(list) { return list.map(function (t) { return '<span class="jp-tag">' + esc(t) + '</span>'; }).join(''); }
    /* Progress is counted, never given as a percentage: the planner reports
       how many steps are done out of how many apply, and leans on dates for
       the rest. The bars stay as a picture of the same two numbers. */
    function tally(r) { return r.den ? r.done + ' of ' + r.den : 'Nothing switched on'; }
    function shortTally(r) { return r.den ? r.done + '/' + r.den : '—'; }
    function barWidth(r) { return (r.den > 0 ? Math.round(r.done / r.den * 100) : 0) + '%'; }
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
        refresh: '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M16.5 10a6.5 6.5 0 1 1-1.9-4.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M16.8 3.2v3.3h-3.3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
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
        r.den = r.inc - r.na;
        return r;
    }
    function sum(rs) {
        var t = { inc: 0, done: 0, prog: 0, ns: 0, na: 0 };
        rs.forEach(function (r) { t.inc += r.inc; t.done += r.done; t.prog += r.prog; t.ns += r.ns; t.na += r.na; });
        t.den = t.inc - t.na; return t;
    }
    function phaseActs(p) { return p.activities.map(function (a) { return P.core[a.key]; }); }
    function phaseIncluded(p) { return phaseActs(p).filter(function (a) { return a.inc; }).length; }
    /*
     * The stages this reader actually sees. A counsellor sees all of them,
     * including the ones switched off; everyone else sees only the stages that
     * have something switched on for this student. Every number on the page —
     * the stage badge, "Stage 3 of 5", the route — counts along THIS list, so
     * what a student reads is always 1, 2, 3… in order, never 1, 5, 6, 7.
     */
    function phases() { return isC() ? T.phases : T.phases.filter(phaseIncluded); }
    function appActs(ap) { return Object.keys(ap.acts).map(function (k) { return ap.acts[k]; }); }
    /** Index into phases() of the stage being worked on now, or -1 when all are done. */
    function currentPhase() {
        var list = phases();
        for (var i = 0; i < list.length; i++) { var r = roll(phaseActs(list[i])); if (r.den > 0 && r.done < r.den) return i; }
        return -1;
    }
    function openItems() {
        var out = [];
        phases().forEach(function (p, pi) {
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
    /** Every included task with a target date, finished or not (Not Applicable aside). */
    function datedItems() {
        var out = [];
        phases().forEach(function (p, pi) {
            p.activities.forEach(function (d) {
                var a = P.core[d.key];
                if (a.inc && a.target && a.status !== 'Not Applicable') out.push({ scope: 'core', appId: null, key: d.key, name: d.name, where: 'Stage ' + (pi + 1) + ' · ' + p.short, a: a });
            });
        });
        P.apps.forEach(function (ap) {
            Object.keys(ap.acts).forEach(function (k) {
                var a = ap.acts[k];
                if (a.inc && a.target && a.status !== 'Not Applicable') out.push({ scope: 'app', appId: ap.id, key: k, name: APP_DEFS[k].name, where: ap.university, a: a });
            });
        });
        return out;
    }
    function byDate(x, y) {
        var a = x.a.target, b = y.a.target;
        if (!a && !b) return 0; if (!a) return 1; if (!b) return -1; return a < b ? -1 : a > b ? 1 : 0;
    }
    function overall() {
        return sum(phases().map(function (p) { return roll(phaseActs(p)); }).concat(P.apps.map(function (a) { return roll(appActs(a)); })));
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

    /*
     * Dates on this plan come from four places and the Deadlines list and the
     * Calendar both read all four: the universities' own closing dates, the
     * dates ODA set itself, the target date on each open task, and meetings.
     * A university's closing date is kept on its block, so it is read from
     * there rather than typed twice.
     */
    function byDateKey(a, b) { return a.date < b.date ? -1 : a.date > b.date ? 1 : 0; }
    function uniDeadlines() {
        return P.deadlines.filter(function (d) { return d.kind === 'uni'; })
            .concat(P.apps.filter(function (a) { return a.deadline; }).map(function (a) {
                return { key: 'app-' + a.id, kind: 'uni', what: 'Application closes', who: a.university, date: a.deadline, appId: a.id };
            })).sort(byDateKey);
    }
    function ourDeadlines() { return P.deadlines.filter(function (d) { return d.kind === 'own'; }).slice().sort(byDateKey); }
    function soonMeetings() {
        return P.meetings.filter(function (m) { var d = daysUntil(m.date); return !m.done && d != null && d >= 0 && d <= 7; });
    }
    function pad2(n) { return String(n).padStart(2, '0'); }
    /*
     * How a meeting happens decides what it needs to be reached on. A join
     * link belongs to a video call and nothing else; a number belongs to a
     * phone call, and to meeting in person, where someone always ends up
     * ringing ahead.
     */
    function wantsLink(mode) { return mode === 'Google Meet'; }
    function wantsPhone(mode) { return mode === 'Phone call' || mode === 'In person'; }
    function phoneLabel(mode) { return mode === 'In person' ? 'Contact number' : 'Phone number'; }

    /*
     * The counsellor's own Google account, connected once, makes the real
     * Meet rooms. Three states matter: not set up on this CRM (the counsellor
     * makes the room in Google Meet and pastes its link), not connected yet,
     * and connected but refused by Google.
     */
    function G() { return P.google || { configured: false, connected: false, works: false }; }
    function googleReady() { var g = G(); return !!(g.configured && g.connected && g.works); }
    function connectUrl() {
        return G().connect + '?back=' + encodeURIComponent(location.pathname + location.search + '#calendar');
    }
    function roomHint(mt) {
        var g = G();
        if (!g.configured) return 'Create the room in Google Meet, then paste its link here. <a href="https://meet.google.com/new" target="_blank" rel="noopener">Open Google Meet</a>';
        if (googleReady()) {
            return (mt && mt.link && !mt.room ? 'A link pasted by hand. Clear it to have a real room made in ' : 'A real Google Meet room in ') + esc(g.email) +
                '\'s Google Calendar. Google invites nobody — only the email below goes out.';
        }
        if (g.connected) return '<span class="jp-google-bad">' + esc(g.problem || 'Google stopped accepting your connection.') + '</span> <a class="jp-btn sm" href="' + esc(connectUrl()) + '">Reconnect Google</a>';
        return 'Connect your Google account once and every Google Meet meeting gets a real room by itself. <a class="jp-btn sm" href="' + esc(connectUrl()) + '">Connect Google</a> Or paste a link here.';
    }
    function setRoomHint(html) { var h = document.getElementById('mt-link-hint'); if (h) h.innerHTML = html; }
    /*
     * Ask the counsellor's Google account for a room as soon as Google Meet is
     * the choice and the link box is empty, so there is a real link to copy
     * before anything is saved. A dialog closed in the meantime gives it back.
     */
    function makeRoom() {
        var box = document.getElementById('mt-link');
        if (!googleReady() || UI.roomBusy || UI.room || !box || box.value.trim()) return;
        var seq = UI.dialogSeq;
        UI.roomBusy = true;
        box.disabled = true; box.placeholder = 'Making a Google Meet room…';
        setRoomHint('Making a room in ' + esc(G().email) + '\'s Google Calendar…');
        api('POST', P.endpoints.meetRoom, {
            title: val('mt-title') || null, date: val('mt-date') || null, time: val('mt-time') || null,
            minutes: parseInt(val('mt-mins'), 10) || null
        }).then(function (res) {
            UI.room = { event: res.room, link: res.link };
            var b = document.getElementById('mt-link');
            if (seq !== UI.dialogSeq || !b) { releaseRoom(); return; }
            b.disabled = false; b.placeholder = 'https://meet.google.com/…';
            if (!b.value.trim()) b.value = res.link;
            setRoomHint(roomHint(null));
        }, function (err) {
            var b = document.getElementById('mt-link');
            if (seq !== UI.dialogSeq || !b) return;
            b.disabled = false; b.placeholder = 'https://meet.google.com/…';
            setRoomHint('<span class="jp-google-bad">' + esc(err.message) + '</span> Paste a link instead, or save and a room is tried again.');
        }).then(function () { UI.roomBusy = false; });
    }
    function releaseRoom() {
        var room = UI.room;
        UI.room = null;
        if (room && P.endpoints.meetRoomRelease) api('DELETE', P.endpoints.meetRoomRelease, { room: room.event }).catch(function () {});
    }
    function googleLine() {
        var g = G();
        if (!isC() || !g.configured) return '';
        if (googleReady()) {
            return '<p class="jp-google ok">Google Meet rooms are made in ' + esc(g.email) + '\'s Google Calendar. ' +
                '<button class="jp-btn ghost sm' + (UI.armed === 'google-off' ? ' armed' : '') + '" data-act="google-disconnect">' + (UI.armed === 'google-off' ? 'Click again to disconnect' : 'Disconnect') + '</button></p>';
        }
        if (g.connected) {
            return '<p class="jp-google bad">' + esc(g.problem || 'Google stopped accepting your connection.') + ' <a class="jp-btn sm" href="' + esc(connectUrl()) + '">Reconnect Google</a></p>';
        }
        return '<p class="jp-google">Connect your Google account and every Google Meet meeting gets a real room by itself. <a class="jp-btn sm" href="' + esc(connectUrl()) + '">Connect Google</a></p>';
    }
    function calBox(iso, over) {
        var dt = new Date(iso + 'T00:00:00');
        return '<span class="jp-cal' + (over ? ' over' : '') + '" aria-hidden="true"><span>' + dt.toLocaleDateString('en-GB', { month: 'short' }) + '</span><b class="num">' + dt.getDate() + '</b></span>';
    }

    /* ------------------------------------------------------------ views */
    var VIEWS = [
        { key: 'dashboard', icon: 'dashboard', label: 'Dashboard' },
        { key: 'journey', icon: 'journey', label: function () { return isStudent() ? 'My journey' : 'Core journey'; } },
        { key: 'universities', icon: 'uni', label: 'Universities' },
        { key: 'documents', icon: 'doc', label: function () { return isStudent() ? 'My documents' : 'Documents'; } },
        { key: 'deadlines', icon: 'alarm', label: 'Deadlines' },
        { key: 'calendar', icon: 'calendar', label: 'Calendar' },
        { key: 'owners', icon: 'people', label: function () { return isStudent() ? 'My team' : 'Team'; } },
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
        hideTip();
        var y = window.scrollY;
        if (UI.essayId && UI.essayDirty && document.getElementById('es-body')) {
            UI.draft = Object.assign({ id: UI.essayId, feedback: (document.getElementById('es-feedback') || {}).value }, essayValues());
        }
        var v = views().find(function (x) { return x.key === UI.view; });
        var body = {
            dashboard: renderDashboard, journey: renderJourney, universities: renderUnis,
            deadlines: renderDeadlines, calendar: renderCalendar, owners: renderOwners,
            login: renderLogin, help: renderGuide, documents: renderDocuments
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
                (v.key === 'calendar' && soonMeetings().length ? '<b class="jp-badge gold num" title="Meetings in the next seven days">' + soonMeetings().length + '</b>' : '') +
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
            '<div class="jp-who-bar"><span>Steps done</span><b class="num">' + tally(overall()) + '</b></div><div class="jp-bar side"><i style="width:' + barWidth(overall()) + '"></i></div></div>' +
            '<div class="jp-nav-label">' + (isStudent() ? 'My plan' : 'Planner') + '</div><nav class="jp-nav">' + nav + '</nav>' +
            '<div class="jp-side-foot">' + (s.counsellor ? '<div class="jp-counsellor"><small>' + (isStudent() ? 'Your counsellor' : 'Counsellor') + '</small><b>' + esc(s.counsellor) + '</b></div>' : '') + foot + '</div>' +
            '</aside>';
    }

    function renderBar(v) {
        var s = P.student;
        var sub = {
            dashboard: isStudent() ? 'Hi ' + s.firstName + ', here\'s where your journey stands today.' : 'Where ' + s.firstName + '\'s journey stands today.',
            journey: plural(phases().length, 'stage', 'stages') + ', done once, however many universities you apply to.',
            universities: 'One checklist per university, from requirements to visa.',
            deadlines: 'University dates, our own dates, and every open task that has one.',
            calendar: 'Every date on this plan in one month view, and every meeting booked.',
            documents: isStudent() ? 'Upload your documents and write your essays. Your counsellor sees everything here.' : 'Everything ' + s.firstName + ' has uploaded or written, and what you have added.',
            owners: isStudent() ? 'Who is working on your journey with you.' : 'Who is on this file, and what each side has open.',
            login: 'How ' + s.firstName + ' signs in to their own planner.',
            help: 'How this planner works.'
        }[v.key];
        var right = isStudent()
            ? '<span class="jp-user"><span class="jp-avatar sm">' + esc(initials(s.name)) + '</span><span><b>' + esc(s.name) + '</b><small>Student</small></span></span>'
            : '<span class="jp-user"><span class="jp-avatar sm alt">' + esc(initials(s.counsellor || 'C')) + '</span><span><b>' + (MODE === 'partner' ? 'Partner view' : esc(s.counsellor || 'Counsellor')) + '</b><small>' + (MODE === 'partner' ? 'Read only' : 'Counsellor view') + '</small></span></span>';
        return '<header class="jp-topbar"><button class="jp-menu" data-act="menu" aria-label="Open menu">' + ICO.menu + '</button>' +
            '<div class="jp-topbar-title"><span class="jp-crumb">' + (isStudent() ? 'My plan' : esc(s.name) + (s.leadNumber ? ' · ' + esc(s.leadNumber) : '')) + '</span><h1>' + label(v) + '</h1><p>' + esc(sub) + '</p></div>' +
            '<div class="jp-topbar-actions">' +
            '<button class="jp-refresh' + (UI.refreshing ? ' spin' : '') + (UI.armed === 'refresh' ? ' armed' : '') + '" data-act="refresh"' +
            ' aria-label="Refresh this plan" title="' + (UI.armed === 'refresh' ? 'Click again — you have unsaved changes' : 'Refresh this plan') + '">' + ICO.refresh + '</button>' +
            right + '</div></header>';
    }

    /* ---- dashboard ---- */
    function renderDashboard() {
        var PH = phases();
        var pr = PH.map(function (p) { return roll(phaseActs(p)); });
        var core = sum(pr), apps = sum(P.apps.map(function (a) { return roll(appActs(a)); })), all = sum([core, apps]);
        var cur = currentPhase();
        var items = openItems();
        var late = lateItems();
        var upcoming = items.filter(function (i) { var d = daysUntil(i.a.target); return d != null && d >= 0; }).sort(byDate);
        var C = 2 * Math.PI * 42, off = C * (1 - (all.den ? all.done / all.den : 0));

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
            '<h2>' + (isStudent() ? esc(s.firstName) + ', you have ' + tally(all) + ' steps done' : esc(s.name) + ' has ' + tally(all) + ' steps done') + '</h2>' +
            '<p>' + (cur >= 0 ? 'Stage ' + (cur + 1) + ' of ' + PH.length + ' · ' + esc(PH[cur].name) + (late.length ? ' · ' + plural(late.length, 'task is', 'tasks are') + ' late' : ' · everything is on time') : 'Every stage of the journey is complete.') + '</p>' +
            '<div class="jp-chips">' + chips + '</div>' + actions + '</div>' +
            '<div class="jp-hero-ring"><svg viewBox="0 0 100 100" role="img" aria-label="' + tally(all) + ' steps done"><circle class="trk" cx="50" cy="50" r="42" fill="none" stroke-width="9"/><circle class="val" cx="50" cy="50" r="42" fill="none" stroke-width="9" stroke-linecap="round" stroke-dasharray="' + C.toFixed(1) + '" stroke-dashoffset="' + off.toFixed(1) + '"/></svg>' +
            '<div><b class="num">' + all.done + '</b><small>of ' + all.den + ' done</small></div></div></section>';

        var kpis = '<div class="jp-kpis">' +
            kpi('journey', isStudent() ? 'My journey' : 'Core journey', shortTally(core), plural(core.den, 'task applies', 'tasks apply'), '', barWidth(core)) +
            kpi('uni', 'University applications', shortTally(apps), P.apps.length ? plural(P.apps.length, 'university', 'universities') : 'No universities yet', '', barWidth(apps)) +
            kpi('alarm', isStudent() ? 'Running late' : 'Overdue', String(late.length), late.length ? late.sort(byDate)[0].name : 'Nothing late', late.length ? 'alert' : 'good') +
            kpi('calendar', 'Next date', nextUp ? fmt(nextUp.a.target, { day: 'numeric', month: 'short' }) : '—', nextUp ? nextUp.name : 'Nothing scheduled', 'sun') +
            '</div>';

        var reach = cur >= 0 ? cur : PH.length - 1;
        var nStages = PH.length;
        var fill = nStages > 1 ? (reach / (nStages - 1)) * (100 - 100 / nStages) : 0;
        var route = PH.map(function (p, i) {
            var r = pr[i], complete = r.den > 0 && r.done === r.den;
            var cls = complete ? 'done' : (i === cur ? 'now' : '');
            return '<li class="' + cls + '"><button data-act="jump-phase" data-p="' + p.key + '" aria-label="Stage ' + (i + 1) + ', ' + esc(p.name) + ', ' + tally(r) + ' done">' +
                (i === cur ? '<span class="here">You are here</span>' : '') +
                '<span class="stop">' + (complete ? ICO.check : (i === cur ? ICO.plane : (i + 1))) + '</span>' +
                '<span class="name">' + esc(p.short) + '</span><span class="pct num">' + shortTally(r) + '</span></button></li>';
        }).join('');
        var progress = '<section class="jp-card jp-progress-card"><div class="jp-card-h"><div><h2>Journey progress</h2><p>' +
            plural(nStages, 'stage', 'stages') + ', done once. Choose one to see its tasks.</p></div><a class="jp-link" href="#journey">Open journey</a></div>' +
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
                    '<span class="fit ' + (a.fit || 'unset') + '">' + esc(T.fits[a.fit] || '—') + '</span><span class="jp-progress num"><span class="jp-bar"><i style="width:' + barWidth(r) + '"></i></span>' + shortTally(r) + '</span></button>';
            }).join('') + '</div>' : (isC() ? '<div class="jp-empty"><button class="jp-btn sm" data-act="add-uni">' + ICO.plus + ' Add university</button></div>' : '<div class="jp-empty">Your counsellor adds universities once your shortlist is agreed.</div>')) + '</section>';

        return creds + hero + kpis + progress + changesCard() + '<div class="jp-grid-2">' + nextCard + dlCard + '</div>' + uniCard + (isStudent() ? '' : phaseTable(pr, core));
    }

    /*
     * Recent changes: who changed what, and when, newest first. Lines added
     * since this person last opened the plan are marked New.
     */
    var SEEN_KEY = 'jp-seen:' + location.pathname;
    var SEEN = (function () { try { return parseInt(localStorage.getItem(SEEN_KEY), 10) || 0; } catch (e) { return 0; } })();
    function rememberSeen() {
        var top = (P.changes || []).reduce(function (m, c) { return Math.max(m, c.id); }, 0);
        try { if (top) localStorage.setItem(SEEN_KEY, String(top)); } catch (e) { /* private window */ }
    }
    function changesCard() {
        var list = P.changes || [];
        var shown = UI.allChanges ? list : list.slice(0, 8);
        var fresh = SEEN ? list.filter(function (c) { return c.id > SEEN; }).length : 0;
        var rows = shown.map(function (c) {
            return '<li class="jp-change' + (SEEN && c.id > SEEN ? ' new' : '') + '">' +
                '<span class="jp-change-dot ' + (c.byStudent ? 'student' : 'team') + '" aria-hidden="true"></span>' +
                '<span class="w"><span class="t"><b>' + esc(c.subject) + '</b> <span class="muted">· ' + esc(c.section) + '</span>' + (SEEN && c.id > SEEN ? ' <span class="jp-new">New</span>' : '') + '</span>' +
                '<span class="s">' + esc(c.what) + '</span></span>' +
                '<span class="by"><span>' + esc(c.who) + '</span><span class="muted">' + esc(when(c.at)) + '</span></span></li>';
        }).join('');
        return '<section class="jp-card"><div class="jp-card-h"><div><h2>Recent changes</h2><p>' +
            (list.length ? (fresh ? plural(fresh, 'new change', 'new changes') + ' since you last looked. ' : '') + 'Everything changed on this plan, newest first.' : 'Changes to this plan will show here.') + '</p></div>' +
            (list.length > 8 ? '<button class="jp-btn ghost sm" data-act="toggle-changes">' + (UI.allChanges ? 'Show fewer' : 'Show more') + '</button>' : '') + '</div>' +
            (list.length ? '<ul class="jp-changes">' + rows + '</ul>' : '<div class="jp-empty">Nothing yet.</div>') + '</section>';
    }

    function kpi(icon, k, v, s, cls, width) {
        return '<div class="jp-kpi ' + (cls || '') + '"><div class="jp-kpi-h"><span class="jp-kpi-ico">' + ICO[icon] + '</span><span class="k">' + esc(k) + '</span></div><div class="v num">' + esc(v) + '</div><div class="s">' + esc(s) + '</div>' +
            (width != null ? '<div class="jp-bar thin"><i style="width:' + width + '"></i></div>' : '') + '</div>';
    }

    function taskRow(i) {
        var d = due(daysUntil(i.a.target)), editable = canEdit(i.a);
        return '<div class="jp-task' + (d && d.cls === 'danger' ? ' over' : '') + '">' +
            '<button class="jp-tick" data-act="tick" data-scope="' + i.scope + '" data-app="' + (i.appId || '') + '" data-key="' + esc(i.key) + '"' + (editable ? '' : ' disabled title="Your counsellor completes this one"') + ' aria-label="Mark ' + esc(i.name) + ' as done">' + ICO.check + '</button>' +
            '<div><div class="t">' + esc(i.name) + '</div><div class="w">' + esc(i.where) + (isStudent() ? '' : ' · ' + esc(ownerLabel(i.a.owner))) + (i.a.status !== 'Not Started' ? ' · <span class="jp-state ' + stPill(i.a.status) + '">' + esc(i.a.status) + '</span>' : '') + '</div></div>' +
            (d ? '<span class="pill ' + d.cls + '">' + d.label + '</span>' : '<span class="pill wait">No date</span>') + '</div>';
    }

    function cnt(n) { return '<td class="r num' + (n ? '' : ' zero') + '">' + n + '</td>'; }
    function doneCell(r) { return '<div class="jp-pct"><div class="jp-bar"><i style="width:' + barWidth(r) + '"></i></div><b class="num">' + shortTally(r) + '</b></div>'; }
    function phaseTable(pr, core) {
        var rows = phases().map(function (p, i) {
            var r = pr[i];
            return '<tr class="click" data-act="jump-phase" data-p="' + p.key + '"><td>' + (i + 1) + '. ' + esc(p.name) + '</td>' + cnt(r.inc) + cnt(r.done) + cnt(r.prog) + cnt(r.ns) + cnt(r.na) + '<td>' + doneCell(r) + '</td></tr>';
        }).join('');
        return '<section class="jp-card flush"><div class="jp-card-h pad"><div><h2>Progress by stage</h2><p>Only switched-on activities count. Done counts Completed against Included minus Not Applicable.</p></div></div><div class="jp-table-wrap"><table class="jp-table"><thead><tr><th>Stage</th><th class="r">Included</th><th class="r">Completed</th><th class="r">In progress / Submitted</th><th class="r">Not started</th><th class="r">Not applicable</th><th>Done</th></tr></thead><tbody>' +
            rows + '<tr class="total"><td>Total</td>' + cnt(core.inc) + cnt(core.done) + cnt(core.prog) + cnt(core.ns) + cnt(core.na) + '<td>' + doneCell(core) + '</td></tr></tbody></table></div></section>';
    }

    function rowHead() {
        return '<div class="jp-row jp-row-head" aria-hidden="true">' + (isC() ? '<span></span>' : '') +
            '<span>Task</span><span>Owner</span><span>Status</span><span>Target date</span><span>Timing</span><span></span></div>';
    }

    /*
     * Repeats of an activity — a second internship, a third competition —
     * are their own tasks, listed straight under the first one. These say
     * how many there are and how many are done.
     */
    function repeatsOf(key) {
        var out = [];
        T.phases.forEach(function (p) { p.activities.forEach(function (d) { if (d.parent === key) out.push(d); }); });
        return out;
    }
    function canRepeat(def) {
        var ph = PHASE_OF[def.key];
        return !def.custom && ph && (T.repeatable || []).indexOf(ph.key) >= 0;
    }
    function repeatChip(def, a) {
        var subs = repeatsOf(def.key);
        if (!subs.length) return '';
        var all = [a].concat(subs.map(function (d) { return P.core[d.key]; })).filter(function (x) { return x && x.inc; });
        var done = all.filter(function (x) { return x.status === 'Completed'; }).length;
        return ' <span class="jp-repeat-chip">' + all.length + ' in all · ' + done + ' done</span>';
    }
    /* The upload a task asks for (the signed declaration form), and what is in so far. */
    function uploadBox(def) {
        if (!def.upload) return '';
        var files = (P.documents || []).filter(function (d) { return d.kind === 'file' && d.category === def.upload; });
        var list = files.length
            ? '<ul class="jp-task-files">' + files.map(function (d) {
                return '<li><a href="' + esc(d.url) + '" target="_blank" rel="noopener">' + esc(d.title) + '</a> <span class="muted">· ' + esc(d.byName) + ', ' + esc(when(d.createdAt)) + '</span></li>';
            }).join('') + '</ul>'
            : '<p class="muted">Nothing uploaded yet.</p>';
        var can = isC() || isStudent();
        return '<div class="full jp-task-upload"><span class="jp-note-lbl">' + esc(def.upload) + '</span>' + list +
            (can ? '<button class="jp-btn sm" data-act="upload-doc" data-cat="' + esc(def.upload) + '">' + ICO.plus + ' ' + (files.length ? 'Upload another copy' : 'Upload the signed form') + '</button>' : '') + '</div>';
    }
    function uploadHint(def) {
        if (!def.upload) return '';
        var n = (P.documents || []).filter(function (d) { return d.kind === 'file' && d.category === def.upload; }).length;
        return n ? ' <span class="jp-upload-chip ok">' + ICO.check + ' Uploaded</span>' : ' <span class="jp-upload-chip">Upload needed</span>';
    }

    /* ---- one activity row, shared by the journey and every university ---- */
    function actRow(scope, appId, def, a, timeline, filtered) {
        if (!a.inc && (!isC() || !UI.showExcluded)) return '';
        if (filtered) {
            if (!ownerMatches(a.owner)) return '';
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
                (!isC() ? '<dt>Who does it</dt><dd>' + esc(ownerLabel(a.owner)) + '</dd><dt>Completed on</dt><dd>' + (a.done ? fmt(a.done) : '—') + '</dd>' : '') +
                (isC() && a.by === 'student' && a.at ? '<dt>Last change</dt><dd>By the student, ' + esc(when(a.at)) + '</dd>' : '') +
                '</dl></div>' +
                (isC()
                    ? '<div class="jp-fields">' +
                        '<div><label for="ow-' + esc(k) + '">Owner</label><select id="ow-' + esc(k) + '" data-f="owner"' + data + '>' + ownerOptionsHtml(a.owner) + '</select></div>' +
                        '<div><label for="dn-' + esc(k) + '">Completion date</label><input id="dn-' + esc(k) + '" type="date" data-f="done"' + data + ' value="' + esc(a.done || '') + '"></div>' +
                        '<div class="full"><label for="nt-' + esc(k) + '">Notes <span style="font-weight:500">(the student sees these)</span></label><textarea id="nt-' + esc(k) + '" data-f="notes"' + data + ' maxlength="1000" placeholder="Scores, offer conditions, what to chase…">' + esc(a.notes) + '</textarea></div>' +
                        uploadBox(def) +
                        (canRepeat(def) ? '<div class="full jp-task-tools"><span class="muted">Did the student do more than one?</span><button class="jp-btn ghost sm" data-act="add-repeat" data-key="' + esc(def.key) + '">' + ICO.plus + ' Add another ' + esc(def.name.toLowerCase()) + '</button></div>' : '') +
                        (def.custom ? '<div class="full jp-task-tools"><span class="muted">' + (def.parent ? 'Another “' + esc((CORE_DEFS[def.parent] || {}).name || '') + '” for this student.' : 'You added this task for this student.') + '</span><button class="jp-btn ghost sm" data-act="edit-task" data-key="' + esc(def.key) + '">Edit task</button>' +
                            '<button class="jp-btn danger sm' + (UI.armed === 'rm-task-' + def.key ? ' armed' : '') + '" data-act="rm-task" data-key="' + esc(def.key) + '">' + (UI.armed === 'rm-task-' + def.key ? 'Click again to remove' : 'Remove task') + '</button></div>' : '') +
                        '</div>'
                    : (a.notes ? '<div><span class="jp-note-lbl">Note from your counsellor</span><div class="jp-note">' + esc(a.notes) + '</div></div>' : '<div></div>') + (def.upload ? '<div class="jp-fields">' + uploadBox(def) + '</div>' : '')) +
                '</div>';
        }
        return '<div class="jp-act' + (a.inc ? '' : ' off') + (open ? ' open' : '') + (def.parent ? ' sub' : '') + '"><div class="jp-row">' +
            (isC() ? '<button class="jp-inc' + (a.inc ? ' on' : '') + '" data-act="toggle-inc"' + data + ' aria-pressed="' + a.inc + '" title="' + (a.inc ? 'Included for this student. Click to exclude.' : 'Excluded. Click to include.') + '">' + (a.inc ? ICO.check : '') + '</button>' : '') +
            '<button class="jp-name" data-act="toggle-row"' + data + ' aria-expanded="' + open + '"><span class="n">' + (def.parent ? '<span class="jp-sub-mark" aria-hidden="true">↳</span>' : '') + esc(def.name) + (def.custom && !def.parent && !isStudent() ? ' <span class="jp-added">Added</span>' : '') + repeatChip(def, a) + uploadHint(def) + '</span><span class="d">' + esc(a.notes || def.desc) + '</span></button>' +
            '<span class="jp-ocell"><span class="jp-owner-chip' + (ownerIsMine(a.owner) ? ' mine' : '') + (isMemberOwner(a.owner) ? ' person' : '') + '" title="' + esc(isMemberOwner(a.owner) ? ownerName(a.owner) + ' · ' + (PEOPLE[a.owner] ? PEOPLE[a.owner].role : 'off the file') : a.owner) + '">' + esc(ownerLabel(a.owner)) + '</span></span>' +
            status + date + '<span class="jp-due">' + after + '</span>' +
            '<button class="jp-chev" data-act="toggle-row"' + data + ' aria-label="Show details">' + ICO.chev + '</button></div>' + detail + '</div>';
    }

    /* ---- core journey ---- */
    function renderJourney() {
        var cur = currentPhase();
        var owners = ['Student', 'Counsellor', 'University', 'Bank/Financial Institution'];
        var tools = '<div class="jp-tools">' +
            '<select id="f-owner" aria-label="Filter by owner"><option value="all">Everyone\'s tasks</option>' +
            '<optgroup label="Roles">' + owners.map(function (o) { return '<option value="' + esc(o) + '"' + (UI.owner === o ? ' selected' : '') + '>' + (isStudent() && o === 'Student' ? 'My tasks' : (o === 'Bank/Financial Institution' ? 'Bank' : o)) + '</option>'; }).join('') + '</optgroup>' +
            (T.ownerPeople.length ? '<optgroup label="People on this file">' + T.ownerPeople.map(function (m) { return '<option value="' + esc(m.value) + '"' + (UI.owner === m.value ? ' selected' : '') + '>' + esc(m.name) + '</option>'; }).join('') + '</optgroup>' : '') +
            '</select>' +
            '<select id="f-status" aria-label="Filter by status"><option value="all">Any status</option><option value="open"' + (UI.status === 'open' ? ' selected' : '') + '>Still to do</option>' + T.statuses.map(function (s) { return '<option' + (UI.status === s ? ' selected' : '') + '>' + s + '</option>'; }).join('') + '</select>' +
            (isC() ? '<label><input type="checkbox" id="f-excl"' + (UI.showExcluded ? ' checked' : '') + '> Show excluded</label>' : '') +
            '<span class="sp"></span>' + (isC() ? '<button class="jp-btn sm" data-act="add-stage">' + ICO.plus + ' Add a stage</button>' : '') + '<button class="jp-btn ghost sm" data-act="expand-all">Open all</button><button class="jp-btn ghost sm" data-act="collapse-all">Close all</button></div>';

        var panels = phases().map(function (p, i) {
            var r = roll(phaseActs(p));
            var open = UI.openPhases[p.key] != null ? UI.openPhases[p.key] : i === cur;
            var complete = r.den > 0 && r.done === r.den;
            var inc = phaseIncluded(p);
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
                '<span class="jp-progress num"><span class="jp-bar"><i style="width:' + barWidth(r) + '"></i></span>' + tally(r) + '</span>' + ICO.chev +
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
                '<span class="foot num"><span class="jp-bar"><i style="width:' + barWidth(r) + '"></i></span>' + shortTally(r) + (a.deadline ? ' · closes ' + esc(fmt(a.deadline, { day: 'numeric', month: 'short' })) : (a.offerType ? ' · ' + esc(a.offerType) : '')) + '</span></button>';
        }).join('') + (isC() ? '<button class="jp-ticket add" data-act="add-uni">' + ICO.plus.replace('<svg', '<svg width="16" height="16"') + ' Add university</button>' : '');

        if (!P.apps.length) {
            return isC() ? '<div class="jp-tickets">' + tickets + '</div>' : '<div class="jp-card jp-empty"><b>No universities yet</b>Your counsellor adds them here once your shortlist is agreed.</div>';
        }
        var summary = '';
        if (!isStudent()) {
            var rs = P.apps.map(function (a) { return roll(appActs(a)); }), tot = sum(rs);
            summary = '<details class="jp-details-toggle"><summary>Applications summary</summary><div class="jp-table-wrap jp-card flush"><table class="jp-table"><thead><tr><th>University</th><th>Country</th><th>Program / course</th><th>Fit</th><th class="r">Included</th><th class="r">Completed</th><th class="r">In progress / Submitted</th><th class="r">Not started</th><th class="r">N/A</th><th>Done</th></tr></thead><tbody>' +
                P.apps.map(function (a, i) {
                    var r = rs[i];
                    return '<tr class="click" data-act="pick-uni" data-u="' + a.id + '"><td><b>' + esc(a.university) + '</b></td><td>' + esc(a.country) + '</td><td>' + esc(a.program) + '</td><td><span class="fit ' + (a.fit || 'unset') + '">' + esc(T.fits[a.fit] || '—') + '</span></td>' +
                        cnt(r.inc) + cnt(r.done) + cnt(r.prog) + cnt(r.ns) + cnt(r.na) + '<td>' + doneCell(r) + '</td></tr>';
                }).join('') +
                '<tr class="total"><td colspan="4">Total</td>' + cnt(tot.inc) + cnt(tot.done) + cnt(tot.prog) + cnt(tot.ns) + cnt(tot.na) + '<td>' + doneCell(tot) + '</td></tr></tbody></table></div></details>';
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
            '<div class="jp-block-side"><div class="jp-big num">' + r.done + '<small>of ' + r.den + ' done</small></div>' +
            (isC() ? '<button class="jp-btn danger sm' + (armed ? ' armed' : '') + '" data-act="rm-uni" data-u="' + a.id + '">' + (armed ? 'Click again to remove' : 'Remove') + '</button>' : '') + '</div></div>' +
            banner + requirementsCard(a) + '<div class="jp-acts' + (isC() ? '' : ' ro') + '">' + rowHead() + groups + '</div></div>';
    }

    /*
     * What this university actually asks for, kept at the top of its block:
     * the tests, the documents, anything else worth writing down, and the date
     * the application closes. The checklist below works through it; this is the
     * brief it works from. The counsellor types straight into it; everyone else
     * reads it. The closing date also shows up in Deadlines and the Calendar.
     */
    function requirementsCard(a) {
        var days = daysUntil(a.deadline), d = a.deadline ? due(days) : null;
        var tests = splitList(a.tests), docs = splitList(a.docs);
        var head = '<div class="jp-reqs-h"><h4>What this university asks for</h4>' +
            (d ? '<span class="pill ' + d.cls + '">Closes ' + esc(fmt(a.deadline, { day: 'numeric', month: 'short', year: 'numeric' })) + ' · ' + d.label + '</span>' : '') +
            (isC() ? '<button class="jp-btn ghost sm" data-act="edit-reqs" data-u="' + a.id + '">' + ICO.pen + ' Edit</button>' : '') + '</div>';

        var rows = '';
        if (tests.length) rows += reqRow('Tests', tagList(tests));
        if (docs.length) rows += reqRow('Documents', tagList(docs));
        if (!d && a.deadline) rows += reqRow('Closes', '<span class="jp-tag">' + esc(fmt(a.deadline)) + '</span>');
        if (a.requirements) rows += reqRow('Entry requirements', '<p class="jp-reqs-text">' + esc(a.requirements) + '</p>');

        return '<div class="jp-reqs">' + head +
            (rows || '<p class="muted">' + (isC()
                ? 'Nothing written down yet. Choose Edit to record the tests, the documents and the closing date.'
                : 'Your counsellor hasn\'t written this university\'s requirements down yet.') + '</p>') + '</div>';
    }

    function reqRow(label, body) {
        return '<div class="jp-reqs-row"><span class="lbl">' + esc(label) + '</span><div class="val">' + body + '</div></div>';
    }

    /* ---- deadlines ---- */
    function renderDeadlines() {
        var all = openItems(), dated = all.filter(function (i) { return i.a.target; }).sort(byDate);
        var list = dated.filter(function (i) { var d = daysUntil(i.a.target); return UI.dl === 'late' ? d < 0 : UI.dl === '30' ? d <= 30 : true; });
        var undated = all.length - dated.length;
        var rows = list.map(function (i) {
            var d = due(daysUntil(i.a.target));
            return '<div class="jp-dl' + (d.cls === 'danger' ? ' over' : '') + '">' + calBox(i.a.target, d.cls === 'danger') +
                '<button class="w" data-act="jump-item" data-scope="' + i.scope + '" data-app="' + (i.appId || '') + '" data-key="' + esc(i.key) + '"><span class="t">' + esc(i.name) + '</span><span class="s">' + esc(i.where) + ' · ' + fmt(i.a.target) + '</span></button>' +
                '<span class="o"><span class="jp-owner-chip' + (ownerIsMine(i.a.owner) ? ' mine' : '') + (isMemberOwner(i.a.owner) ? ' person' : '') + '">' + esc(ownerLabel(i.a.owner)) + '</span></span><span class="pill ' + d.cls + '">' + d.label + '</span></div>';
        }).join('');

        var taskCard = '<section class="jp-card flush"><div class="jp-card-h pad"><div><h2>Task dates</h2><p>Every open task that has a target date on it.</p></div></div>' +
            '<div class="jp-tools inset"><div class="jp-seg" role="group" aria-label="Show">' + [['late', 'Late'], ['30', 'Next 30 days'], ['open', 'All']].map(function (x) { return '<button class="' + (UI.dl === x[0] ? 'on' : '') + '" data-act="dl" data-v="' + x[0] + '">' + x[1] + '</button>'; }).join('') + '</div>' +
            '<span class="sp"></span><span class="jp-owner">' + (undated ? plural(undated, 'open task has', 'open tasks have') + ' no date yet' : '') + '</span></div>' +
            (rows || '<div class="jp-empty"><b>' + (UI.dl === 'late' ? 'Nothing is late' : 'Nothing in this range') + '</b>' + (UI.dl === 'late' ? 'Every dated task is on time.' : 'No open tasks have a date in this range.') + '</div>') + '</section>';

        return dateCard('University deadlines', 'The dates the universities set: when each application closes, and anything else they have told us.', uniDeadlines(), 'uni') +
            dateCard('Our deadlines', isStudent() ? 'Dates One Degree set for your plan, so nothing is left to the last week.' : 'Dates ODA set itself, ahead of the universities\' own.', ourDeadlines(), 'own') +
            taskCard;
    }

    /** One of the two deadline lists: the universities' dates, or ODA's own. */
    function dateCard(title, blurb, rows, kind) {
        var add = isC() ? '<button class="jp-btn ghost sm" data-act="add-deadline" data-v="' + kind + '">' + ICO.plus + ' Add a date</button>' : '';
        var body = rows.map(function (d) {
            var days = daysUntil(d.date), p = due(days), armed = UI.armed === 'rm-dl-' + d.key;
            var fromUni = d.key.indexOf('app-') === 0;
            var acts = fromUni
                ? '<button class="jp-link" data-act="open-uni" data-u="' + d.appId + '">Open</button>'
                : (isC() ? '<button class="jp-btn danger sm' + (armed ? ' armed' : '') + '" data-act="rm-deadline" data-k="' + esc(d.key) + '">' + (armed ? 'Click again' : 'Remove') + '</button>' : '');
            return '<div class="jp-dl with-act' + (p.cls === 'danger' ? ' over' : '') + '">' + calBox(d.date, p.cls === 'danger') +
                '<div class="w">' + (isC() && !fromUni
                    ? '<button class="t as-link" data-act="edit-deadline" data-k="' + esc(d.key) + '">' + esc(d.what) + '</button>'
                    : '<span class="t">' + esc(d.what) + '</span>') +
                '<span class="s">' + esc(d.who || (kind === 'uni' ? 'University' : 'One Degree')) + ' · ' + fmt(d.date) + '</span></div>' +
                '<span class="o"><span class="jp-owner-chip">' + (kind === 'uni' ? 'University' : 'Ours') + '</span></span>' +
                '<span class="pill ' + p.cls + '">' + p.label + '</span><span class="a">' + acts + '</span></div>';
        }).join('');
        return '<section class="jp-card flush"><div class="jp-card-h pad"><div><h2>' + esc(title) + '</h2><p>' + esc(blurb) + '</p></div>' + add + '</div>' +
            (body || '<div class="jp-empty"><b>No dates yet</b>' + (isC() ? 'Add the ones this student has to hit.' : 'Your counsellor adds these as they are confirmed.') + '</div>') + '</section>';
    }

    /* ---- calendar and meetings ---- */
    var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    var DOW = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    var EVENT_NAME = { meeting: 'Meeting', uni: 'University deadline', own: 'Our deadline', task: 'Task date' };

    /** Everything on the plan that has a date, as calendar entries. */
    function calendarEvents() {
        var out = [];
        P.meetings.forEach(function (m) {
            out.push({
                date: m.date, type: 'meeting', label: m.title, done: m.done, key: m.key, link: m.link,
                sub: [m.time, m.mode, m.who].filter(Boolean).join(' · '), m: m,
            });
        });
        uniDeadlines().forEach(function (d) {
            out.push({ date: d.date, type: 'uni', label: d.what, sub: d.who, key: d.key, appId: d.appId, d: d });
        });
        ourDeadlines().forEach(function (d) {
            out.push({ date: d.date, type: 'own', label: d.what, sub: d.who || 'One Degree', key: d.key, d: d });
        });
        // Finished tasks stay on the month, in green; only Not Applicable leaves.
        datedItems().forEach(function (i) {
            out.push({
                date: i.a.target, type: 'task', label: i.name, done: closed(i.a.status),
                sub: i.where + (isStudent() ? '' : ' · ' + i.a.owner),
                scope: i.scope, appId: i.appId, itemKey: i.key, a: i.a, where: i.where,
            });
        });
        return out;
    }

    /*
     * What colour a tile entry takes. Not the kind of thing it is — that is
     * the dot in front of it — but where it stands: done, late, today, this
     * week, or further off. One glance at the month says what is slipping.
     */
    function eventStatus(e) {
        if (e.done) return 'done';
        var d = daysUntil(e.date);
        if (d == null) return 'later';
        if (d < 0) return 'late';
        if (d === 0) return 'today';
        if (d <= 7) return 'soon';
        return 'later';
    }
    var STATUS_NAME = { done: 'Done', late: 'Late', today: 'Today', soon: 'This week', later: 'Later' };

    /*
     * Hovering an entry on the month (or tabbing to it) shows what it is
     * without leaving the calendar: a task's place, status, owner, dates and
     * notes; a meeting's time, how to join and who is coming; a deadline's
     * date and how far off it is. "+3 more" lists the entries it hides.
     */
    var CAL_EVENTS = [];
    function tipEl() {
        var t = document.getElementById('jp-tip');
        if (!t) { t = document.createElement('div'); t.id = 'jp-tip'; t.className = 'jp-tip'; t.setAttribute('role', 'tooltip'); t.hidden = true; document.body.appendChild(t); }
        return t;
    }
    function hideTip() { var t = document.getElementById('jp-tip'); if (t) t.hidden = true; }
    function tipRow(k, v) { return v ? '<dt>' + esc(k) + '</dt><dd>' + v + '</dd>' : ''; }
    function dueText(iso, done) {
        if (done) return '';
        var d = daysUntil(iso);
        if (d == null) return '';
        return d === 0 ? 'Today' : d < 0 ? Math.abs(d) + (d === -1 ? ' day late' : ' days late') : 'In ' + plural(d, 'day', 'days');
    }
    function tipHtml(e) {
        var head = '<div class="jp-tip-h"><i class="dot ' + e.type + '"></i><span>' + esc(EVENT_NAME[e.type]) + '</span>' +
            '<span class="pill ' + ({ late: 'danger', done: 'good', later: 'wait' }[eventStatus(e)] || 'amber') + '">' + esc(STATUS_NAME[eventStatus(e)]) + '</span></div>' +
            '<b class="jp-tip-t">' + esc(e.label) + '</b>';
        var rows = '';
        if (e.type === 'task') {
            var a = e.a || {};
            rows = tipRow('Where', esc(e.where)) +
                tipRow('Status', '<span class="jp-state ' + stPill(a.status) + '">' + esc(a.status) + '</span>') +
                tipRow(isStudent() ? 'Who does it' : 'Owner', esc(ownerLabel(a.owner))) +
                tipRow('Target date', esc(fmt(a.target)) + (dueText(a.target, closed(a.status)) ? ' · ' + esc(dueText(a.target, closed(a.status))) : '')) +
                tipRow('Completed on', a.status === 'Completed' && a.done ? esc(fmt(a.done)) : '') +
                tipRow('Notes', a.notes ? esc(a.notes.length > 180 ? a.notes.slice(0, 180) + '…' : a.notes) : '');
        } else if (e.type === 'meeting') {
            var m = e.m || {};
            rows = tipRow('When', esc(fmt(m.date, { weekday: 'short', day: 'numeric', month: 'short' })) + (m.time ? ', ' + esc(m.time) : '') + (m.minutes ? ' · ' + m.minutes + ' min' : '')) +
                tipRow('How', esc(m.mode)) +
                tipRow('Who', esc(m.who)) +
                tipRow(m.mode === 'In person' ? 'Contact' : 'Phone', esc(m.phone)) +
                tipRow('Join', m.link ? esc(m.link.replace(/^https:\/\//, '')) : '') +
                tipRow('Notes', m.notes ? esc(m.notes.length > 180 ? m.notes.slice(0, 180) + '…' : m.notes) : '');
        } else {
            var d = e.d || {};
            rows = tipRow(e.type === 'uni' ? 'University' : 'Who', esc(d.who || (e.type === 'own' ? 'One Degree' : ''))) +
                tipRow('Date', esc(fmt(d.date, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })) + (dueText(d.date) ? ' · ' + esc(dueText(d.date)) : ''));
        }
        return head + (rows ? '<dl class="jp-tip-kv">' + rows + '</dl>' : '') + '<span class="jp-tip-f">Click to open</span>';
    }
    function moreHtml(ids) {
        return '<div class="jp-tip-h"><span>Also on this day</span></div><ul class="jp-tip-list">' + ids.map(function (i) {
            var e = CAL_EVENTS[i];
            return e ? '<li><i class="dot ' + e.type + '"></i><span class="t">' + esc(e.label) + '</span><span class="pill ' + ({ late: 'danger', done: 'good', later: 'wait' }[eventStatus(e)] || 'amber') + '">' + esc(STATUS_NAME[eventStatus(e)]) + '</span></li>' : '';
        }).join('') + '</ul><span class="jp-tip-f">Click the day to see them all</span>';
    }
    function showTip(target) {
        var html = '';
        if (target.dataset.ev != null) { var e = CAL_EVENTS[parseInt(target.dataset.ev, 10)]; if (e) html = tipHtml(e); }
        else if (target.dataset.more) html = moreHtml(target.dataset.more.split(',').map(Number));
        if (!html) return;
        var t = tipEl();
        t.innerHTML = html; t.hidden = false;
        // Below the entry, or above it when there is no room; never off the side.
        var r = target.getBoundingClientRect(), w = t.offsetWidth, h = t.offsetHeight, gap = 8, edge = 12;
        var vw = window.innerWidth || document.documentElement.clientWidth, vh = window.innerHeight || document.documentElement.clientHeight;
        var top = r.bottom + gap;
        if (top + h > vh - edge && r.top - gap - h > edge) top = r.top - gap - h;
        var left = Math.min(Math.max(edge, r.left), Math.max(edge, vw - w - edge));
        t.style.top = Math.round(top) + 'px'; t.style.left = Math.round(left) + 'px';
    }
    function tipTarget(node) { return node && node.closest ? node.closest('.jp-month [data-ev], .jp-month [data-more]') : null; }
    var CAN_HOVER = !window.matchMedia || window.matchMedia('(hover: hover)').matches;
    document.addEventListener('mouseover', function (e) { if (!CAN_HOVER) return; var t = tipTarget(e.target); if (t) showTip(t); });
    document.addEventListener('mouseout', function (e) {
        var t = tipTarget(e.target);
        if (t && !(e.relatedTarget && t.contains(e.relatedTarget))) hideTip();
    });
    document.addEventListener('focusin', function (e) { var t = tipTarget(e.target); if (t) showTip(t); else hideTip(); });
    document.addEventListener('focusout', function (e) { if (tipTarget(e.target)) hideTip(); });
    window.addEventListener('scroll', hideTip, { passive: true });
    document.addEventListener('click', hideTip, true);

    /** The data-act attributes that open whatever a calendar entry stands for. */
    function eventAction(e) {
        if (e.type === 'meeting') return ' data-act="cal-open-meeting" data-k="' + esc(e.key) + '"';
        if (e.type === 'task') return ' data-act="jump-item" data-scope="' + e.scope + '" data-app="' + (e.appId || '') + '" data-key="' + esc(e.itemKey) + '"';
        if (e.type === 'uni' && e.appId) return ' data-act="open-uni" data-u="' + e.appId + '"';
        if (isC()) return ' data-act="edit-deadline" data-k="' + esc(e.key) + '"';
        return '';
    }

    var EVENT_NAMES = { meeting: 'Meetings', uni: 'University deadlines', own: 'Our deadlines', task: 'Task dates' };
    /* What the reader has chosen to see: some kinds, and finished things or not. */
    function calShows(e) {
        if ((UI.cal.kinds || {})[e.type] === false) return false;
        return !(UI.cal.hideDone && eventStatus(e) === 'done');
    }
    function evTime(e) { return e.type === 'meeting' && e.m && e.m.time ? e.m.time : ''; }
    function byWhen(a, b) {
        var x = a.date + ' ' + (evTime(a) || '99'), y2 = b.date + ' ' + (evTime(b) || '99');
        return x < y2 ? -1 : x > y2 ? 1 : 0;
    }

    /*
     * The calendar: a month (or, on a phone, an agenda) of meetings,
     * deadlines and task dates. Chips choose which kinds show and count what
     * the month holds; colour says where each thing stands.
     */
    function renderCalendar() {
        var now = new Date(P.today + 'T00:00:00');
        if (UI.cal.y == null) { UI.cal.y = now.getFullYear(); UI.cal.m = now.getMonth(); }
        if (!UI.cal.view) UI.cal.view = (window.innerWidth && window.innerWidth < 640) ? 'agenda' : 'month';
        if (!UI.cal.kinds) UI.cal.kinds = { meeting: true, uni: true, own: true, task: true };
        var y = UI.cal.y, m = UI.cal.m, monthKey = y + '-' + pad2(m + 1);

        CAL_EVENTS = calendarEvents();
        CAL_EVENTS.forEach(function (e, i) { e.i = i; });
        var inMonth = CAL_EVENTS.filter(function (e) { return e.date && e.date.slice(0, 7) === monthKey; });
        var byDay = {};
        CAL_EVENTS.filter(calShows).forEach(function (e) { if (e.date) (byDay[e.date] = byDay[e.date] || []).push(e); });
        Object.keys(byDay).forEach(function (d) { byDay[d].sort(byWhen); });

        var late = inMonth.filter(function (e) { return eventStatus(e) === 'late'; }).length;
        var bar = '<div class="jp-cal-bar">' +
            '<div class="jp-cal-title"><button class="jp-cal-arrow" data-act="cal-step" data-v="-1" aria-label="Previous month">‹</button>' +
            '<h2>' + MONTHS[m] + ' <span>' + y + '</span></h2>' +
            '<button class="jp-cal-arrow" data-act="cal-step" data-v="1" aria-label="Next month">›</button>' +
            '<button class="jp-btn ghost sm" data-act="cal-today">Today</button></div>' +
            '<div class="jp-cal-tools"><div class="jp-seg" role="group" aria-label="Calendar view">' +
            ['month', 'agenda'].map(function (v) {
                return '<button data-act="cal-view" data-v="' + v + '" class="' + (UI.cal.view === v ? 'on' : '') + '" aria-pressed="' + (UI.cal.view === v) + '">' + (v === 'month' ? 'Month' : 'Agenda') + '</button>';
            }).join('') + '</div>' +
            (isC() ? '<button class="jp-btn sm" data-act="add-meeting">' + ICO.plus + ' Meeting</button><button class="jp-btn ghost sm" data-act="add-deadline" data-v="own">' + ICO.plus + ' Deadline</button>' : '') +
            '</div></div>';

        var chips = '<div class="jp-cal-filters"><span class="lbl">Show</span>' + ['meeting', 'uni', 'own', 'task'].map(function (k) {
            var on = UI.cal.kinds[k] !== false, n = inMonth.filter(function (e) { return e.type === k; }).length;
            return '<button class="jp-cal-chip' + (on ? ' on' : '') + '" data-act="cal-kind" data-v="' + k + '" aria-pressed="' + on + '"><i class="dot ' + k + '"></i>' + EVENT_NAMES[k] + ' <b class="num">' + n + '</b></button>';
        }).join('') +
            '<label class="jp-cal-done"><input type="checkbox" id="cal-hide-done"' + (UI.cal.hideDone ? ' checked' : '') + '> Hide finished</label>' +
            (late ? '<span class="pill danger">' + late + ' late this month</span>' : '') + '</div>';

        var body = UI.cal.view === 'agenda' ? calAgenda(byDay, monthKey) : calMonth(byDay, y, m);

        var legend = '<div class="jp-legend"><span class="lbl">Colour</span>' +
            ['late', 'today', 'soon', 'later', 'done'].map(function (k) {
                return '<span><i class="chip st-' + k + '"></i>' + STATUS_NAME[k] + '</span>';
            }).join('') + '</div>';
        var hint = '<p class="jp-day-hint muted">' + (UI.cal.view === 'month'
            ? 'Hover over an entry for its details, and click it to open it. Click a day to see everything on it' + (isC() ? ', or to put a meeting or a deadline on it' : '') + '.'
            : 'Everything this month, day by day. Switch to Month for the grid.') + '</p>';

        return '<section class="jp-card jp-calendar">' + bar + chips + body + legend + hint + '</section>' + renderMeetings();
    }

    function calMonth(byDay, y, m) {
        var offset = (new Date(y, m, 1).getDay() + 6) % 7;      // weeks start on Monday
        var daysInMonth = new Date(y, m + 1, 0).getDate();
        var prevDays = new Date(y, m, 0).getDate();
        var cells = '';
        for (var n = 0; n < 42; n++) {
            var nth = n - offset + 1, cy = y, cm = m, cd = nth, other = false;
            if (nth < 1) { cm = m - 1; cd = prevDays + nth; other = true; if (cm < 0) { cm = 11; cy--; } }
            else if (nth > daysInMonth) { cm = m + 1; cd = nth - daysInMonth; other = true; if (cm > 11) { cm = 0; cy++; } }
            var iso = cy + '-' + pad2(cm + 1) + '-' + pad2(cd);
            var list = byDay[iso] || [];
            // The day is the click target; each entry inside it is its own,
            // so clicking an entry opens that thing and clicking the space
            // around it opens the day.
            cells += '<div class="jp-day' + (other ? ' other' : '') + (n % 7 >= 5 ? ' wkend' : '') + (iso === P.today ? ' today' : '') + (iso < P.today ? ' past' : '') + (iso === UI.cal.sel ? ' sel' : '') + '"' +
                ' data-act="cal-day" data-d="' + iso + '" role="button" tabindex="0"' +
                ' aria-label="' + esc(fmt(iso, { weekday: 'long', day: 'numeric', month: 'long' })) + ', ' + plural(list.length, 'entry', 'entries') + '">' +
                '<span class="dn num">' + cd + (iso === P.today ? '<span class="tdy">Today</span>' : '') + '</span>' +
                list.slice(0, 3).map(function (e) {
                    return '<button class="ev st-' + eventStatus(e) + '"' + eventAction(e) + ' data-ev="' + e.i + '" aria-label="' + esc(EVENT_NAME[e.type] + ': ' + e.label + (e.sub ? ' — ' + e.sub : '')) + '">' +
                        '<i class="dot ' + e.type + '"></i>' + (evTime(e) ? '<span class="tm num">' + evTime(e) + '</span>' : '') + '<span class="lb">' + esc(e.label) + '</span></button>';
                }).join('') +
                (list.length > 3 ? '<span class="more num" data-more="' + list.slice(3).map(function (e) { return e.i; }).join(',') + '">+' + (list.length - 3) + ' more</span>' : '') + '</div>';
        }
        return '<div class="jp-month">' + DOW.map(function (d, i) { return '<span class="dow' + (i >= 5 ? ' wkend' : '') + '">' + d + '</span>'; }).join('') + cells + '</div>';
    }

    /* The same month as a list of days, for a narrow screen or a quick read. */
    function calAgenda(byDay, monthKey) {
        var days = Object.keys(byDay).filter(function (d) { return d.slice(0, 7) === monthKey; }).sort();
        if (!days.length) return '<div class="jp-empty"><b>Nothing this month</b>' + (UI.cal.hideDone || Object.keys(UI.cal.kinds).some(function (k) { return UI.cal.kinds[k] === false; }) ? 'Some kinds are hidden — check the chips above.' : 'Use Next to look further ahead.') + '</div>';
        return '<div class="jp-agenda">' + days.map(function (d) {
            var n = daysUntil(d);
            return '<div class="jp-agenda-day' + (d === P.today ? ' today' : '') + (d < P.today ? ' past' : '') + '">' +
                '<button class="jp-agenda-h" data-act="cal-day" data-d="' + d + '">' + calBox(d, false) +
                '<span><b>' + esc(fmt(d, { weekday: 'long', day: 'numeric', month: 'long' })) + '</b><small>' +
                (n === 0 ? 'Today' : n < 0 ? plural(Math.abs(n), 'day', 'days') + ' ago' : 'In ' + plural(n, 'day', 'days')) + ' · ' + plural(byDay[d].length, 'thing', 'things') + '</small></span></button>' +
                '<div class="jp-day-list">' + byDay[d].map(dayRow).join('') + '</div></div>';
        }).join('') + '</div>';
    }

    /** Everything on one day, in a dialog over the month. */
    function openDay(iso) {
        var byDay = {};
        calendarEvents().forEach(function (e) { if (e.date) (byDay[e.date] = byDay[e.date] || []).push(e); });
        var list = byDay[iso] || [];
        var days = daysUntil(iso);
        modal('<h3>' + esc(fmt(iso, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })) + '</h3>' +
            '<p class="sub">' + (iso === P.today ? 'Today' : days < 0 ? plural(Math.abs(days), 'day', 'days') + ' ago' : 'In ' + plural(days, 'day', 'days')) +
            ' · ' + (list.length ? plural(list.length, 'thing', 'things') + ' on it' : 'nothing on it yet') + '.</p>' +
            (list.length ? '<div class="jp-day-list">' + list.map(dayRow).join('') + '</div>' : '') +
            '<div class="jp-actions">' +
            (isC() ? '<button class="jp-btn ghost" data-act="add-deadline" data-v="own" data-d="' + iso + '">' + ICO.plus + ' Deadline</button>' +
                '<button class="jp-btn ghost" data-act="add-meeting" data-d="' + iso + '">' + ICO.plus + ' Meeting</button>' : '') +
            '<button class="jp-btn" data-act="cancel">Close</button></div>', 'wide');
    }

    /** One entry in the chosen day's panel, with whatever can be done to it. */
    function dayRow(e) {
        var meeting = e.type === 'meeting' ? findMeeting(e.key) : null;
        var acts = '';
        if (meeting) {
            if (meeting.link && !meeting.done) acts += '<a class="jp-btn sm" href="' + esc(meeting.link) + '" target="_blank" rel="noopener">Join</a>';
            if (isC()) {
                acts += '<button class="jp-btn ghost sm" data-act="edit-meeting" data-k="' + esc(meeting.key) + '">Edit</button>' +
                    '<button class="jp-btn ghost sm" data-act="meeting-done" data-k="' + esc(meeting.key) + '">' + (meeting.done ? 'Reopen' : 'Mark done') + '</button>';
            }
        } else {
            var open = eventAction(e);
            if (open) acts += '<button class="jp-btn ghost sm"' + open + '>Open</button>';
        }
        return '<div class="jp-day-row st-' + eventStatus(e) + '"><i class="dot ' + e.type + '"></i>' +
            '<div><b>' + esc(e.label) + '</b><small>' + esc(EVENT_NAME[e.type] + (e.sub ? ' · ' + e.sub : '')) + '</small></div>' +
            '<span class="pill ' + (eventStatus(e) === 'late' ? 'danger' : eventStatus(e) === 'done' ? 'good' : eventStatus(e) === 'later' ? 'wait' : 'amber') + '">' + STATUS_NAME[eventStatus(e)] + '</span>' +
            (acts ? '<span class="a">' + acts + '</span>' : '<span class="a"></span>') + '</div>';
    }

    function renderMeetings() {
        var upcoming = P.meetings.filter(function (m) { return !m.done; });
        var past = P.meetings.filter(function (m) { return m.done; }).reverse();
        var add = isC() ? '<button class="jp-btn" data-act="add-meeting">' + ICO.plus + ' Schedule a meeting</button>' : '';
        var body = (upcoming.length ? upcoming.map(meetingRow).join('') : '<div class="jp-empty"><b>Nothing scheduled</b>' + (isC() ? 'Book the next call with this student.' : 'Your counsellor books calls here.') + '</div>') +
            (past.length ? '<div class="jp-meet-past">Past</div>' + past.map(meetingRow).join('') : '');
        return '<section class="jp-card flush" id="jp-meetings"><div class="jp-card-h pad"><div><h2>Meetings</h2><p>' +
            (upcoming.length ? plural(upcoming.length, 'meeting', 'meetings') + ' coming up.' : 'Calls and meetings on this plan.') +
            (isC() ? ' Everyone listed on a meeting is emailed its joining details.' : '') + '</p>' + googleLine() + '</div>' + add + '</div>' + body + '</section>';
    }

    /* Where the two reminders stand: sent, or switched off. */
    function reminderNote(m) {
        var r = m.reminded || {};
        if (m.remind === false) return '<span class="jp-meet-sent">Reminders off</span>';
        var sent = [r.tomorrow ? 'day before' : '', r.today ? 'on the day' : ''].filter(Boolean);
        return sent.length ? '<span class="jp-meet-sent ok">Reminder sent ' + sent.join(' and ') + '</span>' : '<span class="jp-meet-sent">Reminders: day before and on the day</span>';
    }
    function meetingRow(m) {
        var days = daysUntil(m.date), armed = UI.armed === 'rm-meeting-' + m.key;
        var late = !m.done && days != null && days < 0;
        var when = m.done ? 'Done' : (days === 0 ? 'Today' : late ? Math.abs(days) + (Math.abs(days) === 1 ? ' day ago' : ' days ago') : 'In ' + plural(days, 'day', 'days'));
        var how = '';
        if (m.phone) how += '<a class="jp-meet-join" href="tel:' + esc(m.phone.replace(/[^0-9+]/g, '')) + '">' + esc(m.phone) + '</a>';
        if (m.link) how += '<a class="jp-meet-join" href="' + esc(m.link) + '" target="_blank" rel="noopener">' + esc(m.link.replace(/^https:\/\//, '')) + '</a>';
        if (!how && isC() && wantsLink(m.mode)) how = '<span class="jp-meet-nolink">No link yet</span>';
        var invited = m.emails && m.emails.length
            ? '<span class="jp-meet-sent' + (m.sentAt ? ' ok' : '') + '">' + (m.sentAt ? 'Link sent ' + esc(when2(m.sentAt)) : 'Not sent yet') + ' · ' + plural(m.emails.length, 'address', 'addresses') + '</span>'
            : (isC() ? '<span class="jp-meet-sent">Nobody to email</span>' : '');
        if (isC() && !m.done) invited += reminderNote(m);

        return '<div class="jp-meet' + (m.done ? ' done' : '') + '">' + calBox(m.date, late) +
            '<div class="w"><span class="t">' + esc(m.title) + '</span><span class="s">' + esc([m.time, m.minutes + ' min', m.mode, m.who].filter(Boolean).join(' · ')) + '</span>' +
            (how || invited ? '<span class="meta">' + how + invited + '</span>' : '') +
            (m.notes ? '<span class="notes">' + esc(m.notes) + '</span>' : '') + '</div>' +
            '<span class="pill ' + (m.done ? 'good' : late ? 'danger' : days <= 3 ? 'amber' : 'wait') + '">' + when + '</span>' +
            '<span class="a">' +
            (m.link && !m.done ? '<a class="jp-btn sm" href="' + esc(m.link) + '" target="_blank" rel="noopener">Join</a>' : '') +
            (isC() ? (m.emails && m.emails.length ? '<button class="jp-btn ghost sm" data-act="send-meeting" data-k="' + esc(m.key) + '">' + (m.sentAt ? 'Send again' : 'Send the link') + '</button>' : '') +
                '<button class="jp-btn ghost sm" data-act="edit-meeting" data-k="' + esc(m.key) + '">Edit</button>' +
                '<button class="jp-btn ghost sm" data-act="meeting-done" data-k="' + esc(m.key) + '">' + (m.done ? 'Reopen' : 'Mark done') + '</button>' +
                '<button class="jp-btn danger sm' + (armed ? ' armed' : '') + '" data-act="rm-meeting" data-k="' + esc(m.key) + '">' + (armed ? 'Click again' : 'Cancel') + '</button>' : '') +
            '</span></div>';
    }

    /* ---- the team, and who owes what ---- */
    function renderOwners() {
        var items = openItems().sort(byDate);
        var parties = [
            [isStudent() ? 'You' : 'Student', function (o) { return o.indexOf('Student') >= 0; }, 'student'],
            ['Counsellor', function (o) { return o.indexOf('Counsellor') >= 0; }, 'counsellor'],
            ['University / bank', function (o) { return o === 'University' || o.indexOf('Bank') === 0; }, 'other']
        ];
        var owners = '<section class="jp-card"><div class="jp-card-h"><div><h2>Open tasks by side</h2><p>A shared task (for example Student &amp; Counsellor) shows for each of them.</p></div></div><div class="jp-owners">' +
            parties.map(function (p) {
                var mine = items.filter(function (i) { return p[1](i.a.owner); });
                return '<div class="jp-own jp-own-' + p[2] + '"><h3><span class="jp-own-name"><i></i>' + p[0] + '</span><span class="num">' + mine.length + '</span></h3><div class="sub">' + (mine.length === 1 ? 'open task' : 'open tasks') + (mine.length > 6 ? ' · next 6 shown' : '') + '</div>' +
                    (mine.length ? '<ul>' + mine.slice(0, 6).map(function (i) { var d = daysUntil(i.a.target); return '<li><div>' + esc(i.name) + '<small>' + esc(i.where) + '</small></div><span class="when num' + (d != null && d < 0 ? ' over' : '') + '">' + (i.a.target ? fmt(i.a.target, { day: 'numeric', month: 'short' }) : 'No date') + '</span></li>'; }).join('') + '</ul>'
                        : '<p class="muted" style="font-size:.85rem">Nothing open.</p>') + '</div>';
            }).join('') + '</div></section>';
        return renderTeam() + owners + renderPersonLoad(items);
    }

    /** Open tasks grouped by the person they were handed to. */
    function renderPersonLoad(items) {
        var held = T.ownerPeople.map(function (m) {
            return { person: m, tasks: items.filter(function (i) { return i.a.owner === m.value; }) };
        }).filter(function (row) { return row.tasks.length; });
        if (!held.length) return '';
        return '<section class="jp-card"><div class="jp-card-h"><div><h2>By person</h2><p>Tasks handed to someone by name, rather than to a side.</p></div></div>' +
            '<div class="jp-person-load">' + held.map(function (row) {
                return '<div class="jp-person"><div class="jp-person-h"><span class="jp-avatar sm">' + esc(initials(row.person.name)) + '</span>' +
                    '<div><b>' + esc(row.person.name) + '</b><small>' + esc(row.person.role) + '</small></div>' +
                    '<span class="num">' + row.tasks.length + '</span></div><ul>' +
                    row.tasks.slice(0, 6).map(function (i) {
                        var d = daysUntil(i.a.target);
                        return '<li><div>' + esc(i.name) + '<small>' + esc(i.where) + '</small></div><span class="when num' + (d != null && d < 0 ? ' over' : '') + '">' + (i.a.target ? fmt(i.a.target, { day: 'numeric', month: 'short' }) : 'No date') + '</span></li>';
                    }).join('') + '</ul></div>';
            }).join('') + '</div></section>';
    }

    /*
     * Who is on this student's file. The designations are ODA's standard list
     * plus any the counsellor types in; a new one is kept on the plan, so it is
     * there in the dropdown the next time. A student sees the names and the
     * roles — the contact details are the team's own and stay in the CRM.
     */
    function renderTeam() {
        var add = isC() ? '<button class="jp-btn" data-act="add-member">' + ICO.plus + ' Add someone</button>' : '';
        var cards = P.team.map(function (mb) {
            var armed = UI.armed === 'rm-member-' + mb.key;
            return '<div class="jp-member' + (mb.external ? ' ext' : '') + '">' +
                (isC()
                    ? '<select class="jp-role-select" data-mf="role" data-k="' + esc(mb.key) + '" aria-label="Designation for ' + esc(mb.name) + '">' + roleOptions(mb.role) + '</select>'
                    : '<span class="rl">' + esc(mb.role) + '</span>') +
                '<span class="nm">' + esc(mb.name) + '</span>' +
                (mb.contact ? '<span class="ct">' + esc(mb.contact) + '</span>' : '') +
                (mb.external ? '<span class="ex">External</span>' : '') +
                (isC() ? '<span class="a"><button class="jp-link" data-act="edit-member" data-k="' + esc(mb.key) + '">Edit</button>' +
                    '<button class="jp-btn danger sm' + (armed ? ' armed' : '') + '" data-act="rm-member" data-k="' + esc(mb.key) + '">' + (armed ? 'Click again' : 'Remove') + '</button></span>' : '') + '</div>';
        }).join('');
        return '<section class="jp-card"><div class="jp-card-h"><div><h2>' + (isStudent() ? 'Your team' : 'Team on this file') + '</h2><p>' +
            (isStudent() ? 'The people working on your journey with you.' : 'Counsellor, specialist or mentor, supervisor, content writer, external experts. Change a designation from the dropdown on the card, or pick “+ Add a designation” to make a new one.') +
            '</p></div>' + add + '</div>' +
            (cards ? '<div class="jp-team">' + cards + '</div>'
                : '<div class="jp-empty"><b>Nobody named yet</b>' + (isC() ? 'Add the people working on this student.' : 'Your counsellor names the team here.') + '</div>') + '</section>';
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
            '<div class="jp-adminpw"><div><span class="lbl">Admin password</span><p>Always signs in to ' + esc(P.student.firstName) + '\'s portal, whatever their own password is. For the team only: don\'t share it with the student.</p></div>' +
            '<code class="pw" id="jp-adminpw">' + (UI.showAdmin ? esc(L.adminPassword) : '••••••••••••••') + '</code>' +
            '<button class="jp-btn ghost sm" data-act="toggle-admin-pw">' + (UI.showAdmin ? 'Hide' : 'Show') + '</button>' +
            '<button class="jp-btn ghost sm" data-act="copy-admin-pw">Copy</button>' +
            '<button class="jp-btn ghost sm' + (UI.armed === 'admin-pw' ? ' armed' : '') + '" data-act="new-admin-pw">' + (UI.armed === 'admin-pw' ? 'Click again: the old one stops working' : 'New admin password') + '</button></div>' +
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
    /*
     * A document's edit history. The planner writes an entry itself whenever a
     * document moves — a draft saved, an essay sent for review, feedback given
     * — and either side can add one by hand for a change made outside it. The
     * version number moves only when the content does, so "version 3" means
     * the same thing to the student and the counsellor.
     */
    function editLog(d) {
        var rows = (d.edits || []).map(function (e) {
            return '<li><span class="v num">v' + e.version + '</span><div><b>' + esc(e.note) + '</b><small>' + esc(e.byName) + ' · ' + esc(when(e.at)) + '</small></div></li>';
        }).join('');
        return '<div class="jp-doc-history"><div class="jp-doc-history-h"><b>Edits on this document</b>' +
            '<span class="muted">Version ' + (d.version || 1) + ' · ' + plural((d.edits || []).length, 'edit', 'edits') + '</span>' +
            (d.canEdit ? '<button class="jp-btn ghost sm" data-act="log-edit" data-id="' + d.id + '">Log an edit</button>' : '') + '</div>' +
            (rows ? '<ol class="jp-edits">' + rows + '</ol>' : '<p class="muted">Nothing recorded yet.</p>') + '</div>';
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
                        '<h3>' + esc(d.title) + '</h3><span class="meta">' + (d.university ? esc(d.university) + ' · ' : '') + plural(d.words || 0, 'word', 'words') + ' · v' + (d.version || 1) + ' · ' + plural((d.edits || []).length, 'edit', 'edits') + '</span>' +
                        (d.feedback ? '<span class="fb"><b>Feedback:</b> ' + esc(d.feedback.length > 110 ? d.feedback.slice(0, 110) + '…' : d.feedback) + '</span>' : '') + '</button>';
                }).join('') + '</div>' : '<div class="jp-empty"><b>No essays yet</b>' + (canAdd ? 'Choose "Write an essay" to start one.' : 'Nothing has been written yet.') + '</div>') + '</section>';
        }

        var fileHtml = '';
        if (tab !== 'essays') {
            fileHtml = '<section class="jp-card flush"><div class="jp-card-h pad"><div><h2>Files</h2><p>' +
                (isStudent() ? 'Transcripts, score reports, passport, financial papers and anything else your applications need. PDF, Word or JPG/PNG, up to ' + Math.round(DT.limits.maxKb / 1024) + ' MB each.'
                    : 'Documents stored on this plan, by the student and by the team.') + '</p></div></div>' +
                (files.length ? '<div class="jp-files">' + files.map(function (d) {
                    var armed = UI.armed === 'rm-doc-' + d.id, open = UI.openDoc === d.id;
                    return '<div class="jp-file-wrap' + (open ? ' open' : '') + '"><div class="jp-file"><span class="jp-file-ico k-' + fileKind(d) + '">' + ICO.doc + '<small>' + fileKind(d).toUpperCase() + '</small></span>' +
                        '<div class="w"><span class="t">' + esc(d.title) + '</span><span class="s">' + esc(d.fileName || '') + ' · ' + esc(kb(d.size)) + ' · v' + (d.version || 1) + '</span></div>' +
                        '<span class="jp-owner-chip">' + esc(d.category) + '</span>' +
                        '<span class="u">' + (d.university ? esc(d.university) : '<span class="muted">All universities</span>') + '</span>' +
                        '<span class="by"><b>' + esc(d.byName) + '</b><small>' + esc(when2(d.createdAt)) + '</small></span>' +
                        '<span class="acts"><button class="jp-btn ghost sm" data-act="doc-history" data-id="' + d.id + '">' + (open ? 'Hide edits' : plural((d.edits || []).length, 'edit', 'edits')) + '</button>' +
                        '<a class="jp-btn ghost sm" href="' + esc(d.url) + '">Download</a>' +
                        (d.canDelete ? '<button class="jp-btn danger sm' + (armed ? ' armed' : '') + '" data-act="rm-doc" data-id="' + d.id + '">' + (armed ? 'Click again' : 'Remove') + '</button>' : '') + '</span></div>' +
                        (open ? editLog(d) : '') + '</div>';
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
        return head + '<section class="jp-card">' + meta + feedback + info + editor + (actions ? '<div class="jp-actions left">' + actions + '</div>' : '') + '</section>' + review +
            '<section class="jp-card">' + editLog(saved) + '</section>';
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
    /*
     * Help, as tabs: one per part of the planner, each a short numbered
     * walk-through and a few things worth knowing. **Bold** in a step marks
     * the button or word to look for on screen.
     */
    function helpTopics() {
        var meetHow = G().configured
            ? 'Pick how it happens. **Google Meet** makes a real room in your Google account once you have connected Google on the Calendar page; otherwise paste a link. **Phone call** asks for the number; **In person** asks for a contact number.'
            : 'Pick how it happens. For **Google Meet**, create the room in Google Meet (the **Open Google Meet** link opens it) and paste its link in. **Phone call** asks for the number; **In person** asks for a contact number.';
        if (isStudent()) return [
            { key: 'start', title: 'Getting started', go: 'dashboard', intro: 'This is your plan for studying abroad. Your counsellor keeps it up to date, and you tick off the tasks that are yours.', steps: [
                'Sign in with your email and the temporary password your counsellor gave you, then choose a password of your own.',
                'Start on the **Dashboard**: it shows the stage you are on, your next steps and the dates coming up.',
                'Open **My journey** to see every stage, in order, and what is in each.',
                'Check **Recent changes** on the Dashboard to see what has moved since you last looked, and who moved it.'
            ], tips: ['The page keeps itself up to date while it is open. The round arrow at the top right refreshes it straight away.'] },
            { key: 'tasks', title: 'Your tasks', go: 'journey', intro: 'Some tasks are yours, some are your counsellor\'s. You can update yours.', steps: [
                'Find a task that is yours: it has a status you can change.',
                'Choose **In Progress** when you start it and **Completed** when it is done. Your counsellor is told straight away.',
                'Open a task to read what to do, which documents it needs, and any note your counsellor left.',
                'Anything past its date turns red. If a date no longer works, tell your counsellor and they will move it.'
            ], tips: ['Tasks owned by your counsellor or the university show their status, but only they can change it.', 'Doing more than one internship or competition? Each one is listed separately under Skill Enhancers.'] },
            { key: 'unis', title: 'Universities', go: 'universities', intro: 'Each university you apply to has its own checklist.', steps: [
                'Open **Universities** and choose a university from the cards at the top.',
                'The top of its block shows what it asks for: tests, documents, entry requirements and the date applications close.',
                'Work down its checklist with your counsellor: requirements, documents, the application, then the offer and visa.'
            ] },
            { key: 'docs', title: 'Documents & essays', go: 'documents', intro: 'Everything you upload or write for your applications lives here.', steps: [
                'Go to **My documents** and choose **Upload a file**. Pick what it is — Passport / ID, a transcript, a test score, and so on.',
                'For the **Student Declaration Form Sign-off** task, open the task and use its **Upload the signed form** button.',
                'To write your SOP or an essay, choose **Write an essay**. Save the draft as often as you like.',
                'When it is ready, **send it for review**. Your counsellor approves it or sends it back with feedback, which shows on the essay.',
                'Each document shows its history and version number, so you always know you are reading the latest one.'
            ], tips: ['Files are private: only you and your One Degree team can open them.'] },
            { key: 'dates', title: 'Dates & meetings', go: 'calendar', intro: 'Every date that matters, and every meeting booked with you.', steps: [
                '**Deadlines** lists the universities\' dates and One Degree\'s own, side by side.',
                'The **Calendar** shows meetings, deadlines and task dates as a month, or as a list with **Agenda**. Hover over (or tap) an entry for its details.',
                'A meeting shows how to join: a link, a phone number or a place.',
                'You are emailed when a meeting is booked, again the day before, and on the morning of the meeting.'
            ] },
            { key: 'account', title: 'Your account', go: 'owners', intro: 'Your login and the people working with you.', steps: [
                'Change your password any time from the menu at the top right.',
                'Forgotten it? Ask your counsellor to reset it — they will give you a new temporary one.',
                '**My team** shows everyone working on your journey, and what each side has open.'
            ] }
        ];
        return [
            { key: 'start', title: 'Getting started', go: 'dashboard', intro: 'One planner per enrolled student. Every change saves as you make it, and the student sees their side straight away.', steps: [
                'In the CRM, open the student, go to the **Student** tab and choose **Start journey planner**. That builds the plan and the student\'s login.',
                'The temporary password is shown once. Send it to the student with the sign-in link from the **Student login** page.',
                'Use **Edit plan details** on the Dashboard to set the level, intake and focus.',
                'Go through the **Core journey** and untick anything this student does not need.',
                'Add the universities they are applying to, then set target dates on the tasks that matter now.'
            ], tips: ['The page updates itself every few seconds when someone else changes the plan; the round arrow at the top right refreshes it now.', 'Progress is a count — done out of included — never a percentage.'] },
            { key: 'journey', title: 'Core journey', go: 'journey', intro: 'Seven stages done once per student, however many universities they apply to.', steps: [
                'Open a stage to see its tasks. The box on the left includes a task for this student; untick it and it drops out of every count and the student\'s view.',
                'Set each task\'s **status**, **owner** and **target date** in its row. Open the row for **notes** (the student reads these) and the completion date.',
                'An owner can be a role or a person named on the **Team** page, so a task can go to the content writer by name.',
                'Need something the template does not have? **Add a task to this stage**, or **Add a stage** for a whole new one.',
                'In **Skill Enhancers**, open an activity and choose **Add another** for a second internship or a third competition. Each has its own status and date and sits under the first.',
                '**Student Declaration Form Sign-off** (stage 2, after the shortlist sign-off) waits for the signed form. When it is uploaded the task moves to **Submitted**; mark it **Completed** once you have checked it.'
            ], tips: ['ODA\'s own tasks can only be switched off; tasks and stages you add can be edited or removed.', 'The filters at the top show one owner or one status at a time.'] },
            { key: 'unis', title: 'Universities', go: 'universities', intro: 'One block per university or programme, each with the same checklist.', steps: [
                'Choose **Add university** and enter the university, country, programme and fit (Reach, Match or Safe).',
                'Choose **Edit** on the requirements at the top of the block for the tests and documents it asks for, its entry requirements and the closing date. The closing date shows in Deadlines and on the Calendar.',
                'Work down the checklist: fit and requirements, documents, the application, then the offer and visa.',
                'Switch on Interview and Portfolio only where the university asks for them.',
                'Record the offer type when the offer arrives — the Offer received task ticks itself.'
            ] },
            { key: 'docs', title: 'Documents & essays', go: 'documents', intro: 'Everything the student uploads or writes, and everything you add for them.', steps: [
                '**Upload a file** takes PDF, Word or JPG/PNG up to 10 MB. Choose what it is and, if it is for one university, which.',
                '**Write an essay** starts a draft. The student can write one too and send it for review.',
                'Open an essay sent for review to **Approve** it, or send it back with feedback that shows on the essay.',
                'Every document keeps a history and a version number. Use **Log an edit** for a change made outside the planner.'
            ], tips: ['Files are private to people signed in to this plan.', 'A signed declaration form uploaded here moves the declaration task to Submitted.'] },
            { key: 'calendar', title: 'Deadlines & calendar', go: 'calendar', intro: 'Every date on the plan in one place.', steps: [
                '**Deadlines** keeps the universities\' dates and ODA\'s own apart. Add one of ours with **Add a date**, or with **Deadline** on the Calendar.',
                'The **Calendar** shows meetings, deadlines and task dates together. Switch between **Month** and **Agenda**, and use the chips to show only some kinds.',
                'Colour says where each thing stands: late, today, this week, later, done. Finished tasks stay on the calendar in green; **Hide finished** clears them.',
                'Hover over an entry for its details and click it to open it. Click a day to see everything on it, or to put a meeting or deadline on it.'
            ] },
            { key: 'meetings', title: 'Meetings & emails', go: 'calendar', intro: 'Book calls with the student and their family; the planner sends the emails.', steps: [
                'Choose **Schedule a meeting** (on the Calendar, or from a day). Give it a title, date, time and length.',
                meetHow,
                'Say who is coming and list the email addresses to send it to — start with the student\'s.',
                'Save. Everyone listed is emailed the joining details straight away.',
                'Reminders go by themselves at 4:00 am India time, the day before and on the day, to everyone listed and the student\'s counsellor. Untick **Remind them** to switch them off for one meeting.',
                'Afterwards, **Mark done**. Move the date and fresh reminders follow it; **Send again** re-sends the details now.'
            ], tips: ['These are plain emails with the details — not calendar invitations.'] },
            { key: 'team', title: 'Team & changes', go: 'owners', intro: 'Who works on the file, and everything they change.', steps: [
                'On **Team**, add everyone working on the file with their designation: counsellor, specialist, supervisor, content writer, test-prep tutor, external expert…',
                'Not in the list? Choose **+ Add a designation…** and it joins the list for this student.',
                'Everyone you add appears in every **Owner** dropdown, so tasks can be handed to them by name.',
                'The Dashboard\'s **Recent changes** lists every change: what, who and when. Lines since your last visit are marked **New**.'
            ], tips: ['The student sees names and designations, never contact details.', 'Team edits, login changes and stage edits stay off the student\'s list of changes.'] },
            { key: 'login', title: 'Student login', go: 'login', intro: 'How the student signs in, and what you can do about it.', steps: [
                'The student signs in at the address on the **Student login** page with their email and temporary password, then chooses their own.',
                'Forgotten password? **Reset password** issues a new temporary one, shown once.',
                'The **admin password** always opens the student\'s portal, even after they change theirs. Each use is noted on the lead timeline.',
                '**Switch off login** stops them signing in without touching the plan.'
            ] }
        ];
    }
    function helpText(t) { return esc(t).replace(/\*\*(.+?)\*\*/g, '<b>$1</b>'); }
    function renderGuide() {
        var topics = helpTopics();
        var at = Math.max(0, topics.findIndex(function (t) { return t.key === UI.helpTab; }));
        var cur = topics[at], prev = topics[at - 1], next = topics[at + 1];
        var goView = views().find(function (v) { return v.key === cur.go; });
        // The team guide (counsellors) and the illustrated PDF guide (everyone).
        var links = (isC() && P.endpoints.guide ? '<a class="jp-btn sm" href="' + esc(P.endpoints.guide) + '" target="_blank" rel="noopener">Open the full team guide</a>' : '') +
            (P.endpoints.guidePdf ? '<a class="jp-btn ghost sm" href="' + esc(P.endpoints.guidePdf) + '" target="_blank" rel="noopener">' + ICO.doc + ' Download the PDF guide</a>' : '');
        var top = links
            ? '<div class="jp-help-top"><p>' + (isC()
                ? 'Step-by-step help for each part of the planner. The team guide covers the whole CRM; the PDF guide walks through every step with screenshots, for you and for the student.'
                : 'Step-by-step help for each part of your planner. The PDF guide shows every step with pictures, and you can keep it.') +
              '</p><div class="jp-help-links">' + links + '</div></div>'
            : '';
        var tabs = '<div class="jp-help-tabs" role="tablist" aria-label="Help topics">' + topics.map(function (t, i) {
            return '<button role="tab" data-act="help-tab" data-v="' + t.key + '" class="' + (t === cur ? 'on' : '') + '" aria-selected="' + (t === cur) + '"><span class="n num">' + (i + 1) + '</span>' + esc(t.title) + '</button>';
        }).join('') + '</div>';
        var panel = '<section class="jp-card jp-help" role="tabpanel"><div class="jp-card-h"><div><h2>' + esc(cur.title) + '</h2><p>' + esc(cur.intro) + '</p></div>' +
            (goView && cur.go !== 'help' ? '<a class="jp-btn ghost sm" href="#' + cur.go + '">Go to ' + esc(label(goView)) + '</a>' : '') + '</div>' +
            '<ol class="jp-steps">' + cur.steps.map(function (st) { return '<li>' + helpText(st) + '</li>'; }).join('') + '</ol>' +
            (cur.tips ? '<div class="jp-help-tips">' + cur.tips.map(function (t) { return '<p><span class="tag">Good to know</span>' + helpText(t) + '</p>'; }).join('') + '</div>' : '') +
            '<div class="jp-help-nav">' +
            (prev ? '<button class="jp-btn ghost sm" data-act="help-tab" data-v="' + prev.key + '">‹ ' + esc(prev.title) + '</button>' : '<span></span>') +
            (next ? '<button class="jp-btn sm" data-act="help-tab" data-v="' + next.key + '">' + esc(next.title) + ' ›</button>' : '') + '</div></section>';
        return top + tabs + panel;
    }

    /* ------------------------------------------------------------ refreshing */
    /*
     * The page is drawn from the payload the server embedded, so it shows what
     * was true when it loaded. Refreshing fetches the page again and takes the
     * new payload out of it, rather than reloading the browser: the view, the
     * stages left open and where the reader had scrolled all survive.
     *
     * Anything that can't be read that way — a session that has since expired,
     * a login page coming back instead — falls through to a plain reload,
     * which lands them where they need to be anyway.
     */
    function adoptPayload(fresh) {
        ['today', 'student', 'core', 'apps', 'documents', 'team', 'deadlines', 'meetings', 'login', 'docTemplate', 'google', 'changes'].forEach(function (k) {
            if (fresh[k] !== undefined) P[k] = fresh[k];
        });
        if (fresh.pulse) { P.pulse = fresh.pulse; LIVE.seen = fresh.pulse; LIVE.pending = false; }
        T = P.template = fresh.template;

        CORE_DEFS = {}; PHASE_OF = {}; APP_DEFS = {};
        T.phases.forEach(function (ph) { ph.activities.forEach(function (a) { CORE_DEFS[a.key] = a; PHASE_OF[a.key] = ph; }); });
        T.appGroups.forEach(function (g) { g.activities.forEach(function (a) { APP_DEFS[a.key] = a; }); });
        refreshPeople();

        // Keep pointing at things that still exist.
        if (!P.apps.some(function (a) { return a.id === UI.appId; })) UI.appId = P.apps[0] ? P.apps[0].id : null;
        if (UI.essayId && !findDoc(UI.essayId)) { UI.essayId = null; UI.essayDirty = false; UI.draft = null; }
        if (UI.openDoc && !findDoc(UI.openDoc)) UI.openDoc = null;
    }

    function refreshPlanner(quiet) {
        if (UI.refreshing) return;
        UI.refreshing = true;
        render();
        fetch(location.pathname + location.search, {
            credentials: 'same-origin', cache: 'no-store',
            headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            if (!res.ok) throw new Error('refused');
            return res.text();
        }).then(function (html) {
            var node = new DOMParser().parseFromString(html, 'text/html').getElementById('jp-payload');
            if (!node) throw new Error('not the planner');
            adoptPayload(JSON.parse(node.textContent));
            UI.refreshing = false;
            render();
            toast(quiet ? 'Updated' : 'Up to date');
        }).catch(function () {
            location.reload();
        });
    }

    /*
     * Live updates. Every few seconds the page asks the server one small
     * question — has anything on this plan moved? — and only fetches the plan
     * when the answer changes. Three things hold it back, because a planner
     * redrawing itself under someone's hands is worse than being a few
     * seconds behind:
     *
     *   - the tab is in the background, where nobody is reading it
     *   - a dialog is open, or the caret is in a field
     *   - an essay has unsaved changes
     *
     * A change noticed while they are busy is remembered and applied the
     * moment they are not. Repeated failures stop the polling rather than
     * hammering a server that is plainly unwell; the refresh button still
     * works, and it starts the polling again.
     */
    var LIVE = { seen: null, timer: null, fails: 0, pending: false };

    function liveEvery() { return Math.max(0, parseInt(P.pollSeconds, 10) || 0) * 1000; }

    function startLive() {
        stopLive();
        if (!P.endpoints.pulse || !liveEvery()) return;
        LIVE.seen = LIVE.seen || P.pulse || null;
        LIVE.fails = 0;
        LIVE.timer = setInterval(tick, liveEvery());
    }
    function stopLive() { if (LIVE.timer) { clearInterval(LIVE.timer); LIVE.timer = null; } }

    /** Is the reader in the middle of something a redraw would interrupt? */
    function midEdit() {
        if ((document.getElementById('jp-modal') || {}).innerHTML) return true;
        if (UI.essayId && UI.essayDirty) return true;
        var el = document.activeElement;
        return !!(el && /^(INPUT|TEXTAREA|SELECT)$/.test(el.tagName));
    }

    function tick() {
        if (document.hidden || UI.refreshing || UI.busy) return;
        if (LIVE.pending) { if (!midEdit()) refreshPlanner(true); return; }

        fetch(P.endpoints.pulse, {
            credentials: 'same-origin', cache: 'no-store',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            if (!res.ok) throw new Error('refused');
            return res.json();
        }).then(function (json) {
            LIVE.fails = 0;
            if (!json || !json.v) return;
            if (LIVE.seen === null) { LIVE.seen = json.v; return; }
            if (json.v === LIVE.seen) return;
            LIVE.seen = json.v;
            if (midEdit()) { LIVE.pending = true; return; }
            refreshPlanner(true);
        }).catch(function () {
            LIVE.fails++;
            if (LIVE.fails >= 5) stopLive();
        });
    }

    // Coming back to the tab is the moment it most wants to be current.
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && LIVE.timer) tick();
    });

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
    function modal(html, cls) {
        document.getElementById('jp-modal').innerHTML = '<div class="jp-backdrop" data-act="close-modal"><div class="jp-dialog' + (cls ? ' ' + cls : '') + '" role="dialog" aria-modal="true">' + html + '</div></div>';
        var f = document.querySelector('.jp-dialog input, .jp-dialog select'); if (f) setTimeout(function () { f.focus(); }, 20);
    }
    function closeModal() {
        document.getElementById('jp-modal').innerHTML = '';
        UI.dialogSeq++;
        if (UI.room) releaseRoom();
    }
    function val(id) { var e = document.getElementById(id); return e ? e.value.trim() : ''; }
    function area(id) { var e = document.getElementById(id); return e ? e.value : ''; }
    function modalError(msg) { var e = document.querySelector('.jp-dialog .err'); if (e) e.textContent = msg; }
    function uniForm(a) {
        a = a || { university: '', country: '', program: '', fit: '' };
        return '<label for="u-name">University</label><input id="u-name" maxlength="150" value="' + esc(a.university) + '" placeholder="e.g. University of Melbourne">' +
            '<div class="two"><div><label for="u-country">Country</label><input id="u-country" maxlength="80" value="' + esc(a.country) + '" placeholder="e.g. Australia"></div>' +
            '<div><label for="u-fit">Fit</label><select id="u-fit"><option value="">Not set</option>' + Object.keys(T.fits).map(function (f) { return '<option value="' + f + '"' + (a.fit === f ? ' selected' : '') + '>' + T.fits[f] + '</option>'; }).join('') + '</select></div></div>' +
            '<label for="u-prog">Programme / course</label><input id="u-prog" maxlength="190" value="' + esc(a.program) + '" placeholder="e.g. Master of Data Science"><p class="err" role="alert"></p>';
    }

    function checked(id) { var e = document.getElementById(id); return !!(e && e.checked); }
    function findDeadline(key) { return P.deadlines.find(function (d) { return d.key === key; }); }
    function findMeeting(key) { return P.meetings.find(function (m) { return m.key === key; }); }
    function findMember(key) { return P.team.find(function (m) { return m.key === key; }); }
    function keyUrl(template, key) { return template.replace('__KEY__', key); }
    /*
     * The designation dropdown. One control, not two: ODA's list plus the ones
     * this plan has added, and a last entry for typing a new one. Wherever it
     * appears the current designation is the visible value.
     */
    function roleOptions(selected) {
        return (T.teamRoles || []).map(function (r) {
            return '<option' + (r === selected ? ' selected' : '') + '>' + esc(r) + '</option>';
        }).join('') + '<option value="__new">+ Add a designation…</option>';
    }

    function options(list, selected) {
        return list.map(function (o) { return '<option' + (o === selected ? ' selected' : '') + '>' + esc(o) + '</option>'; }).join('');
    }

    function goTo(view) {
        UI.menu = false;
        if (location.hash !== '#' + view) { location.hash = view; } else { fromHash(); render(); }
    }
    function scrollToEl(id) { setTimeout(function () { var el = document.getElementById(id); if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 60); }

    /* ------------------------------------------------------------ events */
    window.addEventListener('hashchange', function () { fromHash(); UI.menu = false; UI.armed = null; if (UI.view !== 'documents') { UI.essayId = null; UI.essayDirty = false; UI.draft = null; UI.openDoc = null; } render(); window.scrollTo(0, 0); });

    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-act]'); if (!el) return;
        var act = el.dataset.act;
        if (UI.armed && ['rm-uni', 'reset-password', 'toggle-login', 'rm-doc', 'essay-back', 'rm-task', 'rm-stage',
            'new-admin-pw', 'rm-deadline', 'rm-meeting', 'rm-member', 'refresh', 'google-disconnect'].indexOf(act) < 0) { UI.armed = null; }
        var appId = el.dataset.app ? parseInt(el.dataset.app, 10) : null;

        switch (act) {
            case 'menu': UI.menu = !UI.menu; render(); return;
            case 'refresh':
                // An essay being written is the one thing a refresh would lose.
                if (UI.essayId && UI.essayDirty && UI.armed !== 'refresh') { UI.armed = 'refresh'; render(); return; }
                UI.armed = null;
                refreshPlanner();
                if (!LIVE.timer) startLive();
                return;
            case 'close-menu': UI.menu = false; render(); return;
            case 'toggle-changes': UI.allChanges = !UI.allChanges; render(); return;
            case 'close-modal': if (e.target === el) closeModal(); return;
            case 'cancel': closeModal(); return;
            case 'toggle-phase': {
                var isOpen = !!document.querySelector('#ph-' + el.dataset.p + '.open');
                UI.openPhases[el.dataset.p] = !isOpen; render(); return;
            }
            case 'expand-all': case 'collapse-all':
                T.phases.forEach(function (p) { UI.openPhases[p.key] = act === 'expand-all'; }); render(); return;
            case 'jump-phase':
                closeModal();
                UI.openPhases[el.dataset.p] = true;
                if (UI.view !== 'journey') goTo('journey'); else render();
                scrollToEl('ph-' + el.dataset.p);
                return;
            case 'toggle-row': { var k = rowKey(el.dataset.scope, appId, el.dataset.key); UI.openRows[k] = !UI.openRows[k]; render(); return; }
            case 'jump-item': {
                closeModal();
                UI.owner = 'all'; UI.status = 'all';
                UI.openRows[rowKey(el.dataset.scope, appId, el.dataset.key)] = true;
                if (el.dataset.scope === 'core') {
                    var p = PHASE_OF[el.dataset.key]; UI.openPhases[p.key] = true;
                    goTo('journey'); scrollToEl('ph-' + p.key);
                } else { UI.appId = appId; goTo('universities'); scrollToEl('jp-block'); }
                return;
            }
            case 'dl': UI.dl = el.dataset.v; render(); return;
            case 'cal-step': {
                UI.cal.m += parseInt(el.dataset.v, 10);
                if (UI.cal.m > 11) { UI.cal.m = 0; UI.cal.y++; }
                if (UI.cal.m < 0) { UI.cal.m = 11; UI.cal.y--; }
                render(); return;
            }
            case 'cal-today': {
                var t0 = new Date(P.today + 'T00:00:00');
                UI.cal.y = t0.getFullYear(); UI.cal.m = t0.getMonth(); UI.cal.sel = P.today; render(); return;
            }
            case 'cal-day': UI.cal.sel = el.dataset.d; render(); openDay(el.dataset.d); return;
            case 'cal-view': UI.cal.view = el.dataset.v; render(); return;
            case 'cal-kind': {
                UI.cal.kinds = UI.cal.kinds || {};
                UI.cal.kinds[el.dataset.v] = UI.cal.kinds[el.dataset.v] === false;
                render(); return;
            }
            case 'help-tab': UI.helpTab = el.dataset.v; render(); window.scrollTo(0, 0); return;
            case 'doc-history': {
                var hid = parseInt(el.dataset.id, 10);
                UI.openDoc = UI.openDoc === hid ? null : hid; render(); return;
            }
            case 'doc-tab': UI.docTab = el.dataset.v; render(); return;
            case 'open-essay': UI.essayId = parseInt(el.dataset.id, 10); UI.essayDirty = false; UI.draft = null; render(); window.scrollTo(0, 0); return;
            case 'essay-back':
                if (UI.essayDirty && UI.armed !== 'essay-back') { UI.armed = 'essay-back'; render(); return; }
                UI.armed = null; UI.essayId = null; UI.essayDirty = false; UI.draft = null; render(); return;
            case 'pick-uni': UI.appId = parseInt(el.dataset.u, 10); render(); scrollToEl('jp-block'); return;
            case 'open-uni': closeModal(); UI.appId = parseInt(el.dataset.u, 10); goTo('universities'); return;
            case 'tick': {
                var a = actFor(el.dataset.scope, appId, el.dataset.key);
                if (a && canEdit(a)) saveActivity(el.dataset.scope, appId, el.dataset.key, { status: 'Completed' }, isStudent() ? 'Nice one. Marked as done.' : 'Completed');
                return;
            }
        }

        if (isC() || isStudent()) {
            switch (act) {
                case 'log-edit': {
                    var ld = findDoc(parseInt(el.dataset.id, 10));
                    if (!ld) return;
                    modal('<h3>Log an edit</h3><p class="sub">For a change you made to “' + esc(ld.title) + '” outside the planner. ' +
                        (isStudent() ? 'Your counsellor sees this.' : 'The student sees this.') + '</p>' +
                        '<label for="le-note">What changed?</label><input id="le-note" maxlength="290" placeholder="e.g. Rewrote the opening and tightened paragraph 3">' +
                        '<label class="jp-check"><input type="checkbox" id="le-ver" checked> This is a new version (now v' + ((ld.version || 1) + 1) + ')</label>' +
                        '<p class="err" role="alert"></p><div class="jp-actions"><button class="jp-btn ghost" data-act="cancel">Cancel</button>' +
                        '<button class="jp-btn" data-act="save-log-edit" data-id="' + ld.id + '">Log it</button></div>');
                    return;
                }
                case 'save-log-edit': {
                    if (UI.busy) return;
                    var note = val('le-note');
                    if (!note) { modalError('Say what changed.'); return; }
                    UI.busy = true; el.disabled = true;
                    api('POST', P.endpoints.documentEdits.replace('__ID__', el.dataset.id), { note: note, new_version: checked('le-ver') })
                        .then(function (res) {
                            replaceDoc(res.document); UI.openDoc = res.document.id; closeModal(); render(); toast('Edit logged');
                        }, function (err) { modalError(err.message); el.disabled = false; }).then(function () { UI.busy = false; });
                    return;
                }
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
                        '<label for="up-cat">What is it?</label><select id="up-cat">' + DT.fileCategories.map(function (c) { return '<option' + (c === el.dataset.cat ? ' selected' : '') + '>' + esc(c) + '</option>'; }).join('') + '</select>' +
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
                        replaceDoc(res.document); closeModal(); UI.docTab = UI.docTab === 'essays' ? 'all' : UI.docTab;
                        // A task waiting on this upload has moved on; fetch it.
                        if (Object.keys(CORE_DEFS).some(function (k) { return CORE_DEFS[k].upload === res.document.category; })) refreshPlanner(true);
                        render(); toast('Uploaded');
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
            case 'edit-reqs': {
                var ra = P.apps.find(function (x) { return x.id === parseInt(el.dataset.u, 10); });
                if (!ra) return;
                modal('<h3>What ' + esc(ra.university) + ' asks for</h3>' +
                    '<p class="sub">One per line, or separated by commas. They are shown as separate items on the university, not as one long line.</p>' +
                    '<div class="two"><div><label for="rq-tests">Tests required</label><textarea id="rq-tests" rows="5" maxlength="190" placeholder="IELTS 7.0&#10;SAT 1400">' + esc(splitList(ra.tests).join('\n')) + '</textarea></div>' +
                    '<div><label for="rq-docs">Documents required</label><textarea id="rq-docs" rows="5" maxlength="300" placeholder="Transcripts&#10;Essays&#10;2 LORs&#10;CV">' + esc(splitList(ra.docs).join('\n')) + '</textarea></div></div>' +
                    '<label for="rq-deadline">Application closes</label><input id="rq-deadline" type="date" value="' + esc(ra.deadline || '') + '">' +
                    '<label for="rq-notes">Entry requirements and anything else</label><textarea id="rq-notes" rows="4" maxlength="2000" placeholder="Grades, prerequisites, portfolio rules, interview format…">' + esc(ra.requirements || '') + '</textarea>' +
                    '<p class="err" role="alert"></p><div class="jp-actions"><button class="jp-btn ghost" data-act="cancel">Cancel</button>' +
                    '<button class="jp-btn" data-act="save-reqs" data-u="' + ra.id + '">Save</button></div>', 'wide');
                return;
            }
            case 'save-reqs': {
                if (UI.busy) return;
                var rqId = parseInt(el.dataset.u, 10);
                var rqBody = {
                    tests_required: splitList(area('rq-tests')).join(', '),
                    documents_required: splitList(area('rq-docs')).join(', '),
                    requirements: area('rq-notes').trim(),
                    deadline: val('rq-deadline') || null
                };
                UI.busy = true; el.disabled = true;
                api('PATCH', appUrl(rqId), rqBody).then(function (res) {
                    replaceApp(res.application); closeModal(); render(); toast('Saved');
                }, function (err) { modalError(err.message); el.disabled = false; }).then(function () { UI.busy = false; });
                return;
            }
            case 'add-deadline': case 'edit-deadline': {
                var dl = act === 'edit-deadline' ? findDeadline(el.dataset.k) : null;
                var dlKind = dl ? dl.kind : el.dataset.v;
                modal('<h3>' + (dl ? 'Edit this date' : dlKind === 'uni' ? 'Add a university date' : 'Add one of our dates') + '</h3>' +
                    '<p class="sub">' + (dlKind === 'uni' ? 'A date a university set. An application closing date is better recorded on the university itself, where the checklist can see it.'
                        : 'A date ODA set for itself, usually ahead of the university\'s own.') + '</p>' +
                    '<label for="dl-what">What is it for?</label><input id="dl-what" maxlength="150" value="' + esc(dl ? dl.what : '') + '" placeholder="' + (dlKind === 'uni' ? 'e.g. Early round closes' : 'e.g. SOP first draft from the student') + '">' +
                    '<div class="two"><div><label for="dl-who">Who</label><input id="dl-who" maxlength="120" value="' + esc(dl ? dl.who : '') + '" placeholder="' + (dlKind === 'uni' ? 'University' : 'Who owns it') + '"></div>' +
                    '<div><label for="dl-date">Date</label><input id="dl-date" type="date" value="' + esc(dl ? dl.date : (el.dataset.d || '')) + '"></div></div>' +
                    '<p class="err" role="alert"></p><div class="jp-actions"><button class="jp-btn ghost" data-act="cancel">Cancel</button>' +
                    '<button class="jp-btn" data-act="save-deadline" data-v="' + dlKind + '"' + (dl ? ' data-k="' + esc(dl.key) + '"' : '') + '>' + (dl ? 'Save' : 'Add date') + '</button></div>');
                return;
            }
            case 'save-deadline': {
                if (UI.busy) return;
                var dWhat = val('dl-what'), dDate = val('dl-date');
                if (!dWhat) { modalError('Say what the date is for.'); return; }
                if (!dDate) { modalError('Choose a date.'); return; }
                var dKey = el.dataset.k;
                UI.busy = true; el.disabled = true;
                api(dKey ? 'PATCH' : 'POST', dKey ? keyUrl(P.endpoints.deadline, dKey) : P.endpoints.deadlines,
                    { kind: el.dataset.v, what: dWhat, who: val('dl-who'), date: dDate }).then(function (res) {
                        P.deadlines = res.deadlines; closeModal(); render(); toast(dKey ? 'Date saved' : 'Date added');
                    }, function (err) { modalError(err.message); el.disabled = false; }).then(function () { UI.busy = false; });
                return;
            }
            case 'rm-deadline': {
                var rdk = el.dataset.k;
                if (UI.armed !== 'rm-dl-' + rdk) { UI.armed = 'rm-dl-' + rdk; render(); return; }
                UI.armed = null;
                api('DELETE', keyUrl(P.endpoints.deadline, rdk)).then(function (res) {
                    P.deadlines = res.deadlines; render(); toast('Date removed');
                }, function (err) { render(); toast(err.message, true); });
                return;
            }
            case 'add-meeting': case 'edit-meeting': {
                var mt = act === 'edit-meeting' ? findMeeting(el.dataset.k) : null;
                var mtMode = mt ? mt.mode : T.meetingModes[0];
                var mtMails = mt && mt.emails ? mt.emails.join(', ') : '';
                var mtLink = mt && mt.link ? mt.link : '';
                if (UI.room) releaseRoom();
                UI.dialogSeq++;
                modal('<h3>' + (mt ? 'Edit meeting' : 'Schedule a meeting') + '</h3>' +
                    '<p class="sub">Everyone you list is emailed the joining details when you save, then reminded the day before and on the day.</p>' +
                    '<label for="mt-title">What is it about?</label><input id="mt-title" maxlength="150" value="' + esc(mt ? mt.title : '') + '" placeholder="e.g. Shortlist review with parents">' +
                    '<div class="three"><div><label for="mt-date">Date</label><input id="mt-date" type="date" value="' + esc(mt ? mt.date : (el.dataset.d || '')) + '"></div>' +
                    '<div><label for="mt-time">Time</label><input id="mt-time" type="time" value="' + esc(mt ? mt.time : '16:00') + '"></div>' +
                    '<div><label for="mt-mins">Minutes</label><input id="mt-mins" type="number" min="5" max="480" step="5" value="' + (mt ? mt.minutes : 45) + '"></div></div>' +
                    '<label for="mt-mode">How</label><select id="mt-mode">' + options(T.meetingModes, mtMode) + '</select>' +

                    '<div id="mt-link-wrap"' + (wantsLink(mtMode) ? '' : ' hidden') + '>' +
                    '<label for="mt-link">Join link</label>' +
                    '<div class="jp-field-row"><input id="mt-link" maxlength="300" value="' + esc(mtLink) + '" placeholder="https://meet.google.com/…">' +
                    '<button type="button" class="jp-btn ghost sm" data-act="copy-meet-link">Copy</button></div>' +
                    '<p class="jp-hint" id="mt-link-hint">' + roomHint(mt) + '</p></div>' +

                    '<div id="mt-phone-wrap"' + (wantsPhone(mtMode) ? '' : ' hidden') + '>' +
                    '<label for="mt-phone" id="mt-phone-label">' + phoneLabel(mtMode) + '</label>' +
                    '<input id="mt-phone" type="tel" maxlength="40" value="' + esc(mt ? mt.phone : '') + '" placeholder="e.g. +91 98290 00000"></div>' +

                    '<div class="two"><div><label for="mt-who">Who is coming</label><input id="mt-who" maxlength="190" value="' + esc(mt ? mt.who : '') + '" placeholder="e.g. ' + esc(P.student.firstName) + ', parents, you"></div>' +
                    '<div><label for="mt-emails">Email the details to</label><input id="mt-emails" maxlength="600" value="' + esc(mtMails) + '" placeholder="aarav@example.com, parent@example.com"></div></div>' +
                    '<p class="jp-hint">Separate addresses with commas. Leave it empty to send nothing.</p>' +
                    '<label for="mt-notes">Notes <span style="font-weight:500">(the student sees these)</span></label><textarea id="mt-notes" maxlength="1000" rows="3" placeholder="Where to meet, what to bring, what was agreed…">' + esc(mt ? mt.notes : '') + '</textarea>' +
                    '<label class="jp-check"><input type="checkbox" id="mt-notify" checked> Email them the joining details when I save</label>' +
                    '<label class="jp-check"><input type="checkbox" id="mt-remind"' + (mt && mt.remind === false ? '' : ' checked') + '> Remind them, and ' + esc(P.student.counsellor || 'the counsellor') + ', the day before and on the day</label>' +
                    '<p class="err" role="alert"></p><div class="jp-actions"><button class="jp-btn ghost" data-act="cancel">Cancel</button>' +
                    '<button class="jp-btn" data-act="save-meeting"' + (mt ? ' data-k="' + esc(mt.key) + '"' : '') + '>' + (mt ? 'Save' : 'Schedule it') + '</button></div>', 'wide');
                if (wantsLink(mtMode)) makeRoom();
                return;
            }
            case 'google-disconnect': {
                if (UI.armed !== 'google-off') { UI.armed = 'google-off'; render(); return; }
                UI.armed = null;
                api('POST', G().disconnect).then(function (res) {
                    P.google = res.google; render(); toast('Google disconnected. Rooms already made keep working.');
                }, function (err) { render(); toast(err.message, true); });
                return;
            }
            case 'copy-meet-link': {
                var linkBox = document.getElementById('mt-link');
                if (linkBox && linkBox.value) copyText(linkBox.value, 'Join link copied');
                return;
            }
            case 'save-meeting': {
                if (UI.busy) return;
                var mTitle = val('mt-title'), mDate = val('mt-date'), mLink = val('mt-link');
                if (!mTitle) { modalError('Say what the meeting is about.'); return; }
                if (!mDate) { modalError('Choose a date.'); return; }
                if (UI.roomBusy) { modalError('Still making the Google Meet room — a moment.'); return; }
                if (mLink && mLink.indexOf('https://') !== 0) { modalError('A join link has to start with https://.'); return; }
                var mKey = el.dataset.k;
                var mMails = splitList(val('mt-emails'));
                var mBad = mMails.filter(function (e) { return !/^[^@\s]+@[^@\s.]+\.[^@\s]+$/.test(e); });
                if (mBad.length) { modalError('This does not look like an email address: ' + mBad[0]); return; }
                var mMode = val('mt-mode');
                var mBody = {
                    title: mTitle, date: mDate, time: val('mt-time') || null, minutes: parseInt(val('mt-mins'), 10) || 45,
                    mode: mMode, who: val('mt-who'), emails: mMails,
                    phone: wantsPhone(mMode) ? val('mt-phone') : '',
                    link: wantsLink(mMode) ? (mLink || null) : null,
                    notes: (document.getElementById('mt-notes') || {}).value || '',
                    notify: checked('mt-notify') && mMails.length > 0,
                    remind: checked('mt-remind'),
                    room: UI.room ? UI.room.event : null
                };
                UI.busy = true; el.disabled = true;
                api(mKey ? 'PATCH' : 'POST', mKey ? keyUrl(P.endpoints.meeting, mKey) : P.endpoints.meetings, mBody).then(function (res) {
                    UI.room = null; // the server has it now, used or given back
                    P.meetings = res.meetings; closeModal(); render();
                    if (res.warning) { toast(res.warning, true); return; }
                    toast((mKey ? 'Meeting saved' : 'Meeting scheduled') + (res.sent ? ' · details emailed to ' + plural(res.sent, 'person', 'people') : ''));
                }, function (err) { modalError(err.message); el.disabled = false; }).then(function () { UI.busy = false; });
                return;
            }
            case 'send-meeting': {
                var sm = findMeeting(el.dataset.k);
                if (!sm) return;
                el.disabled = true;
                api('POST', keyUrl(P.endpoints.meetingNotify, sm.key)).then(function (res) {
                    P.meetings = res.meetings; render();
                    toast(res.sent ? 'Sent to ' + plural(res.sent, 'person', 'people') : 'Nothing went out — check the addresses.', !res.sent);
                }, function (err) { render(); toast(err.message, true); });
                return;
            }
            case 'cal-open-meeting': {
                // A meeting opened from the month: the day it sits on is
                // selected, and the meetings list below is scrolled to.
                var cm = findMeeting(el.dataset.k);
                if (!cm) return;
                UI.cal.sel = cm.date;
                render();
                scrollToEl('jp-meetings');
                return;
            }
            case 'meeting-done': {
                var dm = findMeeting(el.dataset.k);
                if (!dm) return;
                closeModal();
                api('PATCH', keyUrl(P.endpoints.meeting, dm.key), { done: !dm.done }).then(function (res) {
                    P.meetings = res.meetings; render(); toast(dm.done ? 'Back on the list' : 'Marked as done');
                }, function (err) { render(); toast(err.message, true); });
                return;
            }
            case 'rm-meeting': {
                var rmk = el.dataset.k;
                if (UI.armed !== 'rm-meeting-' + rmk) { UI.armed = 'rm-meeting-' + rmk; render(); return; }
                UI.armed = null;
                api('DELETE', keyUrl(P.endpoints.meeting, rmk)).then(function (res) {
                    P.meetings = res.meetings; render(); toast('Meeting cancelled');
                }, function (err) { render(); toast(err.message, true); });
                return;
            }
            case 'add-member': case 'edit-member': {
                var mb = act === 'edit-member' ? findMember(el.dataset.k) : null;
                modal('<h3>' + (mb ? 'Edit this person' : 'Add someone to the file') + '</h3>' +
                    '<p class="sub">' + esc(P.student.firstName) + ' sees the name and the designation, not the contact details.</p>' +
                    '<label for="mb-role">Designation</label><select id="mb-role">' + roleOptions(mb ? mb.role : (T.teamRoles[0] || '')) + '</select>' +
                    '<div id="mb-newrole-wrap" hidden><label for="mb-newrole">Name the new designation</label>' +
                    '<input id="mb-newrole" maxlength="60" placeholder="e.g. Portfolio reviewer"></div>' +
                    '<label for="mb-name">Name</label><input id="mb-name" maxlength="120" value="' + esc(mb ? mb.name : '') + '" placeholder="e.g. Priya Sharma">' +
                    '<label for="mb-contact">Email or note <span style="font-weight:500">(optional)</span></label><input id="mb-contact" maxlength="190" value="' + esc(mb ? mb.contact : '') + '" placeholder="e.g. priya@onedegreeadvisory.com">' +
                    '<label class="jp-check"><input type="checkbox" id="mb-ext"' + (mb && mb.external ? ' checked' : '') + '> Outside ODA (an alum, a partner, a guest expert)</label>' +
                    '<p class="err" role="alert"></p><div class="jp-actions"><button class="jp-btn ghost" data-act="cancel">Cancel</button>' +
                    '<button class="jp-btn" data-act="save-member"' + (mb ? ' data-k="' + esc(mb.key) + '"' : '') + '>' + (mb ? 'Save' : 'Add') + '</button></div>');
                return;
            }
            case 'save-member': {
                if (UI.busy) return;
                var mbName = val('mb-name');
                if (!mbName) { modalError('Give the person a name.'); return; }
                var mbKey = el.dataset.k;
                var mbRole = val('mb-role') === '__new' ? val('mb-newrole') : val('mb-role');
                if (!mbRole) { modalError('Name the new designation, or pick one from the list.'); return; }
                var mbBody = { role: mbRole, name: mbName, contact: val('mb-contact'), external: checked('mb-ext') };
                UI.busy = true; el.disabled = true;
                api(mbKey ? 'PATCH' : 'POST', mbKey ? keyUrl(P.endpoints.member, mbKey) : P.endpoints.team, mbBody).then(function (res) {
                    P.team = res.team; T.teamRoles = res.roles; refreshPeople(); closeModal(); render(); toast(mbKey ? 'Saved' : mbName + ' added');
                }, function (err) { modalError(err.message); el.disabled = false; }).then(function () { UI.busy = false; });
                return;
            }
            case 'save-role': {
                if (UI.busy) return;
                var nrName = val('nr-name');
                if (!nrName) { modalError('Give the designation a name.'); return; }
                UI.busy = true; el.disabled = true;
                api('PATCH', keyUrl(P.endpoints.member, el.dataset.k), { role: nrName }).then(function (res) {
                    P.team = res.team; T.teamRoles = res.roles; refreshPeople(); closeModal(); render(); toast('“' + nrName + '” added');
                }, function (err) { modalError(err.message); el.disabled = false; }).then(function () { UI.busy = false; });
                return;
            }
            case 'rm-member': {
                var rmb = el.dataset.k;
                if (UI.armed !== 'rm-member-' + rmb) { UI.armed = 'rm-member-' + rmb; render(); return; }
                UI.armed = null;
                api('DELETE', keyUrl(P.endpoints.member, rmb)).then(function (res) {
                    P.team = res.team; T.teamRoles = res.roles; refreshPeople(); render(); toast('Removed from the file');
                }, function (err) { render(); toast(err.message, true); });
                return;
            }
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
            case 'add-repeat': {
                var base = CORE_DEFS[el.dataset.key];
                if (!base) return;
                var nth = repeatsOf(base.key).length + 2;
                modal('<h3>Add another ' + esc(base.name.toLowerCase()) + '</h3><p class="sub">Listed under “' + esc(base.name) + '”, with its own status, owner and date.</p>' +
                    '<label for="tk-name">Name</label><input id="tk-name" maxlength="150" value="' + esc(base.name + ' ' + nth) + '" placeholder="e.g. ' + esc(base.name) + ' — summer 2026">' +
                    '<label for="tk-desc">What to do <span style="font-weight:500">(optional)</span></label><input id="tk-desc" maxlength="500" value="' + esc(base.desc) + '">' +
                    '<label for="tk-docs">Documents needed <span style="font-weight:500">(optional)</span></label><input id="tk-docs" maxlength="190" value="' + esc(base.docs !== 'None' ? base.docs : '') + '">' +
                    '<div class="two"><div><label for="tk-owner">Who does it</label><select id="tk-owner">' + ownerOptionsHtml(P.core[base.key].owner) + '</select></div>' +
                    '<div><label for="tk-target">Target date <span style="font-weight:500">(optional)</span></label><input id="tk-target" type="date"></div></div>' +
                    '<p class="err" role="alert"></p><div class="jp-actions"><button class="jp-btn ghost" data-act="cancel">Cancel</button><button class="jp-btn" data-act="save-task" data-p="' + esc(PHASE_OF[base.key].key) + '" data-parent="' + esc(base.key) + '">Add</button></div>', 'wide');
                var nm = document.getElementById('tk-name'); if (nm) setTimeout(function () { nm.select(); }, 30);
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
                    (editing ? '' : '<div class="two"><div><label for="tk-owner">Who does it</label><select id="tk-owner">' + ownerOptionsHtml('Student') + '</select></div>' +
                        '<div><label for="tk-target">Target date <span style="font-weight:500">(optional)</span></label><input id="tk-target" type="date"></div></div>') +
                    '<p class="err" role="alert"></p><div class="jp-actions"><button class="jp-btn ghost" data-act="cancel">Cancel</button><button class="jp-btn" data-act="save-task" data-p="' + esc(phaseKey) + '"' + (editing ? ' data-key="' + esc(editing.key) + '"' : '') + '>' + (editing ? 'Save' : 'Add task') + '</button></div>', 'wide');
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
                    var parentKey = el.dataset.parent || null;
                    api('POST', P.endpoints.tasks, { phase: pk, name: name, desc: val('tk-desc'), docs: val('tk-docs'), owner: val('tk-owner'), target: val('tk-target') || null, parent: parentKey }).then(function (res) {
                        var ph3 = T.phases.find(function (x) { return x.key === pk; });
                        // A repeat goes straight after the last of its kind.
                        var at = -1;
                        if (parentKey) ph3.activities.forEach(function (a, i) { if (a.key === parentKey || a.parent === parentKey) at = i; });
                        if (at >= 0) ph3.activities.splice(at + 1, 0, res.task); else ph3.activities.push(res.task);
                        CORE_DEFS[res.task.key] = res.task; PHASE_OF[res.task.key] = ph3; P.core[res.task.key] = res.activity;
                        if (parentKey && res.parentActivity) P.core[parentKey] = res.parentActivity;
                        UI.openPhases[pk] = true; UI.openRows[rowKey('core', null, res.task.key)] = false;
                        closeModal(); render(); toast(parentKey ? 'Added under ' + (CORE_DEFS[parentKey] || {}).name : 'Task added');
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
            case 'toggle-admin-pw': UI.showAdmin = !UI.showAdmin; render(); return;
            case 'copy-admin-pw': copyText(P.login.adminPassword, 'Admin password copied'); return;
            case 'new-admin-pw':
                if (UI.armed !== 'admin-pw') { UI.armed = 'admin-pw'; render(); return; }
                UI.armed = null;
                api('POST', P.endpoints.adminPassword).then(function (res) {
                    P.login.adminPassword = res.adminPassword; UI.showAdmin = true; render(); toast('New admin password ready');
                }, function (err) { render(); toast(err.message, true); });
                return;
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
        // The designation dropdown on a member card saves as soon as it changes;
        // choosing the last entry asks for the new designation first.
        if (el.dataset.mf === 'role' && isC()) {
            var mKey = el.dataset.k, member = findMember(mKey);
            if (el.value === '__new') {
                el.value = member ? member.role : '';
                modal('<h3>Add a designation</h3><p class="sub">It joins the list for this student from now on.</p>' +
                    '<label for="nr-name">Designation</label><input id="nr-name" maxlength="60" placeholder="e.g. Portfolio reviewer">' +
                    '<p class="err" role="alert"></p><div class="jp-actions"><button class="jp-btn ghost" data-act="cancel">Cancel</button>' +
                    '<button class="jp-btn" data-act="save-role" data-k="' + esc(mKey) + '">Add it</button></div>');
                return;
            }
            api('PATCH', keyUrl(P.endpoints.member, mKey), { role: el.value }).then(function (res) {
                P.team = res.team; T.teamRoles = res.roles; refreshPeople(); render(); toast('Designation changed');
            }, function (err) { render(); toast(err.message, true); });
            return;
        }
        // In the add/edit dialog the same last entry reveals a box to type in.
        if (el.id === 'mb-role') {
            var wrap = document.getElementById('mb-newrole-wrap');
            if (wrap) {
                wrap.hidden = el.value !== '__new';
                if (el.value === '__new') { var box = document.getElementById('mb-newrole'); if (box) box.focus(); }
            }
            return;
        }
        // How a meeting happens decides which of the two fields is asked for.
        if (el.id === 'mt-mode') {
            var linkWrap = document.getElementById('mt-link-wrap'), phoneWrap = document.getElementById('mt-phone-wrap');
            var linkBox = document.getElementById('mt-link'), phoneLbl = document.getElementById('mt-phone-label');
            if (linkWrap) linkWrap.hidden = !wantsLink(el.value);
            if (phoneWrap) phoneWrap.hidden = !wantsPhone(el.value);
            if (phoneLbl) phoneLbl.textContent = phoneLabel(el.value);
            if (wantsLink(el.value) && linkBox && !linkBox.value.trim()) makeRoom();
            return;
        }
        if (el.id === 'f-owner') { UI.owner = el.value; render(); return; }
        if (el.id === 'f-status') { UI.status = el.value; render(); return; }
        if (el.id === 'f-excl') { UI.showExcluded = el.checked; render(); return; }
        if (el.id === 'cal-hide-done') { UI.cal.hideDone = el.checked; render(); return; }
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
        if (e.key === 'Escape') { closeModal(); if (UI.menu) { UI.menu = false; render(); } return; }
        // A calendar day is a div so the entries inside it can be their own
        // buttons; give it back the keyboard a button would have had.
        if ((e.key === 'Enter' || e.key === ' ') && e.target && e.target.classList && e.target.classList.contains('jp-day')) {
            e.preventDefault(); e.target.click();
        }
    });

    refreshPeople();
    fromHash();
    render();
    rememberSeen();
    startLive();
    if (P.google && P.google.notice) toast(P.google.notice.text, !P.google.notice.ok);
})();
