<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /** @var array{nip: mixed, c_role_id: mixed} */
    protected array $clientFormData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->clientFormData = [
            'nip' => $data['nip'] ?? null,
            'c_role_id' => $data['c_role_id'] ?? null,
        ];

        unset($data['nip'], $data['c_role_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        UserResource::syncClientForUser($this->record, $this->clientFormData);
    }
}
