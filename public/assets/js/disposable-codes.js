(() => {
  const root = document.querySelector("[data-disposable-codes]");
  if (!(root instanceof HTMLElement)) {
    return;
  }

  const config = {
    socketUrl: root.getAttribute("data-socket-url") || "http://127.0.0.1:3001",
    ttlSeconds: Number(root.getAttribute("data-ttl-seconds") || 120),
  };
  const ttlSeconds = Number(config.ttlSeconds) || 120;
  const tbody = document.querySelector("[data-codes-tbody]");
  const emptyWrap = document.querySelector("[data-codes-empty-wrap]");
  const tableWrap = document.querySelector("[data-codes-table-wrap]");
  const statusEl = document.querySelector("[data-socket-status]");

  if (!(tbody instanceof HTMLElement)) {
    return;
  }

  const toFaDigits = (value) =>
    String(value).replace(/\d/g, (d) => "۰۱۲۳۴۵۶۷۸۹"[d]);

  const formatAmount = (amount) =>
    toFaDigits(Number(amount || 0).toLocaleString("en-US"));

  const receivedAtSeconds = (receivedAt) => {
    const value = Number(receivedAt) || 0;
    if (value >= 1_000_000_000_000) {
      return Math.floor(value / 1000);
    }
    return Math.floor(value);
  };

  const isExpired = (receivedAt, nowMs = Date.now()) => {
    const age = Math.floor(nowMs / 1000) - receivedAtSeconds(receivedAt);
    return age >= ttlSeconds;
  };

  const formatReceivedAt = (receivedAt) => {
    const seconds = receivedAtSeconds(receivedAt);
    if (seconds <= 0) {
      return "—";
    }
    const date = new Date(seconds * 1000);
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, "0");
    const d = String(date.getDate()).padStart(2, "0");
    const hh = String(date.getHours()).padStart(2, "0");
    const mm = String(date.getMinutes()).padStart(2, "0");
    const ss = String(date.getSeconds()).padStart(2, "0");
    return toFaDigits(`${y}/${m}/${d} ${hh}:${mm}:${ss}`);
  };

  const syncVisibility = () => {
    const hasRows = tbody.querySelectorAll("[data-code-row]").length > 0;
    if (emptyWrap) {
      emptyWrap.hidden = hasRows;
    }
    if (tableWrap) {
      tableWrap.hidden = !hasRows;
    }
  };

  const refreshExpiryBadges = () => {
    const now = Date.now();
    tbody.querySelectorAll("[data-code-row]").forEach((row) => {
      const receivedAt = row.getAttribute("data-received-at") || "0";
      const badge = row.querySelector("[data-expiry-badge]");
      if (!(badge instanceof HTMLElement)) {
        return;
      }
      const expired = isExpired(receivedAt, now);
      const activeLabel = badge.getAttribute("data-active-label") || "معتبر";
      const expiredLabel = badge.getAttribute("data-expired-label") || "منقضی";
      badge.textContent = expired ? expiredLabel : activeLabel;
      badge.classList.toggle("badge-warn", expired);
    });
  };

  const createRow = (message) => {
    const tr = document.createElement("tr");
    tr.setAttribute("data-code-row", "");
    tr.setAttribute("data-id", String(message.id || ""));
    tr.setAttribute("data-received-at", String(message.receivedAt || ""));

    const expired = Boolean(message.isExpired) || isExpired(message.receivedAt);
    const cells = [
      { text: message.disposableCode || "", className: "font-latin font-semibold tracking-wide" },
      { text: message.bank || "" },
      { text: message.destination || "", className: "font-latin" },
      { text: formatAmount(message.amount), className: "font-latin" },
      { text: message.currency || "", className: "font-latin" },
      { text: formatReceivedAt(message.receivedAt) },
    ];

    cells.forEach(({ text, className }) => {
      const td = document.createElement("td");
      if (className) {
        td.className = className;
      }
      td.textContent = String(text);
      tr.appendChild(td);
    });

    const statusTd = document.createElement("td");
    const badge = document.createElement("span");
    badge.className = expired ? "badge badge-warn" : "badge";
    badge.setAttribute("data-expiry-badge", "");
    badge.setAttribute("data-expired-label", "منقضی");
    badge.setAttribute("data-active-label", "معتبر");
    badge.textContent = expired ? "منقضی" : "معتبر";
    statusTd.appendChild(badge);
    tr.appendChild(statusTd);

    return tr;
  };

  const prependMessage = (message) => {
    if (!message || typeof message !== "object") {
      return;
    }
    const id = String(message.id || "");
    if (id && tbody.querySelector(`[data-code-row][data-id="${CSS.escape(id)}"]`)) {
      return;
    }
    tbody.prepend(createRow(message));
    syncVisibility();
    refreshExpiryBadges();
  };

  const setStatus = (text) => {
    if (statusEl) {
      statusEl.textContent = text;
    }
  };

  setInterval(refreshExpiryBadges, 1000);
  refreshExpiryBadges();
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
})();
