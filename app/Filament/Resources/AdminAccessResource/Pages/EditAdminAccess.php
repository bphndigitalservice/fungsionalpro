<?php

namespace App\Filament\Resources\AdminAccessResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\AdminAccessResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdminAccess extends EditRecord
{
    protected static string $resource = AdminAccessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
