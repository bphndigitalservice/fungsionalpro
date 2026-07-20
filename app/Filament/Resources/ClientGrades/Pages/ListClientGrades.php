<?php

namespace App\Filament\Resources\ClientGrades\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ClientGrades\ClientGradeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientGrades extends ListRecords
{
    protected static string $resource = ClientGradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Riwayat Pangkat/Golongan';
    }
}
