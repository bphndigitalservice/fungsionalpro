<?php

namespace App\Filament\Resources\ClientCompetences\Pages;

use Filament\Actions\EditAction;
use App\Concerns\Filament\AuthorizesOwnClientRecord;
use App\Filament\Resources\ClientCompetences\ClientCompetenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClientCompetence extends ViewRecord
{
    use AuthorizesOwnClientRecord;

    protected static string $resource = ClientCompetenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
