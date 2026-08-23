# Deployment Guide — Loan Registrarer

Concise runbook for deploying the Bank Melli–themed loan registration platform.

## Architecture

| Layer | Technology | Role |
|--------|------------|------|
| Web app | PHP 8.0+ (custom front controller) | Pages, admin, payment demo, APIs |
| Database | MySQL 8 / MariaDB 10.5+ (`utf8mb4`) | Products, applications, users, transaction codes |
| Assets | Tailwind CSS 4 (Node build) | Compiled CSS under `public/assets/css/` |
| Realtime | Node.js + Express + Socket.IO | Live admin disposable-code updates |

Document root must be **`public/`**. All HTTP traffic is routed through `public/router.php` (or `public/index.php`).

Two long-running processes are required in production:

1. **PHP** (Apache/Nginx + PHP-FPM, or equivalent)
2. **Socket server** (`npm run socket`) — binds `SOCKET_HOST` (default `127.0.0.1`; use `0.0.0.0` in Docker)

---

## Requirements

- PHP 8.0+ with extensions: `pdo_mysql`, `json`, `mbstring`, `session`, `curl` (recommended for socket emit)
- MySQL 8+ or MariaDB 10.5+
- Node.js 18+ and npm (CSS build + socket server)
- Web server with URL rewriting to the front controller

---

## 1. Get the code

```bash
cd /var/www/loan-registrarer   # or your deploy path
# clone / rsync / CI artifact here
cp .env.example .env
```

Do **not** commit `.env`. Ensure `node_modules/` is either installed on the server or CSS is pre-built in CI.

---

## 2. Environment variables

Edit `.env` (loaded by both PHP and the Node socket server):

| Variable | Purpose | Production notes |
|----------|---------|------------------|
| `DB_HOST` / `DB_PORT` / `DB_NAME` / `DB_USER` / `DB_PASS` | MySQL connection | Prefer TCP; leave `DB_SOCKET` empty unless using a local Unix socket |
| `DB_SOCKET` | Optional Unix socket path | XAMPP/local only; clear on remote hosts |
| `APP_URL` | Public site URL | e.g. `http://loans.example.com` |
| `APP_ENV` | Environment label | Use `production` |
| `SOCKET_PORT` | Socket HTTP port | Default `3001` |
| `SOCKET_HOST` | Bind address | `127.0.0.1` locally; Compose sets `0.0.0.0` so Nginx can reach it |
| `SOCKET_PUBLIC_URL` | Browser-facing Socket.IO base URL | Must match what admins’ browsers can reach (often via reverse proxy) |
| `SOCKET_EMIT_URL` | PHP → Node emit endpoint | Usually `http://127.0.0.1:3001/emit` |
| `SOCKET_EMIT_SECRET` | Shared secret for `/emit` | **Set a strong random value** |
| `SOCKET_CORS_ORIGIN` | Allowed browser origin(s) | Exact app origin, e.g. `http://loans.example.com` |
| `TRANSACTION_API_KEY` | Auth for `POST /api/transaction-message` | **Set a strong key**; send as `X-Api-Key` or `Authorization: Bearer …` |

If `TRANSACTION_API_KEY` or `SOCKET_EMIT_SECRET` are empty, those endpoints accept unauthenticated calls — unsuitable for production.

---

## 3. Database setup

```bash
mysql -u root -p < sql/schema.sql
mysql -u root -p loan_registrarer < sql/seed.sql
php sql/seed_admin.php
```

- `schema.sql` — creates database `loan_registrarer` and all tables (destructive: drops existing tables listed in the script).
- `seed.sql` — loan catalog (products, versions, eligibility rules).
- `seed_admin.php` — creates/updates admin user: **`admin` / `admin`**. Change this password immediately after first login.

Incremental SQL files under `sql/` (`users.sql`, `transaction_messages.sql`, `demo_ipg_payments.sql`) are already covered by a fresh `schema.sql` import; use them only when upgrading an older database.

Grant the app user least privilege (`SELECT`, `INSERT`, `UPDATE`, `DELETE`) on `loan_registrarer` only.

---

## 4. Frontend assets

From project root:

```bash
npm ci
npm run build
```

This writes minified CSS to `public/assets/css/app.css`. JS under `public/assets/js/` is static and does not need a bundler step.

For local CSS watching: `npm run dev`.

---

## 5. Web server

### Document root & rewrite

Point the vhost document root to `…/loan-registrarer/public`.

Deny direct web access to: `.env`, `config/`, `includes/`, `pages/`, `sql/`, `server/`, `src/`, `node_modules/`.

**Nginx (PHP-FPM) sketch:**

