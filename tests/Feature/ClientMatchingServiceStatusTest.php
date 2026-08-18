<?php

namespace Tests\Feature;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\CRole;
use App\Models\CRoleLevel;
use App\Models\MasterJf;
use App\Models\RegDepartment;
use App\Models\RegGrade;
use App\Services\ClientMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class ClientMatchingServiceStatusTest extends TestCase
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

    public function test_apply_master_data_assigns_client_status_enum_directly(): void
    {
        CRole::create([
            'role_name' => 'Analis Hukum',
            'active' => true,
        ]);

        $master = MasterJf::factory()->create([
            'jabatan' => 'Analis Hukum Ahli Pertama',
            'status' => ClientStatus::NonActive_CTLN,
        ]);

        $client = new Client;
        app(ClientMatchingService::class)->applyMasterData($client, $master);

        $this->assertSame(ClientStatus::NonActive_CTLN, $client->status);
    }

    public function test_apply_master_data_prefers_master_fks(): void
    {
        $grade = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $level = CRoleLevel::create(['c_role_id' => $role->id, 'level' => 'Ahli Pertama']);

        $master = MasterJf::factory()->create([
            'jabatan' => 'Unrelated text that will not fuzzy match',
            'reg_grade_id' => $grade->id,
            'c_role_id' => $role->id,
            'c_role_level_id' => $level->id,
        ]);

        $client = new Client;
        app(ClientMatchingService::class)->applyMasterData($client, $master);

        $this->assertSame($role->id, $client->c_role_id);
        $this->assertSame($level->id, $client->c_role_level_id);
        $this->assertSame($grade->id, $client->reg_grade_id);
    }

    public function test_apply_master_data_falls_back_to_gol_ruang_text(): void
    {
        $grade = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        CRoleLevel::create(['c_role_id' => $role->id, 'level' => 'Ahli Pertama']);

        $master = MasterJf::factory()->create([
            'jabatan' => 'Analis Hukum Ahli Pertama',
            'reg_grade_id' => null,
        ]);
        \Illuminate\Support\Facades\DB::table('master_jf')->where('id', $master->id)->update([
            'gol_ruang' => 'III/a',
        ]);
        $master->refresh();

        $client = new Client;
        app(ClientMatchingService::class)->applyMasterData($client, $master);

        $this->assertSame($grade->id, $client->reg_grade_id);
    }

    public function test_apply_master_data_copies_linked_agency_morph(): void
    {
        CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $department = RegDepartment::create(['name' => 'Kementerian Hukum']);

        $master = MasterJf::factory()->create([
            'jabatan' => 'Analis Hukum Ahli Pertama',
            'instansi' => 'spelling that would not match',
            'unit_kerja' => 'nope',
            'agency_type' => RegDepartment::class,
            'agency_id' => $department->id,
            'type' => ClientCluster::Central,
        ]);

        $client = new Client;
        app(ClientMatchingService::class)->applyMasterData($client, $master);

        $this->assertSame(ClientCluster::Central, $client->type);
        $this->assertSame(RegDepartment::class, $client->agency_type);
        $this->assertSame($department->id, $client->agency_id);
    }

    public function test_apply_master_data_uses_fuzzy_lookup_when_morph_missing(): void
    {
        CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $department = RegDepartment::create(['name' => 'Kementerian Hukum']);

        $master = MasterJf::factory()->create([
            'jabatan' => 'Analis Hukum Ahli Pertama',
            'instansi' => 'Kementerian Hukum',
            'unit_kerja' => '',
            'agency_type' => null,
            'agency_id' => null,
        ]);

        $client = new Client;
        app(ClientMatchingService::class)->applyMasterData($client, $master);

        $this->assertSame(RegDepartment::class, $client->agency_type);
        $this->assertSame($department->id, $client->agency_id);
    }
}
