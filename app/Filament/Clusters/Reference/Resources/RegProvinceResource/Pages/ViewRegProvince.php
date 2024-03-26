<?php

namespace App\Filament\Clusters\Reference\Resources\RegProvinceResource\Pages;

use App\Filament\Clusters\Reference\Resources\RegProvinceResource;
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
