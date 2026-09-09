<?php

namespace App\Filament\Resources\AdminAccessResource\Pages;

use App\Filament\Resources\AdminAccessResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdminAccess extends EditRecord
{
    protected static string $resource = AdminAccessResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return AdminAccessResource::formDataForSelectedUser($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
