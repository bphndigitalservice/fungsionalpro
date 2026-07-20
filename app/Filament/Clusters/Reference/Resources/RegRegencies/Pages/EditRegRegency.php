<?php

namespace App\Filament\Clusters\Reference\Resources\RegRegencies\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Clusters\Reference\Resources\RegRegencies\RegRegencyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRegRegency extends EditRecord
{
    protected static string $resource = RegRegencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
