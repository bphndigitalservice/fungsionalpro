# Master JF Enum Alignment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Align Master JF Kluster (`type`), Status, and Status Kepegawaian with Client enum values/labels via casts, a data migration, and Filament/import/matching updates.

**Architecture:** Add `JenisKepegawaian` for Master JF kepegawaian; cast `MasterJf.type` → `ClientCluster`, `status` → `ClientStatus`, `status_kepegawaian` → `JenisKepegawaian`. Centralize label→value mapping in `MasterJfEnumMapper` (used by migration + import). Filament and widgets consume enum classes directly; `ClientMatchingService` assigns `ClientStatus` when already cast.

**Tech Stack:** Laravel 12, PHP 8.2+ backed enums (`HasLabel`), Filament v3.2, Maatwebsite Excel, PHPUnit 11, Livewire 3

## Global Constraints

- Spec: `docs/superpowers/specs/2026-07-28-master-jf-enum-alignment-design.md`
- Do **not** change Client form/filter hardcoded cluster labels (Pusat / Provinsi / Kab/Kota)
- Do **not** rename DB columns
- Do **not** touch `pengangkatan` / `CRoleAssignation`
- Do **not** change how `ClientMatchingService::determineAgencyInfo` derives agency from instansi text
- Do **not** introduce `JenisKepegawaian` on Client
- Remove `MasterJf::statusOptions()` and `MasterJf::statusKepegawaianOptions()` (no wrappers)
- Conventional commits; commit after each task

---

## File structure

| File | Responsibility |
| --- | --- |
| `app/Enums/JenisKepegawaian.php` | PNS/PPPK enum with `HasLabel` (Master JF only) |
| `app/Support/MasterJfEnumMapper.php` | Map raw/label/value strings → enum values (`null` if unknown) |
| `app/Models/MasterJf.php` | Casts; remove status/kepegawaian option helpers |
| `database/factories/MasterJfFactory.php` | Seed enum values |
| `database/migrations/2026_07_28_XXXXXX_normalize_master_jf_enum_columns.php` | One-shot data migration using mapper |
| `app/Filament/Resources/MasterJfResource.php` | Kluster label + enum selects/filters/columns |
| `app/Filament/Widgets/MasterJfNumbersByStatusOverview.php` | Iterate `ClientStatus::cases()` |
| `app/Filament/Widgets/MasterJfNumbersByStatusKepegawaianOverview.php` | Iterate `JenisKepegawaian::cases()` |
| `app/Imports/MasterJfImport.php` | Normalize status/kepegawaian via mapper (`type` still from `determineAgencyInfo`) |
| `app/Services/ClientMatchingService.php` | Assign `ClientStatus` directly when Master JF status is that enum |
| `tests/Unit/Support/MasterJfEnumMapperTest.php` | Mapper coverage |
| `tests/Feature/MasterJfModelTest.php` | Casts + import with enum values |
| `tests/Feature/Migrations/NormalizeMasterJfEnumColumnsTest.php` | Migration maps old labels; unmapped type → null |
| `tests/Feature/Filament/MasterJfListFiltersTest.php` | Filter with enum values |
| `tests/Feature/Filament/MasterJfListStatsTest.php` | Stats with enum values |

---

### Task 1: `JenisKepegawaian` enum

**Files:**
- Create: `app/Enums/JenisKepegawaian.php`
- Test: `tests/Unit/Enums/JenisKepegawaianTest.php`

**Interfaces:**
- Consumes: Filament `HasLabel`
- Produces: `JenisKepegawaian::PNS` (`'PNS'`), `JenisKepegawaian::PPPK` (`'PPPK'`); `getLabel()` returns the value string

- [ ] **Step 1: Write the failing enum test**

Create `tests/Unit/Enums/JenisKepegawaianTest.php`:

```php
<?php

namespace Tests\Unit\Enums;

use App\Enums\JenisKepegawaian;
use PHPUnit\Framework\TestCase;

class JenisKepegawaianTest extends TestCase
{
    public function test_cases_and_labels(): void
    {
        $this->assertSame('PNS', JenisKepegawaian::PNS->value);
        $this->assertSame('PPPK', JenisKepegawaian::PPPK->value);
        $this->assertSame('PNS', JenisKepegawaian::PNS->getLabel());
        $this->assertSame('PPPK', JenisKepegawaian::PPPK->getLabel());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=JenisKepegawaianTest`

