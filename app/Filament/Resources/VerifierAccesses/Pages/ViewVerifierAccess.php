<?php

namespace App\Filament\Resources\VerifierAccesses\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\VerifierAccesses\VerifierAccessResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewVerifierAccess extends ViewRecord
{
    protected static string $resource = VerifierAccessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
