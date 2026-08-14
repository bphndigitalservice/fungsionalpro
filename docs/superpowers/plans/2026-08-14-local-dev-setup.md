# Local Development Setup Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bootstrap a hybrid local dev environment — Docker for PostgreSQL, Redis, and MinIO; native PHP (LERD) for the Laravel app — so a developer can restore a PostgreSQL backup via DBeaver and continue development with production-aligned drivers.

**Architecture:** Add `docker-compose.dev.yml` at repo root (does not touch `deployment/`). Provide `.env.example` with local defaults and `docs/local-development.md` as the human setup guide. Developer copies `.env.example` → `.env`, starts compose, restores backup in DBeaver, runs `composer install` + `bun run dev` + `php artisan serve`.

**Tech Stack:** Docker Compose, PostgreSQL 16, Redis 7, MinIO, Laravel 12, PHP 8.2+ (LERD 8.5), Bun/Vite, DBeaver

## Global Constraints

- Spec: `docs/superpowers/specs/2026-08-14-local-dev-setup-design.md`
- Do **not** modify `deployment/docker-compose.yml` or `deployment/fungsionalpro.env`
- Dev env file: root `.env` (gitignored); committed template: `.env.example`
- `DB_CONNECTION=pgsql`, Redis for session/cache/queue, `FILESYSTEM_DISK=s3` pointing at local MinIO
- Default dev credentials: Postgres `fungsionalpro`/`secret`, MinIO `minioadmin`/`minioadmin`
- Never run `migrate:fresh` on restored backup
- Conventional commits in English; commit after each task
- Octane/Swoole not required locally — use `php artisan serve`

---

## File structure

| File | Responsibility |
| --- | --- |
| `docker-compose.dev.yml` | PostgreSQL, Redis, MinIO services + named volumes |
| `.env.example` | Local-dev defaults; copy to `.env` |
| `docs/local-development.md` | Step-by-step setup, DBeaver restore, daily workflow |
| `README.md` | Add "Local Development" section linking to the guide |
| `.env` | **Gitignored** — created locally from `.env.example` |
| `deployment/*` | **No changes** |

---

### Task 1: Docker Compose dev stack

**Files:**
- Create: `docker-compose.dev.yml`

**Interfaces:**
- Consumes: nothing (first infrastructure task)
- Produces: services `postgres`, `redis`, `minio` reachable on host ports `5432`, `6379`, `9000`, `9001`

- [ ] **Step 1: Create `docker-compose.dev.yml`**

```yaml
services:
  postgres:
    image: postgres:16-alpine
    container_name: fungsionalpro-postgres
    restart: unless-stopped
    ports:
      - "${POSTGRES_PORT:-5432}:5432"
    environment:
      POSTGRES_DB: "${POSTGRES_DB:-fungsionalpro}"
      POSTGRES_USER: "${POSTGRES_USER:-fungsionalpro}"
      POSTGRES_PASSWORD: "${POSTGRES_PASSWORD:-secret}"
    volumes:
      - postgres_data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${POSTGRES_USER:-fungsionalpro} -d ${POSTGRES_DB:-fungsionalpro}"]
      interval: 5s
      timeout: 3s
      retries: 5

  redis:
    image: redis:7-alpine
    container_name: fungsionalpro-redis
    restart: unless-stopped
    ports:
      - "${REDIS_PORT:-6379}:6379"
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 5s
      timeout: 3s
      retries: 5

  minio:
    image: minio/minio:latest
    container_name: fungsionalpro-minio
    restart: unless-stopped
    command: server /data --console-address ":9001"
    ports:
      - "${MINIO_API_PORT:-9000}:9000"
      - "${MINIO_CONSOLE_PORT:-9001}:9001"
    environment:
      MINIO_ROOT_USER: "${MINIO_ROOT_USER:-minioadmin}"
      MINIO_ROOT_PASSWORD: "${MINIO_ROOT_PASSWORD:-minioadmin}"
    volumes:
      - minio_data:/data
    healthcheck:
      test: ["CMD", "mc", "ready", "local"]
      interval: 5s
      timeout: 3s
      retries: 5
      start_period: 10s

volumes:
  postgres_data:
  minio_data:
```

- [ ] **Step 2: Start stack and verify health**

Run:

```bash
docker compose -f docker-compose.dev.yml up -d
docker compose -f docker-compose.dev.yml ps
```

Expected: `postgres`, `redis`, `minio` all **running** (postgres/redis **healthy**; minio may show healthy after start_period).

Run:

```bash
docker compose -f docker-compose.dev.yml exec postgres pg_isready -U fungsionalpro -d fungsionalpro
redis-cli -h 127.0.0.1 ping
curl -sf -o /dev/null -w "%{http_code}" http://127.0.0.1:9000/minio/health/live
```

Expected: `accepting connections`, `PONG`, `200`.

- [ ] **Step 3: Commit**

