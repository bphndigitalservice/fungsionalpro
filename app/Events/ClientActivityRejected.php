<?php

namespace App\Events;

use App\Models\ClientActivity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientActivityRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected ClientActivity $activity;

    protected ?string $verifierNotes;

    public function __construct(
        ClientActivity $activity,
        ?string $verifierNotes = null
    ) {

        $this->activity = $activity;

        $this->verifierNotes = $verifierNotes;
    }

    public function broadcastOn(): array
    {
        return [
            // not using broadcasting
        ];
    }

    public function getActivity(): ClientActivity
    {
        return $this->activity;
    }

    public function getVerifierNotes(): ?string
    {
        return $this->verifierNotes;
    }
}