<?php

namespace App\Filament\Resources\ClientDocumentTypes\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\ClientDocumentTypes\ClientDocumentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClientDocumentType extends ViewRecord
{
    protected static string $resource = ClientDocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
