# Local Development Setup

Hybrid setup: **LERD (native PHP)** for the Laravel app, **Docker** for PostgreSQL, Redis, and MinIO.

Production deployment is documented in [README.md](../README.md#production-deployment). Local dev uses root `.env`; production uses `deployment/.env`.

## Prerequisites

| Tool | Purpose |
|---|---|
| Docker + Compose | PostgreSQL, Redis, MinIO |
| PHP 8.2+ (LERD) | Run Laravel |
| Composer | PHP dependencies |
| Bun | Frontend (Vite) |
| DBeaver | Restore database backup |

Verify PHP extensions:

```bash
php -m | grep -E 'pdo_pgsql|redis|intl|mbstring|zip|gd'
```

All six must appear. Enable missing extensions in LERD before continuing.

### LERD (isolated PHP network)

If you use **LERD**, PHP cannot reach Docker via `127.0.0.1`. Set in `.env`:

```env
DB_HOST=host.docker.internal
REDIS_HOST=host.docker.internal
AWS_ENDPOINT=http://host.docker.internal:9000
AWS_URL=http://host.docker.internal:9000/fungsionalpro
```

Keep `DB_PORT` / `REDIS_PORT` matching your `docker-compose.dev.env` host mappings (default `5432` / `6379`; use `5433` / `6381` if those ports are taken on the host).

**DBeaver** still connects via `localhost` on the host — not `host.docker.internal`.

## Quick start

```bash
cd ~/Workdir/fungsionalpro

# 1. Start infrastructure
cp docker-compose.dev.env.example docker-compose.dev.env   # adjust ports if needed
docker compose --env-file docker-compose.dev.env -f docker-compose.dev.yml up -d

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Dependencies
composer install
bun install && bun run build

# 4. Restore database (see below)

# 5. Create MinIO bucket (see below)

# 6. Optional
php artisan storage:link
```

## Docker services

| Service | Host port | Default credentials |
|---|---|---|
| PostgreSQL | 5432 | user/db: `fungsionalpro`, password: `secret` |
| Redis | 6379 | no password |
| MinIO API | 9000 | `minioadmin` / `minioadmin` |
| MinIO Console | 9001 | same as API |

Daily commands:

```bash
docker compose --env-file docker-compose.dev.env -f docker-compose.dev.yml up -d
docker compose --env-file docker-compose.dev.env -f docker-compose.dev.yml down
docker compose --env-file docker-compose.dev.env -f docker-compose.dev.yml ps
docker compose --env-file docker-compose.dev.env -f docker-compose.dev.yml logs -f postgres
```

### Port conflict

If default ports are already in use (common when PostgreSQL or Redis runs on the host), copy and edit the compose env file:

```bash
cp docker-compose.dev.env.example docker-compose.dev.env
# Edit POSTGRES_PORT / REDIS_PORT as needed
docker compose --env-file docker-compose.dev.env -f docker-compose.dev.yml up -d
```

Match Laravel `.env`: `DB_PORT` = `POSTGRES_PORT`, `REDIS_PORT` = compose `REDIS_PORT` mapping.

## Restore database with DBeaver

### Prerequisites

PostgreSQL container must be running:

```bash
docker compose -f docker-compose.dev.yml up -d postgres
```

Database `fungsionalpro` is created automatically by Docker.

### Connect

| Field | Value |
|---|---|
| Host | `localhost` |
| Port | `5432` (or `5433` if remapped) |
| Database | `fungsionalpro` |
| Username | `fungsionalpro` |
| Password | `secret` |

**Database → New Database Connection → PostgreSQL → Test Connection → Finish**

Download the PostgreSQL driver if DBeaver prompts you.

### Restore `.sql` backup

1. Right-click database **fungsionalpro** → **Tools** → **Execute Script**
2. Select your `.sql` backup file
3. Click **Start** and wait for completion
4. Refresh the schema tree — verify tables exist (`users`, `migrations`, etc.)

### After restore

```bash
php artisan db:show
php artisan migrate --pretend   # preview only
php artisan migrate             # run if backup is behind repo migrations
```

**Never run `migrate:fresh`** — it deletes all restored data.

### Restore troubleshooting

| Error | Fix |
|---|---|
| `role "xxx" does not exist` | Backup references another owner; remove/replace `OWNER TO` in SQL or create the role |
| `database already exists` | SQL contains `CREATE DATABASE`; skip that line |
| `permission denied` | Connect as user `fungsionalpro` |
| Port 5432 in use | Remap compose port or stop local PostgreSQL |

## MinIO bucket

1. Open [http://localhost:9001](http://localhost:9001)
2. Login: `minioadmin` / `minioadmin`
3. **Buckets → Create Bucket →** name: `fungsionalpro` → **Create**

Uploads in Filament use the `s3` disk pointed at this bucket.

## Daily workflow

**Terminal 1 — infrastructure (if not running):**

```bash
docker compose -f docker-compose.dev.yml up -d
```

**Terminal 2 — Laravel:**

```bash
php artisan serve
```

App: [http://localhost:8000](http://localhost:8000)

**Terminal 3 — Vite (hot reload):**

```bash
bun run dev
```

**Terminal 4 (optional) — queue worker:**

```bash
php artisan queue:work
```

## Useful commands

```bash
php artisan migrate
php artisan test
php artisan filament:user    # create admin if no login in backup
php artisan tinker
```

## Verify setup

```bash
docker compose -f docker-compose.dev.yml ps    # all services running
php artisan db:show                            # PostgreSQL connected
php artisan tinker --execute="Illuminate\Support\Facades\Redis::ping();"
```

Open [http://localhost:8000](http://localhost:8000) after `php artisan serve`. Log in with credentials from the restored backup.

## Dev vs production

| Aspect | Local | Production |
|---|---|---|
| Compose | `docker-compose.dev.yml` | `deployment/docker-compose.yml` |
| Env | `.env` (root) | `deployment/.env` |
| App | `php artisan serve` | Octane (Swoole) in container |
| Database | PostgreSQL Docker | External managed PostgreSQL |
| Storage | MinIO local | AWS S3 or server MinIO |
| `APP_DEBUG` | `true` | `false` |
| `SESSION_SECURE_COOKIE` | `false` | `true` |

Drivers stay the same: `pgsql`, Redis, `s3` disk.
