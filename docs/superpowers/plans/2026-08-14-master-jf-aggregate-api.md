# Master JF Aggregate API Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Expose `GET /api/v1/master-jf` with API-key auth, full filter set, filter-aware aggregations, and paginated Master JF rows for superapps integration.

**Architecture:** Add standard Laravel API layer inside the existing app: `routes/api.php`, `VerifyApiKey` middleware, `MasterJfAggregateService` for shared filter/aggregate logic, thin `MasterJfController`, JSON API Resources. Reuse `MasterJf` Eloquent model and enum casts. Document with Scramble.

**Tech Stack:** Laravel 12, PHP 8.2+, PHPUnit 11, SQLite in-memory tests (`RefreshDatabase`), dedoc/scramble for OpenAPI

## Implementation status (2026-08-14)

Tasks 1–5 **implemented** in working tree; **not committed** (per team request). Task 6 manual verification pending locally.

## Global Constraints

- Spec: `docs/superpowers/specs/2026-08-14-master-jf-aggregate-api-design.md`
- Branch: `feat/master-jf-aggregate-api`
- Endpoint: `GET /api/v1/master-jf` (read-only — **no INSERT/UPDATE/DELETE** on `master_jf`)
- Auth header: `X-Api-Key` compared to `SUPERAPPS_API_KEY` via `config('services.superapps.api_key')`
- Rate limit: `throttle:60,1`
- Single Laravel app — no monorepo
- **No new migrations** for `master_jf` / `reg_provinces` — prod/dev DB restore already has `province_id`, `c_role_id`, `reg_grade_id`, etc.; do not add Laravel migrations that could worry operators or touch existing schema
- Province hybrid: `province_id` → `reg_provinces.name`; fallback trimmed `provinsi` text
- Jenjang: parse from `jabatan` (Filament behavior); `c_role_level_id` FK is 0% filled in prod data
- Aggregations over full filtered set; pagination only on `data[]`
- Enum aggregation buckets: zero-fill all known enum values + `unknown` for null/unmapped
- JSON response: top-level keys must not be wrapped in `"data"` — use `MasterJfIndexResource::withoutWrapping()` or `response()->json()`
- Conventional commits in English; commit after each task
- PHPUnit feature tests with `RefreshDatabase` (repo does not use Pest for this area)

---

## Data source audit (must match running Filament)

Verified against dev DB restore (958 rows) and live code paths:

| Field | Where admin UI gets it | Fill rate | API must |
|---|---|---|---|
| Jabatan Fungsional | `cRole.role_name`; import maps from `jabatan` text | 958/958 | Same FK + import-style fallback |
| Jenjang | **`jabatan` regex** in table; filter uses `jabatan LIKE` | `c_role_level_id` **0/958** | `MasterJfDisplay::resolveJenjang()` — parse first, FK only if set |
| Golongan/Ruang | `grade.clean_name`; import via `RegGradeResolver` | 805/958 | Relation; null if unmapped |
| Provinsi | **text `provinsi`** in table/filters; import sets text only | 884/884 paired | Hybrid response; filter `provinsi` only when `province_id` IS NULL |

**Key files:** `MasterJfResource.php` (lines 118–130, 193–207, 218–221), `MasterJfImport.php`, `RegGradeResolver.php`, `MasterJfNumbersByJenjangOverview.php`

**New shared helper:** `app/Support/MasterJfDisplay.php`

---

## Schema policy (no migrations)

Prod/dev database (SQL restore via DBeaver) **already contains**:

- `master_jf.province_id`, `c_role_id`, `reg_grade_id`, `c_role_level_id`, `provinsi`, …
- `reg_provinces`, `reg_grades`, `c_roles`, `c_role_levels` reference tables

The API **only reads** this data. Model changes (`province()` relation, `$fillable`) map to existing columns — they do not alter the database.

**PHPUnit only:** `tests/Concerns/EnsuresMasterJfApiSchema.php` creates minimal `reg_provinces` + `master_jf.province_id` in SQLite `:memory:` when missing (same pattern as `MasterJfModelTest::setUp`). This affects tests only, never prod/dev PostgreSQL.

**Do not run** `php artisan migrate` for this feature on restored databases.

---

## File structure

