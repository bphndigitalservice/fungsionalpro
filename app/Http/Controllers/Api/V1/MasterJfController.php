<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MasterJfIndexRequest;
use App\Http\Resources\Api\V1\MasterJfIndexResource;
use App\Services\MasterJfAggregateService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;

#[Group('Master JF')]
class MasterJfController extends Controller
{
    public function __construct(private readonly MasterJfAggregateService $service) {}

    #[Endpoint(
        operationId: 'masterJf.index',
        title: 'Master JF aggregations by type and cluster',
        description: <<<'DESC'
Returns grouped aggregations of Jabatan Fungsional data for superapps dashboards.

Each group represents one **jenis JF × cluster** combination and includes:
- group-level `aggregate` (card metrics)
- `data[]` instansi list with `agency_type`, `agency_id`, `name`, and `client_count`

**Primary filters (superapps UX):**
- `province_id` / `provinsi` — daerah (hybrid; Pemda only unless `type=central`)
- `c_role_id` — jenis JF (e.g. 1=Analis Hukum, 2=Penyuluh Hukum)
- `type` — effective cluster (`central`, `local_province`, `local_regency`)

When `type=central`, daerah filters are ignored. When daerah is set without `type=central`, K/L rows are excluded.

Authenticate with header `X-Api-Key`.
DESC,
    )]
    #[Response(
        status: 200,
        description: 'Grouped aggregations. `aggregate` exists only on each group/card — not on instansi items.',
        examples: [[
            'data' => [[
                'c_role_id' => 1,
                'c_role_label' => 'Analis Hukum',
                'cluster' => 'local_province',
                'cluster_label' => 'Pemerintah Daerah Provinsi',
                'aggregate' => [
                    'total_jf' => 120,
                    'by_jenjang' => [
                        'Ahli Pertama' => 10,
                        'Ahli Muda' => 25,
                        'Ahli Madya' => 18,
                        'Ahli Utama' => 5,
                        'unknown' => 0,
                    ],
                    'by_status' => ['active' => 30, 'unknown' => 90],
                    'by_status_kepegawaian' => ['PNS' => 80, 'PPPK' => 20, 'unknown' => 20],
                    'by_pengangkatan' => ['Penyetaraan' => 15, 'unknown' => 105],
                ],
                'data' => [
                    [
                        'agency_type' => 'province',
                        'agency_id' => 51,
                        'name' => 'Bali',
                        'client_count' => 45,
                    ],
                    [
                        'agency_type' => null,
                        'agency_id' => null,
                        'name' => 'unknown',
                        'client_count' => 3,
                    ],
                ],
            ]],
        ]],
    )]
    #[Response(401, 'Unauthorized — missing or invalid X-Api-Key', type: 'array{message: string}')]
    public function index(MasterJfIndexRequest $request): MasterJfIndexResource
    {
        $payload = $this->service->aggregate($request->validated());

        return new MasterJfIndexResource($payload);
    }
}
