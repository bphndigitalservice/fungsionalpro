# Master JF Jabatan Fungsional (CRole) Filter

**Date:** 2026-07-28  
**Status:** Approved for planning  
**Resource:** `MasterJfResource` / `ListMasterJfs`

## Goal

Let admins assign a canonical Jabatan Fungsional (`CRole`) on each Master JF row and filter the Master JF list by that assignment. Free-text `jabatan` remains available for search and filtering.

## Context

- Master JF already has AboveContent filters (including free-text `jabatan`) and filter-aware stats widgets via `InteractsWithPageTable`.
- In this app, “Jabatan Fungsional” means `CRole` (same concept as Client’s `c_role_id` filter).
- `master_jf` has no `c_role_id` today. Client matching only infers role by checking whether free-text `jabatan` contains a CRole `role_name`; that inference is **not** reused here.
- Assignment is **manual only** via the Master JF form. Import does not set `c_role_id`. Existing rows stay `null` until edited.

## Approach

**Approach 1 (chosen):** Nullable FK `master_jf.c_role_id` → `c_roles.id`, optional form Select, table column for `cRole.role_name`, and a single searchable `SelectFilter` on `c_role_id`. Keep the existing free-text `jabatan` filter.

Rejected alternatives:

- Multi-select CRole filter — useful later; CRole cardinality is low.
- Bulk assign action — helps scale manual assignment; deferred until needed.
- Runtime `LIKE` matching against `jabatan` without storing FK — no durable assignment and diverges from “canonical CRole” intent.

## Architecture

```text
master_jf.c_role_id (nullable FK → c_roles.id, nullOnDelete)
        │
        ▼
MasterJf::cRole() belongsTo(CRole)
        │
        ├── Form Select (optional) on create/edit
        ├── Table column: cRole.role_name
        └── SelectFilter: c_role_id  (alongside existing jabatan filter)
                │
                └── Header widgets continue to follow via InteractsWithPageTable
```

## Data model

- Migration: add nullable `c_role_id` unsigned big integer on `master_jf`, foreign key to `c_roles.id` with `nullOnDelete()`.
- `MasterJf::$fillable`: include `c_role_id`.
- `MasterJf::cRole()`: `belongsTo(CRole::class)`.
- Factory: allow optional `c_role_id` (default `null` unless a test sets it).
- `MasterJfImport`: unchanged — does not write `c_role_id`.

## UI

### Form

- `Select::make('c_role_id')` labeled “Jabatan Fungsional”.
- Options from `CRole::query()->pluck('role_name', 'id')`.
- `searchable()` + `preload()`; not required.

### Table column

- `TextColumn::make('cRole.role_name')` labeled “Jabatan Fungsional”, placed near free-text `jabatan`.
- Searchable via relationship; blank when unset.

### Filter

- `SelectFilter::make('c_role_id')` labeled “Jabatan Fungsional”.
- Same CRole options; single-select; searchable.
- Remains in `FiltersLayout::AboveContent` with existing filters.
- Free-text `jabatan` filter stays as-is.

## Edge cases

- Null `c_role_id`: column empty; row visible when no CRole filter is applied.
- CRole deleted: FK `nullOnDelete` clears `c_role_id`; Master JF row remains.
- Import/re-import: other columns update; `c_role_id` is not overwritten by import.
- Widgets: applying the CRole filter updates header stats the same way other table filters already do.

## Out of scope

- Auto-match or backfill from free-text `jabatan` on import.
- Bulk “Tetapkan Jabatan Fungsional” action.
- Dedicated “belum ditetapkan” (null-only) filter.
- New stats widget broken down by CRole.
- Changes to Client matching or registration jabatan heuristics.

## Testing

1. Model: persist `c_role_id` and load `cRole`.
2. List filter: two rows with different `c_role_id`s; filter shows only the match.
3. Fillable/form path: `c_role_id` accepted on the Master JF create/update path used by the resource.

Use existing Filament admin auth patterns from `MasterJfListFiltersTest`.

## Success criteria

- Admin can set Jabatan Fungsional on Master JF edit.
- List shows assigned role name and can filter by it.
- Free-text `jabatan` column, search, and filter still work.
- Filtered stats stay in sync with the table query.
