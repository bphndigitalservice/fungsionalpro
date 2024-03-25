<?php

namespace App\Filament\Resources\CRoleResource\Pages;

use App\Filament\Resources\CRoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCRole extends ViewRecord
{
    protected static string $resource = CRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
