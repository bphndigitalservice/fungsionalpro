<?php

namespace App\Filament\Resources\ClientPointSubmissionBagResource\Pages;

use App\Filament\Resources\ClientPointSubmissionBagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientPointSubmissionBags extends ListRecords
{
    protected static string $resource = ClientPointSubmissionBagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
