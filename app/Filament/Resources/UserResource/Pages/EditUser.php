<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /** @var array{nip: mixed, c_role_id: mixed} */
    protected array $clientFormData = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $client = $this->record->client;

        if ($client) {
            $data['nip'] = $client->nip;
            $data['c_role_id'] = $client->c_role_id;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->clientFormData = [
            'nip' => $data['nip'] ?? null,
            'c_role_id' => $data['c_role_id'] ?? null,
        ];

        unset($data['nip'], $data['c_role_id']);

        return $data;
    }

    protected function afterSave(): void
    {
        UserResource::syncClientForUser($this->record, $this->clientFormData);
    }
}