| File | Responsibility |
| --- | --- |
| `bootstrap/app.php` | Register `api` routes + `verify.api.key` alias |
| `routes/api.php` | `GET v1/master-jf` route group (register **after** controller exists) |
| `config/services.php` | `superapps.api_key` config |
| `.env.example` | `SUPERAPPS_API_KEY=` |
| `.env.template` | `SUPERAPPS_API_KEY=` (LERD/local template used by team) |
| `deployment/fungsionalpro.env` | `SUPERAPPS_API_KEY=` placeholder |
| `app/Support/MasterJfDisplay.php` | Jenjang parse + title-case normalization (shared with API) |
| `app/Models/MasterJf.php` | Add `province()` relation + `province_id` in `$fillable` |
| `tests/Concerns/EnsuresMasterJfApiSchema.php` | Test-only schema for `reg_provinces` + `province_id` column |
| `app/Http/Middleware/VerifyApiKey.php` | API key gate |
| `app/Services/MasterJfAggregateService.php` | Filter query + aggregations + pagination |
| `app/Http/Requests/Api/V1/MasterJfIndexRequest.php` | Query validation |
| `app/Http/Resources/Api/V1/MasterJfItemResource.php` | Row JSON shape |
| `app/Http/Resources/Api/V1/MasterJfIndexResource.php` | Top-level wrapper |
| `app/Http/Controllers/Api/V1/MasterJfController.php` | HTTP handler |
| `tests/Feature/Services/MasterJfAggregateServiceTest.php` | Service-layer filter/aggregate tests |
| `tests/Unit/Support/MasterJfDisplayTest.php` | Jenjang parse + role inference unit tests |
| `tests/Feature/Api/V1/MasterJfIndexTest.php` | End-to-end API tests |
| `tests/Feature/Http/Middleware/VerifyApiKeyTest.php` | Middleware tests |
| `config/scramble.php` | OpenAPI docs (published) |
| `phpunit.xml` | `SUPERAPPS_API_KEY` test env value |

---

### Task 1: API scaffold, config, display helpers, and model

**Files:**
- Modify: `bootstrap/app.php`
- Create: `routes/api.php` (ping route initially — **no controller reference yet**)
- Modify: `config/services.php`
- Modify: `.env.example`
- Modify: `.env.template`
- Modify: `deployment/fungsionalpro.env`
- Modify: `phpunit.xml`
- Create: `app/Support/MasterJfDisplay.php`
- Create: `tests/Unit/Support/MasterJfDisplayTest.php`
- Create: `tests/Concerns/EnsuresMasterJfApiSchema.php`
- Modify: `app/Models/MasterJf.php`

**Interfaces:**
- Consumes: existing `MasterJf`, `RegProvince` models; existing DB columns (no migration)
- Produces: API route file + bootstrap wiring; `MasterJf::province()` relation; jenjang helper; test schema trait

- [x] **Step 1: Create `MasterJfDisplay` helper**

Create `app/Support/MasterJfDisplay.php`:

```php
<?php

namespace App\Support;

use App\Models\MasterJf;

final class MasterJfDisplay
{
    /** @var list<string> */
    private const KNOWN_JENJANG = [
        'Ahli Pertama',
        'Ahli Muda',
        'Ahli Madya',
        'Ahli Utama',
    ];

    public static function parseJenjangFromJabatan(?string $jabatan): ?string
    {
        if ($jabatan === null || trim($jabatan) === '') {
            return null;
        }

        $parsed = preg_replace('/^(Penyuluh Hukum|Analis Hukum)\s+/i', '', trim($jabatan));

        return self::normalizeJenjangLabel($parsed !== '' ? $parsed : null);
    }

    public static function resolveJenjang(MasterJf $row): ?string
    {
        if ($row->c_role_level_id !== null) {
            $level = $row->relationLoaded('cRoleLevel') ? $row->getRelation('cRoleLevel') : null;
            if ($level?->level) {
                return self::normalizeJenjangLabel($level->level);
            }
        }

        return self::parseJenjangFromJabatan($row->jabatan);
    }

    public static function inferRoleNameFromJabatan(?string $jabatan): ?string
    {
        if ($jabatan === null || trim($jabatan) === '') {
            return null;
        }

        if (stripos($jabatan, 'Analis Hukum') !== false) {
            return 'Analis Hukum';
        }

        if (stripos($jabatan, 'Penyuluh Hukum') !== false) {
            return 'Penyuluh Hukum';
        }

        return null;
    }

    public static function resolveJabatanFungsional(MasterJf $row): ?string
    {
        $role = $row->relationLoaded('cRole') ? $row->getRelation('cRole') : null;

        return $role?->role_name ?? self::inferRoleNameFromJabatan($row->jabatan);
    }

    public static function normalizeJenjangLabel(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $lower = strtolower(trim($value));

        foreach (self::KNOWN_JENJANG as $known) {
            if ($lower === strtolower($known)) {
                return $known;
            }
        }

        return trim($value);
    }
}
```

- [x] **Step 2: Write unit tests for `MasterJfDisplay`**

Create `tests/Unit/Support/MasterJfDisplayTest.php` — extend `Tests\TestCase` (Laravel app required for Eloquent model stubs):

```php
<?php

namespace Tests\Unit\Support;

use App\Models\CRole;
use App\Models\CRoleLevel;
use App\Models\MasterJf;
use App\Support\MasterJfDisplay;
use Tests\TestCase;

class MasterJfDisplayTest extends TestCase
{
    public function test_it_parses_jenjang_from_jabatan_and_normalizes_case(): void
    {
        $this->assertSame('Ahli Muda', MasterJfDisplay::parseJenjangFromJabatan('Penyuluh Hukum AHLI MUDA'));
    }

    public function test_it_prefers_c_role_level_when_fk_is_set(): void
    {
        $row = new MasterJf([
            'jabatan' => 'Penyuluh Hukum Ahli Pertama',
            'c_role_level_id' => 99,
        ]);
        $row->setRelation('cRoleLevel', new CRoleLevel(['level' => 'Ahli Madya']));

        $this->assertSame('Ahli Madya', MasterJfDisplay::resolveJenjang($row));
    }

    public function test_it_infers_jabatan_fungsional_from_jabatan_text(): void
    {
        $row = new MasterJf(['jabatan' => 'Analis Hukum Ahli Muda']);

        $this->assertSame('Analis Hukum', MasterJfDisplay::resolveJabatanFungsional($row));
    }

    public function test_it_prefers_c_role_relation_for_jabatan_fungsional(): void
    {
        $row = new MasterJf(['jabatan' => 'Analis Hukum Ahli Muda']);
        $row->setRelation('cRole', new CRole(['role_name' => 'Penyuluh Hukum']));

        $this->assertSame('Penyuluh Hukum', MasterJfDisplay::resolveJabatanFungsional($row));
    }
}
```

