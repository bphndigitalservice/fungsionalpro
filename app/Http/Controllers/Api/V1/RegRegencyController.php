<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegRegencyIndexRequest;
use App\Http\Resources\Api\V1\RegRegencyResource;
use App\Models\RegRegency;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Wilayah')]
class RegRegencyController extends Controller
{
    #[Endpoint(
        operationId: 'regRegencies.index',
        title: 'List regencies',
        description: <<<'DESC'
Returns kabupaten/kota ordered by id.

Optional filter: `province_id` (`reg_provinces.id`).

Authenticate with header `X-Api-Key`.
DESC,
    )]
    #[Response(status: 200, description: 'Regency list.', examples: [[
        'data' => [
            ['id' => 1101, 'province_id' => 11, 'name' => 'KABUPATEN SIMEULUE'],
        ],
    ]])]
    #[Response(401, 'Unauthorized — missing or invalid X-Api-Key', type: 'array{message: string}')]
    public function index(RegRegencyIndexRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $query = RegRegency::query()->orderBy('id');

        if (isset($filters['province_id'])) {
            $query->where('province_id', $filters['province_id']);
        }

        return RegRegencyResource::collection($query->get(['id', 'province_id', 'name']));
    }
}
