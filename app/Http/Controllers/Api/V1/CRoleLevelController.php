<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CRoleLevelIndexRequest;
use App\Http\Resources\Api\V1\CRoleLevelResource;
use App\Models\CRoleLevel;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Referensi')]
class CRoleLevelController extends Controller
{
    #[Endpoint(
        operationId: 'cRoleLevels.index',
        title: 'List jenjang',
        description: <<<'DESC'
Returns jabatan fungsional levels (jenjang) ordered by id.

Optional filter: `c_role_id` (`c_roles.id`).

Use `id` as `c_role_level_id` or `name` as `jenjang` on Master JF.

Authenticate with header `X-Api-Key`.
DESC,
    )]
    #[Response(status: 200, description: 'Jenjang list.', examples: [[
        'data' => [
            ['id' => 1, 'c_role_id' => 1, 'name' => 'Ahli Pertama'],
        ],
    ]])]
    #[Response(401, 'Unauthorized — missing or invalid X-Api-Key', type: 'array{message: string}')]
    public function index(CRoleLevelIndexRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $query = CRoleLevel::query()->orderBy('id');

        if (isset($filters['c_role_id'])) {
            $query->where('c_role_id', $filters['c_role_id']);
        }

        return CRoleLevelResource::collection($query->get(['id', 'c_role_id', 'level']));
    }
}
