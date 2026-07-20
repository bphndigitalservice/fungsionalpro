<?php

namespace App\Filament\Resources\ClientCompetenceResource\Pages;

use Filament\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;
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
            CreateAction::make(),
        ];
    }



    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()->where('client_id', Client::current()?->id ?? 0);
    }

    public function getTitle(): string|Htmlable
    {
        return "Diklat/Pelatihan";
    }


}
