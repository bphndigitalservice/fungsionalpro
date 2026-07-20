<?php

namespace Tests\Feature\Filament\Pages;

use App\Enums\SystemRole;
use App\Filament\Pages\Client\ClientProfilePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithFilament;
use Tests\TestCase;

class ClientProfilePageTest extends TestCase
{
    use InteractsWithFilament;
    use RefreshDatabase;

    public function test_client_with_permission_can_render_profile_page(): void
    {
        $user = $this->createUserWithPermissions(
            ['page_ClientProfilePage'],
            SystemRole::Client,
        );

        $this->actingAsFilamentUser($user);

        Livewire::test(ClientProfilePage::class)
            ->assertSuccessful();
    }
}
