<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin array<string, mixed> */
class MasterJfGroupResource extends JsonResource
{
    /**
     * @return array{
     *     jf_type_id: int,
     *     jf_label: string,
     *     cluster_id: string,
     *     cluster_label: string,
     *     aggregate: MasterJfSliceAggregateResource,
     *     data: \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'jf_type_id' => (int) $this->resource['jf_type_id'],
            'jf_label' => (string) $this->resource['jf_label'],
            'cluster_id' => (string) $this->resource['cluster_id'],
            'cluster_label' => (string) $this->resource['cluster_label'],
            'aggregate' => new MasterJfSliceAggregateResource($this->resource['aggregate']),
            'data' => MasterJfInstansiItemResource::collection(
                collect($this->resource['data'] ?? []),
            ),
        ];
    }
}
