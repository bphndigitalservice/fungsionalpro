<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CRoleLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CRoleLevel */
class CRoleLevelResource extends JsonResource
{
    /**
     * @return array{id: int, c_role_id: int, name: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'c_role_id' => (int) $this->c_role_id,
            'name' => (string) $this->level,
        ];
    }
}
