<?php

namespace Tests\Feature\Migrations;

use App\Support\MasterJfEnumMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NormalizeMasterJfEnumColumnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_normalizes_labels_and_nulls_unknown_type(): void
    {
        if (! Schema::hasTable('master_jf')) {
            $this->markTestSkipped('master_jf table missing');
        }

        DB::table('master_jf')->insert([
            [
                'nama' => 'A',
                'nip' => '111111111111111111',
                'status' => 'Aktif',
                'type' => 'Pusat',
                'status_kepegawaian' => 'PNS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'B',
                'nip' => '222222222222222222',
                'status' => 'CTLN',
                'type' => 'weird-legacy-type',
                'status_kepegawaian' => 'Honorer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        MasterJfEnumMapper::normalizeTable();

        $this->assertDatabaseHas('master_jf', [
            'nip' => '111111111111111111',
            'status' => 'active',
            'type' => 'central',
            'status_kepegawaian' => 'PNS',
        ]);

        $rowB = DB::table('master_jf')->where('nip', '222222222222222222')->first();
        $this->assertSame('non_active_ctln', $rowB->status);
        $this->assertNull($rowB->type);
        $this->assertNull($rowB->status_kepegawaian);
    }
}
