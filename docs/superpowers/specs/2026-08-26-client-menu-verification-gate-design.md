# Client Menu Verification Gate

**Date:** 2026-08-26  
**Status:** Approved for planning  
**Area:** Filament panel — navigation group `labels.nav.client_menu` (“Profil Saya”)

## Goal

For users whose system role is **client** and who are **not** SuperAdmin:

1. Under **Profil Saya**, only these menus are available to clients: **Identitas**, **Riwayat Kegiatan**, **Diklat/Pelatihan**, and **Riwayat Pendidikan**.
2. Until `clients.is_verified` is **Verified** (true/1), only **Identitas** is shown and reachable; the other three are hidden and blocked.
3. Direct URL access to a locked page redirects to Identitas with a notice.
4. Other Profil Saya menus are **SuperAdmin only**.

## Context

- Navigation group label: `__('labels.nav.client_menu')` → “Profil Saya”.
- **Identitas** = `App\Filament\Pages\Client\ClientProfilePage` (`labels.page.client_profile.nav`).
- Client verification: `Client.is_verified` cast to `App\Enums\Verified` (`Verified = 1`, `Unverified = 0`, `Rejected = 2`). Unlock only when status is `Verified`. Unverified and Rejected both lock.
- Existing gating pattern: `App\Concerns\Filament\ChecksPhotoUpload` on Kegiatan / Diklat / Pendidikan (photo required). New verification gate stacks as an additional AND for client-only users.
- Role helpers: `User::hasSystemRole(SystemRole::Client)`, `User::isSuperAdmin()`.

### Profil Saya inventory

| Item | Class | Client-only | SuperAdmin |
|---|---|---|---|
| Identitas | `ClientProfilePage` | Always (exempt from verification lock) | Allowed by existing auth |
| Riwayat Kegiatan | `ClientActivityResource` | Only if verified | Unrestricted by this feature |
| Diklat/Pelatihan | `ClientCompetenceResource` | Only if verified | Unrestricted by this feature |
| Riwayat Pendidikan | `ClientEducationResource` | Only if verified | Unrestricted by this feature |
| Riwayat Jabatan | `ClientPositionResource` | Hidden + blocked | Allowed (existing auth) |
| Riwayat Pangkat/Golongan | `ClientGradeResource` | Hidden + blocked | Allowed (existing auth) |
| Informasi Pendukung | `ClientDossierResource` | Hidden + blocked | Allowed (existing auth) |
| Informasi Dasar | `ClientBasicIdentityPage` | Hidden + blocked | Allowed (existing auth) |

**Out of scope:** other navigation groups (Angka Kredit, Verifikasi, admin menus). Non-client roles unchanged by this feature except SuperAdmin visibility for SuperAdmin-only items.

## Approach

**Approach A (chosen):** Shared Filament concern trait(s), modeled on `ChecksPhotoUpload`.

Rejected alternatives:

- Panel middleware matching routes/groups — brittle, less aligned with existing Filament resource/page gates.
- Dynamic Filament Shield permission revocation — fights static Shield permissions; weaker redirect UX.

## Architecture

```text
User requests Profil Saya page/resource
       │
       ▼
  is SuperAdmin?
       ├── yes → existing auth / current shouldRegisterNavigation rules
       └── no, has SystemRole::Client?
              │
              ▼
         Is page Identitas?
              ├── yes → allow
              └── no
                     │
                     ▼
                Is page in client allowlist
                (Kegiatan / Diklat / Pendidikan)?
                     ├── no → hide nav + redirect to Identitas + notice
                     └── yes
                            │
                            ▼
                       Client::current()->is_verified === Verified?
                            ├── no  → hide nav + redirect to Identitas + notice
                            └── yes → allow (AND existing ChecksPhotoUpload where used)
```

## Trait design

### `RequiresVerifiedClientProfile` (name may vary slightly in implementation)

Location: `app/Concerns/Filament/` (alongside `ChecksPhotoUpload`).

Helpers:

- `isClientWithoutSuperAdmin(): bool` — `hasSystemRole(Client)` and not `isSuperAdmin()`.
- `clientProfileIsVerified(): bool` — `Client::current()?->is_verified === Verified::Verified`.
- `clientMayAccessVerifiedMenuItem(): bool` — if not client-without-superadmin → `true` (this feature does not restrict); else require verified.

Methods to override / compose (mirror `ChecksPhotoUpload`):

- `shouldRegisterNavigation(): bool`
- `canAccess(): bool` and/or `canViewAny(): bool` (and create/edit as needed so URL access cannot bypass)

On denied access for a client-only user:

1. Flash / send Filament warning notification (i18n key, e.g. complete/verify identity first).
2. Redirect to `ClientProfilePage::getUrl()`.

### SuperAdmin-only Profil Saya items

Either the same concern with a flag/method (`requiresSuperAdminForClientMenu()`) or a small sibling trait `RequiresSuperAdminForClientMenu`:

- `shouldRegisterNavigation` / `canAccess` return true only when `isSuperAdmin()` (plus any existing checks such as `Client::current() !== null` where already present).
- Client-only users: hide + redirect to Identitas + notice (same UX as verification lock).

### Wiring

Apply verification trait to:

- `ClientActivityResource`
- `ClientCompetenceResource`
- `ClientEducationResource`

Apply SuperAdmin-only gate to:

- `ClientPositionResource`
- `ClientGradeResource`
- `ClientDossierResource`
- `ClientBasicIdentityPage`

Do **not** apply verification lock to `ClientProfilePage`.

Where `ChecksPhotoUpload` already exists, keep it; verification is an additional condition for client-only users (AND).

## Redirect & notice

- Target: Identitas (`ClientProfilePage`).
- Notice: Filament warning notification; Indonesian copy via lang files (e.g. “Lengkapi dan verifikasi Identitas terlebih dahulu”).
- Prefer redirect over hard 403 for client-only users on locked Profil Saya URLs.

## Testing

Feature tests:

1. Unverified (and Rejected) client-only user → nav shows Identitas only among client allowlist; Kegiatan / Diklat / Pendidikan URLs redirect to Identitas and surface notice.
2. Verified client-only user → Kegiatan / Diklat / Pendidikan reachable (subject to existing photo rules).
3. Client-only user → Jabatan / Pangkat / Dossier / Informasi Dasar hidden; URLs redirect (or otherwise blocked) away from those pages.
4. SuperAdmin → SuperAdmin-only Profil Saya items remain reachable under existing permissions.
5. Non-client, non-superadmin roles → not newly restricted by this feature for unrelated menus.

## Non-goals

- Changing how verifiers set `is_verified`.
- Changing menus outside `labels.nav.client_menu`.
- Replacing Filament Shield permissions wholesale.
