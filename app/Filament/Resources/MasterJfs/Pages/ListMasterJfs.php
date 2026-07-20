<?php

namespace App\Filament\Resources\MasterJfs\Pages;

use App\Filament\Resources\MasterJfs\MasterJfResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMasterJfs extends ListRecords
{
    protected static string $resource = MasterJfResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
