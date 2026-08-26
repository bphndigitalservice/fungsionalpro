<?php

namespace App\Filament\Resources\ClientGradeResource\Pages;

use App\Concerns\Filament\RedirectsLockedClientMenuAccess;
use App\Filament\Resources\ClientGradeResource;
use App\Models\Client;
use Filament\Resources\Pages\CreateRecord;

class CreateClientGrade extends CreateRecord
{
    use RedirectsLockedClientMenuAccess;

    protected static string $resource = ClientGradeResource::class;

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
