<?php

namespace App\Filament\Resources\ClientPointSubmissionBags\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ClientPointSubmissionBags\ClientPointSubmissionBagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientPointSubmissionBags extends ListRecords
{
    protected static string $resource = ClientPointSubmissionBagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
