<?php

namespace App\Filament\Resources\ClientActivityResource\Pages;

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
            Actions\CreateAction::make(),
        ];
    }


    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()->where('client_id', Client::current() ? Client::current()->id : 0);
    }
}
