const ready = (callback) => {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", callback, { once: true });
  } else {
    callback();
  }
};

ready(() => {
  const refreshIcons = () => {
    if (window.lucide) {
      window.lucide.createIcons();
    }
  };

  refreshIcons();

  // "Why One Degree" — phone-only infinite carousel (2 rows, columns scrolling
  // sideways, one full column centred with ~20% peeks). It's a real scroll
  // container: script auto-scrolls scrollLeft continuously AND the user can grab
  // and hold-drag to scrub it. Each card is cloned once so the loop has a full
  // second copy to wrap onto. Gated to ≤720 (clones stay display:none above it).
  document.querySelectorAll(".whyus-carousel").forEach((carousel) => {
    const grid = carousel.querySelector(".whyus-grid");
    if (!grid) return;
    const originals = Array.from(grid.children);
    if (originals.length < 2) return;
    const originalCount = originals.length;

    originals.forEach((card) => {
      // Reveal originals now: a moving carousel would otherwise fade each card
      // in as the scroll-reveal observer first notices it sliding into view.
      card.classList.add("is-visible");
      const clone = card.cloneNode(true);
      clone.classList.add("is-clone", "is-visible"); // clones aren't observed
      clone.setAttribute("aria-hidden", "true");
      clone.querySelectorAll("a, button, [tabindex]").forEach((el) => {
        el.setAttribute("tabindex", "-1");
      });
      grid.appendChild(clone);
    });
    carousel.classList.add("is-marquee");

    const phone = window.matchMedia("(max-width: 720px)");
    const reduce = window.matchMedia("(prefers-reduced-motion: reduce)");
    const SECONDS_PER_COPY = 26; // gentle continuous glide

    let copyWidth = 0; // px width of one copy (= left offset of the first clone)
    let paused = false;
    let dragging = false;
    let rafId = null;
    let lastT = null;
    let autoScrollLeft = 0;

    const measure = () => {
      const firstClone = grid.children[originalCount];
      copyWidth = firstClone ? firstClone.offsetLeft : 0;
    };

    const step = (t) => {
      if (lastT === null) lastT = t;
      const dt = (t - lastT) / 1000;
      lastT = t;
      if (!paused && copyWidth > 0) {
        // Keep fractional movement outside scrollLeft. Some mobile browsers
        // round scrollLeft to whole pixels, which can swallow tiny per-frame
        // increments and make the carousel look like it stopped.
        autoScrollLeft += (copyWidth / SECONDS_PER_COPY) * dt;
        if (autoScrollLeft >= copyWidth) autoScrollLeft -= copyWidth;
        carousel.scrollLeft = autoScrollLeft;
      }
      rafId = window.requestAnimationFrame(step);
    };
    const startAuto = () => {
      if (rafId !== null || reduce.matches) return;
      measure();
      autoScrollLeft = carousel.scrollLeft;
      lastT = null;
      rafId = window.requestAnimationFrame(step);
    };
    const stopAuto = () => {
      if (rafId !== null) {
        window.cancelAnimationFrame(rafId);
        rafId = null;
      }
    };
    const resumeSoon = () => {
      window.setTimeout(() => {
        if (!dragging) paused = false;
      }, 900);
    };

    // No hover-pause: this is a phone-only carousel (no hover on touch), and
    // pausing on hover only made it look frozen when previewed on desktop. It
    // pauses solely while the user is actively holding/dragging it.

    // Hold-drag to scrub. Touch uses native scrolling (just pause auto); mouse
    // and pen are dragged manually since overflow doesn't drag with a mouse.
    let startX = 0;
    let startScroll = 0;
    carousel.addEventListener("pointerdown", (e) => {
      paused = true;
      if (e.pointerType === "touch") return; // let native momentum scroll handle it
      if (e.button != null && e.button !== 0) return;
      dragging = true;
      startX = e.clientX;
      startScroll = carousel.scrollLeft;
      carousel.classList.add("is-dragging");
      carousel.setPointerCapture?.(e.pointerId);
    });
    carousel.addEventListener("pointermove", (e) => {
      if (!dragging) return;
      carousel.scrollLeft = startScroll - (e.clientX - startX);
      autoScrollLeft = carousel.scrollLeft;
    });
    carousel.addEventListener("scroll", () => {
      if (!paused && !dragging) return;
      autoScrollLeft = copyWidth > 0 ? carousel.scrollLeft % copyWidth : carousel.scrollLeft;
    }, { passive: true });
    const endDrag = () => {
      if (dragging) {
        dragging = false;
        carousel.classList.remove("is-dragging");
      }
      resumeSoon();
    };
    carousel.addEventListener("pointerup", endDrag);
    carousel.addEventListener("pointercancel", endDrag);

    const sync = () => {
      carousel.scrollLeft = 0;
      autoScrollLeft = 0;
      if (phone.matches && !reduce.matches) startAuto();
      else stopAuto();
    };
    const onMq = (mq, fn) =>
      mq.addEventListener ? mq.addEventListener("change", fn) : mq.addListener(fn);
    onMq(phone, sync);
    onMq(reduce, sync);
    sync();
  });
  refreshIcons();

  const header = document.querySelector("[data-header]");
  const navToggle = document.querySelector("[data-nav-toggle]");
  const navMenu = document.querySelector("[data-nav-menu]");
  const navLinks = Array.from(document.querySelectorAll(".nav-menu a"));
  const studentsHubTriggers = Array.from(document.querySelectorAll("[data-students-hub-trigger]"));
  const studentsHubOverlay = document.querySelector("[data-students-hub-overlay]");
  const finderForm = document.querySelector("[data-finder-form]");
  const finderNote = document.querySelector("[data-finder-note]");
  const filterButtons = Array.from(document.querySelectorAll("[data-filter]"));
  const destinationCards = Array.from(document.querySelectorAll("[data-group]"));
  const mapPins = Array.from(document.querySelectorAll("[data-map-target]"));
  const mapRegion = document.querySelector("[data-map-region]");
  const mapTitle = document.querySelector("[data-map-title]");
  const mapCopy = document.querySelector("[data-map-copy]");
  const mapMeta = document.querySelector("[data-map-meta]");
  const testimonialTrack = document.querySelector("[data-testimonial-track]");
  const testimonials = testimonialTrack ? Array.from(testimonialTrack.querySelectorAll(".testimonial")) : [];
  const prevTestimonial = document.querySelector("[data-testimonial-prev]");
  const nextTestimonial = document.querySelector("[data-testimonial-next]");
  const consultForm = document.querySelector("[data-consult-form]");
  const formStatus = document.querySelector("[data-form-status]");

  // Hysteresis: collapsing the notice bar on scroll shortens the document by
  // ~40px, which nudges scrollY. A single threshold would let that nudge
  // re-cross the line and the header would flicker/shake. Separate on/off
  // thresholds with a dead-zone wider than the collapse height stop the loop.
  const SCROLL_ON = 90;
  const SCROLL_OFF = 30;
  const setHeaderState = () => {
    if (!header) return;
    const scrolled = header.classList.contains("is-scrolled");
    const y = window.scrollY;
    if (!scrolled && y > SCROLL_ON) {
      header.classList.add("is-scrolled");
    } else if (scrolled && y < SCROLL_OFF) {
      header.classList.remove("is-scrolled");
    }
  };

  setHeaderState();
  window.addEventListener("scroll", setHeaderState, { passive: true });

  const closeNavigation = () => {
    document.body.classList.remove("nav-open");
    if (navToggle) {
      navToggle.setAttribute("aria-expanded", "false");
      navToggle.setAttribute("aria-label", "Open navigation");
      navToggle.innerHTML = '<i data-lucide="menu"></i>';
      refreshIcons();
    }
  };

  const toggleNavigation = () => {
    if (!navToggle) return;
    const isOpen = document.body.classList.toggle("nav-open");
    navToggle.setAttribute("aria-expanded", String(isOpen));
    navToggle.setAttribute("aria-label", isOpen ? "Close navigation" : "Open navigation");
    navToggle.innerHTML = isOpen ? '<i data-lucide="x"></i>' : '<i data-lucide="menu"></i>';
    refreshIcons();
  };

  if (navToggle && navMenu) {
    let lastTouchToggle = 0;

    navToggle.addEventListener("click", () => {
      if (Date.now() - lastTouchToggle < 500) return;
      toggleNavigation();
    });

    navToggle.addEventListener("touchend", (event) => {
      event.preventDefault();
      if (Date.now() - lastTouchToggle < 500) return;
      lastTouchToggle = Date.now();
      toggleNavigation();
    }, { passive: false });

    navToggle.addEventListener("pointerup", (event) => {
      if (event.pointerType !== "touch") return;
      if (Date.now() - lastTouchToggle < 500) return;
      lastTouchToggle = Date.now();
      toggleNavigation();
    });

    navMenu.addEventListener("click", (event) => {
      if (event.target.closest("a")) {
        closeNavigation();
      }
    });
  }

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && document.body.classList.contains("nav-open")) {
      closeNavigation();
    }
  });

  const dropdownItems = Array.from(document.querySelectorAll("[data-dropdown]"));
  const isDesktopDropdown = () => window.matchMedia("(min-width: 961px)").matches;

  const closeAllDropdowns = (except) => {
    dropdownItems.forEach((item) => {
      if (item === except) return;
      if (!item.classList.contains("is-open")) return;
      item.classList.remove("is-open");
      const trigger = item.querySelector("[data-dropdown-trigger]");
      if (trigger) trigger.setAttribute("aria-expanded", "false");
    });
    document.querySelectorAll(".submenu-wrap.is-open").forEach((wrap) => {
      wrap.classList.remove("is-open");
      const subTrigger = wrap.querySelector("[data-submenu-trigger]");
      if (subTrigger) subTrigger.setAttribute("aria-expanded", "false");
    });
  };

  document.querySelectorAll("[data-submenu-trigger]").forEach((subTrigger) => {
    const wrap = subTrigger.closest("[data-submenu]");
    if (!wrap) return;
    subTrigger.addEventListener("click", (event) => {
      event.preventDefault();
      event.stopPropagation();
      const willOpen = !wrap.classList.contains("is-open");
      wrap.classList.toggle("is-open", willOpen);
      subTrigger.setAttribute("aria-expanded", String(willOpen));
    });
  });

  const setDropdown = (item, open) => {
    item.classList.toggle("is-open", open);
    const trigger = item.querySelector("[data-dropdown-trigger]");
    if (trigger) trigger.setAttribute("aria-expanded", String(open));
    if (open) closeAllDropdowns(item);
  };

  dropdownItems.forEach((item) => {
    const trigger = item.querySelector("[data-dropdown-trigger]");
    const panel = item.querySelector("[data-dropdown-panel]");
    if (!trigger || !panel) return;

    let hoverTimer = null;
    const openWithDelay = () => {
      if (!isDesktopDropdown()) return;
      window.clearTimeout(hoverTimer);
      setDropdown(item, true);
    };
    const closeWithDelay = () => {
      if (!isDesktopDropdown()) return;
      window.clearTimeout(hoverTimer);
      hoverTimer = window.setTimeout(() => setDropdown(item, false), 140);
    };

    item.addEventListener("mouseenter", openWithDelay);
    item.addEventListener("mouseleave", closeWithDelay);
    panel.addEventListener("mouseenter", () => window.clearTimeout(hoverTimer));

    trigger.addEventListener("click", (event) => {
      event.preventDefault();
      event.stopPropagation();
      setDropdown(item, !item.classList.contains("is-open"));
    });

    trigger.addEventListener("keydown", (event) => {
      if (event.key === "ArrowDown" || event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        setDropdown(item, true);
        const firstLink = panel.querySelector("a");
        if (firstLink) firstLink.focus();
      }
    });

    panel.addEventListener("click", (event) => {
      if (event.target.closest("a")) {
        setDropdown(item, false);
        if (document.body.classList.contains("nav-open")) closeNavigation();
      }
    });
  });

  document.addEventListener("click", (event) => {
    if (!event.target.closest("[data-dropdown]")) closeAllDropdowns();
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") closeAllDropdowns();
  });

  window.addEventListener("resize", () => closeAllDropdowns(), { passive: true });

  if (studentsHubOverlay && studentsHubTriggers.length) {
    const closeButtons = Array.from(studentsHubOverlay.querySelectorAll("[data-students-hub-close]"));
    const focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
    let lastFocusedElement = null;
    let closeStudentsHubTimer = null;

    const getFocusableStudentsHubItems = () => Array
      .from(studentsHubOverlay.querySelectorAll(focusableSelector))
      .filter((element) => element.offsetWidth || element.offsetHeight || element.getClientRects().length);

    const closeStudentsHub = () => {
      if (!studentsHubOverlay.classList.contains("is-open")) return;
      window.clearTimeout(closeStudentsHubTimer);
      studentsHubOverlay.classList.remove("is-open");
      studentsHubOverlay.classList.add("is-closing");
      studentsHubOverlay.setAttribute("aria-hidden", "true");

      closeStudentsHubTimer = window.setTimeout(() => {
        studentsHubOverlay.hidden = true;
        studentsHubOverlay.classList.remove("is-closing");
        document.body.classList.remove("student-hub-open");

        if (lastFocusedElement && document.contains(lastFocusedElement)) {
          lastFocusedElement.focus();
        }
      }, 260);
    };

    const comingSoonContent = {
      "students-hub": {
        kicker: "AI-powered student tools",
        title: "Students Hub",
        desc: "A smarter space for profile insights, best-fit university shortlists, application planning, and progress tracking.",
        features: [
          { icon: "brain", label: "Profile intelligence" },
          { icon: "target", label: "Best-fit shortlists" },
          { icon: "list-checks", label: "Application copilot" },
        ],
      },
      "career-mentoring": {
        kicker: "1:1 expert guidance",
        title: "Career Mentoring",
        desc: "Personalised mentoring that maps your goals to the right programmes, careers, and milestones.",
        features: [
          { icon: "compass", label: "Goal mapping" },
          { icon: "users", label: "1:1 mentor match" },
          { icon: "route", label: "Career roadmap" },
        ],
      },
      "student-development": {
        kicker: "Build your profile",
        title: "Student Development Programme",
        desc: "A structured programme to strengthen your profile with skills, projects, and achievements that stand out.",
        features: [
          { icon: "trending-up", label: "Profile building" },
          { icon: "award", label: "Skill milestones" },
          { icon: "sparkles", label: "Standout projects" },
        ],
      },
      "personality-assessment": {
        kicker: "Know how you work",
        title: "Personality Assessment",
        desc: "A guided assessment that surfaces your strengths, working style, and best-fit paths — so your applications play to what makes you, you.",
        features: [
          { icon: "brain", label: "Strengths profile" },
          { icon: "compass", label: "Work-style insights" },
          { icon: "target", label: "Best-fit matches" },
        ],
      },
    };

    const populateComingSoon = (trigger) => {
      const key = (trigger && trigger.dataset.feature) || "students-hub";
      const content = comingSoonContent[key] || comingSoonContent["students-hub"];
      const kickerEl = studentsHubOverlay.querySelector(".students-hub-kicker");
      const titleEl = studentsHubOverlay.querySelector("#students-hub-title");
      const descEl = studentsHubOverlay.querySelector("#students-hub-desc");
      const featuresEl = studentsHubOverlay.querySelector(".students-hub-features");
      if (kickerEl) kickerEl.innerHTML = '<i data-lucide="sparkles" aria-hidden="true"></i>' + content.kicker;
      if (titleEl) titleEl.textContent = content.title + " is coming soon";
      if (descEl) descEl.textContent = content.desc;
      if (featuresEl) {
        featuresEl.setAttribute("aria-label", content.title + " preview features");
        featuresEl.innerHTML = content.features
          .map((f) => '<span><i data-lucide="' + f.icon + '" aria-hidden="true"></i> ' + f.label + "</span>")
          .join("");
      }
    };

    const openStudentsHub = (trigger) => {
      window.clearTimeout(closeStudentsHubTimer);
      lastFocusedElement = trigger || document.activeElement;
      populateComingSoon(trigger);
      closeAllDropdowns();
      closeNavigation();
      studentsHubOverlay.hidden = false;
      studentsHubOverlay.classList.remove("is-closing");
      document.body.classList.add("student-hub-open");

      window.requestAnimationFrame(() => {
        studentsHubOverlay.classList.add("is-open");
        studentsHubOverlay.setAttribute("aria-hidden", "false");
        refreshIcons();

        const firstFocusable = getFocusableStudentsHubItems()[0];
        if (firstFocusable) firstFocusable.focus();
      });
    };

    studentsHubTriggers.forEach((trigger) => {
      trigger.addEventListener("click", () => openStudentsHub(trigger));
    });

    closeButtons.forEach((button) => {
      button.addEventListener("click", closeStudentsHub);
    });

    document.addEventListener("keydown", (event) => {
      if (!studentsHubOverlay.classList.contains("is-open")) return;

      if (event.key === "Escape") {
        event.preventDefault();
        closeStudentsHub();
        return;
      }

      if (event.key !== "Tab") return;

      const focusableItems = getFocusableStudentsHubItems();
      if (!focusableItems.length) return;

      const firstItem = focusableItems[0];
      const lastItem = focusableItems[focusableItems.length - 1];

      if (event.shiftKey && document.activeElement === firstItem) {
        event.preventDefault();
        lastItem.focus();
      } else if (!event.shiftKey && document.activeElement === lastItem) {
        event.preventDefault();
        firstItem.focus();
      }
    });
  }

  const mbbsCard = document.querySelector("[data-mbbs-card]");
  if (mbbsCard) {
    const mbbsPanel = mbbsCard.querySelector("#mbbs-country-panel");
    const mbbsClose = mbbsCard.querySelector("[data-mbbs-close]");

    const setMbbsRoutes = (open) => {
      mbbsCard.classList.toggle("is-routes-open", open);
      mbbsCard.setAttribute("aria-expanded", String(open));
      if (mbbsPanel) mbbsPanel.setAttribute("aria-hidden", String(!open));
    };

    mbbsCard.addEventListener("click", (event) => {
      if (event.target.closest("a")) return;
      if (event.target.closest("[data-mbbs-close]")) {
        event.stopPropagation();
        setMbbsRoutes(false);
        return;
      }
      if (event.target.closest(".mbbs-routes-panel")) return;
      setMbbsRoutes(!mbbsCard.classList.contains("is-routes-open"));
    });

    mbbsCard.addEventListener("keydown", (event) => {
      if (event.target !== mbbsCard) return;
      if (event.key !== "Enter" && event.key !== " ") return;
      event.preventDefault();
      setMbbsRoutes(!mbbsCard.classList.contains("is-routes-open"));
    });

    if (mbbsClose) {
      mbbsClose.addEventListener("click", (event) => {
        event.stopPropagation();
        setMbbsRoutes(false);
        mbbsCard.focus();
      });
    }

    document.addEventListener("click", (event) => {
      if (!mbbsCard.contains(event.target)) setMbbsRoutes(false);
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") setMbbsRoutes(false);
    });
  }

  const codepenMaps = Array.from(document.querySelectorAll("[data-codepen-map] svg"));
  codepenMaps.forEach((svg) => {
    const setRandomClass = () => {
      const items = Array.from(svg.querySelectorAll("circle"));
      const number = items.length;
      if (!number) return;
      const random = Math.floor(Math.random() * number);
      items.forEach((item) => item.classList.remove("banaan"));
      items[random].classList.add("banaan");
    };

    setRandomClass();
    // Purely decorative, so there is nothing to keep up with while the tab is
    // in the background — it only woke the main thread every two seconds.
    window.setInterval(() => {
      if (!document.hidden) setRandomClass();
    }, 2000);
  });

  const revealItems = Array.from(document.querySelectorAll(".reveal"));
  const revealVisibleItems = () => {
    revealItems.forEach((item) => {
      if (item.classList.contains("is-visible")) return;
      if (item.getBoundingClientRect().top < window.innerHeight * 0.94) {
        item.classList.add("is-visible");
      }
    });
  };

  if ("IntersectionObserver" in window) {
    const revealObserver = new IntersectionObserver(
      (entries, observer) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      },
      { rootMargin: "0px 0px -8% 0px", threshold: 0.12 }
    );

    revealItems.forEach((item) => revealObserver.observe(item));
    revealVisibleItems();
    window.addEventListener("scroll", revealVisibleItems, { passive: true });
    window.addEventListener("resize", revealVisibleItems);
    window.setTimeout(revealVisibleItems, 650);
    const revealTimer = window.setInterval(() => {
      revealVisibleItems();
      if (revealItems.every((item) => item.classList.contains("is-visible"))) {
        window.clearInterval(revealTimer);
      }
    }, 450);
  } else {
    revealItems.forEach((item) => item.classList.add("is-visible"));
  }

  // Hero background slideshow — cycle the stacked .hero-slide layers on a timer.
  // The transition style is pure CSS (driven by [data-hero-anim]); here we only
  // flip which layer is active. Honours reduced-motion (shows the first image).
  document.querySelectorAll("[data-hero-slideshow]").forEach((stack) => {
    if (document.body.classList.contains("cms-editing")) return; // editor previews its own way
    const slides = Array.from(stack.querySelectorAll(".hero-slide"));
    if (slides.length < 2) return;
    if (window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
    const interval = Math.max(2, parseFloat(stack.dataset.heroInterval || "5")) * 1000;
    let i = 0;
    window.setInterval(() => {
      slides[i].classList.remove("is-active");
      i = (i + 1) % slides.length;
      slides[i].classList.add("is-active");
    }, interval);
  });

  // Notice-bar "Left socials, fade on phone" style: on phones the social icons
  // share one slot and fade in/out one at a time. Pure class toggling; the fade
  // is CSS. Honours reduced-motion (shows the first icon only).
  if (document.documentElement.classList.contains("topbar-left-socials-cycle")) {
    const ul = document.querySelector(".notice .site-socials--notice");
    const items = ul ? Array.from(ul.children) : [];
    if (items.length > 1 && window.matchMedia) {
      const phone = window.matchMedia("(max-width: 460px)");
      const reduce = window.matchMedia("(prefers-reduced-motion: reduce)");
      let timer = null, idx = 0;
      const stop = () => { if (timer) { clearInterval(timer); timer = null; } items.forEach((li) => li.classList.remove("is-active")); };
      const start = () => {
        stop(); idx = 0; items[0].classList.add("is-active");
        if (reduce.matches) return;
        timer = window.setInterval(() => {
          items[idx].classList.remove("is-active");
          idx = (idx + 1) % items.length;
          items[idx].classList.add("is-active");
        }, 2600);
      };
      const apply = () => { if (phone.matches) start(); else stop(); };
      apply();
      phone.addEventListener("change", apply);
    }
  }

  // Home hero · Student Hub drawer ("Important Links"). The tab on the hero's
  // right edge slides the panel in and out; the slide itself is CSS (a single
  // transform on .hero-hub), so this only owns the open/closed state. The tab
  // travels with the panel, so the same button closes it again.
  const heroHub = document.querySelector("[data-hero-hub]");
  if (heroHub) {
    const hubToggle = heroHub.querySelector("[data-hero-hub-toggle]");
    const setHubOpen = (open) => {
      heroHub.classList.toggle("is-open", open);
      if (hubToggle) hubToggle.setAttribute("aria-expanded", open ? "true" : "false");
    };

    if (hubToggle) {
      hubToggle.addEventListener("click", () => {
        setHubOpen(!heroHub.classList.contains("is-open"));
      });
    }

    // An open drawer covers part of the hero copy, so a click anywhere else and
    // Escape both dismiss it — the usual way out of an overlay.
    document.addEventListener("click", (event) => {
      if (!heroHub.classList.contains("is-open")) return;
      if (heroHub.contains(event.target)) return;
      setHubOpen(false);
    });

    document.addEventListener("keydown", (event) => {
      if (event.key !== "Escape") return;
      if (!heroHub.classList.contains("is-open")) return;
      setHubOpen(false);
      if (hubToggle) hubToggle.focus();
    });
  }

  // Phone-only collapsible contact card: tap the blue strip to slide the
  // contact details over the form; the close button tucks it away again. The
  // collapsed/overlay styling is gated to ≤720px in CSS, so on desktop this
  // toggles a class that has no visual effect.
  document.querySelectorAll("[data-contact-collapsible]").forEach((wrap) => {
    const handle = wrap.querySelector(".contact-card-handle");
    const closeBtn = wrap.querySelector(".contact-card-close");
    if (!handle) return;
    const setOpen = (open) => {
      wrap.classList.toggle("is-card-open", open);
      handle.setAttribute("aria-expanded", open ? "true" : "false");
    };
    handle.addEventListener("click", () => setOpen(true));
    if (closeBtn) {
      closeBtn.addEventListener("click", () => setOpen(false));
    }
  });

  // Test-preparation expandable cards (accordion)
  const tpCards = Array.from(document.querySelectorAll("[data-tp-card]"));
  tpCards.forEach((card) => {
    const trigger = card.querySelector("[data-tp-toggle]");
    if (!trigger) return;
    trigger.addEventListener("click", () => {
      const isOpen = card.classList.toggle("is-open");
      trigger.setAttribute("aria-expanded", String(isOpen));
    });
  });

  const sections = navLinks
    .map((link) => {
      const href = link.getAttribute("href") || "";
      if (!href.startsWith("#") || href.length < 2) return null;
      let target = null;
      try { target = document.querySelector(href); } catch (_) { return null; }
      return target ? { link, target } : null;
    })
    .filter(Boolean);

  if ("IntersectionObserver" in window && sections.length) {
    const activeObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          navLinks.forEach((link) => link.classList.remove("is-active"));
          const active = sections.find((section) => section.target === entry.target);
          if (active) active.link.classList.add("is-active");
        });
      },
      { rootMargin: "-35% 0px -55% 0px", threshold: 0.02 }
    );

    sections.forEach(({ target }) => activeObserver.observe(target));
  }

  if (finderForm && finderNote) {
    finderForm.addEventListener("submit", (event) => {
      event.preventDefault();
      const data = new FormData(finderForm);
      const destination = data.get("destination");
      const program = data.get("program");
      const intake = data.get("intake");
      finderNote.textContent = `${program} planning for ${destination} in ${intake}: start with profile diagnostics, eligibility checks, and a three-tier university list.`;
    });
  }

  filterButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const filter = button.dataset.filter;

      filterButtons.forEach((item) => item.classList.remove("is-active"));
      button.classList.add("is-active");

      destinationCards.forEach((card) => {
        const groups = card.dataset.group.split(" ");
        card.classList.toggle("is-hidden", filter !== "all" && !groups.includes(filter));
      });
    });
  });

  const destinationInsights = {
    uk: {
      region: "Europe",
      title: "United Kingdom",
      copy: "Focused degrees, one-year masters, strong global recognition, and city-led career exploration.",
      meta: "Best for: focused programs, brand recognition, faster postgraduate timelines"
    },
    canada: {
      region: "North America",
      title: "Canada",
      copy: "Academic quality, co-op pathways, and a student experience that rewards early planning around costs and eligibility.",
      meta: "Best for: settlement pathways, co-op options, balanced budgets"
    },
    usa: {
      region: "North America",
      title: "United States",
      copy: "Deep specialization, research access, and broad campus choice for students with a strong application narrative.",
      meta: "Best for: research, scholarships, flexible academic exploration"
    },
    germany: {
      region: "Europe",
      title: "Germany",
      copy: "Value-led public education with strong technical pathways when language, prerequisites, and timing are handled early.",
      meta: "Best for: engineering, value-led study, structured documentation"
    },
    ireland: {
      region: "Europe",
      title: "Ireland",
      copy: "A compact market with strong technology, business, healthcare, and applied postgraduate opportunities.",
      meta: "Best for: industry access, applied masters, practical city networks"
    },
    australia: {
      region: "Oceania",
      title: "Australia",
      copy: "Practical learning, flexible intakes, vibrant cities, and strong student support for career-aligned programs.",
      meta: "Best for: flexible intakes, applied learning, student lifestyle"
    }
  };

  const setMapInsight = (target) => {
    const insight = destinationInsights[target];
    if (!insight || !mapRegion || !mapTitle || !mapCopy || !mapMeta) return;
    mapPins.forEach((pin) => pin.classList.toggle("is-active", pin.dataset.mapTarget === target));
    mapRegion.textContent = insight.region;
    mapTitle.textContent = insight.title;
    mapCopy.textContent = insight.copy;
    mapMeta.textContent = insight.meta;
  };

  mapPins.forEach((pin) => {
    pin.addEventListener("click", () => setMapInsight(pin.dataset.mapTarget));
    pin.addEventListener("mouseenter", () => setMapInsight(pin.dataset.mapTarget));
  });

  let testimonialIndex = 0;
  let isAnimating = false;
  const EXIT_MS = 460;

  const positionTestimonials = () => {
    const count = testimonials.length;
    testimonials.forEach((testimonial, itemIndex) => {
      const pos = (itemIndex - testimonialIndex + count) % count;
      testimonial.dataset.pos = pos;
      testimonial.classList.toggle("is-active", pos === 0);
      testimonial.setAttribute("aria-hidden", pos === 0 ? "false" : "true");
    });
  };

  // Animated step: the outgoing front card flies aside and fades while the rest
  // of the deck slides forward, then the outgoing card is snapped to the back
  // with its transition suppressed so there is no visible sweep across the deck.
  // direction (+1/-1) decides which card comes to the front; exitSide controls
  // which way the outgoing card flies off. Auto-advance/buttons/tap leave the
  // side unset so it is randomized; a swipe passes the side it was dragged.
  const advanceTestimonial = (direction, exitSide) => {
    if (isAnimating || testimonials.length < 2) return;
    isAnimating = true;
    const side = exitSide || (Math.random() < 0.5 ? "left" : "right");
    const outgoing = testimonials[testimonialIndex];
    outgoing.style.transform = ""; // drop any live-drag transform first
    outgoing.classList.add(side === "left" ? "is-exiting-left" : "is-exiting-right");
    testimonialIndex =
      (testimonialIndex + direction + testimonials.length) % testimonials.length;
    positionTestimonials();
    window.setTimeout(() => {
      outgoing.style.transition = "none";
      outgoing.classList.remove("is-exiting-left", "is-exiting-right");
      void outgoing.offsetWidth; // reflow so the snap to the back is instant
      outgoing.style.transition = "";
      isAnimating = false;
    }, EXIT_MS);
  };

  if (testimonials.length) {
    positionTestimonials();

    const AUTO_DELAY = 6500;
    let autoAdvance = window.setInterval(() => advanceTestimonial(1), AUTO_DELAY);
    const stopAuto = () => window.clearInterval(autoAdvance);
    const restartAuto = () => {
      stopAuto();
      autoAdvance = window.setInterval(() => advanceTestimonial(1), AUTO_DELAY);
    };

    prevTestimonial?.addEventListener("click", () => {
      advanceTestimonial(-1);
      restartAuto();
    });
    nextTestimonial?.addEventListener("click", () => {
      advanceTestimonial(1);
      restartAuto();
    });

    testimonialTrack.addEventListener("mouseenter", stopAuto);
    testimonialTrack.addEventListener("mouseleave", restartAuto);

    // Drag / swipe to flip the deck, plus tap-to-advance. This is the only
    // navigation on phones, where the prev/next buttons are hidden.
    let dragging = false;
    let startX = 0;
    let deltaX = 0;
    let moved = false;

    const onPointerDown = (event) => {
      if (event.button != null && event.button !== 0) return;
      if (event.target.closest("a, button")) return;
      if (isAnimating) return;
      dragging = true;
      moved = false;
      startX = event.clientX;
      deltaX = 0;
      testimonialTrack.classList.add("is-dragging");
      testimonialTrack.setPointerCapture?.(event.pointerId);
      stopAuto();
    };
    const onPointerMove = (event) => {
      if (!dragging) return;
      deltaX = event.clientX - startX;
      if (Math.abs(deltaX) > 5) moved = true;
      const card = testimonials[testimonialIndex];
      if (card) {
        card.style.transform = `translate(${deltaX}px, 0) rotate(${deltaX * 0.02}deg)`;
      }
    };
    const onPointerUp = () => {
      if (!dragging) return;
      dragging = false;
      testimonialTrack.classList.remove("is-dragging");
      const card = testimonials[testimonialIndex];
      const threshold = 60;
      if (deltaX <= -threshold) {
        advanceTestimonial(1, "left");
      } else if (deltaX >= threshold) {
        advanceTestimonial(-1, "right");
      } else if (!moved) {
        advanceTestimonial(1); // tap → random side
      } else if (card) {
        card.style.transform = ""; // below threshold: snap back to the front
      }
      deltaX = 0;
      restartAuto();
    };

    testimonialTrack.addEventListener("pointerdown", onPointerDown);
    testimonialTrack.addEventListener("pointermove", onPointerMove);
    testimonialTrack.addEventListener("pointerup", onPointerUp);
    testimonialTrack.addEventListener("pointercancel", onPointerUp);
  }

  // ── Public form submission (Contact / Home enquiry + Careers application) ──
  // Both POST via fetch to their Laravel route, then show the result in a
  // branded popup (built below) instead of an inline line — the page never
  // reloads. The CSRF token rides along in the FormData (@csrf hidden field)
  // and as a header for good measure.
  const csrfToken = () => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute("content") : "";
  };

  /* ---- Result popup: lazy-built once, reused for every submission ---- */
  const POPUP_ICONS = {
    success:
      '<svg class="oda-popup__check" viewBox="0 0 52 52" aria-hidden="true"><circle class="oda-popup__check-circle" cx="26" cy="26" r="24"/><path class="oda-popup__check-mark" d="M15 27l7.5 7.5L37 19"/></svg>',
    error:
      '<svg class="oda-popup__check" viewBox="0 0 52 52" aria-hidden="true"><circle class="oda-popup__check-circle" cx="26" cy="26" r="24"/><path class="oda-popup__check-mark" d="M18 18l16 16M34 18L18 34"/></svg>',
  };

  let popupEl = null;
  let popupLastFocus = null;

  const closeFormPopup = () => {
    if (!popupEl || popupEl.hasAttribute("hidden")) return;
    popupEl.setAttribute("hidden", "");
    popupEl.setAttribute("aria-hidden", "true");
    document.documentElement.classList.remove("oda-popup-open");
    if (popupLastFocus && typeof popupLastFocus.focus === "function") {
      popupLastFocus.focus();
    }
  };

  const buildFormPopup = () => {
    const el = document.createElement("div");
    el.className = "oda-popup";
    el.setAttribute("hidden", "");
    el.setAttribute("aria-hidden", "true");
    el.innerHTML =
      '<div class="oda-popup__backdrop" data-popup-close></div>' +
      '<div class="oda-popup__card" role="dialog" aria-modal="true" aria-labelledby="oda-popup-title" aria-describedby="oda-popup-message" tabindex="-1">' +
      '<button class="oda-popup__close" type="button" data-popup-close aria-label="Close">' +
      '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>' +
      "</button>" +
      '<div class="oda-popup__icon" data-popup-icon></div>' +
      '<span class="oda-popup__eyebrow">One Degree Advisory</span>' +
      '<h3 class="oda-popup__title" id="oda-popup-title" data-popup-title></h3>' +
      '<p class="oda-popup__message" id="oda-popup-message" data-popup-message></p>' +
      '<button class="btn btn-primary oda-popup__action" type="button" data-popup-close><span>Done</span></button>' +
      "</div>";
    document.body.appendChild(el);
    el.addEventListener("click", (event) => {
      if (event.target.closest("[data-popup-close]")) closeFormPopup();
    });
    return el;
  };

  const showFormPopup = ({ type = "success", title = "", message = "" }) => {
    if (!popupEl) popupEl = buildFormPopup();
    popupLastFocus = document.activeElement;

    const icon = popupEl.querySelector("[data-popup-icon]");
    icon.className = "oda-popup__icon oda-popup__icon--" + type;
    icon.innerHTML = POPUP_ICONS[type] || POPUP_ICONS.success;
    popupEl.querySelector("[data-popup-title]").textContent = title;
    popupEl.querySelector("[data-popup-message]").textContent = message;

    popupEl.removeAttribute("hidden");
    popupEl.setAttribute("aria-hidden", "false");
    document.documentElement.classList.add("oda-popup-open");

    // Restart the card entrance animation each time the popup opens.
    const card = popupEl.querySelector(".oda-popup__card");
    card.style.animation = "none";
    void card.offsetWidth;
    card.style.animation = "";
    card.focus();
  };

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") closeFormPopup();
  });

  const wireFormSubmit = (form) => {
    if (!form) return;

    form.addEventListener("submit", async (event) => {
      event.preventDefault();

      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const submitBtn = form.querySelector('button[type="submit"]');
      const btnLabel = submitBtn ? submitBtn.querySelector("span") : null;
      const originalLabel = btnLabel ? btnLabel.textContent : "";
      if (submitBtn) submitBtn.disabled = true;
      if (btnLabel) btnLabel.textContent = "Sending…";

      try {
        const response = await fetch(form.action, {
          method: "POST",
          headers: {
            "X-CSRF-TOKEN": csrfToken(),
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
          },
          body: new FormData(form),
        });

        let payload = {};
        try {
          payload = await response.json();
        } catch (e) {
          /* non-JSON response — fall through to generic messaging */
        }

        if (response.ok) {
          form.reset();
          showFormPopup({
            type: "success",
            title: payload.title || "Thank you!",
            message: payload.message || "We'll be in touch shortly.",
          });
        } else {
          const firstError =
            payload.errors && typeof payload.errors === "object"
              ? (Object.values(payload.errors)[0] || [])[0]
              : null;
          showFormPopup({
            type: "error",
            title: payload.title || "Something went wrong",
            message: firstError || payload.message || "Please check the form and try again.",
          });
        }
      } catch (e) {
        showFormPopup({
          type: "error",
          title: "Network error",
          message: "Please check your connection and try again.",
        });
      } finally {
        if (submitBtn) submitBtn.disabled = false;
        if (btnLabel) btnLabel.textContent = originalLabel;
      }
    });
  };

  // Every email input site-wide shares one message for a malformed OR
  // placeholder address, replacing the browser's native "include an @" text so
  // a visitor whose own inbox will not work is told how else to reach us.
  const emailHelpMeta = document.querySelector('meta[name="email-help"]');
  const EMAIL_HELP =
    (emailHelpMeta && emailHelpMeta.getAttribute("content")) ||
    "Please use a valid email address.";
  document.querySelectorAll('input[type="email"]').forEach((input) => {
    const refreshEmailValidity = () => {
      const value = input.value.trim();
      const unusable = input.validity.typeMismatch || (value !== "" && /example/i.test(value));
      // Must clear to "" when usable, otherwise the field stays invalid forever.
      input.setCustomValidity(unusable ? EMAIL_HELP : "");
    };
    input.addEventListener("input", refreshEmailValidity);
    input.addEventListener("blur", refreshEmailValidity);
    refreshEmailValidity();
  });

  wireFormSubmit(consultForm);
  wireFormSubmit(document.querySelector("[data-career-form]"));
  // Blog newsletter sign-ups ("Stay Current On…" + "Stay in the loop") — same
  // AJAX flow + confirmation popup; the email is stored server-side.
  document.querySelectorAll("[data-newsletter-form]").forEach(wireFormSubmit);
  // Loan & Acco enquiry forms (/loan-accommodation) — same AJAX flow + popup;
  // each lead is stored server-side (source = loan-acco).
  document.querySelectorAll("[data-loan-acco-form]").forEach(wireFormSubmit);
  // Statement of Purpose "book a strategy call" form (/statement-of-purpose) —
  // same AJAX flow + popup; each lead is stored server-side (source = sop).
  document.querySelectorAll("[data-sop-form]").forEach(wireFormSubmit);
  // Career Counselling consultation request (/career-counselling) — same AJAX
  // flow + popup; the lead is stored server-side (source = career-counselling).
  document.querySelectorAll("[data-career-counselling-form]").forEach(wireFormSubmit);
  // Referral Program submission (/referral-program) — same AJAX flow + popup;
  // the referred STUDENT is stored as the lead (source = referral) with the
  // referrer recorded alongside them.
  document.querySelectorAll("[data-referral-form]").forEach(wireFormSubmit);

  // ── Resume drag-and-drop uploader (careers form) ──
  // The native file input is hidden; the styled zone opens it on click and
  // accepts dropped files by assigning them to the input, so the resume rides
  // along in the form's FormData on submit.
  const initResumeDropzone = () => {
    const zone = document.querySelector("[data-dropzone]");
    if (!zone) return;

    const input = zone.querySelector("[data-dropzone-input]");
    const prompt = zone.querySelector("[data-dropzone-prompt]");
    const fileView = zone.querySelector("[data-dropzone-file]");
    const filenameEl = zone.querySelector("[data-dropzone-filename]");
    const removeBtn = zone.querySelector("[data-dropzone-remove]");
    const errorEl = document.querySelector("[data-dropzone-error]");
    if (!input) return;

    const MAX_BYTES = 2 * 1024 * 1024; // 2 MB — matches the server + PHP limit
    const ALLOWED = ["pdf", "doc", "docx"];

    const prettySize = (bytes) => {
      if (bytes < 1024) return bytes + " B";
      if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + " KB";
      return (bytes / (1024 * 1024)).toFixed(1) + " MB";
    };

    const showError = (msg) => {
      if (!errorEl) return;
      errorEl.textContent = msg || "";
      errorEl.hidden = !msg;
    };

    const clearFile = () => {
      input.value = "";
      zone.classList.remove("has-file");
      if (fileView) fileView.hidden = true;
      if (prompt) prompt.hidden = false;
    };

    const render = () => {
      showError("");
      const file = input.files && input.files[0];
      if (!file) {
        clearFile();
        return;
      }
      const ext = (file.name.split(".").pop() || "").toLowerCase();
      if (!ALLOWED.includes(ext)) {
        clearFile();
        showError("Please upload a PDF, DOC or DOCX file.");
        return;
      }
      if (file.size > MAX_BYTES) {
        clearFile();
        showError("That file is over 2 MB. Please upload a smaller file.");
        return;
      }
      if (filenameEl) filenameEl.textContent = file.name + " · " + prettySize(file.size);
      if (prompt) prompt.hidden = true;
      if (fileView) fileView.hidden = false;
      zone.classList.add("has-file");
    };

    zone.addEventListener("click", (event) => {
      if (event.target.closest("[data-dropzone-remove]")) return;
      input.click();
    });

    input.addEventListener("change", render);

    ["dragenter", "dragover"].forEach((ev) =>
      zone.addEventListener(ev, (event) => {
        event.preventDefault();
        zone.classList.add("is-dragover");
      })
    );
    ["dragleave", "dragend", "drop"].forEach((ev) =>
      zone.addEventListener(ev, (event) => {
        event.preventDefault();
        if (ev === "dragleave" && zone.contains(event.relatedTarget)) return;
        zone.classList.remove("is-dragover");
      })
    );

    zone.addEventListener("drop", (event) => {
      event.preventDefault();
      const dropped = event.dataTransfer && event.dataTransfer.files;
      if (dropped && dropped.length) {
        input.files = dropped; // assign the dropped FileList to the input
        render();
      }
    });

    if (removeBtn) {
      removeBtn.addEventListener("click", (event) => {
        event.stopPropagation();
        clearFile();
        showError("");
      });
    }
  };

  initResumeDropzone();

  const FALLBACK_CITIES_BY_STATE = {
    "Andhra Pradesh": ["Visakhapatnam", "Vijayawada", "Guntur", "Nellore", "Kurnool", "Tirupati", "Rajahmundry", "Kakinada", "Anantapur", "Chittoor", "Kadapa", "Eluru", "Ongole"],
    "Arunachal Pradesh": ["Itanagar", "Naharlagun", "Pasighat", "Bomdila", "Tawang", "Ziro", "Tezu"],
    "Assam": ["Guwahati", "Dibrugarh", "Silchar", "Jorhat", "Nagaon", "Tinsukia", "Tezpur", "Bongaigaon", "Karimganj", "Dhubri"],
    "Bihar": ["Patna", "Gaya", "Bhagalpur", "Muzaffarpur", "Darbhanga", "Purnia", "Bihar Sharif", "Arrah", "Begusarai", "Katihar", "Munger", "Chhapra"],
    "Chhattisgarh": ["Raipur", "Bhilai", "Bilaspur", "Korba", "Durg", "Rajnandgaon", "Jagdalpur", "Ambikapur", "Raigarh"],
    "Goa": ["Panaji", "Margao", "Vasco da Gama", "Mapusa", "Ponda"],
    "Gujarat": ["Ahmedabad", "Surat", "Vadodara", "Rajkot", "Bhavnagar", "Jamnagar", "Junagadh", "Gandhinagar", "Anand", "Navsari", "Mehsana", "Bharuch", "Gandhidham"],
    "Haryana": ["Faridabad", "Gurugram", "Panipat", "Ambala", "Yamunanagar", "Rohtak", "Hisar", "Karnal", "Sonipat", "Panchkula", "Bhiwani", "Sirsa", "Rewari"],
    "Himachal Pradesh": ["Shimla", "Dharamshala", "Solan", "Mandi", "Kullu", "Manali", "Hamirpur", "Una", "Bilaspur", "Chamba", "Kangra", "Palampur"],
    "Jharkhand": ["Ranchi", "Jamshedpur", "Dhanbad", "Bokaro", "Hazaribagh", "Deoghar", "Phusro", "Giridih", "Ramgarh"],
    "Karnataka": ["Bengaluru", "Mysuru", "Hubballi-Dharwad", "Mangaluru", "Belagavi", "Kalaburagi", "Davanagere", "Ballari", "Vijayapura", "Shivamogga", "Tumakuru", "Udupi", "Hassan"],
    "Kerala": ["Thiruvananthapuram", "Kochi", "Kozhikode", "Thrissur", "Kollam", "Kannur", "Alappuzha", "Palakkad", "Malappuram", "Kottayam", "Pathanamthitta"],
    "Madhya Pradesh": ["Indore", "Bhopal", "Jabalpur", "Gwalior", "Ujjain", "Sagar", "Dewas", "Satna", "Ratlam", "Rewa", "Vidisha", "Khandwa", "Burhanpur"],
    "Maharashtra": ["Mumbai", "Pune", "Nagpur", "Nashik", "Thane", "Chhatrapati Sambhajinagar", "Solapur", "Kolhapur", "Amravati", "Navi Mumbai", "Vasai-Virar", "Sangli", "Ahmednagar", "Latur", "Jalgaon", "Akola"],
    "Manipur": ["Imphal", "Thoubal", "Bishnupur", "Churachandpur", "Ukhrul", "Senapati"],
    "Meghalaya": ["Shillong", "Tura", "Jowai", "Nongpoh", "Williamnagar"],
    "Mizoram": ["Aizawl", "Lunglei", "Saiha", "Champhai", "Kolasib", "Serchhip"],
    "Nagaland": ["Kohima", "Dimapur", "Mokokchung", "Tuensang", "Wokha", "Zunheboto"],
    "Odisha": ["Bhubaneswar", "Cuttack", "Rourkela", "Berhampur", "Sambalpur", "Puri", "Balasore", "Bhadrak", "Jeypore", "Angul"],
    "Punjab": ["Ludhiana", "Amritsar", "Jalandhar", "Patiala", "Bathinda", "Mohali", "Pathankot", "Hoshiarpur", "Moga", "Firozpur", "Kapurthala"],
    "Rajasthan": ["Jaipur", "Jodhpur", "Udaipur", "Kota", "Ajmer", "Bikaner", "Alwar", "Bharatpur", "Sikar", "Pali", "Sri Ganganagar", "Kishangarh", "Beawar"],
    "Sikkim": ["Gangtok", "Namchi", "Gyalshing", "Mangan", "Rangpo"],
    "Tamil Nadu": ["Chennai", "Coimbatore", "Madurai", "Tiruchirappalli", "Salem", "Tirunelveli", "Tiruppur", "Erode", "Vellore", "Thoothukudi", "Dindigul", "Thanjavur", "Hosur", "Nagercoil"],
    "Telangana": ["Hyderabad", "Warangal", "Nizamabad", "Karimnagar", "Khammam", "Mahbubnagar", "Ramagundam", "Secunderabad", "Adilabad", "Suryapet"],
    "Tripura": ["Agartala", "Udaipur", "Dharmanagar", "Kailashahar", "Belonia", "Khowai"],
    "Uttar Pradesh": ["Lucknow", "Kanpur", "Ghaziabad", "Agra", "Varanasi", "Meerut", "Prayagraj", "Bareilly", "Aligarh", "Moradabad", "Saharanpur", "Gorakhpur", "Noida", "Firozabad", "Jhansi", "Mathura", "Ayodhya"],
    "Uttarakhand": ["Dehradun", "Haridwar", "Rishikesh", "Nainital", "Roorkee", "Haldwani", "Rudrapur", "Kashipur", "Mussoorie"],
    "West Bengal": ["Kolkata", "Howrah", "Asansol", "Siliguri", "Durgapur", "Bardhaman", "Malda", "Kharagpur", "Haldia", "Berhampore"],
    "Andaman and Nicobar Islands": ["Port Blair", "Diglipur", "Mayabunder", "Rangat", "Car Nicobar"],
    "Chandigarh": ["Chandigarh"],
    "Dadra and Nagar Haveli and Daman and Diu": ["Silvassa", "Daman", "Diu"],
    "Delhi": ["New Delhi", "North Delhi", "South Delhi", "East Delhi", "West Delhi", "Central Delhi", "Dwarka", "Rohini", "Saket", "Karol Bagh", "Pitampura"],
    "Jammu and Kashmir": ["Srinagar", "Jammu", "Anantnag", "Baramulla", "Udhampur", "Kathua", "Sopore", "Pulwama"],
    "Ladakh": ["Leh", "Kargil"],
    "Lakshadweep": ["Kavaratti", "Agatti", "Andrott", "Minicoy", "Amini"],
    "Puducherry": ["Puducherry", "Karaikal", "Mahe", "Yanam"]
  };

  const STATES_API_URL = "https://countriesnow.space/api/v0.1/countries/states";
  const CITIES_API_URL = "https://countriesnow.space/api/v0.1/countries/state/cities";
  const STATE_ALIASES = {
    "Dadra and Nagar Haveli and Daman and Diu": ["Dadra And Nagar Haveli", "Dadra and Nagar Haveli", "Daman and Diu", "Daman And Diu"],
    "Chhatrapati Sambhajinagar": ["Aurangabad"]
  };
  const CACHE_PREFIX = "oda_in_v1_";
  const CACHE_TTL_MS = 30 * 24 * 60 * 60 * 1000;
  const API_TIMEOUT_MS = 6000;

  const readCache = (key) => {
    try {
      const raw = localStorage.getItem(CACHE_PREFIX + key);
      if (!raw) return null;
      const obj = JSON.parse(raw);
      if (!obj || typeof obj.ts !== "number" || !obj.data) return null;
      if (Date.now() - obj.ts > CACHE_TTL_MS) return null;
      return obj.data;
    } catch (_) { return null; }
  };

  const writeCache = (key, data) => {
    try {
      localStorage.setItem(CACHE_PREFIX + key, JSON.stringify({ ts: Date.now(), data }));
    } catch (_) {}
  };

  const fetchJson = async (url, body) => {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), API_TIMEOUT_MS);
    try {
      const res = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json", "Accept": "application/json" },
        body: JSON.stringify(body),
        signal: controller.signal
      });
      if (!res.ok) throw new Error("HTTP " + res.status);
      const json = await res.json();
      if (json.error) throw new Error("API error");
      return json.data;
    } finally {
      clearTimeout(timer);
    }
  };

  const fetchStatesFromApi = async () => {
    const cached = readCache("states");
    if (cached) return cached;
    const data = await fetchJson(STATES_API_URL, { country: "India" });
    if (!data || !Array.isArray(data.states)) throw new Error("Bad states response");
    const states = data.states.map((s) => s.name).filter(Boolean);
    writeCache("states", states);
    return states;
  };

  const fetchCitiesFromApi = async (state) => {
    const cacheKey = "cities_" + state;
    const cached = readCache(cacheKey);
    if (cached) return cached;
    const aliases = [state, ...(STATE_ALIASES[state] || [])];
    let combined = [];
    let anySuccess = false;
    for (const alias of aliases) {
      try {
        const cities = await fetchJson(CITIES_API_URL, { country: "India", state: alias });
        if (Array.isArray(cities) && cities.length) {
          combined = combined.concat(cities);
          anySuccess = true;
        }
      } catch (_) {}
    }
    if (!anySuccess) throw new Error("No cities from API");
    const unique = Array.from(new Set(combined.filter(Boolean)));
    writeCache(cacheKey, unique);
    return unique;
  };

  // ---------- Country data (bundled fallback; restcountries.com enriches at runtime) ----------
  // [name, ISO2, dial, mobile-digit-length]
  const COUNTRIES_FALLBACK = [
    ["Afghanistan","AF","+93",9],["Albania","AL","+355",9],["Algeria","DZ","+213",9],
    ["Andorra","AD","+376",6],["Angola","AO","+244",9],["Argentina","AR","+54",10],
    ["Armenia","AM","+374",8],["Australia","AU","+61",9],["Austria","AT","+43",11],
    ["Azerbaijan","AZ","+994",9],["Bahamas","BS","+1",10],["Bahrain","BH","+973",8],
    ["Bangladesh","BD","+880",10],["Barbados","BB","+1",10],["Belarus","BY","+375",9],
    ["Belgium","BE","+32",9],["Belize","BZ","+501",7],["Benin","BJ","+229",8],
    ["Bhutan","BT","+975",8],["Bolivia","BO","+591",8],["Bosnia and Herzegovina","BA","+387",8],
    ["Botswana","BW","+267",8],["Brazil","BR","+55",11],["Brunei","BN","+673",7],
    ["Bulgaria","BG","+359",9],["Burkina Faso","BF","+226",8],["Burundi","BI","+257",8],
    ["Cambodia","KH","+855",9],["Cameroon","CM","+237",9],["Canada","CA","+1",10],
    ["Cape Verde","CV","+238",7],["Central African Republic","CF","+236",8],["Chad","TD","+235",8],
    ["Chile","CL","+56",9],["China","CN","+86",11],["Colombia","CO","+57",10],
    ["Comoros","KM","+269",7],["Congo","CG","+242",9],["Costa Rica","CR","+506",8],
    ["Croatia","HR","+385",9],["Cuba","CU","+53",8],["Cyprus","CY","+357",8],
    ["Czech Republic","CZ","+420",9],["DR Congo","CD","+243",9],["Denmark","DK","+45",8],
    ["Djibouti","DJ","+253",8],["Dominica","DM","+1",10],["Dominican Republic","DO","+1",10],
    ["Ecuador","EC","+593",9],["Egypt","EG","+20",10],["El Salvador","SV","+503",8],
    ["Equatorial Guinea","GQ","+240",9],["Eritrea","ER","+291",7],["Estonia","EE","+372",8],
    ["Eswatini","SZ","+268",8],["Ethiopia","ET","+251",9],["Fiji","FJ","+679",7],
    ["Finland","FI","+358",10],["France","FR","+33",9],["Gabon","GA","+241",8],
    ["Gambia","GM","+220",7],["Georgia","GE","+995",9],["Germany","DE","+49",11],
    ["Ghana","GH","+233",9],["Greece","GR","+30",10],["Grenada","GD","+1",10],
    ["Guatemala","GT","+502",8],["Guinea","GN","+224",9],["Guinea-Bissau","GW","+245",7],
    ["Guyana","GY","+592",7],["Haiti","HT","+509",8],["Honduras","HN","+504",8],
    ["Hong Kong","HK","+852",8],["Hungary","HU","+36",9],["Iceland","IS","+354",7],
    ["India","IN","+91",10],["Indonesia","ID","+62",11],["Iran","IR","+98",10],
    ["Iraq","IQ","+964",10],["Ireland","IE","+353",9],["Israel","IL","+972",9],
    ["Italy","IT","+39",10],["Jamaica","JM","+1",10],["Japan","JP","+81",10],
    ["Jordan","JO","+962",9],["Kazakhstan","KZ","+7",10],["Kenya","KE","+254",9],
    ["Kuwait","KW","+965",8],["Kyrgyzstan","KG","+996",9],["Laos","LA","+856",10],
    ["Latvia","LV","+371",8],["Lebanon","LB","+961",8],["Lesotho","LS","+266",8],
    ["Liberia","LR","+231",8],["Libya","LY","+218",9],["Liechtenstein","LI","+423",7],
    ["Lithuania","LT","+370",8],["Luxembourg","LU","+352",9],["Macau","MO","+853",8],
    ["Madagascar","MG","+261",9],["Malawi","MW","+265",9],["Malaysia","MY","+60",10],
    ["Maldives","MV","+960",7],["Mali","ML","+223",8],["Malta","MT","+356",8],
    ["Mauritania","MR","+222",8],["Mauritius","MU","+230",8],["Mexico","MX","+52",10],
    ["Moldova","MD","+373",8],["Monaco","MC","+377",8],["Mongolia","MN","+976",8],
    ["Montenegro","ME","+382",8],["Morocco","MA","+212",9],["Mozambique","MZ","+258",9],
    ["Myanmar","MM","+95",10],["Namibia","NA","+264",9],["Nepal","NP","+977",10],
    ["Netherlands","NL","+31",9],["New Zealand","NZ","+64",9],["Nicaragua","NI","+505",8],
    ["Niger","NE","+227",8],["Nigeria","NG","+234",10],["North Macedonia","MK","+389",8],
    ["Norway","NO","+47",8],["Oman","OM","+968",8],["Pakistan","PK","+92",10],
    ["Palestine","PS","+970",9],["Panama","PA","+507",8],["Papua New Guinea","PG","+675",8],
    ["Paraguay","PY","+595",9],["Peru","PE","+51",9],["Philippines","PH","+63",10],
    ["Poland","PL","+48",9],["Portugal","PT","+351",9],["Qatar","QA","+974",8],
    ["Romania","RO","+40",9],["Russia","RU","+7",10],["Rwanda","RW","+250",9],
    ["Saudi Arabia","SA","+966",9],["Senegal","SN","+221",9],["Serbia","RS","+381",9],
    ["Seychelles","SC","+248",7],["Sierra Leone","SL","+232",8],["Singapore","SG","+65",8],
    ["Slovakia","SK","+421",9],["Slovenia","SI","+386",8],["Somalia","SO","+252",8],
    ["South Africa","ZA","+27",9],["South Korea","KR","+82",10],["South Sudan","SS","+211",9],
    ["Spain","ES","+34",9],["Sri Lanka","LK","+94",9],["Sudan","SD","+249",9],
    ["Suriname","SR","+597",7],["Sweden","SE","+46",9],["Switzerland","CH","+41",9],
    ["Syria","SY","+963",9],["Taiwan","TW","+886",9],["Tajikistan","TJ","+992",9],
    ["Tanzania","TZ","+255",9],["Thailand","TH","+66",9],["Timor-Leste","TL","+670",8],
    ["Togo","TG","+228",8],["Trinidad and Tobago","TT","+1",10],["Tunisia","TN","+216",8],
    ["Turkey","TR","+90",10],["Turkmenistan","TM","+993",8],["Uganda","UG","+256",9],
    ["Ukraine","UA","+380",9],["United Arab Emirates","AE","+971",9],["United Kingdom","GB","+44",10],
    ["United States","US","+1",10],["Uruguay","UY","+598",8],["Uzbekistan","UZ","+998",9],
    ["Vanuatu","VU","+678",7],["Venezuela","VE","+58",10],["Vietnam","VN","+84",10],
    ["Yemen","YE","+967",9],["Zambia","ZM","+260",9],["Zimbabwe","ZW","+263",9]
  ];

  const COUNTRIES_API_URL = "https://restcountries.com/v3.1/all?fields=name,cca2,idd";
  const flagUrl = (iso) => `https://flagcdn.com/w40/${iso.toLowerCase()}.png`;
  const flagUrl2x = (iso) => `https://flagcdn.com/w80/${iso.toLowerCase()}.png`;

  const buildCountryItems = (rows) =>
    rows
      .map(([name, iso, dial, length]) => ({
        value: iso,
        name,
        dial,
        length,
        search: (name + " " + dial + " " + iso).toLowerCase()
      }))
      .sort((a, b) => a.name.localeCompare(b.name));

  const fetchCountriesFromApi = async () => {
    const cached = readCache("countries");
    if (cached) return cached;
    try {
      const controller = new AbortController();
      const timer = setTimeout(() => controller.abort(), API_TIMEOUT_MS);
      const res = await fetch(COUNTRIES_API_URL, { signal: controller.signal });
      clearTimeout(timer);
      if (!res.ok) throw new Error();
      const data = await res.json();
      const lengthMap = new Map(COUNTRIES_FALLBACK.map(([, iso, , l]) => [iso, l]));
      const rows = data
        .filter((c) => c.idd && c.idd.root && c.cca2 && c.name && c.name.common)
        .map((c) => {
          const dial = c.idd.root + ((c.idd.suffixes && c.idd.suffixes[0]) || "");
          const length = lengthMap.get(c.cca2) || 10;
          return [c.name.common, c.cca2, dial, length];
        });
      writeCache("countries", rows);
      return rows;
    } catch (_) {
      return null;
    }
  };

  // ---------- Reusable searchable combobox ----------
  const createCombobox = ({ host, items, value, placeholder, onChange, searchPlaceholder, formatItem, formatSelected, emptyText, disabled = false, onDisabledClick }) => {
    const wrap = document.createElement("div");
    wrap.className = "cbx";

    const field = document.createElement("div");
    const listId = "cbx-list-" + Math.random().toString(36).slice(2);
    field.className = "cbx-field";
    field.tabIndex = 0;
    field.setAttribute("role", "combobox");
    field.setAttribute("aria-haspopup", "listbox");
    field.setAttribute("aria-expanded", "false");
    field.setAttribute("aria-controls", listId);

    const valueDisplay = document.createElement("div");
    valueDisplay.className = "cbx-value";

    const panel = document.createElement("div");
    panel.className = "cbx-panel";

    const searchInput = document.createElement("input");
    searchInput.type = "text";
    searchInput.className = "cbx-input";
    searchInput.placeholder = searchPlaceholder || "Search...";
    searchInput.autocomplete = "off";
    searchInput.spellcheck = false;
    searchInput.tabIndex = -1;
    searchInput.setAttribute("aria-label", searchPlaceholder || placeholder || "Search");

    const chevron = document.createElement("i");
    chevron.className = "cbx-chevron";
    chevron.setAttribute("data-lucide", "chevron-down");

    const list = document.createElement("ul");
    list.className = "cbx-list";
    list.setAttribute("role", "listbox");
    list.id = listId;

    field.appendChild(valueDisplay);
    field.appendChild(searchInput);
    field.appendChild(chevron);
    panel.appendChild(list);
    wrap.appendChild(field);
    wrap.appendChild(panel);
    host.appendChild(wrap);

    const state = {
      items: items || [],
      value: value || null,
      filter: "",
      activeIndex: -1,
      filtered: [],
      disabled: !!disabled
    };

    const escapeHtml = (s) => String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

    const defaultFormatItem = (it) => `<span class="cbx-name">${escapeHtml(it.name || it.label || it.value)}</span>`;
    const defaultFormatSelected = (it) => `<span class="cbx-name">${escapeHtml(it.name || it.label || it.value)}</span>`;

    const renderField = () => {
      const sel = state.items.find((x) => x.value === state.value);
      if (sel) {
        valueDisplay.innerHTML = (formatSelected || defaultFormatSelected)(sel);
      } else {
        valueDisplay.innerHTML = `<span class="cbx-placeholder">${escapeHtml(placeholder || "")}</span>`;
      }
      if (window.lucide) window.lucide.createIcons({ icons: window.lucide.icons });
    };

    const renderList = () => {
      const f = state.filter.toLowerCase().trim();
      state.filtered = f ? state.items.filter((it) => (it.search || (it.name || "")).toLowerCase().includes(f)) : state.items;
      list.innerHTML = "";
      if (state.filtered.length === 0) {
        const empty = document.createElement("li");
        empty.className = "cbx-empty";
        empty.textContent = emptyText || "No matches";
        list.appendChild(empty);
        return;
      }
      state.filtered.forEach((it, idx) => {
        const li = document.createElement("li");
        li.className = "cbx-option";
        if (it.value === state.value) li.classList.add("is-selected");
        if (idx === state.activeIndex) li.classList.add("is-active");
        li.setAttribute("role", "option");
        li.dataset.value = String(it.value);
        li.innerHTML = (formatItem || defaultFormatItem)(it);
        li.addEventListener("mousedown", (e) => {
          e.preventDefault();
          selectItem(it);
        });
        list.appendChild(li);
      });
      if (window.lucide) window.lucide.createIcons({ icons: window.lucide.icons });
    };

    const selectItem = (it) => {
      const changed = state.value !== it.value;
      state.value = it.value;
      state.filter = "";
      searchInput.value = "";
      renderField();
      close();
      if (changed && onChange) onChange(it);
    };

    const findParentField = () => wrap.closest(".hc-select.is-enhanced");

    const positionPanel = () => {
      if (!wrap.classList.contains("is-open")) return;

      const rect = field.getBoundingClientRect();
      const parentField = findParentField();
      const pad = 14;
      const gap = 6;
      const maxAllowedWidth = Math.max(180, window.innerWidth - pad * 2);

      const desiredWidth = parentField ? rect.width : Math.min(420, maxAllowedWidth);
      panel.style.minWidth = rect.width + "px";
      panel.style.width = parentField ? rect.width + "px" : "max-content";
      panel.style.maxWidth = Math.min(parentField ? rect.width : 420, maxAllowedWidth) + "px";

      const spaceBelow = window.innerHeight - rect.bottom - pad;
      const spaceAbove = rect.top - pad;
      const openUp = spaceBelow < 180 && spaceAbove > spaceBelow;

      let maxHeight;
      if (openUp) {
        maxHeight = Math.min(320, Math.max(160, spaceAbove - gap));
        panel.style.top = Math.max(pad, rect.top - gap - maxHeight) + "px";
      } else {
        maxHeight = Math.min(320, Math.max(160, spaceBelow - gap));
        panel.style.top = rect.bottom + gap + "px";
      }
      panel.style.bottom = "auto";

      let left = rect.left;
      if (left + desiredWidth > window.innerWidth - pad) {
        left = Math.max(pad, window.innerWidth - pad - desiredWidth);
      }
      panel.style.left = left + "px";
      panel.style.right = "auto";
      panel.style.maxHeight = maxHeight + "px";
    };

    const repositionPanel = () => positionPanel();

    const open = (seed = "") => {
      if (state.disabled) return;
      if (wrap.classList.contains("is-open")) return;
      wrap.classList.add("is-open");
      if (panel.parentElement !== document.body) document.body.appendChild(panel);
      panel.classList.add("is-open");
      field.setAttribute("aria-expanded", "true");
      searchInput.tabIndex = 0;
      const parentField = findParentField();
      if (parentField) parentField.classList.add("is-focused");
      searchInput.value = seed;
      state.filter = seed;
      state.activeIndex = seed ? 0 : -1;
      renderList();
      positionPanel();
      requestAnimationFrame(positionPanel);
      setTimeout(() => {
        searchInput.focus();
        searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
      }, 10);
    };

    const close = () => {
      wrap.classList.remove("is-open");
      panel.classList.remove("is-open");
      field.setAttribute("aria-expanded", "false");
      searchInput.tabIndex = wrap.classList.contains("is-open") && !state.disabled ? 0 : -1;
      state.filter = "";
      state.activeIndex = -1;
      searchInput.value = "";
      const parentField = findParentField();
      if (parentField) parentField.classList.remove("is-focused");
      renderField();
      if (panel.parentElement === document.body) wrap.appendChild(panel);
    };

    const syncDisabled = () => {
      if (state.disabled && wrap.classList.contains("is-open")) close();
      wrap.classList.toggle("is-disabled", state.disabled);
      field.setAttribute("aria-disabled", state.disabled ? "true" : "false");
      field.tabIndex = state.disabled ? -1 : 0;
      searchInput.tabIndex = -1;
    };

    field.addEventListener("click", () => {
      if (state.disabled) {
        if (onDisabledClick) onDisabledClick();
        return;
      }
      if (wrap.classList.contains("is-open")) {
        searchInput.focus();
      } else {
        open();
      }
    });

    field.addEventListener("keydown", (e) => {
      if (e.target === searchInput) return;
      if (state.disabled) {
        if ((e.key === "Enter" || e.key === " ") && onDisabledClick) onDisabledClick();
        return;
      }
      if (wrap.classList.contains("is-open")) return;
      if (e.key === "Enter" || e.key === " " || e.key === "ArrowDown") {
        e.preventDefault();
        open();
      } else if (e.key.length === 1 && !e.altKey && !e.ctrlKey && !e.metaKey) {
        e.preventDefault();
        open(e.key);
      }
    });

    searchInput.addEventListener("input", () => {
      state.filter = searchInput.value;
      state.activeIndex = state.filter ? 0 : -1;
      renderList();
      positionPanel();
    });

    searchInput.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        e.preventDefault();
        close();
        field.focus();
      } else if (e.key === "ArrowDown") {
        e.preventDefault();
        state.activeIndex = Math.min(state.filtered.length - 1, state.activeIndex + 1);
        renderList();
        const active = list.querySelector(".cbx-option.is-active");
        if (active) active.scrollIntoView({ block: "nearest" });
      } else if (e.key === "ArrowUp") {
        e.preventDefault();
        state.activeIndex = Math.max(0, state.activeIndex - 1);
        renderList();
        const active = list.querySelector(".cbx-option.is-active");
        if (active) active.scrollIntoView({ block: "nearest" });
      } else if (e.key === "Enter") {
        e.preventDefault();
        const pick = state.activeIndex >= 0 ? state.filtered[state.activeIndex] : state.filtered[0];
        if (pick) selectItem(pick);
      } else if (e.key === "Tab") {
        close();
      }
    });

    document.addEventListener("mousedown", (e) => {
      if (!wrap.contains(e.target) && !panel.contains(e.target)) close();
    });
    window.addEventListener("resize", repositionPanel);
    window.addEventListener("scroll", repositionPanel, true);

    renderField();
    syncDisabled();

    return {
      element: wrap,
      setItems: (newItems) => {
        state.items = newItems || [];
        renderField();
        if (wrap.classList.contains("is-open")) {
          renderList();
          positionPanel();
        }
      },
      setValue: (v) => { state.value = v; renderField(); },
      setDisabled: (isDisabled) => {
        state.disabled = !!isDisabled;
        syncDisabled();
      },
      getValue: () => state.value,
      getItem: () => state.items.find((x) => x.value === state.value) || null,
      open, close
    };
  };

  // ---------- Phone country combobox ----------
  const countryHosts = Array.from(document.querySelectorAll("[data-country-select]"));

  if (countryHosts.length) {
    const fmtItem = (it) => `<img class="cbx-flag" src="${flagUrl(it.value)}" srcset="${flagUrl2x(it.value)} 2x" width="22" height="16" alt="" loading="lazy"><span class="cbx-name">${it.name}</span><span class="cbx-dial">${it.dial}</span>`;
    const fmtSelected = (it) => `<img class="cbx-flag" src="${flagUrl(it.value)}" srcset="${flagUrl2x(it.value)} 2x" width="22" height="16" alt=""><span class="cbx-dial">${it.dial}</span>`;

    const items = buildCountryItems(COUNTRIES_FALLBACK);
    const countryComboboxes = countryHosts.map((countryHost) => {
      const scope = countryHost.closest("[data-phone-group]") || countryHost.closest(".hc-phone") || countryHost.parentElement;
      const phoneInput = scope ? scope.querySelector("[data-phone-input]") : null;
      const phoneCountryInput = scope ? scope.querySelector("[data-phone-country-input]") : null;

      const applyCountry = (it) => {
        if (!it) return;
        if (phoneCountryInput) phoneCountryInput.value = it.dial;
        if (phoneInput) {
          phoneInput.maxLength = it.length;
          phoneInput.pattern = `\\d{${it.length}}`;
          phoneInput.setAttribute("aria-label", `Phone number (${it.length} digits)`);
          if (phoneInput.value.length > it.length) phoneInput.value = phoneInput.value.slice(0, it.length);
        }
      };

      const countryCbx = createCombobox({
        host: countryHost,
        items,
        value: "IN",
        placeholder: "+code",
        searchPlaceholder: "Search country or code",
        onChange: applyCountry,
        formatItem: fmtItem,
        formatSelected: fmtSelected,
        emptyText: "No country found"
      });
      applyCountry(items.find((c) => c.value === "IN"));

      if (phoneInput) {
        phoneInput.addEventListener("input", () => {
          const digitsOnly = phoneInput.value.replace(/\D+/g, "");
          const len = countryCbx.getItem() ? countryCbx.getItem().length : 10;
          phoneInput.value = digitsOnly.slice(0, len);
        });
      }

      return countryCbx;
    });

    fetchCountriesFromApi().then((rows) => {
      if (!rows || rows.length < 50) return;
      const merged = buildCountryItems(rows);
      countryComboboxes.forEach((countryCbx) => countryCbx.setItems(merged));
    });
  }

  // ---------- State + city comboboxes ----------
  const stateSelect = document.querySelector("[data-state-select]");
  const citySelect = document.querySelector("[data-city-select]");

  if (stateSelect && citySelect) {
    const sortAlpha = (arr) => arr.slice().sort((a, b) => a.localeCompare(b));

    const syncSelect = (selectEl, value, options) => {
      selectEl.innerHTML = "";
      const ph = document.createElement("option");
      ph.value = "";
      ph.disabled = true;
      ph.hidden = true;
      selectEl.appendChild(ph);
      options.forEach((v) => {
        const opt = document.createElement("option");
        opt.value = v;
        opt.textContent = v;
        selectEl.appendChild(opt);
      });
      if (value && options.includes(value)) {
        selectEl.value = value;
      } else {
        ph.selected = true;
      }
    };

    const valueItems = (arr) => arr.map((v) => ({ value: v, name: v, search: v.toLowerCase() }));

    const fmtSimple = (it) => `<span class="cbx-name">${it.name}</span>`;

    const stateWrap = stateSelect.parentElement;
    const cityWrap = citySelect.parentElement;
    stateWrap.classList.add("is-enhanced");
    cityWrap.classList.add("is-enhanced");

    const setFilled = (wrap, filled) => wrap.classList.toggle("is-filled", !!filled);

    let activeRequestToken = 0;
    let stateAlertTimer = null;

    const clearStateAlert = () => {
      if (stateAlertTimer) clearTimeout(stateAlertTimer);
      stateAlertTimer = null;
      stateWrap.classList.remove("is-required-alert");
    };

    const highlightStateRequired = () => {
      if (stateSelect.value) return;
      stateWrap.classList.add("is-required-alert");
      if (stateAlertTimer) clearTimeout(stateAlertTimer);
      stateAlertTimer = setTimeout(clearStateAlert, 1800);
    };

    const cityCbx = createCombobox({
      host: cityWrap,
      items: [],
      value: null,
      placeholder: "",
      searchPlaceholder: "Search city",
      formatItem: fmtSimple,
      formatSelected: fmtSimple,
      emptyText: "Select a state first",
      disabled: true,
      onDisabledClick: highlightStateRequired,
      onChange: (it) => {
        syncSelect(citySelect, it.value, cityCbx._currentList || []);
        setFilled(cityWrap, true);
      }
    });

    const setCityLocked = (locked) => {
      cityCbx.setDisabled(locked);
      citySelect.disabled = locked;
      cityWrap.classList.toggle("is-locked", locked);
    };

    const setCities = (cities) => {
      const sorted = sortAlpha(cities);
      cityCbx._currentList = sorted;
      cityCbx.setItems(valueItems(sorted));
      setCityLocked(!stateSelect.value);
      const currentVal = citySelect.value;
      if (currentVal && sorted.includes(currentVal)) {
        syncSelect(citySelect, currentVal, sorted);
      } else {
        syncSelect(citySelect, "", sorted);
        cityCbx.setValue(null);
        setFilled(cityWrap, false);
      }
    };

    const stateNames = Object.keys(FALLBACK_CITIES_BY_STATE);
    const stateCbx = createCombobox({
      host: stateWrap,
      items: valueItems(sortAlpha(stateNames)),
      value: null,
      placeholder: "",
      searchPlaceholder: "Search state",
      formatItem: fmtSimple,
      formatSelected: fmtSimple,
      emptyText: "No state found",
      onChange: async (it) => {
        clearStateAlert();
        const state = it.value;
        syncSelect(stateSelect, state, stateCbx._currentList || sortAlpha(stateNames));
        setFilled(stateWrap, true);
        cityCbx.setValue(null);
        setFilled(cityWrap, false);

        const fallbackCities = FALLBACK_CITIES_BY_STATE[state] || [];
        const cached = readCache("cities_" + state);
        const initial = cached && cached.length ? cached : fallbackCities;
        const reqToken = ++activeRequestToken;
        setCities(initial);

        if (cached) return;

        try {
          const apiCities = await fetchCitiesFromApi(state);
          if (reqToken !== activeRequestToken) return;
          const merged = Array.from(new Set([...(apiCities || []), ...fallbackCities]));
          setCities(merged);
        } catch (_) {
          if (reqToken !== activeRequestToken) return;
          setCities(fallbackCities);
        }
      }
    });
    stateCbx._currentList = sortAlpha(stateNames);
    syncSelect(stateSelect, "", stateCbx._currentList);
    syncSelect(citySelect, "", []);
    setCityLocked(true);

    fetchStatesFromApi()
      .then((apiStates) => {
        if (!Array.isArray(apiStates) || apiStates.length < 20) return;
        const merged = sortAlpha(Array.from(new Set([...apiStates, ...stateNames])));
        stateCbx._currentList = merged;
        stateCbx.setItems(valueItems(merged));
      })
      .catch(() => {});
  }
});


