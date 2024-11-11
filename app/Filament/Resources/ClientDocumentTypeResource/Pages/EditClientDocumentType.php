<?php

namespace App\Filament\Resources\ClientDocumentTypeResource\Pages;

use App\Filament\Resources\ClientDocumentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientDocumentType extends EditRecord
{
    protected static string $resource = ClientDocumentTypeResource::class;

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
