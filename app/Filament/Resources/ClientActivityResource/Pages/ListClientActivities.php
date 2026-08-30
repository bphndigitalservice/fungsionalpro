<?php

namespace App\Filament\Resources\ClientActivityResource\Pages;

use App\Concerns\Filament\RedirectsLockedClientMenuAccess;
use App\Filament\Resources\ClientActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientActivities extends ListRecords
{
    use RedirectsLockedClientMenuAccess;

    protected static string $resource = ClientActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
