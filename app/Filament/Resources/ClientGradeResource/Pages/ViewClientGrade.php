<?php

namespace App\Filament\Resources\ClientGradeResource\Pages;

use App\Concerns\Filament\RedirectsLockedClientMenuAccess;
use App\Filament\Resources\ClientGradeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClientGrade extends ViewRecord
{
    use RedirectsLockedClientMenuAccess;

    protected static string $resource = ClientGradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
