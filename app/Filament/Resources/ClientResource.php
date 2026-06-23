<?php

namespace App\Filament\Resources;

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
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $modelLabel = "Pejabat Fungsional";

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make()
                    ->schema([
                        Forms\Components\Tabs\Tab::make(__('labels.form.client.tab_info'))
                            ->schema([
                                Forms\Components\Section::make()
                                    ->heading(__('labels.form.client.heading.client_identity'))
                                    ->description(__('labels.form.client.heading.client_identity_description'))
                                    ->collapsible()
                                    ->schema([
                                        Forms\Components\Group::make()
                                            ->schema(static::getClientIdentityForm())
                                            ->columnSpan(5),
                                    ])->columnSpan(['lg' => fn(?Client $record) => $record === null ? 3 : 2]),
                                Forms\Components\Section::make()
                                    ->heading(__('labels.form.client.heading.client_education'))
                                    ->description(__('labels.form.client.heading.client_education_description'))
                                    ->collapsible()
                                    ->schema([
                                        Forms\Components\Group::make()
                                            ->schema(static::getClientEducationForm())
                                            ->columnSpan(5),
                                    ])->columnSpan(['lg' => fn(?Client $record) => $record === null ? 3 : 2]),
                                Forms\Components\Section::make()
                                    ->heading(__('labels.form.client.heading.client_employee_information'))
                                    ->description(__('labels.form.client.heading.client_employee_information_description'))
                                    ->collapsible()
                                    ->schema([
                                        Forms\Components\Group::make()
                                            ->schema(static::getClientBasicInformationForm(fn(Model $record) => $record))
                                            ->columnSpan(5),
                                    ])->columnSpan(['lg' => fn(?Client $record) => $record === null ? 3 : 2]),

                            ]),
                        Forms\Components\Tabs\Tab::make(__('labels.form.client.tab_file'))
                            ->schema(static::getDetailedClientForm()),
                        Forms\Components\Tabs\Tab::make('Riwayat Pendidikan')
                            ->schema([
                                Forms\Components\Livewire::make(ClientEducationInfolist::class)
                            ]),
                        Forms\Components\Tabs\Tab::make('Riwayat Diklat/Pelatihan')
                            ->schema([
                                Forms\Components\Livewire::make(ClientCompetenceInfolist::class)
                            ]),
                        Forms\Components\Tabs\Tab::make('Riwayat Kegiatan')
                            ->schema([
                                Forms\Components\Livewire::make(ClientActivityTable::class)
                            ]),
                        Forms\Components\Tabs\Tab::make(__('labels.form.client.tab_user'))
                            ->visible(fn () => auth()->user()->hasSystemRole(SystemRole::SuperAdmin))
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->searchable()
                                    ->required()
                                    ->relationship('user', 'name'),
                            ]),
                    ])->columnSpan(5),
            ]);
    }

    /**
     * @throws \Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('labels.table.client.id')),
                Tables\Columns\TextColumn::make('nip')
                    ->label(__('labels.table.client.nip'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('identity.name')
                    ->label(__('labels.table.client.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('crole.role_name')
                    ->label(__('labels.table.client.role'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('croleLevel.level')
                    ->label(__('labels.table.client.grade'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('point.point')
                    ->label(__('labels.table.client.point'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('labels.table.client.cluster'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('agenciable.name')
                    ->label(__('Instansi'))
                    ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('echelonable.name')
                    ->label(__('labels.table.client.echelon'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('labels.table.client.status'))
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('assignation_type')
                    ->label(__('labels.table.client.assignation_type'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('jenis_kepegawaian')
                    ->label('Jenis Kepegawaian')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('agency_id')
                    ->hidden(!static::canFilterRegional())
                    ->form([
                        Forms\Components\MorphToSelect::make('agenciable')
                            ->label(__('Instansi'))
                            ->types([
                                Forms\Components\MorphToSelect\Type::make(RegDepartment::class)
                                    ->titleAttribute('name'),
                                Forms\Components\MorphToSelect\Type::make(RegProvince::class)
                                    ->titleAttribute('name'),
                                Forms\Components\MorphToSelect\Type::make(RegRegency::class)
                                    ->titleAttribute('name'),
                            ]),
                    ])->query(function (Builder $query, array $data): Builder {

                        $agencyType = $data['agency_type'] ?? null;
                        $agencyId = $data['agency_id'] ?? null;

                        if (blank($agencyType) || blank($agencyId)) {
                            return $query;
                        }

                        return $query
                            ->where('agency_type', '=', $agencyType)
                            ->where('agency_id', '=', $agencyId);
                    }),
                Tables\Filters\SelectFilter::make('status')
                    ->options(ClientStatus::class),
                Tables\Filters\SelectFilter::make('c_role_id')
                    ->hidden(!static::canFilterClientRoles())
                    ->label(__('labels.table.client.role'))
                    ->options(fn () => CRole::query()->pluck('role_name', 'id')->toArray())
                    ->searchable()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('assignation_type')
                    ->options(CRoleAssignation::class),

            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'view' => Pages\ViewClient::route('/{record}'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): string
    {
        return __('labels.nav.client_management');
    }

    public static function getClientBasicInformationForm(?callable $callback): array
{
    return [
        Forms\Components\Group::make()
            ->schema([
                Forms\Components\TextInput::make('nip')
                    ->label(__('labels.form.client.fields.nip'))
                    ->unique(table: Client::class, ignorable: $callback)
                    ->required()
                    ->placeholder('19930101XXXXXXXXX')
                    ->minLength(18)
                    ->maxLength(18)
                    ->rules('regex:/^\d+$/'),

                Forms\Components\Select::make('reg_grade_id')
                    ->label(__('labels.form.client.fields.grade'))
                    ->relationship('grade', 'grade_code')
                    ->required(),
            ])->columns(2),

        Forms\Components\Group::make()
            ->schema([
                Forms\Components\Select::make('c_role_id')
                    ->label(__('labels.form.client.fields.crole_name'))
                    ->live()
                    ->relationship('crole', 'role_name', function (Builder $query) {
                        $query->where('active', true);
                    })
                    ->required(),

                Forms\Components\Select::make('c_role_level_id')
                    ->label(__('labels.form.client.fields.crole_grade'))
                    ->relationship(
                        'croleLevel',
                        'level',
                        fn (Builder $query, Forms\Get $get) =>
                            $query->where('c_role_id', $get('c_role_id') ?: 0)
                    )
                    ->required(),
            ])->columns(2),

        Forms\Components\Group::make()
            ->schema([
                Forms\Components\Select::make('type')
                    ->label(__('labels.form.client.fields.client_cluster'))
                    ->options(ClientCluster::class)
                    ->live()
                    ->afterStateUpdated(function (?string $state, Forms\Set $set) {
                        $set('agency_id', null);
                        $set('echelon_id', null);
                        $set('echelon_x_text', null);

                        if ($state === 'central') {
                            $set('echelon_type', RegDepartmentEchelon1::class);
                        } elseif ($state === 'local_province') {
                            $set('echelon_type', RegProvince::class);
                        } elseif ($state === 'local_regency') {
                            $set('echelon_type', RegRegency::class);
                        }
                    })
                    ->required(),

                Forms\Components\Select::make('status')
                    ->label(__('labels.form.client.fields.status'))
                    ->options(ClientStatus::class)
                    ->required(),
            ])->columns(2),

        Forms\Components\Group::make()
            ->schema([
                Forms\Components\Select::make('assignation_type')
                    ->options(CRoleAssignation::class)
                    ->label(__('labels.form.client.fields.assignation_type'))
                    ->required()
                    ->live() // Makes the component reactive when changed
                    ->afterStateUpdated(function (?string $state, Forms\Set $set) {
                        if (blank($state)) {
                            $set('jenis_kepegawaian', null);
                        } elseif ($state !== 'cpns') {
                            $set('jenis_kepegawaian', 'PNS');
                        }
                    }),

                Forms\Components\Select::make('jenis_kepegawaian')
                    ->label('Jenis Kepegawaian')
                    ->options([
                        'PNS' => 'PNS',
                        'PPPK' => 'PPPK',
                    ])
                    ->required()
                    ->hidden(fn (Forms\Get $get) => blank($get('assignation_type'))),
            ])->columns(2),

        Forms\Components\Group::make()
            ->schema([
                Forms\Components\Select::make('agency_id')
                    ->label(__('labels.form.client.fields.agency'))
                    ->live()
                    ->searchable()
                    ->options(function (Forms\Get $get) {
                        return match ($get('type')) {
                            'central' => RegDepartment::query()->pluck('name', 'id'),
                            'local_province' => RegProvince::query()->pluck('name', 'id'),
                            'local_regency' => RegRegency::query()->pluck('name', 'id'),
                            default => [],
                        };
                    })
                    ->required(),

                Forms\Components\Select::make('echelon_id')
                    ->label(__('labels.form.client.fields.echelon'))
                    ->options(fn (Forms\Get $get) =>
                        RegDepartmentEchelon1::query()
                            ->where('department_id', $get('agency_id'))
                            ->pluck('name', 'id')
                    )
                    ->required(fn (Forms\Get $get) => $get('type') === 'central')
                    ->hidden(fn (Forms\Get $get) => $get('type') !== 'central'),

                Forms\Components\Select::make('echelon_x_text')
                    ->label(__('labels.form.client.fields.echelon'))
                    ->options(fn (Forms\Get $get) =>
                        RegProvinceEchelon1::query()
                            ->where('reg_province_id', $get('agency_id'))
                            ->pluck('name', 'id')
                    )
                    ->required(fn (Forms\Get $get) => $get('type') === 'local_province')
                    ->hidden(fn (Forms\Get $get) => $get('type') !== 'local_province'),
            ])->columns(2),
    ];
}

    public static function getClientIdentityForm(): array
    {
        return [
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('labels.form.client.fields.name'))
                        ->formatStateUsing(fn(?Model $record) => $record == null ? auth()->user()->name : $record->name)
                        ->required(),

                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\TextInput::make('title_prefix')
                                ->label('Gelar Depan')
                                ->hint('e.g: Dr., H.'),

                            Forms\Components\TextInput::make('academic_title')
                                ->label(__('labels.form.client.fields.academic_title'))
                                ->hint('e.g: S.H, M.H')
                                ->required(),
                        ])
                        ->columns(2),
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\ToggleButtons::make('gender')
                                ->label(__('labels.form.client.fields.gender'))
                                ->options(Gender::class)
                                ->inline()
                                ->required(),
                            Forms\Components\TextInput::make('phone_number')
                                ->label(__('labels.form.client.fields.phone_number'))
                                ->required(),
                        ])->columns(2),
                    Forms\Components\Textarea::make('address')
                        ->label(__('labels.form.client.fields.address'))
                        ->required()
                        ->maxLength(250),
                    Forms\Components\FileUpload::make('photo')
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
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\Select::make('level')
                                ->label(__('labels.form.client.fields.education_level'))
                                ->options(EducationLevel::class)
                                ->required(),
                            Forms\Components\TextInput::make('university_name')
                                ->label(__('labels.form.client.fields.university_name'))
                                ->required(),
                        ])
                        ->columns(2),
                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\TextInput::make('program_name')
                                ->label(__('labels.form.client.fields.program_name'))
                                ->required(),
                            Forms\Components\TextInput::make('academic_title')
                                ->label(__('labels.form.client.fields.academic_title'))
                                ->required(false)->hidden(true),
                        ])
                        ->columns(2),

                    Forms\Components\Group::make()
                        ->schema([
                            Forms\Components\TextInput::make('gpa')
                                ->label(__('labels.form.client.fields.gpa'))
                                ->numeric()
                                ->maxValue(4)
                                ->required(),
                            Forms\Components\FileUpload::make('certificate')
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
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make(__('labels.form.client.heading.client_employee_card_title'))
                        ->schema([
                            Forms\Components\TextInput::make('employee_card')
                                ->label(__('labels.form.client.fields.employee_card')),
                            Forms\Components\FileUpload::make('file_employee_card')
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
                    Forms\Components\Section::make(__('labels.form.client.heading.client_detail_cpns_pns'))
                        ->description(__('labels.form.client.heading.client_detail_cpns_pns_desc'))
                        ->schema([
                            Forms\Components\DatePicker::make('sk_cpns_tmt')
                                ->label(__('labels.form.client.fields.tmt_cpns')),
                            Forms\Components\FileUpload::make('sk_cpns_file')
                                ->disk('s3')
                                ->label(__('labels.form.client.fields.file_sk_cpns'))
                                ->acceptedFileTypes(config('fungsional-pro.accepted_document_type'))
                                ->maxFiles(1)
                                ->directory('sk-cpns')
                                ->visibility(static::storageVisibility())
                                ->downloadable()
                                ->maxSize(config('fungsional-pro.max_upload_file_size'))
                                ->helperText('Format file: PDF | Ukuran maksimal: 750 KB'),
                            Forms\Components\DatePicker::make('sk_pns_tmt')
                                ->label(__('labels.form.client.fields.tmt_pns')),
                            Forms\Components\FileUpload::make('sk_pns_file')
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
                    Forms\Components\Section::make(__('labels.form.client.heading.client_detail_role'))
                        ->description(__('labels.form.client.heading.client_detail_role_desc'))
                        ->schema([
                            Forms\Components\Group::make()
                                ->schema([
                                    Forms\Components\DatePicker::make('sk_latest_jf_tmt')
                                        ->label(__('labels.form.client.fields.tmt_jf_latest')),
                                    Forms\Components\TextInput::make('sk_latest_jf_no')
                                        ->label(__('labels.form.client.fields.latest_jf_no')),
                                ])->columns(2),
                            Forms\Components\FileUpload::make('sk_latest_jf_file')
                                ->disk('s3')
                                ->label(__('labels.form.client.fields.file_sk_jf_latest'))
                                ->visibility(static::storageVisibility())
                                ->acceptedFileTypes(config('fungsional-pro.accepted_document_type'))
                                ->directory('sk-jf')
                                ->maxFiles(1)
                                ->maxSize(config('fungsional-pro.max_upload_file_size'))
                                ->helperText('Format file: PDF | Ukuran maksimal: 750 KB'),
                        ]),
                    Forms\Components\Section::make(__('labels.form.client.heading.client_detail_grade'))
                        ->description(__('labels.form.client.heading.client_detail_grade_desc'))
                        ->schema([
                            Forms\Components\Group::make()
                                ->schema([
                                    Forms\Components\DatePicker::make('sk_latest_grade_tmt')
                                        ->label(__('labels.form.client.fields.tmt_grade_sk_latest')),
                                    Forms\Components\TextInput::make('sk_latest_grade_no')
                                        ->label(__('labels.form.client.fields.grade_sk_latest_no')),
                                ])->columns(2),
                            Forms\Components\FileUpload::make('sk_latest_grade_file')
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
