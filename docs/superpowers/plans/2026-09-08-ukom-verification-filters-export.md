# Ukom Verification Filters & Export Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add cascading Kluster→Instansi and Status filters to Verifikasi Pengajuan Ukom, plus an Excel export that keeps those filters but ignores All/New/Processed tabs.

**Architecture:** Register Filament table filters on `UkomApplicationResource` (ClientResource-style cascading agency filter + status SelectFilter). Add `UkomApplicationExporter` and a header `ExportAction` whose `modifyQueryUsing` rebuilds from `getEloquentQuery()` and re-applies filter state so tab predicates are never included.

**Tech Stack:** Laravel 12, Filament v3 (`SelectFilter`, composite `Filter`, `ExportAction` / `Exporter` / OpenSpout xlsx), Livewire 3, PHPUnit 11, `RefreshDatabase`

## Global Constraints

- Spec: `docs/superpowers/specs/2026-09-08-ukom-verification-filters-export-design.md`
- Cascading Kluster → Instansi; Status excludes `draft`
- Export: apply filters (+ search if Filament supplies it); **ignore tabs**
- Export columns = table columns only (Nama, NIP, Instansi, Jabatan Saat Ini, Daftar Sebagai, Status, Diajukan Pada)
- No `ExportBulkAction` in v1
- Do **not** change tab definitions or pembina `scopedQuery` rules
- **Do not `git add` / `git commit`** — user commits manually; skip every Commit step
- PHPUnit; extend `tests/Feature/Ukom/PengajuanUkomFlowTest.php` (or add a sibling feature test in the same folder)

---

## File structure

| File | Responsibility |
| --- | --- |
| `app/Filament/Resources/UkomApplicationResource.php` | Cascading agency filter, status filter, shared `applyVerificationFilters()`, header `ExportAction` |
| `app/Filament/Exports/UkomApplicationExporter.php` | XLSX column definitions matching the table |
| `app/Filament/Resources/UkomApplicationResource/Pages/ListUkomApplications.php` | Optional: only if export needs a page helper; prefer keeping export on the Resource |
| `tests/Feature/Ukom/PengajuanUkomFlowTest.php` | Filter + export-query coverage |

---

### Task 1: Cascading + status table filters

**Files:**
- Modify: `app/Filament/Resources/UkomApplicationResource.php`
- Modify: `tests/Feature/Ukom/PengajuanUkomFlowTest.php`

**Interfaces:**
- Consumes: `ClientCluster`, `UkomApplicationStatus`, `RegDepartment` / `RegProvince` / `RegRegency`, existing `getEloquentQuery()` / tabs
- Produces:
  - `UkomApplicationResource::applyVerificationFilters(Builder $query, array $filterData): Builder`
  - Table filters: `agency_filter` (form keys `type`, `agency_id`), `status`
  - Filter data shape for later export: `$tableFilters['agency_filter']` → `['type' => ?string, 'agency_id' => ?int]`, `$tableFilters['status']` → `['value' => ?string]` (Filament SelectFilter wrapping)

- [ ] **Step 1: Write failing filter tests**

Append to `tests/Feature/Ukom/PengajuanUkomFlowTest.php` (reuse existing `setUp` helpers / `$this->ah` / `$this->department`):

```php
public function test_verification_filters_by_cluster_instansi_and_status(): void
{
    $calon = $this->makeCalonUser();
    $otherDept = RegDepartment::create(['name' => 'Instansi Lain']);

    $matchPending = UkomApplication::factory()->pendingAdmin()->create([
        'user_id' => $calon->id,
        'target_c_role_id' => $this->ah->id,
        'type' => ClientCluster::Central,
        'agency_type' => RegDepartment::class,
        'agency_id' => $this->department->id,
    ]);

    $otherAgency = UkomApplication::factory()->pendingAdmin()->create([
        'user_id' => $calon->id,
        'target_c_role_id' => $this->ah->id,
        'type' => ClientCluster::Central,
        'agency_type' => RegDepartment::class,
        'agency_id' => $otherDept->id,
    ]);

    $acceptedSameAgency = UkomApplication::factory()->acceptedByPembina()->create([
        'user_id' => $calon->id,
        'target_c_role_id' => $this->ah->id,
        'type' => ClientCluster::Central,
        'agency_type' => RegDepartment::class,
        'agency_id' => $this->department->id,
    ]);

    $super = User::factory()->create();
    $super->assignRole(SystemRole::SuperAdmin->value);
    $this->actingAs($super);

    Livewire::test(ListUkomApplications::class)
        ->set('activeTab', 'all')
        ->filterTable('agency_filter', [
            'type' => ClientCluster::Central->value,
            'agency_id' => $this->department->id,
        ])
        ->assertCanSeeTableRecords([$matchPending, $acceptedSameAgency])
        ->assertCanNotSeeTableRecords([$otherAgency])
        ->filterTable('status', UkomApplicationStatus::PendingAdmin->value)
        ->assertCanSeeTableRecords([$matchPending])
        ->assertCanNotSeeTableRecords([$acceptedSameAgency, $otherAgency]);
}

public function test_verification_status_filter_options_exclude_draft(): void
{
    $super = User::factory()->create();
    $super->assignRole(SystemRole::SuperAdmin->value);
    $this->actingAs($super);

    $filters = collect(UkomApplicationResource::getFilters())
        ->first(fn ($filter) => $filter->getName() === 'status');

    // Prefer asserting via resource helper if getFilters is awkward — alternatively:
    $options = UkomApplicationResource::statusFilterOptions();

    $this->assertArrayNotHasKey(UkomApplicationStatus::Draft->value, $options);
    $this->assertArrayHasKey(UkomApplicationStatus::PendingInstansi->value, $options);
    $this->assertArrayHasKey(UkomApplicationStatus::Accepted->value, $options);
}
```

