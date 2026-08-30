<?php

namespace Tests\Unit\Support;

use App\Enums\ClientCluster;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;
use App\Support\MasterJfAgencyApiMapper;
use PHPUnit\Framework\TestCase;

class MasterJfAgencyApiMapperTest extends TestCase
{
    public function test_it_maps_known_agency_classes_to_short_types(): void
    {
        $this->assertSame('department', MasterJfAgencyApiMapper::shortType(RegDepartment::class));
        $this->assertSame('province', MasterJfAgencyApiMapper::shortType(RegProvince::class));
        $this->assertSame('regency', MasterJfAgencyApiMapper::shortType(RegRegency::class));
    }

    public function test_it_returns_null_for_unknown_or_empty_agency_type(): void
    {
        $this->assertNull(MasterJfAgencyApiMapper::shortType(null));
        $this->assertNull(MasterJfAgencyApiMapper::shortType(''));
        $this->assertNull(MasterJfAgencyApiMapper::shortType('App\\Models\\Client'));
    }

    public function test_it_maps_cluster_enum_values_to_api_labels(): void
    {
        $this->assertSame(
            'Kementerian Lembaga',
            MasterJfAgencyApiMapper::clusterLabel(ClientCluster::Central->value),
        );
        $this->assertSame(
            'Pemerintah Daerah Provinsi',
            MasterJfAgencyApiMapper::clusterLabel(ClientCluster::LocalProvince->value),
        );
        $this->assertSame(
            'Pemerintah Daerah Kabupaten/Kota',
            MasterJfAgencyApiMapper::clusterLabel(ClientCluster::LocalRegency->value),
        );
    }
}
