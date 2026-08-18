<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CRoleResource;
use App\Models\CRole;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Referensi')]
class CRoleController extends Controller
{
    #[Endpoint(
        operationId: 'cRoles.index',
        title: 'List jabatan fungsional types',
        description: 'Returns active `c_roles` ordered by id. Use `id` as `c_role_id` on Master JF. Authenticate with header `X-Api-Key`.',
    )]
    #[Response(status: 200, description: 'Active JF type list.', examples: [[
        'data' => [
            ['id' => 1, 'name' => 'Analis Hukum'],
            ['id' => 2, 'name' => 'Penyuluh Hukum'],
        ],
    ]])]
    #[Response(401, 'Unauthorized — missing or invalid X-Api-Key', type: 'array{message: string}')]
    public function index(): AnonymousResourceCollection
    {
        return CRoleResource::collection(
            CRole::query()
                ->where('active', true)
                ->orderBy('id')
                ->get(['id', 'role_name']),
        );
    }
}