```nginx
server {
    listen 80;
    server_name loans.example.com;
    root /var/www/loan-registrarer/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;  # adjust
    }

    # Optional: proxy Socket.IO if browsers need a public path
    location /socket.io/ {
        proxy_pass http://127.0.0.1:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }
}
```

If you proxy Socket.IO under the same host, set e.g.:

```env
SOCKET_PUBLIC_URL=http://loans.example.com
SOCKET_CORS_ORIGIN=http://loans.example.com
SOCKET_EMIT_URL=http://127.0.0.1:3001/emit
```

**Apache:** set `DocumentRoot` to `public/` and enable `FallbackResource /index.php` (or equivalent rewrite to `index.php`).

### Local / smoke test (not for production)

```bash
php -S localhost:8000 -t public public/router.php
```

---

## 6. Socket server process

```bash
npm run socket
# → Socket.io server listening on http://127.0.0.1:3001
```

Keep it running under systemd, PM2, or Supervisor. Example systemd unit:

```ini
[Unit]
Description=Loan Registrarer Socket.IO
After=network.target

[Service]
Type=simple
WorkingDirectory=/var/www/loan-registrarer
ExecStart=/usr/bin/node server/socket-server.js
Restart=always
User=www-data
Environment=NODE_ENV=production

[Install]
WantedBy=multi-user.target
```

Health check: `GET http://127.0.0.1:3001/health` → `{ "ok": true, … }`.

PHP notifies the socket after storing a transaction message by POSTing to `SOCKET_EMIT_URL` with header `X-Emit-Secret` when configured.

---

## 7. Deploy checklist

1. [ ] `.env` filled; secrets set; `APP_ENV=production`
2. [ ] Schema + seed applied; admin password changed
3. [ ] `npm ci && npm run build`
4. [ ] Web root = `public/`; rewrites work (`/`, `/admin`, `/api/transaction-message`)
5. [ ] Socket process running; CORS origin matches the site
6. [ ] External callers use `POST /api/transaction-message` with `TRANSACTION_API_KEY`
7. [ ] File permissions: web user can read code; `.env` not world-readable

---

## 8. Key URLs

| Path | Description |
|------|-------------|
| `/` | Public home |
| `/apply`, `/calculator`, `/eligibility`, `/track` | Applicant flows |
| `/admin/login` | Admin panel |
| `/admin/disposable-codes` | Live disposable codes (needs socket) |
| `POST /api/transaction-message` | Ingest bank SMS / transaction payload |

---

## 9. Operations notes

- **Logging:** PHP uses `error_log()` (`app_log`); configure PHP-FPM / web server error logs.
- **Sessions:** Cookie flags set in the front controller (`HttpOnly`, `SameSite=Lax`, strict mode).
- **Showcase resilience:** If MySQL is down, some marketing pages still render; apply/admin/API features that need the DB will fail.
- **Default admin** (`admin`/`admin`) must never remain on a public host.
- **Updates:** pull code → `npm ci && npm run build` → run any new SQL migrations → reload PHP-FPM → restart socket service.

---

## Quick start (local)

```bash
cp .env.example .env
# configure DB_* (and clear DB_SOCKET if not using XAMPP)

mysql -u root < sql/schema.sql
mysql -u root loan_registrarer < sql/seed.sql
php sql/seed_admin.php

npm ci && npm run build
npm run socket &          # terminal 1
php -S localhost:8000 -t public public/router.php   # terminal 2
```

Open `http://localhost:8000` and `http://localhost:8000/admin/login`.

---

## 10. Docker

Nginx listens on **port 80**. PHP-FPM, Socket.IO, and MariaDB stay on the internal Compose network. Images are Alpine multi-stage builds (CSS compiled in a throwaway Node stage).

```bash
docker compose up -d --build
```

Open `http://localhost` and `http://localhost/admin/login` (default admin: `admin` / `admin`). Schema and catalog seed run on first database volume create; the PHP container seeds the admin user on startup.

Compose does **not** reuse `DB_HOST` / `APP_URL` / `SOCKET_*` from the project `.env` (those values target XAMPP / `php -S`). Optional overrides:

| Variable | Default |
|----------|---------|
| `DOCKER_MYSQL_ROOT_PASSWORD` | `changeme-root` |
| `DOCKER_SOCKET_EMIT_SECRET` | `changeme-emit-secret` |
| `DOCKER_TRANSACTION_API_KEY` | `changeme-transaction-key` |

Socket.IO is proxied at `/socket.io/` on the same host. Inside Compose, PHP emits to `http://socket:3001/emit`. The Node server binds `SOCKET_HOST=0.0.0.0` in Docker (still `127.0.0.1` for local `npm run socket`).

```bash
docker compose logs -f php nginx socket db
docker compose down            # keep the database volume
docker compose down -v         # wipe MariaDB data
```
