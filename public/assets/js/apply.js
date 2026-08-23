(() => {
  const $ = window.jQuery;
  if (!$ || !$.validator) return;

  const toEn = (value) =>
    String(value || "").replace(/[۰-۹٠-٩]/g, (digit) => {
      const persian = "۰۱۲۳۴۵۶۷۸۹";
      const arabic = "٠١٢٣٤٥٦٧٨٩";
      const p = persian.indexOf(digit);
      if (p >= 0) return String(p);
      const a = arabic.indexOf(digit);
      return a >= 0 ? String(a) : digit;
    });

  const parseMoney = (value) => {
    const raw = toEn(value).replace(/[^\d-]/g, "");
    if (!raw) return 0;
    return Number.parseInt(raw, 10) || 0;
  };

  const jalaliLeap = (year) => [1, 5, 9, 13, 17, 22, 26, 30].includes(year % 33);

  const jalaliDays = (year, month) => {
    if (month >= 1 && month <= 6) return 31;
    if (month >= 7 && month <= 11) return 30;
    if (month === 12) return jalaliLeap(year) ? 30 : 29;
    return 0;
  };

  const parseJalali = (value) => {
    const match = toEn(value).replace(/[-.]/g, "/").match(/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/);
    if (!match) return null;
    const year = Number(match[1]);
    const month = Number(match[2]);
    const day = Number(match[3]);
    if (year < 1200 || year > 1600) return null;
    if (month < 1 || month > 12 || day < 1 || day > jalaliDays(year, month)) return null;
    return { year, month, day };
  };

  const nationalIdValid = (value) => {
    const nid = toEn(value).replace(/\D/g, "");
    if (nid.length !== 10 || /^(\d)\1{9}$/.test(nid)) return false;
    const check = Number(nid[9]);
    let sum = 0;
    for (let i = 0; i < 9; i += 1) sum += Number(nid[i]) * (10 - i);
    const mod = sum % 11;
    return mod < 2 ? check === mod : check === 11 - mod;
  };

  let productRules = {};
  try {
    productRules = JSON.parse(document.getElementById("apply-product-rules")?.textContent || "{}");
  } catch (error) {
    productRules = {};
  }

  const selectedRules = () => productRules[$("#apply-form [name=product]").val()] || null;

  $.extend($.validator.messages, {
    required: "این فیلد الزامی است.",
    number: "فقط عدد وارد کنید.",
    digits: "فقط رقم وارد کنید.",
    maxlength: $.validator.format("حداکثر {0} کاراکتر مجاز است."),
    minlength: $.validator.format("حداقل {0} کاراکتر وارد کنید."),
    min: $.validator.format("مقدار نباید کمتر از {0} باشد."),
    max: $.validator.format("مقدار نباید بیشتر از {0} باشد."),
  });

  $.validator.addMethod(
    "nationalId",
    function (value, element) {
      return this.optional(element) || nationalIdValid(value);
    },
    "کد ملی معتبر نیست."
  );

  $.validator.addMethod(
    "irMobile",
    function (value, element) {
      const digits = toEn(value).replace(/\D/g, "");
      return this.optional(element) || /^09\d{9}$/.test(digits);
    },
    "شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود."
  );

  $.validator.addMethod(
    "jalaliDate",
    function (value, element) {
      if (this.optional(element)) return true;
      const parsed = parseJalali(value);
      if (!parsed) return false;
      const today = document.querySelector("[data-jdp]")?.getAttribute("data-jdp-max-date");
      if (today === "today") {
        const now = new Intl.DateTimeFormat("en-u-ca-persian", {
          year: "numeric",
          month: "2-digit",
          day: "2-digit",
        }).formatToParts(new Date());
        const get = (type) => Number(now.find((part) => part.type === type)?.value || 0);
        const y = get("year");
        const m = get("month");
        const d = get("day");
        if (
          parsed.year > y ||
          (parsed.year === y && parsed.month > m) ||
          (parsed.year === y && parsed.month === m && parsed.day > d)
        ) {
          return false;
        }
      }
      return true;
    },
    "تاریخ تولد شمسی معتبر وارد کنید."
  );

  $.validator.addMethod(
    "money",
    function (value, element) {
      if (this.optional(element)) return true;
      return parseMoney(value) >= 0;
    },
    "مبلغ نامعتبر است."
  );

  $.validator.addMethod(
    "requestedAmount",
    function (value, element) {
      if (this.optional(element)) return false;
      const amount = parseMoney(value);
      const rules = selectedRules();
      if (amount <= 0) return false;
      if (rules?.min_amount && amount < rules.min_amount) return false;
      if (rules?.max_amount && amount > rules.max_amount) return false;
      return true;
    },
    function () {
      const rules = selectedRules();
      if (!rules) return "مبلغ درخواستی را وارد کنید.";
      if (rules.max_amount) {
        return `مبلغ باید بین ${rules.min_amount.toLocaleString("en-US")} و ${rules.max_amount.toLocaleString("en-US")} تومان باشد.`;
      }
      return "مبلغ درخواستی را وارد کنید.";
    }
  );

  $.validator.addMethod(
    "repaymentRange",
    function (value, element) {
      if (this.optional(element)) return false;
      const months = Number(toEn(value));
      const rules = selectedRules();
      if (!rules) return months >= 6 && months <= 60;
      return months >= rules.term_min && months <= rules.term_max;
    },
    function () {
      const rules = selectedRules();
      if (!rules) return "مدت بازپرداخت نامعتبر است.";
      return `مدت بازپرداخت باید بین ${rules.term_min} تا ${rules.term_max} ماه باشد.`;
    }
  );

  $.validator.addMethod(
    "accountRequired",
    function () {
      const rules = selectedRules();
      if (!rules?.account_required) return true;
      return $("#has-account").is(":checked");
    },
    "برای این محصول داشتن حساب بانک ملی لازم است."
  );

  $.validator.addMethod(
    "etebarRate",
    function (value) {
      const rules = selectedRules();
      const rates = (rules?.interest_rates || []).map(Number);
      if (rates.length === 0) return true;
      return rates.includes(Number(value));
    },
    "نرخ سود را از گزینه‌های اعتبار ملی انتخاب کنید."
  );

  $.validator.addMethod(
    "mehrabaniFee",
    function (value) {
      const rules = selectedRules();
      const fees = (rules?.fee_options || []).map(Number);
      if (fees.length === 0) return true;
      return fees.includes(Number(value));
    },
    "کارمزد را از گزینه‌های مهربانی ملی انتخاب کنید."
  );

  const $form = $("#apply-form");
  if (!$form.length) return;

  if (window.jalaliDatepicker) {
    window.jalaliDatepicker.startWatch({
      time: false,
      date: true,
      hasSecond: false,
      hideAfterChange: true,
      useDropDownYears: true,
      persianDigits: false,
      minDate: "attr",
      maxDate: "attr",
    });
  }

  const syncProductFields = () => {
    const slug = String($form.find("[name=product]").val() || "");
    $form.find("[data-apply-for]").each(function () {
      const allowed = String($(this).attr("data-apply-for") || "")
        .split(/\s+/)
        .filter(Boolean);
      $(this).toggleClass("hidden", slug === "" || !allowed.includes(slug));
    });
    const rules = selectedRules();
    const $months = $form.find("[name=repayment_months]");
    if (rules) {
      $months.attr({ min: rules.term_min, max: rules.term_max });
    } else {
      $months.attr({ min: 6, max: 60 });
    }
  };

  $form.validate({
    ignore: ':hidden:not([name="birth_date"]):not(#has-account)',
    errorElement: "span",
    errorClass: "field-error-msg",
    validClass: "field-valid",
    errorPlacement(error, element) {
      if (element.attr("type") === "checkbox") {
        error.insertAfter(element.closest("label"));
      } else {
        error.insertAfter(element);
      }
    },
    highlight(element) {
      $(element).addClass("field-invalid").removeClass("field-valid");
    },
    unhighlight(element) {
      $(element).removeClass("field-invalid").addClass("field-valid");
    },
    rules: {
      product: { required: true },
      full_name: { required: true, minlength: 3, maxlength: 150 },
      national_id: { required: true, nationalId: true },
      mobile: { required: true, irMobile: true },
      birth_date: { required: true, jalaliDate: true },
      employment_type: { required: true },
      monthly_income: { money: true },
      bank_melli_account: { maxlength: 40 },
      account_activity_months: { number: true, min: 0, max: 120 },
      average_balance: { money: true },
      requested_amount: { required: true, requestedAmount: true },
      repayment_months: { required: true, number: true, repaymentRange: true },
      has_account: { accountRequired: true },
      interest_rate: { etebarRate: true },
      fee_option: { mehrabaniFee: true },
    },
    onkeyup(element) {
      $(element).valid();
    },
    onfocusout(element) {
      $(element).valid();
    },
  });

  syncProductFields();

  $form.find("[name=product], #has-account").on("change", () => {
    syncProductFields();
    $form
      .find("[name=requested_amount], [name=repayment_months], #has-account, [name=interest_rate], [name=fee_option]")
      .each(function () {
        if ($(this).is(":visible") || this.id === "has-account") {
          $(this).valid();
        }
      });
  });

  $form.find("[data-jdp]").on("change jdp:change", function () {
    $(this).valid();
  });
})();
