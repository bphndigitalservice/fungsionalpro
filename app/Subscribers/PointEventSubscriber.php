<?php

namespace App\Subscribers;

use App\Events\ClientProfileCompleted;
use App\Events\PointSubmissionAccepted;
use App\Models\ClientPoint;
use Illuminate\Events\Dispatcher;

class PointEventSubscriber
{

    public function handlePointAccepted(PointSubmissionAccepted $event): void
    {
        $clientId = $event->getPointSubmission()->client->id;
        $currentPoint = ClientPoint::getPoint($clientId);

        ClientPoint::updateOrCreate([
            'client_id' => $clientId,
        ], [
            'point' => $currentPoint + $event->getPointSubmission()->point
        ]);
    }


    public function subscribe(Dispatcher $dispatcher): array
    {
        return [
            PointSubmissionAccepted::class => 'handlePointAccepted',
        ];
    }
}
