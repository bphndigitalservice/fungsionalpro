<?php

namespace App\Filament\Resources\ClientDossiers\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\ClientDossiers\ClientDossierResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClientDossier extends ViewRecord
{
    protected static string $resource = ClientDossierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
