<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin array<string, mixed> */
class MasterJfGroupResource extends JsonResource
{
    /**
     * @return array{
     *     c_role_id: int,
     *     c_role_label: string,
     *     cluster: string,
     *     cluster_label: string,
     *     aggregate: MasterJfSliceAggregateResource,
     *     data: \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'c_role_id' => (int) $this->resource['c_role_id'],
            'c_role_label' => (string) $this->resource['c_role_label'],
            'cluster' => (string) $this->resource['cluster'],
            'cluster_label' => (string) $this->resource['cluster_label'],
            'aggregate' => new MasterJfSliceAggregateResource($this->resource['aggregate']),
            'data' => MasterJfInstansiItemResource::collection(
                collect($this->resource['data'] ?? []),
            ),
        ];
    }
}
