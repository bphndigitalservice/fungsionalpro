<?php

namespace App\Events;

use App\Models\Client;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientProfileAccepted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}
