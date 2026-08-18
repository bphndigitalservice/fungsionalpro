# Master JF agency morph (like Client)

**Date:** 2026-08-18  
**Status:** Approved for planning  
**Scope:** Persist a Client-style polymorphic agency on `master_jf` (agency only, no echelon). Use it for registration matching, Filament Master JF admin, and Master JF aggregate API instansi grouping.

## Goal

Stop treating instansi as only free text. Store the same `agency_type` + `agency_id` pair `clients` already uses, so:

1. Registration can copy a real agency onto the client instead of fuzzy name lookup when the Master JF row is already linked.
2. Filament Master JF picks instansi from `RegDepartment` / `RegProvince` / `RegRegency` the same way Client does (cluster then select).
3. `GET /api/v1/master-jf` groups instansi by the linked agency when present, not by spelling variants of `instansi`.

## Context

- `clients` already has `agenciable()`: `morphTo` on `agency_type` / `agency_id`. Allowed models: `RegDepartment`, `RegProvince`, `RegRegency`. Optional echelon morph is **out of scope**.
- `master_jf` today: free-text `instansi` and `unit_kerja`, cluster `type`, `province_id`, `provinsi`. Import and `ClientMatchingService` already *guess* cluster and look up an agency by name; they never persist that link on Master JF.
- Aggregate API groups instansi by `instansi` text (`unknown` when empty). Duplicate official names with different spelling split into multiple rows.

## Decisions (approved)

| Topic | Decision |
|---|---|
| Scope of morph | Agency only (`agency_type` / `agency_id`). No echelon. |
| Unmatched rows | Morph nullable. Keep `instansi` / `unit_kerja` text. |
| Cluster `type` | When morph is set, `type` is derived from the agency model. Unmatched rows keep stored `type` / text fallback. |
| Filament | Client-style: optional cluster `type`, then `agency_id` select. `instansi` is not a second editor. `unit_kerja` stays text. |
| Implementation | Mirror Client columns on `master_jf` (Approach 1). Do not extract a shared Agencyable abstraction. Do not use an enum discriminator. |
| Public API JSON | Instansi items stay `{ name, client_count }`. No new `agency_id` field in this spec. |
| Creating agencies | Never insert missing departments/provinces/regencies from Master JF text. |

## Schema and relations

Add to `master_jf`:

- `agency_type` — nullable string (FQCN, same values as `clients.agency_type`)
- `agency_id` — nullable unsigned big integer

Composite index on `(agency_type, agency_id)`. No single-table foreign key.

**Invariant:** `agency_type` and `agency_id` are both null, or both set. Never one without the other. Enforced in application code (form mutate, resolver, backfill), not a DB check.

`MasterJf::agenciable()` matches Client:

```php
return $this->morphTo(__FUNCTION__, 'agency_type', 'agency_id');
```

Inverse on `RegDepartment`, `RegProvince`, `RegRegency`:

```php
return $this->morphMany(MasterJf::class, 'agency');
```

Name the inverse `masterJfs()`. Do **not** reuse Client’s `agency()` `morphOne` (wrong cardinality and name already taken). Morph name `'agency'` matches existing Client inverse column convention (`agency_id` / `agency_type`).

Keep columns: `instansi`, `unit_kerja`, `type`, `province_id`, `provinsi`. Do not drop them.

### Derived `type` and `province_id` when morph is set

| `agency_type` | `type` (`ClientCluster`) | `province_id` |
|---|---|---|
| `RegDepartment` | `central` | Unchanged (do not null, do not set from morph) |
| `RegProvince` | `local_province` | Set to `agency_id` |
| `RegRegency` | `local_regency` | Set to that regency’s `province_id` |

When morph is null: do not change `type` or `province_id` in backfill/import except what those flows already write today (`type` from `determineAgencyInfo` on import).

## Components

| Unit | Role |
|---|---|
| Migration add columns | Nullable morph + index |
| One-shot backfill migration | Best-effort resolve from existing text |
| `App\Support\MasterJfAgencyResolver` | Name lookup + apply morph / derived `type` / `province_id` |
| `MasterJf` model + factory | Relation, fillable; morph default null |
| `RegDepartment` / `RegProvince` / `RegRegency` | `masterJfs()` inverse |
| `MasterJfImport` | Resolve on write; preserve manual morph if names miss |
| `MasterJfResource` (+ create/edit mutate) | Cluster + agency select; set `agency_type` from `type` |
| `ClientMatchingService::applyMasterData` | Copy morph when present; else existing fuzzy path |
| `MasterJfAggregateService` | Group instansi by morph, else text, else `unknown` |
| PHPUnit tests | Schema, backfill, import, matching, API, Filament edit |

SQLite tests that use `EnsuresMasterJfApiSchema` (or equivalent setup) must add the morph columns when missing, same pattern as `province_id`.

## Write path

### Shared resolver

Reuse `ClientMatchingService::determineAgencyInfo` and the existing lookup order:

