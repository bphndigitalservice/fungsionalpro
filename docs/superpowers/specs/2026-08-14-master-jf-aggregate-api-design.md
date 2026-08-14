# Master JF Aggregate API — Superapps Integration

**Date:** 2026-08-14  
**Status:** Approved for planning  
**Branch:** `feat/master-jf-aggregate-api`  
**Scope:** Read-only REST API exposing paginated Master JF data with filter-aware aggregations for superapps frontend integration.

## Goal

Provide a single API endpoint that lets the superapps frontend team integrate Master JF statistics and records without using the Filament admin panel. Response shape mirrors the OBH aggregation pattern: pagination metadata, an `agregasi` summary block computed over the full filtered set, and a paginated `data` array.

## Context

- **Current state:** No API layer exists (`routes/api.php` missing, no Sanctum/Swagger). App is Filament-only (~110 web routes).
- **Data source:** `master_jf` table (958 rows in dev restore).
- **Existing UI logic:** Filament widgets (`MasterJfNumbersByStatusOverview`, etc.) already aggregate via `getPageTableQuery()` + `groupBy` — same pattern applies to the API service layer.
- **Consumer:** Superapps frontend team (machine-to-machine, not end-user Filament login).
- **Repo strategy:** Single Laravel application — **not** a monorepo or separate microservice.

## Requirements (decided)

| Topic | Decision |
|---|---|
| Endpoint | `GET /api/v1/master-jf` (single endpoint) |
| Auth | Static API key via header `X-Api-Key` |
| Env var | `SUPERAPPS_API_KEY` |
| HTTP methods | GET only (read-only) |
| Filters | Full set: search, c_role_id, c_role_level_id, **jenjang**, reg_grade_id, province_id, provinsi, pengangkatan, status, status_kepegawaian, type |
| Province | Hybrid: canonical `province_id` → `reg_provinces.name`; fallback text column `provinsi` when FK null |
| Docs | Scramble (auto OpenAPI) at `/docs/api` |
| Rate limit | 60 requests/minute per API key |

## Data source audit (running app — dev DB restore, 958 rows)

Trace of how Filament and import **actually** resolve display values today. API must follow this, not assumed FK-only logic.

| Field | Filament / import source | FK fill rate (dev) | API resolution rule |
|---|---|---|---|
| **Jabatan Fungsional** | Table: `cRole.role_name`. Import: hardcoded from `jabatan` text (`Analis Hukum` → id 1, `Penyuluh Hukum` → id 2) | `c_role_id` **958/958** | `cRole.role_name`; if FK null, infer from `jabatan` like `MasterJfImport` |
| **Jenjang** | Table: **parsed from `jabatan`** via regex — **not** `c_role_level_id`. Filter "Jenjang": `jabatan LIKE %Ahli …%`. Widget counts same pattern. Form allows `c_role_level_id` select but import never sets it | `c_role_level_id` **0/958** | Parse `jabatan` (same regex as Filament). Normalize known labels to title case (`Ahli Pertama`, …). Use `cRoleLevel.level` only when FK is set (manual edit path) |
| **Golongan/Ruang** | Table: `grade.clean_name` (`RegGrade` accessor). Import: `RegGradeResolver::resolveId(golruang)`. Backfill migration fills from `gol_ruang` text | `reg_grade_id` **805/958** (73 rows still have `gol_ruang` but no FK) | `grade.clean_name` / `grade.grade_name` via relation; null when unmapped |
| **Provinsi** | Table + filters: **text column `provinsi`** via `MasterJf::distinctOptions('provinsi')`. Import sets `provinsi` only — **not** `province_id`. `province_id` exists in prod backup (884 rows) but **no repo migration/backfill**; values align with `reg_provinces.name` | `province_id` **884/958**, `provinsi` **884/958** (always paired in dev) | Response: `reg_provinces.name` when `province_id` set; else trimmed `provinsi`. Filter `province_id`: exact FK. Filter `provinsi`: text column only when `province_id` IS NULL |

**Code references:**

