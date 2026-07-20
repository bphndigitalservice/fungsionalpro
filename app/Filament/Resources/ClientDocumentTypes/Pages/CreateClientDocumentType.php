<?php

namespace App\Filament\Resources\ClientDocumentTypes\Pages;

use App\Filament\Resources\ClientDocumentTypes\ClientDocumentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateClientDocumentType extends CreateRecord
{
    protected static string $resource = ClientDocumentTypeResource::class;
}
