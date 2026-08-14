# Master JF Instansi Aggregate & Filter Refinement — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend `GET /api/v1/master-jf` so each instansi item includes a full `aggregate` block, and refine daerah/cluster filter behavior per senior superapps feedback.

**Architecture:** Keep single-endpoint grouped response (JF type × cluster). Reuse `MasterJfClusterResolver` for effective cluster. Move `type` filter from SQL to PHP post-filter. Apply Pemda-only daerah rules in SQL + PHP exclusion of central rows. Reuse `computeSliceAggregationsFromCollection()` for instansi-level aggregates.

**Tech Stack:** Laravel 12, PHP 8.2+, PHPUnit 11, SQLite in-memory tests (`RefreshDatabase`), existing `MasterJfClusterResolver`

## Global Constraints

- Spec: `docs/superpowers/specs/2026-08-14-master-jf-instansi-aggregate-design.md`
- Branch: `feat/master-jf-aggregate-api`
- Endpoint: `GET /api/v1/master-jf` (read-only, no schema migrations)
- Auth: `X-Api-Key` → `SUPERAPPS_API_KEY`
- No re-add `instance_id`
- Secondary filters unchanged (`search`, `jenjang`, `status`, etc.)
- Instansi `client_count` must equal `aggregate.total_jf`
- Conventional commits in English; commit after each task (if user requests commits)
- Run tests with: `php artisan test <paths>`

## Plan revisions (post-review, 2026-08-14)

- **Task 3 Step 4:** Fixed daerah SQL — single `where(function)` with OR logic (`province_id` match OR null-FK + `provinsi` text), not stacked AND blocks.
- **Task 1:** Added explicit test for empty instansi → `"unknown"` with aggregate.
- **Task 3:** Added test for combined `type=local_province&province_id`.
- **Task 4:** Added `RegProvince` import requirement and combined-filter API test.

---

## File structure

| File | Responsibility |
| --- | --- |
| `app/Services/MasterJfAggregateService.php` | Instansi aggregate, daerah rules, effective cluster post-filter |
| `tests/Feature/Services/MasterJfAggregateServiceTest.php` | Service-layer tests for new behavior |
| `tests/Feature/Api/V1/MasterJfIndexTest.php` | End-to-end API tests for new behavior |

**Unchanged:** `MasterJfIndexRequest.php`, `MasterJfIndexResource.php`, `MasterJfClusterResolver.php`, routes, middleware.

---

### Task 1: Per-instansi aggregate in response

**Files:**
- Modify: `app/Services/MasterJfAggregateService.php` (`buildInstansiListFromCollection`)
- Modify: `tests/Feature/Services/MasterJfAggregateServiceTest.php`
- Modify: `tests/Feature/Api/V1/MasterJfIndexTest.php`

**Interfaces:**
- Consumes: `computeSliceAggregationsFromCollection(Collection $rows): array` (existing, protected)
- Produces: `buildInstansiListFromCollection()` returns `list<array{name: string, client_count: int, aggregate: array}>`

- [ ] **Step 1: Write the failing service test**

Add to `tests/Feature/Services/MasterJfAggregateServiceTest.php`:

