# Master JF Agency Morph Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Persist a Client-style nullable agency morph on `master_jf` and use it for matching, Filament, and aggregate instansi grouping.

**Architecture:** Add `agency_type` / `agency_id` on `master_jf` with `MasterJf::agenciable()`. Resolve names in `MasterJfAgencyResolver` (reuses `ClientMatchingService` lookup). Backfill and import write the morph when names match; Filament maps cluster + `agency_id` like Client (no echelon). Matching copies a linked morph onto the client; the aggregate API groups by linked agency name, else `instansi` text.

**Tech Stack:** Laravel 12, Eloquent morphs, Filament v3.2, Livewire 3, PHPUnit 11, SQLite `:memory:` tests / PostgreSQL-compatible migrations

## Global Constraints

- Spec: `docs/superpowers/specs/2026-08-18-master-jf-agency-polymorph-design.md`
- Agency morph only — no echelon columns or form fields
- Morph is nullable; `agency_type` and `agency_id` are both null or both set
- Keep `instansi`, `unit_kerja`, `type`, `province_id`, `provinsi` — do not drop them
- Never insert missing departments, provinces, or regencies from Master JF text
- Public instansi JSON stays `{ name, client_count }` — no new `agency_id` field
- Do not extract a shared Agencyable trait or refactor Client forms
- Conventional commits; commit after each task; commit-msg body lines ≤ 100 chars
- PHPUnit: `php artisan test --filter=...` (this repo does not use Pest for these tests)

---

## File structure

| File | Responsibility |
| --- | --- |
| `database/migrations/2026_08_18_000001_add_agency_morph_to_master_jf_table.php` | Nullable `agency_type` / `agency_id` + index |
| `database/migrations/2026_08_18_000002_backfill_master_jf_agency_morph.php` | One-shot backfill via resolver |
| `app/Support/MasterJfAgencyResolver.php` | Resolve text → morph + derived `type` / `province_id` |
| `app/Support/MasterJfAgencyForm.php` | Filament mutate: cluster + `agency_id` → morph |
| `app/Models/MasterJf.php` | Fillable + `agenciable()` |
| `app/Models/RegDepartment.php` | `masterJfs()` morphMany |
| `app/Models/RegProvince.php` | `masterJfs()` morphMany |
| `app/Models/RegRegency.php` | `masterJfs()` morphMany |
| `database/factories/MasterJfFactory.php` | Morph defaults null |
| `app/Services/ClientMatchingService.php` | Extract `findAgency()`; copy morph in `applyMasterData` |
| `app/Imports/MasterJfImport.php` | Write morph on hit; keep previous morph on miss+existing NIP |
| `app/Services/MasterJfAggregateService.php` | Group instansi by morph, else text |
| `app/Filament/Resources/MasterJfResource.php` | Cluster + agency select; table fallback name |
| `app/Filament/Resources/MasterJfResource/Pages/EditMasterJf.php` | Call `MasterJfAgencyForm::mutate` |
| `app/Filament/Resources/MasterJfResource/Pages/CreateMasterJf.php` | Same mutate (page exists; not in `getPages`) |
| `tests/Concerns/EnsuresMasterJfApiSchema.php` | Add morph columns when missing (SQLite) |
| `tests/Feature/MasterJfModelTest.php` | Relation + import morph tests |
| `tests/Unit/Support/MasterJfAgencyResolverTest.php` | Resolver unit tests |
| `tests/Unit/Support/MasterJfAgencyFormTest.php` | Form mutate unit tests |
| `tests/Feature/Migrations/BackfillMasterJfAgencyMorphTest.php` | Backfill behavior |
| `tests/Feature/ClientMatchingServiceStatusTest.php` | Copy morph vs fuzzy fallback |
| `tests/Feature/Services/MasterJfAggregateServiceTest.php` | Instansi grouping by morph |
| `tests/Feature/Filament/MasterJfEditFormTest.php` | Save / clear morph |

---

### Task 1: Schema, model, inverses, factory, SQLite trait

**Files:**
- Create: `database/migrations/2026_08_18_000001_add_agency_morph_to_master_jf_table.php`
- Modify: `app/Models/MasterJf.php`
- Modify: `app/Models/RegDepartment.php`
- Modify: `app/Models/RegProvince.php`
- Modify: `app/Models/RegRegency.php`
- Modify: `database/factories/MasterJfFactory.php`
- Modify: `tests/Concerns/EnsuresMasterJfApiSchema.php`
- Test: `tests/Feature/MasterJfModelTest.php`

**Interfaces:**
- Consumes: existing `master_jf`, `reg_departments`, `reg_provinces`, `reg_regencies`
- Produces:
  - Nullable columns `master_jf.agency_type`, `master_jf.agency_id` with index on both
  - `MasterJf::agenciable(): MorphTo` using `agency_type` / `agency_id`
  - `RegDepartment::masterJfs()`, `RegProvince::masterJfs()`, `RegRegency::masterJfs(): MorphMany`
  - Fillable includes `agency_type`, `agency_id`; factory leaves them unset (null)

- [ ] **Step 1: Write the failing model relation test**

Append to `tests/Feature/MasterJfModelTest.php` (add imports for `RegDepartment`, `RegProvince`, `RegRegency`):

