<?php

namespace App\Filament\Resources\ClientPositionResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\ClientPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClientPosition extends ViewRecord
{
    protected static string $resource = ClientPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
