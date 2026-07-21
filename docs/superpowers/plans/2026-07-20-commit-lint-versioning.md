# Commit Lint + Versioning Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enforce Conventional Commits (local husky + CI) and auto-version on `main` with `semantic-release`, feeding GHCR semver Docker tags on `v*` pushes.

**Architecture:** bun installs husky/commitlint/Commitizen for local DX; GitHub Actions lint PR commits; `semantic-release` on `main` writes tags + CHANGELOG + GitHub Release (no npm publish); Docker workflow also triggers on `v*` tags.

**Tech Stack:** bun, husky, @commitlint/cli + @commitlint/config-conventional, commitizen + cz-conventional-changelog, semantic-release (+ changelog/git/github plugins), GitHub Actions

## Global Constraints

- Package manager: **bun** (`bun.lock` only; remove `package-lock.json`)
- Branch for release: **main**
- Continue from existing tags; if dry-run misses betas, add one-time stable `v0.1.0` baseline
- No npm registry publish (`private: true`)
- No PHP/Filament app code changes
- Scope optional in commitlint; types: feat|fix|docs|style|refactor|perf|test|build|ci|chore|revert
- Spec: `docs/superpowers/specs/2026-07-20-commit-lint-versioning-design.md`

---

## File structure

| File | Responsibility |
| --- | --- |
| `package.json` | Scripts (`prepare`, `commit`) + commitizen config + lock deps via bun |
| `bun.lock` | bun lockfile (updated) |
| `commitlint.config.js` | Conventional + stricter rules (ESM; package `"type": "module"`) |
| `.husky/commit-msg` | Run commitlint on commit message file |
| `.czrc` | Commitizen path → `cz-conventional-changelog` |
| `.releaserc.json` | semantic-release plugins/branches; no npm publish |
| `.github/workflows/commitlint.yml` | Lint all commits in a PR |
| `.github/workflows/release.yml` | Run semantic-release on push to `main` |
| `.github/workflows/build-and-deploy.yml` | Add `tags: ['v*']` trigger |
| `CHANGELOG.md` | Created empty/header; thereafter owned by semantic-release |
| `README.md` | Commit + release contributor notes |
| *(delete)* `package-lock.json` | Avoid dual lockfiles |

---

### Task 1: Install tooling + commitlint + husky + Commitizen

**Files:**
- Modify: `package.json`
- Create: `commitlint.config.js`
- Create: `.husky/commit-msg`
- Create: `.czrc`
- Modify: `bun.lock` (via bun)
- Delete: `package-lock.json`

**Interfaces:**
- Consumes: existing Vite `package.json` scripts/`devDependencies`
- Produces: `bun run commit`, husky `commit-msg` hook, `bunx commitlint` working against `commitlint.config.js`

- [ ] **Step 1: Remove npm lockfile**

```bash
rm -f package-lock.json
```

- [ ] **Step 2: Install devDependencies with bun**

```bash
bun add -d husky @commitlint/cli @commitlint/config-conventional commitizen cz-conventional-changelog
```

Expected: packages appear under `devDependencies`; `bun.lock` updates.

- [ ] **Step 3: Update `package.json` scripts and commitizen config**

Set `scripts` and add `config` (keep existing Vite deps/scripts). Final shape:

```json
{
    "private": true,
    "type": "module",
    "scripts": {
        "dev": "vite",
        "build": "vite build",
        "prepare": "husky",
        "commit": "cz"
    },
    "config": {
        "commitizen": {
            "path": "./node_modules/cz-conventional-changelog"
        }
    },
    "devDependencies": {
        "@commitlint/cli": "<installed>",
        "@commitlint/config-conventional": "<installed>",
        "@tailwindcss/forms": "^0.5.11",
        "@tailwindcss/postcss": "^4.2.2",
        "@tailwindcss/typography": "^0.5.19",
        "autoprefixer": "^10.4.27",
        "axios": "1.15.2",
        "commitizen": "<installed>",
        "cz-conventional-changelog": "<installed>",
        "husky": "<installed>",
        "laravel-vite-plugin": "^3.0",
        "postcss": "^8.5.8",
        "postcss-nesting": "^14.0.0",
        "tailwindcss": "^4.2.2",
        "vite": "8.0.10"
    }
}
```

Also create `.czrc`:

```json
{
  "path": "./node_modules/cz-conventional-changelog"
}
```

- [ ] **Step 4: Write `commitlint.config.js`**

