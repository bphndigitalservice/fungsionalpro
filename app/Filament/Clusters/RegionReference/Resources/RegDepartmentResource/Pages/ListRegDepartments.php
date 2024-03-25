<?php

namespace App\Filament\Clusters\RegionReference\Resources\RegDepartmentResource\Pages;

use App\Filament\Clusters\RegionReference\Resources\RegDepartmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRegDepartments extends ListRecords
{
    protected static string $resource = RegDepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
