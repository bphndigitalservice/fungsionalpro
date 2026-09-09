<?php

namespace Tests\Unit\Support;

use App\Enums\ClientCluster;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;
use App\Support\MasterJfAgencyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\EnsuresWilayahApiSchema;
use Tests\TestCase;

class MasterJfAgencyResolverTest extends TestCase
{
    use EnsuresWilayahApiSchema;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureWilayahApiSchema();
    }

    public function test_it_resolves_department_by_exact_instansi_name(): void
    {
        $department = RegDepartment::create(['name' => 'Kementerian Hukum']);

        $resolved = MasterJfAgencyResolver::resolve('Kementerian Hukum', '');

        $this->assertNotNull($resolved);
        $this->assertSame(RegDepartment::class, $resolved['agency_type']);
        $this->assertSame($department->id, $resolved['agency_id']);
        $this->assertSame(ClientCluster::Central, $resolved['type']);
        $this->assertArrayNotHasKey('province_id', $resolved);
    }

    public function test_it_resolves_province_and_sets_province_id(): void
    {
        $province = RegProvince::query()->create(['id' => 51, 'name' => 'BALI']);

        $resolved = MasterJfAgencyResolver::resolve('Pemerintah Daerah Provinsi Bali', 'Provinsi Bali');

        $this->assertNotNull($resolved);
        $this->assertSame(RegProvince::class, $resolved['agency_type']);
        $this->assertSame($province->id, $resolved['agency_id']);
        $this->assertSame(ClientCluster::LocalProvince, $resolved['type']);
        $this->assertSame(51, $resolved['province_id']);
    }

    public function test_it_resolves_regency_province_id_from_parent(): void
    {
        RegProvince::query()->create(['id' => 51, 'name' => 'BALI']);
        $regency = RegRegency::query()->create([
            'id' => 5103,
            'province_id' => 51,
            'name' => 'KABUPATEN BADUNG',
        ]);

        $resolved = MasterJfAgencyResolver::resolve(
            'Pemerintah Daerah Kabupaten Badung',
            'Kabupaten Badung',
        );

        $this->assertNotNull($resolved);
        $this->assertSame(RegRegency::class, $resolved['agency_type']);
        $this->assertSame($regency->id, $resolved['agency_id']);
        $this->assertSame(ClientCluster::LocalRegency, $resolved['type']);
        $this->assertSame(51, $resolved['province_id']);
    }

    public function test_it_returns_null_when_no_agency_row_matches(): void
    {
        $this->assertNull(MasterJfAgencyResolver::resolve('Instansi Yang Tidak Ada', 'Unit X'));
    }

    public function test_it_returns_null_when_both_names_empty(): void
    {
        $this->assertNull(MasterJfAgencyResolver::resolve(null, null));
        $this->assertNull(MasterJfAgencyResolver::resolve('', ''));
    }
}
