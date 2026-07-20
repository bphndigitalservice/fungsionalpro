<?php

namespace App\Filament\Resources\ClientDocumentTypeResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\ClientDocumentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientDocumentType extends EditRecord
{
    protected static string $resource = ClientDocumentTypeResource::class;

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
