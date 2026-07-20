<?php

namespace App\Filament\Resources\ClientCompetences\Pages;

use App\Filament\Resources\ClientCompetences\ClientCompetenceResource;
use App\Models\Client;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateClientCompetence extends CreateRecord
{
    protected static string $resource = ClientCompetenceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['client_id'] = Client::current()->id;

        return $data;
    }

    public function getTitle(): string|Htmlable
    {
        return "Input Diklat/Pelatihan";
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
