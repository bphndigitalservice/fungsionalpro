<?php

namespace App\Http\Resources\Api\V1;

use App\Models\RegGrade;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RegGrade */
class RegGradeResource extends JsonResource
{
    /**
     * @return array{id: int, name: string, grade_name: string, grade_code: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->clean_name,
            'grade_name' => (string) $this->grade_name,
            'grade_code' => (string) $this->grade_code,
        ];
    }
}
