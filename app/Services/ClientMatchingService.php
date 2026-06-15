<?php

namespace App\Services;

use App\Models\MasterJf;
use App\Models\Client;
use App\Models\CRole;
use App\Models\CRoleLevel;
use App\Models\RegGrade;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;

class ClientMatchingService
{
    public function findMasterByNip(string $nip): ?MasterJf
    {
        $master = MasterJf::where('nip', $nip)->first();

        if ($master && !empty($master->nama)) {
            $master->clean_name = $master->nama;
            $master->academic_title = null;

            if (str_contains($master->nama, ',')) {
                $parts = explode(',', $master->nama, 2);
                $master->clean_name = trim($parts[0]);   
                $master->academic_title = trim($parts[1]);
            }
        }

        return $master;
    }

    public function applyMasterData(Client $client, MasterJf $master): void
    {
        $rawJabatan = $master->jabatan ?? '';

        $role = CRole::get()->first(function ($cRole) use ($rawJabatan) {
            return stripos($rawJabatan, $cRole->role_name) !== false;
        });

        if ($role) {
            $client->c_role_id = $role->id;
            
            $level = CRoleLevel::where('c_role_id', $role->id)
                ->get()
                ->first(function ($cLevel) use ($rawJabatan) {
                    return stripos($rawJabatan, $cLevel->level) !== false;
                });
                
            $client->c_role_level_id = $level ? $level->id : 1; 

        if (!empty($master->gol_ruang)) {
            $rawGolongan = $master->gol_ruang;

            $grade = RegGrade::get()->first(function ($g) use ($rawGolongan) {
                $matchCode = !empty($g->grade_code) && stripos($rawGolongan, $g->grade_code) !== false;
                
                $matchName = !empty($g->grade_name) && stripos($rawGolongan, $g->grade_name) !== false;

                return $matchCode || $matchName;
            });

            if ($grade) {
                $client->reg_grade_id = $grade->id;
            }
        }

        $rawInstansi = strtolower($master->instansi ?? '');
        $agencyType = 'central';
        $agencyModel = RegDepartment::class;

        if (str_contains($rawInstansi, 'provinsi') || str_contains($rawInstansi, 'prov.')) {
            $agencyType = 'local_province';
            $agencyModel = RegProvince::class;
        } elseif (str_contains($rawInstansi, 'kabupaten') || str_contains($rawInstansi, 'kota') || str_contains($rawInstansi, 'kab.')) {
            $agencyType = 'local_regency';
            $agencyModel = RegRegency::class;
        }

        $client->type = $agencyType;
        $client->agency_type = $agencyModel;

        $lookupName = $master->unit_kerja ?? $master->instansi;
        if ($lookupName) {
            $agency = $agencyModel::where('name', 'LIKE', "%{$lookupName}%")->first();
            if ($agency) {
                $client->agency_id = $agency->id;
            }
        }

        $rawStatus = strtolower($master->status ?? '');
        $client->status = match(true) {
            str_contains($rawStatus, 'aktif') || str_contains($rawStatus, 'active') => 'active',
            str_contains($rawStatus, 'undur') || str_contains($rawStatus, 'resign') => 'non_active_resign',
            str_contains($rawStatus, 'ctln') => 'non_active_ctln',
            str_contains($rawStatus, 'belajar') => 'non_active_study_leave',
            default => 'active',
        };

        if (!empty($master->pengangkatan)) {
            $rawPengangkatan = strtolower($master->pengangkatan); 

            $client->assignation_type = match (true) {
                str_contains($rawPengangkatan, 'cpns') || str_contains($rawPengangkatan, 'pppk') => 'cpns',
                
                str_contains($rawPengangkatan, 'inpassing') => 'inpassing',
                str_contains($rawPengangkatan, 'pdjl')      => 'pdjl',
                str_contains($rawPengangkatan, 'setara')    => 'penyetaraan', 
                
                default => 'cpns', 
            };
        } else {
            $client->assignation_type = 'cpns'; 
        }
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