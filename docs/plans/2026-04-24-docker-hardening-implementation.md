# Docker Production Hardening Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Harden the production Docker image and deployment to mitigate container breakout, secret exposure, resource exhaustion, supply chain attacks, and shell access.

**Architecture:** 3-stage Dockerfile build (frontend → PHP extensions → minimal runtime). Docker Compose gets resource limits, read-only filesystem, capability restrictions, and Redis auth. CI/CD gets Trivy scanning and SBOM generation. PHP gets `disable_functions` for the app container only.

**Tech Stack:** Docker multi-stage builds, Alpine Linux, supervisord, tini, Trivy, SPDX SBOM, GitHub Actions

---

### Task 1: Refactor Dockerfile to 3-Stage Build

**Files:**
- Modify: `Dockerfile`

**Step 1: Rewrite the Dockerfile with 3 stages**

The key changes:
- **Stage 1 (`build`)**: Bun frontend build — unchanged
- **Stage 2 (`builder`)**: Current Alpine image with all tools, compiles PHP extensions, installs composer deps, builds app
- **Stage 3 (`production`)**: Fresh minimal Alpine, copies only runtime artifacts from builder

```dockerfile
# Accepted values: 8.4 - 8.3 - 8.2
ARG PHP_VERSION=8.4

ARG COMPOSER_VERSION=latest

###########################################
# Build frontend assets with Bun
###########################################

ARG BUN_VERSION=1

FROM oven/bun:${BUN_VERSION} AS build

ENV ROOT=/var/www/html

WORKDIR ${ROOT}

COPY --link package.json bun.lock ./

RUN bun install

COPY --link . .

RUN bun run build

###########################################
# Builder stage: compile PHP extensions, install deps
###########################################

FROM composer:${COMPOSER_VERSION} AS vendor

FROM php:${PHP_VERSION}-cli-alpine AS builder

ARG WWWUSER=1000
ARG WWWGROUP=1000
ARG TZ=UTC

ENV TERM=xterm-color \
    WITH_HORIZON=false \
    WITH_SCHEDULER=false \
    OCTANE_SERVER=swoole \
    USER=octane \
    ROOT=/var/www/html \
    COMPOSER_FUND=0 \
    COMPOSER_MAX_PARALLEL_HTTP=24

WORKDIR ${ROOT}

SHELL ["/bin/sh", "-eou", "pipefail", "-c"]

RUN ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime \
  && echo ${TZ} > /etc/timezone

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN apk update; \
    apk upgrade; \
    apk add --no-cache \
    curl \
    wget \
    nano \
    git \
    ncdu \
    procps \
    ca-certificates \
    supervisor \
    libsodium-dev \
    && install-php-extensions \
    bz2 \
    pcntl \
    mbstring \
    bcmath \
    sockets \
    pgsql \
    pdo_pgsql \
    opcache \
    exif \
    pdo_mysql \
    zip \
    intl \
    gd \
    redis \
    rdkafka \
    memcached \
    igbinary \
    ldap \
    swoole \
    && docker-php-source delete \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

RUN arch="$(apk --print-arch)" \
    && case "$arch" in \
    armhf) _cronic_fname='supercronic-linux-arm' ;; \
    aarch64) _cronic_fname='supercronic-linux-arm64' ;; \
    x86_64) _cronic_fname='supercronic-linux-amd64' ;; \
    x86) _cronic_fname='supercronic-linux-386' ;; \
    *) echo >&2 "error: unsupported architecture: $arch"; exit 1 ;; \
    esac \
    && wget -q "https://github.com/aptible/supercronic/releases/download/v0.2.29/${_cronic_fname}" \
    -O /usr/bin/supercronic \
    && chmod +x /usr/bin/supercronic \
    && mkdir -p /etc/supercronic \
    && echo "*/1 * * * * php ${ROOT}/artisan schedule:run --no-interaction" > /etc/supercronic/laravel

RUN addgroup -g ${WWWGROUP} ${USER} \
    && adduser -D -h ${ROOT} -G ${USER} -u ${WWWUSER} -s /bin/sh ${USER}

RUN mkdir -p /var/log/supervisor /var/run/supervisor \
    && chown -R ${USER}:${USER} ${ROOT} /var/log /var/run \
    && chmod -R a+rw ${ROOT} /var/log /var/run

RUN cp ${PHP_INI_DIR}/php.ini-production ${PHP_INI_DIR}/php.ini

USER ${USER}

COPY --link --chown=${USER}:${USER} --from=vendor /usr/bin/composer /usr/bin/composer
COPY --link --chown=${USER}:${USER} composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-ansi \
    --no-scripts

RUN composer clear-cache

COPY --link --chown=${USER}:${USER} . .
COPY --link --chown=${USER}:${USER} --from=build ${ROOT}/public public

RUN mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/framework/testing \
    storage/logs \
    bootstrap/cache && chmod -R a+rw storage

COPY --link --chown=${USER}:${USER} deployment/supervisord.conf /etc/supervisor/
COPY --link --chown=${USER}:${USER} deployment/octane/Swoole/supervisord.swoole.conf /etc/supervisor/conf.d/
COPY --link --chown=${USER}:${USER} deployment/octane/FrankenPHP/supervisord.frankenphp.conf /etc/supervisor/conf.d/
COPY --link --chown=${USER}:${USER} deployment/octane/RoadRunner/supervisord.roadrunner.conf /etc/supervisor/conf.d/
COPY --link --chown=${USER}:${USER} deployment/supervisord.*.conf /etc/supervisor/conf.d/
COPY --link --chown=${USER}:${USER} deployment/php.ini ${PHP_INI_DIR}/conf.d/99-octane.ini
COPY --link --chown=${USER}:${USER} deployment/start-container /usr/local/bin/start-container
COPY --link --chown=${USER}:${USER} deployment/healthcheck /usr/local/bin/healthcheck

RUN composer install \
    --classmap-authoritative \
    --no-interaction \
    --no-ansi \
    --no-dev \
    && composer clear-cache

RUN chmod +x /usr/local/bin/start-container /usr/local/bin/healthcheck

RUN cat deployment/utilities.sh >> ~/.bashrc

###########################################
# Production stage: minimal runtime
###########################################

FROM php:${PHP_VERSION}-cli-alpine AS production

ARG WWWUSER=1000
ARG WWWGROUP=1000
ARG TZ=UTC

ENV TERM=xterm-color \
    WITH_HORIZON=false \
    WITH_SCHEDULER=false \
    OCTANE_SERVER=swoole \
    USER=octane \
    ROOT=/var/www/html \
    COMPOSER_FUND=0 \
    COMPOSER_MAX_PARALLEL_HTTP=24

WORKDIR ${ROOT}

SHELL ["/bin/sh", "-eou", "pipefail", "-c"]

# Install ONLY runtime dependencies (no build tools, no debug utils)
RUN apk update; \
    apk upgrade; \
    apk add --no-cache \
    supervisor \
    tini \
    ca-certificates \
    libsodium \
    libpng \
    libjpeg-turbo \
    libwebp \
    freetype \
    libzip \
    oniguruma \
    icu-libs \
    linux-headers \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

# Copy PHP runtime and compiled extensions from builder
COPY --from=builder /usr/local/bin/php /usr/local/bin/php
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=builder /usr/local/etc/php/ /usr/local/etc/php/
COPY --from=builder /usr/local/lib/php/ /usr/local/lib/php/

# Copy PHP ini from builder
COPY --from=builder ${PHP_INI_DIR}/php.ini ${PHP_INI_DIR}/php.ini

# Create user and directories
RUN addgroup -g ${WWWGROUP} ${USER} \
    && adduser -D -h ${ROOT} -G ${USER} -u ${WWWUSER} -s /bin/sh ${USER} \
    && mkdir -p /var/log/supervisor /var/run/supervisor \
    && chown -R ${USER}:${USER} ${ROOT} /var/log /var/run \
    && chmod -R a+rw ${ROOT} /var/log /var/run

# Copy application from builder
COPY --from=builder --chown=${USER}:${USER} ${ROOT} ${ROOT}

# Copy deployment configs
COPY --link --chown=${USER}:${USER} deployment/supervisord.conf /etc/supervisor/
COPY --link --chown=${USER}:${USER} deployment/octane/Swoole/supervisord.swoole.conf /etc/supervisor/conf.d/
COPY --link --chown=${USER}:${USER} deployment/octane/FrankenPHP/supervisord.frankenphp.conf /etc/supervisor/conf.d/
COPY --link --chown=${USER}:${USER} deployment/octane/RoadRunner/supervisord.roadrunner.conf /etc/supervisor/conf.d/
COPY --link --chown=${USER}:${USER} deployment/supervisord.*.conf /etc/supervisor/conf.d/
COPY --link --chown=${USER}:${USER} deployment/php.ini ${PHP_INI_DIR}/conf.d/99-octane.ini
COPY --link --chown=${USER}:${USER} deployment/start-container /usr/local/bin/start-container
COPY --link --chown=${USER}:${USER} deployment/healthcheck /usr/local/bin/healthcheck

RUN chmod +x /usr/local/bin/start-container /usr/local/bin/healthcheck

# Remove composer, apk cache, and unnecessary binaries
RUN rm -f /usr/bin/composer \
    && apk del --no-cache apk-tools 2>/dev/null || true

USER ${USER}

EXPOSE 8000

ENTRYPOINT ["/sbin/tini", "--", "start-container"]

HEALTHCHECK --start-period=30s --interval=5s --timeout=3s --retries=8 CMD healthcheck || exit 1
```

