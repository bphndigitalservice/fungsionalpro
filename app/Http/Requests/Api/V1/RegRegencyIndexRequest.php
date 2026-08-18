<?php

namespace App\Http\Requests\Api\V1;

use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Foundation\Http\FormRequest;

#[QueryParameter('province_id', description: 'Filter kabupaten/kota by `reg_provinces.id`.', type: 'int', example: 11)]
class RegRegencyIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'province_id' => ['sometimes', 'integer', 'exists:reg_provinces,id'],
        ];
    }
}