Expected: FAIL (enum class not found)

- [ ] **Step 3: Create the enum**

Create `app/Enums/JenisKepegawaian.php`:

```php
<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum JenisKepegawaian: string implements HasLabel
{
    case PNS = 'PNS';
    case PPPK = 'PPPK';

    public function getLabel(): ?string
    {
        return $this->value;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=JenisKepegawaianTest`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Enums/JenisKepegawaian.php tests/Unit/Enums/JenisKepegawaianTest.php
git commit -m "feat(master-jf): add JenisKepegawaian enum"
```

---

### Task 2: `MasterJfEnumMapper`

**Files:**
- Create: `app/Support/MasterJfEnumMapper.php`
- Test: `tests/Unit/Support/MasterJfEnumMapperTest.php`

**Interfaces:**
- Consumes: `ClientStatus`, `ClientCluster`, `JenisKepegawaian`
- Produces:
  - `MasterJfEnumMapper::status(?string $raw): ?string` — enum value or `null`
  - `MasterJfEnumMapper::type(?string $raw): ?string` — enum value or `null`
  - `MasterJfEnumMapper::statusKepegawaian(?string $raw): ?string` — enum value or `null`
- Blank / unknown → `null`. Already-valid enum values pass through.

- [ ] **Step 1: Write the failing mapper tests**

Create `tests/Unit/Support/MasterJfEnumMapperTest.php`:

```php
<?php

namespace Tests\Unit\Support;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;
use App\Support\MasterJfEnumMapper;
use PHPUnit\Framework\TestCase;

class MasterJfEnumMapperTest extends TestCase
{
    public function test_status_maps_indonesian_labels_and_values(): void
    {
        $this->assertSame(ClientStatus::Active->value, MasterJfEnumMapper::status('Aktif'));
        $this->assertSame(ClientStatus::NonActive_CTLN->value, MasterJfEnumMapper::status('CTLN'));
        $this->assertSame(ClientStatus::Active->value, MasterJfEnumMapper::status('active'));
        $this->assertNull(MasterJfEnumMapper::status('unknown-status'));
        $this->assertNull(MasterJfEnumMapper::status(null));
        $this->assertNull(MasterJfEnumMapper::status(''));
    }

    public function test_status_maps_all_known_labels(): void
    {
        $this->assertSame('non_active_resign', MasterJfEnumMapper::status('Mengundurkan diri'));
        $this->assertSame('non_active_suspended', MasterJfEnumMapper::status('Diberhentikan Sementara sebagai PNS'));
        $this->assertSame('non_active_study_leave', MasterJfEnumMapper::status('Tugas belajar > 6 Bulan'));
        $this->assertSame('non_active_external_assignment', MasterJfEnumMapper::status('Ditugaskan secara penuh di luar jabatan'));
        $this->assertSame('non_active_doesnt_meet_role_requirement', MasterJfEnumMapper::status('Tidak Memenuhi Persyaratan Jabatan'));
    }

    public function test_type_maps_synonyms_and_nulls_unknown(): void
    {
        $this->assertSame(ClientCluster::Central->value, MasterJfEnumMapper::type('central'));
        $this->assertSame(ClientCluster::Central->value, MasterJfEnumMapper::type('Pusat'));
        $this->assertSame(ClientCluster::Central->value, MasterJfEnumMapper::type('Kementerian Lembaga'));
        $this->assertSame(ClientCluster::LocalProvince->value, MasterJfEnumMapper::type('Provinsi'));
        $this->assertSame(ClientCluster::LocalProvince->value, MasterJfEnumMapper::type('Pemda - Provinsi'));
        $this->assertSame(ClientCluster::LocalRegency->value, MasterJfEnumMapper::type('Kab/Kota'));
        $this->assertSame(ClientCluster::LocalRegency->value, MasterJfEnumMapper::type('Pemda - Kabupaten/Kota'));
        $this->assertNull(MasterJfEnumMapper::type('something-else'));
        $this->assertNull(MasterJfEnumMapper::type(null));
    }

