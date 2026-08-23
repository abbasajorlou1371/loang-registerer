(() => {
  const root = document.documentElement;
  const themeToggle = document.querySelector("[data-theme-toggle]");
  const navToggle = document.querySelector("[data-nav-toggle]");
  const mobileNav = document.querySelector("[data-mobile-nav]");
  const yearEl = document.querySelector("[data-year]");

  if (yearEl) {
    yearEl.textContent = new Intl.DateTimeFormat("fa-IR", { year: "numeric" }).format(new Date());
  }

  const toEnDigits = (value) =>
    String(value || "").replace(/[۰-۹٠-٩]/g, (digit) => {
      const persian = "۰۱۲۳۴۵۶۷۸۹";
      const arabic = "٠١٢٣٤٥٦٧٨٩";
      const p = persian.indexOf(digit);
      if (p >= 0) return String(p);
      const a = arabic.indexOf(digit);
      return a >= 0 ? String(a) : digit;
    });

  const formatThousands = (value) => {
    const digits = toEnDigits(value).replace(/\D/g, "");
    if (!digits) return "";
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  };

  document.querySelectorAll("[data-thousands], .js-thousands").forEach((input) => {
    const applyFormat = () => {
      input.value = formatThousands(input.value);
    };
    applyFormat();
    input.addEventListener("input", applyFormat);
    input.addEventListener("blur", applyFormat);
  });

  if (window.jQuery?.fn?.mask) {
    window.jQuery('input[data-mask="mobile"], input[name="mobile"]').mask("00000000000", {
      placeholder: "09123456789",
    });
  }

  const applyTheme = (theme) => {
    const isDark = theme === "dark";
    root.classList.toggle("dark", isDark);
    localStorage.setItem("lr-theme", theme);

    const darkIcon = themeToggle?.querySelector('[data-theme-icon="dark"]');
    const lightIcon = themeToggle?.querySelector('[data-theme-icon="light"]');

    darkIcon?.classList.toggle("hidden", isDark);
    lightIcon?.classList.toggle("hidden", !isDark);

    if (themeToggle) {
      themeToggle.setAttribute("aria-pressed", isDark ? "true" : "false");
      themeToggle.setAttribute(
        "aria-label",
        isDark ? "فعال‌سازی حالت روشن" : "فعال‌سازی حالت تاریک"
      );
      themeToggle.setAttribute("title", isDark ? "حالت روشن" : "حالت تاریک");
    }
  };

  const savedTheme = localStorage.getItem("lr-theme");
  if (savedTheme === "light" || savedTheme === "dark") {
    applyTheme(savedTheme);
  } else {
    applyTheme(root.classList.contains("dark") ? "dark" : "light");
  }

  themeToggle?.addEventListener("click", () => {
    const next = root.classList.contains("dark") ? "light" : "dark";
    applyTheme(next);
  });

  window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", (event) => {
    if (localStorage.getItem("lr-theme")) return;
    applyTheme(event.matches ? "dark" : "light");
  });

  navToggle?.addEventListener("click", () => {
    const open = mobileNav?.classList.toggle("hidden") === false;
    navToggle.setAttribute("aria-expanded", open ? "true" : "false");
  });


  document.querySelectorAll("[data-smooth]").forEach((link) => {
    link.addEventListener("click", (event) => {
      const href = link.getAttribute("href");
      if (!href || !href.startsWith("#")) return;
      const target = document.querySelector(href);
      if (!target) return;
      event.preventDefault();
      target.scrollIntoView({ behavior: "smooth", block: "start" });
      if (mobileNav && !mobileNav.classList.contains("hidden")) {
        mobileNav.classList.add("hidden");
        navToggle?.setAttribute("aria-expanded", "false");
      }
    });
  });

  const calculatorRoot = document.querySelector("[data-calculator]");
  if (calculatorRoot) {
    const form = calculatorRoot.querySelector("[data-calc-form]");
    const configEl = document.getElementById("calc-config");
    let config = {};
    try {
      config = JSON.parse(configEl?.textContent || "{}");
    } catch (error) {
      config = {};
    }

    const faNumber = (value) =>
      String(value).replace(/[0-9]/g, (digit) => "۰۱۲۳۴۵۶۷۸۹"[digit]);

    const formatAmount = (amount) =>
      `${faNumber(Math.round(amount).toLocaleString("en-US"))} تومان`;

    const formatToman = (amount) => {
      const rounded = Math.round(amount);
      if (rounded >= 1_000_000) {
        const millions = rounded / 1_000_000;
        const label = Number.isInteger(millions)
          ? faNumber(String(millions))
          : faNumber(millions.toFixed(1));
        return `${label} میلیون تومان`;
      }
      return formatAmount(rounded);
    };

    const ratioForMonths = (rows, months) => {
      if (!Array.isArray(rows) || rows.length === 0) return 1;
      const sorted = [...rows].sort((a, b) => Number(a.months) - Number(b.months));
      let selected = Number(sorted[0].ratio);
      sorted.forEach((row) => {
        if (months >= Number(row.months)) selected = Number(row.ratio);
      });
      return selected;
    };

    const readNumber = (name, fallback = 0) => {
      const field = form?.querySelector(`[data-calc-input="${name}"]`);
      if (!field) return fallback;
      if (field.type === "radio") {
        const checked = form.querySelector(`[data-calc-input="${name}"]:checked`);
        if (!checked) return fallback;
        const parsed = Number(toEnDigits(String(checked.value || "")));
        return Number.isFinite(parsed) ? parsed : fallback;
      }
      if (field.type === "checkbox") return field.checked ? 1 : 0;
      const raw = toEnDigits(String(field.value || "")).replace(/[^\d.-]/g, "");
      if (raw === "" || raw === "-" || raw === ".") return fallback;
      const parsed = Number(raw);
      return Number.isFinite(parsed) ? parsed : fallback;
    };

    const mehrabaniInstallment = (amount, feePct, months) => {
      if (months < 1 || amount <= 0) return { installment: 0, total: 0, interest: 0 };
      const rate = feePct / 100;
      if (rate <= 0) {
        return { installment: amount / months, total: amount, interest: 0 };
      }

      const feeMonthCount = Math.ceil(months / 12);
      const principalMonths = months - feeMonthCount;
      if (principalMonths < 1) {
        const fee = amount * rate * (months / 12);
        const total = amount + fee;
        return { installment: total / months, total, interest: fee };
      }

      const monthlyPrincipal = amount / principalMonths;
      let principalLeft = principalMonths;
      let remaining = amount;
      let totalFee = 0;
      let cursor = 0;

      while (cursor < months) {
        const block = Math.min(12, months - cursor);
        totalFee += remaining * rate * (block / 12);
        const principalInBlock = Math.min(Math.max(block - 1, 0), principalLeft);
        principalLeft -= principalInBlock;
        remaining = principalLeft > 0 ? amount * (principalLeft / principalMonths) : 0;
        cursor += block;
      }

      return {
        installment: monthlyPrincipal,
        total: amount + totalFee,
        interest: totalFee,
      };
    };

    const etebarInstallment = (amount, annualRatePct, months) => {
      if (months < 1 || amount <= 0) return { installment: 0, total: 0, interest: 0 };
      const monthlyRate = annualRatePct / 100 / 12;
      if (monthlyRate <= 0) {
        return { installment: amount / months, total: amount, interest: 0 };
      }
      const pow = (1 + monthlyRate) ** months;
      const installment = (amount * monthlyRate * pow) / (pow - 1);
      const total = installment * months;
      return { installment, total, interest: total - amount };
    };

    const estimateEligible = (productConfig, inputs) => {
      if (!productConfig) return 0;
      const max = Number(productConfig.max || 0);
      const min = Number(productConfig.min || 0);
      if (max <= 0 || !inputs.hasAccount || inputs.balance <= 0) return 0;
      const activityRatio = ratioForMonths(productConfig.activity_ratios, Math.max(inputs.activity, 1));
      const termRows = productConfig.term_ratios || [];
      const termRatio = termRows.length > 0
        ? ratioForMonths(termRows, Math.max(inputs.months, 1))
        : 1;
      let estimated = Math.round(inputs.balance * activityRatio * termRatio);
      estimated = Math.min(estimated, max);
      if (estimated > 0 && estimated < min) return 0;
      return Math.max(0, estimated);
    };

    const render = () => {
      const tab = form?.getAttribute("data-calc-tab") || "general";
      const amount = readNumber("amount");
      const months = readNumber("months", 24);
      const balance = readNumber("average_balance");
      const activity = readNumber("activity_months", 12);
      const hasAccount = readNumber("has_account", 1) === 1;
      let calc = { installment: 0, total: 0, interest: 0 };
      let eligible = 0;
      let note = "مقادیر را وارد کنید تا برآورد به‌روز شود.";

      if (tab === "mehrabani-melli") {
        calc = mehrabaniInstallment(amount, readNumber("fee", 2), months);
        eligible = estimateEligible(config.mehrabani, { balance, activity, months, hasAccount });
        note = "قسط نمایش‌داده‌شده مربوط به ماه‌های بازپرداخت اصل است. کارمزد قرض‌الحسنه در ابتدای هر سال روی مانده بدهی محاسبه می‌شود.";
      } else if (tab === "etebar-melli") {
        calc = etebarInstallment(amount, readNumber("rate", 18), months);
        eligible = estimateEligible(config.etebar, { balance, activity, months, hasAccount });
        note = "سقف برآوردی به گردش حساب و مدت بازپرداخت وابسته است.";
      } else {
        calc = etebarInstallment(amount, readNumber("rate", 18), months);
        eligible = amount;
        note = "این محاسبه عمومی است و به محصول خاصی وابسته نیست.";
      }

      const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
      const canAnimate = typeof window.anime === "function" && !reduceMotion;

      const setText = (key, value) => {
        const el = calculatorRoot.querySelector(`[data-out="${key}"]`);
        if (el) el.textContent = value;
      };

      const setAnimatedAmount = (key, nextValue, formatter) => {
        const el = calculatorRoot.querySelector(`[data-out="${key}"]`);
        if (!el) return;

        if (!(nextValue > 0)) {
          if (el._calcAnime) {
            el._calcAnime.pause();
            el._calcAnime = null;
          }
          el._calcValue = 0;
          el.textContent = "—";
          return;
        }

        const from = Number(el._calcValue || 0);
        if (!canAnimate || from === nextValue) {
          el._calcValue = nextValue;
          el.textContent = formatter(nextValue);
          return;
        }

        if (el._calcAnime) el._calcAnime.pause();
        const state = { n: from };
        el._calcAnime = window.anime({
          targets: state,
          n: nextValue,
          duration: 480,
          easing: "easeOutQuad",
          update() {
            el.textContent = formatter(state.n);
          },
          complete() {
            el._calcValue = nextValue;
            el.textContent = formatter(nextValue);
          },
        });
      };

      setAnimatedAmount("eligible", amount > 0 || eligible > 0 ? eligible : 0, formatToman);
      setAnimatedAmount("installment", amount > 0 ? calc.installment : 0, formatAmount);
      setAnimatedAmount("total", amount > 0 ? calc.total : 0, formatAmount);
      setAnimatedAmount("interest", amount > 0 ? calc.interest : 0, formatAmount);
      setText("note", note);
    };

    form?.addEventListener("input", render);
    form?.addEventListener("change", render);
    render();
  }
})();