Run: `lerd php artisan test tests/Unit/Support/MasterJfDisplayTest.php`

Expected: PASS

- [x] **Step 3: Create test-only schema trait**

Create `tests/Concerns/EnsuresMasterJfApiSchema.php` (used by API/service feature tests):

```php
<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait EnsuresMasterJfApiSchema
{
    protected function ensureMasterJfApiSchema(): void
    {
        if (! Schema::hasTable('reg_provinces')) {
            Schema::create('reg_provinces', function (Blueprint $table) {
                $table->unsignedInteger('id')->primary();
                $table->string('name');
            });
        }

        if (Schema::hasTable('master_jf') && ! Schema::hasColumn('master_jf', 'province_id')) {
            Schema::table('master_jf', function (Blueprint $table) {
                $table->unsignedInteger('province_id')->nullable();
            });
        }
    }
}
```

In `MasterJfIndexTest` and `MasterJfAggregateServiceTest`: `use EnsuresMasterJfApiSchema;` and call `$this->ensureMasterJfApiSchema()` in `setUp()`.

- [x] **Step 4: Update `MasterJf` model**

Add to `$fillable`: `'province_id'` ( `provinsi` and `c_role_id` already present).

Add relation:

```php
public function province(): BelongsTo
{
    return $this->belongsTo(RegProvince::class, 'province_id');
}
```

Add `use App\Models\RegProvince;` if importing by name (or same-namespace import not needed).

- [x] **Step 5: Register API routes in `bootstrap/app.php`**

Change `withRouting` to:

```php
->withRouting(
    web: __DIR__ . '/../routes/web.php',
    api: __DIR__ . '/../routes/api.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
)
```

Add middleware alias inside `withMiddleware` callback (keep existing aliases):

```php
$middleware->alias([
    'auth' => \Filament\Http\Middleware\Authenticate::class,
    'verify.api.key' => \App\Http\Middleware\VerifyApiKey::class,
]);
```

- [x] **Step 6: Create stub `routes/api.php` with auth ping route**

Avoid referencing `MasterJfController` until Task 4. Add a ping route so Task 2 middleware tests can hit a real HTTP endpoint (empty group → 404, not 401).

```php
<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['verify.api.key', 'throttle:60,1'])
    ->group(function () {
        Route::get('ping', fn () => response()->json(['ok' => true]));
        // GET master-jf registered in Task 4 after controller exists
    });
```

- [x] **Step 7: Add config and env**

In `config/services.php`:

```php
'superapps' => [
    'api_key' => env('SUPERAPPS_API_KEY'),
],
```

In `.env.example` append:

```env
SUPERAPPS_API_KEY=
```

In `.env.template` append (same block):

```env
SUPERAPPS_API_KEY=
```

In `deployment/fungsionalpro.env` (add new section before Vite):

```env
# ── Superapps API Integration ──────────────────────────────────────────────────

SUPERAPPS_API_KEY=
```

In `phpunit.xml` inside `<php>`:

```xml
<env name="SUPERAPPS_API_KEY" value="test-superapps-key"/>
```

- [x] **Step 8: Run unit tests**

Run: `lerd php artisan test tests/Unit/Support/MasterJfDisplayTest.php`

Expected: PASS

**Do not run** `php artisan migrate` on prod/dev restored DB — schema already exists.

- [ ] **Step 9: Commit** *(wait for explicit request)*

```bash
git add bootstrap/app.php routes/api.php config/services.php .env.example .env.template deployment/fungsionalpro.env phpunit.xml \
  app/Support/MasterJfDisplay.php tests/Unit/Support/MasterJfDisplayTest.php tests/Concerns/EnsuresMasterJfApiSchema.php app/Models/MasterJf.php
git commit -m "feat(api): scaffold API routes, display helpers, and MasterJf province relation"
```

---

### Task 2: VerifyApiKey middleware

**Files:**
- Create: `app/Http/Middleware/VerifyApiKey.php`
- Create: `tests/Feature/Http/Middleware/VerifyApiKeyTest.php`

**Interfaces:**
- Consumes: `config('services.superapps.api_key')`
- Produces: middleware alias `verify.api.key` returning 401 JSON or `$next($request)`

- [ ] **Step 1: Write failing middleware tests**

Create `tests/Feature/Http/Middleware/VerifyApiKeyTest.php`:

