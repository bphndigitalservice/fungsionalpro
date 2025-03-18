<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Filament\Resources\CRoleResource\Concern\CanAccessClientData;
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
            Actions\EditAction::make(),
        ];
    }






}