**Step 2: Verify the Dockerfile syntax**

Run: `docker build --check .` or visually inspect for typos.

**Step 3: Commit**

```bash
git add Dockerfile
git commit -m "refactor: 3-stage Dockerfile with hardened production runtime"
```

---

### Task 2: Harden healthcheck Script

**Files:**
- Modify: `deployment/healthcheck`

**Step 1: Remove `set -e` and improve error handling**

The current `set -e` causes healthcheck to exit on any error instead of reporting the actual health status. Replace with explicit exit codes.

```sh
#!/usr/bin/env sh

container_mode=${CONTAINER_MODE:-"http"}

if [ "${container_mode}" = "http" ]; then
    curl -sf http://localhost:8000 > /dev/null 2>&1
    exit $?
elif [ "${container_mode}" = "horizon" ]; then
    php artisan horizon:status --no-ansi > /dev/null 2>&1
    exit $?
elif [ "${container_mode}" = "scheduler" ]; then
    supervisorctl status scheduler:scheduler_00 > /dev/null 2>&1
    exit $?
elif [ "${container_mode}" = "worker" ]; then
    supervisorctl status worker:worker_00 > /dev/null 2>&1
    exit $?
else
    echo "Container mode mismatched."
    exit 1
fi
```

**Step 2: Commit**

