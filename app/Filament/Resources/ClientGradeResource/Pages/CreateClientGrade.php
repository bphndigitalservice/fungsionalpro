<?php

namespace App\Filament\Resources\ClientGradeResource\Pages;

use App\Filament\Resources\ClientGradeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Client;

class CreateClientGrade extends CreateRecord
{
    protected static string $resource = ClientGradeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['client_id'] = Client::current()->id;

        return $data;
    }
}

