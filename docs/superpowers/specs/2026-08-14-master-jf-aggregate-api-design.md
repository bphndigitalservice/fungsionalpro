# Master JF Aggregate API

**Date:** 2026-08-14  
**Status:** Approved for planning  
**Resource:** `MasterJf` HTTP API (new)

## Goal

Expose a token-authenticated JSON endpoint that returns Master JF **counts** for the filtered set: a total plus breakdowns by status, status kepegawaian, and golongan/ruang. Callers may apply every Master JF list filter plus the same global search as the Filament table.

## Context

- This Laravel 12 + Filament app has **no** HTTP API today (`bootstrap/app.php` registers web, console, and `/up` only). There is no Sanctum/Passport.
- `MasterJfResource` already filters with AboveContent selects and a global search. Header widgets aggregate via `getPageTableQuery()`.
- Widgets and this API are **not** required to match pixel-for-pixel: widgets still show all enum cards (including zeros) and only the top 6 golongan. The API returns only groups with `count > 0` and **every** golongan present in the filtered set.
- `MasterJfPolicy::viewAny` already names the roles allowed to see Master JF: Admin, SuperAdmin, Verifier, AdminRegional, AdminPusat, AdminInstansi. The API reuses that gate. It does **not** add instansi-level row scoping (the policy does not scope `viewAny` today).
- `config/cors.php` already allows `api/*` from any origin with `supports_credentials: false`, which fits Bearer tokens.

## Approach

**Approach 1 (chosen):** Dedicated `GET /api/master-jf/aggregates` with a Form Request and a query object that applies filters/search to `MasterJf::query()`, then runs `COUNT(*)` plus three `GROUP BY`s. Authenticate with Laravel Sanctum personal access tokens. No Filament token UI in this work.

Rejected:

- Drive the API from the Filament table query — couples HTTP to Livewire/Filament and is brittle for an external caller.
- One SQL JSON aggregate blob — harder to test and change; little benefit at this volume.

## Architecture

```text
External client
  Authorization: Bearer <sanctum personal access token>
        │
        ▼
GET /api/master-jf/aggregates  + query filters + optional q
        │
        ├── middleware: auth:sanctum
        └── authorize: MasterJfPolicy::viewAny
        │
        ▼
MasterJfAggregateController
        │
        ▼
MasterJfAggregateRequest     (validate filters + q)
        │
        ▼
MasterJfAggregateQuery
        ├── MasterJf::query() + filters + search
        ├── COUNT(*)                         → total
        ├── GROUP BY status                  → by_status
        ├── GROUP BY status_kepegawaian      → by_status_kepegawaian
        └── GROUP BY reg_grade_id            → by_gol_ruang
                    (+ lookup grade_code)
        │
        ▼
JSON { total, by_status, by_status_kepegawaian, by_gol_ruang }
```

Filament widgets, import, and matching stay unchanged.

## Components

### Sanctum bootstrap (first API in this app)

- Add `laravel/sanctum` and publish its config + `personal_access_tokens` migration.
- Register `routes/api.php` from `bootstrap/app.php` (default `/api` prefix).
- `User` uses `Laravel\Sanctum\HasApiTokens`.
- Tokens are created in code/tinker (`$user->createToken('name')->plainTextToken`). No Filament screen, no Sanctum abilities/scopes. Any valid personal access token is enough; authorization is the policy.
- Do **not** enable Sanctum SPA cookie/session auth for this endpoint. Callers send `Authorization: Bearer <token>` only.

### HTTP

| Piece | Location |
| --- | --- |
| Route | `routes/api.php` → `GET /api/master-jf/aggregates` named `api.master-jf.aggregates` |
| Controller | `App\Http\Controllers\Api\MasterJfAggregateController` — thin: authorize, call query object, return JSON |
| Form Request | `App\Http\Requests\MasterJfAggregateRequest` |
| Query object | `App\Queries\MasterJfAggregateQuery` |

No API Resource class: the payload is a fixed aggregate shape, not a model.

## Request

`GET /api/master-jf/aggregates`

Omitted params mean “no filter on that field”. Empty string `q` and empty arrays for multi-selects are treated as omitted. Filters combine with **AND**. Search is **OR** across searchable columns, then **AND** with filters.

| Param | Cardinality | Validation |
| --- | --- | --- |
| `status` | single | `ClientStatus` backed value |
| `status_kepegawaian` | single | `JenisKepegawaian` backed value (`PNS`, `PPPK`) |
| `pengangkatan` | single | key from `MasterJf::pengangkatanOptions()` |
| `type` | single | `ClientCluster` backed value |
| `reg_grade_id` | single | integer, must exist in `reg_grades` |
| `c_role_level_id` | single | integer, must exist in `c_role_levels` |
| `c_role_id` | single | integer, must exist in `c_roles` |
| `instansi` | multi | array of non-empty strings; query as `instansi[]=` |
| `unit_kerja` | multi | array of non-empty strings |
| `jabatan` | multi | array of non-empty strings |
| `q` | search | optional string, max 255 chars |

