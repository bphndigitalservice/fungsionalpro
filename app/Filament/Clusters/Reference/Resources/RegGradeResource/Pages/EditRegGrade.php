<?php

namespace App\Filament\Clusters\Reference\Resources\RegGradeResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Clusters\Reference\Resources\RegGradeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRegGrade extends EditRecord
{
    protected static string $resource = RegGradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
