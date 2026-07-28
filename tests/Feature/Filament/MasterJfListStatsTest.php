<?php

namespace Tests\Feature\Filament;

use App\Enums\SystemRole;
use App\Filament\Resources\MasterJfResource\Pages\ListMasterJfs;
use App\Filament\Widgets\MasterJfNumbersByGolRuangOverview;
use App\Filament\Widgets\MasterJfNumbersByStatusKepegawaianOverview;
use App\Filament\Widgets\MasterJfNumbersByStatusOverview;
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

    public function test_status_breakdown_reflects_fixture_counts(): void
    {
        $this->actingAsAdmin();

        MasterJf::factory()->count(2)->create(['status' => 'Aktif']);
        MasterJf::factory()->count(1)->create(['status' => 'CTLN']);

        Livewire::test(MasterJfNumbersByStatusOverview::class)
            ->assertSee('Aktif')
            ->assertSee('2')
            ->assertSee('CTLN')
            ->assertSee('1');
    }

    public function test_status_kepegawaian_breakdown_reflects_fixture_counts(): void
    {
        $this->actingAsAdmin();

        MasterJf::factory()->count(2)->create(['status_kepegawaian' => 'PNS']);
        MasterJf::factory()->count(4)->create(['status_kepegawaian' => 'PPPK']);

        Livewire::test(MasterJfNumbersByStatusKepegawaianOverview::class)
            ->assertSee('PNS')
            ->assertSee('2')
            ->assertSee('PPPK')
            ->assertSee('4');
    }

    public function test_gol_ruang_widget_shows_top_counts(): void
    {
        $this->actingAsAdmin();

        MasterJf::factory()->count(3)->create(['gol_ruang' => 'III/a']);
        MasterJf::factory()->count(1)->create(['gol_ruang' => 'IV/a']);

        Livewire::test(MasterJfNumbersByGolRuangOverview::class)
            ->assertSee('III/a')
            ->assertSee('3')
            ->assertSee('IV/a')
            ->assertSee('1');
    }

    public function test_total_widget_follows_gol_ruang_filter(): void
    {
        $this->actingAsAdmin();

        MasterJf::factory()->count(2)->create(['gol_ruang' => 'III/a']);
        MasterJf::factory()->count(5)->create(['gol_ruang' => 'IV/a']);

        Livewire::test(MasterJfNumbersOverview::class, [
            'tableFilters' => [
                'gol_ruang' => ['value' => 'III/a'],
            ],
        ])
            ->assertSee('2');
    }

    public function test_list_page_exposes_table_filters_in_widget_data(): void
    {
        $this->actingAsAdmin();

        MasterJf::factory()->create(['status' => 'Aktif']);
        MasterJf::factory()->create(['status' => 'CTLN']);

        $component = Livewire::test(ListMasterJfs::class)
            ->filterTable('status', 'Aktif');

        $widgetData = $component->instance()->getWidgetData();

        $this->assertArrayHasKey('tableFilters', $widgetData);
        $this->assertSame('Aktif', data_get($widgetData, 'tableFilters.status.value'));
    }

    public function test_list_total_widget_follows_status_filter_via_page(): void
    {
        $this->actingAsAdmin();

        MasterJf::factory()->count(2)->create(['status' => 'Aktif']);
        MasterJf::factory()->count(3)->create(['status' => 'CTLN']);

        Livewire::test(ListMasterJfs::class)
            ->assertSeeLivewire(MasterJfNumbersOverview::class)
            ->assertSee('Total Master JF')
            ->assertSee('5')
            ->filterTable('status', 'Aktif')
            ->assertSee('Total Master JF')
            ->assertSee('2');
    }
}
