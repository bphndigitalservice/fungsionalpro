<?php

namespace Tests\Feature\Filament;

use App\Enums\ClientStatus;
use App\Enums\SystemRole;
use App\Filament\Resources\MasterJfResource\Pages\ListMasterJfs;
use App\Models\CRole;
use App\Models\MasterJf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MasterJfListFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsAdmin(): User
    {
        Role::findOrCreate(SystemRole::Admin->value, 'web');

        $user = User::factory()->create();
        $user->assignRole(SystemRole::Admin->value);

        $this->actingAs($user);

        return $user;
    }

    public function test_status_filter_limits_visible_table_records(): void
    {
        $this->actingAsAdmin();

        $aktif = MasterJf::factory()->create([
            'status' => ClientStatus::Active,
            'nama' => 'Aktif Person',
        ]);
        $ctln = MasterJf::factory()->create([
            'status' => ClientStatus::NonActive_CTLN,
            'nama' => 'CTLN Person',
        ]);

        Livewire::test(ListMasterJfs::class)
            ->assertCanSeeTableRecords([$aktif, $ctln])
            ->filterTable('status', ClientStatus::Active->value)
            ->assertCanSeeTableRecords([$aktif])
            ->assertCanNotSeeTableRecords([$ctln]);
    }

    public function test_instansi_multi_filter_limits_records(): void
    {
        $this->actingAsAdmin();

        $a = MasterJf::factory()->create(['instansi' => 'BPHN', 'nama' => 'BPHN Person']);
        $b = MasterJf::factory()->create(['instansi' => 'Kemenkumham', 'nama' => 'Kemenkumham Person']);

        Livewire::test(ListMasterJfs::class)
            ->filterTable('instansi', ['BPHN'])
            ->assertCanSeeTableRecords([$a])
            ->assertCanNotSeeTableRecords([$b]);
    }

    public function test_c_role_filter_limits_visible_table_records(): void
    {
        $this->actingAsAdmin();

        $analis = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $penyuluh = CRole::create(['role_name' => 'Penyuluh Hukum', 'active' => true]);

        $a = MasterJf::factory()->create([
            'nama' => 'Analis Person',
            'c_role_id' => $analis->id,
        ]);
        $b = MasterJf::factory()->create([
            'nama' => 'Penyuluh Person',
            'c_role_id' => $penyuluh->id,
        ]);

        Livewire::test(ListMasterJfs::class)
            ->assertCanSeeTableRecords([$a, $b])
            ->filterTable('c_role_id', $analis->id)
            ->assertCanSeeTableRecords([$a])
            ->assertCanNotSeeTableRecords([$b]);
    }
}
