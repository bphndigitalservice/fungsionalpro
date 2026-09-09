# Master JF Gol Ruang & Jenjang FK Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Wire Master JF Golongan/Ruang and Jenjang to nullable `reg_grade_id` / `c_role_level_id` FKs across model, form, list, filter, widget, import, and client matching; backfill grades from legacy `gol_ruang` text.

**Architecture:** Add/ensure FK columns on `master_jf`, expose Eloquent relations, and replace free-text `gol_ruang` UI/aggregation with `RegGrade` / `CRoleLevel` references. Share text→grade resolution in `App\Support\RegGradeResolver` for backfill, import, and matching fallback. Stop writing `gol_ruang` on form/import; keep the column.

**Tech Stack:** Laravel 12, Filament v3.2, Livewire 3, PHPUnit 11, PostgreSQL/MySQL-compatible migrations

## Global Constraints

- Spec: `docs/superpowers/specs/2026-07-29-master-jf-gol-ruang-fk-design.md`
- Both FKs are **nullable / not required** on the form
- Do **not** drop `gol_ruang`; do **not** backfill `c_role_level_id`
- Do **not** dual-write display strings into `gol_ruang` on save
- Do **not** change Client form required rules or labels
- Prefer FKs in matching; keep text fallback for legacy rows
- Conventional commits; commit after each task

---

## File structure

| File | Responsibility |
| --- | --- |
| `database/migrations/2026_07_29_000001_add_grade_and_level_fks_to_master_jf_table.php` | Add `reg_grade_id` / `c_role_level_id` if missing + FKs |
| `database/migrations/2026_07_29_000002_backfill_master_jf_reg_grade_id.php` | One-shot backfill via resolver |
| `app/Support/RegGradeResolver.php` | Resolve raw gol_ruang text → `reg_grades.id` |
| `app/Models/MasterJf.php` | Fillable + `grade()` / `cRoleLevel()` relations; drop `gol_ruang` from fillable |
| `database/factories/MasterJfFactory.php` | Prefer `reg_grade_id` / `c_role_level_id` over `gol_ruang` |
| `app/Filament/Resources/MasterJfResource.php` | Form, table, filters for FKs |
| `app/Filament/Widgets/MasterJfNumbersByGolRuangOverview.php` | Group by `reg_grade_id` |
| `app/Imports/MasterJfImport.php` | Resolve `golruang` → `reg_grade_id`; stop writing `gol_ruang` |
| `app/Services/ClientMatchingService.php` | Prefer Master JF FKs; text fallback via resolver |
| `tests/Unit/Support/RegGradeResolverTest.php` | Resolver unit tests |
| `tests/Feature/Migrations/BackfillMasterJfRegGradeIdTest.php` | Backfill behavior |
| `tests/Feature/MasterJfModelTest.php` | Relations + import grade resolution |
| `tests/Feature/Filament/MasterJfEditFormTest.php` | Form saves FKs; clears level on role change |
| `tests/Feature/Filament/MasterJfListStatsTest.php` | Widget/filter use `reg_grade_id` |
| `tests/Feature/Filament/MasterJfListFiltersTest.php` | Grade/level list filters |
| `tests/Feature/ClientMatchingServiceStatusTest.php` | Matching prefers FKs |

---

### Task 1: Schema, model, factory

**Files:**
- Create: `database/migrations/2026_07_29_000001_add_grade_and_level_fks_to_master_jf_table.php`
- Modify: `app/Models/MasterJf.php`
- Modify: `database/factories/MasterJfFactory.php`
- Test: `tests/Feature/MasterJfModelTest.php`

**Interfaces:**
- Consumes: existing `reg_grades`, `c_role_levels`, `master_jf` tables
- Produces:
  - Nullable columns `master_jf.reg_grade_id`, `master_jf.c_role_level_id` with FKs (`nullOnDelete`)
  - `MasterJf::grade(): BelongsTo` → `RegGrade`
  - `MasterJf::cRoleLevel(): BelongsTo` → `CRoleLevel`
  - Fillable includes `reg_grade_id`, `c_role_level_id`; excludes `gol_ruang`

- [ ] **Step 1: Write the failing model relation test**

Append to `tests/Feature/MasterJfModelTest.php` (add imports for `RegGrade`, `CRoleLevel`):

