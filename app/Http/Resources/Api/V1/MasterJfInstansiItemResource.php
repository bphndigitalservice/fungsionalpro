<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin array<string, mixed> */
class MasterJfInstansiItemResource extends JsonResource
{
    /**
     * @return array{name: string, client_count: int}
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => (string) $this->resource['name'],
            'client_count' => (int) $this->resource['client_count'],
        ];
    }
}
