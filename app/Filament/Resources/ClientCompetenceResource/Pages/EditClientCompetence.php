<?php

namespace App\Filament\Resources\ClientCompetenceResource\Pages;

use App\Concerns\Filament\AuthorizesOwnClientRecord;
use App\Filament\Resources\ClientCompetenceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientCompetence extends EditRecord
{
    use AuthorizesOwnClientRecord;

    protected static string $resource = ClientCompetenceResource::class;

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
