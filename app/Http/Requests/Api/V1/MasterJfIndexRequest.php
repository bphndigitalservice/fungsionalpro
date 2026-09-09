<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

#[QueryParameter('province_id', description: 'Filter daerah via FK `reg_provinces.id`. Applies to Pemda rows only; excludes K/L unless combined with `type=central`.', type: 'int', example: 11)]
#[QueryParameter('provinsi', description: 'Filter daerah via text column. Matches only when `province_id` IS NULL (hybrid fallback). Pemda only.', example: 'BALI')]
#[QueryParameter('c_role_id', description: 'Filter jenis Jabatan Fungsional (`c_roles.id`). Example: 1=Analis Hukum, 2=Penyuluh Hukum.', type: 'int', example: 1)]
#[QueryParameter('type', description: 'Filter effective cluster (resolved from stored type or instansi matching). Values: `central`, `local_province`, `local_regency`. When `central`, daerah filters are ignored.', example: 'local_province')]
#[QueryParameter('search', description: 'Search nama, NIP, instansi, or unit_kerja (LIKE).')]
#[QueryParameter('jenjang', description: 'Filter jenjang parsed from jabatan text (LIKE), e.g. `Ahli Muda`.')]
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
