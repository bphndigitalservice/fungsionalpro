<?php

namespace App\Filament\Resources\ClientEducationResource\Pages;

use App\Filament\Resources\ClientEducationResource;
use App\Models\Client;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateClientEducation extends CreateRecord
{
    protected static string $resource = ClientEducationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['client_id'] = Client::current()->id;

        return $data;
    }
}
