<?php

namespace App\Filament\Clusters\Reference\Resources\RegDepartmentResource\Pages;

use App\Filament\Clusters\Reference\Resources\RegDepartmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRegDepartment extends EditRecord
{
    protected static string $resource = RegDepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
