<?php

namespace App\Filament\Resources\MasterJfResource\Pages;

use App\Filament\Resources\MasterJfResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMasterJf extends EditRecord
{
    protected static string $resource = MasterJfResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
