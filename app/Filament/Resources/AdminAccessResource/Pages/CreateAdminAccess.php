<?php

namespace App\Filament\Resources\AdminAccessResource\Pages;

use App\Filament\Resources\AdminAccessResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdminAccess extends CreateRecord
{
    protected static string $resource = AdminAccessResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return AdminAccessResource::formDataForSelectedUser($data);
    }
}
