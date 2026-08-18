<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Models\CRole;
use App\Models\MasterJf;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Support\MasterJfAgencyApiMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\EnsuresMasterJfApiSchema;
use Tests\TestCase;

class MasterJfIndexTest extends TestCase
{
    use EnsuresMasterJfApiSchema;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureMasterJfApiSchema();
    }

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

    public function test_jenjang_filter_narrows_groups(): void
    {
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $hukum = RegDepartment::create(['name' => 'Kementerian Hukum']);
        $agama = RegDepartment::create(['name' => 'Kementerian Agama']);

        MasterJf::factory()->create([
            'c_role_id' => $role->id,
            'type' => ClientCluster::Central,
            'instansi' => 'KEMENTERIAN HUKUM',
            'jabatan' => 'Analis Hukum Ahli Madya',
            'agency_type' => RegDepartment::class,
            'agency_id' => $hukum->id,
        ]);
        MasterJf::factory()->create([
            'c_role_id' => $role->id,
            'type' => ClientCluster::Central,
            'instansi' => 'KEMENTERIAN AGAMA',
            'jabatan' => 'Analis Hukum Ahli Pertama',
            'agency_type' => RegDepartment::class,
            'agency_id' => $agama->id,
        ]);

        $response = $this->getJson('/api/v1/master-jf?jenjang=Ahli%20Madya', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.aggregate.total_jf', 1)
            ->assertJsonPath('data.0.data.0.name', 'Kementerian Hukum');
    }

    public function test_it_separates_analis_and_penyuluh_groups(): void
    {
        $analis = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $penyuluh = CRole::create(['role_name' => 'Penyuluh Hukum', 'active' => true]);

        MasterJf::factory()->create([
            'c_role_id' => $analis->id,
            'type' => ClientCluster::Central,
            'instansi' => 'KEMENTERIAN HUKUM',
            'jabatan' => 'Analis Hukum Ahli Muda',
        ]);
        MasterJf::factory()->create([
            'c_role_id' => $penyuluh->id,
            'type' => ClientCluster::Central,
            'instansi' => 'KEMENTERIAN AGAMA',
            'jabatan' => 'Penyuluh Hukum Ahli Pertama',
        ]);

        $response = $this->getJson('/api/v1/master-jf', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.c_role_label', 'Analis Hukum')
            ->assertJsonPath('data.1.c_role_label', 'Penyuluh Hukum');
    }

    public function test_it_resolves_cluster_from_instansi_when_type_is_null(): void
    {
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);

        MasterJf::factory()->create([
            'c_role_id' => $role->id,
            'type' => null,
            'instansi' => 'Pemerintah Daerah Kabupaten Tangerang',
            'unit_kerja' => 'Sekretariat Daerah',
            'jabatan' => 'Analis Hukum Ahli Muda',
        ]);

        $response = $this->getJson('/api/v1/master-jf', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.cluster', ClientCluster::LocalRegency->value)
            ->assertJsonPath(
                'data.0.cluster_label',
                MasterJfAgencyApiMapper::clusterLabel(ClientCluster::LocalRegency->value),
            );
    }

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
            ->assertJsonPath('data.0.cluster', ClientCluster::LocalProvince->value)
            ->assertJsonPath('data.0.data.0.client_count', 1);
    }

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
            ->assertJsonPath('data.0.cluster', ClientCluster::Central->value)
            ->assertJsonPath('data.0.aggregate.total_jf', 2);
    }

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
            ->assertJsonPath('data.0.cluster', ClientCluster::LocalProvince->value)
            ->assertJsonPath('data.0.aggregate.total_jf', 1);
    }

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
}