```bash
git add docker-compose.dev.yml
git commit -m "build: add docker compose stack for local development"
```

---

### Task 2: Local environment template

**Files:**
- Create: `.env.example`

**Interfaces:**
- Consumes: Task 1 service ports and credentials
- Produces: committed env template; developer copies to `.env` and runs `php artisan key:generate`

- [ ] **Step 1: Create `.env.example`**

Full file content:

```env
# =============================================================================
# FungsionalPro — Local Development Environment
# =============================================================================
# Copy to .env:  cp .env.example .env
# Then run:     php artisan key:generate
# =============================================================================

APP_NAME=FungsionalPro
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Jakarta
TRUSTED_PROXY=
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID
APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=database

APP_PORT=8000
CONTAINER_MODE=http
OCTANE_SERVER=swoole
OCTANE_HTTPS=false
RUNNING_MIGRATIONS_AND_SEEDERS=false
WORKER_NUMPROCS=2

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=fungsionalpro
DB_USERNAME=fungsionalpro
DB_PASSWORD=secret

SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax
SESSION_PATH=/
SESSION_DOMAIN=
SESSION_CONNECTION=default

QUEUE_CONNECTION=redis
CACHE_STORE=redis
CACHE_PREFIX=fungsionalpro_cache_

BCRYPT_ROUNDS=12

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0

LOG_CHANNEL=stack
LOG_LEVEL=debug
LOG_STACK=daily
LOG_DEPRECATIONS_CHANNEL=null

MAIL_MAILER=log
MAIL_HOST=localhost
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=dev@localhost
MAIL_FROM_NAME="${APP_NAME}"

FILESYSTEM_DISK=s3
FILAMENT_FILESYSTEM_DISK=s3

AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=fungsionalpro
AWS_ENDPOINT=http://127.0.0.1:9000
AWS_URL=http://127.0.0.1:9000/fungsionalpro
AWS_USE_PATH_STYLE_ENDPOINT=true

BROADCAST_CONNECTION=log

VITE_APP_NAME="${APP_NAME}"
```

- [ ] **Step 2: Verify `.env.example` is not gitignored**

Run:

```bash
git check-ignore -v .env.example || echo "not ignored"
```

Expected: `not ignored` (file will be committed).

- [ ] **Step 3: Commit**

```bash
git add .env.example
git commit -m "chore: add local development environment template"
```

---

### Task 3: Local development documentation

**Files:**
- Create: `docs/local-development.md`
- Modify: `README.md` (add Local Development section after Documentation header)

**Interfaces:**
- Consumes: Task 1 compose file, Task 2 `.env.example`
- Produces: human-readable guide covering setup, DBeaver restore, daily workflow, troubleshooting

- [ ] **Step 1: Create `docs/local-development.md`**

Include these sections (write full prose, not stubs):

1. **Prerequisites** — Docker, PHP 8.2+ (LERD), Composer, Bun, DBeaver; extension check command
2. **Quick start** — numbered steps: clone, `docker compose -f docker-compose.dev.yml up -d`, `cp .env.example .env`, `php artisan key:generate`, `composer install`, `bun install && bun run build`
3. **Restore database with DBeaver** — connection table (host/port/db/user/password), Execute Script steps, post-restore `php artisan db:show` + `migrate --pretend`
4. **MinIO bucket** — console URL, login, create bucket `fungsionalpro`
5. **Daily workflow** — three terminals: compose, `php artisan serve`, `bun run dev`; optional `queue:work`
6. **Troubleshooting** — port conflicts, missing extensions, restore errors (from spec table)
7. **Production note** — point to `README.md` Production Deployment section; dev uses root `.env`, prod uses `deployment/.env`

- [ ] **Step 2: Add Local Development section to `README.md`**

Insert after the Documentation link block (around line 5):

```markdown
## Local Development

For local setup with PostgreSQL, Redis, and MinIO via Docker, see [docs/local-development.md](docs/local-development.md).
```

- [ ] **Step 3: Commit**

```bash
git add docs/local-development.md README.md
git commit -m "docs: add local development setup guide"
```

---

### Task 4: Bootstrap local `.env` and PHP dependencies

**Files:**
- Create: `.env` (local only, gitignored)
- Modify: `vendor/` via `composer install`

**Interfaces:**
- Consumes: `.env.example`, running Docker stack from Task 1
- Produces: valid `APP_KEY`, installed Composer packages

- [ ] **Step 1: Verify PHP extensions**

Run:

```bash
php -m | grep -E 'pdo_pgsql|redis|intl|mbstring|zip|gd'
```

Expected: all six extensions listed. If `pdo_pgsql` or `redis` missing, install/enable in LERD before continuing.

- [ ] **Step 2: Create local `.env` and generate app key**

Run:

```bash
cp .env.example .env
php artisan key:generate
```

Expected: `.env` updated with `APP_KEY=base64:...`

- [ ] **Step 3: Install Composer dependencies**

Run:

```bash
composer install
```