```php
public function test_it_persists_reg_grade_and_c_role_level_relations(): void
{
    $grade = RegGrade::create([
        'grade_name' => 'Penata',
        'grade_code' => 'III/a',
    ]);

    $role = CRole::create([
        'role_name' => 'Analis Hukum',
        'active' => true,
    ]);

    $level = CRoleLevel::create([
        'c_role_id' => $role->id,
        'level' => 'Ahli Pertama',
    ]);

    $row = MasterJf::factory()->create([
        'reg_grade_id' => $grade->id,
        'c_role_id' => $role->id,
        'c_role_level_id' => $level->id,
    ]);

    $this->assertDatabaseHas('master_jf', [
        'id' => $row->id,
        'reg_grade_id' => $grade->id,
        'c_role_level_id' => $level->id,
    ]);

    $fresh = $row->fresh();
    $this->assertTrue($fresh->grade->is($grade));
    $this->assertTrue($fresh->cRoleLevel->is($level));
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_it_persists_reg_grade_and_c_role_level_relations`

Expected: FAIL (unknown column and/or missing relations / mass assignment)

- [ ] **Step 3: Create migration**

Create `database/migrations/2026_07_29_000001_add_grade_and_level_fks_to_master_jf_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_jf', function (Blueprint $table) {
            if (! Schema::hasColumn('master_jf', 'reg_grade_id')) {
                $table->unsignedBigInteger('reg_grade_id')->nullable()->after('gol_ruang');
            }

            if (! Schema::hasColumn('master_jf', 'c_role_level_id')) {
                $table->unsignedBigInteger('c_role_level_id')->nullable()->after('c_role_id');
            }
        });

        $this->addForeignKeyIfMissing('reg_grade_id', 'reg_grades');
        $this->addForeignKeyIfMissing('c_role_level_id', 'c_role_levels');
    }

    public function down(): void
    {
        Schema::table('master_jf', function (Blueprint $table) {
            if (Schema::hasColumn('master_jf', 'reg_grade_id')) {
                try {
                    $table->dropForeign(['reg_grade_id']);
                } catch (\Throwable) {
                }
                $table->dropColumn('reg_grade_id');
            }

            if (Schema::hasColumn('master_jf', 'c_role_level_id')) {
                try {
                    $table->dropForeign(['c_role_level_id']);
                } catch (\Throwable) {
                }
                $table->dropColumn('c_role_level_id');
            }
        });
    }

    private function addForeignKeyIfMissing(string $column, string $referencesTable): void
    {
        try {
            Schema::table('master_jf', function (Blueprint $table) use ($column, $referencesTable) {
                $table->foreign($column)
                    ->references('id')
                    ->on($referencesTable)
                    ->nullOnDelete();
            });
        } catch (\Throwable) {
            // FK already exists (or driver cannot add it twice)
        }
    }
};
```

- [ ] **Step 4: Update model**

In `app/Models/MasterJf.php`:

- Remove `'gol_ruang'` from `$fillable`
- Add `'reg_grade_id'`, `'c_role_level_id'` to `$fillable`
- Add relations:

```php
public function grade(): BelongsTo
{
    return $this->belongsTo(RegGrade::class, 'reg_grade_id');
}

public function cRoleLevel(): BelongsTo
{
    return $this->belongsTo(CRoleLevel::class, 'c_role_level_id');
}
```

- [ ] **Step 5: Update factory**

In `database/factories/MasterJfFactory.php` `definition()`:

- Remove `'gol_ruang' => ...`
- Add `'reg_grade_id' => null`
- Add `'c_role_level_id' => null`

- [ ] **Step 6: Run migration and test**

Run:

```bash
php artisan migrate
php artisan test --filter=test_it_persists_reg_grade_and_c_role_level_relations
```

Expected: PASS

Also run: `php artisan test --filter=MasterJfModelTest`  
Expected: PASS (existing import tests still pass; `golruang` may still write until Task 5)

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_07_29_000001_add_grade_and_level_fks_to_master_jf_table.php app/Models/MasterJf.php database/factories/MasterJfFactory.php tests/Feature/MasterJfModelTest.php
git commit -m "$(cat <<'EOF'
feat(master-jf): add reg_grade and c_role_level FK columns

