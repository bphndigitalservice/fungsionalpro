# FungsionalPro

## Documentation

Read full documentation [https://docs.fungsionalpro.bphn.go.id](here)

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
