<?php

namespace App\Filament\Clusters\Reference\Resources\RegGrades\Pages;

use Filament\Actions\EditAction;
use App\Filament\Clusters\Reference\Resources\RegGrades\RegGradeResource;
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
