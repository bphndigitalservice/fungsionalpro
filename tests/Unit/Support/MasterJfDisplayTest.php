<?php

namespace Tests\Unit\Support;

use App\Models\CRole;
use App\Models\CRoleLevel;
use App\Models\MasterJf;
use App\Support\MasterJfDisplay;
use Tests\TestCase;

class MasterJfDisplayTest extends TestCase
{
    public function test_it_parses_jenjang_from_jabatan_and_normalizes_case(): void
    {
        $this->assertSame('Ahli Muda', MasterJfDisplay::parseJenjangFromJabatan('Penyuluh Hukum AHLI MUDA'));
    }

    public function test_it_prefers_c_role_level_when_fk_is_set(): void
    {
        $row = new MasterJf([
            'jabatan' => 'Penyuluh Hukum Ahli Pertama',
            'c_role_level_id' => 99,
        ]);
        $row->setRelation('cRoleLevel', new CRoleLevel(['level' => 'Ahli Madya']));

        $this->assertSame('Ahli Madya', MasterJfDisplay::resolveJenjang($row));
    }

    public function test_it_infers_jabatan_fungsional_from_jabatan_text(): void
    {
        $row = new MasterJf(['jabatan' => 'Analis Hukum Ahli Muda']);

        $this->assertSame('Analis Hukum', MasterJfDisplay::resolveJabatanFungsional($row));
    }

    public function test_it_prefers_c_role_relation_for_jabatan_fungsional(): void
    {
        $row = new MasterJf(['jabatan' => 'Analis Hukum Ahli Muda']);
        $row->setRelation('cRole', new CRole(['role_name' => 'Penyuluh Hukum']));

        $this->assertSame('Penyuluh Hukum', MasterJfDisplay::resolveJabatanFungsional($row));
    }
}