1. Exact `name` on cleaned `unit_kerja`
2. Exact `name` on cleaned `instansi`
3. `LIKE %unit_kerja%`
4. `LIKE %instansi%`

Target model is the class returned by `determineAgencyInfo`. Put this in `App\Support\MasterJfAgencyResolver`. It calls `ClientMatchingService::determineAgencyInfo` and `cleanAgencyName`; it does not duplicate the LIKE chain. It does not create agencies on miss.

Return either both morph columns plus derived `type` / `province_id` (rules above), or no match (caller decides whether to leave an existing morph). Form mutate maps `type` → `agency_type` directly and does not have to call the resolver.

### Backfill (one-shot, idempotent)

For each `master_jf` row where `agency_id` is null: resolve from `instansi` / `unit_kerja`. On hit, set morph + derived `type` / `province_id`. On miss, leave morph null; do not change `instansi`. Skip rows that already have `agency_id`. Safe to re-run.

Do not overwrite `instansi` with the canonical agency name during backfill (preserve imported text).

### Import

Still write spreadsheet `instansi` and `unit_kerja`. After mapping a row, run the resolver.

- **Hit:** set morph + derived `type` / `province_id` (spreadsheet can correct a link).
- **Miss on a new NIP:** morph stays null.
- **Miss on an existing NIP:** keep the previous morph (do not wipe a Filament link because Excel text failed to match).

Import continues to set `type` via `determineAgencyInfo` even when morph misses (current behavior).

### Filament

Replace the `instansi` TextInput with:

1. Existing cluster `Select` (`type`) — optional, `live()`, clears `agency_id` when cluster changes.
2. Searchable `Select` `agency_id` — options from the table for that cluster (same match as `ClientResource`). Disabled/empty until `type` is set. Not required.

On save (`mutateFormDataBeforeSave` on create and edit):

- If `type` and `agency_id` are both present: set `agency_type` from `type` using the same class map as `EditClient` (department / province / regency only). Compare `type` by `ClientCluster` value so enum-or-string form state both work. Apply `province_id` rules. Do not set echelon fields.
- If `agency_id` is empty: set `agency_type` and `agency_id` to null. Leave `type` as submitted (cluster-only is allowed).
- Do not persist `agency_type` without `agency_id`.

`unit_kerja` remains a text input. `provinsi` text remains. Do not add a second instansi text editor.

Table: display `agenciable.name`, fallback to `instansi`. Keep the existing `SelectFilter` on text column `instansi`. No new agency morph filter in this spec.

## Read path

### Registration matching

In `applyMasterData`:

- If Master JF has both morph columns set: copy `agency_type`, `agency_id`, and `type` onto the client. Skip name lookup for agency.
- Else: keep today’s `determineAgencyInfo` + name search.

Do not set client echelon from Master JF.

### Cluster resolver / daerah filters

`MasterJfClusterResolver` stays: prefer stored `type`, else guess from text. Linked rows get consistent `type` from writes. Daerah filters stay hybrid `province_id` + `provinsi`. Backfill may fill more `province_id` values; that is an improvement, not an API contract change.

### Aggregate API

`GET /api/v1/master-jf` response shape unchanged. Instansi list grouping:

1. Linked morph → bucket key `agency_type + agency_id`, `name` = related `name` (if the related row is missing, treat as unmatched and fall through).
2. Else non-empty `instansi` text → bucket by that string (current behavior).
3. Else `"unknown"`.

`client_count` is row count in the bucket. Sort by `name` case-insensitive. Two Master JF rows pointing at the same agency count as one instansi even if `instansi` text differs.

Failed resolve and dangling morphs are not HTTP errors.

## Error handling

- Name resolve miss → null morph, not an exception.
- Dangling morph (type/id set, related row gone) → display/API treat as unmatched (`instansi` or `"unknown"`).
- Import row without NIP → existing exception, unchanged.

## Testing

PHPUnit. Factory leaves morph null by default so existing tests stay valid.

1. Model: `agenciable()` for department, province, regency; fillable includes morph columns.
2. Backfill: match sets morph + derived `type` / `province_id`; miss stays null; already-linked skipped; second run idempotent.
3. Import: new NIP with known name writes morph; existing NIP with unresolvable names keeps previous morph; `instansi` text still stored.
4. Matching: linked Master JF copies morph onto Client without LIKE lookup; unlinked uses fuzzy path.
5. Aggregate API: two spellings, same `agency_id`, one instansi row; unlinked groups by `instansi`; empty → `"unknown"`.
6. Filament edit: save cluster + `agency_id` persists `agency_type`; clear agency nulls both morph columns. Follow `MasterJfEditFormTest`.

## Out of scope

- Echelon morph on `master_jf`
- Creating registry rows from unmatched names
- Dropping `instansi`, `unit_kerja`, `type`, `provinsi`
- Refactoring Client forms or extracting a shared Agencyable trait
- Adding `agency_id` to the public aggregate JSON
- New Filament filter on the morph
- Changing OpenAPI field names for instansi items
