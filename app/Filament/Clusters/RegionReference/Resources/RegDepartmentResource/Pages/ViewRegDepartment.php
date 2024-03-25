<?php

namespace App\Filament\Clusters\RegionReference\Resources\RegDepartmentResource\Pages;

use App\Filament\Clusters\RegionReference\Resources\RegDepartmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRegDepartment extends ViewRecord
{
    protected static string $resource = RegDepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
