<?php

namespace Tests\Feature\Api\V1;

use App\Models\RegProvince;
use App\Models\RegRegency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\EnsuresWilayahApiSchema;
use Tests\TestCase;

class RegRegencyIndexTest extends TestCase
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
        $this->getJson('/api/v1/reg-regencies')->assertUnauthorized();
    }

    public function test_it_returns_all_regencies_ordered_by_id(): void
    {
        RegProvince::query()->create(['id' => 11, 'name' => 'ACEH']);
        RegProvince::query()->create(['id' => 51, 'name' => 'BALI']);
        RegRegency::query()->create(['id' => 5103, 'province_id' => 51, 'name' => 'KABUPATEN BADUNG']);
        RegRegency::query()->create(['id' => 1101, 'province_id' => 11, 'name' => 'KABUPATEN SIMEULUE']);

        $this->getJson('/api/v1/reg-regencies', $this->apiHeaders())
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    ['id' => 1101, 'province_id' => 11, 'name' => 'KABUPATEN SIMEULUE'],
                    ['id' => 5103, 'province_id' => 51, 'name' => 'KABUPATEN BADUNG'],
                ],
            ]);
    }

    public function test_it_filters_regencies_by_province_id(): void
    {
        RegProvince::query()->create(['id' => 11, 'name' => 'ACEH']);
        RegProvince::query()->create(['id' => 51, 'name' => 'BALI']);
        RegRegency::query()->create(['id' => 1101, 'province_id' => 11, 'name' => 'KABUPATEN SIMEULUE']);
        RegRegency::query()->create(['id' => 5103, 'province_id' => 51, 'name' => 'KABUPATEN BADUNG']);

        $this->getJson('/api/v1/reg-regencies?province_id=51', $this->apiHeaders())
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    ['id' => 5103, 'province_id' => 51, 'name' => 'KABUPATEN BADUNG'],
                ],
            ]);
    }

    public function test_it_rejects_unknown_province_id(): void
    {
        $this->getJson('/api/v1/reg-regencies?province_id=99999', $this->apiHeaders())
            ->assertUnprocessable();
    }
}
