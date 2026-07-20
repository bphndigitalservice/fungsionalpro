<?php

namespace App\Filament\Resources\CRoles\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\CRoles\CRoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCRoles extends ListRecords
{
    protected static string $resource = CRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