If `getFilters()` is not convenient on the Resource, implement and test only `statusFilterOptions()` as a public static method used by the SelectFilter.

Also ensure `use` imports: `ClientCluster`, `RegDepartment` (already), `UkomApplicationStatus`, `ListUkomApplications`, `UkomApplicationResource`, `Livewire`.

- [ ] **Step 2: Run tests — expect FAIL**

```bash
php artisan test --filter=test_verification_filters_by_cluster_instansi_and_status
php artisan test --filter=test_verification_status_filter_options_exclude_draft
```

Expected: FAIL (no filters / no `statusFilterOptions`).

- [ ] **Step 3: Implement filters + shared apply helper**

In `UkomApplicationResource.php`:

1. Add imports: `ClientCluster`, `Forms`, `RegDepartment`, `RegProvince`, `RegRegency`, `Builder`, `ExportAction` (ExportAction can wait for Task 2), `UkomApplicationExporter` (Task 2).

2. Add helpers:

```php
public static function statusFilterOptions(): array
{
    return collect(UkomApplicationStatus::cases())
        ->reject(fn (UkomApplicationStatus $status) => $status === UkomApplicationStatus::Draft)
        ->mapWithKeys(fn (UkomApplicationStatus $status) => [
            $status->value => $status->getLabel(),
        ])
        ->all();
}

/**
 * @param  array{agency_filter?: array{type?: string|null, agency_id?: int|string|null}, status?: array{value?: string|null}|string|null}  $filterData
 */
public static function applyVerificationFilters(Builder $query, array $filterData): Builder
{
    $agency = $filterData['agency_filter'] ?? [];
    $type = $agency['type'] ?? null;
    $agencyId = $agency['agency_id'] ?? null;

    if (filled($type)) {
        $query->where('type', $type);
    }

    if (filled($agencyId) && filled($type)) {
        $agencyType = match ($type) {
            ClientCluster::Central->value, 'central' => RegDepartment::class,
            ClientCluster::LocalProvince->value, 'local_province' => RegProvince::class,
            ClientCluster::LocalRegency->value, 'local_regency' => RegRegency::class,
            default => null,
        };

        $query->where('agency_id', $agencyId);

        if ($agencyType !== null) {
            $query->where('agency_type', $agencyType);
        }
    }

    $status = $filterData['status']['value'] ?? $filterData['status'] ?? null;
    if (is_array($status)) {
        $status = $status['value'] ?? null;
    }

    if (filled($status)) {
        $query->where('status', $status);
    }

    return $query;
}
```

3. On `table()`, after columns / before or after actions, add:

```php
->filters([
    Tables\Filters\Filter::make('agency_filter')
        ->form([
            Forms\Components\Select::make('type')
                ->label('Tingkat Instansi')
                ->options(ClientCluster::class)
                ->live(),
            Forms\Components\Select::make('agency_id')
                ->label('Instansi')
                ->options(function (Forms\Get $get) {
                    return match ($get('type')) {
                        ClientCluster::Central->value, 'central' => RegDepartment::query()->orderBy('name')->pluck('name', 'id'),
                        ClientCluster::LocalProvince->value, 'local_province' => RegProvince::query()->orderBy('name')->pluck('name', 'id'),
                        ClientCluster::LocalRegency->value, 'local_regency' => RegRegency::query()->orderBy('name')->pluck('name', 'id'),
                        default => [],
                    };
                })
                ->searchable(),
        ])
        ->query(function (Builder $query, array $data): Builder {
            return static::applyVerificationFilters($query, [
                'agency_filter' => $data,
            ]);
        }),
    Tables\Filters\SelectFilter::make('status')
        ->label('Status')
        ->options(fn (): array => static::statusFilterOptions()),
], layout: Tables\Enums\FiltersLayout::AboveContent)
```

