# Production Docker Image Hardening — Design

**Date**: 2026-04-24
**Status**: Approved
**Approach**: A — Hardened Alpine (multi-stage build)

## Threat Model

Five threats addressed:

1. **Container breakout** — attacker gains RCE via web exploit, then uses shell/package tools to escalate
2. **Secret exposure** — credentials leaked through image layers, env vars, or logs
3. **Resource exhaustion** — single container consumes all VPS resources
4. **Supply chain** — compromised base image or dependency
5. **Shell access** — debugging tools (`nano`, `ncdu`, `git`, `wget`, `curl`, `apk`) available to attacker

## Deployment Context

- Single VPS running Docker Compose
- Laravel Octane (Swoole) as HTTP server
- Supervisord for process management (migration to tini planned later)

---

## 1. Dockerfile — Multi-Stage Build

### Current problem

Single-stage build mixes build tools and runtime. `nano`, `git`, `wget`, `ncdu`, `apk`, `composer`, and `curl` all exist in the production image. An attacker with RCE can use these to escalate.

### Design: 3-stage build

**Stage 1 — `build`** (unchanged): Bun frontend asset compilation.

**Stage 2 — `builder`**: Current Alpine image with all tools. Compiles PHP extensions, installs composer dependencies, downloads supervisord. This stage produces artifacts only.

**Stage 3 — `production`**: Fresh minimal Alpine. Copies only:
- PHP binary and compiled extension `.so` files from builder
- Application code (`app/`, `bootstrap/`, `config/`, `database/`, `lang/`, `resources/views/`, `routes/`, `public/`, `vendor/`, `storage/`, `artisan`)
- Supervisord binary + configs
- `tini` as PID 1 init
- `busybox` static binary (provides minimal shell for scripts, no package manager)
- Deployment configs (php.ini, start-container, healthcheck)

**Removed from production stage**:
- `apk` (Alpine package manager)
- `composer`
- `git`, `nano`, `ncdu`, `wget`, `curl`
- Build headers (`libsodium-dev`, `docker-php-source`)
- GCC, make, and other build toolchain

**Supervisord**: Kept for now. Python runtime stays in production image. Migration to `tini` + direct process management is a future task.

### Key Dockerfile changes

```dockerfile
# Stage 3 — production
FROM php:${PHP_VERSION}-cli-alpine AS production

# Install ONLY runtime dependencies
RUN apk add --no-cache \
    supervisor \
    busybox-static \
    tini \
    icu-libs \
    libgd \
    libpng \
    libjpeg-turbo \
    libwebp \
    freetype \
    libzip \
    libsodium \
    oniguruma \
    linux-headers \
    && cp /usr/bin/busybox-static /usr/bin/busybox

# Copy PHP extensions from builder
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

# Copy application (from builder final stage)
COPY --from=builder /var/www/html /var/www/html

# ... rest of production setup
```

---

## 2. Docker Compose Hardening

### Resource limits

```yaml
app:
  deploy:
    resources:
      limits:
        cpus: '2.0'
        memory: 1G
      reservations:
        cpus: '0.5'
        memory: 256M

worker:
  deploy:
    resources:
      limits:
        cpus: '1.0'
        memory: 512M
      reservations:
        cpus: '0.25'
        memory: 128M

redis:
  deploy:
    resources:
      limits:
        cpus: '0.5'
        memory: 192M
      reservations:
        cpus: '0.1'
        memory: 64M
```

### Security options

```yaml
app:
  read_only: true
  tmpfs:
    - /var/tmp:noexec,nosuid,size=100m
    - /tmp:noexec,nosuid,size=50m
    - /run:noexec,nosuid,size=10m
  security_opt:
    - no-new-privileges:true
  cap_drop:
    - ALL
  cap_add:
    - NET_BIND_SERVICE

worker:
  read_only: true
  tmpfs:
    - /var/tmp:noexec,nosuid,size=100m
    - /tmp:noexec,nosuid,size=50m
    - /run:noexec,nosuid,size=10m
  security_opt:
    - no-new-privileges:true
  cap_drop:
    - ALL
```

