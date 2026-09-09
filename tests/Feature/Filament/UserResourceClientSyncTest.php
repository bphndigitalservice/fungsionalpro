<?php

namespace Tests\Feature\Filament;

use App\Enums\SystemRole;
use App\Enums\Verified;
use App\Filament\Pages\Client\ClientProfilePage;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\Client;
use App\Models\CRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserResourceClientSyncTest extends TestCase
{
    use RefreshDatabase;

    private Role $clientRole;

    private Role $adminRole;

    private CRole $jabatan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientRole = Role::findOrCreate(SystemRole::Client->value, 'web');
        $this->adminRole = Role::findOrCreate(SystemRole::Admin->value, 'web');
        Role::findOrCreate(SystemRole::SuperAdmin->value, 'web');

        foreach (['view_any_user', 'create_user', 'view_user', 'update_user'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->jabatan = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);

        $actor = User::factory()->create();
        $actor->assignRole(SystemRole::SuperAdmin->value);
        $actor->givePermissionTo(['view_any_user', 'create_user', 'view_user', 'update_user']);
        $this->actingAs($actor);
    }

    public function test_create_user_with_client_role_creates_client(): void
    {
        $email = 'asn-'.uniqid().'@example.com';

        Livewire::test(CreateUser::class)
            ->fillForm([
                'roles' => [$this->clientRole->id],
                'nip' => '199001012020121001',
                'c_role_id' => $this->jabatan->id,
                'name' => 'ASN User',
                'email' => $email,
                'password' => 'password123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasSystemRole(SystemRole::Client));
        $this->assertDatabaseHas('clients', [
            'user_id' => $user->id,
            'nip' => '199001012020121001',
            'c_role_id' => $this->jabatan->id,
        ]);
    }

    public function test_create_user_with_admin_only_does_not_create_client(): void
    {
        $email = 'admin-'.uniqid().'@example.com';

        Livewire::test(CreateUser::class)
            ->fillForm([
                'roles' => [$this->adminRole->id],
                'name' => 'Admin User',
                'email' => $email,
                'password' => 'password123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertNull($user->client);
    }

    public function test_edit_adding_client_role_creates_client(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::Admin->value);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'roles' => [$this->adminRole->id, $this->clientRole->id],
                'nip' => '199001012020121002',
                'c_role_id' => $this->jabatan->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('clients', [
            'user_id' => $user->id,
            'nip' => '199001012020121002',
            'c_role_id' => $this->jabatan->id,
        ]);
    }

    public function test_edit_removing_client_role_keeps_client_row(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::Client->value);

        $client = Client::create([
            'user_id' => $user->id,
            'nip' => '199001012020121003',
            'c_role_id' => $this->jabatan->id,
        ]);
        $client->forceFill(['is_verified' => Verified::Unverified])->save();

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'roles' => [$this->adminRole->id],
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('clients', ['id' => $client->id, 'user_id' => $user->id]);
        $this->assertFalse($user->fresh()->isActiveClient());
    }

    public function test_identitas_nav_only_for_active_client(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(SystemRole::Admin->value);
        $this->actingAs($admin);
        $this->assertFalse(ClientProfilePage::shouldRegisterNavigation());

        $asn = User::factory()->create();
        $asn->assignRole(SystemRole::Client->value);
        Client::create([
            'user_id' => $asn->id,
            'nip' => '199001012020121004',
            'c_role_id' => $this->jabatan->id,
        ]);
        $this->actingAs($asn->fresh());
        $this->assertTrue(ClientProfilePage::shouldRegisterNavigation());
    }
}