    public function test_status_kepegawaian_maps_known_values(): void
    {
        $this->assertSame(JenisKepegawaian::PNS->value, MasterJfEnumMapper::statusKepegawaian('PNS'));
        $this->assertSame(JenisKepegawaian::PPPK->value, MasterJfEnumMapper::statusKepegawaian('PPPK'));
        $this->assertNull(MasterJfEnumMapper::statusKepegawaian('Honorer'));
        $this->assertNull(MasterJfEnumMapper::statusKepegawaian(null));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=MasterJfEnumMapperTest`

Expected: FAIL (class not found)

- [ ] **Step 3: Implement the mapper**

Create `app/Support/MasterJfEnumMapper.php`:

```php
<?php

namespace App\Support;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;

final class MasterJfEnumMapper
{
    /** @return array<string, string> label/value → enum value */
    private static function statusMap(): array
    {
        $map = [];
        foreach (ClientStatus::cases() as $case) {
            $map[$case->value] = $case->value;
            $map[$case->getLabel()] = $case->value;
        }

        return $map;
    }

    /** @return array<string, string> synonym/value → enum value */
    private static function typeMap(): array
    {
        return [
            ClientCluster::Central->value => ClientCluster::Central->value,
            ClientCluster::Central->getLabel() => ClientCluster::Central->value,
            'Pusat' => ClientCluster::Central->value,
            ClientCluster::LocalProvince->value => ClientCluster::LocalProvince->value,
            ClientCluster::LocalProvince->getLabel() => ClientCluster::LocalProvince->value,
            'Provinsi' => ClientCluster::LocalProvince->value,
            ClientCluster::LocalRegency->value => ClientCluster::LocalRegency->value,
            ClientCluster::LocalRegency->getLabel() => ClientCluster::LocalRegency->value,
            'Kab/Kota' => ClientCluster::LocalRegency->value,
        ];
    }

    public static function status(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        return self::statusMap()[trim($raw)] ?? null;
    }

    public static function type(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        return self::typeMap()[trim($raw)] ?? null;
    }

    public static function statusKepegawaian(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $trimmed = trim($raw);

        return JenisKepegawaian::tryFrom($trimmed)?->value;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=MasterJfEnumMapperTest`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Support/MasterJfEnumMapper.php tests/Unit/Support/MasterJfEnumMapperTest.php
git commit -m "feat(master-jf): add enum label/value mapper"
```

---

### Task 3: MasterJf casts + factory + drop option helpers

**Files:**
- Modify: `app/Models/MasterJf.php`
- Modify: `database/factories/MasterJfFactory.php`
- Modify: `tests/Feature/MasterJfModelTest.php`

**Interfaces:**
- Consumes: `ClientCluster`, `ClientStatus`, `JenisKepegawaian`
- Produces: Eloquent casts on `type`, `status`, `status_kepegawaian`
- Removes: `statusOptions()`, `statusKepegawaianOptions()`

- [ ] **Step 1: Update model tests for casts (replace statusOptions test)**

In `tests/Feature/MasterJfModelTest.php`:

1. Change `test_it_persists_status_kepegawaian_via_factory` to use enum values:

```php
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;

public function test_it_persists_status_kepegawaian_via_factory(): void
{
    $row = MasterJf::factory()->create([
        'status_kepegawaian' => JenisKepegawaian::PNS,
        'status' => ClientStatus::Active,
    ]);

    $this->assertDatabaseHas('master_jf', [
        'id' => $row->id,
        'status_kepegawaian' => 'PNS',
        'status' => 'active',
    ]);

    $this->assertInstanceOf(ClientStatus::class, $row->status);
    $this->assertInstanceOf(JenisKepegawaian::class, $row->status_kepegawaian);
}
```

2. Replace `test_status_options_match_known_keys` with:

```php
public function test_it_casts_type_to_client_cluster(): void
{
    $row = MasterJf::factory()->create([
        'type' => ClientCluster::Central,
    ]);

    $this->assertDatabaseHas('master_jf', [
        'id' => $row->id,
        'type' => 'central',
    ]);
    $this->assertInstanceOf(ClientCluster::class, $row->fresh()->type);
}
```

Add `use App\Enums\ClientCluster;`.

3. In `test_import_does_not_clear_existing_c_role_id`, change `'status' => 'Aktif'` to `'status' => 'active'` (import will normalize later in Task 6; for now either keep Aktif if import still passes through, or use `active` after Task 6 — **for this task keep `'status' => 'Aktif'`** until Task 6 updates import; assert DB may still store Aktif until then). Prefer: leave import test status as `'Aktif'` until Task 6; do not assert status value in that test (it already only asserts nama + c_role_id).

- [ ] **Step 2: Run tests — expect failures on casts / missing helpers**

Run: `php artisan test --filter=MasterJfModelTest`

Expected: FAIL on cast assertions and/or `statusOptions` removal once you delete helpers (factory still referencing helpers will also fail).

- [ ] **Step 3: Update model casts and remove helpers**

In `app/Models/MasterJf.php`:

1. Add imports and `$casts` (same style as `Client`):

```php
use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;

protected $casts = [
    'type' => ClientCluster::class,
    'status' => ClientStatus::class,
    'status_kepegawaian' => JenisKepegawaian::class,
];
```

2. Delete `statusOptions()` and `statusKepegawaianOptions()` entirely.
3. Keep `pengangkatanOptions()` and `distinctOptions()`.

- [ ] **Step 4: Update factory**

In `database/factories/MasterJfFactory.php`:

```php
use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;

// inside definition():
'status' => fake()->randomElement(ClientStatus::cases())->value,
'type' => fake()->randomElement([...array_column(ClientCluster::cases(), 'value'), null]),
'status_kepegawaian' => fake()->randomElement([...array_column(JenisKepegawaian::cases(), 'value'), null]),
```

- [ ] **Step 5: Run model tests**

Run: `php artisan test --filter=MasterJfModelTest`

Expected: PASS for model/cast tests. Import test may still pass with raw `Aktif` if cast accepts it — if Eloquent throws on invalid cast when saving Aktif via import, defer fixing import to Task 6 and temporarily change that import fixture status to `'active'` so Task 3 stays green.

- [ ] **Step 6: Commit**

```bash
git add app/Models/MasterJf.php database/factories/MasterJfFactory.php tests/Feature/MasterJfModelTest.php
git commit -m "feat(master-jf): cast type/status/kepegawaian to enums"
```

---

### Task 4: Data migration

**Files:**
- Create: `database/migrations/2026_07_28_112200_normalize_master_jf_enum_columns.php` (use `php artisan make:migration normalize_master_jf_enum_columns` and rename timestamp if needed)
- Test: `tests/Feature/Migrations/NormalizeMasterJfEnumColumnsTest.php`

**Interfaces:**
- Consumes: `MasterJfEnumMapper`, `DB` facade on `master_jf`
- Produces: In-place updates of `status`, `type`, `status_kepegawaian` to enum values or `null`
- Uses query builder / `DB::table` (not Eloquent casts) so old labels can be read and rewritten

- [ ] **Step 1: Write the failing migration test**

Create `tests/Feature/Migrations/NormalizeMasterJfEnumColumnsTest.php`:

```php
<?php

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NormalizeMasterJfEnumColumnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_normalizes_labels_and_nulls_unknown_type(): void
    {
        if (! Schema::hasTable('master_jf')) {
            $this->markTestSkipped('master_jf table missing');
        }

        DB::table('master_jf')->insert([
            [
                'nama' => 'A',
                'nip' => '111111111111111111',
                'status' => 'Aktif',
                'type' => 'Pusat',
                'status_kepegawaian' => 'PNS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'B',
                'nip' => '222222222222222222',
                'status' => 'CTLN',
                'type' => 'weird-legacy-type',
                'status_kepegawaian' => 'Honorer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_07_28_112200_normalize_master_jf_enum_columns.php',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('master_jf', [
            'nip' => '111111111111111111',
            'status' => 'active',
            'type' => 'central',
            'status_kepegawaian' => 'PNS',
        ]);

        $rowB = DB::table('master_jf')->where('nip', '222222222222222222')->first();
        $this->assertSame('non_active_ctln', $rowB->status);
        $this->assertNull($rowB->type);
        $this->assertNull($rowB->status_kepegawaian);
    }
}
```

Adjust migration filename in `--path` to match the file you generate.

**Note:** `RefreshDatabase` already runs all migrations. Prefer testing the migrator logic by extracting a static `up` helper, **or** insert rows *before* calling only this migration by using `Migrator` carefully. Simpler approach used in this plan:

- Put the update loop in `MasterJfEnumMapper::normalizeTable(): void` (or a dedicated `NormalizeMasterJfEnumColumns` action).
- Migration `up()` only calls that method.
- Test calls `MasterJfEnumMapper::normalizeTable()` (or the action) after inserting raw rows — **do not** re-run a single migration file via `artisan migrate --path` under `RefreshDatabase` (it is already migrated).

Update Step 1 test to:

```php
MasterJfEnumMapper::normalizeTable();
```

instead of `artisan migrate`. Keep a thin migration that calls the same method so production deploy runs it once.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=NormalizeMasterJfEnumColumnsTest`

Expected: FAIL (`normalizeTable` missing)

- [ ] **Step 3: Add `normalizeTable()` and migration**

Append to `app/Support/MasterJfEnumMapper.php`:

```php
use Illuminate\Support\Facades\DB;

public static function normalizeTable(): void
{
    DB::table('master_jf')->orderBy('id')->chunkById(200, function ($rows) {
        foreach ($rows as $row) {
            DB::table('master_jf')->where('id', $row->id)->update([
                'status' => self::status($row->status),
                'type' => self::type($row->type),
                'status_kepegawaian' => self::statusKepegawaian($row->status_kepegawaian),
                'updated_at' => now(),
            ]);
        }
    });
}
```

Create migration:

```php
<?php

use App\Support\MasterJfEnumMapper;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        MasterJfEnumMapper::normalizeTable();
    }

    public function down(): void
    {
        // Irreversible data normalization
    }
};
```

- [ ] **Step 4: Extend unit tests for `normalizeTable` (optional but preferred)**

Add a feature assertion in `NormalizeMasterJfEnumColumnsTest` as rewritten in Step 1.

Run: `php artisan test --filter=NormalizeMasterJfEnumColumnsTest`

Expected: PASS

Also re-run: `php artisan test --filter=MasterJfEnumMapperTest`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Support/MasterJfEnumMapper.php database/migrations/*normalize_master_jf_enum_columns.php tests/Feature/Migrations/NormalizeMasterJfEnumColumnsTest.php
git commit -m "feat(master-jf): migrate status/type/kepegawaian to enum values"
```

---

### Task 5: Filament Master JF resource

**Files:**
- Modify: `app/Filament/Resources/MasterJfResource.php`
- Modify: `tests/Feature/Filament/MasterJfListFiltersTest.php`

**Interfaces:**
- Form/filter options: `ClientCluster::class`, `ClientStatus::class`, `JenisKepegawaian::class`
- UI label for `type`: **Kluster** (form, column, filter)

- [ ] **Step 1: Update filter test to enum values**

In `tests/Feature/Filament/MasterJfListFiltersTest.php`:

```php
use App\Enums\ClientStatus;

public function test_status_filter_limits_visible_table_records(): void
{
    $this->actingAsAdmin();

    $aktif = MasterJf::factory()->create([
        'status' => ClientStatus::Active,
        'nama' => 'Aktif Person',
    ]);
    $ctln = MasterJf::factory()->create([
        'status' => ClientStatus::NonActive_CTLN,
        'nama' => 'CTLN Person',
    ]);

    Livewire::test(ListMasterJfs::class)
        ->assertCanSeeTableRecords([$aktif, $ctln])
        ->filterTable('status', ClientStatus::Active->value)
        ->assertCanSeeTableRecords([$aktif])
        ->assertCanNotSeeTableRecords([$ctln]);
}
```

- [ ] **Step 2: Run filter test — may fail if filter still uses label keys**

Run: `php artisan test --filter=MasterJfListFiltersTest::test_status_filter`

Expected: FAIL or wrong filter options until resource updated

- [ ] **Step 3: Update `MasterJfResource`**

Add imports:

```php
use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;
```

Replace form fields:

```php
Forms\Components\Select::make('type')
    ->label('Kluster')
    ->options(ClientCluster::class)
    ->searchable()
    ->preload(),
Forms\Components\Select::make('status')
    ->label('Status')
    ->options(ClientStatus::class)
    ->searchable()
    ->preload(),
Forms\Components\Select::make('status_kepegawaian')
    ->label('Status Kepegawaian')
    ->options(JenisKepegawaian::class)
    ->searchable()
    ->preload(),
```

Replace table column for type:

```php
Tables\Columns\TextColumn::make('type')
    ->label('Kluster')
    ->searchable()
    ->sortable(),
```

Replace filters:

```php
Tables\Filters\SelectFilter::make('status')
    ->options(ClientStatus::class),
Tables\Filters\SelectFilter::make('status_kepegawaian')
    ->label('Status Kepegawaian')
    ->options(JenisKepegawaian::class),
Tables\Filters\SelectFilter::make('type')
    ->label('Kluster')
    ->options(ClientCluster::class)
    ->searchable(),
```

Leave `pengangkatan`, `gol_ruang`, multi-selects, and `c_role_id` unchanged.

- [ ] **Step 4: Run filter tests**

Run: `php artisan test --filter=MasterJfListFiltersTest`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Resources/MasterJfResource.php tests/Feature/Filament/MasterJfListFiltersTest.php
git commit -m "feat(master-jf): use Client enums for Kluster/Status filters"
```

---

### Task 6: Stats widgets

**Files:**
- Modify: `app/Filament/Widgets/MasterJfNumbersByStatusOverview.php`
- Modify: `app/Filament/Widgets/MasterJfNumbersByStatusKepegawaianOverview.php`
- Modify: `tests/Feature/Filament/MasterJfListStatsTest.php`

**Interfaces:**
- Iterate `ClientStatus::cases()` / `JenisKepegawaian::cases()`
- Icons: Active → check-circle; other statuses → x-circle (match Client widget pattern)
- Labels from `getLabel()` (Aktif, CTLN, … still appear in UI)

- [ ] **Step 1: Update stats tests to seed enum values / filter enum values**

In `tests/Feature/Filament/MasterJfListStatsTest.php`, add imports and replace every `'status' => 'Aktif'` with `ClientStatus::Active` (or `->value`), `'CTLN'` with `ClientStatus::NonActive_CTLN`, and filter values:

```php
use App\Enums\ClientStatus;

// examples:
MasterJf::factory()->count(2)->create(['status' => ClientStatus::Active]);
MasterJf::factory()->count(3)->create(['status' => ClientStatus::NonActive_CTLN]);

'tableFilters' => [
    'status' => ['value' => ClientStatus::Active->value],
],

->filterTable('status', ClientStatus::Active->value);

$this->assertSame(ClientStatus::Active->value, data_get($widgetData, 'tableFilters.status.value'));
```

Keep `assertSee('Aktif')` / `assertSee('CTLN')` — those are **labels**.

- [ ] **Step 2: Run stats tests — expect widget failures if still calling removed helpers**

Run: `php artisan test --filter=MasterJfListStatsTest`

Expected: FAIL on `statusOptions()` / `statusKepegawaianOptions()` if still referenced

- [ ] **Step 3: Update status widget**

In `MasterJfNumbersByStatusOverview.php`:

```php
use App\Enums\ClientStatus;

// replace foreach (MasterJf::statusOptions() ...) with:
foreach (ClientStatus::cases() as $status) {
    $stats[] = Stat::make($status->getLabel(), number_format((int) ($counts[$status->value] ?? 0)))
        ->icon($status === ClientStatus::Active ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle');
}
```

Remove unused `MasterJf` import if no longer needed.

- [ ] **Step 4: Update kepegawaian widget**

```php
use App\Enums\JenisKepegawaian;

foreach (JenisKepegawaian::cases() as $jenis) {
    $stats[] = Stat::make($jenis->getLabel(), number_format((int) ($counts[$jenis->value] ?? 0)))
        ->icon('heroicon-o-identification');
}
```

- [ ] **Step 5: Run stats + filter tests**

Run: `php artisan test --filter=MasterJfList`

Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Filament/Widgets/MasterJfNumbersByStatusOverview.php app/Filament/Widgets/MasterJfNumbersByStatusKepegawaianOverview.php tests/Feature/Filament/MasterJfListStatsTest.php
git commit -m "feat(master-jf): drive status widgets from enums"
```

---

### Task 7: Import normalization

**Files:**
- Modify: `app/Imports/MasterJfImport.php`
- Modify: `tests/Feature/MasterJfModelTest.php` (extend import coverage)

**Interfaces:**
- `status` / `status_kepegawaian` from spreadsheet → `MasterJfEnumMapper`
- `type` remains from `ClientMatchingService::determineAgencyInfo` (already returns enum values); do not map spreadsheet type column (there isn’t one today)

- [ ] **Step 1: Add failing import normalization assertions**

Append to `tests/Feature/MasterJfModelTest.php`:

```php
public function test_import_normalizes_status_label_to_enum_value(): void
{
    (new MasterJfImport)->model([
        'nip' => '333333333333333333',
        'nama' => 'Imported',
        'golruang' => 'III/a',
        'jabatan' => 'Analis',
        'unit_kerjakanwil' => 'Unit A',
        'instansi' => 'Instansi A',
        'pengangkatan' => 'Inpassing',
        'status' => 'Aktif',
        'status_kepegawaian' => 'PNS',
    ]);

    $this->assertDatabaseHas('master_jf', [
        'nip' => '333333333333333333',
        'status' => 'active',
        'status_kepegawaian' => 'PNS',
    ]);
}

public function test_import_nulls_unknown_status(): void
{
    (new MasterJfImport)->model([
        'nip' => '444444444444444444',
        'nama' => 'Unknown Status',
        'status' => 'bukan-status',
        'status_kepegawaian' => 'Honorer',
    ]);

    $row = MasterJf::query()->where('nip', '444444444444444444')->first();
    $this->assertNotNull($row);
    $this->assertNull($row->status);
    $this->assertNull($row->status_kepegawaian);
}
```

- [ ] **Step 2: Run to verify fail**

Run: `php artisan test --filter=test_import_normalizes_status_label`

Expected: FAIL (`status` still `Aktif` or cast error)

- [ ] **Step 3: Normalize in import**

In `app/Imports/MasterJfImport.php`:

```php
use App\Support\MasterJfEnumMapper;

// in updateOrCreate attributes:
'status' => MasterJfEnumMapper::status($row['status'] ?? null),
'status_kepegawaian' => MasterJfEnumMapper::statusKepegawaian($row['status_kepegawaian'] ?? null),
'type' => $type, // still from determineAgencyInfo
```

Also update `test_import_does_not_clear_existing_c_role_id` to pass `'status' => 'Aktif'` (now normalized) — no assertion change needed.

- [ ] **Step 4: Run model tests**

Run: `php artisan test --filter=MasterJfModelTest`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Imports/MasterJfImport.php tests/Feature/MasterJfModelTest.php
git commit -m "feat(master-jf): normalize import status via enum mapper"
```

---

### Task 8: ClientMatchingService status assignment

**Files:**
- Modify: `app/Services/ClientMatchingService.php`
- Create: `tests/Feature/ClientMatchingServiceStatusTest.php`

**Interfaces:**
- When `$master->status` is `ClientStatus`, set `$client->status = $master->status`
- Keep fuzzy Indonesian matching only as fallback when status is somehow still a string / null path not needed if cast always returns enum|null — use:

```php
if ($master->status instanceof \App\Enums\ClientStatus) {
    $client->status = $master->status;
} else {
    // existing fuzzy match on string for safety
}
```

- [ ] **Step 1: Write failing matching test**

Create `tests/Feature/ClientMatchingServiceStatusTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\MasterJf;
use App\Services\ClientMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientMatchingServiceStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_master_data_assigns_client_status_enum_directly(): void
    {
        $master = MasterJf::factory()->create([
            'status' => ClientStatus::NonActive_CTLN,
            'jabatan' => 'Analis Hukum Ahli Pertama', // may need a real CRole fixture if applyMasterData requires role match
        ]);

        // If applyMasterData only sets status inside the `if ($role)` block, create matching CRole + minimal grade/agency data as existing matching tests do.
        // Prefer the smallest fixture that enters the block where status is assigned.

        $client = new Client;
        app(ClientMatchingService::class)->applyMasterData($client, $master);

        $this->assertSame(ClientStatus::NonActive_CTLN, $client->status);
    }
}
```

**Important:** Read `applyMasterData` — status assignment is inside `if ($role)`. Seed a `CRole` whose `role_name` appears in `$master->jabatan` (same pattern as production matching). Example:

```php
\App\Models\CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
$master = MasterJf::factory()->create([
    'jabatan' => 'Analis Hukum Ahli Pertama',
    'status' => ClientStatus::NonActive_CTLN,
]);
```

You may also need `CRoleLevel` id 1 if the code defaults to it — create one if the DB requires it.

- [ ] **Step 2: Run test**

Run: `php artisan test --filter=ClientMatchingServiceStatusTest`

Expected: FAIL or pass via fuzzy path; after Step 3 must pass via direct enum assign

- [ ] **Step 3: Prefer direct enum assignment**

In `ClientMatchingService::applyMasterData`, replace the fuzzy `$rawStatus = strtolower($master->status ?? '')` block with:

```php
if ($master->status instanceof \App\Enums\ClientStatus) {
    $client->status = $master->status;
} else {
    $rawStatus = strtolower((string) ($master->status ?? ''));
    $client->status = match (true) {
        str_contains($rawStatus, 'aktif') || str_contains($rawStatus, 'active')
            => \App\Enums\ClientStatus::Active,
        str_contains($rawStatus, 'undur') || str_contains($rawStatus, 'resign')
            => \App\Enums\ClientStatus::NonActive_Resign,
        str_contains($rawStatus, 'sementara') || str_contains($rawStatus, 'suspend') || str_contains($rawStatus, 'skors')
            => \App\Enums\ClientStatus::NonActive_Suspended,
        str_contains($rawStatus, 'ctln')
            => \App\Enums\ClientStatus::NonActive_CTLN,
        str_contains($rawStatus, 'belajar') || str_contains($rawStatus, 'study')
            => \App\Enums\ClientStatus::NonActive_StudyLeave,
        str_contains($rawStatus, 'luar jabatan') || str_contains($rawStatus, 'external')
            => \App\Enums\ClientStatus::NonActive_ExternalAssignment,
        str_contains($rawStatus, 'tidak memenuhi') || str_contains($rawStatus, 'requirement')
            => \App\Enums\ClientStatus::NonActive_DoesntMeetRoleRequirement,
        default => null,
    };
}
```

- [ ] **Step 4: Run matching + Master JF suite**

Run:

```bash
php artisan test --filter='MasterJf|ClientMatchingServiceStatus|JenisKepegawaian|MasterJfEnumMapper|NormalizeMasterJf'
```

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/ClientMatchingService.php tests/Feature/ClientMatchingServiceStatusTest.php
git commit -m "feat(matching): assign ClientStatus directly from Master JF"
```

---

### Task 9: Final verification

**Files:** none (verification only)

- [ ] **Step 1: Grep for removed helpers and old status literals in Master JF code/tests**

Run:

```bash
rg "statusOptions|statusKepegawaianOptions" app tests
rg "status' => 'Aktif'|status' => 'CTLN'|filterTable\('status', 'Aktif" tests
```

Expected: no matches in `app/` or Master JF tests (except historical comments if any)

- [ ] **Step 2: Full Master JF–related test run**

Run:

```bash
php artisan test --filter='MasterJf|ClientMatchingServiceStatus|JenisKepegawaian|MasterJfEnumMapper|NormalizeMasterJf'
```

Expected: all PASS

- [ ] **Step 3: Commit any leftover fixes** (only if Step 1/2 required code changes)

```bash
git add -A
git commit -m "test(master-jf): finish enum alignment cleanup"
```

Skip this commit if the working tree is clean.

---

## Spec coverage checklist

| Spec requirement | Task |
| --- | --- |
| `JenisKepegawaian` for Master JF only | Task 1 |
| Casts to `ClientCluster` / `ClientStatus` / `JenisKepegawaian` | Task 3 |
| Remove `statusOptions` / `statusKepegawaianOptions` | Task 3 |
| Data migration; unmapped type → null | Task 2 + 4 |
| Filament Kluster label + enum options | Task 5 |
| Widgets iterate enum cases | Task 6 |
| Import normalize label/value → null unknown | Task 7 |
| Matching direct `ClientStatus` assign | Task 8 |
| Update factory / filter / stats tests | Tasks 3, 5, 6 |
| Out of scope Client UI / pengangkatan / agency derivation | Global constraints |

## Placeholder / consistency review

- Mapper method names (`status` / `type` / `statusKepegawaian` / `normalizeTable`) are consistent across Tasks 2–7.
- Stored values always use enum `->value` strings in DB assertions.
- UI assertions keep Indonesian labels from `getLabel()`.
- Migration tested via `normalizeTable()` to avoid `RefreshDatabase` + `--path` pitfalls.
