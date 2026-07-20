<?php

namespace App\Filament\Resources\CRoles\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\CRoles\CRoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCRole extends ViewRecord
{
    protected static string $resource = CRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
