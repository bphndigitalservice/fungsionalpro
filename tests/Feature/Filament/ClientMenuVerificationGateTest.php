<?php

namespace Tests\Feature\Filament;

use App\Enums\ClientCluster;
use App\Enums\SystemRole;
use App\Enums\Verified;
use App\Filament\Pages\Client\ClientBasicIdentityPage;
use App\Filament\Pages\Client\ClientProfilePage;
use App\Filament\Resources\ClientActivityResource;
use App\Filament\Resources\ClientActivityResource\Pages\ListClientActivities;
use App\Filament\Resources\ClientCompetenceResource;
use App\Filament\Resources\ClientDossierResource;
use App\Filament\Resources\ClientEducationResource;
use App\Filament\Resources\ClientGradeResource;
use App\Filament\Resources\ClientPositionResource;
use App\Filament\Resources\ClientPositionResource\Pages\ListClientPositions;
use App\Models\Client;
use App\Models\CRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
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

    public function test_unverified_client_can_still_access_identitas_navigation(): void
    {
        $this->actingAsClient(Verified::Unverified);

        $this->assertTrue(ClientProfilePage::shouldRegisterNavigation());
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

    public function test_client_only_cannot_see_superadmin_client_menu_items(): void
    {
        $this->actingAsClient(Verified::Verified);

        $this->assertFalse(ClientPositionResource::shouldRegisterNavigation());
        $this->assertFalse(ClientGradeResource::shouldRegisterNavigation());
        $this->assertFalse(ClientDossierResource::shouldRegisterNavigation());
        $this->assertFalse(ClientBasicIdentityPage::shouldRegisterNavigation());
        $this->assertFalse(ClientPositionResource::canAccess());
    }

    public function test_superadmin_can_see_superadmin_client_menu_items(): void
    {
        $super = User::factory()->create();
        $super->assignRole(SystemRole::SuperAdmin->value);
        $this->actingAs($super);

        $this->assertTrue(ClientPositionResource::shouldRegisterNavigation());
        $this->assertTrue(ClientGradeResource::shouldRegisterNavigation());
        $this->assertTrue(ClientDossierResource::shouldRegisterNavigation());
        $this->assertTrue(ClientBasicIdentityPage::shouldRegisterNavigation());
    }

    public function test_unverified_client_is_redirected_from_activity_list_to_identitas(): void
    {
        $this->actingAsClient(Verified::Unverified);

        Livewire::test(ListClientActivities::class)
            ->assertRedirect(ClientProfilePage::getUrl());
    }

    public function test_client_is_redirected_from_position_list_to_identitas(): void
    {
        $this->actingAsClient(Verified::Verified);

        Livewire::test(ListClientPositions::class)
            ->assertRedirect(ClientProfilePage::getUrl());
    }

    public function test_subsequent_livewire_request_redirects_locked_client_instead_of_403(): void
    {
        $this->actingAsClient(Verified::Verified, withPhoto: true);

        $component = Livewire::test(ListClientActivities::class);

        Client::current()->forceFill([
            'is_verified' => Verified::Unverified,
            'verified_at' => null,
        ])->save();

        auth()->user()->unsetRelation('client');

        $this->assertFalse(ClientActivityResource::canAccess());

        try {
            $component->instance()->hydrateCanAuthorizeResourceAccess();

            $this->fail('Expected hydrate lock to redirect instead of continuing');
        } catch (HttpResponseException $exception) {
            $this->assertTrue($exception->getResponse()->isRedirect(ClientProfilePage::getUrl()));
        } catch (HttpException $exception) {
            $this->fail('Hydrate still aborted with HTTP '.$exception->getStatusCode().' instead of redirecting');
        }
    }

    public function test_basic_identity_save_authorization_still_requires_client_permissions(): void
    {
        $super = User::factory()->create();
        $super->assignRole(SystemRole::SuperAdmin->value);
        $this->actingAs($super);

        $this->assertFalse($super->can('create_client'));
        $this->assertFalse($super->can('update_client'));

        try {
            Livewire::test(ClientBasicIdentityPage::class)
                ->instance()
                ->authorizeAccess();

            $this->fail('Expected 403 when SuperAdmin lacks create_client/update_client');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }
}
