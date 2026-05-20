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
                'Import gagal: terdapat data tanpa NIP. Nama: ' . ($row['nama'] ?? '-')
            );
        }
        return MasterJf::updateOrCreate(
            [
                'nip' => $row['nip'],
            ],
            [
                'nama' => $row['nama'],
                'gol_ruang' => $row['golruang'],
                'jabatan' => $row['jabatan'],
                'unit_kerja' => $row['unit_kerjakanwil'],
                'instansi' => $row['instansi'],
                'pengangkatan' => $row['pengangkatan'],
                'status' => $row['status'],
            ]
        );
    }
}