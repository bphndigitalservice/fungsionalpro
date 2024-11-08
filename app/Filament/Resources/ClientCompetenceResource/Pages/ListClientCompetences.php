<?php

namespace App\Filament\Resources\ClientCompetenceResource\Pages;

use App\Filament\Resources\ClientCompetenceResource;
use App\Models\Client;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListClientCompetences extends ListRecords
{
    protected static string $resource = ClientCompetenceResource::class;

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

    public function getTitle(): string|Htmlable
    {
        return "Diklat/Pelatihan";
    }


}
