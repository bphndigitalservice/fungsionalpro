<?php

namespace App\Filament\Resources\ClientPositions\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\ClientPositions\ClientPositionResource;
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
