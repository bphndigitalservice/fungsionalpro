<?php

namespace App\Filament\Resources\ClientPositionResource\Pages;

use App\Filament\Resources\ClientPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientPosition extends EditRecord
{
    protected static string $resource = ClientPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
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
