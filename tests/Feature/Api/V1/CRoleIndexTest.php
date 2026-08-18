<?php

namespace Tests\Feature\Api\V1;

use App\Models\CRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CRoleIndexTest extends TestCase
{
    use RefreshDatabase;

    private function apiHeaders(): array
    {
        return ['X-Api-Key' => 'test-superapps-key'];
    }

    public function test_it_requires_api_key(): void
    {
        $this->getJson('/api/v1/c-roles')->assertUnauthorized();
    }

    public function test_it_returns_active_roles_ordered_by_id(): void
    {
        $penyuluh = CRole::create(['role_name' => 'Penyuluh Hukum', 'active' => true]);
        $analis = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        CRole::create(['role_name' => 'Inactive Role', 'active' => false]);

        $this->getJson('/api/v1/c-roles', $this->apiHeaders())
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    ['id' => $penyuluh->id, 'name' => 'Penyuluh Hukum'],
                    ['id' => $analis->id, 'name' => 'Analis Hukum'],
                ],
            ]);
    }
}
