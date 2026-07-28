<?php

namespace Tests\Unit\Support;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;
use App\Support\MasterJfEnumMapper;
use PHPUnit\Framework\TestCase;

class MasterJfEnumMapperTest extends TestCase
{
    public function test_status_maps_indonesian_labels_and_values(): void
    {
        $this->assertSame(ClientStatus::Active->value, MasterJfEnumMapper::status('Aktif'));
        $this->assertSame(ClientStatus::NonActive_CTLN->value, MasterJfEnumMapper::status('CTLN'));
        $this->assertSame(ClientStatus::Active->value, MasterJfEnumMapper::status('active'));
        $this->assertNull(MasterJfEnumMapper::status('unknown-status'));
        $this->assertNull(MasterJfEnumMapper::status(null));
        $this->assertNull(MasterJfEnumMapper::status(''));
    }

    public function test_status_maps_all_known_labels(): void
    {
        $this->assertSame('non_active_resign', MasterJfEnumMapper::status('Mengundurkan diri'));
        $this->assertSame('non_active_suspended', MasterJfEnumMapper::status('Diberhentikan Sementara sebagai PNS'));
        $this->assertSame('non_active_study_leave', MasterJfEnumMapper::status('Tugas belajar > 6 Bulan'));
        $this->assertSame('non_active_external_assignment', MasterJfEnumMapper::status('Ditugaskan secara penuh di luar jabatan'));
        $this->assertSame('non_active_doesnt_meet_role_requirement', MasterJfEnumMapper::status('Tidak Memenuhi Persyaratan Jabatan'));
    }

    public function test_type_maps_synonyms_and_nulls_unknown(): void
    {
        $this->assertSame(ClientCluster::Central->value, MasterJfEnumMapper::type('central'));
        $this->assertSame(ClientCluster::Central->value, MasterJfEnumMapper::type('Pusat'));
        $this->assertSame(ClientCluster::Central->value, MasterJfEnumMapper::type('Kementerian Lembaga'));
        $this->assertSame(ClientCluster::LocalProvince->value, MasterJfEnumMapper::type('Provinsi'));
        $this->assertSame(ClientCluster::LocalProvince->value, MasterJfEnumMapper::type('Pemda - Provinsi'));
        $this->assertSame(ClientCluster::LocalRegency->value, MasterJfEnumMapper::type('Kab/Kota'));
        $this->assertSame(ClientCluster::LocalRegency->value, MasterJfEnumMapper::type('Pemda - Kabupaten/Kota'));
        $this->assertNull(MasterJfEnumMapper::type('something-else'));
        $this->assertNull(MasterJfEnumMapper::type(null));
    }

    public function test_status_kepegawaian_maps_known_values(): void
    {
        $this->assertSame(JenisKepegawaian::PNS->value, MasterJfEnumMapper::statusKepegawaian('PNS'));
        $this->assertSame(JenisKepegawaian::PPPK->value, MasterJfEnumMapper::statusKepegawaian('PPPK'));
        $this->assertNull(MasterJfEnumMapper::statusKepegawaian('Honorer'));
        $this->assertNull(MasterJfEnumMapper::statusKepegawaian(null));
    }
}