- Jenjang display: `MasterJfResource` — `preg_replace('/^(Penyuluh Hukum|Analis Hukum)\s+/i', '', $record->jabatan)`
- Import: `app/Imports/MasterJfImport.php` — sets `c_role_id`, `reg_grade_id`, `provinsi`; does **not** set `c_role_level_id` or `province_id`
- Grade resolver: `app/Support/RegGradeResolver.php`

Shared helper (new): `app/Support/MasterJfDisplay.php` — centralize jenjang parse + title-case normalization so API and Filament stay aligned.

## Foreign key relationships

| `master_jf` column | Related table | Display field(s) | Filament label | Reliable today? |
|---|---|---|---|---|
| `c_role_id` | `c_roles` | `role_name` | Jabatan Fungsional | Yes (100%) |
| `c_role_level_id` | `c_role_levels` | `level` | Jenjang (form only) | **No** — use `jabatan` parse for display/filter |
| `reg_grade_id` | `reg_grades` | `grade_code`, `grade_name`, `clean_name` | Golongan/Ruang | Mostly (84%) |
| `province_id` | `reg_provinces` | `name` | *(not used in Filament table)* | Yes when set; Filament ignores FK |
| `provinsi` | *(text column)* | raw string | Provinsi | Yes for filter/display in admin |

Non-FK columns used in filters and aggregations:

- `pengangkatan` — CPNS, Inpassing, PDJL, Penyetaraan, etc.
- `status` — `ClientStatus` enum (`active`, `non_active_ctln`, …)
- `status_kepegawaian` — `JenisKepegawaian` enum (`PNS`, `PPPK`)
- `type` — `ClientCluster` enum (`central`, `local_province`, `local_regency`)

**Model update (code only, no migration):** Add `province(): BelongsTo` → `RegProvince` on existing `province_id` column; add `province_id` to `$fillable`. DB restore already has the column — API does not run migrations or mutate rows.

## Approach

**Approach 1 (chosen):** Single endpoint in the existing Laravel app.

```
GET /api/v1/master-jf?page=1&per_page=20&province_id=11
```

One response contains pagination + `agregasi` + `data`.

**Rejected alternatives:**

- **Split `/stats` + `/list` endpoints** — forces superapps to sync filters across two calls.
- **Monorepo / separate API service** — duplicates models, migrations, and auth for no benefit at this scale.

## Architecture

```text
Superapps Frontend
    │  GET /api/v1/master-jf?...
    │  Header: X-Api-Key
    ▼
VerifyApiKey middleware
    ▼
MasterJfController@index
    ▼
MasterJfAggregateService
    ├── buildFilteredQuery(MasterJfIndexRequest)
    ├── computeAggregations(Builder)   ← full filtered set
    └── paginate(Builder, page, per_page)
    ▼
MasterJfIndexResource (JSON)
```

### File structure (new)

| File | Responsibility |
|---|---|
| `routes/api.php` | API route registration |
| `bootstrap/app.php` | Register `api` routes |
| `app/Http/Middleware/VerifyApiKey.php` | Validate `X-Api-Key` against env |
| `app/Http/Controllers/Api/V1/MasterJfController.php` | HTTP entry point |
| `app/Http/Requests/Api/V1/MasterJfIndexRequest.php` | Query param validation |
| `app/Http/Resources/Api/V1/MasterJfIndexResource.php` | Top-level response wrapper |
| `app/Http/Resources/Api/V1/MasterJfItemResource.php` | Single row in `data[]` |
| `app/Services/MasterJfAggregateService.php` | Filter query + aggregation logic |
| `app/Models/MasterJf.php` | Add `province()` relationship |
| `tests/Feature/Api/V1/MasterJfIndexTest.php` | Feature tests |
| `config/scramble.php` | OpenAPI docs config (published) |

**Unchanged:** Filament resources, widgets, production deployment stack.

## Endpoint specification

### Request

```
GET /api/v1/master-jf
```

**Required headers:**

```
X-Api-Key: {SUPERAPPS_API_KEY}
Accept: application/json
```

**Query parameters (all optional):**

