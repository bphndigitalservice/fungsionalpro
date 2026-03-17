<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\ClientActivity;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientActivityAccepted
{
    use Dispatchable, SerializesModels;

    protected ClientActivity $activity;

    public function __construct(ClientActivity $activity)
    {
        $this->activity = $activity;
    }

    public function getActivity(): ClientActivity
    {
        return $this->activity;
    }
}