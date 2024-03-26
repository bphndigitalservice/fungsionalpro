<?php

namespace App\Filament\Clusters\Reference\Resources\RegDepartmentResource\Pages;

use App\Filament\Clusters\Reference\Resources\RegDepartmentResource;
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
