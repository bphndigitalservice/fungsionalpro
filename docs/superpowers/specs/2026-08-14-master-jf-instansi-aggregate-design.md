# Master JF API — Instansi Aggregate & Filter Refinement

**Date:** 2026-08-14  
**Status:** Approved for planning  
**Branch:** `feat/master-jf-aggregate-api`  
**Scope:** Extend grouped Master JF API with per-instansi aggregations and refine daerah/cluster filter behavior per superapps senior feedback.

## Goal

Enable the superapps frontend to:

1. Show **card-level aggregations** grouped by jenis JF × cluster (existing behavior).
2. **Drill down per instansi** as a simple list (`name`, `client_count`) under each card group.
3. Filter primarily by **daerah**, **jenis JF**, and **cluster**, while keeping secondary filters available.

## Context

- **Endpoint:** `GET /api/v1/master-jf` (unchanged).
- **Current response:** Groups by `jf_type_id × cluster_id` with group-level `aggregate` and instansi list (`name`, `client_count` only).
- **Senior feedback (2026-08-14):**
  - Filters focus on daerah, jenis JF, cluster.
  - Card section = aggregated data.
  - Visualize per instansi with its aggregation.

## Decisions (approved)

| Topic | Decision |
|---|---|
| Instansi aggregation | **Removed** — instansi items are `name` + `client_count` only; `aggregate` stays at group (card) level |
| Daerah filter | Hybrid: `province_id` (FK) + `provinsi` text fallback when FK null |
| Secondary filters | Keep all existing filters; frontend uses 3 primary filters |
| Cluster filter | Effective cluster via `MasterJfClusterResolver` (post-filter in PHP) |
| Daerah + K/L | Daerah applies to Pemda only; when `type=central`, daerah is ignored |
| Implementation approach | Post-filter PHP (Option 1) — reuse resolver, no SQL duplication |
| `instance_id` | Removed (not a DB ID; dropped in prior iteration) |

## Response shape

```json
{
  "data": [
    {
      "jf_type_id": 1,
      "jf_label": "Analis Hukum",
      "cluster_id": "local_province",
      "cluster_label": "Pemda - Provinsi",
      "aggregate": {
        "total_jf": 120,
        "by_jenjang": {
          "Ahli Pertama": 10,
          "Ahli Muda": 25,
          "Ahli Madya": 18,
          "Ahli Utama": 5,
          "unknown": 0
        },
        "by_status": { "active": 30, "unknown": 90 },
        "by_status_kepegawaian": { "PNS": 80, "PPPK": 20, "unknown": 20 },
        "by_pengangkatan": { "Penyetaraan": 15, "unknown": 105 }
      },
      "data": [
        {
          "name": "Pemerintah Daerah Provinsi Bali",
          "client_count": 45
        }
      ]
    }
  ]
}
```

### Field rules

- Group-level `aggregate`: computed over all rows in the JF type × cluster segment.
- Instansi-level items: `name` and `client_count` only (no nested `aggregate`).
- `client_count` on instansi items equals row count for that instansi within the group.
- Empty instansi text → grouped under `"unknown"`.
- Instansi list sorted alphabetically by `name` (case-insensitive).
- Only groups with at least one row are returned.

## Filter specification

### Primary filters (superapps UX)

| Filter | Parameter | Logic |
|---|---|---|
| Daerah | `province_id` / `provinsi` | Hybrid rules below |
| Jenis JF | `c_role_id` | Exact match on `master_jf.c_role_id` |
| Cluster | `type` | Effective cluster post-filter (not raw DB column) |

### Daerah filter rules

```
IF type filter = central:
    → ignore province_id and provinsi
    → return all K/L (central) rows matching other filters

ELSE IF province_id OR provinsi is set:
    → apply daerah filter to Pemda rows only (local_province, local_regency)
    → exclude K/L (central) rows from results
    → province_id: exact FK match on master_jf.province_id
    → provinsi: LIKE match on text column, only when province_id IS NULL

ELSE (no daerah filter):
    → return all rows (Pemda + K/L) matching other filters
```