Keep existing `->actions([...])` and tabs unchanged.

- [ ] **Step 4: Run filter tests — expect PASS**

```bash
php artisan test --filter=test_verification_filters_by_cluster_instansi_and_status
php artisan test --filter=test_verification_status_filter_options_exclude_draft
php artisan test tests/Feature/Ukom/PengajuanUkomFlowTest.php
```

Expected: PASS for new tests; existing ukom tests still green.

- [ ] **Step 5: Skip commit** (user commits manually)

---

### Task 2: Exporter + export query (filters yes, tabs no)

**Files:**
- Create: `app/Filament/Exports/UkomApplicationExporter.php`
- Modify: `app/Filament/Resources/UkomApplicationResource.php`
- Modify: `tests/Feature/Ukom/PengajuanUkomFlowTest.php`

**Interfaces:**
- Consumes: `applyVerificationFilters()`, `getEloquentQuery()`, `ListUkomApplications` Livewire `tableFilters` / `tableSearch`
- Produces: `UkomApplicationExporter`; `ExportAction` with `modifyQueryUsing` that **does not** call tab filters

- [ ] **Step 1: Write failing export-query tests**

```php
public function test_export_query_ignores_tabs_but_keeps_filters(): void
{
    $calon = $this->makeCalonUser();
    $base = [
        'user_id' => $calon->id,
        'target_c_role_id' => $this->ah->id,
        'type' => ClientCluster::Central,
        'agency_type' => RegDepartment::class,
        'agency_id' => $this->department->id,
    ];

    $newRow = UkomApplication::factory()->pendingAdmin()->create($base);
    $processed = UkomApplication::factory()->acceptedByPembina()->create($base);

    $super = User::factory()->create();
    $super->assignRole(SystemRole::SuperAdmin->value);
    $this->actingAs($super);

    $filterData = [
        'agency_filter' => [
            'type' => ClientCluster::Central->value,
            'agency_id' => $this->department->id,
        ],
    ];

    $exportQuery = UkomApplicationResource::applyVerificationFilters(
        UkomApplicationResource::getEloquentQuery(),
        $filterData,
    );

    $ids = $exportQuery->pluck('id')->all();

    $this->assertContains($newRow->id, $ids);
    $this->assertContains($processed->id, $ids);

    $withStatus = UkomApplicationResource::applyVerificationFilters(
        UkomApplicationResource::getEloquentQuery(),
        array_merge($filterData, [
            'status' => ['value' => UkomApplicationStatus::PendingAdmin->value],
        ]),
    )->pluck('id')->all();

    $this->assertContains($newRow->id, $withStatus);
    $this->assertNotContains($processed->id, $withStatus);
}

public function test_pembina_export_query_excludes_undelivered(): void
{
    $calon = $this->makeCalonUser();
    $base = [
        'user_id' => $calon->id,
        'target_c_role_id' => $this->ah->id,
        'type' => ClientCluster::Central,
        'agency_type' => RegDepartment::class,
        'agency_id' => $this->department->id,
    ];

    $pendingInstansi = UkomApplication::factory()->pendingInstansi()->create($base);
    $pendingAdmin = UkomApplication::factory()->pendingAdmin()->create($base);

    $pembina = User::factory()->create();
    $pembina->assignRole(SystemRole::Admin->value);
    $this->actingAs($pembina);

    $ids = UkomApplicationResource::applyVerificationFilters(
        UkomApplicationResource::getEloquentQuery(),
        [],
    )->pluck('id')->all();

    $this->assertNotContains($pendingInstansi->id, $ids);
    $this->assertContains($pendingAdmin->id, $ids);
}
```

- [ ] **Step 2: Run — expect FAIL only if helpers missing; otherwise GREEN for helpers, then add ExportAction wiring test**

If Task 1 already shipped `applyVerificationFilters`, Step 1 tests should PASS immediately — that is OK. Still proceed to implement exporter + action.

Optional Livewire smoke (assert header action exists):

```php
Livewire::test(ListUkomApplications::class)
    ->assertTableActionExists('export'); // or assertSuccessful + assertSeeText for label — use Filament’s assertHeaderActionsExist if available in this version
```

Prefer checking `ExportAction` is registered:

```php
$actions = collect(UkomApplicationResource::table(Table::make())->getHeaderActions())
    // awkward — instead:
Livewire::test(ListUkomApplications::class)
    ->assertSuccessful();
```

Skip fragile header-action assertions if the Filament version lacks helpers; unit coverage of `applyVerificationFilters` + exporter class existence is enough.

- [ ] **Step 3: Create `UkomApplicationExporter`**

Create `app/Filament/Exports/UkomApplicationExporter.php`:

