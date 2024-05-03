<?php

namespace App\Filament\Resources\ClientCompetenceResource\Pages;

use App\Filament\Resources\ClientCompetenceResource;
use App\Models\Client;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateClientCompetence extends CreateRecord
{
    protected static string $resource = ClientCompetenceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['client_id'] = Client::current()->id;
    
        return $data;
    }

}
