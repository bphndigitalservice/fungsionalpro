<?php

namespace App\Filament\Resources\ClientResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use App\Filament\Widgets\ClientNumbersOverview;
use App\Filament\Widgets\ClientNumbersByStatusOverview;
use App\Filament\Widgets\ClientNumbersByGradeOverview;
use App\Filament\Widgets\ClientNumbersByRoleLevelOverview;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Filament\Resources\ClientResource;
use App\Models\User;
use App\Services\ClientAccessService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    public bool $widgetsCollapsed = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggle-widgets')
                ->label(fn (): string => $this->widgetsCollapsed ? 'Tampilkan Ringkasan' : 'Sembunyikan Ringkasan')
                ->icon(fn (): string => $this->widgetsCollapsed ? 'heroicon-o-chevron-down' : 'heroicon-o-chevron-up')
                ->color('secondary')
                ->action(fn () => $this->widgetsCollapsed = ! $this->widgetsCollapsed),
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        if ($this->widgetsCollapsed) {
            return [];
        }

        return [
            ClientNumbersOverview::class,
            ClientNumbersByStatusOverview::class,
            ClientNumbersByGradeOverview::class,
            ClientNumbersByRoleLevelOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    protected function getTableQuery(): ?Builder
    {
        return app(ClientAccessService::class)->scopedQuery($this->getPrincipal());
    }

    protected function getPrincipal(): Authenticatable|User
    {
        return auth()->user();
    }
}
