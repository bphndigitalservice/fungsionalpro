<?php

namespace App\Filament\Clusters\Reference\Resources\RegRegencyResource\Pages;

use App\Filament\Clusters\Reference\Resources\RegRegencyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRegRegency extends EditRecord
{
    protected static string $resource = RegRegencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
