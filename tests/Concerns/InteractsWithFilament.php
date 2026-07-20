<?php

namespace Tests\Concerns;

use App\Enums\SystemRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

trait InteractsWithFilament
{
    protected function setUpFilamentPanel(string $panelId = 'admin'): void
    {
        Filament::setCurrentPanel(Filament::getPanel($panelId));
    }

    protected function ensureSystemRoles(): void
    {
        foreach (SystemRole::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }
    }

    protected function createSuperAdmin(array $attributes = []): User
    {
        $this->ensureSystemRoles();
        $this->ensureSuperAdminPermissions();

        $user = User::factory()->create($attributes);
        $user->assignRole(SystemRole::SuperAdmin->value);

        return $user;
    }

    protected function ensureSuperAdminPermissions(): void
    {
        $permissions = array_merge(
            (new RolePermissionSeeder)->superAdminPermissions(),
            [
                'view_client::document::type',
                'view_any_client::document::type',
                'create_client::document::type',
                'update_client::document::type',
                'delete_client::document::type',
                'delete_any_client::document::type',
                'force_delete_client::document::type',
                'force_delete_any_client::document::type',
                'restore_client::document::type',
                'restore_any_client::document::type',
                'replicate_client::document::type',
                'reorder_client::document::type',
                'view_client::activity',
                'view_any_client::activity',
                'create_client::activity',
                'update_client::activity',
                'delete_client::activity',
                'delete_any_client::activity',
                'force_delete_client::activity',
                'force_delete_any_client::activity',
                'restore_client::activity',
                'restore_any_client::activity',
                'replicate_client::activity',
                'reorder_client::activity',
                'view_shield::role',
                'view_any_shield::role',
                'create_shield::role',
                'update_shield::role',
                'delete_shield::role',
                'delete_any_shield::role',
                'force_delete_shield::role',
                'force_delete_any_shield::role',
                'restore_shield::role',
                'restore_any_shield::role',
                'replicate_shield::role',
                'reorder_shield::role',
            ],
        );

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findByName(SystemRole::SuperAdmin->value, 'web')
            ->givePermissionTo($permissions);
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function createUserWithPermissions(array $permissions, SystemRole $role = SystemRole::Admin): User
    {
        $this->ensureSystemRoles();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->create();
        $user->assignRole($role->value);
        $user->givePermissionTo($permissions);

        return $user;
    }

    protected function actingAsFilamentUser(User $user): static
    {
        $this->setUpFilamentPanel();

        return $this->actingAs($user);
    }
}