```php
<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\VerifyApiKey;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class VerifyApiKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_missing_api_key(): void
    {
        $response = $this->getJson('/api/v1/ping');

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Unauthorized']);
    }

    public function test_it_rejects_invalid_api_key(): void
    {
        $response = $this->getJson('/api/v1/ping', [
            'X-Api-Key' => 'wrong-key',
        ]);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Unauthorized']);
    }

    public function test_middleware_passes_valid_key(): void
    {
        $middleware = new VerifyApiKey();
        $request = Request::create('/api/v1/ping', 'GET');
        $request->headers->set('X-Api-Key', 'test-superapps-key');

        $response = $middleware->handle($request, fn () => new Response('ok', 200));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }
}
```

- [ ] **Step 2: Run tests to verify failure**

Run: `lerd php artisan test tests/Feature/Http/Middleware/VerifyApiKeyTest.php`

Expected: FAIL — class `VerifyApiKey` not found or 500.

- [ ] **Step 3: Implement middleware**

Create `app/Http/Middleware/VerifyApiKey.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = (string) config('services.superapps.api_key', '');
        $provided = (string) $request->header('X-Api-Key', '');

        if ($configured === '' || ! hash_equals($configured, $provided)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Run tests**

Run: `lerd php artisan test tests/Feature/Http/Middleware/VerifyApiKeyTest.php`

Expected: PASS (first two tests hit `/api/v1/ping`; third tests middleware directly).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Middleware/VerifyApiKey.php tests/Feature/Http/Middleware/VerifyApiKeyTest.php
git commit -m "feat(api): add VerifyApiKey middleware for superapps integration"
```

---

### Task 3: MasterJfAggregateService

**Files:**
- Create: `app/Services/MasterJfAggregateService.php`
- Create: `tests/Feature/Services/MasterJfAggregateServiceTest.php`

**Interfaces:**
- Consumes: `MasterJf` with relations `cRole`, `cRoleLevel`, `grade`, `province`
- Produces:
  - `MasterJfAggregateService::paginate(array $filters): array` returning keys `total_filtered`, `page`, `per_page`, `total_pages`, `agregasi`, `items` (LengthAwarePaginator items collection)

- [ ] **Step 1: Write failing service tests**

Create `tests/Feature/Services/MasterJfAggregateServiceTest.php`:

```php
<?php

namespace Tests\Feature\Services;

use App\Enums\ClientStatus;
use App\Models\CRole;
use App\Models\MasterJf;
use App\Services\MasterJfAggregateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterJfAggregateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_by_c_role_id_and_aggregates_total(): void
    {
        $roleA = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $roleB = CRole::create(['role_name' => 'Penyuluh Hukum', 'active' => true]);

        MasterJf::factory()->count(2)->create(['c_role_id' => $roleA->id, 'status' => ClientStatus::Active]);
        MasterJf::factory()->create(['c_role_id' => $roleB->id, 'status' => ClientStatus::Active]);

        $service = app(MasterJfAggregateService::class);
        $result = $service->paginate(['c_role_id' => $roleA->id, 'page' => 1, 'per_page' => 10]);

        $this->assertSame(2, $result['total_filtered']);
        $this->assertSame(2, $result['agregasi']['total_jf']);
        $this->assertSame(2, $result['agregasi']['by_jabatan_fungsional']['Analis Hukum'] ?? 0);
        $this->assertCount(2, $result['items']);
    }

    public function test_search_matches_nama_or_nip(): void
    {
        MasterJf::factory()->create(['nama' => 'Akbar Maulana', 'nip' => '111']);
        MasterJf::factory()->create(['nama' => 'Other Person', 'nip' => '222']);

        $service = app(MasterJfAggregateService::class);
        $result = $service->paginate(['search' => 'Akbar', 'page' => 1, 'per_page' => 10]);

        $this->assertSame(1, $result['total_filtered']);
    }

    public function test_jenjang_filter_matches_jabatan_text(): void
    {
        MasterJf::factory()->create(['jabatan' => 'Analis Hukum Ahli Madya']);
        MasterJf::factory()->create(['jabatan' => 'Penyuluh Hukum Ahli Pertama']);

        $service = app(MasterJfAggregateService::class);
        $result = $service->paginate(['jenjang' => 'Ahli Madya', 'page' => 1, 'per_page' => 10]);

        $this->assertSame(1, $result['total_filtered']);
    }

    public function test_c_role_level_id_filter_resolves_level_to_jabatan_like(): void
    {
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $level = CRoleLevel::create(['c_role_id' => $role->id, 'level' => 'Ahli Utama']);

        MasterJf::factory()->create(['jabatan' => 'Analis Hukum Ahli Utama', 'c_role_level_id' => null]);
        MasterJf::factory()->create(['jabatan' => 'Analis Hukum Ahli Muda', 'c_role_level_id' => null]);

        $service = app(MasterJfAggregateService::class);
        $result = $service->paginate(['c_role_level_id' => $level->id, 'page' => 1, 'per_page' => 10]);

        $this->assertSame(1, $result['total_filtered']);
    }

    public function test_provinsi_filter_only_applies_when_province_id_is_null(): void
    {
        RegProvince::query()->create(['id' => 11, 'name' => 'ACEH']);

        MasterJf::factory()->create(['province_id' => null, 'provinsi' => 'BENGKULU']);
        MasterJf::factory()->create(['province_id' => 11, 'provinsi' => 'ACEH']);

        $service = app(MasterJfAggregateService::class);
        $result = $service->paginate(['provinsi' => 'BENGKULU', 'page' => 1, 'per_page' => 10]);

        $this->assertSame(1, $result['total_filtered']);
    }
}
```

