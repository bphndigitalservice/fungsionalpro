<?php

namespace App\Filament\Clusters\Reference\Resources\RegProvinceResource\Pages;

use App\Filament\Clusters\Reference\Resources\RegProvinceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRegProvince extends EditRecord
{
    protected static string $resource = RegProvinceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
