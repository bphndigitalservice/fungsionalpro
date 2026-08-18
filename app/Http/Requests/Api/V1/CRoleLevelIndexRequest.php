<?php

namespace App\Http\Requests\Api\V1;

use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Foundation\Http\FormRequest;

#[QueryParameter('c_role_id', description: 'Filter jenjang by `c_roles.id`.', type: 'int', example: 1)]
class CRoleLevelIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'c_role_id' => ['sometimes', 'integer', 'exists:c_roles,id'],
        ];
    }
}
