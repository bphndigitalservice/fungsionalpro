# FungsionalPro

## Documentation

Read full documentation [https://docs.fungsionalpro.bphn.go.id](here)

## Local Development

For local setup with PostgreSQL, Redis, and MinIO via Docker, see [docs/local-development.md](docs/local-development.md).

## Production Deployment

This application ships a production Docker Compose stack in `deployment/`.
The recommended production layout is:

```text
/opt/fungsionalpro
├── deployment/
│   ├── docker-compose.yml
│   ├── fungsionalpro.env
│   ├── php.ini
│   ├── php-ini-restrictions.ini
│   ├── start-container
│   └── octane/
│       └── Swoole/
│           └── supervisord.swoole.conf
└── ...
```

### 1. Prepare the server

- Install Docker Engine and the Docker Compose plugin
- Ensure ports `80` / `443` are handled by your reverse proxy and `8000` is reachable from it
- Create the app directory, for example `/opt/fungsionalpro`

### 2. Copy the deployment files

Clone the repository to the server:

```bash
git clone <your-repo-url> /opt/fungsionalpro
cd /opt/fungsionalpro
```

Create the production env file from the template:

```bash
cp deployment/fungsionalpro.env deployment/.env
```

Then edit `deployment/.env` and fill at least:

- `APP_KEY`
- `APP_URL`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `REDIS_PASSWORD`
- `MAIL_*`
- `AWS_*`

Generate an app key if you do not already have one:

```bash
php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'
```

Paste the generated value into `APP_KEY` in `deployment/.env`.

### 3. Start the production stack

Run Docker Compose from the `deployment/` directory so the relative bind mounts resolve correctly:

```bash
cd /opt/fungsionalpro/deployment
docker compose --env-file .env up -d
```

This starts:

- `app`: Laravel Octane HTTP server
- `worker`: Redis queue workers
- `redis`: cache, session, and queue backend

### 4. First deployment

For the first deployment only, set this in `deployment/.env`:

```env
RUNNING_MIGRATIONS_AND_SEEDERS=true
```

Start or restart the stack:

```bash
cd /opt/fungsionalpro/deployment
docker compose --env-file .env up -d
```

After the first successful boot, change it back to:

```env
RUNNING_MIGRATIONS_AND_SEEDERS=false
```

Then restart:

```bash
docker compose --env-file .env up -d
```

### 5. Updating production

Pull the latest code and restart the services:

```bash
cd /opt/fungsionalpro
git pull
cd deployment
docker compose --env-file .env up -d
```

If the image tag changed and you want to force a fresh pull:

```bash
docker compose --env-file .env pull
docker compose --env-file .env up -d
```

### 6. Logs and health checks

View service status:

```bash
cd /opt/fungsionalpro/deployment
docker compose --env-file .env ps
```

View logs:

```bash
docker compose --env-file .env logs -f app
docker compose --env-file .env logs -f worker
docker compose --env-file .env logs -f redis
```

### 7. Important deployment notes

- Run Compose from `deployment/`, not the repository root, unless you also rewrite the bind-mount paths
- The stack expects `deployment/.env`, not the root `.env`
- `OCTANE_WORKERS=2` and `WORKER_NUMPROCS=2` are conservative defaults for a modest VPS
- Redis is configured with `512mb` max memory and `volatile-lru` eviction to avoid evicting queue keys
- `start-container` warms Laravel caches on boot with `config:cache`, `event:cache`, `route:cache`, and `view:cache`

### 8. Reverse proxy

The app container listens on port `8000`. Put Nginx, Caddy, Traefik, or another reverse proxy in front of it and forward traffic to:

```text
127.0.0.1:8000
```

Also set:

- `APP_URL` to the public HTTPS URL
- `TRUSTED_PROXY` to your proxy IP or CIDR ranges

### 9. Common mistakes

- `not a directory` mount errors:
  usually means `docker compose` was run from the wrong directory for the relative bind mounts
- `bootstrap/cache directory must be present and writable`:
  tmpfs mounts must use `uid=1000,gid=1000` so the `octane` user can write; pull the latest `deployment/docker-compose.yml`
- `/var/run/supervisor/supervisord.pid does not exist`:
  `/run` is a tmpfs; `start-container` must recreate `/run/supervisor` on boot — pull the latest `deployment/start-container`
- slow or inconsistent pages:
  verify `REDIS_PASSWORD` matches across the env file and Redis service
- boot loops:
  check `APP_KEY`, database credentials, and `APP_URL`

## Todo

- [x] Registration
- [x] Email Verification
- [x] Forgot Password
- [x] JF Profile Update
- [x] JF Profile Verification
- [x] JF AK Submission
- [x] Verify JF AK Submission
- [x] Verifier Access Management
- [x] Role Access Management
- [x] User Management
- [x] Minio file upload
- [ ] Verifier invitation link
- [ ] Minio temporary link

## Commits and releases

This repo uses [Conventional Commits](https://www.conventionalcommits.org/).

### Local commits

- Recommended: `bun run commit` (Commitizen wizard)
- Or hand-write: `feat: …`, `fix: …`, `chore: …`, etc.
- husky runs commitlint on `commit-msg` and rejects non-conforming messages

### Pull requests

- CI workflow `Commitlint` checks every commit in the PR against `main`

### Releases

- Merges to `main` run `semantic-release`
- Releasable commits (`feat`, `fix`, breaking) create a `vX.Y.Z` tag, update `CHANGELOG.md`, and publish a GitHub Release
- Docker builds also run on `v*` tags so GHCR gets semver image tags
