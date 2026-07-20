<?php

namespace App\Filament\Clusters\Reference\Resources\RegRegencies\Pages;

use Filament\Actions\EditAction;
use App\Filament\Clusters\Reference\Resources\RegRegencies\RegRegencyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRegRegency extends ViewRecord
{
    protected static string $resource = RegRegencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
