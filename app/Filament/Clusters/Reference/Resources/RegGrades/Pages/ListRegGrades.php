<?php

namespace App\Filament\Clusters\Reference\Resources\RegGrades\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\Reference\Resources\RegGrades\RegGradeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRegGrades extends ListRecords
{
    protected static string $resource = RegGradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
