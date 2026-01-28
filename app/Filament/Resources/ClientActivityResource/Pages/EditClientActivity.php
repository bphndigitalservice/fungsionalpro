<?php

namespace App\Filament\Resources\ClientActivityResource\Pages;

use App\Filament\Resources\ClientActivityResource;
use Filament\Actions;
use App\Models\Client;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EditClientActivity extends EditRecord
{
    protected static string $resource = ClientActivityResource::class;

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