### Examples

| Request | Expected result |
|---|---|
| `?province_id=11` | Pemda rows in Aceh only; no K/L |
| `?type=central` | All K/L nationally; daerah ignored |
| `?province_id=11&type=central` | K/L nationally (daerah ignored because cluster=central) |
| `?province_id=11&type=local_province` | Pemda province-level rows in Aceh only |
| (no filters) | All groups with data |

### Secondary filters (unchanged)

All combine with AND logic:

- `search` — nama, nip, instansi, unit_kerja (LIKE)
- `jenjang` — jabatan LIKE
- `c_role_level_id` — resolves level name → jabatan LIKE
- `reg_grade_id`, `pengangkatan`, `status`, `status_kepegawaian`

## Architecture

```text
GET /api/v1/master-jf?province_id=11&c_role_id=1&type=local_province
    │
    ▼
VerifyApiKey middleware
    │
    ▼
MasterJfController@index
    │
    ▼
MasterJfAggregateService::aggregate()
    ├── buildFilteredQuery()           SQL filters (daerah Pemda rules, c_role_id, secondary)
    ├── load rows + eager load cRole
    ├── resolve effective cluster      MasterJfClusterResolver per row
    ├── post-filter by type param      match effective cluster
    ├── group by jf_type_id × cluster_id
    ├── compute group aggregate
    └── compute instansi aggregate     NEW — per instansi within each group
    │
    ▼
MasterJfIndexResource → JSON
```

### Files to change

| File | Change |
|---|---|
| `app/Services/MasterJfAggregateService.php` | Daerah filter rules; effective cluster post-filter; instansi-level aggregate |
| `tests/Feature/Api/V1/MasterJfIndexTest.php` | Assert instansi aggregate structure; daerah/cluster filter cases |
| `tests/Feature/Services/MasterJfAggregateServiceTest.php` | Unit-level filter and aggregation tests |

### Files unchanged

- `MasterJfIndexRequest.php` — same query parameters
- `MasterJfIndexResource.php` — pass-through
- `MasterJfClusterResolver.php` — reuse as-is
- Routes, middleware, auth

## Cluster resolution (unchanged)

Effective cluster per row:

1. Use stored `type` if valid enum value.
2. Else fallback: `ClientMatchingService::determineAgencyInfo(instansi, unit_kerja)`.

Filter param `type` matches against this **resolved** cluster, not the raw DB column.

## Performance

- Prod dataset ~5k rows — in-memory post-filter is acceptable.
- Monitor if dataset exceeds ~50k; consider backfill `type` column or SQL-level cluster expression at that point.

## Testing

| Test | Assertion |
|---|---|
| Instansi aggregate structure | Each `data[].data[]` item has `aggregate` with all breakdown keys |
| `client_count` consistency | Equals `aggregate.total_jf` on same item |
| `?province_id=X` | Only Pemda in province X; no central groups |
| `?type=central` with province | Central groups returned; daerah ignored |
| `?type=local_province&province_id` | Pemda province in that daerah only |
| Effective cluster filter | Row with `type=null` included in correct cluster when filtered |
| Secondary filter regression | `search`, `jenjang` still narrow results correctly |
| Empty instansi | Grouped as `"unknown"` with valid aggregate |

## Out of scope

- New endpoints or pagination
- Removing secondary filters
- DB migration / backfill of `type` column
- Re-adding `instance_id`
- OpenAPI doc rewrite (Scramble auto-discovers; verify after implementation)

## Future considerations

- Dedicated `/api/v1/master-jf/filters` metadata endpoint for dropdown values (daerah list, jenis JF, cluster)
- SQL-level effective cluster filter if performance requires it
- Regional scoping per API key (v2)
