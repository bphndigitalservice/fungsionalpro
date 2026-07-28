# Master JF Stats Widgets & Table Filters

**Date:** 2026-07-28  
**Status:** Approved for planning  
**Resource:** `MasterJfResource` / `ListMasterJfs`

## Goal

Add Client-list-style stats overview widgets to the Master JF list page. Counts must stay in sync with the table’s search and filters. Add Client-style `AboveContent` filters so filtering is useful, plus a collapse toggle for the summary widgets.

## Context

- `ClientResource` already exposes header stats (`ClientNumbersOverview`, by status / grade / role level) and a “Sembunyikan/Tampilkan Ringkasan” toggle. Those Client widgets are **global** (scoped via `ClientAccessService` only); they do **not** follow table filters.
- `MasterJfResource` has searchable columns and Excel import, but **no table filters** and **no header widgets**.
- `status_kepegawaian` exists in the DB and form, but is missing from `MasterJf::$fillable` and from the list table columns.

## Approach

**Approach 1 (chosen):** Four Filament `StatsOverview` header widgets on `ListMasterJfs`, each using Filament’s `InteractsWithPageTable` so they read `getPageTableQuery()` (filtered + sorted, not paginated). Add matching table filters and the Client-style collapse toggle.

Rejected alternatives:

- Single fat stats widget — fewer files, but poorer layout parity with Client and harder to reason about.
- Custom Blade header counts — full control, but bespoke UI and no Filament stats styling reuse.

## Architecture

```text
ListMasterJfs
├── Header action: toggle $widgetsCollapsed
├── Header widgets (when not collapsed)
│   ├── MasterJfNumbersOverview
│   ├── MasterJfNumbersByStatusOverview
│   ├── MasterJfNumbersByStatusKepegawaianOverview
│   └── MasterJfNumbersByGolRuangOverview
└── Table
    ├── Search (existing searchable columns)
    └── Filters (AboveContent) → drive both table rows and widgets
```

Widgets are registered **only** on the Master JF list page (not on the dashboard). Client widgets are left unchanged.

## Components

### List page (`ListMasterJfs`)

- `getHeaderWidgets()` returns the four widget classes, or `[]` when `$widgetsCollapsed` is true.
- Header action mirrors Client: label/icon flip between “Sembunyikan Ringkasan” and “Tampilkan Ringkasan”.
- `getHeaderWidgetsColumns()` returns `3` (same as Client list).

### Widgets

Location: `app/Filament/Widgets/` (same as existing Client number widgets), named `MasterJfNumbers*`.

Shared concern (recommended): a small trait (e.g. `InteractsWithMasterJfPageTable`) that uses Filament `InteractsWithPageTable` and implements `getTablePage()` → `ListMasterJfs::class`.

| Widget | Cards |
|--------|--------|
| Total | One card: filtered Master JF count |
| By status | One card per known form status option; “Tidak diketahui” only if nulls exist in the filtered set |
| By status_kepegawaian | Cards for PNS / PPPK; “Tidak diketahui” only if nulls exist |
| By gol_ruang | Top 6 values by count (desc); null → “Tidak diketahui” |

Data path:

1. User changes table search or filters.
2. Reactive table state flows into widgets via `InteractsWithPageTable`.
3. Widget calls `getPageTableQuery()` and aggregates with `count` / `groupBy`.
4. Cards render with `number_format` and Heroicon icons consistent with Client widgets.

Empty filtered set: total shows `0`; breakdown cards show `0` for known options; gol_ruang may render no cards if there are no rows.

### Table filters (`MasterJfResource::table`)

Layout: `Tables\Enums\FiltersLayout::AboveContent` (Client pattern).

| Filter | Options |
|--------|---------|
| `status` | Fixed list already used in the form |
| `status_kepegawaian` | Fixed: PNS, PPPK |
| `pengangkatan` | Fixed list already used in the form |
| `type` | Distinct non-null values from `master_jf` |
| `gol_ruang` | Distinct non-null values from `master_jf` |
| `instansi` | Distinct non-null values from `master_jf` |
| `unit_kerja` | Distinct non-null values from `master_jf` |
| `jabatan` | Distinct non-null values from `master_jf` |

Distinct-value filters are searchable `SelectFilter`s. Multi-select for high-cardinality fields: `instansi`, `unit_kerja`, `jabatan`. Single-select for `type` and `gol_ruang`. Fixed-option filters (`status`, `status_kepegawaian`, `pengangkatan`) are single-select.

Existing column search continues to apply and also affects widget counts.

### Model / list hygiene

- Add `status_kepegawaian` to `MasterJf::$fillable`.
- Add a searchable `status_kepegawaian` text column on the Master JF table.

No new migration (column already exists).

## Out of scope

- Registering these widgets on the Filament dashboard.
- Making existing Client stats widgets filter-aware.
- Charts, export of filtered stats, or new Master JF CRUD actions beyond what already exists.
- Changing import mapping beyond ensuring `status_kepegawaian` remains fillable if already imported.

## Testing

Feature / Livewire tests (light but meaningful):

1. Authenticated visit to Master JF list shows header widgets and the collapse toggle.
2. Collapse toggle hides widgets; expand shows them again.
3. With seeded Master JF rows, applying a status filter changes the total widget count to match the filtered set.
4. At least one distinct-field filter (e.g. `gol_ruang` or `instansi`) likewise updates the total.
5. Breakdown widgets reflect grouped counts for a known fixture (status and status_kepegawaian).

Use the project’s existing Filament/auth test patterns for admin access to the resource.

## Success criteria

- Master JF list shows Client-like summary cards that update when search or filters change.
- Filters cover status, status_kepegawaian, gol_ruang, pengangkatan, type, instansi, unit_kerja, and jabatan.
- Summary can be collapsed/expanded without leaving the page.
- `status_kepegawaian` is visible and fillable consistently with the form/DB.
