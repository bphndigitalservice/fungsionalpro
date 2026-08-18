<?php

namespace App\Support;

use App\Enums\ClientCluster;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;
use App\Services\ClientMatchingService;

final class MasterJfAgencyResolver
{
    /**
     * @return array{
     *     agency_type: class-string,
     *     agency_id: int,
     *     type: ClientCluster,
     *     province_id?: int
     * }|null
     */
    public static function resolve(?string $instansi, ?string $unitKerja): ?array
    {
        $instansi = trim((string) $instansi);
        $unitKerja = trim((string) $unitKerja);

        if ($instansi === '' && $unitKerja === '') {
            return null;
        }

        [, $modelClass] = ClientMatchingService::determineAgencyInfo($instansi, $unitKerja);
        $agency = ClientMatchingService::findAgency($modelClass, $instansi, $unitKerja);

        if ($agency === null) {
            return null;
        }

        $type = match ($modelClass) {
            RegDepartment::class => ClientCluster::Central,
            RegProvince::class => ClientCluster::LocalProvince,
            RegRegency::class => ClientCluster::LocalRegency,
            default => null,
        };

        if ($type === null) {
            return null;
        }

        $payload = [
            'agency_type' => $modelClass,
            'agency_id' => (int) $agency->id,
            'type' => $type,
        ];

        if ($agency instanceof RegProvince) {
            $payload['province_id'] = (int) $agency->id;
        }

        if ($agency instanceof RegRegency) {
            $payload['province_id'] = (int) $agency->province_id;
        }

        return $payload;
    }
}