EOF
)"
```

---

### Task 2: RegGradeResolver + backfill

**Files:**
- Create: `app/Support/RegGradeResolver.php`
- Create: `database/migrations/2026_07_29_000002_backfill_master_jf_reg_grade_id.php`
- Create: `tests/Unit/Support/RegGradeResolverTest.php`
- Create: `tests/Feature/Migrations/BackfillMasterJfRegGradeIdTest.php`

**Interfaces:**
- Consumes: `RegGrade` rows (`grade_code`, `grade_name`)
- Produces:
  - `RegGradeResolver::resolveId(?string $raw): ?int`
  - `RegGradeResolver::backfillMasterJf(): void` — sets `reg_grade_id` where null and `gol_ruang` matches; never touches `c_role_level_id`

- [ ] **Step 1: Write failing unit tests**

Create `tests/Unit/Support/RegGradeResolverTest.php`:

```php
<?php

namespace Tests\Unit\Support;

use App\Models\RegGrade;
use App\Support\RegGradeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegGradeResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_by_grade_code(): void
    {
        $grade = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);

        $this->assertSame($grade->id, RegGradeResolver::resolveId('III/a'));
    }

    public function test_resolves_formatted_label(): void
    {
        $grade = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);

        $this->assertSame($grade->id, RegGradeResolver::resolveId('Penata (III/a)'));
    }

    public function test_returns_null_for_unknown(): void
    {
        RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);

        $this->assertNull(RegGradeResolver::resolveId('ZZ/z'));
        $this->assertNull(RegGradeResolver::resolveId(null));
        $this->assertNull(RegGradeResolver::resolveId(''));
    }
}
```

- [ ] **Step 2: Run unit tests to verify fail**

Run: `php artisan test --filter=RegGradeResolverTest`

Expected: FAIL — class not found

- [ ] **Step 3: Implement resolver**

Create `app/Support/RegGradeResolver.php`:

```php
<?php

namespace App\Support;

use App\Models\RegGrade;
use Illuminate\Support\Facades\DB;

final class RegGradeResolver
{
    public static function resolveId(?string $raw): ?int
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        $grades = once(fn () => RegGrade::query()->get());

        $grade = $grades->first(function (RegGrade $g) use ($raw) {
            $matchCode = ! empty($g->grade_code) && stripos($raw, $g->grade_code) !== false;
            $matchName = ! empty($g->grade_name) && stripos($raw, $g->grade_name) !== false;

            return $matchCode || $matchName;
        });

        return $grade?->id;
    }

    public static function backfillMasterJf(): void
    {
        DB::table('master_jf')
            ->whereNull('reg_grade_id')
            ->whereNotNull('gol_ruang')
            ->where('gol_ruang', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $id = self::resolveId($row->gol_ruang);
                    if ($id === null) {
                        continue;
                    }

                    DB::table('master_jf')
                        ->where('id', $row->id)
                        ->update(['reg_grade_id' => $id]);
                }
            });
    }
}
```

- [ ] **Step 4: Run unit tests**

Run: `php artisan test --filter=RegGradeResolverTest`

Expected: PASS

- [ ] **Step 5: Write failing backfill feature test**

Create `tests/Feature/Migrations/BackfillMasterJfRegGradeIdTest.php`:

```php
<?php

namespace Tests\Feature\Migrations;

use App\Models\CRole;
use App\Models\CRoleLevel;
use App\Models\MasterJf;
use App\Models\RegGrade;
use App\Support\RegGradeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BackfillMasterJfRegGradeIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_sets_grade_from_gol_ruang_and_leaves_level_null(): void
    {
        $grade = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);

        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        CRoleLevel::create(['c_role_id' => $role->id, 'level' => 'Ahli Pertama']);

        $matched = MasterJf::factory()->create([
            'reg_grade_id' => null,
            'c_role_level_id' => null,
        ]);
        DB::table('master_jf')->where('id', $matched->id)->update(['gol_ruang' => 'III/a']);

        $junk = MasterJf::factory()->create([
            'reg_grade_id' => null,
            'c_role_level_id' => null,
        ]);
        DB::table('master_jf')->where('id', $junk->id)->update(['gol_ruang' => 'not-a-grade']);

        RegGradeResolver::backfillMasterJf();

        $this->assertDatabaseHas('master_jf', [
            'id' => $matched->id,
            'reg_grade_id' => $grade->id,
            'c_role_level_id' => null,
        ]);

        $this->assertDatabaseHas('master_jf', [
            'id' => $junk->id,
            'reg_grade_id' => null,
            'c_role_level_id' => null,
        ]);
    }
}
```

- [ ] **Step 6: Run backfill test**

Run: `php artisan test --filter=BackfillMasterJfRegGradeIdTest`

Expected: PASS (method already implemented in Step 3). If FAIL, fix resolver chunk logic.

- [ ] **Step 7: Add backfill migration**

Create `database/migrations/2026_07_29_000002_backfill_master_jf_reg_grade_id.php`:

```php
<?php

