<?php

namespace Tests\Feature\Filament;

use App\Enums\SystemRole;
use App\Filament\Resources\VerifierAccessResource;
use App\Filament\Resources\VerifierAccessResource\Pages\CreateVerifierAccess;
use App\Models\CRole;
use App\Models\RegDepartment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VerifierAccessFormTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsSuperAdmin(): User
    {
        Role::findOrCreate(SystemRole::SuperAdmin->value, 'web');
        Role::findOrCreate(SystemRole::Verifier->value, 'web');
        Role::findOrCreate(SystemRole::AdminRegional->value, 'web');

        $user = User::factory()->create();
        $user->assignRole(SystemRole::SuperAdmin->value);
        $this->actingAs($user);

        return $user;
    }

    public function test_can_create_bphn_global_verifier_access(): void
    {
        $this->actingAsSuperAdmin();

        $verifier = User::factory()->create();
        $verifier->assignRole(SystemRole::Verifier->value);
        $jabatan = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);

        Livewire::test(CreateVerifierAccess::class)
            ->fillForm([
                'user_id' => $verifier->id,
                'c_role_id' => $jabatan->id,
                'scope_kind' => VerifierAccessResource::SCOPE_BPHN,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('verifier_accesses', [
            'user_id' => $verifier->id,
            'c_role_id' => $jabatan->id,
            'entity_type' => null,
            'entity_id' => null,
        ]);
    }

    public function test_regional_scope_requires_instansi(): void
    {
        $this->actingAsSuperAdmin();

        $verifier = User::factory()->create();
        $verifier->assignRole(SystemRole::Verifier->value);
        $jabatan = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);

        Livewire::test(CreateVerifierAccess::class)
            ->fillForm([
                'user_id' => $verifier->id,
                'c_role_id' => $jabatan->id,
                'scope_kind' => VerifierAccessResource::SCOPE_REGIONAL,
            ])
            ->call('create')
            ->assertHasFormErrors(['entity_type']);
    }

    public function test_can_create_regional_verifier_access(): void
    {
        $this->actingAsSuperAdmin();

        $verifier = User::factory()->create();
        $verifier->assignRole(SystemRole::Verifier->value);
        $jabatan = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $department = RegDepartment::create(['name' => 'Kementerian Hukum']);

        Livewire::test(CreateVerifierAccess::class)
            ->fillForm([
                'user_id' => $verifier->id,
                'c_role_id' => $jabatan->id,
                'scope_kind' => VerifierAccessResource::SCOPE_REGIONAL,
                'entity_type' => RegDepartment::class,
                'entity_id' => $department->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('verifier_accesses', [
            'user_id' => $verifier->id,
            'c_role_id' => $jabatan->id,
            'entity_type' => RegDepartment::class,
            'entity_id' => $department->id,
        ]);
    }
}