```php
public function test_it_resolves_agenciable_for_department_province_and_regency(): void
{
    $department = RegDepartment::create(['name' => 'Kementerian Hukum']);
    $province = RegProvince::query()->create(['id' => 51, 'name' => 'BALI']);
    $regency = RegRegency::query()->create([
        'id' => 5103,
        'province_id' => 51,
        'name' => 'KABUPATEN BADUNG',
    ]);

    $byDept = MasterJf::factory()->create([
        'agency_type' => RegDepartment::class,
        'agency_id' => $department->id,
    ]);
    $byProv = MasterJf::factory()->create([
        'agency_type' => RegProvince::class,
        'agency_id' => $province->id,
    ]);
    $byReg = MasterJf::factory()->create([
        'agency_type' => RegRegency::class,
        'agency_id' => $regency->id,
    ]);

    $this->assertTrue($byDept->fresh()->agenciable->is($department));
    $this->assertTrue($byProv->fresh()->agenciable->is($province));
    $this->assertTrue($byReg->fresh()->agenciable->is($regency));
    $this->assertTrue($department->masterJfs->contains($byDept));
    $this->assertTrue($province->masterJfs->contains($byProv));
    $this->assertTrue($regency->masterJfs->contains($byReg));
}
```

If SQLite `reg_provinces` / `reg_regencies` are missing columns, create them in this test’s `setUp` the same way `MasterJfModelTest` already creates `reg_provinces`. `reg_departments` and `reg_regencies` come from normal migrations under `RefreshDatabase`.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_it_resolves_agenciable_for_department_province_and_regency`

Expected: FAIL (unknown attribute `agency_type` / missing `agenciable` method)

- [ ] **Step 3: Add migration**

Create `database/migrations/2026_08_18_000001_add_agency_morph_to_master_jf_table.php`:

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
            if (! Schema::hasColumn('master_jf', 'agency_type')) {
                $table->nullableMorphs('agency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('master_jf', function (Blueprint $table) {
            if (Schema::hasColumn('master_jf', 'agency_type')) {
                $table->dropMorphs('agency');
            }
        });
    }
};
```

If `nullableMorphs` fails because one of the two columns already exists in a restored DB, guard each column and add `$table->index(['agency_type', 'agency_id']);` only when the index is missing. Prefer the `hasColumn` guard above for greenfield.

- [ ] **Step 4: Model, inverses, factory, SQLite trait**

`app/Models/MasterJf.php`:
- Add `'agency_type', 'agency_id'` to `$fillable`
- Import `MorphTo`
- Add:

```php
public function agenciable(): MorphTo
{
    return $this->morphTo(__FUNCTION__, 'agency_type', 'agency_id');
}
```

On `RegDepartment`, `RegProvince`, `RegRegency`, import `MorphMany` and add:

```php
public function masterJfs(): MorphMany
{
    return $this->morphMany(MasterJf::class, 'agency');
}
```

Do **not** change existing `agency(): MorphOne` (Client).

`database/factories/MasterJfFactory.php`: do not set `agency_type` / `agency_id` (null by default).

`tests/Concerns/EnsuresMasterJfApiSchema.php` — inside `ensureMasterJfApiSchema()`, after the `province_id` block:

```php
if (Schema::hasTable('master_jf') && ! Schema::hasColumn('master_jf', 'agency_id')) {
    Schema::table('master_jf', function (Blueprint $table) {
        $table->nullableMorphs('agency');
    });
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=test_it_resolves_agenciable_for_department_province_and_regency`

Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_08_18_000001_add_agency_morph_to_master_jf_table.php \
  app/Models/MasterJf.php app/Models/RegDepartment.php app/Models/RegProvince.php \
  app/Models/RegRegency.php database/factories/MasterJfFactory.php \
  tests/Concerns/EnsuresMasterJfApiSchema.php tests/Feature/MasterJfModelTest.php
git commit -m "$(cat <<'EOF'
feat(master-jf): add nullable agency morph columns and relations

EOF
)"
```

---

### Task 2: `MasterJfAgencyResolver` and `ClientMatchingService::findAgency`

**Files:**
- Create: `app/Support/MasterJfAgencyResolver.php`
- Modify: `app/Services/ClientMatchingService.php`
- Test: `tests/Unit/Support/MasterJfAgencyResolverTest.php`

**Interfaces:**
- Consumes: `ClientMatchingService::determineAgencyInfo`, `cleanAgencyName`
- Produces:
  - `ClientMatchingService::findAgency(string $modelClass, string $instansi, string $unitKerja): ?Model`
  - `MasterJfAgencyResolver::resolve(?string $instansi, ?string $unitKerja): ?array`
  - Resolve array shape on hit:
    - `agency_type`: class-string (`RegDepartment|RegProvince|RegRegency`)
    - `agency_id`: int
    - `type`: `ClientCluster`
    - `province_id`: int, **only** present for province/regency hits
  - `null` on miss (empty names, or lookup finds nothing)
  - Does not create registry rows
  - Department hit does **not** include `province_id` (caller must not overwrite)

- [ ] **Step 1: Write failing resolver tests**

Create `tests/Unit/Support/MasterJfAgencyResolverTest.php`:

```php
<?php

namespace Tests\Unit\Support;

