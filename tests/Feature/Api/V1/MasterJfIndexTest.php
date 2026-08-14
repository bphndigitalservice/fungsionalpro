<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Models\CRole;
use App\Models\MasterJf;
use App\Models\RegProvince;
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
        MasterJf::factory()->count(2)->create([
            'c_role_id' => $role->id,
            'status' => ClientStatus::Active,
            'type' => ClientCluster::Central,
            'instansi' => 'KEMENTERIAN HUKUM',
            'jabatan' => 'Analis Hukum Ahli Muda',
        ]);
        MasterJf::factory()->create([
            'c_role_id' => $role->id,
            'type' => ClientCluster::LocalProvince,
            'instansi' => 'Pemerintah Daerah Provinsi Bali',
            'jabatan' => 'Analis Hukum Ahli Pertama',
        ]);

        $response = $this->getJson('/api/v1/master-jf', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'jf_type_id',
                        'jf_label',
                        'cluster_id',
                        'cluster_label',
                        'aggregate' => [
                            'total_jf',
                            'by_jenjang',
                            'by_status',
                            'by_status_kepegawaian',
                            'by_pengangkatan',
                        ],
                        'data' => [
                            '*' => ['name', 'client_count'],
                        ],
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.jf_label', 'Analis Hukum')
            ->assertJsonPath('data.0.cluster_id', ClientCluster::Central->value)
            ->assertJsonPath('data.0.aggregate.total_jf', 2)
            ->assertJsonPath('data.0.data.0.name', 'KEMENTERIAN HUKUM')
            ->assertJsonPath('data.0.data.0.client_count', 2)
            ->assertJsonMissingPath('data.0.data.0.aggregate')
            ->assertJsonPath('data.1.cluster_id', ClientCluster::LocalProvince->value)
            ->assertJsonPath('data.1.aggregate.by_jenjang.Ahli Pertama', 1)
            ->assertJsonMissing(['agregasi']);
    }

    public function test_jenjang_filter_narrows_groups(): void
    {
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);

        MasterJf::factory()->create([
            'c_role_id' => $role->id,
            'type' => ClientCluster::Central,
            'instansi' => 'KEMENTERIAN HUKUM',
            'jabatan' => 'Analis Hukum Ahli Madya',
        ]);
        MasterJf::factory()->create([
            'c_role_id' => $role->id,
            'type' => ClientCluster::Central,
            'instansi' => 'KEMENTERIAN AGAMA',
            'jabatan' => 'Analis Hukum Ahli Pertama',
        ]);

        $response = $this->getJson('/api/v1/master-jf?jenjang=Ahli%20Madya', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.aggregate.total_jf', 1)
            ->assertJsonPath('data.0.data.0.name', 'KEMENTERIAN HUKUM');
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
            ->assertJsonPath('data.0.jf_label', 'Analis Hukum')
            ->assertJsonPath('data.1.jf_label', 'Penyuluh Hukum');
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
            ->assertJsonPath('data.0.cluster_id', ClientCluster::LocalRegency->value)
            ->assertJsonPath('data.0.cluster_label', 'Pemda - Kabupaten/Kota');
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
            ->assertJsonPath('data.0.cluster_id', ClientCluster::LocalProvince->value)
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
            ->assertJsonPath('data.0.cluster_id', ClientCluster::Central->value)
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
            ->assertJsonPath('data.0.cluster_id', ClientCluster::LocalProvince->value)
            ->assertJsonPath('data.0.aggregate.total_jf', 1);
    }
}
