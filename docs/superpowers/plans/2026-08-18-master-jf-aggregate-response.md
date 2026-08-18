# Master JF Aggregate API Response Reshape Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Breakingly reshape `GET /api/v1/master-jf` so groups use `c_role_*` + `cluster` and instansi rows use short `agency_type` + `agency_id`, grouping by persisted morph (or one `"unknown"` bucket).

**Architecture:** Keep the existing controller → `MasterJfAggregateService` → JSON resources path. Add `MasterJfAgencyApiMapper` for FQCN→short type and API cluster labels. Change instansi bucketing in the service; resources only serialize. Do not add routes, migrations, or Filament/import work.

**Tech Stack:** Laravel, Eloquent morphTo, PHPUnit, Scramble OpenAPI, `lerd php artisan test`

## Global Constraints

- Breaking reshape of current `GET /api/v1/master-jf` — no dual keys, no second URL
- Query filter stays `type` (`central` | `local_province` | `local_regency`)
- Group fields: `c_role_id`, `c_role_label`, `cluster`, `cluster_label`, `aggregate`, `data`
- Drop `jf_type_id`, `jf_label`, `cluster_id`
- `cluster_label`: `central` → Kementerian Lembaga; `local_province` → Pemerintah Daerah Provinsi; `local_regency` → Pemerintah Daerah Kabupaten/Kota (not `ClientCluster::getLabel()`)
- Instansi item: `agency_type`, `agency_id`, `name`, `client_count` — no `instance_id`, no nested `aggregate`
- JSON `agency_type`: `department` | `province` | `regency` | `null` — never FQCN
- Linked bucket: both morph columns set **and** related `agenciable` exists; `name` from related `name`
- Unmatched (null/incomplete morph, unknown FQCN, dangling related): one `"unknown"` item per group; do not group unmatched by `instansi` text
- Omit unknown item when `client_count` is 0; sort linked by `name` case-insensitive; unknown last
- `aggregate` shape unchanged; includes unmatched rows in the group
- Prerequisite: `master_jf.agency_type` / `agency_id` and `MasterJf::agenciable()` from the agency-morph plan. Do not add those columns or the relation in this plan. If they are missing, stop and finish morph Task 1 first.
- Out of scope: import, backfill, Filament, `ClientMatchingService`, renaming query `type`, echelon

## File structure

| File | Responsibility |
|---|---|
| `app/Support/MasterJfAgencyApiMapper.php` | FQCN → short type; cluster → API label |
| `tests/Unit/Support/MasterJfAgencyApiMapperTest.php` | Mapper unit tests |
| `tests/Concerns/EnsuresMasterJfApiSchema.php` | SQLite: add morph columns if missing |
| `app/Services/MasterJfAggregateService.php` | New group keys; morph/unknown instansi buckets |
| `tests/Feature/Services/MasterJfAggregateServiceTest.php` | Service grouping tests |
| `app/Http/Resources/Api/V1/MasterJfGroupResource.php` | Serialize new group keys |
| `app/Http/Resources/Api/V1/MasterJfInstansiItemResource.php` | Serialize morph instansi item |
| `app/Http/Controllers/Api/V1/MasterJfController.php` | Scramble example JSON |
| `app/Support/OpenApi/MasterJfOpenApiDocumentTransformer.php` | Document `cluster` instead of `cluster_id` |
| `tests/Feature/Api/V1/MasterJfIndexTest.php` | HTTP schema and behavior |

---

### Task 1: `MasterJfAgencyApiMapper`

**Files:**
- Create: `app/Support/MasterJfAgencyApiMapper.php`
- Create: `tests/Unit/Support/MasterJfAgencyApiMapperTest.php`

**Interfaces:**
- Consumes: `App\Models\RegDepartment`, `RegProvince`, `RegRegency`; `App\Enums\ClientCluster`
- Produces:
  - `MasterJfAgencyApiMapper::shortType(?string $fqcn): ?string` — `department` | `province` | `regency` | `null`
  - `MasterJfAgencyApiMapper::clusterLabel(string $cluster): string`

