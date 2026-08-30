# Local Development Setup — FungsionalPro

**Date:** 2026-08-14  
**Status:** Approved for planning  
**Scope:** Hybrid local dev environment with PostgreSQL, Redis, and MinIO via Docker; DBeaver database restore; production alignment path.

## Goal

Enable a developer to continue work on FungsionalPro locally with:

- Native PHP (LERD) for fast iteration and Xdebug
- PostgreSQL, Redis, and MinIO running in Docker
- Restore of an existing PostgreSQL `.sql` backup via DBeaver
- A clear path to production using the existing `deployment/` stack

## Context

- **Stack:** Laravel 12, Filament, Octane (Swoole in production), Redis (session/cache/queue), S3-compatible storage.
- **Existing Docker:** `deployment/docker-compose.yml` runs app + worker + redis for production. No database service — DB is external.
- **Root `docker-compose.yml`:** Minimal single-app container only; not suitable for local dev.
- **No local `.env`:** Developer has a PostgreSQL plain-SQL backup (exported via DBeaver) but no environment file from the previous developer.
- **Developer machine:** Docker, PHP 8.5 (LERD), Composer, Bun — all available.

## Approach

**Approach 1 (chosen): Hybrid — native app, Docker infrastructure**

| Layer | Runtime |
|---|---|
| Laravel app | LERD + `php artisan serve` |
| Frontend | `bun run dev` (Vite HMR) |
| Queue | `php artisan queue:work` (optional terminal) |
| PostgreSQL, Redis, MinIO | `docker-compose.dev.yml` |

**Rejected alternatives:**

- **Full Docker (GHCR image):** Mirrors production but slow for active development; Xdebug and hot reload are harder.
- **Laravel Sail:** Not yet published in this repo; production uses custom Octane deployment — two Docker worlds to maintain.

## Architecture

```text
┌─────────────────────────────────────────────────────────┐
│  Local machine (LERD + Bun)                             │
│                                                         │
│  ┌──────────────┐   ┌──────────────┐   ┌─────────────┐ │
│  │ Laravel App  │   │ Vite (dev)   │   │ queue:work  │ │
│  │ :8000        │   │ :5173        │   │ (optional)  │ │
│  └──────┬───────┘   └──────────────┘   └──────┬──────┘ │
│         │                                       │       │
│         └───────────────┬───────────────────────┘       │
│                         │ localhost                     │
│  ┌──────────────────────▼──────────────────────────┐   │
│  │  docker compose -f docker-compose.dev.yml       │   │
│  │  ┌──────────┐ ┌───────┐ ┌──────────────────┐  │   │
│  │  │ Postgres │ │ Redis │ │ MinIO (:9000/9001)│  │   │
│  │  │ :5432    │ │ :6379 │ └──────────────────┘  │   │
│  │  └──────────┘ └───────┘                        │   │
│  └─────────────────────────────────────────────────┘   │
│                         ▲                               │
│                    DBeaver (restore .sql)               │
└─────────────────────────────────────────────────────────┘

Production (unchanged): deployment/docker-compose.yml
  → app + worker + redis (external managed PostgreSQL)
```

**Principles:**

- Dev uses root `.env`; production uses `deployment/.env` — never mixed.
- Same drivers in dev and prod: `pgsql`, Redis, S3 disk — only endpoints and credentials differ.
- Dev uses `php artisan serve`; production uses Octane in container.

## Docker Services — `docker-compose.dev.yml`

New file at repository root. Does **not** modify `deployment/docker-compose.yml`.

| Service | Image | Host port | Purpose |
|---|---|---|---|
| postgres | `postgres:16-alpine` | 5432 | Primary database (Laravel + DBeaver) |
| redis | `redis:7-alpine` | 6379 | Session, cache, queue |
| minio | `minio/minio:latest` | 9000 (API), 9001 (Console) | S3-compatible file storage |

**Persistent volumes:**

- `postgres_data` — survives container restarts
- `minio_data` — uploaded files persist

**Default dev credentials** (must match `.env`):

```env
POSTGRES_DB=fungsionalpro
POSTGRES_USER=fungsionalpro
POSTGRES_PASSWORD=secret

MINIO_ROOT_USER=minioadmin
MINIO_ROOT_PASSWORD=minioadmin
```

**Daily commands:**

```bash
docker compose -f docker-compose.dev.yml up -d
docker compose -f docker-compose.dev.yml down
docker compose -f docker-compose.dev.yml ps
docker compose -f docker-compose.dev.yml logs -f postgres
```

**Port conflict:** If host port 5432 is already in use, change mapping to `5433:5432` in compose and set `DB_PORT=5433` in `.env`.

## Local `.env` Configuration

Create `.env` from `.env.template` with these dev overrides:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_KEY=                          # php artisan key:generate

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=fungsionalpro
DB_USERNAME=fungsionalpro
DB_PASSWORD=secret

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=false
SESSION_DOMAIN=
OCTANE_HTTPS=false

QUEUE_CONNECTION=redis
CACHE_STORE=redis

MAIL_MAILER=log

FILESYSTEM_DISK=s3
FILAMENT_FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=fungsionalpro
AWS_ENDPOINT=http://127.0.0.1:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_URL=http://127.0.0.1:9000/fungsionalpro
```

**MinIO bucket:** Create bucket `fungsionalpro` once via Console at `http://localhost:9001` (login: `minioadmin` / `minioadmin`).

