(() => {
  const dialog = document.querySelector("[data-payment-modal]");
  if (!(dialog instanceof HTMLDialogElement)) {
    return;
  }

  document.body.appendChild(dialog);

  const fields = dialog.querySelectorAll("[data-payment-field]");
  const titleEl = dialog.querySelector("[data-payment-title]");

  const fill = (payload) => {
    if (titleEl) {
      titleEl.textContent = payload.tracking
        ? `جزئیات پرداخت ${payload.tracking}`
        : "جزئیات پرداخت";
    }
    fields.forEach((el) => {
      const key = el.getAttribute("data-payment-field");
      const value = payload[key];
      el.textContent = value == null || value === "" ? "—" : String(value);
    });
  };

  document.querySelectorAll("[data-payment-open]").forEach((button) => {
    button.addEventListener("click", () => {
      let payload = {};
      try {
        payload = JSON.parse(button.getAttribute("data-payment") || "{}");
      } catch {
        payload = {};
      }
      fill(payload);
      dialog.showModal();
    });
  });

  dialog.querySelectorAll("[data-payment-close]").forEach((button) => {
    button.addEventListener("click", () => dialog.close());
  });

  dialog.addEventListener("click", (event) => {
    if (event.target === dialog) {
      dialog.close();
    }
  });
})();
