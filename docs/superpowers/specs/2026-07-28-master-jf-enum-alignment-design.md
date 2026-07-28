# Master JF Enum Alignment with Client

**Date:** 2026-07-28  
**Status:** Approved for planning  
**Resource:** `MasterJfResource` / `MasterJf` model

## Goal

Make Master JF **Kluster** (`type`), **Status**, and **Status Kepegawaian** use the same enum values and labels as Client (where Client already has enums), so selects, filters, columns, and stored data stay consistent.

## Context

- Client stores `type` as `ClientCluster` (`central`, `local_province`, `local_regency`) and `status` as `ClientStatus` (`active`, `non_active_ctln`, …), with labels from `getLabel()`.
- Client `jenis_kepegawaian` is inline `PNS` / `PPPK` (no enum).
- Master JF today:
  - `type`: free text, UI label **Tipe**, filter via distinct values
  - `status`: Indonesian labels stored as values (`Aktif`, `CTLN`, …) via `MasterJf::statusOptions()`
  - `status_kepegawaian`: `PNS` / `PPPK` via `MasterJf::statusKepegawaianOptions()`
- `ClientMatchingService` fuzzy-matches Indonesian status strings onto `ClientStatus`.
- Client form/filter hardcodes shorter cluster labels (Pusat / Provinsi / Kab/Kota); those differ from `ClientCluster::getLabel()`. This work does **not** change Client UI.

## Decisions

| Topic | Choice |
| --- | --- |
| Alignment depth | Reuse Client enums on Master JF (casts + Filament `options(Enum::class)`) + data migration |
| Status Kepegawaian | New `JenisKepegawaian` enum for Master JF only; Client stays inline |
| Kluster labels | Use `ClientCluster::getLabel()` (Kementerian Lembaga / Pemda - Provinsi / Pemda - Kabupaten/Kota) |
| Unmapped historical `type` | Set to `null` |

## Approach

**Approach 1 (chosen):** Cast Master JF fields to shared/new enums, migrate stored values, wire Filament/widgets/import/matching to enums.

Rejected:

- Master-JF-only duplicate enums — labels drift again.
- Shared option helpers without casts — stored values stay inconsistent with Client.

## Architecture

```text
ClientCluster / ClientStatus / JenisKepegawaian
        │
        ├── MasterJf casts (type, status, status_kepegawaian)
        ├── MasterJfResource form / table / filters
        ├── Master JF stats widgets (iterate enum cases)
        ├── MasterJfFactory + MasterJfImport (normalize to enum values)
        └── ClientMatchingService (assign status enum directly when present)
```

## Data model

### Casts on `MasterJf`

| Column | Cast | UI label |
| --- | --- | --- |
| `type` | `ClientCluster` | Kluster |
| `status` | `ClientStatus` | Status |
| `status_kepegawaian` | `JenisKepegawaian` | Status Kepegawaian |

DB column names stay the same (`type` is not renamed).

### New enum: `JenisKepegawaian`

- Cases: `PNS = 'PNS'`, `PPPK = 'PPPK'`
- Implements `HasLabel` (labels equal values)
- Used only by Master JF for now

### Remove helpers

- Remove `MasterJf::statusOptions()` and `MasterJf::statusKepegawaianOptions()`. Call sites use the enum classes (or `Enum::cases()` / Filament `options(Enum::class)`) directly.
- Keep `pengangkatanOptions()` and `distinctOptions()` unchanged.

## Data migration

One-shot migration (or data update in a migration) before relying on casts in production:

### `status`

Map known Indonesian labels → `ClientStatus` values, e.g.:

| Old value | New value |
| --- | --- |
| Aktif | `active` |
| Mengundurkan diri | `non_active_resign` |
| Diberhentikan Sementara sebagai PNS | `non_active_suspended` |
| CTLN | `non_active_ctln` |
| Tugas belajar > 6 Bulan | `non_active_study_leave` |
| Ditugaskan secara penuh di luar jabatan | `non_active_external_assignment` |
| Tidak Memenuhi Persyaratan Jabatan | `non_active_doesnt_meet_role_requirement` |

Unmapped → `null`. Values that are already enum values stay as-is.

### `type`

Map known synonyms → `ClientCluster` values, including enum values and common Indonesian labels (e.g. Pusat / Kementerian Lembaga → `central`, Provinsi / Pemda - Provinsi → `local_province`, Kab/Kota / Pemda - Kabupaten/Kota → `local_regency`). Unmapped → `null`.

### `status_kepegawaian`

Keep `PNS` / `PPPK`. Invalid → `null`.

## Filament UI

### Form

- `type`: Select, label **Kluster**, `options(ClientCluster::class)`
- `status`: `options(ClientStatus::class)`
- `status_kepegawaian`: `options(JenisKepegawaian::class)`

### Table & filters

- Columns show enum labels via Filament `HasLabel` support.
- Filters use the same enum classes (replace distinct options for `type` and hardcoded option arrays for status / status_kepegawaian).

## Widgets

- `MasterJfNumbersByStatusOverview` and `MasterJfNumbersByStatusKepegawaianOverview` iterate enum cases (same pattern as `ClientNumbersByStatusOverview`), not removed option helpers.

## Import

- `MasterJfImport` accepts enum value **or** known Indonesian label for type/status/status_kepegawaian.
- Normalize to enum value before save.
- Unknown → `null` (row still imported).

## Client matching

- When Master JF `status` is a valid `ClientStatus`, assign it directly to the client.
- Prefer direct assignment over fuzzy Indonesian-label matching for the happy path.
- Agency/`type` derivation from instansi/unit_kerja text stays as today (out of scope to switch to Master JF `type`).

## Out of scope

- Changing Client form/filter hardcoded cluster labels.
- Renaming DB columns.
- Aligning `pengangkatan` with `CRoleAssignation`.
- Changing how `ClientMatchingService` derives agency cluster from instansi text.
- Introducing `JenisKepegawaian` on Client.

## Testing

1. **Migration:** seed old labels → run migration → assert enum values; unmapped `type` is `null`.
2. **Model/factory:** casts and factory use enum values.
3. **Filament filters/stats:** seed/filter with enum values; widgets count by enum cases; update existing tests that used `Aktif` / `CTLN` as stored strings.
4. **Import (if covered):** label and value inputs normalize correctly; unknown → `null`.

## Success criteria

- Master JF Kluster / Status / Status Kepegawaian show the same labels as `ClientCluster` / `ClientStatus` / `JenisKepegawaian`.
- Stored `type` and `status` match Client enum values.
- Unmapped historical `type` rows are `null` after migration.
- Existing Master JF filter/stats coverage passes with the new values.
- Client UI cluster labels remain unchanged.
