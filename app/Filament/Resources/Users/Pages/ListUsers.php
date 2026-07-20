<?php

namespace App\Filament\Resources\Users\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    // ⚠️ Check if this function exists inside your ListUsers.php file:
    protected function isTablePaginationEnabled(): bool
    {
        return true; // Ensure this is true, or remove the method entirely!
    }
}
