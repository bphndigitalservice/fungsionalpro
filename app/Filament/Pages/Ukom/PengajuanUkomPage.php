<?php

namespace App\Filament\Pages\Ukom;

use App\Enums\ClientCluster;
use App\Enums\UkomApplicationStatus;
use App\Enums\UkomDocumentPack;
use App\Models\CRole;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;
use App\Models\UkomApplication;
use App\Models\UkomDocumentType;
use App\Services\UkomApplicationAccess;
use App\Services\UkomApplicationService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class PengajuanUkomPage extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithFormActions;

    protected static string $view = 'filament.pages.pengajuan-ukom';

    protected static ?int $navigationSort = 5;

    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(app(UkomApplicationAccess::class)->canSubmit(), 403);

        if (! UkomApplication::clientMaySubmit()) {
            Notification::make()
                ->warning()
                ->title(__('labels.page.pengajuan_ukom.verify_required'))
                ->persistent()
                ->send();
        }

        $application = $this->currentApplication();
        $snapshot = UkomApplication::snapshotFromUser(auth()->user());
        $documents = [];

        if ($application) {
            foreach ($application->documents as $document) {
                $documents[$document->ukom_document_type_id] = $document->file_path;
            }
        }

        $this->form->fill([
            'nip' => $application?->nip ?? $snapshot['nip'],
            'nama' => $application?->nama ?? $snapshot['nama'],
            'current_jabatan' => $application?->current_jabatan
                ?? (auth()->user()->isActiveCalonJf()
                    ? null
                    : auth()->user()->client?->crole?->role_name),
            'target_c_role_id' => $application?->target_c_role_id,
            'type' => $application?->type?->value ?? (auth()->user()->isActiveCalonJf() ? null : $snapshot['type']?->value ?? $snapshot['type']),
            'agency_id' => $application?->agency_id ?? (auth()->user()->isActiveCalonJf() ? null : $snapshot['agency_id']),
            'claims_new_degree' => $application?->claims_new_degree ?? false,
            'status' => $application?->status?->value,
            'rejection_reason' => $application?->rejection_reason,
            'documents' => $documents,
        ]);
    }

    public static function getNavigationLabel(): string
    {
        return __('labels.page.pengajuan_ukom.nav');
    }

    public function getTitle(): string|Htmlable
    {
        return __('labels.page.pengajuan_ukom.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('labels.nav.ukom');
    }

    public static function getRoutePath(): string
    {
        return '/pengajuan-ukom';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return app(UkomApplicationAccess::class)->canSubmit();
    }

    public static function canAccess(): bool
    {
        return app(UkomApplicationAccess::class)->canSubmit();
    }

    public function form(Form $form): Form
    {
        $locked = $this->formIsLocked();

        return $form
            ->schema([
                Forms\Components\Placeholder::make('verify_notice')
                    ->label('')
                    ->content(new HtmlString(
                        '<div class="fi-fo-field-wrp-helper-text text-warning-600 font-medium">'
                        .e(__('labels.page.pengajuan_ukom.verify_required'))
                        .'</div>'
                    ))
                    ->visible(fn () => ! UkomApplication::clientMaySubmit()),

                Forms\Components\Placeholder::make('rejected_notice')
                    ->label('')
                    ->content(fn () => new HtmlString(
                        '<div class="text-danger-600 font-medium">'
                        .e('Pengajuan ditolak. Alasan: '.($this->data['rejection_reason'] ?? '-'))
                        .'</div>'
                    ))
                    ->visible(fn () => ($this->currentApplication()?->status === UkomApplicationStatus::Rejected)
                        || (($this->data['status'] ?? null) === UkomApplicationStatus::Rejected->value)),

                Forms\Components\Section::make('Identitas')
                    ->schema([
                        Forms\Components\TextInput::make('nip')
                            ->label('NIP')
                            ->disabled(),
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama')
                            ->disabled(),
                        Forms\Components\TextInput::make('current_jabatan')
                            ->label('Jabatan Saat Ini')
                            ->hint('Contoh: Arsiparis, Pustakawan, Pranata Humas, dsb')
                            ->maxLength(255)
                            ->required()
                            ->disabled(fn () => $locked || ! auth()->user()?->isActiveCalonJf())
                            ->dehydrated()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('target_c_role_id')
                            ->label('Daftar Sebagai')
                            ->options(fn () => CRole::query()->where('active', true)->orderBy('role_name')->pluck('role_name', 'id'))
                            ->required()
                            ->live()
                            ->disabled($locked),
                        Forms\Components\Select::make('type')
                            ->label('Tingkat Instansi')
                            ->options([
                                ClientCluster::Central->value => 'Pusat',
                                ClientCluster::LocalProvince->value => 'Provinsi',
                                ClientCluster::LocalRegency->value => 'Kab/Kota',
                            ])
                            ->live()
                            ->required(fn () => auth()->user()?->isActiveCalonJf())
                            ->visible(fn () => auth()->user()?->isActiveCalonJf())
                            ->disabled($locked),
                        Forms\Components\Select::make('agency_id')
                            ->label('Instansi')
                            ->searchable()
                            ->required(fn () => auth()->user()?->isActiveCalonJf())
                            ->visible(fn () => auth()->user()?->isActiveCalonJf())
                            ->options(function (Get $get) {
                                return match ($get('type')) {
                                    ClientCluster::Central->value => RegDepartment::query()->pluck('name', 'id'),
                                    ClientCluster::LocalProvince->value => RegProvince::query()->pluck('name', 'id'),
                                    ClientCluster::LocalRegency->value => RegRegency::query()->pluck('name', 'id'),
                                    default => [],
                                };
                            })
                            ->disabled($locked),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Persyaratan')
                    ->schema($this->documentFields($locked)),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('submit')
                ->label(__('labels.page.pengajuan_ukom.submit'))
                ->disabled(fn () => $this->formIsLocked())
                ->requiresConfirmation()
                ->modalDescription(__('labels.page.pengajuan_ukom.submit_confirm'))
                ->modalSubmitActionLabel(__('labels.page.pengajuan_ukom.submit'))
                ->action(fn () => $this->submit()),
        ];
    }

    public function submit(): void
    {
        $state = $this->form->getState();
        $documents = $state['documents'] ?? [];

        try {
            app(UkomApplicationService::class)->submit(auth()->user(), $state, $documents);
            Notification::make()->success()->title('Pengajuan Ukom dikirim.')->send();
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title(collect($exception->errors())->flatten()->first() ?? 'Validasi gagal')
                ->send();

            throw $exception;
        }

        $this->mount();
    }

    private function formIsLocked(): bool
    {
        if (! UkomApplication::clientMaySubmit()) {
            return true;
        }

        $application = $this->currentApplication();

        return $application !== null
            && in_array($application->status, [
                UkomApplicationStatus::PendingInstansi,
                UkomApplicationStatus::PendingAdmin,
            ], true);
    }

    private function currentApplication(): ?UkomApplication
    {
        return UkomApplication::query()
            ->with('documents')
            ->where('user_id', auth()->id())
            ->open()
            ->first();
    }

    /**
     * @return array<int, Forms\Components\Component>
     */
    private function documentFields(bool $locked): array
    {
        $user = auth()->user();
        $pack = $user?->isActiveClient()
            ? UkomDocumentPack::Client
            : UkomDocumentPack::CalonJf;

        $fields = [];

        foreach (UkomDocumentType::query()->where('pack', $pack)->orderBy('sort')->get() as $type) {
            if ($type->slug === 'bkn_gelar') {
                $fields[] = Forms\Components\Toggle::make('claims_new_degree')
                    ->label(fn () => auth()->user()?->isActiveCalonJf()
                        ? 'Ada peningkatan pendidikan (pencantuman gelar BKN)'
                        : 'Klaim ijazah baru (pencantuman gelar BKN)')
                    ->live()
                    ->disabled($locked)
                    ->columnSpanFull();
            }

            $upload = $this->documentUpload($type, $locked);

            if ($type->slug === 'bkn_gelar') {
                $upload->visible(fn (Get $get) => (bool) $get('claims_new_degree'));
            }

            $fields[] = $upload;
        }

        return $fields;
    }

    private function documentUpload(UkomDocumentType $type, bool $locked): Forms\Components\FileUpload
    {
        return Forms\Components\FileUpload::make('documents.'.$type->id)
            ->label(function (Get $get) use ($type) {
                $targetName = CRole::query()->find($get('target_c_role_id'))?->role_name ?? 'target';

                return str_replace('[target]', $targetName, $type->label);
            })
            ->disk('s3')
            ->directory('ukom')
            ->visibility(config('fungsional-pro.s3.visibility'))
            ->acceptedFileTypes(config('fungsional-pro.accepted_document_type'))
            ->maxSize((int) config('fungsional-pro.max_upload_file_size'))
            ->maxFiles(1)
            ->downloadable()
            ->openable()
            ->previewable()
            ->deletable(! $locked)
            ->required(function (Get $get) use ($type) {
                return $type->isRequiredFor((bool) $get('claims_new_degree'));
            })
            ->disabled($locked)
            ->extraAttributes([
                'onclick' => "const links = this.querySelectorAll('a'); links.forEach(link => link.setAttribute('target', '_blank'));",
                'onmouseover' => "const links = this.querySelectorAll('a'); links.forEach(link => link.setAttribute('target', '_blank'));",
            ]);
    }
}
