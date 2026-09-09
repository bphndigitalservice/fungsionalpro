<?php

namespace Tests\Feature\Filament;

use App\Enums\Acceptance;
use App\Enums\ClientCluster;
use App\Enums\SystemRole;
use App\Livewire\ClientActivityTable;
use App\Models\Client;
use App\Models\ClientActivity;
use App\Models\CRole;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClientActivityTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate(SystemRole::SuperAdmin->value, 'web');

        if (! Schema::hasTable('reg_provinces')) {
            Schema::create('reg_provinces', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('client_activities', 'client_id')) {
            Schema::table('client_activities', function (Blueprint $table) {
                $table->ulid('client_id')->nullable();
                $table->string('title')->nullable();
                $table->date('start_period')->nullable();
                $table->date('end_period')->nullable();
                $table->text('description')->nullable();
                $table->string('activity_file')->nullable();
            });
        }
    }

    public function test_renders_accepted_and_rejected_activity_verification_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::SuperAdmin->value);
        $this->actingAs($user);

        $role = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);

        $client = Client::create([
            'user_id' => $user->id,
            'c_role_id' => $role->id,
            'nip' => fake()->unique()->numerify('##################'),
            'type' => ClientCluster::Central,
            'agency_type' => 'department',
            'agency_id' => 1,
        ]);

        $accepted = ClientActivity::query()->create([
            'client_id' => $client->id,
            'title' => 'Kegiatan Diterima',
            'is_verified' => Acceptance::Accept,
        ]);

        $rejected = ClientActivity::query()->create([
            'client_id' => $client->id,
            'title' => 'Kegiatan Ditolak',
            'is_verified' => Acceptance::Reject,
            'verification_note' => 'Berkas tidak lengkap',
        ]);

        $pending = ClientActivity::query()->create([
            'client_id' => $client->id,
            'title' => 'Kegiatan Pending',
            'is_verified' => null,
        ]);

        Livewire::test(ClientActivityTable::class, ['record' => $client])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$accepted, $rejected, $pending])
            ->assertSee('Sudah Diverifikasi')
            ->assertSee('Ditolak')
            ->assertSee('Belum Diverifikasi');
    }
}
