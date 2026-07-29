<?php

namespace Tests\Feature;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;
use App\Imports\MasterJfImport;
use App\Models\CRole;
use App\Models\CRoleLevel;
use App\Models\MasterJf;
use App\Models\RegGrade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class MasterJfModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('reg_provinces')) {
            Schema::create('reg_provinces', function (Blueprint $table) {
                $table->integer('id')->primary();
                $table->string('name');
            });
        }
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
}
