<?php

namespace Tests\Feature\Filament\Pages;

use App\Enums\SystemRole;
use App\Filament\Pages\Verification\ClientIdentityVerificationWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithFilament;
use Tests\TestCase;

class ClientIdentityVerificationWorkspaceTest extends TestCase
{
    use InteractsWithFilament;
    use RefreshDatabase;

    public function test_verifier_can_render_identity_workspace(): void
    {
        $user = $this->createUserWithPermissions(
            ['page_ClientIdentityVerificationWorkspace'],
            SystemRole::Verifier,
        );

        $this->actingAsFilamentUser($user);

        Livewire::test(ClientIdentityVerificationWorkspace::class)
            ->assertSuccessful();
    }
}
