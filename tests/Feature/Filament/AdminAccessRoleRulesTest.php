<?php

namespace Tests\Feature\Filament;

use App\Enums\SystemRole;
use App\Filament\Resources\AdminAccessResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessRoleRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate(SystemRole::Admin->value, 'web');
        Role::findOrCreate(SystemRole::AdminInstansi->value, 'web');
        Role::findOrCreate(SystemRole::Verifier->value, 'web');
    }

    public function test_eligible_users_query_includes_admin_and_admin_instansi_only(): void
    {
        $admin = User::factory()->create(['name' => 'Admin User']);
        $admin->assignRole(SystemRole::Admin->value);

        $instansi = User::factory()->create(['name' => 'Instansi User']);
        $instansi->assignRole(SystemRole::AdminInstansi->value);

        $verifier = User::factory()->create(['name' => 'Verifier User']);
        $verifier->assignRole(SystemRole::Verifier->value);

        $ids = AdminAccessResource::eligibleUsersQuery()->pluck('id');

        $this->assertTrue($ids->contains($admin->id));
        $this->assertTrue($ids->contains($instansi->id));
        $this->assertFalse($ids->contains($verifier->id));
    }

    public function test_admin_user_does_not_require_region(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(SystemRole::Admin->value);

        $this->assertFalse(AdminAccessResource::selectedUserRequiresRegion($admin->id));
    }

    public function test_admin_instansi_user_requires_region(): void
    {
        $instansi = User::factory()->create();
        $instansi->assignRole(SystemRole::AdminInstansi->value);

        $this->assertTrue(AdminAccessResource::selectedUserRequiresRegion($instansi->id));
    }

    public function test_dual_role_user_is_treated_as_admin(): void
    {
        $both = User::factory()->create();
        $both->assignRole([SystemRole::Admin->value, SystemRole::AdminInstansi->value]);

        $this->assertFalse(AdminAccessResource::selectedUserRequiresRegion($both->id));
    }

    public function test_missing_user_does_not_require_region(): void
    {
        $this->assertFalse(AdminAccessResource::selectedUserRequiresRegion(null));
        $this->assertFalse(AdminAccessResource::selectedUserRequiresRegion(999999));
    }

    public function test_form_data_clears_region_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(SystemRole::Admin->value);

        $result = AdminAccessResource::formDataForSelectedUser([
            'user_id' => $admin->id,
            'c_role_id' => 1,
            'entity_type' => 'App\\Models\\RegDepartment',
            'entity_id' => 5,
        ]);

        $this->assertNull($result['entity_type']);
        $this->assertNull($result['entity_id']);
    }

    public function test_form_data_keeps_region_for_admin_instansi(): void
    {
        $instansi = User::factory()->create();
        $instansi->assignRole(SystemRole::AdminInstansi->value);

        $result = AdminAccessResource::formDataForSelectedUser([
            'user_id' => $instansi->id,
            'c_role_id' => 1,
            'entity_type' => 'App\\Models\\RegDepartment',
            'entity_id' => 5,
        ]);

        $this->assertSame('App\\Models\\RegDepartment', $result['entity_type']);
        $this->assertSame(5, $result['entity_id']);
    }
}
