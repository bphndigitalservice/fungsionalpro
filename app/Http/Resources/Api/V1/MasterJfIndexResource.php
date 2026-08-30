<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterJfIndexResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'data' => MasterJfGroupResource::collection(
                collect($this->resource['data'] ?? []),
            ),
        ];
    }
}