- [ ] **Step 2: Run tests — expect FAIL**

Run: `lerd php artisan test tests/Feature/Services/MasterJfAggregateServiceTest.php`

Expected: class not found.

- [ ] **Step 3: Implement service**

Create `app/Services/MasterJfAggregateService.php`:

```php
<?php

namespace App\Services;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;
use App\Models\MasterJf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MasterJfAggregateService
{
    /** @return array{total_filtered:int,page:int,per_page:int,total_pages:int,agregasi:array,items:\Illuminate\Support\Collection} */
    public function paginate(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 20)));

        $baseQuery = $this->buildFilteredQuery($filters);
        $totalFiltered = (clone $baseQuery)->count();

        $paginator = (clone $baseQuery)
            ->with(['cRole', 'cRoleLevel', 'grade', 'province'])
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'total_filtered' => $totalFiltered,
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total_pages' => $paginator->lastPage(),
            'agregasi' => $this->computeAggregations($baseQuery, $totalFiltered),
            'items' => collect($paginator->items()),
        ];
    }

    public function buildFilteredQuery(array $filters): Builder
    {
        $query = MasterJf::query();

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function (Builder $q) use ($search) {
                $like = '%'.$search.'%';
                $q->where('nama', 'like', $like)
                    ->orWhere('nip', 'like', $like)
                    ->orWhere('instansi', 'like', $like)
                    ->orWhere('unit_kerja', 'like', $like);
            });
        }

        if (isset($filters['c_role_id'])) {
            $query->where('c_role_id', $filters['c_role_id']);
        }

        if (isset($filters['c_role_level_id'])) {
            $level = \App\Models\CRoleLevel::query()->find($filters['c_role_level_id']);
            if ($level?->level) {
                $query->whereRaw('LOWER(jabatan) LIKE ?', ['%'.strtolower($level->level).'%']);
            } else {
                $query->whereRaw('0 = 1');
            }
        }

        if ($jenjang = trim((string) ($filters['jenjang'] ?? ''))) {
            $query->whereRaw('LOWER(jabatan) LIKE ?', ['%'.strtolower($jenjang).'%']);
        }

        if (isset($filters['reg_grade_id'])) {
            $query->where('reg_grade_id', $filters['reg_grade_id']);
        }

        if (isset($filters['province_id'])) {
            $query->where('province_id', $filters['province_id']);
        }

        if ($provinsi = trim((string) ($filters['provinsi'] ?? ''))) {
            $query->whereNull('province_id')
                ->where('provinsi', 'like', '%'.$provinsi.'%');
        }

        if ($pengangkatan = trim((string) ($filters['pengangkatan'] ?? ''))) {
            $query->where('pengangkatan', $pengangkatan);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['status_kepegawaian'])) {
            $query->where('status_kepegawaian', $filters['status_kepegawaian']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query;
    }

    /** @return array<string, mixed> */
    protected function computeAggregations(Builder $baseQuery, int $totalFiltered): array
    {
        $aggregateBase = (clone $baseQuery)->toBase()->reorder();

        return [
            'total_jf' => $totalFiltered,
            'by_status' => $this->groupEnumCounts(
                (clone $aggregateBase)->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status'),
                array_column(ClientStatus::cases(), 'value'),
            ),
            'by_status_kepegawaian' => $this->groupEnumCounts(
                (clone $aggregateBase)->select('status_kepegawaian', DB::raw('COUNT(*) as total'))->groupBy('status_kepegawaian')->pluck('total', 'status_kepegawaian'),
                array_column(JenisKepegawaian::cases(), 'value'),
            ),
            'by_pengangkatan' => $this->groupRawCounts(
                (clone $aggregateBase)->select('pengangkatan', DB::raw('COUNT(*) as total'))->groupBy('pengangkatan')->pluck('total', 'pengangkatan'),
            ),
            'by_kluster' => $this->groupEnumCounts(
                (clone $aggregateBase)->select('type', DB::raw('COUNT(*) as total'))->groupBy('type')->pluck('total', 'type'),
                array_column(ClientCluster::cases(), 'value'),
            ),
            'by_jabatan_fungsional' => $this->groupJabatanFungsional($baseQuery),
        ];
    }

    /** @param Collection<int|string, mixed> $counts @param list<string> $knownValues */
    protected function groupEnumCounts(Collection $counts, array $knownValues): array
    {
        $result = [];
        $unknown = 0;

        foreach ($counts as $key => $total) {
            $key = $key === null ? '' : (string) $key;
            if ($key === '' || ! in_array($key, $knownValues, true)) {
                $unknown += (int) $total;
                continue;
            }
            $result[$key] = (int) $total;
        }

        if ($unknown > 0) {
            $result['unknown'] = $unknown;
        }

        foreach ($knownValues as $value) {
            $result[$value] ??= 0;
        }

        return $result;
    }

    /** @param Collection<int|string, mixed> $counts */
    protected function groupRawCounts(Collection $counts): array
    {
        $result = [];
        $unknown = 0;

        foreach ($counts as $key => $total) {
            $key = $key === null ? '' : trim((string) $key);
            if ($key === '') {
                $unknown += (int) $total;
                continue;
            }
            $result[$key] = (int) $total;
        }

        if ($unknown > 0) {
            $result['unknown'] = $unknown;
        }

        return $result;
    }

    /** @return array<string, int> */
    protected function groupJabatanFungsional(Builder $baseQuery): array
    {
        $rows = (clone $baseQuery)
            ->toBase()
            ->reorder()
            ->leftJoin('c_roles', 'c_roles.id', '=', 'master_jf.c_role_id')
            ->select('c_roles.role_name', DB::raw('COUNT(*) as total'))
            ->groupBy('c_roles.role_name')
            ->get();

        $result = [];
        $unknown = 0;

        foreach ($rows as $row) {
            $name = trim((string) ($row->role_name ?? ''));
            if ($name === '') {
                $unknown += (int) $row->total;
                continue;
            }
            $result[$name] = (int) $row->total;
        }

        if ($unknown > 0) {
            $result['unknown'] = $unknown;
        }

        return $result;
    }
}
```

