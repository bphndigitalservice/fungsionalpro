<?php

namespace App\Filament\Clusters\Reference\Resources\RegRegencies\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\Reference\Resources\RegRegencies\RegRegencyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRegRegencies extends ListRecords
{
    protected static string $resource = RegRegencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
