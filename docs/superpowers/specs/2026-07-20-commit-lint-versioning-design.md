# Commit Lint, Conventional Commits, and Versioning Design

**Date:** 2026-07-20  
**Status:** Approved  
**Approach:** Heavy DX (Approach 3) — Commitizen + strict commitlint + semantic-release + Docker tag builds

## Goal

Enforce Conventional Commits locally and in CI, and automatically version the FungsionalPro app on merges to `main` using `semantic-release`, producing git tags, `CHANGELOG.md`, and GitHub Releases that feed existing GHCR semver Docker tags.

## Decisions (locked)

| Topic | Choice |
| --- | --- |
| Release automation | Auto on `main` via `semantic-release` |
| Commit enforcement | Local husky + CI PR check |
| Version history | Continue from existing tags (`v0.1.0-beta.1`, `v0.1.0-beta.2`) |
| Package manager | bun (`bun.lock` source of truth) |
| DX extras | Commitizen wizard + stricter commitlint |
| Docker | Also build on `v*` tag pushes so `type=semver` metadata works |

## Current state

- Laravel + Vite app; `package.json` is Vite-only today.
- No husky, commitlint, or release tooling.
- Commit messages are mixed (some `feat:` / `Fix:`, many freeform).
- Tags exist: `v0.1.0-beta.1`, `v0.1.0-beta.2`.
- `.github/workflows/build-and-deploy.yml` builds on push/PR to `main` and already declares `type=semver` Docker tags, but does not trigger on tag push — so semver image tags rarely apply.
- Both `bun.lock` and `package-lock.json` exist; bun is chosen as source of truth for this work.

## Architecture

Four layers:

1. **Local DX** — husky `commit-msg` runs commitlint; `bun run commit` launches Commitizen (`cz-conventional-changelog`).
2. **CI gate** — PR workflow lints every commit in the PR range.
3. **Release** — push to `main` runs `semantic-release` (analyzer, notes, changelog, git, github). No npm publish.
4. **Docker** — tag push `v*` triggers image build so GHCR gets `X.Y.Z` / `X.Y` / `X` tags.

```
local commit → husky commit-msg (commitlint)
     ↓
PR → commitlint.yml (lint all PR commits)
     ↓
merge main → release.yml (semantic-release)
     ↓ (if releasable commits)
tag vX.Y.Z + CHANGELOG commit + GitHub Release
     ↓
build-and-deploy.yml on tag v* → GHCR semver tags
```

Existing branch/PR Docker builds (sha / `latest`) remain.

## Components and files

| Path | Role |
| --- | --- |
| `commitlint.config.js` | Extends `@commitlint/config-conventional` + stricter rules |
| `.husky/commit-msg` | `bunx commitlint --edit "$1"` |
| `package.json` | Scripts: `prepare` (husky), `commit` (cz); new devDependencies |
| `.czrc` or package.json `config.commitizen` | Point to `cz-conventional-changelog` |
| `.releaserc.json` | semantic-release branches/plugins; npm publish disabled |
| `.github/workflows/commitlint.yml` | PR commit lint with bun |
| `.github/workflows/release.yml` | semantic-release on `main` |
| `.github/workflows/build-and-deploy.yml` | Add `push.tags: ['v*']` |
| `CHANGELOG.md` | Maintained by `@semantic-release/changelog` |
| `README.md` | Short “how to commit / release” note |
| `bun.lock` | Updated; **remove** `package-lock.json` so bun is sole lockfile |

No PHP/Filament application code changes. Version lives in git tags + `CHANGELOG.md`, not `composer.json`.

## Commitlint rules

- Allowed types: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `build`, `ci`, `chore`, `revert`
- Header max length: 100
- Subject: required, no trailing period
- Body/footer wrap: 100
- Scope: **optional** (encouraged in docs, not required)
- Breaking changes: `BREAKING CHANGE` footer or `type(scope)!:` → major

## semantic-release behavior

- Branch: `main`
- Plugins: `@semantic-release/commit-analyzer`, `@semantic-release/release-notes-generator`, `@semantic-release/changelog`, `@semantic-release/git`, `@semantic-release/github`
- Explicitly **do not** publish to npm (`private: true`; disable `@semantic-release/npm` / `npmPublish: false`)
- Bumps: `feat` → minor; `fix` → patch; breaking → major; `chore` / `docs` / `ci` / etc. → no release (defaults)
- Version baseline: honor existing tags; do not force a fresh `1.0.0`
- Prerelease/`beta` channels: **out of scope**; existing beta tags remain history only
- **Baseline caveat:** `main` is a stable channel. If dry-run does not detect `v0.1.0-beta.*` as last release, add a one-time stable baseline tag `v0.1.0` (same commit as latest beta or agreed tip) during setup so the next bump continues `0.x` as intended — not a jump to `1.0.0`

### Release job permissions

```yaml
permissions:
  contents: write
  issues: write
  pull-requests: write
```

Use `GITHUB_TOKEN` only. Checkout with `fetch-depth: 0`. Install via bun (`bun install --frozen-lockfile`), run `bunx semantic-release`.

## Docker integration

Extend `build-and-deploy.yml` triggers:

```yaml
on:
  push:
    branches: ["main"]
    tags: ["v*"]
  pull_request:
    branches: ["main"]
```

Existing `docker/metadata-action` semver patterns then apply when a `v*` tag is pushed by semantic-release.

## Error handling

| Case | Behavior |
| --- | --- |
| Bad local commit message | husky rejects; use `bun run commit` or amend |
| Bad message on PR | `commitlint.yml` fails; fix via amend/rebase |
| Push to `main` with no releasable commits | release job succeeds; no new tag |
| Release failure (perms, tag conflict) | job fails; fix config and re-run |

## Out of scope

- Rewriting historical commit messages to conventional format
- Publishing to the npm registry
- Automated beta/prerelease channels
- Embedding version strings in PHP application code
- Changing Filament/Laravel runtime behavior

## Verification

1. Local: invalid commit message fails husky; `bun run commit` produces a valid message.
2. PR: commitlint workflow fails on a deliberately bad commit in the range.
3. Dry-run: `bunx semantic-release --dry-run` shows expected next version from current tags.
4. After merge to `main` with a `feat`/`fix`: new tag, `CHANGELOG.md` update, GitHub Release, and (on tag) Docker semver tags.

## Success criteria

- Contributors can use Commitizen or hand-written conventional messages; both pass commitlint.
- PRs with non-conventional commits cannot merge without fixing messages (CI red).
- Merges to `main` with releasable commits automatically create the next semver tag continuing from existing history.
- GHCR receives versioned tags when releases happen.