use App\Support\RegGradeResolver;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        RegGradeResolver::backfillMasterJf();
    }

    public function down(): void
    {
        // Irreversible data backfill
    }
};
```

- [ ] **Step 8: Commit**

```bash
git add app/Support/RegGradeResolver.php database/migrations/2026_07_29_000002_backfill_master_jf_reg_grade_id.php tests/Unit/Support/RegGradeResolverTest.php tests/Feature/Migrations/BackfillMasterJfRegGradeIdTest.php
git commit -m "$(cat <<'EOF'
feat(master-jf): resolve and backfill reg_grade_id from gol_ruang

EOF
)"
```

---

### Task 3: Filament form (Golongan/Ruang + Jenjang)

**Files:**
- Modify: `app/Filament/Resources/MasterJfResource.php` (form schema)
- Create: `tests/Feature/Filament/MasterJfEditFormTest.php`

**Interfaces:**
- Consumes: `MasterJf::grade()`, `MasterJf::cRoleLevel()`, `c_role_id`
- Produces: form fields `reg_grade_id`, `c_role_level_id` (nullable); `c_role_id` is `live()` and clears level on change; no `gol_ruang` form field

- [ ] **Step 1: Write failing edit-form tests**

Create `tests/Feature/Filament/MasterJfEditFormTest.php`:

```php
<?php

namespace Tests\Feature\Filament;

