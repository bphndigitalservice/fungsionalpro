<?php

namespace App\Filament\Resources\MasterJfResource\Pages;

use App\Filament\Resources\MasterJfResource;
use App\Filament\Widgets\MasterJfNumbersByGolRuangOverview;
use App\Filament\Widgets\MasterJfNumbersByJenjangOverview;
use App\Filament\Widgets\MasterJfNumbersByStatusKepegawaianOverview;
use App\Filament\Widgets\MasterJfNumbersByStatusOverview;
use App\Filament\Widgets\MasterJfNumbersOverview;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListMasterJfs extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = MasterJfResource::class;

    public bool $widgetsCollapsed = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggle-widgets')
                ->label(fn (): string => $this->widgetsCollapsed ? 'Tampilkan Ringkasan' : 'Sembunyikan Ringkasan')
                ->icon(fn (): string => $this->widgetsCollapsed ? 'heroicon-o-chevron-down' : 'heroicon-o-chevron-up')
                ->color('secondary')
                ->action(fn () => $this->widgetsCollapsed = ! $this->widgetsCollapsed),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        if ($this->widgetsCollapsed) {
            return [];
        }

        return [
            MasterJfNumbersOverview::class,
            MasterJfNumbersByStatusOverview::class,
            MasterJfNumbersByStatusKepegawaianOverview::class,
            MasterJfNumbersByGolRuangOverview::class,
            MasterJfNumbersByJenjangOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }
}