(function () {
  function initCourseCarousels() {
    const carousels = Array.from(document.querySelectorAll("[data-course-carousel]"));
    if (!carousels.length) return;

    const reducedMotion = window.matchMedia
      ? window.matchMedia("(prefers-reduced-motion: reduce)")
      : { matches: false };

    carousels.forEach((carousel) => {
      const track = carousel.querySelector("[data-course-track]");
      const firstSet = track ? track.querySelector(".dynamic-course-set") : null;
      if (!track || !firstSet) return;

      let setWidth = 0;
      let offset = 0;
      let targetOffset = 0;
      let pointerStartX = 0;
      let pointerStartOffset = 0;
      let isDragging = false;
      let isHovering = false;
      let suppressClick = false;
      let suppressClickTimer = null;
      let resumeAt = 0;

      const normalize = (value) => {
        if (!setWidth) return value;
        return ((value % setWidth) + setWidth) % setWidth;
      };

      const render = () => {
        track.style.transform = "translate3d(" + (-offset).toFixed(2) + "px, 0, 0)";
      };

      const moveTo = (value) => {
        targetOffset = normalize(value);
        offset = targetOffset;
        render();
      };

      const pauseAuto = (duration) => {
        resumeAt = performance.now() + duration;
      };

      const measure = () => {
        setWidth = firstSet.getBoundingClientRect().width;
        moveTo(targetOffset);
      };

      const finishDrag = (event) => {
        if (!isDragging) return;
        isDragging = false;
        carousel.classList.remove("is-dragging");
        pauseAuto(1800);

        if (event && carousel.releasePointerCapture) {
          try {
            carousel.releasePointerCapture(event.pointerId);
          } catch (error) {}
        }

        if (suppressClick) {
          window.clearTimeout(suppressClickTimer);
          suppressClickTimer = window.setTimeout(() => {
            suppressClick = false;
          }, 220);
        }
      };

      carousel.classList.add("is-scroll-ready");
      measure();

      if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(measure);
      }

      window.addEventListener("resize", measure, { passive: true });

      let hoverCount = 0;
      const cards = Array.from(track.querySelectorAll(".dynamic-course-card"));
      cards.forEach((card) => {
        card.addEventListener("mouseenter", () => {
          hoverCount++;
          isHovering = true;
        });
        card.addEventListener("mouseleave", () => {
          hoverCount = Math.max(0, hoverCount - 1);
          isHovering = hoverCount > 0;
        });
        card.addEventListener("focusin", () => {
          hoverCount++;
          isHovering = true;
        });
        card.addEventListener("focusout", () => {
          hoverCount = Math.max(0, hoverCount - 1);
          isHovering = hoverCount > 0;
        });
      });

      carousel.addEventListener("pointerdown", (event) => {
        if (event.pointerType === "mouse" && event.button !== 0) return;

        isDragging = true;
        suppressClick = false;
        pointerStartX = event.clientX;
        pointerStartOffset = targetOffset;
        carousel.classList.add("is-dragging");
        pauseAuto(2200);

        if (carousel.setPointerCapture) {
          carousel.setPointerCapture(event.pointerId);
        }
      });

      carousel.addEventListener("pointermove", (event) => {
        if (!isDragging) return;

        const delta = pointerStartX - event.clientX;
        if (Math.abs(delta) > 4) suppressClick = true;
        moveTo(pointerStartOffset + delta);

        if (event.cancelable) {
          event.preventDefault();
        }
      });

      carousel.addEventListener("pointerup", finishDrag);
      carousel.addEventListener("pointercancel", finishDrag);
      carousel.addEventListener("lostpointercapture", finishDrag);

      carousel.addEventListener("click", (event) => {
        if (!suppressClick) return;
        event.preventDefault();
        event.stopPropagation();
        suppressClick = false;
      }, true);

      // Wheel / trackpad scrolling — let users scroll the row by hand. We only
      // act on horizontal-intent scrolling (trackpad swipe, or Shift+wheel), so
      // a normal vertical wheel still scrolls the page and is never trapped.
      carousel.addEventListener("wheel", (event) => {
        const horizontal = Math.abs(event.deltaX) > Math.abs(event.deltaY) || event.shiftKey;
        if (!horizontal) return;
        const delta = event.shiftKey && event.deltaX === 0 ? event.deltaY : event.deltaX;
        if (!delta) return;
        pauseAuto(1200);
        moveTo(targetOffset + delta);
        if (event.cancelable) event.preventDefault();
      }, { passive: false });

      // Manual scroll buttons — step by one card (+ gap) with an eased glide.
      let manualTransitionTimer = null;

      const cardStep = () => {
        const card = firstSet.querySelector(".dynamic-course-card");
        const styles = window.getComputedStyle(firstSet);
        const gap = parseFloat(styles.columnGap || styles.gap || "18") || 18;
        if (card) return card.getBoundingClientRect().width + gap;
        return setWidth ? setWidth / 3 : 0;
      };

      const animateBy = (delta) => {
        if (!setWidth || !delta) return;
        pauseAuto(1000);
        window.clearTimeout(manualTransitionTimer);

        // Snap to the normalized equivalent of the current position. The track
        // holds three identical sets, so this never shows a visible jump — but it
        // keeps rapid repeat clicks from drifting past the duplicated track and
        // exposing empty space.
        let from = normalize(targetOffset);
        // Stepping backward past the start would expose the empty left edge, so
        // hop forward by one identical set first to give the track room to reveal.
        if (delta < 0 && from + delta < 0) from += setWidth;

        track.style.transition = "none";
        offset = targetOffset = from;
        render();
        void track.offsetWidth; // commit the snapped position before transitioning

        const to = from + delta;
        track.style.transition = reducedMotion.matches
          ? "none"
          : "transform 520ms cubic-bezier(0.22, 1, 0.36, 1)";
        offset = targetOffset = to;
        render();

        manualTransitionTimer = window.setTimeout(() => {
          track.style.transition = "";
          targetOffset = normalize(to);
          offset = targetOffset;
          render();
        }, 540);
      };

      const controlScope = carousel.closest(".dynamic-courses-bleed") || carousel.parentElement;
      const prevBtn = controlScope ? controlScope.querySelector("[data-course-prev]") : null;
      const nextBtn = controlScope ? controlScope.querySelector("[data-course-next]") : null;
      if (prevBtn) prevBtn.addEventListener("click", () => animateBy(-cardStep()));
      if (nextBtn) nextBtn.addEventListener("click", () => animateBy(cardStep()));

      const tick = (now) => {
        if (!reducedMotion.matches && !isDragging && !isHovering && now > resumeAt) {
          moveTo(targetOffset + 0.504);
        }

        window.requestAnimationFrame(tick);
      };

      window.requestAnimationFrame(tick);
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initCourseCarousels, { once: true });
  } else {
    initCourseCarousels();
  }
})();

