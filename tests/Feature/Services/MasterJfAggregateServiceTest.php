<?php

namespace Tests\Feature\Services;

use App\Enums\ClientStatus;
use App\Models\CRole;
use App\Models\CRoleLevel;
use App\Models\MasterJf;
use App\Models\RegProvince;
use App\Services\MasterJfAggregateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\EnsuresMasterJfApiSchema;
use Tests\TestCase;

class MasterJfAggregateServiceTest extends TestCase
{
    use EnsuresMasterJfApiSchema;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureMasterJfApiSchema();
    }

    public function test_it_filters_by_c_role_id_and_aggregates_total(): void
    {
        $roleA = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $roleB = CRole::create(['role_name' => 'Penyuluh Hukum', 'active' => true]);

        MasterJf::factory()->count(2)->create(['c_role_id' => $roleA->id, 'status' => ClientStatus::Active]);
        MasterJf::factory()->create(['c_role_id' => $roleB->id, 'status' => ClientStatus::Active]);

        $service = app(MasterJfAggregateService::class);
        $result = $service->paginate(['c_role_id' => $roleA->id, 'page' => 1, 'per_page' => 10]);

        $this->assertSame(2, $result['total_filtered']);
        $this->assertSame(2, $result['agregasi']['total_jf']);
        $this->assertSame(2, $result['agregasi']['by_jabatan_fungsional']['Analis Hukum'] ?? 0);
        $this->assertCount(2, $result['items']);
    }

    public function test_search_matches_nama_or_nip(): void
    {
        MasterJf::factory()->create(['nama' => 'Akbar Maulana', 'nip' => '111']);
        MasterJf::factory()->create(['nama' => 'Other Person', 'nip' => '222']);

        $service = app(MasterJfAggregateService::class);
        $result = $service->paginate(['search' => 'Akbar', 'page' => 1, 'per_page' => 10]);

        $this->assertSame(1, $result['total_filtered']);
    }

    public function test_jenjang_filter_matches_jabatan_text(): void
    {
        MasterJf::factory()->create(['jabatan' => 'Analis Hukum Ahli Madya']);
        MasterJf::factory()->create(['jabatan' => 'Penyuluh Hukum Ahli Pertama']);

        $service = app(MasterJfAggregateService::class);
        $result = $service->paginate(['jenjang' => 'Ahli Madya', 'page' => 1, 'per_page' => 10]);

        $this->assertSame(1, $result['total_filtered']);
    }

    public function test_c_role_level_id_filter_resolves_level_to_jabatan_like(): void
    {
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $level = CRoleLevel::create(['c_role_id' => $role->id, 'level' => 'Ahli Utama']);

        MasterJf::factory()->create(['jabatan' => 'Analis Hukum Ahli Utama', 'c_role_level_id' => null]);
        MasterJf::factory()->create(['jabatan' => 'Analis Hukum Ahli Muda', 'c_role_level_id' => null]);

        $service = app(MasterJfAggregateService::class);
        $result = $service->paginate(['c_role_level_id' => $level->id, 'page' => 1, 'per_page' => 10]);

        $this->assertSame(1, $result['total_filtered']);
    }

    public function test_provinsi_filter_only_applies_when_province_id_is_null(): void
    {
        RegProvince::query()->create(['id' => 11, 'name' => 'ACEH']);

        MasterJf::factory()->create(['province_id' => null, 'provinsi' => 'BENGKULU']);
        MasterJf::factory()->create(['province_id' => 11, 'provinsi' => 'ACEH']);

        $service = app(MasterJfAggregateService::class);
        $result = $service->paginate(['provinsi' => 'BENGKULU', 'page' => 1, 'per_page' => 10]);

        $this->assertSame(1, $result['total_filtered']);
    }
}
