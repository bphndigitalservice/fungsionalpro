<?php

namespace App\Filament\Clusters\Reference\Resources\RegProvinces\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Clusters\Reference\Resources\RegProvinces\RegProvinceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRegProvince extends EditRecord
{
    protected static string $resource = RegProvinceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