(function () {
  function initContactFab() {
    const fab = document.querySelector("[data-contact-fab]");
    if (!fab) return;

    const reduced = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    function measure() {
      // Temporarily disable transitions and force the expanded layout so we can
      // read the natural full-content width, then restore.
      const prevTransition = fab.style.transition;
      fab.style.transition = "none";
      fab.style.width = "auto";
      const w = Math.ceil(fab.getBoundingClientRect().width);
      fab.style.width = "";
      // Force a reflow before re-enabling transitions so the next change animates.
      void fab.offsetWidth;
      fab.style.transition = prevTransition;
      fab.style.setProperty("--contact-fab-expanded", w + "px");
    }

    // Measure once fonts are ready so the width matches the actual rendered label.
    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(measure);
    } else {
      measure();
    }
    window.addEventListener("resize", measure);

    if (reduced) return;

    const rand = (min, max) => Math.floor(min + Math.random() * (max - min));
    const EXPAND_MS = 2800;
    let timer = null;
    let userHovered = false;

    fab.addEventListener("mouseenter", () => { userHovered = true; });
    fab.addEventListener("mouseleave", () => { userHovered = false; });
    fab.addEventListener("focus", () => { userHovered = true; });
    fab.addEventListener("blur", () => { userHovered = false; });

    function schedule() {
      const delay = rand(7000, 16000);
      timer = window.setTimeout(expand, delay);
    }

    function expand() {
      if (document.hidden || userHovered) {
        schedule();
        return;
      }
      fab.classList.add("is-expanded");
      timer = window.setTimeout(collapse, EXPAND_MS);
    }

    function collapse() {
      fab.classList.remove("is-expanded");
      schedule();
    }

    document.addEventListener("visibilitychange", () => {
      if (document.hidden && timer) {
        window.clearTimeout(timer);
        timer = null;
        fab.classList.remove("is-expanded");
      } else if (!document.hidden && !timer) {
        schedule();
      }
    });

    schedule();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initContactFab, { once: true });
  } else {
    initContactFab();
  }

  function initHiringProcess() {
    const root = document.querySelector("[data-hiring-process]");
    if (!root) return;

    const steps = Array.from(root.querySelectorAll(".cr-rail-step"));
    const titleEl = root.querySelector("[data-step-title]");
    const bodyEl = root.querySelector("[data-step-body]");
    const metaEl = root.querySelector("[data-rail-meta]");
    const stageEl = root.querySelector("[data-rail-stage]");
    const fillEl = root.querySelector("[data-rail-fill]");
    const prevBtn = root.querySelector("[data-rail-prev]");
    const nextBtn = root.querySelector("[data-rail-next]");
    if (!steps.length || !titleEl || !bodyEl) return;

    const total = steps.length;
    let currentIdx = 0;

    const pad = (n) => String(n).padStart(2, "0");

    const activate = (idx) => {
      idx = Math.max(0, Math.min(total - 1, idx));
      currentIdx = idx;
      steps.forEach((s, i) => {
        const on = i === idx;
        const passed = i < idx;
        s.classList.toggle("is-active", on);
        s.classList.toggle("is-passed", passed);
        s.setAttribute("aria-selected", on ? "true" : "false");
      });
      const step = steps[idx];
      titleEl.textContent = step.dataset.title || "";
      bodyEl.innerHTML = step.dataset.body || "";
      if (metaEl) metaEl.innerHTML = step.dataset.meta || "";
      if (stageEl) stageEl.textContent = "Step " + pad(idx + 1) + " of " + pad(total);
      if (fillEl) {
        const pct = total > 1 ? (idx / (total - 1)) * 100 : 0;
        fillEl.style.width = pct + "%";
      }
      if (prevBtn) prevBtn.disabled = idx === 0;
      if (nextBtn) nextBtn.disabled = idx === total - 1;
    };

    steps.forEach((step, i) => {
      step.addEventListener("click", () => activate(i));
      step.addEventListener("focus", () => activate(i));
    });

    if (prevBtn) prevBtn.addEventListener("click", () => activate(currentIdx - 1));
    if (nextBtn) nextBtn.addEventListener("click", () => activate(currentIdx + 1));

    const initial = steps.findIndex((s) => s.classList.contains("is-active"));
    activate(initial >= 0 ? initial : 0);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initHiringProcess, { once: true });
  } else {
    initHiringProcess();
  }

  function initCountrySnapshotRail() {
    const rails = Array.from(document.querySelectorAll(".country-snapshot"));
    if (!rails.length) return;

    rails.forEach((rail) => {
      if (!rail.hasAttribute("tabindex")) rail.setAttribute("tabindex", "0");
      rail.setAttribute("role", "button");
      rail.setAttribute("aria-expanded", "false");

      rail.addEventListener("click", (e) => {
        const isOpen = rail.classList.toggle("is-open");
        rail.setAttribute("aria-expanded", isOpen ? "true" : "false");
        e.stopPropagation();
      });

      rail.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          rail.click();
        } else if (e.key === "Escape" && rail.classList.contains("is-open")) {
          rail.classList.remove("is-open");
          rail.setAttribute("aria-expanded", "false");
        }
      });
    });

    document.addEventListener("click", (e) => {
      document.querySelectorAll(".country-snapshot.is-open").forEach((rail) => {
        if (!rail.contains(e.target)) {
          rail.classList.remove("is-open");
          rail.setAttribute("aria-expanded", "false");
        }
      });
    });

    const parseRgb = (str) => {
      if (!str) return null;
      const m = str.match(/[\d.]+/g);
      if (!m || m.length < 3) return null;
      const [r, g, b, a] = m.map(Number);
      return { r, g, b, a: a == null ? 1 : a };
    };

    const getOpaqueBg = (el) => {
      let node = el;
      while (node && node !== document.documentElement) {
        const c = parseRgb(getComputedStyle(node).backgroundColor);
        if (c && c.a > 0.5) return c;
        node = node.parentElement;
      }
      return { r: 255, g: 255, b: 255, a: 1 };
    };

    const isLight = (c) => (0.299 * c.r + 0.587 * c.g + 0.114 * c.b) > 165;

    // Runs on every scroll frame, so it is split into phases instead of doing
    // read → write → read → write per rail. Interleaved like that, each rail's
    // class write invalidated layout for the next rail's getBoundingClientRect,
    // forcing a synchronous reflow per rail per frame. Same probing behaviour
    // (one rail's pointer-events suppressed at a time, same probe point), just
    // with every measurement taken before any style is written.
    const updateRailContrast = () => {
      const probes = [];

      // Phase 1 — measure only.
      rails.forEach((rail) => {
        if (rail.closest(".country-hero")) {
          rail.classList.remove("on-light");
          return;
        }
        const rect = rail.getBoundingClientRect();
        probes.push({
          rail,
          x: Math.max(2, rect.left - 12),
          y: rect.top + rect.height / 2,
        });
      });
      if (!probes.length) return;

      // Phase 2 — hit-test. pointer-events is not a layout property, so
      // toggling it here does not dirty the geometry read above.
      probes.forEach((p) => {
        const prevPe = p.rail.style.pointerEvents;
        p.rail.style.pointerEvents = "none";
        p.behind = document.elementFromPoint(p.x, p.y);
        p.rail.style.pointerEvents = prevPe;
      });

      // Phase 3 — resolve colours, reusing the answer for a shared ancestor.
      const bgCache = new Map();
      probes.forEach((p) => {
        if (!p.behind) return;
        if (!bgCache.has(p.behind)) bgCache.set(p.behind, getOpaqueBg(p.behind));
        p.light = isLight(bgCache.get(p.behind));
      });

      // Phase 4 — write.
      probes.forEach((p) => {
        if (p.light === undefined) return;
        p.rail.classList.toggle("on-light", p.light);
      });
    };

    let ticking = false;
    const schedule = () => {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(() => {
        updateRailContrast();
        ticking = false;
      });
    };

    window.addEventListener("scroll", schedule, { passive: true });
    window.addEventListener("resize", schedule);
    schedule();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initCountrySnapshotRail, { once: true });
  } else {
    initCountrySnapshotRail();
  }
})();

