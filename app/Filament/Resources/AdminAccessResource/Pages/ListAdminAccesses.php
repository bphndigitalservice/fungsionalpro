<?php

namespace App\Filament\Resources\AdminAccessResource\Pages;

use App\Filament\Resources\AdminAccessResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdminAccesses extends ListRecords
{
    protected static string $resource = AdminAccessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
