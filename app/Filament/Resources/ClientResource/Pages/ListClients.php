<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\Client;
use App\Models\User;
use App\Models\VerifierAccess;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): ?Builder
    {

        $principal = $this->getPrincipal();

        if ($principal->isSuperAdmin()) {
            return parent::getTableQuery();
        }

        if ($principal->hasRole(['admin-regional', 'verifier'])) {
            $verifierAccess = VerifierAccess::query()->where('user_id', $principal->id);

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
