<?php

namespace Tests\Concerns;

use App\Enums\SystemRole;
use App\Models\User;
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

        $user = User::factory()->create($attributes);
        $user->assignRole(SystemRole::SuperAdmin->value);

        return $user;
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
