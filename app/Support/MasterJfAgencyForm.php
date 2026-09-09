<?php

namespace App\Support;

use App\Enums\ClientCluster;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;

final class MasterJfAgencyForm
{
    /** @param array<string, mixed> $data */
    public static function mutate(array $data): array
    {
        $typeValue = $data['type'] instanceof ClientCluster
            ? $data['type']->value
            : ($data['type'] ?? null);
        $typeValue = $typeValue === '' ? null : $typeValue;

        $agencyId = $data['agency_id'] ?? null;
        if ($agencyId === '') {
            $agencyId = null;
        }

        if ($agencyId === null || $typeValue === null) {
            $data['agency_type'] = null;
            $data['agency_id'] = null;

            return $data;
        }

        $data['agency_id'] = $agencyId;
        $data['agency_type'] = match ($typeValue) {
            ClientCluster::Central->value => RegDepartment::class,
            ClientCluster::LocalProvince->value => RegProvince::class,
            ClientCluster::LocalRegency->value => RegRegency::class,
            default => null,
        };

        if ($data['agency_type'] === null) {
            $data['agency_id'] = null;

            return $data;
        }

        if ($typeValue === ClientCluster::LocalProvince->value) {
            $data['province_id'] = $agencyId;
        } elseif ($typeValue === ClientCluster::LocalRegency->value) {
            $regency = RegRegency::query()->find($agencyId);
            $data['province_id'] = $regency?->province_id;
        }

        return $data;
    }
}
