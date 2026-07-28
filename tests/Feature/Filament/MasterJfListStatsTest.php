<?php

namespace Tests\Feature\Filament;

use App\Enums\SystemRole;
use App\Filament\Resources\MasterJfResource\Pages\ListMasterJfs;
use App\Filament\Widgets\MasterJfNumbersOverview;
use App\Models\MasterJf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MasterJfListStatsTest extends TestCase
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

    public function test_list_shows_total_widget_and_collapse_toggle(): void
    {
        $this->actingAsAdmin();

        MasterJf::factory()->count(3)->create();

        Livewire::test(ListMasterJfs::class)
            ->assertSee('Sembunyikan Ringkasan')
            ->assertSeeLivewire(MasterJfNumbersOverview::class)
            ->assertSee('Total Master JF')
            ->assertSee('3');
    }

    public function test_collapse_toggle_hides_and_shows_widgets(): void
    {
        $this->actingAsAdmin();

        Livewire::test(ListMasterJfs::class)
            ->assertSet('widgetsCollapsed', false)
            ->callAction('toggle-widgets')
            ->assertSet('widgetsCollapsed', true)
            ->assertDontSeeLivewire(MasterJfNumbersOverview::class)
            ->callAction('toggle-widgets')
            ->assertSet('widgetsCollapsed', false)
            ->assertSeeLivewire(MasterJfNumbersOverview::class);
    }

    public function test_total_widget_follows_status_filter(): void
    {
        $this->actingAsAdmin();

        MasterJf::factory()->count(2)->create(['status' => 'Aktif']);
        MasterJf::factory()->count(3)->create(['status' => 'CTLN']);

        Livewire::test(MasterJfNumbersOverview::class, [
            'tableFilters' => [
                'status' => ['value' => 'Aktif'],
            ],
        ])
            ->assertSee('Total Master JF')
            ->assertSee('2');
    }
}
