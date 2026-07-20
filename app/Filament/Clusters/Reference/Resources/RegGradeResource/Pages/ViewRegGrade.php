<?php

namespace App\Filament\Clusters\Reference\Resources\RegGradeResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Clusters\Reference\Resources\RegGradeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRegGrade extends ViewRecord
{
    protected static string $resource = RegGradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
