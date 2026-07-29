<?php

namespace Tests\Feature\Filament;

use App\Enums\SystemRole;
use App\Filament\Resources\MasterJfResource\Pages\EditMasterJf;
use App\Models\CRole;
use App\Models\CRoleLevel;
use App\Models\MasterJf;
use App\Models\RegGrade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MasterJfEditFormTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsAdmin(): User
    {
        Role::findOrCreate(SystemRole::Admin->value, 'web');
        $user = User::factory()->create();
        $user->assignRole(SystemRole::Admin->value);
        $this->actingAs($user);

        return $user;
    }

    public function test_edit_saves_reg_grade_and_c_role_level(): void
    {
        $this->actingAsAdmin();

        $grade = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $level = CRoleLevel::create(['c_role_id' => $role->id, 'level' => 'Ahli Pertama']);

        $record = MasterJf::factory()->create([
            'c_role_id' => $role->id,
        ]);

        Livewire::test(EditMasterJf::class, ['record' => $record->getRouteKey()])
            ->fillForm([
                'nama' => $record->nama,
                'nip' => $record->nip,
                'reg_grade_id' => $grade->id,
                'c_role_id' => $role->id,
                'c_role_level_id' => $level->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('master_jf', [
            'id' => $record->id,
            'reg_grade_id' => $grade->id,
            'c_role_level_id' => $level->id,
        ]);
    }

    public function test_changing_c_role_clears_c_role_level(): void
    {
        $this->actingAsAdmin();

        $roleA = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $roleB = CRole::create(['role_name' => 'Penyuluh Hukum', 'active' => true]);
        $levelA = CRoleLevel::create(['c_role_id' => $roleA->id, 'level' => 'Ahli Pertama']);
        CRoleLevel::create(['c_role_id' => $roleB->id, 'level' => 'Ahli Muda']);

        $record = MasterJf::factory()->create([
            'c_role_id' => $roleA->id,
            'c_role_level_id' => $levelA->id,
        ]);

        Livewire::test(EditMasterJf::class, ['record' => $record->getRouteKey()])
            ->assertFormSet([
                'c_role_id' => $roleA->id,
                'c_role_level_id' => $levelA->id,
            ])
            ->fillForm([
                'c_role_id' => $roleB->id,
            ])
            ->assertFormSet([
                'c_role_level_id' => null,
            ]);
    }

    public function test_clearing_c_role_persists_null_c_role_level(): void
    {
        $this->actingAsAdmin();

        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $level = CRoleLevel::create(['c_role_id' => $role->id, 'level' => 'Ahli Pertama']);

        $record = MasterJf::factory()->create([
            'c_role_id' => $role->id,
            'c_role_level_id' => $level->id,
        ]);

        Livewire::test(EditMasterJf::class, ['record' => $record->getRouteKey()])
            ->fillForm([
                'c_role_id' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('master_jf', [
            'id' => $record->id,
            'c_role_id' => null,
            'c_role_level_id' => null,
        ]);
    }
}
