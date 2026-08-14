<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ClientStatus;
use App\Models\CRole;
use App\Models\MasterJf;
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

    public function test_it_returns_aggregations_only(): void
    {
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        MasterJf::factory()->count(3)->create([
            'c_role_id' => $role->id,
            'status' => ClientStatus::Active,
        ]);

        $response = $this->getJson('/api/v1/master-jf', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'agregasi' => [
                    'total_jf',
                    'by_status',
                    'by_status_kepegawaian',
                    'by_pengangkatan',
                    'by_kluster',
                    'by_jabatan_fungsional',
                ],
            ])
            ->assertJsonPath('agregasi.total_jf', 3)
            ->assertJsonMissing(['data', 'total_filtered', 'page', 'per_page']);
    }

    public function test_jenjang_filter_affects_aggregation_total(): void
    {
        MasterJf::factory()->create(['jabatan' => 'Analis Hukum Ahli Madya', 'c_role_level_id' => null]);
        MasterJf::factory()->create(['jabatan' => 'Penyuluh Hukum Ahli Pertama', 'c_role_level_id' => null]);

        $response = $this->getJson('/api/v1/master-jf?jenjang=Ahli%20Madya', $this->apiHeaders());

        $response->assertOk()->assertJsonPath('agregasi.total_jf', 1);
    }
}