- [ ] **Step 4: Run service tests**

Run: `lerd php artisan test tests/Feature/Services/MasterJfAggregateServiceTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/MasterJfAggregateService.php tests/Feature/Services/MasterJfAggregateServiceTest.php
git commit -m "feat(api): add MasterJfAggregateService with filters and aggregations"
```

---

### Task 4: Request, Resources, and Controller

**Files:**
- Create: `app/Http/Requests/Api/V1/MasterJfIndexRequest.php`
- Create: `app/Http/Resources/Api/V1/MasterJfItemResource.php`
- Create: `app/Http/Resources/Api/V1/MasterJfIndexResource.php`
- Create: `app/Http/Controllers/Api/V1/MasterJfController.php`
- Create: `tests/Feature/Api/V1/MasterJfIndexTest.php`

**Interfaces:**
- Consumes: `MasterJfAggregateService::paginate(array $filters): array`
- Produces: HTTP 200 JSON from `GET /api/v1/master-jf`

- [ ] **Step 1: Write failing feature tests**

Create `tests/Feature/Api/V1/MasterJfIndexTest.php`:

```php
<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ClientStatus;
use App\Models\CRole;
use App\Models\MasterJf;
use App\Models\RegProvince;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterJfIndexTest extends TestCase
{
    use RefreshDatabase;

    private function apiHeaders(): array
    {
        return ['X-Api-Key' => 'test-superapps-key'];
    }

    public function test_it_requires_api_key(): void
    {
        $this->getJson('/api/v1/master-jf')->assertUnauthorized();
    }

    public function test_it_rejects_wrong_api_key(): void
    {
        $this->getJson('/api/v1/master-jf', ['X-Api-Key' => 'bad'])
            ->assertUnauthorized();
    }

    public function test_it_returns_paginated_payload_with_aggregations(): void
    {
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        MasterJf::factory()->count(3)->create([
            'c_role_id' => $role->id,
            'status' => ClientStatus::Active,
        ]);

        $response = $this->getJson('/api/v1/master-jf?per_page=2', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'total_filtered', 'page', 'per_page', 'total_pages', 'agregasi', 'data',
            ])
            ->assertJsonPath('total_filtered', 3)
            ->assertJsonPath('agregasi.total_jf', 3)
            ->assertJsonCount(2, 'data');
    }

    public function test_per_page_over_max_returns_422(): void
    {
        $this->getJson('/api/v1/master-jf?per_page=101', $this->apiHeaders())
            ->assertUnprocessable();
    }

    public function test_province_hybrid_uses_reg_provinces_name(): void
    {
        $province = RegProvince::query()->create(['id' => 11, 'name' => 'ACEH']);

        MasterJf::factory()->create([
            'province_id' => $province->id,
            'provinsi' => 'legacy text',
        ]);

        $response = $this->getJson('/api/v1/master-jf', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('data.0.provinsi', 'ACEH')
            ->assertJsonPath('data.0.provinsi_id', 11);
    }

    public function test_province_fallback_uses_text_column_when_fk_null(): void
    {
        MasterJf::factory()->create([
            'province_id' => null,
            'provinsi' => 'BENGKULU',
        ]);

        $response = $this->getJson('/api/v1/master-jf', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('data.0.provinsi', 'BENGKULU')
            ->assertJsonPath('data.0.provinsi_id', null);
    }

    public function test_jenjang_is_parsed_from_jabatan_when_level_fk_null(): void
    {
        MasterJf::factory()->create([
            'c_role_level_id' => null,
            'jabatan' => 'Penyuluh Hukum Ahli Muda',
        ]);

        $response = $this->getJson('/api/v1/master-jf', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('data.0.jenjang', 'Ahli Muda');
    }

    public function test_jenjang_filter_matches_jabatan_like_filament(): void
    {
        MasterJf::factory()->create(['jabatan' => 'Analis Hukum Ahli Madya', 'c_role_level_id' => null]);
        MasterJf::factory()->create(['jabatan' => 'Penyuluh Hukum Ahli Pertama', 'c_role_level_id' => null]);

        $response = $this->getJson('/api/v1/master-jf?jenjang=Ahli%20Madya', $this->apiHeaders());

        $response->assertOk()->assertJsonPath('total_filtered', 1);
    }
}
```

