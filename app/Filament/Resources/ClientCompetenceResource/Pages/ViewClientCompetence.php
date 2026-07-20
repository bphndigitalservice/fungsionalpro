<?php

namespace App\Filament\Resources\ClientCompetenceResource\Pages;

use App\Concerns\Filament\AuthorizesOwnClientRecord;
use App\Filament\Resources\ClientCompetenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClientCompetence extends ViewRecord
{
    use AuthorizesOwnClientRecord;

    protected static string $resource = ClientCompetenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
