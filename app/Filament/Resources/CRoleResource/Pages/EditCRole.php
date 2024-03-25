<?php

namespace App\Filament\Resources\CRoleResource\Pages;

use App\Filament\Resources\CRoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCRole extends EditRecord
{
    protected static string $resource = CRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
