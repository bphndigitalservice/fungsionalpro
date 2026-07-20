<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithFilament;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use InteractsWithFilament;
    use RefreshDatabase;

    public function test_super_admin_can_render_dashboard(): void
    {
        $user = $this->createSuperAdmin();
        $this->actingAsFilamentUser($user);

        Livewire::test(Dashboard::class)
            ->assertSuccessful();
    }
}
