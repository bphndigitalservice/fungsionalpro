<?php

namespace App\Filament\Resources\ClientDossierResource\Pages;

use App\Concerns\Filament\RedirectsLockedClientMenuAccess;
use App\Filament\Resources\ClientDossierResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClientDossier extends ViewRecord
{
    use RedirectsLockedClientMenuAccess;

    protected static string $resource = ClientDossierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
