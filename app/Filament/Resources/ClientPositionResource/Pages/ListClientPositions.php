<?php

namespace App\Filament\Resources\ClientPositionResource\Pages;

use App\Concerns\Filament\RedirectsLockedClientMenuAccess;
use App\Filament\Resources\ClientPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientPositions extends ListRecords
{
    use RedirectsLockedClientMenuAccess;

    protected static string $resource = ClientPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