```php
<?php

namespace App\Filament\Exports;

use App\Models\UkomApplication;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class UkomApplicationExporter extends Exporter
{
    protected static ?string $model = UkomApplication::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('nama')->label('Nama'),
            ExportColumn::make('nip')->label('NIP'),
            ExportColumn::make('agenciable.name')->label('Instansi'),
            ExportColumn::make('current_jabatan')->label('Jabatan Saat Ini'),
            ExportColumn::make('targetCRole.role_name')->label('Daftar Sebagai'),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? $state),
            ExportColumn::make('created_at')
                ->label('Diajukan Pada'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor pengajuan ukom selesai: '.$export->successful_rows.' baris.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.$failedRowsCount.' baris gagal.';
        }

        return $body;
    }
}
```

Match notification wording style to `ClientExporter` / `ActivityReportExporter` if they differ — copy the closest existing pattern.

- [ ] **Step 4: Wire `ExportAction` so tabs are ignored**

In `UkomApplicationResource::table()`, add `->headerActions([...])` **before** `->actions([...])`:

```php
use Filament\Tables\Actions\ExportAction;
use App\Filament\Exports\UkomApplicationExporter;
use Filament\Facades\Filament; // only if needed

// ...

->headerActions([
    ExportAction::make()
        ->label('Ekspor')
        ->exporter(UkomApplicationExporter::class)
        ->modifyQueryUsing(function (Builder $query) {
            /** @var \App\Filament\Resources\UkomApplicationResource\Pages\ListUkomApplications $livewire */
            $livewire = $query->getConnection() // DO NOT use this
        })
])
```

**Correct Filament v3 pattern** (verify against `ClientResource` / `ActivityReportResource` and the `ExportAction` signature in vendor): `modifyQueryUsing` typically receives `(Builder $query)` and closes over nothing useful for filters. Prefer:

```php
ExportAction::make()
    ->label('Ekspor')
    ->exporter(UkomApplicationExporter::class)
    ->modifyQueryUsing(function (Builder $query): Builder {
        $livewire = app('livewire')->current(); // fragile
        ...
    })
```

**Preferred robust approach:** define the ExportAction on `ListUkomApplications` via `protected function getTableHeaderActions(): array` (overrides / merges with resource header actions — check Filament ListRecords API). On the page:

```php
use Filament\Tables\Actions\ExportAction;
use App\Filament\Exports\UkomApplicationExporter;

protected function getTableHeaderActions(): array
{
    return [
        ExportAction::make()
            ->label('Ekspor')
            ->exporter(UkomApplicationExporter::class)
            ->color('success')
            ->button()
            ->icon('heroicon-m-arrow-down-tray')
            ->modifyQueryUsing(function (): Builder {
                return UkomApplicationResource::applyVerificationFilters(
                    UkomApplicationResource::getEloquentQuery(),
                    $this->tableFilters ?? [],
                );
            }),
    ];
}
```

If `modifyQueryUsing` **must** accept and return `Builder $query`, ignore `$query` (it includes the active tab) and return the rebuilt query above.

Also apply table search if trivial:

```php
$query = UkomApplicationResource::applyVerificationFilters(
    UkomApplicationResource::getEloquentQuery(),
    $this->tableFilters ?? [],
);

if (filled($this->tableSearch)) {
    $search = '%'.$this->tableSearch.'%';
    $query->where(function (Builder $q) use ($search): void {
        $q->where('nama', 'like', $search)
            ->orWhere('nip', 'like', $search);
    });
}

return $query;
```

Eager-load relations the exporter needs if not already on `getEloquentQuery()`: `targetCRole`, `agenciable` (already present).

- [ ] **Step 5: Run full ukom feature file**

```bash
php artisan test tests/Feature/Ukom/PengajuanUkomFlowTest.php
```

Expected: all PASS.

- [ ] **Step 6: Skip commit** (user commits manually)

---

## Spec coverage (self-review)

| Spec requirement | Task |
| --- | --- |
| Cascading Kluster → Instansi | Task 1 |
| Status filter, no draft | Task 1 |
| Tabs + filters stack on screen | Task 1 (unchanged tabs) |
| ExportAction xlsx | Task 2 |
| Columns = table | Task 2 |
| Export applies filters | Task 2 |
| Export ignores tabs | Task 2 (`getEloquentQuery` + `applyVerificationFilters`, not live tab query) |
| No bulk export | Task 2 (omit) |
| Pembina scope on export | Task 2 test |
| No auto-commit | Global constraint / skip commit steps |

**Placeholder scan:** ExportAction Livewire wiring has a deliberate “verify Filament signature / prefer page `getTableHeaderActions`” note — implementer must pick the working Filament API, not leave TODOs in code.

**Type consistency:** `applyVerificationFilters(Builder, array): Builder` is the shared contract between Task 1 filters and Task 2 export.
