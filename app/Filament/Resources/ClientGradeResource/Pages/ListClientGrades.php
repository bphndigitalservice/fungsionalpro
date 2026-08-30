<?php

namespace App\Filament\Resources\ClientGradeResource\Pages;

use App\Concerns\Filament\RedirectsLockedClientMenuAccess;
use App\Filament\Resources\ClientGradeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientGrades extends ListRecords
{
    use RedirectsLockedClientMenuAccess;

    protected static string $resource = ClientGradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Riwayat Pangkat/Golongan';
    }
}