use App\Enums\ClientCluster;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;
use App\Support\MasterJfAgencyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterJfAgencyResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_department_by_exact_instansi_name(): void
    {
        $department = RegDepartment::create(['name' => 'Kementerian Hukum']);

        $resolved = MasterJfAgencyResolver::resolve('Kementerian Hukum', '');

        $this->assertNotNull($resolved);
        $this->assertSame(RegDepartment::class, $resolved['agency_type']);
        $this->assertSame($department->id, $resolved['agency_id']);
        $this->assertSame(ClientCluster::Central, $resolved['type']);
        $this->assertArrayNotHasKey('province_id', $resolved);
    }

    public function test_it_resolves_province_and_sets_province_id(): void
    {
        $province = RegProvince::query()->create(['id' => 51, 'name' => 'BALI']);

        $resolved = MasterJfAgencyResolver::resolve('Pemerintah Daerah Provinsi Bali', 'Provinsi Bali');

        $this->assertNotNull($resolved);
        $this->assertSame(RegProvince::class, $resolved['agency_type']);
        $this->assertSame($province->id, $resolved['agency_id']);
        $this->assertSame(ClientCluster::LocalProvince, $resolved['type']);
        $this->assertSame(51, $resolved['province_id']);
    }

    public function test_it_resolves_regency_province_id_from_parent(): void
    {
        RegProvince::query()->create(['id' => 51, 'name' => 'BALI']);
        $regency = RegRegency::query()->create([
            'id' => 5103,
            'province_id' => 51,
            'name' => 'KABUPATEN BADUNG',
        ]);

        $resolved = MasterJfAgencyResolver::resolve(
            'Pemerintah Daerah Kabupaten Badung',
            'Kabupaten Badung',
        );

        $this->assertNotNull($resolved);
        $this->assertSame(RegRegency::class, $resolved['agency_type']);
        $this->assertSame($regency->id, $resolved['agency_id']);
        $this->assertSame(ClientCluster::LocalRegency, $resolved['type']);
        $this->assertSame(51, $resolved['province_id']);
    }

    public function test_it_returns_null_when_no_agency_row_matches(): void
    {
        $this->assertNull(MasterJfAgencyResolver::resolve('Instansi Yang Tidak Ada', 'Unit X'));
    }

    public function test_it_returns_null_when_both_names_empty(): void
    {
        $this->assertNull(MasterJfAgencyResolver::resolve(null, null));
        $this->assertNull(MasterJfAgencyResolver::resolve('', ''));
    }
}
```

If province/regency name matching fails because `cleanAgencyName` + `determineAgencyInfo` need a specific spelling, adjust the test strings to names that exist on the models (`BALI`, `KABUPATEN BADUNG`) so exact `name` match after clean succeeds. Keep the assertions.

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=MasterJfAgencyResolverTest`

Expected: FAIL (`MasterJfAgencyResolver` not found)

- [ ] **Step 3: Extract `findAgency` and implement resolver**

In `app/Services/ClientMatchingService.php`, add:

```php
public static function findAgency(string $modelClass, string $instansi, string $unitKerja): ?\Illuminate\Database\Eloquent\Model
{
    $cleanUnitKerja = self::cleanAgencyName($unitKerja);
    $cleanInstansi = self::cleanAgencyName($instansi);

    $agency = $modelClass::where('name', '=', $cleanUnitKerja)->first();
    if (! $agency && $cleanInstansi !== '') {
        $agency = $modelClass::where('name', '=', $cleanInstansi)->first();
    }
    if (! $agency && $cleanUnitKerja !== '') {
        $agency = $modelClass::where('name', 'LIKE', '%'.$cleanUnitKerja.'%')->first();
    }
    if (! $agency && $cleanInstansi !== '') {
        $agency = $modelClass::where('name', 'LIKE', '%'.$cleanInstansi.'%')->first();
    }

    return $agency;
}
```

Replace the inline lookup inside `applyMasterData` with `self::findAgency($agencyModel, $rawInstansi, $rawUnitKerja)` (behavior unchanged).

Create `app/Support/MasterJfAgencyResolver.php`:

```php
<?php

namespace App\Support;

use App\Enums\ClientCluster;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;
use App\Services\ClientMatchingService;

final class MasterJfAgencyResolver
{
    /**
     * @return array{
     *     agency_type: class-string,
     *     agency_id: int,
     *     type: ClientCluster,
     *     province_id?: int
     * }|null
     */
    public static function resolve(?string $instansi, ?string $unitKerja): ?array
    {
        $instansi = trim((string) $instansi);
        $unitKerja = trim((string) $unitKerja);

        if ($instansi === '' && $unitKerja === '') {
            return null;
        }

        [, $modelClass] = ClientMatchingService::determineAgencyInfo($instansi, $unitKerja);
        $agency = ClientMatchingService::findAgency($modelClass, $instansi, $unitKerja);

        if ($agency === null) {
            return null;
        }

        $type = match ($modelClass) {
            RegDepartment::class => ClientCluster::Central,
            RegProvince::class => ClientCluster::LocalProvince,
            RegRegency::class => ClientCluster::LocalRegency,
            default => null,
        };

        if ($type === null) {
            return null;
        }

        $payload = [
            'agency_type' => $modelClass,
            'agency_id' => (int) $agency->id,
            'type' => $type,
        ];

        if ($agency instanceof RegProvince) {
            $payload['province_id'] = (int) $agency->id;
        }

        if ($agency instanceof RegRegency) {
            $payload['province_id'] = (int) $agency->province_id;
        }

        return $payload;
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=MasterJfAgencyResolverTest`

Expected: PASS

Also run: `php artisan test --filter=ClientMatchingServiceStatusTest`

Expected: PASS (lookup extraction must not change matching)

- [ ] **Step 5: Commit**

```bash
git add app/Support/MasterJfAgencyResolver.php app/Services/ClientMatchingService.php \
  tests/Unit/Support/MasterJfAgencyResolverTest.php
git commit -m "$(cat <<'EOF'
feat(master-jf): resolve agency morph from instansi text

EOF
)"
```

---

### Task 3: Backfill migration

**Files:**
- Create: `database/migrations/2026_08_18_000002_backfill_master_jf_agency_morph.php`
- Modify: `app/Support/MasterJfAgencyResolver.php` (add `backfillMasterJf()`)
- Test: `tests/Feature/Migrations/BackfillMasterJfAgencyMorphTest.php`

