<?php

namespace Tests\Feature\Filament;

use App\Enums\SystemRole;
use App\Filament\Resources\AdminAccessResource\Pages\CreateAdminAccess;
use App\Filament\Resources\AdminAccessResource\Pages\EditAdminAccess;
use App\Models\AdminAccess;
use App\Models\CRole;
use App\Models\RegDepartment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessFormTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsSuperAdmin(): User
    {
        Role::findOrCreate(SystemRole::SuperAdmin->value, 'web');
        Role::findOrCreate(SystemRole::Admin->value, 'web');
        Role::findOrCreate(SystemRole::AdminInstansi->value, 'web');

        $user = User::factory()->create();
        $user->assignRole(SystemRole::SuperAdmin->value);
        $this->actingAs($user);

        return $user;
    }

    public function test_admin_instansi_user_cannot_be_saved_without_region(): void
    {
        $this->actingAsSuperAdmin();

        $instansi = User::factory()->create();
        $instansi->assignRole(SystemRole::AdminInstansi->value);
        $jabatan = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);

        Livewire::test(CreateAdminAccess::class)
            ->fillForm([
                'user_id' => $instansi->id,
                'c_role_id' => $jabatan->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['entity_type']);
    }

    public function test_admin_instansi_user_saves_with_region(): void
    {
        $this->actingAsSuperAdmin();

        $instansi = User::factory()->create();
        $instansi->assignRole(SystemRole::AdminInstansi->value);
        $jabatan = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $department = RegDepartment::create(['name' => 'Kementerian Hukum']);

        Livewire::test(CreateAdminAccess::class)
            ->fillForm([
                'user_id' => $instansi->id,
                'c_role_id' => $jabatan->id,
                'entity_type' => RegDepartment::class,
                'entity_id' => $department->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('admin_accesses', [
            'user_id' => $instansi->id,
            'c_role_id' => $jabatan->id,
            'entity_type' => RegDepartment::class,
            'entity_id' => $department->id,
        ]);
    }

    public function test_admin_user_saves_without_region(): void
    {
        $this->actingAsSuperAdmin();

        $admin = User::factory()->create();
        $admin->assignRole(SystemRole::Admin->value);
        $jabatan = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);

        Livewire::test(CreateAdminAccess::class)
            ->fillForm([
                'user_id' => $admin->id,
                'c_role_id' => $jabatan->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('admin_accesses', [
            'user_id' => $admin->id,
            'c_role_id' => $jabatan->id,
            'entity_type' => null,
            'entity_id' => null,
        ]);
    }

    public function test_switching_to_admin_clears_region(): void
    {
        $this->actingAsSuperAdmin();

        $instansi = User::factory()->create();
        $instansi->assignRole(SystemRole::AdminInstansi->value);
        $admin = User::factory()->create();
        $admin->assignRole(SystemRole::Admin->value);
        $jabatan = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $department = RegDepartment::create(['name' => 'Kementerian Hukum']);

        $record = AdminAccess::create([
            'user_id' => $instansi->id,
            'c_role_id' => $jabatan->id,
            'entity_type' => RegDepartment::class,
            'entity_id' => $department->id,
        ]);

        Livewire::test(EditAdminAccess::class, ['record' => $record->getRouteKey()])
            ->fillForm([
                'user_id' => $admin->id,
                'c_role_id' => $jabatan->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $record->refresh();

        $this->assertTrue($record->user->is($admin));
        $this->assertNull($record->entity_type);
        $this->assertNull($record->entity_id);
    }

    public function test_creating_admin_with_region_filled_still_saves_null_region(): void
    {
        $this->actingAsSuperAdmin();

        $admin = User::factory()->create();
        $admin->assignRole(SystemRole::Admin->value);
        $jabatan = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $department = RegDepartment::create(['name' => 'Kementerian Hukum']);

        Livewire::test(CreateAdminAccess::class)
            ->fillForm([
                'user_id' => $admin->id,
                'c_role_id' => $jabatan->id,
                'entity_type' => RegDepartment::class,
                'entity_id' => $department->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('admin_accesses', [
            'user_id' => $admin->id,
            'c_role_id' => $jabatan->id,
            'entity_type' => null,
            'entity_id' => null,
        ]);
    }
}
