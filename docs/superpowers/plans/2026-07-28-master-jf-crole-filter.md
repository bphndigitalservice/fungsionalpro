# Master JF CRole Filter Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add nullable `c_role_id` on Master JF, optional form assignment, table column, and a Jabatan Fungsional (`CRole`) list filter while keeping free-text `jabatan` filtering.

**Architecture:** Store a nullable FK `master_jf.c_role_id` → `c_roles.id` (`nullOnDelete`). Expose `MasterJf::cRole()`, an optional Filament Select on the resource form, a `cRole.role_name` table column, and a single searchable `SelectFilter` on `c_role_id`. Import stays unchanged (never writes `c_role_id`). Existing header widgets keep following the table query via `InteractsWithPageTable`.

**Tech Stack:** Laravel 12, Filament v3.2 (`Select`, `SelectFilter`, `TextColumn`), Livewire 3, PHPUnit 11, Spatie Permission (admin role in Filament tests)

## Global Constraints

- Spec: `docs/superpowers/specs/2026-07-28-master-jf-crole-filter-design.md`
- Assignment is **manual only** via the Master JF form — no import auto-match, no backfill
- Keep free-text `jabatan` column, search, and filter as they are today
- CRole filter is **single-select** (not multi), searchable, labeled “Jabatan Fungsional”
- Do **not** add bulk assign, null-only filter, or a CRole stats widget
- Do **not** change `ClientMatchingService` or registration jabatan heuristics
- Conventional commits; commit after each task

---

## File structure

| File | Responsibility |
| --- | --- |
| `database/migrations/2026_07_28_000001_add_c_role_id_to_master_jf_table.php` | Nullable FK `c_role_id` → `c_roles.id`, `nullOnDelete` |
| `app/Models/MasterJf.php` | `$fillable` + `cRole()` relationship |
| `database/factories/MasterJfFactory.php` | Optional `c_role_id` (default unset/`null`) |
| `app/Filament/Resources/MasterJfResource.php` | Form Select, table column, `SelectFilter` |
| `app/Imports/MasterJfImport.php` | **No changes** — must not write `c_role_id` |
| `tests/Feature/MasterJfModelTest.php` | Persist `c_role_id` + relation; import preserves FK |
| `tests/Feature/Filament/MasterJfListFiltersTest.php` | CRole list filter coverage |

---

### Task 1: Migration, model relation, import preservation

**Files:**
- Create: `database/migrations/2026_07_28_000001_add_c_role_id_to_master_jf_table.php`
- Modify: `app/Models/MasterJf.php`
- Modify: `database/factories/MasterJfFactory.php` (only if needed for default null)
- Test: `tests/Feature/MasterJfModelTest.php`
- Do not modify: `app/Imports/MasterJfImport.php`

**Interfaces:**
- Consumes: `c_roles` table (`id`, `role_name`, `active`); existing `MasterJf` / `MasterJfImport`
- Produces:
  - `master_jf.c_role_id` nullable unsignedBigInteger FK → `c_roles.id` with `nullOnDelete()`
  - `MasterJf::$fillable` includes `'c_role_id'`
  - `MasterJf::cRole(): BelongsTo` → `belongsTo(CRole::class)`
  - Import path still omits `c_role_id` so re-import does not clear an assigned role

- [ ] **Step 1: Write the failing model tests**

Append to `tests/Feature/MasterJfModelTest.php`:

```php
use App\Imports\MasterJfImport;
use App\Models\CRole;

public function test_it_persists_c_role_id_and_loads_relation(): void
{
    $role = CRole::create([
        'role_name' => 'Analis Hukum',
        'active' => true,
    ]);

    $row = MasterJf::factory()->create([
        'c_role_id' => $role->id,
    ]);

    $this->assertDatabaseHas('master_jf', [
        'id' => $row->id,
        'c_role_id' => $role->id,
    ]);

    $this->assertTrue($row->fresh()->cRole->is($role));
}

public function test_import_does_not_clear_existing_c_role_id(): void
{
    $role = CRole::create([
        'role_name' => 'Analis Hukum',
        'active' => true,
    ]);

    $row = MasterJf::factory()->create([
        'nip' => '123456789012345678',
        'c_role_id' => $role->id,
        'nama' => 'Before Import',
    ]);

    (new MasterJfImport)->model([
        'nip' => '123456789012345678',
        'nama' => 'After Import',
        'golruang' => 'III/a',
        'jabatan' => 'Analis Hukum Ahli Pertama',
        'unit_kerjakanwil' => 'Unit A',
        'instansi' => 'Instansi A',
        'pengangkatan' => 'Inpassing',
        'status' => 'Aktif',
        'status_kepegawaian' => 'PNS',
    ]);

    $row->refresh();

    $this->assertSame('After Import', $row->nama);
    $this->assertSame($role->id, $row->c_role_id);
}
```

Keep existing tests in that file.

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=MasterJfModelTest`

Expected: FAIL (unknown column `c_role_id` and/or missing `cRole` / fillable)

- [ ] **Step 3: Create the migration**

Create `database/migrations/2026_07_28_000001_add_c_role_id_to_master_jf_table.php`:

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
            $table->foreignId('c_role_id')
                ->nullable()
                ->after('jabatan')
                ->constrained('c_roles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('master_jf', function (Blueprint $table) {
            $table->dropConstrainedForeignId('c_role_id');
        });
    }
};
```

