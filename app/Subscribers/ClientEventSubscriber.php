<?php

namespace App\Subscribers;

use App\Events\ClientProfileCompleted;
use App\Models\ClientPoint;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class ClientEventSubscriber
{
    public function handleCompletedClientProfile(ClientProfileCompleted $event): void
    {
        $client = $event->getClient();

        Log::debug('Masuk');

        ClientPoint::create([
            'client_id' => $client->id,
            'point' => 0,
        ]);
    }

    public function subscribe(Dispatcher $dispatcher): array
    {
        return [
            ClientProfileCompleted::class => 'handleCompletedClientProfile',
        ];
    }
}
