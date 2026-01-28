<?php

namespace App\Filament\Resources\ClientActivityResource\Pages;

use App\Filament\Resources\ClientActivityResource;
use Filament\Actions;
use App\Models\Client;
use Filament\Resources\Pages\CreateRecord;

class CreateClientActivity extends CreateRecord
{
    protected static string $resource = ClientActivityResource::class;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['client_id'] = Client::current()->id;

        return $data;
    }
}


