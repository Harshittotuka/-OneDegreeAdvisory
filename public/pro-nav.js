/* ============================================================================
   "Pro" navigation behaviour (3rd nav style, opt-in)
   ----------------------------------------------------------------------------
   A self-contained clone of the Stripe nav's morphing-overlay behaviour, wired
   to its own data-pro-* hooks so it never collides with stripe-nav.js. The
   nav-style SWITCH itself lives in stripe-nav.js (3-way: classic/stripe/pro);
   this file just listens for the "oda:nav-style-change" event to tidy up when
   the user switches away. Styling/animation is inherited from the shared
   .stripe-* CSS; pro-nav.css adds the professional re-skin.
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

    const header = document.querySelector("[data-pro-header]");
    const nav = document.querySelector("[data-pro-nav]");
    const panel = document.querySelector("[data-pro-panel]");
    const mobileToggle = document.querySelector("[data-pro-mobile-toggle]");
    const flyout = document.querySelector("[data-pro-flyout]");
    const bg = document.querySelector("[data-pro-bg]");
    const arrow = document.querySelector("[data-pro-arrow]");
    const triggers = Array.from(document.querySelectorAll("[data-pro-trigger]"));
    const sections = Array.from(document.querySelectorAll("[data-pro-section]"));

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

    // Tidy up whenever the nav style changes (the switch lives in stripe-nav.js).
    window.addEventListener("oda:nav-style-change", () => {
      closeFlyout();
      closeMobileDrawer();
    });

    if (!header || !nav || !flyout || !bg || !arrow || !triggers.length) {
      return; // nothing else to wire up
    }

    const ORDER = ["destinations", "courses", "services"];
    const sectionFor = (key) => sections.find((s) => s.dataset.proSection === key);
    const triggerFor = (key) => triggers.find((t) => t.dataset.proTrigger === key);
    const isDesktop = () => window.matchMedia("(min-width: 921px)").matches;

    let isOpen = false;
    let activeKey = null;
    let closeTimer = null;
    let servicesBaseHeight = 0;
    let servicesBaseX = 0;

    /* ---- scroll shadow (independent of the other headers) ----------------- */
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

    const growBgForSubmenu = (wrap, sub) => {
      const section = sectionFor("services");
      const leftCol = section.offsetWidth;
      const baseH = servicesBaseHeight || section.offsetHeight;

      const grid = section.querySelector(".course-menu-grid");
      const firstCard = grid && grid.firstElementChild;
      const top = firstCard ? firstCard.offsetTop : 52;
      sub.style.top = top + "px";

      const w = leftCol + sub.offsetWidth + 16;
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
      void next.offsetWidth;
      next.style.transition = "";
      next.classList.add("is-active");
      next.style.transform = "translateX(0)";
      next.style.opacity = "1";

      activeKey = key;
      triggers.forEach((t) =>
        t.setAttribute("aria-expanded", String(t.dataset.proTrigger === key))
      );
    };

    const openFlyout = (key) => {
      window.clearTimeout(closeTimer);
      const wasOpen = isOpen;
      isOpen = true;
      flyout.classList.add("is-open");
      flyout.setAttribute("aria-hidden", "false");
      showSection(key, wasOpen);
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
      const key = trigger.dataset.proTrigger;

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

    flyout.querySelectorAll("[data-students-hub-trigger]").forEach((btn) => {
      btn.addEventListener("click", () => {
        closeFlyout();
        closeMobileDrawer();
      });
    });

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

    bg.addEventListener("mouseleave", () => {
      const open = flyout.querySelectorAll(".submenu-wrap.is-col-open");
      if (!open.length) return;
      open.forEach((w) => w.classList.remove("is-col-open"));
      if (isDesktop() && isOpen && activeKey === "services") {
        positionBg("services", true);
      }
    });

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
      if (!event.target.closest("[data-pro-nav]")) {
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
