<?php

namespace App\Filament\Resources\ClientPositions\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ClientPositions\ClientPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientPositions extends ListRecords
{
    protected static string $resource = ClientPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
