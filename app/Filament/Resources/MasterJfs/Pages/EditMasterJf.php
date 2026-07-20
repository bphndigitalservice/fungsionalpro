<?php

namespace App\Filament\Resources\MasterJfs\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\MasterJfs\MasterJfResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMasterJf extends EditRecord
{
    protected static string $resource = MasterJfResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
