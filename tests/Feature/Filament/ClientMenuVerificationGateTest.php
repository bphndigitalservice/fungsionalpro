<?php

namespace Tests\Feature\Filament;

use App\Enums\ClientCluster;
use App\Enums\SystemRole;
use App\Enums\Verified;
use App\Filament\Resources\ClientActivityResource;
use App\Filament\Resources\ClientCompetenceResource;
use App\Filament\Resources\ClientEducationResource;
use App\Models\Client;
use App\Models\CRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientMenuVerificationGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate(SystemRole::Client->value, 'web');
        Role::findOrCreate(SystemRole::SuperAdmin->value, 'web');
    }

    protected function actingAsClient(Verified $verified, bool $withPhoto = true): User
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::Client->value);

        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);

        $client = Client::create([
            'user_id' => $user->id,
            'c_role_id' => $role->id,
            'nip' => fake()->unique()->numerify('##################'),
            'type' => ClientCluster::Central,
            'agency_type' => 'department',
            'agency_id' => 1,
        ]);

        $client->forceFill([
            'is_verified' => $verified,
            'verified_at' => $verified === Verified::Verified ? now() : null,
        ])->save();

        if ($withPhoto) {
            $client->identity()->update([
                'photo' => 'clients/photo.jpg',
            ]);
        }

        $this->actingAs($user->fresh());

        return $user->fresh();
    }

    public function test_unverified_client_cannot_register_verified_allowlist_nav(): void
    {
        $this->actingAsClient(Verified::Unverified);

        $this->assertFalse(ClientActivityResource::shouldRegisterNavigation());
        $this->assertFalse(ClientCompetenceResource::shouldRegisterNavigation());
        $this->assertFalse(ClientEducationResource::shouldRegisterNavigation());
    }

    public function test_verified_client_with_photo_can_register_allowlist_nav(): void
    {
        $this->actingAsClient(Verified::Verified, withPhoto: true);

        $this->assertTrue(ClientActivityResource::shouldRegisterNavigation());
        $this->assertTrue(ClientCompetenceResource::shouldRegisterNavigation());
        $this->assertTrue(ClientEducationResource::shouldRegisterNavigation());
    }

    public function test_verified_client_without_photo_still_blocked_by_photo_gate(): void
    {
        $this->actingAsClient(Verified::Verified, withPhoto: false);

        $this->assertFalse(ClientActivityResource::shouldRegisterNavigation());
        $this->assertFalse(ClientActivityResource::canAccess());
    }
}
