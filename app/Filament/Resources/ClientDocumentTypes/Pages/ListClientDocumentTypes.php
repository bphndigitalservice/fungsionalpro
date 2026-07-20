<?php

namespace App\Filament\Resources\ClientDocumentTypes\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ClientDocumentTypes\ClientDocumentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientDocumentTypes extends ListRecords
{
    protected static string $resource = ClientDocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
