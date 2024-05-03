<?php

namespace App\Events;

use App\Models\ClientPointSubmission;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PointSubmissionAccepted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected ClientPointSubmission $pointSubmission;

    /**
     * Create a new event instance.
     */
    public function __construct(ClientPointSubmission $pointSubmission)
    {
        $this->pointSubmission = $pointSubmission;
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

    public function getPointSubmission(): ClientPointSubmission
    {
        return $this->pointSubmission;
    }
}