- [ ] **Step 1: Write the failing unit test**

Create `tests/Unit/Support/MasterJfAgencyApiMapperTest.php`:

```php
<?php

namespace Tests\Unit\Support;

use App\Enums\ClientCluster;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;
use App\Support\MasterJfAgencyApiMapper;
use PHPUnit\Framework\TestCase;

class MasterJfAgencyApiMapperTest extends TestCase
{
    public function test_it_maps_known_agency_classes_to_short_types(): void
    {
        $this->assertSame('department', MasterJfAgencyApiMapper::shortType(RegDepartment::class));
        $this->assertSame('province', MasterJfAgencyApiMapper::shortType(RegProvince::class));
        $this->assertSame('regency', MasterJfAgencyApiMapper::shortType(RegRegency::class));
    }

    public function test_it_returns_null_for_unknown_or_empty_agency_type(): void
    {
        $this->assertNull(MasterJfAgencyApiMapper::shortType(null));
        $this->assertNull(MasterJfAgencyApiMapper::shortType(''));
        $this->assertNull(MasterJfAgencyApiMapper::shortType('App\\Models\\Client'));
    }

    public function test_it_maps_cluster_enum_values_to_api_labels(): void
    {
        $this->assertSame(
            'Kementerian Lembaga',
            MasterJfAgencyApiMapper::clusterLabel(ClientCluster::Central->value),
        );
        $this->assertSame(
            'Pemerintah Daerah Provinsi',
            MasterJfAgencyApiMapper::clusterLabel(ClientCluster::LocalProvince->value),
        );
        $this->assertSame(
            'Pemerintah Daerah Kabupaten/Kota',
            MasterJfAgencyApiMapper::clusterLabel(ClientCluster::LocalRegency->value),
        );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `lerd php artisan test tests/Unit/Support/MasterJfAgencyApiMapperTest.php`

Expected: FAIL — class `MasterJfAgencyApiMapper` not found.

- [ ] **Step 3: Write the mapper**

Create `app/Support/MasterJfAgencyApiMapper.php`:

```php
<?php

namespace App\Support;

use App\Enums\ClientCluster;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;

final class MasterJfAgencyApiMapper
{
    public static function shortType(?string $fqcn): ?string
    {
        return match ($fqcn) {
            RegDepartment::class => 'department',
            RegProvince::class => 'province',
            RegRegency::class => 'regency',
            default => null,
        };
    }

