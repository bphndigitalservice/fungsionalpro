<?php

namespace Tests\Feature\Filament\Auth;

use App\Filament\Pages\Authx\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithFilament;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use InteractsWithFilament;
    use RefreshDatabase;

    public function test_login_page_renders_for_guests(): void
    {
        $this->setUpFilamentPanel();

        Livewire::test(Login::class)
            ->assertSuccessful();
    }

    public function test_authenticated_super_admin_can_reach_dashboard_route(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAsFilamentUser($user)
            ->get('/')
            ->assertSuccessful();
    }
}
