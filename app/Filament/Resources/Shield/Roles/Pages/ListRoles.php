<?php

declare(strict_types=1);

namespace App\Filament\Resources\Shield\Roles\Pages;

use App\Filament\Resources\Shield\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
