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

class MasterJfListPaginationTest extends TestCase
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

    public function test_list_pagination_succeeds_with_header_widgets(): void
    {
        $this->actingAsAdmin();

        MasterJf::factory()->count(25)->create();

        Livewire::test(ListMasterJfs::class)
            ->assertSuccessful()
            ->assertSeeLivewire(MasterJfNumbersOverview::class)
            ->call('gotoPage', 2, 'page')
            ->assertSuccessful()
            ->assertSet('paginators.page', 2)
            ->assertSet('widgetsCollapsed', false);

        $this->assertCount(
            4,
            Livewire::test(ListMasterJfs::class)->instance()->getVisibleHeaderWidgets()
        );
    }

    public function test_list_exposes_table_state_required_by_reactive_widgets(): void
    {
        $this->actingAsAdmin();

        MasterJf::factory()->count(25)->create();

        $widgetData = Livewire::test(ListMasterJfs::class)
            ->call('gotoPage', 2, 'page')
            ->instance()
            ->getWidgetData();

        $this->assertArrayHasKey('paginators', $widgetData);
        $this->assertSame(2, data_get($widgetData, 'paginators.page'));
        $this->assertArrayHasKey('tableFilters', $widgetData);
        $this->assertArrayHasKey('tableColumnSearches', $widgetData);
        $this->assertIsArray($widgetData['tableColumnSearches']);
        $this->assertArrayHasKey('tableSearch', $widgetData);
        $this->assertArrayHasKey('tableSortColumn', $widgetData);
    }

    public function test_stats_widget_accepts_exposed_table_state_without_type_error(): void
    {
        $this->actingAsAdmin();

        MasterJf::factory()->count(25)->create();

        $widgetData = Livewire::test(ListMasterJfs::class)
            ->call('gotoPage', 2, 'page')
            ->instance()
            ->getWidgetData();

        Livewire::test(MasterJfNumbersOverview::class, $widgetData)
            ->assertSuccessful()
            ->assertSee('Total Master JF')
            ->assertSee('25');
    }
}
