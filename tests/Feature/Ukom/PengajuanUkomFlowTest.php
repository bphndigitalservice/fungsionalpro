<?php

namespace Tests\Feature\Ukom;

use App\Enums\ClientCluster;
use App\Enums\SystemRole;
use App\Enums\UkomApplicationStatus;
use App\Enums\Verified;
use App\Enums\UkomDocumentPack;
use App\Filament\Pages\Ukom\PengajuanUkomPage;
use App\Filament\Pages\Ukom\RiwayatPengajuanUkomPage;
use App\Filament\Resources\UkomApplicationResource;
use App\Filament\Resources\UkomApplicationResource\Pages\ListUkomApplications;
use App\Filament\Resources\UkomApplicationResource\Pages\ViewUkomApplication;
use App\Models\AdminAccess;
use App\Models\CalonJf;
use App\Models\Client;
use App\Models\CRole;
use App\Models\RegDepartment;
use App\Models\UkomApplication;
use App\Models\UkomDocumentType;
use App\Models\User;
use App\Services\UkomApplicationAccess;
use App\Services\UkomApplicationService;
use Database\Seeders\UkomDocumentTypeSeeder;
use Filament\Forms\Components\FileUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PengajuanUkomFlowTest extends TestCase
{
    use RefreshDatabase;

    private CRole $ah;

    private CRole $ph;

    private RegDepartment $department;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate(SystemRole::Client->value, 'web');
        Role::findOrCreate(SystemRole::CalonJf->value, 'web');
        Role::findOrCreate(SystemRole::Admin->value, 'web');
        Role::findOrCreate(SystemRole::AdminInstansi->value, 'web');
        Role::findOrCreate(SystemRole::SuperAdmin->value, 'web');

        $this->ah = CRole::create(['role_name' => 'Analis Hukum', 'active' => true]);
        $this->ph = CRole::create(['role_name' => 'Penyuluh Hukum', 'active' => true]);
        $this->department = RegDepartment::create(['name' => 'Kementerian Hukum']);

        $this->seed(UkomDocumentTypeSeeder::class);
        Storage::fake('s3');
    }

    public function test_calon_jf_can_open_pengajuan_ukom_page(): void
    {
        $this->actingAsCalon();

        $this->assertTrue(PengajuanUkomPage::canAccess());
        $this->assertTrue(PengajuanUkomPage::shouldRegisterNavigation());
        $this->assertTrue(RiwayatPengajuanUkomPage::canAccess());
        $this->assertTrue(RiwayatPengajuanUkomPage::shouldRegisterNavigation());

        Livewire::test(PengajuanUkomPage::class)->assertSuccessful();
    }

    public function test_bkn_gelar_upload_appears_under_ijazah_when_toggled(): void
    {
        $this->actingAsCalon();

        $bknType = UkomDocumentType::query()
            ->where('pack', \App\Enums\UkomDocumentPack::CalonJf)
            ->where('slug', 'bkn_gelar')
            ->first();

        Livewire::test(PengajuanUkomPage::class)
            ->assertSee('Salinan Ijazah Pendidikan Terakhir')
            ->assertSee('Ada peningkatan pendidikan (pencantuman gelar BKN)')
            ->assertFormFieldIsHidden('documents.'.$bknType->id)
            ->set('data.claims_new_degree', true)
            ->assertFormFieldIsVisible('documents.'.$bknType->id);
    }

    public function test_uploaded_documents_can_be_previewed(): void
    {
        $this->actingAsCalon();

        $page = Livewire::test(PengajuanUkomPage::class);
        $uploads = collect($page->instance()->form->getFlatComponents(withHidden: true))
            ->filter(fn ($component) => $component instanceof FileUpload);

        $this->assertNotEmpty($uploads);

        foreach ($uploads as $upload) {
            $this->assertTrue($upload->isOpenable(), $upload->getName());
            $this->assertTrue($upload->isPreviewable(), $upload->getName());
            $this->assertTrue($upload->isDownloadable(), $upload->getName());
        }
    }

    public function test_applicant_sees_pengajuan_progress_in_history_table(): void
    {
        $user = $this->actingAsCalon();

        $application = UkomApplication::factory()->pendingInstansi()->create([
            'user_id' => $user->id,
            'target_c_role_id' => $this->ah->id,
            'type' => ClientCluster::Central,
            'agency_type' => RegDepartment::class,
            'agency_id' => $this->department->id,
        ]);

        Livewire::test(RiwayatPengajuanUkomPage::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$application])
            ->assertSee('Menunggu verifikasi instansi')
            ->assertSee('Instansi pembina')
            ->assertSee('Analis Hukum')
            ->assertSee('Diterima/Ditolak pada (Instansi)')
            ->assertSee('Diterima/Ditolak pada (Instansi pembina)');

        Livewire::test(PengajuanUkomPage::class)
            ->assertSuccessful()
            ->assertDontSee('Riwayat pengajuan');
    }

    public function test_calon_jf_sees_kirim_below_form_without_draft(): void
    {
        $this->actingAsCalon();

        $page = Livewire::test(PengajuanUkomPage::class)
            ->assertSee('Jabatan Saat Ini')
            ->assertSee('Contoh: Arsiparis, Pustakawan, Pranata Humas, dsb')
            ->assertSee('Kirim')
            ->assertDontSee('Simpan Draft');

        $submit = collect($page->instance()->getCachedFormActions())
            ->first(fn ($action) => $action->getName() === 'submit');

        $this->assertNotNull($submit);
        $this->assertSame(
            'Apakah Anda yakin akan mengirim pengajuan? Pengajuan hanya dapat dilakukan 1x hingga pengajuan diterima/ditolak',
            $submit->getModalDescription(),
        );
    }

    public function test_current_jabatan_is_required_on_submit(): void
    {
        $this->actingAsCalon();

        Livewire::test(PengajuanUkomPage::class)
            ->fillForm([
                'target_c_role_id' => $this->ah->id,
            ])
            ->call('submit')
            ->assertHasFormErrors(['current_jabatan']);
    }

    public function test_unverified_client_sees_nav_but_cannot_submit(): void
    {
        $this->actingAsClient(Verified::Unverified);

        $this->assertTrue(PengajuanUkomPage::shouldRegisterNavigation());
        $this->assertFalse(UkomApplication::clientMaySubmit());

        Livewire::test(PengajuanUkomPage::class)
            ->assertSuccessful()
            ->call('submit')
            ->assertHasErrors();
    }

    public function test_verified_client_can_open_pengajuan_without_draft(): void
    {
        $this->actingAsClient(Verified::Verified);

        Livewire::test(PengajuanUkomPage::class)
            ->assertSuccessful()
            ->assertSee('Kirim')
            ->assertDontSee('Simpan Draft');
    }

    public function test_admin_instansi_forwards_and_admin_accept_does_not_create_client(): void
    {
        $calon = $this->makeCalonUser();
        $application = UkomApplication::factory()->pendingInstansi()->create([
            'user_id' => $calon->id,
            'target_c_role_id' => $this->ah->id,
            'type' => ClientCluster::Central,
            'agency_type' => RegDepartment::class,
            'agency_id' => $this->department->id,
        ]);

        $instansi = User::factory()->create();
        $instansi->assignRole(SystemRole::AdminInstansi->value);
        AdminAccess::create([
            'user_id' => $instansi->id,
            'c_role_id' => $this->ah->id,
            'entity_type' => RegDepartment::class,
            'entity_id' => $this->department->id,
        ]);

        $service = app(UkomApplicationService::class);
        $service->forward($instansi, $application->fresh());

        $this->assertSame(UkomApplicationStatus::PendingAdmin, $application->fresh()->status);

        $admin = User::factory()->create();
        $admin->assignRole(SystemRole::Admin->value);
        AdminAccess::create([
            'user_id' => $admin->id,
            'c_role_id' => $this->ah->id,
            'entity_type' => null,
            'entity_id' => null,
        ]);

        $service->accept($admin, $application->fresh());

        $this->assertSame(UkomApplicationStatus::Accepted, $application->fresh()->status);
        $this->assertNull($calon->fresh()->client);
        $this->assertTrue($calon->fresh()->hasSystemRole(SystemRole::CalonJf));
    }

    public function test_admin_instansi_does_not_see_other_instansi(): void
    {
        $otherDept = RegDepartment::create(['name' => 'Instansi Lain']);
        $calon = $this->makeCalonUser();
        $application = UkomApplication::factory()->pendingInstansi()->create([
            'user_id' => $calon->id,
            'target_c_role_id' => $this->ah->id,
            'agency_type' => RegDepartment::class,
            'agency_id' => $otherDept->id,
        ]);

        $instansi = User::factory()->create();
        $instansi->assignRole(SystemRole::AdminInstansi->value);
        AdminAccess::create([
            'user_id' => $instansi->id,
            'c_role_id' => $this->ah->id,
            'entity_type' => RegDepartment::class,
            'entity_id' => $this->department->id,
        ]);

        $this->actingAs($instansi);

        $this->assertFalse(
            app(UkomApplicationAccess::class)
                ->scopedQuery($instansi)
                ->whereKey($application->id)
                ->exists()
        );
    }

    public function test_dual_role_is_treated_as_admin(): void
    {
        $both = User::factory()->create();
        $both->assignRole([SystemRole::Admin->value, SystemRole::AdminInstansi->value]);
        $this->actingAs($both);

        $this->assertTrue(UkomApplicationResource::shouldRegisterNavigation());
        $this->assertFalse(app(UkomApplicationAccess::class)->isInstansiOnly($both));
    }

    public function test_verification_table_shows_instansi_and_submitted_at(): void
    {
        $calon = $this->makeCalonUser();
        $application = UkomApplication::factory()->pendingAdmin()->create([
            'user_id' => $calon->id,
            'target_c_role_id' => $this->ah->id,
            'type' => ClientCluster::Central,
            'agency_type' => RegDepartment::class,
            'agency_id' => $this->department->id,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole(SystemRole::Admin->value);
        $this->actingAs($admin);

        Livewire::test(ListUkomApplications::class)
            ->assertCanSeeTableRecords([$application])
            ->assertSee('Instansi')
            ->assertSee('Diajukan Pada')
            ->assertSee('Diterima/Ditolak pada (Instansi)')
            ->assertSee('Diterima/Ditolak pada (Instansi pembina)')
            ->assertSee('Kementerian Hukum');
    }

    public function test_verification_view_opens_berkas_with_icon(): void
    {
        $calon = $this->makeCalonUser();
        $application = UkomApplication::factory()->pendingAdmin()->create([
            'user_id' => $calon->id,
            'target_c_role_id' => $this->ah->id,
            'type' => ClientCluster::Central,
            'agency_type' => RegDepartment::class,
            'agency_id' => $this->department->id,
        ]);

        Storage::disk('s3')->put('ukom/preview.pdf', 'pdf');
        $application->documents()->create([
            'ukom_document_type_id' => UkomDocumentType::query()->first()->id,
            'file_path' => 'ukom/preview.pdf',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole(SystemRole::Admin->value);
        $this->actingAs($admin);

        Livewire::test(ViewUkomApplication::class, ['record' => $application->getKey()])
            ->assertSuccessful()
            ->assertSee('Berkas')
            ->assertSee('Buka berkas');
    }

    public function test_pembina_scoped_query_hides_undelivered_and_instansi_rejects(): void
    {
        $calon = $this->makeCalonUser();
        $base = [
            'user_id' => $calon->id,
            'target_c_role_id' => $this->ah->id,
            'type' => ClientCluster::Central,
            'agency_type' => RegDepartment::class,
            'agency_id' => $this->department->id,
        ];

        $pendingInstansi = UkomApplication::factory()->pendingInstansi()->create($base);
        $pendingAdmin = UkomApplication::factory()->pendingAdmin()->create($base);
        $instansiReject = UkomApplication::factory()->rejectedAtInstansi()->create($base);
        $accepted = UkomApplication::factory()->acceptedByPembina()->create($base);
        $pembinaReject = UkomApplication::factory()->rejectedByPembina()->create($base);

        $pembina = User::factory()->create();
        $pembina->assignRole(SystemRole::Admin->value);

        $ids = app(UkomApplicationAccess::class)
            ->scopedQuery($pembina)
            ->pluck('id')
            ->all();

        $this->assertNotContains($pendingInstansi->id, $ids);
        $this->assertNotContains($instansiReject->id, $ids);
        $this->assertContains($pendingAdmin->id, $ids);
        $this->assertContains($accepted->id, $ids);
        $this->assertContains($pembinaReject->id, $ids);
    }

    public function test_super_admin_scoped_query_sees_pending_instansi(): void
    {
        $calon = $this->makeCalonUser();
        $pendingInstansi = UkomApplication::factory()->pendingInstansi()->create([
            'user_id' => $calon->id,
            'target_c_role_id' => $this->ah->id,
            'agency_type' => RegDepartment::class,
            'agency_id' => $this->department->id,
        ]);

        $super = User::factory()->create();
        $super->assignRole(SystemRole::SuperAdmin->value);

        $this->assertTrue(
            app(UkomApplicationAccess::class)
                ->scopedQuery($super)
                ->whereKey($pendingInstansi->id)
                ->exists()
        );
    }

    public function test_dual_role_follows_pembina_visibility(): void
    {
        $calon = $this->makeCalonUser();
        $pendingInstansi = UkomApplication::factory()->pendingInstansi()->create([
            'user_id' => $calon->id,
            'target_c_role_id' => $this->ah->id,
            'agency_type' => RegDepartment::class,
            'agency_id' => $this->department->id,
        ]);

        $both = User::factory()->create();
        $both->assignRole([SystemRole::Admin->value, SystemRole::AdminInstansi->value]);
        AdminAccess::create([
            'user_id' => $both->id,
            'c_role_id' => $this->ah->id,
            'entity_type' => RegDepartment::class,
            'entity_id' => $this->department->id,
        ]);

        $this->assertFalse(
            app(UkomApplicationAccess::class)
                ->scopedQuery($both)
                ->whereKey($pendingInstansi->id)
                ->exists()
        );
    }

    public function test_pembina_list_tabs_separate_new_and_processed(): void
    {
        $calon = $this->makeCalonUser();
        $base = [
            'user_id' => $calon->id,
            'target_c_role_id' => $this->ah->id,
            'type' => ClientCluster::Central,
            'agency_type' => RegDepartment::class,
            'agency_id' => $this->department->id,
        ];

        $newRow = UkomApplication::factory()->pendingAdmin()->create($base);
        $processedAccepted = UkomApplication::factory()->acceptedByPembina()->create($base);
        $processedRejected = UkomApplication::factory()->rejectedByPembina()->create($base);
        $hidden = UkomApplication::factory()->pendingInstansi()->create($base);

        $pembina = User::factory()->create();
        $pembina->assignRole(SystemRole::Admin->value);
        $this->actingAs($pembina);

        Livewire::test(ListUkomApplications::class)
            ->assertSet('activeTab', 'new')
            ->assertCanSeeTableRecords([$newRow])
            ->assertCanNotSeeTableRecords([$processedAccepted, $processedRejected, $hidden])
            ->set('activeTab', 'processed')
            ->assertCanSeeTableRecords([$processedAccepted, $processedRejected])
            ->assertCanNotSeeTableRecords([$newRow, $hidden])
            ->set('activeTab', 'all')
            ->assertCanSeeTableRecords([$newRow, $processedAccepted, $processedRejected])
            ->assertCanNotSeeTableRecords([$hidden]);
    }

    public function test_admin_instansi_list_new_tab_shows_pending_instansi_only(): void
    {
        $calon = $this->makeCalonUser();
        $base = [
            'user_id' => $calon->id,
            'target_c_role_id' => $this->ah->id,
            'type' => ClientCluster::Central,
            'agency_type' => RegDepartment::class,
            'agency_id' => $this->department->id,
        ];

        $newRow = UkomApplication::factory()->pendingInstansi()->create($base);
        $forwarded = UkomApplication::factory()->pendingAdmin()->create(array_merge($base, [
            'instansi_reviewed_at' => now(),
        ]));

        $instansi = User::factory()->create();
        $instansi->assignRole(SystemRole::AdminInstansi->value);
        AdminAccess::create([
            'user_id' => $instansi->id,
            'c_role_id' => $this->ah->id,
            'entity_type' => RegDepartment::class,
            'entity_id' => $this->department->id,
        ]);
        $this->actingAs($instansi);

        Livewire::test(ListUkomApplications::class)
            ->assertSet('activeTab', 'new')
            ->assertCanSeeTableRecords([$newRow])
            ->assertCanNotSeeTableRecords([$forwarded])
            ->set('activeTab', 'processed')
            ->assertCanSeeTableRecords([$forwarded])
            ->assertCanNotSeeTableRecords([$newRow]);
    }

    public function test_super_admin_new_tab_includes_pending_instansi_and_pending_admin(): void
    {
        $calon = $this->makeCalonUser();
        $base = [
            'user_id' => $calon->id,
            'target_c_role_id' => $this->ah->id,
            'agency_type' => RegDepartment::class,
            'agency_id' => $this->department->id,
        ];

        $pendingInstansi = UkomApplication::factory()->pendingInstansi()->create($base);
        $pendingAdmin = UkomApplication::factory()->pendingAdmin()->create($base);
        $accepted = UkomApplication::factory()->acceptedByPembina()->create($base);

        $super = User::factory()->create();
        $super->assignRole(SystemRole::SuperAdmin->value);
        $this->actingAs($super);

        Livewire::test(ListUkomApplications::class)
            ->assertSet('activeTab', 'new')
            ->assertCanSeeTableRecords([$pendingInstansi, $pendingAdmin])
            ->assertCanNotSeeTableRecords([$accepted])
            ->set('activeTab', 'processed')
            ->assertCanSeeTableRecords([$accepted])
            ->assertCanNotSeeTableRecords([$pendingInstansi, $pendingAdmin]);
    }

    public function test_verification_filters_by_cluster_instansi_and_status(): void
    {
        $calon = $this->makeCalonUser();
        $otherDept = RegDepartment::create(['name' => 'Instansi Lain']);

        $matchPending = UkomApplication::factory()->pendingAdmin()->create([
            'user_id' => $calon->id,
            'target_c_role_id' => $this->ah->id,
            'type' => ClientCluster::Central,
            'agency_type' => RegDepartment::class,
            'agency_id' => $this->department->id,
        ]);

        $otherAgency = UkomApplication::factory()->pendingAdmin()->create([
            'user_id' => $calon->id,
            'target_c_role_id' => $this->ah->id,
            'type' => ClientCluster::Central,
            'agency_type' => RegDepartment::class,
            'agency_id' => $otherDept->id,
        ]);

        $acceptedSameAgency = UkomApplication::factory()->acceptedByPembina()->create([
            'user_id' => $calon->id,
            'target_c_role_id' => $this->ah->id,
            'type' => ClientCluster::Central,
            'agency_type' => RegDepartment::class,
            'agency_id' => $this->department->id,
        ]);

        $super = User::factory()->create();
        $super->assignRole(SystemRole::SuperAdmin->value);
        $this->actingAs($super);

        Livewire::test(ListUkomApplications::class)
            ->set('activeTab', 'all')
            ->filterTable('agency_filter', [
                'type' => ClientCluster::Central->value,
                'agency_id' => $this->department->id,
            ])
            ->assertCanSeeTableRecords([$matchPending, $acceptedSameAgency])
            ->assertCanNotSeeTableRecords([$otherAgency])
            ->filterTable('status', UkomApplicationStatus::PendingAdmin->value)
            ->assertCanSeeTableRecords([$matchPending])
            ->assertCanNotSeeTableRecords([$acceptedSameAgency, $otherAgency]);
    }

    public function test_verification_status_filter_options_exclude_draft(): void
    {
        $super = User::factory()->create();
        $super->assignRole(SystemRole::SuperAdmin->value);
        $this->actingAs($super);

        $options = UkomApplicationResource::statusFilterOptions($super);

        $this->assertArrayNotHasKey(UkomApplicationStatus::Draft->value, $options);
        $this->assertArrayHasKey(UkomApplicationStatus::PendingInstansi->value, $options);
        $this->assertArrayHasKey(UkomApplicationStatus::Accepted->value, $options);
    }

    public function test_pembina_status_filter_excludes_pending_instansi(): void
    {
        $pembina = User::factory()->create();
        $pembina->assignRole(SystemRole::Admin->value);
        $this->actingAs($pembina);

        $options = UkomApplicationResource::statusFilterOptions($pembina);

        $this->assertArrayNotHasKey(UkomApplicationStatus::Draft->value, $options);
        $this->assertArrayNotHasKey(UkomApplicationStatus::PendingInstansi->value, $options);
        $this->assertArrayHasKey(UkomApplicationStatus::PendingAdmin->value, $options);
        $this->assertArrayHasKey(UkomApplicationStatus::Accepted->value, $options);
    }

    public function test_admin_instansi_status_filter_includes_pending_instansi(): void
    {
        $instansi = User::factory()->create();
        $instansi->assignRole(SystemRole::AdminInstansi->value);
        $this->actingAs($instansi);

        $options = UkomApplicationResource::statusFilterOptions($instansi);

        $this->assertArrayHasKey(UkomApplicationStatus::PendingInstansi->value, $options);
        $this->assertArrayHasKey(UkomApplicationStatus::PendingAdmin->value, $options);
    }

    public function test_export_query_ignores_tabs_but_keeps_filters(): void
    {
        $calon = $this->makeCalonUser();
        $base = [
            'user_id' => $calon->id,
            'target_c_role_id' => $this->ah->id,
            'type' => ClientCluster::Central,
            'agency_type' => RegDepartment::class,
            'agency_id' => $this->department->id,
        ];

        $newRow = UkomApplication::factory()->pendingAdmin()->create($base);
        $processed = UkomApplication::factory()->acceptedByPembina()->create($base);

        $super = User::factory()->create();
        $super->assignRole(SystemRole::SuperAdmin->value);
        $this->actingAs($super);

        $filterData = [
            'agency_filter' => [
                'type' => ClientCluster::Central->value,
                'agency_id' => $this->department->id,
            ],
        ];

        $exportQuery = UkomApplicationResource::applyVerificationFilters(
            UkomApplicationResource::getEloquentQuery(),
            $filterData,
        );

        $ids = $exportQuery->pluck('id')->all();

        $this->assertContains($newRow->id, $ids);
        $this->assertContains($processed->id, $ids);

        $withStatus = UkomApplicationResource::applyVerificationFilters(
            UkomApplicationResource::getEloquentQuery(),
            array_merge($filterData, [
                'status' => ['value' => UkomApplicationStatus::PendingAdmin->value],
            ]),
        )->pluck('id')->all();

        $this->assertContains($newRow->id, $withStatus);
        $this->assertNotContains($processed->id, $withStatus);
    }

    public function test_pembina_export_query_excludes_undelivered(): void
    {
        $calon = $this->makeCalonUser();
        $base = [
            'user_id' => $calon->id,
            'target_c_role_id' => $this->ah->id,
            'type' => ClientCluster::Central,
            'agency_type' => RegDepartment::class,
            'agency_id' => $this->department->id,
        ];

        $pendingInstansi = UkomApplication::factory()->pendingInstansi()->create($base);
        $pendingAdmin = UkomApplication::factory()->pendingAdmin()->create($base);

        $pembina = User::factory()->create();
        $pembina->assignRole(SystemRole::Admin->value);
        $this->actingAs($pembina);

        $ids = UkomApplicationResource::applyVerificationFilters(
            UkomApplicationResource::getEloquentQuery(),
            [],
        )->pluck('id')->all();

        $this->assertNotContains($pendingInstansi->id, $ids);
        $this->assertContains($pendingAdmin->id, $ids);
    }

    public function test_verification_list_has_export_action(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(SystemRole::Admin->value);
        $this->actingAs($admin);

        Livewire::test(ListUkomApplications::class)
            ->assertSuccessful()
            ->assertSee('Ekspor');
    }

    private function actingAsCalon(): User
    {
        $user = $this->makeCalonUser();
        $this->actingAs($user);

        return $user;
    }

    private function makeCalonUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::CalonJf->value);
        CalonJf::factory()->create([
            'user_id' => $user->id,
            'nama' => 'Calon Tes',
        ]);

        return $user->fresh();
    }

    private function actingAsClient(Verified $verified): User
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::Client->value);

        $client = Client::create([
            'user_id' => $user->id,
            'c_role_id' => $this->ah->id,
            'nip' => fake()->unique()->numerify('##################'),
            'type' => ClientCluster::Central,
            'agency_type' => RegDepartment::class,
            'agency_id' => $this->department->id,
        ]);

        $client->forceFill([
            'is_verified' => $verified,
            'verified_at' => $verified === Verified::Verified ? now() : null,
        ])->save();

        $this->actingAs($user->fresh());

        return $user->fresh();
    }
}
