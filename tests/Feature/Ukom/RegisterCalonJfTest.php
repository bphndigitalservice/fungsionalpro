<?php

namespace Tests\Feature\Ukom;

use App\Enums\SystemRole;
use App\Filament\Pages\Authx\Register;
use App\Models\CalonJf;
use App\Models\CRole;
use App\Models\RegDepartment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegisterCalonJfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate(SystemRole::Client->value, 'web');
        Role::findOrCreate(SystemRole::CalonJf->value, 'web');

        CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        CRole::create(['role_name' => 'Penyuluh Hukum', 'active' => true]);
        RegDepartment::create(['name' => 'Kementerian Hukum']);
    }

    public function test_bukan_keduanya_creates_calon_jf_without_client(): void
    {
        Livewire::test(Register::class)
            ->fillForm([
                'nip' => '198001012000011001',
                'name' => 'Calon User',
                'c_role_id' => 'none',
                'email' => 'calon@example.com',
                'password' => 'password',
                'passwordConfirmation' => 'password',
            ])
            ->call('register')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $user = User::where('email', 'calon@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasSystemRole(SystemRole::CalonJf));
        $this->assertFalse($user->hasSystemRole(SystemRole::Client));
        $this->assertNull($user->client);
        $this->assertDatabaseHas('calon_jfs', [
            'user_id' => $user->id,
            'nip' => '198001012000011001',
            'nama' => 'Calon User',
        ]);
    }

    public function test_analis_hukum_still_creates_client(): void
    {
        $department = RegDepartment::query()->first();

        Livewire::test(Register::class)
            ->fillForm([
                'nip' => '198001012000011002',
                'name' => 'AH User',
                'c_role_id' => 1,
                'agency_type' => 'central',
                'agency_id' => $department->id,
                'email' => 'ah@example.com',
                'password' => 'password',
                'passwordConfirmation' => 'password',
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'ah@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasSystemRole(SystemRole::Client));
        $this->assertNotNull($user->client);
        $this->assertSame(1, (int) $user->client->c_role_id);
        $this->assertFalse(CalonJf::where('user_id', $user->id)->exists());
    }

    public function test_bukan_keduanya_creates_missing_calon_jf_role(): void
    {
        Role::query()->where('name', SystemRole::CalonJf->value)->delete();

        Livewire::test(Register::class)
            ->fillForm([
                'nip' => '198001012000011003',
                'name' => 'Calon Baru',
                'c_role_id' => 'none',
                'email' => 'calon-baru@example.com',
                'password' => 'password',
                'passwordConfirmation' => 'password',
            ])
            ->call('register')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $user = User::where('email', 'calon-baru@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasSystemRole(SystemRole::CalonJf));
        $this->assertNotNull($user->calonJf);
        $this->assertNull($user->email_verified_at);
    }

    public function test_nip_cannot_duplicate_calon_jf(): void
    {
        $existing = User::factory()->create();
        $existing->assignRole(SystemRole::CalonJf->value);
        CalonJf::factory()->create([
            'user_id' => $existing->id,
            'nip' => '198001012000011009',
        ]);

        Livewire::test(Register::class)
            ->fillForm([
                'nip' => '198001012000011009',
                'name' => 'Dup',
                'c_role_id' => 'none',
                'email' => 'dup@example.com',
                'password' => 'password',
                'passwordConfirmation' => 'password',
            ])
            ->call('register')
            ->assertHasFormErrors(['nip']);
    }
}
