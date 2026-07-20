<?php

namespace App\Filament\Clusters\Reference\Resources\RegDepartmentResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Clusters\Reference\Resources\RegDepartmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRegDepartment extends ViewRecord
{
    protected static string $resource = RegDepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
