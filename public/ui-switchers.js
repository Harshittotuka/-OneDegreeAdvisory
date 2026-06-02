/* ============================================================================
   Floating preview switchers (demo/compare aids)
   ----------------------------------------------------------------------------
   1. Nav content — flip the Stripe nav between its CURRENT layout and the
      UPDATED layout (Services removed, courses reworked, destinations trimmed).
      Toggles `html.nav-updated`. Persisted as "oda:nav-content".
   2. Top bar — switch the blue notice bar between three variants:
      original | minimal (no socials/WhatsApp) | compact (WhatsApp icon left).
      Toggles `html.topbar-minimal` / `html.topbar-compact`. Persisted as
      "oda:topbar".

   The persisted choices are applied EARLY by the inline boot script in the
   layout <head> (to avoid a flash); this file only wires the click handlers
   and keeps the buttons' aria-pressed state in sync.
   ========================================================================== */
(function () {
  var root = document.documentElement;
  var refreshIcons = function () {
    if (window.lucide) window.lucide.createIcons();
  };

  function bindSwitch(opts) {
    var buttons = Array.prototype.slice.call(
      document.querySelectorAll("[" + opts.attr + "]")
    );
    if (!buttons.length) return;

    function sync(value) {
      buttons.forEach(function (b) {
        b.setAttribute(
          "aria-pressed",
          String(b.getAttribute(opts.attr) === value)
        );
      });
    }

    function apply(value, persist) {
      opts.set(value);
      if (persist) {
        try {
          localStorage.setItem(opts.key, value);
        } catch (e) {}
      }
      sync(value);
      refreshIcons();
    }

    buttons.forEach(function (b) {
      b.addEventListener("click", function () {
        apply(b.getAttribute(opts.attr), true);
      });
    });

    // Reflect whatever value the boot script already applied.
    var stored = null;
    try {
      stored = localStorage.getItem(opts.key);
    } catch (e) {}
    sync(stored || opts.fallback);
  }

  // 1. Nav content — Current vs Updated
  bindSwitch({
    attr: "data-nav-content-option",
    key: "oda:nav-content",
    fallback: "current",
    set: function (v) {
      root.classList.toggle("nav-updated", v === "updated");
    },
  });

  // 2. Top bar — Original / Minimal / Compact
  bindSwitch({
    attr: "data-topbar-option",
    key: "oda:topbar",
    fallback: "original",
    set: function (v) {
      root.classList.toggle("topbar-minimal", v === "minimal");
      root.classList.toggle("topbar-compact", v === "compact");
    },
  });
})();
