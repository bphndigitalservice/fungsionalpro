<?php

namespace Tests\Feature\Api\V1;

use App\Models\RegGrade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegGradeIndexTest extends TestCase
{
    use RefreshDatabase;

    private function apiHeaders(): array
    {
        return ['X-Api-Key' => 'test-superapps-key'];
    }

    public function test_it_requires_api_key(): void
    {
        $this->getJson('/api/v1/reg-grades')->assertUnauthorized();
    }

    public function test_it_returns_grades_ordered_by_id(): void
    {
        $pembina = RegGrade::create(['grade_name' => 'Pembina (IV/a)', 'grade_code' => 'IVa']);
        $penata = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'IIIc']);

        $this->getJson('/api/v1/reg-grades', $this->apiHeaders())
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    [
                        'id' => $pembina->id,
                        'name' => 'IVa',
                        'grade_name' => 'Pembina (IV/a)',
                        'grade_code' => 'IVa',
                    ],
                    [
                        'id' => $penata->id,
                        'name' => 'IIIc',
                        'grade_name' => 'Penata',
                        'grade_code' => 'IIIc',
                    ],
                ],
            ]);
    }
}
