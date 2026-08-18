<?php

namespace Tests\Feature\Services;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Models\CRole;
use App\Models\CRoleLevel;
use App\Models\MasterJf;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;
use App\Services\MasterJfAggregateService;
use App\Support\MasterJfAgencyApiMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\EnsuresMasterJfApiSchema;
use Tests\TestCase;

class MasterJfAggregateServiceTest extends TestCase
{
    use EnsuresMasterJfApiSchema;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureMasterJfApiSchema();
    }

    public function test_it_builds_groups_by_jf_type_and_cluster(): void
    {
        $roleA = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $roleB = CRole::create(['role_name' => 'Penyuluh Hukum', 'active' => true]);

        MasterJf::factory()->count(2)->create([
            'c_role_id' => $roleA->id,
            'status' => ClientStatus::Active,
            'type' => ClientCluster::Central,
            'instansi' => 'KEMENTERIAN HUKUM',
        ]);
        MasterJf::factory()->create([
            'c_role_id' => $roleB->id,
            'status' => ClientStatus::Active,
            'type' => ClientCluster::LocalRegency,
            'instansi' => 'Pemerintah Daerah Kabupaten Tangerang',
        ]);

        $result = app(MasterJfAggregateService::class)->aggregate([]);

        $this->assertCount(2, $result['data']);
        $this->assertSame('Analis Hukum', $result['data'][0]['c_role_label']);
        $this->assertSame(ClientCluster::Central->value, $result['data'][0]['cluster']);
        $this->assertSame(2, $result['data'][0]['aggregate']['total_jf']);
        $this->assertSame('Penyuluh Hukum', $result['data'][1]['c_role_label']);
        $this->assertSame(ClientCluster::LocalRegency->value, $result['data'][1]['cluster']);
    }

    public function test_search_matches_nama_or_nip(): void
    {
        MasterJf::factory()->create([
            'nama' => 'Akbar Maulana',
            'nip' => '111',
            'type' => ClientCluster::Central,
            'instansi' => 'KEMENTERIAN HUKUM',
        ]);
        MasterJf::factory()->create([
            'nama' => 'Other Person',
            'nip' => '222',
            'type' => ClientCluster::Central,
            'instansi' => 'KEMENTERIAN HUKUM',
        ]);

        $result = app(MasterJfAggregateService::class)->aggregate(['search' => 'Akbar']);

        $this->assertCount(1, $result['data']);
        $this->assertSame(1, $result['data'][0]['aggregate']['total_jf']);
    }

    public function test_jenjang_filter_matches_jabatan_text(): void
    {
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);

        MasterJf::factory()->create([
            'c_role_id' => $role->id,
            'jabatan' => 'Analis Hukum Ahli Madya',
            'type' => ClientCluster::Central,
            'instansi' => 'KEMENTERIAN HUKUM',
        ]);
        MasterJf::factory()->create([
            'c_role_id' => $role->id,
            'jabatan' => 'Penyuluh Hukum Ahli Pertama',
            'type' => ClientCluster::Central,
            'instansi' => 'KEMENTERIAN AGAMA',
        ]);

        $result = app(MasterJfAggregateService::class)->aggregate(['jenjang' => 'Ahli Madya']);

        $this->assertCount(1, $result['data']);
        $this->assertSame(1, $result['data'][0]['aggregate']['total_jf']);
    }

    public function test_c_role_level_id_filter_resolves_level_to_jabatan_like(): void
    {
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $level = CRoleLevel::create(['c_role_id' => $role->id, 'level' => 'Ahli Utama']);

        MasterJf::factory()->create([
            'jabatan' => 'Analis Hukum Ahli Utama',
            'c_role_level_id' => null,
            'type' => ClientCluster::Central,
            'instansi' => 'KEMENTERIAN HUKUM',
        ]);
        MasterJf::factory()->create([
            'jabatan' => 'Analis Hukum Ahli Muda',
            'c_role_level_id' => null,
            'type' => ClientCluster::Central,
            'instansi' => 'KEMENTERIAN HUKUM',
        ]);

        $result = app(MasterJfAggregateService::class)->aggregate(['c_role_level_id' => $level->id]);

        $this->assertCount(1, $result['data']);
        $this->assertSame(1, $result['data'][0]['aggregate']['total_jf']);
    }

    public function test_provinsi_filter_only_applies_when_province_id_is_null(): void
    {
        RegProvince::query()->create(['id' => 11, 'name' => 'ACEH']);

        MasterJf::factory()->create([
            'province_id' => null,
            'provinsi' => 'BENGKULU',
            'type' => ClientCluster::LocalProvince,
            'instansi' => 'Pemerintah Daerah Provinsi Bengkulu',
        ]);
        MasterJf::factory()->create([
            'province_id' => 11,
            'provinsi' => 'ACEH',
            'type' => ClientCluster::LocalProvince,
            'instansi' => 'Pemerintah Daerah Provinsi Aceh',
        ]);

        $result = app(MasterJfAggregateService::class)->aggregate(['provinsi' => 'BENGKULU']);

        $this->assertCount(1, $result['data']);
        $this->assertSame(1, $result['data'][0]['aggregate']['total_jf']);
    }

    public function test_instansi_items_have_name_and_client_count_only(): void
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
        $this->assertSame(3, $result['data'][0]['aggregate']['total_jf']);
        $this->assertArrayNotHasKey('aggregate', $result['data'][0]['data'][0]);
    }

    public function test_empty_instansi_groups_as_unknown(): void
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
        $this->assertArrayNotHasKey('aggregate', $unknown);
    }

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
        $this->assertSame(ClientCluster::LocalRegency->value, $result['data'][0]['cluster']);
        $this->assertSame(1, $result['data'][0]['aggregate']['total_jf']);
    }

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
        $this->assertSame(ClientCluster::LocalProvince->value, $result['data'][0]['cluster']);
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
        $this->assertSame(ClientCluster::Central->value, $result['data'][0]['cluster']);
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
        $this->assertSame(ClientCluster::LocalProvince->value, $result['data'][0]['cluster']);
        $this->assertSame(1, $result['data'][0]['aggregate']['total_jf']);
    }

    public function test_it_resolves_effective_cluster_when_type_is_null(): void
    {
        MasterJf::factory()->create([
            'instansi' => 'PEMERINTAH DAERAH KOTA DENPASAR',
            'type' => null,
            'jabatan' => 'Analis Hukum Ahli Utama',
        ]);
        MasterJf::factory()->create([
            'instansi' => 'KEMENTERIAN HUKUM',
            'type' => ClientCluster::Central,
            'jabatan' => 'Analis Hukum Ahli Muda',
        ]);

        $result = app(MasterJfAggregateService::class)->aggregate([]);

        $this->assertCount(2, $result['data']);

        $clusters = array_column($result['data'], 'cluster');
        $this->assertContains(ClientCluster::LocalRegency->value, $clusters);
        $this->assertContains(ClientCluster::Central->value, $clusters);
    }

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
}
