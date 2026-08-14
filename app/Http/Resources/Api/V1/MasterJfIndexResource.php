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
            'total_filtered' => $this->resource['total_filtered'],
            'page' => $this->resource['page'],
            'per_page' => $this->resource['per_page'],
            'total_pages' => $this->resource['total_pages'],
            'agregasi' => $this->resource['agregasi'],
            'data' => MasterJfItemResource::collection($this->resource['items']),
        ];
    }
}
