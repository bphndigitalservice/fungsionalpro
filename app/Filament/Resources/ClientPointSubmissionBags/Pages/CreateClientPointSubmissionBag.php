<?php

namespace App\Filament\Resources\ClientPointSubmissionBags\Pages;

use App\Filament\Resources\ClientPointSubmissionBags\ClientPointSubmissionBagResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClientPointSubmissionBag extends CreateRecord
{
    protected static string $resource = ClientPointSubmissionBagResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->user()->id;
        $data['updated_by'] = $data['created_by'];

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
