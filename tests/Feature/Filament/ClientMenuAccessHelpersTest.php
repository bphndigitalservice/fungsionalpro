<?php

namespace Tests\Feature\Filament;

use App\Concerns\Filament\ClientMenuAccess;
use App\Enums\ClientCluster;
use App\Enums\SystemRole;
use App\Enums\Verified;
use App\Models\Client;
use App\Models\CRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientMenuAccessHelpersTest extends TestCase
{
    use RefreshDatabase;

    private static int $nipSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate(SystemRole::Client->value, 'web');
        Role::findOrCreate(SystemRole::SuperAdmin->value, 'web');
        Role::findOrCreate(SystemRole::Admin->value, 'web');
    }

    protected function makeClientUser(Verified $verified = Verified::Unverified): array
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::Client->value);

        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);

        $client = Client::create([
            'user_id' => $user->id,
            'c_role_id' => $role->id,
            'nip' => '19900101202012'.str_pad((string) ++self::$nipSequence, 4, '0', STR_PAD_LEFT),
            'type' => ClientCluster::Central,
            'agency_type' => 'department',
            'agency_id' => 1,
        ]);

        $client->forceFill([
            'is_verified' => $verified,
            'verified_at' => $verified === Verified::Verified ? now() : null,
        ])->save();

        return [$user->fresh(), $client->fresh()];
    }

    public function test_client_without_superadmin_is_detected(): void
    {
        [$user] = $this->makeClientUser();

        $this->assertTrue(ClientMenuAccess::isClientWithoutSuperAdmin($user));
    }

    public function test_client_who_is_also_superadmin_is_not_client_without_superadmin(): void
    {
        [$user] = $this->makeClientUser();
        $user->assignRole(SystemRole::SuperAdmin->value);

        $this->assertFalse(ClientMenuAccess::isClientWithoutSuperAdmin($user->fresh()));
    }

    public function test_unverified_and_rejected_are_not_verified(): void
    {
        [, $unverified] = $this->makeClientUser(Verified::Unverified);
        [, $rejected] = $this->makeClientUser(Verified::Rejected);

        $this->assertFalse(ClientMenuAccess::clientProfileIsVerified($unverified));
        $this->assertFalse(ClientMenuAccess::clientProfileIsVerified($rejected));
    }

    public function test_verified_client_may_access_verified_menu_items(): void
    {
        [$user, $client] = $this->makeClientUser(Verified::Verified);

        $this->assertTrue(ClientMenuAccess::clientMayAccessVerifiedClientMenuItem($user, $client));
    }

    public function test_unverified_client_may_not_access_verified_menu_items(): void
    {
        [$user, $client] = $this->makeClientUser(Verified::Unverified);

        $this->assertFalse(ClientMenuAccess::clientMayAccessVerifiedClientMenuItem($user, $client));
    }

    public function test_non_client_is_not_restricted_by_verified_menu_helper(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(SystemRole::Admin->value);

        $this->assertTrue(ClientMenuAccess::clientMayAccessVerifiedClientMenuItem($admin, null));
    }

    public function test_only_superadmin_may_access_superadmin_client_menu_items(): void
    {
        [$clientUser] = $this->makeClientUser();
        $super = User::factory()->create();
        $super->assignRole(SystemRole::SuperAdmin->value);

        $this->assertFalse(ClientMenuAccess::userMayAccessSuperAdminClientMenuItem($clientUser));
        $this->assertTrue(ClientMenuAccess::userMayAccessSuperAdminClientMenuItem($super));
    }
}
