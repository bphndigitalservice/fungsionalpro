<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Enums\SystemRole;
use App\Filament\Resources\ClientResource;
use App\Filament\Resources\CRoleResource\Concern\CanAccessClientData;
use App\Models\Client;
use App\Models\User;
use App\Models\VerifierAccess;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Log;

class ListClients extends ListRecords
{

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

    protected function getTableQuery(): ?Builder
    {

        $principal = $this->getPrincipal();

        Log::info("ListClients getTableQuery for user id: ".$principal->id);

        if ($principal->isSuperAdmin()) {
            return parent::getTableQuery();
        }

        if($principal->hasSystemRole(SystemRole::Admin)) {
            $adminAccess = \App\Models\AdminAccess::query()->where('user_id', $principal->id)->limit(1);

            return Client::joinSub($adminAccess, 'aa', function (JoinClause $join) {
                $join->on('clients.c_role_id', '=', 'aa.c_role_id');
            })->select('clients.*');
        }

        if ($principal->hasAnySystemRole(SystemRole::AdminRegional, SystemRole::Verifier, SystemRole::AdminPusat)) {
            $verifierAccess = VerifierAccess::query()->where('user_id', $principal->id)->limit(1);

            return Client::joinSub($verifierAccess, 'va', function (JoinClause $join) {
                $join->on('clients.c_role_id', '=', 'va.c_role_id');
                $join->on('va.entity_type', '=', 'clients.agency_type');
                $join->on('va.entity_id', '=', 'clients.agency_id');
            })->select('clients.*');
        }

        return null;

    }

    protected function getPrincipal(): \Illuminate\Contracts\Auth\Authenticatable|User
    {
        return auth()->user();
    }


}
