<?php

namespace Tests\Feature;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;
use App\Imports\MasterJfImport;
use App\Models\CRole;
use App\Models\CRoleLevel;
use App\Models\MasterJf;
use App\Models\RegDepartment;
use App\Models\RegGrade;
use App\Models\RegProvince;
use App\Models\RegRegency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\EnsuresMasterJfApiSchema;
use Tests\Concerns\EnsuresWilayahApiSchema;
use Tests\TestCase;

class MasterJfModelTest extends TestCase
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

    public function test_it_persists_status_kepegawaian_via_factory(): void
    {
        $row = MasterJf::factory()->create([
            'status_kepegawaian' => JenisKepegawaian::PNS,
            'status' => ClientStatus::Active,
        ]);

        $this->assertDatabaseHas('master_jf', [
            'id' => $row->id,
            'status_kepegawaian' => 'PNS',
            'status' => 'active',
        ]);

        $this->assertInstanceOf(ClientStatus::class, $row->status);
        $this->assertInstanceOf(JenisKepegawaian::class, $row->status_kepegawaian);
    }

    public function test_it_casts_type_to_client_cluster(): void
    {
        $row = MasterJf::factory()->create([
            'type' => ClientCluster::Central,
        ]);

        $this->assertDatabaseHas('master_jf', [
            'id' => $row->id,
            'type' => 'central',
        ]);
        $this->assertInstanceOf(ClientCluster::class, $row->fresh()->type);
    }

    public function test_it_persists_c_role_id_and_loads_relation(): void
    {
        $role = CRole::create([
            'role_name' => 'Analis Hukum',
            'active' => true,
        ]);

        $row = MasterJf::factory()->create([
            'c_role_id' => $role->id,
        ]);

        $this->assertDatabaseHas('master_jf', [
            'id' => $row->id,
            'c_role_id' => $role->id,
        ]);

        $this->assertTrue($row->fresh()->cRole->is($role));
    }

    public function test_import_does_not_clear_existing_c_role_id(): void
    {
        $role = CRole::create([
            'role_name' => 'Analis Hukum',
            'active' => true,
        ]);

        $row = MasterJf::factory()->create([
            'nip' => '123456789012345678',
            'c_role_id' => $role->id,
            'nama' => 'Before Import',
        ]);

        (new MasterJfImport)->model([
            'nip' => '123456789012345678',
            'nama' => 'After Import',
            'golruang' => 'III/a',
            'jabatan' => 'Analis Hukum Ahli Pertama',
            'unit_kerjakanwil' => 'Unit A',
            'instansi' => 'Instansi A',
            'pengangkatan' => 'Inpassing',
            'status' => 'Aktif',
            'status_kepegawaian' => 'PNS',
        ]);

        $row->refresh();

        $this->assertSame('After Import', $row->nama);
        $this->assertSame($role->id, $row->c_role_id);
    }

    public function test_import_normalizes_status_label_to_enum_value(): void
    {
        (new MasterJfImport)->model([
            'nip' => '333333333333333333',
            'nama' => 'Imported',
            'golruang' => 'III/a',
            'jabatan' => 'Analis',
            'unit_kerjakanwil' => 'Unit A',
            'instansi' => 'Instansi A',
            'pengangkatan' => 'Inpassing',
            'status' => 'Aktif',
            'status_kepegawaian' => 'PNS',
        ]);

        $this->assertDatabaseHas('master_jf', [
            'nip' => '333333333333333333',
            'status' => 'active',
            'status_kepegawaian' => 'PNS',
        ]);
    }

    public function test_import_nulls_unknown_status(): void
    {
        (new MasterJfImport)->model([
            'nip' => '444444444444444444',
            'nama' => 'Unknown Status',
            'status' => 'bukan-status',
            'status_kepegawaian' => 'Honorer',
        ]);

        $row = MasterJf::query()->where('nip', '444444444444444444')->first();
        $this->assertNotNull($row);
        $this->assertNull($row->status);
        $this->assertNull($row->status_kepegawaian);
    }

    public function test_import_resolves_reg_grade_id_from_golruang(): void
    {
        $grade = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);

        (new MasterJfImport)->model([
            'nip' => '555555555555555555',
            'nama' => 'Imported Grade',
            'golruang' => 'III/a',
            'jabatan' => 'Analis',
            'unit_kerjakanwil' => 'Unit A',
            'instansi' => 'Instansi A',
            'pengangkatan' => 'Inpassing',
            'status' => 'Aktif',
            'status_kepegawaian' => 'PNS',
        ]);

        $row = MasterJf::query()->where('nip', '555555555555555555')->first();
        $this->assertNotNull($row);
        $this->assertSame($grade->id, $row->reg_grade_id);
        $this->assertNull($row->gol_ruang);
    }

    public function test_import_nulls_unknown_golruang(): void
    {
        (new MasterJfImport)->model([
            'nip' => '666666666666666666',
            'nama' => 'Unknown Grade',
            'golruang' => 'ZZ/z',
        ]);

        $row = MasterJf::query()->where('nip', '666666666666666666')->first();
        $this->assertNotNull($row);
        $this->assertNull($row->reg_grade_id);
    }

    public function test_it_persists_reg_grade_and_c_role_level_relations(): void
    {
        $grade = RegGrade::create([
            'grade_name' => 'Penata',
            'grade_code' => 'III/a',
        ]);

        $role = CRole::create([
            'role_name' => 'Analis Hukum',
            'active' => true,
        ]);

        $level = CRoleLevel::create([
            'c_role_id' => $role->id,
            'level' => 'Ahli Pertama',
        ]);

        $row = MasterJf::factory()->create([
            'reg_grade_id' => $grade->id,
            'c_role_id' => $role->id,
            'c_role_level_id' => $level->id,
        ]);

        $this->assertDatabaseHas('master_jf', [
            'id' => $row->id,
            'reg_grade_id' => $grade->id,
            'c_role_level_id' => $level->id,
        ]);

        $fresh = $row->fresh();
        $this->assertTrue($fresh->grade->is($grade));
        $this->assertTrue($fresh->cRoleLevel->is($level));
    }

    public function test_it_resolves_agenciable_for_department_province_and_regency(): void
    {
        $department = RegDepartment::create(['name' => 'Kementerian Hukum']);
        $province = RegProvince::query()->create(['id' => 51, 'name' => 'BALI']);
        $regency = RegRegency::query()->create([
            'id' => 5103,
            'province_id' => 51,
            'name' => 'KABUPATEN BADUNG',
        ]);

        $byDept = MasterJf::factory()->create([
            'agency_type' => RegDepartment::class,
            'agency_id' => $department->id,
        ]);
        $byProv = MasterJf::factory()->create([
            'agency_type' => RegProvince::class,
            'agency_id' => $province->id,
        ]);
        $byReg = MasterJf::factory()->create([
            'agency_type' => RegRegency::class,
            'agency_id' => $regency->id,
        ]);

        $this->assertTrue($byDept->fresh()->agenciable->is($department));
        $this->assertTrue($byProv->fresh()->agenciable->is($province));
        $this->assertTrue($byReg->fresh()->agenciable->is($regency));
        $this->assertTrue($department->masterJfs->contains($byDept));
        $this->assertTrue($province->masterJfs->contains($byProv));
        $this->assertTrue($regency->masterJfs->contains($byReg));
    }

    public function test_import_writes_agency_morph_when_instansi_matches(): void
    {
        $department = RegDepartment::create(['name' => 'Kementerian Hukum']);

        (new MasterJfImport)->model([
            'nip' => '777777777777777777',
            'nama' => 'Imported Morph',
            'instansi' => 'Kementerian Hukum',
            'unit_kerjakanwil' => '',
            'status' => 'Aktif',
            'status_kepegawaian' => 'PNS',
        ]);

        $this->assertDatabaseHas('master_jf', [
            'nip' => '777777777777777777',
            'instansi' => 'Kementerian Hukum',
            'agency_type' => RegDepartment::class,
            'agency_id' => $department->id,
            'type' => 'central',
        ]);
    }

    public function test_import_keeps_existing_morph_when_names_do_not_resolve(): void
    {
        $department = RegDepartment::create(['name' => 'Kementerian Hukum']);

        $row = MasterJf::factory()->create([
            'nip' => '888888888888888888',
            'agency_type' => RegDepartment::class,
            'agency_id' => $department->id,
            'type' => ClientCluster::Central,
        ]);

        (new MasterJfImport)->model([
            'nip' => '888888888888888888',
            'nama' => 'After Import',
            'instansi' => 'Nama Yang Tidak Ada',
            'unit_kerjakanwil' => 'Unit X',
            'status' => 'Aktif',
            'status_kepegawaian' => 'PNS',
        ]);

        $row->refresh();
        $this->assertSame('After Import', $row->nama);
        $this->assertSame('Nama Yang Tidak Ada', $row->instansi);
        $this->assertSame(RegDepartment::class, $row->agency_type);
        $this->assertSame($department->id, $row->agency_id);
    }
}
