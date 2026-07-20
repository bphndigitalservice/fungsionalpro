<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\ClientDocumentTypes\Pages\CreateClientDocumentType;
use App\Filament\Resources\ClientDocumentTypes\Pages\ListClientDocumentTypes;
use App\Models\ClientDocumentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithFilament;
use Tests\TestCase;

class ClientDocumentTypeResourceTest extends TestCase
{
    use InteractsWithFilament;
    use RefreshDatabase;

    public function test_super_admin_can_list_document_types(): void
    {
        $user = $this->createSuperAdmin();
        $types = ClientDocumentType::factory()->count(3)->create();

        $this->actingAsFilamentUser($user);

        Livewire::test(ListClientDocumentTypes::class)
            ->assertCanSeeTableRecords($types);
    }

    public function test_super_admin_can_create_document_type(): void
    {
        $user = $this->createSuperAdmin();
        $this->actingAsFilamentUser($user);

        Livewire::test(CreateClientDocumentType::class)
            ->fillForm([
                'type' => 'SK',
                'description' => 'Surat Keputusan',
                'is_required' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(ClientDocumentType::class, [
            'type' => 'SK',
            'description' => 'Surat Keputusan',
        ]);
    }
}
