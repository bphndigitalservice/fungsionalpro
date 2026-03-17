<?php

namespace App\Filament\Resources\ClientGradeResource\Pages;

use App\Filament\Resources\ClientGradeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientGrade extends EditRecord
{
    protected static string $resource = ClientGradeResource::class;

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
