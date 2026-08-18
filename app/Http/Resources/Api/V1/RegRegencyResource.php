<?php

namespace App\Http\Resources\Api\V1;

use App\Models\RegRegency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RegRegency */
class RegRegencyResource extends JsonResource
{
    /**
     * @return array{id: int, province_id: int, name: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'province_id' => (int) $this->province_id,
            'name' => (string) $this->name,
        ];
    }
}
