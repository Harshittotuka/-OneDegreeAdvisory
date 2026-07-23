document.addEventListener('DOMContentLoaded', () => {
    const transitionScreen = document.getElementById('transitionScreen');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let activeLoads = 0;
    let pageRequest = null;

    const setCrmTheme = (theme, persist = true) => {
        const selectedTheme = ['classic', 'evergreen', 'orbit'].includes(theme) ? theme : 'evergreen';
        const stylesheet = document.getElementById('crmThemeStylesheet');
        if (!stylesheet) return;
        const nextHref = selectedTheme === 'classic' ? stylesheet.dataset.classicHref : (selectedTheme === 'orbit' ? stylesheet.dataset.orbitHref : stylesheet.dataset.evergreenHref);
        document.documentElement.dataset.crmTheme = selectedTheme;
        if (stylesheet.getAttribute('href') !== nextHref) stylesheet.setAttribute('href', nextHref);
        document.querySelectorAll('[data-crm-theme-switcher]').forEach((selector) => { selector.value = selectedTheme; });
        if (persist) {
            try { localStorage.setItem('crmTheme', selectedTheme); } catch (error) {}
        }
    };

    const showTransition = (label, mode = 'full') => {
        if (!transitionScreen) return;
        const copy = transitionScreen.querySelector('[data-transition-copy]');
        if (copy && label) copy.textContent = label;
        transitionScreen.classList.toggle('quick', mode === 'quick');
        transitionScreen.setAttribute('aria-hidden', 'false');
        document.body.classList.add('is-transitioning');
        requestAnimationFrame(() => transitionScreen.classList.add('active'));
    };

    const resetTransition = () => {
        transitionScreen?.classList.remove('active', 'quick');
        transitionScreen?.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('is-transitioning');
    };

    const setLoading = (loading) => {
        activeLoads = Math.max(0, activeLoads + (loading ? 1 : -1));
        document.body.classList.toggle('crm-ajax-loading', activeLoads > 0);
        const app = document.querySelector('[data-crm-app]');
        if (app) {
            if (activeLoads > 0) app.setAttribute('aria-busy', 'true');
            else app.removeAttribute('aria-busy');
        }
    };

    const openModal = (id, focus = true) => {
        const overlay = document.getElementById(id);
        if (!overlay) return;
        overlay.classList.add('open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        if (!focus) return;
        setTimeout(() => {
            const target = overlay.querySelector('[aria-invalid="true"]') || overlay.querySelector('input:not([type=file]), select, textarea');
            target?.focus();
            target?.scrollIntoView({block: 'center', behavior: reducedMotion.matches ? 'auto' : 'smooth'});
        }, 70);
    };

    const closeModal = (overlay) => {
        overlay?.classList.remove('open');
        overlay?.setAttribute('aria-hidden', 'true');
        if (!document.querySelector('.overlay.open')) document.body.style.overflow = '';
    };

    const setDrawerExpanded = (expanded, remember = true) => {
        const overlay = document.getElementById('leadDrawer');
        const drawer = overlay?.querySelector('.drawer');
        const button = overlay?.querySelector('[data-drawer-expand]');
        if (!overlay || !drawer || !button) return;
        overlay.classList.toggle('is-expanded', expanded);
        button.setAttribute('aria-pressed', expanded ? 'true' : 'false');
        button.setAttribute('aria-label', expanded ? 'Restore normal drawer width' : 'Expand lead workspace to full screen');
        button.title = expanded ? 'Restore drawer' : 'Expand full screen';
        if (remember) sessionStorage.setItem('crmDrawerExpanded', expanded ? '1' : '0');
        requestAnimationFrame(() => {
            const head = drawer.querySelector('.drawer-head');
            if (head) drawer.style.setProperty('--drawer-head-height', `${Math.ceil(head.getBoundingClientRect().height)}px`);
        });
    };

    const activateTab = (name, animate = true) => {
        const tabs = [...document.querySelectorAll('[data-tab]')];
        const targetIndex = tabs.findIndex((tab) => tab.dataset.tab === name);
        if (targetIndex < 0) return;
        const currentIndex = tabs.findIndex((tab) => tab.classList.contains('active'));

        tabs.forEach((tab) => tab.classList.toggle('active', tab.dataset.tab === name));
        document.querySelectorAll('[data-panel]').forEach((panel) => {
            const isTarget = panel.dataset.panel === name;
            panel.classList.toggle('active', isTarget);
            panel.classList.remove('tab-motion-forward', 'tab-motion-back');
            if (isTarget && animate && currentIndex !== targetIndex && !reducedMotion.matches) {
                void panel.offsetWidth;
                panel.classList.add(targetIndex > currentIndex ? 'tab-motion-forward' : 'tab-motion-back');
            }
        });
    };

    const initialiseDrawerChrome = () => {
        const drawer = document.querySelector('#leadDrawer .drawer');
        const head = drawer?.querySelector('.drawer-head');
        const tabs = drawer?.querySelector('.drawer-tabs');
        if (!drawer || !head || !tabs) return;

        const quickActions = drawer.querySelector('.quick-actions');
        const measure = () => drawer.style.setProperty('--drawer-head-height', `${Math.ceil(head.getBoundingClientRect().height)}px`);
        const sync = () => {
            const threshold = (quickActions?.offsetHeight || 48) + 10;
            tabs.classList.toggle('is-stuck', drawer.scrollTop > threshold);
        };

        measure();
        sync();
        drawer.addEventListener('scroll', sync, {passive: true});
        setTimeout(() => { measure(); sync(); }, 80);
    };

    const initialiseDashboardMap = () => {
        const host = document.querySelector('[data-lead-world-map]');
        const canvas = host?.querySelector('[data-leaflet-canvas]');
        if (!host || !canvas || host.dataset.mapReady === '1') return;

        if (!window.L) {
            const loading = canvas.querySelector('.map-loading');
            if (loading) loading.textContent = 'The geographic map could not be loaded.';
            return;
        }

        let points = [];
        try {
            points = JSON.parse(host.dataset.mapPoints || '[]');
        } catch (error) {
            points = [];
        }

        canvas.replaceChildren();
        const map = window.L.map(canvas, {
            scrollWheelZoom: false,
            zoomSnap: .5,
            minZoom: 1,
            maxZoom: 7,
            maxBounds: [[-75, -180], [85, 180]],
            maxBoundsViscosity: .8,
        });

        window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            noWrap: true,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);

        const bounds = [];
        points.forEach((point) => {
            const latitude = Number(point.lat);
            const longitude = Number(point.lng);
            const total = Number(point.total) || 0;
            if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return;

            const marker = window.L.marker([latitude, longitude], {
                icon: window.L.divIcon({
                    className: 'crm-leaflet-marker-shell',
                    html: `<span>${total}</span>`,
                    iconSize: [38, 38],
                    iconAnchor: [19, 19],
                }),
            }).addTo(map);

            const tooltip = document.createElement('div');
            const country = document.createElement('strong');
            const count = document.createElement('small');
            country.textContent = point.label || 'Other destination';
            count.textContent = `${total} ${total === 1 ? 'lead' : 'leads'}`;
            tooltip.append(country, count);
            marker.bindTooltip(tooltip, {direction: 'top', offset: [0, -17], className: 'crm-map-tooltip'});
            bounds.push([latitude, longitude]);
        });

        if (bounds.length > 1) map.fitBounds(bounds, {padding: [42, 42], maxZoom: 3});
        else if (bounds.length === 1) map.setView(bounds[0], 3);
        else map.setView([24, 15], 1.5);

        host.dataset.mapReady = '1';
        host._crmLeafletMap = map;
        setTimeout(() => map.invalidateSize(), 100);
    };

    const markScrollableTables = () => {
        document.querySelectorAll('.table-wrap').forEach((wrap) => {
            const scrollable = wrap.scrollWidth > wrap.clientWidth + 1 || wrap.scrollHeight > wrap.clientHeight + 1;
            wrap.classList.toggle('is-scrollable', scrollable);
        });
    };

    const initialiseApp = ({modal = null, tab = null, drawerScroll = null, windowScroll = null, drawerExpanded = null} = {}) => {
        setCrmTheme(document.documentElement.dataset.crmTheme, false);
        document.querySelectorAll('[data-open-on-load]').forEach((overlay) => openModal(overlay.id));
        if (modal && !document.querySelector('.overlay.open')) openModal(modal, false);
        if (tab) activateTab(tab, false);
        if (drawerScroll !== null) {
            const drawer = document.querySelector('#leadDrawer .drawer');
            if (drawer) drawer.scrollTop = drawerScroll;
        }
        if (windowScroll !== null) window.scrollTo({top: windowScroll, behavior: 'auto'});
        if (!document.querySelector('.overlay.open')) document.body.style.overflow = '';
        setDrawerExpanded(drawerExpanded ?? sessionStorage.getItem('crmDrawerExpanded') === '1', false);
        initialiseDrawerChrome();
        initialiseDashboardMap();
        initialiseToasts();
        markScrollableTables();
    };

    const dismissToast = (toast) => {
        if (!toast || toast.classList.contains('is-leaving')) return;
        toast.classList.add('is-leaving');
        setTimeout(() => toast.remove(), reducedMotion.matches ? 20 : 310);
    };

    const scheduleToast = (toast) => {
        if (!toast || toast.dataset.toastScheduled === '1') return;
        toast.dataset.toastScheduled = '1';
        setTimeout(() => dismissToast(toast), 6500);
    };

    const initialiseToasts = () => {
        document.querySelectorAll('[data-toast]').forEach(scheduleToast);
    };

    const showToast = (message, type = 'error', title = null) => {
        let stack = document.querySelector('[data-crm-toast-stack]');
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'crm-toast-stack';
            stack.dataset.crmToastStack = '';
            stack.setAttribute('aria-live', 'polite');
            document.body.append(stack);
        }

        const notice = document.createElement('div');
        notice.className = `crm-toast is-${type}`;
        notice.dataset.toast = '';
        notice.setAttribute('role', type === 'error' ? 'alert' : 'status');
        const icon = document.createElement('span');
        icon.className = 'crm-toast-icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.innerHTML = type === 'error'
            ? '<svg viewBox="0 0 24 24"><path d="M12 8v5M12 17h.01"/><circle cx="12" cy="12" r="9"/></svg>'
            : (type === 'info' ? '<svg viewBox="0 0 24 24"><path d="M12 11v6M12 7h.01"/><circle cx="12" cy="12" r="9"/></svg>' : '<svg viewBox="0 0 24 24"><path d="m7 12 3 3 7-7"/></svg>');
        const copy = document.createElement('span');
        copy.className = 'crm-toast-copy';
        const heading = document.createElement('strong');
        heading.textContent = title || (type === 'error' ? 'Action needs attention' : (type === 'info' ? 'For your information' : 'Update complete'));
        const detail = document.createElement('span');
        detail.textContent = message;
        copy.append(heading, detail);
        const close = document.createElement('button');
        close.type = 'button';
        close.dataset.toastClose = '';
        close.setAttribute('aria-label', 'Dismiss notification');
        close.textContent = '×';
        const progress = document.createElement('i');
        progress.className = 'crm-toast-progress';
        progress.setAttribute('aria-hidden', 'true');
        notice.append(icon, copy, close, progress);
        stack.prepend(notice);
        scheduleToast(notice);
    };

    const showPageMessage = (message, type = 'error') => showToast(message, type);

    const isDashboardUrl = (url) => {
        const path = url.pathname.replace(/\/$/, '');
        return url.origin === window.location.origin && path.endsWith('/crm');
    };

    const swapApp = async (html, finalUrl, options) => {
        const parsed = new DOMParser().parseFromString(html, 'text/html');
        const nextApp = parsed.querySelector('[data-crm-app]');
        const currentApp = document.querySelector('[data-crm-app]');
        if (!nextApp || !currentApp) {
            window.location.assign(finalUrl);
            return false;
        }
        nextApp.classList.add('is-ajax-rendered');

        // Preserve the focused filter search field (name, value, caret) so live
        // "search as you type" survives the full-app swap without losing focus.
        const activeEl = document.activeElement;
        const activeFilter = activeEl && typeof activeEl.matches === 'function'
            && activeEl.matches('[data-crm-filter-form] input[type="search"]')
            ? { name: activeEl.name, value: activeEl.value, start: activeEl.selectionStart, end: activeEl.selectionEnd }
            : null;

        const previousUrl = new URL(window.location.href);
        const nextUrl = new URL(finalUrl, window.location.href);
        const sameLead = previousUrl.searchParams.get('lead') && previousUrl.searchParams.get('lead') === nextUrl.searchParams.get('lead');
        const state = {
            modal: options.preserveModal || null,
            tab: sameLead ? (options.preserveTab || document.querySelector('.tab.active')?.dataset.tab || null) : null,
            drawerScroll: sameLead ? (document.querySelector('#leadDrawer .drawer')?.scrollTop ?? null) : null,
            drawerExpanded: document.getElementById('leadDrawer')?.classList.contains('is-expanded') ?? null,
            windowScroll: options.preserveScroll ? window.scrollY : null,
        };

        const replace = () => {
            currentApp.querySelector('[data-lead-world-map]')?._crmLeafletMap?.remove();
            currentApp.replaceWith(nextApp);
            document.title = parsed.title || document.title;
            if (options.historyMode === 'push') history.pushState({}, '', nextUrl.href);
            if (options.historyMode === 'replace') history.replaceState({}, '', nextUrl.href);
            initialiseApp(state);
            if (activeFilter) {
                const field = nextApp.querySelector(`input[type="search"][name="${CSS.escape(activeFilter.name)}"]`);
                if (field) {
                    // Keep anything typed while the request was in flight; a newer
                    // value re-triggers the debounced search so results catch up.
                    if (field.value !== activeFilter.value) {
                        field.value = activeFilter.value;
                        field.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    field.focus();
                    const caret = activeFilter.end ?? field.value.length;
                    try { field.setSelectionRange(activeFilter.start ?? caret, caret); } catch (e) {}
                }
            }
        };

        document.body.classList.add('crm-ajax-swap');
        if (document.startViewTransition && !reducedMotion.matches) {
            const transition = document.startViewTransition(replace);
            await transition.finished.catch(() => {});
        } else {
            replace();
        }
        document.body.classList.remove('crm-ajax-swap');
        return true;
    };

    const loadCrmPage = async (url, options = {}) => {
        const method = (options.method || 'GET').toUpperCase();
        if (method === 'GET') {
            pageRequest?.abort();
            pageRequest = new AbortController();
        }

        setLoading(true);
        try {
            const response = await fetch(url, {
                method,
                body: method === 'GET' ? null : options.body,
                credentials: 'same-origin',
                redirect: 'follow',
                signal: method === 'GET' ? pageRequest.signal : undefined,
                headers: {'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest'},
            });
            if (!response.ok) throw new Error(`Request failed with status ${response.status}`);
            const html = await response.text();
            return await swapApp(html, response.url || url, {
                historyMode: options.historyMode || (method === 'GET' ? 'push' : 'replace'),
                preserveModal: options.preserveModal || null,
                preserveTab: options.preserveTab || null,
                preserveScroll: options.preserveScroll !== false,
            });
        } catch (error) {
            if (error.name !== 'AbortError') showPageMessage('That action could not be completed. Please try again.');
            return false;
        } finally {
            setLoading(false);
        }
    };

    const prepareSpreadsheet = async (form) => {
        const input = form.querySelector('input[type=file]');
        const file = input?.files?.[0];
        if (!file || !/\.xlsx?$/i.test(file.name)) return true;
        if (!window.XLSX) {
            showPageMessage('The Excel reader could not load. Save the sheet as CSV and try again.');
            return false;
        }
        try {
            const workbook = window.XLSX.read(await file.arrayBuffer(), {type: 'array'});
            const sheet = workbook.Sheets[workbook.SheetNames[0]];
            const csv = window.XLSX.utils.sheet_to_csv(sheet);
            const transfer = new DataTransfer();
            transfer.items.add(new File([csv], file.name.replace(/\.xlsx?$/i, '.csv'), {type: 'text/csv'}));
            input.files = transfer.files;
            return true;
        } catch (error) {
            showPageMessage('This spreadsheet could not be read. Please check the first sheet and try again.');
            return false;
        }
    };

    const submitTimeline = async (form) => {
        if (form.dataset.submitting === 'true') return;
        const textarea = form.querySelector('textarea[name="comment"]');
        const submit = form.querySelector('button[type="submit"]');
        const submitLabel = form.querySelector('[data-timeline-submit]');
        const originalLabel = submitLabel?.textContent || 'Add to timeline';

        const showFeedback = (message, type) => {
            if (message) showToast(message, type);
        };

        form.dataset.submitting = 'true';
        if (submit) submit.disabled = true;
        if (submitLabel) submitLabel.textContent = 'Adding…';
        textarea?.removeAttribute('aria-invalid');
        showFeedback('', '');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            });
            const payload = await response.json();
            if (!response.ok) {
                textarea?.setAttribute('aria-invalid', 'true');
                textarea?.focus();
                showFeedback(payload.errors?.comment?.[0] || payload.message || 'The comment could not be added.', 'error');
                return;
            }

            document.querySelector('[data-timeline-empty]')?.remove();
            const list = document.querySelector('[data-timeline-list]');
            if (list && payload.activity) {
                const item = document.createElement('li');
                item.className = 'activity-item activity-conversation timeline-item-new';
                item.dataset.activityGroup = 'conversation';
                const marker = document.createElement('span');
                marker.className = 'activity-marker';
                marker.textContent = '✎';
                const card = document.createElement('article');
                card.className = 'activity-card';
                const head = document.createElement('div');
                head.className = 'activity-head';
                const actor = document.createElement('div');
                const avatar = document.createElement('span');
                avatar.className = 'mini-avatar';
                avatar.textContent = String(payload.activity.actor || 'System').split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase();
                const actorCopy = document.createElement('span');
                const title = document.createElement('strong');
                const actorName = document.createElement('small');
                const time = document.createElement('time');
                const body = document.createElement('p');
                title.textContent = payload.activity.label;
                actorName.textContent = payload.activity.actor;
                time.textContent = payload.activity.created_at;
                body.textContent = payload.activity.body;
                actorCopy.append(title, actorName);
                actor.append(avatar, actorCopy);
                head.append(actor, time);
                card.append(head, body);
                item.append(marker, card);
                list.prepend(item);
            }
            const count = document.querySelector('[data-timeline-count]');
            if (count && Number.isFinite(Number(payload.total))) count.textContent = String(payload.total);
            const total = document.querySelector('[data-timeline-total]');
            if (total && Number.isFinite(Number(payload.total))) total.textContent = String(payload.total);
            const comments = document.querySelector('[data-timeline-comments]');
            if (comments) comments.textContent = String(Number(comments.textContent || 0) + 1);
            const lastContacted = document.querySelector('[data-last-contacted]');
            if (lastContacted) lastContacted.textContent = 'just now';
            document.querySelector('[data-timeline-filter="all"]')?.click();
            form.reset();
            const commentCount = form.querySelector('[data-comment-count]');
            if (commentCount) commentCount.textContent = '0 / 3000';
            showFeedback(payload.message || 'Added.', 'success');
            textarea?.focus();
        } catch (error) {
            showFeedback('Could not add the comment. Please try again.', 'error');
        } finally {
            form.dataset.submitting = 'false';
            if (submit) submit.disabled = false;
            if (submitLabel) submitLabel.textContent = originalLabel;
        }
    };

    const submitCrmForm = async (form, submitter = null) => {
        const method = (form.method || 'GET').toUpperCase();
        if (method === 'GET') {
            const destination = new URL(form.action, window.location.href);
            destination.search = new URLSearchParams(new FormData(form)).toString();
            await loadCrmPage(destination.href, {historyMode: 'push', preserveScroll: true});
            return;
        }

        if (form.matches('[data-import-form]') && !await prepareSpreadsheet(form)) return;
        const button = submitter || form.querySelector('button[type="submit"]');
        if (button?.disabled) return;
        button?.classList.add('is-busy');
        if (button) button.disabled = true;

        await loadCrmPage(form.action, {
            method,
            body: new FormData(form),
            historyMode: 'replace',
            preserveModal: form.dataset.ajaxPreserveModal || null,
            preserveTab: form.closest('#leadDrawer') ? document.querySelector('.tab.active')?.dataset.tab : null,
            preserveScroll: true,
        });

        button?.classList.remove('is-busy');
        if (button) button.disabled = false;
    };

    document.addEventListener('click', (event) => {
        const target = event.target;
        const expandDrawer = target.closest('[data-drawer-expand]');
        if (expandDrawer) {
            event.preventDefault();
            const overlay = expandDrawer.closest('#leadDrawer');
            setDrawerExpanded(!overlay?.classList.contains('is-expanded'));
            return;
        }

        const drawerFeedbackClose = target.closest('[data-dismiss-drawer-feedback]');
        if (drawerFeedbackClose) {
            drawerFeedbackClose.closest('[data-drawer-feedback]')?.remove();
            return;
        }

        const commentTemplate = target.closest('[data-comment-template]');
        if (commentTemplate) {
            const textarea = commentTemplate.closest('.comment-box')?.querySelector('textarea[name="comment"]');
            if (textarea) {
                textarea.value = commentTemplate.dataset.commentTemplate || '';
                textarea.dispatchEvent(new Event('input', {bubbles: true}));
                textarea.focus();
                textarea.setSelectionRange(textarea.value.length, textarea.value.length);
            }
            return;
        }

        const timelineFilter = target.closest('[data-timeline-filter]');
        if (timelineFilter) {
            const group = timelineFilter.dataset.timelineFilter;
            document.querySelectorAll('[data-timeline-filter]').forEach((button) => button.classList.toggle('active', button === timelineFilter));
            document.querySelectorAll('[data-activity-group]').forEach((item) => { item.hidden = group !== 'all' && item.dataset.activityGroup !== group; });
            return;
        }

        const teamFilterChip = target.closest('.team-filter-chip');
        if (teamFilterChip) {
            teamFilterChip.parentElement?.querySelectorAll('.team-filter-chip').forEach((chip) => chip.classList.toggle('is-active', chip === teamFilterChip));
            applyTeamFilter();
            return;
        }

        const modalButton = target.closest('[data-modal-open]');
        if (modalButton) {
            event.preventDefault();
            const menuWrap = modalButton.closest('[data-user-menu]');
            menuWrap?.classList.remove('is-open');
            menuWrap?.querySelector('[data-user-menu-toggle]')?.setAttribute('aria-expanded', 'false');
            openModal(modalButton.dataset.modalOpen);
            return;
        }

        const modalClose = target.closest('[data-modal-close]');
        if (modalClose) {
            event.preventDefault();
            closeModal(modalClose.closest('.overlay'));
            return;
        }

        if (target.closest('#menuToggle')) {
            document.getElementById('sidebar')?.classList.toggle('open');
            return;
        }

        const notificationToggle = target.closest('#notificationToggle');
        if (notificationToggle) {
            event.stopPropagation();
            document.getElementById('notificationPopover')?.classList.toggle('hidden');
            return;
        }

        const userMenuToggle = target.closest('[data-user-menu-toggle]');
        if (userMenuToggle) {
            event.stopPropagation();
            const wrap = userMenuToggle.closest('[data-user-menu]');
            const open = wrap?.classList.toggle('is-open');
            userMenuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            return;
        }

        const themeOption = target.closest('[data-theme-option]');
        if (themeOption) {
            setCrmTheme(themeOption.dataset.themeOption);
            const wrap = themeOption.closest('[data-user-menu]');
            wrap?.classList.remove('is-open');
            wrap?.querySelector('[data-user-menu-toggle]')?.setAttribute('aria-expanded', 'false');
            return;
        }

        const toastClose = target.closest('[data-toast-close]');
        if (toastClose) {
            dismissToast(toastClose.closest('[data-toast]'));
            return;
        }

        const tab = target.closest('[data-tab]');
        if (tab) {
            event.preventDefault();
            if (!tab.classList.contains('active')) activateTab(tab.dataset.tab);
            return;
        }

        const link = target.closest('a[href]');
        if (link && link.closest('[data-user-menu-panel]')) {
            const wrap = link.closest('[data-user-menu]');
            wrap?.classList.remove('is-open');
            wrap?.querySelector('[data-user-menu-toggle]')?.setAttribute('aria-expanded', 'false');
        }
        if (link?.matches('[data-transition-link]')) {
            if (event.ctrlKey || event.metaKey || event.shiftKey || link.target === '_blank') return;
            const destination = new URL(link.href, window.location.href);
            if (destination.href === window.location.href) return;
            event.preventDefault();
            showTransition(link.dataset.transitionLabel || 'Loading your workspace…', link.dataset.transitionMode || 'full');
            setTimeout(() => window.location.assign(destination.href), 120);
            return;
        }

        if (link && !link.matches('[data-native-navigation]') && !link.download && !link.target && !event.ctrlKey && !event.metaKey && !event.shiftKey) {
            const destination = new URL(link.href, window.location.href);
            if (isDashboardUrl(destination) && document.querySelector('[data-crm-app]')) {
                event.preventDefault();
                if (destination.href !== window.location.href) loadCrmPage(destination.href, {historyMode: 'push', preserveScroll: true});
                return;
            }
        }

        const row = target.closest('[data-crm-href]');
        if (row && !target.closest('a,button,input,select,textarea,label')) {
            event.preventDefault();
            loadCrmPage(row.dataset.crmHref, {historyMode: 'push', preserveScroll: true});
            return;
        }

        const drawer = document.getElementById('leadDrawer');
        if (drawer && target === drawer) drawer.querySelector('.drawer-head .close-btn')?.click();

        const notices = document.getElementById('notificationPopover');
        if (notices && !notices.contains(target)) notices.classList.add('hidden');

        const openUserMenu = document.querySelector('[data-user-menu].is-open');
        if (openUserMenu && !openUserMenu.contains(target)) {
            openUserMenu.classList.remove('is-open');
            openUserMenu.querySelector('[data-user-menu-toggle]')?.setAttribute('aria-expanded', 'false');
        }

        if (target.classList.contains('overlay')) closeModal(target);
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;

        if (form.matches('[data-transition-form]')) {
            if (form.dataset.transitioning === 'true') return;
            event.preventDefault();
            form.dataset.transitioning = 'true';
            const submit = event.submitter || form.querySelector('button[type="submit"]');
            if (submit) submit.disabled = true;
            showTransition(form.dataset.transitionLabel || 'Securing your workspace…', form.dataset.transitionMode || 'full');
            setTimeout(() => form.submit(), 180);
            return;
        }

        if (!form.closest('[data-crm-app]')) return;
        event.preventDefault();
        if (form.matches('[data-timeline-form]')) submitTimeline(form);
        else submitCrmForm(form, event.submitter);
    });

    document.addEventListener('change', (event) => {
        const input = event.target;
        if (input.matches('[data-crm-theme-switcher]')) setCrmTheme(input.value);
        if (input.matches('.dropzone input[type=file]')) {
            const label = input.closest('.dropzone');
            if (input.files?.[0]) label?.querySelector('strong')?.replaceChildren(input.files[0].name);
        }
        if (input.matches('[data-crm-filter-form] select')) input.form?.requestSubmit();
        if (input.matches('[data-status-select]')) {
            const help = input.closest('.section-card')?.querySelector('[data-status-help]');
            if (help) help.textContent = input.selectedOptions[0]?.dataset.hint || '';
            const symbol = input.closest('.section-card')?.querySelector('[data-status-symbol]');
            if (symbol) symbol.textContent = input.selectedOptions[0]?.dataset.symbol || '•';
        }
        if (input.matches('[data-stage-select]')) {
            const help = input.closest('.section-card')?.querySelector('[data-stage-help]');
            if (help) help.textContent = input.selectedOptions[0]?.dataset.hint || '';
        }
        const trackedForm = input.closest('[data-track-changes]');
        if (trackedForm) {
            trackedForm.classList.add('is-dirty');
            const state = trackedForm.querySelector('[data-form-state] span');
            if (state) state.textContent = 'Unsaved changes';
        }
    });

    const applyTeamFilter = () => {
        const toolbar = document.querySelector('[data-team-toolbar]');
        if (!toolbar) return;
        const query = (toolbar.querySelector('[data-team-search-input]')?.value || '').trim().toLowerCase();
        const role = toolbar.querySelector('.team-filter-chip.is-active')?.dataset.teamFilter || 'all';
        let visible = 0;
        document.querySelectorAll('[data-team-member]').forEach((member) => {
            const matchesRole = role === 'all' || member.dataset.teamRole === role;
            const matchesQuery = !query || (member.dataset.teamSearch || '').includes(query);
            const show = matchesRole && matchesQuery;
            member.hidden = !show;
            if (show) visible++;
        });
        document.querySelectorAll('[data-team-group]').forEach((group) => {
            const roleMatch = role === 'all' || role === group.dataset.role;
            const hasMembers = !!group.querySelector('[data-team-member]');
            const anyVisible = !!group.querySelector('[data-team-member]:not([hidden])');
            group.hidden = !roleMatch || (hasMembers && !anyVisible);
        });
        const noResults = document.querySelector('[data-team-no-results]');
        if (noResults) noResults.hidden = !(visible === 0 && query !== '');
    };

    let filterSearchTimer = null;
    document.addEventListener('input', (event) => {
        const input = event.target;
        // Live "search as you type" for every server-side CRM filter (leads,
        // enrollments, subscriptions, audit) — debounced so it filters without
        // pressing Enter. GET loads abort the previous in-flight request, and
        // 'replace' history keeps typing from flooding the back button.
        if (input.matches('[data-crm-filter-form] input[type="search"]')) {
            const form = input.form;
            if (form) {
                clearTimeout(filterSearchTimer);
                filterSearchTimer = setTimeout(() => {
                    const destination = new URL(form.action, window.location.href);
                    destination.search = new URLSearchParams(new FormData(form)).toString();
                    loadCrmPage(destination.href, { historyMode: 'replace', preserveScroll: true });
                }, 350);
            }
        }
        if (input.matches('[data-team-search-input]')) applyTeamFilter();
        if (input.matches('[data-timeline-form] textarea')) {
            const count = input.closest('form')?.querySelector('[data-comment-count]');
            if (count) count.textContent = `${input.value.length} / 3000`;
        }
        const trackedForm = input.closest?.('[data-track-changes]');
        if (trackedForm) {
            trackedForm.classList.add('is-dirty');
            const state = trackedForm.querySelector('[data-form-state] span');
            if (state) state.textContent = 'Unsaved changes';
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            const openUserMenu = document.querySelector('[data-user-menu].is-open');
            if (openUserMenu) {
                openUserMenu.classList.remove('is-open');
                openUserMenu.querySelector('[data-user-menu-toggle]')?.setAttribute('aria-expanded', 'false');
            }
            const expandedDrawer = document.querySelector('#leadDrawer.is-expanded');
            if (expandedDrawer) {
                setDrawerExpanded(false);
                return;
            }
            document.querySelectorAll('.overlay.open').forEach(closeModal);
        }
        const row = event.target.closest?.('[data-crm-href]');
        if (row && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            loadCrmPage(row.dataset.crmHref, {historyMode: 'push', preserveScroll: true});
        }
    });

    window.addEventListener('popstate', () => {
        if (document.querySelector('[data-crm-app]')) loadCrmPage(window.location.href, {historyMode: 'none', preserveScroll: true});
    });
    window.addEventListener('pageshow', resetTransition);
    window.addEventListener('resize', markScrollableTables);

    // Remark cell hover overlay — shows the full activity note in a floating card.
    const remarkPop = document.createElement('div');
    remarkPop.className = 'remark-pop';
    remarkPop.setAttribute('role', 'tooltip');
    remarkPop.hidden = true;
    document.body.appendChild(remarkPop);
    let remarkHideTimer = null;

    const hideRemark = () => { remarkPop.hidden = true; };

    const showRemark = (cell) => {
        const body = cell.dataset.remarkFull;
        if (!body) return;
        remarkPop.innerHTML = '';
        const text = document.createElement('p');
        text.className = 'remark-pop-body';
        text.textContent = body;
        remarkPop.appendChild(text);
        if (cell.dataset.remarkTime) {
            const meta = document.createElement('span');
            meta.className = 'remark-pop-meta';
            meta.textContent = cell.dataset.remarkTime;
            remarkPop.appendChild(meta);
        }
        remarkPop.hidden = false;
        const rect = cell.getBoundingClientRect();
        const pop = remarkPop.getBoundingClientRect();
        const margin = 10;
        let top = rect.top - pop.height - margin;
        remarkPop.dataset.placement = 'top';
        if (top < 12) { top = rect.bottom + margin; remarkPop.dataset.placement = 'bottom'; }
        let left = rect.left + (rect.width / 2) - (pop.width / 2);
        left = Math.max(12, Math.min(left, window.innerWidth - pop.width - 12));
        remarkPop.style.top = `${Math.round(top)}px`;
        remarkPop.style.left = `${Math.round(left)}px`;
    };

    document.addEventListener('mouseover', (event) => {
        const cell = event.target.closest?.('[data-remark-full]');
        if (!cell) return;
        clearTimeout(remarkHideTimer);
        showRemark(cell);
    });
    document.addEventListener('mouseout', (event) => {
        const cell = event.target.closest?.('[data-remark-full]');
        if (!cell) return;
        const next = event.relatedTarget;
        if (next && cell.contains(next)) return;
        remarkHideTimer = setTimeout(hideRemark, 90);
    });
    document.addEventListener('scroll', hideRemark, true);
    document.addEventListener('mousedown', hideRemark);

    // Click-and-hold drag scrolling for overflowing tables.
    let tableDrag = null;
    const DRAG_THRESHOLD = 5;
    document.addEventListener('mousedown', (event) => {
        if (event.button !== 0) return;
        const wrap = event.target.closest?.('.table-wrap');
        if (!wrap) return;
        // never hijack a press on an interactive control (links, buttons, edit toggles…)
        if (event.target.closest('a,button,input,select,textarea,label,summary')) return;
        const scrollable = wrap.scrollWidth > wrap.clientWidth || wrap.scrollHeight > wrap.clientHeight;
        if (!scrollable) return;
        tableDrag = {
            wrap,
            startX: event.pageX,
            startY: event.pageY,
            scrollLeft: wrap.scrollLeft,
            scrollTop: wrap.scrollTop,
            moved: false,
        };
    });
    document.addEventListener('mousemove', (event) => {
        if (!tableDrag) return;
        const dx = event.pageX - tableDrag.startX;
        const dy = event.pageY - tableDrag.startY;
        if (!tableDrag.moved && (Math.abs(dx) > DRAG_THRESHOLD || Math.abs(dy) > DRAG_THRESHOLD)) {
            tableDrag.moved = true;
            tableDrag.wrap.classList.add('is-grabbing');
        }
        if (tableDrag.moved) {
            event.preventDefault();
            tableDrag.wrap.scrollLeft = tableDrag.scrollLeft - dx;
            tableDrag.wrap.scrollTop = tableDrag.scrollTop - dy;
        }
    });
    document.addEventListener('mouseup', () => {
        if (!tableDrag) return;
        const dragged = tableDrag.moved;
        tableDrag.wrap.classList.remove('is-grabbing');
        tableDrag = null;
        if (dragged) {
            // swallow the click that fires after a drag so rows don't navigate
            const suppressClick = (event) => { event.stopPropagation(); event.preventDefault(); };
            document.addEventListener('click', suppressClick, { capture: true, once: true });
            setTimeout(() => document.removeEventListener('click', suppressClick, { capture: true }), 0);
        }
    });

    initialiseApp();
});
