<?php

namespace App\Concerns\Filament;

use App\Models\Client;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait AuthorizesOwnClientRecord
{
    protected function authorizeAccess(): void
    {
        $client = Client::current();

        if ($client === null || (string) $this->record->client_id !== (string) $client->id) {
            throw new ModelNotFoundException('You are not authorized to access this record.');
        }
    }
}
