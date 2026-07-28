<?php

namespace Tests\Feature;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;
use App\Imports\MasterJfImport;
use App\Models\CRole;
use App\Models\MasterJf;
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
            'status' => 'active',
            'status_kepegawaian' => 'PNS',
        ]);

        $row->refresh();

        $this->assertSame('After Import', $row->nama);
        $this->assertSame($role->id, $row->c_role_id);
    }
}
