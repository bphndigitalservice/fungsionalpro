# Docker Production Hardening Design

**Date:** 2026-04-29
**Status:** Implemented

## Summary

Hardened the Laravel Octane Dockerfile and supporting deployment configuration for production-grade security, reproducibility, and minimal attack surface.

## Changes

### 1. Dockerfile — Base Image Pinning

- **Bun**: `1` → `1.2.9` — prevents supply chain and reproducibility issues from floating major version
- **Alpine**: Added `ARG ALPINE_VERSION=3.21` and used `php:${PHP_VERSION}-cli-alpine${ALPINE_VERSION}` — ensures reproducible base images across builds

### 2. Dockerfile — Reduced Attack Surface

- **Removed `busybox-extras`** from production stage — eliminated `nc`, `telnet`, `ftpget`, and other unnecessary network utilities
- **Removed `wget`** from builder stage — replaced with `curl` (already present) for supercronic download
- **Removed broad COPY lines** for `/usr/local/etc/php/` and `/usr/local/lib/php/` — no longer copies dev headers, test files, or documentation into production image

### 3. Dockerfile — Layer Consolidation

- **Builder**: Merged `adduser`, `cp php.ini`, `mkdir`, `chown`, `chmod` from 3 layers into 1
- **Production**: Merged `cp php.ini`, `adduser`, `mkdir`, `chown`, `chmod` from 3 layers into 1
- Added `VCS_REF` and `VERSION` build ARGs with OCI image labels for traceability

### 4. php-ini-security.ini — Comprehensive Security Directives

Added:
- `allow_url_fopen = 0` — prevent remote file inclusion via PHP streams
- `disable_functions` — exec, passthru, shell_exec, system, popen, proc_open, proc_nice, proc_terminate, putenv, dl, show_source, phpinfo
- `expose_php = Off` — hide PHP version from HTTP headers
- `display_errors = Off` / `display_startup_errors = Off` — prevent error leakage to clients
- `log_errors = On` / `error_reporting = E_ALL` — log all errors server-side
- `session.cookie_httponly = 1` — prevent JavaScript access to session cookies
- `session.cookie_secure = 1` — require HTTPS for session cookies
- `session.use_strict_mode = 1` — reject uninitialized session IDs
- `session.use_only_cookies = 1` — prevent session ID in URLs
- `mail.add_x_header = 0` — prevent PHP version leakage in mail headers

### 5. php.ini — Performance & correctness

- `zlib.output_compression_level`: 9 → 6 — reduced CPU overhead under load
- `max_input_time`: 5 → 30 — consistent with 100MB upload limit
- `opcache.jit`: `function` → `tracing` — better JIT performance for PHP 8.4

### 6. start-container — Remove netcat dependency

- Replaced `nc -z` with `curl`-based TCP connectivity check
- Uses curl exit codes (6=DNS failure, 7=connection refused, 28=timeout) to determine port availability
- Works for both HTTP (Octane) and non-HTTP (Redis, Postgres) services

### 7. docker-compose.yml — Redis healthcheck

- Removed `-a "${REDIS_PASSWORD}"` flag from `redis-cli ping` — eliminated password leakage in process listings and container logs
- Uses local `127.0.0.1` connection which authenticates via `REDISCLI_AUTH` env var when available

## Risk Assessment

| Change | Risk | Mitigation |
|--------|------|------------|
| Bun version pin | Lock file incompatibility | Verify `bun.lock` format matches 1.2.x |
| Alpine version pin | Missing security patches | Re-pin on regular cadence |
| Removing busybox-extras | `nc` unavailable in containers | Replaced with curl-based check |
| `allow_url_fopen=0` | Some file operations may break | Test remote stream usage |
| `disable_functions` | Code using disabled functions breaks | HTTP container only (worker has separate restrictions) |
| Removing broad COPY | Missing runtime files | Verified extensions/conf.d targeted copies sufficient |