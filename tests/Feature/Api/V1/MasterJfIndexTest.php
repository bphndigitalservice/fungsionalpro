<?php

namespace Tests\Feature\Api\V1;

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

    public function test_it_returns_paginated_payload_with_aggregations(): void
    {
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        MasterJf::factory()->count(3)->create([
            'c_role_id' => $role->id,
            'status' => ClientStatus::Active,
        ]);

        $response = $this->getJson('/api/v1/master-jf?per_page=2', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'total_filtered', 'page', 'per_page', 'total_pages', 'agregasi', 'data',
            ])
            ->assertJsonPath('total_filtered', 3)
            ->assertJsonPath('agregasi.total_jf', 3)
            ->assertJsonCount(2, 'data');
    }

    public function test_per_page_over_max_returns_422(): void
    {
        $this->getJson('/api/v1/master-jf?per_page=101', $this->apiHeaders())
            ->assertUnprocessable();
    }

    public function test_province_hybrid_uses_reg_provinces_name(): void
    {
        $province = RegProvince::query()->create(['id' => 11, 'name' => 'ACEH']);

        MasterJf::factory()->create([
            'province_id' => $province->id,
            'provinsi' => 'legacy text',
        ]);

        $response = $this->getJson('/api/v1/master-jf', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('data.0.provinsi', 'ACEH')
            ->assertJsonPath('data.0.provinsi_id', 11);
    }

    public function test_province_fallback_uses_text_column_when_fk_null(): void
    {
        MasterJf::factory()->create([
            'province_id' => null,
            'provinsi' => 'BENGKULU',
        ]);

        $response = $this->getJson('/api/v1/master-jf', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('data.0.provinsi', 'BENGKULU')
            ->assertJsonPath('data.0.provinsi_id', null);
    }

    public function test_jenjang_is_parsed_from_jabatan_when_level_fk_null(): void
    {
        MasterJf::factory()->create([
            'c_role_level_id' => null,
            'jabatan' => 'Penyuluh Hukum Ahli Muda',
        ]);

        $response = $this->getJson('/api/v1/master-jf', $this->apiHeaders());

        $response->assertOk()
            ->assertJsonPath('data.0.jenjang', 'Ahli Muda');
    }

    public function test_jenjang_filter_matches_jabatan_like_filament(): void
    {
        MasterJf::factory()->create(['jabatan' => 'Analis Hukum Ahli Madya', 'c_role_level_id' => null]);
        MasterJf::factory()->create(['jabatan' => 'Penyuluh Hukum Ahli Pertama', 'c_role_level_id' => null]);

        $response = $this->getJson('/api/v1/master-jf?jenjang=Ahli%20Madya', $this->apiHeaders());

        $response->assertOk()->assertJsonPath('total_filtered', 1);
    }
}
