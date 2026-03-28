(function () {
  "use strict";

  function isNavigatingLink(anchor, event) {
    if (!anchor || !anchor.href) return false;
    if (anchor.hasAttribute("download")) return false;
    if ((anchor.getAttribute("target") || "").toLowerCase() === "_blank") return false;

    const rawHref = (anchor.getAttribute("href") || "").trim();
    if (!rawHref || rawHref === "#" || rawHref.startsWith("javascript:")) return false;
    if (rawHref.startsWith("mailto:") || rawHref.startsWith("tel:")) return false;
    if (rawHref.toLowerCase().includes("logout")) return false;
    if ((anchor.className || "").toLowerCase().includes("logout")) return false;

    if (event.defaultPrevented) return false;
    if (event.button !== 0) return false;
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;

    try {
      const url = new URL(anchor.href, window.location.href);
      if (url.origin !== window.location.origin) return false;
      if (url.href === window.location.href) return false;
      if (url.pathname.toLowerCase().includes("logout")) return false;
    } catch (_) {
      return false;
    }

    return true;
  }

  function createOverlay() {
    let overlay = document.getElementById("pageLoadingOverlay");
    if (overlay) return overlay;

    overlay = document.createElement("div");
    overlay.id = "pageLoadingOverlay";
    overlay.className = "page-loading-overlay";
    overlay.innerHTML =
      '<div class="page-loading-spinner" aria-hidden="true"></div>' +
      '<div class="page-loading-text">Loading...</div>';
    document.body.appendChild(overlay);
    return overlay;
  }

  function injectStyles() {
    if (document.getElementById("pageLoadingStyles")) return;
    const style = document.createElement("style");
    style.id = "pageLoadingStyles";
    style.textContent =
      ".page-loading-overlay{position:fixed;inset:0;display:none;align-items:center;justify-content:center;flex-direction:column;gap:12px;background:rgba(235, 235, 235, 0.86);backdrop-filter:blur(1px);z-index:99999}" +
      ".page-loading-overlay.active{display:flex}" +
      ".page-loading-spinner{width:42px;height:42px;border:4px solid #dbe3f4;border-top-color:#233a8b;border-radius:50%;animation:pageLoadingSpin .8s linear infinite}" +
      ".page-loading-text{font:600 14px Arial,Helvetica,sans-serif;color:#233a8b;letter-spacing:.2px}" +
      "@keyframes pageLoadingSpin{to{transform:rotate(360deg)}}";
    document.head.appendChild(style);
  }

  function showLoading() {
    const overlay = createOverlay();
    overlay.classList.add("active");
  }

  function hideLoading() {
    const el = document.getElementById("pageLoadingOverlay");
    if (el) el.classList.remove("active");
  }

  window.hideEmployerPageLoading = hideLoading;

  /** Skill Registry (skill.php): forms are AJAX + preventDefault in bubble phase; capture-phase submit would show overlay and it never clears (no full navigation). */
  function skipSubmitLoadingForSkillRegistry() {
    try {
      return (window.location.pathname || "").toLowerCase().includes("skill.php");
    } catch (_) {
      return false;
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    injectStyles();
    const overlay = createOverlay();

    document.addEventListener(
      "click",
      function (event) {
        const anchor = event.target.closest("a");
        if (isNavigatingLink(anchor, event)) {
          showLoading();
        }
      },
      true
    );

    document.addEventListener(
      "submit",
      function (event) {
        if (skipSubmitLoadingForSkillRegistry()) return;
        const form = event.target;
        const action = ((form && form.getAttribute("action")) || "").toLowerCase();
        if (action.includes("logout")) return;
        if (form && form.tagName === "FORM" && !event.defaultPrevented) {
          showLoading();
        }
      },
      true
    );

    window.addEventListener("pageshow", function () {
      overlay.classList.remove("active");
    });

    if (skipSubmitLoadingForSkillRegistry()) {
      hideLoading();
    }
  });
})();
