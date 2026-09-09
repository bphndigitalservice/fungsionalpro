<?php

namespace App\Filament\Resources\ClientDossierResource\Pages;

use App\Concerns\Filament\RedirectsLockedClientMenuAccess;
use App\Filament\Resources\ClientDossierResource;
use App\Models\Client;
use Filament\Resources\Pages\CreateRecord;

class CreateClientDossier extends CreateRecord
{
    use RedirectsLockedClientMenuAccess;

    protected static string $resource = ClientDossierResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['client_id'] = Client::current()->id;

        return $data;
    }
}
