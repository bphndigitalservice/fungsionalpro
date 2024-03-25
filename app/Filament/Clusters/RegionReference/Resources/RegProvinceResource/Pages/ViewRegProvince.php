<?php

namespace App\Filament\Clusters\RegionReference\Resources\RegProvinceResource\Pages;

use App\Filament\Clusters\RegionReference\Resources\RegProvinceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRegProvince extends ViewRecord
{
    protected static string $resource = RegProvinceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
