<?php

namespace App\Filament\Resources\ClientDossiers\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ClientDossiers\ClientDossierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientDossiers extends ListRecords
{
    protected static string $resource = ClientDossierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
