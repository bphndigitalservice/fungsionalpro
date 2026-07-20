<?php

namespace App\Filament\Resources\ClientPointSubmissionBagResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\ClientPointSubmissionBagResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClientPointSubmissionBag extends ViewRecord
{
    protected static string $resource = ClientPointSubmissionBagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
