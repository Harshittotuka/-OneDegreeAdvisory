/* ============================================================================
   Stripe-style navigation behaviour (opt-in alternative nav)
   ----------------------------------------------------------------------------
   Self-contained. Drives:
     1. The floating "Nav style" toggle (classic  <->  stripe), persisted.
     2. The single shared overlay that MORPHS (size + position + cross-fade)
        between the Destinations / Courses / Services panels — the way Stripe's
        global nav transitions within one overlay.
     3. A slide-down drawer + inline accordion on mobile.

   It only ever reads/writes its own `.stripe-*` / `data-stripe-*` hooks, so
   public/script.js (the classic nav) is completely unaffected.
   ========================================================================== */
(function () {
  const ready = (cb) => {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", cb, { once: true });
    } else {
      cb();
    }
  };

  ready(() => {
    const refreshIcons = () => {
      if (window.lucide) window.lucide.createIcons();
    };

    /* ----------------------------------------------------------------------
       1. Floating "Nav style" toggle
       ---------------------------------------------------------------------- */
    const STORAGE_KEY = "oda:nav-style";
    const root = document.documentElement;
    const toggle = document.querySelector("[data-nav-style-toggle]");
    const toggleLabel = document.querySelector("[data-nav-style-label]");

    const isStripe = () => root.classList.contains("nav-stripe");

    const syncToggleUi = () => {
      if (toggleLabel) toggleLabel.textContent = isStripe() ? "Stripe" : "Classic";
      if (toggle) toggle.setAttribute("aria-pressed", String(isStripe()));
    };

    const setNavStyle = (stripe) => {
      root.classList.toggle("nav-stripe", stripe);
      try {
        localStorage.setItem(STORAGE_KEY, stripe ? "stripe" : "classic");
      } catch (e) {}
      // Tidy up whichever nav is being hidden.
      document.body.classList.remove("nav-open"); // classic mobile menu
      closeMobileDrawer();
      closeFlyout();
      syncToggleUi();
      refreshIcons();
    };

    if (toggle) {
      toggle.addEventListener("click", () => setNavStyle(!isStripe()));
    }
    syncToggleUi();

    /* ----------------------------------------------------------------------
       2 + 3. The Stripe header itself
       ---------------------------------------------------------------------- */
    const header = document.querySelector("[data-stripe-header]");
    const nav = document.querySelector("[data-stripe-nav]");
    const panel = document.querySelector("[data-stripe-panel]");
    const mobileToggle = document.querySelector("[data-stripe-mobile-toggle]");
    const flyout = document.querySelector("[data-stripe-flyout]");
    const bg = document.querySelector("[data-stripe-bg]");
    const arrow = document.querySelector("[data-stripe-arrow]");
    const triggers = Array.from(document.querySelectorAll("[data-stripe-trigger]"));
    const sections = Array.from(document.querySelectorAll("[data-stripe-section]"));

    // Declared early so setNavStyle (above) can call them even if the header
    // is somehow absent.
    function closeMobileDrawer() {
      if (!header || !header.classList.contains("stripe-nav-open")) return;
      header.classList.remove("stripe-nav-open");
      document.body.classList.remove("stripe-nav-open");
      if (mobileToggle) {
        mobileToggle.setAttribute("aria-expanded", "false");
        mobileToggle.setAttribute("aria-label", "Open navigation");
        mobileToggle.innerHTML = '<i data-lucide="menu"></i>';
        refreshIcons();
      }
    }

    function closeFlyout() {
      if (!flyout) return;
      isOpen = false;
      flyout.classList.remove("is-open");
      flyout.setAttribute("aria-hidden", "true");
      triggers.forEach((t) => t.setAttribute("aria-expanded", "false"));
      flyout
        .querySelectorAll(".submenu-wrap.is-open, .submenu-wrap.is-col-open")
        .forEach((wrap) => {
          wrap.classList.remove("is-open", "is-col-open");
          const st = wrap.querySelector("[data-submenu-trigger]");
          if (st) st.setAttribute("aria-expanded", "false");
        });
    }

    if (!header || !nav || !flyout || !bg || !arrow || !triggers.length) {
      return; // toggle still works; nothing else to wire up
    }

    const ORDER = ["destinations", "courses", "services"];
    const sectionFor = (key) => sections.find((s) => s.dataset.stripeSection === key);
    const triggerFor = (key) => triggers.find((t) => t.dataset.stripeTrigger === key);
    const isDesktop = () => window.matchMedia("(min-width: 921px)").matches;

    let isOpen = false;
    let activeKey = null;
    let closeTimer = null;
    let servicesBaseHeight = 0;
    let servicesBaseX = 0;

    /* ---- scroll shadow (independent of the classic header) ----------------
       Hysteresis (separate on/off thresholds): collapsing the notice bar
       shortens the page ~40px and nudges scrollY; a single threshold would let
       that re-cross the line and the header would flicker. A dead-zone wider
       than the collapse height keeps the toggle stable. */
    const SCROLL_ON = 90;
    const SCROLL_OFF = 30;
    const setHeaderState = () => {
      const scrolled = header.classList.contains("is-scrolled");
      const y = window.scrollY;
      if (!scrolled && y > SCROLL_ON) header.classList.add("is-scrolled");
      else if (scrolled && y < SCROLL_OFF) header.classList.remove("is-scrolled");
    };
    setHeaderState();
    window.addEventListener("scroll", setHeaderState, { passive: true });

    /* ---- geometry: size + position the morphing card under a trigger ------ */
    const clampX = (desiredX, w) => {
      const fRect = flyout.getBoundingClientRect();
      const minX = 16 - fRect.left;
      const maxX = window.innerWidth - 16 - w - fRect.left;
      return maxX < minX ? minX : Math.max(minX, Math.min(maxX, desiredX));
    };

    const triggerCenter = (key) => {
      const fRect = flyout.getBoundingClientRect();
      const tRect = triggerFor(key).getBoundingClientRect();
      return tRect.left - fRect.left + tRect.width / 2;
    };

    const applyGeometry = (w, h, x, arrowCenter, animate) => {
      if (!animate) flyout.classList.add("is-snapping");
      bg.style.width = w + "px";
      bg.style.height = h + "px";
      bg.style.transform = "translateX(" + x + "px)";
      const arrowX = Math.max(x + 18, Math.min(x + w - 18, arrowCenter));
      arrow.style.transform = "translateX(" + arrowX + "px) rotate(45deg)";
      if (!animate) {
        void bg.offsetWidth; // flush the snap before re-enabling transitions
        requestAnimationFrame(() => flyout.classList.remove("is-snapping"));
      }
    };

    const positionBg = (key, animate) => {
      const section = sectionFor(key);
      if (!section || !triggerFor(key)) return;
      const w = section.offsetWidth;
      const h = section.offsetHeight;
      if (key === "services") servicesBaseHeight = h;
      const center = triggerCenter(key);
      const x = clampX(center - w / 2, w);
      if (key === "services") servicesBaseX = x;
      applyGeometry(w, h, x, center, animate);
    };

    const closeSubmenus = () => {
      flyout
        .querySelectorAll(".submenu-wrap.is-open, .submenu-wrap.is-col-open")
        .forEach((wrap) => {
          wrap.classList.remove("is-open", "is-col-open");
          const st = wrap.querySelector("[data-submenu-trigger]");
          if (st) st.setAttribute("aria-expanded", "false");
        });
    };

    // The nested "Study Abroad" menu reveals as a top-aligned SECOND COLUMN
    // inside the same card. Because the card clips its contents for the morph,
    // we grow it to exactly fit the two columns, then shrink back on close.
    const growBgForSubmenu = (wrap, sub) => {
      const section = sectionFor("services");
      const leftCol = section.offsetWidth; // left column = the services panel
      const baseH = servicesBaseHeight || section.offsetHeight;

      // Align the column with the first service item.
      const grid = section.querySelector(".course-menu-grid");
      const firstCard = grid && grid.firstElementChild;
      const top = firstCard ? firstCard.offsetTop : 52;
      sub.style.top = top + "px";

      const w = leftCol + sub.offsetWidth + 16; // left + right column + breathing room
      const h = Math.max(baseH, top + sub.scrollHeight + 16);
      applyGeometry(w, h, clampX(servicesBaseX, w), triggerCenter("services"), true);
    };

    const syncSubmenuBg = (wrap, sub) => {
      if (!sub || !isDesktop() || !isOpen || activeKey !== "services") return;
      const shown =
        wrap.classList.contains("is-col-open") || wrap.matches(":focus-within");
      if (shown) growBgForSubmenu(wrap, sub);
      else positionBg("services", true);
    };

    /* ---- cross-fade + directional slide between panels -------------------- */
    const showSection = (key, animate) => {
      const next = sectionFor(key);
      if (!next) return;

      const prevKey = activeKey;
      if (prevKey && prevKey !== key) closeSubmenus();
      positionBg(key, animate);

      if (prevKey === key) return;

      const dir = prevKey ? (ORDER.indexOf(key) > ORDER.indexOf(prevKey) ? 1 : -1) : 0;

      sections.forEach((s) => {
        if (s === next || !s.classList.contains("is-active")) return;
        s.classList.remove("is-active");
        s.style.transform = "translateX(" + -dir * 16 + "px)";
        s.style.opacity = "0";
      });

      next.style.transition = "none";
      next.style.transform = "translateX(" + dir * 16 + "px)";
      next.style.opacity = "0";
      void next.offsetWidth; // reflow so the entrance animates from the offset
      next.style.transition = "";
      next.classList.add("is-active");
      next.style.transform = "translateX(0)";
      next.style.opacity = "1";

      activeKey = key;
      triggers.forEach((t) =>
        t.setAttribute("aria-expanded", String(t.dataset.stripeTrigger === key))
      );
    };

    const openFlyout = (key) => {
      window.clearTimeout(closeTimer);
      const wasOpen = isOpen;
      isOpen = true;
      flyout.classList.add("is-open");
      flyout.setAttribute("aria-hidden", "false");
      showSection(key, wasOpen); // morph only when already open; otherwise snap
      refreshIcons();
    };

    const scheduleClose = () => {
      if (!isDesktop()) return;
      window.clearTimeout(closeTimer);
      closeTimer = window.setTimeout(closeFlyout, 160);
    };

    /* ---- mobile drawer + accordion ---------------------------------------- */
    const openMobileDrawer = () => {
      header.classList.add("stripe-nav-open");
      document.body.classList.add("stripe-nav-open");
      if (mobileToggle) {
        mobileToggle.setAttribute("aria-expanded", "true");
        mobileToggle.setAttribute("aria-label", "Close navigation");
        mobileToggle.innerHTML = '<i data-lucide="x"></i>';
        refreshIcons();
      }
    };

    const toggleMobileSection = (key) => {
      const next = sectionFor(key);
      if (!next) return;
      const willOpen = !next.classList.contains("is-active");
      sections.forEach((s) => s.classList.remove("is-active"));
      triggers.forEach((t) => t.setAttribute("aria-expanded", "false"));
      if (willOpen) {
        next.classList.add("is-active");
        triggerFor(key).setAttribute("aria-expanded", "true");
      }
    };

    if (mobileToggle) {
      mobileToggle.addEventListener("click", () => {
        if (header.classList.contains("stripe-nav-open")) closeMobileDrawer();
        else openMobileDrawer();
      });
    }

    /* ---- trigger wiring ---------------------------------------------------- */
    triggers.forEach((trigger) => {
      const key = trigger.dataset.stripeTrigger;

      trigger.addEventListener("mouseenter", () => {
        if (isDesktop()) openFlyout(key);
      });
      trigger.addEventListener("focus", () => {
        if (isDesktop()) openFlyout(key);
      });

      trigger.addEventListener("click", (event) => {
        event.preventDefault();
        if (isDesktop()) {
          if (isOpen && activeKey === key) closeFlyout();
          else openFlyout(key);
        } else {
          toggleMobileSection(key);
        }
      });

      trigger.addEventListener("keydown", (event) => {
        if (isDesktop() && event.key === "ArrowDown") {
          event.preventDefault();
          openFlyout(key);
          const first = sectionFor(key).querySelector("a, button");
          if (first) first.focus();
        }
      });
    });

    /* ---- hover intent ------------------------------------------------------ */
    nav.addEventListener("mouseleave", scheduleClose);
    nav.addEventListener("mouseenter", () => window.clearTimeout(closeTimer));
    flyout.addEventListener("mouseenter", () => window.clearTimeout(closeTimer));
    flyout.addEventListener("mouseleave", scheduleClose);

    /* ---- close the overlay/drawer when a real destination is chosen -------- */
    flyout.addEventListener("click", (event) => {
      if (event.target.closest("a")) {
        closeFlyout();
        closeMobileDrawer();
      }
    });

    // The "coming soon" buttons reuse the classic [data-students-hub-trigger]
    // (handled in script.js); just make sure our overlay/drawer steps aside.
    flyout.querySelectorAll("[data-students-hub-trigger]").forEach((btn) => {
      btn.addEventListener("click", () => {
        closeFlyout();
        closeMobileDrawer();
      });
    });

    // "Study Abroad" reveals its column on HOVER (or keyboard focus) only — it
    // never locks open on click. Hovering it opens the column; the column then
    // stays open while the pointer is anywhere in the card (see bg.mouseleave
    // below), so you can travel diagonally to its options without it closing.
    // At ≤920px it falls back to the classic inline tap-accordion.
    flyout.querySelectorAll(".submenu-wrap").forEach((wrap) => {
      const sub = wrap.querySelector(".course-submenu");
      if (!sub) return;
      const sync = () => syncSubmenuBg(wrap, sub);
      wrap.addEventListener("mouseenter", () => {
        if (isDesktop()) wrap.classList.add("is-col-open");
        sync();
      });
      wrap.addEventListener("focusin", sync);
      wrap.addEventListener("focusout", () => requestAnimationFrame(sync));
    });

    // Close the column only when the pointer leaves the whole card.
    bg.addEventListener("mouseleave", () => {
      const open = flyout.querySelectorAll(".submenu-wrap.is-col-open");
      if (!open.length) return;
      open.forEach((w) => w.classList.remove("is-col-open"));
      if (isDesktop() && isOpen && activeKey === "services") {
        positionBg("services", true);
      }
    });

    // Hover-only on desktop: swallow the click before it reaches classic
    // script.js (which would otherwise toggle .is-open and "lock" the column).
    // Capture phase + stopPropagation keeps the click from reaching the trigger.
    flyout.addEventListener(
      "click",
      (event) => {
        if (isDesktop() && event.target.closest("[data-submenu-trigger]")) {
          event.preventDefault();
          event.stopPropagation();
        }
      },
      true
    );

    /* ---- global dismissers ------------------------------------------------- */
    document.addEventListener("click", (event) => {
      if (!event.target.closest("[data-stripe-nav]")) {
        if (isOpen) closeFlyout();
        closeMobileDrawer();
      }
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        if (isOpen) closeFlyout();
        closeMobileDrawer();
      }
    });

    window.addEventListener(
      "resize",
      () => {
        if (isDesktop()) {
          closeMobileDrawer();
          if (isOpen) positionBg(activeKey, false);
        } else {
          closeFlyout();
        }
      },
      { passive: true }
    );
  });
})();