```bash
git add deployment/healthcheck
git commit -m "fix: remove set -e from healthcheck, use explicit exit codes"
```

---

### Task 3: Harden start-container Script

**Files:**
- Modify: `deployment/start-container`

**Step 1: Add APP_KEY guard and improve robustness**

Add an early check for `APP_KEY` to prevent bootstrapping a broken app. This prevents the death spiral where artisan commands fail endlessly.

```sh
#!/usr/bin/env sh
set -e

container_mode=${CONTAINER_MODE:-"http"}
octane_server=${OCTANE_SERVER}
running_migrations_and_seeders=${RUNNING_MIGRATIONS_AND_SEEDERS:-"false"}
export WORKER_NUMPROCS=${WORKER_NUMPROCS:-"8"}

echo "Container mode: $container_mode"

# Guard: APP_KEY must be set for Laravel to function
if [ -z "${APP_KEY}" ]; then
    echo "ERROR: APP_KEY is not set. Application cannot start."
    exit 1
fi

wait_for() {
    host=${1%:*}
    port=${1#*:}
    max_attempts=${2:-30}
    attempt=0

    echo "Waiting for $host:$port ..."

    while [ $attempt -lt $max_attempts ]; do
        if nc -z "$host" "$port" 2>/dev/null; then
            echo "$host:$port is available"
            return 0
        fi
        attempt=$((attempt + 1))
        echo "Attempt $attempt/$max_attempts - $host:$port not available yet..."
        sleep 2
    done

    echo "ERROR: Unable to connect to $host:$port after $max_attempts attempts"
    return 1
}

initialStuff() {
    wait_for "${REDIS_HOST:-redis}:${REDIS_PORT:-6379}" || true
    wait_for "${DB_HOST:-mysql}:${DB_PORT:-3306}" || true

    php artisan storage:link || true
    php artisan optimize:clear || true
    php artisan event:cache || true
    php artisan config:cache || true
    php artisan route:cache || true

    if [ "${running_migrations_and_seeders}" = "true" ]; then
        echo "Running migrations and seeding database ..."
        php artisan migrate --isolated --seed --force
    fi
}

if [ "$1" != "" ]; then
    exec "$@"
elif [ "${container_mode}" = "http" ]; then
    echo "Octane Server: $octane_server"
    initialStuff
    if [ "${octane_server}"  = "frankenphp" ]; then
        exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.frankenphp.conf
    elif [ "${octane_server}"  = "swoole" ]; then
        exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.swoole.conf
    elif [ "${octane_server}"  = "roadrunner" ]; then
        exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.roadrunner.conf
    else
        echo "Invalid Octane server supplied."
        exit 1
    fi
elif [ "${container_mode}" = "horizon" ]; then
    initialStuff
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.horizon.conf
elif [ "${container_mode}" = "scheduler" ]; then
    initialStuff
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.scheduler.conf
elif [ "${container_mode}" = "worker" ]; then
    initialStuff
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.worker.conf
else
    echo "Container mode mismatched."
    exit 1
fi
```