### Search (`q`)

Contains match (`LIKE %q%`, same as Filament column search) on the same fields the Master JF table marks searchable:

- Columns: `nama`, `nip`, `jabatan`, `unit_kerja`, `instansi`, `type`, `pengangkatan`, `status`, `status_kepegawaian`
- Relations: `grade.grade_code`, `cRole.role_name`, `cRoleLevel.level`

Enum/string columns are matched on **stored values**, not Filament labels (same as the table search).

## Response

`200`:

```json
{
  "total": 42,
  "by_status": [
    { "key": "active", "label": "Aktif", "count": 40 }
  ],
  "by_status_kepegawaian": [
    { "key": "PNS", "label": "PNS", "count": 42 }
  ],
  "by_gol_ruang": [
    { "key": 12, "label": "III/a", "count": 30 },
    { "key": null, "label": "Tidak diketahui", "count": 2 }
  ]
}
```

Rules:

- `total` is the filtered row count (integer ≥ 0).
- Breakdown arrays include **only** groups with `count > 0`.
- Null / empty `status`, `status_kepegawaian`, or `reg_grade_id` collapse into one bucket: `"key": null`, `"label": "Tidak diketahui"`.
- `by_status.key` and `by_status_kepegawaian.key` are enum backed values (string) or `null`.
- `by_gol_ruang.key` is `reg_grade_id` (integer) or `null`. Label is `RegGrade.grade_code`, or `"Tidak diketahui"` when the id is null. If a non-null `reg_grade_id` has no matching `RegGrade` row, label is `"Grade #{id}"` (same fallback as the widget).
- `by_status` and `by_status_kepegawaian` are ordered by enum case order among keys that are present; `"Tidak diketahui"` last if present.
- `by_gol_ruang` is ordered by `count` descending, then `label` ascending; `"Tidak diketahui"` sorts by count like any other row.
- No Master JF row fields (nama, nip, etc.) appear in the payload.

Empty filtered set: `{ "total": 0, "by_status": [], "by_status_kepegawaian": [], "by_gol_ruang": [] }`.

## Errors

| Case | Status | Body |
| --- | --- | --- |
| Missing or invalid Bearer token | 401 | Laravel/Sanctum default unauthenticated JSON |
| Authenticated user fails `MasterJfPolicy::viewAny` | 403 | Laravel default JSON |
| Invalid filter (`status` not in enum, non-integer id, unknown FK, `instansi` not an array of strings, `q` too long, etc.) | 422 | `{ "message", "errors" }` |

Unknown but well-typed filter values that simply match no rows (e.g. a valid `c_role_id` with zero Master JF rows) are **200** with `total: 0`, not 422. FK `exists` rules still 422 on ids that are not in the referenced table.

Do not add a custom CORS config in this work; existing `api/*` + `allowed_origins: *` is enough for Bearer callers.

## Out of scope

- Filament UI to create/revoke tokens
- Sanctum token abilities/scopes
- Changing Master JF widgets, list filters, import, or matching
- CRUD / list API of Master JF **rows**
- Instansi-scoped aggregation beyond `viewAny`
- Charts, CSV export, caching, or extra rate limits beyond Laravel’s default API group

## Testing

Feature tests (`tests/Feature/Api/MasterJfAggregateTest.php`), `RefreshDatabase`, Sanctum token via `$user->createToken('test')->plainTextToken`. Do not use Livewire.

1. No token → 401.
2. Client-role user with a token → 403.
3. Admin token, no filters → `total` equals seeded Master JF count.
4. `status=active` → `total` and `by_status` match only those rows; other statuses omitted.
5. `instansi[]` multi-value → counts only those instansi.
6. `q` matches `nama`; a second assertion matches a related searchable field (e.g. `grade.grade_code`).
7. Filter AND `q` together.
8. Invalid `status` → 422.
9. Filters that match nothing → `total: 0` and empty breakdown arrays.
10. Rows with null `reg_grade_id` → `by_gol_ruang` includes `{ "key": null, "label": "Tidak diketahui" }`.
11. Breakdowns omit zero-count enum values.

Existing Filament Master JF tests stay as they are.

## Success criteria

- An allowed user can call `GET /api/master-jf/aggregates` with a Sanctum PAT and receive total + three breakdowns for the filtered set.
- All Master JF list filters and table search are available as query params.
- Unauthorized callers get 401/403; bad input gets 422.
- Filament Master JF list/widgets remain unchanged.
