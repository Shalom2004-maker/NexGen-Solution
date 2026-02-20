(function () {
  var THEME_KEY = "nexgen_future_theme";
  var SUPPORTED_THEMES = {
    nebula: true,
    ember: true,
    aurora: true
  };
  var parallaxBound = false;

  function normalizeTheme(theme, fallback) {
    return SUPPORTED_THEMES[theme] ? theme : fallback;
  }

  function safeReadTheme(defaultTheme) {
    try {
      var saved = window.localStorage.getItem(THEME_KEY);
      return normalizeTheme(saved, defaultTheme);
    } catch (error) {
      return defaultTheme;
    }
  }

  function safeWriteTheme(theme) {
    try {
      window.localStorage.setItem(THEME_KEY, theme);
    } catch (error) {
      return;
    }
  }

  function applyTheme(body, theme, buttons, fallbackTheme) {
    var nextTheme = normalizeTheme(theme, fallbackTheme);
    body.dataset.theme = nextTheme;

    buttons.forEach(function (button) {
      var active = button.dataset.themeChoice === nextTheme;
      button.classList.toggle("is-active", active);
      button.setAttribute("aria-pressed", String(active));
    });

    safeWriteTheme(nextTheme);
  }

  function initThemeSwitcher(body) {
    if (!body) {
      return;
    }

    var buttons = Array.prototype.slice.call(document.querySelectorAll("[data-theme-choice]"));
    var defaultTheme = normalizeTheme(body.dataset.theme || "nebula", "nebula");
    var activeTheme = safeReadTheme(defaultTheme);
    applyTheme(body, activeTheme, buttons, defaultTheme);

    buttons.forEach(function (button) {
      if (button.dataset.futureThemeBound === "1") {
        return;
      }

      button.addEventListener("click", function () {
        applyTheme(body, button.dataset.themeChoice || defaultTheme, buttons, defaultTheme);
      });

      button.dataset.futureThemeBound = "1";
    });
  }

  function registerPressDepth() {
    var pressables = Array.prototype.slice.call(document.querySelectorAll(".pressable"));

    pressables.forEach(function (element) {
      if (element.dataset.futurePressBound === "1") {
        return;
      }

      function pressOn() {
        element.classList.add("is-pressed");
      }

      function pressOff() {
        element.classList.remove("is-pressed");
      }

      element.addEventListener("pointerdown", pressOn);
      element.addEventListener("pointerup", pressOff);
      element.addEventListener("pointerleave", pressOff);
      element.addEventListener("pointercancel", pressOff);
      element.addEventListener("blur", pressOff);

      element.addEventListener("keydown", function (event) {
        if (event.key === "Enter" || event.key === " ") {
          pressOn();
        }
      });

      element.addEventListener("keyup", function (event) {
        if (event.key === "Enter" || event.key === " ") {
          pressOff();
        }
      });

      element.dataset.futurePressBound = "1";
    });
  }

  function registerTiltAndGlow() {
    var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (reduceMotion) {
      return;
    }

    var tiltTargets = Array.prototype.slice.call(document.querySelectorAll("[data-tilt]"));

    tiltTargets.forEach(function (element) {
      if (element.dataset.futureTiltBound === "1") {
        return;
      }

      var strength = Number(element.dataset.tilt || 7);

      function reset() {
        element.style.setProperty("--tilt-x", "0deg");
        element.style.setProperty("--tilt-y", "0deg");
        element.classList.remove("is-hovered");
      }

      element.addEventListener("pointerenter", function () {
        element.classList.add("is-hovered");
      });

      element.addEventListener("pointermove", function (event) {
        var rect = element.getBoundingClientRect();
        var x = event.clientX - rect.left;
        var y = event.clientY - rect.top;
        var px = rect.width ? x / rect.width : 0.5;
        var py = rect.height ? y / rect.height : 0.5;
        var rotateX = (0.5 - py) * strength;
        var rotateY = (px - 0.5) * strength;

        element.style.setProperty("--tilt-x", rotateX.toFixed(2) + "deg");
        element.style.setProperty("--tilt-y", rotateY.toFixed(2) + "deg");
        element.style.setProperty("--glow-x", (px * 100).toFixed(1) + "%");
        element.style.setProperty("--glow-y", (py * 100).toFixed(1) + "%");
      });

      element.addEventListener("pointerleave", reset);
      element.addEventListener("pointercancel", reset);
      element.addEventListener("blur", reset);

      element.dataset.futureTiltBound = "1";
    });
  }

  function registerParallax() {
    if (parallaxBound) {
      return;
    }

    var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (reduceMotion) {
      return;
    }

    var orbs = Array.prototype.slice.call(document.querySelectorAll(".future-orb"));
    if (!orbs.length) {
      return;
    }

    var targetX = 0;
    var targetY = 0;
    var rafId = null;

    function paint() {
      for (var i = 0; i < orbs.length; i += 1) {
        var depth = (i + 1) * 10;
        var tx = (targetX * depth).toFixed(2);
        var ty = (targetY * depth).toFixed(2);
        orbs[i].style.transform = "translate(" + tx + "px, " + ty + "px)";
      }

      rafId = null;
    }

    function queuePaint() {
      if (rafId !== null) {
        return;
      }

      rafId = window.requestAnimationFrame(paint);
    }

    window.addEventListener("pointermove", function (event) {
      var normalizedX = event.clientX / window.innerWidth - 0.5;
      var normalizedY = event.clientY / window.innerHeight - 0.5;

      targetX = -normalizedX;
      targetY = -normalizedY;
      queuePaint();
    }, { passive: true });

    window.addEventListener("blur", function () {
      targetX = 0;
      targetY = 0;
      queuePaint();
    });

    parallaxBound = true;
  }

  function initHomeNavEnhancements() {
    var navMenus = Array.prototype.slice.call(document.querySelectorAll("details.home-nav-menu"));
    var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    var anchorLinks = Array.prototype.slice.call(document.querySelectorAll(".navbar-actions a[href^='#']"));

    function closeMenus() {
      navMenus.forEach(function (menu) {
        menu.open = false;
      });
    }

    navMenus.forEach(function (menu) {
      if (menu.dataset.futureMenuBound === "1") {
        return;
      }

      var menuLinks = Array.prototype.slice.call(menu.querySelectorAll("a[href]"));
      menuLinks.forEach(function (link) {
        link.addEventListener("click", function () {
          menu.open = false;
        });
      });

      menu.dataset.futureMenuBound = "1";
    });

    anchorLinks.forEach(function (link) {
      if (link.dataset.futureSmoothBound === "1") {
        return;
      }

      link.addEventListener("click", function (event) {
        var targetSelector = link.getAttribute("href");
        if (!targetSelector || targetSelector.length < 2) {
          return;
        }

        var target = document.querySelector(targetSelector);
        if (!target) {
          return;
        }

        event.preventDefault();
        target.scrollIntoView({
          behavior: reduceMotion ? "auto" : "smooth",
          block: "start"
        });

        if (window.history && typeof window.history.pushState === "function") {
          window.history.pushState(null, "", targetSelector);
        } else {
          window.location.hash = targetSelector;
        }

        closeMenus();
      });

      link.dataset.futureSmoothBound = "1";
    });
  }

  function isFutureContext(body) {
    return !!body && (body.classList.contains("future-page") || !!document.querySelector("[data-theme-choice]"));
  }

  function initializeFutureUi() {
    var body = document.body;
    if (!isFutureContext(body)) {
      return false;
    }

    initThemeSwitcher(body);
    registerPressDepth();
    registerTiltAndGlow();
    registerParallax();
    initHomeNavEnhancements();
    return true;
  }

  function waitForFutureContext() {
    if (initializeFutureUi()) {
      return;
    }

    var observer = new MutationObserver(function () {
      if (initializeFutureUi()) {
        observer.disconnect();
      }
    });

    observer.observe(document.documentElement, {
      subtree: true,
      childList: true,
      attributes: true,
      attributeFilter: ["class", "data-theme"]
    });

    window.setTimeout(function () {
      observer.disconnect();
    }, 12000);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", waitForFutureContext, { once: true });
  } else {
    waitForFutureContext();
  }

  document.addEventListener("future-ui:init", initializeFutureUi);
})();
