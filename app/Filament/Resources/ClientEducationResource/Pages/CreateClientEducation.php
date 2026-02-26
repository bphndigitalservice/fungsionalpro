<?php

namespace App\Filament\Resources\ClientEducationResource\Pages;

use App\Filament\Resources\ClientEducationResource;
use App\Models\Client;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateClientEducation extends CreateRecord
{
    protected static string $resource = ClientEducationResource::class;

    /**
     * @return string|\Illuminate\Contracts\Support\Htmlable
     */
    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('labels.page.client_education_create.title');
    }

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