| Parameter | Type | Default | Rules |
|---|---|---|---|
| `page` | integer | `1` | min 1 |
| `per_page` | integer | `20` | min 1, max 100 |
| `search` | string | — | max 255; matches `nama`, `nip`, `instansi`, `unit_kerja` (LIKE) |
| `c_role_id` | integer | — | exists:c_roles,id |
| `c_role_level_id` | integer | — | exists:c_role_levels,id; resolves `c_role_levels.level` then filters `jabatan LIKE` (FK column is empty in prod data) |
| `jenjang` | string | — | max 255; matches `jabatan LIKE` (same as Filament Jenjang filter) |
| `reg_grade_id` | integer | — | exists:reg_grades,id |
| `province_id` | integer | — | exists:reg_provinces,id |
| `provinsi` | string | — | max 255; matches text column **only when** `province_id` IS NULL |
| `pengangkatan` | string | — | max 255 |
| `status` | string | — | valid `ClientStatus` enum value |
| `status_kepegawaian` | string | — | valid `JenisKepegawaian` enum value |
| `type` | string | — | valid `ClientCluster` enum value |

All filters combine with AND logic. Aggregations reflect the full filtered queryset, not only the current page.

### Response `200 OK`

```json
{
  "total_filtered": 958,
  "page": 1,
  "per_page": 20,
  "total_pages": 48,
  "agregasi": {
    "total_jf": 958,
    "by_status": {
      "active": 32,
      "non_active_study_leave": 2,
      "unknown": 924
    },
    "by_status_kepegawaian": {
      "PNS": 500,
      "PPPK": 100,
      "unknown": 358
    },
    "by_pengangkatan": {
      "Penyetaraan": 31,
      "Inpassing": 26,
      "PDJL": 7,
      "unknown": 887
    },
    "by_kluster": {
      "central": 10,
      "local_province": 200,
      "local_regency": 150,
      "unknown": 598
    },
    "by_jabatan_fungsional": {
      "Penyuluh Hukum": 500,
      "Analis Hukum": 458
    }
  },
  "data": [
    {
      "id": 1,
      "nama": "John Doe",
      "nip": "199001012020011001",
      "jabatan": "Analis Hukum Ahli Muda",
      "jabatan_fungsional": "Analis Hukum",
      "jenjang": "Ahli Muda",
      "golongan_ruang": "Penata Muda - III/a",
      "golongan_ruang_code": "IIIa",
      "instansi": "Kejaksaan Agung",
      "unit_kerja": "BPHN",
      "provinsi_id": 11,
      "provinsi": "ACEH",
      "kluster": "Kementerian Lembaga",
      "kluster_code": "central",
      "pengangkatan": "Penyetaraan",
      "status": "Aktif",
      "status_code": "active",
      "status_kepegawaian": "PNS"
    }
  ]
}
```

**Field rules for `data[]`:**

- `jabatan_fungsional`: `MasterJfDisplay::resolveJabatanFungsional($row)` — `cRole.role_name` with import-style fallback from `jabatan`.
- `jenjang`: `MasterJfDisplay::resolveJenjang($row)` — parse from `jabatan` when `c_role_level_id` is null; honor `cRoleLevel.level` when FK is set (manual edit).
- `provinsi`: use `reg_provinces.name` when `province_id` is set; otherwise use trimmed `provinsi` text column.
- Enum-backed fields expose human label + machine `*_code` value.
- `golongan_ruang_code`: use existing `RegGrade::clean_name` accessor via `grade` relation; null when `reg_grade_id` unset.
- Eager-load `cRole`, `cRoleLevel`, `grade`, `province` to avoid N+1.

**Aggregation rules:**

- `total_jf` equals `total_filtered`.
- Breakdown keys use enum values where applicable; `unknown` counts null/empty/unmapped values.
- `by_jabatan_fungsional` groups on `c_roles.role_name` via join (null → `unknown`).
- Aggregations run on the same filtered base query as pagination (no page offset in aggregates).

## Authentication

### Middleware: `VerifyApiKey`

1. Read header `X-Api-Key`.
2. Compare to `config('services.superapps.api_key')` sourced from `SUPERAPPS_API_KEY` env.
3. Missing or mismatch → `401 Unauthorized` with `{ "message": "Unauthorized" }`.
4. Constant-time comparison (`hash_equals`) to prevent timing attacks.

