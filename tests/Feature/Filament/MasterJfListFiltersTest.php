<?php

namespace Tests\Feature\Filament;

use App\Enums\SystemRole;
use App\Filament\Resources\MasterJfResource\Pages\ListMasterJfs;
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

        $aktif = MasterJf::factory()->create(['status' => 'Aktif', 'nama' => 'Aktif Person']);
        $ctln = MasterJf::factory()->create(['status' => 'CTLN', 'nama' => 'CTLN Person']);

        Livewire::test(ListMasterJfs::class)
            ->assertCanSeeTableRecords([$aktif, $ctln])
            ->filterTable('status', 'Aktif')
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
}
