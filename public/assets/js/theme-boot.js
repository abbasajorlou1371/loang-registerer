(function () {
  var saved = localStorage.getItem("lr-theme");
  var prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
  if (saved === "dark" || (!saved && prefersDark)) {
    document.documentElement.classList.add("dark");
  }

  if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    document.documentElement.classList.add("is-reduced-motion");
  } else {
    document.documentElement.classList.add("js-motion");
    window.setTimeout(function () {
      document.querySelectorAll("[data-enter], [data-success-tracking]").forEach(function (el) {
        el.classList.add("is-entered");
      });
    }, 2200);
  }
})();