```js
export default {
    extends: ['@commitlint/config-conventional'],
    rules: {
        'type-enum': [
            2,
            'always',
            [
                'feat',
                'fix',
                'docs',
                'style',
                'refactor',
                'perf',
                'test',
                'build',
                'ci',
                'chore',
                'revert',
            ],
        ],
        'header-max-length': [2, 'always', 100],
        'subject-empty': [2, 'never'],
        'subject-full-stop': [2, 'never', '.'],
        'body-max-line-length': [2, 'always', 100],
        'footer-max-line-length': [2, 'always', 100],
    },
};
```

- [ ] **Step 5: Init husky and add commit-msg hook**

```bash
bunx husky init
```

Replace `.husky/commit-msg` contents with:

```sh
bunx commitlint --edit "$1"
```

Ensure the file is executable:

```bash
chmod +x .husky/commit-msg
```

If `husky init` created a default `pre-commit` that runs tests/build, remove that default pre-commit file unless the repo already wanted it — this plan only requires `commit-msg`.

- [ ] **Step 6: Verify commitlint rejects bad / accepts good messages**

```bash
echo "bad message" | bunx commitlint
```

Expected: exit non-zero (type/subject errors).

```bash
echo "feat: add commit lint tooling" | bunx commitlint
```

Expected: exit 0.

- [ ] **Step 7: Commit**

```bash
git add package.json bun.lock commitlint.config.js .husky .czrc
git rm -f --ignore-unmatch package-lock.json
git commit -m "$(cat <<'EOF'
chore: add husky, commitlint, and commitizen

EOF
)"
```

Note: first commit after hook install must itself be conventional or use `HUSKY=0` only if hook blocks unexpectedly during bootstrap; prefer a valid message as above.

---

### Task 2: CI commitlint workflow

**Files:**
- Create: `.github/workflows/commitlint.yml`

**Interfaces:**
- Consumes: `commitlint.config.js`, bun lockfile, `@commitlint/*` from Task 1
- Produces: PR check that fails on non-conventional commits in the PR range

- [ ] **Step 1: Create `.github/workflows/commitlint.yml`**

```yaml
name: Commitlint

on:
  pull_request:
    branches: ["main"]

permissions:
  contents: read
  pull-requests: read

jobs:
  commitlint:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v4
        with:
          fetch-depth: 0

      - name: Setup bun
        uses: oven-sh/setup-bun@v2
        with:
          bun-version: latest

      - name: Install dependencies
        run: bun install --frozen-lockfile

      - name: Lint PR commits
        run: bunx commitlint --from ${{ github.event.pull_request.base.sha }} --to ${{ github.event.pull_request.head.sha }} --verbose
```

- [ ] **Step 2: Sanity-check YAML locally (optional)**

```bash
python3 -c "import yaml; yaml.safe_load(open('.github/workflows/commitlint.yml'))"
```

Expected: no exception (skip if PyYAML missing; CI will validate).

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/commitlint.yml
git commit -m "$(cat <<'EOF'
ci: add commitlint workflow for pull requests

EOF
)"
```

---

### Task 3: semantic-release config + release workflow

**Files:**
- Create: `.releaserc.json`
- Create: `.github/workflows/release.yml`
- Create: `CHANGELOG.md` (initial header)
- Modify: `package.json` (add semantic-release deps)

**Interfaces:**
- Consumes: conventional commits on `main`; existing git tags
- Produces: automated `vX.Y.Z` tags, `CHANGELOG.md` updates, GitHub Releases; no npm publish

- [ ] **Step 1: Install semantic-release packages**

```bash
bun add -d semantic-release @semantic-release/changelog @semantic-release/git @semantic-release/github @semantic-release/commit-analyzer @semantic-release/release-notes-generator
```

Do **not** rely on `@semantic-release/npm` publishing. If the default plugin set tries npm, disable it in `.releaserc.json` (next step).

- [ ] **Step 2: Create `.releaserc.json`**

```json
{
  "branches": ["main"],
  "plugins": [
    "@semantic-release/commit-analyzer",
    "@semantic-release/release-notes-generator",
    [
      "@semantic-release/changelog",
      {
        "changelogFile": "CHANGELOG.md"
      }
    ],
    [
      "@semantic-release/git",
      {
        "assets": ["CHANGELOG.md", "package.json"],
        "message": "chore(release): ${nextRelease.version} [skip ci]\n\n${nextRelease.notes}"
      }
    ],
    "@semantic-release/github"
  ]
}
```

Note: omitting `@semantic-release/npm` avoids registry publish. Do not add it.

- [ ] **Step 3: Create initial `CHANGELOG.md`**

```markdown
# Changelog

