<?php

namespace App\Filament\Resources\ClientGradeResource\Pages;

use App\Filament\Resources\ClientGradeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientGrades extends ListRecords
{
    protected static string $resource = ClientGradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
