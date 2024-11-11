<?php

namespace App\Filament\Resources\ClientEducationResource\Pages;

use App\Filament\Resources\ClientEducationResource;
use App\Models\Client;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EditClientEducation extends EditRecord
{
    protected static string $resource = ClientEducationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function authorizeAccess(): void
    {
        $client = Client::current();
        if ($client && $this->record->client_id !== $client->id) {
            throw new ModelNotFoundException('You are not authorized to access this record.');
        }
    }
}
