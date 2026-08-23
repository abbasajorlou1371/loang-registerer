# syntax=docker/dockerfile:1
# Multi-stage images: css builder → php-fpm, nginx, socket.
# Alpine + BuildKit cache mounts keep builds small and fast.

ARG NODE_VERSION=22-alpine
ARG PHP_VERSION=8.3-fpm-alpine
ARG NGINX_VERSION=1.27-alpine

# -----------------------------------------------------------------------------
# Tailwind CSS (discarded after copying app.css)
# -----------------------------------------------------------------------------
FROM node:${NODE_VERSION} AS css
WORKDIR /build

COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm \
    ARCH="$(uname -m)" \
    && case "$ARCH" in \
         x86_64) MUSL_ARCH=x64 ;; \
         aarch64) MUSL_ARCH=arm64 ;; \
         *) echo "unsupported architecture: $ARCH" >&2; exit 1 ;; \
       esac \
    && npm ci --no-audit --no-fund \
    && npm install --no-save --no-audit --no-fund \
         "lightningcss-linux-${MUSL_ARCH}-musl@1.32.0" \
         "@tailwindcss/oxide-linux-${MUSL_ARCH}-musl@4.3.3"

COPY src ./src
COPY pages ./pages
COPY includes ./includes
COPY public ./public

RUN npm run build \
    && rm -rf node_modules

# -----------------------------------------------------------------------------
# PHP-FPM — only runtime extensions the app uses
# -----------------------------------------------------------------------------
FROM php:${PHP_VERSION} AS php
WORKDIR /var/www/html

RUN apk add --no-cache oniguruma \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        oniguruma-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        opcache \
    && apk del .build-deps \
    && rm -rf /tmp/* /var/cache/apk/*

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-tuning.conf
COPY docker/php/entrypoint.sh /usr/local/bin/app-entrypoint.sh
RUN chmod +x /usr/local/bin/app-entrypoint.sh

COPY --chown=www-data:www-data config ./config
COPY --chown=www-data:www-data includes ./includes
COPY --chown=www-data:www-data pages ./pages
COPY --chown=www-data:www-data public ./public
COPY --chown=www-data:www-data sql ./sql
COPY --from=css --chown=www-data:www-data /build/public/assets/css/app.css ./public/assets/css/app.css

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/app-entrypoint.sh"]
CMD ["php-fpm"]

# -----------------------------------------------------------------------------
# Nginx — static assets + reverse proxy to php-fpm / socket
# -----------------------------------------------------------------------------
FROM nginx:${NGINX_VERSION} AS nginx

RUN apk add --no-cache --virtual .ssl-build openssl \
    && mkdir -p /etc/nginx/certs \
    && openssl req -x509 -nodes -newkey rsa:2048 \
        -keyout /etc/nginx/certs/privkey.pem \
        -out /etc/nginx/certs/fullchain.pem \
        -days 3650 \
        -subj "/CN=localhost" \
        -addext "subjectAltName=DNS:localhost,DNS:*.localhost,IP:127.0.0.1" \
    && chmod 600 /etc/nginx/certs/privkey.pem \
    && apk del .ssl-build

COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=php /var/www/html/public /var/www/html/public

EXPOSE 80 443
HEALTHCHECK --interval=15s --timeout=3s --start-period=45s --retries=8 \
    CMD wget -qO- http://127.0.0.1/ >/dev/null || exit 1

# -----------------------------------------------------------------------------
# Socket.IO — production Node deps only
# -----------------------------------------------------------------------------
FROM node:${NODE_VERSION} AS socket-deps
WORKDIR /app
COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm \
    npm ci --omit=dev --no-audit --no-fund

FROM node:${NODE_VERSION} AS socket
WORKDIR /app
ENV NODE_ENV=production
COPY --from=socket-deps /app/node_modules ./node_modules
COPY --from=socket-deps /app/package.json ./package.json
COPY server/socket-server.js ./server/socket-server.js

USER node
EXPOSE 3001
HEALTHCHECK --interval=15s --timeout=3s --start-period=5s --retries=5 \
    CMD node -e "fetch('http://127.0.0.1:3001/health').then(r=>process.exit(r.ok?0:1)).catch(()=>process.exit(1))"
CMD ["node", "server/socket-server.js"]