- [ ] **Step 4: Update the model**

In `app/Models/MasterJf.php`:

1. Add `'c_role_id'` to `$fillable` (after `'jabatan'` is fine).
2. Add imports and relationship:

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;

public function cRole(): BelongsTo
{
    return $this->belongsTo(CRole::class);
}
```

Add `use App\Models\CRole;` only if needed for type hints; `belongsTo(CRole::class)` needs `use App\Models\CRole;` or the FQCN. Prefer:

```php
use App\Models\CRole;
```

at the top of the file (same namespace already — `CRole` is `App\Models\CRole`, so no import is required if referenced as `CRole::class` from `App\Models\MasterJf`). Use `CRole::class` without an extra import.

Do **not** change `MasterJfImport`.

Factory: leave definition without `c_role_id` (defaults to null / unset). Tests pass explicit `c_role_id` when needed.

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter=MasterJfModelTest`

Expected: PASS (all methods in that class)

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_07_28_000001_add_c_role_id_to_master_jf_table.php \
  app/Models/MasterJf.php \
  tests/Feature/MasterJfModelTest.php
git commit -m "$(cat <<'EOF'
feat(master-jf): add nullable c_role_id FK and relation

EOF
)"
```

---

### Task 2: Form Select, table column, and list filter

**Files:**
- Modify: `app/Filament/Resources/MasterJfResource.php`
- Test: `tests/Feature/Filament/MasterJfListFiltersTest.php`

**Interfaces:**
- Consumes: `MasterJf::cRole()`, `c_role_id` column from Task 1; `CRole` rows for options
- Produces:
  - Form: `Select::make('c_role_id')` labeled “Jabatan Fungsional”, optional, searchable, preload
  - Column: `TextColumn::make('cRole.role_name')` labeled “Jabatan Fungsional”, near `jabatan`
  - Filter: `SelectFilter::make('c_role_id')` labeled “Jabatan Fungsional”, single-select, searchable
  - Existing `jabatan` filter unchanged

- [ ] **Step 1: Write the failing filter test**

Append to `tests/Feature/Filament/MasterJfListFiltersTest.php`:

```php
use App\Models\CRole;

public function test_c_role_filter_limits_visible_table_records(): void
{
    $this->actingAsAdmin();

    $analis = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
    $penyuluh = CRole::create(['role_name' => 'Penyuluh Hukum', 'active' => true]);

    $a = MasterJf::factory()->create([
        'nama' => 'Analis Person',
        'c_role_id' => $analis->id,
    ]);
    $b = MasterJf::factory()->create([
        'nama' => 'Penyuluh Person',
        'c_role_id' => $penyuluh->id,
    ]);

    Livewire::test(ListMasterJfs::class)
        ->assertCanSeeTableRecords([$a, $b])
        ->filterTable('c_role_id', $analis->id)
        ->assertCanSeeTableRecords([$a])
        ->assertCanNotSeeTableRecords([$b]);
}
```

Keep existing filter tests.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_c_role_filter_limits_visible_table_records`

Expected: FAIL (filter `c_role_id` not defined on the table)

- [ ] **Step 3: Implement form, column, and filter**

In `app/Filament/Resources/MasterJfResource.php`:

1. Ensure `use App\Models\CRole;` is present.

2. In `form()` schema, after the free-text `jabatan` field, add:

```php
Forms\Components\Select::make('c_role_id')
    ->label('Jabatan Fungsional')
    ->options(fn (): array => CRole::query()->pluck('role_name', 'id')->all())
    ->searchable()
    ->preload(),
```

3. In `table()` columns, after the `jabatan` column, add:

```php
Tables\Columns\TextColumn::make('cRole.role_name')
    ->label('Jabatan Fungsional')
    ->searchable(),
```

4. In `table()` filters, add (order: after `jabatan` filter is fine; do not remove `jabatan`):

```php
Tables\Filters\SelectFilter::make('c_role_id')
    ->label('Jabatan Fungsional')
    ->options(fn (): array => CRole::query()->pluck('role_name', 'id')->all())
    ->searchable(),
```

Do not call `->multiple()` on this filter.

- [ ] **Step 4: Run filter tests to verify they pass**

Run: `php artisan test --filter=MasterJfListFiltersTest`

Expected: PASS (status, instansi, and new c_role filter tests)

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Resources/MasterJfResource.php \
  tests/Feature/Filament/MasterJfListFiltersTest.php
git commit -m "$(cat <<'EOF'
feat(master-jf): add CRole form field, column, and list filter

EOF
)"
```

---

## Spec coverage checklist

| Spec requirement | Task |
| --- | --- |
| Nullable `c_role_id` FK + `nullOnDelete` | Task 1 |
| `$fillable` + `cRole()` | Task 1 |
| Import unchanged / does not clear FK | Task 1 test |
| Optional form Select | Task 2 |
| Table column `cRole.role_name` | Task 2 |
| Single searchable `SelectFilter` | Task 2 |
| Keep free-text `jabatan` filter | Task 2 (explicit no-change) |
| Widgets follow filters | No code — existing `InteractsWithPageTable` |
| No bulk assign / null filter / CRole widget | Out of scope — no tasks |

## Plan self-review

- No placeholders or “similar to Task N” shortcuts.
- `cRole` / `c_role_id` naming consistent across migration, model, Filament, and tests.
- Import regression covered without modifying `MasterJfImport`.
