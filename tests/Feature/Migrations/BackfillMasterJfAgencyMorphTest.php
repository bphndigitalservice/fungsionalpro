<?php

namespace Tests\Feature\Migrations;

use App\Enums\ClientCluster;
use App\Models\MasterJf;
use App\Models\RegDepartment;
use App\Support\MasterJfAgencyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\EnsuresWilayahApiSchema;
use Tests\TestCase;

class BackfillMasterJfAgencyMorphTest extends TestCase
{
    use EnsuresWilayahApiSchema;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureWilayahApiSchema();
    }

    public function test_backfill_sets_morph_on_match_and_skips_miss_and_linked(): void
    {
        $department = RegDepartment::create(['name' => 'Kementerian Hukum']);
        $other = RegDepartment::create(['name' => 'Kementerian Agama']);

        $matched = MasterJf::factory()->create([
            'instansi' => 'Kementerian Hukum',
            'unit_kerja' => '',
            'agency_type' => null,
            'agency_id' => null,
            'type' => null,
        ]);
        $missed = MasterJf::factory()->create([
            'instansi' => 'Tidak Ada Di Master',
            'unit_kerja' => 'X',
            'agency_type' => null,
            'agency_id' => null,
        ]);
        $already = MasterJf::factory()->create([
            'instansi' => 'wrong spelling should not overwrite',
            'agency_type' => RegDepartment::class,
            'agency_id' => $other->id,
            'type' => ClientCluster::Central,
        ]);

        MasterJfAgencyResolver::backfillMasterJf();
        MasterJfAgencyResolver::backfillMasterJf();

        $this->assertDatabaseHas('master_jf', [
            'id' => $matched->id,
            'agency_type' => RegDepartment::class,
            'agency_id' => $department->id,
            'type' => 'central',
            'instansi' => 'Kementerian Hukum',
        ]);
        $this->assertDatabaseHas('master_jf', [
            'id' => $missed->id,
            'agency_type' => null,
            'agency_id' => null,
            'instansi' => 'Tidak Ada Di Master',
        ]);
        $this->assertDatabaseHas('master_jf', [
            'id' => $already->id,
            'agency_id' => $other->id,
            'instansi' => 'wrong spelling should not overwrite',
        ]);
    }
}