    public static function clusterLabel(string $cluster): string
    {
        return match ($cluster) {
            ClientCluster::Central->value => 'Kementerian Lembaga',
            ClientCluster::LocalProvince->value => 'Pemerintah Daerah Provinsi',
            ClientCluster::LocalRegency->value => 'Pemerintah Daerah Kabupaten/Kota',
            default => $cluster,
        };
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `lerd php artisan test tests/Unit/Support/MasterJfAgencyApiMapperTest.php`

Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Support/MasterJfAgencyApiMapper.php tests/Unit/Support/MasterJfAgencyApiMapperTest.php
git commit -m "$(cat <<'EOF'
feat(api): map Master JF agency FQCN and cluster labels for JSON

EOF
)"
```

---

### Task 2: Service grouping by morph (or unknown)

**Files:**
- Modify: `tests/Concerns/EnsuresMasterJfApiSchema.php`
- Modify: `app/Services/MasterJfAggregateService.php`
- Modify: `tests/Feature/Services/MasterJfAggregateServiceTest.php`

**Interfaces:**
- Consumes: `MasterJfAgencyApiMapper::shortType`, `MasterJfAgencyApiMapper::clusterLabel`; `MasterJf::agenciable()`
- Produces: `MasterJfAggregateService::aggregate(array $filters): array{data: list<array{c_role_id: int, c_role_label: string, cluster: string, cluster_label: string, aggregate: array, data: list<array{agency_type: ?string, agency_id: ?int, name: string, client_count: int}>}>}`

- [ ] **Step 1: Ensure SQLite morph columns**

In `tests/Concerns/EnsuresMasterJfApiSchema.php`, after the `province_id` block, add:

```php
        if (Schema::hasTable('master_jf') && ! Schema::hasColumn('master_jf', 'agency_id')) {
            Schema::table('master_jf', function (Blueprint $table) {
                $table->nullableMorphs('agency');
            });
        }
```

If the morph plan already added this, leave the existing block (do not duplicate).

- [ ] **Step 2: Write failing service tests for morph grouping**

In `tests/Feature/Services/MasterJfAggregateServiceTest.php`:

1. Add imports: `App\Models\RegDepartment`, `App\Models\RegRegency`, `App\Support\MasterJfAgencyApiMapper`.
2. Replace every `$result['data'][n]['jf_label']` with `c_role_label`.
3. Replace every `$result['data'][n]['cluster_id']` and `array_column(..., 'cluster_id')` with `cluster`.
4. Do **not** change filter input key `'type'`.
5. Append these methods (keep existing filter tests; they may now expose an `"unknown"` instansi item — assert group totals, not old `instansi` names):

```php
    public function test_it_emits_c_role_and_cluster_keys_with_api_labels(): void
    {
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        MasterJf::factory()->create([
            'c_role_id' => $role->id,
            'type' => ClientCluster::LocalProvince,
            'jabatan' => 'Analis Hukum Ahli Muda',
        ]);

        $result = app(MasterJfAggregateService::class)->aggregate([]);

        $this->assertArrayHasKey('c_role_id', $result['data'][0]);
        $this->assertArrayHasKey('c_role_label', $result['data'][0]);
        $this->assertArrayHasKey('cluster', $result['data'][0]);
        $this->assertArrayHasKey('cluster_label', $result['data'][0]);
        $this->assertArrayNotHasKey('jf_type_id', $result['data'][0]);
        $this->assertArrayNotHasKey('jf_label', $result['data'][0]);
        $this->assertArrayNotHasKey('cluster_id', $result['data'][0]);
        $this->assertSame($role->id, $result['data'][0]['c_role_id']);
        $this->assertSame('Analis Hukum', $result['data'][0]['c_role_label']);
        $this->assertSame(ClientCluster::LocalProvince->value, $result['data'][0]['cluster']);
        $this->assertSame(
            MasterJfAgencyApiMapper::clusterLabel(ClientCluster::LocalProvince->value),
            $result['data'][0]['cluster_label'],
        );
    }

    public function test_it_groups_instansi_by_morph_not_instansi_text(): void
    {
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $province = RegProvince::query()->create(['id' => 51, 'name' => 'Bali']);

        MasterJf::factory()->create([
            'c_role_id' => $role->id,
            'type' => ClientCluster::LocalProvince,
            'instansi' => 'PEMDA PROVINSI BALI',
            'jabatan' => 'Analis Hukum Ahli Muda',
            'agency_type' => RegProvince::class,
            'agency_id' => $province->id,
        ]);
        MasterJf::factory()->create([
            'c_role_id' => $role->id,
            'type' => ClientCluster::LocalProvince,
            'instansi' => 'Pemerintah Daerah Provinsi Bali',
            'jabatan' => 'Analis Hukum Ahli Pertama',
            'agency_type' => RegProvince::class,
            'agency_id' => $province->id,
        ]);

        $result = app(MasterJfAggregateService::class)->aggregate([]);

        $this->assertCount(1, $result['data'][0]['data']);
        $this->assertSame('province', $result['data'][0]['data'][0]['agency_type']);
        $this->assertSame(51, $result['data'][0]['data'][0]['agency_id']);
        $this->assertSame('Bali', $result['data'][0]['data'][0]['name']);
        $this->assertSame(2, $result['data'][0]['data'][0]['client_count']);
        $this->assertSame(2, $result['data'][0]['aggregate']['total_jf']);
    }

