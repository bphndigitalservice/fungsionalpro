<?php

namespace App\Filament\Resources\ClientPositionResource\Pages;

use App\Filament\Resources\ClientPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Client;

class CreateClientPosition extends CreateRecord
{
    protected static string $resource = ClientPositionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['client_id'] = Client::current()->id;
        return $data;
    }
}
