<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MasterJfIndexRequest;
use App\Http\Resources\Api\V1\MasterJfIndexResource;
use App\Services\MasterJfAggregateService;

class MasterJfController extends Controller
{
    public function __construct(private readonly MasterJfAggregateService $service) {}

    public function index(MasterJfIndexRequest $request): MasterJfIndexResource
    {
        $payload = $this->service->aggregate($request->validated());

        return new MasterJfIndexResource($payload);
    }
}
