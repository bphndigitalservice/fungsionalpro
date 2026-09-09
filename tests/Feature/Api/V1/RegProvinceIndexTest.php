<?php

namespace Tests\Feature\Api\V1;

use App\Models\RegProvince;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\EnsuresWilayahApiSchema;
use Tests\TestCase;

class RegProvinceIndexTest extends TestCase
{
    use EnsuresWilayahApiSchema;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureWilayahApiSchema();
    }

    private function apiHeaders(): array
    {
        return ['X-Api-Key' => 'test-superapps-key'];
    }

    public function test_it_requires_api_key(): void
    {
        $this->getJson('/api/v1/reg-provinces')->assertUnauthorized();
    }

    public function test_it_returns_provinces_ordered_by_id(): void
    {
        RegProvince::query()->create(['id' => 51, 'name' => 'BALI']);
        RegProvince::query()->create(['id' => 11, 'name' => 'ACEH']);

        $this->getJson('/api/v1/reg-provinces', $this->apiHeaders())
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    ['id' => 11, 'name' => 'ACEH'],
                    ['id' => 51, 'name' => 'BALI'],
                ],
            ]);
    }
}
