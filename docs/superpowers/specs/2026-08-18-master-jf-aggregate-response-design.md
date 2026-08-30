# Master JF aggregate API — response reshape

**Date:** 2026-08-18  
**Status:** Approved for planning  
**Scope:** Breaking reshape of `GET /api/v1/master-jf` JSON. Grouping still jenis JF × cluster. Instansi items expose Client-style morph as short `agency_type` + `agency_id`. Persistence of morph columns is **not** in this spec.

**Supersedes:** public API JSON rows in `docs/superpowers/specs/2026-08-18-master-jf-agency-polymorph-design.md` (that spec said instansi items stay `{ name, client_count }` and listed adding `agency_id` as out of scope). Schema, import, Filament, and matching stay in the morph spec.

## Goal

Give superapps a card payload keyed like the rest of the API (`c_role_*`, `cluster`) and instansi rows that join to `RegDepartment` / `RegProvince` / `RegRegency` without fuzzy name matching on the client.

## Context

- Endpoint and filters are already live. Groups use `jf_type_id` / `jf_label` / `cluster_id` (string enum) / `cluster_label` from `ClientCluster::getLabel()` (e.g. `"Pemda - Provinsi"`). Instansi items are `{ name, client_count }` grouped by raw `instansi` text.
- `clients` stores `agency_type` (FQCN) + `agency_id`. The morph spec persists the same pair on `master_jf`.
- `GET /api/v1/c-roles` already returns `{ id, name }` for jenis JF.

## Decisions (approved)

| Topic | Decision |
|---|---|
| Approach | Breaking reshape of the current endpoint (no dual keys, no new path) |
| JF fields | `c_role_id` / `c_role_label`; drop `jf_type_id` / `jf_label` |
| Cluster field | Response key `cluster` (`central` \| `local_province` \| `local_regency`); drop `cluster_id` |
| Query filter | Still `type` (same enum values). Do not rename the query param |
| Cluster labels | See map below (not `ClientCluster::getLabel()`) |
| Instansi identity | `agency_type` + `agency_id` + `name` + `client_count`. No `instance_id` |
| `agency_type` JSON | Short keys: `department` \| `province` \| `regency` \| `null`. Never FQCN |
| Morph source | Read persisted `master_jf.agency_type` / `agency_id` when present |
| Unmatched | One bucket per group: both morph fields `null`, `name` `"unknown"` |
| Unmatched vs text | Do not also group unmatched rows by raw `instansi` |
| `aggregate` | Unchanged shape and still group-level only; includes unmatched rows |
| Unknown item | Omit when `client_count` is 0 |

### `cluster_label` map

| `cluster` | `cluster_label` |
|---|---|
| `central` | Kementerian Lembaga |
| `local_province` | Pemerintah Daerah Provinsi |
| `local_regency` | Pemerintah Daerah Kabupaten/Kota |

### `agency_type` map (JSON ← stored FQCN)

| Stored `agency_type` | JSON |
|---|---|
| `App\Models\RegDepartment` | `department` |
| `App\Models\RegProvince` | `province` |
| `App\Models\RegRegency` | `regency` |
| anything else / incomplete morph | unmatched |

## Response shape

```json
{
  "data": [
    {
      "c_role_id": 1,
      "c_role_label": "Analis Hukum",
      "cluster": "local_province",
      "cluster_label": "Pemerintah Daerah Provinsi",
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
          "agency_type": "province",
          "agency_id": 51,
          "name": "Bali",
          "client_count": 45
        },
        {
          "agency_type": null,
          "agency_id": null,
          "name": "unknown",
          "client_count": 3
        }
      ]
    }
  ]
}
```

### Grouping

1. Segment rows by resolved JF type × effective cluster (`MasterJfClusterResolver`), same as today.
2. Linked: both morph columns set **and** related `agenciable` row exists → bucket key `(short agency_type, agency_id)`. `name` = related model `name`.
3. Else (null morph, one column only, unknown FQCN, dangling related): increment that group's unknown bucket.
4. `client_count` = row count in the bucket.
5. Sort linked items by `name` case-insensitive; unknown last if present.
6. Only groups with at least one row are returned.

## Architecture

```text
GET /api/v1/master-jf?...
  → VerifyApiKey
  → MasterJfIndexRequest (filters unchanged)
  → MasterJfAggregateService::aggregate()
  → MasterJfIndexResource → MasterJfGroupResource → MasterJfInstansiItemResource
```

| Unit | Change |
|---|---|
| `MasterJfAggregateService` | Eager-load morph; emit new group keys; bucket instansi by morph or unknown |
| Short-type helper | Map FQCN ↔ `department` / `province` / `regency` (one place, used by service/resource) |
| `MasterJfGroupResource` | New field names and `cluster_label` map |
| `MasterJfInstansiItemResource` | `agency_type`, `agency_id`, `name`, `client_count` |
| Controller Scramble examples | Match the new JSON |
| `MasterJfOpenApiDocumentTransformer` | Update `cluster` (was `cluster_id`) if it still patches that property |
| `EnsuresMasterJfApiSchema` | Ensure morph columns exist in SQLite tests if the morph migration is not on that path |
| Feature tests | Service + HTTP schema, grouping, labels, filters |

No new endpoint, no new aggregate service class, no Agencyable trait.

**Prerequisite:** `master_jf.agency_type` / `agency_id` and `MasterJf::agenciable()` from the morph work. This spec does not add those columns.

## Error handling

- 401 / 422: unchanged.
- Unmatched or dangling morph: HTTP 200; rows go to `"unknown"`.
- Unknown stored FQCN: unmatched, not a 500.
- Empty filter result: `{ "data": [] }`.

## Testing

PHPUnit on existing Master JF API/service tests.

1. Group keys are `c_role_id`, `c_role_label`, `cluster`, `cluster_label`, `aggregate`, `data`. Old keys absent.
2. Label map asserted for all three clusters.
3. Two rows, same morph, different `instansi` text → one item, `client_count` 2, short `agency_type`, `name` from related model.
4. Unmatched rows → one `"unknown"` item; `aggregate.total_jf` includes them.
5. Dangling `agency_id` → unknown, not a linked item.
6. `type=local_province` still filters by effective cluster.
7. Unknown item omitted when every row in the group is linked.

## Out of scope

- Persisting morph (migration, backfill, import, Filament, `ClientMatchingService`)
- Creating registry rows from unmatched names
- Renaming query param `type` to `cluster`
- Dual JSON keys or a second URL
- Instansi-level `aggregate`
- Echelon morph
