<?php

namespace App\Filament\Resources\ClientDocumentTypeResource\Pages;

use App\Filament\Resources\ClientDocumentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientDocumentTypes extends ListRecords
{
    protected static string $resource = ClientDocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
