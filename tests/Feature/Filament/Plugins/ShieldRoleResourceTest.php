<?php

namespace Tests\Feature\Filament\Plugins;

use App\Filament\Resources\Shield\RoleResource\Pages\ListRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithFilament;
use Tests\TestCase;

class ShieldRoleResourceTest extends TestCase
{
    use InteractsWithFilament;
    use RefreshDatabase;

    public function test_super_admin_can_list_shield_roles(): void
    {
        $user = $this->createSuperAdmin();
        $this->actingAsFilamentUser($user);

        Livewire::test(ListRoles::class)
            ->assertSuccessful();
    }
}
