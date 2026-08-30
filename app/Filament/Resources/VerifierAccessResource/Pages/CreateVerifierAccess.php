<?php

namespace App\Filament\Resources\VerifierAccessResource\Pages;

use App\Filament\Resources\VerifierAccessResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVerifierAccess extends CreateRecord
{
    protected static string $resource = VerifierAccessResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return VerifierAccessResource::formDataForRegionalScope($data);
    }
}
