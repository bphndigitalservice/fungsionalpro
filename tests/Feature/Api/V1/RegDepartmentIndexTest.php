<?php

namespace Tests\Feature\Api\V1;

use App\Models\RegDepartment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegDepartmentIndexTest extends TestCase
{
    use RefreshDatabase;

    private function apiHeaders(): array
    {
        return ['X-Api-Key' => 'test-superapps-key'];
    }

    public function test_it_requires_api_key(): void
    {
        $this->getJson('/api/v1/reg-departments')->assertUnauthorized();
    }

    public function test_it_returns_departments_ordered_by_id(): void
    {
        $kemenkum = RegDepartment::create(['name' => 'Kementerian Hukum']);
        $setneg = RegDepartment::create(['name' => 'Kementerian Sekretariat Negara']);

        $this->getJson('/api/v1/reg-departments', $this->apiHeaders())
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    ['id' => $kemenkum->id, 'name' => 'Kementerian Hukum'],
                    ['id' => $setneg->id, 'name' => 'Kementerian Sekretariat Negara'],
                ],
            ]);
    }
}