use App\Enums\SystemRole;
use App\Filament\Resources\MasterJfResource\Pages\EditMasterJf;
use App\Models\CRole;
use App\Models\CRoleLevel;
use App\Models\MasterJf;
use App\Models\RegGrade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MasterJfEditFormTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsAdmin(): User
    {
        Role::findOrCreate(SystemRole::Admin->value, 'web');
        $user = User::factory()->create();
        $user->assignRole(SystemRole::Admin->value);
        $this->actingAs($user);

        return $user;
    }

    public function test_edit_saves_reg_grade_and_c_role_level(): void
    {
        $this->actingAsAdmin();

        $grade = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $level = CRoleLevel::create(['c_role_id' => $role->id, 'level' => 'Ahli Pertama']);

        $record = MasterJf::factory()->create([
            'c_role_id' => $role->id,
        ]);

        Livewire::test(EditMasterJf::class, ['record' => $record->getRouteKey()])
            ->fillForm([
                'nama' => $record->nama,
                'nip' => $record->nip,
                'reg_grade_id' => $grade->id,
                'c_role_id' => $role->id,
                'c_role_level_id' => $level->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('master_jf', [
            'id' => $record->id,
            'reg_grade_id' => $grade->id,
            'c_role_level_id' => $level->id,
        ]);
    }

    public function test_changing_c_role_clears_c_role_level(): void
    {
        $this->actingAsAdmin();

        $roleA = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $roleB = CRole::create(['role_name' => 'Penyuluh Hukum', 'active' => true]);
        $levelA = CRoleLevel::create(['c_role_id' => $roleA->id, 'level' => 'Ahli Pertama']);
        CRoleLevel::create(['c_role_id' => $roleB->id, 'level' => 'Ahli Muda']);

        $record = MasterJf::factory()->create([
            'c_role_id' => $roleA->id,
            'c_role_level_id' => $levelA->id,
        ]);

        Livewire::test(EditMasterJf::class, ['record' => $record->getRouteKey()])
            ->assertFormSet([
                'c_role_id' => $roleA->id,
                'c_role_level_id' => $levelA->id,
            ])
            ->fillForm([
                'c_role_id' => $roleB->id,
            ])
            ->assertFormSet([
                'c_role_level_id' => null,
            ]);
    }
}
```

- [ ] **Step 2: Run tests to verify fail**

Run: `php artisan test --filter=MasterJfEditFormTest`

Expected: FAIL (form still uses `gol_ruang` / no `c_role_level_id`)

- [ ] **Step 3: Update form schema**

In `app/Filament/Resources/MasterJfResource.php` `form()`:

1. Replace the `gol_ruang` Select with:

```php
Forms\Components\Select::make('reg_grade_id')
    ->label('Golongan/Ruang')
    ->relationship('grade', 'grade_code')
    ->searchable()
    ->preload(),
```

2. Make `c_role_id` live and clear level:

```php
Forms\Components\Select::make('c_role_id')
    ->label('Jabatan Fungsional')
    ->options(fn (): array => CRole::query()
        ->where('active', true)
        ->orderBy('role_name')
        ->pluck('role_name', 'id')
        ->all())
    ->searchable()
    ->preload()
    ->live()
    ->afterStateUpdated(fn (callable $set) => $set('c_role_level_id', null)),
```

3. Add Jenjang after `c_role_id` (add `use App\Models\CRoleLevel`):

```php
Forms\Components\Select::make('c_role_level_id')
    ->label('Jenjang')
    ->options(fn (Forms\Get $get): array => CRoleLevel::query()
        ->where('c_role_id', $get('c_role_id') ?: 0)
        ->orderBy('level')
        ->pluck('level', 'id')
        ->all())
    ->searchable()
    ->disabled(fn (Forms\Get $get): bool => blank($get('c_role_id'))),
```

Keep free-text `jabatan` unchanged. Do not mark the new selects required.

- [ ] **Step 4: Run form tests**

Run: `php artisan test --filter=MasterJfEditFormTest`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Resources/MasterJfResource.php tests/Feature/Filament/MasterJfEditFormTest.php
git commit -m "$(cat <<'EOF'
feat(master-jf): bind gol ruang and jenjang to FK selects

EOF
)"
```

---

### Task 4: Table, filters, and gol_ruang widget

**Files:**
- Modify: `app/Filament/Resources/MasterJfResource.php` (table + filters)
- Modify: `app/Filament/Widgets/MasterJfNumbersByGolRuangOverview.php`
- Modify: `tests/Feature/Filament/MasterJfListStatsTest.php`
- Modify: `tests/Feature/Filament/MasterJfListFiltersTest.php`

**Interfaces:**
- Consumes: `reg_grade_id`, `c_role_level_id`, `RegGrade`, `CRoleLevel`
- Produces: list columns `grade.grade_code` + `cRoleLevel.level`; filters `reg_grade_id` + `c_role_level_id`; widget groups by `reg_grade_id` with `grade_code` labels

- [ ] **Step 1: Update failing stats/filter tests**

In `tests/Feature/Filament/MasterJfListStatsTest.php`, replace gol_ruang tests:

```php
public function test_gol_ruang_widget_shows_top_counts(): void
{
    $this->actingAsAdmin();

    $iiia = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);
    $iva = RegGrade::create(['grade_name' => 'Pembina', 'grade_code' => 'IV/a']);

    MasterJf::factory()->count(3)->create(['reg_grade_id' => $iiia->id]);
    MasterJf::factory()->count(1)->create(['reg_grade_id' => $iva->id]);

    Livewire::test(MasterJfNumbersByGolRuangOverview::class)
        ->assertSee('III/a')
        ->assertSee('3')
        ->assertSee('IV/a')
        ->assertSee('1');
}

public function test_total_widget_follows_gol_ruang_filter(): void
{
    $this->actingAsAdmin();

    $iiia = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);
    $iva = RegGrade::create(['grade_name' => 'Pembina', 'grade_code' => 'IV/a']);

    MasterJf::factory()->count(2)->create(['reg_grade_id' => $iiia->id]);
    MasterJf::factory()->count(5)->create(['reg_grade_id' => $iva->id]);

    Livewire::test(MasterJfNumbersOverview::class, [
        'tableFilters' => [
            'reg_grade_id' => ['value' => $iiia->id],
        ],
    ])
        ->assertSee('2');
}
```

Add `use App\Models\RegGrade;`

Append to `tests/Feature/Filament/MasterJfListFiltersTest.php`:

```php
public function test_reg_grade_filter_limits_visible_table_records(): void
{
    $this->actingAsAdmin();

    $iiia = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);
    $iva = RegGrade::create(['grade_name' => 'Pembina', 'grade_code' => 'IV/a']);

    $a = MasterJf::factory()->create(['nama' => 'Grade III', 'reg_grade_id' => $iiia->id]);
    $b = MasterJf::factory()->create(['nama' => 'Grade IV', 'reg_grade_id' => $iva->id]);

    Livewire::test(ListMasterJfs::class)
        ->filterTable('reg_grade_id', $iiia->id)
        ->assertCanSeeTableRecords([$a])
        ->assertCanNotSeeTableRecords([$b]);
}
```

Add `use App\Models\RegGrade;`

- [ ] **Step 2: Run tests to verify fail**

Run:

```bash
php artisan test --filter=test_gol_ruang_widget_shows_top_counts
php artisan test --filter=test_reg_grade_filter_limits_visible_table_records
```

Expected: FAIL

- [ ] **Step 3: Update table columns**

In `MasterJfResource::table()`, replace the `gol_ruang` column with:

```php
Tables\Columns\TextColumn::make('grade.grade_code')
    ->label('Golongan/Ruang')
    ->searchable()
    ->sortable(),
Tables\Columns\TextColumn::make('cRoleLevel.level')
    ->label('Jenjang')
    ->searchable()
    ->sortable(),
```

- [ ] **Step 4: Update filters**

Replace `gol_ruang` filter with:

```php
Tables\Filters\SelectFilter::make('reg_grade_id')
    ->label('Golongan/Ruang')
    ->options(fn (): array => RegGrade::query()
        ->orderBy('grade_code')
        ->pluck('grade_code', 'id')
        ->all())
    ->searchable(),
Tables\Filters\SelectFilter::make('c_role_level_id')
    ->label('Jenjang')
    ->options(fn (): array => CRoleLevel::query()
        ->orderBy('level')
        ->pluck('level', 'id')
        ->all())
    ->searchable(),
```

Add imports: `use App\Models\RegGrade;`, `use App\Models\CRoleLevel;`

- [ ] **Step 5: Update widget**

Replace body of `MasterJfNumbersByGolRuangOverview::getStats()` with:

```php
$rows = $this->getPageTableQuery()
    ->toBase()
    ->reorder()
    ->select('reg_grade_id', DB::raw('COUNT(*) as total'))
    ->groupBy('reg_grade_id')
    ->orderByDesc('total')
    ->limit(6)
    ->get();

$gradeCodes = \App\Models\RegGrade::whereIn('id', $rows->pluck('reg_grade_id')->filter()->all())
    ->pluck('grade_code', 'id');

$stats = [];

foreach ($rows as $row) {
    $label = $row->reg_grade_id
        ? ($gradeCodes[$row->reg_grade_id] ?? ('Grade #' . $row->reg_grade_id))
        : 'Tidak diketahui';
    $stats[] = Stat::make($label, number_format((int) $row->total))
        ->icon('heroicon-o-academic-cap');
}

return $stats;
```

- [ ] **Step 6: Run list/widget tests**

Run:

```bash
php artisan test --filter=MasterJfListStatsTest
php artisan test --filter=MasterJfListFiltersTest
```

Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Filament/Resources/MasterJfResource.php app/Filament/Widgets/MasterJfNumbersByGolRuangOverview.php tests/Feature/Filament/MasterJfListStatsTest.php tests/Feature/Filament/MasterJfListFiltersTest.php
git commit -m "$(cat <<'EOF'
feat(master-jf): switch list filter and grade widget to FKs

EOF
)"
```

---

### Task 5: Import resolves `reg_grade_id`

**Files:**
- Modify: `app/Imports/MasterJfImport.php`
- Modify: `tests/Feature/MasterJfModelTest.php`

**Interfaces:**
- Consumes: `RegGradeResolver::resolveId(?string $raw): ?int`
- Produces: import sets `reg_grade_id` from `golruang`; does not write `gol_ruang`; unknown → null

- [ ] **Step 1: Write failing import tests**

Append to `tests/Feature/MasterJfModelTest.php`:

```php
public function test_import_resolves_reg_grade_id_from_golruang(): void
{
    $grade = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);

    (new MasterJfImport)->model([
        'nip' => '555555555555555555',
        'nama' => 'Imported Grade',
        'golruang' => 'III/a',
        'jabatan' => 'Analis',
        'unit_kerjakanwil' => 'Unit A',
        'instansi' => 'Instansi A',
        'pengangkatan' => 'Inpassing',
        'status' => 'Aktif',
        'status_kepegawaian' => 'PNS',
    ]);

    $row = MasterJf::query()->where('nip', '555555555555555555')->first();
    $this->assertNotNull($row);
    $this->assertSame($grade->id, $row->reg_grade_id);
    $this->assertNull($row->gol_ruang);
}

