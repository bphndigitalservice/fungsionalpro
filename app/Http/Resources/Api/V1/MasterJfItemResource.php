<?php

namespace App\Http\Resources\Api\V1;

use App\Models\MasterJf;
use App\Support\MasterJfDisplay;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MasterJf */
class MasterJfItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'nip' => $this->nip,
            'jabatan' => $this->jabatan,
            'jabatan_fungsional' => MasterJfDisplay::resolveJabatanFungsional($this->resource),
            'jenjang' => MasterJfDisplay::resolveJenjang($this->resource),
            'golongan_ruang' => $this->grade?->grade_name,
            'golongan_ruang_code' => $this->grade?->clean_name,
            'instansi' => $this->instansi,
            'unit_kerja' => $this->unit_kerja,
            'provinsi_id' => $this->province_id,
            'provinsi' => $this->province?->name ?? (is_string($this->provinsi) ? trim($this->provinsi) : null),
            'kluster' => $this->type?->getLabel(),
            'kluster_code' => $this->type?->value,
            'pengangkatan' => $this->pengangkatan,
            'status' => $this->status?->getLabel(),
            'status_code' => $this->status?->value,
            'status_kepegawaian' => $this->status_kepegawaian?->value,
        ];
    }
}