### Redis authentication

```yaml
redis:
  command: redis-server --appendonly yes --maxmemory 128mb --maxmemory-policy allkeys-lru --requirepass "${REDIS_PASSWORD}"
```

Corresponding `REDIS_PASSWORD` must be set in the app/worker environment (already templated in compose as `${REDIS_PASSWORD:-null}`).

### Image digest pinning

```yaml
app:
  image: ghcr.io/bphndigitalservice/fungsionalpro:latest@sha256:<DIGEST>
```

Digest updated on each deploy via CI/CD.

---

## 3. Healthcheck & Start-Container Fixes

### healthcheck

- Remove `set -e` (causes healthcheck to mask real failures)
- HTTP mode: `curl -sf http://localhost:8000 > /dev/null 2>&1` (already done)
- Worker/horizon/scheduler modes: keep `php artisan` commands (lighter than Octane status)

### start-container

- Add `APP_KEY` guard: skip `initialStuff` and error out if `APP_KEY` is empty
- Already has `|| true` on all artisan commands (from earlier fix)

---

## 4. PHP Hardening

### deployment/php.ini — additions

```ini
allow_url_include = 0
allow_url_fopen = 0
open_basedir = /var/www/html:/var/tmp:/tmp
```

### New: deployment/php-ini-restrictions.ini — app-only

```ini
[PHP]
disable_functions = exec,passthru,shell_exec,system,popen
```

**Not** `proc_open` — queue workers need it. This file is mounted only in the `app` service via volume:

```yaml
app:
  volumes:
    - ./deployment/php-ini-restrictions.ini:/usr/local/etc/php/conf.d/99-restrictions.ini:ro
```

Worker container does NOT get this file.

---

## 5. Supervisord Hardening

### deployment/supervisord.conf

Add `chmod=0700` to restrict socket access:

```ini
[unix_http_server]
file=/var/run/supervisor.sock
chmod=0700
```

Removes the `chown=` directive — `octane` user already owns it.

---

## 6. CI/CD Supply Chain Hardening

### .github/workflows/build-and-deploy.yml

**Add Trivy vulnerability scan** (blocks on CRITICAL/HIGH):

```yaml
- name: Run Trivy vulnerability scanner
  uses: aquasecurity/trivy-action@master
  with:
    image-ref: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:${{ github.sha }}
    severity: CRITICAL,HIGH
    exit-code: 1
    format: sarif
    output: trivy-results.sarif
```

**Add SBOM generation**:

```yaml
- name: Generate SBOM
  uses: anchore/sbom-action@latest
  with:
    image: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:${{ github.sha }}
    format: spdx-json
    output-file: sbom.spdx.json

- name: Attach SBOM to image
  run: |
    oras attach ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:${{ github.sha }} \
      --artifact-type application/spdx+json \
      sbom.spdx.json
```

**Pin base image digest**: Update `ARG PHP_VERSION=8.4` to include a specific Alpine digest, or document the pinned sha256 in the Dockerfile comments. Update via Dependabot.

---

## Files Changed

| File | Change |
|------|--------|
| `Dockerfile` | Refactored to 3-stage build, strip runtime |
| `deployment/docker-compose.yml` | Resource limits, read_only, cap_drop, Redis auth, digest pinning |
| `deployment/healthcheck` | Remove `set -e` (already uses `curl`) |
| `deployment/start-container` | APP_KEY guard (artisan `|| true` already done) |
| `deployment/supervisord.conf` | `chmod=0700` on socket |
| `deployment/php.ini` | `allow_url_include`, `allow_url_fopen`, `open_basedir` |
| `deployment/php-ini-restrictions.ini` | NEW — `disable_functions` for app container only |
| `.github/workflows/build-and-deploy.yml` | Trivy scan, SBOM generation |
| `.dockerignore` | Already hardened (previous work) |

## Future Work (Not In Scope)

- Migrate from supervisord to `tini` + direct process management (eliminates Python runtime)
- Cosign image signing for verification
- Writable storage layer via tmpfs (Laravel storage needs writes)