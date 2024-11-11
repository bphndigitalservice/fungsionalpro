<?php

namespace App\Filament\Resources\ClientDossierResource\Pages;

use App\Filament\Resources\ClientDossierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientDossier extends EditRecord
{
    protected static string $resource = ClientDossierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
