<?php

namespace App\Filament\Resources\ClientDossierResource\Pages;

use App\Concerns\Filament\RedirectsLockedClientMenuAccess;
use App\Filament\Resources\ClientDossierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientDossiers extends ListRecords
{
    use RedirectsLockedClientMenuAccess;

    protected static string $resource = ClientDossierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