public function test_import_nulls_unknown_golruang(): void
{
    (new MasterJfImport)->model([
        'nip' => '666666666666666666',
        'nama' => 'Unknown Grade',
        'golruang' => 'ZZ/z',
    ]);

    $row = MasterJf::query()->where('nip', '666666666666666666')->first();
    $this->assertNotNull($row);
    $this->assertNull($row->reg_grade_id);
}
```

Add `use App\Models\RegGrade;` if not already present.

- [ ] **Step 2: Run tests to verify fail**

Run: `php artisan test --filter=test_import_resolves_reg_grade_id_from_golruang`

Expected: FAIL (`gol_ruang` still written / `reg_grade_id` null)

- [ ] **Step 3: Update import**

In `app/Imports/MasterJfImport.php`, replace `'gol_ruang' => $row['golruang'] ?? null,` with:

```php
'reg_grade_id' => RegGradeResolver::resolveId($row['golruang'] ?? null),
```

Add `use App\Support\RegGradeResolver;`

Do not write `gol_ruang`. Leave `c_role_level_id` unset.

- [ ] **Step 4: Run import-related tests**

Run: `php artisan test --filter=MasterJfModelTest`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Imports/MasterJfImport.php tests/Feature/MasterJfModelTest.php
git commit -m "$(cat <<'EOF'
feat(master-jf): resolve import golruang to reg_grade_id

EOF
)"
```

