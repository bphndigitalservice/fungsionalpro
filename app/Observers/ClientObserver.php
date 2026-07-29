<?php


namespace App\Observers;

use App\Models\Client;
use App\Services\ClientMatchingService;
use App\Models\User;

class ClientObserver
{
    protected $service;

    public function __construct(ClientMatchingService $service)
    {
        $this->service = $service;
    }

    public function creating(Client $client): void
    {
        if (blank($client->nip)) {
            $client->is_verified = \App\Enums\Verified::Unverified;
            $client->status = $client->status ?? null;
            $client->assignation_type = $client->assignation_type ?? null;

            return;
        }

        $master = $this->service->findMasterByNip($client->nip);

        if ($master) {
            $this->service->applyMasterData($client, $master);
            $client->is_verified = \App\Enums\Verified::Unverified;
        } else {
            // Normal fallbacks if NIP is not present in master excel records
            $client->is_verified = \App\Enums\Verified::Unverified;
            $client->status = $client->status ?? null;
            $client->assignation_type = $client->assignation_type ?? null;
        }
    }

    public function created(Client $client): void
    {
        $master = filled($client->nip)
            ? $this->service->findMasterByNip($client->nip)
            : null;

        $extractedGender = filled($client->nip)
            ? $this->service->getGenderFromNip($client->nip)
            : 'male';

        $user = User::find($client->user_id);

        $client->identity()->create([
            'name'           => $master->nama ?? ($user->name ?? 'User'),
            'academic_title' => $master->academic_title ?? null,
            'gender'         => $extractedGender ?? 'male',
            'address'        => '-',
            'phone_number'   => '-',
        ]);
    }
}
