<?php

namespace App\Subscribers;

use App\Enums\Verified;
use App\Events\ClientProfileAccepted;
use App\Events\ClientProfileCompleted;
use App\Events\ClientProfileRejected;
use App\Events\ClientProfileUpdated;
use App\Models\ClientPoint;
use App\Models\VClientNote;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientEventSubscriber
{
    public function handleCompletedClientProfile(ClientProfileCompleted $event): void
    {
        $client = $event->getClient();

        ClientPoint::create([
            'client_id' => $client->id,
            'point' => 0,
        ]);

        VClientNote::create([
            'client_id' => $client->id,
            'client_notes' => 'system::init',
        ]);
    }

    public function handleAcceptedClientProfile(ClientProfileAccepted $event): void
    {
        $client = $event->getClient();

        VClientNote::updateOrCreate([
            'client_id' => $client->id,
        ], [
            'client_notes' => 'system::update-profile',
            'verifier_notes' => 'ACK OK'
        ]);
    }

    public function handleRejectedClientProfile(ClientProfileRejected $event): void
    {
        $client = $event->getClient();

        DB::beginTransaction();
        try {
            $client->update([
                'is_verified' => Verified::Unverified,
            ]);

            VClientNote::updateOrCreate([
                'client_id' => $client->id,
            ], [
                'client_notes' => 'system::update-profile',
                'verifier_notes' => $event->getVerifierNotes()
            ]);

            DB::commit();

        } catch (\Exception $exception) {
            Log::error($exception->getMessage(), [$event->getClient(), $event->getVerifierNotes()]);
            DB::rollBack();
        }


    }

    public function handleUpdatedClientProfile(ClientProfileUpdated $event): void
    {
        $client = $event->getClient();

        DB::beginTransaction();
        try {
            // reset verification
            $client->update([
                'is_verified' => null,
            ]);

            VClientNote::updateOrCreate([
                'client_id' => $client->id,
            ], [
                'client_notes' => 'system::update-profile',
                'verifier_notes' => null
            ]);

            DB::commit();

        } catch (\Exception $exception) {
            Log::error($exception->getMessage(), [$event->getClient(), $event->getVerifierNotes()]);
            DB::rollBack();
        }


    }

    public function subscribe(Dispatcher $dispatcher): array
    {
        return [
            ClientProfileCompleted::class => 'handleCompletedClientProfile',
            ClientProfileRejected::class => 'handleRejectedClientProfile',
            ClientProfileAccepted::class => 'handleAcceptedClientProfile',
            ClientProfileUpdated::class => 'handleUpdatedClientProfile'
        ];
    }
}
