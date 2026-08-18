<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\RegProvinceResource;
use App\Models\RegProvince;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Wilayah')]
class RegProvinceController extends Controller
{
    #[Endpoint(
        operationId: 'regProvinces.index',
        title: 'List provinces',
        description: 'Returns all provinces ordered by id. Authenticate with header `X-Api-Key`.',
    )]
    #[Response(status: 200, description: 'Province list.', examples: [[
        'data' => [
            ['id' => 11, 'name' => 'ACEH'],
            ['id' => 51, 'name' => 'BALI'],
        ],
    ]])]
    #[Response(401, 'Unauthorized — missing or invalid X-Api-Key', type: 'array{message: string}')]
    public function index(): AnonymousResourceCollection
    {
        return RegProvinceResource::collection(
            RegProvince::query()->orderBy('id')->get(['id', 'name']),
        );
    }
}
