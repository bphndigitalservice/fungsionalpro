<?php

namespace Tests\Feature\Filament\Plugins;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\CRoleAssignation;
use App\Enums\SystemRole;
use App\Filament\Resources\ClientActivityResource\Pages\ListClientActivities;
use App\Models\Client;
use App\Models\CRole;
use App\Models\CRoleLevel;
use App\Models\RegDepartment;
use App\Models\RegDepartmentEchelon1;
use App\Models\RegGrade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithFilament;
use Tests\TestCase;

class MediaActionSmokeTest extends TestCase
{
    use InteractsWithFilament;
    use RefreshDatabase;

    public function test_client_activity_table_registers_media_action(): void
    {
        $user = $this->createClientUserWithPhoto();
        $this->actingAsFilamentUser($user);

        Livewire::test(ListClientActivities::class)
            ->assertSuccessful()
            ->assertTableActionExists('media');
    }

    private function createClientUserWithPhoto(): User
    {
        $user = $this->createUserWithPermissions(
            ['view_any_client::activity'],
            SystemRole::Client,
        );

        $role = CRole::query()->create(['role_name' => 'Analis Hukum']);
        $roleLevel = CRoleLevel::query()->create([
            'c_role_id' => $role->id,
            'level' => 'Pertama',
        ]);
        $grade = RegGrade::query()->create([
            'grade_name' => 'III/a',
            'grade_code' => 'IIIa',
        ]);
        $department = RegDepartment::query()->create(['name' => 'Test Department']);
        $echelon = RegDepartmentEchelon1::query()->create([
            'department_id' => $department->id,
            'name' => 'Test Echelon',
        ]);

        $client = Client::query()->create([
            'user_id' => $user->id,
            'c_role_id' => $role->id,
            'c_role_level_id' => $roleLevel->id,
            'nip' => '000000000000001',
            'reg_grade_id' => $grade->id,
            'type' => ClientCluster::Central,
            'agency_type' => RegDepartment::class,
            'agency_id' => $department->id,
            'echelon_type' => RegDepartmentEchelon1::class,
            'echelon_id' => $echelon->id,
            'status' => ClientStatus::Active,
            'assignation_type' => CRoleAssignation::CPNS,
        ]);

        $client->identity()->update(['photo' => 'photos/test.jpg']);

        return $user;
    }
}
