<?php

namespace App\Filament\Clusters\RegionReference\Resources\RegRegencyResource\Pages;

use App\Filament\Clusters\RegionReference\Resources\RegRegencyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRegRegency extends ViewRecord
{
    protected static string $resource = RegRegencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