**Interfaces:**
- Consumes: `MasterJfAgencyResolver::resolve`
- Produces: `MasterJfAgencyResolver::backfillMasterJf(): void`
  - Updates only rows with `agency_id` IS NULL
  - On hit: writes `agency_type`, `agency_id`, `type`; writes `province_id` only when key present
  - On miss: no change to morph or `instansi`
  - Idempotent
  - Does not overwrite `instansi`

- [ ] **Step 1: Write failing backfill test**

Create `tests/Feature/Migrations/BackfillMasterJfAgencyMorphTest.php`:

```php
<?php

namespace Tests\Feature\Migrations;

use App\Enums\ClientCluster;
use App\Models\MasterJf;
use App\Models\RegDepartment;
use App\Support\MasterJfAgencyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillMasterJfAgencyMorphTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_sets_morph_on_match_and_skips_miss_and_linked(): void
    {
        $department = RegDepartment::create(['name' => 'Kementerian Hukum']);
        $other = RegDepartment::create(['name' => 'Kementerian Agama']);

        $matched = MasterJf::factory()->create([
            'instansi' => 'Kementerian Hukum',
            'unit_kerja' => '',
            'agency_type' => null,
            'agency_id' => null,
            'type' => null,
        ]);
        $missed = MasterJf::factory()->create([
            'instansi' => 'Tidak Ada Di Master',
            'unit_kerja' => 'X',
            'agency_type' => null,
            'agency_id' => null,
        ]);
        $already = MasterJf::factory()->create([
            'instansi' => 'wrong spelling should not overwrite',
            'agency_type' => RegDepartment::class,
            'agency_id' => $other->id,
            'type' => ClientCluster::Central,
        ]);

        MasterJfAgencyResolver::backfillMasterJf();
        MasterJfAgencyResolver::backfillMasterJf();

        $this->assertDatabaseHas('master_jf', [
            'id' => $matched->id,
            'agency_type' => RegDepartment::class,
            'agency_id' => $department->id,
            'type' => 'central',
            'instansi' => 'Kementerian Hukum',
        ]);
        $this->assertDatabaseHas('master_jf', [
            'id' => $missed->id,
            'agency_type' => null,
            'agency_id' => null,
            'instansi' => 'Tidak Ada Di Master',
        ]);
        $this->assertDatabaseHas('master_jf', [
            'id' => $already->id,
            'agency_id' => $other->id,
            'instansi' => 'wrong spelling should not overwrite',
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_backfill_sets_morph_on_match_and_skips_miss_and_linked`

Expected: FAIL (`backfillMasterJf` undefined)

- [ ] **Step 3: Implement backfill**

Add to `MasterJfAgencyResolver`:

```php
public static function backfillMasterJf(): void
{
    \App\Models\MasterJf::query()
        ->whereNull('agency_id')
        ->orderBy('id')
        ->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                $resolved = self::resolve($row->instansi, $row->unit_kerja);
                if ($resolved === null) {
                    continue;
                }

                $updates = [
                    'agency_type' => $resolved['agency_type'],
                    'agency_id' => $resolved['agency_id'],
                    'type' => $resolved['type']->value,
                ];
                if (array_key_exists('province_id', $resolved)) {
                    $updates['province_id'] = $resolved['province_id'];
                }

                \Illuminate\Support\Facades\DB::table('master_jf')
                    ->where('id', $row->id)
                    ->update($updates);
            }
        });
}
```

Create `database/migrations/2026_08_18_000002_backfill_master_jf_agency_morph.php`:

```php
<?php

use App\Support\MasterJfAgencyResolver;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        MasterJfAgencyResolver::backfillMasterJf();
    }

    public function down(): void
    {
        // Irreversible data backfill
    }
};
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=BackfillMasterJfAgencyMorphTest`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Support/MasterJfAgencyResolver.php \
  database/migrations/2026_08_18_000002_backfill_master_jf_agency_morph.php \
  tests/Feature/Migrations/BackfillMasterJfAgencyMorphTest.php
git commit -m "$(cat <<'EOF'
feat(master-jf): backfill agency morph from instansi text

EOF
)"
```

---

### Task 4: Import writes morph and preserves manual links

**Files:**
- Modify: `app/Imports/MasterJfImport.php`
- Test: `tests/Feature/MasterJfModelTest.php`

**Interfaces:**
- Consumes: `MasterJfAgencyResolver::resolve`
- Produces: import `updateOrCreate` payload includes morph on hit; on miss for existing NIP, omit morph keys so previous `agency_type`/`agency_id` remain; still writes `instansi` text; still sets `type` from `determineAgencyInfo`

- [ ] **Step 1: Write failing import tests**

Append to `tests/Feature/MasterJfModelTest.php`:

```php
public function test_import_writes_agency_morph_when_instansi_matches(): void
{
    $department = RegDepartment::create(['name' => 'Kementerian Hukum']);

    (new MasterJfImport)->model([
        'nip' => '777777777777777777',
        'nama' => 'Imported Morph',
        'instansi' => 'Kementerian Hukum',
        'unit_kerjakanwil' => '',
        'status' => 'Aktif',
        'status_kepegawaian' => 'PNS',
    ]);

    $this->assertDatabaseHas('master_jf', [
        'nip' => '777777777777777777',
        'instansi' => 'Kementerian Hukum',
        'agency_type' => RegDepartment::class,
        'agency_id' => $department->id,
        'type' => 'central',
    ]);
}

