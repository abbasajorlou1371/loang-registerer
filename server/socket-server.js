const http = require("http");
const fs = require("fs");
const path = require("path");
const express = require("express");
const { Server } = require("socket.io");

function loadEnvFile(filePath) {
  if (!fs.existsSync(filePath)) {
    return;
  }
  const lines = fs.readFileSync(filePath, "utf8").split(/\r?\n/);
  for (const line of lines) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith("#") || !trimmed.includes("=")) {
      continue;
    }
    const eq = trimmed.indexOf("=");
    const key = trimmed.slice(0, eq).trim();
    let value = trimmed.slice(eq + 1).trim();
    if (
      (value.startsWith('"') && value.endsWith('"')) ||
      (value.startsWith("'") && value.endsWith("'"))
    ) {
      value = value.slice(1, -1);
    }
    if (key && process.env[key] === undefined) {
      process.env[key] = value;
    }
  }
}

loadEnvFile(path.join(__dirname, "..", ".env"));

const PORT = Number(process.env.SOCKET_PORT || 3001);
const HOST = process.env.SOCKET_HOST || "127.0.0.1";
const EMIT_SECRET = process.env.SOCKET_EMIT_SECRET || "";
const CORS_ORIGIN = process.env.SOCKET_CORS_ORIGIN || "*";

const app = express();
app.use(express.json({ limit: "64kb" }));

const server = http.createServer(app);
const io = new Server(server, {
  cors: {
    origin: CORS_ORIGIN === "*" ? true : CORS_ORIGIN.split(",").map((v) => v.trim()),
    methods: ["GET", "POST"],
  },
});

io.on("connection", (socket) => {
  socket.join("admins");
});

app.get("/health", (_req, res) => {
  res.json({ ok: true, clients: io.engine.clientsCount });
});

app.post("/emit", (req, res) => {
  if (EMIT_SECRET) {
    const provided = String(req.header("x-emit-secret") || "");
    if (provided !== EMIT_SECRET) {
      res.status(401).json({ ok: false, error: "Unauthorized" });
      return;
    }
  }

  const event = String(req.body?.event || "");
  const data = req.body?.data;
  if (!event) {
    res.status(422).json({ ok: false, error: "event is required" });
    return;
  }

  io.to("admins").emit(event, data);
  res.json({ ok: true });
});

server.listen(PORT, HOST, () => {
  console.log(`Socket.io server listening on http://${HOST}:${PORT}`);
});
