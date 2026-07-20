<?php

namespace App\Filament\Clusters\Reference\Resources\RegProvinces\Pages;

use Filament\Actions\EditAction;
use App\Filament\Clusters\Reference\Resources\RegProvinces\RegProvinceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRegProvince extends ViewRecord
{
    protected static string $resource = RegProvinceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
