<?php

namespace App\Subscribers;

use App\Enums\Verified;
use App\Events\ClientProfileAccepted;
use App\Events\ClientProfileCompleted;
use App\Events\ClientProfileRejected;
use App\Events\ClientProfileUpdated;
use App\Jobs\ProcessAcceptedProfile;
use App\Jobs\ProcessRejectedProfile;
use App\Models\ClientPoint;
use App\Models\VClientNote;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Notifications\IdentityVerificationNotification;

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
            'verifier_notes' => 'ACK OK',
        ]);

        if ($client->user) {
            $client->user->notify(new IdentityVerificationNotification($client, 'accepted'));
        }

        ProcessAcceptedProfile::dispatch($client)->onQueue('emails');

    }

    public function handleRejectedClientProfile(ClientProfileRejected $event): void
    {
        $client = $event->getClient();

        DB::beginTransaction();
        try {
            $client->forceFill([
                'is_verified' => Verified::Unverified,
            ])->save();

            VClientNote::updateOrCreate([
                'client_id' => $client->id,
            ], [
                'client_notes' => 'system::update-profile',
                'verifier_notes' => $event->getVerifierNotes(),
            ]);

            DB::commit();

            if ($client->user) {
                $client->user->notify(new IdentityVerificationNotification(
                    $client, 
                    'rejected', 
                    $event->getVerifierNotes()
                ));
            }

            ProcessRejectedProfile::dispatch($client->user->email,
                $client->crole->role_name,
                $event->getVerifierNotes());

        } catch (\Exception $exception) {
            Log::error($exception->getMessage(), ['client_id' => $client->id]);
            DB::rollBack();
        }

    }

    public function handleUpdatedClientProfile(ClientProfileUpdated $event): void
    {
        $client = $event->getClient();

        DB::beginTransaction();
        try {
            $client->forceFill([
                'is_verified' => null,
                'verified_at' => null,
            ])->save();

            VClientNote::updateOrCreate([
                'client_id' => $client->id,
            ], [
                'client_notes' => 'system::update-profile',
                'verifier_notes' => null,
            ]);

            DB::commit();

        } catch (\Exception $exception) {
            Log::error($exception->getMessage(), ['client_id' => $event->getClient()->id]);
            DB::rollBack();
        }

    }

    public function subscribe(Dispatcher $dispatcher): array
    {
        return [
            ClientProfileCompleted::class => 'handleCompletedClientProfile',
            ClientProfileRejected::class => 'handleRejectedClientProfile',
            ClientProfileAccepted::class => 'handleAcceptedClientProfile',
            ClientProfileUpdated::class => 'handleUpdatedClientProfile',
        ];
    }
}
