<?php

namespace App\Filament\Resources\ClientPositionResource\Pages;

use App\Concerns\Filament\RedirectsLockedClientMenuAccess;
use App\Filament\Resources\ClientPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClientPosition extends ViewRecord
{
    use RedirectsLockedClientMenuAccess;

    protected static string $resource = ClientPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
