<?php

namespace App\Filament\Resources\ClientPointSubmissionBags\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\ClientPointSubmissionBags\ClientPointSubmissionBagResource;
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
