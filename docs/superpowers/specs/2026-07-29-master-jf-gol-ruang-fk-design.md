# Master JF Gol Ruang & Jenjang FK Alignment

**Date:** 2026-07-29  
**Status:** Approved for planning  
**Resource:** `MasterJfResource` / `MasterJf` model

## Goal

Make Master JF **Golongan/Ruang** and **Jenjang** use nullable FK references (`reg_grade_id` → `reg_grades`, `c_role_level_id` → `c_role_levels`), mirroring Client. Edit/create, list, filter, stats, import, and client matching prefer those IDs. Legacy free-text `gol_ruang` remains in the DB but is no longer written by the form or import after backfill.

## Context

- Live `master_jf` already has `reg_grade_id` and `c_role_level_id` columns (per current DDL), but the app does not use them: model fillable/relations omit them, and there is no migration in-repo guaranteeing the columns/FKs.
- Form today binds Golongan/Ruang to string `gol_ruang`, loading option *labels* from `RegGrade` but storing the label string as the value.
- Client already uses `reg_grade_id` (Pangkat/Golongan) and `c_role_level_id` (Jenjang, filtered by `c_role_id`).
- List filter and `MasterJfNumbersByGolRuangOverview` group/filter on distinct `gol_ruang` text.
- Import writes `gol_ruang` from the `golruang` Excel column.
- `ClientMatchingService` fuzzy-parses `gol_ruang` / `jabatan` text; it does not yet prefer Master JF FK columns.

## Decisions

| Topic | Choice |
| --- | --- |
| Scope | Both `reg_grade_id` (Golongan/Ruang) and `c_role_level_id` (Jenjang) |
| Legacy `gol_ruang` | Backfill `reg_grade_id` from text; stop writing `gol_ruang` on edit/import; keep column (no drop) |
| Surface area | Full: form, list, filter, widget, import, matching |
| Required? | Both FKs optional (nullable) |
| Level backfill | None — only backfill `reg_grade_id` |
| Approach | Mirror Client FKs end-to-end (no dual-write sync layer) |

## Architecture

```text
reg_grades / c_role_levels
        │
        ├── MasterJf.reg_grade_id / c_role_level_id (+ relations)
        ├── MasterJfResource form / table / filters
        ├── MasterJfNumbersByGolRuangOverview (group by reg_grade_id)
        ├── MasterJfImport (resolve golruang → reg_grade_id)
        └── ClientMatchingService (prefer FKs, text fallback)
```

## Data model

### Schema

| Column | Role |
| --- | --- |
| `reg_grade_id` | Nullable FK → `reg_grades.id` |
| `c_role_level_id` | Nullable FK → `c_role_levels.id` |
| `gol_ruang` | Legacy string; kept; stop writing after migrate |

Migration must:

1. Add `reg_grade_id` / `c_role_level_id` **if missing** (idempotent-friendly for DBs that already have them).
2. Add foreign keys (`nullOnDelete` on delete; cascade on update as appropriate) and indexes when not already present.

Do **not** drop `gol_ruang` in this work.

### Model (`MasterJf`)

- Fillable: add `reg_grade_id`, `c_role_level_id`; remove `gol_ruang` from fillable once form/import no longer write it.
- Relations: `grade()` → `RegGrade`, `cRoleLevel()` → `CRoleLevel` (keep existing `cRole()`).
- No enum casts for these IDs.

### Backfill (one-shot)

- Match existing `gol_ruang` → `reg_grade_id` using `grade_code` / `grade_name` (including formats like `III/a` or `Penata (III/a)`), same spirit as `ClientMatchingService`.
- Unmatched → leave `reg_grade_id` null.
- Do **not** backfill `c_role_level_id`.

## Filament UI

### Form

| Field | Binding | Behavior |
| --- | --- | --- |
| Golongan/Ruang | `reg_grade_id` | Searchable `Select` via `relationship('grade', 'grade_code')` (or equivalent). Not required. Replaces the current `gol_ruang` label-string select. |
| Jenjang | `c_role_level_id` | Options from `CRoleLevel` where `c_role_id` = selected JF. Disabled when no `c_role_id`. Not required. Clear to `null` when `c_role_id` changes (`live` + `afterStateUpdated`). |
| Jabatan Fungsional | `c_role_id` | Existing select; make `live()` so Jenjang reacts. |

Free-text `jabatan` stays unchanged (separate from Jenjang).

### Table

- Replace `gol_ruang` column with `grade.grade_code` (label **Golongan/Ruang**).
- Add `cRoleLevel.level` column (label **Jenjang**).

### Filters

- Replace distinct `gol_ruang` filter with `reg_grade_id` options from `RegGrade` (searchable).
- Add searchable `c_role_level_id` filter (options from levels; simple `level` label is acceptable).

## Widgets

`MasterJfNumbersByGolRuangOverview`:

- Group by `reg_grade_id` (top 6 by count).
- Resolve labels from `RegGrade` using `grade_code`.
- Null ID → “Tidak diketahui”.
- Keep existing filter-aware `getPageTableQuery()` pattern.

## Import

- On `golruang` cell: resolve to `reg_grade_id` via code/name match; set `reg_grade_id`; do **not** write `gol_ruang`.
- Unknown → `reg_grade_id` null (row still imports).
- No new Excel column for Jenjang; `c_role_level_id` stays null on import.

## Client matching

Prefer Master JF FKs when present:

1. `master.reg_grade_id` → `client.reg_grade_id`; else existing `gol_ruang` text parse.
2. Role: if `master.c_role_id` is set, use it; else if `master.c_role_level_id` is set, use that level’s `c_role_id`; else jabatan fuzzy match (current).
3. Level: if `master.c_role_level_id` is set and its `c_role_id` matches the chosen client role, assign it; else jabatan fuzzy / default `1` as today.

## Factory

Prefer setting `reg_grade_id` (and optionally `c_role_level_id`) for new/updated tests instead of relying on free-text `gol_ruang`.

## Testing

1. **Form:** Saves `reg_grade_id` / `c_role_level_id`; changing `c_role_id` clears level.
2. **Backfill:** Known `gol_ruang` → correct grade ID; junk → null; `c_role_level_id` untouched.
3. **List/filter/widget:** Filter and gol_ruang widget follow `reg_grade_id`.
4. **Import / matching:** Import resolves grade ID; matching uses FKs when set and text fallback when not.

## Out of scope

- Dropping the `gol_ruang` column.
- Automatic backfill of `c_role_level_id`.
- Changing Client form labels or required rules.
- Adding a Jenjang column to the Excel import template.
- Dual-writing display strings into `gol_ruang` on save.

## Success criteria

- Master JF create/edit stores Golongan/Ruang and Jenjang as FKs (nullable).
- List, filter, and gol_ruang stats use `reg_grade_id`.
- Import sets `reg_grade_id` from `golruang` text and does not write `gol_ruang`.
- Matching prefers Master JF FKs with text fallback.
- Existing `gol_ruang` values are backfilled to `reg_grade_id` where matchable; unmatched stay null.
- `c_role_level_id` is never auto-backfilled.
)