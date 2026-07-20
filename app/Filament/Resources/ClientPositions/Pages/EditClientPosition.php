<?php

namespace App\Filament\Resources\ClientPositions\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\ClientPositions\ClientPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientPosition extends EditRecord
{
    protected static string $resource = ClientPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
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
