<?php

namespace App\Filament\Resources\ClientEducationResource\Pages;

use App\Filament\Resources\ClientEducationResource;
use App\Models\Client;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientEducation extends ListRecords
{
    protected static string $resource = ClientEducationResource::class;

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
