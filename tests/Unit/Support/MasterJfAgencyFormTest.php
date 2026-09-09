<?php

namespace Tests\Unit\Support;

use App\Enums\ClientCluster;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;
use App\Support\MasterJfAgencyForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\EnsuresWilayahApiSchema;
use Tests\TestCase;

class MasterJfAgencyFormTest extends TestCase
{
    use EnsuresWilayahApiSchema;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureWilayahApiSchema();
    }

    public function test_it_sets_agency_type_from_cluster_when_agency_id_present(): void
    {
        $department = RegDepartment::create(['name' => 'Kementerian Hukum']);

        $data = MasterJfAgencyForm::mutate([
            'type' => ClientCluster::Central->value,
            'agency_id' => $department->id,
        ]);

        $this->assertSame(RegDepartment::class, $data['agency_type']);
        $this->assertSame($department->id, $data['agency_id']);
        $this->assertSame(ClientCluster::Central->value, $data['type']);
        $this->assertArrayNotHasKey('echelon_type', $data);
    }

    public function test_it_sets_province_id_from_regency(): void
    {
        RegProvince::query()->create(['id' => 51, 'name' => 'BALI']);
        $regency = RegRegency::query()->create([
            'id' => 5103,
            'province_id' => 51,
            'name' => 'KABUPATEN BADUNG',
        ]);

        $data = MasterJfAgencyForm::mutate([
            'type' => ClientCluster::LocalRegency,
            'agency_id' => $regency->id,
        ]);

        $this->assertSame(RegRegency::class, $data['agency_type']);
        $this->assertSame(51, $data['province_id']);
    }

    public function test_it_nulls_morph_when_agency_cleared(): void
    {
        $data = MasterJfAgencyForm::mutate([
            'type' => 'central',
            'agency_id' => null,
            'agency_type' => RegDepartment::class,
        ]);

        $this->assertNull($data['agency_type']);
        $this->assertNull($data['agency_id']);
        $this->assertSame('central', $data['type']);
    }
}
