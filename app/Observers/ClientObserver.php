<?php


namespace App\Observers;

use App\Models\Client;
use App\Services\ClientMatchingService;

class ClientObserver
{
    protected $service;

    public function __construct(ClientMatchingService $service)
    {
        $this->service = $service;
    }

    public function creating(Client $client): void
    {
        $master = $this->service->findMasterByNip($client->nip);

        if ($master) {

            $this->service->applyMasterData($client, $master);
            $client->is_verified = \App\Enums\Verified::Unverified; 
        } else {
            $client->is_verified = \App\Enums\Verified::Unverified;
            
            $client->c_role_level_id = $client->c_role_level_id ?? null; 
            $client->reg_grade_id = $client->reg_grade_id ?? null;      
            $client->status = $client->status ?? null;
            $client->assignation_type = $client->assignation_type ?? null;
        }
    }

    public function created(Client $client): void
    {
        $master = $this->service->findMasterByNip($client->nip);
        
        $extractedGender = $this->service->getGenderFromNip($client->nip);

        $client->identity()->create([
            'name'           => $master->name ?? $client->user->name,
            'academic_title' => $master->academic_title ?? null,
            'gender'         => $master->gender ?? $extractedGender,
            'address'        => $master->address ?? '-', 
            'phone_number'   => $master->phone_number ?? '-',
        ]);
    }
}