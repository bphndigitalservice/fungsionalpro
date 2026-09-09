<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\User;
use App\Services\ClientAccessService;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListClients extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = ClientResource::class;

    public bool $widgetsCollapsed = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggle-widgets')
                ->label(fn (): string => $this->widgetsCollapsed ? 'Tampilkan Ringkasan' : 'Sembunyikan Ringkasan')
                ->icon(fn (): string => $this->widgetsCollapsed ? 'heroicon-o-chevron-down' : 'heroicon-o-chevron-up')
                ->color('secondary')
                ->action(fn () => $this->widgetsCollapsed = ! $this->widgetsCollapsed),
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        if ($this->widgetsCollapsed) {
            return [];
        }

        return [
            \App\Filament\Widgets\ClientNumbersOverview::class,
            \App\Filament\Widgets\ClientNumbersByStatusOverview::class,
            \App\Filament\Widgets\ClientNumbersByGradeOverview::class,
            \App\Filament\Widgets\ClientNumbersByRoleLevelOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }
}