public function test_import_keeps_existing_morph_when_names_do_not_resolve(): void
{
    $department = RegDepartment::create(['name' => 'Kementerian Hukum']);

    $row = MasterJf::factory()->create([
        'nip' => '888888888888888888',
        'agency_type' => RegDepartment::class,
        'agency_id' => $department->id,
        'type' => ClientCluster::Central,
    ]);

    (new MasterJfImport)->model([
        'nip' => '888888888888888888',
        'nama' => 'After Import',
        'instansi' => 'Nama Yang Tidak Ada',
        'unit_kerjakanwil' => 'Unit X',
        'status' => 'Aktif',
        'status_kepegawaian' => 'PNS',
    ]);

    $row->refresh();
    $this->assertSame('After Import', $row->nama);
    $this->assertSame('Nama Yang Tidak Ada', $row->instansi);
    $this->assertSame(RegDepartment::class, $row->agency_type);
    $this->assertSame($department->id, $row->agency_id);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=test_import_writes_agency_morph`

Expected: FAIL (`agency_id` null)

- [ ] **Step 3: Update import**

In `app/Imports/MasterJfImport.php` `model()`, after computing `$instansi` / `$unitKerja` / `$type`, build the update array as today, then:

```php
$resolved = \App\Support\MasterJfAgencyResolver::resolve($instansi, $unitKerja);

$values = [
    'nama'               => $row['nama'] ?? null,
    'reg_grade_id'       => RegGradeResolver::resolveId($row['golruang'] ?? null),
    'jabatan'            => $jabatan,
    'c_role_id'          => $cRoleId,
    'unit_kerja'         => $unitKerja,
    'instansi'           => $instansi,
    'pengangkatan'       => $row['pengangkatan'] ?? null,
    'status'             => MasterJfEnumMapper::status($row['status'] ?? null),
    'status_kepegawaian' => MasterJfEnumMapper::statusKepegawaian($row['status_kepegawaian'] ?? null),
    'type'               => $type,
    'provinsi'           => $row['provinsi'] ?? null,
    'divisi'             => $row['divisi'] ?? null,
];

if ($resolved !== null) {
    $values['agency_type'] = $resolved['agency_type'];
    $values['agency_id'] = $resolved['agency_id'];
    $values['type'] = $resolved['type']->value;
    if (array_key_exists('province_id', $resolved)) {
        $values['province_id'] = $resolved['province_id'];
    }
}

return MasterJf::updateOrCreate(
    ['nip' => $row['nip']],
    $values,
);
```

Do **not** set `agency_type`/`agency_id` to null on miss. `updateOrCreate` then leaves existing morph in place.

Keep the existing NIP-empty exception unchanged.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=MasterJfModelTest`

Expected: PASS (including existing import tests)

- [ ] **Step 5: Commit**

```bash
git add app/Imports/MasterJfImport.php tests/Feature/MasterJfModelTest.php
git commit -m "$(cat <<'EOF'
feat(master-jf): persist agency morph on import without wiping links

EOF
)"
```

---

### Task 5: Matching copies linked morph

**Files:**
- Modify: `app/Services/ClientMatchingService.php`
- Test: `tests/Feature/ClientMatchingServiceStatusTest.php`

**Interfaces:**
- Consumes: `MasterJf.agency_type`, `agency_id`, `type`
- Produces: `applyMasterData` copies morph + `type` when both morph columns set; otherwise existing `determineAgencyInfo` + `findAgency` path; does not set client echelon from Master JF

- [ ] **Step 1: Write failing matching tests**

Append to `tests/Feature/ClientMatchingServiceStatusTest.php` (import `RegDepartment`, `ClientCluster`):

```php
public function test_apply_master_data_copies_linked_agency_morph(): void
{
    CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
    $department = RegDepartment::create(['name' => 'Kementerian Hukum']);

    $master = MasterJf::factory()->create([
        'jabatan' => 'Analis Hukum Ahli Pertama',
        'instansi' => 'spelling that would not match',
        'unit_kerja' => 'nope',
        'agency_type' => RegDepartment::class,
        'agency_id' => $department->id,
        'type' => ClientCluster::Central,
    ]);

    $client = new Client;
    app(ClientMatchingService::class)->applyMasterData($client, $master);

    $this->assertSame(ClientCluster::Central, $client->type);
    $this->assertSame(RegDepartment::class, $client->agency_type);
    $this->assertSame($department->id, $client->agency_id);
}

public function test_apply_master_data_uses_fuzzy_lookup_when_morph_missing(): void
{
    CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
    $department = RegDepartment::create(['name' => 'Kementerian Hukum']);

    $master = MasterJf::factory()->create([
        'jabatan' => 'Analis Hukum Ahli Pertama',
        'instansi' => 'Kementerian Hukum',
        'unit_kerja' => '',
        'agency_type' => null,
        'agency_id' => null,
    ]);

    $client = new Client;
    app(ClientMatchingService::class)->applyMasterData($client, $master);

    $this->assertSame(RegDepartment::class, $client->agency_type);
    $this->assertSame($department->id, $client->agency_id);
}
```

- [ ] **Step 2: Run tests to verify the copy test fails**

Run: `php artisan test --filter=test_apply_master_data_copies_linked_agency_morph`

Expected: FAIL (`agency_id` not copied because fuzzy lookup misses)

- [ ] **Step 3: Implement copy path**

In `applyMasterData`, replace the block from `$rawInstansi = ...` through `if ($agency) { $client->agency_id = ... }` with:

```php
if ($master->agency_type && $master->agency_id) {
    $client->agency_type = $master->agency_type;
    $client->agency_id = $master->agency_id;
    $client->type = $master->type instanceof \App\Enums\ClientCluster
        ? $master->type
        : \App\Enums\ClientCluster::tryFrom((string) $master->type);
} else {
    $rawInstansi = $master->instansi ?? '';
    $rawUnitKerja = $master->unit_kerja ?? '';

    [$agencyType, $agencyModel] = self::determineAgencyInfo($rawInstansi, $rawUnitKerja);

    $client->type = $agencyType;
    $client->agency_type = $agencyModel;

    $agency = self::findAgency($agencyModel, $rawInstansi, $rawUnitKerja);
    if ($agency) {
        $client->agency_id = $agency->id;
    }
}
```

`$client->type` in the fuzzy branch is currently assigned the cluster **string** from `determineAgencyInfo` (`'central'`, …). Keep that. For the morph branch, Client casts `type` to `ClientCluster`, so enum or string both work; prefer the enum from Master JF.

Do not assign echelon fields.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=ClientMatchingServiceStatusTest`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/ClientMatchingService.php tests/Feature/ClientMatchingServiceStatusTest.php
git commit -m "$(cat <<'EOF'
feat(matching): copy Master JF agency morph onto client

EOF
)"
```

---

### Task 6: Aggregate API groups by linked agency

**Files:**
- Modify: `app/Services/MasterJfAggregateService.php`
- Test: `tests/Feature/Services/MasterJfAggregateServiceTest.php`

**Interfaces:**
- Consumes: `MasterJf.agency_type`, `agency_id`, `agenciable.name`, `instansi`
- Produces: instansi list items still `{ name, client_count }`
  - Linked + related row exists: bucket `agency_type + ":" + agency_id`, name = related `name`
  - Dangling morph or no morph: bucket by trimmed `instansi`, or `"unknown"`
  - Two rows same agency, different `instansi` text → one item
  - Sort by `name` case-insensitive

- [ ] **Step 1: Write failing aggregate tests**

Append to `tests/Feature/Services/MasterJfAggregateServiceTest.php` (import `RegDepartment`):

```php
public function test_it_groups_instansi_by_agency_morph_not_text_spelling(): void
{
    $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
    $department = RegDepartment::create(['name' => 'Kementerian Hukum']);

    MasterJf::factory()->create([
        'c_role_id' => $role->id,
        'type' => ClientCluster::Central,
        'instansi' => 'KEMENKUM spelling A',
        'agency_type' => RegDepartment::class,
        'agency_id' => $department->id,
    ]);
    MasterJf::factory()->create([
        'c_role_id' => $role->id,
        'type' => ClientCluster::Central,
        'instansi' => 'KEMENKUM spelling B',
        'agency_type' => RegDepartment::class,
        'agency_id' => $department->id,
    ]);

    $result = app(MasterJfAggregateService::class)->aggregate([]);

    $this->assertCount(1, $result['data'][0]['data']);
    $this->assertSame('Kementerian Hukum', $result['data'][0]['data'][0]['name']);
    $this->assertSame(2, $result['data'][0]['data'][0]['client_count']);
    $this->assertArrayNotHasKey('agency_id', $result['data'][0]['data'][0]);
}

public function test_it_falls_back_to_instansi_text_and_unknown_when_unlinked(): void
{
    $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);

    MasterJf::factory()->create([
        'c_role_id' => $role->id,
        'type' => ClientCluster::Central,
        'instansi' => 'Free Text Instansi',
        'agency_type' => null,
        'agency_id' => null,
    ]);
    MasterJf::factory()->create([
        'c_role_id' => $role->id,
        'type' => ClientCluster::Central,
        'instansi' => '',
        'agency_type' => null,
        'agency_id' => null,
    ]);

    $result = app(MasterJfAggregateService::class)->aggregate([]);
    $names = array_column($result['data'][0]['data'], 'name');
    sort($names);

    $this->assertSame(['Free Text Instansi', 'unknown'], $names);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=test_it_groups_instansi_by_agency_morph`

Expected: FAIL (two instansi names from text)

- [ ] **Step 3: Implement grouping**

In `aggregate()`, add `agency_type` and `agency_id` to `select([...])` and eager-load `agenciable`:

```php
->with(['cRole:id,role_name', 'agenciable'])
```

Replace `buildInstansiListFromCollection` with:

```php
protected function buildInstansiListFromCollection(Collection $rows): array
{
    $counts = [];
    $labels = [];

    foreach ($rows as $row) {
        $related = $row->agenciable;
        if ($row->agency_type && $row->agency_id && $related) {
            $key = $row->agency_type.':'.$row->agency_id;
            $labels[$key] = (string) $related->name;
        } else {
            $name = trim((string) ($row->instansi ?? ''));
            $key = $name === '' ? 'unknown' : 'text:'.$name;
            $labels[$key] = $name === '' ? 'unknown' : $name;
        }

        $counts[$key] = ($counts[$key] ?? 0) + 1;
    }

    $instansi = [];
    foreach ($counts as $key => $clientCount) {
        $instansi[] = [
            'name' => $labels[$key],
            'client_count' => $clientCount,
        ];
    }

    usort($instansi, fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

    return $instansi;
}
```

Do not add `agency_id` to the item array. Do not change OpenAPI field names.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=MasterJfAggregateServiceTest`

Also: `php artisan test --filter=MasterJfIndexTest`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/MasterJfAggregateService.php \
  tests/Feature/Services/MasterJfAggregateServiceTest.php
git commit -m "$(cat <<'EOF'
feat(api): group Master JF instansi by agency morph

EOF
)"
```

---

### Task 7: Filament form mutate, selects, table display

**Files:**
- Create: `app/Support/MasterJfAgencyForm.php`
- Modify: `app/Filament/Resources/MasterJfResource.php`
- Modify: `app/Filament/Resources/MasterJfResource/Pages/EditMasterJf.php`
- Modify: `app/Filament/Resources/MasterJfResource/Pages/CreateMasterJf.php`
- Test: `tests/Unit/Support/MasterJfAgencyFormTest.php`
- Test: `tests/Feature/Filament/MasterJfEditFormTest.php`

**Interfaces:**
- Consumes: form `type` + `agency_id`
- Produces: `MasterJfAgencyForm::mutate(array $data): array`
  - Both `type` and `agency_id` present → set `agency_type` from cluster value; set `province_id` for pemda; no echelon
  - `agency_id` empty or `type` empty → `agency_type` and `agency_id` null; leave `type` as submitted
  - Compare `type` by `ClientCluster` value (enum or string)
- Form: remove `instansi` TextInput; `type` live-clears `agency_id`; searchable optional `agency_id` by cluster
- Table: `agenciable.name` with fallback to `instansi`; keep text `instansi` SelectFilter
- `getEloquentQuery()` eager-loads `agenciable`

- [ ] **Step 1: Write failing form-mutate unit tests**

Create `tests/Unit/Support/MasterJfAgencyFormTest.php`:

```php
<?php

namespace Tests\Unit\Support;

use App\Enums\ClientCluster;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;
use App\Support\MasterJfAgencyForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterJfAgencyFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sets_agency_type_from_cluster_when_agency_id_present(): void
    {
        $department = RegDepartment::create(['name' => 'Kementerian Hukum']);

        $data = MasterJfAgencyForm::mutate([
            'type' => ClientCluster::Central->value,
            'agency_id' => $department->id,
        ]);

        $this->assertSame(RegDepartment::class, $data['agency_type']);
        $this->assertSame($department->id, $data['agency_id']);
        $this->assertSame(ClientCluster::Central->value, $data['type']);
        $this->assertArrayNotHasKey('echelon_type', $data);
    }

    public function test_it_sets_province_id_from_regency(): void
    {
        RegProvince::query()->create(['id' => 51, 'name' => 'BALI']);
        $regency = RegRegency::query()->create([
            'id' => 5103,
            'province_id' => 51,
            'name' => 'KABUPATEN BADUNG',
        ]);

        $data = MasterJfAgencyForm::mutate([
            'type' => ClientCluster::LocalRegency,
            'agency_id' => $regency->id,
        ]);

        $this->assertSame(RegRegency::class, $data['agency_type']);
        $this->assertSame(51, $data['province_id']);
    }

    public function test_it_nulls_morph_when_agency_cleared(): void
    {
        $data = MasterJfAgencyForm::mutate([
            'type' => 'central',
            'agency_id' => null,
            'agency_type' => RegDepartment::class,
        ]);

        $this->assertNull($data['agency_type']);
        $this->assertNull($data['agency_id']);
        $this->assertSame('central', $data['type']);
    }
}
```

- [ ] **Step 2: Run unit tests to verify they fail**

Run: `php artisan test --filter=MasterJfAgencyFormTest`

Expected: FAIL (class not found)

- [ ] **Step 3: Implement `MasterJfAgencyForm` and wire pages**

Create `app/Support/MasterJfAgencyForm.php`:

```php
<?php

namespace App\Support;

use App\Enums\ClientCluster;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;

final class MasterJfAgencyForm
{
    /** @param array<string, mixed> $data */
    public static function mutate(array $data): array
    {
        $typeValue = $data['type'] instanceof ClientCluster
            ? $data['type']->value
            : ($data['type'] ?? null);
        $typeValue = $typeValue === '' ? null : $typeValue;

        $agencyId = $data['agency_id'] ?? null;
        if ($agencyId === '') {
            $agencyId = null;
        }

        if ($agencyId === null || $typeValue === null) {
            $data['agency_type'] = null;
            $data['agency_id'] = null;

            return $data;
        }

        $data['agency_id'] = $agencyId;
        $data['agency_type'] = match ($typeValue) {
            ClientCluster::Central->value => RegDepartment::class,
            ClientCluster::LocalProvince->value => RegProvince::class,
            ClientCluster::LocalRegency->value => RegRegency::class,
            default => null,
        };

        if ($data['agency_type'] === null) {
            $data['agency_id'] = null;

            return $data;
        }

        if ($typeValue === ClientCluster::LocalProvince->value) {
            $data['province_id'] = $agencyId;
        } elseif ($typeValue === ClientCluster::LocalRegency->value) {
            $regency = RegRegency::query()->find($agencyId);
            $data['province_id'] = $regency?->province_id;
        }

        return $data;
    }
}
```

`EditMasterJf` and `CreateMasterJf`:

```php
protected function mutateFormDataBeforeSave(array $data): array
{
    return \App\Support\MasterJfAgencyForm::mutate($data);
}
```

On `CreateRecord` the hook is `mutateFormDataBeforeCreate`. Use that on `CreateMasterJf` and `mutateFormDataBeforeSave` on `EditMasterJf`.

- [ ] **Step 4: Update `MasterJfResource` form, query, table**

In `getEloquentQuery()`, after the existing query is built, add `->with('agenciable')` on both the superadmin and restricted branches (or chain once on the returned builder).

Replace `TextInput::make('instansi')` with nothing. Keep `Select::make('type')` and make it live:

```php
Forms\Components\Select::make('type')
    ->label('Kluster')
    ->options(ClientCluster::class)
    ->searchable()
    ->preload()
    ->live()
    ->afterStateUpdated(fn (callable $set) => $set('agency_id', null)),
Forms\Components\Select::make('agency_id')
    ->label('Instansi')
    ->searchable()
    ->preload()
    ->live()
    ->options(function (Forms\Get $get): array {
        $type = $get('type');
        $value = $type instanceof ClientCluster ? $type->value : $type;

        return match ($value) {
            ClientCluster::Central->value => \App\Models\RegDepartment::query()->orderBy('name')->pluck('name', 'id')->all(),
            ClientCluster::LocalProvince->value => \App\Models\RegProvince::query()->orderBy('name')->pluck('name', 'id')->all(),
            ClientCluster::LocalRegency->value => \App\Models\RegRegency::query()->orderBy('name')->pluck('name', 'id')->all(),
            default => [],
        };
    })
    ->disabled(function (Forms\Get $get): bool {
        $type = $get('type');
        $value = $type instanceof ClientCluster ? $type->value : $type;

        return blank($value);
    })
    ->dehydrated(true),
```

Keep `unit_kerja` and `provinsi` text inputs. Do not add a second instansi text field. Do not add echelon fields.

Replace table column `instansi` with:

```php
Tables\Columns\TextColumn::make('agenciable.name')
    ->label('Instansi')
    ->getStateUsing(fn (MasterJf $record) => $record->agenciable?->name ?: $record->instansi)
    ->searchable(['instansi'])
    ->sortable(),
```

Keep `SelectFilter::make('instansi')` on the **text** column (`distinctOptions('instansi')`). Do not add a morph filter.

- [ ] **Step 5: Write failing Filament edit tests, then implement until pass**

Append to `tests/Feature/Filament/MasterJfEditFormTest.php` (import `ClientCluster`, `RegDepartment`):

```php
public function test_edit_saves_agency_morph_from_cluster_and_agency_id(): void
{
    $this->actingAsAdmin();
    $department = RegDepartment::create(['name' => 'Kementerian Hukum']);
    $record = MasterJf::factory()->create([
        'type' => null,
        'agency_type' => null,
        'agency_id' => null,
    ]);

    Livewire::test(EditMasterJf::class, ['record' => $record->getRouteKey()])
        ->fillForm([
            'nama' => $record->nama,
            'nip' => $record->nip,
            'type' => ClientCluster::Central->value,
            'agency_id' => $department->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('master_jf', [
        'id' => $record->id,
        'type' => 'central',
        'agency_type' => RegDepartment::class,
        'agency_id' => $department->id,
    ]);
}

public function test_edit_clears_agency_morph_when_agency_id_cleared(): void
{
    $this->actingAsAdmin();
    $department = RegDepartment::create(['name' => 'Kementerian Hukum']);
    $record = MasterJf::factory()->create([
        'type' => ClientCluster::Central,
        'agency_type' => RegDepartment::class,
        'agency_id' => $department->id,
    ]);

    Livewire::test(EditMasterJf::class, ['record' => $record->getRouteKey()])
        ->fillForm([
            'nama' => $record->nama,
            'nip' => $record->nip,
            'type' => ClientCluster::Central->value,
            'agency_id' => null,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('master_jf', [
        'id' => $record->id,
        'type' => 'central',
        'agency_type' => null,
        'agency_id' => null,
    ]);
}
```

Run: `php artisan test --filter=MasterJfEditFormTest`

Expected: first new test FAIL until form + mutate exist; after Step 3–4, both PASS. Existing grade/level tests must still PASS.

- [ ] **Step 6: Run the full related suite**

Run:

```bash
php artisan test --filter=MasterJfAgencyFormTest
php artisan test --filter=MasterJfEditFormTest
php artisan test --filter=MasterJfModelTest
php artisan test --filter=MasterJfAgencyResolverTest
php artisan test --filter=BackfillMasterJfAgencyMorphTest
php artisan test --filter=ClientMatchingServiceStatusTest
php artisan test --filter=MasterJfAggregateServiceTest
php artisan test --filter=MasterJfIndexTest
```

Expected: all PASS

- [ ] **Step 7: Commit**

```bash
git add app/Support/MasterJfAgencyForm.php \
  app/Filament/Resources/MasterJfResource.php \
  app/Filament/Resources/MasterJfResource/Pages/EditMasterJf.php \
  app/Filament/Resources/MasterJfResource/Pages/CreateMasterJf.php \
  tests/Unit/Support/MasterJfAgencyFormTest.php \
  tests/Feature/Filament/MasterJfEditFormTest.php
git commit -m "$(cat <<'EOF'
feat(filament): select Master JF instansi via agency morph

EOF
)"
```

---

## Self-review (spec coverage)

| Spec requirement | Task |
| --- | --- |
| Nullable `agency_type` / `agency_id` + index | 1 |
| `agenciable()` + `masterJfs()` inverses; keep Client `agency()` | 1 |
| Factory morph null; SQLite trait columns | 1 |
| Resolver + `findAgency` lookup order; no create-on-miss | 2 |
| Derived `type` / `province_id` rules | 2, 3, 4, 7 |
| Backfill skip linked / miss / idempotent / keep `instansi` | 3 |
| Import hit writes morph; miss keeps existing; text still stored | 4 |
| Matching copies morph; fuzzy fallback | 5 |
| API group by morph else text else `unknown`; no `agency_id` in JSON | 6 |
| Filament cluster + agency select; no instansi text editor; table fallback | 7 |
| No echelon, no Agencyable trait, no dropping text columns | Global constraints |
