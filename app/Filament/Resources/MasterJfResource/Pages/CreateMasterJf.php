<?php

namespace App\Filament\Resources\MasterJfResource\Pages;

use App\Filament\Resources\MasterJfResource;
use App\Support\MasterJfAgencyForm;
use Filament\Resources\Pages\CreateRecord;

class CreateMasterJf extends CreateRecord
{
    protected static string $resource = MasterJfResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return MasterJfAgencyForm::mutate($data);
    }
}
