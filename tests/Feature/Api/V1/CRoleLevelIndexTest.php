<?php

namespace Tests\Feature\Api\V1;

use App\Models\CRole;
use App\Models\CRoleLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CRoleLevelIndexTest extends TestCase
{
    use RefreshDatabase;

    private function apiHeaders(): array
    {
        return ['X-Api-Key' => 'test-superapps-key'];
    }

    public function test_it_requires_api_key(): void
    {
        $this->getJson('/api/v1/c-role-levels')->assertUnauthorized();
    }

    public function test_it_returns_all_levels_ordered_by_id(): void
    {
        $analis = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $penyuluh = CRole::create(['role_name' => 'Penyuluh Hukum', 'active' => true]);
        $pertama = CRoleLevel::create(['c_role_id' => $analis->id, 'level' => 'Ahli Pertama']);
        $muda = CRoleLevel::create(['c_role_id' => $penyuluh->id, 'level' => 'Ahli Muda']);

        $this->getJson('/api/v1/c-role-levels', $this->apiHeaders())
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    ['id' => $pertama->id, 'c_role_id' => $analis->id, 'name' => 'Ahli Pertama'],
                    ['id' => $muda->id, 'c_role_id' => $penyuluh->id, 'name' => 'Ahli Muda'],
                ],
            ]);
    }

    public function test_it_filters_levels_by_c_role_id(): void
    {
        $analis = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $penyuluh = CRole::create(['role_name' => 'Penyuluh Hukum', 'active' => true]);
        $pertama = CRoleLevel::create(['c_role_id' => $analis->id, 'level' => 'Ahli Pertama']);
        CRoleLevel::create(['c_role_id' => $penyuluh->id, 'level' => 'Ahli Muda']);

        $this->getJson('/api/v1/c-role-levels?c_role_id='.$analis->id, $this->apiHeaders())
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    ['id' => $pertama->id, 'c_role_id' => $analis->id, 'name' => 'Ahli Pertama'],
                ],
            ]);
    }

    public function test_it_rejects_unknown_c_role_id(): void
    {
        $this->getJson('/api/v1/c-role-levels?c_role_id=99999', $this->apiHeaders())
            ->assertUnprocessable();
    }
}
