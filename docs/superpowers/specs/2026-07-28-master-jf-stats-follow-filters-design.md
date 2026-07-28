# Master JF Stats Follow Table Filters

**Date:** 2026-07-28  
**Status:** Approved for planning  
**Resource:** `MasterJfResource` / `ListMasterJfs`

## Goal

Make the Master JF list header statistics update when the table’s filters or search change, matching the filtered row set.

## Context

- Four header widgets (`MasterJfNumbersOverview`, by status / status_kepegawaian / gol_ruang) already use `InteractsWithMasterJfPageTable` → Filament `InteractsWithPageTable` and aggregate via `getPageTableQuery()`.
- Filament’s `InteractsWithPageTable` expects reactive props (`tableFilters`, `tableSearch`, sort, etc.) from the parent page.
- Those props are supplied when the list page uses `Filament\Pages\Concerns\ExposesTableToWidgets` (`getWidgetData()`).
- `ListMasterJfs` does **not** use that trait today. Widgets therefore see empty/default table state and always count the full Master JF set, even though the table itself is filtered.
- Existing tests that pass `tableFilters` into an isolated widget still pass; they never exercised the page → widget wiring.

## Approach

**Approach 1 (chosen):** Add `ExposesTableToWidgets` to `ListMasterJfs`. Leave widget query logic unchanged.

Rejected:

- Custom Livewire events — more code, easy to miss search/sort.
- In-page Blade stats — rebuilds UI and abandons current widgets.

## Architecture

```text
ListMasterJfs
  └── use ExposesTableToWidgets
        └── getWidgetData(): tableFilters, tableSearch, sort, …
              │
              ▼ (Livewire reactive props)
        MasterJfNumbers* widgets
              └── getPageTableQuery() → filtered aggregates
```

## Changes

### `ListMasterJfs`

- `use Filament\Pages\Concerns\ExposesTableToWidgets;`
- No other page behavior changes (collapse toggle, widget registration stay as-is).

### Widgets

- No code changes required for this fix.
- All four widgets continue to use the shared trait and `getPageTableQuery()`.

## Edge cases

- No filters / empty search: stats equal full dataset (same as today).
- Filter with zero matches: total shows `0`; breakdown cards show `0` for known options (existing empty-set behavior).
- Collapse toggle: when widgets are hidden they are not rendered; when shown again they receive current table state via `getWidgetData()`.
- Multi-select filters (`instansi`, `unit_kerja`, `jabatan`) and single-select filters (including `c_role_id`) all flow through `tableFilters`.

## Out of scope

- Changing Client list widgets.
- New stats widgets or breakdown dimensions.
- Changing aggregation formulas inside existing Master JF widgets.
- Import or model schema changes.

## Testing

Add (or extend) a Livewire feature test on `ListMasterJfs`:

1. Seed Master JF rows with distinct filterable values (e.g. status Aktif vs CTLN).
2. Visit the list; assert the total widget reflects the unfiltered count.
3. `filterTable(...)` to one value; assert the total widget (and preferably one breakdown) reflects the filtered count, not the global total.

Keep existing isolated widget filter tests; they remain valid unit-style coverage of query logic.

## Success criteria

- Changing AboveContent filters or table search updates all visible Master JF header stats to match the filtered table query.
- Collapse / expand still works.
- No regressions in existing Master JF filter or stats tests.
