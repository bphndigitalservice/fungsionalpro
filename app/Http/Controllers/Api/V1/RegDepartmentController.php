<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\RegDepartmentResource;
use App\Models\RegDepartment;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Referensi')]
class RegDepartmentController extends Controller
{
    #[Endpoint(
        operationId: 'regDepartments.index',
        title: 'List departments (K/L)',
        description: 'Returns all kementerian/lembaga ordered by id. Authenticate with header `X-Api-Key`.',
    )]
    #[Response(status: 200, description: 'Department list.', examples: [[
        'data' => [
            ['id' => 1, 'name' => 'Kementerian Hukum'],
        ],
    ]])]
    #[Response(401, 'Unauthorized — missing or invalid X-Api-Key', type: 'array{message: string}')]
    public function index(): AnonymousResourceCollection
    {
        return RegDepartmentResource::collection(
            RegDepartment::query()->orderBy('id')->get(['id', 'name']),
        );
    }
}
