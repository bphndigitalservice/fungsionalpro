<?php

namespace App\Subscribers;

use App\Enums\PointSubmissionStatus;
use App\Events\PointSubmissionAccepted;
use App\Events\PointSubmissionRejected; // Ensure this event exists
use App\Models\ClientPoint;
use App\Notifications\AKVerificationNotification;
use Illuminate\Events\Dispatcher;

class PointEventSubscriber
{
    public function handlePointAccepted(PointSubmissionAccepted $event): void
    {
        $submission = $event->getPointSubmission();
        $client = $submission->client;

        if ($submission->status == PointSubmissionStatus::Verified) {
            // 1. Update/Create Points
            $currentPoint = ClientPoint::getPoint($client->id);
            ClientPoint::updateOrCreate([
                'client_id' => $client->id,
            ], [
                'point' => $currentPoint + $submission->point,
            ]);

            // 2. Notify User
            if ($client->user) {
                $client->user->notify(new AKVerificationNotification($submission, 'accepted'));
            }
        }
    }

    public function handlePointRejected($event): void
    {
        $submission = $event->getPointSubmission();
        $client = $submission->client;

        if ($client->user) {
            $client->user->notify(new AKVerificationNotification(
                $submission, 
                'rejected', 
                $event->getVerifierNotes()
            ));
        }
    }

    public function subscribe(Dispatcher $dispatcher): array
    {
        return [
            PointSubmissionAccepted::class => 'handlePointAccepted',
            PointSubmissionRejected::class => 'handlePointRejected',
        ];
    }
}