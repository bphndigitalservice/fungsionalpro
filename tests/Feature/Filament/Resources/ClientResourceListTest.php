<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages\ListClients;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithFilament;
use Tests\TestCase;

class ClientResourceListTest extends TestCase
{
    use InteractsWithFilament;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('reg_provinces')) {
            Schema::create('reg_provinces', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
            });
        }
    }

    public function test_super_admin_can_render_client_list(): void
    {
        $user = $this->createSuperAdmin();
        $this->actingAsFilamentUser($user);

        Livewire::test(ListClients::class)
            ->assertSuccessful();
    }
}