**Step 2: Commit**

```bash
git add deployment/start-container
git commit -m "feat: add APP_KEY guard to start-container"
```

---

### Task 4: Harden Supervisord Config

**Files:**
- Modify: `deployment/supervisord.conf`

**Step 1: Add chmod=0700 to unix socket**

```ini
[supervisord]
nodaemon=true
user=%(ENV_USER)s
logfile=/var/log/supervisor/supervisord.log
pidfile=/var/run/supervisord.pid

[unix_http_server]
file=/var/run/supervisor.sock
chmod=0700

[supervisorctl]
serverurl=unix:///var/run/supervisor.sock

[rpcinterface:supervisor]
supervisor.rpcinterface_factory=supervisor.rpcinterface:make_main_rpcinterface
```

**Step 2: Commit**

```bash
git add deployment/supervisord.conf
git commit -m "hardening: restrict supervisord socket to owner only"
```

---

### Task 5: Add PHP Hardening Config

**Files:**
- Modify: `deployment/php.ini`
- Create: `deployment/php-ini-restrictions.ini`

**Step 1: Add security directives to php.ini**

Append to `deployment/php.ini`:

```ini
[Security]
allow_url_include = 0
allow_url_fopen = 0
open_basedir = /var/www/html:/var/tmp:/tmp
```

**Step 2: Create php-ini-restrictions.ini for app container only**

Create `deployment/php-ini-restrictions.ini`:

```ini
[PHP]
; Disable dangerous functions in HTTP container only.
; DO NOT mount this file on worker containers — queue workers need proc_open.
disable_functions = exec,passthru,shell_exec,system,popen
```

**Step 3: Commit**

```bash
git add deployment/php.ini deployment/php-ini-restrictions.ini
git commit -m "hardening: add PHP security directives and app-only disable_functions"
```

---

### Task 6: Harden Docker Compose

**Files:**
- Modify: `deployment/docker-compose.yml`
- Modify: `deployment/fungsionalpro.env`

**Step 1: Rewrite docker-compose.yml with all hardening**

