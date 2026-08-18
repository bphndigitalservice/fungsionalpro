<?php

namespace Tests\Feature\Migrations;

use App\Enums\ClientCluster;
use App\Models\MasterJf;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;
use App\Support\MasterJfAgencyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\EnsuresMasterJfApiSchema;
use Tests\Concerns\EnsuresWilayahApiSchema;
use Tests\TestCase;

class BackfillMasterJfAgencyMorphTest extends TestCase
{
    use EnsuresMasterJfApiSchema;
    use EnsuresWilayahApiSchema;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureWilayahApiSchema();
        $this->ensureMasterJfApiSchema();
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
            'province_id' => 11,
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
            'province_id' => 12,
        ]);

        MasterJfAgencyResolver::backfillMasterJf();
        MasterJfAgencyResolver::backfillMasterJf();

        $this->assertDatabaseHas('master_jf', [
            'id' => $matched->id,
            'agency_type' => RegDepartment::class,
            'agency_id' => $department->id,
            'type' => 'central',
            'instansi' => 'Kementerian Hukum',
            'province_id' => 11,
        ]);
        $this->assertDatabaseHas('master_jf', [
            'id' => $missed->id,
            'agency_type' => null,
            'agency_id' => null,
            'instansi' => 'Tidak Ada Di Master',
        ]);
        $this->assertDatabaseHas('master_jf', [
            'id' => $already->id,
            'agency_type' => RegDepartment::class,
            'agency_id' => $other->id,
            'type' => 'central',
            'province_id' => 12,
            'instansi' => 'wrong spelling should not overwrite',
        ]);
    }

    public function test_backfill_writes_province_id_for_province_and_regency_hits(): void
    {
        $province = RegProvince::query()->create(['id' => 51, 'name' => 'BALI']);
        $regency = RegRegency::query()->create([
            'id' => 5103,
            'province_id' => 51,
            'name' => 'KABUPATEN BADUNG',
        ]);

        $provinceRow = MasterJf::factory()->create([
            'instansi' => 'Pemerintah Daerah Provinsi Bali',
            'unit_kerja' => 'Provinsi Bali',
            'agency_type' => null,
            'agency_id' => null,
            'type' => null,
            'province_id' => null,
        ]);
        $regencyRow = MasterJf::factory()->create([
            'instansi' => 'Pemerintah Daerah Kabupaten Badung',
            'unit_kerja' => 'Kabupaten Badung',
            'agency_type' => null,
            'agency_id' => null,
            'type' => null,
            'province_id' => null,
        ]);

        MasterJfAgencyResolver::backfillMasterJf();

        $this->assertDatabaseHas('master_jf', [
            'id' => $provinceRow->id,
            'agency_type' => RegProvince::class,
            'agency_id' => $province->id,
            'type' => 'local_province',
            'province_id' => 51,
        ]);
        $this->assertDatabaseHas('master_jf', [
            'id' => $regencyRow->id,
            'agency_type' => RegRegency::class,
            'agency_id' => $regency->id,
            'type' => 'local_regency',
            'province_id' => 51,
        ]);
    }
}
