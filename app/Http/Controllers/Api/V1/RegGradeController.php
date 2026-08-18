<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\RegGradeResource;
use App\Models\RegGrade;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Referensi')]
class RegGradeController extends Controller
{
    #[Endpoint(
        operationId: 'regGrades.index',
        title: 'List golongan/ruang',
        description: 'Returns all `reg_grades` ordered by id. Use `id` as `reg_grade_id` on Master JF. `name` is the display code (`clean_name`). Authenticate with header `X-Api-Key`.',
    )]
    #[Response(status: 200, description: 'Golongan/ruang list.', examples: [[
        'data' => [
            [
                'id' => 13,
                'name' => 'IVa',
                'grade_name' => 'Pembina',
                'grade_code' => 'IVa',
            ],
        ],
    ]])]
    #[Response(401, 'Unauthorized — missing or invalid X-Api-Key', type: 'array{message: string}')]
    public function index(): AnonymousResourceCollection
    {
        return RegGradeResource::collection(
            RegGrade::query()->orderBy('id')->get(['id', 'grade_name', 'grade_code']),
        );
    }
}