```php
public function test_instansi_items_include_full_aggregate(): void
{
    $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);

    MasterJf::factory()->create([
        'c_role_id' => $role->id,
        'status' => ClientStatus::Active,
        'type' => ClientCluster::Central,
        'instansi' => 'KEMENTERIAN HUKUM',
        'jabatan' => 'Analis Hukum Ahli Muda',
    ]);
    MasterJf::factory()->create([
        'c_role_id' => $role->id,
        'status' => ClientStatus::Active,
        'type' => ClientCluster::Central,
        'instansi' => 'KEMENTERIAN HUKUM',
        'jabatan' => 'Analis Hukum Ahli Utama',
    ]);
    MasterJf::factory()->create([
        'c_role_id' => $role->id,
        'type' => ClientCluster::Central,
        'instansi' => 'KEMENTERIAN AGAMA',
        'jabatan' => 'Analis Hukum Ahli Pertama',
    ]);

    $result = app(MasterJfAggregateService::class)->aggregate([]);

    $this->assertCount(1, $result['data']);
    $instansi = $result['data'][0]['data'];

    $this->assertCount(2, $instansi);

    $kemenkumham = collect($instansi)->firstWhere('name', 'KEMENTERIAN HUKUM');
    $this->assertNotNull($kemenkumham);
    $this->assertSame(2, $kemenkumham['client_count']);
    $this->assertSame(2, $kemenkumham['aggregate']['total_jf']);
    $this->assertSame(1, $kemenkumham['aggregate']['by_jenjang']['Ahli Muda']);
    $this->assertSame(1, $kemenkumham['aggregate']['by_jenjang']['Ahli Utama']);
    $this->assertArrayHasKey('by_status', $kemenkumham['aggregate']);
    $this->assertArrayHasKey('by_status_kepegawaian', $kemenkumham['aggregate']);
    $this->assertArrayHasKey('by_pengangkatan', $kemenkumham['aggregate']);
}

public function test_empty_instansi_groups_as_unknown_with_aggregate(): void
{
    MasterJf::factory()->create([
        'instansi' => '',
        'type' => ClientCluster::Central,
        'jabatan' => 'Analis Hukum Ahli Muda',
    ]);

    $result = app(MasterJfAggregateService::class)->aggregate([]);

    $unknown = collect($result['data'][0]['data'])->firstWhere('name', 'unknown');
    $this->assertNotNull($unknown);
    $this->assertSame(1, $unknown['client_count']);
    $this->assertSame(1, $unknown['aggregate']['total_jf']);
    $this->assertArrayHasKey('by_jenjang', $unknown['aggregate']);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Services/MasterJfAggregateServiceTest.php --filter="test_instansi_items_include_full_aggregate|test_empty_instansi_groups_as_unknown_with_aggregate"`

Expected: FAIL — `aggregate` key missing on instansi items

- [ ] **Step 3: Implement instansi-level aggregate**

Replace `buildInstansiListFromCollection()` in `app/Services/MasterJfAggregateService.php`:

```php
/** @param  Collection<int, MasterJf>  $rows
 * @return list<array{name: string, client_count: int, aggregate: array<string, mixed>}>
 */
protected function buildInstansiListFromCollection(Collection $rows): array
{
    /** @var array<string, Collection<int, MasterJf>> $grouped */
    $grouped = [];

    foreach ($rows as $row) {
        $name = trim((string) ($row->instansi ?? ''));
        if ($name === '') {
            $name = 'unknown';
        }

        $grouped[$name] ??= collect();
        $grouped[$name]->push($row);
    }

    $instansi = [];

    foreach ($grouped as $name => $instansiRows) {
        $aggregate = $this->computeSliceAggregationsFromCollection($instansiRows);

        $instansi[] = [
            'name' => $name,
            'client_count' => $aggregate['total_jf'],
            'aggregate' => $aggregate,
        ];
    }

    usort($instansi, fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

    return $instansi;
}
```

- [ ] **Step 4: Update existing API structure test**

In `tests/Feature/Api/V1/MasterJfIndexTest.php`, change instansi structure assertion from:

```php
'*' => ['name', 'client_count'],
```

to:

```php
'*' => [
    'name',
    'client_count',
    'aggregate' => [
        'total_jf',
        'by_jenjang',
        'by_status',
        'by_status_kepegawaian',
        'by_pengangkatan',
    ],
],
```

Add assertion:

```php
->assertJsonPath('data.0.data.0.client_count', 2)
->assertJsonPath('data.0.data.0.aggregate.total_jf', 2)
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Services/MasterJfAggregateServiceTest.php tests/Feature/Api/V1/MasterJfIndexTest.php`

Expected: PASS (all tests in both files)

- [ ] **Step 6: Commit**

```bash
git add app/Services/MasterJfAggregateService.php tests/Feature/Services/MasterJfAggregateServiceTest.php tests/Feature/Api/V1/MasterJfIndexTest.php
git commit -m "feat(api): add per-instansi aggregate breakdown in master-jf response"
```

---

### Task 2: Effective cluster post-filter (remove SQL type filter)

**Files:**
- Modify: `app/Services/MasterJfAggregateService.php` (`aggregate`, `buildFilteredQuery`)
- Modify: `tests/Feature/Services/MasterJfAggregateServiceTest.php`

