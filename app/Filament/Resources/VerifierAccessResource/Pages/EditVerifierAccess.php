<?php

namespace App\Filament\Resources\VerifierAccessResource\Pages;

use App\Filament\Resources\VerifierAccessResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVerifierAccess extends EditRecord
{
    protected static string $resource = VerifierAccessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