Register alias in `bootstrap/app.php`:

```php
$middleware->alias([
    'verify.api.key' => \App\Http\Middleware\VerifyApiKey::class,
]);
```

### Route group

```php
Route::prefix('v1')
    ->middleware(['verify.api.key', 'throttle:60,1'])
    ->group(function () {
        Route::get('master-jf', [MasterJfController::class, 'index']);
    });
```

API routes are prefixed `/api` by Laravel automatically → full path `/api/v1/master-jf`.

## Error handling

| HTTP | Condition | Body |
|---|---|---|
| 401 | Missing/invalid API key | `{ "message": "Unauthorized" }` |
| 422 | Invalid query params | Laravel validation JSON |
| 429 | Rate limit exceeded | `{ "message": "Too Many Requests" }` |
| 500 | Unhandled exception | `{ "message": "Server Error" }` (production) |

## Documentation (Scramble)

Install `dedoc/scramble` and publish config. Scramble auto-discovers:

- Route `GET /api/v1/master-jf`
- `MasterJfIndexRequest` validation rules
- `MasterJfIndexResource` response shape

Expose:

- UI: `/docs/api`
- JSON: `/docs/api.json`

Protect docs in production via middleware or `SCRAMBLE_ENABLED=false` if required.

## Environment

Add to `.env.example`, `.env.template`, `deployment/fungsionalpro.env`, and runtime `.env`:

```env
SUPERAPPS_API_KEY=
```

Register in `config/services.php`:

```php
'superapps' => [
    'api_key' => env('SUPERAPPS_API_KEY'),
],
```

**Never commit real API keys.** Rotate key by updating env on both FungsionalPro and superapps gateway.

## Testing

File: `tests/Feature/Api/V1/MasterJfIndexTest.php`

| Test | Assertion |
|---|---|
| No API key | 401 |
| Wrong API key | 401 |
| Valid key | 200 + JSON structure keys present |
| Filter `c_role_id` | `total_filtered` decreases appropriately |
| Filter `search` | matches nama/nip |
| `per_page=101` | 422 |
| Aggregations | `agregasi.total_jf` equals `total_filtered` |
| Province hybrid | row with `province_id` returns `reg_provinces.name`; null FK uses `provinsi` text |
| Jenjang from jabatan | row with null `c_role_level_id` still returns parsed jenjang (e.g. `"Ahli Muda"`) |
| Filter `jenjang` | matches Filament — filters via `jabatan LIKE` |
| Filter `c_role_level_id` | resolves level name → `jabatan LIKE` when FK column unused |
| Pagination | `data` count ≤ `per_page`; `total_pages` correct |

Use `MasterJf::factory()` and existing reference seeders/factories where available.

## Security notes

- Read-only GET — no mutation endpoints in v1.
- Rate limit 60/min prevents abuse from leaked keys.
- API key is service-level (not per-user) — acceptable for internal superapps integration.
- Do not expose Filament session auth on the same route group.
- Log 401/429 at `warning` level without logging the provided key value.

## Out of scope (v1)

- OAuth2 / JWT / per-client API keys in database
- Write endpoints (create/update/delete Master JF)
- Monorepo or separate deployable API package
- User-level authorization (admin vs regional scoping) — v1 returns all Master JF rows matching filters; row-level scoping can be added in v2 if superapps requires it
- Caching layer (Redis) — defer until performance testing shows need
- Normalization migration for `province_id` backfill — not needed; column already populated in prod/dev restore; hybrid read covers gaps
- New Laravel migrations for `master_jf` / `reg_provinces` as part of this API — schema exists; PHPUnit uses `EnsuresMasterJfApiSchema` trait only

## Future considerations (v2+)

- Multiple API keys with scopes stored in database
- Regional scoping mirroring `MasterJfResource::getEloquentQuery()` admin access rules
- Dedicated `/api/v1/master-jf/filters` metadata endpoint (distinct filter option values)
- Response caching keyed by filter hash