---

### Task 6: Client matching prefers FKs

**Files:**
- Modify: `app/Services/ClientMatchingService.php`
- Modify: `tests/Feature/ClientMatchingServiceStatusTest.php`

**Interfaces:**
- Consumes: `MasterJf.reg_grade_id`, `MasterJf.c_role_id`, `MasterJf.c_role_level_id`, `RegGradeResolver::resolveId()`, legacy `gol_ruang` / `jabatan`
- Produces: `applyMasterData` assigns Client FKs preferring Master JF FKs; text fallback when IDs null

- [ ] **Step 1: Write failing matching tests**

Append to `tests/Feature/ClientMatchingServiceStatusTest.php`:

```php
public function test_apply_master_data_prefers_master_fks(): void
{
    $grade = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);
    $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
    $level = CRoleLevel::create(['c_role_id' => $role->id, 'level' => 'Ahli Pertama']);

    $master = MasterJf::factory()->create([
        'jabatan' => 'Unrelated text that will not fuzzy match',
        'reg_grade_id' => $grade->id,
        'c_role_id' => $role->id,
        'c_role_level_id' => $level->id,
    ]);

    $client = new Client;
    app(ClientMatchingService::class)->applyMasterData($client, $master);

    $this->assertSame($role->id, $client->c_role_id);
    $this->assertSame($level->id, $client->c_role_level_id);
    $this->assertSame($grade->id, $client->reg_grade_id);
}

public function test_apply_master_data_falls_back_to_gol_ruang_text(): void
{
    $grade = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);
    $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
    CRoleLevel::create(['c_role_id' => $role->id, 'level' => 'Ahli Pertama']);

    $master = MasterJf::factory()->create([
        'jabatan' => 'Analis Hukum Ahli Pertama',
        'reg_grade_id' => null,
    ]);
    \Illuminate\Support\Facades\DB::table('master_jf')->where('id', $master->id)->update([
        'gol_ruang' => 'III/a',
    ]);
    $master->refresh();

    $client = new Client;
    app(ClientMatchingService::class)->applyMasterData($client, $master);

    $this->assertSame($grade->id, $client->reg_grade_id);
}
```

Add imports: `use App\Models\RegGrade;`, `use App\Models\CRoleLevel;`

