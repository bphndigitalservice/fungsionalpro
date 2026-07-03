<?php

namespace App\Imports;

use App\Models\MasterJf;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Exception;

class MasterJfImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row['nip'])) {
            throw new Exception(
                'Import gagal: terdapat data tanpa NIP atau baris kosong. Periksa kembali file.' . ($row['nama'] ?? '-')
            );
        }

        $instansi = $row['instansi'] ?? null;
        $unitKerja = $row['unit_kerjakanwil'] ?? null;
        [$type, $model] = \App\Services\ClientMatchingService::determineAgencyInfo($instansi ?? '', $unitKerja ?? '');

        return MasterJf::updateOrCreate(
            [
                'nip' => $row['nip'],
            ],
            [
                'nama'               => $row['nama'] ?? null,
                'gol_ruang'          => $row['golruang'] ?? null,
                'jabatan'            => $row['jabatan'] ?? null,
                'unit_kerja'         => $unitKerja,
                'instansi'           => $instansi,
                'pengangkatan'       => $row['pengangkatan'] ?? null,
                'status'             => $row['status'] ?? null,
                'status_kepegawaian' => $row['status_kepegawaian'] ?? null,
                'type'               => $type,
            ]
        );
    }
}
