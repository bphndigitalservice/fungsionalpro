<?php

namespace App\Filament\Resources\VerifierAccesses\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\VerifierAccesses\VerifierAccessResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVerifierAccess extends EditRecord
{
    protected static string $resource = VerifierAccessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
