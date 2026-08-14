<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin array<string, mixed> */
class MasterJfSliceAggregateResource extends JsonResource
{
    /**
     * @return array{
     *     total_jf: int,
     *     by_jenjang: array<string, int>,
     *     by_status: array<string, int>,
     *     by_status_kepegawaian: array<string, int>,
     *     by_pengangkatan: array<string, int>
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'total_jf' => (int) $this->resource['total_jf'],
            'by_jenjang' => $this->resource['by_jenjang'],
            'by_status' => $this->resource['by_status'],
            'by_status_kepegawaian' => $this->resource['by_status_kepegawaian'],
            'by_pengangkatan' => $this->resource['by_pengangkatan'],
        ];
    }
}
