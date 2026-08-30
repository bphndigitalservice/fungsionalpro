<?php

namespace App\Filament\Resources\ClientEducationResource\Pages;

use App\Concerns\Filament\RedirectsLockedClientMenuAccess;
use App\Filament\Resources\ClientEducationResource;
use App\Models\Client;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateClientEducation extends CreateRecord
{
    use RedirectsLockedClientMenuAccess;

    protected static string $resource = ClientEducationResource::class;

    public function getTitle(): string|Htmlable
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
