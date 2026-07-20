<?php

namespace App\Filament\Resources\ClientDossiers\Pages;

use App\Filament\Resources\ClientDossiers\ClientDossierResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Client;

class CreateClientDossier extends CreateRecord
{
    protected static string $resource = ClientDossierResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['client_id'] = Client::current()->id;

        return $data;
    }
}