**Interfaces:**
- Consumes: `MasterJfClusterResolver::resolveLabels()` (existing)
- Produces: rows with `type=null` included when effective cluster matches `type` filter param

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/Services/MasterJfAggregateServiceTest.php`:

```php
public function test_type_filter_uses_effective_cluster_not_raw_column(): void
{
    MasterJf::factory()->create([
        'instansi' => 'Pemerintah Daerah Kabupaten Tangerang',
        'unit_kerja' => 'Sekretariat Daerah',
        'type' => null,
        'jabatan' => 'Analis Hukum Ahli Muda',
    ]);
    MasterJf::factory()->create([
        'instansi' => 'KEMENTERIAN HUKUM',
        'type' => ClientCluster::Central,
        'jabatan' => 'Analis Hukum Ahli Muda',
    ]);

    $result = app(MasterJfAggregateService::class)->aggregate([
        'type' => ClientCluster::LocalRegency->value,
    ]);

    $this->assertCount(1, $result['data']);
    $this->assertSame(ClientCluster::LocalRegency->value, $result['data'][0]['cluster_id']);
    $this->assertSame(1, $result['data'][0]['aggregate']['total_jf']);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Services/MasterJfAggregateServiceTest.php --filter=test_type_filter_uses_effective_cluster_not_raw_column`

Expected: FAIL — 0 groups returned (SQL `where type = local_regency` excludes null rows)

- [ ] **Step 3: Remove SQL type filter and add post-filter**

In `buildFilteredQuery()`, **delete** this block:

```php
if (isset($filters['type'])) {
    $query->where('type', $filters['type']);
}
```

In `aggregate()`, after loading rows, add post-filter before the grouping loop:

```php
$clusterFilter = isset($filters['type']) ? (string) $filters['type'] : null;

foreach ($rows as $row) {
    [$clusterId, $clusterLabel] = MasterJfClusterResolver::resolveLabels(
        $row->type,
        $row->instansi,
        $row->unit_kerja,
    );

    if ($clusterFilter !== null && $clusterId !== $clusterFilter) {
        continue;
    }

    // ... existing grouping logic using $clusterId, $clusterLabel
}
```

Refactor the loop to resolve cluster once and skip early when filter mismatches.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Services/MasterJfAggregateServiceTest.php tests/Feature/Api/V1/MasterJfIndexTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/MasterJfAggregateService.php tests/Feature/Services/MasterJfAggregateServiceTest.php
git commit -m "fix(api): filter master-jf cluster by effective cluster instead of raw type column"
```

---

### Task 3: Daerah filter rules (Pemda-only, central ignores daerah)

**Files:**
- Modify: `app/Services/MasterJfAggregateService.php` (`buildFilteredQuery`, `aggregate`, new helpers)
- Modify: `tests/Feature/Services/MasterJfAggregateServiceTest.php`

**Interfaces:**
- Consumes: `ClientCluster::Central`, `ClientCluster::LocalProvince`, `ClientCluster::LocalRegency`
- Produces:
  - `protected function isCentralClusterFilter(array $filters): bool`
  - `protected function hasDaerahFilter(array $filters): bool`
  - Daerah SQL skipped when `type=central`; central rows excluded when daerah active without central filter

- [ ] **Step 1: Write failing tests for daerah rules**

Add to `tests/Feature/Services/MasterJfAggregateServiceTest.php`:

```php
public function test_province_id_filter_excludes_kl_rows(): void
{
    RegProvince::query()->create(['id' => 11, 'name' => 'ACEH']);

    MasterJf::factory()->create([
        'province_id' => 11,
        'provinsi' => 'ACEH',
        'type' => ClientCluster::LocalProvince,
        'instansi' => 'Pemerintah Daerah Provinsi Aceh',
        'jabatan' => 'Analis Hukum Ahli Muda',
    ]);
    MasterJf::factory()->create([
        'province_id' => 11,
        'provinsi' => 'ACEH',
        'type' => ClientCluster::Central,
        'instansi' => 'KEMENTERIAN HUKUM',
        'jabatan' => 'Analis Hukum Ahli Muda',
    ]);
    MasterJf::factory()->create([
        'province_id' => 12,
        'provinsi' => 'BALI',
        'type' => ClientCluster::LocalProvince,
        'instansi' => 'Pemerintah Daerah Provinsi Bali',
        'jabatan' => 'Analis Hukum Ahli Muda',
    ]);

    $result = app(MasterJfAggregateService::class)->aggregate(['province_id' => 11]);

    $this->assertCount(1, $result['data']);
    $this->assertSame(ClientCluster::LocalProvince->value, $result['data'][0]['cluster_id']);
    $this->assertSame(1, $result['data'][0]['aggregate']['total_jf']);
}

public function test_central_filter_ignores_daerah_filter(): void
{
    RegProvince::query()->create(['id' => 11, 'name' => 'ACEH']);

    MasterJf::factory()->create([
        'province_id' => 11,
        'type' => ClientCluster::LocalProvince,
        'instansi' => 'Pemerintah Daerah Provinsi Aceh',
        'jabatan' => 'Analis Hukum Ahli Muda',
    ]);
    MasterJf::factory()->create([
        'province_id' => null,
        'type' => ClientCluster::Central,
        'instansi' => 'KEMENTERIAN HUKUM',
        'jabatan' => 'Analis Hukum Ahli Muda',
    ]);
    MasterJf::factory()->create([
        'province_id' => null,
        'type' => ClientCluster::Central,
        'instansi' => 'KEMENTERIAN AGAMA',
        'jabatan' => 'Analis Hukum Ahli Pertama',
    ]);

    $result = app(MasterJfAggregateService::class)->aggregate([
        'province_id' => 11,
        'type' => ClientCluster::Central->value,
    ]);

    $this->assertCount(1, $result['data']);
    $this->assertSame(ClientCluster::Central->value, $result['data'][0]['cluster_id']);
    $this->assertSame(2, $result['data'][0]['aggregate']['total_jf']);
}

public function test_local_province_and_province_filter_combined(): void
{
    RegProvince::query()->create(['id' => 11, 'name' => 'ACEH']);

    MasterJf::factory()->create([
        'province_id' => 11,
        'type' => ClientCluster::LocalProvince,
        'instansi' => 'Pemerintah Daerah Provinsi Aceh',
        'jabatan' => 'Analis Hukum Ahli Muda',
    ]);
    MasterJf::factory()->create([
        'province_id' => 11,
        'type' => ClientCluster::LocalRegency,
        'instansi' => 'Pemerintah Daerah Kabupaten Aceh Tamiang',
        'jabatan' => 'Analis Hukum Ahli Muda',
    ]);
    MasterJf::factory()->create([
        'province_id' => 12,
        'type' => ClientCluster::LocalProvince,
        'instansi' => 'Pemerintah Daerah Provinsi Bali',
        'jabatan' => 'Analis Hukum Ahli Muda',
    ]);

    $result = app(MasterJfAggregateService::class)->aggregate([
        'province_id' => 11,
        'type' => ClientCluster::LocalProvince->value,
    ]);

    $this->assertCount(1, $result['data']);
    $this->assertSame(ClientCluster::LocalProvince->value, $result['data'][0]['cluster_id']);
    $this->assertSame(1, $result['data'][0]['aggregate']['total_jf']);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Services/MasterJfAggregateServiceTest.php --filter="test_province_id_filter_excludes_kl_rows|test_central_filter_ignores_daerah_filter|test_local_province_and_province_filter_combined"`

Expected: FAIL

- [ ] **Step 3: Add helper methods**

Add to `MasterJfAggregateService.php`:

```php
protected function isCentralClusterFilter(array $filters): bool
{
    return isset($filters['type'])
        && (string) $filters['type'] === ClientCluster::Central->value;
}

protected function hasDaerahFilter(array $filters): bool
{
    return isset($filters['province_id'])
        || trim((string) ($filters['provinsi'] ?? '')) !== '';
}
```

- [ ] **Step 4: Update buildFilteredQuery daerah logic**

Replace the existing `province_id` and `provinsi` blocks with a single grouped condition:

```php
if (! $this->isCentralClusterFilter($filters) && $this->hasDaerahFilter($filters)) {
    $provinceId = $filters['province_id'] ?? null;
    $provinsi = trim((string) ($filters['provinsi'] ?? ''));

    $query->where(function (Builder $q) use ($provinceId, $provinsi) {
        if ($provinceId !== null) {
            $q->where('province_id', $provinceId);
        }

        if ($provinsi !== '') {
            $method = $provinceId !== null ? 'orWhere' : 'where';

            $q->{$method}(function (Builder $q2) use ($provinsi) {
                $q2->whereNull('province_id')
                    ->where('provinsi', 'like', '%'.$provinsi.'%');
            });
        }
    });
}
```

**Logic summary:**

| Params set | SQL behavior |
|---|---|
| `province_id` only | `WHERE province_id = ?` |
| `provinsi` only | `WHERE province_id IS NULL AND provinsi LIKE ?` |
| Both | `WHERE province_id = ? OR (province_id IS NULL AND provinsi LIKE ?)` |
| `type=central` | Daerah block skipped entirely |

PHP post-filter (Step 5) still excludes effective-central rows when daerah is active without `type=central`.

- [ ] **Step 5: Exclude central rows in aggregate loop when daerah active**

In the grouping loop (after cluster resolve, before grouping):

```php
if (
    $this->hasDaerahFilter($filters)
    && ! $this->isCentralClusterFilter($filters)
    && $clusterId === ClientCluster::Central->value
) {
    continue;
}
```

- [ ] **Step 6: Fix existing provinsi test to use Pemda rows**

Update `test_provinsi_filter_only_applies_when_province_id_is_null` — change both factory rows from `ClientCluster::Central` to `ClientCluster::LocalProvince` and use Pemda instansi names. Central rows are excluded when daerah filter is active.

- [ ] **Step 7: Run service tests**

Run: `php artisan test tests/Feature/Services/MasterJfAggregateServiceTest.php`

Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add app/Services/MasterJfAggregateService.php tests/Feature/Services/MasterJfAggregateServiceTest.php
git commit -m "fix(api): apply daerah filter to Pemda only and ignore daerah when cluster is central"
```

---

### Task 4: API feature tests for new filter behavior

**Files:**
- Modify: `tests/Feature/Api/V1/MasterJfIndexTest.php`

**Interfaces:**
- Consumes: full service stack via HTTP
- Produces: HTTP-level regression coverage for daerah + cluster interaction

- [ ] **Step 0: Add missing import**

At top of `tests/Feature/Api/V1/MasterJfIndexTest.php`, add:

```php
use App\Models\RegProvince;
```

- [ ] **Step 1: Add API test for province filter excluding K/L**

Add to `tests/Feature/Api/V1/MasterJfIndexTest.php`:

```php
public function test_province_filter_returns_pemda_only(): void
{
    $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
    RegProvince::query()->create(['id' => 11, 'name' => 'ACEH']);

    MasterJf::factory()->create([
        'c_role_id' => $role->id,
        'province_id' => 11,
        'type' => ClientCluster::LocalProvince,
        'instansi' => 'Pemerintah Daerah Provinsi Aceh',
        'jabatan' => 'Analis Hukum Ahli Muda',
    ]);
    MasterJf::factory()->create([
        'c_role_id' => $role->id,
        'province_id' => 11,
        'type' => ClientCluster::Central,
        'instansi' => 'KEMENTERIAN HUKUM',
        'jabatan' => 'Analis Hukum Ahli Muda',
    ]);

    $response = $this->getJson('/api/v1/master-jf?province_id=11', $this->apiHeaders());

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.cluster_id', ClientCluster::LocalProvince->value)
        ->assertJsonPath('data.0.data.0.aggregate.total_jf', 1);
}
```

- [ ] **Step 2: Add API test for central filter ignoring daerah**

```php
public function test_central_filter_ignores_province_filter(): void
{
    $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
    RegProvince::query()->create(['id' => 11, 'name' => 'ACEH']);

    MasterJf::factory()->create([
        'c_role_id' => $role->id,
        'province_id' => 11,
        'type' => ClientCluster::LocalProvince,
        'instansi' => 'Pemerintah Daerah Provinsi Aceh',
        'jabatan' => 'Analis Hukum Ahli Muda',
    ]);
    MasterJf::factory()->count(2)->create([
        'c_role_id' => $role->id,
        'type' => ClientCluster::Central,
        'instansi' => 'KEMENTERIAN HUKUM',
        'jabatan' => 'Analis Hukum Ahli Muda',
    ]);

    $response = $this->getJson(
        '/api/v1/master-jf?province_id=11&type=central',
        $this->apiHeaders(),
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.cluster_id', ClientCluster::Central->value)
        ->assertJsonPath('data.0.aggregate.total_jf', 2);
}
```

- [ ] **Step 3: Add API test for combined local_province + province filter**

```php
public function test_local_province_and_province_filter_combined_via_api(): void
{
    $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
    RegProvince::query()->create(['id' => 11, 'name' => 'ACEH']);

    MasterJf::factory()->create([
        'c_role_id' => $role->id,
        'province_id' => 11,
        'type' => ClientCluster::LocalProvince,
        'instansi' => 'Pemerintah Daerah Provinsi Aceh',
        'jabatan' => 'Analis Hukum Ahli Muda',
    ]);
    MasterJf::factory()->create([
        'c_role_id' => $role->id,
        'province_id' => 11,
        'type' => ClientCluster::LocalRegency,
        'instansi' => 'Pemerintah Daerah Kabupaten Aceh Tamiang',
        'jabatan' => 'Analis Hukum Ahli Muda',
    ]);

    $response = $this->getJson(
        '/api/v1/master-jf?province_id=11&type=local_province',
        $this->apiHeaders(),
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.cluster_id', ClientCluster::LocalProvince->value)
        ->assertJsonPath('data.0.aggregate.total_jf', 1);
}
```

- [ ] **Step 4: Run API tests**

Run: `php artisan test tests/Feature/Api/V1/MasterJfIndexTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Api/V1/MasterJfIndexTest.php
git commit -m "test(api): cover daerah and central filter interaction for master-jf"
```

---

### Task 5: Full verification

**Files:** (none — verification only)

- [ ] **Step 1: Run full Master JF test suite**

Run:

```bash
php artisan test \
  tests/Feature/Api/V1/MasterJfIndexTest.php \
  tests/Feature/Services/MasterJfAggregateServiceTest.php \
  tests/Unit/Support/MasterJfClusterResolverTest.php
```

Expected: all PASS

- [ ] **Step 2: Manual smoke check (optional, local)**

If `.env` has test data:

```bash
curl -s -H "X-Api-Key: $SUPERAPPS_API_KEY" \
  "http://localhost/api/v1/master-jf?c_role_id=1" | jq '.data[0].data[0].aggregate'
```

Expected: JSON object with `total_jf`, `by_jenjang`, `by_status`, `by_status_kepegawaian`, `by_pengangkatan`

- [ ] **Step 3: Commit plan/spec alignment (optional)**

If spec status should be updated, change `docs/superpowers/specs/2026-08-14-master-jf-instansi-aggregate-design.md` status line to `Implemented`.

---

## Spec coverage checklist

| Spec requirement | Task |
|---|---|
| Instansi-level `aggregate` block | Task 1 |
| `client_count` = `aggregate.total_jf` | Task 1 |
| Effective cluster post-filter | Task 2 |
| Daerah hybrid filter | Task 3 |
| Daerah excludes K/L when active | Task 3 |
| `type=central` ignores daerah | Task 3 |
| Secondary filters unchanged | Task 5 regression |
| Empty instansi → `"unknown"` | Task 1 (`test_empty_instansi_groups_as_unknown_with_aggregate`) |
| `type=local_province&province_id` | Task 3 + Task 4 |

## Known data limitations

- Pemda row **without** `province_id` will **not** match `?province_id=X` (FK exact match only). Use `?provinsi=` for text-only rows.
- Effective cluster post-filter loads all SQL-matched rows into memory (~5k prod rows is acceptable).

## Self-review notes

- No placeholders or TBD sections.
- `test_provinsi_filter_only_applies_when_province_id_is_null` must be updated in Task 3 — old behavior (central rows + provinsi filter) conflicts with new Pemda-only daerah rules.
- Daerah SQL uses single OR-group (`province_id` OR null-FK + `provinsi` text); central exclusion happens in PHP after cluster resolve.
- No new files beyond test modifications — all logic stays in `MasterJfAggregateService`.
