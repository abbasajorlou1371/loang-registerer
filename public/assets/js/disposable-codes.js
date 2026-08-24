(() => {
  const root = document.querySelector("[data-disposable-codes]");
  if (!(root instanceof HTMLElement)) {
    return;
  }

  const config = {
    socketUrl: root.getAttribute("data-socket-url") || "http://127.0.0.1:3001",
  };
  const list = document.querySelector("[data-codes-list]");
  const emptyEl = document.querySelector("[data-codes-empty]");
  const statusEl = document.querySelector("[data-socket-status]");

  if (!(list instanceof HTMLElement)) {
    return;
  }

  const formatData = (value) => {
    const raw = String(value ?? "");
    if (raw === "") {
      return "";
    }
    try {
      return JSON.stringify(JSON.parse(raw), null, 2);
    } catch {
      return raw;
    }
  };

  const syncVisibility = () => {
    const hasRows = list.querySelectorAll("[data-code-row]").length > 0;
    if (emptyEl) {
      emptyEl.hidden = hasRows;
    }
  };

  const createAlert = (message) => {
    const wrap = document.createElement("div");
    wrap.className = "alert alert-info";
    wrap.setAttribute("data-code-row", "");
    wrap.setAttribute("data-id", String(message.id || ""));

    const pre = document.createElement("pre");
    pre.textContent = formatData(message.data);
    wrap.appendChild(pre);

    return wrap;
  };

  const prependMessage = (message) => {
    if (!message || typeof message !== "object") {
      return;
    }
    const id = String(message.id || "");
    if (id && list.querySelector(`[data-code-row][data-id="${CSS.escape(id)}"]`)) {
      return;
    }
    const alertEl = createAlert(message);
    if (emptyEl instanceof HTMLElement && emptyEl.parentNode === list) {
      emptyEl.insertAdjacentElement("afterend", alertEl);
    } else {
      list.prepend(alertEl);
    }
    syncVisibility();
  };

  const clearMessages = () => {
    list.querySelectorAll("[data-code-row]").forEach((row) => row.remove());
    syncVisibility();
  };

  const setStatus = (text) => {
    if (statusEl) {
      statusEl.textContent = text;
    }
  };

  syncVisibility();

  if (typeof window.io !== "function") {
    setStatus("به‌روزرسانی زنده در دسترس نیست");
    return;
  }

  const socket = window.io(config.socketUrl, {
    transports: ["websocket", "polling"],
    withCredentials: false,
  });

  socket.on("connect", () => {
    setStatus("به‌روزرسانی زنده فعال است");
  });

  socket.on("disconnect", () => {
    setStatus("ارتباط زنده قطع شد — تلاش برای اتصال مجدد…");
  });

  socket.on("connect_error", () => {
    setStatus("خطا در اتصال زنده — سرور Socket را بررسی کنید");
  });

  socket.on("transaction_message:created", (payload) => {
    prependMessage(payload);
  });

  socket.on("transaction_message:cleared", () => {
    clearMessages();
  });
})();