```yaml
x-shared-env: &shared-env
    APP_NAME: "${APP_NAME}"
    APP_ENV: "${APP_ENV}"
    APP_KEY: "${APP_KEY}"
    APP_DEBUG: "${APP_DEBUG}"
    APP_TIMEZONE: "${APP_TIMEZONE}"
    APP_URL: "${APP_URL}"
    APP_LOCALE: "${APP_LOCALE}"
    APP_FALLBACK_LOCALE: "${APP_FALLBACK_LOCALE}"
    APP_FAKER_LOCALE: "${APP_FAKER_LOCALE}"
    APP_MAINTENANCE_DRIVER: "${APP_MAINTENANCE_DRIVER}"
    APP_MAINTENANCE_STORE: "${APP_MAINTENANCE_STORE}"
    BCRYPT_ROUNDS: "${BCRYPT_ROUNDS}"
    LOG_CHANNEL: "${LOG_CHANNEL}"
    LOG_STACK: "${LOG_STACK}"
    LOG_DEPRECATIONS_CHANNEL: "${LOG_DEPRECATIONS_CHANNEL}"
    LOG_LEVEL: "${LOG_LEVEL}"
    DB_CONNECTION: "${DB_CONNECTION}"
    DB_HOST: "${DB_HOST}"
    DB_PORT: "${DB_PORT}"
    DB_DATABASE: "${DB_DATABASE}"
    DB_USERNAME: "${DB_USERNAME}"
    DB_PASSWORD: "${DB_PASSWORD}"
    SESSION_DRIVER: redis
    SESSION_LIFETIME: "${SESSION_LIFETIME}"
    SESSION_ENCRYPT: "${SESSION_ENCRYPT}"
    SESSION_PATH: "${SESSION_PATH}"
    SESSION_DOMAIN: "${SESSION_DOMAIN}"
    SESSION_CONNECTION: redis
    BROADCAST_CONNECTION: "${BROADCAST_CONNECTION}"
    FILESYSTEM_DISK: "${FILESYSTEM_DISK}"
    QUEUE_CONNECTION: redis
    CACHE_STORE: redis
    CACHE_PREFIX: "${CACHE_PREFIX}"
    MEMCACHED_HOST: "${MEMCACHED_HOST}"
    REDIS_CLIENT: "${REDIS_CLIENT:-phpredis}"
    REDIS_HOST: redis
    REDIS_PASSWORD: "${REDIS_PASSWORD}"
    REDIS_PORT: 6379
    REDIS_DB: "${REDIS_DB:-0}"
    MAIL_MAILER: "${MAIL_MAILER}"
    MAIL_HOST: "${MAIL_HOST}"
    MAIL_PORT: "${MAIL_PORT}"
    MAIL_USERNAME: "${MAIL_USERNAME}"
    MAIL_PASSWORD: "${MAIL_PASSWORD}"
    MAIL_ENCRYPTION: "${MAIL_ENCRYPTION}"
    MAIL_FROM_ADDRESS: "${MAIL_FROM_ADDRESS}"
    MAIL_FROM_NAME: "${MAIL_FROM_NAME}"
    FILAMENT_FILESYSTEM_DISK: "${FILAMENT_FILESYSTEM_DISK}"
    AWS_ENDPOINT: "${AWS_ENDPOINT}"
    AWS_URL: "${AWS_URL}"
    AWS_ACCESS_KEY_ID: "${AWS_ACCESS_KEY_ID}"
    AWS_SECRET_ACCESS_KEY: "${AWS_SECRET_ACCESS_KEY}"
    AWS_DEFAULT_REGION: "${AWS_DEFAULT_REGION}"
    AWS_BUCKET: "${AWS_BUCKET}"
    AWS_USE_PATH_STYLE_ENDPOINT: "${AWS_USE_PATH_STYLE_ENDPOINT}"
    VITE_APP_NAME: "${VITE_APP_NAME}"

services:
    app:
        image: ghcr.io/bphndigitalservice/fungsionalpro:latest
        restart: unless-stopped
        ports:
            - "${APP_PORT:-8000}:8000"
        volumes:
            - upload_data:/var/tmp
            - ./php-ini-restrictions.ini:/usr/local/etc/php/conf.d/99-restrictions.ini:ro
        tmpfs:
            - /tmp:noexec,nosuid,size=50m
            - /run:noexec,nosuid,size=10m
        read_only: true
        depends_on:
            redis:
                condition: service_healthy
        networks:
            - app_network
        environment:
            <<: *shared-env
            CONTAINER_MODE: http
            OCTANE_SERVER: "${OCTANE_SERVER:-swoole}"
        security_opt:
            - no-new-privileges:true
        cap_drop:
            - ALL
        cap_add:
            - NET_BIND_SERVICE
        deploy:
            resources:
                limits:
                    cpus: '2.0'
                    memory: 1G
                reservations:
                    cpus: '0.5'
                    memory: 256M

    worker:
        image: ghcr.io/bphndigitalservice/fungsionalpro:latest
        restart: unless-stopped
        volumes:
            - upload_data:/var/tmp
        tmpfs:
            - /tmp:noexec,nosuid,size=50m
            - /run:noexec,nosuid,size=10m
        read_only: true
        depends_on:
            redis:
                condition: service_healthy
        networks:
            - app_network
        environment:
            <<: *shared-env
            CONTAINER_MODE: worker
            WORKER_NUMPROCS: "${WORKER_NUMPROCS:-8}"
        security_opt:
            - no-new-privileges:true
        cap_drop:
            - ALL
        deploy:
            resources:
                limits:
                    cpus: '1.0'
                    memory: 512M
                reservations:
                    cpus: '0.25'
                    memory: 128M

    redis:
        image: redis:7-alpine
        restart: unless-stopped
        command: redis-server --appendonly yes --maxmemory 128mb --maxmemory-policy allkeys-lru --requirepass "${REDIS_PASSWORD}"
        volumes:
            - redis_data:/data
        networks:
            - app_network
        healthcheck:
            test: ["CMD", "redis-cli", "-a", "${REDIS_PASSWORD}", "ping"]
            interval: 5s
            timeout: 3s
            retries: 5
        deploy:
            resources:
                limits:
                    cpus: '0.5'
                    memory: 192M
                reservations:
                    cpus: '0.1'
                    memory: 64M

volumes:
    upload_data:
    redis_data:

networks:
    app_network:
        driver: bridge
```

