<?php

namespace App\Events;

use App\Models\Client;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientProfileCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected Client $client;

    /**
     * Create a new event instance.
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [];
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}