- [ ] **Step 2: Run tests — expect FAIL**

Run: `lerd php artisan test tests/Feature/Api/V1/MasterJfIndexTest.php`

Expected: FAIL — controller not found / 500.

- [ ] **Step 3: Create Form Request**

`app/Http/Requests/Api/V1/MasterJfIndexRequest.php`:

```php
<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterJfIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'string', 'max:255'],
            'c_role_id' => ['sometimes', 'integer', 'exists:c_roles,id'],
            'c_role_level_id' => ['sometimes', 'integer', 'exists:c_role_levels,id'],
            'jenjang' => ['sometimes', 'string', 'max:255'],
            'reg_grade_id' => ['sometimes', 'integer', 'exists:reg_grades,id'],
            'province_id' => ['sometimes', 'integer', 'exists:reg_provinces,id'],
            'provinsi' => ['sometimes', 'string', 'max:255'],
            'pengangkatan' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(ClientStatus::class)],
            'status_kepegawaian' => ['sometimes', Rule::enum(JenisKepegawaian::class)],
            'type' => ['sometimes', Rule::enum(ClientCluster::class)],
        ];
    }
}
```

- [ ] **Step 4: Create API Resources**

`app/Http/Resources/Api/V1/MasterJfItemResource.php`:

```php
<?php

namespace App\Http\Resources\Api\V1;

use App\Models\MasterJf;
use App\Support\MasterJfDisplay;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MasterJf */
class MasterJfItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'nip' => $this->nip,
            'jabatan' => $this->jabatan,
            'jabatan_fungsional' => MasterJfDisplay::resolveJabatanFungsional($this->resource),
            'jenjang' => MasterJfDisplay::resolveJenjang($this->resource),
            'golongan_ruang' => $this->grade?->grade_name,
            'golongan_ruang_code' => $this->grade?->clean_name,
            'instansi' => $this->instansi,
            'unit_kerja' => $this->unit_kerja,
            'provinsi_id' => $this->province_id,
            'provinsi' => $this->province?->name ?? (is_string($this->provinsi) ? trim($this->provinsi) : null),
            'kluster' => $this->type?->getLabel(),
            'kluster_code' => $this->type?->value,
            'pengangkatan' => $this->pengangkatan,
            'status' => $this->status?->getLabel(),
            'status_code' => $this->status?->value,
            'status_kepegawaian' => $this->status_kepegawaian?->value,
        ];
    }
}
```

`app/Http/Resources/Api/V1/MasterJfIndexResource.php`:

```php
<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterJfIndexResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'total_filtered' => $this->resource['total_filtered'],
            'page' => $this->resource['page'],
            'per_page' => $this->resource['per_page'],
            'total_pages' => $this->resource['total_pages'],
            'agregasi' => $this->resource['agregasi'],
            'data' => MasterJfItemResource::collection($this->resource['items']),
        ];
    }
}
```

- [ ] **Step 5: Register route and create controller**

Add to `routes/api.php` inside the existing group:

```php
use App\Http\Controllers\Api\V1\MasterJfController;

Route::get('master-jf', [MasterJfController::class, 'index']);
```

`app/Http/Controllers/Api/V1/MasterJfController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MasterJfIndexRequest;
use App\Http\Resources\Api\V1\MasterJfIndexResource;
use App\Services\MasterJfAggregateService;

class MasterJfController extends Controller
{
    public function __construct(private readonly MasterJfAggregateService $service) {}

    public function index(MasterJfIndexRequest $request): MasterJfIndexResource
    {
        $payload = $this->service->paginate($request->validated());

        return new MasterJfIndexResource($payload);
    }
}
```

- [ ] **Step 6: Run feature tests**

Run: `lerd php artisan test tests/Feature/Api/V1/MasterJfIndexTest.php`

Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add routes/api.php \
  app/Http/Requests/Api/V1/MasterJfIndexRequest.php \
  app/Http/Resources/Api/V1/MasterJfItemResource.php \
  app/Http/Resources/Api/V1/MasterJfIndexResource.php \
  app/Http/Controllers/Api/V1/MasterJfController.php \
  tests/Feature/Api/V1/MasterJfIndexTest.php
