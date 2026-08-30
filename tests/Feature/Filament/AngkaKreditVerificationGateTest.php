<?php

namespace Tests\Feature\Filament;

use App\Enums\ClientCluster;
use App\Enums\SystemRole;
use App\Enums\Verified;
use App\Filament\Pages\Client\Point\ClientPointCreate;
use App\Filament\Pages\Client\Point\ClientPointList;
use App\Filament\Pages\Dashboard;
use App\Models\Client;
use App\Models\CRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AngkaKreditVerificationGateTest extends TestCase
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

    public function test_verify_identity_lang_key_matches_required_copy(): void
    {
        $this->assertSame(
            'Lengkapi Identitas dan Tunggu Identitas anda di verifikasi',
            __('labels.page.dashboard.verify_identity_required')
        );
    }

    public function test_unverified_client_gets_persistent_verify_notice_on_dashboard(): void
    {
        $this->actingAsClient(Verified::Unverified);

        Livewire::test(Dashboard::class)->assertSuccessful();

        $notifications = session('filament.notifications') ?? [];
        $titles = collect($notifications)->pluck('title')->all();

        $this->assertContains(__('labels.page.dashboard.verify_identity_required'), $titles);
        $this->assertNotContains('Lengkapi Pengisian Identitas Terlebih Dahulu', $titles);
    }

    public function test_verified_client_does_not_get_verify_notice_on_dashboard(): void
    {
        $this->actingAsClient(Verified::Verified, withPhoto: true);

        Livewire::test(Dashboard::class)->assertSuccessful();

        $notifications = session('filament.notifications') ?? [];
        $titles = collect($notifications)->pluck('title')->all();

        $this->assertNotContains(__('labels.page.dashboard.verify_identity_required'), $titles);
    }

    public function test_unverified_client_cannot_register_angka_kredit_nav(): void
    {
        $this->actingAsClient(Verified::Unverified, withPhoto: true);
        $this->grantPointPagePermissions();

        $this->assertFalse(ClientPointList::shouldRegisterNavigation());
        $this->assertFalse(ClientPointCreate::shouldRegisterNavigation());
    }

    public function test_verified_client_with_photo_can_register_angka_kredit_nav(): void
    {
        $this->actingAsClient(Verified::Verified, withPhoto: true);
        $this->grantPointPagePermissions();

        $this->assertTrue(ClientPointList::shouldRegisterNavigation());
        $this->assertTrue(ClientPointCreate::shouldRegisterNavigation());
    }

    public function test_verified_client_without_photo_cannot_register_angka_kredit_nav(): void
    {
        $this->actingAsClient(Verified::Verified, withPhoto: false);
        $this->grantPointPagePermissions();

        $this->assertFalse(ClientPointList::shouldRegisterNavigation());
    }

    public function test_unverified_client_is_redirected_from_point_list_to_dashboard(): void
    {
        $this->actingAsClient(Verified::Unverified, withPhoto: true);
        $this->grantPointPagePermissions();

        Livewire::test(ClientPointList::class)
            ->assertRedirect(Dashboard::getUrl());
    }

    protected function grantPointPagePermissions(): void
    {
        $user = auth()->user();
        $role = Role::findByName(SystemRole::Client->value, 'web');

        foreach (['page_ClientPointList', 'page_ClientPointCreate', 'page_ClientPointEdit'] as $permission) {
            Permission::findOrCreate($permission, 'web');
            $role->givePermissionTo($permission);
        }

        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
    }
}
