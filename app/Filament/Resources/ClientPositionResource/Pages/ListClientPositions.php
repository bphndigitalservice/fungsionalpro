<?php

namespace App\Filament\Resources\ClientPositionResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ClientPositionResource;
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
