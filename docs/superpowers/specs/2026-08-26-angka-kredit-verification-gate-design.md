# Angka Kredit Verification Gate + Dashboard Notice

**Date:** 2026-08-26  
**Status:** Approved for planning  
**Area:** Filament — navigation group `labels.nav.client_point` (“Angka Kredit”) and Dashboard  
**Related:** `docs/superpowers/specs/2026-08-26-client-menu-verification-gate-design.md`

## Goal

For client-without-SuperAdmin users whose `clients.is_verified` is not **Verified**:

1. Hide and block all **client** Angka Kredit pages until verified.
2. Redirect locked Angka Kredit URLs to the **Dashboard**.
3. On Dashboard, show a **persistent** warning: “Lengkapi Identitas dan Tunggu Identitas anda di verifikasi” (manual dismiss only), replacing the current photo-only dashboard notice for this audience.

## Context

- Angka Kredit group label: `__('labels.nav.client_point')`.
- Client pages: `ClientPointList`, `ClientPointCreate`, `ClientPointEdit` (Edit has `$shouldRegisterNavigation = false`).
- These pages already gate on identity photo (`shouldRegisterNavigation` / mount `abort(403)` when photo missing).
- Rule-set (`ClientPointSubmissionBagResource`) sits in the same nav group but is SuperAdmin-facing; clients do not need it and must not gain it via this work.
- Dashboard already sends a persistent photo notice in `Dashboard::mount()` for clients without a photo — that path is replaced for unverified clients by the new copy.
- Verification helpers already exist: `App\Concerns\Filament\ClientMenuAccess`.

## Rules

| Actor / state | Angka Kredit (List/Create/Edit) | Dashboard notice |
|---|---|---|
| Client-without-SuperAdmin, not Verified | Hidden + URL blocked → Dashboard | Persistent: new verify-identity copy (replaces photo notice) |
| Client-without-SuperAdmin, Verified, no photo | Still blocked by existing photo gate | No verify notice (photo-only reminder out of scope) |
| Client-without-SuperAdmin, Verified + photo | Allowed | No verify notice |
| SuperAdmin / non-client | Unchanged by this feature (Rule-set remains SuperAdmin as today) | Unchanged |

Unlock condition: `Client.is_verified === Verified::Verified` only (Unverified and Rejected stay locked).

## Approach

**Approach A (chosen):** Extend existing `ClientMenuAccess` helpers; gate Angka Kredit pages with verification **AND** photo; redirect locked clients to Dashboard with notice; update `Dashboard::mount()` for the persistent message.

Rejected:

- Panel middleware on `/c/points*` — brittle vs Filament page APIs.
- New notice/event subsystem — overbuilt.

## Architecture

```text
Client hits Angka Kredit page
       │
       ▼
  client-without-SuperAdmin?
       ├── no  → existing photo / Shield behavior
       └── yes
              │
              ▼
         is_verified === Verified AND photo present?
              ├── yes → allow
              └── no  → hide nav; redirect to Dashboard
                         (+ warning notification on redirect)

Dashboard::mount (client-without-SuperAdmin)
       │
       ▼
  is_verified === Verified?
       ├── no  → persistent warning (new copy); do NOT send old photo-only notice
       └── yes → no verify notice (photo reminder not reintroduced here)
```

## Implementation shape

### Helpers

Reuse:

- `ClientMenuAccess::isClientWithoutSuperAdmin()`
- `ClientMenuAccess::clientProfileIsVerified()`
- `ClientMenuAccess::clientMayAccessVerifiedClientMenuItem()`

Optional thin helper for “may access Angka Kredit as client” = verified menu item **AND** photo present (photo check can stay local to point pages if clearer).

### Page gating

On `ClientPointList`, `ClientPointCreate`, `ClientPointEdit`:

- `shouldRegisterNavigation` / access paths require verified (for client-without-SuperAdmin) **and** existing photo rule.
- Redirect trait/helper sibling to `RedirectsLockedClientMenuAccess` that:
  - Targets Dashboard URL (not Identitas)
  - Uses the new notice copy (or a dedicated redirect title; Dashboard owns the persistent copy)
  - Prefer redirect over bare 403 for locked client-without-SuperAdmin
  - Include hydrate/mount halt behavior consistent with the Profil Saya redirect trait (so Livewire does not 403 after redirect)

Do **not** change Profil Saya redirect targets.

### Rule-set

No change required for client visibility. Do not grant Rule-set to clients. SuperAdmin access remains as today.

### Dashboard notice

In `App\Filament\Pages\Dashboard::mount()`:

- For client-without-SuperAdmin with `is_verified !== Verified`:
  - `Notification::make()->warning()->persistent()->title/body` with the Indonesian string via lang key
  - Do **not** also send the old “Lengkapi Pengisian Identitas Terlebih Dahulu” photo notice in that branch
- Once Verified: do not send this verify notice
- Persistence: Filament `persistent()` — stays until user closes; re-send on next Dashboard visit while still unverified

### i18n

Add keys under `lang/id/labels.php` and `lang/en/labels.php` (e.g. `page.dashboard.verify_identity_required`) with exact ID copy:

> Lengkapi Identitas dan Tunggu Identitas anda di verifikasi

English equivalent for completeness.

## Testing

1. Unverified (and Rejected) client-without-SuperAdmin → Angka Kredit List/Create nav hidden; List/Create URL redirects to Dashboard.
2. Verified client with photo → Angka Kredit nav allowed (Edit remains non-nav).
3. Verified client without photo → still blocked by photo gate.
4. Dashboard mount for unverified client → persistent notification with the new copy; old photo-only notice not sent.
5. SuperAdmin / Rule-set → not newly restricted for SuperAdmin; clients still do not see Rule-set.

## Non-goals

- Changing verifier workflows or `is_verified` writes.
- Reintroducing a separate photo-only Dashboard notice for verified clients in this change.
- Granting or redesigning Rule-set for clients.
- Changing Profil Saya Identitas redirect behavior.
