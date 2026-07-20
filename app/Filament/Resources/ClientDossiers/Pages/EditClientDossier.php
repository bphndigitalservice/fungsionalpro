<?php

namespace App\Filament\Resources\ClientDossiers\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\ClientDossiers\ClientDossierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientDossier extends EditRecord
{
    protected static string $resource = ClientDossierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
