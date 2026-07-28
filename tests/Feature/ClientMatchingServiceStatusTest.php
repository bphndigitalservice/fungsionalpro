<?php

namespace Tests\Feature;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\CRole;
use App\Models\MasterJf;
use App\Services\ClientMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class ClientMatchingServiceStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('reg_provinces')) {
            Schema::create('reg_provinces', function (Blueprint $table) {
                $table->integer('id')->primary();
                $table->string('name');
            });
        }
    }

    public function test_apply_master_data_assigns_client_status_enum_directly(): void
    {
        CRole::create([
            'role_name' => 'Analis Hukum',
            'active' => true,
        ]);

        $master = MasterJf::factory()->create([
            'jabatan' => 'Analis Hukum Ahli Pertama',
            'status' => ClientStatus::NonActive_CTLN,
        ]);

        $client = new Client;
        app(ClientMatchingService::class)->applyMasterData($client, $master);

        $this->assertSame(ClientStatus::NonActive_CTLN, $client->status);
    }
}