    public function test_unmatched_rows_collapse_to_one_unknown_bucket_included_in_aggregate(): void
    {
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        MasterJf::factory()->count(2)->create([
            'c_role_id' => $role->id,
            'type' => ClientCluster::Central,
            'instansi' => 'KEMENTERIAN HUKUM',
            'jabatan' => 'Analis Hukum Ahli Muda',
            'agency_type' => null,
            'agency_id' => null,
        ]);
        MasterJf::factory()->create([
            'c_role_id' => $role->id,
            'type' => ClientCluster::Central,
            'instansi' => 'KEMENTERIAN AGAMA',
            'jabatan' => 'Analis Hukum Ahli Madya',
            'agency_type' => null,
            'agency_id' => null,
        ]);

        $result = app(MasterJfAggregateService::class)->aggregate([]);

        $this->assertSame(3, $result['data'][0]['aggregate']['total_jf']);
        $this->assertCount(1, $result['data'][0]['data']);
        $this->assertNull($result['data'][0]['data'][0]['agency_type']);
        $this->assertNull($result['data'][0]['data'][0]['agency_id']);
        $this->assertSame('unknown', $result['data'][0]['data'][0]['name']);
        $this->assertSame(3, $result['data'][0]['data'][0]['client_count']);
    }

    public function test_dangling_morph_counts_as_unknown(): void
    {
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        MasterJf::factory()->create([
            'c_role_id' => $role->id,
            'type' => ClientCluster::Central,
            'jabatan' => 'Analis Hukum Ahli Muda',
            'agency_type' => RegDepartment::class,
            'agency_id' => 999999,
        ]);

        $result = app(MasterJfAggregateService::class)->aggregate([]);

        $this->assertSame('unknown', $result['data'][0]['data'][0]['name']);
        $this->assertNull($result['data'][0]['data'][0]['agency_type']);
        $this->assertSame(1, $result['data'][0]['data'][0]['client_count']);
    }

    public function test_unknown_item_is_omitted_when_every_row_is_linked(): void
    {
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $dept = RegDepartment::create(['name' => 'Kementerian Hukum']);
        MasterJf::factory()->create([
            'c_role_id' => $role->id,
            'type' => ClientCluster::Central,
            'jabatan' => 'Analis Hukum Ahli Muda',
            'agency_type' => RegDepartment::class,
            'agency_id' => $dept->id,
        ]);

        $items = app(MasterJfAggregateService::class)->aggregate([])['data'][0]['data'];

        $this->assertNotContains('unknown', array_column($items, 'name'));
        $this->assertSame('department', $items[0]['agency_type']);
        $this->assertSame('Kementerian Hukum', $items[0]['name']);
    }

