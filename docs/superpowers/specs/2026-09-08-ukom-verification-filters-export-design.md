# Ukom Verification Filters & Export

**Date:** 2026-09-08  
**Area:** Filament — Verifikasi Pengajuan Ukom (`UkomApplicationResource` / `ListUkomApplications`)  
**Approach:** Cascading cluster→instansi filter + status filter; Filament `ExportAction` that keeps filters/search but ignores All/New/Processed tabs

## Problem

Verifiers need to narrow the list by cluster, instansi, and status, and download Excel of the matching scoped rows. Tabs alone are not enough; export must not be limited to the active tab.

## Decisions (confirmed)

| Topic | Choice |
|---|---|
| Filters | Cascading **Kluster → Instansi**, plus **Status** |
| Export vs tabs | Ignore tabs |
| Export vs filters | **Apply** cluster / instansi / status (and table search if Filament applies it) |
| Export columns | Same as table only |
| Bulk export | Out of scope for v1 |
| Commits | Do **not** auto-commit; user commits manually |

## Filters

Add table filters on `UkomApplicationResource::table()` (visible to every role that can open Verifikasi Pengajuan Ukom).

### Cascading agency filter

Composite `Filter` (mirror `ClientResource` `agency_filter`):

1. **Tingkat Instansi / Kluster** — options from `ClientCluster` (`central`, `local_province`, `local_regency`), `->live()`.
2. **Instansi** — searchable select; options from `RegDepartment` / `RegProvince` / `RegRegency` according to selected cluster; empty until cluster is chosen.

Query: when set, constrain `ukom_applications.type` and `agency_id` (and `agency_type` to the matching morph class when applying `agency_id`, so IDs cannot collide across morph types).

### Status filter

`SelectFilter::make('status')` with `UkomApplicationStatus` options **except** `draft` (drafts never appear in `scopedQuery`).

### Interaction with tabs

On-screen table: **tabs + filters** stack (same as today for tabs, plus new filters). Changing tab does not clear filters.

## Export

- Header button: Filament `Tables\Actions\ExportAction` → `UkomApplicationExporter` → `.xlsx` (same stack as `ClientExporter` / `ActivityReportExporter`).
- Label/icon/color: match existing verification/export buttons (success + download icon).
- **Columns (table parity):**
  - Nama
  - NIP
  - Instansi (`agenciable.name`)
  - Jabatan Saat Ini
  - Daftar Sebagai (`targetCRole.role_name`)
  - Status (enum label)
  - Diajukan Pada (`created_at`)
- **Query semantics:**
  1. Start from `UkomApplicationResource::getEloquentQuery()` (role `scopedQuery` + eager loads).
  2. Re-apply **active table filters** (and search if the Filament export pipeline supports it from the livewire page).
  3. **Do not** apply All / New / Processed tab `modifyQueryUsing`.
- No `ExportBulkAction` in v1.
- Pembina / admin-instansi / SuperAdmin visibility rules from existing `scopedQuery` remain in force (export cannot leak undelivered rows to pembina).

Implementation note: verify the Filament v3 export entry query on `ListRecords` (whether it already includes the active tab). Prefer rebuilding from `getEloquentQuery()` + applying filter state over trying to surgically strip tab SQL from the live table query.

## Touch points

| File | Role |
|---|---|
| `app/Filament/Resources/UkomApplicationResource.php` | Register filters + `ExportAction` |
| `app/Filament/Exports/UkomApplicationExporter.php` (new) | Column definitions + labels |
| `app/Filament/Resources/UkomApplicationResource/Pages/ListUkomApplications.php` | Only if export must live on the page / needs a helper to build “filters without tabs” query |
| `tests/Feature/Ukom/PengajuanUkomFlowTest.php` (or dedicated filter/export test) | Filter query behavior; export query ignores tab but keeps status/agency filters |

## Testing

1. Cluster alone filters by `type`; cluster + instansi filters morph agency correctly.
2. Status filter narrows the table; draft never offered.
3. With New tab active and no status filter, export includes processed rows that still pass other filters (proves tabs ignored).
4. With a status filter set, export only includes that status (proves filters applied).
5. Pembina export still excludes `pending_instansi` and instansi-only rejects.

## Non-goals

- Bulk row export
- Extra export columns (cluster label, rejection reason, reviewer timestamps)
- Changing tab definitions or pembina visibility rules
- Auto git commits

## Related

- Tabs / pembina scope: `docs/superpowers/specs/2026-09-08-ukom-verification-tabs-design.md`
- Original ukom flow: `docs/superpowers/specs/2026-08-31-pengajuan-ukom-design.md`
