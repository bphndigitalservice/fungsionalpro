<?php

namespace App\Filament\Clusters\Reference\Resources\RegProvinceResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\Reference\Resources\RegProvinceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRegProvinces extends ListRecords
{
    protected static string $resource = RegProvinceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
