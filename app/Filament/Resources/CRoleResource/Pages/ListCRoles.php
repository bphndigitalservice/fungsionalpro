<?php

namespace App\Filament\Resources\CRoleResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\CRoleResource;
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
