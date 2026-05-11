<?php

namespace App\Services;

use App\Models\MasterClient;
use App\Models\Client;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;

class ClientMatchingService
{
    public function findMasterByNip(string $nip): ?MasterClient
    {
        return MasterClient::where('nip', $nip)->first();
    }

    public function applyMasterData(Client $client, MasterClient $master): void
    {
        $client->c_role_id = $master->c_role_id;
        $client->c_role_level_id = $master->c_role_level_id;
        $client->reg_grade_id = $master->reg_grade_id;
        $client->agency_id = $master->agency_id;
        
        $client->agency_type = match ($master->type) {
            'central' => RegDepartment::class,
            'local_province' => RegProvince::class,
            'local_regency' => RegRegency::class,
            default => RegDepartment::class, 
        };

        $client->type = $master->type;
        $client->status = $master->status;
        $client->assignation_type = $master->assignation_type;
    }

    public function getGenderFromNip(string $nip): ?string
    {
        if (strlen($nip) < 15) return null;

        $genderDigit = $nip[14];

        return match ($genderDigit) {
            '1' => 'male',
            '2' => 'female',
            default => null,
        };
    }
}