(() => {
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const anime = window.anime;
  const hasAnime = typeof anime === "function";

  const revealNow = (els) => {
    els.forEach((el) => {
      el.classList.add("is-entered", "is-visible");
      el.style.opacity = "";
      el.style.transform = "";
    });
  };

  const header = document.querySelector("[data-site-header]");
  if (header) {
    const onScroll = () => {
      header.classList.toggle("is-scrolled", window.scrollY > 8);
    };
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
  }

  if (reduceMotion || !hasAnime) {
    revealNow([...document.querySelectorAll("[data-enter], .reveal")]);
    return;
  }

  const enterEls = [...document.querySelectorAll("[data-enter]")].sort(
    (a, b) => Number(a.dataset.enter || 0) - Number(b.dataset.enter || 0)
  );

  if (enterEls.length) {
    anime({
      targets: enterEls,
      opacity: [0, 1],
      translateY: [14, 0],
      duration: 560,
      delay: anime.stagger(70),
      easing: "easeOutCubic",
      complete() {
        revealNow(enterEls);
      },
    });
  }

  window.setTimeout(() => revealNow(enterEls), 1800);

  const revealEls = [...document.querySelectorAll(".reveal")];
  if (revealEls.length && "IntersectionObserver" in window) {
    const observer = new IntersectionObserver(
      (entries) => {
        const visible = entries
          .filter((entry) => entry.isIntersecting)
          .map((entry) => entry.target);
        if (!visible.length) return;

        visible.forEach((el) => observer.unobserve(el));
        anime({
          targets: visible,
          opacity: [0, 1],
          translateY: [18, 0],
          scale: [0.985, 1],
          duration: 540,
          delay: anime.stagger(70),
          easing: "easeOutCubic",
          complete() {
            revealNow(visible);
          },
        });
      },
      { threshold: 0.16, rootMargin: "0px 0px -8% 0px" }
    );
    revealEls.forEach((el) => observer.observe(el));
  } else {
    revealNow(revealEls);
  }

  const pressSel = ".btn-primary, .btn-secondary, .icon-btn";
  const resetScale = (btn) => {
    anime.remove(btn);
    anime({
      targets: btn,
      scale: 1,
      duration: 160,
      easing: "easeOutElastic(1, 0.7)",
    });
  };

  document.addEventListener("pointerdown", (event) => {
    const btn = event.target.closest(pressSel);
    if (!btn) return;
    anime.remove(btn);
    anime({
      targets: btn,
      scale: 0.98,
      duration: 80,
      easing: "easeOutQuad",
    });
  });

  document.addEventListener("pointerup", (event) => {
    const btn = event.target.closest(pressSel);
    if (btn) resetScale(btn);
  });

  document.addEventListener("pointercancel", (event) => {
    const btn = event.target.closest(pressSel);
    if (btn) resetScale(btn);
  });

  const themeToggle = document.querySelector("[data-theme-toggle]");
  themeToggle?.addEventListener("click", () => {
    anime.remove(themeToggle);
    anime({
      targets: themeToggle,
      rotate: [0, 180],
      duration: 420,
      easing: "easeOutCubic",
      complete() {
        themeToggle.style.transform = "";
      },
    });
  });

  const seal = document.querySelector("[data-success-seal]");
  if (seal) {
    const ring = seal.querySelector(".success-seal-ring");
    const check = seal.querySelector(".success-seal-check");
    const tracking = document.querySelector("[data-success-tracking]");

    if (ring && check) {
      const ringLen = ring.getTotalLength();
      const checkLen = check.getTotalLength();
      ring.setAttribute("stroke-dasharray", String(ringLen));
      ring.setAttribute("stroke-dashoffset", String(ringLen));
      check.setAttribute("stroke-dasharray", String(checkLen));
      check.setAttribute("stroke-dashoffset", String(checkLen));

      anime
        .timeline({ easing: "easeOutCubic" })
        .add({
          targets: ring,
          strokeDashoffset: [ringLen, 0],
          duration: 520,
        })
        .add(
          {
            targets: check,
            strokeDashoffset: [checkLen, 0],
            duration: 420,
          },
          "-=180"
        );

      if (tracking) {
        anime({
          targets: tracking,
          opacity: [0, 1],
          translateY: [10, 0],
          delay: 420,
          duration: 480,
          easing: "easeOutCubic",
          complete() {
            tracking.classList.add("is-entered");
            tracking.style.opacity = "";
            tracking.style.transform = "";
          },
        });
      }
    }
  }
})();
