<?php

namespace Tests\Feature\Ukom;

use App\Enums\ClientCluster;
use App\Enums\SystemRole;
use App\Enums\UkomApplicationStatus;
use App\Models\CalonJf;
use App\Models\Client;
use App\Models\CRole;
use App\Models\UkomApplication;
use App\Models\User;
use App\Rules\UniqueNip;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CalonJfAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate(SystemRole::Client->value, 'web');
        Role::findOrCreate(SystemRole::CalonJf->value, 'web');
    }

    public function test_calon_jf_can_access_panel_without_client(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::CalonJf->value);
        CalonJf::factory()->create([
            'user_id' => $user->id,
            'nip' => '198001012000011001',
            'nama' => 'Calon Nama',
        ]);

        $user = $user->fresh();

        $this->assertTrue($user->isActiveCalonJf());
        $this->assertFalse($user->isActiveClient());
        $this->assertNull($user->client);
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_unique_nip_rejects_existing_client_and_calon(): void
    {
        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $clientUser = User::factory()->create();
        $clientUser->assignRole(SystemRole::Client->value);
        Client::create([
            'user_id' => $clientUser->id,
            'c_role_id' => $role->id,
            'nip' => '111111111111111111',
            'type' => ClientCluster::Central,
            'agency_type' => 'department',
            'agency_id' => 1,
        ]);

        $calonUser = User::factory()->create();
        $calonUser->assignRole(SystemRole::CalonJf->value);
        CalonJf::factory()->create([
            'user_id' => $calonUser->id,
            'nip' => '222222222222222222',
        ]);

        $this->assertTrue(Validator::make(['nip' => '111111111111111111'], ['nip' => [new UniqueNip]])->fails());
        $this->assertTrue(Validator::make(['nip' => '222222222222222222'], ['nip' => [new UniqueNip]])->fails());
        $this->assertFalse(Validator::make(['nip' => '333333333333333333'], ['nip' => [new UniqueNip]])->fails());
    }

    public function test_user_has_open_ukom_application(): void
    {
        $user = User::factory()->create();
        $jabatan = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);

        $this->assertFalse(UkomApplication::userHasOpen($user->id));

        UkomApplication::factory()->create([
            'user_id' => $user->id,
            'target_c_role_id' => $jabatan->id,
            'status' => UkomApplicationStatus::PendingInstansi,
        ]);

        $this->assertTrue(UkomApplication::userHasOpen($user->id));
    }

    public function test_rejected_application_is_not_open(): void
    {
        $user = User::factory()->create();
        $jabatan = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);

        UkomApplication::factory()->rejected()->create([
            'user_id' => $user->id,
            'target_c_role_id' => $jabatan->id,
        ]);

        $this->assertFalse(UkomApplication::userHasOpen($user->id));
    }
}