**Step 2: Update fungsionalpro.env to document REDIS_PASSWORD**

Add to the Redis section:

```ini
REDIS_PASSWORD=changeme-use-a-strong-password  # [REQUIRED] Must match Redis requirepass
```

Remove the `REDIS_PASSWORD:-null` default from compose (now required, no fallback).

**Step 3: Commit**

```bash
git add deployment/docker-compose.yml deployment/fungsionalpro.env
git commit -m "hardening: resource limits, read_only, cap_drop, Redis auth, no-new-privileges"
```

---

### Task 7: Add CI/CD Supply Chain Hardening

**Files:**
- Modify: `.github/workflows/build-and-deploy.yml`

**Step 1: Add Trivy scan and SBOM generation**

```yaml
name: Docker Build and Publish

on:
  push:
    branches: ["main"]
  pull_request:
    branches: ["main"]

env:
  REGISTRY: ghcr.io
  IMAGE_NAME: ${{ github.repository }}

jobs:
  build-and-push-image:
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: write
      security-events: write

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Log in to the Container registry
        uses: docker/login-action@v3
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Extract metadata (tags, labels) for Docker
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}
          tags: |
            type=ref,event=branch
            type=ref,event=pr
            type=semver,pattern={{version}}
            type=semver,pattern={{major}}.{{minor}}
            type=semver,pattern={{major}}
            type=sha
            latest

      - name: Build and push Docker image
        id: build
        uses: docker/build-push-action@v5
        with:
          context: .
          push: true
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}

      - name: Run Trivy vulnerability scanner
        uses: aquasecurity/trivy-action@master
        with:
          image-ref: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:${{ github.sha }}
          severity: CRITICAL,HIGH
          exit-code: 1
          format: sarif
          output: trivy-results.sarif

      - name: Upload Trivy scan results to GitHub Security tab
        uses: github/codeql-action/upload-sarif@v3
        if: always()
        with:
          sarif_file: trivy-results.sarif

      - name: Generate SBOM
        uses: anchore/sbom-action@latest
        with:
          image: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:${{ github.sha }}
          format: spdx-json
          output-file: sbom.spdx.json

      - name: Upload SBOM artifact
        uses: actions/upload-artifact@v4
        with:
          name: sbom
          path: sbom.spdx.json
```

**Step 2: Commit**

```bash
git add .github/workflows/build-and-deploy.yml
git commit -m "ci: add Trivy vulnerability scan and SBOM generation"
```

---

### Task 8: Verify Build and Runtime

**Files:** None (verification only)

**Step 1: Build the Docker image locally**

```bash
docker build -t fungsionalpro:hardened-test .
```

Expected: Build succeeds without errors.

**Step 2: Check production stage doesn't have debug tools**

```bash
docker run --rm fungsionalpro:hardened-test sh -c "which nano git wget curl ncdu apk composer 2>&1 || true"
```

Expected: All commands return "not found" or similar.

**Step 3: Check healthcheck works**

```bash
docker run --rm -e CONTAINER_MODE=http fungsionalpro:hardened-test healthcheck
```

Expected: Exits with error (no Octane running), but the script itself works.

**Step 4: Check app starts correctly with docker compose**

```bash
cd deployment && docker compose up -d
docker compose ps
docker compose logs app --tail=50
```

Expected: App container starts, Octane listens on 8000, healthcheck passes.

**Step 5: Commit verification results**

If all checks pass, no commit needed. If fixes required, commit them.