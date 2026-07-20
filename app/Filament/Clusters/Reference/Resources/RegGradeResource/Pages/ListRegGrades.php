<?php

namespace App\Filament\Clusters\Reference\Resources\RegGradeResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\Reference\Resources\RegGradeResource;
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