/* Color theme switcher removed — "Logo Colours" (cream) is now the only theme,
   hardcoded as data-color-theme="cream" on <html> in the layout. */

/* The One Degree Method — interactive 4D compass on the home page. The four "D"
   stages (cardinal points) and the two curved threads inside the ring all carry
   a [data-odm-key]; selecting one highlights it and swaps the detail panel. */
(function () {
  const initMethodCompass = () => {
    const root = document.querySelector("[data-odm]");
    if (!root) return;

    const triggers = Array.from(root.querySelectorAll("[data-odm-key]"));
    const panels = Array.from(root.querySelectorAll("[data-odm-panel]"));
    if (!triggers.length || !panels.length) return;

    const activate = (key) => {
      triggers.forEach((el) => {
        const on = el.getAttribute("data-odm-key") === key;
        el.classList.toggle("is-active", on);
        if (el.tagName === "BUTTON" || el.getAttribute("role") === "button") {
          el.setAttribute("aria-pressed", String(on));
        }
      });
      panels.forEach((panel) => {
        panel.classList.toggle("is-active", panel.getAttribute("data-odm-panel") === key);
      });
    };

    triggers.forEach((el) => {
      el.addEventListener("click", (event) => {
        event.preventDefault();
        activate(el.getAttribute("data-odm-key"));
      });
      // SVG <a role="button"> needs explicit keyboard activation; native
      // <button> already fires click on Enter/Space, so only bind the anchors.
      if (el.tagName !== "BUTTON") {
        el.addEventListener("keydown", (event) => {
          if (event.key === "Enter" || event.key === " " || event.key === "Spacebar") {
            event.preventDefault();
            activate(el.getAttribute("data-odm-key"));
          }
        });
      }
    });

    const defaultPanel = root.querySelector("[data-odm-default]") || panels[0];
    activate(defaultPanel.getAttribute("data-odm-panel"));
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initMethodCompass, { once: true });
  } else {
    initMethodCompass();
  }
})();
