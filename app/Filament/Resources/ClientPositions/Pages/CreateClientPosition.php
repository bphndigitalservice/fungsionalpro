<?php

namespace App\Filament\Resources\ClientPositions\Pages;

use App\Filament\Resources\ClientPositions\ClientPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Client;

class CreateClientPosition extends CreateRecord
{
    protected static string $resource = ClientPositionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['client_id'] = Client::current()->id;
        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Simpan'),

            $this->getCreateAnotherFormAction()
                ->label('Simpan & Buat Lagi'),

            $this->getCancelFormAction()
                ->label('Batal'),
        ];
    }
}