- [ ] **Step 2: Run tests to verify fail**

Run: `php artisan test --filter=test_apply_master_data_prefers_master_fks`

Expected: FAIL (current matching requires jabatan fuzzy role match before assigning FKs)

- [ ] **Step 3: Update `applyMasterData` role/grade block**

In `app/Services/ClientMatchingService.php`, replace the opening role/grade section (from `$rawJabatan = ...` through the grade `if` block) with:

```php
$rawJabatan = $master->jabatan ?? '';

$roles = once(fn () => CRole::query()->get());

$roleId = $master->c_role_id;

if (! $roleId && $master->c_role_level_id) {
    $roleId = CRoleLevel::query()->whereKey($master->c_role_level_id)->value('c_role_id');
}

if (! $roleId) {
    $role = $roles->first(function ($cRole) use ($rawJabatan) {
        return stripos($rawJabatan, $cRole->role_name) !== false;
    });
    $roleId = $role?->id;
}

if ($roleId) {
    $client->c_role_id = $roleId;

    $levelId = null;
    if ($master->c_role_level_id) {
        $level = CRoleLevel::query()->whereKey($master->c_role_level_id)->first();
        if ($level && (int) $level->c_role_id === (int) $roleId) {
            $levelId = $level->id;
        }
    }

    if (! $levelId) {
        $level = CRoleLevel::where('c_role_id', $roleId)
            ->get()
            ->first(function ($cLevel) use ($rawJabatan) {
                return stripos($rawJabatan, $cLevel->level) !== false;
            });
        $levelId = $level?->id ?? 1;
    }

    $client->c_role_level_id = $levelId;
}

if ($master->reg_grade_id) {
    $client->reg_grade_id = $master->reg_grade_id;
} else {
    $resolved = RegGradeResolver::resolveId($master->gol_ruang);
    if ($resolved) {
        $client->reg_grade_id = $resolved;
    }
}
```

Keep the remaining agency/status/pengangkatan logic that previously lived inside `if ($role)`. **Unwrap** agency/status/pengangkatan so they still run when only FKs are set (FK-only masters must still get status/agency updates).

Add `use App\Support\RegGradeResolver;`

- [ ] **Step 4: Run matching + status tests**

Run: `php artisan test --filter=ClientMatchingServiceStatusTest`

Expected: PASS (including existing status enum test)

- [ ] **Step 5: Commit**

```bash
git add app/Services/ClientMatchingService.php tests/Feature/ClientMatchingServiceStatusTest.php
git commit -m "$(cat <<'EOF'
feat(matching): prefer Master JF grade and level FKs

EOF
)"
```

---

### Task 7: Full regression sweep

**Files:**
- (none new — verification only)

- [ ] **Step 1: Run the Master JF / matching suite**

```bash
php artisan test --filter='MasterJf|RegGradeResolver|BackfillMasterJf|ClientMatchingService'
```

Expected: PASS

- [ ] **Step 2: Fix any leftover `gol_ruang` factory/filter references**

Search:

```bash
rg "gol_ruang" app/Filament tests/Feature/Filament database/factories
```

Update any remaining Filament/test references that still assume text `gol_ruang` for list/widget behavior. Legacy column reads in matching/backfill tests via `DB::table` are fine.

- [ ] **Step 3: Final commit only if Step 2 produced fixes**

```bash
git add -A
git status
# commit only if there are intentional leftover fixes
git commit -m "$(cat <<'EOF'
fix(master-jf): finish gol_ruang FK migration leftovers

EOF
)"
```

---

## Spec coverage checklist

| Spec requirement | Task |
| --- | --- |
| Ensure FK columns + FKs if missing | Task 1 |
| Model fillable + `grade` / `cRoleLevel` | Task 1 |
| Remove `gol_ruang` from fillable | Task 1 |
| Backfill `reg_grade_id` only | Task 2 |
| Form `reg_grade_id` + Jenjang + clear on role change | Task 3 |
| Table columns | Task 4 |
| Filters | Task 4 |
| Widget by `reg_grade_id` | Task 4 |
| Import resolve, no `gol_ruang` write | Task 5 |
| Matching prefers FKs + text fallback | Task 6 |
| No level backfill / no column drop | Tasks 2, 7 (explicit non-goals) |
