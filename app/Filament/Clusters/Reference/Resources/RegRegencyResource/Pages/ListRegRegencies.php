<?php

namespace App\Filament\Clusters\Reference\Resources\RegRegencyResource\Pages;

use App\Filament\Clusters\Reference\Resources\RegRegencyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRegRegencies extends ListRecords
{
    protected static string $resource = RegRegencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
