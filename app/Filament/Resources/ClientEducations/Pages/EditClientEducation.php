<?php

namespace App\Filament\Resources\ClientEducations\Pages;

use Filament\Actions\DeleteAction;
use App\Concerns\Filament\AuthorizesOwnClientRecord;
use App\Filament\Resources\ClientEducations\ClientEducationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientEducation extends EditRecord
{
    use AuthorizesOwnClientRecord;

    protected static string $resource = ClientEducationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Perbarui'),

            $this->getCancelFormAction()
                ->label('Batal'),
        ];
    }
}
