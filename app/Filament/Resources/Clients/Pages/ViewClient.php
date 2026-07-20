<?php

namespace App\Filament\Resources\Clients\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\CRoles\Concern\CanAccessClientData;
use App\Models\Client;
use App\Models\User;
use App\Models\VerifierAccess;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Query\JoinClause;

class ViewClient extends ViewRecord
{
    use CanAccessClientData;

    protected static string $resource = ClientResource::class;

    protected function authorizeAccess(): void
    {
        $this->canAccessClientData();
    }


    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }






}
