<?php

namespace App\Filament\Resources\ClientPointSubmissionBagResource\Pages;

use App\Filament\Resources\ClientPointSubmissionBagResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClientPointSubmissionBag extends ViewRecord
{
    protected static string $resource = ClientPointSubmissionBagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
