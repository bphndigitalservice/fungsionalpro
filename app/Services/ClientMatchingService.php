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

            $rawInstansi = $master->instansi ?? '';
            $rawUnitKerja = $master->unit_kerja ?? '';

            [$agencyType, $agencyModel] = self::determineAgencyInfo($rawInstansi, $rawUnitKerja);

            $client->type = $agencyType;
            $client->agency_type = $agencyModel;

            // Need to lookup agency_id
            $cleanUnitKerja = self::cleanAgencyName($rawUnitKerja);
            $cleanInstansi = self::cleanAgencyName($rawInstansi);

            $agency = $agencyModel::where('name', '=', $cleanUnitKerja)->first();
            if (!$agency && $cleanInstansi) {
                $agency = $agencyModel::where('name', '=', $cleanInstansi)->first();
            }
            if (!$agency && $cleanUnitKerja) {
                $agency = $agencyModel::where('name', 'LIKE', "%" . $cleanUnitKerja . "%")->first();
            }
            if (!$agency && $cleanInstansi) {
                $agency = $agencyModel::where('name', 'LIKE', "%" . $cleanInstansi . "%")->first();
            }

            if ($agency) {
                $client->agency_id = $agency->id;
            }

            $rawStatus = strtolower($master->status ?? '');
            $client->status = match (true) {
                str_contains($rawStatus, 'aktif') || str_contains($rawStatus, 'active')
                => \App\Enums\ClientStatus::Active,

                str_contains($rawStatus, 'undur') || str_contains($rawStatus, 'resign')
                => \App\Enums\ClientStatus::NonActive_Resign,

                str_contains($rawStatus, 'sementara') || str_contains($rawStatus, 'suspend') || str_contains($rawStatus, 'skors')
                => \App\Enums\ClientStatus::NonActive_Suspended,

                str_contains($rawStatus, 'ctln')
                => \App\Enums\ClientStatus::NonActive_CTLN,

                str_contains($rawStatus, 'belajar') || str_contains($rawStatus, 'study')
                => \App\Enums\ClientStatus::NonActive_StudyLeave,

                str_contains($rawStatus, 'luar jabatan') || str_contains($rawStatus, 'external')
                => \App\Enums\ClientStatus::NonActive_ExternalAssignment,

                str_contains($rawStatus, 'tidak memenuhi') || str_contains($rawStatus, 'requirement')
                => \App\Enums\ClientStatus::NonActive_DoesntMeetRoleRequirement,

                default => null,
            };

            if (!empty($master->pengangkatan)) {
                $rawPengangkatan = strtolower($master->pengangkatan);

                $client->assignation_type = match (true) {
                    str_contains($rawPengangkatan, 'cpns') || str_contains($rawPengangkatan, 'pppk') || str_contains($rawPengangkatan, 'pertama') => 'cpns',
                    str_contains($rawPengangkatan, 'inpassing') => 'inpassing',
                    str_contains($rawPengangkatan, 'pdjl') => 'pdjl',
                    str_contains($rawPengangkatan, 'penyetaraan') => 'penyetaraan',
                    str_contains($rawPengangkatan, 'promosi') => 'promosi',
                    default => null,
                };
            } else {
                $client->assignation_type = null;
            }
        }
    }

    public static function determineAgencyInfo(string $instansi, string $unitKerja): array
    {
        $rawInstansi = strtolower($instansi);
        $rawUnitKerja = strtolower($unitKerja);

        // 0. "Kantor Wilayah" check
        if (str_contains($rawInstansi, 'kantor wilayah') || str_contains($rawInstansi, 'kanwil') ||
            str_contains($rawUnitKerja, 'kantor wilayah') || str_contains($rawUnitKerja, 'kanwil')) {
            return ['central', RegDepartment::class];
        }

        // 5. "Pemerintah Daerah" check
        if (str_contains($rawInstansi, 'pemerintah daerah')) {
            if (str_contains($rawUnitKerja, 'kabupaten') || str_contains($rawUnitKerja, 'kota')) {
                return ['local_regency', RegRegency::class];
            }
            if (str_contains($rawUnitKerja, 'provinsi')) {
                return ['local_province', RegProvince::class];
            }
        }

        // 1. Kota, Kabupaten check
        if (str_contains($rawInstansi, 'kabupaten') || str_contains($rawInstansi, 'kota') || str_contains($rawInstansi, 'kab.')) {
            return ['local_regency', RegRegency::class];
        }

        // 2. Provinsi check
        if (str_contains($rawInstansi, 'provinsi') || str_contains($rawInstansi, 'prov.')) {
            return ['local_province', RegProvince::class];
        }

        // 3. RegProvince check
        if (!str_contains($rawInstansi, 'kementerian') && !str_contains($rawInstansi, 'badan')) {
            $province = RegProvince::all()->first(fn($p) => str_contains($rawInstansi, strtolower($p->name)));
            if ($province) {
                return ['local_province', RegProvince::class];
            }
        }

        // 4. RegDepartment check
        if (RegDepartment::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($instansi) . '%'])->exists()) {
            return ['central', RegDepartment::class];
        }

        // Default
        return ['central', RegDepartment::class];
    }

    public static function cleanAgencyName(string $name): string
    {
        // Case insensitive regex to remove common prefixes only at the beginning
        $name = preg_replace('/^pemerintah\s+daerah\s+/i', '', $name);
        $name = preg_replace('/^pemerintah\s+/i', '', $name);
        $name = preg_replace('/^pemda\s+/i', '', $name);

        // Remove "provinsi", "prov.", "kabupaten", "kab.", "kota", "kot."
        // Using \.?\s* to handle optional dots and spaces
        return trim(preg_replace('/^(provinsi|prov\.|kabupaten|kab\.|kota|kot\.)\.?\s*/i', '', $name));
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
