<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use App\Models\Client;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientProfileUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected Client $client;

    protected ?string $verifierNotes;

    /**
     * Create a new event instance.
     */
    public function __construct(Client $client, ?string $verifierNotes = null)
    {
        $this->client = $client;
        $this->verifierNotes = $verifierNotes;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [];
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getVerifierNotes(): ?string
    {
        return $this->verifierNotes;
    }
}
