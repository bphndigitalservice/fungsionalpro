<?php

namespace App\Imports;

use App\Models\MasterJf;
use App\Services\ClientMatchingService;
use App\Support\MasterJfAgencyResolver;
use App\Support\MasterJfEnumMapper;
use App\Support\RegGradeResolver;
use Exception;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MasterJfImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty(array_filter($row))) {
            return null;
        }

        if (empty($row['nip'])) {
            throw new Exception(
                'Import gagal: terdapat data tanpa NIP pada baris data. Periksa kembali file. Nama: '.($row['nama'] ?? '-')
            );
        }

        $instansi = $row['instansi'] ?? null;
        $unitKerja = $row['unit_kerja'] ?? $row['unit_kerjakanwil'] ?? null;
        [$type] = ClientMatchingService::determineAgencyInfo($instansi ?? '', $unitKerja ?? '');

        $jabatan = $row['jabatan'] ?? null;
        $cRoleId = null;
        if ($jabatan) {
            if (stripos($jabatan, 'Analis Hukum') !== false) {
                $cRoleId = 1;
            } elseif (stripos($jabatan, 'Penyuluh Hukum') !== false) {
                $cRoleId = 2;
            }
        }

        $resolved = MasterJfAgencyResolver::resolve($instansi, $unitKerja);

        $values = [
            'nama' => $row['nama'] ?? null,
            'reg_grade_id' => RegGradeResolver::resolveId($row['golruang'] ?? null),
            'jabatan' => $jabatan,
            'c_role_id' => $cRoleId,
            'unit_kerja' => $unitKerja,
            'instansi' => $instansi,
            'pengangkatan' => $row['pengangkatan'] ?? null,
            'status' => MasterJfEnumMapper::status($row['status'] ?? null),
            'status_kepegawaian' => MasterJfEnumMapper::statusKepegawaian($row['status_kepegawaian'] ?? null),
            'type' => $type,
            'provinsi' => $row['provinsi'] ?? null,
        ];

        if ($resolved !== null) {
            $values['agency_type'] = $resolved['agency_type'];
            $values['agency_id'] = $resolved['agency_id'];
            $values['type'] = $resolved['type']->value;
            if (array_key_exists('province_id', $resolved)) {
                $values['province_id'] = $resolved['province_id'];
            }
        }

        return MasterJf::updateOrCreate(
            ['nip' => $row['nip']],
            $values,
        );
    }
}