**Why `s3` disk, not `minio` disk:** Production template uses `FILESYSTEM_DISK=s3` with `AWS_*` variables. Local dev mirrors that pattern; only endpoint and credentials change for production.

## PHP Requirements (LERD)

Required extensions:

- `pdo_pgsql`
- `redis`
- `intl`, `mbstring`, `zip`, `gd` (standard Laravel/Filament)

Verify: `php -m | grep -E 'pdo_pgsql|redis'`

Octane/Swoole is **not** required locally — use `php artisan serve`.

## Database Restore via DBeaver

### Prerequisites

1. Start PostgreSQL: `docker compose -f docker-compose.dev.yml up -d postgres`
2. Database `fungsionalpro` is auto-created by Docker via `POSTGRES_DB`.

### Connect DBeaver

| Field | Value |
|---|---|
| Host | `localhost` |
| Port | `5432` (or `5433` if remapped) |
| Database | `fungsionalpro` |
| Username | `fungsionalpro` |
| Password | `secret` |

Test connection → download PostgreSQL driver if prompted → Finish.

### Restore `.sql` backup

**Method A — Execute Script (recommended for DBeaver plain-SQL exports):**

1. Right-click database `fungsionalpro` → **Tools** → **Execute Script**
2. Select the `.sql` backup file
3. Click **Start**; wait for completion
4. Refresh schema tree; verify tables exist

**Method B — SQL Editor (small files only):**

1. Right-click `fungsionalpro` → **SQL Editor** → **Open SQL Script**
2. Open `.sql` → **Execute SQL Script** (Ctrl+Alt+X)

### Post-restore

```bash
php artisan db:show
php artisan migrate --pretend   # preview pending migrations
php artisan migrate             # run only if backup is behind repo migrations
```

**Never run `migrate:fresh`** on a restored backup — it drops all data.

### Restore troubleshooting

| Error | Fix |
|---|---|
| `role "xxx" does not exist` | Backup references another owner; remove/replace `OWNER TO` clauses or create the role |
| `database already exists` | SQL contains `CREATE DATABASE`; skip that line or restore into empty DB |
| `permission denied` | Connect as `fungsionalpro` user |
| Port 5432 in use | Remap compose port or stop local PostgreSQL |

## Initial Setup Checklist

```bash
cd ~/Workdir/fungsionalpro

# 1. Start infrastructure
docker compose -f docker-compose.dev.yml up -d

# 2. Environment
cp .env.template .env
# Edit .env per section above
php artisan key:generate

# 3. Dependencies
composer install
bun install && bun run build

# 4. Restore DB via DBeaver (see above)

# 5. Create MinIO bucket "fungsionalpro" at http://localhost:9001

# 6. Optional
php artisan storage:link
```

## Daily Development Workflow

**Terminal 1 — infrastructure (if not running):**

```bash
docker compose -f docker-compose.dev.yml up -d
```

**Terminal 2 — Laravel:**

```bash
php artisan serve
```

**Terminal 3 — Vite:**

```bash
bun run dev
```

**Terminal 4 (optional) — queue:**

```bash
php artisan queue:work
```

**Useful commands:**

```bash
php artisan migrate
php artisan test
php artisan filament:user
php artisan tinker
```

## Production Alignment

| Aspect | Development | Production |
|---|---|---|
| Compose file | `docker-compose.dev.yml` | `deployment/docker-compose.yml` |
| Env file | `.env` (root) | `deployment/.env` |
| App runtime | `php artisan serve` | Octane (Swoole) in container |
| Database | PostgreSQL Docker | External managed PostgreSQL |
| Storage | MinIO local | AWS S3 or server MinIO |
| Redis password | `null` | Required |
| `APP_DEBUG` | `true` | `false` |
| `SESSION_SECURE_COOKIE` | `false` | `true` |
| `OCTANE_HTTPS` | `false` | `true` (behind reverse proxy) |

Shared between environments: `DB_CONNECTION=pgsql`, Redis drivers, `s3` filesystem disk — code paths stay identical.

## Files to Create (Implementation)

| File | Responsibility |
|---|---|
| `docker-compose.dev.yml` | PostgreSQL, Redis, MinIO services for local dev |
| `.env.example` | Documented local-dev defaults (optional; derived from `.env.template`) |
| `docs/local-development.md` | Human-readable setup guide (optional; can live in README section) |

**Not modified:** `deployment/docker-compose.yml`, `deployment/fungsionalpro.env` — production stack stays as-is.

## Error Handling & Edge Cases

- **Backup older than repo migrations:** Run `php artisan migrate` after restore; use `--pretend` first.
- **Backup newer than repo:** No migration needed; app may require latest code branch.
- **Missing MinIO bucket:** S3 uploads fail with clear AWS SDK error — create bucket via console.
- **Redis not running:** Session/cache/queue fail immediately — start compose stack.
- **PHP 8.5 vs project `^8.2`:** Supported; if extension issues arise, switch LERD to PHP 8.4.

## Testing the Setup

1. `docker compose -f docker-compose.dev.yml ps` — all services healthy
2. `php artisan db:show` — connects to PostgreSQL
3. `php artisan serve` + browser — app loads at `http://localhost:8000`
4. Login with credentials from restored backup (or create admin via `php artisan filament:user`)
5. Upload a file in Filament — verify object appears in MinIO console

## Out of Scope

- Modifying production deployment stack
- Converting MySQL backups (backup is confirmed PostgreSQL)
- CI/CD pipeline changes
- Automated MinIO bucket provisioning (manual one-time step via console is sufficient for dev)
