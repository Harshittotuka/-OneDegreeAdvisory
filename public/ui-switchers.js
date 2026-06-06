/* ============================================================================
   Floating preview switcher (demo/compare aid)
   ----------------------------------------------------------------------------
   Nav content — flip the Stripe nav between its CURRENT layout and the
   UPDATED layout (Services removed, courses reworked, destinations trimmed).
   Toggles `html.nav-updated`. Persisted as "oda:nav-content".

   The persisted choice is applied EARLY by the inline boot script in the
   layout <head> (to avoid a flash); this file only wires the click handlers
   and keeps the buttons' aria-pressed state in sync.

   (The top-bar variant used to live here too, but it now lives in the CMS at
   /admin/notice-bar and is applied server-side as html.topbar-*.)
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

  // Nav content — Current vs Updated
  bindSwitch({
    attr: "data-nav-content-option",
    key: "oda:nav-content",
    fallback: "current",
    set: function (v) {
      root.classList.toggle("nav-updated", v === "updated");
    },
  });
})();
