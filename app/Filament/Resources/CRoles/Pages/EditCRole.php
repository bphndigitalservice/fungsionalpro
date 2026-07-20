<?php

namespace App\Filament\Resources\CRoles\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\CRoles\CRoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCRole extends EditRecord
{
    protected static string $resource = CRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