git commit -m "feat(api): add GET /api/v1/master-jf endpoint with JSON resources"
```

---

### Task 5: Scramble OpenAPI documentation

**Files:**
- Modify: `composer.json` / `composer.lock` (via composer require)
- Create: `config/scramble.php` (published)
- Modify: `bootstrap/app.php` or Scramble config for `/docs/api` path

**Interfaces:**
- Consumes: existing route + request + resource classes
- Produces: OpenAPI UI at `/docs/api`, JSON at `/docs/api.json`

- [ ] **Step 1: Install Scramble**

Run: `composer require dedoc/scramble --no-interaction`

- [ ] **Step 2: Publish config**

Run: `lerd php artisan vendor:publish --provider="Dedoc\Scramble\ScrambleServiceProvider" --tag=scramble-config`

- [ ] **Step 3: Restrict docs to non-production (optional but recommended)**

In `config/scramble.php`, set middleware or gate so `/docs/api` is available in `local`/`staging` only, or document that production sets `SCRAMBLE_ENABLED=false` if the package supports it.

- [ ] **Step 4: Smoke-test docs locally**

Run: `lerd php artisan serve` (or use LERD site) and open `/docs/api`.

Expected: `GET /api/v1/master-jf` listed with query params and response schema.

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock config/scramble.php
git commit -m "docs(api): add Scramble OpenAPI documentation for Master JF API"
```

---

### Task 6: Manual verification checklist

**Files:** none (verification only)

- [ ] **Step 1: Set API key in local `.env`**

```env
SUPERAPPS_API_KEY=local-dev-key-change-me
```

Run: `lerd php artisan config:clear`

- [ ] **Step 2: Call endpoint via curl**

```bash
curl -s -H "X-Api-Key: local-dev-key-change-me" \
  "http://fungsionalpro.test/api/v1/master-jf?page=1&per_page=3" | jq .
```

Expected: JSON with `total_filtered`, `agregasi`, `data` array.

- [ ] **Step 3: Run full test suite for API files**

Run: `lerd php artisan test tests/Feature/Api tests/Feature/Services/MasterJfAggregateServiceTest.php tests/Feature/Http/Middleware/VerifyApiKeyTest.php`

Expected: all PASS

- [ ] **Step 4: Share with superapps team**

Provide:
- Base URL (staging/production)
- Header `X-Api-Key`
- OpenAPI URL `/docs/api.json`
- Example: `GET /api/v1/master-jf?page=1&per_page=20&status=active`

---

## Spec coverage checklist

| Spec requirement | Task |
|---|---|
| `GET /api/v1/master-jf` | Task 1 bootstrap, Task 4 route + controller |
| `X-Api-Key` auth | Task 2 |
| `SUPERAPPS_API_KEY` env | Task 1 (`.env.example`, `.env.template`, `deployment/fungsionalpro.env`) |
| Full filter set incl. `jenjang` | Task 3 service + Task 4 request |
| Jabatan fungsional fallback | Task 1 `MasterJfDisplay::resolveJabatanFungsional`, Task 4 resource |
| Jenjang from `jabatan` | Task 1 `MasterJfDisplay`, Task 4 resource |
| Filter `c_role_level_id` → jabatan LIKE | Task 3 service test |
| Filter `provinsi` only when FK null | Task 3 service test |
| Auth ping route for middleware tests | Task 1 `/api/v1/ping`, Task 2 tests |
| Province hybrid | Task 1 model + test trait, Task 3 filter, Task 4 resource |
| No JsonResource double-wrap | Task 4 `MasterJfIndexResource::$wrap = null` |
| Test schema for `reg_provinces` / `province_id` | Task 1 `EnsuresMasterJfApiSchema` (PHPUnit only) |
| No DB migrations for this feature | Schema policy section — read-only API on existing restore |
| Aggregations on filtered set | Task 3 |
| Pagination metadata | Task 3 + Task 4 |
| Rate limit 60/min | Task 1 route group |
| Scramble docs | Task 5 |
| Feature tests | Tasks 2–4 |
| `province()` on MasterJf | Task 1 |

## Out of scope (confirmed)

- OAuth2/JWT, write endpoints, monorepo, regional row scoping, Redis caching
- **New Laravel migrations** for `master_jf.province_id` or `reg_provinces` — columns/tables already exist in prod/dev restore
- Refactoring Filament to use `MasterJfDisplay` (optional follow-up; API uses helper first)
- `province_id` backfill — not needed; hybrid read handles null FK via `provinsi` text

---

## Plan self-review (2026-08-14, revised)

| Check | Result |
|---|---|
| Spec coverage | All endpoint, auth, filter, aggregation, and data-source rules mapped to Tasks 1–6 |
| Placeholder scan | No TBD/TODO steps |
| Type consistency | `MasterJfAggregateService::paginate()` return shape used consistently in controller + resources |
| Test ordering | Task 2 uses `/api/v1/ping` (Task 1); full endpoint tests in Task 4 |
| Schema safety | No migrations; test trait mirrors `MasterJfModelTest` pattern; API is read-only |
| Implementation | Tasks 1–5 done in working tree; commits deferred per team request |

---

## Manual verification (Task 6)

1. Set `SUPERAPPS_API_KEY` in local `.env`, run `lerd php artisan config:clear`
2. **Do not migrate** — use existing restored DB
3. `curl -H "X-Api-Key: …" "http://fungsionalpro.test/api/v1/master-jf?per_page=3"`
4. Open `/docs/api` for Scramble UI
5. Run: `lerd php artisan test tests/Feature/Api tests/Feature/Services/MasterJfAggregateServiceTest.php tests/Feature/Http/Middleware/VerifyApiKeyTest.php tests/Unit/Support/MasterJfDisplayTest.php`
