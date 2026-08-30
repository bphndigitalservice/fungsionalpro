<?php

namespace App\Support;

use App\Enums\ClientCluster;
use App\Services\ClientMatchingService;

final class MasterJfClusterResolver
{
    /** @return array{0: string, 1: string} */
    public static function resolveLabels(mixed $type, ?string $instansi, ?string $unitKerja): array
    {
        $cluster = self::resolve($type, $instansi, $unitKerja);

        return [$cluster->value, (string) $cluster->getLabel()];
    }

    public static function resolve(mixed $type, ?string $instansi, ?string $unitKerja): ClientCluster
    {
        if ($type instanceof ClientCluster) {
            return $type;
        }

        if (is_string($type) && $type !== '') {
            $stored = ClientCluster::tryFrom($type);
            if ($stored !== null) {
                return $stored;
            }
        }

        [$clusterValue] = ClientMatchingService::determineAgencyInfo(
            $instansi ?? '',
            $unitKerja ?? '',
        );

        return ClientCluster::from($clusterValue);
    }
}
