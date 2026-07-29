<?php

namespace Tests\Feature\Migrations;

use App\Models\CRole;
use App\Models\CRoleLevel;
use App\Models\MasterJf;
use App\Models\RegGrade;
use App\Support\RegGradeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BackfillMasterJfRegGradeIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_sets_grade_from_gol_ruang_and_leaves_level_null(): void
    {
        $grade = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);

        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        CRoleLevel::create(['c_role_id' => $role->id, 'level' => 'Ahli Pertama']);

        $matched = MasterJf::factory()->create([
            'reg_grade_id' => null,
            'c_role_level_id' => null,
        ]);
        DB::table('master_jf')->where('id', $matched->id)->update(['gol_ruang' => 'III/a']);

        $junk = MasterJf::factory()->create([
            'reg_grade_id' => null,
            'c_role_level_id' => null,
        ]);
        DB::table('master_jf')->where('id', $junk->id)->update(['gol_ruang' => 'not-a-grade']);

        RegGradeResolver::backfillMasterJf();

        $this->assertDatabaseHas('master_jf', [
            'id' => $matched->id,
            'reg_grade_id' => $grade->id,
            'c_role_level_id' => null,
        ]);

        $this->assertDatabaseHas('master_jf', [
            'id' => $junk->id,
            'reg_grade_id' => null,
            'c_role_level_id' => null,
        ]);
    }
}
