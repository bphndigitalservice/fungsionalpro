<?php

namespace Tests\Feature\Services;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\CRoleAssignation;
use App\Enums\SystemRole;
use App\Models\Client;
use App\Models\CRole;
use App\Models\CRoleLevel;
use App\Models\RegDepartment;
use App\Models\RegGrade;
use App\Models\User;
use App\Models\VerifierAccess;
use App\Services\ClientAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VerifierAccessScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate(SystemRole::Verifier->value, 'web');
    }

    public function test_bphn_scope_grants_access_to_all_agencies_for_same_jabatan(): void
    {
        $verifier = User::factory()->create();
        $verifier->assignRole(SystemRole::Verifier->value);

        $jabatan = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $level = CRoleLevel::create(['c_role_id' => $jabatan->id, 'level' => 'Ahli Pertama']);
        $grade = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);

        $departmentA = RegDepartment::create(['name' => 'Kementerian A']);
        $departmentB = RegDepartment::create(['name' => 'Kementerian B']);

        VerifierAccess::create([
            'user_id' => $verifier->id,
            'c_role_id' => $jabatan->id,
            'entity_type' => null,
            'entity_id' => null,
        ]);

        $clientA = $this->createClient($jabatan, $level, $grade, RegDepartment::class, $departmentA->id);
        $clientB = $this->createClient($jabatan, $level, $grade, RegDepartment::class, $departmentB->id, '987654321098765432');

        $service = app(ClientAccessService::class);

        $this->assertTrue($service->canAccess($verifier, $clientA));
        $this->assertTrue($service->canAccess($verifier, $clientB));
    }

    public function test_regional_scope_only_grants_matching_agency(): void
    {
        $verifier = User::factory()->create();
        $verifier->assignRole(SystemRole::Verifier->value);

        $jabatan = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $level = CRoleLevel::create(['c_role_id' => $jabatan->id, 'level' => 'Ahli Pertama']);
        $grade = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);

        $departmentA = RegDepartment::create(['name' => 'Kementerian A']);
        $departmentB = RegDepartment::create(['name' => 'Kementerian B']);

        VerifierAccess::create([
            'user_id' => $verifier->id,
            'c_role_id' => $jabatan->id,
            'entity_type' => RegDepartment::class,
            'entity_id' => $departmentA->id,
        ]);

        $clientA = $this->createClient($jabatan, $level, $grade, RegDepartment::class, $departmentA->id);
        $clientB = $this->createClient($jabatan, $level, $grade, RegDepartment::class, $departmentB->id, '987654321098765432');

        $service = app(ClientAccessService::class);

        $this->assertTrue($service->canAccess($verifier, $clientA));
        $this->assertFalse($service->canAccess($verifier, $clientB));
    }

    public function test_bphn_scope_does_not_grant_other_jabatan(): void
    {
        $verifier = User::factory()->create();
        $verifier->assignRole(SystemRole::Verifier->value);

        $jabatanA = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $jabatanB = CRole::create(['role_name' => 'Penyuluh Hukum', 'active' => true]);
        $level = CRoleLevel::create(['c_role_id' => $jabatanA->id, 'level' => 'Ahli Pertama']);
        $levelB = CRoleLevel::create(['c_role_id' => $jabatanB->id, 'level' => 'Ahli Muda']);
        $grade = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);
        $department = RegDepartment::create(['name' => 'Kementerian A']);

        VerifierAccess::create([
            'user_id' => $verifier->id,
            'c_role_id' => $jabatanA->id,
            'entity_type' => null,
            'entity_id' => null,
        ]);

        $otherJabatanClient = $this->createClient($jabatanB, $levelB, $grade, RegDepartment::class, $department->id, '987654321098765432');

        $service = app(ClientAccessService::class);

        $this->assertFalse($service->canAccess($verifier, $otherJabatanClient));
    }

    private function createClient(
        CRole $jabatan,
        CRoleLevel $level,
        RegGrade $grade,
        string $agencyType,
        int $agencyId,
        string $nip = '123456789012345678',
    ): Client {
        $clientUser = User::factory()->create();

        return Client::create([
            'user_id' => $clientUser->id,
            'c_role_id' => $jabatan->id,
            'c_role_level_id' => $level->id,
            'nip' => $nip,
            'reg_grade_id' => $grade->id,
            'type' => ClientCluster::Central->value,
            'agency_type' => $agencyType,
            'agency_id' => $agencyId,
            'status' => ClientStatus::Active->value,
            'assignation_type' => CRoleAssignation::CPNS->value,
        ]);
    }
}