    public function test_unknown_bucket_sorts_last_after_linked_names(): void
    {
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $deptB = RegDepartment::create(['name' => 'Badan A']);
        $deptZ = RegDepartment::create(['name' => 'Kementerian Z']);
        MasterJf::factory()->create([
            'c_role_id' => $role->id,
            'type' => ClientCluster::Central,
            'jabatan' => 'Analis Hukum Ahli Muda',
            'agency_type' => RegDepartment::class,
            'agency_id' => $deptZ->id,
        ]);
        MasterJf::factory()->create([
            'c_role_id' => $role->id,
            'type' => ClientCluster::Central,
            'jabatan' => 'Analis Hukum Ahli Muda',
            'agency_type' => null,
            'agency_id' => null,
        ]);
        MasterJf::factory()->create([
            'c_role_id' => $role->id,
            'type' => ClientCluster::Central,
            'jabatan' => 'Analis Hukum Ahli Muda',
            'agency_type' => RegDepartment::class,
            'agency_id' => $deptB->id,
        ]);

        $names = array_column(
            app(MasterJfAggregateService::class)->aggregate([])['data'][0]['data'],
            'name',
        );

        $this->assertSame(['Badan A', 'Kementerian Z', 'unknown'], $names);
    }
```

- [ ] **Step 3: Run service tests to verify they fail**

Run: `lerd php artisan test tests/Feature/Services/MasterJfAggregateServiceTest.php`

Expected: FAIL — missing `c_role_id` / still grouping by `instansi` text / `cluster_id` key present.

- [ ] **Step 4: Update `MasterJfAggregateService`**

Add `use App\Support\MasterJfAgencyApiMapper;`.

In `aggregate()`, extend the select list with `'agency_type', 'agency_id'`. Change eager load to:

```php
            ->with(['cRole:id,role_name', 'agenciable'])
```

Rename segment/group keys `jf_type_id` → `c_role_id`, `jf_label` → `c_role_label`, `cluster_id` → `cluster`. Set:

```php
                    'cluster_label' => MasterJfAgencyApiMapper::clusterLabel($clusterId),
```

when creating a segment (use the resolver’s `$clusterId` string). Keep `usort` comparing `c_role_id` and `clusterSortOrder($a['cluster'])`.

Replace `buildInstansiListFromCollection` with:

```php
    /**
     * @param  Collection<int, MasterJf>  $rows
     * @return list<array{agency_type: ?string, agency_id: ?int, name: string, client_count: int}>
     */
    protected function buildInstansiListFromCollection(Collection $rows): array
    {
        $buckets = [];
        $unknownCount = 0;

        foreach ($rows as $row) {
            $shortType = MasterJfAgencyApiMapper::shortType(
                is_string($row->agency_type) ? $row->agency_type : null,
            );
            $agencyId = $row->agency_id;
            $related = $row->agenciable;

            if ($shortType === null || $agencyId === null || $related === null) {
                $unknownCount++;

                continue;
            }

            $key = $shortType.':'.$agencyId;
            if (! isset($buckets[$key])) {
                $buckets[$key] = [
                    'agency_type' => $shortType,
                    'agency_id' => (int) $agencyId,
                    'name' => (string) $related->name,
                    'client_count' => 0,
                ];
            }
            $buckets[$key]['client_count']++;
        }

        $instansi = array_values($buckets);
        usort($instansi, fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        if ($unknownCount > 0) {
            $instansi[] = [
                'agency_type' => null,
                'agency_id' => null,
                'name' => 'unknown',
                'client_count' => $unknownCount,
            ];
        }

        return $instansi;
    }
```

Update the `$segments` PHPDoc to use `c_role_id`, `c_role_label`, `cluster`. Stop writing `cluster_label` from `MasterJfClusterResolver::resolveLabels` second return; still use resolver for the enum value. You may switch to `MasterJfClusterResolver::resolve(...)` + `->value` if that is cleaner than discarding the Filament label.

- [ ] **Step 5: Run service tests to verify they pass**

Run: `lerd php artisan test tests/Feature/Services/MasterJfAggregateServiceTest.php`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add tests/Concerns/EnsuresMasterJfApiSchema.php \
  app/Services/MasterJfAggregateService.php \
  tests/Feature/Services/MasterJfAggregateServiceTest.php
git commit -m "$(cat <<'EOF'
feat(api): group Master JF instansi by agency morph

EOF
)"
```

---

### Task 3: Resources, OpenAPI, and HTTP tests

**Files:**
- Modify: `app/Http/Resources/Api/V1/MasterJfGroupResource.php`
- Modify: `app/Http/Resources/Api/V1/MasterJfInstansiItemResource.php`
- Modify: `app/Http/Controllers/Api/V1/MasterJfController.php`
- Modify: `app/Support/OpenApi/MasterJfOpenApiDocumentTransformer.php`
- Modify: `tests/Feature/Api/V1/MasterJfIndexTest.php`

**Interfaces:**
- Consumes: service payload from Task 2 (`c_role_id`, `cluster`, instansi morph fields)
- Produces: HTTP 200 JSON matching the spec example shape

- [ ] **Step 1: Write failing HTTP assertions**

In `tests/Feature/Api/V1/MasterJfIndexTest.php`:

Add imports: `App\Models\RegDepartment`, `App\Support\MasterJfAgencyApiMapper`.

Replace `test_it_returns_grouped_data_with_instansi_list` with:

```php
    public function test_it_returns_grouped_data_with_instansi_list(): void
    {
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $dept = RegDepartment::create(['name' => 'Kementerian Hukum']);
        $province = RegProvince::query()->create(['id' => 51, 'name' => 'Bali']);

        MasterJf::factory()->count(2)->create([
            'c_role_id' => $role->id,
            'status' => ClientStatus::Active,
            'type' => ClientCluster::Central,
            'instansi' => 'KEMENTERIAN HUKUM',
            'jabatan' => 'Analis Hukum Ahli Muda',
            'agency_type' => RegDepartment::class,
            'agency_id' => $dept->id,
        ]);
        MasterJf::factory()->create([
            'c_role_id' => $role->id,
            'type' => ClientCluster::LocalProvince,
            'instansi' => 'Pemerintah Daerah Provinsi Bali',
            'jabatan' => 'Analis Hukum Ahli Pertama',
            'agency_type' => RegProvince::class,
            'agency_id' => $province->id,
        ]);

        $response = $this->getJson('/api/v1/master-jf', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'c_role_id',
                        'c_role_label',
                        'cluster',
                        'cluster_label',
                        'aggregate' => [
                            'total_jf',
                            'by_jenjang',
                            'by_status',
                            'by_status_kepegawaian',
                            'by_pengangkatan',
                        ],
                        'data' => [
                            '*' => ['agency_type', 'agency_id', 'name', 'client_count'],
                        ],
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.c_role_label', 'Analis Hukum')
            ->assertJsonPath('data.0.cluster', ClientCluster::Central->value)
            ->assertJsonPath(
                'data.0.cluster_label',
                MasterJfAgencyApiMapper::clusterLabel(ClientCluster::Central->value),
            )
            ->assertJsonPath('data.0.aggregate.total_jf', 2)
            ->assertJsonPath('data.0.data.0.agency_type', 'department')
            ->assertJsonPath('data.0.data.0.name', 'Kementerian Hukum')
            ->assertJsonPath('data.0.data.0.client_count', 2)
            ->assertJsonMissingPath('data.0.data.0.aggregate')
            ->assertJsonPath('data.1.cluster', ClientCluster::LocalProvince->value)
            ->assertJsonPath(
                'data.1.cluster_label',
                MasterJfAgencyApiMapper::clusterLabel(ClientCluster::LocalProvince->value),
            )
            ->assertJsonPath('data.1.aggregate.by_jenjang.Ahli Pertama', 1)
            ->assertJsonMissing(['agregasi'])
            ->assertJsonMissingPath('data.0.jf_type_id')
            ->assertJsonMissingPath('data.0.cluster_id');
    }
```

In `test_jenjang_filter_narrows_groups`, link both rows to departments (or expect `"unknown"` if you leave morph null). Link them:

```php
        $hukum = RegDepartment::create(['name' => 'Kementerian Hukum']);
        $agama = RegDepartment::create(['name' => 'Kementerian Agama']);
        // first factory: agency_type RegDepartment::class, agency_id $hukum->id
        // second factory: agency_id $agama->id
        // assert data.0.data.0.name === 'Kementerian Hukum'
```

Replace `jf_label` → `c_role_label` in `test_it_separates_analis_and_penyuluh_groups`.

In `test_it_resolves_cluster_from_instansi_when_type_is_null`:

```php
            ->assertJsonPath('data.0.cluster', ClientCluster::LocalRegency->value)
            ->assertJsonPath(
                'data.0.cluster_label',
                MasterJfAgencyApiMapper::clusterLabel(ClientCluster::LocalRegency->value),
            );
```

Replace remaining `cluster_id` JSON paths with `cluster` in the three filter tests. Keep query string `type=central` / `type=local_province`.

Add:

```php
    public function test_it_returns_unknown_instansi_when_morph_is_missing(): void
    {
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        MasterJf::factory()->create([
            'c_role_id' => $role->id,
            'type' => ClientCluster::Central,
            'instansi' => 'KEMENTERIAN HUKUM',
            'jabatan' => 'Analis Hukum Ahli Muda',
        ]);

        $this->getJson('/api/v1/master-jf', $this->apiHeaders())
            ->assertOk()
            ->assertJsonPath('data.0.data.0.name', 'unknown')
            ->assertJsonPath('data.0.data.0.agency_type', null)
            ->assertJsonPath('data.0.aggregate.total_jf', 1);
    }
```

- [ ] **Step 2: Run HTTP tests to verify they fail**

Run: `lerd php artisan test tests/Feature/Api/V1/MasterJfIndexTest.php`

Expected: FAIL — resources still emit `jf_type_id` / `cluster_id` / `{name, client_count}` only.

- [ ] **Step 3: Update resources, controller example, OpenAPI transformer**

`app/Http/Resources/Api/V1/MasterJfGroupResource.php`:

```php
        return [
            'c_role_id' => (int) $this->resource['c_role_id'],
            'c_role_label' => (string) $this->resource['c_role_label'],
            'cluster' => (string) $this->resource['cluster'],
            'cluster_label' => (string) $this->resource['cluster_label'],
            'aggregate' => new MasterJfSliceAggregateResource($this->resource['aggregate']),
            'data' => MasterJfInstansiItemResource::collection(
                collect($this->resource['data'] ?? []),
            ),
        ];
```

Update the `@return` array shape to match.

`app/Http/Resources/Api/V1/MasterJfInstansiItemResource.php`:

```php
        return [
            'agency_type' => $this->resource['agency_type'],
            'agency_id' => $this->resource['agency_id'] === null
                ? null
                : (int) $this->resource['agency_id'],
            'name' => (string) $this->resource['name'],
            'client_count' => (int) $this->resource['client_count'],
        ];
```

PHPDoc: `@return array{agency_type: ?string, agency_id: ?int, name: string, client_count: int}`.

In `MasterJfController` example and description, replace the sample group with the spec JSON (short keys, `cluster`, morph instansi). Keep `type` in the filter bullet list.

In `MasterJfOpenApiDocumentTransformer::annotateClusterId`, look up property `cluster` (not `cluster_id`). Rename the method to `annotateCluster` and update the `transform()` call.

- [ ] **Step 4: Run HTTP tests to verify they pass**

Run: `lerd php artisan test tests/Feature/Api/V1/MasterJfIndexTest.php`

Expected: PASS.

- [ ] **Step 5: Run the related suite**

Run: `lerd php artisan test tests/Unit/Support/MasterJfAgencyApiMapperTest.php tests/Feature/Services/MasterJfAggregateServiceTest.php tests/Feature/Api/V1/MasterJfIndexTest.php`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Resources/Api/V1/MasterJfGroupResource.php \
  app/Http/Resources/Api/V1/MasterJfInstansiItemResource.php \
  app/Http/Controllers/Api/V1/MasterJfController.php \
  app/Support/OpenApi/MasterJfOpenApiDocumentTransformer.php \
  tests/Feature/Api/V1/MasterJfIndexTest.php
git commit -m "$(cat <<'EOF'
feat(api): reshape Master JF JSON resources for morph instansi

EOF
)"
```

---

## Spec coverage (self-review)

| Spec requirement | Task |
|---|---|
| Rename JF fields; drop old keys | 2 (payload), 3 (HTTP/resources) |
| `cluster` + API labels; keep query `type` | 1 (labels), 2–3 |
| Short `agency_type` + `agency_id` | 1, 2, 3 |
| Group by morph; name from related | 2 |
| One unknown bucket; include in aggregate | 2, 3 |
| No grouping unmatched by `instansi` text | 2 |
| Omit unknown when count 0; unknown last | 2 |
| Dangling / unknown FQCN → unknown | 1 (null short type), 2 |
| `aggregate` unchanged | 2 (no change to computeSlice) |
| OpenAPI / Scramble example | 3 |
| SQLite morph columns in API tests | 2 |
| No migration / Filament / matching | none (out of scope) |
