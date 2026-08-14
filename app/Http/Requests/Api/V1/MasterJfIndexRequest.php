<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterJfIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'c_role_id' => ['sometimes', 'integer', 'exists:c_roles,id'],
            'c_role_level_id' => ['sometimes', 'integer', 'exists:c_role_levels,id'],
            'jenjang' => ['sometimes', 'string', 'max:255'],
            'reg_grade_id' => ['sometimes', 'integer', 'exists:reg_grades,id'],
            'province_id' => ['sometimes', 'integer', 'exists:reg_provinces,id'],
            'provinsi' => ['sometimes', 'string', 'max:255'],
            'pengangkatan' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(ClientStatus::class)],
            'status_kepegawaian' => ['sometimes', Rule::enum(JenisKepegawaian::class)],
            'type' => ['sometimes', Rule::enum(ClientCluster::class)],
        ];
    }
}
