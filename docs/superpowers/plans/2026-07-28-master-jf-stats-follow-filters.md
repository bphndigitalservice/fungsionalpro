# Master JF Stats Follow Filters Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Wire Master JF list header stats to the table’s filters and search so counts match the filtered row set.

**Architecture:** Add Filament’s `ExposesTableToWidgets` to `ListMasterJfs` so `getWidgetData()` passes reactive `tableFilters` / `tableSearch` / sort into existing `MasterJfNumbers*` widgets. Widget aggregation via `getPageTableQuery()` stays unchanged.

**Tech Stack:** Laravel 12, Filament v3.2 (`ExposesTableToWidgets`, `InteractsWithPageTable`), Livewire 3, PHPUnit 11

## Global Constraints

- Spec: `docs/superpowers/specs/2026-07-28-master-jf-stats-follow-filters-design.md`
- Do **not** change Master JF widget query/aggregation code
- Do **not** change Client list widgets
- Do **not** add new stats widgets or breakdowns
- Keep collapse toggle and existing isolated widget tests
- Conventional commits; commit after the task

---

## File structure

| File | Responsibility |
| --- | --- |
| `app/Filament/Resources/MasterJfResource/Pages/ListMasterJfs.php` | Add `ExposesTableToWidgets` so header widgets receive table state |
| `tests/Feature/Filament/MasterJfListStatsTest.php` | Page-level test: filter on list → exposed widget data + filtered total |

---

### Task 1: Expose table state to Master JF header widgets

**Files:**
- Modify: `app/Filament/Resources/MasterJfResource/Pages/ListMasterJfs.php`
- Test: `tests/Feature/Filament/MasterJfListStatsTest.php`

**Interfaces:**
- Consumes: Filament `Filament\Pages\Concerns\ExposesTableToWidgets`; existing `ListMasterJfs` table filters; existing `MasterJfNumbersOverview` header widget
- Produces:
  - `ListMasterJfs::getWidgetData(): array` includes `tableFilters`, `tableSearch`, sort keys (from the trait)
  - Filtering the list page updates header total to the filtered count

- [ ] **Step 1: Write the failing page-wiring tests**

Append to `tests/Feature/Filament/MasterJfListStatsTest.php`:

```php
public function test_list_page_exposes_table_filters_in_widget_data(): void
{
    $this->actingAsAdmin();

    MasterJf::factory()->create(['status' => 'Aktif']);
    MasterJf::factory()->create(['status' => 'CTLN']);

    $component = Livewire::test(ListMasterJfs::class)
        ->filterTable('status', 'Aktif');

    $widgetData = $component->instance()->getWidgetData();

    $this->assertArrayHasKey('tableFilters', $widgetData);
    $this->assertSame('Aktif', data_get($widgetData, 'tableFilters.status.value'));
}

public function test_list_total_widget_follows_status_filter_via_page(): void
{
    $this->actingAsAdmin();

    MasterJf::factory()->count(2)->create(['status' => 'Aktif']);
    MasterJf::factory()->count(3)->create(['status' => 'CTLN']);

    Livewire::test(ListMasterJfs::class)
        ->assertSeeLivewire(MasterJfNumbersOverview::class)
        ->assertSee('Total Master JF')
        ->assertSee('5')
        ->filterTable('status', 'Aktif')
        ->assertSee('Total Master JF')
        ->assertSee('2');
}
```

Keep all existing tests in that file.

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter='test_list_page_exposes_table_filters_in_widget_data|test_list_total_widget_follows_status_filter_via_page'`

Expected: FAIL — `getWidgetData()` lacks `tableFilters` (base `Page::getWidgetData()` returns `[]`), and/or total still shows `5` after filtering.

- [ ] **Step 3: Add `ExposesTableToWidgets` to the list page**

Update `app/Filament/Resources/MasterJfResource/Pages/ListMasterJfs.php` to:

```php
<?php

namespace App\Filament\Resources\MasterJfResource\Pages;

use App\Filament\Resources\MasterJfResource;
use App\Filament\Widgets\MasterJfNumbersByGolRuangOverview;
use App\Filament\Widgets\MasterJfNumbersByStatusKepegawaianOverview;
use App\Filament\Widgets\MasterJfNumbersByStatusOverview;
use App\Filament\Widgets\MasterJfNumbersOverview;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListMasterJfs extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = MasterJfResource::class;

    public bool $widgetsCollapsed = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggle-widgets')
                ->label(fn (): string => $this->widgetsCollapsed ? 'Tampilkan Ringkasan' : 'Sembunyikan Ringkasan')
                ->icon(fn (): string => $this->widgetsCollapsed ? 'heroicon-o-chevron-down' : 'heroicon-o-chevron-up')
                ->color('secondary')
                ->action(fn () => $this->widgetsCollapsed = ! $this->widgetsCollapsed),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        if ($this->widgetsCollapsed) {
            return [];
        }

        return [
            MasterJfNumbersOverview::class,
            MasterJfNumbersByStatusOverview::class,
            MasterJfNumbersByStatusKepegawaianOverview::class,
            MasterJfNumbersByGolRuangOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }
}
```

Do not modify any files under `app/Filament/Widgets/`.

- [ ] **Step 4: Run the new tests and the full Master JF stats suite**

Run: `php artisan test --filter=MasterJfListStatsTest`

Expected: PASS (all methods, including the two new ones and existing collapse/isolated-widget tests)

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Resources/MasterJfResource/Pages/ListMasterJfs.php \
  tests/Feature/Filament/MasterJfListStatsTest.php
git commit -m "$(cat <<'EOF'
fix(master-jf): expose table filters to header stats widgets

EOF
)"
```

---

## Spec coverage checklist

| Spec requirement | Task |
| --- | --- |
| `ExposesTableToWidgets` on `ListMasterJfs` | Task 1 |
| Widgets unchanged | Task 1 (explicit no-touch) |
| Page-level filter → filtered total test | Task 1 |
| Keep collapse + isolated widget tests | Task 1 |
| No Client / new widgets / import changes | Out of scope |

## Plan self-review

- No placeholders; single focused task.
- Trait import path matches Filament v3: `Filament\Pages\Concerns\ExposesTableToWidgets`.
- RED expected failure is the missing `tableFilters` key in `getWidgetData()`, which is exactly the production bug.
