<?php

namespace App\Support;

use App\Enums\ClientCluster;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;

final class MasterJfAgencyApiMapper
{
    public static function shortType(?string $fqcn): ?string
    {
        return match ($fqcn) {
            RegDepartment::class => 'department',
            RegProvince::class => 'province',
            RegRegency::class => 'regency',
            default => null,
        };
    }

    public static function clusterLabel(string $cluster): string
    {
        return match ($cluster) {
            ClientCluster::Central->value => 'Kementerian Lembaga',
            ClientCluster::LocalProvince->value => 'Pemerintah Daerah Provinsi',
            ClientCluster::LocalRegency->value => 'Pemerintah Daerah Kabupaten/Kota',
            default => $cluster,
        };
    }
}
