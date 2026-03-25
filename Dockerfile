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
