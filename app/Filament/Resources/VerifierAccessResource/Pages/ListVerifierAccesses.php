<?php

namespace App\Filament\Resources\VerifierAccessResource\Pages;

use App\Filament\Resources\VerifierAccessResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVerifierAccesses extends ListRecords
{
    protected static string $resource = VerifierAccessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
