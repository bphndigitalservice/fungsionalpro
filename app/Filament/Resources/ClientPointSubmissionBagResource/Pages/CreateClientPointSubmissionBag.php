<?php

namespace App\Filament\Resources\ClientPointSubmissionBagResource\Pages;

use App\Filament\Resources\ClientPointSubmissionBagResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClientPointSubmissionBag extends CreateRecord
{
    protected static string $resource = ClientPointSubmissionBagResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->user()->id;

        return $data;
    }
}
