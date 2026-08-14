<?php

namespace Tests\Unit\Support;

use App\Enums\ClientCluster;
use App\Support\MasterJfClusterResolver;
use Tests\TestCase;

class MasterJfClusterResolverTest extends TestCase
{
    public function test_it_uses_stored_type_when_present(): void
    {
        [$clusterId, $clusterLabel] = MasterJfClusterResolver::resolveLabels(
            ClientCluster::Central->value,
            'Pemerintah Daerah Provinsi Bali',
            'Biro Hukum',
        );

        $this->assertSame('central', $clusterId);
        $this->assertSame('Kementerian Lembaga', $clusterLabel);
    }

    public function test_it_resolves_pemda_kabupaten_from_instansi_when_type_is_null(): void
    {
        [$clusterId, $clusterLabel] = MasterJfClusterResolver::resolveLabels(
            null,
            'Pemerintah Daerah Kabupaten Tangerang',
            'Sekretariat Daerah',
        );

        $this->assertSame('local_regency', $clusterId);
        $this->assertSame('Pemda - Kabupaten/Kota', $clusterLabel);
    }

    public function test_it_resolves_pemda_provinsi_from_unit_kerja_when_type_is_null(): void
    {
        [$clusterId] = MasterJfClusterResolver::resolveLabels(
            null,
            'PEMERINTAH DAERAH',
            'Provinsi Bali',
        );

        $this->assertSame('local_province', $clusterId);
    }
}
