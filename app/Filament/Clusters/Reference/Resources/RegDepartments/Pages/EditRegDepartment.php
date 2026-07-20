<?php

namespace App\Filament\Clusters\Reference\Resources\RegDepartments\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Clusters\Reference\Resources\RegDepartments\RegDepartmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRegDepartment extends EditRecord
{
    protected static string $resource = RegDepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
