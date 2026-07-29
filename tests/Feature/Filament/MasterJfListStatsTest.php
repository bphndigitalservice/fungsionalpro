<?php

namespace Tests\Feature\Filament;

use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;
use App\Enums\SystemRole;
use App\Filament\Resources\MasterJfResource\Pages\ListMasterJfs;
use App\Filament\Widgets\MasterJfNumbersByGolRuangOverview;
use App\Filament\Widgets\MasterJfNumbersByStatusKepegawaianOverview;
use App\Filament\Widgets\MasterJfNumbersByStatusOverview;
use App\Filament\Widgets\MasterJfNumbersOverview;
use App\Models\MasterJf;
use App\Models\RegGrade;
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

        MasterJf::factory()->count(2)->create(['status' => ClientStatus::Active]);
        MasterJf::factory()->count(3)->create(['status' => ClientStatus::NonActive_CTLN]);

        Livewire::test(MasterJfNumbersOverview::class, [
            'tableFilters' => [
                'status' => ['value' => ClientStatus::Active->value],
            ],
        ])
            ->assertSee('Total Master JF')
            ->assertSee('2');
    }

    public function test_status_breakdown_reflects_fixture_counts(): void
    {
        $this->actingAsAdmin();

        MasterJf::factory()->count(2)->create(['status' => ClientStatus::Active]);
        MasterJf::factory()->count(1)->create(['status' => ClientStatus::NonActive_CTLN]);

        Livewire::test(MasterJfNumbersByStatusOverview::class)
            ->assertSee('Aktif')
            ->assertSee('2')
            ->assertSee('CTLN')
            ->assertSee('1');
    }

    public function test_status_kepegawaian_breakdown_reflects_fixture_counts(): void
    {
        $this->actingAsAdmin();

        MasterJf::factory()->count(2)->create(['status_kepegawaian' => JenisKepegawaian::PNS]);
        MasterJf::factory()->count(4)->create(['status_kepegawaian' => JenisKepegawaian::PPPK]);

        Livewire::test(MasterJfNumbersByStatusKepegawaianOverview::class)
            ->assertSee('PNS')
            ->assertSee('2')
            ->assertSee('PPPK')
            ->assertSee('4');
    }

    public function test_gol_ruang_widget_shows_top_counts(): void
    {
        $this->actingAsAdmin();

        $iiia = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);
        $iva = RegGrade::create(['grade_name' => 'Pembina', 'grade_code' => 'IV/a']);

        MasterJf::factory()->count(3)->create(['reg_grade_id' => $iiia->id]);
        MasterJf::factory()->count(1)->create(['reg_grade_id' => $iva->id]);

        Livewire::test(MasterJfNumbersByGolRuangOverview::class)
            ->assertSee('III/a')
            ->assertSee('3')
            ->assertSee('IV/a')
            ->assertSee('1');
    }

    public function test_total_widget_follows_gol_ruang_filter(): void
    {
        $this->actingAsAdmin();

        $iiia = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);
        $iva = RegGrade::create(['grade_name' => 'Pembina', 'grade_code' => 'IV/a']);

        MasterJf::factory()->count(2)->create(['reg_grade_id' => $iiia->id]);
        MasterJf::factory()->count(5)->create(['reg_grade_id' => $iva->id]);

        Livewire::test(MasterJfNumbersOverview::class, [
            'tableFilters' => [
                'reg_grade_id' => ['value' => $iiia->id],
            ],
        ])
            ->assertSee('2');
    }

    public function test_list_page_exposes_table_filters_in_widget_data(): void
    {
        $this->actingAsAdmin();

        MasterJf::factory()->create(['status' => ClientStatus::Active]);
        MasterJf::factory()->create(['status' => ClientStatus::NonActive_CTLN]);

        $component = Livewire::test(ListMasterJfs::class)
            ->filterTable('status', ClientStatus::Active->value);

        $widgetData = $component->instance()->getWidgetData();

        $this->assertArrayHasKey('tableFilters', $widgetData);
        $this->assertSame(ClientStatus::Active->value, data_get($widgetData, 'tableFilters.status.value'));
    }

    public function test_list_total_widget_follows_status_filter_via_page(): void
    {
        $this->actingAsAdmin();

        MasterJf::factory()->count(2)->create(['status' => ClientStatus::Active]);
        MasterJf::factory()->count(3)->create(['status' => ClientStatus::NonActive_CTLN]);

        $component = Livewire::test(ListMasterJfs::class)
            ->assertSeeLivewire(MasterJfNumbersOverview::class)
            ->assertSee('Total Master JF')
            ->assertSee('5')
            ->filterTable('status', ClientStatus::Active->value);

        Livewire::test(MasterJfNumbersOverview::class, $component->instance()->getWidgetData())
            ->assertSee('Total Master JF')
            ->assertSee('2');
    }
}
