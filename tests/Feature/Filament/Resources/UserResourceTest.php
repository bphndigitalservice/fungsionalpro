<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithFilament;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use InteractsWithFilament;
    use RefreshDatabase;

    public function test_super_admin_can_list_users(): void
    {
        $actor = $this->createSuperAdmin(['email' => 'actor@example.com']);
        $other = User::factory()->create(['name' => 'Other User']);

        $this->actingAsFilamentUser($actor);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$other])
            ->searchTable('Other User')
            ->assertCanSeeTableRecords([$other]);
    }

    public function test_user_table_includes_invite_action(): void
    {
        $actor = $this->createSuperAdmin();
        $this->actingAsFilamentUser($actor);

        Livewire::test(ListUsers::class)
            ->assertTableActionExists('invite');
    }
}
