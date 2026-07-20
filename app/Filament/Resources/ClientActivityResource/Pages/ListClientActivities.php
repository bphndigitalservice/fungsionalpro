<?php

namespace App\Filament\Resources\ClientActivityResource\Pages;

use Filament\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ClientActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Client;

class ListClientActivities extends ListRecords
{
    protected static string $resource = ClientActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
        ];
    }


    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()->where('client_id', Client::current()?->id ?? 0);
    }
}
