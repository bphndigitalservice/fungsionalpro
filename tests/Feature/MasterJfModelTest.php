<?php

namespace Tests\Feature;

use App\Models\MasterJf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterJfModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_status_kepegawaian_via_factory(): void
    {
        $row = MasterJf::factory()->create([
            'status_kepegawaian' => 'PNS',
            'status' => 'Aktif',
        ]);

        $this->assertDatabaseHas('master_jf', [
            'id' => $row->id,
            'status_kepegawaian' => 'PNS',
            'status' => 'Aktif',
        ]);
    }

    public function test_status_options_match_known_keys(): void
    {
        $options = MasterJf::statusOptions();

        $this->assertArrayHasKey('Aktif', $options);
        $this->assertArrayHasKey('CTLN', $options);
    }
}