All notable changes to this project will be documented in this file.
This file is maintained by semantic-release.
```

- [ ] **Step 4: Create `.github/workflows/release.yml`**

```yaml
name: Release

on:
  push:
    branches: ["main"]

permissions:
  contents: read

jobs:
  release:
    runs-on: ubuntu-latest
    permissions:
      contents: write
      issues: write
      pull-requests: write
    steps:
      - name: Checkout
        uses: actions/checkout@v4
        with:
          fetch-depth: 0
          persist-credentials: true

      - name: Setup bun
        uses: oven-sh/setup-bun@v2
        with:
          bun-version: latest

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: "lts/*"

      - name: Install dependencies
        run: bun install --frozen-lockfile

      - name: Release
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        run: bunx semantic-release
```

Node setup is included because some semantic-release plugins expect a Node runtime alongside bun-installed packages.

- [ ] **Step 5: Dry-run baseline check**

```bash
bunx semantic-release --dry-run
```

Expected: prints analysis. Note the reported last/next version.

- If last release is missing / next would be `1.0.0` against intent to stay on `0.x`, create baseline tag (do **not** push until confirmed):

```bash
git tag v0.1.0 "$(git rev-list -n 1 v0.1.0-beta.2)"
git push origin v0.1.0
```

Re-run dry-run; expect next version to bump from `0.1.0` (e.g. `0.1.1` / `0.2.0` depending on commits since that point).

- [ ] **Step 6: Commit release tooling**

```bash
git add package.json bun.lock .releaserc.json .github/workflows/release.yml CHANGELOG.md
git commit -m "$(cat <<'EOF'
ci: add semantic-release for automated versioning

EOF
)"
```

---

### Task 4: Docker tag trigger + README

**Files:**
- Modify: `.github/workflows/build-and-deploy.yml` (trigger `on.push` only)
- Modify: `README.md`

**Interfaces:**
- Consumes: `v*` tags from semantic-release
- Produces: GHCR images tagged via existing `type=semver` metadata; contributor docs for commit/release

- [ ] **Step 1: Update Docker workflow triggers**

Change the top of `.github/workflows/build-and-deploy.yml` from:

```yaml
on:
  push:
    branches: ["main"]
  pull_request:
    branches: ["main"]
```

to:

```yaml
on:
  push:
    branches: ["main"]
    tags: ["v*"]
  pull_request:
    branches: ["main"]
```

Leave all jobs/steps unchanged.

- [ ] **Step 2: Update `README.md`**

Append (keep existing content):

```markdown
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
```

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/build-and-deploy.yml README.md
git commit -m "$(cat <<'EOF'
ci: build Docker images on version tags and document release flow

EOF
)"
```

---

### Task 5: End-to-end verification checklist

**Files:** none (verification only)

- [ ] **Step 1: Local hook**

```bash
# should fail
git commit --allow-empty -m "not conventional"
# should succeed
git commit --allow-empty -m "chore: verify commitlint hook"
```

Reset the empty success commit if you do not want it on the branch:

```bash
git reset --hard HEAD~1
```

(Only reset if that empty commit is the tip and was created solely for this check.)

- [ ] **Step 2: Confirm lockfile policy**

```bash
test ! -f package-lock.json && test -f bun.lock && echo OK
```

Expected: `OK`

- [ ] **Step 3: Confirm workflows exist**

```bash
test -f .github/workflows/commitlint.yml
test -f .github/workflows/release.yml
grep -q 'tags: \["v\*"\]' .github/workflows/build-and-deploy.yml && echo docker-tags-ok
```

- [ ] **Step 4: Final dry-run**

```bash
bunx semantic-release --dry-run
```

Expected: completes without trying to publish npm; reports next version or “no release”.

---

## Spec coverage (self-review)

| Spec requirement | Task |
| --- | --- |
| husky + commitlint local | Task 1 |
| Commitizen | Task 1 |
| Stricter commitlint rules / optional scope | Task 1 |
| CI PR commitlint | Task 2 |
| semantic-release on main | Task 3 |
| No npm publish | Task 3 (`.releaserc.json` omits npm plugin) |
| Continue from tags / baseline `v0.1.0` if needed | Task 3 Step 5 |
| Docker `v*` trigger | Task 4 |
| README note | Task 4 |
| Remove `package-lock.json` | Task 1 |
| CHANGELOG maintained by release | Task 3 |

No placeholders left after self-review.
