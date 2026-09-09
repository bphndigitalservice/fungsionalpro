<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CRole */
class CRoleResource extends JsonResource
{
    /**
     * @return array{id: int, name: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->role_name,
        ];
    }
}