Expected: exit 0, `vendor/` populated.

- [ ] **Step 4: Verify Laravel boots (DB may be empty — that's OK for now)**

Run:

```bash
php artisan about --only=environment
```

Expected: `Environment ................ local`, `Debug Mode ................. ENABLED`

**Do not commit** `.env` or `vendor/`.

---

### Task 5: Restore database via DBeaver (manual)

**Files:**
- None (manual DBeaver + optional migration)

**Interfaces:**
- Consumes: running `postgres` service, developer's `.sql` backup file
- Produces: populated `fungsionalpro` database with tables from backup

- [ ] **Step 1: Ensure PostgreSQL is running**

Run:

```bash
docker compose -f docker-compose.dev.yml up -d postgres
docker compose -f docker-compose.dev.yml exec postgres pg_isready -U fungsionalpro
```

Expected: `accepting connections`

- [ ] **Step 2: Connect DBeaver**

| Field | Value |
|---|---|
| Host | `localhost` |
| Port | `5432` |
| Database | `fungsionalpro` |
| Username | `fungsionalpro` |
| Password | `secret` |

Test connection → Finish.

- [ ] **Step 3: Restore backup**

1. Right-click database `fungsionalpro` → **Tools** → **Execute Script**
2. Select your `.sql` backup file
3. Click **Start**; wait for completion
4. Refresh schema — verify tables exist (e.g. `users`, `migrations`)

- [ ] **Step 4: Verify from Laravel**

Run:

```bash
php artisan db:show
php artisan migrate --pretend
```

Expected: `db:show` lists PostgreSQL connection with table count > 0. `migrate --pretend` either shows pending migrations or "Nothing to migrate".

If pending migrations exist (backup older than repo):

```bash
php artisan migrate
```

**Never run `migrate:fresh`.**

---

### Task 6: MinIO bucket and frontend assets

**Files:**
- None (manual MinIO console + bun build)

**Interfaces:**
- Consumes: running `minio` service, `.env` AWS_* vars
- Produces: bucket `fungsionalpro`, compiled frontend assets

- [ ] **Step 1: Create MinIO bucket**

1. Open `http://localhost:9001`
2. Login: `minioadmin` / `minioadmin`
3. **Buckets** → **Create Bucket** → name: `fungsionalpro` → Create

- [ ] **Step 2: Install and build frontend**

Run:

```bash
bun install
bun run build
```

Expected: exit 0, `public/build/manifest.json` exists.

- [ ] **Step 3: Optional storage link**

Run:

```bash
php artisan storage:link
```

Expected: `public/storage` → `storage/app/public`

---

### Task 7: End-to-end smoke test

**Files:**
- None

**Interfaces:**
- Consumes: all prior tasks complete
- Produces: confirmed working local dev environment

- [ ] **Step 1: Verify all Docker services**

Run:

```bash
docker compose -f docker-compose.dev.yml ps
```

Expected: postgres, redis, minio running.

- [ ] **Step 2: Verify database connection**

Run:

```bash
php artisan db:show
```

Expected: PostgreSQL, database `fungsionalpro`, tables listed.

- [ ] **Step 3: Start app and verify HTTP response**

Run in background or separate terminal:

```bash
php artisan serve
```

Run:

```bash
curl -sf -o /dev/null -w "%{http_code}" http://127.0.0.1:8000
```

Expected: `200` or `302` (redirect to login is fine).

Stop serve when done: `Ctrl+C` or kill the process.

- [ ] **Step 4: Verify Redis session driver**

Run:

```bash
php artisan tinker --execute="Illuminate\Support\Facades\Redis::ping();"
```

Expected: output `PONG` or `true`.

- [ ] **Step 5: Document completion**

Checklist for developer sign-off:

- [ ] Docker stack healthy
- [ ] `.env` configured with `APP_KEY`
- [ ] Database restored and `db:show` works
- [ ] MinIO bucket `fungsionalpro` exists
- [ ] `php artisan serve` loads in browser
- [ ] Login works with credentials from backup (or create admin: `php artisan filament:user`)

No commit for this task unless smoke-test fixes were needed in repo files.

---

## Spec coverage checklist

| Spec requirement | Task |
|---|---|
| `docker-compose.dev.yml` with postgres, redis, minio | Task 1 |
| Persistent volumes | Task 1 |
| Local `.env` with pgsql + MinIO s3 settings | Task 2, 4 |
| DBeaver restore workflow | Task 3 (docs), Task 5 (manual) |
| PHP extension verification | Task 4 |
| MinIO bucket creation | Task 3 (docs), Task 6 |
| Daily dev workflow documented | Task 3 |
| Production alignment note | Task 3 |
| Do not modify `deployment/` | Global constraint |
| End-to-end testing | Task 7 |
| README link | Task 3 |

## Out of scope (confirmed)

- Production deployment changes
- Automated MinIO bucket provisioning
- CI/CD changes
- MySQL backup conversion
