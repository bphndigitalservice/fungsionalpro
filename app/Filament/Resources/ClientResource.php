<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Filament\Resources\ClientResource\Pages\CreateClient;
use App\Filament\Resources\ClientResource\Pages\ViewClient;
use App\Filament\Resources\ClientResource\Pages\EditClient;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DatePicker;
use Exception;
use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\CRoleAssignation;
use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Enums\SystemRole;
use App\Filament\Resources\ClientResource\Pages;
use App\Livewire\ClientEducationInfolist;
use App\Livewire\ClientCompetenceInfolist;
use App\Livewire\ClientActivityInfolist;
use App\Livewire\ClientActivityTable;
use App\Models\Client;
use App\Models\CRole;
use App\Models\RegDepartment;
use App\Models\RegDepartmentEchelon1;
use App\Models\RegProvinceEchelon1;
use App\Models\RegProvince;
use App\Models\RegRegency;
use Filament\Forms;
use Filament\Forms\Components\InfoList;
use Filament\Forms\Components\InfoList\Item;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Exports\ClientExporter;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $modelLabel = "Pejabat Fungsional";

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->schema([
                        Tab::make(__('labels.form.client.tab_info'))
                            ->schema([
                                Section::make()
                                    ->heading(__('labels.form.client.heading.client_identity'))
                                    ->description(__('labels.form.client.heading.client_identity_description'))
                                    ->collapsible()
                                    ->schema([
                                        Group::make()
                                            ->schema(static::getClientIdentityForm())
                                            ->columnSpan(5),
                                    ])->columnSpan(['lg' => fn(?Client $record) => $record === null ? 3 : 2]),
                                Section::make()
                                    ->heading(__('labels.form.client.heading.client_education'))
                                    ->description(__('labels.form.client.heading.client_education_description'))
                                    ->collapsible()
                                    ->schema([
                                        Group::make()
                                            ->schema(static::getClientEducationForm())
                                            ->columnSpan(5),
                                    ])->columnSpan(['lg' => fn(?Client $record) => $record === null ? 3 : 2]),
                                Section::make()
                                    ->heading(__('labels.form.client.heading.client_employee_information'))
                                    ->description(__('labels.form.client.heading.client_employee_information_description'))
                                    ->collapsible()
                                    ->schema([
                                        Group::make()
                                            ->schema(static::getClientBasicInformationForm(fn(Model $record) => $record))
                                            ->columnSpan(5),
                                    ])->columnSpan(['lg' => fn(?Client $record) => $record === null ? 3 : 2]),

                            ]),
                        Tab::make(__('labels.form.client.tab_file'))
                            ->schema(static::getDetailedClientForm()),
                        Tab::make('Riwayat Pendidikan')
                            ->visible(fn (?Client $record) => $record?->identity?->photo !== null)
                            ->schema([
                                Livewire::make(ClientEducationInfolist::class)
                            ]),
                        Tab::make('Riwayat Diklat/Pelatihan')
                            ->visible(fn (?Client $record) => $record?->identity?->photo !== null)
                            ->schema([
                                Livewire::make(ClientCompetenceInfolist::class)
                            ]),
                        Tab::make('Riwayat Kegiatan')
                            ->visible(fn (?Client $record) => $record?->identity?->photo !== null)
                            ->schema([
                                Livewire::make(ClientActivityTable::class)
                            ]),
                        Tab::make(__('labels.form.client.tab_user'))
                            ->visible(fn () => auth()->user()->hasSystemRole(SystemRole::SuperAdmin))
                            ->schema([
                                Select::make('user_id')
                                    ->searchable()
                                    ->required()
                                    ->relationship('user', 'name'),
                            ]),
                    ])->columnSpan(5),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('labels.table.client.id')),
                TextColumn::make('nip')
                    ->label(__('labels.table.client.nip'))
                    ->searchable(),
                TextColumn::make('identity.name')
                    ->label(__('labels.table.client.name'))
                    ->searchable(),
                TextColumn::make('regProvince.name')
                    ->label('Provinsi')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('crole.role_name')
                    ->label(__('labels.table.client.role'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('croleLevel.level')
                    ->label(__('labels.table.client.grade'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('point.point')
                    ->label(__('labels.table.client.point'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('labels.table.client.cluster'))
                    ->searchable(),
                TextColumn::make('agenciable.name')
                    ->label(__('Instansi'))
                    ->searchable()->sortable(),
                TextColumn::make('echelonable.name')
                    ->label(__('labels.table.client.echelon'))
                    ->state(function (Client $record) {
                        return match ($record->type) {
                            ClientCluster::LocalRegency => $record->echelon_x_text,
                            ClientCluster::LocalProvince => is_numeric($record->echelon_x_text)
                                ? (once(fn () => RegProvinceEchelon1::query()->pluck('name', 'id'))[$record->echelon_x_text] ?? null)
                                : null,
                            default => $record->echelonable?->name,
                        };
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('labels.table.client.status'))
                    ->wrap()
                    ->searchable(),
                TextColumn::make('assignation_type')
                    ->label(__('labels.table.client.assignation_type'))
                    ->searchable(),
                TextColumn::make('jenis_kepegawaian')
                    ->label('Jenis Kepegawaian')
                    ->searchable(),
            ])
            ->filters([
                Filter::make('agency_filter')
                    ->hidden(!static::canFilterRegional())
                    ->schema([
                        Select::make('type')
                            ->label('Tingkat Instansi')
                            ->options([
                                'central' => 'Pusat',
                                'local_province' => 'Provinsi',
                                'local_regency' => 'Kab/Kota',
                            ])
                            ->live(),
                        Select::make('agency_id')
                            ->label('Instansi')
                            ->options(function (Get $get) {
                                $type = $get('type');
                                return match ($type) {
                                    'central' => RegDepartment::query()->pluck('name', 'id'),
                                    'local_province' => RegProvince::query()->pluck('name', 'id'),
                                    'local_regency' => RegRegency::query()->pluck('name', 'id'),
                                    default => [],
                                };
                            })
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['type'], fn (Builder $query, $type) => $query->where('type', $type))
                            ->when($data['agency_id'], fn (Builder $query, $id) => $query->where('agency_id', $id));
                    }),
                SelectFilter::make('status')
                    ->options(ClientStatus::class),
                SelectFilter::make('c_role_id')
                    ->hidden(!static::canFilterClientRoles())
                    ->label(__('labels.table.client.role'))
                    ->options(fn () => CRole::query()->pluck('role_name', 'id')->toArray())
                    ->searchable()
                    ->multiple(),
                SelectFilter::make('assignation_type')
                    ->options(CRoleAssignation::class),
                SelectFilter::make('reg_province_id')
                    ->label('Provinsi')
                    ->options(fn () => RegProvince::query()->pluck('name', 'id')->toArray())
                    ->searchable(),
            ], layout: FiltersLayout::AboveContent)
            ->headerActions([
                ExportAction::make()
                    ->exporter(ClientExporter::class)
                    ->color('success')
                    ->button()
                    ->icon('heroicon-m-arrow-down-tray'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // 3. Add bulk action to download selected data rows
                    ExportBulkAction::make()
                        ->exporter(ClientExporter::class)
                        ->label('Ekspor Pejabat Terpilih'),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canFilterRegional(): bool
    {
        return auth()->user()->hasAnySystemRole(SystemRole::SuperAdmin, SystemRole::AdminPusat);
    }

    public static function canFilterClientRoles(): bool
    {
        return auth()->user()->hasAnySystemRole(SystemRole::SuperAdmin, SystemRole::AdminSdmBphn);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'view' => ViewClient::route('/{record}'),
            'edit' => EditClient::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): string
    {
        return __('labels.nav.client_management');
    }

    public static function getClientBasicInformationForm(?callable $callback): array
    {
        return [
            Group::make()
                ->schema([
                    TextInput::make('nip')
                        ->label(__('labels.form.client.fields.nip'))
                        ->unique(table: Client::class, ignorable: $callback)
                        ->required()
                        ->placeholder('19930101XXXXXXXXX')
                        ->minLength(18)
                        ->maxLength(18)
                        ->rules('regex:/^\d+$/'),

                    Select::make('reg_grade_id')
                        ->label(__('labels.form.client.fields.grade'))
                        ->relationship('grade', 'grade_code')
                        ->required(),
                ])->columns(2),

            Group::make()
                ->schema([
                    Select::make('c_role_id')
                        ->label(__('labels.form.client.fields.crole_name'))
                        ->live()
                        ->relationship('crole', 'role_name', function (Builder $query) {
                            $query->where('active', true);
                        })
                        ->required(),

                    Select::make('c_role_level_id')
                        ->label(__('labels.form.client.fields.crole_grade'))
                        ->relationship(
                            'croleLevel',
                            'level',
                            fn (Builder $query, Get $get) =>
                                $query->where('c_role_id', $get('c_role_id') ?: 0)
                        )
                        ->required(),
                ])->columns(2),

            Group::make()
                ->schema([
                    Select::make('type')
                        ->label(__('labels.form.client.fields.client_cluster'))
                        ->options([
                            'central' => 'Pusat',
                            'local_province' => 'Provinsi',
                            'local_regency' => 'Kab/Kota',
                        ])
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set) {
                            $set('agency_id', null);
                            $set('echelon_id', null);
                            $set('echelon_x_text', null);
                            $set('reg_province_id', null);

                            if ($state === 'central') {
                                $set('echelon_type', RegDepartmentEchelon1::class);
                            } elseif ($state === 'local_province') {
                                $set('echelon_type', RegProvince::class);
                            } elseif ($state === 'local_regency') {
                                $set('echelon_type', RegRegency::class);
                            }
                        })
                        ->required(),

                    Select::make('status')
                        ->label(__('labels.form.client.fields.status'))
                        ->options(ClientStatus::class)
                        ->required(),
                ])->columns(2),

            Group::make()
                ->schema([
                    Select::make('assignation_type')
                        ->options(CRoleAssignation::class)
                        ->label(__('labels.form.client.fields.assignation_type'))
                        ->required()
                        ->live() // Makes the component reactive when changed
                        ->afterStateUpdated(function (?string $state, Set $set) {
                            if (blank($state)) {
                                $set('jenis_kepegawaian', null);
                            } elseif ($state !== 'cpns') {
                                $set('jenis_kepegawaian', 'PNS');
                            }
                        }),

                    Select::make('jenis_kepegawaian')
                        ->label('Jenis Kepegawaian')
                        ->options([
                            'PNS' => 'PNS',
                            'PPPK' => 'PPPK',
                        ])
                        ->required()
                        ->hidden(fn (Get $get) => blank($get('assignation_type'))),
                ])->columns(2),

            Group::make()
                ->schema([
                    Select::make('agency_id')
                        ->label(__('labels.form.client.fields.agency'))
                        ->live()
                        ->searchable()
                        ->options(function (Get $get) {
                            return match ($get('type')) {
                                'central' => RegDepartment::query()->pluck('name', 'id'),
                                'local_province' => RegProvince::query()->pluck('name', 'id'),
                                'local_regency' => RegRegency::query()->pluck('name', 'id'),
                                default => [],
                            };
                        })
                        ->required()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $type = $get('type');
                            $agencyId = $get('agency_id');

                            if ($type === 'local_province') {
                                 $set('reg_province_id', $agencyId);
                            } elseif ($type === 'local_regency') {
                                 $regency = RegRegency::find($agencyId);
                                 $set('reg_province_id', $regency ? $regency->province_id : null);
                            }
                        }),

                    Select::make('echelon_id')
                        ->label(__('labels.form.client.fields.echelon'))
                        ->options(fn (Get $get) =>
                            RegDepartmentEchelon1::query()
                                ->where('department_id', $get('agency_id'))
                                ->pluck('name', 'id')
                        )
                        ->required(fn (Get $get) => $get('type') === 'central')
                        ->hidden(fn (Get $get) => $get('type') !== 'central')
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            if ($get('type') === 'central') {
                                $echelon = RegDepartmentEchelon1::find($get('echelon_id'));
                                $set('reg_province_id', $echelon ? $echelon->reg_province_id : null);
                            }
                        }),

                    Hidden::make('reg_province_id'),

                    Select::make('echelon_x_text')
                        ->label(__('labels.form.client.fields.echelon'))
                        ->options(fn (Get $get) =>
                            RegProvinceEchelon1::query()
                                ->where('reg_province_id', $get('agency_id'))
                                ->pluck('name', 'id')
                        )
                        ->required(fn (Get $get) => $get('type') === 'local_province')
                        ->hidden(fn (Get $get) => $get('type') !== 'local_province'),

                    TextInput::make('echelon_x_text')
                        ->label(__('labels.form.client.fields.echelon'))
                        ->required(fn (Get $get) => $get('type') === 'local_regency')
                        ->hidden(fn (Get $get) => $get('type') !== 'local_regency'),
                ])->columns(2),

        ];
    }

    public static function getClientIdentityForm(): array
    {
        return [
            Group::make()
                ->schema([
                    TextInput::make('name')
                        ->label(__('labels.form.client.fields.name'))
                        ->formatStateUsing(fn(?Model $record) => $record == null ? auth()->user()->name : $record->name)
                        ->required(),

                    Group::make()
                        ->schema([
                            TextInput::make('title_prefix')
                                ->label('Gelar Depan')
                                ->hint('e.g: Dr., H.'),

                            TextInput::make('academic_title')
                                ->label(__('Gelar Belakang'))
                                ->hint('e.g: S.H, M.H')
                                ->required(),
                        ])
                        ->columns(2),
                    Group::make()
                        ->schema([
                            ToggleButtons::make('gender')
                                ->label(__('labels.form.client.fields.gender'))
                                ->options(Gender::class)
                                ->inline()
                                ->required(),
                            TextInput::make('phone_number')
                                ->label(__('labels.form.client.fields.phone_number'))
                                ->required(),
                        ])->columns(2),
                    Textarea::make('address')
                        ->label(__('labels.form.client.fields.address'))
                        ->required()
                        ->maxLength(250),
                    FileUpload::make('photo')
                        ->disk('s3')
                        ->label(__('labels.form.client.fields.photo'))
                        ->maxSize(config('fungsional-pro.max_media_file_size'))
                        ->acceptedFileTypes(config('fungsional-pro.accepted_media_type'))
                        ->directory('photos')
                        ->image()
                        ->visibility('private')
                        ->downloadable()
                        ->avatar()
                        ->required(),
                ])
                ->relationship('identity'),
        ];
    }

    public static function getClientEducationForm(): array
    {
        return [
            Group::make()
                ->schema([
                    Group::make()
                        ->schema([
                            Select::make('level')
                                ->label(__('labels.form.client.fields.education_level'))
                                ->options(EducationLevel::class)
                                ->required(),
                            TextInput::make('university_name')
                                ->label(__('labels.form.client.fields.university_name'))
                                ->required(),
                        ])
                        ->columns(2),
                    Group::make()
                        ->schema([
                            TextInput::make('program_name')
                                ->label(__('labels.form.client.fields.program_name'))
                                ->required(),
                            TextInput::make('academic_title')
                                ->label(__('labels.form.client.fields.academic_title'))
                                ->required(false)->hidden(true),
                        ])
                        ->columns(2),

                    Group::make()
                        ->schema([
                            TextInput::make('gpa')
                                ->label(__('labels.form.client.fields.gpa'))
                                ->numeric()
                                ->maxValue(4)
                                ->required(),
                            FileUpload::make('certificate')
                                ->disk('s3')
                                ->label(__('labels.form.client.fields.certificate'))
                                ->required()
                                ->maxFiles(1)
                                ->acceptedFileTypes(config('fungsional-pro.accepted_document_type'))
                                ->maxSize(config('fungsional-pro.max_upload_file_size'))
                                ->directory('education_certificate')
                                ->visibility('private')
                                ->downloadable()
                                ->helperText('Format file: PDF | Ukuran maksimal: 750 KB'),
                        ])->columns(2),
                ])
                ->relationship('education'),
        ];
    }

    public static function getDetailedClientForm(): array
    {
        return [
            Group::make()
                ->schema([
                    Section::make(__('labels.form.client.heading.client_employee_card_title'))
                        ->schema([
                            TextInput::make('employee_card')
                                ->label(__('labels.form.client.fields.employee_card')),
                            FileUpload::make('file_employee_card')
                                ->disk('s3')
                                ->label(__('labels.form.client.fields.file_employee_card'))
                                ->acceptedFileTypes(config('fungsional-pro.accepted_document_type'))
                                ->maxFiles(1)
                                ->visibility(static::storageVisibility())
                                ->directory('employee-cards')
                                ->downloadable()
                                ->maxSize(config('fungsional-pro.max_upload_file_size'))
                                ->helperText('Format file: PDF | Ukuran maksimal: 750 KB'),
                        ]),
                    Section::make(__('labels.form.client.heading.client_detail_cpns_pns'))
                        ->description(__('labels.form.client.heading.client_detail_cpns_pns_desc'))
                        ->schema([
                            DatePicker::make('sk_cpns_tmt')
                                ->label(__('labels.form.client.fields.tmt_cpns')),
                            FileUpload::make('sk_cpns_file')
                                ->disk('s3')
                                ->label(__('labels.form.client.fields.file_sk_cpns'))
                                ->acceptedFileTypes(config('fungsional-pro.accepted_document_type'))
                                ->maxFiles(1)
                                ->directory('sk-cpns')
                                ->visibility(static::storageVisibility())
                                ->downloadable()
                                ->maxSize(config('fungsional-pro.max_upload_file_size'))
                                ->helperText('Format file: PDF | Ukuran maksimal: 750 KB'),
                            DatePicker::make('sk_pns_tmt')
                                ->label(__('labels.form.client.fields.tmt_pns')),
                            FileUpload::make('sk_pns_file')
                                ->disk('s3')
                                ->label(__('labels.form.client.fields.file_sk_pns'))
                                ->visibility(static::storageVisibility())
                                ->directory('sk-pns')
                                ->downloadable()
                                ->acceptedFileTypes(config('fungsional-pro.accepted_document_type'))
                                ->maxFiles(1)
                                ->maxSize(config('fungsional-pro.max_upload_file_size'))
                                ->helperText('Format file: PDF | Ukuran maksimal: 750 KB'),
                        ]),
                    Section::make(__('labels.form.client.heading.client_detail_role'))
                        ->description(__('labels.form.client.heading.client_detail_role_desc'))
                        ->schema([
                            Group::make()
                                ->schema([
                                    DatePicker::make('sk_latest_jf_tmt')
                                        ->label(__('labels.form.client.fields.tmt_jf_latest')),
                                    TextInput::make('sk_latest_jf_no')
                                        ->label(__('labels.form.client.fields.latest_jf_no')),
                                ])->columns(2),
                            FileUpload::make('sk_latest_jf_file')
                                ->disk('s3')
                                ->label(__('labels.form.client.fields.file_sk_jf_latest'))
                                ->visibility(static::storageVisibility())
                                ->acceptedFileTypes(config('fungsional-pro.accepted_document_type'))
                                ->directory('sk-jf')
                                ->maxFiles(1)
                                ->maxSize(config('fungsional-pro.max_upload_file_size'))
                                ->helperText('Format file: PDF | Ukuran maksimal: 750 KB'),
                        ]),
                    Section::make(__('labels.form.client.heading.client_detail_grade'))
                        ->description(__('labels.form.client.heading.client_detail_grade_desc'))
                        ->schema([
                            Group::make()
                                ->schema([
                                    DatePicker::make('sk_latest_grade_tmt')
                                        ->label(__('labels.form.client.fields.tmt_grade_sk_latest')),
                                    TextInput::make('sk_latest_grade_no')
                                        ->label(__('labels.form.client.fields.grade_sk_latest_no')),
                                ])->columns(2),
                            FileUpload::make('sk_latest_grade_file')
                                ->disk('s3')
                                ->label(__('labels.form.client.fields.file_sk_grade_latest'))
                                ->visibility(static::storageVisibility())
                                ->acceptedFileTypes(config('fungsional-pro.accepted_document_type'))
                                ->directory('sk-grade')
                                ->maxFiles(1)
                                ->maxSize(config('fungsional-pro.max_upload_file_size'))
                                ->helperText('Format file: PDF | Ukuran maksimal: 750 KB'),
                        ]),
                ])->relationship('detail'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nip', 'identity.name'];
    }

    public static function canCreate(): bool
{
        return false;
    }


    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Nama' => $record->identity->name,
            'NIP' => $record->nip,
        ];
    }

    private static function storageVisibility(): string
    {
        return config('fungsional-pro.s3.visibility');
    }
}
