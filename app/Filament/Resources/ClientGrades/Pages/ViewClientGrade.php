<?php

namespace App\Filament\Resources\ClientGrades\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\ClientGrades\ClientGradeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClientGrade extends ViewRecord
{
    protected static string $resource = ClientGradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
