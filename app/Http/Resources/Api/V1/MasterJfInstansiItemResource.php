<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin array<string, mixed> */
class MasterJfInstansiItemResource extends JsonResource
{
    /**
     * @return array{agency_type: ?string, agency_id: ?int, name: string, client_count: int}
     */
    public function toArray(Request $request): array
    {
        return [
            'agency_type' => $this->resource['agency_type'],
            'agency_id' => $this->resource['agency_id'] === null
                ? null
                : (int) $this->resource['agency_id'],
            'name' => (string) $this->resource['name'],
            'client_count' => (int) $this->resource['client_count'],
        ];
    }
}
