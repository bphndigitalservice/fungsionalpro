<?php

namespace Tests\Feature\Services;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Models\CRole;
use App\Models\CRoleLevel;
use App\Models\MasterJf;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Services\MasterJfAggregateService;
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
        $this->assertSame('Analis Hukum', $result['data'][0]['jf_label']);
        $this->assertSame(ClientCluster::Central->value, $result['data'][0]['cluster_id']);
        $this->assertSame(2, $result['data'][0]['aggregate']['total_jf']);
        $this->assertSame('Penyuluh Hukum', $result['data'][1]['jf_label']);
        $this->assertSame(ClientCluster::LocalRegency->value, $result['data'][1]['cluster_id']);
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
        $instansi = $result['data'][0]['data'];

        $this->assertCount(2, $instansi);

        $kemenkumham = collect($instansi)->firstWhere('name', 'KEMENTERIAN HUKUM');
        $this->assertNotNull($kemenkumham);
        $this->assertSame(2, $kemenkumham['client_count']);
        $this->assertArrayNotHasKey('aggregate', $kemenkumham);
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
        $this->assertSame(ClientCluster::LocalRegency->value, $result['data'][0]['cluster_id']);
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

        $clusters = array_column($result['data'], 'cluster_id');
        $this->assertContains(ClientCluster::LocalRegency->value, $clusters);
        $this->assertContains(ClientCluster::Central->value, $clusters);
    }

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
}
