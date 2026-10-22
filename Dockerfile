# syntax=docker/dockerfile:1

# ── Stage 1 : PHP dependencies (no dev) ───────────────────────────────────────
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts \
    --no-progress

# ── Stage 2 : Build frontend assets ───────────────────────────────────────────
FROM oven/bun:1-alpine AS frontend

WORKDIR /app

COPY package.json bun.lock* ./
RUN bun install --frozen-lockfile

COPY resources/ resources/
COPY vite.config.js ./
COPY public/ public/

# Flux CSS est importé depuis vendor — on copie uniquement ce qui est nécessaire au build
COPY --from=vendor /app/vendor/livewire/flux vendor/livewire/flux

RUN bun run build

# ── Stage 3 : Production image ────────────────────────────────────────────────
FROM dunglas/frankenphp:1-php8.5-alpine

WORKDIR /app

# PHP2Shell (CVE-2026-109618 / CVE-2026-109619): refuse to build on an unpatched PHP,
# and apply the PHP project's interim mitigations (no native PHP sessions in use).
RUN php -r 'exit(version_compare(PHP_VERSION, "8.5.12", ">=") ? 0 : 1);' \
    || { echo "PHP $(php -r 'echo PHP_VERSION;') is vulnerable to PHP2Shell, need >= 8.5.12" >&2; exit 1; } \
    && printf 'session.upload_progress.enabled=Off\nfatal_error_backtraces=Off\n' \
        > "$PHP_INI_DIR/conf.d/zz-php2shell.ini"

# Bust cache on each deploy (value injected by CI via --build-arg)
ARG CACHEBUST=1

# Install system deps: pdo_pgsql, pcntl (queue), opcache
RUN install-php-extensions \
    pdo_pgsql \
    pcntl \
    opcache \
    redis \
    intl \
    zip \
    gd

# Copy application code
COPY . .

# Copy build artifacts from previous stages
COPY --from=frontend /app/public/build public/build
COPY --from=vendor /app/vendor vendor/

# Ensure storage and cache dirs exist and are writable
RUN mkdir -p storage/framework/{sessions,views,cache} \
             storage/logs \
             bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY Caddyfile /etc/caddy/Caddyfile

EXPOSE 8000

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
